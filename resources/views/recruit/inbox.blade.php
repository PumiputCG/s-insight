@extends('layouts.portal')

@section('title', 'กล่องเอกสาร')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="recruit.kicker">การสรรหา</span>
  <span class="tt-title" data-i18n="nav.rcInbox">กล่องเอกสาร</span>
@endsection

@section('page-style')
    .rc-stub { width: min(100%, 52rem); margin-top: clamp(1rem, 6vh, 4rem); display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem; color: var(--muted-light); }
    .rc-stub-icon { width: 3.4rem; height: 3.4rem; opacity: .55; fill: none; stroke: currentColor; stroke-width: 1.4; stroke-linecap: round; stroke-linejoin: round; }
    .rc-stub h1 { font-family: var(--font-display); font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 400; color: var(--light-text); }
    .rc-stub p { max-width: 30rem; font-size: .9rem; line-height: 1.6; }
    .rc-wip-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .9rem; border: 1px solid var(--line-strong); border-radius: 999px; font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; color: var(--moss); }
@endsection

@section('content')
  <div class="rc-stub">
    <svg class="rc-stub-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"></path><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
    <h1 data-i18n="nav.rcInbox">กล่องเอกสาร</h1>
    <span class="rc-wip-badge" data-i18n="rc.wip">อยู่ระหว่างพัฒนา</span>
    <p data-i18n="rc.stubNote">ส่วนนี้กำลังพัฒนา จะเปิดใช้งานเร็ว ๆ นี้</p>
  </div>
@endsection
