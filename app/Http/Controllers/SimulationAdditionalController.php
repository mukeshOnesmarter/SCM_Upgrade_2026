<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Models\DemandChannel;
use App\Models\Faculty;
use App\Models\Firm;
use App\Models\Industry;
use App\Models\IndustryInitialDemand;
use App\Models\Product;
use App\Models\QuarterActualDemand;
use App\Models\QuarterForecastDemand;
use App\Models\QuarterInitialDemand;
use App\Models\QuarterProbabilityDemand;
use App\Models\Region;
use App\Models\Simulation;
use App\Models\SimulationFaculty;
use App\Models\SimulationFirm;
use App\Models\SimulationQuarter;
use App\Services\ActiveUserService;
use App\Services\GeneralSettingsService;
use App\Services\GenerateDemandService;
use App\Services\MailjetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SimulationAdditionalController extends Controller
{
    /**
     * SERVICE INHERITED
     *
     * This service is designed to be inherited and reused across various parts
     * of the application, providing consistent and reusable logic.
     */
    protected $generalSettingsService;

    protected $generateDemandService;

    protected $mailjetService;

    protected $activeUserService;

    public function __construct(
        GeneralSettingsService $generalSettingsService,
        GenerateDemandService $generateDemandService,
        MailjetService $mailjetService,
        ActiveUserService $activeUserService
    ) {
        $this->generalSettingsService = $generalSettingsService;
        $this->generateDemandService = $generateDemandService;
        $this->mailjetService = $mailjetService;
        $this->activeUserService = $activeUserService;
    }

    /**
     * Retrieve and format basic course/simulation details used by other actions.
     */
    public function courseDetail(int $courses_id)
    {
        $course = Simulation::where('id', $courses_id)->first();
        // STEP 01 (A): FORMAT THE DATA..
        $course->start_date = Carbon::parse($course->start_date)->format('m-d-Y');
        $course->end_date = Carbon::parse($course->end_date)->format('m-d-Y');
        // STEP 01 (B):  CALCULATE THE NUMBER OF DAYS BETWEEN START DATE AND END DATE..
        $startDate = Carbon::createFromFormat('m-d-Y', $course->start_date);
        $endDate = Carbon::createFromFormat('m-d-Y', $course->end_date);
        // STEP 01 (C):  GET THE DIFFERENCE IN DAYS..
        $course->daysDifference = (int) $startDate->diffInDays($endDate, true);

        return $course;
    }

    /**
     * Display and manage scheduling details for all quarters in a simulation.
     */
    public function manageQuarter($courses_id)
    {
        // CHECK 01: IF THE SIMULATION / COURSE EXISTS.
        if (! Simulation::where('id', $courses_id)->exists()) {
            // >> RETURN TO SIMULATION / COURSE LISTING PAGE
            return redirect()->route('admin.simulation.index');
        }

        // CHECK 02: IF THE SIMULATION / COURSE QUARTER EXISTS.
        if (! SimulationQuarter::where('simulation_id', $courses_id)->exists()) {
            // >> RETURN TO SIMULATION / COURSE LISTING PAGE.
            return redirect()->route('admin.simulation.index');
        }

        // STEP 01: GET THE SIMULATION / COURSE INFO.
        $course = $this->courseDetail($courses_id);

        // STEP 02: GET THE SIMULATION / COURSE QUARTER INFO.
        $quarters = SimulationQuarter::where('simulation_id', $courses_id)->get();
        // STEP 02 (A): FORMAT THE RECORDS..
        $quarters->each(function ($quarter) {
            $quarter->start_date = Carbon::parse($quarter->start_date)->format('m-d-Y');
            $quarter->end_date = Carbon::parse($quarter->end_date)->format('m-d-Y');
            $quarter->start_time = Carbon::parse($quarter->start_time)->format('h:i A');
            $quarter->end_time = Carbon::parse($quarter->end_time)->format('h:i A');
        });

        // STEP 03: ATTACH THIS DECISIONS TO THE QUARTERS COLLECTION.
        foreach ($quarters as $quarter) {
            if ($quarter->decisions == 'all') {
                $quarter->decisions = Decision::all();
            } else {
                $decision_Ids = explode(',', $quarter->decisions);
                $quarter->decisions = Decision::whereIn('id', $decision_Ids)->get();
            }
        }

        return view('admin.simulation.manage-quarter', compact('course', 'quarters'));
    }

    /**
     * Save scheduling updates (dates/times) for single or multiple quarters.
     */
    public function saveQuarterSchedule(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();

        if ($request->updation_type === 'single') {

            DB::beginTransaction();

            try {

                $simulationId = (int) $request->simulation_id;

                // 1️⃣ Get all quarters
                $quarters = SimulationQuarter::where('simulation_id', $simulationId)
                    ->orderBy('quarter_name')
                    ->get();

                // 2️⃣ Calculate daysBetween from Quarter 1
                $quarterOne = $quarters->firstWhere('quarter_name', 1);
                if (! $quarterOne) {
                    throw new \Exception('Quarter 1 not found');
                }

                $daysBetween = (int) Carbon::parse($quarterOne->start_date)
                    ->diffInDays(Carbon::parse($quarterOne->end_date), true);

                // 3️⃣ Update selected quarter
                $currentQuarter = $quarters->firstWhere('id', (int) $request->quarter_id);
                if (! $currentQuarter) {
                    throw new \Exception('Quarter not found');
                }

                $currentQuarter->update([
                    'start_date' => Carbon::createFromFormat('m-d-Y', $request->start_date)->format('Y-m-d'),
                    'end_date' => Carbon::createFromFormat('m-d-Y', $request->end_date)->format('Y-m-d'),
                    'is_schedule' => 'Yes',
                    'start_time' => $currentQuarter->start_time ?? '00:00:00',
                    'end_time' => $currentQuarter->end_time ?? '23:59:59',
                    'close_manualy' => null,
                ]);

                // 4️⃣ Cascade future quarters
                $nextStartDate = Carbon::parse($currentQuarter->end_date)->addDay();

                $futureQuarters = $quarters
                    ->filter(fn ($q) => $q->quarter_name > $currentQuarter->quarter_name)
                    ->values();

                foreach ($futureQuarters as $quarter) {

                    $startDate = $nextStartDate->copy();
                    $endDate = $startDate->copy()->addDays($daysBetween);

                    $quarter->update([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'is_schedule' => 'Yes',
                        'close_manualy' => null,
                    ]);

                    $nextStartDate = $endDate->copy()->addDay();
                }

                // 5️⃣ Update simulation end_date from last quarter
                $lastQuarterEndDate = SimulationQuarter::where('simulation_id', $simulationId)
                    ->max('end_date');

                if ($lastQuarterEndDate) {
                    Simulation::where('id', $simulationId)->update([
                        'end_date' => $lastQuarterEndDate,
                    ]);
                }

                DB::commit();

                return redirect()
                    ->route('admin.courses.manageQuarter', $simulationId)
                    ->with('success', 'Quarter updated and future quarters adjusted successfully.');
            } catch (\Throwable $e) {
                report($e);
                DB::rollBack();

                return redirect()
                    ->route('admin.courses.manageQuarter', $request->simulation_id)
                    ->with('error', 'Failed to update quarter schedule.');
            }
        } else {
            // OPTION (2): PERFORM BULK ACTIONN (QUERY EXECUTION):-
            $updatedRows = SimulationQuarter::where([
                'simulation_id' => $request->simulation_id,
                'is_schedule' => 'No',
            ])
                ->update([
                    'is_schedule' => 'Yes',
                ]);

            // CHECK THE UPDATED ROWS AND REDIRECT BASED ON THE CONDITION.
            if ($updatedRows > 0) {
                return redirect()->route('admin.courses.quarterDecisions', $request->courses_id)
                    ->with('success', 'Quarter schedule saved successfully.');
            } else {
                // REDIRECT IF NO ROWS WERE UPDATED.
                return redirect()->route('admin.courses.manageQuarter', $request->simulation_id)
                    ->with('error', 'Failed to update the record.');
            }
        }
    }

    /**
     * Display the decision-selection page for each accessible quarter.
     */
    public function quarterDecisions($courses_id)
    {
        if (! Simulation::where('id', $courses_id)->exists()) {
            return redirect()->route('admin.simulation.index');
        }

        if (! SimulationQuarter::where('simulation_id', $courses_id)->exists()) {
            return redirect()->route('admin.simulation.index');
        }

        $course = $this->courseDetail($courses_id);

        $quarters = SimulationQuarter::where('simulation_id', $courses_id)
            ->orderBy('id')
            ->get();

        // find the highest quarter_name (last quarter)
        $lastQuarterNumber = $quarters->max('quarter_name');

        // prepare decisions_array for pre-checking (same as earlier)
        $quarters->each(function ($q) {
            $raw = $q->decisions;

            if ($raw === 'all') {
                $q->decisions_array = Decision::pluck('id')->map(fn ($id) => (int) $id)->toArray();

                return;
            }

            if (is_string($raw) && strlen($raw) && ($json = json_decode($raw, true)) !== null && json_last_error() === JSON_ERROR_NONE) {
                $ids = [];
                foreach ($json as $item) {
                    if (is_array($item) && isset($item['id'])) {
                        $ids[] = (int) $item['id'];
                    } elseif (is_numeric($item)) {
                        $ids[] = (int) $item;
                    }
                }
                $q->decisions_array = array_values(array_unique($ids));

                return;
            }

            if (is_string($raw) && strlen($raw)) {
                $ids = array_filter(array_map('trim', explode(',', $raw)));
                $q->decisions_array = array_map('intval', $ids);

                return;
            }

            $q->decisions_array = [];
        });

        $decisions = Decision::get();

        return view('admin.simulation.quarter-decisions', compact('course', 'quarters', 'decisions', 'lastQuarterNumber'));
    }

    /**
     * Save updated decision selections for each quarter and proceed with demand/faculty flows.
     */
    public function saveQuarterDecisions(Request $request)
    {
        $activeUser = $this->activeUserService->getActiveUser();

        $payload = $request->input('decisions', []);
        if (! is_array($payload)) {
            return redirect()->back()->with('error', 'Invalid submission format.');
        }

        // compute last quarter number for this simulation
        $lastQuarterNumber = SimulationQuarter::where('simulation_id', $request->simulation_id)->max('quarter_name');

        DB::beginTransaction();
        try {
            foreach ($payload as $quarter_db_id => $ids) {
                $quarter = SimulationQuarter::where('id', $quarter_db_id)
                    ->where('simulation_id', $request->simulation_id)
                    ->first();

                if (! $quarter) {
                    continue;
                }

                // SERVER SAFETY: Do not update first 3 quarters or the last quarter at all
                $qn = (int) $quarter->quarter_name;
                if ($qn <= 3 || $qn === (int) $lastQuarterNumber) {
                    // deliberately skip any update for these quarters
                    continue;
                }

                // sanitize selected ids and ensure mandatory present
                $selected = array_values(array_unique(array_map('intval', (array) $ids)));
                $mandatory = [1, 2, 3]; // verify these IDs match your Decision table
                $selected = array_unique(array_merge($mandatory, $selected));

                // store as CSV for backwards compatibility
                $quarter->decisions = implode(',', $selected);
                $quarter->save();
            }

            DB::commit();

            if ($activeUser->role === 'super-admin') {
                return redirect()->route('admin.courses.industryInitialDemand', $request->simulation_id)
                    ->with('success', 'Quarter decisions saved successfully.');
            }

            // auto-generate initial demand for non super-admins (GDP default 5)
            $this->generateInitialDemand(new Request([
                'simulation_id' => $request->simulation_id,
                'gdp_percentage' => 5,
            ]));

            return redirect()->route('admin.courses.selectFaculty', $request->simulation_id)
                ->with('success', 'Quarter decisions saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save quarter decisions: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to save quarter decisions.');
        }
    }

    /**
     * Delete a specific quarter and reorder remaining quarter numbers.
     */
    public function removeQuarter($simulation_id, $quarter_id)
    {
        // Find the quarter record for the given simulation and quarter ID
        $quarter = SimulationQuarter::where('id', $quarter_id)
            ->where('simulation_id', $simulation_id)
            ->first();

        if ($quarter) {
            // Delete the record if found
            $quarter->delete();

            // Reorder remaining quarters
            $quarters = SimulationQuarter::where('simulation_id', $simulation_id)
                ->orderBy('id') // or order by start_date if more appropriate
                ->get();

            $counter = 1;
            foreach ($quarters as $qtr) {
                $qtr->quarter_name = $counter;
                $qtr->save();
                $counter++;
            }

            return redirect()
                ->route('admin.courses.manageQuarter', $simulation_id)
                ->with('success', 'Quarter deleted successfully.');
        }

        // If no record was found, return with an error
        return redirect()
            ->route('admin.courses.manageQuarter', $simulation_id)
            ->with('error', 'Quarter not found.');
    }

    /**
     * Display industry-level base demand data before generating quarterly demand.
     */
    public function industryInitialDemand($courses_id)
    {
        // CHECK CONDITION 01: IF THE SIMULATION / COURSE EXISTS
        if (! Simulation::where('id', $courses_id)->exists()) {
            // RETURN TO SIMULATION / COURSE LISTING PAGE
            return redirect()->route('admin.simulation.index');
        }

        // CHECK CONDITION 02: IF BASE DEMAND IS SET UP FOR THE SIMULATION / COURSE
        if (QuarterForecastDemand::where('simulation_id', $courses_id)->exists()) {
            // >> RETURN TO SIMULATION DETAIL PAGE.
            return redirect()->route('admin.simulation.show', $courses_id);
        }

        // STEP 01: GET THE SIMULATION / COURSE INFO
        $course = $course = $this->courseDetail($courses_id);

        // STEP 02: INITIALDEMANDS
        $initialDemands = Region::select('id', 'name', 'custome_name', 'description')->get();
        foreach ($initialDemands as $key => $initialDemand) {
            $initialDemand->products = Product::select('id', 'name', 'description')->get();
            foreach ($initialDemand->products as $key => $products) {
                $products->demand_channels = DemandChannel::select('id', 'title')->get();
                foreach ($products->demand_channels as $key => $channel) {
                    if ($channel->id == 1) {
                        $channel->demand = IndustryInitialDemand::where([
                            'region_id' => $initialDemand->id,
                            'product_id' => $products->id,
                        ])->select('demand_channel_01')->first();
                    } else {
                        $channel->demand = IndustryInitialDemand::where([
                            'region_id' => $initialDemand->id,
                            'product_id' => $products->id,
                        ])->select('demand_channel_02')->first();
                    }
                }
            }
        }

        return view('admin.simulation.initial-demand', compact('course', 'initialDemands'));
    }

    /**
     * Generate initial, probability, actual, and forecast demand records for default quarters.
     */
    public function generateInitialDemand(Request $request)
    {
        $courses_id = $request->simulation_id;
        $gdp_percentage = $request->gdp_percentage;

        // CHECK CONDITION 01: IF THE SIMULATION / COURSE EXISTS.
        if (! Simulation::where('id', $courses_id)->exists()) {
            // RETURN TO SIMULATION / COURSE LISTING PAGE.
            return redirect()->route('admin.simulation.index');
        }

        // CHECK CONDITION 02: CHECK IF THE DEMAND ALREADY GENERATED
        if (QuarterForecastDemand::where('simulation_id', $courses_id)->exists()) {
            // DELETE IF THE RECORD EXIST.
            QuarterInitialDemand::where('simulation_id', $courses_id)->delete();
            QuarterProbabilityDemand::where('simulation_id', $courses_id)->delete();
            QuarterActualDemand::where('simulation_id', $courses_id)->delete();
            QuarterForecastDemand::where('simulation_id', $courses_id)->delete();
        }

        // VALIDATION
        $request->validate([
            'gdp_percentage' => 'required|integer|between:2,10',
        ], [
            'gdp_percentage.required' => 'The GDP percentage field is required.',
            'gdp_percentage.integer' => 'The GDP percentage must be a whole number.',
            'gdp_percentage.between' => 'The GDP percentage must be between 2 and 10.',
        ]);

        $gdp_percentage = $request->input('gdp_percentage');

        // STEP 1: CREATE RECORD FOR DEFAULT QUARTER(S) #123...
        $industry_id = 1;
        $simulation_id = $courses_id;
        $quarters_default_execute = [0, 1, 2, 3];

        // STEP 2: CHECK IF THE QUARTERS ARE AVAILALBE TO BE EXECUTE
        if (! empty($quarters_default_execute)) {
            // STEP 1 (A): LOOP TO QUARTERS TO CREATE INITIAL_DEMANDS RECORDS.
            foreach ($quarters_default_execute as $key => $quarter) {
                $quarter_id = $quarter; // *STORE*//
                // STEP 2: CHECK IF THE INDUSTRY EXISTS CREATING DEMAND.
                if (Industry::where('id', $industry_id)->exists()) {
                    // STEP 3: COLLECT THE PRODUCT ASSOCIATED WITH THE INDUSTRY.
                    $products = Product::where('industry_id', $industry_id)->select('id')->get();
                    if ($products->isNotEmpty()) {
                        foreach ($products as $key => $product) {
                            $product_id = $product->id; // *STORE*//
                            // STEP 4: COLLECT THE REGION FOR THE PRODUCT DEMAND.
                            $regions = Region::select('id')->get();
                            if ($regions->isNotEmpty()) {
                                foreach ($regions as $key => $region) {
                                    $region_id = $region->id; // *STORE*//
                                    // STEP 5: COLLECT DEMAND CHANNELS (RETAIL / DIRECT).
                                    $demand_channels = DemandChannel::select('id')->get();
                                    if ($demand_channels->isNotEmpty()) {
                                        // STEP 6: COLLECT FIRMS ASSOCIATED WITH THE SIMULATION.
                                        $firms = SimulationFirm::where(['simulation_id' => $simulation_id, 'status' => 1, 'deleted_at' => 0])->select('firm_id')->get();
                                        if ($firms->isNotEmpty()) {
                                            foreach ($firms as $key => $firm) {
                                                $firm_id = $firm->firm_id; // *STORE*//
                                                $demand_increment = Firm::whereKey($firm_id)->value('demand_increment');
                                                $firm_distribution = mt_rand(150, 200);

                                                if ($quarter == 0) {
                                                    // STEP 7: COLLECT DEMANDS (RETAIL / DIRECT) HISTORICAL DATA.
                                                    $collectDemand = IndustryInitialDemand::where(['industry_id' => $industry_id, 'region_id' => $region_id, 'product_id' => $product_id, 'quarters' => 0])->select('demand_channel_01', 'demand_channel_02')->first();
                                                    // STORE DEMAND CHANNEL 01(RETAIL):
                                                    $demand_channel_01 = $collectDemand->demand_channel_01;
                                                    $demand_channel_01 = $demand_channel_01 + $this->generateDemandService->growthPercentage($demand_channel_01, $gdp_percentage);
                                                    // STORE DEMAND CHANNEL 02(DIRECT):
                                                    $demand_channel_02 = $collectDemand->demand_channel_02;
                                                    $demand_channel_02 = $demand_channel_02 + $this->generateDemandService->growthPercentage($demand_channel_02, $gdp_percentage);
                                                } else {
                                                    // STEP 7: COLLECT DEMANDS (RETAIL / DIRECT) HISTORICAL DATA.
                                                    $collectDemand = QuarterForecastDemand::where(['region_id' => $region_id, 'product_id' => $product_id, 'quarter' => $quarter - 1, 'simulation_id' => $simulation_id])->select('demand_channel_01', 'demand_channel_02')->first();
                                                    // STORE DEMAND CHANNEL 01(RETAIL):
                                                    $demand_channel_01 = $collectDemand->demand_channel_01;
                                                    $demand_channel_01 = $demand_channel_01 + $this->generateDemandService->growthPercentage($demand_channel_01, $gdp_percentage);
                                                    // STORE DEMAND CHANNEL 02(DIRECT):
                                                    $demand_channel_02 = $collectDemand->demand_channel_02;
                                                    $demand_channel_02 = $demand_channel_02 + $this->generateDemandService->growthPercentage($demand_channel_02, $gdp_percentage);
                                                }

                                                // STEP 8: CREATE INITIAL DEMAND
                                                $initialDemandCreate = QuarterInitialDemand::create([
                                                    'simulation_id' => $simulation_id,
                                                    'product_id' => $product_id,
                                                    'region_id' => $region_id,
                                                    'firm_id' => $firm_id,
                                                    'quarter' => $quarter_id,
                                                    'demand_channel_01' => $demand_channel_01,
                                                    'demand_channel_02' => $demand_channel_02,
                                                ]);
                                                // STEP 9: CREATE PROBABILITYDEMAND
                                                $probabilityDemandCreate = QuarterProbabilityDemand::create([
                                                    'simulation_id' => $simulation_id,
                                                    'product_id' => $product_id,
                                                    'region_id' => $region_id,
                                                    'firm_id' => $firm_id,
                                                    'quarter' => $quarter_id,
                                                    'gdp_pct' => 2,
                                                    'demand_channel_01' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_01, $this->generateDemandService->randomNumber()),
                                                    'demand_channel_02' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_02, $this->generateDemandService->randomNumber()),
                                                ]);
                                                // STEP 10: CREATE ACTUAL DEMAND
                                                $actualDemandCreate = QuarterActualDemand::create([
                                                    'simulation_id' => $simulation_id,
                                                    'product_id' => $product_id,
                                                    'region_id' => $region_id,
                                                    'firm_id' => $firm_id,
                                                    'quarter' => $quarter_id,
                                                    'demand_channel_01' => $this->generateDemandService->actualDemand($probabilityDemandCreate->demand_channel_01),
                                                    'demand_channel_02' => $this->generateDemandService->actualDemand($probabilityDemandCreate->demand_channel_02),
                                                ]);
                                                // STEP 11: CREATE FORECAST DEMAND
                                                $forecastDemandCreate = QuarterForecastDemand::create([
                                                    'simulation_id' => $simulation_id,
                                                    'product_id' => $product_id,
                                                    'region_id' => $region_id,
                                                    'firm_id' => $firm_id,
                                                    'quarter' => $quarter_id,
                                                    'demand_channel_01' => $this->generateDemandService->forecastDemand($actualDemandCreate->demand_channel_01, $initialDemandCreate->demand_channel_01),
                                                    'demand_channel_02' => $this->generateDemandService->forecastDemand($actualDemandCreate->demand_channel_02, $initialDemandCreate->demand_channel_02),
                                                    'accuracy_channel_01' => '0',
                                                    'accuracy_channel_02' => '0',
                                                ]);
                                                // STEP 12: CREATE FORECAST DEMAND
                                                $forecastDemandCreate->update([
                                                    'accuracy_channel_01' => $this->generateDemandService->forecastAccuracy($forecastDemandCreate->demand_channel_01, $actualDemandCreate->demand_channel_01),
                                                    'accuracy_channel_02' => $this->generateDemandService->forecastAccuracy($forecastDemandCreate->demand_channel_02, $actualDemandCreate->demand_channel_02),
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Additionally update Quarter Status if it setup as no
        SimulationQuarter::where(['simulation_id' => $request->simulation_id, 'is_schedule' => 'No'])
            ->update(['is_schedule' => 'Yes']);

        return redirect()->route('admin.courses.selectFaculty', $courses_id);
    }

    /**
     * Display faculty selection screen to assign faculty to the simulation.
     */
    public function selectFaculty($courses_id)
    {
        // CHECK 01: IF THE SIMULATION / COURSE EXISTS.
        if (! Simulation::where('id', $courses_id)->exists()) {
            // >> RETURN TO SIMULATION / COURSE LISTING PAGE
            return redirect()->route('admin.simulation.index');
        }

        // CHECK 03: IF INITIAL-DEMAND SETUP IS DONE
        if (! QuarterForecastDemand::where('simulation_id', $courses_id)->exists()) {
            // >> RETURN TO INITIAL-DEMAND SETUP PAGE.
            return redirect()->route('admin.courses.industryInitialDemand', $courses_id);
        }

        // CHECK 02: IF THE FACULTY HAS BEEN INVITED
        if (SimulationFaculty::where('simulation_id', $courses_id)->exists()) {
            // >> RETURN TO SIMULATION / COURSE DETAIL PAGE.
            return redirect()->route('admin.simulation.show', $courses_id);
        }

        // STEP 01: GET THE SIMULATION / COURSE INFO
        $course = $this->courseDetail($courses_id);

        // STEP 02: GET THE LIST OF FACULTIES
        $faculties = Faculty::where('status', 'Active')
            ->select('id', 'first_name', 'last_name', 'department', 'designation', 'email')
            ->with(['profile' => function ($query) {
                $query->select('faculty_id', 'profile_picture', 'qualifications', 'research_interests');
            }])->get();

        return view('admin.simulation.select-faculty', compact('course', 'faculties'));
    }

    /**
     * Validate and save selected faculty assignment(s) for the simulation.
     */
    public function saveFaculty(Request $request)
    {
        try {
            // CHECK: Ensure the simulation exists
            $simulation = Simulation::find($request->simulation_id);
            if (! $simulation) {
                return redirect()->route('admin.simulation.index')
                    ->with('error', 'Simulation not found.');
            }

            // STEP 1: Validate input (single faculty now)
            $request->validate([
                'faculty' => 'required|exists:faculties,id',
            ], [
                'faculty.required' => 'You must select a faculty.',
                'faculty.exists' => 'Selected faculty does not exist.',
            ]);

            // STEP 2: Assign the selected faculty to the simulation
            SimulationFaculty::create([
                'simulation_id' => $simulation->id,
                'faculty_id' => $request->input('faculty'),
            ]);

            return redirect()->route('admin.simulation.show', $simulation->id)
                ->with('success', 'Faculties assigned successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error assigning faculty to simulation: '.$e->getMessage());

            return redirect()->route('admin.simulation.index')
                ->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Remove assigned faculty from a simulation (placeholder / to be implemented).
     */
    public function removeFaculty($courses_id)
    {
        //
    }

    /**
     * Manage assigned faculty for a simulation (placeholder / to be implemented).
     */
    public function manageFaculty($courses_id)
    {
        //
    }

    /**
     * Publish/deploy the simulation, update publication settings and notify assigned faculty.
     */
    public function deploySimulation(Request $request)
    {
        try {

            // STEP 01: Ensure the simulation exists
            $simulation = Simulation::findOrFail($request->simulation_id);

            // STEP 02: Validate input with custom error messages
            $request->validate([
                'fee' => 'required|numeric|min:100',
                'licence_count' => 'required|numeric|min:'.$simulation->licence_count,
            ], [
                'fee.required' => 'The fee field is required.',
                'fee.numeric' => 'The fee must be a number.',
                'fee.min' => 'The fee must be at least 100.',
                'licence_count.required' => 'The licence count field is required.',
                'licence_count.numeric' => 'The licence count must be a number.',
                'licence_count.min' => 'The licence count must be at least '.$simulation->licence_count.'.',
            ]);

            $lastQuarterEndDate = $simulation->simulationQuarters()
                ->max('end_date');

            if (! $lastQuarterEndDate) {
                return redirect()->back()
                    ->with('error', 'Simulation quarters are not configured properly.');
            }

            // STEP 03: Update is_published, fee, and licence_count
            $simulation->update([
                'is_published' => Carbon::now(),
                'fee' => $request->fee,
                'licence_count' => $request->licence_count,
                'end_date' => Carbon::parse($lastQuarterEndDate),
            ]);

            // STEP 04: Fetch assigned faculties
            $assignedFaculties = SimulationFaculty::with('faculty')
                ->where('simulation_id', $simulation->id)
                ->get();

            if ($assignedFaculties->isEmpty()) {
                return redirect()->back()
                    ->with('message', 'Simulation published, but no faculty records were found.')
                    ->withInput();
            }

            // STEP 05: Send Email To Faculty
            foreach ($assignedFaculties as $assignment) {
                $faculty = $assignment->faculty;

                if ($faculty && $faculty->email) {
                    try {
                        $simulationData = Simulation::with('simulationQuarters')->find($simulation->id);
                        $simulationData->course_code = $simulationData->share_code;
                        $simulationData->share_code = Crypt::encryptString($simulationData->share_code);

                        $facultyData = Faculty::find($faculty->id);
                        $facultyData->faculty_code = Crypt::encryptString($facultyData->faculty_code);

                        $html = view('emails.simulation-invite-faculty', [
                            'simulation' => $simulationData,
                            'faculty' => $facultyData,
                        ])->render();

                        $this->mailjetService->send(
                            $faculty->email,
                            $faculty->email,
                            'Welcome to FLEXEE - Faculty Enrollment and Course Setup',
                            $html
                        );
                    } catch (\Exception $emailEx) {
                        Log::warning("Failed to send email to {$faculty->email}: ".$emailEx->getMessage());
                    }
                }
            }

            return redirect()->back()->with('success', 'Simulation deployed and emails sent successfully.');
        } catch (\Exception $ex) {
            return redirect()->back()->with('error', 'Failed to deploy simulation: '.$ex->getMessage());
        }
    }

    /**
     * Display the faculty selection screen to add co-faculty members to the simulation.
     */
    public function selectCoFaculty($courses_id)
    {
        // CHECK 01: IF THE SIMULATION / COURSE EXISTS
        if (! Simulation::where('id', $courses_id)->exists()) {
            return redirect()->route('admin.simulation.index');
        }

        // STEP 01: GET THE SIMULATION / COURSE INFO
        $course = $this->courseDetail($courses_id);

        // STEP 02: GET FACULTY IDs ALREADY ASSIGNED TO THIS SIMULATION
        $assignedFacultyIds = SimulationFaculty::where('simulation_id', $courses_id)
            ->pluck('faculty_id')
            ->toArray();

        // STEP 03: GET ONLY FACULTY NOT ALREADY ASSIGNED
        $faculties = Faculty::where('status', 'Active')
            ->whereNotIn('id', $assignedFacultyIds)
            ->select('id', 'first_name', 'last_name', 'department', 'designation', 'email')
            ->with(['profile' => function ($query) {
                $query->select('faculty_id', 'profile_picture', 'qualifications', 'research_interests');
            }])
            ->get();

        return view('admin.simulation.select-co-faculty', compact('course', 'faculties'));
    }

    /**
     * Store selected faculty members as co-faculty for the simulation.
     */
    public function saveCoFaculty(Request $request)
    {
        try {
            // CHECK: Ensure the simulation exists
            $simulation = Simulation::find($request->simulation_id);

            if (! $simulation) {
                return redirect()->route('admin.simulation.index')
                    ->with('error', 'Simulation not found.');
            }

            // STEP 1: Validate input (single faculty now)
            $request->validate([
                'faculty' => 'required|exists:faculties,id',
            ], [
                'faculty.required' => 'You must select a faculty.',
                'faculty.exists' => 'Selected faculty does not exist.',
            ]);

            // STEP 2: Assign the selected faculty to the simulation
            SimulationFaculty::create([
                'simulation_id' => $simulation->id,
                'role' => 'co_faculty',
                'faculty_id' => $request->input('faculty'),
            ]);

            return redirect()->route('admin.simulation.show', $simulation->id)
                ->with('success', 'Co-Faculty assigned successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error assigning faculty to simulation: '.$e->getMessage());

            return redirect()->route('admin.simulation.index')
                ->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }
}
