@extends('emails.layout', ['eyebrow' => 'Contact form'])

@section('body')
  <p style="margin:0 0 16px">New message from the VoxSign contact form:</p>
  <p style="margin:0 0 16px"><strong>Name:</strong> {{ $name }}<br>
  <strong>Email:</strong> {{ $email }}</p>
  <p style="margin:0">{{ $messageBody }}</p>
@endsection
