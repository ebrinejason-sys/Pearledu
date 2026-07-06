@extends('emails.layout', ['eyebrow' => 'Onboarding request'])

@section('body')
  <p style="margin:0 0 16px">New PearlEdu onboarding request:</p>
  <p style="margin:0 0 16px"><strong>School:</strong> {{ $schoolName }}<br>
  <strong>Contact:</strong> {{ $contactName }}<br>
  <strong>Email:</strong> {{ $email }}<br>
  <strong>Phone:</strong> {{ $phone }}</p>
  <p style="margin:0">{{ $messageBody }}</p>
@endsection
