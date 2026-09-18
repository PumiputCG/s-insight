@extends('layouts.portal')

@section('title', $panel['context_area'].' · ตรวจประเมิน')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.myWork.reviewTitle">ตรวจประเมิน</span>
@endsection

@section('content')
  @include('area5s.partials.history-panel', ['panel' => $panel])
@endsection
