<?php

use App\Http\Controllers\DownloadConsultingReportController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\DownloadScorecardController;
use App\Http\Controllers\SimulationManualController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StudentBuyReportController;
use App\Http\Controllers\StudentDecisionController;
use App\Http\Controllers\StudentDecisionExtraReportController;
use App\Http\Controllers\StudentDecisionManageController;
use App\Http\Controllers\StudentDecisionReportController;
use App\Http\Controllers\StudentLoginController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentSimulationController;
use Illuminate\Support\Facades\Route;

// STUDENT ROUTES
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [StudentLoginController::class, 'index'])->name('login');
    Route::post('/submit-login', [StudentLoginController::class, 'login'])->middleware('throttle:10,1')->name('submitLogin');
    Route::get('/logout', [StudentLoginController::class, 'logout'])->name('logout');

    Route::middleware('student', 'share.activeuser', 'freezeSimulation')->group(function () {
        Route::get('/', [StudentLoginController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [StudentLoginController::class, 'dashboard'])->name('dashboard');
        Route::get('/invitations', [StudentSimulationController::class, 'invitations'])->name('invitations');
        Route::get('/join-simulation', [StudentSimulationController::class, 'joinSimulation'])->name('join-Simulation');
        Route::match(['get', 'post'], '/review-simulation', [StudentSimulationController::class, 'reviewSimulation'])->name('review-Simulation');

        // PREFIX PROFILE
        Route::name('profile.')->group(function () {
            Route::get('/profile', [StudentProfileController::class, 'edit'])->name('edit');
            Route::put('/profile', [StudentProfileController::class, 'update'])->name('update');
            Route::put('/profile/password', [StudentProfileController::class, 'updatePassword'])->name('password');
        });

        // PREFIX MANUAL
        Route::prefix('manual')->name('manual.')->group(function () {
            Route::get('/', [SimulationManualController::class, 'index'])->name('index');
            Route::get('/chapter-01', [SimulationManualController::class, 'chapter01'])->name('chapter01');
            Route::get('/chapter-02', [SimulationManualController::class, 'chapter02'])->name('chapter02');
            Route::get('/chapter-03', [SimulationManualController::class, 'chapter03'])->name('chapter03');
            Route::get('/chapter-04', [SimulationManualController::class, 'chapter04'])->name('chapter04');
            Route::get('/chapter-05', [SimulationManualController::class, 'chapter05'])->name('chapter05');
            Route::get('/chapter-06', [SimulationManualController::class, 'chapter06'])->name('chapter06');
            Route::get('/chapter-07', [SimulationManualController::class, 'chapter07'])->name('chapter07');
            Route::get('/chapter-08', [SimulationManualController::class, 'chapter08'])->name('chapter08');
            Route::get('/chapter-09', [SimulationManualController::class, 'chapter09'])->name('chapter09');
            Route::get('/chapter-10', [SimulationManualController::class, 'chapter10'])->name('chapter10');
            Route::get('/chapter-11', [SimulationManualController::class, 'chapter11'])->name('chapter11');
            Route::get('/chapter-12', [SimulationManualController::class, 'chapter12'])->name('chapter12');
        });

        // PREFIX SIMULATION
        Route::prefix('simulation')->name('simulation.')->group(function () {
            Route::get('/', [StudentSimulationController::class, 'index'])->name('index');
            Route::get('/{simulation_id}/locked', [StudentSimulationController::class, 'locked'])->name('locked')->withoutMiddleware('freezeSimulation');
            Route::get('/{simulation_id}/dashboard/{quarter_id}', [StudentDecisionController::class, 'simulationDashboard'])->name('simulation-dashboard')->middleware('studentDashboard');
            Route::get('/{simulation_id}/dashboard/{quarter_id}/performance-trends', [StudentDecisionController::class, 'performanceTrends'])->name('performance-trends')->middleware('studentDashboard');
            Route::get('/{simulation_id}/dashboard/{quarter_id}/kpis', [StudentDecisionController::class, 'dashboardKPIs'])->name('kpis')->middleware('studentDashboard');
            Route::get('/{simulation_id}/products', [StudentDecisionController::class, 'simulationProducts'])->name('products');
            Route::get('/{simulation_id}/firms', [StudentDecisionController::class, 'simulationFirms'])->name('firms');
            Route::get('/{simulation_id}/{quarter_id}/team-member', [StudentDecisionController::class, 'simulationTeamMember'])->name('team-member');
            Route::prefix('/{simulation_id}/quarter/{quarter_id}')->name('decisions.')->group(function () {
                Route::get('/', [StudentDecisionController::class, 'forecasting']);
                Route::get('/forecasting', [StudentDecisionController::class, 'forecasting'])->name('forecasting');
                Route::get('/procurement', [StudentDecisionController::class, 'procurement'])->name('procurement');
                Route::get('/manufacturing', [StudentDecisionController::class, 'manufacturing'])->name('manufacturing');
                Route::get('/distribution', [StudentDecisionController::class, 'distribution'])->name('distribution');
                Route::get('/transportation', [StudentDecisionController::class, 'transportation'])->name('transportation');
                Route::get('/service-management', [StudentDecisionController::class, 'serviceManagement'])->name('service-management');
                Route::get('/demand-planning', [StudentDecisionController::class, 'demandPlanning'])->name('demand-planning');
                Route::get('/information-technology', [StudentDecisionController::class, 'informationTechnology'])->name('information-technology');
                Route::get('/analysis-summary', [StudentDecisionController::class, 'analysisSummary'])->name('analysis-summary');
                // MANAGE DECISION POST REQUEST
                Route::post('/save-forecasting', [StudentDecisionManageController::class, 'saveForecasting'])->name('save-forecasting');
                Route::post('/save-forecasting-default', [StudentDecisionManageController::class, 'saveDefaultForecasting'])->name('save-forecasting-default');
                Route::post('/save-procurement', [StudentDecisionManageController::class, 'saveProcurement'])->name('save-procurement');
                Route::post('/save-manufacturing', [StudentDecisionManageController::class, 'saveManufacturing'])->name('save-manufacturing');
                Route::post('/save-distribution', [StudentDecisionManageController::class, 'saveDistribution'])->name('save-distribution');
                Route::post('/save-transportation', [StudentDecisionManageController::class, 'saveTransportation'])->name('save-transportation');
                Route::post('/save-service-management', [StudentDecisionManageController::class, 'saveServiceManagement'])->name('save-service-management');
                Route::post('/save-demand-planning', [StudentDecisionManageController::class, 'saveDemandPlanning'])->name('save-demand-planning');
                Route::post('/save-information-technology', [StudentDecisionManageController::class, 'saveInformationTechnology'])->name('save-information-technology');
                Route::post('/close-distribution', [StudentDecisionManageController::class, 'closeDistribution'])->name('close-distribution');

                Route::get('/store-scorecard', [StudentDecisionController::class, 'storeScorecard'])->name('close-scorecard');
            });

            // BUY REPORTS
            Route::middleware(['consultingReports'])->group(function () {
                Route::prefix('/{simulation_id}/quarter/{quarter_id}/buy-reports/')->name('buyreports.')->group(function () {
                    Route::get('/', [StudentBuyReportController::class, 'benchmarkEarnings'])->name('index');
                    Route::get('earnings', [StudentBuyReportController::class, 'benchmarkEarnings'])->name('earnings');
                    Route::get('balance-sheet', [StudentBuyReportController::class, 'benchmarkBalanceSheet'])->name('balancesheet');
                    Route::get('procurement', [StudentBuyReportController::class, 'benchmarkProcurement'])->name('procurement');
                    Route::get('manufacturing', [StudentBuyReportController::class, 'benchmarkManufacturing'])->name('manufacturing');
                    Route::get('distribution', [StudentBuyReportController::class, 'benchmarkDistribution'])->name('distribution');
                    Route::get('transportation', [StudentBuyReportController::class, 'benchmarkTransportation'])->name('transportation');
                    Route::get('generate-demand', [StudentBuyReportController::class, 'benchmarkGenerateDemand'])->name('generatedemand');
                    Route::get('information-technology', [StudentBuyReportController::class, 'benchmarkIT'])->name('it');
                    Route::get('operating-statistics', [StudentBuyReportController::class, 'benchmarkOperatingStatistics'])->name('operating');
                    Route::get('market-statistics', [StudentBuyReportController::class, 'marketStatistics'])->name('market');
                    Route::get('regional-summary', [StudentBuyReportController::class, 'regionalSummary'])->name('region');
                    Route::get('customer-satisfaction', [StudentBuyReportController::class, 'customerSatisfaction'])->name('customersatisfaction');
                    Route::get('price-sensitivity', [StudentBuyReportController::class, 'priceSensitivity'])->name('pricesensitivity');

                    Route::get('upcoming-quarter', [StudentBuyReportController::class, 'upcomingQuarterSelection'])->name('upcoming-quarter')->withoutMiddleware('consultingReports');
                    Route::post('buy-consulting-report', [StudentBuyReportController::class, 'buyConsultingReport'])->name('buyConsultingReport');
                    Route::post('remove-consulting-report', [StudentBuyReportController::class, 'removeConsultingReport'])->name('removeConsultingReport');
                });
            });

            // FINANCIAL REPORTS /middleware(['financialReports'])/
            Route::middleware(['financialReports'])->group(function () {
                Route::prefix('/{simulation_id}/quarter/{quarter_id}/financial-reports')->name('reports.')->group(function () {
                    Route::get('/', [StudentDecisionReportController::class, 'index']);
                    Route::get('/corporate-pnl', [StudentDecisionReportController::class, 'corporatePnl'])->name('corporate-pnl');
                    Route::get('/product-pnl', [StudentDecisionReportController::class, 'productPnl'])->name('product-pnl');
                    Route::get('/balance-sheet', [StudentDecisionReportController::class, 'balanceSheet'])->name('balance-sheet');
                    Route::get('/cash-flow', [StudentDecisionReportController::class, 'cashFlow'])->name('cash-flow');
                    Route::get('/balance-sheet-stock', [StudentDecisionReportController::class, 'balanceSheetStock'])->name('balance-sheet-stock');
                    Route::get('/inventory-stock', [StudentDecisionExtraReportController::class, 'inventoryStock'])->name('inventory-stock');
                    Route::prefix('extra')->name('extra.')->group(function () {
                        Route::get('/', [StudentDecisionExtraReportController::class, 'index']);
                        Route::get('/finished-goods', [StudentDecisionExtraReportController::class, 'finishedGoods'])->name('finished-goods');
                        Route::get('/procurement', [StudentDecisionExtraReportController::class, 'procurement'])->name('procurement');
                        Route::get('/forecast-accuracy', [StudentDecisionExtraReportController::class, 'forecastAccuracy'])->name('forecast-accuracy');
                        Route::get('/service-ops', [StudentDecisionExtraReportController::class, 'serviceOps'])->name('service-ops');
                        Route::get('/decision-vars', [StudentDecisionExtraReportController::class, 'decisionVars'])->name('decision-vars');
                        Route::get('/industry-bulletin', [StudentDecisionExtraReportController::class, 'industryBulletin'])->name('industry-bulletin');
                        // CONSULTING REPORTS
                        Route::get('/consulting-reports', [StudentDecisionExtraReportController::class, 'consultingreports'])->name('consulting-reports');
                    });
                });

                // DOWNLOAD REPORTS
                Route::prefix('/{simulation_id}/quarter/{quarter_id}/download-report')->name('download.')->group(function () {
                    Route::get('/corporate-pnl', [DownloadController::class, 'corporatePnl'])->name('corporate-pnl');
                    Route::get('/product-pnl', [DownloadController::class, 'productPnl'])->name('product-pnl');
                    Route::get('/balance-sheet', [DownloadController::class, 'balanceSheet'])->name('balance-sheet');
                    Route::get('/cash-flow', [DownloadController::class, 'cashFlow'])->name('cash-flow');
                    Route::get('/inventory-stock', [DownloadController::class, 'inventoryStock'])->name('inventory-stock');
                    // ADDITIONAL REPORTS (SUPPLEMENTARY NOTES)
                    Route::get('/finished-goods', [DownloadController::class, 'finishedGoods'])->name('finished-goods');
                    Route::get('/procurement', [DownloadController::class, 'procurement'])->name('procurement');
                    Route::get('/forecast-accuracy', [DownloadController::class, 'forecastAccuracy'])->name('forecast-accuracy');
                    Route::get('/service-ops', [DownloadController::class, 'serviceOps'])->name('service-ops');
                    Route::get('/decision-vars', [DownloadController::class, 'decisionVars'])->name('decision-vars');
                    Route::get('/industry-bulletin', [DownloadController::class, 'industryBulletin'])->name('industry-bulletin');
                    Route::get('/scorecard-raw-download', [DownloadScorecardController::class, 'downloadScorecardRaw'])->name('scorecard-raw-download');
                    // CONSULTING REPORTS
                    Route::get('/consulting-reports/{slug}', [DownloadConsultingReportController::class, 'downloadConsultingReport'])->name('consulting-reports');
                });
            });

            // ACCESS DENIED
            Route::get('/{simulation_id}/blocked', [StudentSimulationController::class, 'studentBlocked'])->name('blocked')->withoutMiddleware('student');
            Route::get('/{simulation_id}/removed', [StudentSimulationController::class, 'studenRemoved'])->name('removed')->withoutMiddleware('student');
            Route::get('/{simulation_id}/unpaid', [StudentSimulationController::class, 'studenUnpaid'])->name('unpaid')->withoutMiddleware('student');
            Route::get('/{simulation_id}/payment', [StudentSimulationController::class, 'payment'])->name('payment');

            // STRIPE PAYMENT
            Route::get('/{simulation_id}/checkout', [StripePaymentController::class, 'checkout'])->name('stripe.checkout');
            Route::get('/{simulation_id}/success', [StripePaymentController::class, 'success'])->name('stripe.success');
            Route::get('/{simulation_id}/cancel', [StripePaymentController::class, 'cancel'])->name('stripe.cancel');
        });
    });
});
