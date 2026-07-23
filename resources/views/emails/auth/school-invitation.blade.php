@extends('emails.layout', ['eyebrow' => 'Welcome'])

@section('body')
  <p style="margin:0 0 16px">Hi {{ $user->full_name }},</p>
  <p style="margin:0 0 12px">You have been invited to manage <strong>{{ $schoolName }}</strong> on {{ config('app.name') }}.</p>
  <p style="margin:0 0 20px">Click the button below to set your password and activate your account. This invitation expires on {{ $expiresAt->timezone(config('app.timezone'))->format('j M Y, g:i A T') }}.</p>
  <p style="margin:0 0 24px;text-align:center">
    <a href="{{ $acceptUrl }}" style="display:inline-block;background:#053F5C;color:#fff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:999px">Accept invitation</a>
  </p>
  <p style="margin:0;font-size:13px;color:#4A6270">Sign in at: <a href="{{ $schoolUrl }}" style="color:#429EBD">{{ $schoolUrl }}</a> (same link for every school — your account loads your school’s data).</p>
@endsection

@section('footer')
  If you were not expecting this invitation, you can ignore this email.
@endsection
