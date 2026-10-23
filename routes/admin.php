<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\SimulationAdditionalController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\SimulationOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'index'])->name('login');
    Route::post('/submit-login', [AdminLoginController::class, 'login'])->middleware('throttle:10,1')->name('submitLogin');
    Route::get('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::middleware(['admin', 'share.activeuser'])->group(function () {
        Route::get('/', [AdminLoginController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [AdminLoginController::class, 'dashboard'])->name('dashboard');
        Route::resource('/simulation', SimulationController::class);
        Route::post('/simulation/{simulation_id}/reschedule-quarter', [SimulationController::class, 'quarterReSchedule'])->name('simulation.rescheduleQuarter');
        Route::get('/simulation/{simulation_id}/remove-quarter/{quarter_id}', [SimulationController::class, 'removeQuarter'])->name('simulation.removeQuarter');
        Route::post('/simulation/{simulation_id}/increase-licence', [SimulationController::class, 'increaseLicence'])->name('simulation.increaseLicence');
        Route::post('/simulation/{simulation_id}/ai-analysis-status', [SimulationController::class, 'aiAnalysis'])->name('simulation.aiAnalysis');

        // Assign Co-Faculty
        Route::get('/simulation/{simulation_id}/co-faculty', [SimulationAdditionalController::class, 'selectCoFaculty'])->name('simulation.selectCoFaculty');
        Route::post('/simulation/{simulation_id}/save-co-faculty', [SimulationAdditionalController::class, 'saveCoFaculty'])->name('simulation.saveCoFaculty');

        // Profile
        Route::name('profile.')->group(function () {
            Route::get('/', [AdminProfileController::class, 'edit'])->name('edit');
            Route::get('/profile', [AdminProfileController::class, 'edit'])->name('edit');
            Route::put('/profile', [AdminProfileController::class, 'update'])->name('update');
            Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('password');
        });

        Route::prefix('courses')->group(function () {
            Route::get('/', [SimulationController::class, 'index'])->name('courses.index');
            Route::get('/{courses_id}/manage-quarter', [SimulationAdditionalController::class, 'manageQuarter'])->name('courses.manageQuarter');
            Route::get('/{courses_id}/remove-quarter/{quarter_id}', [SimulationAdditionalController::class, 'removeQuarter'])->name('courses.removeQuarter');
            Route::get('/{courses_id}/industry-initial-demand', [SimulationAdditionalController::class, 'industryInitialDemand'])->name('courses.industryInitialDemand');
            Route::get('/{courses_id}/select-faculty', [SimulationAdditionalController::class, 'selectFaculty'])->name('courses.selectFaculty');
            Route::get('/{courses_id}/quarter-decisions', [SimulationAdditionalController::class, 'quarterDecisions'])->name('courses.quarterDecisions');

            Route::post('/{courses_id}/deploy-simulation', [SimulationAdditionalController::class, 'deploySimulation'])->name('courses.deploySimulation');
            Route::post('/{courses_id}/save-faculty', [SimulationAdditionalController::class, 'saveFaculty'])->name('courses.saveFaculty');
            Route::post('/{courses_id}/generate-initial-demand', [SimulationAdditionalController::class, 'generateInitialDemand'])->name('courses.generateInitialDemand');
            Route::post('/{courses_id}/save-quarter-schedule', [SimulationAdditionalController::class, 'saveQuarterSchedule'])->name('courses.saveQuarterSchedule');
            Route::post('/{courses_id}/save-quarter-decisions', [SimulationAdditionalController::class, 'saveQuarterDecisions'])->name('courses.saveQuarterDecisions');
        });

        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::resource('/faculties', FacultyController::class);
        Route::get('/regions', [AdminController::class, 'regions'])->name('regions');
        Route::get('/firms', [AdminController::class, 'firms'])->name('firms');
        Route::get('/suppliers', [AdminController::class, 'suppliers'])->name('suppliers');
        Route::get('/products', [AdminController::class, 'products'])->name('products');

        // Simulation Order
        Route::resource('simulation-order', SimulationOrderController::class);
        Route::get('simulation-order/{order}/assign-decisions', [SimulationOrderController::class, 'showAssignDecisions'])
            ->name('simulation-order.assign-decisions');

        // Route::post('simulation-order/{order}/assign-decisions', [SimulationOrderController::class, 'saveAssignDecisions'])
        //     ->name('simulation-order.assign-decisions.save');

        Route::post('simulation-order/{order}/quarter/{quarter}/save', [SimulationOrderController::class, 'saveQuarter'])
            ->name('simulation-order.quarter.save');
    });
});
