<?php
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware(['guest','throttle:10,1']);
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::get('/invitations/{invitation}/accept', [InvitationController::class, 'show'])->name('invitations.accept');
    Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::get('/account', [AccountController::class, 'show'])->name('account.settings');
        Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
    });
});
