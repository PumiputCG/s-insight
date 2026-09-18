@extends('layouts.portal')

@section('title', 'ขอลา')
@section('topbar-title')<span data-i18n="leave.requests.title">ขอลา</span>@endsection

@section('content')
  <style>
    .main { min-width:0; }
    .leave-request-page { width:min(100%,92rem); margin:0 auto; }
    .lr-head { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:end; margin-bottom:1rem; }
    {{-- ไม่มี kicker แล้ว (.lr-kicker ถูกถอดทิ้ง 2026-08-21) ให้ตรงกับหน้าภาพรวม --}}
    .lr-head h1 { margin:.2rem 0; font-size:clamp(1.35rem,2.4vw,2rem); font-weight:600; }
    .lr-head p { margin:0; color:var(--muted-light); font-size:.84rem; }
    .lr-head-side { display:grid; justify-items:end; gap:.6rem; }

    /* ── ปุ่มนาฬิกา + โมดัลสรุปตัวเลข — สเปกเดียวกับหน้าภาพรวมการลา ────────── */
    .ot-summary-open { width:2.4rem; height:2.4rem; flex:0 0 auto; display:grid; place-items:center; border:1px solid var(--line-strong); border-radius:.3rem; background:var(--panel); color:var(--muted-light); cursor:var(--cursor-action); }
    .ot-summary-open:hover { border-color:var(--moss); background:var(--hover-soft); color:var(--moss); }
    .ot-summary-open svg { width:1.15rem; height:1.15rem; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .ot-summary-modal { position:fixed; inset:0; z-index:1200; display:grid; place-items:center; padding:1rem; background:var(--overlay-bg); }
    .ot-summary-modal[hidden] { display:none; }
    .ot-summary-dialog { width:min(28rem,100%); border:1px solid var(--line-light); border-radius:.4rem; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 22%); }
    .ot-summary-dialog-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 1.1rem; border-bottom:1px solid var(--line-light); }
    .ot-summary-close { width:2.1rem; height:2.1rem; border:1px solid var(--line-light); border-radius:.3rem; background:transparent; color:inherit; cursor:var(--cursor-action); font-size:1.1rem; line-height:1; }
    /* ทั้งบล็อกอยู่กึ่งกลางการ์ด · แต่ละบรรทัดเป็น ป้ายซ้าย–ตัวเลขขวา */
    .lr-summary { display:grid; gap:0; width:min(22rem,100%); margin:0 auto; padding:1.2rem 1.1rem; justify-content:stretch; justify-items:stretch; }
    .lr-summary-item { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:baseline; gap:1rem; min-width:0; padding:.38rem .2rem; border-bottom:1px dashed var(--line-light); }
    .lr-summary-item:last-child { border-bottom:0; }
    .lr-summary-item span { color:var(--muted-light); font-size:.78rem; }
    .lr-summary-item strong { font-size:.95rem; font-weight:600; font-variant-numeric:tabular-nums; text-align:right; }
    .lr-date { min-height:2.35rem; padding:.45rem .65rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); color:var(--light-text); }
    .lr-company { margin-bottom:.75rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel); overflow:hidden; }
    .lr-company > summary { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.1rem; background:var(--ot-tint-company); cursor:pointer; list-style:none; }
    .lr-company > summary::-webkit-details-marker { display:none; }
    .lr-company strong,.lr-company small { display:block; }
    .lr-company small { margin-top:.15rem; color:var(--muted-light); font-size:.7rem; }
    .lr-company i,.lr-dept i { font-style:normal; }
    .lr-departments { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; padding:1rem; }
    .lr-dept { position:relative; min-height:7.8rem; padding:.9rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); color:var(--light-text); text-align:left; cursor:pointer; overflow:hidden; }
    .lr-dept::before { content:''; position:absolute; top:0; left:1rem; right:1rem; height:3px; background:var(--moss); opacity:.75; }
    .lr-table .is-order { color:var(--muted-light); font-size:.74rem; font-variant-numeric:tabular-nums; text-align:center; }
    .lr-dept strong { display:block; overflow:hidden; margin-top:.25rem; font-size:.88rem; text-overflow:ellipsis; white-space:nowrap; }
    /* กระดานตัวเลขชุดเดียวกับหน้าภาพรวมและหน้าขอ OT ต่างที่คอลัมน์ขวาเป็นยอดขอลา
       คอลัมน์ตัวเลขกว้างคงที่ ไม่ให้ความยาวของเลขดันคอลัมน์เหลื่อมกันระหว่างการ์ด */
    .lr-board { display:grid; gap:.12rem; margin-top:.6rem; }
    .lr-board-head,
    .lr-board-row { display:grid; grid-template-columns:minmax(0,1fr) 2.6rem 3.5rem; align-items:center; gap:.3rem; }
    .lr-board-head { padding:0 .1rem .18rem; color:var(--muted-light); font-size:.61rem; font-weight:700; letter-spacing:.03em; }
    .lr-board-head span + span { text-align:right; }
    .lr-board-row.is-total { padding:.1rem .1rem .38rem; border-bottom:1px solid var(--line-light); margin-bottom:.18rem; }
    .lr-board-row.is-total .lr-board-label { color:var(--muted-light); font-size:.68rem; font-weight:650; }
    .lr-board-row.is-total .lr-board-metric b { font-size:1rem; }
    .lr-board-row.is-morning { --board-tone:#e8830c; }
    .lr-board-row.is-night { --board-tone:#6d28d9; }

    .lr-board-label { display:inline-flex; align-items:center; gap:.3rem; min-width:0; overflow:hidden; color:var(--light-text); font-size:.72rem; text-overflow:ellipsis; white-space:nowrap; }
    .lr-board-label svg { width:.85rem; height:.85rem; flex:0 0 auto; color:var(--board-tone,var(--muted-light)); fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .lr-board-metric { display:inline-flex; align-items:baseline; justify-content:flex-end; gap:.04rem; font-variant-numeric:tabular-nums; }
    .lr-board-metric b { color:var(--light-text); font-size:.84rem; font-weight:700; line-height:1.1; }
    .lr-board-metric i { color:var(--muted-light); font-size:.68rem; font-style:normal; }
    /* เว้นที่ล่างสุดให้แถบส่งที่ลอยอยู่ ไม่ให้บังแถวสุดท้ายของตาราง */
    .lr-workspace { margin-top:1rem; margin-bottom:5rem; border:1px solid var(--line-light); border-radius: 5px; background:var(--panel); overflow:hidden; }
    .lr-workspace[hidden] { display:none; }
    .lr-paper-head { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.1rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); }
    .lr-paper-head h2 { margin:0; font-size:1rem; }
    .lr-paper-head small { color:var(--muted-light); }
    /* ชิดขวาให้ตรงกับ .otr-paper-meta ของหน้าขอ OT — ไม่งั้นตอนตัวกรองขึ้นบรรทัดใหม่
       ปุ่ม `ขอลาทั้งหมด` จะไหลไปกองซ้ายคนละตำแหน่งกับปุ่ม `ขอ OT ทั้งหมด` */
    .lr-actions { display:flex; align-items:center; justify-content:flex-end; gap:.45rem; flex-wrap:wrap; margin-left:auto; }
    /* ตัวเรียงลำดับใช้โครงเดียวกับตัวกรองกะ/สาขา จะได้สูงเท่ากันทั้งแถว (ชุดเดียวกับหน้าภาพรวม) */
    .ot-sort-filter { position:relative; display:inline-flex; align-items:center; flex:0 0 auto; }
    .ot-sort-filter svg { position:absolute; left:.55rem; width:.95rem; height:.95rem; color:var(--muted-light); fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; pointer-events:none; }
    .ot-sort-filter select { min-height:2.45rem; padding:.4rem 1.7rem .4rem 1.95rem; border:1px solid var(--line-strong); border-radius:4px; background:var(--panel); color:var(--light-text); font-family:inherit; font-size:.74rem; font-weight:650; cursor:var(--cursor-action); appearance:none; }
    .ot-sort-filter::after { content:''; position:absolute; right:.6rem; width:.4rem; height:.4rem; border-right:1.6px solid var(--muted-light); border-bottom:1.6px solid var(--muted-light); transform:translateY(-.12rem) rotate(45deg); pointer-events:none; }
    .lr-button { min-height:2.25rem; padding:.45rem .8rem; border:1px solid var(--moss); border-radius: 4px; background:var(--moss); color:#fff; font-size:.73rem; font-weight:700; cursor:pointer; }
    .lr-button:disabled { opacity:.45; cursor:not-allowed; }
    /* ปุ่ม `ส่งขออนุมัติ` ในโมดัล `ขอลาทั้งหมด` เป็นเขียว #35a863 ชุดเดียวกับปุ่มอนุมัติของหน้าอนุมัติ
       (Manager สั่ง 2026-08-24) — เจาะจงที่ปุ่มส่ง ไม่กระทบปุ่มอื่นที่ใช้ .lr-button ร่วมกัน */
    .lr-button[data-lr-bulk-submit] { border-color:#35a863; background:#35a863; color:#fff; }
    .lr-button[data-lr-bulk-submit]:hover:not(:disabled) { background:color-mix(in srgb, #35a863 86%, #000); }
    .lr-button.is-secondary { background:transparent; color:var(--moss); }
    /* ปุ่ม `ขอลาทั้งหมด` — คัดสไตล์มาจาก .otr-primary + .otr-bulk-open ของหน้าขอ OT ให้เหมือนกันทุกจุด */
    .lr-bulk-open { min-height:2.7rem; padding:.55rem .85rem; display:inline-flex; align-items:center; gap:.4rem; white-space:nowrap; font-size:.74rem; font-weight:750; }
    .lr-bulk-open svg { width:1rem; flex:0 0 auto; fill:none; stroke:currentColor; stroke-width:1.8; }
    /* ลบร่าง — ปุ่มโปร่งขอบแดง ไม่ให้เด่นเท่าปุ่มส่งอนุมัติ */
    .lr-button.is-danger { border-color:#da5a4e; background:transparent; color:#da5a4e; }
    .lr-button.is-danger-solid { border-color:#da5a4e; background:#da5a4e; color:#fff; }
    /* กล่องยืนยัน — ทรงเดียวกับ .otr-confirm ของหน้าขอ OT */
    .lr-confirm { z-index:1200; }
    .lr-confirm-dialog { width:min(100%,30rem); }
    .lr-confirm-body { padding:1.6rem 1.4rem .4rem; text-align:center; }
    .lr-confirm-mark { display:inline-grid; place-items:center; width:2.8rem; height:2.8rem; border:1px solid color-mix(in srgb,#da5a4e 45%,transparent); border-radius:999px; color:#da5a4e; }
    .lr-confirm-mark.is-send { border-color:color-mix(in srgb,var(--moss) 45%,transparent); color:var(--moss); }
    .lr-confirm-mark svg { width:1.5rem; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .lr-confirm-body h2 { margin-top:.7rem; font-size:1rem; font-weight:700; }
    .lr-confirm-body p { margin-top:.5rem; color:var(--muted-light); font-size:.78rem; line-height:1.7; }
    .lr-confirm-field { display:block; margin-top:.9rem; text-align:left; }
    .lr-confirm-field span { display:block; margin-bottom:.3rem; color:var(--muted-light); font-size:.7rem; font-weight:650; }
    .lr-confirm-field select,
    .lr-confirm-field textarea { width:100%; padding:.55rem .7rem; border:1px solid var(--line-strong); border-radius: 4px; background:var(--panel); color:var(--light-text); font-family:inherit; font-size:.78rem; line-height:1.6; resize:vertical; }
    .lr-confirm-field select { min-height:2.35rem; }
    .lr-confirm-field textarea { margin-top:.5rem; }
    .lr-confirm-field select:focus-visible,
    .lr-confirm-field textarea:focus-visible { outline:2px solid var(--moss); outline-offset:1px; }
    .lr-confirm-error { margin-top:.5rem; color:#da5a4e; font-size:.72rem; text-align:left; }
    .lr-confirm-error[hidden] { display:none; }
    .lr-confirm-actions { display:flex; justify-content:center; gap:.6rem; padding:1.2rem 1.4rem 1.4rem; }
    .lr-confirm-actions button { min-width:8rem; }
    /* ปุ่มขอลากับปุ่มยกเลิกอยู่คู่กันในคอลัมน์เดียว */
    .lr-row-cancel { min-height:1.8rem; padding:.25rem .55rem; border:1px solid #da5a4e; border-radius: 4px; background:transparent; color:#da5a4e; font-family:inherit; font-size:.69rem; font-weight:700; cursor:pointer; }
    .lr-row-cancel:hover { background:color-mix(in srgb,#da5a4e 12%,transparent); }
    .lr-button.is-danger:hover:not(:disabled) { background:color-mix(in srgb, #da5a4e 12%, transparent); }
    .lr-row-actions { display:flex; align-items:center; justify-content:center; gap:.35rem; flex-wrap:wrap; }
    /* ป้ายบอกว่าตำแหน่งนี้ถูก admin ห้ามขอลา (หัวข้อ 8 ในหน้าตั้งค่า) — แทนที่ปุ่ม ไม่ใช่ซ่อนแถว */
    .lr-blocked { padding:.12rem .45rem; border-radius:999px; background:color-mix(in srgb,#da5a4e 13%,transparent); color:#9f2f26; font-size:.66rem; font-weight:700; }
    .lr-table-wrap { overflow:auto; }
    /* ── ผังคอลัมน์: ต้องอ่านจบในจอเดียว ไม่ต้องเลื่อนซ้าย-ขวา (Manager สั่ง 2026-08-21) ──
       ของเดิมตรึง min-width 86rem จึงล้นจอเสมอ · เปลี่ยนมากำหนดเป็น % ผ่าน <colgroup>
       เหลือ min-width ไว้เท่าที่จอมือถือยังอ่านออก (ต่ำกว่านั้นค่อยเลื่อน) */
    .lr-table { width:100%; min-width:52rem; border-collapse:collapse; table-layout:fixed; font-size:.73rem; }
    /* 10 คอลัมน์ (ไม่มีคอลัมน์ช่องติ๊กแล้ว) ผลรวมต้องเป็น 100% เสมอ */
    .lr-table col.lr-col-order { width:4%; }
    /* ลดคอลัมน์พนักงานลง เพื่อยกที่ว่างไปให้ `สถานะทั้งหมด` ที่ปุ่มผังเดินเรื่องล้นออกนอกช่อง
       (Manager แจ้ง 2026-08-24) — ชื่อยาวตัดด้วย … อยู่แล้ว จึงไม่เสียข้อมูล */
    .lr-table col.lr-col-employee { width:14%; }
    .lr-table col.lr-col-position { width:12.5%; }
    .lr-table col.lr-col-shift { width:9%; }
    .lr-table col.lr-col-date { width:8.5%; }
    .lr-table col.lr-col-action { width:11%; }
    .lr-table col.lr-col-type { width:12%; }
    .lr-table col.lr-col-status { width:10%; }
    .lr-table col.lr-col-note { width:7%; }
    .lr-table col.lr-col-route { width:12%; }
    /* ปุ่มผังเดินเรื่องต้องอยู่ในช่อง ไม่ล้นออกมา — บีบให้พอดีความกว้างคอลัมน์ */
    .lr-table td[data-lr-route-cell] { padding-left:.25rem; padding-right:.25rem; }
    .lr-table td[data-lr-route-cell] > * { max-width:100%; }
    .lr-table th,.lr-table td { padding:.6rem .45rem; border:1px solid var(--line-light); text-align:center; vertical-align:middle; }
    .lr-table th { background:var(--panel-soft); color:var(--muted-light); font-size:.67rem; line-height:1.3; overflow-wrap:anywhere; }
    /* ข้อความยาว (ตำแหน่ง · เหตุผล) ตัดบรรทัดในช่องตัวเอง ไม่ดันคอลัมน์อื่นให้เบี้ยว */
    .lr-table td { overflow-wrap:anywhere; }
    .lr-table td.is-employee { text-align:left; }
    .lr-employee { display:flex; align-items:center; gap:.5rem; min-width:0; }
    .lr-avatar { width:2.1rem; height:2.1rem; flex:0 0 auto; border-radius:50%; object-fit:cover; background:#dfe4ea; }
    /* ชื่อ-รหัสอยู่ในกล่องที่หดได้ ชื่อยาวตัดด้วย … แทนการดันคอลัมน์ให้กว้าง */
    .lr-employee > div { min-width:0; flex:1 1 auto; }
    .lr-employee strong,.lr-employee small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .lr-employee small { margin-top:.12rem; color:var(--muted-light); }
    /* ปลด min-width ของป้ายสถานะ ไม่งั้นมันจะดันคอลัมน์ให้กว้างเกินสัดส่วนที่ตั้งไว้ */
    .lr-status { display:inline-flex; justify-content:center; align-items:center; min-width:0; min-height:1.75rem; padding:.25rem .45rem; border-radius: 4px; color:#fff; font-size:.66rem; font-weight:700; }
    .lr-status.is-warning { background:#e0a51c; border:1px solid #e0a51c; }
    .lr-status.is-success { background:#35a863; border:1px solid #35a863; }
    .lr-status.is-danger { background:#da5a4e; border:1px solid #da5a4e; }
    .lr-status.is-neutral { min-width:0; padding:0; background:transparent; border:0; color:var(--muted-light); }
    .lr-status-detail { display:block; margin-top:.25rem; color:var(--light-text); font-size:.65rem; }
    .lr-empty { padding:2.4rem!important; text-align:center!important; color:var(--muted-light); }
    .lr-modal { position:fixed; inset:0; z-index:1150; display:grid; place-items:center; padding:1rem; background:rgb(8 12 10 / 56%); backdrop-filter:blur(5px); }
    .lr-modal[hidden] { display:none; }
    .lr-dialog { width:min(35rem,100%); max-height:92vh; overflow:auto; border:1px solid var(--line-light); border-radius: 6px; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 24%); }
    .lr-dialog-head { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.1rem; border-bottom:1px solid var(--line-light); }
    .lr-dialog-head h2 { margin:0; font-size:1rem; }
    .lr-close { width:2.2rem; height:2.2rem; border:1px solid var(--line-light); border-radius:50%; background:transparent; color:inherit; cursor:pointer; }
    .lr-form { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; padding:1.1rem; }
    .lr-field { display:grid; gap:.32rem; }
    .lr-field.is-wide { grid-column:1/-1; }
    .lr-field span { color:var(--muted-light); font-size:.7rem; font-weight:700; }
    .lr-field input,.lr-field select,.lr-field textarea { width:100%; padding:.65rem .7rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); color:var(--light-text); font:inherit; }
    .lr-field textarea { min-height:5rem; resize:vertical; }
    .lr-form-note { grid-column:1/-1; padding:.7rem; border-radius: 4px; background:color-mix(in srgb,var(--moss) 8%,transparent); color:var(--muted-light); font-size:.7rem; line-height:1.55; }
    .lr-form-actions { grid-column:1/-1; display:flex; justify-content:flex-end; gap:.5rem; }
    .lr-table td.is-date { white-space:nowrap; font-variant-numeric:tabular-nums; }
    /* กะงาน — ใช้รูปแบบเดียวกับ .otr-shift ของหน้าขอ OT */
    .lr-table td.is-shift { padding:.55rem .35rem; white-space:nowrap; font-size:.72rem; }
    /* สีบอกกะอยู่ที่คอลัมน์ `กะงาน` เท่านั้น ชุดเดียวกับหน้าภาพรวม (กะเวลา A ส้ม · กะเวลา B ม่วง)
       มาจากกะของแถวนั้น ไม่ใช่กะที่กำลังเลือกในตัวกรอง */
    .lr-table td.is-shift-morning { background:color-mix(in srgb, #e8830c 30%, transparent); }
    .lr-table td.is-shift-night { background:color-mix(in srgb, #6d28d9 26%, transparent); }
    .lr-shift { display:inline-block; font-size:.85rem; font-weight:600; line-height:normal; letter-spacing:normal; font-variant-numeric:tabular-nums; white-space:nowrap; }
    /* กะที่ถอยมาจากวันก่อนหน้า ไม่ใช่กะของวันลาจริง จึงหรี่ลงและมีป้ายกำกับ */
    .lr-shift.is-estimated { color:var(--muted-light); cursor:help; }
    .lr-shift-hint { display:block; margin-top:.1rem; font-size:.6rem; font-weight:700; opacity:.85; }
    /* ประเภทการลาบรรทัดบน วันที่ลาบรรทัดล่างแบบตัวเลขจาง ๆ */
    .lr-type-name { display:block; }
    .lr-type-date { display:block; margin-top:.18rem; color:var(--muted-light); font-size:.7rem; font-variant-numeric:tabular-nums; }

    /* ป้าย "กะที่คุณดูแล" + ไอคอนดวงอาทิตย์/ดวงจันทร์ + สีย้อมพื้นที่
       ใช้ชุดกลางจาก partials/shift-filter ร่วมกับหน้าขอ OT */
    /* ปุ่มกลับไปเลือกแผนก — ทรงเดียวกับ .otr-back ของหน้าขอ OT */
    .lr-back {
      display:inline-flex; align-items:center; gap:.3rem; flex:0 0 auto;
      min-height:2.35rem; padding:.4rem .8rem .4rem .6rem;
      border:1px solid var(--line-strong); border-radius:4px;
      background:var(--panel); color:var(--light-text);
      font-family:inherit; font-size:.74rem; font-weight:700; cursor:var(--cursor-action);
    }
    .lr-back:hover { border-color:var(--moss); color:var(--moss); }
    .lr-back svg { width:1rem; height:1rem; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .lr-back[hidden] { display:none; }
    /* ป้ายกับช่องวันที่อยู่บรรทัดเดียวกัน (เดิมเป็น grid 2 แถว ป้ายอยู่บนช่อง)
       ทำให้กล่องนี้สูงกว่าปุ่ม `กลับ` ข้าง ๆ จนดูเหมือนอยู่คนละบรรทัด (Manager แจ้ง 2026-08-24) */
    .lr-date-field { display:flex; align-items:center; gap:.45rem; }
    .lr-date-field span { color:var(--muted-light); font-size:.68rem; font-weight:700; white-space:nowrap; }

    /* ── โมดัลขอลาทั้งหมด 2 ขั้น ───────────────────────────────── */
    .lr-bulk-dialog { width:min(100%, 34rem); }
    .lr-bulk-dialog.is-wide { width:min(100%, 74rem); }
    .lr-bulk-step[hidden] { display:none; }
    .lr-bulk-meta { display:flex; align-items:center; gap:.4rem; margin-left:auto; flex-wrap:wrap; }
    .lr-chip { padding:.2rem .55rem; border:1px solid var(--line-light); border-radius:999px; color:var(--muted-light); font-size:.68rem; font-weight:700; }
    .lr-chip b { color:var(--light-text); }
    /* ตัวกรองเกาะกลุ่มชิดซ้าย ตัวนับไปชิดขวาด้วย margin-left:auto
       ห้ามใช้ space-between เพราะพอมีตัวกรองหลายตัวจะกระจายห่างกันคนละมุมจนอ่านไม่เป็นชุดเดียว */
    .lr-bulk-toolbar { display:flex; align-items:center; justify-content:flex-start; gap:.5rem; flex-wrap:wrap; padding:.7rem 1rem; border-bottom:1px solid var(--line-light); }
    .lr-bulk-toolbar .lr-bulk-count { margin-left:auto; }
    .lr-bulk-toolbar .lr-bulk-search { flex:0 1 14rem; }
    /* ช่องค้นหาในโมดัลกินพื้นที่ตรงกลางระหว่าง "เลือกทั้งหมด" กับตัวนับ */
    .lr-bulk-search { position:relative; display:flex; align-items:center; flex:1 1 12rem; max-width:20rem; }
    .lr-bulk-search svg { position:absolute; left:.6rem; width:.9rem; height:.9rem; color:var(--muted-light); fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; }
    .lr-bulk-search input { width:100%; min-height:2.1rem; padding:.35rem .6rem .35rem 1.9rem; border:1px solid var(--line-strong); border-radius: 4px; background:var(--panel); color:var(--light-text); font-family:inherit; font-size:.75rem; }
    .lr-bulk-search input:focus-visible { outline:2px solid var(--moss); outline-offset:1px; }
    .lr-bulk-empty { padding:1.4rem .8rem; color:var(--muted-light); font-size:.78rem; text-align:center; }
    .lr-bulk-all { display:inline-flex; align-items:center; gap:.4rem; font-size:.74rem; font-weight:700; }
    .lr-bulk-count { color:var(--muted-light); font-size:.72rem; font-variant-numeric:tabular-nums; }
    .lr-bulk-count b { color:var(--light-text); }
    .lr-bulk-scroll { max-height:min(60vh, 32rem); overflow:auto; }
    /* ตรึงผังคอลัมน์ไว้ (ความกว้างเป็น % อยู่ที่ <th> ใน renderBulkTable)
       ไม่งั้นช่องหมายเหตุที่พิมพ์ยาว ๆ จะดันคอลัมน์อื่นให้เบี้ยวไปทั้งตาราง */
    .lr-bulk-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:.73rem; }
    .lr-bulk-table th,.lr-bulk-table td { padding:.4rem .5rem; border-bottom:1px solid var(--line-light); text-align:center; vertical-align:middle; }
    .lr-bulk-table th { position:sticky; top:0; z-index:1; background:var(--panel-soft); color:var(--muted-light); font-size:.66rem; font-weight:700; }
    .lr-bulk-table tr.is-off { opacity:.45; }
    .lr-bulk-table input,.lr-bulk-table select { width:100%; padding:.32rem .4rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); color:var(--light-text); font:inherit; font-size:.72rem; }
    .lr-bulk-person { display:flex; align-items:center; gap:.45rem; text-align:left; }
    .lr-bulk-person strong { display:block; font-size:.74rem; }
    .lr-bulk-person small { display:block; color:var(--muted-light); font-size:.66rem; }
    .lr-bulk-hint { color:#d69a12; font-size:.64rem; }
    .lr-form-error { grid-column:1/-1; padding:.55rem .7rem; border-radius: 4px; background:color-mix(in srgb,#da5a4e 12%,transparent); color:#b83d34; font-size:.72rem; }
    .lr-form-error[hidden] { display:none; }
    .lr-bulk-step[data-lr-bulk-step="2"] .lr-form-actions { padding:.8rem 1rem; }

    .lr-success-mark { width:4rem; height:4rem; display:grid; place-items:center; margin:1.25rem auto .6rem; border-radius:50%; background:#35a863; color:#fff; font-size:2rem; }
    .lr-success-copy { padding:0 1rem 1.25rem; text-align:center; }
    @media(max-width:900px){.lr-head{grid-template-columns:1fr}.lr-departments{grid-template-columns:1fr}.lr-paper-head{align-items:flex-start;flex-direction:column}.lr-form{grid-template-columns:1fr}.lr-field.is-wide,.lr-form-note,.lr-form-actions{grid-column:1}}
  </style>

  <main class="leave-request-page" data-leave-requests>
    <header class="lr-head">
      <div><h1 data-i18n="leave.requests.title">ขอลา</h1><p data-i18n="leave.requests.subtitle">เลือกพนักงานและส่งคำขอลาเต็มวันตามมาตรา 75</p></div>
      {{-- ปุ่มนาฬิกาเปิดสรุปตัวเลขรวม — ชุดเดียวกับหน้าภาพรวม (Manager สั่ง 2026-08-21) --}}
      <div class="lr-head-side">
        <button type="button" class="ot-summary-open" data-summary-open aria-haspopup="dialog" data-i18n-aria="ot.attendance.summaryOpen" aria-label="ดูสรุปตัวเลขรวม" title="ดูสรุปตัวเลขรวม">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
        </button>

        <div class="ot-summary-modal" data-summary-modal hidden>
          <div class="ot-summary-dialog" role="dialog" aria-modal="true" aria-label="สรุปตัวเลขรวม">
            <header class="ot-summary-dialog-head">
              <strong data-i18n="ot.attendance.summaryTitle">สรุปตัวเลขรวม</strong>
              <button type="button" class="ot-summary-close" data-summary-close aria-label="ปิด">&times;</button>
            </header>
            <section class="lr-summary" aria-label="สรุปข้อมูลการลา">
              @php $lrTotals = $leaveSummary['totals'] ?? []; @endphp
              @foreach ([
                ['employees', 'leave.employees', 'พนักงานทั้งหมด'],
                ['requested', 'leave.requested', 'ขอลา'],
                ['pending', 'leave.pending', 'รอดำเนินการ'],
                ['approved', 'leave.approved', 'ผ่าน'],
                ['rejected', 'leave.rejected', 'ไม่อนุมัติ'],
              ] as [$key, $labelKey, $label])
                <div class="lr-summary-item">
                  <span data-i18n="{{ $labelKey }}">{{ $label }}</span>
                  <strong>{{ number_format($lrTotals[$key] ?? 0) }}</strong>
                </div>
              @endforeach
            </section>
          </div>
        </div>
      </div>
    </header>

    {{-- แถบเดียวกับหน้าขอ OT: ปฏิทินซ้าย · ป้ายกะกลางจอ · ช่องว่างขวาถ่วงให้กลางจริง --}}
    <section class="ot-shift-bar" aria-label="เลือกวันที่ดูข้อมูลและกะที่ดูแล">
      {{-- ปุ่มกลับไปเลือกแผนก — โผล่เฉพาะตอนเปิดเอกสารของแผนกอยู่ (Manager สั่ง 2026-08-24) --}}
      <button class="lr-back" type="button" data-lr-back hidden>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"></path></svg>
        <span data-i18n="ot.common.back">กลับ</span>
      </button>
      {{-- ปฏิทินนี้ใช้ "ดู" ข้อมูลของวันนั้นเท่านั้น วันที่จะลาเลือกในโมดัลขอลา --}}
      <label class="lr-date-field">
        <span data-i18n="leave.viewDate">วันที่ดูข้อมูล</span>
        <input class="lr-date" type="date" value="{{ $selectedDate->format('Y-m-d') }}" data-lr-date>
      </label>

      {{-- ป้ายกะที่ Admin กำหนดให้ดูแล ของแผนกที่เปิดอยู่ — ต้องตรงกับหน้าขอ OT --}}
      <span class="ot-my-shift" data-lr-my-shift>
        <small><span data-i18n="ot.requests.myShift">กะที่คุณดูแล</span>:</small>
        <b data-lr-my-shift-label>-</b>
      </span>
      <span class="ot-shift-bar-spacer" aria-hidden="true"></span>
    </section>

    <section data-lr-directory>
      @forelse (($leaveSummary['companies'] ?? []) as $company)
        <details class="lr-company">
          <summary><div><strong>{{ $company['name'] }}</strong><small>{{ count($company['departments']) }} <span data-i18n="leave.departments">แผนก</span></small></div><span>{{ number_format($company['total']) }} <i data-i18n="leave.peopleSuffix">คน</i>⌄</span></summary>
          <div class="lr-departments">
            @foreach ($company['departments'] as $department)
              @php
                $shifts = $department['shifts'] ?? [];
                $blank = ['total' => 0, 'requested' => 0];
                $morning = ($shifts['morning'] ?? []) + $blank;
                $night = ($shifts['night'] ?? []) + $blank;
              @endphp
              <button class="lr-dept" type="button" data-company="{{ $company['code'] }}" data-dept="{{ $department['code'] }}" data-name-th="{{ $department['name_th'] }}" data-name-en="{{ $department['name_en'] }}">
                {{-- ชื่อแผนกเป็นรูปแบบ `Accounting (ACC)` เหมือนกันทุกภาษาแล้ว จึงพิมพ์ครั้งเดียว --}}
                <strong>{{ $department['name_th'] }}</strong>
                {{-- กระดานตัวเลขชุดเดียวกับหน้าภาพรวมและหน้าขอ OT ต่างที่คอลัมน์ขวาเป็นยอดขอลา
                     กะของวันลาอ่านจาก snapshot (ลาล่วงหน้าจึงถอยไปใช้กะล่าสุดให้อัตโนมัติ) --}}
                <span class="lr-board">
                  <span class="lr-board-head" aria-hidden="true">
                    <span></span>
                    <span data-i18n="leave.peopleSuffix">คน</span>
                    <span data-i18n="leave.saved">บันทึกแล้ว</span>
                  </span>
                  <span class="lr-board-row is-total">
                    <span class="lr-board-label" data-i18n="ot.attendance.wholeDepartment">ทั้งแผนก</span>
                    <span class="lr-board-metric"><b>{{ number_format($department['total']) }}</b></span>
                    <span class="lr-board-metric"><b>{{ number_format($department['requested']) }}</b><i>/{{ number_format($department['total']) }}</i></span>
                  </span>
                  @if ($morning['total'] > 0)
                    <span class="lr-board-row is-morning">
                      <span class="lr-board-label">
                        <span data-i18n="ot.settings.shift.morning">กะเวลา A</span>
                      </span>
                      <span class="lr-board-metric"><b>{{ number_format($morning['total']) }}</b></span>
                      <span class="lr-board-metric"><b>{{ number_format($morning['requested']) }}</b><i>/{{ number_format($morning['total']) }}</i></span>
                    </span>
                  @endif
                  @if ($night['total'] > 0)
                    <span class="lr-board-row is-night">
                      <span class="lr-board-label">
                        <span data-i18n="ot.settings.shift.night">กะเวลา B</span>
                      </span>
                      <span class="lr-board-metric"><b>{{ number_format($night['total']) }}</b></span>
                      <span class="lr-board-metric"><b>{{ number_format($night['requested']) }}</b><i>/{{ number_format($night['total']) }}</i></span>
                    </span>
                  @endif
                </span>
              </button>
            @endforeach
          </div>
        </details>
      @empty
        <p class="lr-empty" data-i18n="leave.emptyPermission">ไม่พบแผนกที่อยู่ในสิทธิ์ของคุณ</p>
      @endforelse
    </section>

    <section class="lr-workspace" data-lr-workspace hidden>
      <header class="lr-paper-head">
        {{-- ถอดบรรทัด "เฉพาะวันที่ ... · กดขอลาแล้วส่งถึง Supervisor ทันที" ออก (Manager สั่ง 2026-08-24)
             วันที่อ่านได้จากปฏิทินด้านบนอยู่แล้ว ส่วนคำเตือนมีในหน้าต่างยืนยันก่อนส่งอีกที --}}
        <div><h2 data-lr-dept-title data-i18n="leave.document">เอกสารขอลา</h2></div>
        {{-- แถบส่งคำขอให้อ่านรู้เรื่องแบบหน้าขอ OT: บอกจำนวนที่เลือกและเงื่อนไขว่าส่งได้เฉพาะร่าง --}}
        <div class="lr-actions">
          {{-- ตัวกรองกะใช้ partial เดียวกับหน้าขอ OT · ปุ่มขอลาทั้งกะอยู่ข้างกัน
               ส่วนแถบส่งย้ายไปเป็นแถบล่างแบบหน้าขอ OT --}}
          {{-- เรียงลำดับตัวกรองให้เหมือนหน้าภาพรวม: ค้นหา · เรียงลำดับ · สาขา · กะ --}}
          @include('ot_approval.partials.employee-search')
          {{-- เรียงตามตำแหน่ง — ใช้ OtPositionRank ชุดเดียวกับหน้าภาพรวม --}}
          <label class="ot-sort-filter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
            <select data-sort-filter aria-label="เรียงลำดับ">
              <option value="code" data-i18n="ot.attendance.sortCode">ตามรหัสพนักงาน</option>
              <option value="rank" data-i18n="ot.attendance.sortRank">ตำแหน่งสูง → ต่ำ</option>
              <option value="rank_asc" data-i18n="ot.attendance.sortRankAsc">ตำแหน่งต่ำ → สูง</option>
            </select>
          </label>
          @include('ot_approval.partials.branch-filter')
          @include('ot_approval.partials.shift-filter')
          @include('ot_approval.partials.column-filter')
          {{-- ทรงและสีเดียวกับปุ่ม `ขอ OT ทั้งหมด` (.otr-primary + .otr-bulk-open) ของหน้าขอ OT --}}
          <button class="lr-button lr-bulk-open" type="button" data-lr-bulk>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"></path><path d="M17 15v6M14 18h6"></path></svg>
            <span data-i18n="leave.bulkRequest">ขอลาทั้งหมด</span>
          </button>
        </div>
      </header>
      <div class="lr-table-wrap" data-drag-scroll>
        <table class="lr-table">
          {{-- ความกว้างของทุกคอลัมน์กำหนดที่นี่ที่เดียว (ดูเหตุผลในบล็อก CSS `.lr-table`) --}}
          <colgroup>
            <col class="lr-col-order"><col class="lr-col-employee"><col class="lr-col-position">
            <col class="lr-col-shift"><col class="lr-col-date"><col class="lr-col-action"><col class="lr-col-type">
            <col class="lr-col-status"><col class="lr-col-note"><col class="lr-col-route">
          </colgroup>
          <thead><tr><th class="is-order" data-i18n="ot.common.order">ลำดับ</th><th data-i18n="leave.employee">พนักงาน</th><th data-i18n="leave.position">ตำแหน่ง</th><th data-i18n="leave.shift">กะงาน</th><th data-i18n="leave.requestedDate">วันที่ขอ</th><th data-i18n="leave.requested">ขอลา</th><th data-i18n="leave.leaveType">ประเภทการลา</th><th data-i18n="leave.status">สถานะ</th><th data-i18n="leave.reason">เหตุผล</th><th data-i18n="leave.route">สถานะทั้งหมด</th></tr></thead>
          <tbody data-lr-body><tr><td colspan="10" class="lr-empty" data-i18n="leave.loading">กำลังโหลด...</td></tr></tbody>
        </table>
      </div>

      {{-- ช่องติ๊กในตารางถูกถอดออกแล้ว (Manager สั่ง 2026-08-21 ว่าไม่ได้ใช้)
           แถบ "ยกเลิกที่เลือก" จึงถูกถอดตามไปด้วย — การยกเลิกใช้ปุ่มในแถวของแต่ละคนแทน --}}
    </section>
  </main>

  <div class="lr-modal" data-lr-form-modal hidden>
    <section class="lr-dialog" role="dialog" aria-modal="true" aria-labelledby="lrFormTitle">
      <header class="lr-dialog-head"><h2 id="lrFormTitle" data-i18n="leave.form.title">ส่งคำขอลา</h2><button class="lr-close" type="button" data-lr-close>×</button></header>
      <form class="lr-form" data-lr-form>
        <div class="lr-field is-wide"><span data-i18n="leave.employee">พนักงาน</span><strong data-lr-person>-</strong></div>
        <label class="lr-field is-wide"><span data-i18n="leave.leaveType">ประเภทการลา</span><select name="leave_type" required>@foreach($leaveTypes as $type)<option value="{{ $type['code'] }}" data-label-th="{{ $type['label_th'] }}" data-label-en="{{ $type['label_en'] }}" data-label-my="{{ $type['label_my'] }}">{{ $type['label_th'] }}</option>@endforeach</select></label>
        <label class="lr-field"><span data-i18n="leave.form.from">ตั้งแต่วันที่</span><input type="date" name="start_date" value="{{ $selectedDate->format('Y-m-d') }}" required></label>
        <label class="lr-field"><span data-i18n="leave.form.to">ถึงวันที่</span><input type="date" name="end_date" value="{{ $selectedDate->format('Y-m-d') }}" required></label>
        <label class="lr-field is-wide"><span data-i18n="leave.note">หมายเหตุ</span><textarea name="note" maxlength="1000" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)" data-i18n-placeholder="leave.form.notePlaceholder"></textarea></label>
        <p class="lr-form-note" data-i18n="leave.form.splitHint">ระบบจะแตกช่วงวันที่เป็นคำขอเต็มวัน วันละ 1 รายการ เพื่อให้ตรงกับฟอร์มนำเข้า Bplus</p>
        <div class="lr-form-actions"><button class="lr-button is-secondary" type="button" data-lr-cancel data-i18n="leave.cancel">ยกเลิก</button><button class="lr-button" type="submit" data-i18n="leave.save">ส่งอนุมัติ</button></div>
      </form>
    </section>
  </div>

  {{-- ── ขอลาทั้งหมด 2 ขั้น ตามหน้าขอ OT ─────────────────────────
       ขั้น 1 เลือกช่วงวันที่จะลา · ขั้น 2 เลือกคนและแก้รายคน แล้วส่งอนุมัติทันที --}}
  <div class="lr-modal" data-lr-bulk-modal hidden>
    <section class="lr-dialog lr-bulk-dialog" role="dialog" aria-modal="true" aria-labelledby="lrBulkTitle">
      <header class="lr-dialog-head">
        <div>
          <small data-i18n="leave.bulkRequest">ขอลาทั้งหมด</small>
          <h2 id="lrBulkTitle" data-lr-bulk-title>—</h2>
        </div>
        <div class="lr-bulk-meta">
          <span class="lr-chip" data-lr-bulk-shift>—</span>
          <span class="lr-chip"><b data-lr-bulk-people>0</b> <span data-i18n="leave.peopleSuffix">คน</span></span>
        </div>
        <button class="lr-close" type="button" data-lr-bulk-close>×</button>
      </header>

      <div class="lr-bulk-step" data-lr-bulk-step="1">
        <div class="lr-form">
          <label class="lr-field is-wide">
            <span data-i18n="leave.leaveType">ประเภทการลา</span>
            <select data-lr-bulk-type>@foreach($leaveTypes as $type)<option value="{{ $type['code'] }}" data-label-th="{{ $type['label_th'] }}" data-label-en="{{ $type['label_en'] }}" data-label-my="{{ $type['label_my'] }}">{{ $type['label_th'] }}</option>@endforeach</select>
          </label>
          <label class="lr-field"><span data-i18n="leave.form.from">ตั้งแต่วันที่</span><input type="date" data-lr-bulk-start></label>
          <label class="lr-field"><span data-i18n="leave.form.to">ถึงวันที่</span><input type="date" data-lr-bulk-end></label>
          <label class="lr-field is-wide"><span data-i18n="leave.note">หมายเหตุ</span><textarea maxlength="1000" data-lr-bulk-note></textarea></label>
          <p class="lr-form-note" data-i18n="leave.form.splitHint">ระบบจะแตกช่วงวันที่เป็นคำขอเต็มวัน วันละ 1 รายการ เพื่อให้ตรงกับฟอร์มนำเข้า Bplus</p>
          <div class="lr-form-error" data-lr-bulk-error-1 hidden></div>
          <div class="lr-form-actions">
            <button class="lr-button is-secondary" type="button" data-lr-bulk-close data-i18n="leave.cancel">ยกเลิก</button>
            <button class="lr-button" type="button" data-lr-bulk-next data-i18n="leave.next">ไปต่อ</button>
          </div>
        </div>
      </div>

      <div class="lr-bulk-step" data-lr-bulk-step="2" hidden>
        <div class="lr-bulk-toolbar">
          <label class="lr-bulk-all">
            <input type="checkbox" checked data-lr-bulk-all>
            <span data-i18n="leave.bulkSelectAll">เลือกทั้งหมด</span>
          </label>
          {{-- ตัวกรองชุดเดียวกับตารางหลักและหน้าภาพรวม เรียงลำดับเหมือนกัน: ค้นหา · เรียงลำดับ · สาขา · กะ
               กรองแค่การแสดงผล ไม่แตะการติ๊ก คนที่ติ๊กไว้แล้วแต่ถูกกรองหายยังถูกส่งอยู่
               (ตัวนับ "เลือกแล้ว x/y" ข้าง ๆ เป็นตัวบอกว่ายังมีคนที่ติ๊กไว้แต่ไม่เห็นบนจอ) --}}
          <label class="lr-bulk-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.2-3.2"></path></svg>
            <input type="search" data-lr-bulk-search data-i18n-placeholder="ot.search.placeholder" placeholder="ค้นหารหัสหรือชื่อพนักงาน">
          </label>
          <label class="ot-sort-filter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
            <select data-lr-bulk-sort aria-label="เรียงลำดับ">
              <option value="code" data-i18n="ot.attendance.sortCode">ตามรหัสพนักงาน</option>
              <option value="rank" data-i18n="ot.attendance.sortRank">ตำแหน่งสูง → ต่ำ</option>
              <option value="rank_asc" data-i18n="ot.attendance.sortRankAsc">ตำแหน่งต่ำ → สูง</option>
            </select>
          </label>
          @include('ot_approval.partials.branch-filter')
          @include('ot_approval.partials.shift-filter')
          <span class="lr-bulk-count"><b data-lr-bulk-picked>0</b>/<span data-lr-bulk-total>0</span> <span data-i18n="leave.peopleSuffix">คน</span></span>
        </div>
        <div class="lr-bulk-scroll"><table class="lr-bulk-table" data-lr-bulk-table></table></div>
        <div class="lr-form-error" data-lr-bulk-error-2 hidden></div>
        <div class="lr-form-actions">
          <button class="lr-button is-secondary" type="button" data-lr-bulk-back data-i18n="leave.bulkBack">ย้อนกลับ</button>
          <button class="lr-button" type="button" data-lr-bulk-submit><span data-i18n="leave.submit">ส่งขออนุมัติ</span> (<span data-lr-bulk-submit-count>0</span>)</button>
        </div>
      </div>
    </section>
  </div>

  <div class="lr-modal" data-lr-success hidden>
    <section class="lr-dialog" role="alertdialog" aria-modal="true"><div class="lr-success-mark">✓</div><div class="lr-success-copy"><h2 data-i18n="leave.success">สำเร็จ</h2><p data-lr-success-message data-i18n="leave.done">ดำเนินการเรียบร้อยแล้ว</p><button class="lr-button" type="button" data-lr-success-close data-i18n="leave.ok">ตกลง</button></div></section>
  </div>

  @include('ot_approval.partials.leave-process-route')
  {{-- ยืนยันก่อนส่งจริง — ใช้ร่วมกันทั้งขอรายคนและขอทั้งกะ (ทรงเดียวกับหน้าขอ OT) --}}
  <div class="lr-modal lr-confirm" data-lr-send-confirm role="alertdialog" aria-modal="true" aria-labelledby="lrSendTitle" hidden>
    <section class="lr-dialog lr-confirm-dialog">
      <div class="lr-confirm-body">
        <span class="lr-confirm-mark is-send" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M21.5 2.5 11 13"></path><path d="M21.5 2.5 15 21l-4-8-8-4Z"></path></svg>
        </span>
        <h2 id="lrSendTitle" data-i18n="ot.requests.sendConfirmTitle">ยืนยันส่งขออนุมัติ</h2>
        <p data-lr-send-message>—</p>
      </div>
      <footer class="lr-confirm-actions">
        <button class="lr-button is-secondary" type="button" data-lr-send-cancel data-i18n="ot.requests.sendConfirmCancel">ยกเลิก</button>
        <button class="lr-button" type="button" data-lr-send-accept data-i18n="ot.requests.sendConfirmAccept">ยืนยันส่ง</button>
      </footer>
    </section>
  </div>

  {{-- ยกเลิกคำขอที่ส่งไปแล้ว — บังคับกรอกเหตุผลเพราะเก็บไว้เป็นประวัติให้ตรวจย้อนได้ --}}
  <div class="lr-modal lr-confirm" data-lr-cancel-confirm role="alertdialog" aria-modal="true" aria-labelledby="lrCancelTitle" hidden>
    <section class="lr-dialog lr-confirm-dialog">
      <div class="lr-confirm-body">
        <span class="lr-confirm-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 9v4.5M12 17h.01M10.3 3.9 2.4 17.4A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3.1L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
        </span>
        <h2 id="lrCancelTitle" data-i18n="ot.requests.cancelConfirmTitle">ยืนยันยกเลิกคำขอ</h2>
        <p data-lr-cancel-message>—</p>
        <label class="lr-confirm-field">
          <span data-i18n="ot.requests.cancelReasonLabel">เหตุผลที่ยกเลิก (บังคับกรอก)</span>
          <select data-lr-cancel-reason-select>
            <option value="" disabled hidden data-i18n="ot.cancel.selectReason">เลือกเหตุผล</option>
            <option value="พนักงานไม่สะดวก" data-i18n="ot.cancel.employeeUnavailable">พนักงานไม่สะดวก</option>
            <option value="ปรับแผนงาน" data-i18n="ot.cancel.workPlanChanged">ปรับแผนงาน</option>
            <option value="วันที่หรือเวลาไม่ถูกต้อง" data-i18n="ot.cancel.wrongSchedule">วันที่หรือเวลาไม่ถูกต้อง</option>
            <option value="ประเภทคำขอไม่ถูกต้อง" data-i18n="ot.cancel.wrongRequestType">ประเภทคำขอไม่ถูกต้อง</option>
            <option value="ส่งคำขอซ้ำ" data-i18n="ot.cancel.duplicateRequest">ส่งคำขอซ้ำ</option>
            <option value="__other__" data-i18n="ot.cancel.other">อื่นๆ</option>
          </select>
          <textarea rows="3" maxlength="500" placeholder="พิมพ์เหตุผลอื่นๆ" data-i18n-placeholder="ot.cancel.otherPlaceholder" data-lr-cancel-reason-other hidden></textarea>
        </label>
        <p class="lr-confirm-error" data-lr-cancel-error hidden></p>
      </div>
      <footer class="lr-confirm-actions">
        <button class="lr-button is-secondary" type="button" data-lr-cancel-close data-i18n="leave.cancel">ยกเลิก</button>
        <button class="lr-button is-danger-solid" type="button" data-lr-cancel-accept data-i18n="ot.requests.cancelConfirmAccept">ยืนยันยกเลิก</button>
      </footer>
    </section>
  </div>

  @include('ot_approval.partials.photo-zoom')
  @include('ot_approval.partials.success-feedback')

  <script>
    (function leaveRequests() {
      'use strict';
      var root=document.querySelector('[data-leave-requests]');if(!root)return;
      var workspace=root.querySelector('[data-lr-workspace]'),directory=root.querySelector('[data-lr-directory]'),body=root.querySelector('[data-lr-body]');
      var modal=document.querySelector('[data-lr-form-modal]'),form=modal.querySelector('[data-lr-form]'),success=document.querySelector('[data-lr-success]');
      var csrf=document.querySelector('meta[name="csrf-token"]').content;var selectedDate=@json($selectedDate->format('Y-m-d'));var currentDepartment=null,currentEmployee=null,currentRows=[];
      var endpoints={employees:@json(route('ot-approval.leave-requests.employees')),store:@json(route('ot-approval.leave-requests.store')),submit:@json(route('ot-approval.leave-requests.submit')),cancel:@json(route('ot-approval.leave-requests.cancel')),bulk:@json(route('ot-approval.leave-requests.bulk'))};
      var myShiftByDept=@json($myShiftByDept);   // กะที่ Admin เซ็ตไว้ (module ลา) แยกตามแผนก
      function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[c];});}
      function lang(){return document.documentElement.getAttribute('data-lang')||'th';}function text(key,fallback){return window.__portalLang?window.__portalLang.text(key,fallback):fallback;}function val(row,key){return row[key+'_'+lang()]||row[key+'_th']||row[key+'_en']||'-';}
      /* data-leave-photo = ตัวจับของ partial photo-zoom กดรูปแล้วเปิดภาพใหญ่ (ชุดเดียวกับหน้าอนุมัติลา) */
      function employee(row){var name=val(row,'name');return '<div class="lr-employee">'+(row.avatar?'<img class="lr-avatar" src="'+esc(row.avatar)+'" alt="'+esc(name)+'" data-leave-photo data-caption-name="'+esc(name)+'" data-caption-code="'+esc(row.code)+'">':'<span class="lr-avatar"></span>')+'<div><strong>'+esc(name)+'</strong><small>'+esc(row.code)+'</small></div></div>';}
      function badge(request){if(!request)return '<span class="lr-status is-neutral">-</span>';var label=text('leave.status.'+request.approval_status,request.status_label);var detail=request.approval_status==='submitted'?text('leave.status.waitingSupervisor',request.status_detail||''):'';if(request.approval_status==='submitted'&&request.can_decide===false){label=text('leave.expired','พ้นกำหนดอนุมัติ');detail='';}return '<span class="lr-status is-'+esc(request.approval_status==='submitted'&&request.can_decide===false?'danger':request.status_tone)+'">'+esc(label)+'</span>'+(detail?'<small class="lr-status-detail">'+esc(detail)+'</small>':'');}
      /* ช่องติ๊กและแถบ "ยกเลิกที่เลือก" ถูกถอดออกแล้ว (Manager สั่ง 2026-08-21)
         คงฟังก์ชันเปล่าไว้เพราะมีจุดเรียกหลายที่ในไฟล์
         การยกเลิกตอนนี้ใช้ปุ่มในแถวของแต่ละคน (data-lr-cancel-one) อย่างเดียว */
      function syncSelected(){}
      /* ── ตัวกรองกะ ────────────────────────────────────────────────
         ใช้ helper กลางตัวเดียวกับหน้าขอ OT (window.otShiftFilter)
         ข้อมูลกะมาจาก Snapshot ท้องถิ่น ไม่ได้เรียก Bplus ตามข้อกำหนดของหน้าลา */
      var shiftSelect=root.querySelector('[data-shift-filter]');
      var shiftValue=window.otShiftFilter?window.otShiftFilter.ALL:'all';
      var employeeSearchInput=root.querySelector('[data-emp-search]');
      var employeeQuery='';   // คำค้นรหัส/ชื่อ กรองในเครื่องจากรายชื่อที่โหลดมาแล้ว
      var branchValue='all';  // สาขา (โรงงาน / โรงงาน-พม่า) กรองในเครื่องเช่นกัน
      var sortMode='code';    // ตามรหัสพนักงาน (เริ่มต้น) / ตำแหน่งสูง→ต่ำ / ตำแหน่งต่ำ→สูง
      /* true = เปลี่ยนวันไปแล้วระหว่างเปิดเอกสารแผนก การ์ดแผนกด้านหลังจึงเป็นยอดของวันเดิม
         ต้องโหลดหน้าใหม่ตอนกดปุ่มกลับ ไม่งั้นตัวเลขบนการ์ดกับที่เพิ่งดูคนละวันกัน */
      var pendingDirectoryReload=false;
      /* ตัวกรองรายคอลัมน์แบบ Excel — helper กลางจาก partials/column-filter
         กดตกลงแล้ววาดตารางใหม่จากข้อมูลในมือ ไม่ยิงเซิร์ฟเวอร์ซ้ำ */
      var columnFilters=window.otColumnFilter.create(function(){renderRows(visibleRows());});

      function visibleRows(){
        var rows=currentRows.filter(function(row){
          var shiftOk=!window.otShiftFilter||window.otShiftFilter.matches(row,shiftValue);

          return shiftOk
            &&window.otBranchFilter.matches(row,branchValue)
            &&window.otEmployeeSearch.matches(row,employeeQuery)
            &&columnFilters.matches(row);
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

      function paintShift(){
        if(!window.otShiftFilter)return;
        /* ย้อมที่กล่องตาราง ไม่ใช่ทั้ง section เพราะหัวเอกสารมีพื้นทึบของตัวเองอยู่แล้ว
           (เท่ากับที่หน้าขอ OT ย้อม paperContent ไม่ใช่ .otr-paper ทั้งก้อน) */
        /* ส่ง null แทน .lr-table-wrap: พื้นตารางต้องขาวเสมอ (Manager สั่ง 2026-08-21)
           สีบอกกะเหลืออยู่แค่คอลัมน์ `กะงาน` · ช่องตัวกรองยังย้อมขอบได้ */
        window.otShiftFilter.paint(shiftSelect,null,shiftValue,currentRows);
        var wrap=root.querySelector('.lr-table-wrap');
        if(wrap)wrap.classList.remove('ot-shift-scene','is-shift-morning','is-shift-night','is-shift-all');
      }

      /* ── กะที่ Admin กำหนดให้ดูแล ─────────────────────────────────
         แผนกเดียวกันคนละคนอาจได้คนละกะ จึงอ่านตามแผนกที่เปิดอยู่
         เป็นค่าเริ่มต้นของตัวกรองเท่านั้น ยังเลื่อนไปดูกะอื่นได้ เหมือนหน้าขอ OT */
      function activeShiftAssignment(){
        return currentDepartment
          ? (myShiftByDept[currentDepartment.company+'|'+currentDepartment.dept]||null)
          : null;
      }

      function assignedShiftFilterValue(){
        var group=activeShiftAssignment();
        return group&&group.filter_value?group.filter_value:(window.otShiftFilter?window.otShiftFilter.ALL:'all');
      }

      function renderMyShift(){
        var group=activeShiftAssignment();
        root.querySelector('[data-lr-my-shift-label]').textContent=group?val(group,'label'):'-';

        /* Manager สั่ง 2026-08-21: เอาไอคอนดวงอาทิตย์/ดวงจันทร์ออก และเลิกย้อมพื้นที่เนื้อหา
           ตามกะที่ดูแล — สีบอกกะเหลืออยู่แค่คอลัมน์ `กะงาน` ในตาราง (เหมือนหน้าขอ OT) */
        var shell=document.querySelector('.main');
        if(shell)shell.classList.remove('is-myshift-morning','is-myshift-night');
      }

      /* สีพื้นคอลัมน์กะงาน มาจากกะของ "แถวนั้น" ไม่ใช่กะที่กำลังเลือกในตัวกรอง */
      function shiftTone(row){
        var group=window.otShiftFilter?window.otShiftFilter.groupOf('s:'+(row.shift_code||''),[row]):'';
        return group==='morning'||group==='night'?' is-shift-'+group:'';
      }

      function render(rows,useAssignedShift){
        currentRows=rows;
        refreshColumnFilters();
        if(window.otShiftFilter&&shiftSelect){
          // เปิดแผนกใหม่ให้เริ่มที่กะของตัวเอง หลังจากนั้นเคารพค่าที่ผู้ใช้เลือกเอง
          shiftValue=window.otShiftFilter.build(shiftSelect,rows,useAssignedShift?assignedShiftFilterValue():shiftValue);
          paintShift();
        }
        renderRows(visibleRows());
      }

      /* กะงานของวันที่กำลังดู — แสดงช่วงเวลาแบบเดียวกับหน้าขอ OT (compactShiftRange)
         ลา 75 ขอล่วงหน้า วันลาจึงมักยังไม่มี Snapshot ระบบจะถอยไปใช้กะล่าสุดที่เคยบันทึก
         กรณีนั้นติดดอกจันไว้ให้รู้ว่าไม่ใช่กะของวันนั้นจริง ๆ */
      function shiftCell(row){
        if(!row.shift_in||!row.shift_out)return '<span class="lr-shift">-</span>';
        var start=String(row.shift_in).slice(0,5).replace(':','.');
        var end=String(row.shift_out).slice(0,5).replace(':','.');
        var label=esc(start+'–'+end);

        if(!row.shift_is_estimated)return '<span class="lr-shift">'+label+'</span>';

        var hint=text('leave.shiftEstimated','กะล่าสุดที่บันทึกไว้')
          +(row.shift_source_date?' ('+thaiDate(row.shift_source_date)+')':'');

        return '<span class="lr-shift is-estimated" title="'+esc(hint)+'">'+label
          +'<small class="lr-shift-hint">'+esc(text('leave.shiftEstimatedShort','กะล่าสุด'))+'</small></span>';
      }

      /* ประเภทการลาพ่วงวันที่ลาไว้ใต้ชื่อ ส่วนคอลัมน์แรกเหลือวันที่ยื่นคำขออย่างเดียว */
      function typeCell(request){
        return '<span class="lr-type-name">'+esc(request['leave_type_label_'+lang()]||request.leave_type_label_th)+'</span>'
          +'<span class="lr-type-date">'+esc(request.leave_date_label)+'</span>';
      }

      /* ตัวกรองรายคอลัมน์ — ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะพอจะกรองได้จริง
         (ตำแหน่ง · กะงาน · ประเภทการลา · สถานะ) ชุดเดียวกับหน้าภาพรวมการลา
         สร้างตัวเลือกจากรายชื่อทั้งแผนก ไม่ใช่แถวที่เหลือหลังกรอง ไม่งั้นเลือกกลับไม่ได้ */
      function refreshColumnFilters(){
        var head=root.querySelectorAll('.lr-table thead th');
        columnFilters.attach(head[2],'position',function(row){return val(row,'position');},currentRows);
        columnFilters.attach(head[3],'shift',function(row){
          return row.shift_in&&row.shift_out?String(row.shift_in).slice(0,5)+'–'+String(row.shift_out).slice(0,5):'-';
        },currentRows);
        columnFilters.attach(head[6],'leaveType',function(row){
          var request=row.leave_request;
          return request?(request['leave_type_label_'+lang()]||request.leave_type_label_th||'-'):'-';
        },currentRows);
        columnFilters.attach(head[7],'status',function(row){
          var request=row.leave_request;
          return request?(request.approval_status||'-'):'none';
        },currentRows,function(value){
          return value==='none'?text('leave.noRequest','ไม่มีคำขอ'):text('leave.status.'+value,value);
        });
      }

      function renderRows(rows){
        if(!rows.length){body.innerHTML='<tr><td colspan="10" class="lr-empty">'+esc(text('leave.emptyDepartment','ไม่พบพนักงานในแผนกนี้'))+'</td></tr>';syncSelected();return;}
        body.replaceChildren();
        rows.forEach(function(row,order){
          var request=row.leave_request;
          if(request)request.avatar=row.avatar;
          var canCancel=!!request&&(request.approval_status==='draft'||request.approval_status==='submitted');
          var action=request&&request.approval_status!=='draft'?text('leave.view','แก้ไขลา'):(request?text('leave.edit','แก้ไข'):text('leave.requested','ขอลา'));
          /* ตำแหน่งที่ admin สั่งห้ามขอลา (หัวข้อ 8 ในหน้าตั้งค่า) — ซ่อนปุ่มแล้วบอกเหตุผลแทน
             ยังต้องเห็นแถวอยู่ ไม่ใช่หายไปเฉย ๆ จะได้รู้ว่าทำไมคนนี้กดไม่ได้
             ถ้าเคยยื่นไว้ก่อนถูกบล็อก ยังต้องเปิดดู/ยกเลิกคำขอเดิมได้ */
          var leaveBlocked=row.can_request_leave===false&&!request;
          var tr=document.createElement('tr');
          /* ลำดับนับจากแถวที่มองเห็นจริง จึงเริ่มที่ 1 ใหม่ทุกครั้งที่เปลี่ยนตัวกรอง */
          tr.innerHTML='<td class="is-order">'+(order+1)+'</td>'
            +'<td class="is-employee">'+employee(row)+'</td>'
            /* ตัดคอลัมน์ `แผนก` ออก (Manager สั่ง 2026-08-21) — ทุกแถวเป็นแผนกเดียวกันอยู่แล้ว
               และชื่อแผนกอยู่บนหัวเอกสารด้านบนแล้ว ไม่ต้องซ้ำทุกบรรทัด */
            +'<td>'+esc(val(row,'position'))+'</td>'
            +'<td class="is-shift'+shiftTone(row)+'">'+shiftCell(row)+'</td>'
            +'<td class="is-date">'+(request?esc(request.requested_date_label||'-'):'-')+'</td>'
            +'<td><div class="lr-row-actions">'
            +(leaveBlocked
              ?'<span class="lr-blocked">'+esc(text('leave.positionNotEligible','ตำแหน่งนี้ไม่มีสิทธิ์ขอลา'))+'</span>'
              :'<button class="lr-button is-secondary" type="button" data-lr-request data-code="'+esc(row.code)+'">'+esc(action)+'</button>')
            +(canCancel?'<button class="lr-row-cancel" type="button" data-lr-cancel-one value="'+request.id+'">'+esc(text('ot.requests.cancelOne','ยกเลิก'))+'</button>':'')
            +'</div></td>'
            +'<td class="is-type">'+(request?typeCell(request):'-')+'</td>'
            +'<td>'+badge(request)+'</td>'
            +'<td>'+esc(request?(request.decision_note||request.note||'-'):'-')+'</td>'
            +'<td data-lr-route-cell></td>';

          /* ปุ่มสถานะเป็น element จริงที่มี event listener ติดมา ต่อเป็น string ไม่ได้
             (จะกลายเป็น [object HTMLButtonElement]) จึง append เข้า cell ที่เว้นไว้
             อ้างด้วย attribute แทน index เพราะเดิมใช้ children[8] ซึ่งชี้ผิดไปคอลัมน์ "เหตุผล"
             และจะพังอีกทุกครั้งที่สลับลำดับคอลัมน์ */
          var routeCell=tr.querySelector('[data-lr-route-cell]');
          if(request&&window.leaveProcessRoute){
            routeCell.appendChild(window.leaveProcessRoute.create(request));
          } else {
            routeCell.textContent='-';
          }
          body.appendChild(tr);
        });
        body.querySelectorAll('[data-lr-cancel-one]').forEach(function(button){
          button.addEventListener('click',function(){openCancelConfirm([Number(button.value)]);});
        });
        body.querySelectorAll('[data-lr-request]').forEach(function(button){button.addEventListener('click',function(){currentEmployee=rows.find(function(row){return row.code===button.dataset.code;});openForm();});});syncSelected();
      }

      /* ── ขอลาทั้งหมด 2 ขั้น ───────────────────────────────────────
         ขั้น 1 เลือกช่วงวันที่จะลาจริง (คนละเรื่องกับปฏิทินดูข้อมูลด้านบน)
         ขั้น 2 เลือกคนและแก้รายคน แล้วกดส่งขออนุมัติ = สร้างและส่งในครั้งเดียว
         ไม่ค้างเป็นร่างเหมือนของเดิม */
      var bulkModal=document.querySelector('[data-lr-bulk-modal]');
      var bulkRows=[];
      var bulkQuery='';   // คำค้นในโมดัล — กรองแค่การแสดงผล ไม่แตะการติ๊ก
      var bulkBranch='all';  // สาขาในโมดัล — กรองแค่การแสดงผลเหมือนกัน
      var bulkShift='all';   // กะในโมดัล — กรองแค่การแสดงผลเหมือนกัน
      var bulkSort='code';   // การเรียงในโมดัล
      /* ตัวกรองรายคอลัมน์ของตารางในโมดัล — helper กลางตัวเดียวกับตารางหลัก */
      var bulkColumnFilters=window.otColumnFilter.create(function(){renderBulkTable();});
      /* ป้ายประเภทการลาตามภาษาที่เปิดอยู่ อ่านจาก <option> ของฟอร์มขั้นที่ 1 (แหล่งเดียว) */
      function bulkTypeLabel(code){
        var option=bulkField('type').querySelector('option[value="'+String(code||'').replace(/"/g,'')+'"]');
        return option?option.textContent:'';
      }

      function bulkTargets(){ return visibleRows(); }

      function bulkField(name){ return bulkModal.querySelector('[data-lr-bulk-'+name+']'); }

      function bulkStep(step){
        bulkModal.querySelectorAll('[data-lr-bulk-step]').forEach(function(pane){
          pane.hidden=Number(pane.dataset.lrBulkStep)!==step;
        });
        bulkModal.querySelector('.lr-bulk-dialog').classList.toggle('is-wide',step===2);
      }

      function closeBulk(){ bulkModal.hidden=true; document.body.style.overflow=''; bulkRows=[]; bulkQuery=''; bulkBranch='all'; bulkShift='all'; bulkSort='code'; bulkColumnFilters.reset(); }

      function localizeBulkTypes(){
        bulkField('type').querySelectorAll('option').forEach(function(option){
          option.textContent=option.dataset['label'+lang().charAt(0).toUpperCase()+lang().slice(1)]||option.dataset.labelTh;
        });
      }

      function openBulkForm(){
        var targets=bulkTargets();
        if(!targets.length){window.alert(text('leave.bulkEmpty','ไม่มีพนักงานในตัวกรองนี้'));return;}

        /* ตัวกรองในโมดัลเริ่มใหม่ทุกครั้งที่เปิด และต้องล้าง "ตัวควบคุมบนจอ" ด้วย
           ไม่ใช่แค่ตัวแปร ไม่งั้นช่องค้นหายังมีข้อความค้างแต่ตารางแสดงครบ = เห็นไม่ตรงกัน */
        bulkQuery=''; bulkBranch='all'; bulkShift='all'; bulkSort='code'; bulkColumnFilters.reset();
        if(bulkSearchInput)bulkSearchInput.value='';
        if(bulkBranchSelect)bulkBranchSelect.value='all';
        if(bulkSortSelect)bulkSortSelect.value='code';

        bulkModal.querySelector('[data-lr-bulk-title]').textContent=departmentName(currentDepartment.button);
        bulkField('shift').textContent=(shiftSelect&&shiftSelect.options[shiftSelect.selectedIndex]
          ?shiftSelect.options[shiftSelect.selectedIndex].textContent:'-').replace(/\s*\(\d+\)\s*$/,'');
        bulkField('people').textContent=targets.length;

        /* ค่าเริ่มต้นเป็นวันถัดจากวันที่กำลังดู เพราะลา 75 มักแจ้งล่วงหน้า
           แต่แก้เป็นวันไหนก็ได้ รวมถึงย้อนหลัง */
        var tomorrow=new Date(selectedDate+'T00:00:00');
        tomorrow.setDate(tomorrow.getDate()+1);
        var defaultDate=tomorrow.toISOString().slice(0,10);
        bulkField('start').value=defaultDate;
        bulkField('end').value=defaultDate;
        bulkField('note').value='';
        localizeBulkTypes();
        bulkField('error-1').hidden=true;
        bulkField('error-2').hidden=true;
        bulkStep(1);
        bulkModal.hidden=false;
        document.body.style.overflow='hidden';
      }

      function buildBulkRows(){
        var shared={
          leave_type:bulkField('type').value,
          start_date:bulkField('start').value,
          end_date:bulkField('end').value,
          note:bulkField('note').value.trim()
        };
        var error=bulkField('error-1');
        if(!shared.start_date||!shared.end_date||shared.end_date<shared.start_date){
          error.textContent=text('leave.bulkInvalidRange','ช่วงวันที่ไม่ถูกต้อง');
          error.hidden=false;
          return;
        }
        error.hidden=true;

        bulkRows=bulkTargets().map(function(row){
          /* ตำแหน่งที่ admin สั่งห้ามขอลา (หัวข้อ 8) — ติ๊กไม่ได้และไม่ถูกส่ง
             แต่ยังโชว์ในตารางพร้อมเหตุผล ไม่ใช่หายไปเฉย ๆ (กติกาเดียวกับฝั่ง OT) */
          var blocked=row.can_request_leave===false?text('leave.positionNotEligible','ตำแหน่งนี้ไม่มีสิทธิ์ขอลา'):'';

          return {
            employee:row,
            blocked:blocked,
            picked:blocked==='',
            leave_type:shared.leave_type,
            start_date:shared.start_date,
            end_date:shared.end_date,
            note:shared.note,
            /* คนที่มีคำขอของ "วันที่กำลังดู" อยู่แล้ว เตือนไว้เฉย ๆ ไม่บล็อก
               เพราะช่วงที่เลือกอาจไม่ใช่วันนั้น และ server จะข้ามซ้ำให้เองพร้อมเหตุผล */
            hint:row.leave_request?text('leave.bulkHasRequest','มีคำขอของวันที่กำลังดูแล้ว'):''
          };
        });

        renderBulkTable();
        bulkStep(2);
      }

      /* แถวที่ผ่านตัวกรองอยู่ตอนนี้ — ใช้ร่วมกันทั้งตอนวาดตาราง
         ตอนกด "เลือกทั้งหมด" และตอนคำนวณสถานะของ master checkbox
         ชุดตัวกรองเท่ากับตารางหลัก: ค้นหา · สาขา · กะ แล้วเรียงตามตัวเลือก */
      function visibleBulkRows(){
        var rows=bulkRows.filter(function(row){
          return window.otEmployeeSearch.matches(row.employee,bulkQuery)
            &&window.otBranchFilter.matches(row.employee,bulkBranch)
            &&(!window.otShiftFilter||window.otShiftFilter.matches(row.employee,bulkShift))
            &&bulkColumnFilters.matches(row);
        });

        if(bulkSort==='rank'||bulkSort==='rank_asc'){
          var dir=bulkSort==='rank'?1:-1;
          rows=rows.slice().sort(function(a,b){
            var diff=((a.employee.position_rank||999)-(b.employee.position_rank||999))*dir;
            return diff!==0?diff:String(a.employee.code).localeCompare(String(b.employee.code));
          });
        }

        return rows;
      }

      function syncBulkCount(){
        var picked=bulkRows.filter(function(row){return row.picked&&!row.blocked;}).length;
        /* เลขหน้า = คนที่เลือกไว้ · เลขหลัง = พนักงานทั้งหมดในตัวกรองนี้ (รวมคนที่ขอไม่ได้)
           ส่วนปุ่มส่งบอกจำนวนที่จะถูกส่งจริง — ปกติเท่ากับเลขหน้า
           เพราะ renderBulkTable ปลดติ๊กคนที่ถูกกรองหายออกให้แล้ว */
        var visible=visibleBulkRows();
        bulkModal.querySelector('[data-lr-bulk-picked]').textContent=visible.filter(function(row){return row.picked&&!row.blocked;}).length;
        bulkModal.querySelector('[data-lr-bulk-total]').textContent=visible.length;
        bulkModal.querySelector('[data-lr-bulk-submit-count]').textContent=picked;
        bulkModal.querySelector('[data-lr-bulk-submit]').disabled=picked<1;

        /* master สะท้อนเฉพาะแถวที่มองเห็นและติ๊กได้ ไม่ใช่ทั้งแผนก
           ไม่งั้นกรองเหลือโรงงาน-พม่าแล้วติ๊กครบ ช่องหัวจะยังไม่ติ๊กเพราะคนอื่นยังไม่ถูกเลือก
           และแถวที่ถูกบล็อกติ๊กไม่ได้อยู่แล้ว ถ้านับรวมช่องหัวจะไม่มีวันติ๊กครบ */
        var visibleUsable=visible.filter(function(row){return !row.blocked;});
        var visiblePicked=visibleUsable.filter(function(row){return row.picked;}).length;
        var master=bulkModal.querySelector('[data-lr-bulk-all]');
        if(master){
          master.disabled=visibleUsable.length===0;
          master.checked=visibleUsable.length>0&&visiblePicked===visibleUsable.length;
          master.indeterminate=visiblePicked>0&&visiblePicked<visibleUsable.length;
        }
      }

      function renderBulkTable(){
        var table=bulkModal.querySelector('[data-lr-bulk-table]');
        table.replaceChildren();

        /* ติ๊กต้องเดินตามตัวกรองเสมอ (Manager สั่ง 2026-08-24)
           เปิดโมดัลมาทุกคนถูกติ๊กไว้ก่อน พอกรองแล้วคนที่หายไปจากจอต้องถูกปลดติ๊กด้วย
           ไม่งั้น "เลือกทั้งหมด" จะหมายถึงคนละชุดกับที่ตาเห็น แล้วกดส่งได้คนที่ไม่ได้ดู */
        var stillVisible=visibleBulkRows();
        bulkRows.forEach(function(row){
          if(stillVisible.indexOf(row)===-1)row.picked=false;
        });

        /* ตัวเลือกกะสร้างจากคนทั้งชุดก่อนกรอง ไม่งั้นพอกรองแล้วตัวเลือกอื่นหายจนกลับไม่ได้ */
        if(bulkShiftSelect&&window.otShiftFilter){
          bulkShift=window.otShiftFilter.build(
            bulkShiftSelect,
            bulkRows.map(function(row){return row.employee;}),
            bulkShift
          );
        }

        /* ผังคอลัมน์เป็น % รวมกัน 100 พอดี (8 คอลัมน์) — เพิ่ม `กะงาน` เข้ามาให้กรองได้จริง
           เพราะคอลัมน์ที่เหลือเป็นช่องแก้รายคนที่ค่าเริ่มเหมือนกันหมด กรองแล้วไม่ได้อะไร */
        var head=document.createElement('thead');
        head.innerHTML='<tr><th style="width:3%"></th><th style="width:3.5%">#</th>'
          +'<th style="width:24%;text-align:left">'+esc(text('leave.employee','พนักงาน'))+'</th>'
          +'<th style="width:10%">'+esc(text('leave.shift','กะงาน'))+'</th>'
          +'<th style="width:16%">'+esc(text('leave.leaveType','ประเภทการลา'))+'</th>'
          +'<th style="width:12%">'+esc(text('leave.form.from','ตั้งแต่วันที่'))+'</th>'
          +'<th style="width:12%">'+esc(text('leave.form.to','ถึงวันที่'))+'</th>'
          +'<th style="width:19.5%">'+esc(text('leave.note','หมายเหตุ'))+'</th></tr>';
        table.appendChild(head);

        /* ตัวกรองรายคอลัมน์แบบ Excel — รูปแบบเดียวกับตารางหลัก (helper กลาง otColumnFilter)
           ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะพอจะกรองได้จริง: กะงาน · ประเภทการลา
           สร้างตัวเลือกจาก bulkRows ทั้งชุดก่อนกรอง ไม่งั้นกรองแล้วเลือกกลับไม่ได้ */
        var bulkHeadCells=head.querySelectorAll('th');
        bulkColumnFilters.attach(bulkHeadCells[3],'shift',function(row){
          var person=row.employee;
          return person.shift_in&&person.shift_out
            ? String(person.shift_in).slice(0,5)+'–'+String(person.shift_out).slice(0,5)
            : '-';
        },bulkRows);
        bulkColumnFilters.attach(bulkHeadCells[4],'leaveType',function(row){
          var option=bulkTypeLabel(row.leave_type);
          return option||row.leave_type||'-';
        },bulkRows);

        var tbody=document.createElement('tbody');
        /* ตัวกรองตัดแค่ "การแสดงผล" ไม่ตัดออกจาก bulkRows จริง คนที่ติ๊กไว้แล้วจึงไม่หลุด
           (ตัวนับ "เลือกแล้ว x/y" ข้างบนเป็นตัวบอกว่ายังมีคนที่ติ๊กไว้แต่ถูกกรองหายอยู่) */
        var visible=visibleBulkRows();
        if(!visible.length){
          var emptyRow=document.createElement('tr');
          var emptyCell=document.createElement('td');
          emptyCell.colSpan=8;
          emptyCell.className='lr-bulk-empty';
          emptyCell.textContent=bulkQuery?text('ot.search.empty','ไม่พบพนักงานที่ค้นหา'):text('leave.emptyDepartment','ไม่พบพนักงานในแผนกนี้');
          emptyRow.appendChild(emptyCell);
          tbody.appendChild(emptyRow);
        }
        /* เลข # นับจากแถวที่มองเห็นจริง เริ่ม 1 ใหม่ทุกครั้งที่กรอง/เรียงใหม่
           (เดิมนับจาก bulkRows ทั้งชุด พอเพิ่มการเรียงลำดับแล้วเลขจะกระโดด 5, 2, 9 …) */
        visible.forEach(function(row,index){
          var tr=document.createElement('tr');
          tr.classList.toggle('is-off',!row.picked);

          function cell(node){var td=document.createElement('td');td.appendChild(node);return td;}

          var pick=document.createElement('input');
          pick.type='checkbox';
          pick.checked=row.picked;
          pick.disabled=Boolean(row.blocked);   // ตำแหน่งที่ห้ามขอลา ติ๊กไม่ได้
          pick.setAttribute('aria-label',val(row.employee,'name'));
          pick.addEventListener('change',function(){
            row.picked=pick.checked;
            tr.classList.toggle('is-off',!row.picked);
            syncBulkCount();
          });
          tr.appendChild(cell(pick));
          tr.appendChild(cell(document.createTextNode(String(index+1))));

          var person=document.createElement('div');
          person.className='lr-bulk-person';
          person.innerHTML=(row.employee.avatar?'<img class="lr-avatar" src="'+esc(row.employee.avatar)+'" alt="'+esc(val(row.employee,'name'))+'" data-leave-photo data-caption-name="'+esc(val(row.employee,'name'))+'" data-caption-code="'+esc(row.employee.code)+'">':'<span class="lr-avatar"></span>')
            +'<div><strong>'+esc(val(row.employee,'name'))+'</strong><small>'+esc(row.employee.code)+' · '+esc(val(row.employee,'position'))+'</small>'
            +(row.hint?'<small class="lr-bulk-hint">'+esc(row.hint)+'</small>':'')
            +(row.blocked?'<small class="lr-blocked">'+esc(row.blocked)+'</small>':'')+'</div>';
          tr.appendChild(cell(person));

          /* กะงานของคนนั้น — คอลัมน์เดียวในตารางนี้ที่มีค่าซ้ำจริงจนกรองได้มีประโยชน์
             ที่เหลือเป็นช่องแก้รายคนที่ค่าเริ่มเหมือนกันหมดจากขั้นที่ 1 */
          var shiftText=row.employee.shift_in&&row.employee.shift_out
            ? String(row.employee.shift_in).slice(0,5)+'–'+String(row.employee.shift_out).slice(0,5)
            : '-';
          tr.appendChild(cell(document.createTextNode(shiftText)));

          var type=document.createElement('select');
          bulkField('type').querySelectorAll('option').forEach(function(option){
            var clone=document.createElement('option');
            clone.value=option.value;
            clone.textContent=option.textContent;
            type.appendChild(clone);
          });
          type.value=row.leave_type;
          type.addEventListener('change',function(){row.leave_type=type.value;});
          tr.appendChild(cell(type));

          [['start_date','from'],['end_date','to']].forEach(function(pair){
            var input=document.createElement('input');
            input.type='date';
            input.value=row[pair[0]];
            input.addEventListener('change',function(){row[pair[0]]=input.value;});
            tr.appendChild(cell(input));
          });

          var note=document.createElement('input');
          note.type='text';
          note.maxLength=1000;
          note.value=row.note;
          note.addEventListener('input',function(){row.note=note.value;});
          tr.appendChild(cell(note));

          tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        /* สะท้อนสถานะจริง ไม่ใช่บังคับติ๊กทุกครั้ง ไม่งั้นกด "เลือกทั้งหมด" ออกแล้วจะเด้งกลับ
           เทียบกับ "แถวที่มองเห็นอยู่" ไม่ใช่ทั้งชุด ไม่งั้นกรองแล้วติ๊กครบแต่ช่องหัวยังไม่ติ๊ก
           (syncBulkCount ด้านล่างตั้งค่าซ้ำอีกชั้นด้วยตรรกะเดียวกัน) */
        var all=bulkModal.querySelector('[data-lr-bulk-all]');
        var visibleNow=visibleBulkRows().filter(function(row){return !row.blocked;});
        all.checked=visibleNow.length>0&&visibleNow.every(function(row){return row.picked;});
        syncBulkCount();
      }

      async function submitBulk(){
        // กันชนอีกชั้น: แถวที่ตำแหน่งถูกห้ามขอลาต้องไม่หลุดไปถึงเซิร์ฟเวอร์ แม้ติ๊กไม่ได้อยู่แล้ว
        var picked=bulkRows.filter(function(row){return row.picked&&!row.blocked;});
        var error=bulkField('error-2');
        var button=bulkModal.querySelector('[data-lr-bulk-submit]');
        error.hidden=true;

        var invalid=picked.find(function(row){
          return !row.start_date||!row.end_date||row.end_date<row.start_date;
        });
        if(invalid){
          error.textContent=text('leave.bulkInvalidRange','ช่วงวันที่ไม่ถูกต้อง');
          error.hidden=false;
          return;
        }

        // ยืนยันก่อนยิงจริง เพราะส่งทีเดียวทั้งกะแล้วถอยไม่ได้
        var agreed=await askSend(
          text('ot.requests.sendConfirmBulk','ระบบจะส่งคำขอทั้งหมดให้ Supervisor ทันทีและแจ้งทางอีเมล')
          +' · '+picked.length+' '+text('ot.requests.items','รายการ')
        );
        if(!agreed)return;

        button.disabled=true;
        try{
          var response=await fetch(endpoints.bulk,{
            method:'POST',
            headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
            body:JSON.stringify({
              company:currentDepartment.company,
              dept_code:currentDepartment.dept,
              items:picked.map(function(row){
                return {
                  employee_code:row.employee.code,
                  leave_type:row.leave_type,
                  start_date:row.start_date,
                  end_date:row.end_date,
                  note:row.note
                };
              })
            })
          });
          var data=await response.json();
          if(!response.ok||!data.ok)throw new Error(messageOf(data));
          closeBulk();
          /* ขั้น 2 แก้วันรายคนได้ วันเริ่มจึงไม่จำเป็นต้องเท่ากันทุกแถว
             พาไปวันที่เร็วที่สุดที่เพิ่งบันทึก (Y-m-d เรียงแบบ string = เรียงตามวัน) */
          planDateJump(picked.map(function(row){return row.start_date;}).sort()[0]);
          showSuccess(data.message||text('leave.done','ดำเนินการเรียบร้อยแล้ว'));
          await loadDepartment();
        }catch(err){
          error.textContent=err.message||text('leave.loadError','ส่งคำขอไม่สำเร็จ');
          error.hidden=false;
        }finally{
          syncBulkCount();
        }
      }

      bulkModal.querySelectorAll('[data-lr-bulk-close]').forEach(function(button){button.addEventListener('click',closeBulk);});
      bulkModal.addEventListener('click',function(event){if(event.target===bulkModal)closeBulk();});
      bulkModal.querySelector('[data-lr-bulk-next]').addEventListener('click',buildBulkRows);
      bulkModal.querySelector('[data-lr-bulk-back]').addEventListener('click',function(){bulkStep(1);});
      bulkModal.querySelector('[data-lr-bulk-submit]').addEventListener('click',submitBulk);

      /* ค้นหาในโมดัลขั้นที่ 2 — หน่วงไว้เล็กน้อยเพราะวาดตารางใหม่ทั้งใบทุกครั้งที่พิมพ์
         ตัวติ๊กไม่ถูกล้างเมื่อค้นหา เพราะยังไม่ได้ส่งอะไรออกไป ผู้ใช้จึงเลือกทีละกลุ่ม
         ด้วยการค้นหลายรอบแล้วค่อยกดส่งครั้งเดียวได้ */
      /* ตัวกรองสาขาในโมดัลต้องอ้างผ่าน bulkModal ไม่ใช่ root
         เพราะหน้าเดียวมี [data-branch-filter] สองตัว (แถบหัวตาราง กับ ในโมดัล) */
      /* ── ตัวกรองในโมดัลขอลาทั้งหมด (Manager สั่ง 2026-08-21 ให้เท่ากับตารางหลัก) ──────
         กะ · เรียงลำดับ = กรองการแสดงผลเฉย ๆ ไม่แตะการติ๊ก คนที่ติ๊กไว้แล้วยังถูกส่งอยู่ */
      var bulkShiftSelect=bulkModal.querySelector('[data-shift-filter]');
      if(bulkShiftSelect){
        bulkShiftSelect.addEventListener('change',function(){bulkShift=bulkShiftSelect.value;renderBulkTable();});
      }
      var bulkSortSelect=bulkModal.querySelector('[data-lr-bulk-sort]');
      if(bulkSortSelect){
        bulkSortSelect.addEventListener('change',function(){bulkSort=bulkSortSelect.value;renderBulkTable();});
      }
      var bulkBranchSelect=bulkModal.querySelector('[data-branch-filter]');
      if(bulkBranchSelect){
        bulkBranchSelect.addEventListener('change',function(){bulkBranch=bulkBranchSelect.value;renderBulkTable();});
      }

      var bulkSearchInput=bulkModal.querySelector('[data-lr-bulk-search]');
      if(bulkSearchInput){
        bulkSearchInput.addEventListener('input',window.otEmployeeSearch.debounce(function(){
          bulkQuery=bulkSearchInput.value;
          renderBulkTable();
        },200));
      }
      bulkModal.querySelector('[data-lr-bulk-all]').addEventListener('change',function(event){
        /* เลือกเฉพาะแถวที่ผ่านตัวกรองอยู่ตอนนี้ — กรองโรงงาน-พม่าแล้วกดเลือกทั้งหมด
           ต้องได้เฉพาะคนพม่า ไม่ใช่ลากคนทั้งแผนกมาส่งด้วย */
        var pickAll=event.target.checked;
        visibleBulkRows().forEach(function(row){if(!row.blocked)row.picked=pickAll;});
        renderBulkTable();
      });
      async function loadDepartment(useAssignedShift){body.innerHTML='<tr><td colspan="10" class="lr-empty">'+esc(text('leave.loading','กำลังโหลด...'))+'</td></tr>';try{var url=endpoints.employees+'?company='+encodeURIComponent(currentDepartment.company)+'&dept_code='+encodeURIComponent(currentDepartment.dept)+'&date='+encodeURIComponent(selectedDate);var response=await fetch(url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});var data=await response.json();if(!response.ok||!data.ok)throw new Error(messageOf(data));render(data.leave.employees||[],useAssignedShift);}catch(error){body.innerHTML='<tr><td colspan="10" class="lr-empty">'+esc(error.message||text('leave.loadError','ไม่สามารถโหลดข้อมูลได้'))+'</td></tr>';}}
      function messageOf(payload){if(payload&&payload.errors){var values=Object.values(payload.errors);if(values.length)return Array.isArray(values[0])?values[0][0]:values[0];}return payload&&payload.message?payload.message:text('leave.loadError','ดำเนินการไม่สำเร็จ');}
      function openForm(){if(!currentEmployee)return;form.querySelector('button[type="submit"]').textContent=text('leave.save','ส่งอนุมัติ');var request=currentEmployee.leave_request;modal.querySelector('[data-lr-person]').textContent=val(currentEmployee,'name')+' · '+currentEmployee.code;form.start_date.value=request?request.range_start:selectedDate;form.end_date.value=request?request.range_end:selectedDate;form.leave_type.value=request?request.leave_type:(form.leave_type.options[0]?form.leave_type.options[0].value:'section_75');form.note.value=request&&request.note?request.note:'';modal.hidden=false;document.body.style.overflow='hidden';}
      function closeForm(){modal.hidden=true;document.body.style.overflow='';}
      /* ตารางหน้านี้ผูกกับวันที่ในปฏิทินตัวเดียว แต่ฟอร์มเลือกช่วงวันเองได้
         ขอลาวันที่ 20 ขณะที่ปฏิทินอยู่วันที่ 18 คำขอจะไปอยู่คนละวันกับที่กำลังดู
         มองไม่เห็นในตาราง และถ้าเป็นร่างก็กดส่งอนุมัติไม่ได้ จึงต้องพาไปวันที่ลาให้ */
      var pendingJumpDate=null;
      function thaiDate(value){var p=String(value).split('-');return p[2]+'/'+p[1]+'/'+(Number(p[0])+543);}
      function planDateJump(startDate){pendingJumpDate=(startDate&&startDate!==selectedDate)?startDate:null;}
      function goPendingDate(){if(!pendingJumpDate)return false;window.location.assign(@json(route('ot-approval.leave-requests.index'))+'?date='+encodeURIComponent(pendingJumpDate));return true;}
      function closeSuccess(){if(goPendingDate())return;success.hidden=true;document.body.style.overflow='';}

      function showSuccess(message){
        var note=pendingJumpDate
          ? ' · '+text('leave.jumpToDate','กำลังพาไปยังวันที่')+' '+thaiDate(pendingJumpDate)
          : '';
        if (window.otSuccessFeedback) {
          window.otSuccessFeedback.show(message+note, {
            duration: pendingJumpDate ? 0 : 1700,
            onClose: pendingJumpDate ? function () { goPendingDate(); } : null
          });
          return;
        }
        success.querySelector('[data-lr-success-message]').textContent=message+note;
        success.hidden=false;document.body.style.overflow='hidden';
      }
      function departmentName(button){return lang()==='th'?button.dataset.nameTh:(button.dataset.nameEn||button.dataset.nameTh);}
      function localizeOptions(){form.querySelectorAll('select[name="leave_type"] option').forEach(function(option){option.textContent=option.dataset['label'+lang().charAt(0).toUpperCase()+lang().slice(1)]||option.dataset.labelTh;});}
      root.querySelectorAll('.lr-dept').forEach(function(button){button.addEventListener('click',function(){currentDepartment={company:button.dataset.company,dept:button.dataset.dept,button:button};root.querySelector('[data-lr-dept-title]').removeAttribute('data-i18n');root.querySelector('[data-lr-dept-title]').textContent=departmentName(button);directory.hidden=true;workspace.hidden=false;if(backButton)backButton.hidden=false;renderMyShift();syncSelected();loadDepartment(true);});});

      /* ปุ่มกลับไปเลือกแผนก — ล้างแผนกที่เปิดอยู่ ไม่งั้นตัวโหลดยังอ้างแผนกเดิมค้างไว้ */
      var backButton=root.querySelector('[data-lr-back]');
      if(backButton){
        backButton.addEventListener('click',function(){
          /* ถ้าเปลี่ยนวันไประหว่างเปิดเอกสารอยู่ ยอดบนการ์ดแผนกยังเป็นของวันเดิม
             (ข้อมูลส่วนนั้นเรนเดอร์จากฝั่ง server) จึงต้องโหลดหน้าใหม่ตอนกลับ */
          if(pendingDirectoryReload){
            window.location.assign(@json(route('ot-approval.leave-requests.index'))+'?date='+encodeURIComponent(selectedDate));
            return;
          }

          currentDepartment=null;
          currentRows=[];
          workspace.hidden=true;
          directory.hidden=false;
          backButton.hidden=true;
          renderMyShift();
        });
      }
      root.querySelector('[data-lr-bulk]').addEventListener('click',openBulkForm);
      if(shiftSelect){
        shiftSelect.addEventListener('change',function(){
          shiftValue=shiftSelect.value;
          paintShift();
          renderRows(visibleRows());
        });
      }
      var branchSelect=root.querySelector('[data-branch-filter]');
      if(branchSelect){
        branchSelect.addEventListener('change',function(){
          branchValue=branchSelect.value;
          renderRows(visibleRows());
        });
      }
      /* เรียงลำดับ — ต้องเจาะจงตัวใน .lr-actions เพราะโมดัลขอลาทั้งหมดมีตัวเรียงของตัวเองอีกตัว */
      var sortSelect=root.querySelector('.lr-actions [data-sort-filter]');
      if(sortSelect){
        sortSelect.addEventListener('change',function(){
          sortMode=sortSelect.value;
          renderRows(visibleRows());
        });
      }
      if(employeeSearchInput){
        employeeSearchInput.addEventListener('input',function(){
          employeeQuery=employeeSearchInput.value;
          renderRows(visibleRows());
        });
      }
      /* เปลี่ยนวันแล้ว "ค้างอยู่ที่แผนกเดิม" (Manager สั่ง 2026-08-24)
         เดิมโหลดทั้งหน้าใหม่ ผู้ใช้จึงเด้งกลับไปหน้าเลือกแผนกทุกครั้งที่เปลี่ยนวัน
         - เปิดเอกสารแผนกอยู่ = โหลดเฉพาะตาราง แล้วแก้ URL ให้ตรงด้วย replaceState
         - ยังอยู่หน้ารายชื่อแผนก = โหลดทั้งหน้าเหมือนเดิม เพราะยอดบนการ์ดมาจากฝั่ง server */
      root.querySelector('[data-lr-date]').addEventListener('change',function(event){
        if(!event.target.value)return;
        selectedDate=event.target.value;

        if(!currentDepartment){
          window.location.assign(@json(route('ot-approval.leave-requests.index'))+'?date='+encodeURIComponent(selectedDate));
          return;
        }

        try{
          var url=new URL(window.location.href);
          url.searchParams.set('date',selectedDate);
          window.history.replaceState({},'',url);
        }catch(error){/* URL API ใช้ไม่ได้ก็ข้ามไป ไม่ใช่สาระสำคัญ */}

        pendingDirectoryReload=true;   // การ์ดแผนกด้านหลังยังเป็นยอดของวันเดิม
        loadDepartment(false);
      });
      /* ── ยืนยันก่อนส่ง ────────────────────────────────────────────
         เดิมกดแล้วยิงทันที ย้อนไม่ได้เพราะอีเมลถึง Supervisor ไปแล้ว */
      var sendConfirm=document.querySelector('[data-lr-send-confirm]');
      function askSend(message){
        return new Promise(function(resolve){
          sendConfirm.querySelector('[data-lr-send-message]').textContent=message;
          sendConfirm.hidden=false;document.body.style.overflow='hidden';
          var accept=sendConfirm.querySelector('[data-lr-send-accept]');
          var cancel=sendConfirm.querySelector('[data-lr-send-cancel]');
          function close(result){
            sendConfirm.hidden=true;document.body.style.overflow='';
            accept.removeEventListener('click',onAccept);cancel.removeEventListener('click',onCancel);
            resolve(result);
          }
          function onAccept(){close(true);}
          function onCancel(){close(false);}
          accept.addEventListener('click',onAccept);cancel.addEventListener('click',onCancel);
        });
      }

      /* ── ยกเลิกคำขอ ───────────────────────────────────────────────
         ใช้ทั้งปุ่มรายแถวและปุ่มยกเลิกที่เลือกในแถบล่าง endpoint เดียวกัน
         เหตุผลบังคับกรอกเพราะเก็บลง audit ไว้ให้ตรวจย้อนได้ว่าใครยกเลิกเพราะอะไร */
      var cancelConfirm=document.querySelector('[data-lr-cancel-confirm]');
      function openCancelConfirm(ids){
        if(!ids.length)return;
        var reasonSelect=cancelConfirm.querySelector('[data-lr-cancel-reason-select]');
        var reasonOther=cancelConfirm.querySelector('[data-lr-cancel-reason-other]');
        var errorNode=cancelConfirm.querySelector('[data-lr-cancel-error]');
        var accept=cancelConfirm.querySelector('[data-lr-cancel-accept]');
        var close=cancelConfirm.querySelector('[data-lr-cancel-close]');
        reasonSelect.value='';reasonOther.value='';reasonOther.hidden=true;errorNode.hidden=true;
        reasonSelect.onchange=function(){
          reasonOther.hidden=reasonSelect.value!=='__other__';
          if(!reasonOther.hidden)reasonOther.focus();
        };
        cancelConfirm.querySelector('[data-lr-cancel-message]').textContent=
          text('ot.requests.cancelConfirmBody','คำขอที่ยกเลิกจะถูกถอนออกจากคิวของ Supervisor และเก็บไว้เป็นประวัติพร้อมเหตุผล')
          +' · '+ids.length+' '+text('ot.requests.items','รายการ');
        cancelConfirm.hidden=false;document.body.style.overflow='hidden';reasonSelect.focus();

        function shut(){
          cancelConfirm.hidden=true;document.body.style.overflow='';
          accept.removeEventListener('click',onAccept);close.removeEventListener('click',shut);
        }
        async function onAccept(){
          var value=reasonSelect.value==='__other__'?reasonOther.value.trim():reasonSelect.value.trim();
          if(value.length<3){
            errorNode.textContent=text('ot.requests.cancelReasonRequired','กรุณาระบุเหตุผลอย่างน้อย 3 ตัวอักษร');
            errorNode.hidden=false;(reasonSelect.value==='__other__'?reasonOther:reasonSelect).focus();return;
          }
          accept.disabled=true;
          try{
            var response=await fetch(endpoints.cancel,{
              method:'POST',
              headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
              body:JSON.stringify({request_ids:ids,reason:value}),
            });
            var data=await response.json();
            if(!response.ok||!data.ok)throw new Error(messageOf(data));
            shut();
            showSuccess(data.message||text('ot.requests.cancelled','ยกเลิกคำขอแล้ว'));
            await loadDepartment();
          }catch(error){
            errorNode.textContent=error.message||text('leave.loadError','ยกเลิกคำขอไม่สำเร็จ');
            errorNode.hidden=false;
          }finally{
            accept.disabled=false;syncSelected();
          }
        }
        accept.addEventListener('click',onAccept);close.addEventListener('click',shut);
      }


      async function storeFor(code){
        var response=await fetch(endpoints.store,{
          method:'POST',
          headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
          body:JSON.stringify({company:currentDepartment.company,dept_code:currentDepartment.dept,employee_code:code,leave_type:form.leave_type.value,start_date:form.start_date.value,end_date:form.end_date.value,note:form.note.value}),
        });
        var data=await response.json();
        if(!response.ok||!data.ok)throw new Error(messageOf(data));

        return data;
      }

      form.addEventListener('submit',async function(event){
        event.preventDefault();
        var button=form.querySelector('button[type="submit"]');
        button.disabled=true;

        /* ยืนยันก่อนยิงจริง เพราะกดแล้วส่งถึง Supervisor ทันที ถอยไม่ได้ */
        var agreed=await askSend(text('ot.requests.sendConfirmBody','ระบบจะส่งคำขอนี้ให้ Supervisor ทันทีและแจ้งทางอีเมล ต้องการส่งเลยหรือไม่'));
        if(!agreed){button.disabled=false;return;}

        try{
          var savedStart=form.start_date.value;
          var data=await storeFor(currentEmployee.code);
          closeForm();
          planDateJump(savedStart);
          showSuccess(data.message||text('leave.done','ดำเนินการเรียบร้อยแล้ว'));
          await loadDepartment();
        }catch(error){
          window.alert(error.message||text('leave.loadError','บันทึกคำขอไม่สำเร็จ'));
        }finally{
          button.disabled=false;
        }
      });
      /* ตัวส่งร่างเดิมถูกถอดออก — กดขอลาแล้วส่งให้ Supervisor ทันที ไม่มีร่างค้างให้ส่งซ้ำ */
      document.addEventListener('insight:languagechange',function(){localizeOptions();localizeBulkTypes();renderMyShift();if(currentDepartment){root.querySelector('[data-lr-dept-title]').textContent=departmentName(currentDepartment.button);render(currentRows);}});localizeOptions();localizeBulkTypes();renderMyShift();
      modal.querySelector('[data-lr-close]').addEventListener('click',closeForm);modal.querySelector('[data-lr-cancel]').addEventListener('click',closeForm);modal.addEventListener('click',function(event){if(event.target===modal)closeForm();});success.querySelector('[data-lr-success-close]').addEventListener('click',closeSuccess);
      document.addEventListener('keydown',function(event){if(event.key!=='Escape')return;if(!success.hidden){closeSuccess();}else if(!bulkModal.hidden)closeBulk();else if(!modal.hidden)closeForm();});
    })();

    /* ── สรุปตัวเลขรวม: เปิดจากปุ่มนาฬิกา (ต้นแบบหน้าภาพรวมการลา) ──────────
       แยกเป็น IIFE ของตัวเองเพื่อไม่ให้พังไปกับก้อนใหญ่ด้านบน (กับดักข้อ 2) */
    (function leaveRequestsSummaryModal() {
      'use strict';
      var box = document.querySelector('[data-summary-modal]');
      var open = document.querySelector('[data-summary-open]');
      if (!box || !open) return;
      function close() { box.hidden = true; document.body.style.overflow = ''; }
      open.addEventListener('click', function () { box.hidden = false; document.body.style.overflow = 'hidden'; });
      box.querySelector('[data-summary-close]').addEventListener('click', close);
      box.addEventListener('click', function (event) { if (event.target === box) close(); });
      document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !box.hidden) close(); });
    })();
  </script>
@endsection
