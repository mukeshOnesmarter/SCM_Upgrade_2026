<?php

namespace App\Jobs;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\Manufacturing;
use App\Services\EmergencyProductionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyProductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    protected int $simulationId;

    protected int $facultyId;

    protected int $groupId;

    protected int $quarterId;

    public function __construct(int $simulationId, int $facultyId, int $groupId, int $quarterId)
    {
        $this->simulationId = $simulationId;
        $this->facultyId = $facultyId;
        $this->groupId = $groupId;
        $this->quarterId = $quarterId;

        $this->onQueue('quarter-closure');
    }

    public function middleware()
    {
        return [
            new WithoutOverlapping(
                "emg-prod:{$this->simulationId}:{$this->groupId}:{$this->quarterId}",
                600
            ),
        ];
    }

    /**
     * PURE emergency supply creation.
     * - Reads demand
     * - Creates EmergencyProduction
     * - DOES NOT touch ActualDemand or fulfillment
     */
    public function handle(EmergencyProductionService $service)
    {
        $cacheKey = "flexee:emg_prod:{$this->simulationId}:{$this->groupId}:{$this->quarterId}";

        if (Cache::has($cacheKey)) {
            Log::info('EmergencyProductionJob skipped (cache)', [
                'sim' => $this->simulationId,
                'group' => $this->groupId,
                'quarter' => $this->quarterId,
            ]);

            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(30));

        DB::beginTransaction();
        try {

            // 1️⃣ Aggregate demand per product (READ ONLY)
            $demands = ActualDemand::query()
                ->where([
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group_number' => $this->groupId,
                    'quarter' => $this->quarterId,
                ])
                ->select('product_id', DB::raw('SUM(demand_channel_01 + demand_channel_02) as demand'))
                ->groupBy('product_id')
                ->get();

            if ($demands->isEmpty()) {
                DB::commit();

                return;
            }

            // 2️⃣ Planned production → available plant capacity
            $plannedThisQuarter = Manufacturing::where([
                'simulation_id' => $this->simulationId,
                'faculty_id' => $this->facultyId,
                'group_number' => $this->groupId,
                'quarter' => $this->quarterId,
            ])->sum('production_quantity') ?: 0;

            $availablePlantCapacity = $service->computeAvailablePlantCapacity($plannedThisQuarter);

            foreach ($demands as $row) {
                $productId = (int) $row->product_id;
                $demandQty = (int) $row->demand;

                // product_id = 0 is postponed source, never demand
                if ($productId === 0 || $demandQty <= 0) {
                    continue;
                }

                // 3️⃣ Emergency production limits
                $manufacturing = Manufacturing::where([
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group_number' => $this->groupId,
                    'quarter' => $this->quarterId,
                    'product_id' => $productId,
                ])->first();

                $limit = (int) (
                    $manufacturing->emergency_production
                    ?? config('simulation.emergency_limit_per_product', 10000)
                );

                $alreadyProduced = $service->alreadyEmergencyProducedThisQuarter(
                    $this->simulationId,
                    $productId,
                    $this->quarterId
                );

                $availableByLimit = max(0, $limit - $alreadyProduced);
                $allowed = min($demandQty, $availableByLimit, $availablePlantCapacity);

                if ($allowed <= 0) {
                    continue;
                }

                // 4️⃣ Cost calculation
                $baseUnitCost = $service->fetchBaseUnitProductionCost(
                    $this->simulationId,
                    $productId
                );

                [$unitCost, $totalCost] =
                  $service->computeEmergencyCostTiered($baseUnitCost, $allowed);

                // 5️⃣ Create EmergencyProduction (idempotent)
                $service->createOrGetProduction([
                    'simulation_id' => $this->simulationId,
                    'faculty_id' => $this->facultyId,
                    'group_id' => $this->groupId,
                    'quarter' => $this->quarterId,
                    'product_id' => $productId,
                    'quantity' => $allowed,
                    'estimated_unit_cost' => $unitCost,
                    'estimated_total_cost' => $totalCost,
                    'source' => 'in_house',
                    'status' => 'placed',
                    'estimated_arrival_at' => Carbon::now(),
                    'meta' => [
                        'base_unit_cost' => $baseUnitCost,
                        'limit' => $limit,
                        'capacity_used' => $allowed,
                    ],
                ]);

                $availablePlantCapacity -= $allowed;
                if ($availablePlantCapacity <= 0) {
                    break;
                }
            }

            DB::commit();

            Log::info('[Emergency Production] completed', [
                'simulation' => $this->simulationId,
                'group' => $this->groupId,
                'quarter' => $this->quarterId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('EmergencyProductionJob failed', [
                'error' => $e->getMessage(),
                'sim' => $this->simulationId,
                'group' => $this->groupId,
                'quarter' => $this->quarterId,
            ]);

            throw $e;
        }
    }
}
