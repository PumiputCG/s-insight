@extends('layouts.portal')

@section('title', $panel['context_area'].' · งานพื้นที่ของฉัน')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.myWork.title">งานพื้นที่ของฉัน</span>
@endsection

@section('content')
  @include('area5s.partials.history-panel', ['panel' => $panel])
@endsection
