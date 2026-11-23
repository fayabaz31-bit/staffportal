<?php

use App\Http\Controllers\StudentAdministrationController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Middleware\EnsureStudentActive;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

$studentRoutes = Route::name('student.');
if (config('student.domain')) {
    $studentRoutes->domain(config('student.domain'));
} else {
    $studentRoutes->prefix('student');
}

$studentRoutes->group(function (): void {
    Route::post('/payments/remita/notify', [StudentPortalController::class, 'paymentNotification'])
        ->withoutMiddleware(PreventRequestForgery::class)
        ->middleware('throttle:30,1')->name('remita.notify');
    Route::get('/', [StudentPortalController::class, 'login'])->name('login');
    Route::post('/login', [StudentPortalController::class, 'authenticate'])->middleware('throttle:10,1')->name('authenticate');
    Route::get('/forgot-password', [StudentPortalController::class, 'forgot'])->name('password.request');
    Route::post('/forgot-password', [StudentPortalController::class, 'emailReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [StudentPortalController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [StudentPortalController::class, 'saveReset'])->middleware('throttle:5,1')->name('password.save');
    Route::middleware(EnsureStudentActive::class)->group(function (): void {
        Route::get('/dashboard', [StudentPortalController::class, 'show'])->name('dashboard');
        Route::post('/logout', [StudentPortalController::class, 'logout'])->name('logout');
        Route::post('/profile', [StudentPortalController::class, 'profile'])->name('profile');
        Route::post('/registration', [StudentPortalController::class, 'register'])->name('register');
        Route::post('/requests', [StudentPortalController::class, 'requestService'])->name('requests.store');
        Route::post('/requests/{id}/reply', [StudentPortalController::class, 'reply'])->name('requests.reply');
        Route::post('/documents', [StudentPortalController::class, 'upload'])->name('documents.store');
        Route::get('/documents/{id}', [StudentPortalController::class, 'download'])->name('documents.download');
        Route::post('/settings', [StudentPortalController::class, 'settings'])->name('settings');
        Route::post('/fees/{id}/pay', [StudentPortalController::class, 'pay'])->middleware('throttle:5,1')->name('fees.pay');
        Route::post('/fees/{id}/verify', [StudentPortalController::class, 'verify'])->middleware('throttle:10,1')->name('fees.verify');
        Route::get('/fees/{id}/receipt', [StudentPortalController::class, 'receipt'])->name('fees.receipt');
        Route::get('/results/statement', [StudentPortalController::class, 'results'])->name('results.statement');
        Route::get('/{section}', [StudentPortalController::class, 'show'])
            ->whereIn('section', ['profile', 'registration', 'timetable', 'results', 'fees', 'documents', 'accommodation', 'notices', 'support', 'settings'])->name('section');
    });
});

Route::prefix('student-administration')->name('students.admin.')->middleware(['auth', 'can:access-student-administration'])->group(function (): void {
    Route::get('/', [StudentAdministrationController::class, 'index'])->name('index');
    Route::post('/students', [StudentAdministrationController::class, 'createStudent'])->name('create');
    Route::post('/courses', [StudentAdministrationController::class, 'course'])->name('courses');
    Route::post('/notices', [StudentAdministrationController::class, 'notice'])->name('notices');
    Route::get('/{student}', [StudentAdministrationController::class, 'show'])->name('show');
    Route::post('/{student}/account', [StudentAdministrationController::class, 'updateStudent'])->name('account');
    Route::post('/{student}/enrollments/{id}', [StudentAdministrationController::class, 'enrollment'])->name('enrollment');
    Route::post('/{student}/invoices', [StudentAdministrationController::class, 'invoice'])->name('invoice');
    Route::post('/{student}/requests/{id}', [StudentAdministrationController::class, 'review'])->name('review');
    Route::post('/{student}/requests/{id}/reply', [StudentAdministrationController::class, 'reply'])->name('reply');
    Route::post('/{student}/documents', [StudentAdministrationController::class, 'issueDocument'])->name('documents');
    Route::get('/{student}/documents/{id}', [StudentAdministrationController::class, 'download'])->name('download');
    Route::post('/{student}/documents/{id}', [StudentAdministrationController::class, 'verifyDocument'])->name('verify-document');
});
