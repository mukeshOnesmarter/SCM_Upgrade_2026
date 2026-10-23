<?php

use Illuminate\Support\Facades\Schedule;

/*
| Cron runs `php artisan schedule:run` every minute.
*/

// Deadline closes and decision reminders (the old app's cron, unchanged in stage 1).
Schedule::command('simulation:cron-jobs')
    ->name('simulation-cron-jobs')
    ->hourlyAt([0, 30])
    ->withoutOverlapping(30);

/*
| Shared hosting can't keep a queue worker running, so the schedule starts one
| every minute. Only one runs at a time: the lock lasts up to 30 minutes,
| longer than the longest job. The worker stops when the queues are empty, or
| after 55 seconds between jobs, and the next minute's run picks up from there.
|
| The old app started a new worker every minute with no lock, so workers piled
| up and raced during a quarter close. It also never worked the default queue.
*/

Schedule::command('queue:work --queue=simulation-setup,quarter-closure,financial-reports,scorecards,ai-report-analysis,default --stop-when-empty --max-time=55 --tries=3 --timeout=1200')
    ->name('queue-worker')
    ->everyMinute()
    ->withoutOverlapping(30)
    ->runInBackground();
