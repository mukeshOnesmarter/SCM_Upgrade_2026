<?php

namespace App\Console\Commands;

use App\Http\Controllers\CronJobController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RunSimulationCronJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan simulation:cron-jobs
     */
    protected $signature = 'simulation:cron-jobs';

    /**
     * The console command description.
     */
    protected $description = 'Runs Simulation Synchronization and Decision Reminder cron jobs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = app(CronJobController::class);

        // Simulation Synchronization
        try {
            $controller->simulationSynchronization(new Request);
            Log::info('Cron Command: Simulation Quarter Closing executed at: '.now());
        } catch (\Exception $e) {
            Log::error('Cron Command: Simulation Quarter Closing error — '.$e->getMessage());
        }

        // Decision Reminder
        try {
            $controller->simulationDecisionReminder(new Request);
            Log::info('Cron Command: Simulation Decision Reminder executed at: '.now());
        } catch (\Exception $e) {
            Log::error('Cron Command: Simulation Decision Reminder error — '.$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
