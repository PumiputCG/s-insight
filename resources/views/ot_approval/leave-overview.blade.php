@extends('layouts.portal')

@section('title', 'ภาพรวมการลา')
@section('topbar-title')<span data-i18n="leave.overview.title">ภาพรวมการลา</span>@endsection

@section('content')
  @php
    $totals = $leaveSummary['totals'] ?? [];
    $cycleKey = \App\Support\OtApproval\PayrollCycle::containing($selectedDate)['key'];
  @endphp

  <style>
    .main { min-width:0; }
    .leave-page { width:min(100%,92rem); margin:0 auto; }
    .leave-head { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:end; margin-bottom:1rem; }
    .leave-kicker { color:var(--moss); font-size:.7rem; font-weight:800; letter-spacing:.13em; }
    .leave-head h1 { margin:.2rem 0; font-size:clamp(1.35rem,2.4vw,2rem); font-weight:600; }
    .leave-head p { margin:0; color:var(--muted-light); font-size:.84rem; }
    .leave-head-side { display:grid; justify-items:end; gap:.6rem; }
    .leave-tools { display:flex; justify-content:flex-end; align-items:center; gap:.45rem; flex-wrap:wrap; }
    /* แถบสรุปตัวเลข — ใช้สเปกเดียวกับ .ot-summary-strip ของภาพรวม OT */
    .leave-summary-strip { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:.5rem .9rem; width:100%; }
    .leave-summary-item { min-width:max-content; padding:0 .9rem 0 0; border-right:1px solid var(--line-light); }
    .leave-summary-item:last-child { border-right:0; }
    .leave-summary-item span { display:block; color:var(--muted-light); font-size:.63rem; line-height:1.2; white-space:nowrap; }
    .leave-summary-item strong { display:block; margin-top:.08rem; font-size:.92rem; font-weight:600; line-height:1.1; }
    @media (max-width:900px) {
      .leave-head-side { justify-items:start; }
      .leave-summary-strip { justify-content:flex-start; }
    }
    .leave-company { margin-bottom:.75rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel); overflow:hidden; }
    /* โทนพาสเทลชุดเดียวกับภาพรวม OT (token กลางจาก layouts/portal) เพื่อไล่ชั้น บริษัท → แผนก */
    .leave-company > summary { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.1rem; background:var(--ot-tint-company); cursor:pointer; list-style:none; transition:background .16s ease; }
    .leave-company > summary:hover { background:color-mix(in srgb, var(--moss) 12%, var(--panel)); }
    .leave-company > summary::-webkit-details-marker { display:none; }
    .leave-company > summary strong,.leave-company > summary small { display:block; }
    .leave-company > summary small { margin-top:.2rem; color:var(--muted-light); }
    .leave-department[hidden], .leave-company[hidden] { display:none; }
    .leave-departments { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; padding:.75rem 1rem 1rem; }
    .leave-department {
      padding:.9rem; border:1px solid var(--line-light); border-radius: 5px;
      background:var(--ot-tint-card); text-align:left; cursor:pointer; color:var(--light-text);
      transition:transform .16s ease, border-color .16s ease, background .16s ease;
    }
    .leave-department:hover {
      transform:translateY(-2px);
      border-color:color-mix(in srgb, var(--moss) 45%, var(--line-light));
      background:var(--ot-tint-card-hover);
    }
    .leave-department strong { display:block; overflow:hidden; font-size:.88rem; text-overflow:ellipsis; white-space:nowrap; }
    /* ขีดเน้นใต้ชื่อแผนกให้ตรงกับหน้าภาพรวม OT */
    .leave-department strong::after { content:''; display:block; width:70%; height:3px; margin-top:.4rem; border-radius:999px; background:var(--moss); opacity:.85; transition:width .18s ease; }
    .leave-department:hover strong::after { width:100%; }
    @media (prefers-reduced-motion: reduce) { .leave-department, .leave-department strong::after { transition:none; } }
    .leave-department i { font-style:normal; }

    /* กระดานตัวเลขชุดเดียวกับหน้าภาพรวม OT และหน้าขอลา หัวคอลัมน์บอกครั้งเดียว
       แล้วไล่ลงเป็นแถว ทั้งแผนก → กะเวลา A → กะเวลา B ตัวเลขจึงตรงคอลัมน์กันทุกแถว */
    .lo-board { display:grid; gap:.12rem; margin-top:.6rem; }
    .lo-board-head,
    .lo-board-row { display:grid; grid-template-columns:minmax(0,1fr) 2.6rem 3.5rem; align-items:center; gap:.3rem; }
    .lo-board-head { padding:0 .1rem .18rem; color:var(--muted-light); font-size:.61rem; font-weight:700; letter-spacing:.03em; }
    .lo-board-head span + span { text-align:right; }
    .lo-board-row.is-total { padding:.1rem .1rem .38rem; border-bottom:1px solid var(--line-light); margin-bottom:.18rem; }
    .lo-board-row.is-total .lo-board-label { color:var(--muted-light); font-size:.68rem; font-weight:650; }
    .lo-board-row.is-total .lo-board-metric b { font-size:1rem; }
    /* การ์ดแผนกไม่มีไอคอนดวงอาทิตย์/ดวงจันทร์แล้ว (Manager สั่ง) เหลือเฉพาะชื่อกะกับตัวเลข */
    .lo-board-label { display:inline-flex; align-items:center; gap:.3rem; min-width:0; overflow:hidden; color:var(--light-text); font-size:.72rem; text-overflow:ellipsis; white-space:nowrap; }
    .lo-board-metric { display:inline-flex; align-items:baseline; justify-content:flex-end; gap:.04rem; font-variant-numeric:tabular-nums; }
    .lo-board-metric b { color:var(--light-text); font-size:.84rem; font-weight:700; line-height:1.1; }
    .lo-board-metric i { color:var(--muted-light); font-size:.68rem; }
    /* คอลัมน์ลำดับกึ่งกลาง และสีพื้นกะชุดเดียวกับหน้าภาพรวม OT (ดู Design Pattern ใน AI_HANDOFF)
       ความกว้างย้ายไปอยู่ที่ <colgroup> แล้ว ที่นี่เหลือเฉพาะเรื่องสี/การจัดวาง */
    .leave-table .lo-order-col, .leave-table td.is-order { color:var(--muted-light); font-variant-numeric:tabular-nums; text-align:center; }
    .leave-table td.is-shift-morning { background:color-mix(in srgb, #e8830c 30%, transparent); }
    .leave-table td.is-shift-night { background:color-mix(in srgb, #6d28d9 26%, transparent); }

    /* โมดัลสรุปตัวเลข — สเปกเดียวกับหน้าภาพรวม OT (ดู Design Pattern ใน AI_HANDOFF) */
    .ot-summary-open { width:2.4rem; height:2.4rem; flex:0 0 auto; display:grid; place-items:center; border:1px solid var(--line-strong); border-radius:.3rem; background:var(--panel); color:var(--muted-light); cursor:var(--cursor-action); }
    .ot-summary-open:hover { border-color:var(--moss); background:var(--hover-soft); color:var(--moss); }
    .ot-summary-open svg { width:1.15rem; height:1.15rem; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .ot-summary-modal { position:fixed; inset:0; z-index:1200; display:grid; place-items:center; padding:1rem; background:var(--overlay-bg); }
    .ot-summary-modal[hidden] { display:none; }
    .ot-summary-dialog { width:min(28rem,100%); border:1px solid var(--line-light); border-radius:.4rem; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 22%); }
    .ot-summary-dialog-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 1.1rem; border-bottom:1px solid var(--line-light); }
    .ot-summary-close { width:2.1rem; height:2.1rem; border:1px solid var(--line-light); border-radius:.3rem; background:transparent; color:inherit; cursor:var(--cursor-action); font-size:1.1rem; line-height:1; }
    /* ทั้งบล็อกอยู่กึ่งกลางการ์ด · แต่ละบรรทัดเป็น ป้ายซ้าย–ตัวเลขขวา */
    .ot-summary-dialog .leave-summary-strip { display:grid; gap:0; width:min(22rem,100%); margin:0 auto; padding:1.2rem 1.1rem; justify-content:stretch; }
    .ot-summary-dialog .leave-summary-item { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:baseline; gap:1rem; min-width:0; padding:.38rem .2rem; border-right:0; border-bottom:1px dashed var(--line-light); }
    .ot-summary-dialog .leave-summary-item:last-child { border-bottom:0; }
    .ot-summary-dialog .leave-summary-item span { display:block; }
    .ot-summary-dialog .leave-summary-item strong { margin-top:0; text-align:right; }

    .ot-dept-filter, .ot-sort-filter { position:relative; display:inline-flex; align-items:center; flex:0 0 auto; }
    .ot-dept-filter svg, .ot-sort-filter svg { position:absolute; left:.55rem; width:.95rem; height:.95rem; color:var(--muted-light); fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; pointer-events:none; }
    .ot-dept-filter select, .ot-sort-filter select { min-height:2.4rem; padding:.4rem 1.7rem .4rem 1.95rem; border:1px solid var(--line-strong); border-radius:.3rem; background:var(--panel); color:var(--light-text); font-family:inherit; font-size:.74rem; font-weight:650; cursor:var(--cursor-action); appearance:none; text-overflow:ellipsis; }
    .ot-dept-filter select { width:9.5rem; }
    .ot-dept-filter::after, .ot-sort-filter::after { content:''; position:absolute; right:.6rem; width:.4rem; height:.4rem; border-right:1.6px solid var(--muted-light); border-bottom:1.6px solid var(--muted-light); transform:translateY(-.12rem) rotate(45deg); pointer-events:none; }
    /* ตัวกรองรายคอลัมน์แบบ Excel — ไอคอนมุมขวาล่างไม่มีกรอบ */
    /* หัวคอลัมน์เป็น sticky อยู่แล้ว (ดู `.leave-table th` ด้านล่าง) ซึ่งเป็น positioned element
       เมนูกรองที่ absolute จึงเกาะได้ถูกต้อง · ห้ามทับด้วย position:relative ไม่งั้นคอลัมน์ที่กรองได้
       จะหลุด sticky เลื่อนตารางแล้วหัวหายไปเฉพาะคอลัมน์นั้น */
    .ot-col-filter { position:absolute; right:.2rem; bottom:.1rem; width:1.1rem; height:1.1rem; display:grid; place-items:center; border:0; background:transparent; color:var(--muted-light); cursor:var(--cursor-action); }
    .ot-col-filter svg { width:.78rem; height:.78rem; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linejoin:round; }
    .ot-col-filter:hover { color:var(--light-text); }
    .ot-col-filter.is-active { color:var(--moss); }
    .ot-col-menu { position:absolute; z-index:6; top:calc(100% + .2rem); right:0; min-width:11rem; max-height:16rem; overflow-y:auto; display:grid; gap:.1rem; padding:.4rem; border:1px solid var(--line-strong); border-radius:.3rem; background:var(--panel); box-shadow:0 .7rem 1.6rem rgb(0 0 0 / 14%); text-align:left; font-weight:500; }
    .ot-col-menu[hidden] { display:none; }
    .ot-col-menu label { display:flex; align-items:center; gap:.45rem; padding:.3rem .4rem; border-radius:.2rem; cursor:var(--cursor-action); font-size:.74rem; }
    .ot-col-menu label:hover { background:var(--hover-soft); }
    .ot-col-menu input { accent-color:var(--moss); }
    .ot-col-actions { display:grid; grid-template-columns:1fr auto; gap:.4rem; align-items:center; margin-top:.3rem; padding-top:.35rem; border-top:1px solid var(--line-light); }
    .ot-col-clear { padding:.3rem; border:0; background:transparent; color:var(--muted-light); cursor:var(--cursor-action); font-family:inherit; font-size:.72rem; font-weight:600; text-align:left; }
    .ot-col-apply { padding:.32rem .8rem; border:1px solid var(--moss); border-radius:.25rem; background:var(--moss); color:#fff; cursor:var(--cursor-action); font-family:inherit; font-size:.72rem; font-weight:700; }

    .leave-modal { position:fixed; inset:0; z-index:1100; display:grid; place-items:center; padding:.5rem; background:rgb(8 12 10 / 56%); backdrop-filter:blur(5px); }
    .leave-modal[hidden] { display:none; }
    /* โมดัลเป็น flex column: หัวเรื่องกับสรุปอยู่กับที่ ให้เฉพาะตารางเลื่อน
       เดิมทั้ง .leave-dialog และ .leave-table-wrap ต่าง overflow:auto ซ้อนกัน
       หัวตาราง sticky จึงเกาะผิดตัวจนแถวแรกลอยไปอยู่เหนือหัวคอลัมน์ */
    /* กางเต็มจอเหมือนโมดัลหน้าภาพรวม OT — ตารางกว้างขึ้นหลังเพิ่มคอลัมน์ ไม่ควรต้องเลื่อน */
    .leave-dialog { width:100%; max-height:96vh; display:flex; flex-direction:column; overflow:hidden; border:1px solid var(--line-light); border-radius: 6px; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 22%); }
    .leave-dialog-head { flex:0 0 auto; display:flex; justify-content:space-between; align-items:center; padding:1rem 1.1rem; border-bottom:1px solid var(--line-light); background:var(--panel); }
    .leave-dialog-head h2 { margin:0; font-size:1rem; }
    /* ตัวกรองกะอยู่ข้างปุ่มปิด เหมือน .ot-modal-tools ของ modal รายชื่อในภาพรวม OT */
    /* ตัวกรองทั้งหมดอยู่แถวเดียวกัน ไม่ห่อบรรทัด (ช่องค้นหาหดได้เมื่อจอแคบ) */
    .leave-dialog-tools { display:flex; align-items:center; gap:.5rem; flex-wrap:nowrap; }
    .leave-dialog-tools .ot-emp-search { flex:0 1 auto; min-width:0; }
    /* ช่องเลือกวันในหัวโมดัล — ทรงเดียวกับ .ot-modal-date ของหน้าภาพรวม OT */
    .lo-modal-date {
      min-height:2.7rem; display:inline-flex; align-items:center; gap:.45rem; flex:0 0 auto;
      padding:0 .7rem; border:1px solid var(--line-strong); border-radius:4px; background:var(--panel-soft);
    }
    .lo-modal-date:focus-within { border-color:var(--moss); }
    .lo-modal-date svg { width:1rem; flex:0 0 auto; fill:none; stroke:currentColor; stroke-width:1.8; opacity:.7; }
    .lo-modal-date input { border:0; outline:0; background:transparent; color:var(--light-text); font:inherit; font-size:.78rem; font-weight:650; color-scheme:light dark; }
    .leave-close { width:2.2rem; height:2.2rem; flex:0 0 auto; border:1px solid var(--line-light); border-radius:50%; background:transparent; color:inherit; cursor:pointer; }
    /* ── แถบเลือกวัน: ถอดแบบ .ot-date-panel ของหน้าภาพรวม OT ── */
    .leave-date-panel { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; margin-bottom:1rem; }
    .leave-date-picker { position:relative; display:flex; align-items:center; gap:.45rem; flex:0 0 auto; color:var(--muted-light); }
    .leave-date-picker > span:not(.sr-only) { font-size:.74rem; font-weight:600; }
    .leave-date-picker svg { width:1.05rem; fill:none; stroke:currentColor; stroke-width:1.7; }
    .leave-date-picker input {
      width:8.9rem; padding:.45rem .55rem; border:1px solid var(--line-light); border-radius: 4px;
      background:var(--panel-soft); color:var(--light-text); color-scheme:light dark;
    }
    .leave-date-picker input::-webkit-inner-spin-button,
    .leave-date-picker input::-webkit-outer-spin-button { -webkit-appearance:none; appearance:none; margin:0; }
    @media (max-width:640px) {
      .leave-date-picker { justify-content:space-between; width:100%; }
      .leave-date-picker input { width:min(12rem,70vw); }
    }

    /* "ไม่มีคำขอ" เป็นข้อความล้วน ไม่ใส่กรอบ (Manager สั่ง) จะได้ไม่แย่งสายตากับสถานะจริง */
    .leave-badge.is-idle { min-width:auto; min-height:auto; padding:0; border:0; background:transparent; color:var(--muted-light); font-weight:500; }
    .leave-table tr.is-idle td { color:var(--muted-light); }
    .leave-foot { flex:0 0 auto; padding:.75rem 1rem; border-top:1px solid var(--line-light); color:var(--muted-light); font-size:.72rem; text-align:center; }
    .leave-foot b { color:var(--light-text); font-weight:700; font-variant-numeric:tabular-nums; }
    .leave-foot[hidden] { display:none; }
    .leave-table-wrap { flex:1 1 auto; min-height:0; overflow:auto; }
    /* ── ผังคอลัมน์ของตารางรายชื่อ ────────────────────────────────────
       โมดัลกางเต็มจอ ถ้าปล่อยให้เบราว์เซอร์เกลี่ยความกว้างเอง ช่อง "พนักงาน"
       จะดูดที่ว่างที่เหลือไปทั้งหมด จนชื่อกับรูปลอยอยู่กลางช่องโหว่กว้าง ๆ
       จึงตรึงสัดส่วนทุกคอลัมน์ด้วย table-layout:fixed + <colgroup>
       ให้แต่ละช่องกว้างตามปริมาณข้อมูลจริง และคงสัดส่วนเดิมทุกขนาดจอ */
    .leave-table { width:100%; border-collapse:collapse; table-layout:fixed; min-width:70rem; font-size:.74rem; }
    .leave-table col.lo-col-order { width:4%; }
    .leave-table col.lo-col-employee { width:16%; }
    .leave-table col.lo-col-position { width:16%; }
    .leave-table col.lo-col-shift { width:10%; }
    .leave-table col.lo-col-date { width:11%; }
    .leave-table col.lo-col-type { width:13%; }
    .leave-table col.lo-col-status { width:15%; }
    .leave-table col.lo-col-route { width:15%; }
    .leave-table th,.leave-table td { padding:.68rem .7rem; border:1px solid var(--line-light); text-align:center; vertical-align:middle; }
    /* ข้อความยาว (ตำแหน่ง · ประเภทการลา) ตัดลงบรรทัดในช่องตัวเอง ไม่ดันคอลัมน์อื่นให้เบี้ยว */
    .leave-table td.is-wrap { line-height:1.45; overflow-wrap:anywhere; }
    /* ตัวเลื่อนคือ .leave-table-wrap ในโมดัล ไม่ใช่ทั้งหน้า — top ต้องเป็น 0
       ถ้าตั้งเป็นความสูง topbar (4.25rem) หัวตารางจะถูกดันลง แล้วแถวแรกโผล่ขึ้นไปอยู่เหนือหัวคอลัมน์ */
    .leave-table th { position:sticky; top:0; z-index:2; background:var(--panel); color:var(--muted-light); font-size:.68rem; box-shadow:inset 0 -1px 0 var(--line-light); }
    /* รูป-ชื่อ-รหัส เกาะกลุ่มชิดซ้ายของช่อง ชื่อยาวตัดด้วย … แล้วดูเต็มได้จาก tooltip
       (เลิกใช้ min-width:15rem เพราะตอนนี้ความกว้างมาจาก <colgroup> แล้ว) */
    .leave-employee { display:flex; align-items:center; gap:.6rem; min-width:0; text-align:left; }
    .leave-employee img,.leave-avatar { width:2.2rem; height:2.2rem; flex:0 0 auto; border-radius:50%; object-fit:cover; background:#dfe4ea; }
    .leave-employee-copy { min-width:0; flex:1 1 auto; }
    .leave-employee strong,.leave-employee small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .leave-emp-link { color:inherit; text-decoration:none; border-bottom:1px dotted var(--line-strong); }
    .leave-emp-link:hover { color:var(--moss); border-bottom-color:var(--moss); }
    .leave-employee small { color:var(--muted-light); margin-top:.12rem; }
    .leave-badge { display:inline-flex; justify-content:center; align-items:center; min-width:7.2rem; min-height:1.75rem; padding:.25rem .55rem; border-radius: 4px; color:#fff; font-size:.69rem; font-weight:700; }
    .leave-badge.is-warning { background:#e0a51c; border:1px solid #e0a51c; }
    .leave-badge.is-success { background:#35a863; border:1px solid #35a863; }
    .leave-badge.is-danger { background:#da5a4e; border:1px solid #da5a4e; }
    .leave-badge.is-neutral { min-width:0; padding:0; background:transparent; border:0; color:var(--muted-light); }
    .leave-detail { display:block; margin-top:.25rem; color:var(--light-text); font-size:.66rem; }
    .leave-empty { padding:2.5rem; text-align:center; color:var(--muted-light); }
    @media(max-width:900px){ .leave-head{grid-template-columns:1fr}.leave-tools{justify-content:flex-start}.leave-departments{grid-template-columns:1fr} }
  </style>

  <main class="leave-page" data-leave-overview>
    @include('ot_approval.partials.overview-tabs')

    <header class="leave-head">
      <div>
        <h1 data-i18n="leave.overview.title">ภาพรวมการลา</h1>
        <p data-i18n="leave.overview.subtitle">ตรวจสอบคำขอและผลอนุมัติการลาตามวันที่เลือก</p>
      </div>
      <div class="leave-head-side">
        {{-- สรุปตัวเลขเป็นแถบในหัวหน้าเหมือนภาพรวม OT ไม่ใช่การ์ด 5 ใบใต้ปฏิทิน --}}
        {{-- ปุ่มนาฬิกาเปิดสรุปตัวเลขรวม — ย่อแถบยาวให้เหลือไอคอนเดียว (ต้นแบบหน้าภาพรวม OT) --}}
        <button type="button" class="ot-summary-open" data-summary-open aria-haspopup="dialog" data-i18n-aria="ot.attendance.summaryOpen" aria-label="ดูสรุปตัวเลขรวม" title="ดูสรุปตัวเลขรวม">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
        </button>

        <div class="ot-summary-modal" data-summary-modal hidden>
          <div class="ot-summary-dialog" role="dialog" aria-modal="true" aria-label="สรุปตัวเลขรวม">
            <header class="ot-summary-dialog-head">
              <strong data-i18n="ot.attendance.summaryTitle">สรุปตัวเลขรวม</strong>
              <button type="button" class="ot-summary-close" data-summary-close aria-label="ปิด">&times;</button>
            </header>
        <section class="leave-summary-strip" aria-label="สรุปข้อมูลการลา" data-i18n-aria="leave.summary.aria">
          @foreach ([
            ['employees', 'leave.employees', 'พนักงานทั้งหมด'],
            ['requested', 'leave.requested', 'ขอลา'],
            ['pending', 'leave.pending', 'รอดำเนินการ'],
            ['approved', 'leave.approved', 'ผ่าน'],
            ['rejected', 'leave.rejected', 'ไม่อนุมัติ'],
          ] as [$key, $labelKey, $label])
            <div class="leave-summary-item">
              <span data-i18n="{{ $labelKey }}">{{ $label }}</span>
              <strong>{{ number_format($totals[$key] ?? 0) }}</strong>
            </div>
          @endforeach
        </section>
          </div>
        </div>
      </div>
    </header>

    {{-- แถบเลือกวันใช้โครงเดียวกับหน้าภาพรวม OT (`.ot-date-panel`) ให้สองหน้าอ่านเหมือนกัน --}}
    <section class="leave-date-panel" aria-label="เลือกวันที่ดูข้อมูลการลา">
      <label class="leave-date-picker">
        <span data-i18n="leave.selectDate">เลือกวันที่</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
        {{-- ไม่มี max เพราะลา 75 ขอล่วงหน้าได้ ต้องเปิดวันข้างหน้าให้ดู/แก้ร่างได้ --}}
        <input type="date" value="{{ $selectedDate->format('Y-m-d') }}" data-leave-date>
      </label>

      @if ($isOtAdmin ?? false)
        {{-- แอดมินเห็นทุกแผนกจึงต้องมีตัวกรอง · Foreman เห็นแค่แผนกตัวเองอยู่แล้ว --}}
        <label class="ot-dept-filter">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"></path></svg>
          <select data-dept-filter aria-label="กรองตามแผนก">
            <option value="all" data-i18n="ot.attendance.allDepartments">ทุกแผนก</option>
            @foreach (collect($leaveSummary['companies'] ?? [])->flatMap(fn ($c) => $c['departments'] ?? [])->sortBy('name_th') as $dept)
              <option value="{{ $dept['code'] }}">{{ $dept['name_th'] }}</option>
            @endforeach
          </select>
        </label>
      @endif
    </section>

    <section>
      @forelse (($leaveSummary['companies'] ?? []) as $company)
        <details class="leave-company">
          <summary>
            <div><strong>{{ $company['name'] }}</strong><small>{{ number_format($company['total']) }} <span data-i18n="leave.peopleSuffix">คน</span> · {{ count($company['departments']) }} <span data-i18n="leave.departments">แผนก</span></small></div>
            <span>⌄</span>
          </summary>
          <div class="leave-departments">
            @foreach ($company['departments'] as $department)
              @php
                $shifts = $department['shifts'] ?? [];
                $blank = ['total' => 0, 'requested' => 0];
                $morning = ($shifts['morning'] ?? []) + $blank;
                $night = ($shifts['night'] ?? []) + $blank;
              @endphp
              <button class="leave-department" type="button"
                      data-company="{{ $company['code'] }}" data-dept="{{ $department['code'] }}"
                      data-dept-name-th="{{ $department['name_th'] }}" data-dept-name-en="{{ $department['name_en'] }}">
                {{-- ชื่อแผนกเป็นรูปแบบ `Accounting (ACC)` เหมือนกันทุกภาษาแล้ว จึงพิมพ์ครั้งเดียว --}}
                <strong>{{ $department['name_th'] }}</strong>
                <span class="lo-board">
                  <span class="lo-board-head" aria-hidden="true">
                    <span></span>
                    <span data-i18n="leave.peopleSuffix">คน</span>
                    <span data-i18n="leave.requested">ขอลา</span>
                  </span>
                  <span class="lo-board-row is-total">
                    <span class="lo-board-label" data-i18n="ot.attendance.wholeDepartment">ทั้งแผนก</span>
                    <span class="lo-board-metric"><b>{{ number_format($department['total']) }}</b></span>
                    <span class="lo-board-metric"><b>{{ number_format($department['requested']) }}</b><i>/{{ number_format($department['total']) }}</i></span>
                  </span>
                  @if ($morning['total'] > 0)
                    <span class="lo-board-row is-morning">
                      <span class="lo-board-label">
                        <span data-i18n="ot.settings.shift.morning">กะเวลา A</span>
                      </span>
                      <span class="lo-board-metric"><b>{{ number_format($morning['total']) }}</b></span>
                      <span class="lo-board-metric"><b>{{ number_format($morning['requested']) }}</b><i>/{{ number_format($morning['total']) }}</i></span>
                    </span>
                  @endif
                  @if ($night['total'] > 0)
                    <span class="lo-board-row is-night">
                      <span class="lo-board-label">
                        <span data-i18n="ot.settings.shift.night">กะเวลา B</span>
                      </span>
                      <span class="lo-board-metric"><b>{{ number_format($night['total']) }}</b></span>
                      <span class="lo-board-metric"><b>{{ number_format($night['requested']) }}</b><i>/{{ number_format($night['total']) }}</i></span>
                    </span>
                  @endif
                </span>
              </button>
            @endforeach
          </div>
        </details>
      @empty
        <p class="leave-empty" data-i18n="leave.emptyScope">ไม่พบข้อมูลพนักงานในขอบเขตสิทธิ์ของคุณ</p>
      @endforelse
    </section>
  </main>

  <div class="leave-modal" data-leave-modal hidden>
    <section class="leave-dialog" role="dialog" aria-modal="true" aria-labelledby="leaveDeptTitle">
      <header class="leave-dialog-head">
        <h2 id="leaveDeptTitle" data-leave-modal-title data-i18n="leave.employees">รายชื่อพนักงาน</h2>
        {{-- ตัวกรองกะชุดเดียวกับ modal รายชื่อของภาพรวม OT (ทุกกะ/กะเวลา A/กะเวลา B/ไม่มีกะ)
             อ่านกะจาก Snapshot ท้องถิ่นที่ endpoint ส่งมาแล้ว ไม่ได้เรียก Bplus เพิ่ม --}}
        <div class="leave-dialog-tools">
          @include('ot_approval.partials.employee-search')
          {{-- ปฏิทินในโมดัล — เปลี่ยนวันแล้วโหลดรายชื่อแผนกเดิมใหม่ทันทีโดยไม่ปิดโมดัล
               (ปฏิทินบนหน้าหลักโหลดทั้งหน้า โมดัลจึงหลุด) ชุดเดียวกับหน้าภาพรวม OT --}}
          <label class="lo-modal-date">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"></path></svg>
            <span class="sr-only" data-i18n="leave.selectDate">เลือกวันที่</span>
            <input type="date" value="{{ $selectedDate->format('Y-m-d') }}" data-leave-modal-date aria-label="เลือกวันที่">
          </label>
          {{-- เรียงตามตำแหน่ง — ใช้ OtPositionRank ชุดเดียวกับหน้าภาพรวม OT --}}
          <label class="ot-sort-filter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
            <select data-sort-filter aria-label="เรียงลำดับ">
              <option value="code" data-i18n="ot.attendance.sortCode">ตามรหัสพนักงาน</option>
              <option value="rank" data-i18n="ot.attendance.sortRank">ตำแหน่งสูง → ต่ำ</option>
              <option value="rank_asc" data-i18n="ot.attendance.sortRankAsc">ตำแหน่งต่ำ → สูง</option>
            </select>
          </label>
          {{-- ตัวกรองสาขา (ทุกสาขา / สำนักงานใหญ่ / โรงงาน / โรงงาน-พม่า) — ชุดเดียวกับหน้ายื่นขอ OT
               กรองในเครื่องจาก `branch_th` ที่ payload ส่งมาแล้ว ไม่ต้องยิงเซิร์ฟเวอร์ซ้ำ --}}
          @include('ot_approval.partials.branch-filter')
          @include('ot_approval.partials.shift-filter')
          <button class="leave-close" type="button" data-leave-close>×</button>
        </div>
      </header>
      <div class="leave-table-wrap">
        <table class="leave-table">
          {{-- ความกว้างของทุกคอลัมน์กำหนดที่นี่ที่เดียว (ดูเหตุผลในบล็อก CSS `.leave-table`) --}}
          <colgroup>
            <col class="lo-col-order"><col class="lo-col-employee"><col class="lo-col-position"><col class="lo-col-shift">
            <col class="lo-col-date"><col class="lo-col-type"><col class="lo-col-status"><col class="lo-col-route">
          </colgroup>
          {{-- ตัดคอลัมน์ `แผนก` ออก (ทุกแถวเป็นแผนกเดียวกันอยู่แล้ว) และ `เหตุผล` ที่ย้ายไปอยู่ใน
               modal สถานะทั้งหมดแล้ว เหลือ 6 คอลัมน์ที่ต่างกันจริงต่อแถว --}}
          <thead><tr><th class="lo-order-col" data-i18n="common.rowNo">ลำดับ</th><th data-i18n="leave.employee">พนักงาน</th><th data-i18n="leave.position">ตำแหน่ง</th><th class="lo-shift-col" data-i18n="leave.shift">กะงาน</th><th data-i18n="leave.leaveDate">วันที่ลา</th><th data-i18n="leave.leaveType">ประเภทการลา</th><th data-i18n="leave.status">สถานะ</th><th data-i18n="leave.route">สถานะทั้งหมด</th></tr></thead>
          <tbody data-leave-body><tr><td colspan="8" class="leave-empty" data-i18n="leave.loading">กำลังโหลด...</td></tr></tbody>
        </table>
      </div>
      <p class="leave-foot" data-leave-foot hidden></p>
    </section>
  </div>

  @include('ot_approval.partials.photo-zoom')
  @include('ot_approval.partials.leave-process-route')

  <script>
    (function leaveOverview() {
      'use strict';
      var root=document.querySelector('[data-leave-overview]'); if(!root)return;
      var modal=document.querySelector('[data-leave-modal]'); var body=modal.querySelector('[data-leave-body]'); var title=modal.querySelector('[data-leave-modal-title]');
      var selectedDate=@json($selectedDate->format('Y-m-d'));var currentRows=[];
      var endpoint=@json(route('ot-approval.leave-overview.employees'));
      function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[c];});}
      function lang(){return document.documentElement.getAttribute('data-lang')||'th';}
      function text(key,fallback){return window.__portalLang?window.__portalLang.text(key,fallback):fallback;}
      function value(row,key){return row[key+'_'+lang()]||row[key+'_th']||row[key+'_en']||'-';}
      function badge(request){if(!request)return '<span class="leave-badge is-neutral">-</span>';var key='leave.status.'+request.approval_status;var label=text(key,request.status_label);var detail=request.approval_status==='submitted'?text('leave.status.waitingSupervisor',request.status_detail||''):'';if(request.approval_status==='submitted'&&request.can_decide===false){label=text('leave.expired','พ้นกำหนดอนุมัติ');detail='';}return '<span class="leave-badge is-'+esc(request.approval_status==='submitted'&&request.can_decide===false?'danger':request.status_tone)+'">'+esc(label)+'</span>'+(detail?'<small class="leave-detail">'+esc(detail)+'</small>':'');}
      /* asset() คืนโฮสต์ตามค่า APP_URL (localhost) แต่หน้าเว็บอาจเปิดที่ 127.0.0.1:8000
         ถ้าไม่ดึงกลับมาที่ origin ปัจจุบัน รูปจะโหลดไม่ขึ้นทั้งหน้า */
      function localAssetUrl(value){if(!value)return '';try{var url=new URL(value,window.location.href);return ['localhost','127.0.0.1'].indexOf(url.hostname)!==-1?window.location.origin+url.pathname+url.search:url.href;}catch(error){return value;}}
      /* ชื่อพนักงานกดได้ → เปิดแท็บใหม่ดูประวัติ OT + การลาทั้งเดือนของคนนั้น
         ใช้ <a target="_blank"> ไม่ใช่ JS เพื่อให้คลิกกลางปุ่ม/คลิกขวาเปิดแท็บได้ตามปกติ */
      function employeeUrl(row){return @json(url('/ot-approval/employees'))+'/'+encodeURIComponent(row.company)+'/'+encodeURIComponent(row.code)+'?month='+encodeURIComponent(selectedDate.slice(0,7));}
      function employee(row){var avatar=localAssetUrl(row.avatar||'');var name=value(row,'name');return '<div class="leave-employee">'+(avatar?'<img src="'+esc(avatar)+'" alt="'+esc(name)+'" data-leave-photo data-caption-name="'+esc(name)+'" data-caption-code="'+esc(row.code)+'">':'<span class="leave-avatar"></span>')+'<div class="leave-employee-copy"><strong><a class="leave-emp-link" href="'+esc(employeeUrl(row))+'" target="_blank" rel="noopener" title="'+esc(name)+'">'+esc(name)+'</a></strong><small>'+esc(row.code)+'</small></div></div>';}
      /* ── ตัวกรองกะใน modal รายชื่อ ─────────────────────────────────
         ใช้ helper กลางตัวเดียวกับภาพรวม OT · ย้อมพื้นตารางตามกะที่เลือกด้วย */
      var shiftSelect=modal.querySelector('[data-shift-filter]');
      var shiftValue=window.otShiftFilter?window.otShiftFilter.ALL:'all';
      var employeeQuery='';      // ค้นรหัส/ชื่อ กรองในเครื่องจากรายชื่อที่โหลดมาแล้ว
      var sortMode='code';
      var branchValue='all';     // สาขา (สำนักงานใหญ่ / โรงงาน / โรงงาน-พม่า) กรองในเครื่องเช่นกัน
      /* ตัวกรองรายคอลัมน์แบบ Excel — ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะพอจะกรองได้จริง
         (ตำแหน่ง · กะงาน · ประเภทการลา · สถานะ) ไม่ใส่คอลัมน์พนักงาน/วันที่ตามต้นแบบ */
      var columnFilters={};
      var COLUMN_FILTERS=[
        { key:'position', value:function(r){return value(r,'position');} },
        { key:'shift', value:function(r){return r.shift_in&&r.shift_out?String(r.shift_in).slice(0,5)+'–'+String(r.shift_out).slice(0,5):'–';} },
        { key:'leaveType', value:function(r){var q=r.leave_request;return q?(q['leave_type_label_'+lang()]||q.leave_type_label_th):'–';} },
        { key:'status', value:function(r){var q=r.leave_request;return q?(q.status_label||q.approval_status):text('leave.noRequest','ไม่มีคำขอ');} }
      ];
      function columnSpec(key){return COLUMN_FILTERS.filter(function(c){return c.key===key;})[0]||null;}
      function matchesColumnFilters(row){
        return Object.keys(columnFilters).every(function(key){
          var picked=columnFilters[key];
          if(!picked||!picked.size)return true;
          var spec=columnSpec(key);
          return spec?picked.has(String(spec.value(row))):true;
        });
      }

      function visibleRows(){
        var rows=currentRows.filter(function(row){
          var shiftOk=!window.otShiftFilter||window.otShiftFilter.matches(row,shiftValue);
          var branchOk=!window.otBranchFilter||window.otBranchFilter.matches(row,branchValue);
          return shiftOk
            &&branchOk
            &&window.otEmployeeSearch.matches(row,employeeQuery)
            &&matchesColumnFilters(row);
        });
        /* ตำแหน่งเท่ากันเรียงรหัสต่อ ลำดับจึงคงที่ไม่สลับทุกครั้งที่โหลด */
        if(sortMode==='rank'||sortMode==='rank_asc'){
          var dir=sortMode==='rank'?1:-1;
          rows=rows.slice().sort(function(a,b){
            var diff=((a.position_rank||999)-(b.position_rank||999))*dir;
            return diff!==0?diff:String(a.code).localeCompare(String(b.code));
          });
        }
        return rows;
      }

      /* ตัวกรองรายคอลัมน์: ติ๊กแล้วกดตกลงถึงมีผล ไม่วาดใหม่ทุกครั้งที่ติ๊ก
         ไม่งั้นเมนูจะปิดแล้วต้องเปิดใหม่ทีละค่า (บทเรียนจากหน้าภาพรวม OT) */
      function attachColumnFilter(th,filterKey,labelOf){
        try{
          var spec=columnSpec(filterKey);
          if(!spec||!th)return;
          th.classList.add('is-filterable');
          th.querySelectorAll('.ot-col-filter,.ot-col-menu').forEach(function(n){n.remove();});

          var values=[];
          currentRows.forEach(function(r){var v=String(spec.value(r));if(values.indexOf(v)===-1)values.push(v);});
          values.sort();

          var picked=columnFilters[filterKey]||new Set();
          var draft=new Set(picked);
          var btn=document.createElement('button');
          btn.type='button';
          btn.className='ot-col-filter'+(picked.size?' is-active':'');
          btn.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="2"></rect><path d="M8 10.5 12 15l4-4.5"></path></svg>';

          var menu=document.createElement('div');
          menu.className='ot-col-menu';
          menu.hidden=true;
          values.forEach(function(v){
            var label=document.createElement('label');
            var box=document.createElement('input');
            box.type='checkbox';
            box.checked=picked.has(v);
            box.addEventListener('change',function(){if(box.checked)draft.add(v);else draft.delete(v);});
            var span=document.createElement('span');
            span.textContent=labelOf?labelOf(v):v;
            label.append(box,span);
            menu.appendChild(label);
          });

          var actions=document.createElement('div');
          actions.className='ot-col-actions';
          var clear=document.createElement('button');
          clear.type='button';
          clear.className='ot-col-clear';
          clear.textContent=text('ot.attendance.clearFilter','ล้างตัวกรอง');
          clear.addEventListener('click',function(){delete columnFilters[filterKey];renderRows(visibleRows());});
          var apply=document.createElement('button');
          apply.type='button';
          apply.className='ot-col-apply';
          apply.textContent=text('common.confirm','ตกลง');
          apply.addEventListener('click',function(){
            if(draft.size)columnFilters[filterKey]=new Set(draft);else delete columnFilters[filterKey];
            renderRows(visibleRows());
          });
          actions.append(clear,apply);
          menu.appendChild(actions);

          btn.addEventListener('click',function(event){
            event.stopPropagation();
            modal.querySelectorAll('.ot-col-menu').forEach(function(m){if(m!==menu)m.hidden=true;});
            menu.hidden=!menu.hidden;
          });
          menu.addEventListener('click',function(event){event.stopPropagation();});
          th.append(btn,menu);
        }catch(error){console.error('column filter failed:',filterKey,error);}
      }

      function refreshColumnFilters(){
        var head=modal.querySelectorAll('.leave-table thead th');
        attachColumnFilter(head[2],'position');
        attachColumnFilter(head[3],'shift');
        attachColumnFilter(head[5],'leaveType');
        attachColumnFilter(head[6],'status');
      }

      function paintShift(){
        if(!window.otShiftFilter)return;
        // ส่ง null: พื้นโมดัลต้องขาวเสมอ สีบอกกะอยู่ที่คอลัมน์กะงานอย่างเดียว (Manager สั่ง)
        window.otShiftFilter.paint(shiftSelect,null,shiftValue,currentRows);
      }

      var deptFilterSelect=document.querySelector('[data-dept-filter]');
      if(deptFilterSelect){
        deptFilterSelect.addEventListener('change',function(){
          var v=deptFilterSelect.value;
          document.querySelectorAll('.leave-company').forEach(function(company){
            var shown=0;
            company.querySelectorAll('.leave-department').forEach(function(card){
              var match=v==='all'||card.dataset.dept===v;
              card.hidden=!match;
              if(match)shown++;
            });
            company.hidden=shown===0;
            if(shown>0&&v!=='all')company.open=true;
          });
        });
      }

      /* ── สรุปตัวเลขรวม: เปิดจากปุ่มนาฬิกา (ต้นแบบหน้าภาพรวม OT) ── */
      (function(){
        var box=document.querySelector('[data-summary-modal]');
        var open=document.querySelector('[data-summary-open]');
        if(!box||!open)return;
        function close(){box.hidden=true;document.body.style.overflow='';}
        open.addEventListener('click',function(){box.hidden=false;document.body.style.overflow='hidden';});
        box.querySelector('[data-summary-close]').addEventListener('click',close);
        box.addEventListener('click',function(e){if(e.target===box)close();});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!box.hidden)close();});
      })();

      var searchInput=modal.querySelector('[data-emp-search]');
      if(searchInput){
        searchInput.addEventListener('input',window.otEmployeeSearch.debounce(function(){
          employeeQuery=searchInput.value;
          renderRows(visibleRows());
        },250));
      }
      var sortSelect=modal.querySelector('[data-sort-filter]');
      if(sortSelect){
        sortSelect.addEventListener('change',function(){sortMode=sortSelect.value;renderRows(visibleRows());});
      }
      /* ตัวกรองสาขา — เทียบด้วยชื่อสาขา ไม่ใช่รหัส เพราะรหัสซ้ำความหมายข้ามบริษัท (ดู OtBranchFilter) */
      var branchSelect=modal.querySelector('[data-branch-filter]');
      if(branchSelect){
        branchSelect.addEventListener('change',function(){branchValue=branchSelect.value;renderRows(visibleRows());});
      }
      // คลิกที่อื่นปิดเมนูกรอง ไม่ให้ค้างทับตาราง
      modal.addEventListener('click',function(){
        modal.querySelectorAll('.ot-col-menu').forEach(function(m){m.hidden=true;});
      });

      function render(rows){
        currentRows=rows;
        refreshColumnFilters();
        if(window.otShiftFilter&&shiftSelect){
          shiftValue=window.otShiftFilter.build(shiftSelect,rows,shiftValue);
          paintShift();
        }
        renderRows(visibleRows());
      }

      function renderRows(rows){
        if(!rows.length){body.innerHTML='<tr><td colspan="8" class="leave-empty">'+esc(text('leave.emptyDepartment','ไม่พบพนักงานในแผนกนี้'))+'</td></tr>';renderFoot(0,0);return;}

        /* แสดงพนักงานทุกคนในแผนก (modal นี้ชื่อ "รายชื่อพนักงาน") โดยคนที่ยื่นลาขึ้นก่อน
           ทุกแถวมี 6 ช่องเท่ากันเสมอ ไม่ใช้ colspan อีก เพราะเป็นต้นเหตุที่คอลัมน์เบี้ยว
           คนที่ไม่ได้ลาจะได้ป้ายจาง ๆ ว่าไม่มีคำขอ ไม่ใช่แถวขีดว่าง */
        var sorted=rows.slice().sort(function(a,b){return (b.leave_request?1:0)-(a.leave_request?1:0);});
        var onLeave=sorted.filter(function(row){return row.leave_request;}).length;

        body.replaceChildren();
        sorted.forEach(function(row,index){
          var request=row.leave_request;
          if(request)request.avatar=row.avatar;
          var tr=document.createElement('tr');
          if(!request)tr.className='is-idle';

          /* กะงานใช้สีพื้นตามกลุ่มเดียวกับหน้าภาพรวม OT (กะเวลา A ส้ม · กะเวลา B ม่วง) */
          var shiftGroup=(window.otShiftFilter?window.otShiftFilter.groupOf('s:'+(row.shift_code||''),[row]):'');
          var shiftText=row.shift_in&&row.shift_out?String(row.shift_in).slice(0,5)+'–'+String(row.shift_out).slice(0,5):'–';
          var shiftClass=(shiftGroup==='morning'||shiftGroup==='night')?' class="is-shift-'+shiftGroup+'"':'';

          tr.innerHTML='<td class="is-order">'+(index+1)+'</td>'
            +'<td>'+employee(row)+'</td>'
            +'<td class="is-wrap" title="'+esc(value(row,'position'))+'">'+esc(value(row,'position'))+'</td>'
            +'<td'+shiftClass+'>'+esc(shiftText)+'</td>'
            +'<td>'+(request?esc(request.leave_date_label):'–')+'</td>'
            +'<td class="is-wrap">'+(request?esc(request['leave_type_label_'+lang()]||request.leave_type_label_th):'–')+'</td>'
            +'<td>'+(request?badge(request):'<span class="leave-badge is-idle">'+esc(text('leave.noRequest','ไม่มีคำขอ'))+'</span>')+'</td>'
            +'<td></td>';

          /* ปุ่มสถานะต้องวางเป็น element จริง ถ้าต่อเป็น string ผ่าน innerHTML
             event listener ที่ create() ผูกไว้จะหลุด กดแล้วไม่มีอะไรเกิดขึ้น */
          if(request&&window.leaveProcessRoute){
            tr.lastElementChild.appendChild(window.leaveProcessRoute.create(request));
          } else {
            tr.lastElementChild.textContent='–';
          }
          body.appendChild(tr);
        });

        renderFoot(onLeave,rows.length);
      }

      if(shiftSelect){
        shiftSelect.addEventListener('change',function(){
          shiftValue=shiftSelect.value;
          paintShift();
          renderRows(visibleRows());
        });
      }

      /* สรุปใต้ตาราง: ยื่นลากี่คนจากทั้งแผนก อ่านจบในบรรทัดเดียวโดยไม่ต้องนับแถวเอง */
      function renderFoot(onLeave,total){
        var foot=modal.querySelector('[data-leave-foot]');
        if(!foot)return;
        if(!total){foot.hidden=true;return;}
        foot.replaceChildren(
          document.createTextNode(text('leave.requested','ขอลา')+' '),
          Object.assign(document.createElement('b'),{textContent:onLeave}),
          document.createTextNode(' / '+total+' '+text('leave.peopleSuffix','คน'))
        );
        foot.hidden=false;
      }
      /* จำแผนกที่เปิดอยู่ไว้ เพื่อโหลดรายชื่อชุดใหม่ได้เมื่อเปลี่ยนวันจากในโมดัล
         โดยไม่ต้องปิดแล้วกดการ์ดแผนกเดิมซ้ำ */
      var activeDept=null;
      var initialDate=selectedDate;
      async function loadDepartment(){
        if(!activeDept)return;
        body.innerHTML='<tr><td colspan="8" class="leave-empty">'+esc(text('leave.loading','กำลังโหลด...'))+'</td></tr>';
        try{var url=endpoint+'?company='+encodeURIComponent(activeDept.company)+'&dept_code='+encodeURIComponent(activeDept.dept)+'&date='+encodeURIComponent(selectedDate);var response=await fetch(url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});var data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||'failed');render(data.leave.employees||[]);}catch(error){body.innerHTML='<tr><td colspan="8" class="leave-empty">'+esc(text('leave.loadError','ไม่สามารถโหลดข้อมูลได้'))+'</td></tr>';}
      }
      function openDepartment(button){
        activeDept={company:button.dataset.company,dept:button.dataset.dept,nameTh:button.dataset.deptNameTh,nameEn:button.dataset.deptNameEn};
        modal.hidden=false;document.body.style.overflow='hidden';
        title.removeAttribute('data-i18n');
        title.textContent=(lang()==='th'?activeDept.nameTh:activeDept.nameEn)||text('leave.employees','รายชื่อพนักงาน');
        if(modalDate)modalDate.value=selectedDate;
        loadDepartment();
      }

      /* เปลี่ยนวันจากในโมดัล: โหลดเฉพาะตาราง แล้วแก้ช่องวันที่ข้างนอกกับ URL ให้ตรงกัน
         การ์ดแผนกด้านหลังเป็นข้อมูลฝั่ง server จึงยังเป็นยอดของวันเดิม — ค่อยโหลดหน้าใหม่ตอนปิดโมดัล */
      var modalDate=modal.querySelector('[data-leave-modal-date]');
      if(modalDate){
        modalDate.addEventListener('change',function(){
          if(!modalDate.value)return;
          selectedDate=modalDate.value;
          syncPageDate(selectedDate);
          loadDepartment();
        });
      }
      function syncPageDate(date){
        var picker=root.querySelector('[data-leave-date]');
        if(picker&&picker.value!==date)picker.value=date;
        try{var url=new URL(window.location.href);url.searchParams.set('date',date);window.history.replaceState({},'',url);}catch(error){/* URL API ใช้ไม่ได้ก็ข้ามไป ไม่ใช่สาระสำคัญ */}
      }
      root.querySelectorAll('.leave-department').forEach(function(button){button.addEventListener('click',function(){openDepartment(button);});});
      root.querySelector('[data-leave-date]').addEventListener('change',function(event){if(event.target.value)window.location.assign(@json(route('ot-approval.leave-overview'))+'?date='+encodeURIComponent(event.target.value));});
      var scope=root.querySelector('[data-leave-export-scope]'),cycle=root.querySelector('[data-leave-export-cycle]');if(scope&&cycle){var sync=function(){cycle.hidden=scope.value!=='cycle';cycle.required=scope.value==='cycle';};scope.addEventListener('change',sync);sync();}
      function localizeExport(){var option=root.querySelector('[data-leave-export-date]');if(option)option.textContent=text('leave.export.date','เอกสารลา (วันที่เลือก)').replace('วันที่เลือก','{{ $selectedDate->format('d/m/Y') }}').replace('selected date','{{ $selectedDate->format('d/m/Y') }}').replace('ရွေးထားသောရက်','{{ $selectedDate->format('d/m/Y') }}');}
      document.addEventListener('insight:languagechange',function(){localizeExport();if(!modal.hidden)render(currentRows);});localizeExport();
      /* ปิดโมดัลหลังเปลี่ยนวันข้างใน = ต้องโหลดหน้าใหม่ ไม่งั้นการ์ดแผนกด้านหลัง
         ยังเป็นยอดของวันเดิม แล้วตัวเลขจะคนละวันกับที่เพิ่งดู */
      function close(){modal.hidden=true;document.body.style.overflow='';if(selectedDate!==initialDate)window.location.assign(@json(route('ot-approval.leave-overview'))+'?date='+encodeURIComponent(selectedDate));}
      modal.querySelector('[data-leave-close]').addEventListener('click',close);modal.addEventListener('click',function(event){if(event.target===modal)close();});document.addEventListener('keydown',function(event){if(event.key==='Escape'&&!modal.hidden)close();});
    })();
  </script>
@endsection
