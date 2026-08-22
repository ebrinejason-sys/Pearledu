@extends('layouts.app')
@section('title', $student->full_name)
@section('content')
  <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:16px">
    <div>
      <h2 style="margin:0">{{ $student->full_name }}</h2>
      <p style="color:var(--muted);margin:6px 0 0">
        <span class="pill">{{ $student->status }}</span>
        @if($student->schoolClass) · {{ $student->schoolClass->name }} @endif
        · {{ \App\Support\Gender::label($student->gender) }}
      </p>
    </div>
    @if(!empty($canManageLearners) || !empty($canEditProfile))
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="{{ route('app.students.edit', $student) }}">Edit</a>
      @if(!empty($canManageLearners))
      <form method="post" action="{{ route('app.students.destroy', $student) }}" onsubmit="return confirm('Archive this student record?')">
        @csrf
        @method('DELETE')
        <button class="btn ghost" type="submit">Archive</button>
      </form>
      @endif
    </div>
    @endif
  </div>

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Record</h3>
      @if($student->photoUrl())
        <p><img src="{{ $student->photoUrl() }}" alt="" width="96" height="96" style="width:96px;height:96px;object-fit:cover;border-radius:12px"></p>
      @endif
      <p><strong>EMIS:</strong> {{ $student->emis_number ?: '—' }}</p>
      <p><strong>Gender:</strong> {{ \App\Support\Gender::label($student->gender) }}</p>
      <p><strong>Date of birth:</strong> {{ $student->date_of_birth?->format('Y-m-d') ?: '—' }}</p>
      <p><strong>Class:</strong> {{ $student->schoolClass?->displayName() ?: '—' }}</p>
      <p><strong>Day / boarding:</strong> {{ \App\Support\Residency::label($student->residency) }}</p>
      <p><strong>Nationality:</strong> {{ $student->nationality ?: 'Uganda' }}</p>
      <p><strong>Religion:</strong> {{ $student->religion ?: '—' }}</p>
      <p><strong>Home address:</strong> {{ $student->home_address ?: '—' }}</p>
      <p><strong>Medical notes:</strong> {{ $student->medical_notes ?: '—' }}</p>
      <p><strong>Status:</strong> {{ $student->status }}</p>
      <p style="color:var(--muted);font-size:13px">LIN/NIN are hidden on this page to avoid unnecessary sensitive reads.@if(!empty($canEditProfile)) Open Edit to view or change them.@endif</p>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Student login</h3>
      @if($student->user)
        <p>
          <strong>{{ $student->user->full_name }}</strong>
          <span class="pill">{{ $student->user->status }}</span>
        </p>
        <p style="color:var(--muted);font-size:13px;margin:0 0 12px">{{ $student->user->email }}@if($student->user->phone) · {{ $student->user->phone }}@endif</p>
        @if(!empty($canManageLearners))
        <form method="post" action="{{ route('app.students.account.destroy', $student) }}" onsubmit="return confirm('Unlink this login from the learner? The user account is kept.')">
          @csrf
          @method('DELETE')
          <button class="btn ghost" type="submit">Unlink login</button>
        </form>
        @endif
      @else
        <p style="color:var(--muted)">No portal login linked.@if(!empty($canManageLearners)) Invite or attach one so the learner can use results, LMS, and CBT.@endif</p>
        @if(!empty($canManageLearners))

        <h4>Attach existing member</h4>
        <form method="post" action="{{ route('app.students.account.store', $student) }}" style="margin-bottom:18px">
          @csrf
          <input type="hidden" name="mode" value="attach">
          <label>Email</label><input name="email" type="email" required value="{{ old('mode') === 'attach' && ! old('relationship') ? old('email') : '' }}">
          @error('email')<div class="err">{{ $message }}</div>@enderror
          <p style="margin-top:8px"><button class="btn" type="submit">Attach login</button></p>
        </form>

        <h4>Invite new student login</h4>
        <form method="post" action="{{ route('app.students.account.store', $student) }}">
          @csrf
          <input type="hidden" name="mode" value="invite">
          <div class="grid g2">
            <div><label>Full name</label><input name="full_name" required value="{{ old('mode') === 'invite' ? old('full_name') : $student->full_name }}"></div>
            <div><label>Email</label><input name="email" type="email" required value="{{ old('mode') === 'invite' ? old('email') : '' }}"></div>
            <div><label>Phone</label><input name="phone" value="{{ old('mode') === 'invite' ? old('phone') : '' }}"></div>
          </div>
          @error('full_name')<div class="err">{{ $message }}</div>@enderror
          <p style="margin-top:8px"><button class="btn" type="submit">Invite &amp; link</button></p>
        </form>
        @endif
      @endif
    </div>
  </div>

  <div class="card" style="margin-top:16px">
      <h3 style="margin-top:0">Guardians</h3>
      @if($student->guardianships->isEmpty())
        <p style="color:var(--muted)">No guardians linked yet.</p>
      @else
        <ul style="list-style:none;padding:0;margin:0 0 16px">
          @foreach($student->guardianships as $link)
            <li style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:8px 0;border-top:1px solid var(--border, #e5e7eb)">
              <div style="display:flex;gap:12px;align-items:center">
                @if($link->guardian?->avatarUrl())
                  <img src="{{ $link->guardian->avatarUrl() }}" alt="" width="48" height="48" style="width:48px;height:48px;object-fit:cover;border-radius:10px">
                @endif
                <div>
                  <strong>{{ $link->guardian?->full_name ?? 'Unknown' }}</strong>
                  <div style="color:var(--muted);font-size:13px">
                    {{ $link->guardian?->email }}
                    @if($link->relationship) · {{ $link->relationship }} @endif
                    @if($link->is_primary) · <span class="pill">primary</span> @endif
                  </div>
                </div>
              </div>
              <div style="display:flex;gap:8px;align-items:center">
                @if(!empty($canManageLearners))
                @unless($link->is_primary)
                  <form method="post" action="{{ route('app.students.guardians.primary', [$student, $link]) }}">
                    @csrf
                    @method('PUT')
                    <button class="btn ghost" type="submit">Make primary</button>
                  </form>
                @endunless
                <form method="post" action="{{ route('app.students.guardians.destroy', [$student, $link]) }}" onsubmit="return confirm('Detach this guardian?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn ghost" type="submit">Detach</button>
                </form>
                @endif
              </div>
            </li>
          @endforeach
        </ul>
      @endif

      @if(!empty($canLinkGuardians))
      <h4>Attach existing member</h4>
      <form method="post" action="{{ route('app.students.guardians.store', $student) }}" style="margin-bottom:18px">
        @csrf
        <input type="hidden" name="mode" value="attach">
        <div class="grid g2">
          <div><label>Email</label><input name="email" type="email" required value="{{ old('mode') === 'attach' ? old('email') : '' }}"></div>
          <div><label>Relationship</label><input name="relationship" value="{{ old('mode') === 'attach' ? old('relationship') : '' }}" placeholder="mother, father, …"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;width:auto;margin:8px 0">
          <input type="checkbox" name="is_primary" value="1" style="width:auto"> Primary guardian
        </label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Attach</button>
      </form>

      <h4>Invite new guardian</h4>
      <form method="post" action="{{ route('app.students.guardians.store', $student) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="mode" value="invite">
        <div class="grid g2">
          <div><label>Full name</label><input name="full_name" required value="{{ old('mode') === 'invite' ? old('full_name') : '' }}"></div>
          <div><label>Email</label><input name="email" type="email" required value="{{ old('mode') === 'invite' ? old('email') : '' }}"></div>
          <div><label>Phone</label><input name="phone" value="{{ old('mode') === 'invite' ? old('phone') : '' }}"></div>
          <div><label>National ID (NIN)</label><input name="nin" required value="{{ old('mode') === 'invite' ? old('nin') : '' }}" autocomplete="off" minlength="10" maxlength="20"></div>
          <div><label>Relationship</label><input name="relationship" value="{{ old('mode') === 'invite' ? old('relationship') : '' }}"></div>
          <div><label>Photo</label><input type="file" name="photo" accept="image/*" capture="user"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;width:auto;margin:8px 0">
          <input type="checkbox" name="is_primary" value="1" style="width:auto"> Primary guardian
        </label>
        @error('full_name')<div class="err">{{ $message }}</div>@enderror
        <button class="btn" type="submit">Invite &amp; link</button>
      </form>
      @endif
  </div>

  @if(!empty($canViewFinance) || !empty($canApplyFees))
  <div class="card" style="margin-top:16px">
    <h3 style="margin-top:0">Fees</h3>
    <p style="color:var(--muted);font-size:13px;margin-top:0">Class day or boarding tuition is billed from the saved class structure. Extra fees (van, club) are applied on this profile. The cumulative balance is what this learner is meant to pay.</p>
    @if(!empty($canApplyFees))
      <form method="post" action="{{ route('app.students.fees.apply', $student) }}" style="margin-bottom:18px">
        @csrf
        <div class="grid g2">
          <div>
            <label>Custom fee name</label>
            <input name="name" value="{{ old('name') }}" placeholder="e.g. Van for Aisha">
            @error('name')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Amount (UGX)</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount') }}">
            @error('amount')<div class="err">{{ $message }}</div>@enderror
          </div>
          <div>
            <label>Kind</label>
            <select name="kind">
              @foreach($feeKinds as $kind)
                <option value="{{ $kind }}" @selected(old('kind', 'transport') === $kind)>{{ \App\Support\FeeKind::label($kind) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Due on (optional)</label>
            <input type="date" name="due_on" value="{{ old('due_on') }}">
          </div>
        </div>
        <p style="margin-top:8px"><button class="btn" type="submit">Apply custom fee</button></p>
      </form>
      @if(($applyableStructures ?? collect())->isNotEmpty())
        <form method="post" action="{{ route('app.students.fees.apply', $student) }}">
          @csrf
          <label>Or apply a saved learner extra</label>
          <select name="fee_structure_id" required>
            <option value="">— Select —</option>
            @foreach($applyableStructures as $structure)
              <option value="{{ $structure->id }}">{{ $structure->name }} · UGX {{ number_format((float) $structure->amount) }}</option>
            @endforeach
          </select>
          @error('fee_structure_id')<div class="err">{{ $message }}</div>@enderror
          <p style="margin-top:8px"><button class="btn ghost" type="submit">Apply saved extra</button></p>
        </form>
      @endif
    @endif
    @if(!empty($canViewFinance))
      <h4>Amount due (cumulative)</h4>
      @if(empty($statement['lines']))
        <p style="color:var(--muted);margin:0">No fee activity yet.</p>
      @else
        <table>
          <thead><tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
          <tbody>
            @foreach($statement['lines'] as $line)
              <tr>
                <td>{{ $line['date'] }}</td>
                <td>{{ $line['description'] }}</td>
                <td>{{ $line['debit'] > 0 ? number_format($line['debit']) : '—' }}</td>
                <td>{{ $line['credit'] > 0 ? number_format($line['credit']) : '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <p style="margin:12px 0 0"><strong>Balance: UGX {{ number_format($statement['balance']) }}</strong></p>
      @endif
    @endif
  </div>
  @endif
@endsection
