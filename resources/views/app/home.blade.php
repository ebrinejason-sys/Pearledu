@extends('layouts.app')
@section('title', $school?->name ?? 'Home')
@section('content')
  @if(!$school)
    <div class="card"><p>No active school context for this account.</p></div>
  @else
    @php
      $ws = $workspace ?? [];
      $primary = $ws['primary'] ?? 'none';
      $roleKeys = $ws['roleKeys'] ?? [];
    @endphp
    <div class="page-header">
      <div class="ws-greet">
        @if(!empty($ws['crest_url']))
          <img class="ws-crest" src="{{ $ws['crest_url'] }}" alt="" width="56" height="56">
        @endif
        <div>
          <p class="page-header__eyebrow">{{ $school->name }}</p>
          <h1 class="page-header__title">{{ $ws['greeting'] ?? $school->name }}</h1>
          <p class="ws-mantra">{{ $ws['mantra'] ?? '' }}</p>
        </div>
      </div>
      <div class="page-header__actions">
        @if($primary === 'homeroom' && !empty($ws['homeroom']))
          <a class="btn accent ws-cta" href="{{ route('app.attendance.index', ['class_id' => $ws['homeroom']['class_id']]) }}">Take register</a>
        @elseif($primary === 'teacher')
          <a class="btn accent ws-cta" href="{{ route('app.teaching.mine') }}">My classes</a>
        @elseif($primary === 'academicLead')
          <a class="btn accent ws-cta" href="{{ route('app.assessment.index') }}">Assessment</a>
        @elseif($primary === 'bursar')
          <a class="btn accent ws-cta" href="{{ route('app.fees.index') }}">Fees desk</a>
        @elseif($primary === 'operationsLead' && in_array('promotions.approve', $permissions, true))
          <a class="btn accent ws-cta" href="{{ route('app.promotions.index') }}">Promotions</a>
        @elseif($primary === 'hygiene')
          <a class="btn accent ws-cta" href="{{ route('app.setup.index') }}">Setup</a>
        @elseif(in_array('learners.view', $permissions, true) || in_array('learners.manage', $permissions, true))
          <a class="btn ghost ws-cta" href="{{ route('app.students.index') }}">View Learners</a>
        @endif
      </div>
    </div>

    @if($primary === 'homeroom' && !empty($ws['homeroom']))
      @include('app.partials.workspace.homeroom', ['workspace' => $ws, 'compact' => false, 'permissions' => $permissions])
    @elseif($primary === 'teacher' && !empty($ws['teacher']))
      @include('app.partials.workspace.teacher', ['workspace' => $ws, 'compact' => false, 'permissions' => $permissions])
    @elseif($primary === 'academicLead' && !empty($ws['academicLead']))
      @include('app.partials.workspace.academic', ['workspace' => $ws, 'compact' => false])
    @elseif($primary === 'bursar' && !empty($ws['bursar']))
      @include('app.partials.workspace.bursar', ['workspace' => $ws, 'compact' => false])
    @elseif($primary === 'operationsLead' && !empty($ws['operationsLead']))
      @include('app.partials.workspace.operations', ['workspace' => $ws, 'compact' => false])
    @elseif($primary === 'governance' && !empty($ws['governance']))
      @include('app.partials.workspace.governance', ['workspace' => $ws, 'compact' => false, 'emis' => $emis ?? $ws['governance']['emis'] ?? null])
    @elseif($primary === 'hygiene' && !empty($ws['hygiene']))
      @include('app.partials.workspace.hygiene', ['workspace' => $ws, 'compact' => false])
    @endif

    @php
      $secondaries = [
        'homeroom' => ['on' => !empty($ws['homeroom']), 'view' => 'homeroom'],
        'teacher' => ['on' => !empty($ws['teacher']), 'view' => 'teacher'],
        'academicLead' => ['on' => !empty($ws['academicLead']), 'view' => 'academic'],
        'bursar' => ['on' => !empty($ws['bursar']), 'view' => 'bursar'],
        'operationsLead' => ['on' => !empty($ws['operationsLead']), 'view' => 'operations'],
        'governance' => ['on' => !empty($ws['governance']), 'view' => 'governance'],
        'hygiene' => ['on' => !empty($ws['hygiene']), 'view' => 'hygiene'],
      ];
    @endphp
    @foreach($secondaries as $key => $meta)
      @if($meta['on'] && $key !== $primary)
        @include('app.partials.workspace.'.$meta['view'], ['workspace' => $ws, 'compact' => true, 'permissions' => $permissions])
      @endif
    @endforeach

    @include('app.partials.workspace.action-queue')

    @if(!empty($showSchoolCharts) && $primary !== 'governance')
      @if(!empty($classChart) || (collect($feeChart ?? [])->sum('amount') > 0))
        <div class="grid g2" style="margin-top:4px">
          @if(!empty($classChart) && in_array('reports.view', $permissions, true))
            <div class="card dash-chart-card">
              <div class="dash-chart-card__head">
                <h3 style="margin:0">Learners by class</h3>
                <span class="pill">Enrollment</span>
              </div>
              <div class="dash-bars" role="img" aria-label="Active students by class">
                @foreach($classChart as $row)
                  <div class="dash-bar">
                    <div class="dash-bar__meta">
                      <span>{{ $row['label'] }}</span>
                      <strong>{{ number_format($row['count']) }}</strong>
                    </div>
                    <div class="dash-bar__track"><span style="width:{{ $row['pct'] }}%"></span></div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
          @if(!empty($feeChart) && in_array('finance.view', $permissions, true))
            <div class="card dash-chart-card">
              <div class="dash-chart-card__head">
                <h3 style="margin:0">Fee collections</h3>
                <span class="pill">Last 6 months</span>
              </div>
              @php
                $maxFee = max(1, ...array_map(fn ($r) => (float) $r['amount'], $feeChart ?: [['amount' => 1]]));
              @endphp
              @if(collect($feeChart)->sum('amount') <= 0)
                <p class="ws-hint">No confirmed fee payments in this window yet.</p>
              @else
                <div class="dash-cols" role="img" aria-label="Confirmed fee collections by month">
                  @foreach($feeChart as $row)
                    <div class="dash-col">
                      <div class="dash-col__bar" style="height:{{ max(4, $row['pct']) }}%" title="UGX {{ number_format($row['amount'], 0) }}"></div>
                      <span class="dash-col__label">{{ $row['label'] }}</span>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          @endif
        </div>
      @endif
    @endif

    @if(!empty($showShortcuts) && !empty($shortcuts))
      <div class="card" style="margin-top:12px">
        <div class="dash-chart-card__head" style="margin-bottom:14px">
          <h3 style="margin:0">Quick access</h3>
        </div>
        <div class="dash-tiles">
          @foreach($shortcuts as $tile)
            <a class="dash-tile" href="{{ $tile['url'] }}">
              <span class="dash-tile__glyph" aria-hidden="true">{{ strtoupper(substr($tile['label'], 0, 1)) }}</span>
              <span class="dash-tile__body">
                <strong>{{ $tile['label'] }}</strong>
                <span>{{ $tile['desc'] }}</span>
              </span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    @if(!empty($permissionLabels) && in_array('school.manage', $permissions, true))
      <details class="card dash-access">
        <summary>
          <strong>Your access</strong>
          <span>{{ count($permissionLabels) }} permissions on this school</span>
        </summary>
        <div class="dash-access__list">
          @foreach($permissionLabels as $label)
            <span class="pill">{{ $label }}</span>
          @endforeach
        </div>
      </details>
    @endif
  @endif
@endsection
