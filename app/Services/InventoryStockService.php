<?php

namespace App\Services;

use App\Services\Reports\Supplementary\FinishedGoodsInventoryService;
use App\Services\Reports\Supplementary\ProcurementService;
use Illuminate\Support\Facades\Log;

class InventoryStockService
{
    protected $procurementService;

    protected $finishedGoodsInventoryService;

    public function __construct(
        ProcurementService $procurementService,
        FinishedGoodsInventoryService $finishedGoodsInventoryService
    ) {
        $this->procurementService = $procurementService;
        $this->finishedGoodsInventoryService = $finishedGoodsInventoryService;
    }

    /**
     * Store raw material or sub-assembly ending inventory for all groups (DCs)
     */
    public function storeEndingInventory(int $simulationId, int $facultyId, int $quarterId, int $groupNumber)
    {
        try {
            $this->procurementService->procurement(
                $simulationId,
                $facultyId,
                $quarterId,
                $groupNumber,
                true
            );
        } catch (\Throwable $e) {
            Log::error('SAC Inventory Stock Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Store finished goods ending inventory for all groups (DCs)
     */
    public function storeEndingInventoryFinishGood(int $simulationId, int $facultyId, int $quarterId, int $groupNumber)
    {
        try {
            $this->finishedGoodsInventoryService->finishedGoodsInventory(
                $simulationId,
                $facultyId,
                $quarterId,
                $groupNumber,
                true
            );
        } catch (\Throwable $e) {
            Log::error('FGI Stock Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
