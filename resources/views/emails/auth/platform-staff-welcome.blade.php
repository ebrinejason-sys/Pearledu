@extends('emails.layout', ['eyebrow' => $isPasswordReset ? 'Password reset' : 'PearlEdu staff'])

@section('body')
  <p style="margin:0 0 16px">Hi {{ $user->full_name }},</p>

  @if($isPasswordReset)
    <p style="margin:0 0 12px">Your PearlEdu admin password was reset by a platform administrator.</p>
  @else
    <p style="margin:0 0 12px">You have been added as <strong>{{ $roleLabel }}</strong> on {{ config('app.name') }}.</p>
    <p style="margin:0 0 12px">Use the credentials below to sign in to the PearlEdu admin console. Change your password after your first login.</p>
  @endif

  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;width:100%;background:#F5FBFD;border:1px solid #D4E8EE;border-radius:12px">
    <tr>
      <td style="padding:16px 18px;font-size:14px">
        <div style="margin:0 0 8px"><strong>Email:</strong> {{ $user->email }}</div>
        <div style="margin:0"><strong>Temporary password:</strong>
          <code style="display:inline-block;margin-top:4px;padding:6px 10px;background:#fff;border:1px solid #D4E8EE;border-radius:8px;font-size:15px;letter-spacing:.02em">{{ $temporaryPassword }}</code>
        </div>
      </td>
    </tr>
  </table>

  <p style="margin:0 0 24px;text-align:center">
    <a href="{{ $loginUrl }}" style="display:inline-block;background:#053F5C;color:#fff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:999px">Sign in to PearlEdu</a>
  </p>

  <p style="margin:0;font-size:13px;color:#4A6270">
    Sign in: <a href="{{ $loginUrl }}" style="color:#429EBD">{{ $loginUrl }}</a><br>
    After login, open the admin console at <strong>/admin</strong>.
  </p>
@endsection

@section('footer')
  If you were not expecting this email, contact your PearlEdu administrator. Do not forward this message — it contains a temporary password.
@endsection
