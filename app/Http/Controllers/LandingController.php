<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller {
    public function index() {
        return view('landing.home', [
            'team' => $this->team(),
            'partners' => $this->partners(),
            'testimonials' => $this->testimonials(),
            'pricingTiers' => $this->pricingTiers(),
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

    private function partners(): array {
        return [
            ['name' => 'Uganda National Association of the Deaf', 'logo' => 'partner-unad.png'],
            ['name' => 'Kyambogo University — Faculty of Special Needs & Rehabilitation', 'logo' => 'partner-kyu.png'],
            ['name' => 'YouTube', 'logo' => 'partner-youtube.webp'],
            ['name' => 'TGN Systems', 'logo' => 'partner-4.jpg'],
            ['name' => 'Makerere University', 'logo' => null],
            ['name' => 'Makerere Innovation and Incubation Centre', 'logo' => null],
        ];
    }

    private function testimonials(): array {
        return [
            [
                'quote' => "I can't wait to try VoxSign! The idea of having an AI avatar translate speech into Ugandan Sign Language sounds life-changing for students like me.",
                'name' => 'Naikambo Sandra',
                'role' => 'Student, Makerere University',
            ],
            [
                'quote' => "As an educator, I'm excited about VoxSign's potential to make classrooms more inclusive. If it works as described, it will truly revolutionize how we teach Deaf students.",
                'name' => 'Birabwa Jane Lydia',
                'role' => 'Educator, Makerere University',
            ],
            [
                'quote' => "I'm really looking forward to VoxSign's launch. A tool that can help my child understand spoken words and communicate back would be a dream come true.",
                'name' => "Naikambo Sandra's Parent",
                'role' => 'Parent',
            ],
        ];
    }

    private function pricingTiers(): array {
        return [
            ['name' => 'Free', 'price' => 'UGX 0', 'features' => ['3,000 words/day limit']],
            ['name' => 'Premium', 'price' => 'UGX 50,000/month', 'features' => ['Unlimited translations', 'Ad-free', 'Collaboration tools']],
            ['name' => 'Enterprise', 'price' => 'UGX 50,000,000/year', 'features' => ['Custom branding', 'Dedicated support']],
            ['name' => 'Government/NGOs', 'price' => 'UGX 500,000,000/year', 'features' => ['Full suite', 'Chatbot', 'Analytics']],
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
