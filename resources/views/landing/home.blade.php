@extends('layouts.app')
@section('title','VoxSign')
@section('content')
  <div class="card" style="background:linear-gradient(135deg,var(--brand),var(--brand-600));color:#fff">
    <h1 style="margin:0 0 6px">VoxSign</h1>
    <p style="opacity:.9;max-width:560px">Software, built deliberately. Secure, multi-tenant systems for Ugandan institutions.</p>
  </div>

  <div class="card">
    <span class="pill">Flagship product</span>
    <h2 style="margin:8px 0">PearlEdu — School Management</h2>
    <p style="color:var(--muted);max-width:620px">Onboarding, fees, assessment, attendance and parent SMS — multi-tenant, with database-enforced data isolation. Each school runs on its own subdomain.</p>
    <a class="btn accent" href="https://pearledu.{{ config('tenancy.base_domain') }}">Open PearlEdu →</a>
  </div>

  <div class="card">
    <h3>Contact us</h3>
    <form method="post" action="{{ route('contact') }}">
      @csrf
      <div style="position:absolute;left:-9999px"><input name="website" tabindex="-1" autocomplete="off"></div>
      <div class="grid g2">
        <div><label>Name</label><input name="name" required></div>
        <div><label>Email</label><input name="email" type="email" required></div>
      </div>
      <label>Message</label><textarea name="message" rows="4" required></textarea>
      @error('name')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit">Send</button></p>
    </form>
  </div>
@endsection
