@extends('emails.layout', ['eyebrow' => $isPasswordReset ? 'Password reset' : 'PearlEdu staff'])

@section('body')
  <p style="margin:0 0 16px">Hi {{ $user->full_name }},</p>

  @if($isPasswordReset)
    <p style="margin:0 0 12px">A platform administrator requested a password reset for your PearlEdu account.</p>
  @else
    <p style="margin:0 0 12px">You have been added as <strong>{{ $roleLabel }}</strong> on {{ config('app.name') }}.</p>
    <p style="margin:0 0 12px">Set your own password using the secure password-reset flow, then sign in to the PearlEdu admin console.</p>
  @endif

  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;width:100%;background:#F5FBFD;border:1px solid #D4E8EE;border-radius:12px">
    <tr>
      <td style="padding:16px 18px;font-size:14px">
        <div><strong>Account email:</strong> {{ $user->email }}</div>
      </td>
    </tr>
  </table>

  <p style="margin:0 0 24px;text-align:center">
    <a href="{{ $setPasswordUrl }}" style="display:inline-block;background:#053F5C;color:#fff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:999px">{{ $isPasswordReset ? 'Reset your password' : 'Set your password' }}</a>
  </p>

  <p style="margin:0;font-size:13px;color:#4A6270">
    Password setup: <a href="{{ $setPasswordUrl }}" style="color:#429EBD">{{ $setPasswordUrl }}</a><br>
    After setting your password, sign in at <a href="{{ $loginUrl }}" style="color:#429EBD">{{ $loginUrl }}</a>.
  </p>
@endsection

@section('footer')
  If you were not expecting this email, contact your PearlEdu administrator. Never share password-reset links or security codes.
@endsection
