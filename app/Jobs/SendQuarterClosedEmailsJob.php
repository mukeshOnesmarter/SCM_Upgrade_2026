<?php

namespace App\Jobs;

use App\Models\SimulationTeamMember;
use App\Services\MailjetService;
use App\Services\QuarterSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendQuarterClosedEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

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

    public function handle(QuarterSummaryService $summaryService, MailjetService $mailjetService): void
    {
        $students = SimulationTeamMember::with('student')
            ->where('simulation_id', $this->simulation_id)
            ->where('faculty_id', $this->faculty_id)
            ->where('simulation_team_id', $this->group_number)
            ->get();

        $totalUnitsSold = $summaryService->getTotalUnitsSold($this->simulation_id, $this->faculty_id, $this->quarter_id);
        $marketShares = $summaryService->getMarketShares($this->simulation_id, $this->faculty_id, $this->quarter_id);
        $topRevenues = collect(); // $summaryService->getRevenueLeaders(...) // optional
        $openedDcs = $summaryService->getOpenedDCs($this->simulation_id, $this->faculty_id, $this->quarter_id);
        $closedDcs = $summaryService->getClosedDCs($this->simulation_id, $this->faculty_id, $this->quarter_id);
        $priceAlerts = $summaryService->getSupplierPriceAlerts($this->simulation_id, $this->faculty_id, $this->quarter_id);

        $quarterName = 'Q'.$this->quarter_id;

        foreach ($students as $student) {
            $email = $student->student->email;
            $subject = "Quarter {$this->quarter_id} Closed – Performance Insights Inside";
            $html = view('emails.quarter-summary', compact(
                'quarterName',
                'totalUnitsSold',
                'marketShares',
                'topRevenues',
                'openedDcs',
                'closedDcs',
                'priceAlerts'
            ))->render();

            try {
                $mailjetService->send($email, $email, $subject, $html);
                Log::info("[Quarter Closing Email] Sent to: {$email}");
            } catch (\Throwable $e) {
                Log::error("Failed to send email to {$email}", ['error' => $e->getMessage()]);
            }
        }
    }
}
