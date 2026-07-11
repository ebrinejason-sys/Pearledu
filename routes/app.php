<?php
use App\Http\Controllers\AppHomeController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\RequireSchoolMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', RequireSchoolMembership::class])->group(function () {
    Route::get('/home', [AppHomeController::class, 'index'])->name('app.home');

    Route::middleware('permission:sms.send')->group(function () {
        Route::get('/sms', [SmsController::class, 'index'])->name('app.sms');
        Route::post('/sms', [SmsController::class, 'send'])->name('app.sms.send');
    });

    Route::middleware('permission:learners.manage')->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('app.students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('app.students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('app.students.store');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('app.students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('app.students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('app.students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('app.students.destroy');

        Route::post('/students/{student}/guardians', [StudentController::class, 'storeGuardian'])->name('app.students.guardians.store');
        Route::put('/students/{student}/guardians/{guardianship}/primary', [StudentController::class, 'makePrimary'])->name('app.students.guardians.primary');
        Route::delete('/students/{student}/guardians/{guardianship}', [StudentController::class, 'destroyGuardian'])->name('app.students.guardians.destroy');
    });
});
