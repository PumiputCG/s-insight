@extends('layouts.portal')

@section('title', 'อนุมัติลา')
@section('topbar-title')<span data-i18n="nav.leaveApprovals">อนุมัติลา</span>@endsection

@section('content')
  <style>
    .main { min-width:0; }
    .leave-approval-page { width:min(100%,94rem); margin:0 auto; }
    .la-head { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:end; gap:1rem; margin-bottom:1rem; }
    .la-head h1 { margin:.2rem 0; font-size:clamp(1.35rem,2.4vw,2rem); font-weight:600; }
    .la-head p { margin:0; color:var(--muted-light); font-size:.84rem; }

    /* ── แถวเลือกวัน: ถอดสเปกจาก .ota-datebar ของหน้าอนุมัติ OT ── */
    .la-datebar { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:.6rem; }
    .la-date-picker {
      min-height:2.45rem; display:inline-flex; align-items:center; gap:.45rem; flex:0 0 auto;
      padding:0 .7rem; border:1px solid var(--line-light); border-radius: 4px;
      background:var(--panel); color:var(--muted-light);
    }
    .la-date-picker:focus-within { border-color:var(--moss); }
    .la-date-picker.is-active { border-color:var(--moss); background:color-mix(in srgb, var(--moss) 10%, var(--panel)); color:var(--light-text); }
    .la-date-picker svg { width:1rem; flex:0 0 auto; fill:none; stroke:currentColor; stroke-width:1.7; opacity:.8; }
    .la-date-picker input {
      width:8.9rem; border:0; outline:0; background:transparent;
      color:var(--light-text); color-scheme:light dark; font-size:.76rem; font-weight:650;
    }
    .la-date-all { min-height:2.45rem; padding:.5rem .8rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel); color:var(--muted-light); font-size:.72rem; font-weight:650; white-space:nowrap; cursor:pointer; }
    .la-date-all:hover { border-color:var(--line-strong); color:var(--light-text); }
    .la-date-all.is-active { border-color:var(--moss); background:color-mix(in srgb, var(--moss) 10%, var(--panel)); color:var(--light-text); }
    .la-date-hint { color:var(--muted-light); font-size:.7rem; font-weight:600; }
    @media (max-width:640px) {
      .la-date-picker { flex:1 1 100%; justify-content:space-between; }
      .la-date-picker input { width:auto; flex:1 1 auto; }
    }
    .la-button { min-height:2.3rem; padding:.44rem .7rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); color:var(--light-text); font-size:.73rem; font-weight:700; cursor:pointer; }
    .la-button.is-active,.la-button.is-primary { border-color:var(--moss); background:var(--moss); color:#fff; }
    .la-button.is-danger { border-color:#da5a4e; background:#da5a4e; color:#fff; }
    /* ปุ่ม `อนุมัติที่เลือก` ใช้เขียว #35a863 ชุดเดียวกับป้ายสถานะ `อนุมัติแล้ว` (Manager สั่ง 2026-08-24)
       คู่กับปุ่มปฏิเสธสีแดง อ่านออกทันทีว่าอันไหนคืออนุมัติ */
    /* ปุ่มอนุมัติเป็นเขียว #35a863 ทั้ง `อนุมัติที่เลือก` บนแถบ และปุ่มในโมดัล `ตรวจคำขอ`
       รวมถึงปุ่มยืนยันตอนอนุมัติ — คู่กับปุ่มไม่อนุมัติสีแดง อ่านออกทันทีว่าอันไหนคืออะไร */
    .la-bulk .la-button.is-primary,
    .la-review-actions .la-button.is-primary,
    .la-confirm-actions .la-button.is-primary { border-color:#35a863; background:#35a863; }
    .la-bulk .la-button.is-primary:hover:not(:disabled),
    .la-review-actions .la-button.is-primary:hover:not(:disabled),
    .la-confirm-actions .la-button.is-primary:hover:not(:disabled) { background:color-mix(in srgb, #35a863 86%, #000); }
    .la-button:disabled { opacity:.45; cursor:not-allowed; }
    /* ตัวกรองเกาะกลุ่มชิดซ้าย ปุ่มตัดสินหลายรายการไปชิดขวาด้วย margin-left:auto
       ห้ามใช้ space-between เพราะพอมีตัวกรองหลายตัวจะกระจายห่างกันคนละมุม */
    .la-filter-bar { display:flex; justify-content:flex-start; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem; padding:.55rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel); }
    .la-filter-bar .la-bulk { margin-left:auto; }
    /* ตัวกรองแบบ dropdown ใช้โครงเดียวกับตัวกรองกะ/สาขา จะได้สูงเท่ากันทั้งแถว */
    .ot-sort-filter { position:relative; display:inline-flex; align-items:center; flex:0 0 auto; }
    .ot-sort-filter svg { position:absolute; left:.55rem; width:.95rem; height:.95rem; color:var(--muted-light); fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; pointer-events:none; }
    .ot-sort-filter select { min-height:2.45rem; max-width:12rem; padding:.4rem 1.7rem .4rem 1.95rem; border:1px solid var(--line-strong); border-radius:4px; background:var(--panel); color:var(--light-text); font-family:inherit; font-size:.74rem; font-weight:650; cursor:var(--cursor-action); appearance:none; text-overflow:ellipsis; }
    .ot-sort-filter::after { content:''; position:absolute; right:.6rem; width:.4rem; height:.4rem; border-right:1.6px solid var(--muted-light); border-bottom:1.6px solid var(--muted-light); transform:translateY(-.12rem) rotate(45deg); pointer-events:none; }
    .la-bulk { display:flex; align-items:center; gap:.4rem; }
    .la-selected { color:var(--muted-light); font-size:.7rem; white-space:nowrap; }
    .la-panel { border:1px solid var(--line-light); border-radius: 4px; background:var(--panel); overflow:hidden; }
    .la-panel-head { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:.9rem 1rem; border-bottom:1px solid var(--line-light); background:var(--ot-tint-company); }
    .la-panel-head h2 { margin:0; font-size:.92rem; }
    .la-panel-head small { color:var(--muted-light); }
    .la-table-wrap { overflow:auto; }
    /* ── ผังคอลัมน์: ต้องอ่านจบในจอเดียว ไม่ต้องเลื่อนซ้าย-ขวา (Manager สั่ง 2026-08-24) ──
       ของเดิมตรึง min-width 95rem จึงล้นจอเสมอ · เปลี่ยนมากำหนดเป็น % ผ่าน <colgroup>
       เหลือ min-width ไว้เท่าที่จอมือถือยังอ่านออก (ต่ำกว่านั้นค่อยเลื่อน) */
    /* min-width ต้องพอให้คอลัมน์ `สถานะทั้งหมด` (11%) กว้างอย่างน้อย ~7.2rem
       เพราะปุ่มผังเดินเรื่องตรึงขนาดไว้ที่ 6.8rem บวก padding — 11% ของ 66rem = 7.26rem พอดี
       ถ้าตั้งต่ำกว่านี้ ปุ่มจะล้นออกนอกช่องบนจอแคบ */
    .la-table { width:100%; min-width:66rem; border-collapse:collapse; table-layout:fixed; font-size:.72rem; }
    .la-table th,.la-table td { padding:.6rem .45rem; border:1px solid var(--line-light); text-align:center; vertical-align:middle; overflow-wrap:anywhere; }
    .la-table th { background:var(--panel-soft); color:var(--muted-light); font-size:.66rem; line-height:1.3; }
    .la-table td.is-employee { text-align:left; }
    /* ปุ่มผังเดินเรื่องต้องอยู่ในช่อง ไม่ล้นออกมา — บีบ padding แล้วให้ตัวปุ่มหดตามความกว้างคอลัมน์ */
    .la-table td.is-route { padding-left:.25rem; padding-right:.25rem; }
    .la-table td.is-route > * { max-width:100%; }
    /* ปุ่ม `ตรวจคำขอ` ในคอลัมน์ `ดำเนินการ` ก็ต้องไม่ล้นเช่นกัน */
    .la-table .la-button { max-width:100%; padding:.4rem .5rem; font-size:.7rem; }
    /* ว่าง/กำลังโหลด/โหลดพลาด = ไม่มีตาราง ขึ้นข้อความกลางแผงแบบหน้าอนุมัติ OT */
    /* กล่องแจ้งผลสำเร็จ — โครงเดียวกับ .lr-success ของหน้าขอลา */
    .la-dialog.is-narrow { width:min(24rem,100%); }
    .la-success-mark { width:4rem; height:4rem; display:grid; place-items:center; margin:1.25rem auto .6rem; border-radius:50%; background:#35a863; color:#fff; font-size:2rem; }
    .la-success-copy { padding:0 1rem 1.25rem; text-align:center; }
    .la-success-copy h2 { margin:0 0 .35rem; font-size:1rem; }
    .la-success-copy p { margin:0 0 1rem; color:var(--muted-light); font-size:.78rem; line-height:1.6; }
    .la-state { min-height:17rem; display:grid; place-items:center; padding:2rem; color:var(--muted-light); font-size:.8rem; text-align:center; }
    .la-pager-wrap { padding:0 1rem .35rem; }
    /* ความกว้างมาจาก style ที่ <th> ใน tableHead() แล้ว ที่นี่เหลือแค่เรื่องสี/ตัวเลข
       (ถ้าตั้ง width เป็น rem ซ้ำตรงนี้ จะไปทับสัดส่วน % จนคอลัมน์อื่นเบี้ยว) */
    .la-table .is-no { color:var(--muted-light); font-variant-numeric:tabular-nums; }
    .la-count { margin-left:auto; color:var(--muted-light); font-size:.72rem; white-space:nowrap; }
    .la-count b { color:var(--light-text); font-weight:700; font-variant-numeric:tabular-nums; }
    .la-table td.is-date { white-space:nowrap; font-variant-numeric:tabular-nums; }
    /* ประเภทการลาบรรทัดบน วันที่ลาบรรทัดล่างแบบตัวเลขจาง ๆ */
    .la-type-name { display:block; }
    .la-type-date { display:block; margin-top:.18rem; color:var(--muted-light); font-size:.7rem; font-variant-numeric:tabular-nums; }
    /* ปลด min-width ไม่งั้นดันคอลัมน์ให้กว้างเกินสัดส่วนที่ตั้งไว้ · ชื่อยาวตัดด้วย … */
    .la-employee { display:flex; align-items:center; gap:.5rem; min-width:0; }
    .la-employee > div { min-width:0; flex:1 1 auto; }
    .la-employee strong,.la-employee small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    /* ย่อรูปให้เท่าหน้าอนุมัติ OT เพื่อให้ชื่อ-รหัสมีที่พอในคอลัมน์ที่แคบลง */
    .la-avatar { width:2.1rem; height:2.1rem; flex:0 0 auto; border-radius:50%; object-fit:cover; background:#dfe4ea; }
    {{-- rule เดิม `.la-employee strong,small { display:block }` ถูกยุบไปรวมกับบรรทัดด้านบนแล้ว
         ถ้าปล่อยไว้ตรงนี้จะมาทีหลังแล้วล้าง text-overflow:ellipsis ทิ้ง ชื่อยาวก็จะดันคอลัมน์อีก --}}
    .la-employee small { margin-top:.12rem; color:var(--muted-light); }
    /* ปลด min-width ของป้ายสถานะ ไม่งั้นมันดันคอลัมน์ให้กว้างเกินสัดส่วนที่ตั้งไว้
       และต้อง max-width:100% + ตัดบรรทัดได้ ไม่งั้นป้ายยาว (เช่น `พ้นกำหนดอนุมัติ`)
       จะล้นออกนอกช่องหลังจัดผังคอลัมน์เป็น % (Manager แจ้ง 2026-08-24) */
    .la-status { display:inline-flex; justify-content:center; align-items:center; min-width:0; max-width:100%; min-height:1.75rem; padding:.25rem .4rem; border-radius: 4px; color:#fff; font-size:.65rem; font-weight:700; line-height:1.25; white-space:normal; overflow-wrap:anywhere; text-align:center; }
    .la-status.is-warning { background:#e0a51c; border:1px solid #e0a51c; }
    .la-status.is-success { background:#35a863; border:1px solid #35a863; }
    .la-status.is-danger { background:#da5a4e; border:1px solid #da5a4e; }
    .la-status.is-neutral { min-width:0; padding:0; background:transparent; border:0; color:var(--muted-light); }
    .la-detail { display:block; margin-top:.25rem; color:var(--light-text); font-size:.65rem; }
    .la-empty { padding:2.7rem!important; color:var(--muted-light); text-align:center!important; }
    .la-modal { position:fixed; inset:0; z-index:1180; display:grid; place-items:center; padding:1rem; background:rgb(8 12 10 / 56%); backdrop-filter:blur(5px); }
    .la-modal[hidden] { display:none; }
    .la-dialog { width:min(38rem,100%); max-height:92vh; overflow:auto; border:1px solid var(--line-light); border-radius: 6px; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 24%); }
    .la-dialog-head { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.1rem; border-bottom:1px solid var(--line-light); }
    .la-dialog-head h2 { margin:0; font-size:1rem; }
    .la-close { width:2.2rem; height:2.2rem; border:1px solid var(--line-light); border-radius:50%; background:transparent; color:inherit; cursor:pointer; }
    .la-review { display:grid; grid-template-columns:1fr 1fr; gap:.7rem; padding:1.05rem 1.1rem; }
    .la-review-card { padding:.72rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); }
    .la-review-card.is-wide { grid-column:1/-1; }
    .la-review-card small,.la-review-card strong { display:block; }
    .la-review-card small { color:var(--muted-light); font-size:.67rem; }
    .la-review-card strong { margin-top:.22rem; font-size:.79rem; line-height:1.5; }
    .la-review-actions { grid-column:1/-1; display:flex; justify-content:flex-end; gap:.5rem; margin-top:.25rem; }
    .la-confirm { display:grid; gap:.75rem; padding:1.05rem 1.1rem; }
    .la-confirm p { margin:0; color:var(--muted-light); font-size:.78rem; line-height:1.6; }
    .la-confirm textarea { min-height:6rem; padding:.65rem .7rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); color:var(--light-text); resize:vertical; }
    .la-confirm-actions { display:flex; justify-content:flex-end; gap:.5rem; }
    @media(max-width:850px){.la-head{grid-template-columns:1fr}.la-filter-bar{align-items:flex-start;flex-direction:column}.la-review{grid-template-columns:1fr}.la-review-card.is-wide,.la-review-actions{grid-column:1}}
  </style>

  <main class="leave-approval-page" data-leave-approvals>
    <header class="la-head">
      {{-- ไม่มีปุ่ม `รีเฟรช` แล้ว (Manager สั่ง 2026-08-24) — เปลี่ยนตัวกรอง/หน้า ก็โหลดใหม่อยู่แล้ว --}}
      <div><h1 data-i18n="leave.approvals.title">ตรวจอนุมัติลา</h1><p data-i18n="leave.approvals.subtitle">ตรวจคำขอแบบเต็มวัน และอนุมัติได้ถึงวันสุดท้ายของรอบเงินเดือน</p></div>
    </header>

    {{-- แถวเลือกวันวางตำแหน่งเดียวกับหน้าอนุมัติ OT (.ota-datebar): อยู่ใต้หัวเรื่อง เหนือแท็บสถานะ
         เพราะเป็นคนละแกนกับแท็บ (สถานะ = รอ/อนุมัติ · วันที่ = ทุกวัน หรือเจาะจงวัน) --}}
    <div class="la-datebar" aria-label="วันที่ลา">
      <label class="la-date-picker {{ $selectedDate ? 'is-active' : '' }}" data-la-date-wrap>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>
        <span class="sr-only" data-i18n="leave.selectDate">เลือกวันที่</span>
        <input type="date" value="{{ $selectedDate?->format('Y-m-d') }}" data-la-date aria-label="เลือกวันที่ลา">
      </label>
      <button class="la-date-all {{ $selectedDate ? '' : 'is-active' }}" type="button" data-la-all-dates data-i18n="leave.allDates">ทุกวัน</button>
      <span class="la-date-hint" data-la-date-label></span>
    </div>

    <section class="la-filter-bar">
      {{-- ตัวกรองอยู่ **ทั้งสองที่** (Manager สั่งเอาของเดิมกลับมา 2026-08-24):
           แถบเครื่องมือนี้ + เมนูที่หัวคอลัมน์ · ทั้งคู่อ่าน/เขียนตัวแปรชุดเดียวกัน --}}
      <label class="ot-sort-filter">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v6l-4 2v-8Z"></path></svg>
        <select data-la-filter-select aria-label="กรองตามสถานะคำขอ">
          <option value="pending" data-i18n="leave.pending">รอดำเนินการ</option>
          <option value="approved" data-i18n="leave.approvedTab">อนุมัติแล้ว</option>
          <option value="rejected" data-i18n="leave.rejected">ไม่อนุมัติ</option>
          <option value="cancelled" data-i18n="leave.cancelled">ยกเลิก</option>
          <option value="all" data-i18n="leave.all">ทั้งหมด</option>
        </select>
      </label>
      @include('ot_approval.partials.employee-search')
      @include('ot_approval.partials.branch-filter')
      @include('ot_approval.partials.column-filter')
      <div class="la-bulk"><span class="la-selected"><span data-i18n="leave.selected">เลือกแล้ว</span> <b data-la-selected>0</b></span><button class="la-button is-danger" type="button" data-la-bulk-reject disabled data-i18n="leave.bulkReject">ปฏิเสธที่เลือก</button><button class="la-button is-primary" type="button" data-la-bulk-approve disabled data-i18n="leave.bulkApprove">อนุมัติที่เลือก</button></div>
    </section>

    <section class="la-panel">
      <header class="la-panel-head">
        <h2 data-i18n="leave.approval.list">รายการคำขออนุมัติการลา</h2>
        {{-- จำนวนรายการอยู่หัวแผงเหมือนหน้าอนุมัติ OT จะได้รู้ทันทีว่าแท็บนี้ว่างจริง --}}
        <span class="la-count"><b data-la-count>0</b> <span data-i18n="leave.items">รายการ</span></span>
      </header>
      {{-- ว่าง/กำลังโหลด = ไม่ต้องมีตารางเลย ขึ้นข้อความกลางแผงแบบหน้าอนุมัติ OT
           มีข้อมูลเมื่อไหร่ค่อยสร้างตารางพร้อมคอลัมน์ของหน้าขอลาเอง --}}
      <div class="la-table-wrap" data-la-content data-drag-scroll>
        <div class="la-state" data-i18n="leave.loadingRequests">กำลังโหลดคำขอ...</div>
      </div>
      <div class="la-pager-wrap">
        @include('ot_approval.partials.table-pager')
      </div>
    </section>
  </main>

  <div class="la-modal" data-la-review-modal hidden>
    <section class="la-dialog" role="dialog" aria-modal="true" aria-labelledby="laReviewTitle">
      <header class="la-dialog-head"><h2 id="laReviewTitle" data-i18n="leave.review">ตรวจคำขอ</h2><button class="la-close" type="button" data-la-review-close>×</button></header>
      <div class="la-review" data-la-review-content></div>
    </section>
  </div>

  <div class="la-modal" data-la-confirm-modal hidden>
    <section class="la-dialog" role="dialog" aria-modal="true" aria-labelledby="laConfirmTitle">
      <header class="la-dialog-head"><h2 id="laConfirmTitle" data-la-confirm-title data-i18n="leave.confirm">ยืนยันผล</h2><button class="la-close" type="button" data-la-confirm-close>×</button></header>
      <form class="la-confirm" data-la-confirm-form><p data-la-confirm-hint>-</p><label><span class="sr-only" data-i18n="leave.note">หมายเหตุ</span><textarea name="note" maxlength="1000" placeholder="หมายเหตุหรือเหตุผล" data-i18n-placeholder="leave.notePlaceholder"></textarea></label><div class="la-confirm-actions"><button class="la-button" type="button" data-la-confirm-cancel data-i18n="leave.cancel">ยกเลิก</button><button class="la-button is-primary" type="submit" data-la-confirm-submit data-i18n="leave.confirmButton">ยืนยัน</button></div></form>
    </section>
  </div>

  {{-- แจ้งผลหลังอนุมัติ/ปฏิเสธ ใช้หน้าตาเดียวกับ success modal ของหน้าขอลา --}}
  <div class="la-modal" data-la-success hidden>
    <section class="la-dialog is-narrow" role="alertdialog" aria-modal="true">
      <div class="la-success-mark">✓</div>
      <div class="la-success-copy">
        <h2 data-i18n="leave.success">สำเร็จ</h2>
        <p data-la-success-message data-i18n="leave.done">ดำเนินการเรียบร้อยแล้ว</p>
        <button class="la-button is-primary" type="button" data-la-success-close data-i18n="leave.ok">ตกลง</button>
      </div>
    </section>
  </div>

  @include('ot_approval.partials.photo-zoom')
  @include('ot_approval.partials.leave-process-route')
  @include('ot_approval.partials.success-feedback')

  <script>
    (function leaveApprovals() {
      'use strict';
      var root=document.querySelector('[data-leave-approvals]');if(!root)return;
      var content=root.querySelector('[data-la-content]'),body=null,dateInput=root.querySelector('[data-la-date]'),allDates=root.querySelector('[data-la-all-dates]'),selectedNode=root.querySelector('[data-la-selected]'),bulkApprove=root.querySelector('[data-la-bulk-approve]'),bulkReject=root.querySelector('[data-la-bulk-reject]');
      var reviewModal=document.querySelector('[data-la-review-modal]'),reviewContent=reviewModal.querySelector('[data-la-review-content]'),confirmModal=document.querySelector('[data-la-confirm-modal]'),confirmForm=confirmModal.querySelector('[data-la-confirm-form]'),successModal=document.querySelector('[data-la-success]');
      var csrf=document.querySelector('meta[name="csrf-token"]').content;var filter='pending';var employeeSearchInput=document.querySelector('[data-emp-search]');var employeeQuery='';var branchValue='all';var leaveTypeValue='all';
      /* ตัวเลือกของตัวกรองประเภทการลา — มาจาก config ทั้งชุด ป้ายสลับตามภาษาที่เปิดอยู่ */
      /* ⚠️ `LeaveRequestWorkflowService::types()` คืน array ที่ **คีย์เป็นรหัสประเภท** ไม่ใช่ list
         ตัวแปลง json จึงได้ object ไม่ใช่ array → เรียก .forEach ตรง ๆ จะพัง TypeError
         แล้วทั้ง render() ตาย = ตารางไม่ขึ้นทั้งหน้า (บทเรียน 2026-08-24)
         ห่อด้วย Object.values() ไว้ ใช้ได้ทั้งสองรูปแบบ
         (และห้ามพิมพ์ชื่อ directive ของ Blade ลงในคอมเมนต์ JS — Blade จะคอมไพล์มันจริง ๆ) */
      var leaveTypeList=Object.values(@json($leaveTypes ?? []));
      /* ตัวเลือกของตัวกรองที่หัวคอลัมน์ — ทุกตัวส่งค่าไปกรองที่เซิร์ฟเวอร์
         `สถานะ` ใช้ค่าเดียวกับพารามิเตอร์ `filter` เดิม */
      function statusOptions(){
        return [
          {value:'pending',label:text('leave.pending','รอดำเนินการ')},
          {value:'approved',label:text('leave.approvedTab','อนุมัติแล้ว')},
          {value:'rejected',label:text('leave.rejected','ไม่อนุมัติ')},
          {value:'cancelled',label:text('leave.cancelled','ยกเลิก')},
          {value:'all',label:text('leave.all','ทั้งหมด')}
        ];
      }
      /* สาขาอ่านตัวเลือกจาก <select> ที่ partial สร้างไว้แล้ว (ซ่อนอยู่ในหน้า)
         จะได้ไม่ต้องทำรายการซ้ำสองชุดให้หลุดกันภายหลัง */
      function branchOptions(){
        var select=document.querySelector('[data-branch-filter]');
        if(!select)return [{value:'all',label:text('ot.branch.all','ทุกสาขา')}];

        return Array.prototype.map.call(select.options,function(option){
          return {value:option.value,label:option.textContent};
        });
      }
      function leaveTypeOptions(){
        var lang=document.documentElement.getAttribute('data-lang')||'th';
        var options=[{value:'all',label:text('leave.allTypes','ทุกประเภทการลา')}];
        leaveTypeList.forEach(function(type){
          options.push({value:type.code,label:type['label_'+lang]||type.label_th||type.code});
        });

        return options;
      }var date=@json($selectedDate?->format('Y-m-d'));var records=[];var totalRecords=0;var decisionContext=null;
      var endpoints={queue:@json(route('ot-approval.leave-approvals.data')),decisionBase:@json(url('/ot-approval/leave-approvals')),bulk:@json(route('ot-approval.leave-approvals.bulk-decision'))};
      function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[c];});}
      function lang(){return document.documentElement.getAttribute('data-lang')||'th';}
      function text(key,fallback){return window.__portalLang?window.__portalLang.text(key,fallback):fallback;}
      function typeLabel(row){return row['leave_type_label_'+lang()]||row.leave_type_label_th||'-';}
      function employeeName(row){return row['employee_name_'+lang()]||row.employee_name_en||row.employee_name_th||row.employee_name||row.employee_code||'';}
      function status(row){var label=text('leave.status.'+row.approval_status,row.status_label);var detail=row.approval_status==='submitted'?text('leave.status.waitingSupervisor',row.status_detail||''):'';var tone=row.status_tone;if(row.approval_status==='submitted'&&row.can_decide===false){label=text('leave.expired','พ้นกำหนดอนุมัติ');detail='';tone='danger';}return '<span class="la-status is-'+esc(tone)+'">'+esc(label)+'</span>'+(detail?'<small class="la-detail">'+esc(detail)+'</small>':'');}
      /* asset() คืนโฮสต์ตามค่า APP_URL (localhost) แต่หน้าเว็บอาจเปิดที่ 127.0.0.1:8000
         ถ้าไม่ดึงกลับมาที่ origin ปัจจุบัน รูปจะโหลดไม่ขึ้นทั้งหน้า */
      function localAssetUrl(value){if(!value)return '';try{var url=new URL(value,window.location.href);return ['localhost','127.0.0.1'].indexOf(url.hostname)!==-1?window.location.origin+url.pathname+url.search:url.href;}catch(error){return value;}}
      function employee(row){var avatar=localAssetUrl(row.avatar||'');return '<div class="la-employee">'+(avatar?'<img class="la-avatar" src="'+esc(avatar)+'" alt="'+esc(row.employee_name)+'" data-leave-photo data-caption-name="'+esc(row.employee_name)+'" data-caption-code="'+esc(row.employee_code)+'">':'<span class="la-avatar"></span>')+'<div><strong>'+esc(row.employee_name)+'</strong><small>'+esc(row.employee_code)+'</small></div></div>';}
      function selectedIds(){return body?Array.from(body.querySelectorAll('[data-la-check]:checked')).map(function(box){return Number(box.value);}):[];}
      function syncSelected(){
        var boxes=body?body.querySelectorAll('[data-la-check]'):[];
        var count=selectedIds().length;
        selectedNode.textContent=count;
        bulkApprove.disabled=count<1;
        bulkReject.disabled=count<1;

        /* ใส่จำนวนที่เลือกไว้ในตัวปุ่มด้วย เหมือนหน้าอนุมัติ OT (Manager สั่ง 2026-08-24)
           กดจากตรงไหนก็รู้ทันทีว่ากำลังจะตัดสินกี่รายการ ไม่ต้องเงยไปดูตัวนับ
           ยังไม่เลือกใครก็เป็นข้อความเปล่า ๆ ไม่ต้องมี (0) ให้รก */
        bulkApprove.textContent=count>0
          ? text('leave.bulkApprove','อนุมัติที่เลือก')+' ('+count+')'
          : text('leave.bulkApprove','อนุมัติที่เลือก');
        bulkReject.textContent=count>0
          ? text('leave.bulkReject','ปฏิเสธที่เลือก')+' ('+count+')'
          : text('leave.bulkReject','ปฏิเสธที่เลือก');

        var checkAll=root.querySelector('[data-la-check-all]');
        if(checkAll){
          checkAll.checked=boxes.length>0&&count===boxes.length;
          checkAll.indeterminate=count>0&&count<boxes.length;
        }
      }
      /* ประเภทการลาพ่วงวันที่ลาไว้ใต้ชื่อ ส่วนคอลัมน์แรกเหลือวันที่ยื่นคำขออย่างเดียว */
      function typeCell(row){
        return '<span class="la-type-name">'+esc(typeLabel(row))+'</span>'
          +'<span class="la-type-date">'+esc(row.leave_date_label)+'</span>';
      }

      /* หัวตาราง — สร้างตอนมีข้อมูลเท่านั้น คอลัมน์ติ๊กใส่เมื่อมีรายการที่ตัดสินได้จริง */
      function tableHead(canPick){
        /* ⚠️ ความกว้างรวมกันต้องเป็น 100% พอดี **ตอนที่มีคอลัมน์ติ๊กด้วย**
           ของเดิมรวมได้ 106% ตารางจึงล้นออกนอกกรอบ (Manager แจ้ง 2026-08-24)
           ตอนไม่มีคอลัมน์ติ๊ก ที่เหลือจะยืดตามสัดส่วนเดิมให้เอง */
        var columns=[
          canPick?'<th style="width:3%"><input type="checkbox" data-la-check-all aria-label="'+esc(text('leave.selectAll','เลือกทั้งหมด'))+'"></th>':'',
          '<th class="is-no" style="width:3%">'+esc(text('common.rowNo','ลำดับ'))+'</th>',
          '<th style="width:7%">'+esc(text('leave.requestedDate','วันที่ขอ'))+'</th>',
          '<th style="width:13%">'+esc(text('leave.employee','พนักงาน'))+'</th>',
          '<th style="width:10%">'+esc(text('leave.position','ตำแหน่ง'))+'</th>',
          '<th class="la-dept-col" style="width:8.5%">'+esc(text('leave.department','แผนก'))+'</th>',
          '<th class="la-type-col" style="width:10.5%">'+esc(text('leave.leaveType','ประเภทการลา'))+'</th>',
          '<th style="width:5.5%">'+esc(text('leave.quantity','จำนวน'))+'</th>',
          '<th style="width:7%">'+esc(text('leave.note','หมายเหตุ'))+'</th>',
          '<th class="la-status-col" style="width:9%">'+esc(text('leave.status','สถานะ'))+'</th>',
          '<th style="width:5%">'+esc(text('leave.reason','เหตุผล'))+'</th>',
          '<th style="width:7.5%">'+esc(text('leave.action','ดำเนินการ'))+'</th>',
          /* `สถานะทั้งหมด` ต้องกว้างพอให้ปุ่มผังเดินเรื่อง (~7rem) อยู่ในช่องได้
             เดิม 7.5% แคบไปจนปุ่มล้นออกนอกคอลัมน์ (Manager แจ้ง 2026-08-24) */
          '<th style="width:11%">'+esc(text('leave.route','สถานะทั้งหมด'))+'</th>'
        ];

        return '<tr>'+columns.join('')+'</tr>';
      }

      /* วาดใหม่เมื่อเปลี่ยนหน้า/จำนวนต่อหน้า — ใช้ records เดิม ไม่ต้องยิง API ซ้ำ */
      var pager=window.otTablePager
        ? window.otTablePager.create(root.querySelector('[data-table-pager]'), {perPage:20, onChange:function(){load(pager.page);}})
        : null;

      /* แท็บที่ยังไม่มีข้อมูลไม่ต้องมีตารางเลย ขึ้นข้อความกลางแผงแบบหน้าอนุมัติ OT */
      function showState(message){
        body=null;
        content.replaceChildren();
        var state=document.createElement('div');
        state.className='la-state';
        state.textContent=message;
        content.appendChild(state);
        syncSelected();
      }

      function render(){
        records.forEach(function(row){row.employee_name=employeeName(row);});
        root.querySelector('[data-la-count]').textContent=totalRecords;

        if(!records.length){
          showState(text('leave.emptyCategory','ไม่มีรายการในหมวดนี้'));
          return;
        }

        /* ติ๊กเลือกได้เฉพาะแถวที่เห็นอยู่ในหน้านี้ ปุ่ม "อนุมัติที่เลือก" จึงทำงานทีละหน้า
           กันเผลออนุมัติรายการที่ยังไม่ได้ดูจากหน้าอื่น */
        var pageRows=records;
        var canPick=pageRows.some(function(row){return row.approval_status==='submitted'&&row.can_decide!==false;});
        content.replaceChildren();
        var table=document.createElement('table');
        table.className='la-table';
        var head=document.createElement('thead');
        head.innerHTML=tableHead(canPick);
        table.appendChild(head);

        /* ตัวกรองที่หัวคอลัมน์ `ประเภทการลา` — เลือกได้ค่าเดียวและ **กรองที่เซิร์ฟเวอร์**
           รายการตัวเลือกมาจาก config ทั้งชุด ไม่ใช่จากแถวที่เห็น เพราะคิวแบ่งหน้า */
        if(window.otColumnFilter){
          window.otColumnFilter.attachServerChoice(
            head.querySelector('.la-type-col'),
            leaveTypeOptions(),
            leaveTypeValue,
            function(value){leaveTypeValue=value;load(1);}
          );
          /* สถานะ · แผนก(สาขา) ก็ย้ายมาอยู่ที่หัวคอลัมน์ (Manager สั่ง 2026-08-24)
             ทุกตัวส่งไปกรองที่เซิร์ฟเวอร์ ไม่ใช่กรองแถวที่โหลดมาแล้ว */
          window.otColumnFilter.attachServerChoice(
            head.querySelector('.la-status-col'),
            statusOptions(),
            filter,
            function(value){filter=value;load(1);}
          );
          window.otColumnFilter.attachServerChoice(
            head.querySelector('.la-dept-col'),
            branchOptions(),
            branchValue,
            function(value){branchValue=value;load(1);}
          );
        }
        body=document.createElement('tbody');
        table.appendChild(body);
        content.appendChild(table);

        // เลขที่ไล่ต่อเนื่องข้ามหน้า หน้า 2 จึงเริ่มที่ 21 ไม่ใช่ 1
        var start=pager?pager.offset:0;
        pageRows.forEach(function(row,index){
          var actionable=row.approval_status==='submitted'&&row.can_decide!==false;
          var tr=document.createElement('tr');
          tr.innerHTML=(canPick?'<td>'+(actionable?'<input type="checkbox" data-la-check value="'+row.id+'">':'-')+'</td>':'')
            +'<td class="is-no">'+(start+index+1)+'</td>'
            +'<td class="is-date">'+esc(row.requested_date_label||'-')+'</td>'
            +'<td class="is-employee">'+employee(row)+'</td>'
            +'<td>'+esc(row.position_name)+'</td>'
            +'<td>'+esc(row.department_name)+'</td>'
            +'<td class="is-type">'+typeCell(row)+'</td>'
            +'<td>'+esc(text('leave.oneDay','1 วัน'))+'</td>'
            +'<td>'+esc(row.note||'-')+'</td>'
            +'<td>'+status(row)+'</td>'
            +'<td>'+esc(row.approval_status==='cancelled'?(row.cancel_reason||'-'):(row.decision_note||'-'))+'</td>'
            +'<td><button class="la-button" type="button" data-la-review="'+row.id+'">'+esc(text('leave.review','ตรวจคำขอ'))+'</button></td>'
            +'<td class="is-route"></td>';

          /* ปุ่มสถานะเป็น element จริงที่มี event listener ติดมา ต่อเป็น string ไม่ได้
             (จะกลายเป็น [object HTMLButtonElement]) จึง append เข้า cell ที่เว้นไว้ */
          var routeCell=tr.children[tr.children.length-1];
          if(row.approval_status==='cancelled'){
            routeCell.textContent='-';
          } else if(window.leaveProcessRoute){
            routeCell.appendChild(window.leaveProcessRoute.create(row));
          } else {
            routeCell.textContent='-';
          }
          body.appendChild(tr);
        });

        body.querySelectorAll('[data-la-check]').forEach(function(box){box.addEventListener('change',syncSelected);});
        body.querySelectorAll('[data-la-review]').forEach(function(button){button.addEventListener('click',function(){openReview(Number(button.dataset.laReview));});});

        /* หัวตารางถูกสร้างใหม่ทุกครั้ง จึงต้องผูก listener ตรงนี้ ไม่ใช่ตอน init */
        var checkAll=table.querySelector('[data-la-check-all]');
        if(checkAll){
          checkAll.addEventListener('change',function(){
            body.querySelectorAll('[data-la-check]').forEach(function(box){box.checked=checkAll.checked;});
            syncSelected();
          });
        }
        syncSelected();
      }
      /* โหลดทีละหน้าจาก server — คำขอลาวันหยุดทั้งบริษัทมีเป็นพันแถว
         ถ้าดึงมาทั้งก้อนแล้วตัดหน้าในเบราว์เซอร์ หน้าจะค้างตั้งแต่ตอน parse JSON */
      async function load(page){
        showState(text('leave.loadingRequests','กำลังโหลดคำขอ...'));
        try{
          var query='?filter='+encodeURIComponent(filter)
            +(date?'&date='+encodeURIComponent(date):'')
            +'&page='+encodeURIComponent(page||1)
            +'&per_page='+encodeURIComponent(pager?pager.perPage:20)
            +(employeeQuery.trim()?'&q='+encodeURIComponent(employeeQuery.trim()):'')
            /* สาขาต้องกรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกับ q — คิวแบ่งหน้าที่ SQL */
            +(branchValue&&branchValue!=='all'?'&branch='+encodeURIComponent(branchValue):'')
            +(leaveTypeValue&&leaveTypeValue!=='all'?'&leave_type='+encodeURIComponent(leaveTypeValue):'');
          var response=await fetch(endpoints.queue+query,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
          var data=await response.json();
          if(!response.ok||!data.ok)throw new Error(data.message||'failed');
          records=data.requests||[];
          totalRecords=Number(data.total)||records.length;
          if(pager)pager.apply(data);
          render();
        }catch(error){
          showState(text('leave.loadListError','ไม่สามารถโหลดรายการได้'));
        }
      }
      function openReview(id){var row=records.find(function(item){return item.id===id;});if(!row)return;decisionContext={ids:[id],row:row};reviewContent.innerHTML='<article class="la-review-card is-wide"><small>'+esc(text('leave.employee','พนักงาน'))+'</small><strong>'+esc(row.employee_name)+' · '+esc(row.employee_code)+'</strong></article><article class="la-review-card"><small>'+esc(text('leave.leaveDate','วันที่ลา'))+'</small><strong>'+esc(row.leave_date_label)+'</strong></article><article class="la-review-card"><small>'+esc(text('leave.leaveType','ประเภทการลา'))+'</small><strong>'+esc(typeLabel(row))+'</strong></article><article class="la-review-card is-wide"><small>'+esc(text('leave.employeeNote','หมายเหตุจาก Foreman'))+'</small><strong>'+esc(row.note||'-')+'</strong></article><article class="la-review-card is-wide"><small>'+esc(text('leave.reviewResult','ผลการตรวจ'))+'</small><strong>'+status(row)+'</strong></article>'+(row.approval_status==='cancelled'&&row.cancel_reason?'<article class="la-review-card is-wide"><small>'+esc(text('leave.cancelReason','เหตุผลที่ยกเลิก'))+'</small><strong>'+esc(row.cancel_reason)+'</strong></article>':'')+(row.approval_status!=='cancelled'&&row.decision_note?'<article class="la-review-card is-wide"><small>'+esc(text('leave.supervisorNote','หมายเหตุจาก Supervisor'))+'</small><strong>'+esc(row.decision_note)+'</strong></article>':'')+(row.approval_status==='submitted'&&row.can_decide!==false?'<div class="la-review-actions"><button class="la-button is-danger" type="button" data-review-reject>'+esc(text('leave.rejected','ไม่อนุมัติ'))+'</button><button class="la-button is-primary" type="button" data-review-approve>'+esc(text('leave.approve','อนุมัติ'))+'</button></div>':(row.approval_status==='approved'?'<div class="la-review-actions"><button class="la-button is-danger" type="button" data-review-reject>'+esc(text('leave.rejected','ไม่อนุมัติ'))+'</button></div>':(row.approval_status==='rejected'?'<div class="la-review-actions"><button class="la-button is-primary" type="button" data-review-approve>'+esc(text('leave.approve','อนุมัติ'))+'</button></div>':'')));reviewModal.hidden=false;document.body.style.overflow='hidden';var reject=reviewContent.querySelector('[data-review-reject]'),approve=reviewContent.querySelector('[data-review-approve]');if(reject)reject.addEventListener('click',function(){openConfirm('rejected',[row.id],row);});if(approve)approve.addEventListener('click',function(){openConfirm('approved',[row.id],row);});}
      function closeReview(){reviewModal.hidden=true;document.body.style.overflow='';}
      function openConfirm(decision,ids,row){closeReview();decisionContext={decision:decision,ids:ids,row:row||null};var reject=decision==='rejected';var reverseReject=reject&&row&&row.approval_status==='approved';var reverseApprove=!reject&&row&&row.approval_status==='rejected';confirmModal.querySelector('[data-la-confirm-title]').removeAttribute('data-i18n');confirmModal.querySelector('[data-la-confirm-title]').textContent=reverseReject?text('leave.confirmReverseReject','เปลี่ยนคำขออนุมัติแล้วเป็นไม่อนุมัติ?'):(reverseApprove?text('leave.confirmReverseApprove','เปลี่ยนคำขอไม่อนุมัติเป็นอนุมัติ?'):(reject?text('leave.confirmReject','ยืนยันไม่อนุมัติ'):text('leave.confirmApprove','ยืนยันอนุมัติ')));confirmModal.querySelector('[data-la-confirm-hint]').textContent=reverseReject?text('leave.confirmReverseRejectHint','การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นไม่อนุมัติ และต้องระบุเหตุผลให้ตรวจสอบย้อนหลังได้'):(reverseApprove?text('leave.confirmReverseApproveHint','การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นอนุมัติอีกครั้ง โปรดตรวจสอบเหตุผลเดิมก่อนยืนยัน'):(reject?text('leave.confirmRejectHint','กรุณาระบุเหตุผล เหตุผลเดียวกันจะถูกส่งให้ทุกรายการที่เลือก'):text('leave.confirmApproveHint','อนุมัติแล้วจะถือว่าการลาผ่านทันทีและพร้อมสร้างเอกสาร Bplus')));confirmForm.note.value='';confirmForm.note.required=reject;confirmModal.querySelector('[data-la-confirm-submit]').className='la-button '+(reject?'is-danger':'is-primary');confirmModal.hidden=false;document.body.style.overflow='hidden';}
      function closeConfirm(){confirmModal.hidden=true;document.body.style.overflow='';decisionContext=null;}

      /* แจ้งผลหลังทำรายการ — เรียกหลัง load() เสร็จ ผู้ใช้จะได้เห็นตารางอัปเดตแล้วพร้อมข้อความ */
      function showSuccess(message){
        if (window.otSuccessFeedback) {
          window.otSuccessFeedback.show(message || text('leave.done','ดำเนินการเรียบร้อยแล้ว'));
          return;
        }
        successModal.querySelector('[data-la-success-message]').removeAttribute('data-i18n');
        successModal.querySelector('[data-la-success-message]').textContent=message;
        successModal.hidden=false;
        document.body.style.overflow='hidden';
      }

      function closeSuccess(){if(window.otSuccessFeedback){window.otSuccessFeedback.close();return;}successModal.hidden=true;document.body.style.overflow='';}
      async function decide(event){event.preventDefault();if(!decisionContext)return;var payload={decision:decisionContext.decision,note:confirmForm.note.value};if(payload.decision==='rejected'&&!payload.note.trim()){window.alert(text('leave.rejectReasonRequired','กรุณาระบุเหตุผลที่ไม่อนุมัติ'));return;}var many=decisionContext.ids.length>1;var url=many?endpoints.bulk:endpoints.decisionBase+'/'+decisionContext.ids[0]+'/decision';if(many)payload.request_ids=decisionContext.ids;try{var response=await fetch(url,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(payload)});var data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||text('leave.loadError','ดำเนินการไม่สำเร็จ'));closeConfirm();await load(pager?pager.page:1);showSuccess(data.message||text('leave.done','ดำเนินการเรียบร้อยแล้ว'));}catch(error){window.alert(error.message||text('leave.loadError','ดำเนินการไม่สำเร็จ'));}}
      if(employeeSearchInput){
        /* หน่วงการพิมพ์ ไม่ยิงเซิร์ฟเวอร์ทุกตัวอักษร · ค้นใหม่ต้องกลับไปหน้า 1 */
        employeeSearchInput.addEventListener('input',window.otEmployeeSearch.debounce(function(){employeeQuery=employeeSearchInput.value;load(1);},350));
      }
      var branchSelect=document.querySelector('[data-branch-filter]');
      if(branchSelect){
        // กลับหน้า 1 เสมอ กันอยู่หน้า 3 แล้วผลลัพธ์เหลือ 2 แถวจนเห็นหน้าว่าง
        branchSelect.addEventListener('change',function(){branchValue=branchSelect.value;load(1);});
      }
      /* ตัวกรองสถานะมีทั้งบนแถบเครื่องมือและที่หัวคอลัมน์ — เขียนลงตัวแปรเดียวกัน */
      var filterSelect=root.querySelector('[data-la-filter-select]');
      if(filterSelect){
        filterSelect.addEventListener('change',function(){filter=filterSelect.value;load(1);});
      }
      /* ย้อมทั้งช่องปฏิทินและปุ่มทุกวันให้รู้ว่ากำลังใช้แกนไหนอยู่ เหมือนหน้าอนุมัติ OT */
      function syncDateLabel(){
        var wrap=root.querySelector('[data-la-date-wrap]');
        if(wrap)wrap.classList.toggle('is-active',!!date);
        allDates.classList.toggle('is-active',!date);
        /* โหมดทุกวันไม่ต้องมีข้อความบอกซ้ำ เพราะปุ่ม "ทุกวัน" ที่ย้อมอยู่บอกอยู่แล้ว
           เหลือข้อความไว้เฉพาะตอนเจาะจงวัน ซึ่งปุ่มบอกไม่ได้ว่าเป็นวันไหน */
        root.querySelector('[data-la-date-label]').textContent=date
          ? text('leave.dateOnly','เฉพาะวันที่')+' '+date.split('-').reverse().join('/')
          : '';
      }
      dateInput.addEventListener('change',function(){date=dateInput.value||null;syncDateLabel();load(1);});
      allDates.addEventListener('click',function(){date=null;dateInput.value='';syncDateLabel();load(1);});
      // ปุ่มรีเฟรชถูกถอดออกแล้ว (2026-08-24) — เปลี่ยนตัวกรอง/หน้า ก็โหลดใหม่อยู่แล้ว
      bulkApprove.addEventListener('click',function(){var ids=selectedIds();if(ids.length)openConfirm('approved',ids);});bulkReject.addEventListener('click',function(){var ids=selectedIds();if(ids.length)openConfirm('rejected',ids);});
      reviewModal.querySelector('[data-la-review-close]').addEventListener('click',closeReview);reviewModal.addEventListener('click',function(event){if(event.target===reviewModal)closeReview();});confirmModal.querySelector('[data-la-confirm-close]').addEventListener('click',closeConfirm);confirmModal.querySelector('[data-la-confirm-cancel]').addEventListener('click',closeConfirm);confirmModal.addEventListener('click',function(event){if(event.target===confirmModal)closeConfirm();});confirmForm.addEventListener('submit',decide);
      successModal.querySelector('[data-la-success-close]').addEventListener('click',closeSuccess);
      successModal.addEventListener('click',function(event){if(event.target===successModal)closeSuccess();});
      document.addEventListener('keydown',function(event){if(event.key!=='Escape')return;if(!successModal.hidden)closeSuccess();else if(!confirmModal.hidden)closeConfirm();else if(!reviewModal.hidden)closeReview();});document.addEventListener('insight:languagechange',function(){syncDateLabel();render();if(!reviewModal.hidden&&decisionContext&&decisionContext.row)openReview(decisionContext.row.id);});syncDateLabel();load(1);
    })();
  </script>
@endsection
