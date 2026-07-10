@extends('emails.layout', ['eyebrow' => 'Account security'])

@section('body')
  <p style="margin:0 0 16px">Hi {{ $user->full_name }},</p>
  <p style="margin:0 0 20px">We received a request to reset the password for your {{ config('app.name') }} account. Click the button below to choose a new password. This link expires in {{ $expiresMinutes }} minutes.</p>
  <p style="margin:0 0 24px;text-align:center">
    <a href="{{ $resetUrl }}" style="display:inline-block;background:#053F5C;color:#fff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:999px">Reset password</a>
  </p>
  <p style="margin:0;font-size:13px;color:#4A6270">If the button does not work, copy and paste this link into your browser:<br>
    <a href="{{ $resetUrl }}" style="color:#429EBD;word-break:break-all">{{ $resetUrl }}</a>
  </p>
@endsection

@section('footer')
  If you did not request a password reset, you can safely ignore this email. Your password will not change.
@endsection
