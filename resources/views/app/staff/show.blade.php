@extends('layouts.app')
@section('title', $staff->full_name)
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('app.staff.index') }}">Staff</a></p>
      <h2 class="page-header__title">{{ $staff->full_name }}</h2>
      <p style="color:var(--muted)">{{ \App\Support\Gender::label($staff->gender) }}</p>
    </div>
    <div class="page-header__actions">
      @if(!empty($canPrintId))
        <a class="btn" href="{{ route('app.staff.id', $staff) }}">Print ID</a>
      @endif
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      @if($staff->avatarUrl())
        <img src="{{ $staff->avatarUrl() }}" alt="" width="96" height="96" style="width:96px;height:96px;border-radius:12px;object-fit:cover">
      @endif
      <p>Email: {{ $staff->email ?: '—' }}<br>Phone: {{ $staff->phone ?: '—' }}</p>
      <p>Date of birth: {{ $staff->date_of_birth?->format('Y-m-d') ?: '—' }}</p>
      <p>Nationality: {{ $staff->nationality ?: '—' }}</p>
      <p>Address: {{ $staff->home_address ?: '—' }}</p>
      <p>Clock ID: {{ $badge->code ?? '—' }}</p>
      <p>Roles:
        @foreach($roles as $assignment)
          <span class="pill">{{ $assignment->role?->label }}@if($assignment->schoolClass) · {{ $assignment->schoolClass->name }}@endif</span>
        @endforeach
      </p>
      @if(($assignments ?? collect())->isNotEmpty())
        <h3>Teaching load</h3>
        <ul>
          @foreach($assignments as $assignment)
            <li>{{ $assignment->subject?->name }} · {{ $assignment->schoolClass?->displayName() }} · {{ $assignment->periods_per_week }} periods/week</li>
          @endforeach
        </ul>
      @endif
      @if(!empty($canEditProfile))
        <h3>Update file</h3>
        <form method="post" action="{{ route('app.staff.profile.update', $staff) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <label>Full name</label>
          <input name="full_name" required value="{{ old('full_name', $staff->full_name) }}">
          <label>Phone</label>
          <input name="phone" value="{{ old('phone', $staff->phone) }}">
          <label>NIN</label>
          <input name="nin" value="{{ old('nin') }}" autocomplete="off" placeholder="Leave blank to keep the current NIN">
          <label>Date of birth</label>
          <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $staff->date_of_birth?->format('Y-m-d')) }}">
          <label>Nationality</label>
          <input name="nationality" value="{{ old('nationality', $staff->nationality) }}">
          <label>Home address</label>
          <input name="home_address" value="{{ old('home_address', $staff->home_address) }}">
          <label>Photo</label>
          <input type="file" name="photo" accept="image/*" capture="user">
          <p style="margin-top:8px"><button class="btn" type="submit">Save profile</button></p>
        </form>
      @endif
    </div>
    <div class="card">
      <h3 style="margin-top:0">Clocking</h3>
      @forelse($punches as $punch)
        <p>{{ $punch->punched_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }} · {{ $punch->direction }}</p>
      @empty
        <p style="color:var(--muted)">No clock records, or you cannot view staff attendance.</p>
      @endforelse
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <h3 style="margin-top:0">Academic documents</h3>
    @if(($documents ?? collect())->isEmpty())
      <p style="color:var(--muted)">No documents on this file yet.</p>
    @else
      <ul style="list-style:none;padding:0">
        @foreach($documents as $document)
          <li style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:8px 0;border-top:1px solid var(--line)">
            <div>
              <a href="{{ $document->url() }}" target="_blank" rel="noopener">{{ $document->title }}</a>
              <div style="color:var(--muted);font-size:12px">{{ $document->original_name }}</div>
            </div>
            @if(!empty($canEditProfile))
              <form method="post" action="{{ route('app.staff.documents.destroy', [$staff, $document]) }}" onsubmit="return confirm('Remove this document?')">
                @csrf
                @method('DELETE')
                <button class="btn ghost" type="submit">Remove</button>
              </form>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
    @if(!empty($canEditProfile))
      <form method="post" action="{{ route('app.staff.documents.store', $staff) }}" enctype="multipart/form-data">
        @csrf
        <label>Title</label>
        <input name="title" required placeholder="e.g. Bachelor’s certificate">
        <label>File (PDF or image)</label>
        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
        <p style="margin-top:8px"><button class="btn" type="submit">Upload document</button></p>
      </form>
    @endif
  </div>

  @if(!empty($canViewPayroll))
    <div class="card" style="margin-top:16px">
      <h3 style="margin-top:0">Salary</h3>
      <p>{{ $salary ? 'UGX '.number_format($salary->amount).' from '.$salary->effective_on->format('Y-m-d') : 'No salary set.' }}</p>
      @if(!empty($canManagePayroll))
        <form method="post" action="{{ route('app.staff.payroll.salary', $staff) }}" style="margin-bottom:16px">
          @csrf
          <label>Amount (UGX)</label>
          <input type="number" min="0" name="amount" required value="{{ $salary?->amount }}">
          <label>Effective on</label>
          <input type="date" name="effective_on" required value="{{ $salary?->effective_on?->format('Y-m-d') ?? now()->toDateString() }}">
          <label>Notes</label>
          <input name="notes" value="{{ $salary?->notes }}">
          <p style="margin-top:8px"><button class="btn" type="submit">Save salary</button></p>
        </form>
      @endif
      <table>
        <thead><tr><th>Paid on</th><th>Amount</th><th>Method</th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
          <tr><td>{{ $payment->paid_on->format('Y-m-d') }}</td><td>UGX {{ number_format($payment->amount) }}</td><td>{{ $payment->method }}</td></tr>
        @empty
          <tr><td colspan="3" style="color:var(--muted)">No payments yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  @endif
@endsection
