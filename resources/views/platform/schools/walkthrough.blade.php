@extends('layouts.app')
@section('title', 'Demonstration school')
@section('content')
  <div class="page-header">
    <div>
      <p class="page-header__eyebrow"><a href="{{ route('platform.schools.index') }}">Schools</a></p>
      <h2 class="page-header__title">Demonstration school</h2>
      <p style="margin:8px 0 0;color:var(--muted);font-size:14px">
        Creates or refreshes <strong>St. Kizito Demonstration Primary</strong> (Baby–P7, 100 learners)
        and sets one shared password on every named test login so you can click through roles on this host.
        @if($school)
          The school already exists (EMIS {{ $school->emis_number }}). Submitting updates every test password.
        @endif
      </p>
    </div>
  </div>

  @if($errors->any())
    <div class="err" style="margin-bottom:14px">{{ $errors->first() }}</div>
  @endif

  <div class="grid g2">
    <div class="card">
      <h3 style="margin-top:0">Set all test passwords</h3>
      <form method="post" action="{{ route('platform.schools.walkthrough.store') }}">
        @csrf
        @include('partials.password-input', ['name' => 'walkthrough_password', 'label' => 'Shared password (min 10 characters)', 'autocomplete' => 'new-password'])
        @error('walkthrough_password')<div class="err">{{ $message }}</div>@enderror
        @include('partials.password-input', ['name' => 'walkthrough_password_confirmation', 'label' => 'Confirm password', 'autocomplete' => 'new-password'])
        <p style="margin:14px 0;color:var(--muted);font-size:13px">
          Use this only for walkthrough testing. Do not use it as a real school password.
          Leave <code>SEED_TEST_SCHOOL_PASSWORD</code> unset in <code>.env</code>.
        </p>
        <p><button class="btn accent" type="submit">{{ $school ? 'Update all test passwords' : 'Create school and set passwords' }}</button></p>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Logins that will use that password</h3>
      <table>
        <thead><tr><th>Role</th><th>Name</th><th>Email</th></tr></thead>
        <tbody>
        @foreach($accounts as $account)
          <tr>
            <td>{{ str_replace('_', ' ', $account['role']) }}</td>
            <td>{{ $account['name'] }}</td>
            <td><code>{{ $account['email'] }}</code></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
@section('head')
@include('partials.password-field-assets')
@endsection
