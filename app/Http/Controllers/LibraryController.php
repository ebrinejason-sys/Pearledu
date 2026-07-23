<?php
namespace App\Http\Controllers;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class LibraryController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $books = LibraryBook::where('school_id',$school->id)->orderBy('title')->get();
        $loans = LibraryLoan::where('school_id',$school->id)->whereNull('returned_on')->with(['book','student'])->orderByDesc('id')->get();
        $students = Student::where('school_id',$school->id)->orderBy('full_name')->get();
        return view('app.library.index', compact('school','books','loans','students'));
    }
    public function storeBook(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['title'=>'required|string|max:200','author'=>'nullable|string|max:120','isbn'=>'nullable|string|max:40','copies'=>'nullable|integer|min:1']);
        LibraryBook::create($data + ['school_id'=>$school->id,'copies'=>$data['copies'] ?? 1]);
        return back()->with('status','Book added.');
    }
    public function storeLoan(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['book_id'=>'required|integer','student_id'=>'required|integer','due_on'=>'nullable|date','loaned_on'=>'nullable|date']);
        LibraryLoan::create($data + ['school_id'=>$school->id,'loaned_on'=>$data['loaned_on'] ?? now()->toDateString()]);
        return back()->with('status','Loan recorded.');
    }

    public function returnLoan(LibraryLoan $loan, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school && (int)$loan->school_id === (int)$school->id, 404);
        abort_if($loan->returned_on, 422, 'Already returned.');
        $loan->update(['returned_on' => now()->toDateString()]);
        return back()->with('status','Book returned.');
    }
}
