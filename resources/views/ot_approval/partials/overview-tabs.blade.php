@php
  $overviewDate = isset($selectedDate) && $selectedDate ? $selectedDate->format('Y-m-d') : now()->format('Y-m-d');
  /* ฝั่ง OT ดูวันข้างหน้าไม่ได้ (เวลาสแกนยังไม่เกิด) แต่ฝั่งการลาเปิดล่วงหน้าได้
     ถ้าส่งวันอนาคตข้ามไปจะกลับมาไม่ได้ จึงหนีบไว้ที่วันนี้ตั้งแต่ตัวลิงก์ */
  $otTabDate = min($overviewDate, now()->format('Y-m-d'));
@endphp
<nav class="workflow-overview-tabs" aria-label="หมวดภาพรวม">
  <a href="{{ route('ot-approval.home', ['date' => $otTabDate]) }}"
     class="{{ request()->routeIs('ot-approval.home', 'ot-approval.index') ? 'is-active' : '' }}">
    {{-- ไอคอนแท็บ OT ใช้โลโก้ Approval (นาฬิกา + เครื่องหมายถูก) ตามที่ Manager ส่งมา 2026-08-17 --}}
    <img src="{{ asset('assets/systems/ot-approval.png') }}?v=2" alt="" width="18" height="18" loading="lazy">
    <span data-i18n="ot.overview.otTab">ภาพรวม OT</span>
  </a>
  <a href="{{ route('ot-approval.leave-overview', ['date' => $overviewDate]) }}"
     class="{{ request()->routeIs('ot-approval.leave-overview*') ? 'is-active' : '' }}">
    <img src="{{ asset('assets/systems/leave-notification.png') }}?v=1" alt="" width="18" height="18" loading="lazy">
    <span data-i18n="ot.overview.leaveTab">ภาพรวมการลา</span>
  </a>
</nav>

@once
  <style>
    .workflow-overview-tabs {
      display: inline-flex;
      gap: .3rem;
      margin-bottom: 1rem;
      padding: .25rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel-soft);
    }
    .workflow-overview-tabs a {
      display: inline-flex;
      align-items: center;
      gap: .42rem;
      min-height: 2.2rem;
      padding: .45rem .75rem;
      border-radius: 4px;
      color: var(--muted-light);
      font-size: .76rem;
      font-weight: 700;
      text-decoration: none;
    }
    .workflow-overview-tabs a:hover { color: var(--light-text); }
    .workflow-overview-tabs a.is-active { background: var(--moss); color: #fff; box-shadow: 0 3px 10px rgb(0 0 0 / 8%); }
    /* ไอคอนแอปเป็นภาพจริง — แท็บที่ยังไม่เลือกหรี่ลงเพื่อไม่ให้แย่งสายตากับแท็บที่กำลังใช้ */
    .workflow-overview-tabs img { width: 1.1rem; height: 1.1rem; flex: 0 0 auto; object-fit: contain; opacity: .65; transition: opacity .16s ease; }
    .workflow-overview-tabs a:hover img,
    .workflow-overview-tabs a.is-active img { opacity: 1; }
  </style>
@endonce
