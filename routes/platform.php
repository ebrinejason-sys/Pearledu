<?php

use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\ConfirmPlatformAuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\InvitationController;
use App\Http\Controllers\Platform\OperatorController;
use App\Http\Controllers\Platform\PricingPlanController;
use App\Http\Controllers\Platform\SchoolClassController;
use App\Http\Controllers\Platform\SchoolController;
use App\Http\Controllers\Platform\SmsCreditController;
use App\Http\Controllers\Platform\StaffController;
use App\Http\Controllers\Platform\StudentController;
use App\Http\Controllers\Platform\SupportTicketController;
use App\Http\Controllers\Platform\SystemController;
use App\Http\Controllers\Platform\WalkthroughSchoolController;
use App\Http\Controllers\Platform\WorkspaceController;
use Illuminate\Support\Facades\Route;

/**
 * PearlEdu operator console — admin/staff only.
 * School users never use this path; they log in at /login and land on /home.
 *
 * Authorization: `platform` (is_platform + role) then `platform.permission:…`.
 * Destructive mutations also use `platform.recent_auth`.
 */
Route::middleware(['web', 'auth', 'platform'])->prefix('admin')->name('platform.')->group(function () {
    Route::get('auth/confirm', [ConfirmPlatformAuthController::class, 'show'])->name('auth.confirm');
    Route::post('auth/confirm', [ConfirmPlatformAuthController::class, 'store'])->name('auth.confirm.store');
    Route::get('auth/confirm/resume', [ConfirmPlatformAuthController::class, 'resume'])->name('auth.confirm.resume');

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('platform.permission:platform.dashboard.view')
        ->name('dashboard');

    Route::get('schools', [SchoolController::class, 'index'])
        ->middleware('platform.permission:platform.schools.view')
        ->name('schools.index');
    Route::get('schools/create', [SchoolController::class, 'create'])
        ->middleware('platform.permission:platform.schools.create')
        ->name('schools.create');
    Route::post('schools', [SchoolController::class, 'store'])
        ->middleware('platform.permission:platform.schools.create')
        ->name('schools.store');
    Route::get('schools/walkthrough', [WalkthroughSchoolController::class, 'create'])
        ->middleware('platform.permission:platform.schools.create')
        ->name('schools.walkthrough');
    Route::post('schools/walkthrough', [WalkthroughSchoolController::class, 'store'])
        ->middleware(['platform.permission:platform.schools.create', 'platform.recent_auth'])
        ->name('schools.walkthrough.store');
    Route::get('schools/{school}', [SchoolController::class, 'show'])
        ->middleware('platform.permission:platform.schools.view')
        ->name('schools.show');
    Route::put('schools/{school}', [SchoolController::class, 'update'])
        ->middleware(['platform.permission:platform.schools.update', 'platform.recent_auth'])
        ->name('schools.update');
    Route::delete('schools/{school}', [SchoolController::class, 'destroy'])
        ->middleware(['platform.permission:platform.schools.delete', 'platform.recent_auth'])
        ->name('schools.destroy');
    Route::post('schools/{school}/restore', [SchoolController::class, 'restore'])
        ->middleware(['platform.permission:platform.schools.delete', 'platform.recent_auth'])
        ->name('schools.restore');
    Route::post('schools/{school}/enter', [SchoolController::class, 'enter'])
        ->middleware('platform.permission:platform.schools.enter')
        ->name('schools.enter');
    Route::post('schools/leave', [SchoolController::class, 'leave'])
        ->middleware('platform.permission:platform.schools.enter')
        ->name('schools.leave');
    Route::post('schools/{school}/imitate/{user}', [ImpersonationController::class, 'store'])
        ->middleware(['platform.permission:platform.users.impersonate', 'platform.recent_auth'])
        ->name('schools.imitate');

    Route::get('operators', [OperatorController::class, 'index'])
        ->middleware('platform.permission:platform.staff.view')
        ->name('operators.index');
    Route::get('operators/create', [OperatorController::class, 'create'])
        ->middleware('platform.permission:platform.staff.manage')
        ->name('operators.create');
    Route::post('operators', [OperatorController::class, 'store'])
        ->middleware(['platform.permission:platform.staff.manage', 'platform.recent_auth'])
        ->name('operators.store');
    Route::get('operators/{operator}/edit', [OperatorController::class, 'edit'])
        ->middleware('platform.permission:platform.staff.manage')
        ->name('operators.edit');
    Route::put('operators/{operator}', [OperatorController::class, 'update'])
        ->middleware(['platform.permission:platform.staff.manage', 'platform.recent_auth'])
        ->name('operators.update');
    Route::delete('operators/{operator}', [OperatorController::class, 'destroy'])
        ->middleware(['platform.permission:platform.staff.manage', 'platform.recent_auth'])
        ->name('operators.destroy');
    Route::post('operators/{operator}/reset-password', [OperatorController::class, 'resetPassword'])
        ->middleware(['platform.permission:platform.staff.manage', 'platform.recent_auth'])
        ->name('operators.reset-password');
    Route::post('operators/{operator}/force-logout', [OperatorController::class, 'forceLogout'])
        ->middleware(['platform.permission:platform.staff.manage', 'platform.recent_auth'])
        ->name('operators.force-logout');
    Route::post('operators/{operator}/reset-two-factor', [OperatorController::class, 'resetTwoFactor'])
        ->middleware(['platform.permission:platform.staff.manage', 'platform.recent_auth'])
        ->name('operators.reset-two-factor');

    Route::get('support', [SupportTicketController::class, 'index'])
        ->middleware('platform.permission:platform.support.view')
        ->name('support.index');
    Route::get('support/{ticket}', [SupportTicketController::class, 'show'])
        ->middleware('platform.permission:platform.support.view')
        ->name('support.show');
    Route::put('support/{ticket}', [SupportTicketController::class, 'update'])
        ->middleware('platform.permission:platform.support.manage')
        ->name('support.update');
    Route::post('support/{ticket}/assign', [SupportTicketController::class, 'assign'])
        ->middleware('platform.permission:platform.support.manage')
        ->name('support.assign');

    Route::get('audit', [AuditLogController::class, 'index'])
        ->middleware('platform.permission:platform.audit.view')
        ->name('audit.index');
    Route::get('system', [SystemController::class, 'index'])
        ->middleware('platform.permission:platform.system.view')
        ->name('system.index');

    Route::get('invitations', [InvitationController::class, 'index'])
        ->middleware('platform.permission:platform.invitations.manage')
        ->name('invitations.index');
    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])
        ->middleware('platform.permission:platform.invitations.manage')
        ->name('invitations.resend');

    Route::get('pricing', [PricingPlanController::class, 'index'])
        ->middleware('platform.permission:platform.pricing.view')
        ->name('pricing.index');
    Route::post('pricing', [PricingPlanController::class, 'store'])
        ->middleware(['platform.permission:platform.pricing.manage', 'platform.recent_auth'])
        ->name('pricing.store');
    Route::put('pricing/{plan}', [PricingPlanController::class, 'update'])
        ->middleware(['platform.permission:platform.pricing.manage', 'platform.recent_auth'])
        ->name('pricing.update');
    Route::delete('pricing/{plan}', [PricingPlanController::class, 'destroy'])
        ->middleware(['platform.permission:platform.pricing.manage', 'platform.recent_auth'])
        ->name('pricing.destroy');

    Route::get('sms', [SmsCreditController::class, 'index'])
        ->middleware('platform.permission:platform.sms.view')
        ->name('sms.index');
    Route::post('sms/settings', [SmsCreditController::class, 'updateSettings'])
        ->middleware(['platform.permission:platform.sms.configure', 'platform.recent_auth'])
        ->name('sms.settings');
    Route::post('sms/{school}/top-up', [SmsCreditController::class, 'topUp'])
        ->middleware(['platform.permission:platform.sms.topup', 'platform.recent_auth'])
        ->name('sms.topup');

    // Permission first (platform RLS), then pin entered-school RLS for data routes.
    Route::middleware(['platform.permission:platform.schools.enter', 'platform.school'])->group(function () {
        Route::get('workspace', [WorkspaceController::class, 'show'])->name('workspace');

        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])
            ->middleware('platform.recent_auth')
            ->name('students.destroy');
        Route::post('students/{student}/guardians', [StudentController::class, 'storeGuardian'])->name('students.guardians.store');
        Route::put('students/{student}/guardians/{guardianship}/primary', [StudentController::class, 'makePrimary'])->name('students.guardians.primary');
        Route::delete('students/{student}/guardians/{guardianship}', [StudentController::class, 'destroyGuardian'])
            ->middleware('platform.recent_auth')
            ->name('students.guardians.destroy');
        Route::post('students/{student}/account', [StudentController::class, 'storeAccount'])->name('students.account.store');
        Route::delete('students/{student}/account', [StudentController::class, 'destroyAccount'])
            ->middleware('platform.recent_auth')
            ->name('students.account.destroy');

        Route::get('classes', [SchoolClassController::class, 'index'])->name('classes.index');
        Route::post('classes', [SchoolClassController::class, 'store'])->name('classes.store');
        Route::delete('classes/{schoolClass}', [SchoolClassController::class, 'destroy'])
            ->middleware('platform.recent_auth')
            ->name('classes.destroy');

        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('staff/{user}/roles', [StaffController::class, 'updateRoles'])
            ->middleware('platform.recent_auth')
            ->name('staff.roles');
        Route::delete('staff/{user}', [StaffController::class, 'revoke'])
            ->middleware('platform.recent_auth')
            ->name('staff.revoke');
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
