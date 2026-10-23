<?php

namespace App\Services\Reports\Supplementary;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Region;
use App\Models\SimulationTeam;
use App\Models\SubAssemblyComponent;
use App\Models\Supplier;
use App\Models\SupplierComponentPrice;
use App\Services\Reports\CorporatePnlService;

class IndustryBulletinService
{
    public function getIndustryBulletinData($simulationId, $facultyId, $groupId, $quarterId)
    {
        return [
            'totalUnitsSold' => $this->getTotalUnitsSold($simulationId, $facultyId, $quarterId),
            'marketShares' => $this->calculateMarketShares($simulationId, $facultyId, $quarterId),
            'revenueLeaders' => $this->getRevenueLeaders($simulationId, $facultyId, $quarterId),
            'openedDCs' => $this->getDistributionCenters($simulationId, $facultyId, $quarterId, 'yes'),
            'closedDCs' => $this->getDistributionCenters($simulationId, $facultyId, $quarterId, 'no'),
            'supplierPriceAlerts' => $this->getSupplierPriceAlerts($simulationId, $facultyId, $quarterId),
        ];
    }

    public function getTotalUnitsSold($simulationId, $facultyId, $quarterId)
    {
        $corporatePnlService = app(CorporatePnlService::class);

        // Firm master list (correct source)
        $groups = SimulationTeam::where([
            'simulation_id' => $simulationId,
        ])->pluck('group_number');

        $totalUnitsSold = 0;

        foreach ($groups as $groupNumber) {

            $pnl = $corporatePnlService
                ->corporateProfitLoss($simulationId, $facultyId, $groupNumber, $quarterId);

            // ✅ ACTUAL SALES (already validated)
            $totalUnitsSold += (int) ($pnl->summary->fulfilledUnits ?? 0);
            // (productSalesVolume would also be fine)
        }

        return $totalUnitsSold;
    }

    public function calculateMarketShares($simulationId, $facultyId, $quarterId)
    {
        $actualDemands = ActualDemand::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
        ])->get();

        $totalsByFirm = $actualDemands->groupBy('group_number')->map(function ($items) {
            return $items->sum(fn ($d) => $d->demand_channel_01 + $d->demand_channel_02);
        });

        $totalUnits = $totalsByFirm->sum();

        return $totalsByFirm->mapWithKeys(function ($units, $firmId) use ($totalUnits) {
            return [$firmId => round(($units / max(1, $totalUnits)) * 100, 2)];
        })->sortDesc()->toArray();
    }

    public function getRevenueLeaders($simulationId, $facultyId, $quarterId)
    {
        $actualDemands = ActualDemand::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
        ])->get();

        $demandPlanning = DemandPlanning::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
        ])->get();

        $revenues = [];

        foreach ($actualDemands->groupBy('group_number') as $groupId => $records) {
            $totalRevenue = 0;

            foreach ($records as $record) {
                $planning = $demandPlanning->first(function ($plan) use ($record) {
                    return $plan->product_id == $record->product_id &&
                        $plan->region_id == $record->region_id &&
                        $plan->group_number == $record->group_number;
                });

                $price = $planning ? $planning->price : 0;
                $totalRevenue += ($record->demand_channel_01 + $record->demand_channel_02) * $price;
            }

            $revenues[$groupId] = round($totalRevenue, 2);
        }

        return collect($revenues)->sortDesc()->take(3)->toArray();
    }

    public function getDistributionCenters($simulationId, $facultyId, $quarterId, $status)
    {
        return Distribution::where('region_id', '!=', 1)
            ->where('dc_number', '!=', 1)
            ->where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $quarterId,
                'is_operational' => $status,
            ])->get()->map(function ($dc) {
                return [
                    'firm' => 'Firm '.$dc->group_number,
                    'region' => optional(Region::find($dc->region_id))->name ?? 'Region #'.$dc->region_id,
                ];
            })->toArray();
    }

    public function getSupplierPriceAlerts($simulationId, $facultyId, $quarterId)
    {
        $nextQuarter = $quarterId + 1;

        $currentPrices = SupplierComponentPrice::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter_id' => $quarterId,
        ])->get()->keyBy(fn ($item) => $item->supplier_id.'_'.$item->sub_assembly_component_id);

        $nextPrices = SupplierComponentPrice::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter_id' => $nextQuarter,
        ])->get();

        $alerts = [];

        foreach ($nextPrices as $newPrice) {
            $key = $newPrice->supplier_id.'_'.$newPrice->sub_assembly_component_id;

            if (! isset($currentPrices[$key])) {
                continue;
            }

            $old = $currentPrices[$key];

            if ($newPrice->cost > $old->cost) {
                $alerts[] = [
                    'supplier' => optional(Supplier::find($newPrice->supplier_id))->name ?? 'Supplier #'.$newPrice->supplier_id,
                    'component' => optional(SubAssemblyComponent::find($newPrice->sub_assembly_component_id))->name ?? 'Component #'.$newPrice->sub_assembly_component_id,
                    'old_price' => $old->cost,
                    'new_price' => $newPrice->cost,
                ];
            }
        }

        return $alerts;
    }
}
