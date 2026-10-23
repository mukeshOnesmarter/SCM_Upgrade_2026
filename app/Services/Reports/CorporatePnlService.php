<?php

namespace App\Services\Reports;

use App\Models\CarrierCost;
use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InventoryStockFinishedGood;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\DemandChannel;
use App\Models\FinancialQuarter;
use App\Models\Packaging;
use App\Models\PostpondConversion;
use App\Models\Product;
use App\Models\ProductMaterial;
use App\Models\RawMaterialCost;
use App\Models\RawMaterialVolumeDiscount;
use App\Models\Region;
use App\Models\ReplacementSubAssembly;
use App\Models\SubAssemblyVolumeDiscount;
use App\Models\Supplier;
use App\Models\SupplierComponent;
use App\Services\ActiveUserService;
use App\Services\Reports\Supplementary\ProcurementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CorporatePnlService
{
    protected $activeUserService;

    protected $procurementService;

    protected $productPnlService;

    public function __construct(
        ActiveUserService $activeUserService,
        ProcurementService $procurementService,
        ProductPnlService $productPnlService
    ) {
        $this->activeUserService = $activeUserService;
        $this->procurementService = $procurementService;
        $this->productPnlService = $productPnlService;
    }

    public function corporateProfitLoss($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        // Load all required data for the simulation
        $actualDemand = $this->filterBySimulation(ActualDemand::class, $simulation_id, $faculty_id, $group_id, $quarter_id);
        $forecastDemand = $this->filterBySimulation(Forecasting::class, $simulation_id, $faculty_id, $group_id, $quarter_id);
        $demandPlanning = $this->filterBySimulation(DemandPlanning::class, $simulation_id, $faculty_id, $group_id, $quarter_id);
        $dcCenters = $this->filterBySimulation(Distribution::class, $simulation_id, $faculty_id, $group_id, $quarter_id, ['is_operational' => 'yes']);
        $replacementSubAssembly = $this->filterBySimulation(ReplacementSubAssembly::class, $simulation_id, $faculty_id, $group_id, $quarter_id);
        $shipment = $this->filterBySimulation(Transportation::class, $simulation_id, $faculty_id, $group_id, $quarter_id);
        $demandChannel = DemandChannel::all();
        $dutiesTariffs = [0, 8, 12];

        $procurement = Procurement::with('subAssembly')->where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->first();

        $productPricing = DemandPlanning::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
        ])->get();

        $serviceOutsourcing = ServiceManagement::with('serviceOutsourcing.serviceCost')->where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $manufacturing = Manufacturing::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $dcCenters->transform(function ($dc) {
            // don't overwrite if available_inventory already set; we will build available_inventory inside helper from InventoryStockFinishedGood
            return $dc;
        });

        // initialize global emergency usage tracker (shared across all products & DCs)
        $emergencyUsed = [];

        // Loop through each product and calculate its P&L details
        $products = Product::all()->each(function ($product) use (
            $dcCenters,
            $actualDemand,
            $forecastDemand,
            $demandPlanning,
            $demandChannel,
            $replacementSubAssembly,
            $serviceOutsourcing,
            $dutiesTariffs,
            $procurement,
            $shipment,
            $productPricing,
            $manufacturing,
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id,
            &$emergencyUsed
        ) {

            $product_id = $product->id;

            // Filter demand, forecasting, and planning data specific to this product
            $productActualDemand = $actualDemand->where('product_id', $product_id);
            $productForecastDemand = $forecastDemand->where('product_id', $product_id);
            $productSale = $actualDemand->where('product_id', $product_id);
            $productDemandPlanning = $demandPlanning->where('product_id', $product_id);
            $productPricing = $productPricing->where('product_id', $product_id);
            $productManufacturing = $manufacturing->where('product_id', $product_id);

            $product->setAttribute('dcCenter', $dcCenters);

            $product->setAttribute('salesVolume', $productSale);
            $marketSalesVolume = $product->salesVolume; // Retail + Direct

            $product->setAttribute('demandPlaning', $productDemandPlanning);
            $product->setAttribute('demandPlaningchannel_01', $product->demandPlaning->firstWhere('demand_channel_id', 1));
            $product->setAttribute('demandPlaningchannel_02', $product->demandPlaning->firstWhere('demand_channel_id', 2));
            $marketDemandPlaning = $product->demandPlaning; // Retail + Direct

            $product->setAttribute('forecastDemand', $productForecastDemand);
            $marketForecastDemand = $product->forecastDemand; // Retail + Direct

            $product->setAttribute('manufactured', $productManufacturing);
            $marketManufactured = $product->manufactured;

            $product->setAttribute('actualDemand', $productActualDemand);
            $marketActualDemand = $product->actualDemand; // Retail + Direct

            $product->setAttribute('duties_tariff', $dutiesTariffs);
            $product->setAttribute('serviceOutsourcing', $serviceOutsourcing);
            $product->setAttribute('shipment', $shipment);

            $replacementSubAssembly = ProductMaterial::where([
                'product_id' => $product_id,
                'type' => 'sub_assembly_component',
            ])->get();

            $replacementSubAssembly->each(function ($component) use ($procurement) {
                // Get all procurement sub-assemblies for this component
                $component->suppliers = collect(optional($procurement)->subAssembly)
                    ->where('sub_assembly_component_id', $component->material_id);

                // Preload all supplier components for this material to avoid N+1 queries
                $supplierComponents = SupplierComponent::where('sub_assembly_component_id', $component->material_id)
                    ->get()->keyBy('supplier_id');

                $component->suppliers->each(function ($item) use ($supplierComponents) {
                    // Calculate replacements due to failure
                    $failureRate = optional($supplierComponents->get($item->supplier_id))->failure_rate ?? 0;
                    $cost = optional($supplierComponents->get($item->supplier_id))->cost ?? 0;
                    $item->cost = (float) $cost;
                    $item->failureRate = (float) $failureRate;

                    $item->replacements = ceil((float) $item->quantity * ($item->failureRate / 100));
                });

                // Calculate weighted average cost
                $totalCost = $component->suppliers->sum(function ($item) {
                    return $item->cost * $item->quantity;
                });

                $totalQuantity = $component->suppliers->sum('quantity');
                $weightedAvgCost = $totalQuantity > 0 ? ($totalCost / $totalQuantity) : 0;

                // Calculate total replacement cost
                $totalReplacements = $component->suppliers->sum('replacements');
                $component->replacementCost = $totalReplacements * $weightedAvgCost;
            });

            $replacementSubAssembly = $replacementSubAssembly->sum('replacementCost') ?? 0;

            // Market demand comes from ActualDemand
            $product->marketDemand = $productSale->sum(function ($item) {
                return $item->demand_channel_01 +
                  $item->demand_channel_02;
            });

            $alloc = $this->calculateUnfilledForProduct(
                $product_id,
                $dcCenters,
                $manufacturing,
                $actualDemand,
                $emergencyUsed,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id
            );

            $product->alloc = $alloc;

            $unfilledResult = $this->calculateUnfilledForProduct(
                $product_id,
                $dcCenters,
                $manufacturing,
                $actualDemand,
                $emergencyUsed,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id
            );

            $product->emergencyProducedUnits = (int) ($unfilledResult['emergency_produced'] ?? 0);
            $product->unfilledOrders = (int) ($unfilledResult['unfilled'] ?? 0);
            $product->unfilledHandling = $product->unfilledOrders * 25;

            $product->fulfilledUnits = max(0, $product->marketDemand - $product->unfilledOrders);
            $product->productSalesVolume = $product->fulfilledUnits;

            // market demand by channel (SAFE: fulfilledUnits now exists)
            $marketCh1 = $product->salesVolume->sum('demand_channel_01');
            $marketCh2 = $product->salesVolume->sum('demand_channel_02');
            $marketTotal = $marketCh1 + $marketCh2;

            // Revenue (based on FULFILLED units — REGION SAFE)
            $regions = Region::all();
            $totalRevenue = 0.00;

            foreach ($regions as $region) {

                $region_id = $region->id;

                $allocated = (int) ($alloc['alloc_map'][$region_id]['allocated'] ?? 0);
                if ($allocated <= 0) {
                    continue;
                }

                $sale = $productSale->firstWhere('region_id', $region_id);

                $marketRetail = (int) ($sale->demand_channel_01 ?? 0);
                $marketDirect = (int) ($sale->demand_channel_02 ?? 0);
                $marketTotal = $marketRetail + $marketDirect;

                if ($marketTotal <= 0) {
                    continue;
                }

                $regionPricing = $productDemandPlanning->where('region_id', $region_id);

                foreach ($demandChannel as $channel) {

                    $channelMarketQty = $channel->id === 1 ? $marketRetail : $marketDirect;
                    $fulfilledQty = ($channelMarketQty / $marketTotal) * $allocated;

                    $price = optional(
                        $regionPricing->firstWhere('demand_channel_id', $channel->id)
                    )->price ?? 0;

                    // ❗ DO NOT ROUND HERE
                    $totalRevenue += ($fulfilledQty * $price);
                }
            }

            // ✅ ROUND ONLY ONCE
            $product->revenue = round($totalRevenue, 2);
            $product->totalRevenue = $product->revenue;

            // Calculate weighted average price (based on FULFILLED units)
            $totalWeightedPrice = 0;
            $totalFulfilledQty = 0;

            foreach ($regions as $region) {
                $region_id = $region->id;

                // ✅ Fulfilled units PER REGION (single source of truth)
                $allocated = (int) ($alloc['alloc_map'][$region_id]['allocated'] ?? 0);
                if ($allocated <= 0) {
                    continue;
                }

                $productSaleData = $productSale->firstWhere('region_id', $region_id);
                $regionDemandPlanning = $productDemandPlanning->where('region_id', $region_id);

                if (! $productSaleData || $regionDemandPlanning->isEmpty()) {
                    continue;
                }

                // Market demand (ONLY for channel split)
                $marketRetail = (int) ($productSaleData->demand_channel_01 ?? 0);
                $marketDirect = (int) ($productSaleData->demand_channel_02 ?? 0);
                $marketTotal = $marketRetail + $marketDirect;

                if ($marketTotal <= 0) {
                    continue;
                }

                foreach (DemandChannel::all() as $channel) {

                    $channelMarketQty = $channel->id === 1
                      ? $marketRetail
                      : $marketDirect;

                    // ✅ Split ONLY this region’s allocated units
                    $fulfilledQty = ($channelMarketQty / $marketTotal) * $allocated;

                    $price = optional(
                        $regionDemandPlanning->firstWhere('demand_channel_id', $channel->id)
                    )->price ?? 0;

                    if ($fulfilledQty > 0 && $price > 0) {
                        $totalWeightedPrice += $fulfilledQty * $price;
                        $totalFulfilledQty += $fulfilledQty;
                    }
                }
            }

            // ✅ Final weighted avg price
            $product->price = $totalFulfilledQty > 0
              ? round($totalWeightedPrice / $totalFulfilledQty, 2)
              : 0;

            // product Cost For Sold Product

            /**
             * Total Expenses Expenses = Product Costs + Production Shift Cost + Fixed Order Cost + Order Processing + Replacement Parts
             * RFID Costs + Duties & Tariffs - Transportation Rebates - Volume Discounts.
             */

            // Item 1: Product Costs
            $product->productCost = $this->calculateProductCost($simulation_id, $faculty_id, $group_id, $product_id, $quarter_id, 0.5, 3, 1) *
              $product->fulfilledUnits;

            // Item 2: Production Shift Cost
            $product->productionShiftCost = ceil($productForecastDemand->sum(function ($item) {
                return $item->demand_channel_01 + $item->demand_channel_02;
            }) / 50000) * 100000;

            // Item 3: Fixed Order Cost
            $product->fixedCost = $product_id == 1 ? 22500 : 24500;

            // Item 4: Order Processing
            $product->orderProcessing =
              ($product->salesVolumechannel_01 * 4) +
              ($product->salesVolumechannel_02 * 24);

            // Item 5: Replacement Parts
            $totalUnitsSold = $marketActualDemand->sum(function ($item) {
                return $item->demand_channel_01 + $item->demand_channel_02;
            });

            if ($totalUnitsSold == 0) {
                $product->replacementParts = 0;
            } else {
                // Calculate total replacement parts cost distributed across all regions
                $product->replacementParts = $marketActualDemand->sum(function ($item) use ($totalUnitsSold, $replacementSubAssembly) {
                    $regionUnitsSold = $item->demand_channel_01 + $item->demand_channel_02;

                    return ($regionUnitsSold / $totalUnitsSold) * $replacementSubAssembly;
                });
            }

            // Item 6: RFID Costs ($)
            $product->rfidCosts = $dcCenters->sum(function ($item) use ($productSale) {

                // Retail-channel demand only (Channel #1)
                $demand = $productSale->firstWhere('region_id', $item->region_id);
                $retailQty = $demand ? $demand->demand_channel_01 : 0;

                // No retail demand → no RFID cost
                if ($retailQty <= 0) {
                    return 0;
                }

                $unitCost = ($item->dc_type === 'insourced') ? 1 : 11;

                return $retailQty * $unitCost;
            });

            // Item 7: Transportation Cost
            $product->simulation_id = $simulation_id;
            $product->faculty_id = $faculty_id;
            $product->group_number = $group_id;
            $product->quarter = $quarter_id;

            $product->transportationCost = $this->calculateTransportationCost($product, $dcCenters);

            // Item 8: Duties & Tariffs ($)
            $totalDutiesTariffs = 0;

            $regions = Region::all();
            foreach ($regions as $region) {

                if ($region->id === 1) {
                    $region->dutiesTariffs = 0;
                } else {
                    $regionSalesVolume = $marketSalesVolume->where('region_id', $region->id);
                    $unitsSoldRegion = $regionSalesVolume->sum(function ($item) {
                        return $item->demand_channel_01 + $item->demand_channel_02;
                    });

                    $averagePrice = $marketDemandPlaning->where('region_id', $region->id)->avg('price') ?? 0;
                    $dutiesRate = $dutiesTariffs[$region->id - 1];
                    $region->dutiesTariffs = $unitsSoldRegion * $averagePrice * ($dutiesRate / 100);
                }

                $totalDutiesTariffs += $region->dutiesTariffs;
            }

            $product->dutiesTariffs = $totalDutiesTariffs;

            /**
             * Item 9: Emergency Production Cost (Manual-Aligned)
             *
             * Cost Structure:
             *  - Standby cost: $0.50 × declared emergency capacity (always charged)
             *  - Variable emergency cost (only if triggered):
             *      • Base charge per unit
             *      • Tiered production premium (50% / 100%)
             *      • Emergency air-shipping premium
             */

            // Declared emergency capacity (units)
            $productEmergencyLimit =
              (float) optional($productManufacturing->first())->emergency_production ?? 0;

            // Standby cost — charged regardless of usage
            $product->emergencyProductionStandby =
              round($productEmergencyLimit * 0.5, 2);

            // Regular per-unit production cost (used for premium calculation)
            $regularUnitProductionCost =
              $this->calculateProductCost(
                  $simulation_id,
                  $faculty_id,
                  $group_id,
                  $product_id,
                  $quarter_id,
                  0.5,    // terahertz (policy default)
                  3,      // warranty (policy default)
                  1       // packaging (policy default)
              );

            // Variable emergency production cost (manual-aligned)
            $product->emergencyProductionCost =
              $this->calculateEmergencyProductionCost(
                  $product_id,
                  $product->emergencyProducedUnits,
                  $regularUnitProductionCost
              );

            // CALCULATE TOTALEXPENSES: ($)
            $product->totalExpenses = $product->productCost
              + $product->productionShiftCost
              + $product->fixedCost
              + $product->orderProcessing
              + $product->replacementParts
              + $product->rfidCosts
              + $product->transportationCost
              + $product->dutiesTariffs
              + $product->emergencyProductionCost;

            // CALCULATE GROSS MARGIN: (Revenue - totalExpenses)
            $product->grossMargin = $product->revenue - $product->totalExpenses;

            /**
             * FIXED COSTS = Administrative O/H + Forecast Inaccuracy + Marketing + Price Changes + Service Outsourcing
             */

            // -------------------------------------------------
            // Item 1: Administrative OH (FIXED)
            // -------------------------------------------------

            $regions = Region::all();
            $administrativeOH = 0;

            /**
             * STEP 1: Calculate fulfilled units by region + channel
             * (this replaces use of market demand as "actual")
             */
            $fulfilledByRegionChannel = [];

            // Initialize structure
            foreach ($regions as $region) {
                $fulfilledByRegionChannel[$region->id] = [
                    1 => 0, // Retail
                    2 => 0, // Direct
                ];
            }

            // Distribute fulfilled units using allocation + demand mix
            foreach ($product->alloc['alloc_map'] ?? [] as $region_id => $alloc) {

                $allocatedUnits = (int) ($alloc['allocated'] ?? 0);
                if ($allocatedUnits <= 0) {
                    continue;
                }

                // Market demand used ONLY for channel split ratio
                $saleData = $productSale->firstWhere('region_id', $region_id);
                if (! $saleData) {
                    continue;
                }

                $retailDemand = (int) ($saleData->demand_channel_01 ?? 0);
                $directDemand = (int) ($saleData->demand_channel_02 ?? 0);
                $totalDemand = $retailDemand + $directDemand;

                if ($totalDemand <= 0) {
                    continue;
                }

                // Proportional split of fulfilled units
                $fulfilledByRegionChannel[$region_id][1] +=
                  ($retailDemand / $totalDemand) * $allocatedUnits;

                $fulfilledByRegionChannel[$region_id][2] +=
                  ($directDemand / $totalDemand) * $allocatedUnits;
            }

            /**
             * STEP 2: Administrative OH calculation
             */
            foreach ($regions as $region) {

                $region_id = $region->id;

                $forecastDemandData =
                  optional($marketForecastDemand)->where('region_id', $region_id)->first();

                $regionAdministrativeOH = 0;

                foreach (DemandChannel::all() as $channel) {

                    $channel_id = $channel->id;

                    // Forecasted units (UNCHANGED)
                    $forecast = $channel_id === 1
                      ? ($forecastDemandData->demand_channel_01 ?? 0)
                      : ($forecastDemandData->demand_channel_02 ?? 0);

                    // ✅ FIX: Actual = fulfilled (sold) units
                    $actual = (float) ($fulfilledByRegionChannel[$region_id][$channel_id] ?? 0);

                    $regionAdministrativeOH +=
                      $this->calculateAdministrativeOverhead($forecast, $actual, $channel_id);
                }

                $region->administrativeOH = round($regionAdministrativeOH, 2);
                $administrativeOH += $region->administrativeOH;
            }

            // Assign to product
            $product->administrativeOH = round($administrativeOH, 2);

            // Item 2: Forecast Inaccuracy
            $regions = Region::all();
            $totalForecastInaccuracy = 0;
            foreach ($regions as $region) {
                $region_id = $region->id;

                $forecastData = optional($marketForecastDemand)->where('region_id', $region_id)->first();
                $actualData = optional($marketActualDemand)->where('region_id', $region_id)->first();

                $regionForecastInaccuracy = 0;

                foreach (DemandChannel::all() as $channel) {
                    $channel_id = $channel->id;

                    $forecast = $channel_id === 1
                      ? $forecastData->demand_channel_01 ?? 0
                      : $forecastData->demand_channel_02 ?? 0;

                    $actual = $channel_id === 1
                      ? $actualData->demand_channel_01 ?? 0
                      : $actualData->demand_channel_02 ?? 0;

                    $regionForecastInaccuracy += $this->calculateForecastInaccuracyFixedCost($forecast, $actual, $channel_id);
                }

                $region->forecastInaccuracy = $regionForecastInaccuracy;
                $totalForecastInaccuracy += $regionForecastInaccuracy;
            }

            $product->forecastInaccuracy = $totalForecastInaccuracy;

            // Item 3: Marketing
            $initialMarketingBudget = 100000;
            $totalMarketingBudget = 0;
            $regions = Region::all();

            foreach ($regions as $region) {
                $region_id = $region->id;

                $regionSpending = optional($marketDemandPlaning)
                    ->where('region_id', $region_id)
                    ->sum('marketSpending');

                $region->marketing = $this->calculateMarketingBudget($initialMarketingBudget, $regionSpending);
                $totalMarketingBudget += $region->marketing;
            }

            $product->marketing = $totalMarketingBudget;

            // Item 3: priceChanges
            $totalPriceChangeCost = 0;
            foreach (Region::all() as $region) {
                $region_id = $region->id;
                $regionPricing = $productPricing->where('region_id', $region_id);

                // Get old and new pricing for this region
                $oldPricing = optional($regionPricing)->where('quarter', $quarter_id - 1);
                $newPricing = optional($regionPricing)->where('quarter', $quarter_id);

                $oldRetailPrice = $oldPricing->firstWhere('demand_channel_id', 1)->price ?? 0;
                $oldDirectPrice = $oldPricing->firstWhere('demand_channel_id', 2)->price ?? 0;
                $retailPrice = $newPricing->firstWhere('demand_channel_id', 1)->price ?? 0;
                $directPrice = $newPricing->firstWhere('demand_channel_id', 2)->price ?? 0;

                // Get region-specific sales volume
                $regionSales = optional($marketSalesVolume)->where('region_id', $region_id)->first();
                $retailVolume = $regionSales->demand_channel_01 ?? 0;
                $directVolume = $regionSales->demand_channel_02 ?? 0;

                // Revenue per channel
                $retailRevenue = $retailVolume * $retailPrice;
                $directRevenue = $directVolume * $directPrice;

                // Price change cost calculation
                $priceChangeCostRetail = $this->calculatePriceChangeCost($oldRetailPrice, $retailPrice, $retailRevenue);
                $priceChangeCostDirect = $this->calculatePriceChangeCost($oldDirectPrice, $directPrice, $directRevenue);

                $region->priceChanges = $priceChangeCostRetail + $priceChangeCostDirect;
                $totalPriceChangeCost += $region->priceChanges;
            }

            $product->priceChanges = $totalPriceChangeCost;

            // Item 4: serviceOutsourcing
            $totalServiceOutsourcing = 0;
            foreach (Region::all() as $region) {
                $region_id = $region->id;
                $regionserviceOutsourcing = $serviceOutsourcing->firstWhere('region_id', $region_id);
                // Safely access the service cost for the region
                $serviceCost = optional(
                    optional(
                        optional($regionserviceOutsourcing)->serviceOutsourcing
                    )->serviceCost
                )->firstWhere('region_id', $region_id)->cost_call ?? 0;
                // Add randomized cost for the region
                $totalServiceOutsourcing += 500 * $serviceCost;
            }

            $product->serviceOutsourcing = $totalServiceOutsourcing;

            // Collect all fixed costs (incl. standby + unfilled handling)
            $product->totalFixedCosts = $product->administrativeOH +
              $product->forecastInaccuracy +
              $product->marketing +
              $product->priceChanges +
              $product->serviceOutsourcing;

            $product->unfilledHandling = $product->unfilledHandling ?? 0;
            $product->totalFixedCosts += $product->unfilledHandling + $product->emergencyProductionStandby;

            // Final operating income
            $product->operatingIncome = $product->grossMargin - $product->totalFixedCosts;
        });

        // ALL PRODUCT (Product 1 + 2)
        $allProduct = new \stdClass;

        $volumeDiscountTotal = 0;
        $totalWeightedPrice = 0;
        $totalSalesVolume = 0;

        foreach ($products as $product) {
            $totalWeightedPrice += $product->price * $product->productSalesVolume;
            $totalSalesVolume += $product->productSalesVolume;
        }

        // Volume Discount Calculation: Sub Assembly
        $volumeDiscountTotal = 0;

        $normalSubAssemblies = collect(optional($procurement)->subAssembly);

        $groupedComponents = $normalSubAssemblies
            ->groupBy('sub_assembly_component_id');

        foreach ($groupedComponents as $componentId => $items) {

            // 1️⃣ Total quantity procured for this component
            $totalQty = $items->sum('quantity');
            if ($totalQty <= 0) {
                continue;
            }

            // 2️⃣ Highest applicable discount slab
            $slab = SubAssemblyVolumeDiscount::where('sub_assembly_component_id', $componentId)
                ->where('volume', '<=', $totalQty)
                ->orderByDesc('volume')
                ->first();

            if (! $slab || $slab->discount <= 0) {
                continue;
            }

            $discountRate = $slab->discount / 100;

            // 3️⃣ Total cost for this component
            $componentCost = 0;
            foreach ($items as $item) {
                $unitCost = SupplierComponent::where([
                    'supplier_id' => $item->supplier_id,
                    'sub_assembly_component_id' => $componentId,
                ])->value('cost') ?? 0;

                $componentCost += $unitCost * $item->quantity;
            }

            // 4️⃣ Apply discount
            $volumeDiscountTotal += $componentCost * $discountRate;
        }

        // $allProduct->productSalesVolume = $totalSalesVolume;

        $allProduct->marketDemand = $products->sum('marketDemand');
        $allProduct->fulfilledUnits = $products->sum('fulfilledUnits');
        $allProduct->unfilledOrders = $products->sum('unfilledOrders');
        $allProduct->productSalesVolume = $products->sum('productSalesVolume');

        $allProduct->price = $totalSalesVolume > 0
          ? round($totalWeightedPrice / $totalSalesVolume, 2)
          : 0;

        $allProduct->unfilledOrders = $products->sum('unfilledOrders');
        $allProduct->revenue = $products->sum('revenue');
        $allProduct->productCost = $products->sum('productCost');
        $allProduct->productionShiftCost = $products->sum('productionShiftCost');
        $allProduct->fixedCost = $products->sum('fixedCost');
        $allProduct->replacementParts = $products->sum('replacementParts');
        $allProduct->orderProcessing = $products->sum('orderProcessing');
        $allProduct->rfidCosts = $products->sum('rfidCosts');
        $allProduct->transportationCost = $products->sum('transportationCost');
        $allProduct->transportationRebate = $products->sum('transportationRebate');
        $allProduct->volumeDiscounts = $this->calculateProcurementVolumeDiscount($simulation_id, $faculty_id, $group_id, $quarter_id);
        $allProduct->dutiesTariffs = $products->sum('dutiesTariffs');
        $allProduct->totalExpenses = $products->sum('totalExpenses');
        $allProduct->emergencyProductionCost = $products->sum('emergencyProductionCost');

        $allProduct->grossMargin =
          $allProduct->revenue
          - $allProduct->productCost
          - $allProduct->productionShiftCost
          - $allProduct->fixedCost
          - $allProduct->orderProcessing
          - $allProduct->replacementParts
          - $allProduct->rfidCosts
          - $allProduct->transportationCost
          + $allProduct->transportationRebate
          + $allProduct->volumeDiscounts
          - $allProduct->dutiesTariffs
          - $allProduct->emergencyProductionCost;

        $allProduct->administrativeOh = $products->sum('administrativeOH');
        $allProduct->unfilledHandling = $products->sum('unfilledHandling');
        $allProduct->emergencyProduction = $products->sum('emergencyProductionStandby');
        $allProduct->emergencyProduction = $allProduct->emergencyProduction ?? 0;
        $allProduct->forecastInaccuracy = $products->sum('forecastInaccuracy');
        $allProduct->marketing = $products->sum('marketing');
        $allProduct->serviceOutsourcing = $products->sum('serviceOutsourcing');
        $allProduct->priceChanges = $products->sum('priceChanges');

        $allProduct->corporateOh = 2 * 250000;
        $allProduct->consultingFees = 0;
        $allProduct->crossDocking = 0;
        $allProduct->distributionFc = 75000;
        $allProduct->emergencyProcurement = $this->calculateEmergencyProcurementCost($simulation_id, $faculty_id, $group_id, $quarter_id);
        $allProduct->informationTechnology = $this->calculateSupplierRelationshipAndITCost($simulation_id, $faculty_id, $group_id, $quarter_id);
        $allProduct->introductions = 0;
        $allProduct->plantCapacityFc = 200000;
        $allProduct->procurementFc = 5000;
        $allProduct->productionFc = 47000;
        $allProduct->researchStudies = 0;
        $allProduct->inventoryCharges = $this->inventoryCharges($dcCenters, $simulation_id, $faculty_id, $quarter_id, $group_id);

        /**
         * ------------------------------------------------------------
         * CROSS-DOCKING (Chapter 5 – Distribution Decisions)
         * ------------------------------------------------------------
         * Applied at CORPORATE level:
         * - Adds cross-docking fixed + quarterly cost
         * - Reduces DC operating cost (already embedded in inventoryCharges logic)
         * - Adjusts inventory holding via rate override (handled in inventoryCharges)
         * ------------------------------------------------------------
         */
        $crossDockingImpact = $this->calculateCrossDockingImpact(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        );

        // Fixed cross-docking cost (setup + quarterly)
        $allProduct->crossDocking = $crossDockingImpact['crossDockingCost'];

        // NOTE:
        // - dcOperatingSavings is NOT subtracted here because
        //   DC operating costs are already abstracted into inventoryCharges()
        // - holdingRateOverride is applied inside inventoryCharges()

        $allProduct->totalFixedCosts =
          +$allProduct->administrativeOh
            + $allProduct->corporateOh
            + $allProduct->consultingFees
            + $allProduct->crossDocking
            + $allProduct->distributionFc
            + $allProduct->emergencyProcurement
            + $allProduct->unfilledHandling
            + $allProduct->emergencyProduction
            + $allProduct->forecastInaccuracy
            + $allProduct->informationTechnology
            + $allProduct->introductions
            + $allProduct->inventoryCharges
            + $allProduct->marketing
            + $allProduct->plantCapacityFc
            + $allProduct->priceChanges
            + $allProduct->procurementFc
            + $allProduct->productionFc
            + $allProduct->researchStudies
            + $allProduct->serviceOutsourcing;

        $allProduct->operatingIncome = $allProduct->grossMargin - $allProduct->totalFixedCosts;

        $allProduct->nonOperatingIncomeBase = $this->calculateNonOperatingIncome($simulation_id, $faculty_id, $group_id, $quarter_id);
        $dcDisposal = $this->calculateDisposalSalesForClosedDcs($simulation_id, $faculty_id, $group_id, $quarter_id);
        $allProduct->disposalSaleIncome = round((float) $dcDisposal['disposalSaleIncome'], 2);
        $allProduct->nonOperatingIncome = ($allProduct->nonOperatingIncomeBase ?? 0.0) + ($allProduct->disposalSaleIncome ?? 0.0);

        $allProduct->taxes = $this->calculateCorporateTaxes($simulation_id, $faculty_id, $group_id, $quarter_id);

        $allProduct->netIncome =
          $allProduct->operatingIncome
          + $allProduct->nonOperatingIncome
          - $allProduct->taxes;

        return (object) [
            'products' => $products,
            'summary' => $allProduct,
        ];
    }

    /**
     * Calculate marketing budget for a product in a specific region and channel.
     *
     * @param  float  $initialBudget  The original marketing spending budget.
     * @param  float  $proposedBudget  The new proposed budget by the user.
     * @return float The adjusted budget within the allowed corporate policy range.
     */
    public function calculateMarketingBudget(float $initialBudget, float $proposedBudget): float
    {
        // Define the min and max allowed budget values
        $minBudget = max(0, $initialBudget - 100000);
        $maxBudget = $initialBudget + 100000;

        // Clamp the proposed budget within the allowable range
        if ($proposedBudget < $minBudget) {
            return $minBudget;
        } elseif ($proposedBudget > $maxBudget) {
            return $maxBudget;
        }

        return $proposedBudget;
    }

    /**
     * Calculate total product cost based on configuration.
     *
     * @param  float  $rawMaterialsCost
     * @param  float  $componentsCost
     * @param  float  $laborCost
     * @param  float  $productionCost
     * @param  int  $terahertzLevel  (1-7)
     * @param  int|null  $warrantyMonths  Set to null if no warranty
     * @param  int  $packagingType  1 = Standard, 2 = Premium, 3 = Eco-premium
     */
    public function calculateProductCost(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $product_id,
        int $quarter_id,
        float $terahertzLevel,
        ?int $warrantyMonths,
        int $packagingType
    ): float {
        // Determine the supplier based on the quarter.
        // Supplier ID 4 is used for quarters 1 to 3 (simulation-controlled),
        // and Supplier ID 2 is used for later quarters (student-controlled).
        if ($quarter_id <= 3) {
            $supplier = Supplier::with('supplierComponent')->find(4);
        } else {
            $supplier = Supplier::with('supplierComponent')->find(2);
        }

        // Fetch the product along with its associated materials (raw and sub-assembly components)
        $product = Product::with('material')->find($product_id);

        if (! $product) {
            return 0;
        }

        // Separate raw materials and sub-assembly components from the product's materials
        $materials = collect(optional($product)->material);
        $rawMaterials = $materials->where('type', 'raw_material');
        $subAssemblyComponents = $materials->where('type', 'sub_assembly_component');

        // Calculate cost for each raw material as: quantity × unit cost from RawMaterialCost table
        $rawMaterials->each(function ($material) {
            $unitCost = optional(
                RawMaterialCost::where('raw_material_id', $material->material_id)->first()
            )->cost ?? 0;
            $material->cost = $material->quantity * $unitCost;
        });

        // Calculate cost for each sub-assembly component as: quantity × unit cost from SupplierComponent
        $subAssemblyComponents->each(function ($component) use ($simulation_id, $faculty_id, $group_number, $quarter_id) {

            if ($quarter_id <= 3) {
                // Quarters 1-3: Simulation supplier (ID 4)
                $supplier = Supplier::with('supplierComponent')->find(4);

                $unitCost = optional($supplier)
                    ->supplierComponent
                    ->firstWhere('sub_assembly_component_id', $component->material_id)
                    ->cost ?? 0;

                $component->cost = $component->quantity * $unitCost;
            } elseif ($quarter_id === 4) {
                // Quarter 4: Student suppliers, use supplierComponent (not price table)
                $procurements = ProcurementSubAssembly::with('supplier.supplierComponent')
                    ->where('sub_assembly_component_id', $component->material_id)
                    ->whereHas('procurement', function ($query) use ($simulation_id, $faculty_id, $group_number, $quarter_id) {
                        $query->where([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_number,
                            'quarter' => $quarter_id,
                        ]);
                    })
                    ->get();

                $totalQuantity = $procurements->sum('quantity');

                $weightedUnitCost = 0;
                foreach ($procurements as $entry) {
                    $supplierComponent = optional($entry->supplier->supplierComponent)
                        ->firstWhere('sub_assembly_component_id', $component->material_id);

                    $cost = $supplierComponent->cost ?? 0;
                    $share = ($totalQuantity > 0) ? $entry->quantity / $totalQuantity : 0;
                    $weightedUnitCost += $cost * $share;
                }

                $component->cost = $component->quantity * $weightedUnitCost;
            } else {
                // Quarter 5 and above: Student suppliers, use supplierComponentPrice (filtered)
                $procurements = ProcurementSubAssembly::with([
                    'supplier' => function ($query) use ($simulation_id, $faculty_id, $quarter_id) {
                        $query->with(['supplierComponentPrice' => function ($q) use ($simulation_id, $faculty_id, $quarter_id) {
                            $q->where([
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'quarter_id' => $quarter_id,
                            ]);
                        }]);
                    },
                ])
                    ->where('sub_assembly_component_id', $component->material_id)
                    ->whereHas('procurement', function ($query) use ($simulation_id, $faculty_id, $group_number, $quarter_id) {
                        $query->where([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_number,
                            'quarter' => $quarter_id,
                        ]);
                    })
                    ->get();

                $totalQuantity = $procurements->sum('quantity');

                $weightedUnitCost = 0;
                foreach ($procurements as $entry) {
                    $price = optional($entry->supplier->supplierComponentPrice->first())->price ?? 0;
                    $share = ($totalQuantity > 0) ? $entry->quantity / $totalQuantity : 0;
                    $weightedUnitCost += $price * $share;
                }

                $component->cost = $component->quantity * $weightedUnitCost;
            }
        });

        // Sum total cost of all raw materials
        $totalRawMaterialsCost = $rawMaterials->sum(function ($material) {
            return $material->cost;
        });

        // Sum total cost of all sub-assembly components
        $totalSubAssemblyCost = $subAssemblyComponents->sum(function ($component) {
            return $component->cost;
        });

        $laborCost = (float) $product_id == 1 ? 30 : 36;
        $productionCost = (float) $product_id == 1 ? 20 : 16;

        // Bandwidth Cost: $10 + 0.5 * (T^3)
        $bandwidthCost = 10 + 0.5 * pow($terahertzLevel, 3);

        // Warranty Cost
        $warrantyCost = 0;
        if (! is_null($warrantyMonths) && $warrantyMonths > 0) {
            $warrantyCost = 8 + 3 * pow($warrantyMonths, 2);
        }

        // Packaging Cost
        $packagingCost = ($quarter_id <= 3)
          ? (int) (Packaging::find(1)->cost ?? 10)
          : (int) (Packaging::find($packagingType)->cost ?? 10);

        // Total Product Cost
        return $totalRawMaterialsCost
          + $totalSubAssemblyCost
          + $laborCost
          + $productionCost
          + $bandwidthCost
          + $warrantyCost
          + $packagingCost;
    }

    /**
     * Calculate the fixed cost penalty due to forecast inaccuracy for a specific channel.
     */
    public function calculateForecastInaccuracyFixedCost(float $forecast, float $actual, int $channel): float
    {
        if ($actual == 0) {
            return 0.0; // Avoid division by zero
        }

        // Determine base cost based on channel
        if ($channel === 2) {
            $baseCost = 120000;
        } else {
            $baseCost = 80000; // Default or Channel 1
        }

        // Calculate inaccuracy rate (capped at 1.0 or 100%)
        $inaccuracyRate = abs($forecast - $actual) / $actual;
        $inaccuracyRate = min($inaccuracyRate, 1.0);

        // Forecast inaccuracy penalty
        return round($baseCost * $inaccuracyRate, 2);
    }

    /**
     * Calculate final administrative overhead based on forecast accuracy.
     * Admin OH = Base OH × (1 + Forecast Inaccuracy %), capped at 100%
     */
    public function calculateAdministrativeOverhead(float $forecast, float $actual, int $channel): float
    {
        // Set base cost per channel
        $channelCosts = [
            1 => 80000,
            2 => 120000,
        ];

        $baseCost = $channelCosts[$channel] ?? 0;

        // Absolute error percentage
        if ($actual != 0) {
            $errorRate = abs($forecast - $actual) / $actual;
        } else {
            $errorRate = 0; // Or handle differently if needed
        }

        // Cap penalty at 100%
        $errorRate = min($errorRate, 1.0);

        // Final adjusted administrative overhead
        $administrativeOH = $baseCost * (1 + $errorRate);

        return round($administrativeOH, 2);
    }

    /**
     * Calculate the cost incurred due to a product price change in a specific region/channel.
     */
    public function calculatePriceChangeCost(float $oldPrice, float $newPrice, float $revenue): float
    {
        $fixedCost = 10000;
        $costPerDollarChange = 200;
        $revenuePenaltyRate = 0.0025;

        $priceChange = abs($newPrice - $oldPrice);

        $cost = $fixedCost + ($costPerDollarChange * $priceChange) + ($revenue * $revenuePenaltyRate);

        return round($cost, 2);
    }

    /**
     * Calculate the shipment cost for a specific region, quantity, carrier, and supply channel.
     */
    public function calculateShipmentCost($region_id, $quantity, $carrier_id, $supply_channel_id)
    {
        $carrierCost = CarrierCost::where([
            'carrier_id' => $carrier_id,
            'region_id' => $region_id,
            'supply_channel_id' => $supply_channel_id,
        ])->first();

        return round((optional($carrierCost)->cost ?? 0) * $quantity, 2);
    }

    /**
     * Calculate transportation costs for a product based on assigned distribution centers.
     */
    private function calculateTransportationCost($product, $dcCenters)
    {
        $totalCost = 0;
        $plantToDcCarrierIds = [];

        // ✅ Step 1: Plant ➝ DC shipment cost
        $transportationRecords = Transportation::with(['transportationInfo', 'region'])
            ->where('quarter', $product->quarter)
            ->where('simulation_id', $product->simulation_id)
            ->where('faculty_id', $product->faculty_id)
            ->where('group_number', $product->group_number)
            ->get();

        foreach ($transportationRecords as $transportation) {
            foreach ($transportation->transportationInfo as $info) {
                if ($info->product_id !== $product->id) {
                    continue;
                }

                $quantity = $info->quantity ?? 0;

                // Only count cost if DC is not DC1 and is operational
                $dc = $dcCenters->firstWhere('region_id', $transportation->region_id);
                $isOperational = $dc && $dc->is_operational === 'yes';

                if ($transportation->distribution_center_id != 1 && $isOperational && $info->supply_channel_id == 1) {
                    $unitCost = 3;
                    $totalCost += $quantity * $unitCost;
                    $plantToDcCarrierIds[] = $info->carrier_id;
                }
            }
        }

        // ✅ Step 2: DC ➝ Customer (based on actual demand)
        $actualDemand = $product->actualDemand ?? collect();

        foreach ($actualDemand->groupBy('region_id') as $regionId => $demands) {
            $hasLocalOperationalDc = $dcCenters->contains(function ($dc) use ($regionId) {
                return $dc->region_id == $regionId && $dc->is_operational === 'yes';
            });

            $units = $demands->sum(fn ($item) => $item->demand_channel_01 + $item->demand_channel_02);

            $unitCost = $hasLocalOperationalDc ? 4 : 8; // surface vs air from DC1
            $totalCost += $units * $unitCost;
        }

        // ✅ Step 3: Rebate if only one carrier used for plant ➝ DC
        $rebate = 0;
        if (count($plantToDcCarrierIds) > 0 && collect($plantToDcCarrierIds)->unique()->count() === 1) {
            $rebate = $totalCost * 0.2;
        }

        $product->transportationRebate = round($rebate, 2);

        return round($totalCost - $rebate, 2);
    }

    /**
     * Calculate total inventory charges for all distribution centers (DCs) in a simulation.
     */
    public function inventoryCharges($dcCenters, $simulation_id, $faculty_id, $quarter_id, $group_id)
    {
        $inventoryCharge = 0;

        foreach ($this->procurementService->procurement($simulation_id, $faculty_id, $quarter_id, $group_id)->dcCenters as $dc) {
            $region_id = $dc->region_id;
            $dc_number = $dc->dc_number;

            $distributionType = optional(
                $dcCenters->where('region_id', $region_id)
                    ->where('dc_number', $dc_number)
                    ->first()
            )->dc_type;

            if ($dc->beginning_inventory) {
                $beginningInventory = $dc->beginning_inventory->plastic
                  + $dc->beginning_inventory->casing
                  + $dc->beginning_inventory->control_interface
                  + $dc->beginning_inventory->audio_modal
                  + $dc->beginning_inventory->motherboard;
            } else {
                $beginningInventory = 0;
            }

            if (is_object($dc->ending_inventory)) {
                $endingInventory = $dc->ending_inventory->plastic
                  + $dc->ending_inventory->casing
                  + $dc->ending_inventory->control_interface
                  + $dc->ending_inventory->audio_modal
                  + $dc->ending_inventory->motherboard;
            } else {
                // handle the null or unexpected structure
                $endingInventory = 0;
            }

            $avgInventoryValue = ($beginningInventory + $endingInventory) / 2;

            $crossDocking = $this->calculateCrossDockingImpact(
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id
            );

            $rate = $crossDocking['holdingRateOverride']
              ?? ($distributionType === 'insourced' ? 0.03 : 0.05);

            $inventoryCharge += $avgInventoryValue * $rate * 3;
        }

        return $inventoryCharge;
    }

    /**
     * Calculate non-operating income for the P&L.
     *
     * Rules:
     *  - Uses previous month's Balance Sheet (quarter_id - 1) values for
     *    marketable_securities and loans.
     *  - Marketable securities => interest income (monthly rate, configurable).
     *  - Loans => interest expense (monthly rate, configurable).
     *
     * Note: This method queries FinancialQuarter directly (avoids circular service DI).
     *
     * @return float Non-operating income base (rounded to 2 decimals). Positive for income, negative for net interest expense.
     */
    public function calculateNonOperatingIncome(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ): float {
        // If quarter is 0 (startup) there's no previous month to read from
        $prevQuarter = max(0, $quarter_id - 1);

        // safe default rates (adjustable). These follow the simulation policy / manual assumptions.
        // Change here if policy changes.
        $marketableSecuritiesMonthlyRate = 0.005; // 0.5% per month (income)
        $loansMonthlyRate = 0.01;                 // 1.0% per month (expense)

        // Use FinancialQuarter directly — BalanceSheetService::getCashBalance uses the same model.
        $fq = FinancialQuarter::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter_id' => $prevQuarter,
        ])->first();

        $marketable = (float) ($fq->marketable_securities ?? 0.0);
        $loans = (float) ($fq->loans ?? 0.0);

        // Compute interest amounts
        // msInterest: positive income, loanInterest: negative expense
        $msInterest = $marketable * $marketableSecuritiesMonthlyRate;
        $loanInterest = -($loans * $loansMonthlyRate);

        // Net non-operating base (sum; in practice one of marketable or loans will be non-zero)
        $net = $msInterest + $loanInterest;

        // Round to 2 decimals to match other monetary fields
        return round($net, 2);
    }

    /**
     * Calculate disposal sale proceeds & disposal cost for DCs closed between prev-quarter and current quarter.
     */
    private function calculateDisposalSalesForClosedDcs(int $simulation_id, int $faculty_id, int $group_number, int $quarter_id): array
    {
        $disposalCash = 0.0;
        $disposalCost = 0.0;
        $closedCount = 0;

        // sanity: need a previous quarter to compare
        if ($quarter_id <= 0) {
            return [
                'disposalSaleIncome' => 0.0,
                'disposalCost' => 0.0,
                'closed_dcs' => 0,
            ];
        }

        // load prev and current operational DC rows (explicitly filter by sim/fac/group)
        $prevOperational = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id - 1,
            'is_operational' => 'yes',
        ])->get();

        $currOperational = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
            'is_operational' => 'yes',
        ])->get();

        // safe constants (manual)
        $disposalRate = 0.20;            // 20% disposal cost
        $closeOwnedDcCost = 0.0;         // set to manual value if closure fixed-cost applies

        foreach ($prevOperational as $prevDc) {
            // never dispose of a home-region DC
            if (isset($prevDc->is_home_region) && trim((string) $prevDc->is_home_region) === 'yes') {
                continue;
            }

            // Normalize keys for robust matching (prevents '01' vs '1 ' mismatch)
            $prevRegion = trim((string) ($prevDc->region_id ?? ''));
            $prevDcNum = trim((string) ($prevDc->dc_number ?? ''));
            $prevGroup = (int) ($prevDc->group_number ?? 0);

            $stillOpen = $currOperational->first(function ($c) use ($prevRegion, $prevDcNum, $prevGroup) {
                $currRegion = trim((string) ($c->region_id ?? ''));
                $currDcNum = trim((string) ($c->dc_number ?? ''));
                $currGroup = (int) ($c->group_number ?? 0);

                return $prevRegion === $currRegion
                  && $prevDcNum === $currDcNum
                  && $prevGroup === $currGroup
                  && (isset($c->is_operational) && trim((string) $c->is_operational) === 'yes');
            });

            if ($stillOpen) {
                // DC is still operational in current quarter — skip disposal
                continue;
            }

            // At this point: prev quarter had operational DC but no current-quarter match => treat closed
            // Load finished goods snapshot for prev quarter (must match sim/fac/group/region/dc)
            $stock = InventoryStockFinishedGood::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id - 1,
                'region_id' => $prevRegion,
                'dc_number' => $prevDcNum,
            ])->first();

            if (! $stock) {
                continue;
            }

            // safe read product quantities (tolerate naming variants)
            $p0_qty = (float) ($stock->product_0 ?? $stock->product_00 ?? 0);
            $p1_qty = (float) ($stock->product_01 ?? $stock->product_1 ?? 0);
            $p2_qty = (float) ($stock->product_02 ?? $stock->product_2 ?? 0);

            // Unit value: prefer balance-sheet valuation if you have it. Fallback to existing product cost calc:
            // Example fallback call — keep your existing method signature.
            $p0_unit = $this->calculateProductCost($simulation_id, $faculty_id, $group_number, 1, $quarter_id - 1, 0.5, 3, 1);
            $p1_unit = $this->calculateProductCost($simulation_id, $faculty_id, $group_number, 2, $quarter_id - 1, 0.5, 3, 1);
            $p2_unit = $this->calculateProductCost($simulation_id, $faculty_id, $group_number, 3, $quarter_id - 1, 0.5, 3, 1);

            $inventoryValue = ($p0_qty * $p0_unit) + ($p1_qty * $p1_unit) + ($p2_qty * $p2_unit);

            if ($inventoryValue <= 0) {
                continue;
            }

            $disposalCash += $inventoryValue;
            $disposalCost += $inventoryValue * $disposalRate;

            // optional: add fixed close cost for owned DC if manual requires it (set $closeOwnedDcCost accordingly)
            if (isset($prevDc->dc_type) && trim((string) $prevDc->dc_type) === 'owned' && $closeOwnedDcCost > 0) {
                $disposalCost += $closeOwnedDcCost;
            }

            $closedCount++;
        }

        return [
            'disposalSaleIncome' => round($disposalCash, 2),
            'disposalCost' => round($disposalCost, 2),
            'closed_dcs' => $closedCount,
        ];
    }

    /**
     * Calculates the corporate tax based on operating income generated in Region 1.
     */
    public function calculateCorporateTaxes($simulation_id, $faculty_id, $group_id, $quarter_id): float
    {
        $calculateCorporateTaxes = 0;

        // Get all products' P&L data
        $operatingIncome = $this->productPnlService->productPnl($simulation_id, $faculty_id, $group_id, $quarter_id);

        foreach ($operatingIncome as $income) {
            // Extract Region 1's operating income
            $region1OperatingIncome = $income->marketRegion->find(1)->operatingIncome ?? 0;

            // Apply 50% tax if income is positive
            $taxes = $region1OperatingIncome > 0 ? round($region1OperatingIncome * 0.50, 2) : 0.0;

            $calculateCorporateTaxes += $taxes;
        }

        return $calculateCorporateTaxes;
    }

    /**
     * Calculate unfilled for a single product.
     *
     * @param  Collection|array  $dcCenters  // array/collection of DCs (each must have region_id, dc_number)
     * @param  array|object  $manufacturing  // manufacturing info (may contain emergency limits keyed by product id)
     * @param  Collection|array  $actualDemand  // demands preloaded (or you can query inside)
     * @param  array  &$emergencyUsed  // passed by reference, updated with usage per product
     * @param  int  $group_id
     * @return array ['unfilled' => int, 'emergency_produced' => int, 'alloc_map' => [...]]
     */
    private function calculateUnfilledForProduct(
        int $product_id,
        $dcCenters,
        $manufacturing,
        $actualDemand,
        array &$emergencyUsed,
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ) {
        $logPrefix = "[P&L][prod:$product_id][sim:$simulation_id][grp:$group_number][q:$quarter_id]";

        // -----------------------------------------------------
        // 1) Emergency limit (correct: emergency_production)
        // -----------------------------------------------------
        $emergencyLimit = 0;

        if ($manufacturing instanceof Collection) {
            $m = $manufacturing->firstWhere('product_id', $product_id);
            if ($m) {
                $emergencyLimit = (int) ($m->emergency_production ?? 0);
            }
        } elseif (is_array($manufacturing)) {
            foreach ($manufacturing as $m) {
                $pid = is_array($m) ? ($m['product_id'] ?? null) : ($m->product_id ?? null);
                if ((int) $pid === $product_id) {
                    $emergencyLimit = (int) (
                        is_array($m) ? ($m['emergency_production'] ?? 0) : ($m->emergency_production ?? 0)
                    );
                    break;
                }
            }
        }

        // -----------------------------------------------------
        // 2) Demand by region
        // -----------------------------------------------------
        $demandByRegion = [];
        foreach ($actualDemand as $d) {
            if ((int) $d->product_id !== $product_id) {
                continue;
            }
            $r = (int) $d->region_id;
            $qty = (int) $d->demand_channel_01 + (int) $d->demand_channel_02;
            $demandByRegion[$r] = ($demandByRegion[$r] ?? 0) + $qty;
        }

        // -----------------------------------------------------
        // 3) Beginning inventory (previous quarter ending)
        // -----------------------------------------------------
        $column = [0 => 'product_0', 1 => 'product_01', 2 => 'product_02'][$product_id] ?? null;
        if (! $column) {
            throw new \Exception("Unknown product id: $product_id");
        }

        $prevQ = max(1, $quarter_id - 1);

        $beginRows = InventoryStockFinishedGood::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $prevQ,
        ])->get(['region_id', 'dc_number', $column]);

        $availableByDc = [];
        foreach ($beginRows as $r) {
            $key = "{$r->region_id}::{$r->dc_number}";
            $availableByDc[$key] = ($availableByDc[$key] ?? 0) + (int) $r->{$column};
        }

        // -----------------------------------------------------
        // 4) Add Postponed Production (P0 → P1/P2 inflow)
        // -----------------------------------------------------
        $ppRows = PostpondProduction::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
            'product_id' => $product_id,
        ])->get();

        foreach ($ppRows as $p) {
            $key = "{$p->region_id}::{$p->dc_number}";
            $availableByDc[$key] = ($availableByDc[$key] ?? 0) + (int) $p->quantity;
        }

        // -----------------------------------------------------
        // 5) Add Conversions (Postpond → Finished)
        // -----------------------------------------------------
        $convRows = PostpondConversion::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get();

        foreach ($convRows as $c) {
            $key = "{$c->region_id}::{$c->dc_number}";
            $qty = ($product_id == 1) ? (int) $c->converted_p1 : (($product_id == 2) ? (int) $c->converted_p2 : 0);

            $availableByDc[$key] = ($availableByDc[$key] ?? 0) + $qty;
        }

        // -----------------------------------------------------
        // 6) Add Transportation Arrivals (TransportationInfo)
        // -----------------------------------------------------
        try {
            if (Schema::hasTable('transportation_info')) {
                $ship = DB::table('transportation_info')
                    ->where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_number,
                        'quarter' => $quarter_id,
                        'product_id' => $product_id,
                    ])->get();

                foreach ($ship as $s) {
                    $key = "{$s->region_id}::{$s->dc_number}";
                    $arrived = (int) ($s->arrived_quantity ?? $s->arrived_qty ?? $s->delivered_qty ?? 0);
                    if ($arrived > 0) {
                        $availableByDc[$key] = ($availableByDc[$key] ?? 0) + $arrived;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("$logPrefix transportation arrival read error: ".$e->getMessage());
        }

        // -----------------------------------------------------
        // 7) Add Emergency Shipments
        // -----------------------------------------------------
        // -----------------------------------------------------
        // 7) Add Emergency Shipments (Plant → DC1)
        // -----------------------------------------------------
        try {
            if (Schema::hasTable('emergency_shipments')) {

                $es = DB::table('emergency_shipments')
                    ->where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_id' => $group_number,
                        'quarter' => $quarter_id,
                        'product_id' => $product_id,
                    ])->get();

                // 🔍 Find Plant DC (DC1)
                $plantDc = 1;
                $plantRegion = null;

                foreach ($dcCenters as $dc) {
                    if ((int) $dc->dc_number === $plantDc) {
                        $plantRegion = (int) $dc->region_id;
                        break;
                    }
                }

                // 🧠 Inject emergency shipments into plant DC
                if ($plantRegion !== null) {
                    $plantKey = "{$plantRegion}::{$plantDc}";

                    foreach ($es as $e) {
                        $q = (int) ($e->quantity ?? $e->qty ?? 0);

                        if ($q > 0) {
                            $availableByDc[$plantKey] = ($availableByDc[$plantKey] ?? 0) + $q;
                        }
                    }
                } else {
                    Log::warning("$logPrefix plant region not found while applying emergency shipments");
                }
            }
        } catch (\Exception $e) {
            Log::warning("$logPrefix emergency shipments read error: ".$e->getMessage());
        }

        // -----------------------------------------------------
        // 8) Add Plant Production
        // -----------------------------------------------------
        $plantDc = 1;
        $plantRegion = null;

        foreach ($dcCenters as $dc) {
            if ((int) $dc->dc_number === $plantDc) {
                $plantRegion = (int) $dc->region_id;
                break;
            }
        }

        if ($plantRegion !== null) {
            $prodQty = 0;

            if ($manufacturing instanceof Collection) {
                $m = $manufacturing->firstWhere('product_id', $product_id);
                if ($m) {
                    $prodQty = (int) ($m->production_quantity ?? 0);
                }
            } else {
                foreach ($manufacturing as $m) {
                    $pid = is_array($m) ? ($m['product_id'] ?? null) : ($m->product_id ?? null);
                    if ((int) $pid === $product_id) {
                        $prodQty = (int) (
                            is_array($m) ? ($m['production_quantity'] ?? 0) : ($m->production_quantity ?? 0)
                        );
                        break;
                    }
                }
            }

            if ($prodQty > 0) {
                $key = "{$plantRegion}::{$plantDc}";
                $availableByDc[$key] = ($availableByDc[$key] ?? 0) + $prodQty;
            }
        }

        // -----------------------------------------------------
        // 9) Convert DC → Region
        // -----------------------------------------------------
        $availableByRegion = [];
        foreach ($availableByDc as $key => $qty) {
            [$r, $dc] = explode('::', $key);
            $r = (int) $r;
            $availableByRegion[$r] = ($availableByRegion[$r] ?? 0) + $qty;
        }

        // Region list
        $regionList = array_unique(array_merge(
            array_map(fn ($d) => (int) $d->region_id, $dcCenters->all()),
            array_keys($availableByRegion),
            array_keys($demandByRegion)
        ));

        foreach ($regionList as $r) {
            $availableByRegion[$r] = $availableByRegion[$r] ?? 0;
            $demandByRegion[$r] = $demandByRegion[$r] ?? 0;
        }

        // -----------------------------------------------------
        // 10) Allocation Engine
        // -----------------------------------------------------
        $allocMap = [];
        $totalUnfilled = 0;
        $emergencyProduced = 0;

        foreach ($regionList as $r) {

            $need = $demandByRegion[$r];
            $fromFgi = $fromConv = $fromPP = $fromE = 0;

            // FGI
            $take = min($availableByRegion[$r], $need);
            $fromFgi += $take;
            $availableByRegion[$r] -= $take;
            $need -= $take;

            // Conversions
            // (We already added conversions into FGI pool, so this is empty by design)

            // Postpond
            // (Already merged into availableByDc → availableByRegion)

            // Inter-region
            if ($need > 0) {
                foreach ($availableByRegion as $r2 => $qty) {
                    if ($need <= 0 || $r2 == $r) {
                        continue;
                    }
                    $take = min($qty, $need);
                    if ($take > 0) {
                        $fromFgi += $take;
                        $availableByRegion[$r2] -= $take;
                        $need -= $take;
                    }
                }
            }

            // Emergency Production
            if ($need > 0) {
                $used = $emergencyUsed[$product_id] ?? 0;
                $canUse = max(0, $emergencyLimit - $used);

                $take = min($canUse, $need);
                if ($take > 0) {
                    $fromE += $take;
                    $emergencyUsed[$product_id] = $used + $take;
                    $emergencyProduced += $take;
                    $need -= $take;
                }
            }

            $unfilled = max(0, $need);
            $totalUnfilled += $unfilled;

            $allocMap[$r] = [
                'demand' => $demandByRegion[$r],
                'allocated' => $demandByRegion[$r] - $unfilled,
                'from_fgi' => $fromFgi,
                'from_conversion' => 0,
                'from_postpond' => 0,
                'from_emergency' => $fromE,
                'unfilled' => $unfilled,
            ];
        }

        // Debug:
        // Log::debug("$logPrefix Final Available = ".array_sum($availableByRegion)." | Total Unfilled = $totalUnfilled");

        return [
            'unfilled' => $totalUnfilled,
            'emergency_produced' => $emergencyProduced,
            'alloc_map' => $allocMap,
        ];
    }

    /**
     * Calculate total emergency procurement cost for a given quarter.
     *
     * This method implements the FLEXEE manual rules for emergency procurement,
     * which are applied automatically when normal procurement is insufficient.
     *
     * EMERGENCY RAW MATERIALS:
     * - Emergency unit cost = base raw material cost + $1/kg surcharge
     * - No volume discounts apply
     * - Administrative fee of $2,000 is charged per supplier per quarter
     *
     * EMERGENCY SUB-ASSEMBLIES:
     * - Always procured from the designated emergency supplier (Supplier D)
     * - Fixed per-unit emergency surcharges apply:
     *     • Motherboards        → +$8 per unit
     *     • Audio Modules       → +$6 per unit
     *     • Control Interfaces  → +$4 per unit
     * - No transportation or percentage uplift logic is used
     * - Administrative fee of $2,000 is charged per component per quarter
     *
     * These emergency costs represent severe penalties and are added directly
     * to Corporate P&L as emergency procurement expenses, significantly
     * increasing total costs and reducing gross margins.
     *
     * @param  int  $simulationId  Simulation identifier
     * @param  int  $facultyId  Faculty identifier
     * @param  int  $group  Group number
     * @param  int  $quarter  Quarter number
     * @return float Total emergency procurement cost for the quarter
     */
    protected function calculateEmergencyProcurementCost(
        int $simulationId,
        int $facultyId,
        int $group,
        int $quarter
    ): float {

        $totalEmergencyCost = 0.0;

        $procurements = Procurement::with([
            'procurementEmergencyRaw',
            'procurementEmergencySub',
            'procurementEmergencySub.subAssemblyComponent',
        ])->where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'group_number' => $group,
            'quarter' => $quarter,
        ])->get();

        foreach ($procurements as $procurement) {

            /* =========================================================
               * 1️⃣ RAW MATERIAL EMERGENCY
               * Base cost + $1/kg surcharge
               * + $2,000 admin fee per supplier per quarter
               * ========================================================= */

            $rawSuppliersUsed = [];

            foreach ($procurement->procurementEmergencyRaw as $raw) {

                // Base raw material cost
                $baseCost = RawMaterialCost::where(
                    'raw_material_id',
                    $raw->raw_material_id
                )->value('cost') ?? 0;

                // Emergency surcharge: +$1/kg
                $emergencyUnitCost = $baseCost + 1;

                $totalEmergencyCost += $emergencyUnitCost * $raw->quantity;

                // Track supplier for admin fee
                if (! empty($raw->supplier_id)) {
                    $rawSuppliersUsed[$raw->supplier_id] = true;
                }
            }

            // RAW admin fee: $2,000 per supplier
            $totalEmergencyCost += count($rawSuppliersUsed) * 2000;

            /* =========================================================
               * 2️⃣ SUB-ASSEMBLY EMERGENCY
               * Fixed per-unit surcharge + $2,000 admin per component
               * ========================================================= */

            $componentAdminTracker = [];

            foreach ($procurement->procurementEmergencySub as $sub) {

                // Component base cost (Supplier D enforced upstream)
                $componentCost = SupplierComponent::where([
                    'supplier_id' => $sub->supplier_id,
                    'sub_assembly_component_id' => $sub->sub_assembly_component_id,
                ])->value('cost') ?? 0;

                // Determine emergency surcharge per unit
                $componentName = strtolower(
                    optional($sub->subAssemblyComponent)->name ?? ''
                );

                $surcharge = 0;
                if (str_contains($componentName, 'motherboard')) {
                    $surcharge = 8;
                } elseif (str_contains($componentName, 'audio')) {
                    $surcharge = 6;
                } elseif (str_contains($componentName, 'control')) {
                    $surcharge = 4;
                }

                // Emergency unit cost
                $unitCost = $componentCost + $surcharge;

                $totalEmergencyCost += $unitCost * $sub->quantity;

                // Track component for admin fee
                $componentAdminTracker[$sub->sub_assembly_component_id] = true;
            }

            // SUB admin fee: $2,000 per component
            $totalEmergencyCost += count($componentAdminTracker) * 2000;
        }

        return round($totalEmergencyCost, 2);
    }

    /**
     * Calculate total procurement volume discount (RAW + SUB) for a quarter.
     *
     * Rules:
     * - Applies only to NORMAL procurement (excludes emergency)
     * - Uses volume slab tables as the source of truth
     * - Highest applicable slab is applied
     * - Discount is applied on total procurement cost
     *
     * @return float Total volume discount for the quarter
     */
    protected function calculateProcurementVolumeDiscount(
        int $simulationId,
        int $facultyId,
        int $group,
        int $quarter
    ): float {
        $totalDiscount = 0.0;

        $procurement = Procurement::with([
            'rawMaterial',
            'subAssembly',
        ])->where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'group_number' => $group,
            'quarter' => $quarter,
        ])->first();

        if (! $procurement) {
            return 0.0;
        }

        /**
         * ----------------------------------------
         * RAW MATERIAL VOLUME DISCOUNT
         * ----------------------------------------
         */
        $rawGroups = collect($procurement->rawMaterial)
            ->groupBy('raw_material_id');

        foreach ($rawGroups as $rawMaterialId => $items) {

            $totalQty = $items->sum('quantity');
            if ($totalQty <= 0) {
                continue;
            }

            $slab = RawMaterialVolumeDiscount::where('raw_material_id', $rawMaterialId)
                ->where('volume', '<=', $totalQty)
                ->orderByDesc('volume')
                ->first();

            if (! $slab || $slab->discount <= 0) {
                continue;
            }

            $unitCost = RawMaterialCost::where('raw_material_id', $rawMaterialId)
                ->value('cost') ?? 0;

            if ($unitCost <= 0) {
                continue;
            }

            $totalCost = $unitCost * $totalQty;
            $totalDiscount += $totalCost * ($slab->discount / 100);
        }

        /**
         * ----------------------------------------
         * SUB-ASSEMBLY VOLUME DISCOUNT
         * ----------------------------------------
         */
        $subGroups = collect($procurement->subAssembly)
            ->groupBy('sub_assembly_component_id');

        foreach ($subGroups as $componentId => $items) {

            $totalQty = $items->sum('quantity');
            if ($totalQty <= 0) {
                continue;
            }

            $slab = SubAssemblyVolumeDiscount::where('sub_assembly_component_id', $componentId)
                ->where('volume', '<=', $totalQty)
                ->orderByDesc('volume')
                ->first();

            if (! $slab || $slab->discount <= 0) {
                continue;
            }

            $componentCost = 0;

            foreach ($items as $item) {
                $unitCost = SupplierComponent::where([
                    'supplier_id' => $item->supplier_id,
                    'sub_assembly_component_id' => $componentId,
                ])->value('cost') ?? 0;

                if ($unitCost > 0) {
                    $componentCost += $unitCost * $item->quantity;
                }
            }

            $totalDiscount += $componentCost * ($slab->discount / 100);
        }

        return round($totalDiscount, 2);
    }

    /**
     * Calculate total Supplier Relationship + Supplier IT Sync + Carrier IT Sync cost.
     *
     * MANUAL ALIGNMENT:
     *
     * SUPPLIER RELATIONSHIP
     *  - $5,000 per UNIQUE supplier per quarter
     *  - $1,250 per procurement order
     *  - Consolidation applies (same supplier used multiple times counts once)
     *
     * SUPPLIER IT SYNC (Chapter 10)
     *  - Setup cost (Quarter 1 only)
     *  - Quarterly maintenance cost
     *  - Assumed ENABLED for all suppliers used
     *
     * CARRIER IT SYNC (Chapter 10)
     *  - Setup cost (Quarter 1 only)
     *  - Quarterly maintenance cost
     *  - Assumed ENABLED for all carriers used
     *
     * NOTE:
     *  - Cost-only implementation (behavioral effects applied later)
     *  - No database tables required
     */
    protected function calculateSupplierRelationshipAndITCost(
        int $simulationId,
        int $facultyId,
        int $group,
        int $quarter
    ): float {

        // ---------------------------------
        // FIXED COSTS (Manual)
        // ---------------------------------
        $RELATIONSHIP_FEE_PER_SUPPLIER = 5000;
        $ORDER_TRANSACTION_FEE = 1250;

        // ---------------------------------
        // SUPPLIER IT SYNC (Manual Chapter 10)
        // supplier_id => [setup, quarterly]
        // ---------------------------------
        $supplierItSync = [
            1 => ['setup' => 9000, 'quarterly' => 7000], // A
            2 => ['setup' => 8000, 'quarterly' => 7000], // B
            3 => ['setup' => 9000, 'quarterly' => 9000], // C
            4 => ['setup' => 9000, 'quarterly' => 8000], // D
            5 => ['setup' => 6000, 'quarterly' => 6000], // E
            6 => ['setup' => 7000, 'quarterly' => 5000], // F
            7 => ['setup' => 7000, 'quarterly' => 5000], // G
        ];

        // ---------------------------------
        // CARRIER IT SYNC (Manual Chapter 10)
        // carrier_id => [setup, quarterly]
        // ---------------------------------
        $carrierItSync = [
            1 => ['setup' => 9000, 'quarterly' => 7000], // I
            2 => ['setup' => 8000, 'quarterly' => 7000], // J
            3 => ['setup' => 9000, 'quarterly' => 9000], // K
            4 => ['setup' => 9000, 'quarterly' => 8000], // L
            5 => ['setup' => 6000, 'quarterly' => 6000], // M
            6 => ['setup' => 5000, 'quarterly' => 3000], // N
        ];

        $uniqueSuppliers = collect(); // supplier_id => true
        $uniqueCarriers = collect(); // carrier_id  => true
        $orderCount = 0;

        // ---------------------------------
        // 1️⃣ PROCUREMENT → suppliers + orders
        // ---------------------------------
        $procurements = Procurement::with([
            'rawMaterial',
            'subAssembly',
            'procurementEmergencyRaw',
            'procurementEmergencySub',
        ])->where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'group_number' => $group,
            'quarter' => $quarter,
        ])->get();

        foreach ($procurements as $procurement) {

            foreach (
                [
                    $procurement->rawMaterial,
                    $procurement->subAssembly,
                    $procurement->procurementEmergencyRaw,
                    $procurement->procurementEmergencySub,
                ] as $rows
            ) {

                foreach ($rows as $row) {
                    if (! empty($row->supplier_id)) {
                        $uniqueSuppliers->put($row->supplier_id, true);
                        $orderCount++;
                    }
                }
            }
        }

        // ---------------------------------
        // 2️⃣ TRANSPORTATION → carriers used
        // ---------------------------------
        $transportations = Transportation::with('transportationInfo')
            ->where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $group,
                'quarter' => $quarter,
            ])->get();

        foreach ($transportations as $transportation) {
            foreach ($transportation->transportationInfo as $info) {
                if (! empty($info->carrier_id)) {
                    $uniqueCarriers->put($info->carrier_id, true);
                }
            }
        }

        // ---------------------------------
        // 3️⃣ Supplier relationship costs
        // ---------------------------------
        $supplierRelationshipCost =
          $uniqueSuppliers->count() * $RELATIONSHIP_FEE_PER_SUPPLIER;

        $orderTransactionCost =
          $orderCount * $ORDER_TRANSACTION_FEE;

        // ---------------------------------
        // 4️⃣ Supplier IT Sync costs
        // ---------------------------------
        $supplierItCost = 0.0;

        foreach ($uniqueSuppliers->keys() as $supplierId) {
            if (! isset($supplierItSync[$supplierId])) {
                continue;
            }

            // One-time setup (Quarter 1 only)
            if ($quarter === 1) {
                $supplierItCost += $supplierItSync[$supplierId]['setup'];
            }

            // Quarterly maintenance
            $supplierItCost += $supplierItSync[$supplierId]['quarterly'];
        }

        // ---------------------------------
        // 5️⃣ Carrier IT Sync costs
        // ---------------------------------
        $carrierItCost = 0.0;

        foreach ($uniqueCarriers->keys() as $carrierId) {
            if (! isset($carrierItSync[$carrierId])) {
                continue;
            }

            // One-time setup (Quarter 1 only)
            if ($quarter === 1) {
                $carrierItCost += $carrierItSync[$carrierId]['setup'];
            }

            // Quarterly maintenance
            $carrierItCost += $carrierItSync[$carrierId]['quarterly'];
        }

        // ---------------------------------
        // 6️⃣ TOTAL
        // ---------------------------------
        return round(
            $supplierRelationshipCost +
              $orderTransactionCost +
              $supplierItCost +
              $carrierItCost,
            2
        );
    }

    /**
     * Calculate Emergency Production Variable Cost (EXCLUDING standby).
     *
     * MANUAL REFERENCE:
     * Emergency production incurs multiple penalty layers:
     *
     * 1) Base emergency usage charge:
     *    - SHA: $2 per unit
     *    - ST : $3 per unit
     *
     * 2) Production cost premium (based on REGULAR production unit cost):
     *    - First 5,000 emergency units → +50% premium
     *    - Units beyond 5,000        → +100% premium
     *
     * 3) Emergency transportation premium:
     *    - All emergency units ship by air
     *    - Flat $20 per unit surcharge
     *
     * NOTE:
     * - This method calculates ONLY the variable emergency cost.
     * - Standby cost ($0.50 × declared limit) is handled separately.
     * - Regular product cost is NOT included here (already counted elsewhere).
     *
     * @param  int  $productId  Product ID (1 = SHA, 2 = ST)
     * @param  int  $emergencyUnits  Units produced under emergency
     * @param  float  $regularUnitProductionCost  Regular per-unit production cost
     * @return float Emergency production cost (variable portion only)
     */
    protected function calculateEmergencyProductionCost(
        int $productId,
        int $emergencyUnits,
        float $regularUnitProductionCost
    ): float {

        if ($emergencyUnits <= 0) {
            return 0.0;
        }

        // ----------------------------------
        // Manual constants
        // ----------------------------------
        $BASE_CHARGE_PER_UNIT = ($productId === 1) ? 2.0 : 3.0; // SHA vs ST
        $AIR_SHIPPING_PREMIUM = 20.0;                            // per unit

        // ----------------------------------
        // Tiered emergency production volumes
        // ----------------------------------
        $tier1Units = min($emergencyUnits, 5000);
        $tier2Units = max(0, $emergencyUnits - 5000);

        // ----------------------------------
        // Tiered production premiums
        // ----------------------------------
        $tier1Premium =
          $tier1Units * ($regularUnitProductionCost * 0.50);

        $tier2Premium =
          $tier2Units * ($regularUnitProductionCost * 1.00);

        // ----------------------------------
        // Base emergency usage charge
        // ----------------------------------
        $baseUsageCharge =
          $emergencyUnits * $BASE_CHARGE_PER_UNIT;

        // ----------------------------------
        // Emergency air-shipping premium
        // ----------------------------------
        $transportPremium =
          $emergencyUnits * $AIR_SHIPPING_PREMIUM;

        // ----------------------------------
        // Total variable emergency cost
        // ----------------------------------
        return round(
            $baseUsageCharge +
              $tier1Premium +
              $tier2Premium +
              $transportPremium,
            2
        );
    }

    /**
     * Calculate Cross-Docking cost and benefits.
     *
     * MANUAL ALIGNMENT:
     * - Cross-docking is implemented per CARRIER.
     * - Costs:
     *     • $7,500 one-time setup per carrier (Q1 only)
     *     • $2,500 per carrier per quarter (ongoing)
     * - Benefits (REGULAR shipments only):
     *     • Inventory holding charge reduction
     *     • DC operating cost reduction
     * - NO benefit applies to emergency shipments.
     *
     * ASSUMPTIONS:
     * - All carriers used in the quarter are cross-docked
     *   (no decision table yet).
     *
     * @return array {
     *               crossDockingCost,
     *               holdingRateOverride,
     *               dcOperatingSavings
     *               }
     */
    protected function calculateCrossDockingImpact(
        int $simulationId,
        int $facultyId,
        int $group,
        int $quarter
    ): array {

        // ----------------------------------
        // Manual constants
        // ----------------------------------
        $SETUP_COST = 7500;
        $QUARTERLY_COST = 2500;
        $OPERATING_SAVING_PER_CARRIER_PER_DC = 1000;

        // Holding charge overrides
        $HOLDING_RATE = [
            'outsourced' => 0.025, // 2.5%
            'owned' => 0.015, // 1.5%
        ];

        $uniqueCarriers = collect();
        $regionalDcCount = 0;

        // ----------------------------------
        // 1️⃣ Identify carriers used
        // ----------------------------------
        $transportations = Transportation::with('transportationInfo')
            ->where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $group,
                'quarter' => $quarter,
            ])->get();

        foreach ($transportations as $t) {
            foreach ($t->transportationInfo as $info) {
                if (! empty($info->carrier_id)) {
                    $uniqueCarriers->put($info->carrier_id, true);
                }
            }
        }

        $carrierCount = $uniqueCarriers->count();

        if ($carrierCount === 0) {
            return [
                'crossDockingCost' => 0,
                'holdingRateOverride' => null,
                'dcOperatingSavings' => 0,
            ];
        }

        // ----------------------------------
        // 2️⃣ Count regional DCs (R2 + R3)
        // ----------------------------------
        $distributionCenters = Distribution::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'group_number' => $group,
        ])->whereIn('region_id', [2, 3])
            ->where('is_operational', 'yes')
            ->get();

        foreach ($distributionCenters as $dc) {
            if (in_array($dc->type, ['outsourced', 'owned'])) {
                $regionalDcCount++;
            }
        }

        // ----------------------------------
        // 3️⃣ Cross-docking COST
        // ----------------------------------
        $crossDockingCost =
          ($quarter === 1 ? $carrierCount * $SETUP_COST : 0)
          + ($carrierCount * $QUARTERLY_COST);

        // ----------------------------------
        // 4️⃣ Operating cost savings
        // ----------------------------------
        $dcOperatingSavings =
          $carrierCount * $regionalDcCount * $OPERATING_SAVING_PER_CARRIER_PER_DC;

        // ----------------------------------
        // 5️⃣ Determine holding charge override
        // ----------------------------------
        $holdingRateOverride = null;

        if ($regionalDcCount > 0) {
            // If ANY owned DC exists, use owned rate
            $hasOwned = $distributionCenters->contains('type', 'owned');

            $holdingRateOverride =
              $hasOwned ? $HOLDING_RATE['owned'] : $HOLDING_RATE['outsourced'];
        }

        return [
            'crossDockingCost' => round($crossDockingCost, 2),
            'holdingRateOverride' => $holdingRateOverride,
            'dcOperatingSavings' => round($dcOperatingSavings, 2),
        ];
    }

    /**
     * Filter the given model data based on simulation, faculty, group, and quarter.
     */
    protected function filterBySimulation($model, $simulation_id, $faculty_id, $group_id, $quarter_id, $extraConditions = [])
    {
        return $model::where(array_merge([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ], $extraConditions))->get();
    }
}
