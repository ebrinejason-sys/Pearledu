@extends('layouts.app')
@section('title', 'School setup')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Welcome to PearlEdu</p>
      <h2 class="page-header__title">Get this school ready</h2>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">{{ $percent }}% complete. Empty account → configured school → imported learners → invoiced fees → staff invited.</p>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif

  <div class="card">
    <ol style="list-style:none;padding:0;margin:0">
      @foreach($steps as $i => $step)
        <li style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 0;border-top:1px solid var(--border,#e5e7eb)">
          <div>
            <span class="pill">{{ $step['done'] ? 'Done' : ($i + 1) }}</span>
            <strong style="margin-left:8px">{{ $step['label'] }}</strong>
          </div>
          @if($step['route'] && \Illuminate\Support\Facades\Route::has($step['route']))
            <a class="btn ghost" href="{{ route($step['route']) }}">{{ $step['done'] ? 'Review' : 'Open' }}</a>
          @endif
        </li>
      @endforeach
    </ol>
    <form method="post" action="{{ route('app.setup.complete') }}" style="margin-top:16px">
      @csrf
      @if($next)
        <input type="hidden" name="force" value="1">
        <button class="btn ghost" type="submit">Skip remaining and go to dashboard</button>
      @else
        <button class="btn accent" type="submit">You're ready — go to dashboard</button>
      @endif
    </form>
  </div>
@endsection
