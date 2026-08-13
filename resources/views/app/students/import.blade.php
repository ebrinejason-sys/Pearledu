@extends('layouts.app')
@section('title', 'Import learners')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">Learners</p>
      <h2 class="page-header__title">Import students</h2>
      <p style="margin:6px 0 0;color:var(--muted);font-size:14px">CSV only (Excel: File → Save As → CSV UTF-8). PearlEdu creates classes, enrollments, and skips duplicates.</p>
    </div>
  </div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  @foreach($errors->all() as $e)<div class="err" style="margin-bottom:12px">{{ $e }}</div>@endforeach

  @if(empty($headers))
    <div class="card">
      <h3 style="margin-top:0">1. Upload CSV</h3>
      <p style="color:var(--muted);font-size:13px">Include columns such as Name, Class, Parent Phone, LIN.</p>
      <form method="post" action="{{ route('app.students.import.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" required>
        <p style="margin-top:14px"><button class="btn" type="submit">Continue</button></p>
      </form>
    </div>
  @elseif(empty($preview))
    <div class="card">
      <h3 style="margin-top:0">2. Match columns</h3>
      <form method="post" action="{{ route('app.students.import.preview') }}">
        @csrf
        @php($fields = ['full_name' => 'Name', 'class' => 'Class', 'parent_name' => 'Parent name', 'parent_phone' => 'Parent phone', 'parent_email' => 'Parent email', 'lin' => 'LIN', 'emis_number' => 'EMIS'])
        @foreach($fields as $key => $label)
          <label>{{ $label }}</label>
          <select name="mapping[{{ $key }}]" @required(in_array($key, ['full_name','class'], true))>
            <option value="">—</option>
            @foreach($headers as $i => $header)
              <option value="{{ $i }}" @selected((string) ($mapping[$key] ?? '') === (string) $i)>{{ $header }}</option>
            @endforeach
          </select>
        @endforeach
        <p style="margin-top:14px"><button class="btn" type="submit">Preview</button></p>
      </form>
    </div>
  @else
    <div class="card">
      <h3 style="margin-top:0">3. Preview</h3>
      @if(!empty($preview['errors']))
        <p style="color:var(--muted)">{{ count($preview['errors']) }} row(s) have errors and will be skipped.</p>
        <ul>
          @foreach(array_slice($preview['errors'], 0, 20) as $err)
            <li>Row {{ $err['row'] }}: {{ $err['message'] }}</li>
          @endforeach
        </ul>
      @endif
      <table>
        <thead><tr><th>Name</th><th>Class</th><th>Notes</th></tr></thead>
        <tbody>
          @foreach(array_slice($preview['ok'] ?? [], 0, 25) as $row)
            <tr>
              <td>{{ $row['full_name'] }}</td>
              <td>{{ $row['class_name'] }}@if($row['will_create_class']) <span class="pill">new class</span>@endif</td>
              <td>
                @if($row['duplicate']) Duplicate — will skip
                @elseif($row['parent_phone'] || $row['parent_email']) Parent on file
                @else —
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <form method="post" action="{{ route('app.students.import.commit') }}" style="margin-top:14px">
        @csrf
        <button class="btn accent" type="submit">Import {{ count($preview['ok'] ?? []) }} row(s)</button>
        <a class="btn ghost" href="{{ route('app.students.import', ['reset' => 1]) }}">Start over</a>
      </form>
    </div>
  @endif
@endsection
