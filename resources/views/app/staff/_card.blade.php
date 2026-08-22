@php
  $user = $member['user'];
  $primary = $member['primary_key'] ?? 'staff';
  $status = (string) $user->status;
  $loadCount = count($member['teaching_load'] ?? []);
  $periodTotal = collect($member['teaching_load'] ?? [])->sum('periods');
@endphp
<article class="staff-card staff-card--{{ $primary }}">
  <div class="staff-card__hero">
    @if($user->avatarUrl())
      <img src="{{ $user->avatarUrl() }}" alt="" class="staff-card__photo-lg" width="72" height="72">
    @else
      <span class="staff-card__photo-lg staff-card__photo-lg--initial" aria-hidden="true">{{ $user->avatarInitial() }}</span>
    @endif
    <div class="staff-card__identity">
      <span class="staff-card__name">{{ $user->full_name }}</span>
      <span class="staff-card__meta staff-card__meta--on-hero">{{ $user->email ?? $user->phone ?? '—' }}</span>
      <span class="staff-card__status staff-card__status--{{ $status }}">{{ $status }}</span>
    </div>
  </div>
  <div class="staff-card__body">
    @if(empty($member['can_administer']))
    <div class="staff-card__roles">
      @foreach($member['roles'] as $role)
        <span class="pill">{{ $role['label'] }}@if(!empty($role['class'])) · {{ $role['class'] }}@endif</span>
      @endforeach
    </div>
    @endif
    @if($loadCount > 0)
      <div class="staff-card__stats">
        <span><strong>{{ $loadCount }}</strong> subject{{ $loadCount === 1 ? '' : 's' }}</span>
        <span><strong>{{ $periodTotal }}</strong> periods/wk</span>
      </div>
      <div class="teach-chips">
        @foreach($member['teaching_load'] as $load)
          <span class="pill">{{ $load['subject'] }} · {{ $load['class'] }} · {{ $load['periods'] }}/wk</span>
        @endforeach
      </div>
    @endif
    @if(!empty($member['can_administer']) && $roles->isNotEmpty())
      <form method="post" action="{{ route('app.staff.roles', $user) }}">
        @csrf @method('PUT')
        @foreach($member['role_keys'] as $existingKey)
          @unless($roles->contains('key', $existingKey))
            <input type="hidden" name="role_keys[]" value="{{ $existingKey }}">
          @endunless
        @endforeach
        <div class="role-picks">
          @foreach($member['roles'] as $role)
            @unless($roles->contains('key', $role['key']))
              <span class="pill">{{ $role['label'] }}@if(!empty($role['class'])) · {{ $role['class'] }}@endif</span>
            @endunless
          @endforeach
          @foreach($roles as $role)
            <label class="role-pick">
              <input type="checkbox" name="role_keys[]" value="{{ $role->key }}" class="js-member-role" data-user="{{ $user->id }}" @checked(collect($member['role_keys'])->contains($role->key))>
              <strong>{{ $role->label }}</strong>
            </label>
          @endforeach
        </div>
        <div class="js-member-homeroom" data-user="{{ $user->id }}" @if(!collect($member['role_keys'])->contains('class_teacher')) hidden @endif>
          <label>Homeroom class</label>
          <select name="class_id">
            <option value="">Select class</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" @selected((string) $member['homeroom_class_id'] === (string) $c->id)>{{ $c->displayName() }}</option>
            @endforeach
          </select>
        </div>
        <div class="js-member-teach" data-user="{{ $user->id }}" @if(!collect($member['role_keys'])->contains('subject_teacher')) hidden @endif>
          <p style="margin:12px 0 0;font-size:13px;color:var(--muted)">Add or extend teaching load (subject + classes + periods/week). Existing rows stay unless you replace them from Teaching assignments.</p>
          @include('app.teaching._load-builder', [
            'builderId' => 'member-load-'.$user->id,
            'subjects' => $subjects ?? collect(),
            'classes' => $classes,
            'hint' => 'New rows are added to this teacher. One person may teach many subjects to many classes.',
          ])
        </div>
        <p style="margin:12px 0 0;display:flex;flex-wrap:wrap;gap:8px">
          <button type="submit" class="btn ghost">Save responsibilities</button>
          @if(!empty($member['can_edit_file']))
            <a class="btn ghost" href="{{ route('app.staff.show', $user) }}">Edit details</a>
          @else
            <a class="btn ghost" href="{{ route('app.staff.show', $user) }}">Profile</a>
          @endif
          @if(!empty($canPrintId))
            <a class="btn ghost" href="{{ route('app.staff.id', $user) }}">ID card</a>
          @endif
        </p>
      </form>
    @else
      <p style="margin:12px 0 0">
        @if(!empty($member['can_edit_file']))
          <a class="btn ghost" href="{{ route('app.staff.show', $user) }}">Edit details</a>
        @else
          <a class="btn ghost" href="{{ route('app.staff.show', $user) }}">Profile</a>
        @endif
        @if(!empty($canPrintId))
          <a class="btn ghost" href="{{ route('app.staff.id', $user) }}">ID card</a>
        @endif
      </p>
    @endif
    @if(!empty($member['can_administer']) && (int) $user->id !== (int) auth()->id())
      <form method="post" action="{{ route('app.staff.revoke', $user) }}" onsubmit="return confirm('Revoke school access for {{ $user->full_name }}?')" style="margin-top:8px">
        @csrf @method('DELETE')
        <button type="submit" class="btn ghost" style="color:var(--danger,#b42318)">Revoke</button>
      </form>
    @endif
  </div>
</article>
