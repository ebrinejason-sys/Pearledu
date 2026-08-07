@extends('layouts.marketing')
@section('content')
  {{-- Scroll-avatar experiment: set false to roll back to hero-only avatar --}}
  @php($vxScrollAvatar = true)

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

  @if($vxScrollAvatar)
    @include('landing.partials.scroll-avatar')
  @endif
@endsection
