<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppHomeController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CbtController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\EmisController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\HelpdeskController;
use App\Http\Controllers\HostelController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LmsController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TransportController;
use App\Http\Middleware\RequireSchoolMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', RequireSchoolMembership::class])->group(function () {
    Route::get('/home', [AppHomeController::class, 'index'])->name('app.home');

    Route::middleware('permission:child.results.view,self.results.view,child.fees.view,fees.pay,self.timetable.view,announcements.view')->group(function () {
        Route::get('/portal', [PortalController::class, 'home'])->name('app.portal.home');
    });
    Route::middleware('permission:child.results.view,self.results.view')->group(function () {
        Route::get('/portal/results', [PortalController::class, 'results'])->name('app.portal.results');
    });
    Route::middleware('permission:child.fees.view,fees.pay')->group(function () {
        Route::get('/portal/fees', [PortalController::class, 'fees'])->name('app.portal.fees');
    });
    Route::middleware('permission:fees.pay')->group(function () {
        Route::post('/portal/fees/{invoice}/pay', [PortalController::class, 'pay'])->name('app.portal.fees.pay');
        Route::post('/portal/fees/{invoice}/schoolpay', [PortalController::class, 'payWithSchoolPay'])->name('app.portal.fees.schoolpay');
    });
    Route::middleware('permission:self.timetable.view')->group(function () {
        Route::get('/portal/timetable', [PortalController::class, 'timetable'])->name('app.portal.timetable');
    });
    Route::middleware('permission:announcements.view')->group(function () {
        Route::get('/portal/announcements', [PortalController::class, 'announcements'])->name('app.portal.announcements');
    });

    Route::middleware('permission:staff.manage')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('app.staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('app.staff.store');
        Route::put('/staff/{user}/roles', [StaffController::class, 'updateRoles'])->name('app.staff.roles');
        Route::delete('/staff/{user}', [StaffController::class, 'revoke'])->name('app.staff.revoke');
    });

    Route::middleware('permission:sms.send')->group(function () {
        Route::get('/sms', [SmsController::class, 'index'])->name('app.sms');
        Route::post('/sms', [SmsController::class, 'send'])->name('app.sms.send');
    });

    Route::middleware('permission:learners.manage')->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('app.students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('app.students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('app.students.store');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('app.students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('app.students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('app.students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('app.students.destroy');

        Route::post('/students/{student}/guardians', [StudentController::class, 'storeGuardian'])->name('app.students.guardians.store');
        Route::put('/students/{student}/guardians/{guardianship}/primary', [StudentController::class, 'makePrimary'])->name('app.students.guardians.primary');
        Route::delete('/students/{student}/guardians/{guardianship}', [StudentController::class, 'destroyGuardian'])->name('app.students.guardians.destroy');
        Route::post('/students/{student}/account', [StudentController::class, 'storeAccount'])->name('app.students.account.store');
        Route::delete('/students/{student}/account', [StudentController::class, 'destroyAccount'])->name('app.students.account.destroy');

        Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('app.enrollments.index');
        Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('app.enrollments.store');
    });

    Route::middleware('permission:school.manage')->group(function () {
        Route::get('/settings/school', [SchoolSettingsController::class, 'edit'])->name('app.settings.school');
        Route::put('/settings/school', [SchoolSettingsController::class, 'update'])->name('app.settings.school.update');

        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('app.years.index');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('app.years.store');
        Route::post('/academic-years/{year}/current', [AcademicYearController::class, 'setCurrent'])->name('app.years.current');
        Route::post('/academic-years/{year}/terms', [AcademicYearController::class, 'storeTerm'])->name('app.years.terms.store');
        Route::put('/terms/{term}', [AcademicYearController::class, 'updateTerm'])->name('app.years.terms.update');
        Route::get('/subjects', [SubjectController::class, 'index'])->name('app.subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('app.subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('app.subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('app.subjects.destroy');
        Route::get('/teaching-assignments', [TeachingAssignmentController::class, 'index'])->name('app.teaching.index');
        Route::post('/teaching-assignments', [TeachingAssignmentController::class, 'store'])->name('app.teaching.store');
        Route::delete('/teaching-assignments/{assignment}', [TeachingAssignmentController::class, 'destroy'])->name('app.teaching.destroy');
    });

    Route::middleware('permission:attendance.mark')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('app.attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('app.attendance.store');
    });

    Route::middleware('permission:assessment.view,assessment.enter,assessment.manage')->group(function () {
        Route::get('/assessment', [AssessmentController::class, 'index'])->name('app.assessment.index');
        Route::get('/assessment/broadsheet', [AssessmentController::class, 'broadsheet'])->name('app.assessment.broadsheet');
        Route::get('/assessment/report-cards', [AssessmentController::class, 'reportCards'])->name('app.assessment.reports');
    });

    Route::middleware('permission:assessment.manage')->group(function () {
        Route::post('/assessment/periods', [AssessmentController::class, 'storePeriod'])->name('app.assessment.periods.store');
    });

    Route::middleware('permission:assessment.enter')->group(function () {
        Route::get('/assessment/marks', [AssessmentController::class, 'marks'])->name('app.assessment.marks');
        Route::post('/assessment/marks', [AssessmentController::class, 'storeMarks'])->name('app.assessment.marks.store');
    });

    Route::middleware('permission:promotions.approve')->group(function () {
        Route::get('/promotions', [PromotionController::class, 'index'])->name('app.promotions.index');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('app.promotions.store');
        Route::post('/promotions/{batch}/commit', [PromotionController::class, 'commit'])->name('app.promotions.commit');
    });

    Route::middleware('permission:timetable.manage')->group(function () {
        Route::get('/timetable', [TimetableController::class, 'index'])->name('app.timetable.index');
        Route::post('/timetable/slots', [TimetableController::class, 'storeSlot'])->name('app.timetable.slots.store');
        Route::delete('/timetable/slots/{slot}', [TimetableController::class, 'destroySlot'])->name('app.timetable.slots.destroy');
    });

    Route::middleware('permission:finance.manage')->group(function () {
        Route::get('/fees', [FeeController::class, 'index'])->name('app.fees.index');
        Route::post('/fees/structures', [FeeController::class, 'storeStructure'])->name('app.fees.structures.store');
        Route::put('/fees/structures/{structure}', [FeeController::class, 'updateStructure'])->name('app.fees.structures.update');
        Route::post('/fees/structures/{structure}/archive', [FeeController::class, 'archiveStructure'])->name('app.fees.structures.archive');
        Route::post('/fees/invoices', [FeeController::class, 'storeInvoice'])->name('app.fees.invoices.store');
        Route::post('/fees/invoices/bulk', [FeeController::class, 'storeBulkInvoices'])->name('app.fees.invoices.bulk');
        Route::post('/fees/payments', [FeeController::class, 'storePayment'])->name('app.fees.payments.store');
        Route::post('/fees/payments/{payment}/confirm', [FeeController::class, 'confirmPayment'])->name('app.fees.payments.confirm');
        Route::post('/fees/payments/{payment}/reject', [FeeController::class, 'rejectPayment'])->name('app.fees.payments.reject');
        Route::post('/fees/schoolpay/sync', [FeeController::class, 'syncSchoolPay'])->name('app.fees.schoolpay.sync');
    });

    Route::middleware('permission:announcements.manage')->group(function () {
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('app.announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('app.announcements.store');
    });

    Route::middleware('permission:admissions.manage')->group(function () {
        Route::get('/admissions', [AdmissionController::class, 'index'])->name('app.admissions.index');
        Route::post('/admissions', [AdmissionController::class, 'store'])->name('app.admissions.store');
        Route::post('/admissions/{application}/decide', [AdmissionController::class, 'decide'])->name('app.admissions.decide');
    });

    Route::middleware('permission:lms.manage,lms.view')->group(function () {
        Route::get('/lms', [LmsController::class, 'index'])->name('app.lms.index');
    });
    Route::middleware('permission:lms.manage')->group(function () {
        Route::post('/lms/materials', [LmsController::class, 'storeMaterial'])->name('app.lms.materials.store');
        Route::post('/lms/assignments', [LmsController::class, 'storeAssignment'])->name('app.lms.assignments.store');
        Route::post('/lms/submissions/{submission}/grade', [LmsController::class, 'grade'])->name('app.lms.submissions.grade');
    });
    Route::middleware('permission:lms.view')->group(function () {
        Route::post('/lms/assignments/{assignment}/submit', [LmsController::class, 'submit'])->name('app.lms.assignments.submit');
    });

    Route::middleware('permission:cbt.manage,cbt.take')->group(function () {
        Route::get('/cbt', [CbtController::class, 'index'])->name('app.cbt.index');
    });
    Route::middleware('permission:cbt.manage')->group(function () {
        Route::post('/cbt/exams', [CbtController::class, 'storeExam'])->name('app.cbt.exams.store');
        Route::post('/cbt/questions', [CbtController::class, 'storeQuestion'])->name('app.cbt.questions.store');
        Route::post('/cbt/exams/{exam}/publish', [CbtController::class, 'publish'])->name('app.cbt.exams.publish');
    });
    Route::middleware('permission:cbt.take')->group(function () {
        Route::post('/cbt/exams/{exam}/start', [CbtController::class, 'start'])->name('app.cbt.exams.start');
        Route::get('/cbt/attempts/{attempt}', [CbtController::class, 'take'])->name('app.cbt.attempts.take');
        Route::post('/cbt/attempts/{attempt}/submit', [CbtController::class, 'submit'])->name('app.cbt.attempts.submit');
        Route::get('/cbt/attempts/{attempt}/result', [CbtController::class, 'result'])->name('app.cbt.attempts.result');
    });

    Route::middleware('permission:library.manage')->group(function () {
        Route::get('/library', [LibraryController::class, 'index'])->name('app.library.index');
        Route::post('/library/books', [LibraryController::class, 'storeBook'])->name('app.library.books.store');
        Route::post('/library/loans', [LibraryController::class, 'storeLoan'])->name('app.library.loans.store');
        Route::post('/library/loans/{loan}/return', [LibraryController::class, 'returnLoan'])->name('app.library.loans.return');
    });

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('app.inventory.index');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('app.inventory.store');
    });

    Route::middleware('permission:transport.manage')->group(function () {
        Route::get('/transport', [TransportController::class, 'index'])->name('app.transport.index');
        Route::post('/transport/routes', [TransportController::class, 'storeRoute'])->name('app.transport.routes.store');
        Route::post('/transport/allocations', [TransportController::class, 'storeAllocation'])->name('app.transport.allocations.store');
        Route::post('/transport/allocations/{allocation}/end', [TransportController::class, 'endAllocation'])->name('app.transport.allocations.end');
    });

    Route::middleware('permission:hostel.manage')->group(function () {
        Route::get('/hostel', [HostelController::class, 'index'])->name('app.hostel.index');
        Route::post('/hostel/rooms', [HostelController::class, 'storeRoom'])->name('app.hostel.rooms.store');
        Route::post('/hostel/allocations', [HostelController::class, 'storeAllocation'])->name('app.hostel.allocations.store');
        Route::post('/hostel/allocations/{allocation}/vacate', [HostelController::class, 'vacate'])->name('app.hostel.allocations.vacate');
    });

    Route::middleware('permission:hr.manage')->group(function () {
        Route::get('/hr', [HrController::class, 'index'])->name('app.hr.index');
        Route::post('/hr/leave', [HrController::class, 'storeLeave'])->name('app.hr.leave.store');
        Route::post('/hr/leave/{leave}/decide', [HrController::class, 'decideLeave'])->name('app.hr.leave.decide');
    });

    Route::middleware('permission:clinic.manage')->group(function () {
        Route::get('/clinic', [ClinicController::class, 'index'])->name('app.clinic.index');
        Route::post('/clinic/visits', [ClinicController::class, 'storeVisit'])->name('app.clinic.visits.store');
    });

    Route::middleware('permission:helpdesk.create,helpdesk.view_own,helpdesk.manage')->group(function () {
        Route::get('/helpdesk', [HelpdeskController::class, 'index'])->name('app.helpdesk.index');
    });
    Route::middleware('permission:helpdesk.create')->group(function () {
        Route::post('/helpdesk', [HelpdeskController::class, 'store'])->name('app.helpdesk.store');
    });
    Route::middleware('permission:helpdesk.view_own,helpdesk.manage')->group(function () {
        Route::post('/helpdesk/{ticket}/close', [HelpdeskController::class, 'close'])->name('app.helpdesk.close');
    });

    Route::middleware('permission:emis.manage')->group(function () {
        Route::get('/emis/export', [EmisController::class, 'export'])->name('app.emis.export');
    });
});
