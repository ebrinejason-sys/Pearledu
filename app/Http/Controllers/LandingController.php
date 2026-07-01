<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller {
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
        ]);
    }

    private function team(): array {
        return [
            ['name' => 'Tusuubira Victor', 'role' => 'CEO/Founder', 'photo' => 'team-victor.jpg'],
            ['name' => 'Kamanzi Ahmed', 'role' => 'Head of Marketing and Operations', 'photo' => 'team-kamanzi.jpg'],
            ['name' => 'Muwanguzi Joan Najjingo', 'role' => 'Finance and Sales Manager', 'photo' => 'team-joan.jpg'],
            ['name' => 'Muhumuza Alex', 'role' => 'Head of Product Development', 'photo' => 'team-alex.jpg'],
            ['name' => 'Naikambo Sandra', 'role' => 'Sign Language Specialist and Consultant', 'photo' => 'team-sandra.jpg'],
            ['name' => 'Oyoka Daniel', 'role' => 'Machine Learning Expert and Developer', 'photo' => 'team-daniel.jpg'],
        ];
    }

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
