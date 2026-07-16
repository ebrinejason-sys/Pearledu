@extends('layouts.app')
@section('title','SMS & credit')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Communications</p>
      <h2 class="page-header__title">SMS &amp; credits</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px;max-width:62ch">
        Configure the global SMS provider, then top up each school’s credit balance for parent notifications.
      </p>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Provider settings</h3>
    <form method="post" action="{{ route('platform.sms.settings') }}">
      @csrf
      <div class="grid g4">
        <div><label>Provider</label><input name="provider" value="{{ $settings->provider }}"></div>
        <div><label>Sender ID</label><input name="sender_id" value="{{ $settings->sender_id }}"></div>
        <div><label>Credits / segment</label><input name="segment_credits" type="number" value="{{ $settings->segment_credits }}"></div>
        <div>
          <label>Enabled</label>
          <select name="is_enabled">
            <option value="1" @selected($settings->is_enabled)>Yes</option>
            <option value="0" @selected(! $settings->is_enabled)>No</option>
          </select>
        </div>
      </div>
      <p style="margin-top:14px"><button class="btn" type="submit">Save settings</button></p>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">School credit</h3>
    @if(count($rows) === 0)
      <p style="color:var(--muted);margin:0">
        No schools yet.
        <a href="{{ route('platform.schools.create') }}">Onboard a school</a>
        to start topping up SMS credits.
      </p>
    @else
      <table>
        <thead><tr><th>School</th><th>Balance</th><th>Top up</th></tr></thead>
        <tbody>
        @foreach($rows as $r)
          <tr>
            <td><strong>{{ $r['school']->name }}</strong></td>
            <td><strong>{{ number_format($r['balance']) }}</strong> credits</td>
            <td>
              <form method="post" action="{{ route('platform.sms.topup', $r['school']) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                @csrf
                <input name="credits" type="number" min="1" placeholder="credits" style="max-width:140px" required>
                <input name="reference" placeholder="Reference (optional)" style="max-width:180px">
                <button class="btn accent" type="submit">Add</button>
              </form>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection
