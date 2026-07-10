<?php
use App\Http\Controllers\AppHomeController;
use App\Http\Controllers\SmsController;
use App\Http\Middleware\RequireSchoolMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', RequireSchoolMembership::class])->group(function () {
    Route::get('/home', [AppHomeController::class, 'index'])->name('app.home');

    Route::middleware('permission:sms.send')->group(function () {
        Route::get('/sms', [SmsController::class, 'index'])->name('app.sms');
        Route::post('/sms', [SmsController::class, 'send'])->name('app.sms.send');
    });
});
