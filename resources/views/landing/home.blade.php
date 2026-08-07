@extends('layouts.marketing')
@section('content')
  {{-- Hero-only avatar (scroll-guide rolled back) --}}
  @php($vxScrollAvatar = false)

  @include('landing.partials.hero', ['scrollAvatar' => $vxScrollAvatar])
  @include('landing.partials.partners')
  @include('landing.partials.divisions')
  @include('landing.partials.pearledu')
  @include('landing.partials.accessibility')
  @include('landing.partials.avatar-demo', ['scrollAvatar' => $vxScrollAvatar])
  @include('landing.partials.how-it-works')
  @include('landing.partials.features')
  @include('landing.partials.team')
  @include('landing.partials.testimonials')
  @include('landing.partials.roadmap')
  @include('landing.partials.contact')
@endsection
