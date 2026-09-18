@extends('layouts.portal')

@section('title', 'รายละเอียดการทำงาน')

@section('topbar-title')
  <span class="tt-kicker">TIME &amp; LEAVE</span>
  <span class="tt-title" data-i18n="emp.title">รายละเอียดการทำงาน</span>
@endsection

@section('page-style')
  /* บัญชีที่ไม่ได้ผูกกับพนักงาน (เช่น admin ของระบบ) ไม่มีประวัติ OT/ลาให้แสดง
     เดิมหน้านี้เด้งออกไป /systems ซึ่งดูเหมือนระบบพัง จึงเปลี่ยนเป็นสถานะว่างที่บอกเหตุผลชัด */
  .we-page { width:min(100%, 46rem); margin:0 auto; padding:2.5rem 0; }
  .we-card {
    display:grid; justify-items:center; gap:.55rem;
    padding:2.6rem 1.6rem; border:1px dashed var(--line-light); border-radius: 6px;
    background:var(--panel); text-align:center;
  }
  .we-mark {
    width:3.6rem; height:3.6rem; margin-bottom:.35rem;
    display:grid; place-items:center; border-radius:50%;
    background:var(--hover-soft); color:var(--muted-light);
  }
  .we-mark svg { width:1.6rem; fill:none; stroke:currentColor; stroke-width:1.6; stroke-linecap:round; stroke-linejoin:round; }
  .we-card h1 { font-size:1.15rem; font-weight:650; }
  .we-card p { max-width:28rem; color:var(--muted-light); font-size:.82rem; line-height:1.7; }
  .we-code { margin-top:.3rem; color:var(--muted-light); font-size:.72rem; font-variant-numeric:tabular-nums; }
@endsection

@section('content')
  <main class="we-page">
    <section class="we-card">
      <span class="we-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M5 3v4M19 3v4M3 9h18M5 5h14a2 2 0 0 1 2 2v13H3V7a2 2 0 0 1 2-2Z"></path><path d="m9 14 6 4M15 14l-6 4"></path></svg>
      </span>
      <h1 data-i18n="emp.emptyTitle">ไม่มีข้อมูลสำหรับ admin</h1>
      <p data-i18n="emp.emptyBody">บัญชีนี้เป็นบัญชีผู้ดูแลระบบ ไม่ได้ผูกกับข้อมูลพนักงาน จึงไม่มีประวัติ OT และการลาให้แสดง — ดูข้อมูลรายคนได้จากชื่อพนักงานในหน้าภาพรวม</p>
      @isset($me)
        <span class="we-code">{{ $me->employee_code }}</span>
      @endisset
    </section>
  </main>
@endsection
