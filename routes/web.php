<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\PearlEduLandingController;
use App\Http\Controllers\PublicAdmissionController;
use App\Http\Controllers\SchoolPayWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $r) {
    if ($r->attributes->get('is_landing')) {
        return app(LandingController::class)->index();
    }
    if ($r->attributes->get('is_pearledu_landing')) {
        return app(PearlEduLandingController::class)->index();
    }

    return redirect('/login');     // platform/tenant hosts go to auth
});

Route::post('/contact', [LandingController::class, 'contact'])
    ->middleware('throttle:3,1')->name('contact');

Route::post('/onboard', [PearlEduLandingController::class, 'onboard'])
    ->middleware('throttle:3,1')->name('pearledu.onboard');

Route::get('/apply', [PublicAdmissionController::class, 'create'])->name('public.admissions.create');
Route::post('/apply', [PublicAdmissionController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('public.admissions.store');

// SchoolPay callbacks — public, CSRF-exempt (see bootstrap/app.php). Throttled.
Route::post('/webhooks/schoolpay/{school}/callback', [SchoolPayWebhookController::class, 'adhocCallback'])
    ->middleware('throttle:120,1')
    ->name('webhooks.schoolpay.callback');
Route::post('/webhooks/schoolpay/{school}/notify', [SchoolPayWebhookController::class, 'notify'])
    ->middleware('throttle:120,1')
    ->name('webhooks.schoolpay.notify');
