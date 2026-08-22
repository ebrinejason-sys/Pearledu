<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\Academics\CurrentAcademicContext;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\DefaulterNoticeService;
use App\Services\Fees\FeeInvoiceService;
use App\Services\Fees\FeePaymentService;
use App\Services\SchoolPay\SchoolPayPaymentService;
use App\Services\Tenancy\TenantContext;
use App\Support\FeeKind;
use App\Support\Residency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    public function index(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $user = $request->user();
        $statusFilter = (string) $request->query('status', 'all');
        if (in_array($statusFilter, ['demanded', 'cleared', 'overdue', 'void'], true)) {
            return $this->ledger($request, $ctx, $statusFilter);
        }

        $canManageFinance = $user && in_array('finance.manage', $user->permissionsForSchool($school->id), true);

        $structures = FeeStructure::where('school_id', $school->id)->with(['schoolClass', 'term', 'learners'])->orderByDesc('id')->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('name')->orderBy('stream')->get();
        $terms = Term::where('school_id', $school->id)->orderBy('sequence')->get();
        $students = Student::where('school_id', $school->id)->where('status', 'active')->with('schoolClass')->orderBy('full_name')->get(['id', 'full_name', 'class_id', 'residency']);

        $countsBase = FeeInvoice::query()->where('school_id', $school->id)->where('status', '!=', 'void');
        $summary = [
            'demanded' => (clone $countsBase)->whereIn('status', ['open', 'partial'])->where('balance', '>', 0)->count(),
            'cleared' => (clone $countsBase)->where(function ($q) {
                $q->where('status', 'paid')->orWhere(fn ($x) => $x->where('balance', '<=', 0)->where('status', '!=', 'void'));
            })->count(),
            'overdue' => (clone $countsBase)->whereIn('status', ['open', 'partial'])
                ->where('balance', '>', 0)
                ->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->toDateString())
                ->count(),
            'outstanding' => (float) (clone $countsBase)->whereIn('status', ['open', 'partial'])->sum('balance'),
        ];

        return view('app.fees.index', compact(
            'school', 'structures', 'classes', 'terms', 'students', 'summary', 'canManageFinance'
        ));
    }

    public function invoices(Request $request, TenantContext $ctx)
    {
        return $this->ledger($request, $ctx, 'demanded');
    }

    public function cleared(Request $request, TenantContext $ctx)
    {
        return $this->ledger($request, $ctx, 'cleared');
    }

    public function overdue(Request $request, TenantContext $ctx)
    {
        return $this->ledger($request, $ctx, 'overdue');
    }

    public function receipt(FeePayment $payment, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $payment->school_id === (int) $school->id, 404);
        abort_unless($payment->status === 'confirmed', 404);
        $payment->load(['invoice.student.schoolClass', 'invoice.structure', 'recordedBy']);

        return view('app.fees.receipt', [
            'school' => $school,
            'payment' => $payment,
            'print' => request()->boolean('print'),
        ]);
    }

    public function emailReceipt(FeePayment $payment, TenantContext $ctx, \App\Services\Fees\FeeReceiptService $receipts, Request $request)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $payment->school_id === (int) $school->id, 404);
        $emails = $receipts->email($school, $payment);

        return back()->with('status', 'Receipt emailed to '.implode(', ', $emails).'.');
    }

    private function ledger(Request $request, TenantContext $ctx, string $statusFilter)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $user = $request->user();
        $canManageFinance = $user && in_array('finance.manage', $user->permissionsForSchool($school->id), true);

        $classId = $request->integer('class_id') ?: null;
        $termId = $request->integer('term_id') ?: null;
        $q = trim((string) $request->query('q', ''));

        $invoiceQuery = FeeInvoice::query()
            ->where('school_id', $school->id)
            ->with(['student.schoolClass', 'structure', 'payments' => fn ($p) => $p->where('status', 'confirmed')->orderByDesc('id')])
            ->when($classId, fn ($query) => $query->whereHas('student', fn ($s) => $s->where('class_id', $classId)))
            ->when($termId, fn ($query) => $query->whereHas('structure', fn ($s) => $s->where('term_id', $termId)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reference', 'like', '%'.$q.'%')
                        ->orWhereHas('student', fn ($s) => $s->where('full_name', 'like', '%'.$q.'%'));
                });
            });

        $countsBase = FeeInvoice::query()->where('school_id', $school->id)->where('status', '!=', 'void');
        $summary = [
            'demanded' => (clone $countsBase)->whereIn('status', ['open', 'partial'])->where('balance', '>', 0)->count(),
            'cleared' => (clone $countsBase)->where(function ($q) {
                $q->where('status', 'paid')->orWhere(fn ($x) => $x->where('balance', '<=', 0)->where('status', '!=', 'void'));
            })->count(),
            'overdue' => (clone $countsBase)->whereIn('status', ['open', 'partial'])
                ->where('balance', '>', 0)
                ->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->toDateString())
                ->count(),
            'outstanding' => (float) (clone $countsBase)->whereIn('status', ['open', 'partial'])->sum('balance'),
        ];

        if ($statusFilter === 'demanded') {
            $invoiceQuery->whereIn('status', ['open', 'partial'])->where('balance', '>', 0);
        } elseif ($statusFilter === 'cleared') {
            $invoiceQuery->where(function ($q) {
                $q->where('status', 'paid')
                    ->orWhere(fn ($x) => $x->where('balance', '<=', 0)->where('status', '!=', 'void'));
            });
        } elseif ($statusFilter === 'overdue') {
            $invoiceQuery->whereIn('status', ['open', 'partial'])
                ->where('balance', '>', 0)
                ->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->toDateString());
        } elseif ($statusFilter === 'void') {
            $invoiceQuery->where('status', 'void');
        } else {
            $invoiceQuery->where('status', '!=', 'void');
        }

        $invoices = $invoiceQuery->orderByDesc('id')->limit(200)->get();
        $pendingPayments = FeePayment::where('school_id', $school->id)
            ->where('status', 'pending')
            ->with(['invoice.student'])
            ->orderByDesc('id')
            ->limit(50)
            ->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('name')->orderBy('stream')->get();
        $terms = Term::where('school_id', $school->id)->orderBy('sequence')->get();

        $groupedInvoices = $invoices->groupBy(function (FeeInvoice $inv) {
            $class = $inv->student?->schoolClass;

            return $class instanceof SchoolClass ? $class->displayName() : 'Unassigned';
        });

        $title = match ($statusFilter) {
            'cleared' => 'Cleared invoices',
            'overdue' => 'Overdue invoices',
            'void' => 'Void invoices',
            default => 'Demanded invoices',
        };

        return view('app.fees.ledger', compact(
            'school', 'invoices', 'groupedInvoices', 'pendingPayments',
            'classes', 'terms', 'statusFilter', 'classId', 'termId', 'q', 'summary',
            'canManageFinance', 'title'
        ));
    }

    public function defaulters(Request $request, TenantContext $ctx, DefaulterNoticeService $defaulters)
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);

        $perms = $request->user()->permissionsForSchool($school->id);
        $classes = SchoolClass::query()->where('school_id', $school->id)->orderBy('level')->orderBy('name')->get();
        $classId = $request->integer('class_id') ?: null;
        $class = $classId
            ? SchoolClass::query()->where('school_id', $school->id)->where('id', $classId)->firstOrFail()
            : null;
        $rows = $class ? $defaulters->forClass($school, (int) $class->id) : collect();
        $print = $request->boolean('print');

        return view('app.fees.defaulters', [
            'school' => $school,
            'class' => $class,
            'classes' => $classes,
            'rows' => $rows,
            'print' => $print && $class,
            'canNotify' => in_array('staff.messages', $perms, true),
            'canViewLearners' => in_array('learners.view', $perms, true) || in_array('learners.manage', $perms, true),
        ]);
    }

    public function notifyDefaulters(Request $request, TenantContext $ctx, DefaulterNoticeService $defaulters)
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);
        $data = $request->validate([
            'class_id' => 'required|integer',
        ]);
        $class = SchoolClass::query()->where('school_id', $school->id)->where('id', $data['class_id'])->firstOrFail();

        try {
            $defaulters->notifyClassTeacher($school, $class, $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['class_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Class teacher notified about defaulters in '.$class->name.'.');
    }

    public function storeStructure(Request $request, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'class_id' => 'nullable|integer',
            'term_id' => 'nullable|integer',
            'kind' => ['required', Rule::in(FeeKind::keys())],
            'residency' => ['required', Rule::in(Residency::structureKeys())],
            'applies_to' => ['required', Rule::in(['class', 'learners'])],
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer',
            'due_on' => 'nullable|date',
        ]);
        if ($data['applies_to'] === 'learners' && empty($data['student_ids'])) {
            throw ValidationException::withMessages([
                'student_ids' => 'Select the learners this fee type applies to.',
            ]);
        }
        if ($data['applies_to'] === 'class' && empty($data['class_id'])) {
            throw ValidationException::withMessages([
                'class_id' => 'Choose a class for this fee structure.',
            ]);
        }
        if ($data['applies_to'] === 'learners') {
            $data['class_id'] = null;
        }
        $studentIds = $data['student_ids'] ?? [];
        unset($data['student_ids'], $data['due_on']);
        $structure = FeeStructure::create($data + ['school_id' => $school->id, 'currency' => 'UGX', 'is_active' => true]);
        if ($structure->isLearnerTargeted()) {
            $structure->syncLearners((int) $school->id, $studentIds);
        }

        return back()->with('status', 'Fee structure created.');
    }

    public function updateStructure(Request $request, FeeStructure $structure, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $structure->school_id === (int) $school->id, 404);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'class_id' => 'nullable|integer',
            'term_id' => 'nullable|integer',
            'kind' => ['nullable', Rule::in(FeeKind::keys())],
            'residency' => ['nullable', Rule::in(Residency::structureKeys())],
        ]);
        $structure->update($data);

        return back()->with('status', 'Fee structure updated.');
    }

    public function archiveStructure(FeeStructure $structure, TenantContext $ctx)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $structure->school_id === (int) $school->id, 404);
        $structure->update(['is_active' => ! $structure->is_active]);

        return back()->with('status', $structure->is_active ? 'Structure reactivated.' : 'Structure archived.');
    }

    public function storeInvoice(Request $request, TenantContext $ctx, FeeInvoiceService $invoices)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'student_id' => 'required|integer',
            'fee_structure_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0',
            'due_on' => 'nullable|date',
        ]);
        $invoice = $invoices->createSingle(
            $school->id,
            (int) $data['student_id'],
            (float) $data['amount'],
            isset($data['fee_structure_id']) ? (int) $data['fee_structure_id'] : null,
            $data['due_on'] ?? null,
        );

        return back()->with('status', 'Invoice '.$invoice->reference.' ready.');
    }

    public function storeBulkInvoices(Request $request, TenantContext $ctx, FeeInvoiceService $invoices, CurrentAcademicContext $academic)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'fee_structure_id' => 'required|integer',
            'class_id' => 'nullable|integer',
            'due_on' => 'nullable|date',
        ]);
        $structure = FeeStructure::query()->where('school_id', $school->id)->findOrFail($data['fee_structure_id']);
        if (! $structure->isLearnerTargeted() && empty($data['class_id']) && ! $structure->class_id) {
            throw ValidationException::withMessages(['class_id' => 'Choose a class to invoice.']);
        }

        $stats = $invoices->generateClassInvoices(
            $school->id,
            (int) $data['fee_structure_id'],
            (int) ($data['class_id'] ?? $structure->class_id ?? 0),
            $data['due_on'] ?? null,
            $academic->year()?->id,
        );

        return back()->with(
            'status',
            "Created {$stats['created']}. Already existed: {$stats['already_existed']}. Skipped: {$stats['skipped']}."
        );
    }

    public function voidInvoice(FeeInvoice $invoice, TenantContext $ctx, FeeInvoiceService $invoices, AuditLogger $audit, Request $request)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $invoice->school_id === (int) $school->id, 404);
        $invoices->void($invoice);
        $audit->record('fees.invoice.voided', $invoice, [
            'reference' => $invoice->reference,
            'amount' => $invoice->amount,
        ], actor: $request->user());

        return back()->with('status', 'Invoice voided.');
    }

    public function discountInvoice(Request $request, FeeInvoice $invoice, TenantContext $ctx, FeeInvoiceService $invoices, AuditLogger $audit)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $invoice->school_id === (int) $school->id, 404);
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:190',
        ]);
        $invoices->applyDiscount($invoice, (float) $data['amount'], $data['reason'], $request->user()?->id);
        $audit->record('fees.discount.applied', $invoice, [
            'amount' => $data['amount'],
            'reason' => $data['reason'],
        ], actor: $request->user());

        return back()->with('status', 'Discount applied.');
    }

    public function storePayment(Request $request, TenantContext $ctx, FeePaymentService $svc)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        $data = $request->validate([
            'invoice_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mtn_momo,airtel_money,bank,schoolpay',
            'provider_ref' => 'nullable|string|max:120',
        ]);
        $invoice = FeeInvoice::where('school_id', $school->id)->findOrFail($data['invoice_id']);
        $payment = $svc->record([
            'school_id' => $school->id,
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'provider_ref' => $data['provider_ref'] ?? null,
            'recorded_by' => $request->user()->id,
        ], confirmImmediately: true);

        return back()->with('status', 'Payment recorded.')->with('receipt_id', $payment->id);
    }

    public function syncSchoolPay(Request $request, TenantContext $ctx, SchoolPayPaymentService $schoolPay)
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        abort_unless($school->schoolPayConfigured(), 422, 'Enable SchoolPay and save credentials under School identity first.');

        $data = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);
        $date = $data['date'] ?? now(config('app.timezone'))->toDateString();

        try {
            $stats = $schoolPay->syncDay($school, $date);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['schoolpay' => 'SchoolPay sync failed: '.$e->getMessage()]);
        }

        return back()->with(
            'status',
            "SchoolPay sync {$date}: applied {$stats['applied']}, skipped {$stats['skipped']}, unmatched {$stats['unmatched']}."
        );
    }

    public function confirmPayment(FeePayment $payment, TenantContext $ctx, FeePaymentService $svc, Request $request)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $payment->school_id === (int) $school->id, 404);
        $svc->confirm($payment, (int) $request->user()->id);

        return back()->with('status', 'Payment confirmed. Invoice balance updated.');
    }

    public function rejectPayment(FeePayment $payment, TenantContext $ctx, FeePaymentService $svc, Request $request)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $payment->school_id === (int) $school->id, 404);
        $svc->reject($payment, (int) $request->user()->id);

        return back()->with('status', 'Payment rejected. Invoice balance unchanged.');
    }

    public function reversePayment(FeePayment $payment, TenantContext $ctx, FeePaymentService $svc, Request $request, AuditLogger $audit)
    {
        $school = $ctx->school();
        abort_unless($school && (int) $payment->school_id === (int) $school->id, 404);
        $svc->reverse($payment, (int) $request->user()->id, $request->input('reason'));
        $audit->record('fees.payment.reversed', $payment, [
            'amount' => $payment->amount,
            'reason' => $request->input('reason'),
        ], actor: $request->user());

        return back()->with('status', 'Payment reversed. Invoice balance restored.');
    }
}
