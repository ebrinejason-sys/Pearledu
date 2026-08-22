<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Services\Fees\FeePaymentService;
use App\Services\Portal\PortalService;
use App\Services\SchoolPay\SchoolPayPaymentService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PortalController extends Controller
{
    public function __construct(
        private PortalService $portal,
        private TenantContext $context,
    ) {}

    public function home(Request $request)
    {
        $user = $request->user();
        $learners = $this->portal->learnersFor($user);
        $student = $learners->isNotEmpty()
            ? $this->portal->resolveStudent($user, $request->integer('student_id') ?: null)
            : null;

        $schoolId = $this->context->schoolId();
        $permissions = $schoolId ? $user->permissionsForSchool($schoolId) : [];
        $canPay = in_array('fees.pay', $permissions, true);
        $classTeacher = $student ? $this->portal->classTeacherFor($student) : null;
        $latestAttendance = $this->portal->latestAttendanceFor($learners->pluck('id')->all());

        $expected = 0.0;
        $balance = 0.0;
        if ($student) {
            foreach ($this->portal->invoices($student) as $inv) {
                if ($inv->status === 'void') {
                    continue;
                }
                $expected += (float) $inv->amount;
                $balance += (float) $inv->balance;
            }
        }

        return view('app.portal.home', [
            'school' => $this->context->school(),
            'learners' => $learners,
            'student' => $student,
            'invoices' => $student ? $this->portal->invoices($student)->take(5) : collect(),
            'announcements' => $student ? $this->portal->announcements($student, $user)->take(5) : collect(),
            'resultsPreview' => $student ? $this->portal->results($student)->take(5) : collect(),
            'attendancePreview' => $student ? $this->portal->attendance($student)->take(7) : collect(),
            'latestAttendance' => $latestAttendance,
            'classTeacher' => $classTeacher,
            'canPay' => $canPay,
            'feeExpected' => $expected,
            'feeBalance' => $balance,
            'todaySlots' => $student
                ? $this->portal->timetable($student)->where('day_of_week', (int) now(config('app.timezone'))->isoWeekday())->values()
                : collect(),
            'canViewFees' => in_array('child.fees.view', $permissions, true)
                || in_array('self.fees.view', $permissions, true)
                || $canPay,
            'canViewAttendance' => in_array('child.attendance.view', $permissions, true)
                || in_array('self.attendance.view', $permissions, true),
            'isParent' => in_array('child.results.view', $permissions, true) || $canPay,
            'isStudent' => in_array('self.results.view', $permissions, true),
        ]);
    }

    public function results(Request $request)
    {
        return $this->portalPage($request, 'app.portal.results', function ($user, $student) {
            return ['marks' => $student ? $this->portal->results($student) : collect()];
        });
    }

    public function fees(Request $request)
    {
        return $this->portalPage($request, 'app.portal.fees', function ($user, $student) {
            $canPay = in_array('fees.pay', $user->permissionsForSchool($this->context->schoolId()), true);
            $school = $this->context->school();

            return [
                'invoices' => $student ? $this->portal->invoices($student) : collect(),
                'canPay' => $canPay,
                'schoolPayEnabled' => $school?->schoolPayConfigured() && config('schoolpay.adhoc_enabled'),
            ];
        });
    }

    public function pay(Request $request, FeeInvoice $invoice, FeePaymentService $payments)
    {
        $user = $request->user();
        $this->portal->assertCanPay($user, $invoice);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,mtn_momo,airtel_money,bank'],
            'provider_ref' => ['nullable', 'string', 'max:120'],
        ]);

        $payments->record([
            'school_id' => (int) $invoice->school_id,
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'provider_ref' => $data['provider_ref'] ?? null,
            'recorded_by' => $user->id,
        ], confirmImmediately: false);

        return back()->with('status', 'Payment submitted for school verification. Balance updates after confirmation.');
    }

    public function payWithSchoolPay(Request $request, FeeInvoice $invoice, SchoolPayPaymentService $schoolPay)
    {
        $user = $request->user();
        $this->portal->assertCanPay($user, $invoice);

        $school = $this->context->school();
        abort_unless($school && (int) $invoice->school_id === (int) $school->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $callbackUrl = route('webhooks.schoolpay.callback', $school);

        try {
            $result = $schoolPay->initiateAdhoc(
                $school,
                $invoice,
                (float) $data['amount'],
                $data['phone'],
                $user,
                $callbackUrl,
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        $ref = $result['schoolpay_reference'] ?? $result['payment']->external_reference;

        return back()->with(
            'status',
            'SchoolPay debit request sent to '.$data['phone'].'. Approve on your phone. Ref: '.$ref.'. Balance updates automatically when paid.'
        );
    }

    public function timetable(Request $request)
    {
        return $this->portalPage($request, 'app.portal.timetable', function ($user, $student) {
            return ['slots' => $student ? $this->portal->timetable($student) : collect()];
        });
    }

    public function announcements(Request $request)
    {
        return $this->portalPage($request, 'app.portal.announcements', function ($user, $student) {
            return ['announcements' => $student ? $this->portal->announcements($student, $user) : collect()];
        });
    }

    public function attendance(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->context->schoolId();
        $perms = $user->permissionsForSchool($schoolId);
        abort_unless(
            in_array('child.attendance.view', $perms, true) || in_array('self.attendance.view', $perms, true),
            403
        );

        return $this->portalPage($request, 'app.portal.attendance', function ($user, $student) {
            return ['records' => $student ? $this->portal->attendance($student) : collect()];
        });
    }

    /**
     * Soft-empty portal pages when the account has no linked learner yet.
     *
     * @param  callable(mixed, mixed): array<string, mixed>  $payload
     */
    private function portalPage(Request $request, string $view, callable $payload)
    {
        $user = $request->user();
        $learners = $this->portal->learnersFor($user);
        $student = null;
        if ($learners->isNotEmpty()) {
            $student = $this->portal->resolveStudent($user, $request->integer('student_id') ?: null);
        }

        return view($view, array_merge([
            'school' => $this->context->school(),
            'learners' => $learners,
            'student' => $student,
        ], $payload($user, $student)));
    }
}
