<?php

namespace App\Services;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Region;
use App\Models\SubAssemblyComponent;
use App\Models\Supplier;
use App\Models\SupplierComponentPrice;
use Illuminate\Support\Facades\DB;

class QuarterSummaryService
{
    public function getTotalUnitsSold($simulationId, $facultyId, $quarterId)
    {
        return ActualDemand::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
        ])->sum(DB::raw('demand_channel_01 + demand_channel_02'));
    }

    public function getMarketShares($simulationId, $facultyId, $quarterId)
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
                $planning = $demandPlanning->firstWhere(function ($plan) use ($record) {
                    return $plan->product_id == $record->product_id &&
                      $plan->region_id == $record->region_id &&
                      $plan->group_number == $record->group_number;
                });

                $retailPrice = $planning ? $planning->price : 0;
                $retailUnits = $record->demand_channel_01;
                $directUnits = $record->demand_channel_02;

                $totalRevenue += $retailUnits * $retailPrice;
                $totalRevenue += $directUnits * $retailPrice; // If direct price differs, add logic accordingly
            }

            $revenues[$groupId] = round($totalRevenue, 2);
        }

        return collect($revenues)
            ->sortDesc()
            ->take(3)
            ->toArray();
    }

    public function getOpenedDCs($simulationId, $facultyId, $quarterId)
    {
        return Distribution::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
            'is_operational' => 'yes',
        ])->get()->map(function ($dc) {
            return [
                'firm' => 'Firm '.$dc->group_number,
                'region' => Region::find($dc->region_id) ? Region::find($dc->region_id)->name : 'Region #'.$dc->region_id,
            ];
        })->toArray();
    }

    public function getClosedDCs($simulationId, $facultyId, $quarterId)
    {
        return Distribution::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
            'is_operational' => 'no',
        ])->get()->map(function ($dc) {
            return [
                'firm' => 'Firm '.$dc->group_number,
                'region' => Region::find($dc->region_id) ? Region::find($dc->region_id)->name : 'Region #'.$dc->region_id,
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
                    'supplier' => Supplier::find($newPrice->supplier_id)
                      ? Supplier::find($newPrice->supplier_id)->name
                      : 'Supplier #'.$newPrice->supplier_id,
                    'component' => SubAssemblyComponent::find($newPrice->sub_assembly_component_id)
                      ? SubAssemblyComponent::find($newPrice->sub_assembly_component_id)->name
                      : 'Component #'.$newPrice->sub_assembly_component_id,
                    'old_price' => $old->cost,
                    'new_price' => $newPrice->cost,
                ];
            }
        }

        return $alerts;
    }
}
