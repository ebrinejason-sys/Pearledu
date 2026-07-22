@extends('layouts.app')
@section('title','Library · '.$school->name)
@section('content')
  <div class="page-header"><div><p class="page-header__eyebrow">Learning</p><h2 class="page-header__title">Library</h2></div></div>
  @if(session('status'))<div class="vx-auth-status" style="margin-bottom:16px">{{ session('status') }}</div>@endif
  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Add book</h3>
      <form method="post" action="{{ route('app.library.books.store') }}">@csrf
        <label>Title</label><input name="title" required>
        <label>Author</label><input name="author">
        <label>ISBN</label><input name="isbn">
        <label>Copies</label><input type="number" name="copies" value="1" min="1">
        <p style="margin-top:14px"><button class="btn" type="submit">Save book</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Loan</h3>
      <form method="post" action="{{ route('app.library.loans.store') }}">@csrf
        <label>Book</label>
        <select name="book_id" required>@foreach($books as $b)<option value="{{ $b->id }}">{{ $b->title }}</option>@endforeach</select>
        <label>Student</label>
        <select name="student_id" required>@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach</select>
        <label>Loaned on</label><input type="date" name="loaned_on" value="{{ now()->toDateString() }}" required>
        <label>Due on</label><input type="date" name="due_on">
        <p style="margin-top:14px"><button class="btn" type="submit">Record loan</button></p>
      </form>
    </div>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Books</h3>
    <table>
      <thead><tr><th>Title</th><th>Author</th><th>Copies</th></tr></thead>
      <tbody>
      @forelse($books as $b)
        <tr><td>{{ $b->title }}</td><td>{{ $b->author ?: '—' }}</td><td>{{ $b->copies }}</td></tr>
      @empty
        <tr><td colspan="3" style="color:var(--muted)">No books.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
@endsection
