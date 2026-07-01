<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Services\Account\InvitationService;
use Illuminate\Http\Request;

class InvitationController extends Controller {
    public function __construct(private InvitationService $invitations) {}

    public function show(Request $request, int $invitation) {
        $token = (string) $request->query('token', '');
        try { $this->invitations->verify($invitation, $token); }
        catch (\Throwable $e) { abort(403, $e->getMessage()); }
        return view('auth.accept-invitation', ['invitation'=>$invitation,'token'=>$token]);
    }

    public function store(Request $request, int $invitation) {
        $data = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:10|confirmed',
        ]);
        $this->invitations->accept($invitation, $data['token'], $data['password']);
        return redirect('/login')->with('status', 'Account ready — please sign in.');
    }
}
