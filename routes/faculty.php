<?php

use App\Http\Controllers\FacultyLoginController;
use App\Http\Controllers\FacultyMimicController;
use App\Http\Controllers\FacultyProfileController;
use App\Http\Controllers\FacultySimulationController;
use Illuminate\Support\Facades\Route;

Route::prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/login', [FacultyLoginController::class, 'index'])->name('login');
    Route::post('/submit-login', [FacultyLoginController::class, 'login'])->middleware('throttle:10,1')->name('submitLogin');
    Route::get('/logout', [FacultyLoginController::class, 'logout'])->name('logout');

    Route::middleware('faculty', 'share.activeuser')->group(function () {
        Route::get('/', [FacultyLoginController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [FacultyLoginController::class, 'dashboard'])->name('dashboard');

        // Profile
        Route::name('profile.')->group(function () {
            Route::get('/profile', [FacultyProfileController::class, 'edit'])->name('edit');
            Route::put('/profile', [FacultyProfileController::class, 'update'])->name('update');
            Route::put('/profile/password', [FacultyProfileController::class, 'updatePassword'])->name('password');
        });

        Route::prefix('courses')->name('courses.')->middleware('faculty.course')->group(function () {
            Route::get('/', [FacultySimulationController::class, 'index'])->name('index');
            Route::get('/{course_id}', [FacultySimulationController::class, 'show'])->name('show');
            Route::get('/{course_id}/add-student', [FacultySimulationController::class, 'addStudent'])->name('addStudent');
            Route::post('/{course_id}/store-student', [FacultySimulationController::class, 'storeStudent'])->name('storeStudent');
            Route::post('/{course_id}/add-quarter', [FacultySimulationController::class, 'addQuarter'])->name('addQuarter');
            Route::get('/{course_id}/quarter-removed/{quarter_id}', [FacultySimulationController::class, 'removeQuarter'])->name('removeQuarter');
            Route::post('/{course_id}/extend-qtr-duration/{quarter_id}', [FacultySimulationController::class, 'extendQtrDuration'])->name('extendQtrDuration');

            Route::get('/{course_id}/quarter/{quarter_id}', [FacultySimulationController::class, 'quarterInfo'])->name('quarterInfo');
            Route::get('/{course_id}/quarter-close/{quarter_id}', [FacultySimulationController::class, 'quartersClosed'])->name('quartersClosed');
            Route::get('/{course_id}/invitees', [FacultySimulationController::class, 'invitees'])->name('invitees');
            Route::post('/{course_id}/bulk-invitees', [FacultySimulationController::class, 'bulkInvite'])->name('bulkInvitees');
            Route::get('/{course_id}/students', [FacultySimulationController::class, 'students'])->name('students');
            Route::get('/{course_id}/faculties', [FacultySimulationController::class, 'faculties'])->name('faculties');
            Route::get('/{course_id}/students-distribute', [FacultySimulationController::class, 'studentsDistribute'])->name('studentsDistribute');
            Route::get('/{course_id}/institute-payment', [FacultySimulationController::class, 'institutePayment'])->name('institutePayment');
            Route::post('/{course_id}/institute-payment/submit', [FacultySimulationController::class, 'submitInstitutePayment'])->name('institutePayment.submit');

            Route::post('/{course_id}/invite', [FacultySimulationController::class, 'invite'])->name('invite');
            Route::get('/{course_id}/deploy-simulation', [FacultySimulationController::class, 'deploySimulation'])->name('deploySimulation');
            Route::get('/{course_id}/group/{group_id}', [FacultySimulationController::class, 'studentsGroup'])->name('studentsGroup');
            Route::post('/{course_id}/groups/assign-student', [FacultySimulationController::class, 'assignStudentToGroup'])->name('assignStudentToGroup');
            Route::post('/{course_id}/groups/remove-student', [FacultySimulationController::class, 'removeStudentFromGroup'])->name('removeStudentFromGroup');
            Route::get('/{course_id}/student-profile/{student_id}', [FacultySimulationController::class, 'studentProfile'])->name('studentProfile');
            Route::get('/{course_id}/faculty-profile/{faculty_id}', [FacultySimulationController::class, 'facultyProfile'])->name('facultyProfile');

            Route::post('/{course_id}/remove-student', [FacultySimulationController::class, 'toggleRemoveFromCourse'])->name('toggleRemoveFromCourse');
            Route::post('/{course_id}/permanent-delete-student', [FacultySimulationController::class, 'permanentDeleteStudent'])->name('permanentDeleteStudent');
            Route::post('/{course_id}/temporary-block', [FacultySimulationController::class, 'toggleTemporaryBlock'])->name('toggleTemporaryBlock');
            Route::post('/{course_id}/extend-payment-duedate', [FacultySimulationController::class, 'extendPaymentDueDate'])->name('extendPaymentDueDate');
            Route::post('/{course_id}/password-reset', [FacultySimulationController::class, 'studResetPassword'])->name('resetPassword');
            Route::post('/{course_id}/change-group', [FacultySimulationController::class, 'changeStudentGroup'])->name('changeStudentGroup');
            Route::post('/{course_id}/toggle-payment-status', [FacultySimulationController::class, 'togglePaymentStatus'])->name('togglePaymentStatus');
            Route::post('/{course_id}/add-firm', [FacultySimulationController::class, 'addFirm'])->name('addFirm');
            Route::post('/{course_id}/remove-firm', [FacultySimulationController::class, 'removeFirm'])->name('removeFirm');

            // FIRM PERFORMANCE
            Route::get('/{course_id}/performance-metrics/{quarter_id?}', [FacultySimulationController::class, 'simulationPerformance'])->name('simulationPerformance');
            Route::get('/{course_id}/group/{group_id}/performance-metrics/{quarter_id?}', [FacultySimulationController::class, 'groupPerformance'])->name('groupPerformance');

            // FIRM SCORECARD COMPARISON
            Route::get('/{course_id}/firm-scorecard/{quarter_id?}', [FacultySimulationController::class, 'firmScorecardSummary'])->name('firmScorecard');
        });

        Route::get('/students', [FacultyLoginController::class, 'students'])->name('students');
        Route::get('/students/{student_id}', [FacultyLoginController::class, 'studentDetail'])->middleware('faculty.course')->name('students.profile');
        Route::get('/invitees', [FacultyLoginController::class, 'invitees'])->name('invitees');

        // Sets up a team of test students before a course is published.
        Route::get('/import-students/{simulation_id}', [FacultySimulationController::class, 'inviteStudents'])->middleware('faculty.course')->name('importStudent');
    });
});

Route::middleware(['faculty', 'share.activeuser', 'faculty.course'])->group(function () {
    Route::get('mimic/student/{student_id}/simulation/{simulation_id}', [FacultyMimicController::class, 'startMimic'])->name('faculty.mimic.start');
    Route::get('mimic/stop/{simulation_id}/student-profile/{student_id}', [FacultyMimicController::class, 'stopMimic'])->name('faculty.mimic.stop');
});
