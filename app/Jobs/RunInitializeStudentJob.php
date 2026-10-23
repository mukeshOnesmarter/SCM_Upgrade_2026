<?php

namespace App\Jobs;

use App\Models\Simulation;
use App\Models\SimulationTeamMember;
use App\Services\InitializeStudentService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunInitializeStudentJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $simulationId;

    public int $teamMemberId;

    public function __construct(int $simulationId, int $teamMemberId)
    {
        $this->simulationId = $simulationId;
        $this->teamMemberId = $teamMemberId;
    }

    public function handle(InitializeStudentService $initService)
    {
        $member = SimulationTeamMember::find($this->teamMemberId);

        if (! $member) {
            Log::warning('RunInitializeStudentJob: member not found', [
                'team_member_id' => $this->teamMemberId,
                'simulation_id' => $this->simulationId,
            ]);

            return;
        }

        // Idempotency guard: if initial setup already done, skip
        if ($member->initial_setup_done) {
            Log::info('RunInitializeStudentJob: initial setup already done, skipping', [
                'team_member_id' => $this->teamMemberId,
                'simulation_id' => $this->simulationId,
            ]);

            return;
        }

        if ((int) $member->simulation_id !== (int) $this->simulationId) {
            Log::warning('RunInitializeStudentJob: member simulation mismatch', [
                'team_member_id' => $this->teamMemberId,
                'member_simulation_id' => $member->simulation_id,
                'expected_simulation_id' => $this->simulationId,
            ]);

            return;
        }

        // Fetch Simulation Once:
        $simulation = Simulation::select('id', 'is_analysis_enabled')
            ->find($this->simulationId);

        if (! $simulation) {
            Log::warning('RunInitializeStudentJob: simulation not found', [
                'simulation_id' => $this->simulationId,
            ]);

            return;
        }

        // gather context to pass to the service methods
        $facultyId = (int) $member->faculty_id;
        $groupId = (int) $member->simulation_team_id;
        $studentId = (int) $member->student_id;

        try {
            // Call each InitializeStudentService method with the required params
            $initService->setupForecasting($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupDistribution($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupTransportation($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupServiceManagement($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupDemandPlanning($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupInformationTechnology($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupDemandElasticity($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupManufacturing($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupProcurement($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->setupFinancialSnapshot($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->storeEndingInventoryFinishGood($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->storeEndingInventory($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->storeScorecard($this->simulationId, $facultyId, $groupId, $studentId);
            $initService->exportFinancialReports($this->simulationId, $facultyId, $groupId, $studentId);

            // Only run analysis if enabled
            if ($simulation->is_analysis_enabled) {
                $initService->analysisReport($this->simulationId, $facultyId, $groupId, $studentId);
            }

            // All done — mark initial setup complete
            $member->initial_setup_done = true;
            $member->initial_setup_at = now();
            $member->save();

            Log::info('RunInitializeStudentJob: Completed and initial_setup marked', [
                'Group ' => $groupId,
                'simulation_id' => $this->simulationId,
                'initial_setup_at' => $member->initial_setup_at,
            ]);
        } catch (Throwable $e) {
            Log::error('RunInitializeStudentJob failed', [
                'team_member_id' => $groupId,
                'simulation_id' => $this->simulationId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // allow queue to retry / move to failed_jobs
        }
    }
}
