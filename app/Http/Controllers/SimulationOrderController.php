<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulationOrderRequest;
use App\Models\Decision;
use App\Models\Faculty;
use App\Models\SimulationOrder;
use App\Models\SimulationOrderQuarter;
use App\Models\SimulationOrderQuarterDecision;
use App\Services\ActiveUserService;
use App\Services\MailjetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimulationOrderController extends Controller
{
    protected $activeUserService;

    protected $mailjetService;

    public function __construct(
        ActiveUserService $activeUserService,
        MailjetService $mailjetService
    ) {
        $this->activeUserService = $activeUserService;
        $this->mailjetService = $mailjetService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('admin.simulation-order.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.simulation-order.create', [
            'faculties' => Faculty::get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreSimulationOrderRequest $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();
        $faculty_id = $activeUser->id;

        $data = $request->validated();

        $facultyIdInput = (string) $request->input('faculty_id');

        $resolvedFacultyId = null;
        $resolvedFacultyName = null;
        $resolvedFacultyEmail = null;

        if ($facultyIdInput !== '' && $facultyIdInput !== '0') {
            // existing faculty selected: fetch from DB and snapshot name/email
            $faculty = Faculty::find($facultyIdInput);
            if ($faculty) {
                $resolvedFacultyId = $faculty->id;
                $resolvedFacultyName = trim(($faculty->first_name ?? '').' '.($faculty->last_name ?? ''));
                $resolvedFacultyEmail = $faculty->email ?? null;
            }
        } elseif ($facultyIdInput === '0') {
            // Other chosen: user provided unique faculty_name & faculty_email (validated to NOT exist)
            $resolvedFacultyName = $data['faculty_name'];
            $resolvedFacultyEmail = $data['faculty_email'];
        }

        $persist = [
            'course_title' => $data['course_title'],
            'organization' => $data['organization'],
            'faculty_id' => $resolvedFacultyId,
            'faculty_name' => $resolvedFacultyName,
            'faculty_email' => $resolvedFacultyEmail,
            'start_date' => $data['start_date'],
            'quarters' => $data['quarters'],
            'teams' => $data['teams'],
            'learners' => $data['learners'],
            'fee_per_learner' => number_format((float) $data['fee_per_learner'], 2, '.', ''),
            'payment_responsibility' => $data['payment_responsibility'],
            'time_zone' => $data['time_zone'],
            'special_instructions' => $data['special_instructions'] ?? null,
            'settings' => $data['settings'] ?? null,
            'status' => 'draft',
            'created_by' => $faculty_id,
        ];

        DB::beginTransaction();
        try {

            $activeUser = $this->activeUserService->getActiveUser();
            $faculty_id = $activeUser->id;

            $order = SimulationOrder::create($persist);

            $startQuarter = 4; // as per your example; could be a param or order setting
            $this->createQuartersFromOrder($order, $startQuarter);

            DB::commit();

            return redirect()->route('admin.simulation-order.assign-decisions', $order->id)
                ->with('success', 'Simulation order created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['general' => 'Unable to create simulation order.']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SimulationOrder $simulationOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SimulationOrder $simulationOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, SimulationOrder $simulationOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SimulationOrder $simulationOrder)
    {
        //
    }

    // CUSTOME METHOD: 01
    public function showAssignDecisions(SimulationOrder $order)
    {
        // load quarters ordered by quarter_number
        $quarters = $order->orderQuarters()->orderBy('quarter_number')->get();

        // decisions and existing assignments
        $decisions = Decision::orderBy('id')->get();
        $assigned = SimulationOrderQuarterDecision::whereIn('simulation_order_quarter_id', $quarters->pluck('id'))
            ->get()
            ->groupBy('simulation_order_quarter_id')
            ->map(function ($rows) {
                return $rows->pluck('decision_id')->toArray();
            });

        $mandatoryDecisionIds = [1, 2, 3];

        return view('admin.simulation-order.assign-decisions', compact(
            'order',
            'quarters',
            'decisions',
            'assigned',
            'mandatoryDecisionIds'
        ));
    }

    // CUSTOME METHOD: 02
    public function saveQuarter(Request $request, SimulationOrder $order, SimulationOrderQuarter $quarter)
    {
        // ensure quarter belongs to order
        if ($quarter->simulation_order_id !== $order->id) {
            abort(404);
        }

        $data = $request->validate([
            'end_date' => 'required|date',
            'decisions' => 'nullable|array',
            'decisions.*' => 'integer|exists:decisions,id',
        ]);

        DB::beginTransaction();
        try {
            // update this quarter's end_date
            $endDate = Carbon::parse($data['end_date'])->startOfDay();
            $quarter->end_date = $endDate->toDateString();
            $quarter->save();

            // compute propagation for all following quarters
            $allQuarters = $order->orderQuarters()->orderBy('quarter_number')->get()->keyBy('quarter_number');

            $duration = max(1, (int) ($order->quarter_duration ?? 1));

            // start propagation from this quarter's end
            $currentEnd = $endDate->copy();
            $nextNumber = $quarter->quarter_number + 1;

            while ($allQuarters->has($nextNumber)) {
                $next = $allQuarters[$nextNumber];

                $nextStart = $currentEnd->copy()->addDay();
                $nextEnd = $nextStart->copy()->addDays($duration - 1);

                $next->start_date = $nextStart->toDateString();
                $next->end_date = $nextEnd->toDateString();
                $next->save();

                // advance
                $currentEnd = $nextEnd->copy();
                $nextNumber++;
            }

            // decision handling — pivot-as-model
            $globalMandatory = [1, 2, 3];
            $submitted = is_array($data['decisions'] ?? null) ? array_map('intval', $data['decisions']) : [];
            // ensure global mandatory present
            $submitted = array_unique(array_merge($submitted, $globalMandatory));

            // sync for current quarter: delete removed, insert missing
            SimulationOrderQuarterDecision::where('simulation_order_quarter_id', $quarter->id)
                ->whereNotIn('decision_id', $submitted)
                ->delete();

            $existing = SimulationOrderQuarterDecision::where('simulation_order_quarter_id', $quarter->id)
                ->whereIn('decision_id', $submitted)
                ->pluck('decision_id')
                ->toArray();

            $toInsert = array_diff($submitted, $existing);
            foreach ($toInsert as $did) {
                SimulationOrderQuarterDecision::create([
                    'simulation_order_quarter_id' => $quarter->id,
                    'decision_id' => $did,
                ]);
            }

            // NEW: for any non-global decision selected now, ensure it exists on ALL subsequent quarters (becomes mandatory)
            $selectedNow = array_diff($submitted, $globalMandatory);
            if (! empty($selectedNow)) {
                // iterate subsequent quarters and insert missing links
                $nextNum = $quarter->quarter_number + 1;
                while ($allQuarters->has($nextNum)) {
                    $nextQ = $allQuarters[$nextNum];
                    $existingNext = SimulationOrderQuarterDecision::where('simulation_order_quarter_id', $nextQ->id)
                        ->whereIn('decision_id', $selectedNow)
                        ->pluck('decision_id')
                        ->toArray();

                    $toInsertNext = array_diff($selectedNow, $existingNext);
                    foreach ($toInsertNext as $did) {
                        SimulationOrderQuarterDecision::create([
                            'simulation_order_quarter_id' => $nextQ->id,
                            'decision_id' => $did,
                        ]);
                    }
                    $nextNum++;
                }
            }

            DB::commit();

            // redirect: last quarter -> show page, else back to assign-decisions
            $maxQuarter = $order->orderQuarters()->max('quarter_number') ?? $quarter->quarter_number;
            if ($quarter->quarter_number >= $maxQuarter) {
                return redirect()->route('admin.simulation-order.show', $order->id)
                    ->with('success', 'Quarter saved. Order completed.');
            }

            return redirect()->route('admin.simulation-order.assign-decisions', $order->id)
                ->with('success', 'Quarter saved. Continue to next quarter.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('saveQuarter failed: '.$e->getMessage());

            return redirect()->back()->withInput()->withErrors(['general' => 'Unable to save quarter.']);
        }
    }

    // CUSTOME METHOD: 03
    protected function createQuartersFromOrder(SimulationOrder $order, ?int $startQuarter = null, ?array $defaultDecisionIds = null)
    {
        // must have start_date and quarter_duration in days
        if (! $order->start_date || ! $order->quarters) {
            return [];
        }

        $created = [];
        $quarterCount = (int) $order->quarters;
        // default starting quarter number: if provided use that, otherwise start from 1
        $startNumber = $startQuarter !== null ? (int) $startQuarter : 1;

        // default decisions: if provided use that; otherwise pick first 3 decisions by id
        if ($defaultDecisionIds === null) {
            $defaultDecisionIds = Decision::orderBy('id')->limit(3)->pluck('id')->toArray();
        }

        // initial start date is the order->start_date
        $currentStart = $order->start_date instanceof Carbon ? $order->start_date->copy() : Carbon::parse($order->start_date);

        for ($i = 0; $i < $quarterCount; $i++) {
            $qNumber = $startNumber + $i;

            // compute end_date: start + (quarter_duration - 1) days
            $qStart = $currentStart->copy();
            $qEnd = $qStart->copy()->addDays(max(0, 4 - 1));

            // create quarter row
            $quarter = SimulationOrderQuarter::create([
                'simulation_order_id' => $order->id,
                'quarter_number' => $qNumber,
                'start_date' => $qStart->toDateString(),
                'end_date' => $qEnd->toDateString(),
                'status' => 'scheduled',
            ]);

            // attach default decisions
            if (! empty($defaultDecisionIds)) {
                // use pivot insert for speed
                $attachData = [];
                foreach ($defaultDecisionIds as $decisionId) {
                    $attachData[$decisionId] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                // attach through many-to-many
                $quarter->decisions()->attach($attachData);
            }

            $created[] = $quarter;

            // next quarter start is qEnd + 1 day
            $currentStart = $qEnd->copy()->addDay();
        }

        return $created;
    }
}
