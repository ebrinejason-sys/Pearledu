<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function show(): View { return view('auth.forgot-password'); }

    public function store(Request $request, PasswordResetService $resets) {
        $data = $request->validate(['email' => 'required|email|max:160']);
        $resets->sendResetLink($data['email']);
        return back()->with('status', 'If an account exists for that email, we have sent a password reset link.');
    }
}
