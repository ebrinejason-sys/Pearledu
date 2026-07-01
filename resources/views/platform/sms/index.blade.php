@extends('layouts.app')
@section('title','SMS & credit')
@section('content')
  <h2>SMS &amp; credit (reselling)</h2>
  <div class="card">
    <h3>Provider settings</h3>
    <form method="post" action="{{ route('platform.sms.settings') }}">
      @csrf
      <div class="grid g4">
        <div><label>Provider</label><input name="provider" value="{{ $settings->provider }}"></div>
        <div><label>Sender ID</label><input name="sender_id" value="{{ $settings->sender_id }}"></div>
        <div><label>Credits / segment</label><input name="segment_credits" type="number" value="{{ $settings->segment_credits }}"></div>
        <div><label>Enabled</label><select name="is_enabled"><option value="1" @selected($settings->is_enabled)>Yes</option><option value="0" @selected(!$settings->is_enabled)>No</option></select></div>
      </div>
      <p><button class="btn ghost" type="submit">Save settings</button></p>
    </form>
  </div>
  <div class="card">
    <h3>School credit</h3>
    <table>
      <thead><tr><th>School</th><th>Balance</th><th>Top up</th></tr></thead>
      <tbody>
      @foreach($rows as $r)
        <tr>
          <td>{{ $r['school']->name }}</td>
          <td><strong>{{ number_format($r['balance']) }}</strong> credits</td>
          <td>
            <form method="post" action="{{ route('platform.sms.topup', $r['school']) }}" style="display:flex;gap:8px">
              @csrf
              <input name="credits" type="number" min="1" placeholder="credits" style="max-width:140px">
              <button class="btn accent" type="submit">Add</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@endsection
