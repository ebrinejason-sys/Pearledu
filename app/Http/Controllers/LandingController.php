<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller {
    public function index() { return view('landing.home'); }

    public function contact(Request $request) {
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'email'=>'required|email|max:160',
            'message'=>'required|string|max:2000',
            'website'=>'nullable|max:0',   // honeypot: must be empty
        ]);
        Mail::raw($data['message']."\n\nFrom: {$data['name']} <{$data['email']}>", function ($m) use ($data) {
            $m->to(config('mail.contact_inbox', env('CONTACT_INBOX')))
              ->replyTo($data['email'], $data['name'])
              ->subject('VoxSign contact form');
        });
        return back()->with('status', 'Thanks — we will be in touch shortly.');
    }
}
