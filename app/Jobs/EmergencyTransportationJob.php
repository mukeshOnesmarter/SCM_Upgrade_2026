<?php

namespace App\Jobs;

use App\Models\Decisions\EmergencyProduction;
use App\Models\Decisions\EmergencyShipment;
use App\Services\EmergencyTransportationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyTransportationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    protected int $simulationId;

    protected int $facultyId;

    protected ?int $groupId;

    protected int $quarterId;

    public function __construct(
        int $simulationId,
        int $facultyId,
        ?int $groupId,
        int $quarterId
    ) {
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
                "emg-transport:{$this->simulationId}:{$this->groupId}:{$this->quarterId}",
                600
            ),
        ];
    }

    public function handle(EmergencyTransportationService $transport)
    {
        DB::beginTransaction();

        try {
            // 🔐 Lock productions to avoid race duplication
            $productions = EmergencyProduction::where([
                'simulation_id' => $this->simulationId,
                'quarter' => $this->quarterId,
            ])
                ->when($this->groupId !== null, fn ($q) => $q->where('group_id', $this->groupId)
                )
                ->lockForUpdate()
                ->get();

            if ($productions->isEmpty()) {
                DB::commit();

                return;
            }

            foreach ($productions as $prod) {

                // ✅ STRONG idempotency: 1 shipment per production
                $alreadyShipped = EmergencyShipment::where(
                    'emergency_production_id',
                    $prod->id
                )->exists();

                if ($alreadyShipped) {
                    continue;
                }

                $units = (int) $prod->quantity;
                if ($units <= 0) {
                    continue;
                }

                $baseFreight = $transport->fetchBaseFreightPerUnit(
                    $this->simulationId,
                    $prod->product_id
                );

                [$unitFreight, $totalFreight, $freightMeta] =
                    $transport->computeAirFreight($baseFreight, $units);

                // ✅ Apply freight ONCE
                $prod->update([
                    'estimated_unit_cost' => round($prod->estimated_unit_cost + $unitFreight, 4),
                    'estimated_total_cost' => round($prod->estimated_total_cost + $totalFreight, 2),
                    'meta' => array_merge((array) $prod->meta, [
                        'freight' => $freightMeta,
                        'freight_applied_at' => Carbon::now()->toDateTimeString(),
                    ]),
                ]);

                // ✅ Shipment row linked to production
                EmergencyShipment::create([
                    'simulation_id' => $prod->simulation_id,
                    'faculty_id' => $this->facultyId,
                    'group_id' => $prod->group_id,
                    'quarter' => $prod->quarter,
                    'emergency_production_id' => $prod->id,
                    'product_id' => $prod->product_id,
                    'quantity' => $units,
                    'unit_freight' => $unitFreight,
                    'total_freight' => $totalFreight,
                    'mode' => 'air',
                    'meta' => $freightMeta,
                ]);
            }

            DB::commit();

            Log::info('[Emergency Production] completed', [
                'simulation' => $this->simulationId,
                'group' => $this->groupId,
                'quarter' => $this->quarterId,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('EmergencyTransportationJob failed', [
                'error' => $e->getMessage(),
                'sim' => $this->simulationId,
                'quarter' => $this->quarterId,
            ]);

            throw $e;
        }
    }
}
