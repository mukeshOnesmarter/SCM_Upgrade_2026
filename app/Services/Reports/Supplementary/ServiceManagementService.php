<?php

namespace App\Services\Reports\Supplementary;

use App\Models\Decisions\ServiceManagement;
use App\Models\Product;
use App\Models\Region;
use App\Services\ActiveUserService;
use App\Services\Reports\ProductPnlService;
use Illuminate\Support\Facades\Log;

class ServiceManagementService
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    public function serviceManagement(
        int $simulation_id,
        int $faculty_id,
        int $quarter_id,
        int $group_id
    ) {
        try {

            /** -------------------------------------------------
             * CONFIG
             * ------------------------------------------------- */
            $CALLS_PER_UNIT = 0.20; // simulation-safe constant

            /** -------------------------------------------------
             * 1️⃣ GET PRODUCT P&L (AUTHORITATIVE SALES SOURCE)
             * ------------------------------------------------- */
            $productPnlService = app(ProductPnlService::class);

            $products = $productPnlService->productPnl(
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id
            );

            /** -------------------------------------------------
             * 2️⃣ REGIONS LOOP
             * ------------------------------------------------- */
            $regions = Region::all()->map(function ($region) use (
                $products,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $CALLS_PER_UNIT
            ) {

                /** ---------------------------------------------
                 * 2.1 REGION SERVICE DECISION
                 * --------------------------------------------- */
                $serviceManagement = ServiceManagement::with('serviceOutsourcing.serviceCost')
                    ->where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $region->id,
                    ])
                    ->first();

                $costPerCall = optional(
                    optional($serviceManagement->serviceOutsourcing)->serviceCost
                )->where('region_id', $region->id)->first()->cost_call ?? 0;

                /** ---------------------------------------------
                 * 2.2 INITIALIZE TOTALS
                 * --------------------------------------------- */
                $region->totalCalls = 0;
                $region->serviceOutsourcingTotal = 0;

                /** ---------------------------------------------
                 * 2.3 PRODUCT LOOP (SALES-DRIVEN)
                 * --------------------------------------------- */
                $region->products = $products->map(function ($product) use (
                    $region,
                    $CALLS_PER_UNIT,
                    &$regionCalls,
                    &$regionCost,
                    $costPerCall
                ) {

                    // Find region-level fulfilled units from P&L
                    $regionRow = $product->marketRegion
                        ->firstWhere('id', $region->id);

                    $fulfilledUnits = (int) ($regionRow->salesVolumechannel_01 ?? 0)
                      + (int) ($regionRow->salesVolumechannel_02 ?? 0);

                    // Calls generated
                    $calls = (int) round($fulfilledUnits * $CALLS_PER_UNIT);

                    // Cost
                    $serviceCost = $calls * $costPerCall;

                    // Attach for reporting
                    $productClone = clone $product;
                    $productClone->fulfilledUnits = $fulfilledUnits;
                    $productClone->calls = $calls;
                    $productClone->csr_cost = $costPerCall;
                    $productClone->serviceOutsourcingCost = $serviceCost;

                    return $productClone;
                });

                /** ---------------------------------------------
                 * 2.4 REGION TOTALS
                 * --------------------------------------------- */
                $region->totalCalls = $region->products->sum('calls');
                $region->serviceOutsourcingTotal = $region->products->sum('serviceOutsourcingCost');
                $region->costPerCall = $costPerCall;

                return $region;
            });

            return $regions;
        } catch (\Exception $e) {
            Log::error('ServiceManagementService Error: '.$e->getMessage());

            return collect();
        }
    }
}
