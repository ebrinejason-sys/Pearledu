@extends('layouts.app')
@section('title','Send SMS')
@section('content')
  <h2>Send SMS</h2>
  <div class="card">
    <p>Credit balance: <strong>{{ number_format($balance) }}</strong></p>
    <form method="post" action="{{ route('app.sms.send') }}">
      @csrf
      <div class="grid g2">
        <div><label>To (phone)</label><input name="to" placeholder="+2567..." required></div>
        <div><label>Category</label>
          <select name="category">
            <option value="general">General</option><option value="fees">Fees</option>
            <option value="results">Results</option><option value="attendance">Attendance</option>
            <option value="auth">Auth</option>
          </select>
        </div>
      </div>
      <label>Message</label><textarea name="body" rows="3" maxlength="1000" required></textarea>
      @error('to')<div class="err">{{ $message }}</div>@enderror
      <p><button class="btn" type="submit">Send</button> <span style="color:var(--muted);font-size:13px">Credits are deducted per 160-char segment.</span></p>
    </form>
  </div>
@endsection
