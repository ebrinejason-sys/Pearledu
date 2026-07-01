<?php
use App\Http\Controllers\AppHomeController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\Illuminate\Http\Request $r) {
    if ($r->attributes->get('is_landing')) {
        return app(LandingController::class)->index();
    }
    return redirect('/login');     // platform/tenant hosts go to auth
});

Route::post('/contact', [LandingController::class, 'contact'])
    ->middleware('throttle:5,1')->name('contact');
