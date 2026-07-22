@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">PearlEdu</p><h2 class="page-header__title">Announcements</h2></div></div>
  @if(session('status'))<p style="color:var(--brand);margin-bottom:12px">{{ session('status') }}</p>@endif
  <div class="card"><form method="post" action="{{ route('app.announcements.store') }}">@csrf
    <label>Title</label><input name="title" required>
    <label>Body</label><textarea name="body" rows="4" required></textarea>
    <label>Audience</label><select name="audience"><option value="school">Whole school</option><option value="class">Class</option><option value="role">Role</option><option value="guardians">Guardians</option></select>
    <label>Class</label><select name="class_id"><option value="">—</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
    <label><input type="checkbox" name="send_sms" value="1"> Also SMS members with phones</label>
    <p><button class="btn" type="submit">Publish</button></p>
  </form></div>
  <div class="card">@foreach($announcements as $a)<div style="margin-bottom:12px"><strong>{{ $a->title }}</strong><div style="color:var(--muted)">{{ $a->created_at }}</div><p>{{ $a->body }}</p></div>@endforeach</div>
@endsection
