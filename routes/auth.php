<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\HeartbeatController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:10,1']);
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware('guest')->group(function () {
        Route::get('/login/2fa/setup', [TwoFactorSetupController::class, 'show'])->middleware('2fa.pending');
        Route::post('/login/2fa/setup', [TwoFactorSetupController::class, 'store'])->middleware(['2fa.pending', 'throttle:10,1']);
        Route::post('/login/2fa/setup/skip', [TwoFactorChallengeController::class, 'continueWithoutAuthenticator'])->middleware(['2fa.pending', 'throttle:10,1']);
        Route::get('/login/2fa/challenge', [TwoFactorChallengeController::class, 'show'])->middleware('2fa.pending');
        Route::post('/login/2fa/challenge', [TwoFactorChallengeController::class, 'store'])->middleware(['2fa.pending', 'throttle:10,1']);
        Route::post('/login/2fa/email', [TwoFactorChallengeController::class, 'sendEmailCode'])->middleware(['2fa.pending', 'throttle:5,1']);
    });
    // Not wrapped in 'guest': by the time this page loads, TwoFactorSetupController::store()
    // has already called Auth::login() on the user, so the 'guest' middleware would bounce them
    // away (via RedirectIfAuthenticated) before they ever see their one-time recovery codes.
    Route::get('/login/2fa/recovery-codes', [TwoFactorSetupController::class, 'showRecoveryCodes']);

    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
    });

    Route::get('/invitations/{invitation}/accept', [InvitationController::class, 'show'])->name('invitations.accept');
    Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::post('/session/heartbeat', [HeartbeatController::class, 'store'])->name('session.heartbeat');
        Route::get('/account', [AccountController::class, 'show'])->name('account.settings');
        Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
        Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
        Route::post('/impersonation/stop', [ImpersonationController::class, 'destroy'])->name('impersonation.stop');
    });
});
