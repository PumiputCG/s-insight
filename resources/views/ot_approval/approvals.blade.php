@extends('layouts.portal')

@section('title', 'OT Approvals')
@section('topbar-title')<span data-i18n="ot.approvals.title">อนุมัติ OT</span>@endsection

@section('content')
  <style>
    .ota-page,
    .ota-modal {
      --ota-status-warning: #8a6200;
      --ota-status-warning-border: #e0a51c;
      --ota-status-success: #1d7a42;
      --ota-status-success-border: #35a863;
      --ota-status-danger: #9f2f26;
      --ota-status-danger-border: #da5a4e;
    }
    .ota-page {
      width: min(100%, 92rem);
      margin: 0 auto;
    }

    .ota-head { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .ota-heading h1 { margin-top: .2rem; font-size: 1.8rem; font-weight: 650; line-height: 1.2; }
    .ota-heading p { margin-top: .35rem; color: var(--muted-light); font-size: .84rem; }
    /* แถวเดียวกัน: แท็บสถานะชิดซ้าย ปุ่มอนุมัติที่เลือกชิดขวา */
    .ota-toolbar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin-bottom: .8rem; }
    /* แถบเครื่องมือ: ตัวกรองเกาะกลุ่มชิดซ้าย ปุ่มตัดสินหลายรายการไปชิดขวา */
    .ota-toolbar .ota-bulk { margin-left: auto; }
    /* ตัวกรองประเภท OT — ใช้โครงเดียวกับตัวกรองกะ/สาขา จะได้สูงเท่ากันทั้งแถว */
    .ot-type-filter { position: relative; display: inline-flex; align-items: center; flex: 0 0 auto; }
    .ot-type-filter svg { position: absolute; left: .55rem; width: .95rem; height: .95rem; color: var(--muted-light); fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; pointer-events: none; }
    .ot-type-filter select { min-height: 2.45rem; max-width: 12rem; padding: .4rem 1.7rem .4rem 1.95rem; border: 1px solid var(--line-strong); border-radius: 4px; background: var(--panel); color: var(--light-text); font-family: inherit; font-size: .74rem; font-weight: 650; cursor: var(--cursor-action); appearance: none; text-overflow: ellipsis; }
    .ot-type-filter::after { content: ''; position: absolute; right: .6rem; width: .4rem; height: .4rem; border-right: 1.6px solid var(--muted-light); border-bottom: 1.6px solid var(--muted-light); transform: translateY(-.12rem) rotate(45deg); pointer-events: none; }
    .ota-bulk { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; flex: 0 0 auto; margin-left: auto; }
    .ota-bulk-count { color: var(--muted-light); font-size: .7rem; font-weight: 650; white-space: nowrap; }
    /* ใช้ชุดเดียวกับปุ่ม bulk ของหน้าอนุมัติลา (.la-button) ให้สองหน้าอ่านเหมือนกัน */
    .ota-bulk-action { min-height: 2.3rem; padding: .44rem .7rem; border-radius: 4px; font-size: .73rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
    /* ปุ่ม `อนุมัติที่เลือก` ใช้เขียว #35a863 ชุดเดียวกับป้ายสถานะ `อนุมัติแล้ว` (Manager สั่ง 2026-08-24)
       คู่กับปุ่มปฏิเสธสีแดง อ่านออกทันทีว่าอันไหนคืออนุมัติ ไม่ใช่เขียว moss ของธีมทั่วไป */
    .ota-bulk-approve { border: 1px solid #35a863; background: #35a863; color: #fff; }
    .ota-bulk-approve:hover:not(:disabled) { background: color-mix(in srgb, #35a863 86%, #000); }
    .ota-bulk-reject { border: 1px solid #da5a4e; background: #da5a4e; color: #fff; }
    .ota-bulk-reject:hover:not(:disabled) { background: color-mix(in srgb, #da5a4e 86%, #000); }
    .ota-bulk-action:disabled { opacity: .45; cursor: not-allowed; }
    /* ── ปฏิทินกรองวันที่ทำ OT ────────────────────────────────────────
       อยู่แถวบนสุดของตัวกรอง แยกจากแท็บสถานะชัดเจน เพราะเป็นคนละแกน
       (สถานะ = รอ/อนุมัติ/ไม่อนุมัติ · วันที่ = ทุกวัน หรือเจาะจงวัน) */
    .ota-datebar { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .6rem; }
    .ota-date-picker {
      min-height: 2.45rem; display: inline-flex; align-items: center; gap: .45rem; flex: 0 0 auto;
      padding: 0 .7rem; border: 1px solid var(--line-light); border-radius: 4px;
      background: var(--panel); color: var(--muted-light);
    }
    .ota-date-picker:focus-within { border-color: var(--moss); }
    .ota-date-picker.is-active { border-color: var(--moss); background: color-mix(in srgb, var(--moss) 10%, var(--panel)); color: var(--light-text); }
    .ota-date-picker svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.7; opacity: .8; }
    .ota-date-picker input {
      width: 8.9rem; border: 0; outline: 0; background: transparent;
      color: var(--light-text); color-scheme: light dark; font-size: .76rem; font-weight: 650;
    }
    .ota-date-all { min-height: 2.45rem; padding: .5rem .8rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); color: var(--muted-light); font-size: .72rem; font-weight: 650; white-space: nowrap; }
    .ota-date-all:hover { border-color: var(--line-strong); color: var(--light-text); }
    .ota-date-all.is-active { border-color: var(--moss); background: color-mix(in srgb, var(--moss) 10%, var(--panel)); color: var(--light-text); }
    .ota-date-hint { color: var(--muted-light); font-size: .7rem; font-weight: 600; }
    @media (max-width: 640px) {
      .ota-date-picker { flex: 1 1 100%; justify-content: space-between; }
      .ota-date-picker input { width: auto; flex: 1 1 auto; }
    }

    {{-- แท็บสถานะกลายเป็น dropdown แล้ว CSS ของ .ota-tabs/.ota-tab จึงถูกถอดทิ้ง (2026-08-24) --}}
    .ota-notice { margin-bottom: .75rem; padding: .7rem .8rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); font-size: .75rem; }
    .ota-notice[hidden] { display: none; }
    .ota-notice.is-error { border-color: color-mix(in srgb, var(--danger) 40%, transparent); color: var(--danger); }
    .ota-paper { border: 1px solid var(--line-strong); border-radius: 4px; background: var(--panel); box-shadow: 0 .55rem 1.6rem rgb(0 0 0 / 6%); overflow: clip; }
    .ota-pager-wrap { padding: 0 1rem .35rem; }
    .ota-paper-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1rem; border-bottom: 1px solid var(--line-strong); }
    .ota-paper-head strong { font-size: .82rem; }
    .ota-paper-head span { color: var(--muted-light); font-size: .68rem; }
    .ota-scroll { overflow: auto; }
    /* ── ผังคอลัมน์: ต้องอ่านจบในจอเดียว ไม่ต้องเลื่อนซ้าย-ขวา (Manager สั่ง 2026-08-24) ──
       ของเดิมตรึงเป็น rem รวมกัน ~125rem จึงล้นจอเสมอ · เปลี่ยนมาเป็น % ทั้งหมด
       เหลือ min-width ไว้เท่าที่จอมือถือยังอ่านออก (ต่ำกว่านั้นค่อยเลื่อน) */
    .ota-table { width: 100%; min-width: 62rem; border-collapse: collapse; table-layout: fixed; }
    .ota-table th, .ota-table td { padding: .65rem .55rem; border-right: 1px solid var(--line-light); border-bottom: 1px solid var(--line-light); vertical-align: middle; text-align: center; }
    .ota-table th:last-child, .ota-table td:last-child { border-right: 0; }
    .ota-table tbody tr:last-child td { border-bottom: 0; }
    /* ใช้ class แทนตำแหน่ง เพราะคอลัมน์ช่องติ๊กมีบ้างไม่มีบ้างตามแท็บที่เลือกอยู่ */
    .ota-table tbody td.ota-cell-left { text-align: left; }
    .ota-table th { background: var(--panel-soft); color: var(--muted-light); font-size: .65rem; font-weight: 700; }
    .ota-table td { font-size: .75rem; }
    /* สัดส่วนคอลัมน์รวมกัน = 100% พอดี แก้ตรงนี้ที่เดียวเมื่อจะปรับผัง
       (ตอนไม่มีคอลัมน์ติ๊ก ที่เหลือจะยืดตามสัดส่วนเดิมให้เอง) */
    .ota-check-col { width: 2.5%; }
    .ota-no-col { width: 3%; color: var(--muted-light); font-variant-numeric: tabular-nums; }
    .ota-check { width: 1.05rem; height: 1.05rem; accent-color: var(--moss); cursor: pointer; vertical-align: middle; }
    .ota-table tbody tr.is-picked td { background: color-mix(in srgb, var(--moss) 7%, transparent); }
    .ota-date-col { width: 5.5%; }
    .ota-person-col { width: 13%; }
    .ota-dept-col { width: 9%; }
    .ota-shift-col { width: 7.5%; }
    .ota-ot-col { width: 9%; }
    .ota-type-col { width: 10%; }
    .ota-clock-col { width: 5%; }
    /* พื้นเหลืองของคอลัมน์เวลาเข้า-ออก ใช้ชุดเดียวกันทุกหน้า */
    /* ── 3 คอลัมน์ `กะงาน` + `เวลาเข้างาน` + `เวลาล่าสุด/ออกงาน` เป็นแถบสีเดียวกัน ──────
       ชุดเดียวกับหน้าขอ OT (Manager สั่ง 2026-08-24): กะเวลา A ส้ม 30% · กะเวลา B ม่วง 26%
       เลิกใช้พื้นเหลืองของคอลัมน์เวลาสแกนบนหน้านี้แล้ว เพราะทำให้ดูเป็นคนละก้อนกับคอลัมน์กะ */
    .ota-table td.is-shift-morning { background: color-mix(in srgb, #e8830c 30%, transparent); }
    .ota-table td.is-shift-night { background: color-mix(in srgb, #6d28d9 26%, transparent); }
    .ota-status-col { width: 8%; }
    .ota-reason-col { width: 5.5%; }
    .ota-action-col { width: 7%; }
    .ota-actor-col { width: 10%; }
    /* หัวคอลัมน์ยาว (เช่น `ผลจากลักษณะการรูดบัตร`) ขึ้นบรรทัดใหม่ในช่องตัวเอง
       ห้ามดันความกว้างคอลัมน์จนตารางล้นจอ */
    .ota-table th { line-height: 1.3; overflow-wrap: anywhere; }
    .ota-table td { overflow-wrap: anywhere; }
    .ota-type-copy { display: block; line-height: 1.45; overflow-wrap: anywhere; text-align: center; }
    /* ย่อรูปกับช่องไฟลง เพื่อให้ชื่อ-รหัสมีที่พอในคอลัมน์ที่แคบลงหลังจัดผังใหม่ */
    .ota-person { display: grid; grid-template-columns: 2.1rem minmax(0, 1fr); align-items: center; gap: .45rem; }
    .ota-avatar { width: 2.1rem; height: 2.1rem; display: grid; place-items: center; border: 1px solid var(--line-light); border-radius: 50%; background: var(--hover-soft); color: var(--muted-light); object-fit: cover; overflow: hidden; }
    .ota-modal-head-main { display: flex; align-items: center; gap: .9rem; min-width: 0; }
    .ota-modal-head-copy { min-width: 0; }
    .ota-modal-photo { display: grid; place-items: center; flex: 0 0 auto; }
    .ota-modal-photo .ota-avatar { width: 3.6rem; height: 3.6rem; font-size: 1.05rem; font-weight: 650; }

    /* ── กดรูปพนักงานเพื่อดูแบบขยาย ─────────────────────── */
    img.ota-avatar { cursor: zoom-in; transition: transform .18s ease; }
    img.ota-avatar:hover { transform: scale(1.08); }

    .ota-lightbox {
      position: fixed;
      z-index: 140;                 /* ต้องสูงกว่าโมดัลยืนยัน (120) และเหตุผล (110) */
      inset: 0;
      display: grid;
      place-items: center;
      padding: clamp(1rem, 4vw, 3rem);
      background: rgb(0 0 0 / 78%);
      -webkit-backdrop-filter: blur(3px);
      backdrop-filter: blur(3px);
      cursor: zoom-out;
      animation: ota-lightbox-in .16s ease;
    }
    .ota-lightbox[hidden] { display: none; }

    @keyframes ota-lightbox-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .ota-lightbox-figure {
      max-width: min(100%, 34rem);
      display: grid;
      gap: .75rem;
      justify-items: center;
      margin: 0;
      cursor: default;
      animation: ota-lightbox-zoom .2s ease;
    }

    @keyframes ota-lightbox-zoom {
      from { opacity: 0; transform: scale(.94); }
      to { opacity: 1; transform: none; }
    }

    .ota-lightbox-figure img {
      max-width: 100%;
      max-height: min(72vh, 34rem);
      border-radius: 6px;
      background: var(--panel);
      box-shadow: 0 2rem 4rem rgb(0 0 0 / 45%);
      object-fit: contain;
    }

    .ota-lightbox-caption { display: grid; gap: .1rem; color: #fff; font-size: .9rem; text-align: center; }
    .ota-lightbox-caption small { color: rgb(255 255 255 / 68%); font-size: .78rem; }

    .ota-lightbox-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 2.6rem;
      height: 2.6rem;
      display: grid;
      place-items: center;
      border: 1px solid rgb(255 255 255 / 28%);
      border-radius: 50%;
      background: rgb(255 255 255 / 10%);
      color: #fff;
      cursor: pointer;
    }
    .ota-lightbox-close:hover { background: rgb(255 255 255 / 20%); }
    .ota-lightbox-close svg { width: 1.1rem; fill: none; stroke: currentColor; stroke-width: 2; }

    .ota-person-copy { min-width: 0; text-align: left; }
    .ota-person-copy strong { display: block; overflow: hidden; font-size: .8rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
    .ota-person-copy small, .ota-muted { color: var(--muted-light); font-size: .65rem; }
    .ota-ellipsis { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ota-ot strong, .ota-time strong { display: block; font-size: .76rem; font-weight: 650; font-variant-numeric: tabular-nums; }
    .ota-ot small, .ota-time small { display: block; color: var(--muted-light); font-size: .64rem; font-variant-numeric: tabular-nums; }
    /* ในตาราง: กล่องสถานะกว้างเท่าช่อง แล้วป้ายข้างในหดตามได้
       ของเดิมเป็น inline-flex ที่กว้างตามเนื้อหา + ป้ายตรึง 6.6rem ป้ายจึงล้นออกนอกคอลัมน์
       หลังจัดผังคอลัมน์เป็น % (Manager แจ้ง 2026-08-24) */
    .ota-table .ota-status { display: flex; flex-direction: column; align-items: center; gap: .18rem; width: 100%; max-width: 100%; min-width: 0; }
    .ota-table .ota-status-badge { width: auto; max-width: 100%; height: auto; min-height: 1.5rem; padding: .2rem .4rem; line-height: 1.25; white-space: normal; overflow-wrap: anywhere; }
    .ota-table .ota-status-detail { text-align: center; }

    /* ป้ายสถานะ = แท็กวงรี ความกว้างคงที่เท่ากันทุกสถานะ ตัวอักษรกึ่งกลาง
       6.6rem เผื่อคำยาวสุดของทั้ง 3 ภาษา (สเปกเดียวกันทั้ง 3 หน้า OT) */
    .ota-status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 6.6rem;
      max-width: 100%;
      height: 1.5rem;
      flex: 0 0 auto;
      padding: 0 .4rem;
      border: 1px solid transparent;
      border-radius: 999px;
      background: transparent;
      font-size: .64rem;
      font-weight: 650;
      line-height: 1;
      overflow: hidden;
      text-align: center;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .ota-status-detail { display: block; min-width: 0; color: var(--light-text); font-size: .62rem; font-weight: 500; line-height: 1.3; }
    .ota-status[data-tone="warning"] .ota-status-badge {
      border-color: var(--ota-status-warning-border);
      background: var(--ota-status-warning-border);
      color: #fff;
    }
    .ota-status[data-tone="success"] .ota-status-badge {
      border-color: var(--ota-status-success-border);
      background: var(--ota-status-success-border);
      color: #fff;
    }
    .ota-status[data-tone="danger"] .ota-status-badge {
      border-color: var(--ota-status-danger-border);
      background: var(--ota-status-danger-border);
      color: #fff;
    }
    .ota-status[data-tone="neutral"] .ota-status-badge {
      width: auto;
      height: auto;
      display: inline;
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      color: var(--muted-light);
      font-weight: 500;
      overflow: visible;
      text-overflow: clip;
    }
    .ota-review { min-height: 2.15rem; padding: .4rem .65rem; border: 1px solid var(--moss); border-radius: 4px; background: transparent; color: var(--moss); font-size: .7rem; font-weight: 700; }
    /* ปุ่ม `เหตุผล` เคยชิดขอบคอลัมน์จนดูอึดอัด — ขยายคอลัมน์เล็กน้อยและย่อปุ่มลง
       ให้มีช่องไฟรอบปุ่มจริง ๆ (Manager แจ้ง 2026-08-24) */
    .ota-table td.ota-reason-cell { padding-left: .3rem; padding-right: .3rem; }
    .ota-reason-button { min-height: 1.9rem; max-width: 100%; padding: .32rem .4rem; border: 1px solid var(--line-strong); border-radius: 4px; background: transparent; color: var(--light-text); font-size: .66rem; font-weight: 700; white-space: nowrap; }
    .ota-reason-button.is-cancelled { border-color: var(--line-light); color: var(--muted-light); }
    .ota-reason-button:hover { border-color: var(--moss); color: var(--moss); }
    .ota-loading, .ota-empty { min-height: 17rem; display: grid; place-items: center; padding: 2rem; color: var(--muted-light); font-size: .8rem; text-align: center; }
    .ota-spinner { width: 1.2rem; height: 1.2rem; margin: 0 auto .55rem; border: 2px solid var(--line-light); border-top-color: var(--moss); border-radius: 50%; animation: ota-spin .7s linear infinite; }

    .ota-modal { position: fixed; z-index: 100; inset: 0; display: grid; place-items: center; padding: 1rem; background: var(--overlay-bg); backdrop-filter: blur(4px); }
    .ota-modal[hidden] { display: none; }
    .ota-dialog { width: min(100%, 38rem); border: 1px solid var(--line-strong); border-radius: 4px; background: var(--menu-bg); box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 32%); overflow: hidden; }
    .ota-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--line-light); }
    .ota-modal-head small { color: var(--moss); font-size: .65rem; font-weight: 800; }
    .ota-modal-head h2 { margin-top: .12rem; font-size: 1rem; font-weight: 650; }
    .ota-close { width: 2.35rem; height: 2.35rem; border: 1px solid var(--line-light); border-radius: 50%; background: transparent; color: var(--muted-light); font-size: 1.1rem; }
    .ota-modal-body { display: grid; gap: .8rem; padding: 1rem; }
    .ota-detail { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; padding: .75rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); }
    .ota-detail small { display: block; color: var(--muted-light); font-size: .61rem; }
    .ota-detail strong { display: block; margin-top: .08rem; font-size: .75rem; font-weight: 650; }
    .ota-note { display: grid; gap: .3rem; }
    .ota-note label { color: var(--muted-light); font-size: .68rem; font-weight: 650; }
    .ota-note textarea { min-height: 5rem; padding: .55rem .65rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); color: var(--light-text); font: inherit; font-size: .76rem; resize: vertical; }
    .ota-request-note, .ota-reason-item { padding: .75rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); }
    .ota-request-note small, .ota-reason-item small { display: block; margin-bottom: .3rem; color: var(--muted-light); font-size: .66rem; font-weight: 700; }
    .ota-request-note p, .ota-reason-item p { margin: 0; color: var(--light-text); font-size: .78rem; line-height: 1.65; overflow-wrap: anywhere; white-space: pre-wrap; }
    .ota-actions { display: flex; justify-content: flex-end; gap: .5rem; padding: .85rem 1rem; border-top: 1px solid var(--line-light); }
    .ota-approve, .ota-reject { min-height: 2.45rem; padding: .55rem .8rem; border-radius: 4px; font-size: .73rem; font-weight: 750; }
    /* ปุ่มพื้นเขียว ใช้ตัวอักษรสีขาวเสมอทั้งธีมสว่างและมืด */
    /* ปุ่มอนุมัติในโมดัล `ตรวจคำขอ` ใช้เขียว #35a863 ชุดเดียวกับป้ายสถานะ `อนุมัติแล้ว`
       และปุ่ม `อนุมัติที่เลือก` (Manager สั่ง 2026-08-24) — คู่กับปุ่มไม่อนุมัติสีแดง */
    .ota-approve { border: 1px solid #35a863; background: #35a863; color: #fff; }
    .ota-approve:hover:not(:disabled) { background: color-mix(in srgb, #35a863 86%, #000); }
    .ota-reject { border: 1px solid color-mix(in srgb, var(--danger) 60%, transparent); background: transparent; color: var(--danger); }
    /* ปุ่มยืนยันในโมดัลใช้ตัวเดียวกัน สลับเป็นแดงทึบเมื่อเป็นการยืนยัน "ไม่อนุมัติ" */
    .ota-confirm-danger { border-color: var(--danger); background: var(--danger); color: #fff; }
    .ota-secondary { min-height: 2.45rem; padding: .55rem .8rem; border: 1px solid var(--line-strong); border-radius: 4px; background: transparent; color: var(--light-text); font-size: .73rem; font-weight: 700; }
    .ota-approve:disabled, .ota-reject:disabled { opacity: .45; }
    .ota-confirm-modal { z-index: 120; }
    .ota-reason-modal { z-index: 110; }
    .ota-small-dialog { width: min(100%, 31rem); }
    .ota-confirm-copy { margin: 0; color: var(--muted-light); font-size: .76rem; line-height: 1.6; }
    .ota-note-toggle { display: inline-flex; align-items: center; gap: .5rem; width: fit-content; color: var(--light-text); font-size: .75rem; font-weight: 650; }
    .ota-note-toggle input { width: 1rem; height: 1rem; accent-color: var(--moss); }
    .ota-confirm-error { padding: .6rem .7rem; border: 1px solid color-mix(in srgb, var(--danger) 40%, transparent); border-radius: 4px; color: var(--danger); font-size: .72rem; }
    .ota-confirm-error[hidden], .ota-note[hidden] { display: none; }
    .ota-reason-body { display: grid; gap: .75rem; padding: 1rem; }
    @keyframes ota-spin { to { transform: rotate(360deg); } }
    @media (max-width: 48rem) {
      .ota-head { align-items: flex-start; flex-direction: column; }
      .ota-bulk { width: 100%; flex-wrap: wrap; }
      .ota-bulk-count { width: 100%; text-align: right; }
      .ota-bulk-action { flex: 1 1 9rem; }
      .ota-modal { align-items: end; padding: .4rem; }
      .ota-dialog { border-radius: 4px 10px 6px 6px; }
    }
    @media (prefers-reduced-motion: reduce) { .ota-spinner { animation: none; } }
  </style>

  <main
    class="ota-page"
    data-ot-approvals
    data-queue-url="{{ route('ot-approval.approvals.data') }}"
    data-decision-url="{{ route('ot-approval.approvals.decision', ['otRequest' => '__REQUEST__']) }}"
    data-bulk-decision-url="{{ route('ot-approval.approvals.bulk-decision') }}"
    data-page-url="{{ route('ot-approval.approvals.index') }}"
    data-selected-date="{{ $selectedDate?->format('Y-m-d') }}"
  >
    <header class="ota-head">
      <div class="ota-heading">
        {{-- ไม่มี kicker `OT APPROVAL` และปุ่ม `รีเฟรชข้อมูล` แล้ว (Manager สั่ง 2026-08-24)
             หน้านี้รีเฟรชเองอัตโนมัติทุก 30 วิอยู่แล้ว ปุ่มจึงซ้ำซ้อน --}}
        <h1 data-i18n="ot.approvals.heading">อนุมัติ OT</h1>
        <p data-i18n="ot.approvals.description">ตรวจคำขอ เวลาเข้า–ออก และผลการทำ OT ของแผนกที่รับผิดชอบ</p>
      </div>
    </header>

    {{-- แบ่งงานอนุมัติเป็นวัน ๆ — ค่าเริ่มต้นคือทุกวัน จึงไม่มีคำขอค้างหลุดสายตา --}}
    <div class="ota-datebar" aria-label="วันที่ทำ OT">
      <label class="ota-date-picker {{ $selectedDate ? 'is-active' : '' }}" data-date-wrap>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>
        <span class="sr-only" data-i18n="ot.attendance.selectDate">เลือกวันที่</span>
        <input type="date" value="{{ $selectedDate?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" data-date-filter aria-label="เลือกวันที่ทำ OT">
      </label>
      <button class="ota-date-all {{ $selectedDate ? '' : 'is-active' }}" type="button" data-date-all data-i18n="ot.approvals.allDates">ทุกวัน</button>
      <span class="ota-date-hint" data-date-hint></span>
    </div>

    <div class="ota-toolbar">
      {{-- ตัวกรองอยู่ **ทั้งสองที่** (Manager สั่งเอาของเดิมกลับมา 2026-08-24):
           แถบเครื่องมือนี้ + เมนูที่หัวคอลัมน์ · ทั้งคู่อ่าน/เขียนตัวแปรชุดเดียวกัน
           เปลี่ยนที่ไหนก็มีผลเหมือนกัน และวาดใหม่แล้วค่าตรงกันเสมอ --}}
      <label class="ot-type-filter ota-status-filter">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v6l-4 2v-8Z"></path></svg>
        <select data-filter-select aria-label="กรองตามสถานะคำขอ">
          <option value="pending" data-i18n="ot.status.pending">รอดำเนินการ</option>
          <option value="approved" data-i18n="ot.approvals.approvedTab">อนุมัติแล้ว</option>
          <option value="rejected" data-i18n="ot.status.rejected">ไม่อนุมัติ</option>
          <option value="cancelled" data-i18n="ot.status.cancelled">ยกเลิก</option>
          <option value="all" data-i18n="ot.approvals.allTab">ทั้งหมด</option>
        </select>
      </label>
      @include('ot_approval.partials.employee-search')
      {{-- ตัวกรองประเภท OT ย้ายไปอยู่ที่หัวคอลัมน์ `ผลจากลักษณะการรูดบัตร` แล้ว (Manager สั่ง 2026-08-24)
           ยังกรองที่เซิร์ฟเวอร์เหมือนเดิม เพราะคิวแบ่งหน้า กรองในเครื่องจะได้แค่หน้าที่เปิดอยู่ --}}
      @include('ot_approval.partials.shift-filter')
      @include('ot_approval.partials.branch-filter')
      @include('ot_approval.partials.column-filter')
      {{-- ตัดสินหลายรายการที่ติ๊กเลือกไว้ในตาราง อยู่แถวเดียวกับแท็บแต่ชิดขวา --}}
      <div class="ota-bulk" data-bulk-wrap hidden>
        <span class="ota-bulk-count" data-bulk-count hidden></span>
        <button class="ota-bulk-action ota-bulk-reject" type="button" data-bulk-reject disabled data-i18n="ot.approvals.bulkReject">ปฏิเสธที่เลือก</button>
        <button class="ota-bulk-action ota-bulk-approve" type="button" data-bulk-approve disabled data-i18n="ot.approvals.bulkApprove">อนุมัติที่เลือก</button>
      </div>
    </div>

    <div class="ota-notice" data-notice role="status" aria-live="polite" hidden></div>
    <section class="ota-paper">
      <header class="ota-paper-head">
        <strong data-i18n="ot.approvals.documentTitle">รายการขออนุมัติทำงานล่วงเวลา</strong>
        <span><span data-result-count>0</span> <span data-i18n="ot.approvals.items">รายการ</span></span>
      </header>
      <div data-queue-content></div>
      <div class="ota-pager-wrap">
        @include('ot_approval.partials.table-pager')
      </div>
    </section>
  </main>

  <div class="ota-modal" data-decision-modal role="dialog" aria-modal="true" aria-labelledby="otaModalTitle" hidden>
    <div class="ota-dialog">
      <header class="ota-modal-head">
        <div class="ota-modal-head-main">
          {{-- รูปพนักงานของคำขอนี้ อยู่ซ้ายสุดของหัวโมดัล --}}
          <span class="ota-modal-photo" data-modal-photo></span>
          <div class="ota-modal-head-copy">
            <small data-i18n="ot.approvals.reviewLabel">ตรวจคำขอ OT</small>
            <h2 id="otaModalTitle" data-modal-title>—</h2>
          </div>
        </div>
        <button class="ota-close" type="button" data-close-decision aria-label="ปิด">×</button>
      </header>
      <div class="ota-modal-body">
        <section class="ota-detail">
          <span><small data-i18n="ot.requests.workDate">วันที่ทำ OT</small><strong data-detail-date>—</strong></span>
          <span><small data-i18n="ot.requests.otType">ผลจากลักษณะการรูดบัตร</small><strong data-detail-type>—</strong></span>
          <span><small data-i18n="ot.requests.timeRange">ช่วงเวลา OT</small><strong data-detail-ot>—</strong></span>
          <span><small data-i18n="ot.approvals.scanTime">เวลาสแกน</small><strong data-detail-scan>—</strong></span>
          <span><small data-i18n="ot.attendance.status">สถานะ</small><strong data-detail-status>—</strong></span>
          <span><small data-i18n="ot.requests.department">แผนก</small><strong data-detail-dept>—</strong></span>
        </section>
        <section class="ota-request-note">
          <small data-i18n="ot.reason.requestNote">หมายเหตุจาก Foreman</small>
          <p data-detail-request-note>—</p>
        </section>
      </div>
      <footer class="ota-actions">
        <button class="ota-reject" type="button" data-decision="rejected" data-i18n="ot.approvals.reject">ไม่อนุมัติ</button>
        <button class="ota-approve" type="button" data-decision="approved" data-i18n="ot.approvals.approve">อนุมัติ OT</button>
      </footer>
    </div>
  </div>

  <div class="ota-modal ota-confirm-modal" data-confirm-modal role="dialog" aria-modal="true" aria-labelledby="otaConfirmTitle" aria-describedby="otaConfirmCopy" hidden>
    <div class="ota-dialog ota-small-dialog">
      <header class="ota-modal-head">
        <div>
          <small data-i18n="ot.approvals.confirmLabel">ยืนยันผล</small>
          <h2 id="otaConfirmTitle" data-confirm-title>—</h2>
        </div>
        <button class="ota-close" type="button" data-close-confirm data-i18n-aria="ot.reason.close" aria-label="ปิด">×</button>
      </header>
      <div class="ota-modal-body">
        <p class="ota-confirm-copy" id="otaConfirmCopy" data-confirm-copy>—</p>
        <label class="ota-note-toggle">
          <input type="checkbox" data-note-toggle>
          <span data-note-toggle-label>—</span>
        </label>
        <div class="ota-note" data-confirm-note-wrap hidden>
          <label for="otaConfirmNote" data-confirm-note-label>—</label>
          <textarea id="otaConfirmNote" maxlength="1000" data-confirm-note></textarea>
        </div>
        <div class="ota-confirm-error" data-confirm-error role="alert" hidden></div>
      </div>
      <footer class="ota-actions">
        <button class="ota-secondary" type="button" data-close-confirm data-i18n="ot.common.cancel">ยกเลิก</button>
        <button class="ota-approve" type="button" data-confirm-decision>—</button>
      </footer>
    </div>
  </div>

  <div class="ota-modal ota-reason-modal" data-reason-modal role="dialog" aria-modal="true" aria-labelledby="otaReasonTitle" hidden>
    <div class="ota-dialog ota-small-dialog">
      <header class="ota-modal-head">
        <div class="ota-modal-head-main">
          {{-- รูปพนักงานของคำขอนี้ อยู่ซ้ายสุดของหัวโมดัล --}}
          <span class="ota-modal-photo" data-reason-photo></span>
          <div class="ota-modal-head-copy">
            <small data-i18n="ot.reason.button">เหตุผล</small>
            <h2 id="otaReasonTitle" data-reason-title>—</h2>
          </div>
        </div>
        <button class="ota-close" type="button" data-close-reason data-i18n-aria="ot.reason.close" aria-label="ปิด">×</button>
      </header>
      <div class="ota-reason-body">
        <section class="ota-reason-item">
          <small data-i18n="ot.reason.requestNote">หมายเหตุจาก Foreman</small>
          <p data-reason-request>—</p>
        </section>
        <section class="ota-reason-item">
          <small data-i18n="ot.reason.decisionNote" data-reason-decision-label>หมายเหตุจาก Supervisor</small>
          <p data-reason-decision>—</p>
        </section>
      </div>
      <footer class="ota-actions">
        <button class="ota-secondary" type="button" data-close-reason data-i18n="ot.reason.close">ปิด</button>
      </footer>
    </div>
  </div>

  {{-- ดูรูปพนักงานแบบขยาย ใช้ได้ทั้งในตารางและในหัวโมดัล --}}
  <div class="ota-lightbox" data-ota-lightbox role="dialog" aria-modal="true" aria-label="รูปพนักงาน" data-i18n-aria="ot.route.zoomPhoto" hidden>
    <button class="ota-lightbox-close" type="button" data-lightbox-close aria-label="ปิด" data-i18n-aria="ot.common.close">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
    </button>
    <figure class="ota-lightbox-figure">
      <img data-lightbox-image src="" alt="">
      <figcaption class="ota-lightbox-caption" data-lightbox-caption hidden></figcaption>
    </figure>
  </div>

  @include('ot_approval.partials.process-route')
  @include('ot_approval.partials.success-feedback')

  <script>
    'use strict';

    (function () {
      var root = document.querySelector('[data-ot-approvals]');
      if (!root) return;
      var queueUrl = root.dataset.queueUrl;
      var employeeSearchInput = root.querySelector('[data-emp-search]');
      var employeeQuery = '';   // ค้นรหัส/ชื่อ ส่งไปกรองที่เซิร์ฟเวอร์
      var branchFilterValue = 'all';   // สาขา (โรงงาน / โรงงาน-พม่า) กรองที่เซิร์ฟเวอร์เช่นกัน
      var otTypeFilterValue = 'all';   // ประเภท OT กรองที่เซิร์ฟเวอร์เช่นกัน (ตัวกรองอยู่ที่หัวคอลัมน์)
      /* ตัวเลือกของตัวกรองประเภท OT — มาจาก config ทั้งชุด ป้ายสลับตามภาษาที่เปิดอยู่ */
      /* ห่อ Object.values() ไว้เผื่อ types() เปลี่ยนไปคืน array แบบมีคีย์ (ฝั่งลาเคยพังมาแล้ว) */
      var otTypeList = Object.values(@json($otTypes ?? []));
      function otTypeOptions() {
        var options = [{ value: 'all', label: text('ot.approvals.allTypes', 'ทุกประเภท OT') }];
        otTypeList.forEach(function (type) {
          options.push({ value: type.key, label: localized(type, 'label', type.key) });
        });

        return options;
      }

      /* ตัวเลือกของตัวกรองที่หัวคอลัมน์ — ทุกตัวส่งค่าไปกรองที่เซิร์ฟเวอร์
         `สถานะ` ใช้ค่าเดียวกับพารามิเตอร์ `filter` เดิม (ไม่มีค่า 'all' แยก เพราะมีตัวเลือก `ทั้งหมด` อยู่แล้ว) */
      function statusOptions() {
        return [
          { value: 'pending', label: text('ot.status.pending', 'รอดำเนินการ') },
          { value: 'approved', label: text('ot.approvals.approvedTab', 'อนุมัติแล้ว') },
          { value: 'rejected', label: text('ot.status.rejected', 'ไม่อนุมัติ') },
          { value: 'cancelled', label: text('ot.status.cancelled', 'ยกเลิก') },
          { value: 'all', label: text('ot.approvals.allTab', 'ทั้งหมด') }
        ];
      }

      /* กะและสาขาอ่านตัวเลือกจาก <select> ที่ partial สร้างไว้แล้ว (ยังซ่อนอยู่ในหน้า)
         จะได้ไม่ต้องทำรายการซ้ำสองชุดให้หลุดกันภายหลัง */
      function optionsFromSelect(selector, fallbackLabel) {
        var select = root.querySelector(selector);
        if (!select) return [{ value: 'all', label: fallbackLabel }];

        return Array.prototype.map.call(select.options, function (option) {
          return { value: option.value, label: option.textContent };
        });
      }

      function shiftOptions() { return optionsFromSelect('[data-shift-filter]', text('ot.shiftFilter.all', 'ทุกกะ')); }
      function branchOptions() { return optionsFromSelect('[data-branch-filter]', text('ot.branch.all', 'ทุกสาขา')); }
      var decisionTemplate = root.dataset.decisionUrl;
      var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      var content = root.querySelector('[data-queue-content]');
      var modal = document.querySelector('[data-decision-modal]');
      var confirmModal = document.querySelector('[data-confirm-modal]');
      var reasonModal = document.querySelector('[data-reason-modal]');
      var activeFilter = 'pending';
      var activeDate = root.dataset.selectedDate || '';   // '' = ทุกวัน
      var pageUrl = root.dataset.pageUrl;
      var dateInput = root.querySelector('[data-date-filter]');
      var dateWrap = root.querySelector('[data-date-wrap]');
      var dateAllButton = root.querySelector('[data-date-all]');
      var dateHint = root.querySelector('[data-date-hint]');
      var activeRequest = null;
      var pendingDecision = null;
      var lastRequests = [];
      var lastMeta = null;   // meta ของการโหลดล่าสุด ใช้ตอนวาดใหม่โดยไม่ยิง API   // ชุดที่มองเห็นหลังกรอง ใช้นับในแถบอนุมัติหลายรายการ
      var allRequests = [];    // ชุดดิบก่อนกรอง ใช้สร้างตัวเลือกกะและวาดใหม่
      var confirmationTrigger = null;
      var reasonPreviousFocus = null;
      var bulkUrl = root.dataset.bulkDecisionUrl;
      var bulkWrap = root.querySelector('[data-bulk-wrap]');
      var bulkBar = root.querySelector('[data-bulk-count]');
      var bulkButton = root.querySelector('[data-bulk-approve]');
      var bulkRejectButton = root.querySelector('[data-bulk-reject]');
      var shiftFilterSelect = root.querySelector('[data-shift-filter]');
      var shiftFilterValue = window.otShiftFilter.ALL;   // จำค่าไว้ กันเด้งกลับตอนรีเฟรชอัตโนมัติทุก 30 วิ
      var selectedIds = [];        // id ที่ติ๊กไว้ในตาราง (เฉพาะรายการที่ยังรออนุมัติ)
      var bulkPending = null;      // ล็อต id ที่กำลังรอยืนยันในโมดัล ถ้าเป็น null = โหมดทีละรายการ

      function text(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
      }
      function localized(item, field, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return item[field + '_' + lang] || item[field + '_en'] || item[field + '_th'] || fallback || '—';
      }
      function employeeName(request) {
        return localized(request, 'employee_name', request.employee_name || request.employee_code || '');
      }
      function durationText(request) {
        return request.requested_hours + ' ' + text('ot.requests.hour', 'ชั่วโมง') + ' ' +
          (request.requested_minutes || 0) + ' ' + text('ot.requests.minute', 'นาที');
      }
      function localAssetUrl(value) {
        if (!value) return '';
        try {
          var url = new URL(value, window.location.href);
          return ['localhost', '127.0.0.1'].indexOf(url.hostname) !== -1 ? window.location.origin + url.pathname + url.search : url.href;
        } catch (error) { return value; }
      }
      async function fetchJson(url, options) {
        var response = await fetch(url, Object.assign({ headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }, options || {}));
        var payload = await response.json().catch(function () { return {}; });
        if (!response.ok) { var error = new Error(payload.message || 'HTTP ' + response.status); error.payload = payload; throw error; }
        return payload;
      }
      function showNotice(message, error) {
        var notice = root.querySelector('[data-notice]');
        notice.textContent = message;
        notice.classList.toggle('is-error', Boolean(error));
        notice.hidden = false;
        window.setTimeout(function () { notice.hidden = true; }, 4500);
      }
      function showSuccessFeedback(message) {
        if (window.otSuccessFeedback) {
          window.otSuccessFeedback.show(message || text('leave.done', 'ดำเนินการเรียบร้อยแล้ว'));
          return;
        }

        showNotice(message || text('leave.done', 'ดำเนินการเรียบร้อยแล้ว'), false);
      }
      function formatDate(value) {
        try { return new Intl.DateTimeFormat(document.documentElement.lang || 'th', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value + 'T00:00:00')); }
        catch (error) { return value; }
      }
      function statusPresentation(key) {
        var map = {
          not_eligible: ['-', '-', '', '', 'neutral'],
          not_requested: ['ot.status.notRequested', 'ไม่มีการขอ OT', '', '', 'neutral'],
          not_worked_ot: ['ot.status.notWorkedOt', 'ไม่ได้ทำ OT', '', '', 'neutral'],
          draft: ['ot.status.pending', 'รอดำเนินการ', '', '', 'warning'],
          pending: ['ot.status.pending', 'รอดำเนินการ', '', '', 'warning'],
          waiting_approval: ['ot.status.pending', 'รอดำเนินการ', '', '', 'warning'],
          approved_waiting_scan: ['ot.status.approved', 'อนุมัติ', '', '', 'success'],
          success: ['ot.status.success', 'ผ่าน', '', '', 'success'],
          failed_time: ['ot.status.rejected', 'ไม่อนุมัติ', '', '', 'danger'],
          rejected: ['ot.status.rejected', 'ไม่อนุมัติ', '', '', 'danger'],
          cancelled: ['ot.status.cancelled', 'ยกเลิก', '', '', 'neutral'],
          exported: ['ot.status.success', 'ผ่าน', '', '', 'success']
        };
        var item = map[key] || map.pending;
        return {
          label: item[0] === '-' ? '-' : text(item[0], item[1]),
          detail: item[2] ? text(item[2], item[3]) : '',
          tone: item[4]
        };
      }
      function statusElement(key, request) {
        var presentation = statusPresentation(key);
        if (request && request.approval_status === 'submitted' && request.can_decide === false) {
          presentation = { label: text('ot.status.rejected', 'ไม่อนุมัติ'), detail: '', tone: 'danger' };
        } else if (request && request.approval_status === 'submitted') {
          presentation = { label: text('ot.status.pending', 'รอดำเนินการ'), detail: '', tone: 'warning' };
        }
        var status = document.createElement('span');
        status.className = 'ota-status';
        status.dataset.status = key;
        status.dataset.tone = presentation.tone;
        status.setAttribute('aria-label', [presentation.label, presentation.detail].filter(Boolean).join(' '));
        var badge = document.createElement('span');
        badge.className = 'ota-status-badge';
        badge.textContent = presentation.label;
        badge.title = presentation.label;
        status.appendChild(badge);
        if (presentation.detail) {
          var detail = document.createElement('small');
          detail.className = 'ota-status-detail';
          detail.textContent = presentation.detail;
          status.appendChild(detail);
        }
        return status;
      }
      function renderLoading() { content.innerHTML = '<div class="ota-loading"><span><span class="ota-spinner" aria-hidden="true"></span>' + text('ot.approvals.loading', 'กำลังโหลดคำขอ') + '</span></div>'; }
      function avatar(request) {
        if (request.avatar) { var image = document.createElement('img'); image.className = 'ota-avatar'; image.src = localAssetUrl(request.avatar); image.alt = employeeName(request); image.loading = 'lazy'; return image; }
        var fallback = document.createElement('span'); fallback.className = 'ota-avatar'; fallback.textContent = (employeeName(request) || '?').charAt(0); return fallback;
      }
      function cell(row, node) { var td = document.createElement('td'); if (node instanceof Node) td.appendChild(node); else td.textContent = node || '-'; row.appendChild(td); return td; }
      function timeValue(value) {
        var time = document.createElement('span');
        time.className = 'ota-time';
        var copy = document.createElement('strong');
        copy.textContent = value || '-';
        time.appendChild(copy);
        return time;
      }
      function actorCell(request) {
        if (request.approval_status === 'cancelled') return '-';
        return window.otProcessRoute.create(request);
      }

      function requestNote(request) { return request.request_note || request.note || ''; }
      function cancelNote(request) { return request.cancel_reason || ''; }
      function decisionNote(request) {
        if (request.approval_status === 'cancelled') return cancelNote(request);
        if (request.decision_note) return request.decision_note;
        if (request.attendance_status === 'failed') return text('ot.status.failedTimeDetail', 'เวลา OT ไม่ครบ');
        if (request.approval_status === 'submitted' && request.can_decide === false) return text('ot.approvals.expired', 'พ้นกำหนดอนุมัติ');
        return '';
      }
      function reasonCell(request) {
        return request.approval_status === 'cancelled'
          ? (cancelNote(request) || '-')
          : reasonButton(request);
      }
      function actionCell(request) {
        if (canReview(request)) {
          var review = document.createElement('button');
          review.type = 'button';
          review.className = 'ota-review';
          review.textContent = text('ot.approvals.review', 'ตรวจคำขอ');
          review.addEventListener('click', function () { openDecision(request); });
          return review;
        }

        return request.approval_status === 'cancelled' ? reasonButton(request) : '-';
      }
      function reasonButton(request) {
        if (!requestNote(request) && !decisionNote(request)) return '-';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ota-reason-button';
        if (request.approval_status === 'cancelled') button.classList.add('is-cancelled');
        button.textContent = text('ot.reason.button', 'เหตุผล');
        button.addEventListener('click', function () { openReason(request); });
        return button;
      }

      /* ── ติ๊กเลือกเพื่ออนุมัติทีเดียว ─────────────────────
         เลือกได้เฉพาะรายการที่ยังรออนุมัติ (submitted) รายการที่ตัดสินไปแล้วไม่มีช่องติ๊ก
         รายการที่เวลาสแกนตกกฎก็ติ๊กไม่ได้ เพราะต้องรวมไปอยู่ฝั่งไม่อนุมัติแล้ว
         อนุมัติไปก็ออก V74 ไม่ได้จนกว่า HR จะแก้เวลาใน Bplus ให้ก่อน */
      function isSelectable(request) {
        return request.approval_status === 'submitted'
          && request.can_decide !== false
          && request.attendance_status !== 'failed';
      }

      function canReview(request) {
        return isSelectable(request) || request.approval_status === 'approved' || request.approval_status === 'rejected';
      }

      function syncBulkBar() {
        var pool = lastRequests.filter(isSelectable);
        var count = selectedIds.length;
        // แท็บ "อนุมัติแล้ว"/"ไม่อนุมัติ" ไม่มีอะไรให้อนุมัติซ้ำ ซ่อนปุ่มไปเลยจะได้ไม่ดูค้าง
        bulkWrap.hidden = pool.length === 0;
        bulkButton.disabled = count === 0;
        bulkRejectButton.disabled = count === 0;
        bulkButton.textContent = count > 0
          ? text('ot.approvals.bulkApprove', 'อนุมัติที่เลือก') + ' (' + count + ')'
          : text('ot.approvals.bulkApprove', 'อนุมัติที่เลือก');
        bulkRejectButton.textContent = count > 0
          ? text('ot.approvals.bulkReject', 'ปฏิเสธที่เลือก') + ' (' + count + ')'
          : text('ot.approvals.bulkReject', 'ปฏิเสธที่เลือก');
        /* ป้าย "เลือกแล้ว x/y" แสดงตลอดเวลาที่มีของให้เลือก ไม่ใช่โผล่ตอนติ๊กแล้วเท่านั้น
           (Manager สั่ง 2026-08-24 ให้เหมือนหน้าอนุมัติลา) — ยังไม่ติ๊กก็เห็นว่ามีกี่รายการที่ตัดสินได้ */
        bulkBar.hidden = pool.length === 0;
        // บอกแค่ "เลือกไปกี่รายการ" พอ (Manager สั่ง 2026-08-24) — ยอดทั้งหมดอ่านได้จากตารางอยู่แล้ว
        bulkBar.textContent = text('ot.approvals.selectedCount', 'เลือกแล้ว') + ' ' + count;

        var master = content.querySelector('[data-select-all]');
        if (master) {
          master.checked = pool.length > 0 && count === pool.length;
          master.indeterminate = count > 0 && count < pool.length;
        }
      }

      function toggleSelection(id, picked, row) {
        var at = selectedIds.indexOf(id);
        if (picked && at === -1) selectedIds.push(id);
        if (!picked && at !== -1) selectedIds.splice(at, 1);
        if (row) row.classList.toggle('is-picked', picked);
        syncBulkBar();
      }

      /* เปลี่ยนหน้า/จำนวนต่อหน้า = วาดใหม่จากชุดเดิมที่โหลดมาแล้ว ไม่ต้องยิง API ซ้ำ */
      var pager = window.otTablePager
        ? window.otTablePager.create(root.querySelector('[data-table-pager]'), {
            perPage: 20,
            onChange: function () { loadQueue(pager.page); },
          })
        : null;

      function renderQueue(rows, meta) {
        allRequests = rows;
        if (meta) lastMeta = meta;
        meta = meta || lastMeta;
        selectedIds = [];   // ล้างการเลือกทุกครั้งที่โหลดใหม่ กันอนุมัติผิดตัวหลังเปลี่ยนแท็บ

        /* ตัวเลือกกะสร้างจากยอดรวมทั้งคิวที่ server นับมา ไม่ใช่จากแถวของหน้านี้
           ไม่งั้นพอเปิดหน้า 2 ตัวเลือกกะอื่นจะหายจนกลับไปเลือกไม่ได้ */
        shiftFilterValue = window.otShiftFilter.buildFromCounts(
          shiftFilterSelect,
          (meta && meta.shifts) || [],
          shiftFilterValue
        );
        window.otShiftFilter.tint([root.querySelector('.ota-paper')], shiftFilterValue, rows);

        var total = meta && meta.total !== undefined ? Number(meta.total) : rows.length;
        root.querySelector('[data-result-count]').textContent = total;

        /* แถวที่ได้มาคือ "หน้าเดียว" ที่ server กรองและตัดมาแล้ว
           ปุ่มอนุมัติที่เลือกจึงทำงานทีละหน้า กันเผลออนุมัติรายการจากหน้าที่ยังไม่ได้ดู */
        var pageRequests = rows;
        lastRequests = pageRequests;   // syncBulkBar นับจากชุดที่มองเห็นเท่านั้น
        content.replaceChildren();
        if (!rows.length) {
          var empty = document.createElement('div');
          empty.className = 'ota-empty';
          empty.textContent = shiftFilterValue && shiftFilterValue !== window.otShiftFilter.ALL
            ? text('ot.shiftFilter.empty', 'ไม่พบพนักงานในกะที่เลือก')
            : text('ot.approvals.empty', 'ไม่มีรายการในสถานะนี้');
          content.appendChild(empty);
          syncBulkBar();
          return;
        }
        var scroll = document.createElement('div'); scroll.className = 'ota-scroll';
        // ลากด้วยเมาส์เพื่อเลื่อนดูคอลัมน์ที่ล้นจอ (ตัวจัดการอยู่ใน layouts/portal)
        scroll.setAttribute('data-drag-scroll', '');
        // แท็บที่ไม่มีรายการรออนุมัติเลย ไม่ต้องมีคอลัมน์ช่องติ๊กให้เหลือเป็นช่องว่าง
        var canPick = pageRequests.some(isSelectable);
        var table = document.createElement('table'); table.className = canPick ? 'ota-table has-check' : 'ota-table';
        var checkHead = canPick
          ? '<th class="ota-check-col"><input type="checkbox" class="ota-check" data-select-all aria-label="' + text('ot.approvals.selectAll', 'เลือกทั้งหมด') + '" title="' + text('ot.approvals.selectAll', 'เลือกทั้งหมด') + '"></th>'
          : '';
        table.innerHTML = '<thead><tr>' + checkHead + '<th class="ota-no-col">' + text('common.rowNo', 'ลำดับ') + '</th><th class="ota-date-col">' + text('ot.requests.workDate', 'วันที่') + '</th><th class="ota-person-col">' + text('ot.attendance.employee', 'พนักงาน') + '</th><th class="ota-dept-col">' + text('ot.requests.department', 'แผนก') + '</th><th class="ota-shift-col">' + text('ot.requests.shift', 'กะงาน') + '</th><th class="ota-clock-col">' + text('ot.attendance.clockIn', 'เวลาเข้างาน') + '</th><th class="ota-clock-col">' + text('ot.attendance.clockOut', 'เวลาล่าสุด / ออกงาน') + '</th><th class="ota-ot-col">' + text('ot.requests.timeRange', 'ช่วงเวลา OT') + '</th><th class="ota-type-col">' + text('ot.requests.otType', 'ผลจากลักษณะการรูดบัตร') + '</th><th class="ota-status-col">' + text('ot.attendance.status', 'สถานะ') + '</th><th class="ota-reason-col">' + text('ot.reason.button', 'เหตุผล') + '</th><th class="ota-action-col">' + text('ot.approvals.action', 'ดำเนินการ') + '</th><th class="ota-actor-col">' + text('ot.route.column', 'สถานะทั้งหมด') + '</th></tr></thead>';
        /* ตัวกรองที่หัวคอลัมน์ `ผลจากลักษณะการรูดบัตร` — เลือกได้ค่าเดียวและ **กรองที่เซิร์ฟเวอร์**
           รายการตัวเลือกมาจาก config ทั้งชุด ไม่ใช่จากแถวที่เห็น เพราะคิวแบ่งหน้า
           (กรองในเครื่องจะได้แค่ 20 แถวของหน้าที่เปิดอยู่ = หลอกตาผู้ใช้) */
        if (window.otColumnFilter) {
          var head = table.querySelector('thead');
          window.otColumnFilter.attachServerChoice(head.querySelector('.ota-type-col'), otTypeOptions(), otTypeFilterValue, function (value) {
            otTypeFilterValue = value;
            loadQueue(1);
          });
          /* สถานะ · กะงาน · แผนก ก็ย้ายมาไว้ที่หัวคอลัมน์เหมือนกัน (Manager สั่ง 2026-08-24)
             ทุกตัวส่งไปกรองที่เซิร์ฟเวอร์ ไม่ใช่กรองแถวที่โหลดมาแล้ว */
          window.otColumnFilter.attachServerChoice(head.querySelector('.ota-status-col'), statusOptions(), activeFilter, function (value) {
            activeFilter = value;
            loadQueue(1);
          });
          window.otColumnFilter.attachServerChoice(head.querySelector('.ota-shift-col'), shiftOptions(), shiftFilterValue, function (value) {
            shiftFilterValue = value;
            loadQueue(1);
          });
          window.otColumnFilter.attachServerChoice(head.querySelector('.ota-dept-col'), branchOptions(), branchFilterValue, function (value) {
            branchFilterValue = value;
            loadQueue(1);
          });
        }

        var body = document.createElement('tbody');
        // เลขที่ไล่ต่อเนื่องข้ามหน้า หน้า 2 จึงเริ่มที่ 21 ไม่ใช่ 1
        var start = pager ? pager.offset : 0;
        pageRequests.forEach(function (request, index) {
          var row = document.createElement('tr');
          if (canPick) {
            if (isSelectable(request)) {
              var pick = document.createElement('input');
              pick.type = 'checkbox';
              pick.className = 'ota-check';
              pick.dataset.pick = String(request.id);
              pick.setAttribute('aria-label', employeeName(request));
              pick.addEventListener('change', function () { toggleSelection(request.id, pick.checked, row); });
              cell(row, pick);
            } else {
              // อยู่ในแท็บ "ทั้งหมด" ปนกับรายการที่ตัดสินไปแล้ว อนุมัติซ้ำไม่ได้จึงเว้นว่าง
              cell(row, document.createTextNode(''));
            }
          }
          cell(row, document.createTextNode(String(start + index + 1))).classList.add('ota-no-col');
          cell(row, formatDate(request.work_date));
          var person = document.createElement('span'); person.className = 'ota-person'; person.appendChild(avatar(request));
          var copy = document.createElement('span'); copy.className = 'ota-person-copy';
          var name = document.createElement('strong'); name.textContent = employeeName(request);
          var code = document.createElement('small'); code.textContent = request.employee_code + ' · ' + (request.position_name || '-'); copy.append(name, code); person.appendChild(copy); cell(row, person).classList.add('ota-cell-left');
          var dept = document.createElement('span'); dept.className = 'ota-ellipsis'; dept.textContent = request.department_name || request.dept_code || '-'; dept.title = dept.textContent; cell(row, dept);
          /* `กะงาน` + `เวลาเข้างาน` + `เวลาล่าสุด/ออกงาน` เป็นแถบสีเดียวกันตามกะของแถวนั้น
             (รูปแบบเดียวกับหน้าขอ OT — Manager สั่ง 2026-08-24) กะเวลา A ส้ม · กะเวลา B ม่วง
             สีมาจากกะของแถว ไม่ใช่จากกะที่กำลังเลือกในตัวกรอง · แถวที่ไม่รู้กะปล่อยพื้นว่าง */
          var shiftTime = request.shift_in && request.shift_out ? request.shift_in + '–' + request.shift_out : '-';
          var shiftTone = request.shift_group === 'morning' || request.shift_group === 'night'
            ? 'is-shift-' + request.shift_group
            : '';
          cell(row, timeValue(shiftTime)).className = shiftTone;
          cell(row, timeValue(request.clock_in)).className = shiftTone;
          cell(row, timeValue(request.clock_out)).className = shiftTone;
          var ot = document.createElement('span'); ot.className = 'ota-ot'; var otTime = document.createElement('strong'); otTime.textContent = request.requested_start + '–' + request.requested_end; var otMeta = document.createElement('small'); otMeta.textContent = durationText(request); ot.append(otTime, otMeta); cell(row, ot);
          var otType = document.createElement('span'); otType.className = 'ota-type-copy'; otType.textContent = localized(request, 'ot_type_label', request.ot_type || '-'); otType.title = otType.textContent; cell(row, otType);
          cell(row, statusElement(request.status_key, request));
          cell(row, reasonCell(request)).classList.add('ota-reason-cell');
          cell(row, actionCell(request));
          cell(row, actorCell(request));
          body.appendChild(row);
        });
        table.appendChild(body); scroll.appendChild(table); content.appendChild(scroll);

        /* ติ๊กหัวตาราง = เลือกทั้งหมด กดอีกครั้ง = เอาออกทั้งหมด
           อ่าน master.checked เก็บไว้ก่อนวนลูป เพราะ syncBulkBar() เขียนทับค่านี้ตามจำนวนที่เลือก
           ถ้าอ่านสดในลูปจะกลายเป็นเลือกได้แค่แถวแรกแล้วปลดที่เหลือ */
        var master = table.querySelector('[data-select-all]');
        if (master) master.addEventListener('change', function () {
          var pickAll = master.checked;
          selectedIds = [];
          body.querySelectorAll('[data-pick]').forEach(function (pick) {
            pick.checked = pickAll;
            pick.closest('tr').classList.toggle('is-picked', pickAll);
            if (pickAll) selectedIds.push(Number(pick.dataset.pick));
          });
          syncBulkBar();
        });
        syncBulkBar();
      }
      /* โหลดทีละหน้าจาก server พร้อมส่งตัวกรองกะไปกรองที่ SQL
         คิวอนุมัติอาจมีเป็นพันแถว ถ้าดึงมาทั้งก้อนแล้ววาดทุกแถวหน้าจะค้าง */
      async function loadQueue(page) {
        renderLoading();
        var query = '?filter=' + encodeURIComponent(activeFilter)
          + '&page=' + encodeURIComponent(page || 1)
          + '&per_page=' + encodeURIComponent(pager ? pager.perPage : 20)
          + '&shift=' + encodeURIComponent(shiftFilterValue);
        if (activeDate) query += '&date=' + encodeURIComponent(activeDate);
        // ค้นหาต้องกรองที่เซิร์ฟเวอร์ เพราะคิวแบ่งหน้า กรองในเครื่องจะได้แค่หน้าที่เปิดอยู่
        if (employeeQuery.trim()) query += '&q=' + encodeURIComponent(employeeQuery.trim());
        // สาขาก็ต้องกรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกัน ไม่งั้นกรองได้แค่หน้าที่เปิดอยู่
        if (branchFilterValue && branchFilterValue !== 'all') query += '&branch=' + encodeURIComponent(branchFilterValue);
        // ประเภท OT ก็กรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกัน
        if (otTypeFilterValue && otTypeFilterValue !== 'all') query += '&ot_type=' + encodeURIComponent(otTypeFilterValue);
        try {
          var response = await fetchJson(queueUrl + query);
          if (pager) pager.apply(response);
          renderQueue(response.requests || [], response);
        } catch (error) {
          content.innerHTML = '<div class="ota-empty">' + text('ot.approvals.loadError', 'ไม่สามารถโหลดรายการได้') + '</div>';
        }
      }

      /* ── ปฏิทิน: ทุกวัน ⇄ เจาะจงวันที่ทำ OT ──────────────────────────
         เขียน ?date= ลง URL ด้วย เพื่อให้รีเฟรช/แชร์ลิงก์แล้วยังอยู่วันเดิม */
      function paintDateBar() {
        dateWrap.classList.toggle('is-active', !!activeDate);
        dateAllButton.classList.toggle('is-active', !activeDate);
        dateHint.textContent = activeDate
          ? text('ot.approvals.dateOnly', 'เฉพาะ OT วันที่') + ' ' + thaiDate(activeDate)
          : text('ot.approvals.allDatesHint', 'กำลังแสดงคำขอทุกวัน');
      }

      function thaiDate(value) {
        var parts = String(value).split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
      }

      function syncDateUrl() {
        if (!window.history || !window.history.replaceState) return;
        window.history.replaceState({}, '', activeDate ? pageUrl + '?date=' + encodeURIComponent(activeDate) : pageUrl);
      }

      function applyDate(value) {
        activeDate = value || '';
        dateInput.value = activeDate;
        paintDateBar();
        syncDateUrl();
        loadQueue(1);
      }
      // วางรูปพนักงานลงหัวโมดัล ใช้ class เดิมจึงกดซูมได้เหมือนในตาราง
      function fillModalPhoto(slot, request) {
        if (!slot) return;
        slot.replaceChildren();
        var node = avatar(request);
        node.dataset.captionName = employeeName(request);
        node.dataset.captionCode = request.employee_code || '';
        slot.appendChild(node);
      }

      function openDecision(request) {
        activeRequest = request;
        fillModalPhoto(modal.querySelector('[data-modal-photo]'), request);
        modal.querySelector('[data-modal-title]').textContent = employeeName(request);
        modal.querySelector('[data-detail-date]').textContent = formatDate(request.work_date);
        modal.querySelector('[data-detail-type]').textContent = localized(request, 'ot_type_label', request.ot_type);
        modal.querySelector('[data-detail-ot]').textContent = request.requested_start + '–' + request.requested_end + ' · ' + durationText(request);
        modal.querySelector('[data-detail-scan]').textContent = (request.clock_in || '-') + ' / ' + (request.clock_out || '-');
        modal.querySelector('[data-detail-status]').replaceChildren(statusElement(request.status_key, request));
        modal.querySelector('[data-detail-dept]').textContent = request.department_name || request.dept_code || '-';
        modal.querySelector('[data-detail-request-note]').textContent = requestNote(request) || '-';
        var approved = request.approval_status === 'approved';
        var rejected = request.approval_status === 'rejected';
        var approveButton = modal.querySelector('[data-decision="approved"]');
        var rejectButton = modal.querySelector('[data-decision="rejected"]');
        approveButton.hidden = approved;
        rejectButton.hidden = rejected;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
      }
      function closeDecision() { modal.hidden = true; document.body.style.overflow = ''; activeRequest = null; }

      function setConfirmCopy() {
        var approved = pendingDecision === 'approved';
        var bulkCount = bulkPending ? bulkPending.length : 0;
        var reversingApproved = !bulkPending
          && activeRequest
          && activeRequest.approval_status === 'approved'
          && pendingDecision === 'rejected';
        var reversingRejected = !bulkPending
          && activeRequest
          && activeRequest.approval_status === 'rejected'
          && pendingDecision === 'approved';
        if (reversingApproved) {
          confirmModal.querySelector('[data-confirm-title]').textContent = text('ot.approvals.confirmReverseRejectTitle', 'เปลี่ยนคำขออนุมัติแล้วเป็นไม่อนุมัติ?');
          confirmModal.querySelector('[data-confirm-copy]').textContent = text('ot.approvals.confirmReverseRejectHint', 'การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นไม่อนุมัติ และต้องระบุเหตุผลให้ตรวจสอบย้อนหลังได้');
        } else if (reversingRejected) {
          confirmModal.querySelector('[data-confirm-title]').textContent = text('ot.approvals.confirmReverseApproveTitle', 'เปลี่ยนคำขอไม่อนุมัติเป็นอนุมัติ?');
          confirmModal.querySelector('[data-confirm-copy]').textContent = text('ot.approvals.confirmReverseApproveHint', 'การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นอนุมัติอีกครั้ง โปรดตรวจสอบเหตุผลเดิมและข้อมูลสแกนก่อนยืนยัน');
        } else {
        confirmModal.querySelector('[data-confirm-title]').textContent = bulkPending
          ? (approved
            ? text('ot.approvals.confirmBulkTitle', 'อนุมัติ {n} รายการที่เลือก?')
            : text('ot.approvals.confirmBulkRejectTitle', 'ปฏิเสธ {n} รายการที่เลือก?')).replace('{n}', bulkCount)
          : (approved
            ? text('ot.approvals.confirmApproveTitle', 'อนุมัติคำขอนี้?')
            : text('ot.approvals.confirmRejectTitle', 'ไม่อนุมัติคำขอนี้?'));
        confirmModal.querySelector('[data-confirm-copy]').textContent = bulkPending
          ? (approved
            ? text('ot.approvals.confirmBulkHint', 'ระบบจะบันทึกผลอนุมัติให้ทุกรายการที่เลือก และรอสแกนล่าสุดหลังจบ OT')
            : text('ot.approvals.confirmBulkRejectHint', 'เหตุผลเดียวกันนี้จะถูกบันทึกให้พนักงานทุกคนที่เลือก และส่งกลับไปยัง Foreman'))
          : (approved
            ? text('ot.approvals.confirmApproveHint', 'ระบบจะบันทึกผลอนุมัติ และรอสแกนล่าสุดหลังจบ OT')
            : text('ot.approvals.confirmRejectHint', 'ระบุเหตุผลก่อนส่งกลับไปยัง Foreman'));
        }
        confirmModal.querySelector('[data-confirm-note-label]').textContent = approved
          ? text('ot.approvals.note', 'หมายเหตุ')
          : text('ot.approvals.rejectReason', 'เหตุผล (บังคับ)');
        confirmModal.querySelector('[data-note-toggle-label]').textContent = approved
          ? text('ot.approvals.addNote', 'เพิ่มหมายเหตุ')
          : text('ot.approvals.addReason', 'ระบุเหตุผล');
        var confirmButton = confirmModal.querySelector('[data-confirm-decision]');
        confirmButton.textContent = approved
          ? text('ot.approvals.confirmApprove', 'ยืนยันอนุมัติ')
          : text('ot.approvals.confirmReject', 'ยืนยันไม่อนุมัติ');
        // ยืนยันอนุมัติ = เขียว, ยืนยันไม่อนุมัติ = แดง (ตัวอักษรขาวทั้งคู่)
        confirmButton.classList.toggle('ota-confirm-danger', !approved);
      }

      function openConfirm(decision, trigger) {
        pendingDecision = decision;
        confirmationTrigger = trigger;
        var required = decision === 'rejected';
        var toggle = confirmModal.querySelector('[data-note-toggle]');
        var noteWrap = confirmModal.querySelector('[data-confirm-note-wrap]');
        var note = confirmModal.querySelector('[data-confirm-note]');
        modal.hidden = true;
        toggle.checked = required;
        toggle.disabled = required;
        noteWrap.hidden = !required;
        note.required = required;
        note.value = '';
        confirmModal.querySelector('[data-confirm-error]').hidden = true;
        setConfirmCopy();
        confirmModal.hidden = false;
        document.body.style.overflow = 'hidden';
        (required ? note : toggle).focus();
      }

      function closeConfirm(returnToReview) {
        var returnTrigger = confirmationTrigger;
        confirmModal.hidden = true;
        pendingDecision = null;
        bulkPending = null;
        if (returnToReview && activeRequest) {
          modal.hidden = false;
          if (confirmationTrigger) confirmationTrigger.focus();
          return;
        }
        confirmationTrigger = null;
        document.body.style.overflow = '';
        if (returnToReview && returnTrigger) returnTrigger.focus();
      }

      function openReason(request) {
        reasonPreviousFocus = document.activeElement;
        fillModalPhoto(reasonModal.querySelector('[data-reason-photo]'), request);
        reasonModal.querySelector('[data-reason-title]').textContent = employeeName(request);
        reasonModal.querySelector('[data-reason-request]').textContent = requestNote(request) || '-';
        reasonModal.querySelector('[data-reason-decision-label]').textContent = request.approval_status === 'cancelled'
          ? text('ot.reason.cancelReason', 'เหตุผลที่ยกเลิก')
          : text('ot.reason.decisionNote', 'หมายเหตุจาก Supervisor');
        reasonModal.querySelector('[data-reason-decision]').textContent = decisionNote(request) || '-';
        reasonModal.hidden = false;
        document.body.style.overflow = 'hidden';
        reasonModal.querySelector('[data-close-reason]').focus();
      }

      function closeReason() {
        reasonModal.hidden = true;
        document.body.style.overflow = '';
        if (reasonPreviousFocus && document.contains(reasonPreviousFocus)) reasonPreviousFocus.focus();
        reasonPreviousFocus = null;
      }

      function validationMessage(error) {
        var errors = error.payload?.errors || {};
        var first = Object.keys(errors)[0];
        return first && errors[first]?.[0] ? errors[first][0] : (error.payload?.message || error.message);
      }

      async function decide() {
        var bulk = bulkPending;                       // เก็บไว้ก่อน เพราะ closeConfirm จะล้างค่า
        if (!bulk && !activeRequest) return;
        var note = confirmModal.querySelector('[data-note-toggle]').checked
          ? confirmModal.querySelector('[data-confirm-note]').value.trim()
          : '';
        var errorNode = confirmModal.querySelector('[data-confirm-error]');
        var button = confirmModal.querySelector('[data-confirm-decision]');
        if (pendingDecision === 'rejected' && !note) {
          errorNode.textContent = text('ot.approvals.rejectReasonRequired', 'กรุณาระบุเหตุผลที่ไม่อนุมัติ');
          errorNode.hidden = false;
          confirmModal.querySelector('[data-confirm-note]').focus();
          return;
        }
        button.disabled = true;
        errorNode.hidden = true;
        try {
          var headers = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
          var response = bulk
            ? await fetchJson(bulkUrl, { method: 'POST', headers: headers, body: JSON.stringify({ request_ids: bulk, decision: pendingDecision, note: note || null }) })
            : await fetchJson(decisionTemplate.replace('__REQUEST__', String(activeRequest.id)), { method: 'POST', headers: headers, body: JSON.stringify({ decision: pendingDecision, note: note || null }) });
          closeConfirm(false); activeRequest = null; response.ok === false ? showNotice(response.message, true) : showSuccessFeedback(response.message); await loadQueue(pager ? pager.page : 1);
        } catch (error) {
          errorNode.textContent = validationMessage(error);
          errorNode.hidden = false;
        } finally { button.disabled = false; }
      }

      /* ตัวกรองสถานะมีทั้งบนแถบเครื่องมือและที่หัวคอลัมน์ — เขียนลงตัวแปรเดียวกัน
         กลับหน้า 1 เสมอ กันอยู่หน้า 3 แล้วผลลัพธ์ชุดใหม่มีไม่ถึง */
      var filterSelect = root.querySelector('[data-filter-select]');
      if (filterSelect) {
        filterSelect.addEventListener('change', function () { activeFilter = filterSelect.value; loadQueue(1); });
      }
      dateInput.addEventListener('change', function (event) { applyDate(event.target.value); });
      dateAllButton.addEventListener('click', function () { if (activeDate) applyDate(''); });
      // ปุ่มรีเฟรชถูกถอดออกแล้ว (2026-08-24) — คงการรีเฟรชอัตโนมัติทุก 30 วิไว้เหมือนเดิม
      if (employeeSearchInput) {
        /* หน่วงการพิมพ์ ไม่ยิงเซิร์ฟเวอร์ทุกตัวอักษร · ค้นใหม่ต้องกลับไปหน้า 1
           ไม่งั้นอยู่หน้า 3 แล้วค้นเจอ 2 แถวจะเห็นหน้าว่าง */
        var branchFilterSelect = root.querySelector('[data-branch-filter]');
        if (branchFilterSelect) {
          branchFilterSelect.addEventListener('change', function () {
            branchFilterValue = branchFilterSelect.value;
            loadQueue(1);   // กลับหน้า 1 เสมอ กันอยู่หน้า 3 แล้วผลลัพธ์เหลือ 2 แถวจนเห็นหน้าว่าง
          });
        }

        employeeSearchInput.addEventListener('input', window.otEmployeeSearch.debounce(function () {
          employeeQuery = employeeSearchInput.value;
          loadQueue(1);
        }, 350));
      }
      shiftFilterSelect.addEventListener('change', function () {
        shiftFilterValue = shiftFilterSelect.value;
        /* renderQueue ล้าง selectedIds ให้อยู่แล้ว จำเป็นมาก เพราะแถวที่ติ๊กไว้แล้วถูกกรอง
           หายจากจอจะยังถูกอนุมัติไปด้วย = อนุมัติผิดคนโดยไม่มีอะไรเตือน */
        loadQueue(1);
      });
      bulkButton.addEventListener('click', function () {
        if (!selectedIds.length) return;
        bulkPending = selectedIds.slice();
        openConfirm('approved', bulkButton);
      });
      bulkRejectButton.addEventListener('click', function () {
        if (!selectedIds.length) return;
        bulkPending = selectedIds.slice();
        openConfirm('rejected', bulkRejectButton);
      });
      modal.querySelectorAll('[data-decision]').forEach(function (button) { button.addEventListener('click', function () { openConfirm(button.dataset.decision, button); }); });
      modal.querySelector('[data-close-decision]').addEventListener('click', closeDecision);
      modal.addEventListener('click', function (event) { if (event.target === modal) closeDecision(); });
      confirmModal.querySelector('[data-note-toggle]').addEventListener('change', function (event) {
        var wrap = confirmModal.querySelector('[data-confirm-note-wrap]');
        wrap.hidden = !event.target.checked;
        if (event.target.checked) confirmModal.querySelector('[data-confirm-note]').focus();
      });
      confirmModal.querySelector('[data-confirm-decision]').addEventListener('click', decide);
      confirmModal.querySelectorAll('[data-close-confirm]').forEach(function (button) { button.addEventListener('click', function () { closeConfirm(true); }); });
      confirmModal.addEventListener('click', function (event) { if (event.target === confirmModal) closeConfirm(true); });
      reasonModal.querySelectorAll('[data-close-reason]').forEach(function (button) { button.addEventListener('click', closeReason); });
      reasonModal.addEventListener('click', function (event) { if (event.target === reasonModal) closeReason(); });
      /* ── กดรูปพนักงานเพื่อดูแบบขยาย ────────────────────────
         ใช้ delegation แบบ capture เพราะแถวในตารางถูกสร้างใหม่ทุกครั้งที่โหลดคิว */
      var lightbox = document.querySelector('[data-ota-lightbox]');
      var lightboxImage = lightbox.querySelector('[data-lightbox-image]');
      var lightboxCaption = lightbox.querySelector('[data-lightbox-caption]');

      function closeLightbox() {
        if (lightbox.hidden) return;
        lightbox.hidden = true;
        lightboxImage.removeAttribute('src');
      }

      function openLightbox(image) {
        if (!image.getAttribute('src')) return;
        lightboxImage.src = image.currentSrc || image.src;
        lightboxImage.alt = image.alt || '';

        /* รูปในหัวโมดัลติด data-caption-* มาให้ ส่วนรูปในตารางอ่านจาก
           บล็อกข้อความข้าง ๆ ในแถวเดียวกัน */
        var captionName = image.dataset.captionName || '';
        var captionCode = image.dataset.captionCode || '';

        if (!captionName) {
          var copy = image.parentElement ? image.parentElement.querySelector('.ota-person-copy') : null;
          if (copy) {
            var nameNode = copy.querySelector('strong');
            var codeNode = copy.querySelector('small');
            captionName = nameNode ? nameNode.textContent : '';
            captionCode = codeNode ? codeNode.textContent : '';
          }
        }

        lightboxCaption.replaceChildren();
        if (captionName.trim() !== '') {
          var nameEl = document.createElement('strong');
          nameEl.textContent = captionName;
          lightboxCaption.appendChild(nameEl);
        }
        if (captionCode.trim() !== '' && captionCode.trim() !== '-') {
          var codeEl = document.createElement('small');
          codeEl.textContent = captionCode;
          lightboxCaption.appendChild(codeEl);
        }
        lightboxCaption.hidden = !lightboxCaption.childNodes.length;
        lightbox.hidden = false;
      }

      document.addEventListener('click', function (event) {
        var image = event.target.closest('img.ota-avatar');
        if (!image) return;
        // กันคลิกทะลุไปโดนแถวหรือปุ่มที่ครอบรูปอยู่
        event.preventDefault();
        event.stopPropagation();
        openLightbox(image);
      }, true);

      lightbox.addEventListener('click', function (event) {
        // คลิกพื้นหลังหรือปุ่มปิด = ปิด, คลิกที่ตัวรูปไม่ปิด
        if (event.target.closest('[data-lightbox-close]') || !event.target.closest('.ota-lightbox-figure')) {
          closeLightbox();
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        // กำลังดูรูปขยาย ให้ปิดแค่รูป ไม่ปิดโมดัลที่อยู่ข้างหลัง
        if (!lightbox.hidden) { closeLightbox(); return; }
        if (!confirmModal.hidden) { closeConfirm(true); return; }
        if (!reasonModal.hidden) { closeReason(); return; }
        if (!modal.hidden) closeDecision();
      });
      document.addEventListener('insight:languagechange', function () { renderQueue(allRequests, lastMeta); paintDateBar(); if (!confirmModal.hidden) setConfirmCopy(); });
      paintDateBar();
      loadQueue(1);
      window.setInterval(function () {
        // ถ้ากำลังติ๊กเลือกอยู่ อย่ารีเฟรชทับ เพราะ renderQueue จะล้างรายการที่เลือกไปหมด
        if (selectedIds.length) return;
        if (!document.hidden && modal.hidden && confirmModal.hidden && reasonModal.hidden) loadQueue(pager ? pager.page : 1);
      }, 30000);
    })();
  </script>
@endsection
