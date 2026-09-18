@extends('layouts.portal')

@section('title', $panel['context_area'].' — SUPAVUT 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.area.title">พื้นที่ย่อย</span>
@endsection

@section('content')
  @include('area5s.partials.history-panel', ['panel' => $panel])
@endsection
