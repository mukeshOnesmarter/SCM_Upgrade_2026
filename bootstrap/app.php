<?php

use App\Http\Middleware\ActiveUserMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConsultingReportMiddleware;
use App\Http\Middleware\EnsureFacultyTeachesCourse;
use App\Http\Middleware\FacultyMiddleware;
use App\Http\Middleware\FinancialReportMiddleware;
use App\Http\Middleware\FreezeSimulationMiddleware;
use App\Http\Middleware\GernalSettingsMiddleware;
use App\Http\Middleware\NoIndexOutsideProduction;
use App\Http\Middleware\QuarterAccessMiddleware;
use App\Http\Middleware\StudentDashboardMiddleware;
use App\Http\Middleware\StudentMiddleware;
use App\Http\Middleware\SyncDecisionDataMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(NoIndexOutsideProduction::class);

        // SCM's own middleware, under the names its routes already use.
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'faculty' => FacultyMiddleware::class,
            'faculty.course' => EnsureFacultyTeachesCourse::class,
            'student' => StudentMiddleware::class,
            'consultingReports' => ConsultingReportMiddleware::class,
            'financialReports' => FinancialReportMiddleware::class,
            'studentDashboard' => StudentDashboardMiddleware::class,
            'share.activeuser' => ActiveUserMiddleware::class,
            'share.gernalSettings' => GernalSettingsMiddleware::class,
            'quarter.access' => QuarterAccessMiddleware::class,
            'syncDecision' => SyncDecisionDataMiddleware::class,
            'freezeSimulation' => FreezeSimulationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A signed-in person never sees a bare error page: 403 and 404 use
        // SCM's layout with a link back to their own home (errors/*.blade.php).
        $exceptions->dontReportWhen(fn (Throwable $e) => $e instanceof HttpExceptionInterface && $e->getStatusCode() === 403);
    })->create();
