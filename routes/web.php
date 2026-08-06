<?php
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PearlEduLandingController;
use App\Http\Controllers\PublicAdmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\Illuminate\Http\Request $r) {
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
