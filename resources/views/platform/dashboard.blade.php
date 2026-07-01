@extends('layouts.app')
@section('title','Platform dashboard')
@section('content')
  <div class="page-header">
    <div>
      <h2 class="page-header__title" style="margin:0">Platform overview</h2>
    </div>
  </div>
  <div class="grid g4">
    <div class="card stat"><div class="l">Schools</div><div class="v">{{ $stats['schools'] }}</div></div>
    <div class="card stat"><div class="l">Active</div><div class="v">{{ $stats['active'] }}</div></div>
    <div class="card stat"><div class="l">Learners</div><div class="v">{{ number_format($stats['learners']) }}</div></div>
    <div class="card stat"><div class="l">SMS sent</div><div class="v">{{ number_format($stats['sms_sent']) }}</div></div>
  </div>
  <div class="card">
    <h3>Recent schools</h3>
    <table>
      <thead><tr><th>School</th><th>Subdomain</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse($schools as $s)
        <tr>
          <td><strong>{{ $s->name }}</strong><br><span style="color:var(--muted);font-size:12px">{{ $s->district }}</span></td>
          <td>{{ $s->slug }}.{{ config('tenancy.base_domain') }}</td>
          <td><span class="pill">{{ $s->status }}</span></td>
          <td><a href="{{ route('platform.schools.show', $s) }}">View</a></td>
        </tr>
      @empty
        <tr><td colspan="4" style="color:var(--muted)">No schools yet. Onboard your first one.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
