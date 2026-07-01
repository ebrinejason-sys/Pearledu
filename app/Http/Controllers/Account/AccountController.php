<?php
namespace App\Http\Controllers\Account;
use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller {
    public function show() { return view('account.settings'); }

    public function destroy(Request $request, AccountDeletionService $deletion) {
        $request->validate(['confirm'=>'required|in:DELETE','password'=>'required|string']);
        $user = Auth::user();
        if (! Hash::check($request->input('password'), $user->password ?? '')) {
            throw ValidationException::withMessages(['password'=>'Password is incorrect.']);
        }
        Auth::logout();
        $deletion->erase($user, 'self');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('status', 'Your account and personal data have been erased.');
    }
}
