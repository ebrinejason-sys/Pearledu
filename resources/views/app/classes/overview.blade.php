@extends('layouts.app')
@section('title', 'Classes overview')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow">{{ $school->name }}</p>
      <h2 class="page-header__title">Classes &amp; people</h2>
    </div>
  </div>
  <div class="grid g2" style="margin-bottom:16px">
    <div class="card stat"><div class="l">Learners male / female</div><div class="v">{{ $gender['learners']['male'] }} / {{ $gender['learners']['female'] }}</div></div>
    <div class="card stat"><div class="l">Staff male / female</div><div class="v">{{ $gender['staff']['male'] }} / {{ $gender['staff']['female'] }}</div></div>
  </div>
  <div class="card">
    <table>
      <thead><tr><th>Class</th><th>Learners</th><th>Male</th><th>Female</th><th>Mean (published)</th><th></th></tr></thead>
      <tbody>
      @foreach($rows as $row)
        <tr>
          <td>{{ $row['class']->displayName() }}</td>
          <td>{{ $row['students'] }}</td>
          <td>{{ $row['gender']['male'] }}</td>
          <td>{{ $row['gender']['female'] }}</td>
          <td>{{ $row['mean'] !== null ? $row['mean'].'%' : '—' }}</td>
          <td>
            <a href="{{ route('app.students.index', ['class_id' => $row['class']->id]) }}">Students</a>
            · <a href="{{ route('app.assessment.broadsheet', ['class_id' => $row['class']->id]) }}">Performance</a>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@endsection
