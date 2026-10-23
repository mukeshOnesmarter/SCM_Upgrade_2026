<?php

namespace App\Services;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\Demand\InitialDemand;
use App\Models\Decisions\Demand\ProbabilityDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\InformationTechnologyCarrier;
use App\Models\Decisions\InformationTechnologySupplier;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ProcurementRawMaterial;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\Decisions\TransportationInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DecisionDataSyncService
{
    protected $models = [
        InitialDemand::class,
        ProbabilityDemand::class,
        ActualDemand::class,
        Forecasting::class,
        Procurement::class,
        Manufacturing::class,
        Distribution::class,
        Transportation::class,
        DemandPlanning::class,
        ServiceManagement::class,
        InformationTechnology::class,
    ];

    public function syncQuarterData($simulation_id, $faculty_id, $group_id, $quarter_to_check)
    {
        DB::beginTransaction();

        try {
            $this->syncPreviousQuarterData($simulation_id, $faculty_id, $group_id, $quarter_to_check);
            $this->prepareNextQuarterDistribution($simulation_id, $faculty_id, $group_id, $quarter_to_check);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('DecisionDataSyncService error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function syncPreviousQuarterData($simulation_id, $faculty_id, $group_id, $quarter_to_check)
    {
        $source_quarter = $quarter_to_check - 1;

        foreach ($this->models as $model) {
            $exists = $model::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_to_check,
                'group_number' => $group_id,
            ])->exists();

            if (! $exists) {
                $source_data = $model::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $source_quarter,
                    'group_number' => $group_id,
                ])->get();

                foreach ($source_data as $record) {
                    $new_record = $record->replicate();
                    $new_record->quarter = $quarter_to_check;
                    $new_record->save();

                    if ($model === Procurement::class) {
                        $this->copyProcurementChildren($record->id, $new_record->id);
                    }

                    if ($model === Transportation::class) {
                        $this->copyTransportationChildren($record->id, $new_record->id);
                    }

                    if ($model === InformationTechnology::class) {
                        $this->copyInformationTechnologyChildren($record->id, $new_record->id);
                    }
                }
            }
        }
    }

    protected function prepareNextQuarterDistribution($simulation_id, $faculty_id, $group_id, $current_quarter)
    {
        $next_quarter = $current_quarter + 1;

        $currentData = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'quarter' => $current_quarter,
            'group_number' => $group_id,
            'is_operational' => 'yes', // Only operational DCs
        ])->get();

        foreach ($currentData as $record) {

            $exists = Distribution::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $next_quarter,
                'group_number' => $group_id,
                'region_id' => $record->region_id,
            ])->exists();

            if (! $exists) {
                $newRecord = $record->replicate();
                $newRecord->quarter = $next_quarter;
                $newRecord->save();
            }
        }
    }

    protected function copyProcurementChildren($oldProcurementId, $newProcurementId)
    {
        $rawMaterials = ProcurementRawMaterial::where('procurement_id', $oldProcurementId)->get();
        foreach ($rawMaterials as $item) {
            $newItem = $item->replicate();
            $newItem->procurement_id = $newProcurementId;
            $newItem->save();
        }

        $subAssemblies = ProcurementSubAssembly::where('procurement_id', $oldProcurementId)->get();
        foreach ($subAssemblies as $item) {
            $newItem = $item->replicate();
            $newItem->procurement_id = $newProcurementId;
            $newItem->save();
        }
    }

    protected function copyTransportationChildren($oldTransportationId, $newTransportationId)
    {
        $infos = TransportationInfo::where('transportation_id', $oldTransportationId)->get();
        foreach ($infos as $item) {
            $newItem = $item->replicate();
            $newItem->transportation_id = $newTransportationId;
            $newItem->save();
        }
    }

    protected function copyInformationTechnologyChildren($oldItId, $newItId)
    {
        $carriers = InformationTechnologyCarrier::where('information_technology_id', $oldItId)->get();
        foreach ($carriers as $carrier) {
            $newCarrier = $carrier->replicate();
            $newCarrier->information_technology_id = $newItId;
            $newCarrier->save();
        }

        $suppliers = InformationTechnologySupplier::where('information_technology_id', $oldItId)->get();
        foreach ($suppliers as $supplier) {
            $newSupplier = $supplier->replicate();
            $newSupplier->information_technology_id = $newItId;
            $newSupplier->save();
        }
    }
}
