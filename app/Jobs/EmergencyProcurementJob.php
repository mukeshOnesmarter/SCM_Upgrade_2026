<?php

namespace App\Jobs;

use App\Services\EmergencyProcurementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmergencyProcurementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $simulation_id;

    protected $faculty_id;

    protected $group_number;

    protected $quarter_id;

    public function __construct(int $simulation_id, int $faculty_id, int $group_number, int $quarter_id)
    {
        $this->simulation_id = $simulation_id;
        $this->faculty_id = $faculty_id;
        $this->group_number = $group_number;
        $this->quarter_id = $quarter_id;
    }

    public function handle(EmergencyProcurementService $emergencyProcurementService): void
    {
        $emergencyProcurementService->handleEmergencyProcurement(
            $this->simulation_id,
            $this->faculty_id,
            $this->quarter_id,
            $this->group_number
        );

        Log::info('[Emergency Procurement] completed', [
            'simulation' => $this->simulation_id,
            'group' => $this->group_number,
            'quarter' => $this->quarter_id,
        ]);
    }
}
