<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request, PasswordResetService $resets) {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:160',
            'password' => 'required|string|min:10|confirmed',
        ]);

        $status = $resets->reset($data['email'], $data['token'], $data['password']);

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('status', 'Password updated — you can sign in now.')
            : back()->withErrors(['email' => __($status)]);
    }
}
