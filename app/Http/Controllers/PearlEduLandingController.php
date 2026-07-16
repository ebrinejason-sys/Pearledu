<?php
namespace App\Http\Controllers;
use App\Models\PricingPlan;
use App\Services\Security\TurnstileVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PearlEduLandingController extends Controller {
    public function index() {
        return view('landing.pearledu-home', [
            'plans' => PricingPlan::active()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function onboard(Request $request, TurnstileVerifier $turnstile) {
        $data = $request->validate([
            'school_name'=>'required|string|max:160',
            'contact_name'=>'required|string|max:120',
            'email'=>'required|email|max:160',
            'phone'=>'nullable|string|max:40',
            'message'=>'nullable|string|max:2000',
            'website'=>'nullable|max:0',   // honeypot: must be empty
        ]);
        $turnstile->assertValid($request);

        Mail::to(config('mail.contact_inbox'))->send(new \App\Mail\SchoolOnboardingRequestReceived(
            $data['school_name'],
            $data['contact_name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['message'] ?? '',
        ));

        return back()->with('status', "Thanks — we've received your school's onboarding request and will be in touch shortly.");
    }
}
