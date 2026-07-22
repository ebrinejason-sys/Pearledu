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

class FeeController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $structures = FeeStructure::where('school_id',$school->id)->orderByDesc('id')->get();
        $invoices = FeeInvoice::where('school_id',$school->id)->orderByDesc('id')->limit(100)->get();
        $classes = SchoolClass::where('school_id',$school->id)->orderBy('name')->get();
        $terms = Term::where('school_id',$school->id)->orderBy('sequence')->get();
        $students = Student::where('school_id',$school->id)->orderBy('full_name')->get();
        return view('app.fees.index', compact('school','structures','invoices','classes','terms','students'));
    }
    public function storeStructure(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['name'=>'required|string|max:120','amount'=>'required|numeric|min:0','class_id'=>'nullable|integer','term_id'=>'nullable|integer']);
        FeeStructure::create($data + ['school_id'=>$school->id,'currency'=>'UGX']);
        return back()->with('status','Fee structure created.');
    }
    public function storeInvoice(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['student_id'=>'required|integer','fee_structure_id'=>'nullable|integer','amount'=>'required|numeric|min:0','due_on'=>'nullable|date']);
        FeeInvoice::create($data + ['school_id'=>$school->id,'balance'=>$data['amount'],'status'=>'open','reference'=>'INV-'.now()->format('YmdHis')]);
        return back()->with('status','Invoice created.');
    }
    public function storePayment(Request $request, TenantContext $ctx, FeePaymentService $svc) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['invoice_id'=>'required|integer','amount'=>'required|numeric|min:0.01','method'=>'required|in:cash,mtn_momo,airtel_money,bank','provider_ref'=>'nullable|string|max:120']);
        $invoice = FeeInvoice::where('school_id',$school->id)->findOrFail($data['invoice_id']);
        $svc->record($invoice, (float)$data['amount'], $data['method'], $request->user()->id, $data['provider_ref'] ?? null);
        return back()->with('status','Payment recorded.');
    }
}
