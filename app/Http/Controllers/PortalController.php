<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Services\Fees\FeePaymentService;
use App\Services\Portal\PortalService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

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

        return view('app.portal.home', [
            'school' => $this->context->school(),
            'learners' => $learners,
            'student' => $student,
            'invoices' => $student ? $this->portal->invoices($student)->take(5) : collect(),
            'announcements' => $student ? $this->portal->announcements($student, $user)->take(5) : collect(),
            'resultsPreview' => $student ? $this->portal->results($student)->take(5) : collect(),
        ]);
    }

    public function results(Request $request)
    {
        $user = $request->user();
        $student = $this->portal->resolveStudent($user, $request->integer('student_id') ?: null);

        return view('app.portal.results', [
            'school' => $this->context->school(),
            'learners' => $this->portal->learnersFor($user),
            'student' => $student,
            'marks' => $this->portal->results($student),
        ]);
    }

    public function fees(Request $request)
    {
        $user = $request->user();
        $student = $this->portal->resolveStudent($user, $request->integer('student_id') ?: null);
        $canPay = in_array('fees.pay', $user->permissionsForSchool($this->context->schoolId()), true);

        return view('app.portal.fees', [
            'school' => $this->context->school(),
            'learners' => $this->portal->learnersFor($user),
            'student' => $student,
            'invoices' => $this->portal->invoices($student),
            'canPay' => $canPay,
        ]);
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
        ]);

        return back()->with('status', 'Payment recorded. Thank you.');
    }

    public function timetable(Request $request)
    {
        $user = $request->user();
        $student = $this->portal->resolveStudent($user, $request->integer('student_id') ?: null);

        return view('app.portal.timetable', [
            'school' => $this->context->school(),
            'learners' => $this->portal->learnersFor($user),
            'student' => $student,
            'slots' => $this->portal->timetable($student),
        ]);
    }

    public function announcements(Request $request)
    {
        $user = $request->user();
        $student = $this->portal->resolveStudent($user, $request->integer('student_id') ?: null);

        return view('app.portal.announcements', [
            'school' => $this->context->school(),
            'learners' => $this->portal->learnersFor($user),
            'student' => $student,
            'announcements' => $this->portal->announcements($student, $user),
        ]);
    }
}
