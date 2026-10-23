<?php

namespace App\Http\Controllers;

use App\Jobs\QuarterClosing\DispatchGroupChainsJob;
use App\Jobs\QuarterClosureJob;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\ReminderNotification;
use App\Models\Simulation;
use App\Models\SimulationQuarterCustomize;
use App\Models\SimulationTeamMember;
use App\Services\EmergencyProcurementService;
use App\Services\InventoryStockService;
use App\Services\MailjetService;
use App\Services\QuarterClosingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CronJobController extends Controller
{
    /**
     * SERVICE INHERITED
     *
     * This service is designed to be inherited and reused across various parts
     * of the application, providing consistent and reusable logic.
     */
    protected $mailjetService;

    protected $quarterClosingService;

    protected $emergencyProcurementService;

    protected $inventoryStockService;

    public function __construct(
        MailjetService $mailjetService,
        QuarterClosingService $quarterClosingService,
        EmergencyProcurementService $emergencyProcurementService,
        InventoryStockService $inventoryStockService
    ) {
        $this->mailjetService = $mailjetService;
        $this->quarterClosingService = $quarterClosingService;
        $this->emergencyProcurementService = $emergencyProcurementService;
        $this->inventoryStockService = $inventoryStockService;
    }

    /**
     * Simulation Synchronization
     */
    public function simulationSynchronization()
    {
        // 1️⃣ Fetch only quarters ending today (in any timezone) and not yet synced
        //    We'll still filter by local time below, but this narrows DB work.
        $quartersEndingToday = SimulationQuarterCustomize::with('simulation')
            ->whereNull('closing_sync')
            ->whereNull('sync_date')
            ->get();

        if ($quartersEndingToday->isEmpty()) {
            Log::info('simulationSynchronization: no unsynced quarters found.');

            return;
        }

        // 2️⃣ Collect simulation IDs and eager-load only those simulations + their teams
        $simulationIds = $quartersEndingToday->pluck('simulation_id')->unique()->values()->all();

        $simulations = Simulation::with('simulationTeams')
            ->whereIn('id', $simulationIds)
            ->get()
            ->keyBy('id'); // key by ID for quick lookup

        // 3️⃣ Process each quarter individually
        foreach ($quartersEndingToday as $quarter) {
            $simulation = $simulations->get($quarter->simulation_id) ?? $quarter->simulation;
            if (! $simulation) {
                Log::warning("simulationSynchronization: quarter {$quarter->id} missing simulation; skipping.");

                continue;
            }

            // get simulation’s timezone (default if missing)
            $tz = $simulation->timezone ?? 'America/New_York';
            $nowLocal = Carbon::now($tz);
            $todayLocal = $nowLocal->format('Y-m-d');
            $timeLocal = $nowLocal->format('H:i:s');

            // ✅ Only proceed if it's the simulation's local end_date and past end_time
            if ($quarter->end_date != $todayLocal || $timeLocal < $quarter->end_time) {
                continue; // not yet time in local timezone
            }

            DB::beginTransaction();
            try {
                $faculty_id = $quarter->faculty_id;

                // ✅ Collect unique group numbers (same logic as quartersClosed)
                $groups = SimulationTeamMember::where([
                    'simulation_id' => $simulation->id,
                    'faculty_id' => $faculty_id,
                ])
                    ->whereNotNull('student_joined')
                    ->pluck('simulation_team_id')
                    ->unique()
                    ->values()
                    ->toArray();

                Bus::chain([
                    new QuarterClosureJob(
                        $simulation->id,
                        $faculty_id,
                        $quarter->quarter_name
                    ),
                    new DispatchGroupChainsJob(
                        $simulation->id,
                        $faculty_id,
                        $quarter->quarter_name,
                        $groups
                    ),
                ])->onQueue('quarter-closure')->dispatch();

                // ✅ Mark quarter as synced (UTC)
                $quarter->closing_sync = 'yes';
                $quarter->sync_date = Carbon::now('UTC');
                $quarter->save();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error during quarter {$quarter->id} sync ({$tz}): ".$e->getMessage());
            }
        }
    }

    /**
     * Sends decision-save reminders to students for quarters ending today.
     *
     * - Uses quarter->faculty_id for module checks that require it (eg. Forecasting).
     * - Builds three triggers in the quarter's timezone: end-day 10:00, 4h before end, 1h before end.
     * - Uses ReminderNotification to avoid duplicate sends.
     */
    public function simulationDecisionReminder()
    {
        // tolerance window (cron runs every minute)
        $windowSeconds = 60;

        // Fetch only quarters ending today and not yet synced/closed (same pre-filter approach)
        $quartersEndingToday = SimulationQuarterCustomize::with('simulation')
            ->whereNull('closing_sync')
            ->whereNull('sync_date')
            ->get();

        if ($quartersEndingToday->isEmpty()) {
            Log::info('simulationDecisionReminder: no candidate quarters found.');

            return;
        }

        // Collect simulation ids and eager-load
        $simulationIds = $quartersEndingToday->pluck('simulation_id')->unique()->values()->all();

        $simulations = Simulation::with('simulationTeams')
            ->whereIn('id', $simulationIds)
            ->get()
            ->keyBy('id');

        $nowUtc = Carbon::now('UTC');

        foreach ($quartersEndingToday as $quarter) {

            $simulation = $simulations->get($quarter->simulation_id) ?? $quarter->simulation;
            if (! $simulation) {
                Log::warning("simulationDecisionReminder: quarter {$quarter->id} missing simulation; skipping.");

                continue;
            }

            // Use simulation timezone (fallback if missing) — note: simulationSynchronization used simulation timezone
            $tz = $simulation->timezone ?? 'America/New_York';

            // quarter end details (ensure end_date and end_time exist)
            if (! $quarter->end_date || ! $quarter->end_time) {
                Log::warning("simulationDecisionReminder: quarter {$quarter->id} missing end_date/end_time; skipping.");

                continue;
            }

            // Local now for the simulation timezone
            $nowLocal = Carbon::now($tz);
            $todayLocal = $nowLocal->format('Y-m-d');

            // Only consider quarters that end today in their local timezone (same rule as sync)
            // Note: quarter->end_date expected in 'Y-m-d' format
            if ($quarter->end_date !== $todayLocal) {
                continue;
            }

            // Build quarter end time in local timezone
            $quarterEndLocal = Carbon::createFromFormat('Y-m-d H:i:s', $quarter->end_date.' '.$quarter->end_time, $tz);

            // Trigger times (in local tz)
            $endDay10amLocal = $quarterEndLocal->copy()->setTime(10, 0, 0);
            $fourHoursBeforeLocal = $quarterEndLocal->copy()->subHours(4);
            $oneHourBeforeLocal = $quarterEndLocal->copy()->subHour();

            $reminderTriggers = [
                'endday_10am' => $endDay10amLocal->copy()->setTimezone('UTC'),
                '4h_before' => $fourHoursBeforeLocal->copy()->setTimezone('UTC'),
                '1h_before' => $oneHourBeforeLocal->copy()->setTimezone('UTC'),
            ];

            foreach ($reminderTriggers as $reminderType => $triggerUtc) {
                // is it time (within window)?
                $diff = (int) $nowUtc->diffInSeconds($triggerUtc, true);
                if ($diff > $windowSeconds) {
                    continue;
                }

                // Prevent duplicate sends
                $already = ReminderNotification::where('simulation_id', $simulation->id)
                    ->where('quarter_id', $quarter->id)
                    ->where('reminder_type', $reminderType)
                    ->exists();

                if ($already) {
                    continue;
                }

                // For each team in the simulation, collect students and send reminders
                $teams = $simulation->simulationTeams ?? collect();

                foreach ($teams as $team) {
                    // collect students for this team similar to sync method
                    $students = SimulationTeamMember::with('student')
                        ->where([
                            'simulation_id' => $simulation->id,
                            'simulation_team_id' => $team->group_number,
                            'faculty_id' => $quarter->faculty_id,
                        ])
                        ->whereNotNull('student_joined')
                        ->get();

                    // send to each student
                    foreach ($students as $member) {
                        if (! $member->student) {
                            continue;
                        }

                        $facultyId = $quarter->faculty_id;
                        $groupNumber = $team->group_number;

                        [$saved, $notSaved] = $this->collectStudentDecisionStatusForReminder(
                            $simulation->id,
                            $quarter->quarter_name,
                            $facultyId,
                            $groupNumber,
                            $member->student->id
                        );

                        // Build email content (reuse quarter summary template pattern or use a dedicated reminder view)
                        $quarterNameLabel = 'Q'.$quarter->quarter_name;
                        $email = $member->student->email;
                        $subject = "Reminder: Decisions for {$quarterNameLabel} — Please save your inputs";

                        $tz = $simulation->timezone ?? 'Asia/Kolkata';
                        $closingAt = Carbon::createFromFormat('Y-m-d H:i:s', $quarter->end_date.' '.$quarter->end_time, $tz);

                        $html = view('emails.decision-reminder', compact(
                            'quarterNameLabel',
                            'saved',
                            'notSaved',
                            'simulation',
                            'member',
                            'team',
                            'closingAt'
                        ))->render();

                        // Use same mailjet service used in simulationSynchronization
                        try {
                            $this->mailjetService->send($email, $email, $subject, $html);
                            Log::info("Decision reminder ({$reminderType}) sent to {$email} for simulation {$simulation->id} quarter {$quarter->quarter_name} (tz: {$tz}).");
                        } catch (\Exception $e) {
                            Log::error("Decision reminder send failed to {$email}: ".$e->getMessage());
                        }
                    }
                }

                // mark reminder as sent
                ReminderNotification::create([
                    'simulation_id' => $simulation->id,
                    'quarter_id' => $quarter->id,
                    'reminder_type' => $reminderType,
                    'sent_at' => Carbon::now('UTC'),
                ]);
            }
        }
    }

    /**
     * Collect saved / not-saved decision items for a student/team for reminder.
     *
     * - $simulationQuarterName is the quarter number / name (as used in your other services).
     * - Forecasting check uses faculty_id + group_number (per your requirement).
     *
     * @param  int|string  $simulationQuarterName
     * @return array [savedArray, notSavedArray]
     */
    protected function collectStudentDecisionStatusForReminder(int $simulationId, $simulationQuarterName, int $facultyId, int $groupNumber, int $studentId): array
    {
        $saved = [];
        $notSaved = [];

        // helper runner
        $check = function (string $label, \Closure $existsFn) use (&$saved, &$notSaved) {
            try {
                $exists = (bool) $existsFn();
            } catch (\Throwable $e) {
                Log::warning("Decision check failed for {$label}: ".$e->getMessage());
                $exists = false;
            }

            if ($exists) {
                $saved[] = $label;
            } else {
                $notSaved[] = $label;
            }
        };

        // Forecasting
        $check('forecasting', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return Forecasting::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // Manufacturing
        $check('manufacturing', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return Manufacturing::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // Procurement (example): per-student
        $check('procurement', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return Procurement::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // Distribution (example)
        $check('distribution', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return Distribution::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // Transportation (example)
        $check('transportation', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return Transportation::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // demandPlanning
        $check('DemandPlanning', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return DemandPlanning::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // serviceManagement
        $check('ServiceManagement', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return ServiceManagement::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        // informationTechnology
        $check('InformationTechnology', function () use ($simulationId, $facultyId, $simulationQuarterName, $groupNumber) {
            return InformationTechnology::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $simulationQuarterName,
                'group_number' => $groupNumber,
            ])->exists();
        });

        return [$saved, $notSaved];
    }
}
