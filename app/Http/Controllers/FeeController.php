<?php
namespace App\Http\Controllers;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\Fees\FeePaymentService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $structures = FeeStructure::where('school_id',$school->id)->with(['schoolClass','term'])->orderByDesc('id')->get();
        $invoices = FeeInvoice::where('school_id',$school->id)->with('student')->orderByDesc('id')->limit(100)->get();
        $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
        $terms = Term::where('school_id',$school->id)->orderBy('sequence')->get();
        $students = Student::where('school_id',$school->id)->orderBy('full_name')->get();
        return view('app.fees.index', compact('school','structures','invoices','classes','terms','students'));
    }

    public function storeStructure(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['name'=>'required|string|max:120','amount'=>'required|numeric|min:0','class_id'=>'nullable|integer','term_id'=>'nullable|integer']);
        FeeStructure::create($data + ['school_id'=>$school->id,'currency'=>'UGX','is_active'=>true]);
        return back()->with('status','Fee structure created.');
    }

    public function updateStructure(Request $request, FeeStructure $structure, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$structure->school_id === (int)$school->id, 404);
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'amount'=>'required|numeric|min:0',
            'class_id'=>'nullable|integer',
            'term_id'=>'nullable|integer',
        ]);
        $structure->update($data);
        return back()->with('status','Fee structure updated.');
    }

    public function archiveStructure(FeeStructure $structure, TenantContext $ctx) {
        $school = $ctx->school();
        abort_unless($school && (int)$structure->school_id === (int)$school->id, 404);
        $structure->update(['is_active' => ! $structure->is_active]);
        return back()->with('status', $structure->is_active ? 'Structure reactivated.' : 'Structure archived.');
    }

    public function storeInvoice(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'student_id'=>'required|integer',
            'fee_structure_id'=>'nullable|integer',
            'amount'=>'required|numeric|min:0',
            'due_on'=>'nullable|date',
        ]);
        FeeInvoice::create($data + [
            'school_id'=>$school->id,
            'balance'=>$data['amount'],
            'status'=>'open',
            'reference'=>'INV-'.now()->format('YmdHis').'-'.$data['student_id'],
        ]);
        return back()->with('status','Invoice created.');
    }

    public function storeBulkInvoices(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'fee_structure_id' => 'required|integer',
            'class_id' => 'required|integer',
            'due_on' => 'nullable|date',
        ]);

        $structure = FeeStructure::where('school_id', $school->id)->findOrFail($data['fee_structure_id']);
        $students = Student::where('school_id', $school->id)->where('class_id', $data['class_id'])->get();
        abort_if($students->isEmpty(), 422, 'No students in that class.');

        $count = 0;
        DB::transaction(function () use ($school, $structure, $students, $data, &$count) {
            foreach ($students as $student) {
                FeeInvoice::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'fee_structure_id' => $structure->id,
                    'amount' => $structure->amount,
                    'balance' => $structure->amount,
                    'status' => 'open',
                    'due_on' => $data['due_on'] ?? null,
                    'reference' => 'INV-'.now()->format('YmdHis').'-'.$student->id,
                ]);
                $count++;
            }
        });

        return back()->with('status', "Created {$count} invoices for the class.");
    }

    public function storePayment(Request $request, TenantContext $ctx, FeePaymentService $svc) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate([
            'invoice_id'=>'required|integer',
            'amount'=>'required|numeric|min:0.01',
            'method'=>'required|in:cash,mtn_momo,airtel_money,bank',
            'provider_ref'=>'nullable|string|max:120',
        ]);
        $invoice = FeeInvoice::where('school_id',$school->id)->findOrFail($data['invoice_id']);
        $svc->record([
            'school_id' => $school->id,
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'provider_ref' => $data['provider_ref'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);
        return back()->with('status','Payment recorded.');
    }
}
