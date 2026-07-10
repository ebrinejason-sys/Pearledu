<?php
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware(['guest','throttle:10,1']);
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware('guest')->group(function () {
        Route::get('/login/2fa/setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'show'])->middleware('2fa.pending');
        Route::post('/login/2fa/setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'store'])->middleware(['2fa.pending','throttle:10,1']);
    });
    // Not wrapped in 'guest': by the time this page loads, TwoFactorSetupController::store()
    // has already called Auth::login() on the user, so the 'guest' middleware would bounce them
    // away (via RedirectIfAuthenticated) before they ever see their one-time recovery codes.
    Route::get('/login/2fa/recovery-codes', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'showRecoveryCodes']);

    Route::get('/invitations/{invitation}/accept', [InvitationController::class, 'show'])->name('invitations.accept');
    Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::get('/account', [AccountController::class, 'show'])->name('account.settings');
        Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
        Route::post('/impersonation/stop', [\App\Http\Controllers\ImpersonationController::class, 'destroy'])->name('impersonation.stop');
    });
});
