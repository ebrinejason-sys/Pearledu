<?php
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\InvitationController;
use App\Http\Controllers\Platform\OperatorController;
use App\Http\Controllers\Platform\PricingPlanController;
use App\Http\Controllers\Platform\SchoolClassController;
use App\Http\Controllers\Platform\SchoolController;
use App\Http\Controllers\Platform\SmsCreditController;
use App\Http\Controllers\Platform\StaffController;
use App\Http\Controllers\Platform\StudentController;
use App\Http\Controllers\Platform\SupportTicketController;
use App\Http\Controllers\Platform\WorkspaceController;
use Illuminate\Support\Facades\Route;

/**
 * PearlEdu operator console — admin/staff only.
 * School users never use this path; they log in at /login and land on /home.
 */
Route::middleware(['web', 'auth', 'platform'])->prefix('admin')->name('platform.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::get('schools/create', [SchoolController::class, 'create'])->name('schools.create');
    Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::get('schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
    Route::put('schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
    Route::delete('schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');
    Route::post('schools/{school}/enter', [SchoolController::class, 'enter'])->name('schools.enter');
    Route::post('schools/leave', [SchoolController::class, 'leave'])->name('schools.leave');
    Route::post('schools/{school}/imitate/{user}', [\App\Http\Controllers\Platform\ImpersonationController::class, 'store'])->name('schools.imitate');

    Route::get('operators', [OperatorController::class, 'index'])->name('operators.index');
    Route::get('operators/create', [OperatorController::class, 'create'])->name('operators.create');
    Route::post('operators', [OperatorController::class, 'store'])->name('operators.store');
    Route::get('operators/{operator}/edit', [OperatorController::class, 'edit'])->name('operators.edit');
    Route::put('operators/{operator}', [OperatorController::class, 'update'])->name('operators.update');
    Route::delete('operators/{operator}', [OperatorController::class, 'destroy'])->name('operators.destroy');
    Route::post('operators/{operator}/reset-password', [OperatorController::class, 'resetPassword'])->name('operators.reset-password');

    Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::get('support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::put('support/{ticket}', [SupportTicketController::class, 'update'])->name('support.update');
    Route::post('support/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('support.assign');

    Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');

    Route::get('pricing', [PricingPlanController::class, 'index'])->name('pricing.index');
    Route::post('pricing', [PricingPlanController::class, 'store'])->name('pricing.store');
    Route::put('pricing/{plan}', [PricingPlanController::class, 'update'])->name('pricing.update');
    Route::delete('pricing/{plan}', [PricingPlanController::class, 'destroy'])->name('pricing.destroy');

    Route::get('sms', [SmsCreditController::class, 'index'])->name('sms.index');
    Route::post('sms/settings', [SmsCreditController::class, 'updateSettings'])->name('sms.settings');
    Route::post('sms/{school}/top-up', [SmsCreditController::class, 'topUp'])->name('sms.topup');

    // School data entry — requires Enter school scope (RLS pinned to that school).
    Route::middleware('platform.school')->group(function () {
        Route::get('workspace', [WorkspaceController::class, 'show'])->name('workspace');

        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('students/{student}/guardians', [StudentController::class, 'storeGuardian'])->name('students.guardians.store');
        Route::put('students/{student}/guardians/{guardianship}/primary', [StudentController::class, 'makePrimary'])->name('students.guardians.primary');
        Route::delete('students/{student}/guardians/{guardianship}', [StudentController::class, 'destroyGuardian'])->name('students.guardians.destroy');

        Route::get('classes', [SchoolClassController::class, 'index'])->name('classes.index');
        Route::post('classes', [SchoolClassController::class, 'store'])->name('classes.store');
        Route::delete('classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->name('classes.destroy');

        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    });
});

// Legacy operator paths → /admin
foreach (['platform', 'console'] as $legacy) {
    Route::middleware('web')->any($legacy.'/{path?}', function (?string $path = null) {
        $target = '/admin'.($path ? '/'.$path : '');
        $qs = request()->getQueryString();

        return redirect($target.($qs ? '?'.$qs : ''), 308);
    })->where('path', '.*');
}
