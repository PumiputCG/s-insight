@extends('layouts.portal')

@section('title', 'แผนกภายใต้สังกัด')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="recruit.kicker">การสรรหา</span>
  <span class="tt-title" data-i18n="nav.rcDepts">แผนกภายใต้สังกัด</span>
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
    <svg class="rc-stub-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
    <h1 data-i18n="nav.rcDepts">แผนกภายใต้สังกัด</h1>
    <span class="rc-wip-badge" data-i18n="rc.wip">อยู่ระหว่างพัฒนา</span>
    <p data-i18n="rc.stubNote">ส่วนนี้กำลังพัฒนา จะเปิดใช้งานเร็ว ๆ นี้</p>
  </div>
@endsection
