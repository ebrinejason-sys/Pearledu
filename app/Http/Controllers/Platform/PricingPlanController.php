<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\Request;

/** Platform console CRUD for the PearlEdu landing-page pricing tiers. */
class PricingPlanController extends Controller {
    public function index() {
        return view('platform.pricing.index', [
            'plans' => PricingPlan::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request) {
        $data = $this->validated($request);
        PricingPlan::create($data);
        return back()->with('status', "Added pricing plan \"{$data['name']}\".");
    }

    public function update(Request $request, PricingPlan $plan) {
        $data = $this->validated($request);
        $plan->update($data);
        return back()->with('status', "Updated pricing plan \"{$plan->name}\".");
    }

    public function destroy(PricingPlan $plan) {
        $plan->delete();
        return back()->with('status', "Deleted pricing plan \"{$plan->name}\".");
    }

    /** Features arrive as a one-per-line textarea; blank lines are dropped. */
    private function validated(Request $request): array {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'tagline' => 'nullable|string|max:160',
            'price' => 'nullable|integer|min:0|max:1000000000',
            'currency' => 'required|string|max:8',
            'billing_period' => 'required|string|max:40',
            'features_text' => 'nullable|string|max:4000',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0|max:1000',
        ]);

        $data['features'] = array_values(array_filter(array_map('trim',
            preg_split('/\r\n|\r|\n/', $data['features_text'] ?? ''))));
        unset($data['features_text']);
        $data['is_highlighted'] = $request->boolean('is_highlighted');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
