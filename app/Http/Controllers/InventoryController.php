<?php
namespace App\Http\Controllers;
use App\Models\InventoryItem;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class InventoryController extends Controller {
    public function index(TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $items = InventoryItem::where('school_id',$school->id)->orderBy('name')->get();
        return view('app.inventory.index', compact('school','items'));
    }
    public function store(Request $request, TenantContext $ctx) {
        $school = $ctx->school(); abort_unless($school, 404);
        $data = $request->validate(['name'=>'required|string|max:160','sku'=>'nullable|string|max:60','quantity'=>'nullable|integer|min:0','location'=>'nullable|string|max:120']);
        InventoryItem::create($data + ['school_id'=>$school->id,'quantity'=>$data['quantity'] ?? 0]);
        return back()->with('status','Item saved.');
    }
}
