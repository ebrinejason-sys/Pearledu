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
        return $this->portalPage($request, 'app.portal.results', function ($user, $student) {
            return ['marks' => $student ? $this->portal->results($student) : collect()];
        });
    }

    public function fees(Request $request)
    {
        return $this->portalPage($request, 'app.portal.fees', function ($user, $student) {
            $canPay = in_array('fees.pay', $user->permissionsForSchool($this->context->schoolId()), true);

            return [
                'invoices' => $student ? $this->portal->invoices($student) : collect(),
                'canPay' => $canPay,
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
