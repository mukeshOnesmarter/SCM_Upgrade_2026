<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\StudentRegistrationController;
use App\Support\HomeByRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Signed-in people go to their own home; everyone else sees the front page.
Route::get('/', function () {
    $home = HomeByRole::route();

    return $home ? redirect()->route($home) : view('welcome');
})->name('home');

// COURSES
Route::prefix('courses')->name('courses.')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/leadership-finance', [CourseController::class, 'leadershipFinance'])->name('leadership-finance');
    Route::get('/activity-book', [CourseController::class, 'activityBook'])->name('activity-book');
    Route::get('/cpim', [CourseController::class, 'cpim'])->name('cpim');
    Route::get('/cscp', [CourseController::class, 'cscp'])->name('cscp');
    Route::get('/cltd', [CourseController::class, 'cltd'])->name('cltd');
    Route::get('/ctsc', [CourseController::class, 'ctsc'])->name('ctsc');
    Route::get('/project-management', [CourseController::class, 'projectManagement'])->name('project-management');
    Route::get('/leadership', [CourseController::class, 'leadership'])->name('leadership');
    Route::get('/finance', [CourseController::class, 'finance'])->name('finance');
    Route::get('/scor-ds', [CourseController::class, 'scorDs'])->name('scor-ds');
    Route::get('/supply-chain-network-design', [CourseController::class, 'networkDesign'])->name('network-design');
});

// ADMIN ROUTES
require __DIR__.'/admin.php';

// FACULTY ROUTES
require __DIR__.'/faculty.php';

// STUDENT ROUTES
require __DIR__.'/students.php';

// FORGOT PASSWORD (the {role} is admin, faculty or student)
Route::pattern('role', 'admin|faculty|student');

Route::get('{role}/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');

Route::post('{role}/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::get('{role}/password/link-sent/{email}', function ($role, $email) {
    return view('auth.link-sent', [
        'role' => $role,
        'email' => $email,
    ]);
})->name('password.linksent');

// RESET PASSWORD
Route::get('{role}/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');

Route::post('{role}/password/reset', [ResetPasswordController::class, 'reset'])
    ->name('password.update');

Route::get('{role}/password/reset-success', function ($role) {
    return view('auth.reset-success', compact('role'));
})->name('password.updatesuccess');

// STUDENT REGISTRATION (Invite Based)
Route::get('/student-registration/{facultycode}/{simulationcode}/{studentemail}', [StudentRegistrationController::class, 'studentRegistration'])
    ->name('student-registration');

Route::post('/save-student-registration', [StudentRegistrationController::class, 'saveStudentRegistration'])
    ->name('save-student-registration');

Route::get('/account-verified/{email}/{token}', [StudentRegistrationController::class, 'accountVerified'])
    ->name('account-verified');

// OPEN REGISTRATION (Self Join Link)
Route::get('/join/{token}',
    [StudentRegistrationController::class, 'showDirectRegistration'])
    ->name('self-student-registration');

Route::post('/join/register',
    [StudentRegistrationController::class, 'storeDirectRegistration'])
    ->name('self-student-registration.store');

// EMBED VIDEOS
Route::prefix('videos')->name('videos.')->group(function () {
    Route::get('/', function () {
        return view('videos.intorduction');
    })->name('intorduction');

    Route::get('/forecasting', function () {
        return view('videos.forecasting');
    })->name('forecasting');

    Route::get('/procurement', function () {
        return view('videos.procurement');
    })->name('procurement');

    Route::get('/manufacturing', function () {
        return view('videos.manufacturing');
    })->name('manufacturing');

    Route::get('/distribution', function () {
        return view('videos.distribution');
    })->name('distribution');

    Route::get('/transportation', function () {
        return view('videos.transportation');
    })->name('transportation');

    Route::get('/inventory', function () {
        return view('videos.inventory');
    })->name('inventory');

    Route::get('/form-teams', function () {
        return view('videos.form-teams');
    })->name('form-teams');

    Route::get('/make-decisions', function () {
        return view('videos.make-decisions');
    })->name('make-decisions');

    Route::get('/analyze-results', function () {
        return view('videos.analyze-results');
    })->name('analyze-results');

    Route::get('/refine-strategy', function () {
        return view('videos.refine-strategy');
    })->name('refine-strategy');
});
