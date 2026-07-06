@extends('emails.layout', ['eyebrow' => 'Message received'])

@section('body')
  <p style="margin:0 0 16px">Hi {{ $name }},</p>
  <p style="margin:0 0 16px">Thanks for reaching out to VoxSign — your message has been delivered and we'll be in touch with you soon.</p>
  <p style="margin:0">On behalf of VoxSign,<br>The VoxSign team</p>
@endsection
