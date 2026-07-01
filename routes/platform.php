<?php
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\SchoolController;
use App\Http\Controllers\Platform\SmsCreditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web','auth','platform'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::get('schools/create', [SchoolController::class, 'create'])->name('schools.create');
    Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::get('schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
    Route::post('schools/{school}/enter', [SchoolController::class, 'enter'])->name('schools.enter');
    Route::post('schools/leave', [SchoolController::class, 'leave'])->name('schools.leave');
    Route::post('schools/{school}/imitate/{user}', [\App\Http\Controllers\Platform\ImpersonationController::class, 'store'])->name('schools.imitate');

    Route::get('sms', [SmsCreditController::class, 'index'])->name('sms.index');
    Route::post('sms/settings', [SmsCreditController::class, 'updateSettings'])->name('sms.settings');
    Route::post('sms/{school}/top-up', [SmsCreditController::class, 'topUp'])->name('sms.topup');
});
