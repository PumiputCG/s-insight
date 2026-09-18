@extends('layouts.portal')

@section('title', 'ยังไม่มีการเปิดรอบ · 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="nav.area5s">พื้นที่ 5ส</span>
@endsection

@section('page-style')
    .a5x-wrap { display:grid; place-items:center; min-height:min(62vh, 34rem); }
    .a5x-box { display:grid; gap:.9rem; place-items:center; width:min(100%, 34rem); padding:2.6rem 1.6rem; border:1px dashed var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); text-align:center; }
    .a5x-icon { width:3.4rem; height:3.4rem; display:grid; place-items:center; border-radius:50%; background:rgb(200 150 74 / 14%); }
    .a5x-icon svg { width:1.8rem; height:1.8rem; fill:none; stroke:#c8964a; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .a5x-box h2 { margin:0; color:var(--light-text); font-size:1.02rem; line-height:1.6; }
    .a5x-box p { margin:0; color:var(--muted-light); font-size:.84rem; }
    .a5x-actions { display:flex; gap:.55rem; flex-wrap:wrap; justify-content:center; margin-top:.3rem; }
    .a5x-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:2.3rem; padding:.5rem 1.1rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); font-size:.8rem; font-weight:750; text-decoration:none; }
    .a5x-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5x-btn.primary { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5x-btn.primary:hover { color:#fff; filter:brightness(1.06); }
@endsection

@section('content')
  <div class="a5x-wrap">
    <div class="a5x-box">
      <span class="a5x-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 10h18"></path><path d="m9.5 15.5 5-0.01M12 13v5" opacity="0"></path></svg>
      </span>
      <h2 data-i18n="a5s.noRound.title">ยังไม่มีการเปิดรอบเดือน 5ส — บันทึกและส่งตรวจได้เมื่อเปิดรอบ</h2>
      <p data-i18n="a5s.noRound.help">ระบบจะกลับมาใช้งานได้ทันทีเมื่อแอดมินเปิดรอบเดือน</p>
      <div class="a5x-actions">
        @if ($isAdmin ?? false)
          <a class="a5x-btn primary nav-go" href="{{ route('area5s.rounds.index') }}" data-i18n="a5s.noRound.open">ไปเปิดรอบ</a>
        @endif
        <a class="a5x-btn nav-go" href="{{ route('systems.index') }}" data-i18n="a5s.noRound.backSystems">กลับสู่ระบบทั้งหมด</a>
      </div>
    </div>
  </div>
@endsection
