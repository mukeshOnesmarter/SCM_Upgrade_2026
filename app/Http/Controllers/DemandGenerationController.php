<?php

namespace App\Http\Controllers;

use App\Models\DemandChannel;
use App\Models\Industry;
use App\Models\IndustryInitialDemand;
use App\Models\Product;
use App\Models\QuarterActualDemand;
use App\Models\QuarterForecastDemand;
use App\Models\QuarterInitialDemand;
use App\Models\QuarterProbabilityDemand;
use App\Models\Region;
use App\Models\Simulation;
use App\Models\SimulationFirm;
use App\Services\GenerateDemandService;

class DemandGenerationController extends Controller
{
    // CALL THE SERVICS (GENERATE DEMAND)
    protected $generateDemandService;

    public function __construct(GenerateDemandService $generateDemandService)
    {
        $this->generateDemandService = $generateDemandService;
    }

    public function generateInitialDemand($courses_id)
    {
        // CHECK CONDITION 01: IF THE SIMULATION / COURSE EXISTS.
        if (! Simulation::where('id', $courses_id)->exists()) {
            // RETURN TO SIMULATION / COURSE LISTING PAGE.
            return redirect()->route('admin.courses.index');
        }

        // **CALL FUNCTION TO GENERATE FORCAST DEMAND:- SELECT ANY ONE EITHER LONG PROCESS OR SHORT PROCESS**//
        // A: LONG PROCESS:
        $randomNumber = $this->generateDemandService->randomNumber();
        $initialDemand = $this->generateDemandService->initialDemand(10000);
        $probabilityDemand = $this->generateDemandService->probabilityDemand($initialDemand, $randomNumber);
        $actualDemand = $this->generateDemandService->actualDemand($probabilityDemand);
        $forcastDemand = $this->generateDemandService->forecastDemand($actualDemand, $initialDemand);
        $forecastAccuracy = $this->generateDemandService->forecastAccuracy($forcastDemand, $actualDemand);
        $completeProcess = [
            'randomNumber' => $randomNumber,
            'initialDemand' => $initialDemand,
            'probabilityDemand' => $probabilityDemand,
            'actualDemand' => $actualDemand,
            'forcastDemand' => $forcastDemand,
            'forecastAccuracy' => $forecastAccuracy,
        ];
        // B: GENERATE FORCAST DEMAND SHORT PROCESS:
        $generateDemand = $this->generateDemandService->generateDemand(10000);

        // CREATE RECORD FOR DEFAULT QUARTER(S) #123...
        $industry_id = 1;
        $simulation_id = $courses_id;
        $quarters_default_execute = [1, 2, 3];
        // STEP 1: CHECK IF THE QUARTERS ARE AVAILALBE TO BE EXECUTE
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
                                                // STEP 7: COLLECT DEMANDS (RETAIL / DIRECT) HISTORICAL DATA.
                                                $collectDemand = IndustryInitialDemand::where(['industry_id' => $industry_id, 'region_id' => $region_id, 'product_id' => $product_id, 'quarters' => 0])->select('demand_channel_01', 'demand_channel_02')->first();
                                                $demand_channel_01 = $collectDemand->demand_channel_01; // *STORE*//
                                                $demand_channel_02 = $collectDemand->demand_channel_02; // *STORE*//
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
                                                    'demand_channel_01' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_01, $randomNumber),
                                                    'demand_channel_02' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_02, $randomNumber),
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

        // dd($initialDemandCreate, $probabilityDemandCreate, $actualDemandCreate, $forecastDemandCreate);
        return redirect()->route('admin.courses.industryInitialDemand', $courses_id);
    }
}
