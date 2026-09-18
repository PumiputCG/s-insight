@extends('layouts.portal')

@section('title', $layout->name.' · งานพื้นที่ของฉัน')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.myWork.title">งานพื้นที่ของฉัน</span>
@endsection

@section('page-style')
    .a5w-wrap { display:grid; gap:.9rem; width:min(100%, 88rem); margin:0 auto; }
    .a5w-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5w-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:2.25rem; padding:.45rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:650; text-decoration:none; }
    .a5w-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5w-btn.primary { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5w-btn.primary:hover { color:#fff; filter:brightness(1.05); }
    .a5w-btn.subtle { color:var(--muted-light); }

    /* แถวปุ่มบนสุดของพาเนลขวา: ประวัติการส่ง (ซ้าย) · เพิ่มรายการ (ขวา) */
    .a5w-panel-top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; padding-bottom:.6rem; border-bottom:1px solid var(--line-light); }

    .a5w-board { display:grid; grid-template-columns:minmax(0, 1.45fr) minmax(20rem, .72fr); gap:.9rem; align-items:start; }
    .a5w-stage-wrap, .a5w-panel { border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-tint); padding:.8rem; }
    .a5w-stage { position:relative; border-radius: 0.25rem; overflow:auto; background:var(--menu-bg); }
    .a5w-canvas { position:relative; width:100%; margin:0 auto; transition:width .18s ease; transform-origin:top center; }
    .a5w-stage img { display:block; width:100%; height:auto; }
    /* ซูม 5%–300% แบบเดียวกับ Layout viewer */
    .a5z-bar { display:flex; justify-content:flex-end; margin-bottom:.5rem; }
    .a5z-controls { display:inline-flex; align-items:center; gap:.18rem; padding:.18rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5z-btn { width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); cursor:pointer; font-size:.95rem; font-weight:800; }
    .a5z-btn:hover, .a5z-btn:focus-visible { background:var(--panel-soft); color:var(--moss); outline:none; }
    .a5z-btn:disabled { cursor:not-allowed; color:var(--muted-light); opacity:.45; }
    .a5z-value { min-width:3.35rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:var(--panel-soft); color:var(--muted-light); cursor:pointer; font-size:.74rem; font-weight:750; }
    .a5z-value:hover, .a5z-value:focus-visible { color:var(--moss); outline:none; }
    .a5w-marker { position:absolute; transform:translate(-50%, -50%); width:2rem; height:2rem; display:grid; place-items:center; border:2px solid #fff; border-radius:50%; background:#5b7343; color:#fff; box-shadow:0 3px 10px rgb(0 0 0 / 35%); font-size:.78rem; font-weight:800; cursor:pointer; transition:transform .14s ease, background-color .14s ease, opacity .14s ease; }
    .a5w-marker:hover, .a5w-marker:focus-visible { transform:translate(-50%, -50%) scale(1.1); outline:none; }
    .a5w-marker.is-muted { opacity:.42; background:#697063; }
    .a5w-marker.is-mine { width:2.38rem; height:2.38rem; background:#c8964a; z-index:4; animation:a5wPulse 2s infinite; }
    .a5w-marker.is-open { outline:3px solid color-mix(in srgb, var(--moss) 55%, transparent); z-index:6; }
    @keyframes a5wPulse { 0%,100% { box-shadow:0 3px 10px rgb(0 0 0 / 35%); } 50% { box-shadow:0 0 0 8px rgb(200 150 74 / 24%); } }
    /* สีสถานะบน marker จุดของฉัน: เหลือง=รอดำเนินการ, เขียว=ผ่าน, แดง=ปฏิเสธ (ยังไม่ส่ง = ทองเดิม) */
    .a5w-marker.is-mine.st-submitted, .a5w-marker.is-mine.st-resubmitted { background:#dfa321; color:#42350a; }
    .a5w-marker.is-mine.st-passed { background:#2f9461; }
    .a5w-marker.is-mine.st-failed { background:#bf4036; }
    .a5w-legend { display:flex; align-items:center; flex-wrap:wrap; gap:.9rem; margin-top:.55rem; padding:0 .15rem; color:var(--muted-light); font-size:.72rem; }
    .a5w-legend i { display:inline-block; width:.68rem; height:.68rem; margin-right:.32rem; border-radius:50%; vertical-align:-1px; }
    .a5w-legend .lg-pass { background:#2f9461; }
    .a5w-legend .lg-wait { background:#dfa321; }
    .a5w-legend .lg-fail { background:#bf4036; }

    .a5w-panel { display:grid; gap:.75rem; min-height:18rem; }
    .a5w-inline-code { display:inline-flex; align-items:center; gap:.25rem; margin-left:.25rem; color:#c8964a; font-size:.82rem; font-weight:800; white-space:nowrap; }
    .a5w-empty { display:grid; gap:.35rem; place-items:center; margin-top:.85rem; padding:2rem .8rem; color:var(--muted-light); text-align:center; border:1px dashed var(--line-light); border-radius: 0.3rem; font-size:.84rem; }
    .a5w-card-list { display:grid; gap:0; margin-top:.9rem; }
    /* ปุ่มเปิด modal ประวัติ — ย้ายมาอยู่มุมขวาของหัวหน้า แทนป้ายจำนวนจุด (Manager 2026-08-27) */
    .a5w-history-btn { flex:none; display:inline-flex; align-items:center; gap:.42rem; padding:.45rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; font-size:.78rem; font-weight:700; }
    .a5w-history-btn:hover, .a5w-history-btn:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    .a5w-history-btn[hidden] { display:none !important; }
    .a5w-history-btn svg { width:1rem; height:1rem; flex:none; stroke:currentColor; }
    .a5w-history-btn small { color:var(--muted-light); font-size:.72rem; font-weight:650; }

    /* แถวปุ่มทำงาน: แก้ไขรายงาน → กดแล้วจึงโผล่ปุ่มส่ง (Manager 2026-08-27) */
    .a5w-panel-actions { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; justify-content:flex-end; margin-top:.25rem; }
    .a5w-panel-actions[hidden] { display:none !important; }
    .a5w-edit-report { display:inline-flex; align-items:center; min-height:2rem; padding:.34rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; font-size:.78rem; font-weight:750; }
    .a5w-edit-report:hover, .a5w-edit-report:focus-visible { border-color:var(--line-strong); color:var(--light-text); outline:none; }
    .a5w-edit-report[aria-pressed="true"] { border-color:var(--moss); color:var(--moss); background:color-mix(in srgb, var(--moss) 10%, var(--menu-bg)); }
    .a5w-edit-report[hidden] { display:none !important; }
    .a5w-add-card { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; width:max-content; min-height:2rem; padding:.34rem .7rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; cursor:pointer; font-size:.78rem; font-weight:750; }
    /* ผ่านแล้ว/ล็อก = ซ่อนปุ่มเพิ่ม — display:inline-flex ทับ [hidden] ของ UA จึงต้อง override เอง (Manager 2026-07-18) */
    .a5w-add-card[hidden] { display:none !important; }
    .a5w-add-card:hover { filter:brightness(1.08); }
    .a5w-add-icon { width:1.2rem; height:1.2rem; display:grid; place-items:center; border-radius:50%; background:rgb(255 255 255 / 22%); color:#fff; font-size:.9rem; line-height:1; }
    .a5w-card-tools[hidden] { display:none !important; }
    /* modal ยืนยันก่อนส่ง */
    .a5w-confirm-dialog { position:fixed; inset:0; width:min(calc(100% - 2rem), 26rem); margin:auto; border:1px solid var(--line-light); border-radius: 0.34rem; padding:1.1rem 1.15rem 1.15rem; background:var(--panel-soft); color:var(--light-text); box-shadow:0 1.2rem 3.5rem rgb(0 0 0 / 32%); }
    .a5w-confirm-dialog::backdrop { background:rgb(0 0 0 / 52%); }
    .a5w-confirm-dialog h3 { margin:0 0 .4rem; font-size:1rem; font-weight:800; }
    .a5w-confirm-dialog p { margin:0 0 1rem; color:var(--muted-light); font-size:.84rem; line-height:1.55; }
    .a5w-confirm-actions { display:flex; justify-content:flex-end; gap:.45rem; }
    .a5w-card { display:grid; gap:.48rem; padding:.78rem 0; border:0; border-radius:0; background:transparent; }
    .a5w-card + .a5w-card { border-top:1px solid var(--line-light); }
    .a5w-card.is-new { padding:.78rem .65rem; border-radius: 0.25rem; background:rgb(91 141 239 / 8%); box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--moss) 45%, transparent); }
    .a5w-card-title { display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; }
    .a5w-card-title strong { color:var(--light-text); font-size:.86rem; line-height:1.35; }
    .a5w-card-title small, .a5w-card p { color:var(--muted-light); font-size:.74rem; line-height:1.45; }
    .a5w-card p { margin:0; }
    .a5w-images { display:flex; gap:.4rem; overflow:auto; padding-bottom:.1rem; }
    .a5w-images button { flex:none; width:4.4rem; aspect-ratio:1; padding:0; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); overflow:hidden; cursor:zoom-in; }
    .a5w-images img { width:100%; height:100%; object-fit:cover; display:block; }
    .a5w-image-file { height:100%; display:grid; place-items:center; padding:.35rem; color:var(--muted-light); font-size:.65rem; font-weight:800; text-align:center; overflow-wrap:anywhere; }
    .a5w-card-tools { display:flex; align-items:center; justify-content:flex-end; gap:.4rem; flex-wrap:wrap; }
    .a5w-mini-btn { min-height:1.85rem; padding:.25rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:var(--muted-light); cursor:pointer; font-size:.72rem; font-weight:750; }
    .a5w-mini-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5w-mini-btn.danger { background:#c8463a; border-color:#c8463a; color:#fff; }
    .a5w-mini-btn.danger:hover { background:#a9342b; border-color:#a9342b; color:#fff; }
    .a5w-card-tools form { margin:0; }
    .a5w-modal[hidden] { display:none; }
    .a5w-modal { position:fixed; inset:0; z-index:1500; display:grid; place-items:center; padding:1rem; background:rgb(0 0 0 / 48%); }
    .a5w-modal-backdrop { position:absolute; inset:0; border:0; background:transparent; cursor:pointer; }
    .a5w-dialog { position:relative; z-index:1; width:min(100%, 32rem); max-height:min(88vh, 44rem); overflow:auto; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1rem 1.1rem; box-shadow:0 22px 70px rgb(0 0 0 / 38%); }
    .a5w-dialog-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.8rem; margin-bottom:.75rem; }
    .a5w-dialog-head h3 { margin:0 0 .15rem; color:var(--light-text); font-size:.98rem; }
    .a5w-dialog-head p { margin:0; color:var(--muted-light); font-size:.76rem; }
    .a5w-close { width:1.9rem; height:1.9rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; }
    .a5w-close:hover { border-color:#d98a80; color:#d98a80; }
    .a5w-form[hidden] { display:none; }
    .a5w-form { display:grid; gap:.65rem; }
    .a5w-form label { display:grid; gap:.28rem; color:var(--muted-light); font-size:.76rem; font-weight:650; }
    .a5w-form input[type="text"], .a5w-form textarea { width:100%; padding:.52rem .65rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; font-size:.84rem; }
    .a5w-form textarea { min-height:5rem; resize:vertical; }
    .a5w-form input:focus, .a5w-form textarea:focus { outline:none; border-color:var(--moss); }
    .a5w-form input[type="file"] { font-size:.78rem; color:var(--muted-light); }
    .a5w-preview-grid[hidden] { display:none; }
    .a5w-preview-grid { display:flex; flex-wrap:wrap; gap:.45rem; }
    .a5w-preview-item { position:relative; width:4.4rem; aspect-ratio:1; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); overflow:hidden; }
    .a5w-preview-item img { width:100%; height:100%; object-fit:cover; display:block; }
    .a5w-preview-file { height:100%; display:grid; place-items:center; padding:.35rem; color:var(--muted-light); font-size:.65rem; font-weight:800; text-align:center; overflow-wrap:anywhere; }
    .a5w-preview-remove { position:absolute; top:.2rem; right:.2rem; width:1.25rem; height:1.25rem; display:grid; place-items:center; border:1px solid #c8463a; border-radius:50%; background:#c8463a; color:#fff; cursor:pointer; font-size:.86rem; line-height:1; }
    .a5w-preview-remove:hover { background:#a9342b; border-color:#a9342b; color:#fff; }
    .a5w-actions { display:flex; justify-content:flex-end; gap:.45rem; flex-wrap:wrap; }
    .a5w-errors { display:grid; gap:.18rem; padding:.55rem .65rem; border:1px solid rgb(217 138 128 / 45%); border-radius: 0.25rem; background:rgb(217 138 128 / 12%); color:#d98a80; font-size:.76rem; }
    .image-viewer { z-index:2400; }

    /* สถานะงานเดือนนี้ + ปุ่มส่งตรวจ (ปุ่มหลักท้าย panel) */
    .a5w-point-meta[hidden], .a5w-submit-bar[hidden], .a5w-meta-item[hidden] { display:none !important; }
    /* ข้อมูลของจุด: หัวข้อคอลัมน์ซ้าย ค่าอยู่ขวา เรียงตรงกันทุกแถว (Manager 2026-08-27) */
    .a5w-point-meta { display:grid; gap:.35rem; margin:.5rem 0 .2rem; }
    .a5w-meta-item { display:grid; grid-template-columns:7.5rem minmax(0, 1fr); align-items:center; gap:.6rem; }
    .a5w-meta-item dt { color:var(--muted-light); font-size:.76rem; font-weight:650; }
    .a5w-meta-item dd { margin:0; color:var(--light-text); font-size:.8rem; font-weight:700; }
    /* สถานะ: ข้อความล้วน ไม่มีกรอบ ไม่มีจุด (Manager 2026-08-27 — ให้ตรงกับตารางหน้า my-work) */
    .a5w-status-pill { display:inline-flex; align-items:center; padding:0; border:0; border-radius:0; background:none; font-size:.8rem; font-weight:750; color:var(--muted-light); }
    .a5w-status-pill::before { content:none; }
    .a5w-status-pill.st-submitted, .a5w-status-pill.st-resubmitted { color:#c8964a; }
    .a5w-status-pill.st-failed { color:#c62828; }
    .a5w-status-pill.st-passed { color:#4caf7d; }
    /* หมายเหตุจากผู้ประเมินตอนให้ผ่าน — ปุ่มข้างสถานะ + modal กรอบเขียว (Manager 2026-08-27) */
    /* ปุ่มเปิดดูหมายเหตุ: โทนเทากลาง ๆ (Manager 2026-08-27) */
    .a5w-note-btn { display:inline-flex; align-items:center; padding:.26rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; font-size:.74rem; font-weight:750; }
    .a5w-note-btn:hover, .a5w-note-btn:focus-visible { border-color:var(--line-strong); color:var(--light-text); background:var(--hover-soft); outline:none; }
    .a5w-note-btn[hidden] { display:none !important; }
    .a5w-meta-item .is-none[hidden] { display:none !important; }
    .a5w-note-dialog { position:fixed; inset:0; width:min(calc(100% - 2rem), 30rem); max-height:min(80vh, 34rem); overflow:auto; margin:auto; border:1px solid var(--line-light); border-left:4px solid var(--line-strong); border-radius: 0.34rem; padding:1rem 1.1rem 1.15rem; background:var(--panel-soft); color:var(--light-text); box-shadow:0 1.2rem 3.5rem rgb(0 0 0 / 32%); }
    .a5w-note-dialog.is-pass { border-color:rgb(76 175 125 / 55%); border-left-color:#4caf7d; }
    .a5w-note-dialog.is-fail { border-color:rgb(198 40 40 / 55%); border-left-color:#c62828; }
    .a5w-note-dialog::backdrop { background:rgb(0 0 0 / 52%); }
    .a5w-note-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.55rem; }
    .a5w-note-head h3 { margin:0; font-size:.92rem; font-weight:800; }
    .a5w-note-dialog.is-pass .a5w-note-head h3 { color:#4caf7d; }
    .a5w-note-dialog.is-fail .a5w-note-head h3 { color:#c62828; }
    .a5w-note-body { margin:0; font-size:.85rem; line-height:1.6; white-space:pre-line; }
    /* ฟอร์มส่งเป็น display:contents ปุ่มจึงอยู่ในแถวเดียวกับ แก้ไขรายงาน/เพิ่ม ไม่ตกบรรทัด */
    .a5w-submit-bar { display:contents; }
    .a5w-submit-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:2rem; padding:.34rem .85rem; border:1px solid #2f9461; border-radius: 0.25rem; background:#2f9461; color:#fff; font-size:.78rem; font-weight:800; cursor:pointer; transition:filter .15s ease; }
    .a5w-submit-btn:hover { filter:brightness(1.06); }
    .a5w-submit-btn:focus-visible { outline:2px solid color-mix(in srgb, var(--moss) 55%, transparent); outline-offset:2px; }

    @media (max-width: 980px) {
      .a5w-board { grid-template-columns:1fr; }
    }
@endsection

@section('content')
  <div class="a5w-wrap">
    <div class="a5w-top">
      <a class="a5s-back nav-go" href="{{ $backUrl ?? route('area5s.responsible.index') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
    </div>

    {{-- ถอดการ์ดหัวหน้าออก — ชื่อพื้นที่ย้ายไปอยู่บนสุดของพาเนลขวา (Manager 2026-08-27) --}}
    <div class="a5w-board">
      <div class="a5w-stage-wrap">
        <div class="a5z-bar">
          <div class="a5z-controls">
            <button type="button" class="a5z-btn" data-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">-</button>
            <button type="button" class="a5z-value" data-zoom-reset aria-label="รีเซ็ตซูม" data-i18n-aria="a5s.common.resetZoom">100%</button>
            <button type="button" class="a5z-btn" data-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
          </div>
        </div>
        <div class="a5w-stage" data-stage>
          <div class="a5w-canvas" data-canvas>
            <img src="{{ asset('storage/'.$layout->image_path) }}" alt="{{ $layout->name }}" draggable="false">
          </div>
        </div>
        <div class="a5w-legend">
          <span><i class="lg-pass"></i><span data-i18n="a5s.status.passed">ผ่าน</span></span>
          <span><i class="lg-wait"></i><span data-i18n="a5s.status.submitted">รอดำเนินการ</span></span>
          <span><i class="lg-fail"></i><span data-i18n="a5s.status.failed">ปฏิเสธ</span></span>
        </div>
      </div>

      <aside class="a5w-panel" data-panel data-store-base="{{ url('area5s/my-work/points') }}">
        <div data-point-detail hidden>
          {{-- ปุ่มประวัติการส่งอยู่บนสุดของพาเนล ส่วนชื่อพื้นที่ลงไปเป็นหัวข้อในรายการข้อมูล (Manager 2026-08-27) --}}
          <div class="a5w-panel-top">
            <button type="button" class="a5w-history-btn" data-open-history hidden>
              <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>
              <span data-i18n="a5s.common.historySubmit">ประวัติการส่ง</span>
            </button>
            {{-- บรรทัดเดียวกับประวัติการส่ง: ปุ่มเพิ่ม (Manager 2026-08-27) --}}
            <button type="button" class="a5w-add-card" data-add-card>
              <span class="a5w-add-icon">+</span>
              <span data-i18n="a5s.work.add">เพิ่ม</span>
            </button>
          </div>

          {{-- แถวถัดมา: แก้ไขรายงาน — กดแล้วจึงโผล่ปุ่มส่ง (Manager 2026-08-27) --}}
          <div class="a5w-panel-actions" data-panel-actions hidden>
            <button type="button" class="a5w-edit-report" data-toggle-edit hidden aria-pressed="false">
              <span data-i18n="a5s.work.editReport">แก้ไขรายงาน</span>
            </button>
            <form method="POST" class="a5w-submit-bar" data-submit-form hidden>
              @csrf
              <button type="button" class="a5w-submit-btn" data-submit-btn data-i18n="a5s.work.submit">ส่งตรวจ</button>
            </form>
          </div>

          {{-- ข้อมูลของจุด: หัวข้อ + ค่า เรียงเป็นแถวเดียวกันทั้งหมด --}}
          <dl class="a5w-point-meta" data-status-row hidden>
            <div class="a5w-meta-item">
              <dt data-i18n="a5s.common.name">ชื่อ</dt>
              <dd>{{ $layout->name }}</dd>
            </div>
            <div class="a5w-meta-item">
              <dt data-i18n="a5s.common.pointName">ชื่อจุด</dt>
              <dd>
                <span data-point-name>-</span>
                <span class="a5w-inline-code">(<span data-i18n="a5s.common.point">จุด</span> <b data-point-code>-</b>)</span>
              </dd>
            </div>
            <div class="a5w-meta-item">
              <dt data-i18n="a5s.common.status">สถานะ</dt>
              <dd><span class="a5w-status-pill" data-status-pill>-</span></dd>
            </div>
            {{-- "ครั้งที่" = ลำดับการตรวจประเมิน ไม่ใช่จำนวนครั้งที่กดส่ง (Manager 2026-08-27) --}}
            <div class="a5w-meta-item">
              <dt data-i18n="a5s.workspace.roundNumber">ครั้งที่</dt>
              <dd><span class="is-none" data-attempt-empty>-</span><span data-attempt-no hidden></span></dd>
            </div>
            {{-- หมายเหตุผู้ประเมิน (ผ่าน=คำแนะนำ · ปฏิเสธ=เหตุผล) — ยังไม่ตัดสิน = "-" (Manager 2026-08-27) --}}
            <div class="a5w-meta-item" data-note-row>
              <dt data-i18n="a5s.common.noteEvaluator">หมายเหตุ (ผู้ประเมิน)</dt>
              <dd>
                <span class="is-none" data-note-empty>-</span>
                <button type="button" class="a5w-note-btn" data-open-note hidden>
                  <span data-i18n="a5s.common.view">เปิดดู</span>
                </button>
              </dd>
            </div>
            <div class="a5w-meta-item" data-updated-row hidden>
              <dt data-i18n="a5s.work.latestUpdate">อัปเดตล่าสุด</dt>
              <dd data-updated-at></dd>
            </div>
          </dl>

          <div class="a5w-card-list" data-card-list></div>
        </div>
      </aside>
    </div>
  </div>

  <div class="a5w-modal" data-card-modal hidden aria-hidden="true">
    <button type="button" class="a5w-modal-backdrop" data-close-modal aria-label="ปิดฟอร์ม" data-i18n-aria="a5s.common.closeForm"></button>
    <div class="a5w-dialog" role="dialog" aria-modal="true" aria-labelledby="a5w-modal-title" tabindex="-1">
      <div class="a5w-dialog-head">
        <div>
          <h3 id="a5w-modal-title" data-modal-title data-i18n="a5s.work.addCard">เพิ่ม</h3>
          <p data-modal-note data-i18n="a5s.work.addCardNote">กรอกรายละเอียดงาน และแนบรูปอย่างน้อย 1 รูป</p>
        </div>
        <button type="button" class="a5w-close" data-close-modal aria-label="ปิด" data-i18n-aria="a5s.common.close">×</button>
      </div>

      <form class="a5w-form" method="POST" enctype="multipart/form-data" data-card-form>
        @csrf
        <input type="hidden" name="_method" value="" data-form-method disabled>
        <input type="hidden" name="_point_id" value="{{ old('_point_id') }}" data-form-point>
        <input type="hidden" name="_card_id" value="{{ old('_card_id') }}" data-form-card>
        <input type="hidden" name="_form_mode" value="{{ old('_form_mode', 'create') }}" data-form-mode>
        @if ($errors->any())
          <div class="a5w-errors">
            @foreach ($errors->all() as $error)
              <span>{{ $error }}</span>
            @endforeach
          </div>
        @endif
        <label><span data-i18n="a5s.work.cardTitle">หัวข้อ</span>
          <input type="text" name="title" maxlength="191" required value="{{ old('title') }}" data-i18n-placeholder="a5s.work.cardTitlePlaceholder" placeholder="เช่น ทำความสะอาดโต๊ะทำงาน">
        </label>
        <label><span data-i18n="a5s.work.cardDetail">รายละเอียด</span>
          <textarea name="detail" maxlength="3000" data-i18n-placeholder="a5s.work.cardDetailPlaceholder" placeholder="สิ่งที่ดำเนินการ หรือหมายเหตุ">{{ old('detail') }}</textarea>
        </label>
        <label><span data-image-label data-i18n="a5s.work.attach">แนบรูป (JPG / PNG / WEBP / HEIC)</span>
          <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.jpg,.jpeg,.png,.webp,.heic,.heif" multiple data-image-input>
        </label>
        <div class="a5w-preview-grid" data-image-preview-list hidden></div>
        <div class="a5w-actions">
          <button type="button" class="a5w-btn" data-close-modal data-i18n="a5s.manage.cancel">ยกเลิก</button>
          <button type="submit" class="a5w-btn primary" data-submit-label data-i18n="a5s.work.save">บันทึก</button>
        </div>
      </form>
    </div>
  </div>

  {{-- modal ยืนยันก่อนเข้าโหมดแก้ไขรายงาน (Manager 2026-08-27) --}}
  <dialog class="a5w-confirm-dialog" data-edit-confirm aria-labelledby="a5w-edit-title">
    <h3 id="a5w-edit-title" data-i18n="a5s.work.editReport">แก้ไขรายงาน</h3>
    <p data-i18n="a5s.work.editReportConfirm">ต้องการแก้ไขรายงานของจุดนี้ใช่ไหม?</p>
    <div class="a5w-confirm-actions">
      <button type="button" class="a5w-btn" data-edit-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
      <button type="button" class="a5w-btn primary" data-edit-ok data-i18n="a5s.review.confirm">ยืนยัน</button>
    </div>
  </dialog>

  {{-- modal ยืนยันก่อนส่งตรวจ/ส่งอัปเดต/ส่งใหม่ (Manager 2026-08-27) --}}
  <dialog class="a5w-confirm-dialog" data-submit-confirm aria-labelledby="a5w-confirm-title">
    <h3 id="a5w-confirm-title" data-confirm-title data-i18n="a5s.work.submit">ส่งตรวจ</h3>
    <p data-i18n="a5s.work.submitConfirm">ยืนยันส่งข้อมูลจุดนี้ให้ผู้ประเมินตรวจใช่ไหม?</p>
    <div class="a5w-confirm-actions">
      <button type="button" class="a5w-btn" data-confirm-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
      <button type="button" class="a5w-btn primary" data-confirm-ok data-i18n="a5s.review.confirm">ยืนยัน</button>
    </div>
  </dialog>

  {{-- modal หมายเหตุจากผู้ประเมินตอนให้ผ่าน — กรอบเขียวเหมือนกล่องเดิม (Manager 2026-08-27) --}}
  <dialog class="a5w-note-dialog" data-note-dialog aria-labelledby="a5w-note-title">
    <div class="a5w-note-head">
      <h3 id="a5w-note-title" data-i18n="a5s.common.note">หมายเหตุ</h3>
      <button type="button" class="a5w-close" data-close-note aria-label="ปิด" data-i18n-aria="a5s.common.close">×</button>
    </div>
    <p class="a5w-note-body" data-note-body></p>
  </dialog>

  {{-- modal ประวัติการส่ง (แท็บครั้งที่ 1,2,...) ใช้ร่วมกับหน้าตรวจประเมิน --}}
  @include('area5s.partials.history-modal', ['historyDetails' => $historyDetails ?? []])

  <script>
    'use strict';
    (function () {
      const points = @json($points);
      const restorePoint = @json((int) (old('_point_id') ?: ($restorePoint ?? session('responsible_point_id'))));
      const oldInput = {
        mode: @json(old('_form_mode')),
        pointId: @json((int) old('_point_id')),
        cardId: @json((int) old('_card_id')),
        title: @json(old('title')),
        detail: @json(old('detail')),
        hasErrors: @json($errors->any()),
      };
      const createdCardId = @json((int) session('created_card_id'));
      const stage = document.querySelector('[data-stage]');
      const canvas = document.querySelector('[data-canvas]');
      const zoomIn = document.querySelector('[data-zoom-in]');
      const zoomOut = document.querySelector('[data-zoom-out]');
      const zoomReset = document.querySelector('[data-zoom-reset]');
      const panel = document.querySelector('[data-panel]');
      const detail = document.querySelector('[data-point-detail]');
      const modal = document.querySelector('[data-card-modal]');
      const form = document.querySelector('[data-card-form]');
      const list = document.querySelector('[data-card-list]');
      const methodInput = document.querySelector('[data-form-method]');
      const imageInput = document.querySelector('[data-image-input]');
      const imagePreviewList = document.querySelector('[data-image-preview-list]');
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch]);
      const i18n = window.__portalLang || {};
      const t = (key, fallback, replacements = null) => i18n.text ? i18n.text(key, fallback, replacements) : fallback;
      const ownerName = card => {
        const owner = {
          name: card.owner,
          name_th: card.owner_th,
          name_en: card.owner_en,
          name_my: card.owner_my,
        };
        return i18n.name ? i18n.name(owner, card.owner || '-') : (card.owner || '-');
      };
      const imageExt = value => String(value || '').split('?')[0].split('.').pop().toUpperCase();
      const imageButtonHtml = (src, title, index) => {
        const ext = imageExt(src);
        const content = ['HEIC', 'HEIF'].includes(ext)
          ? `<span class="a5w-image-file">${esc(ext)}</span>`
          : `<img src="${esc(src)}" alt="">`;
        return `
          <button type="button" data-image-preview data-image-src="${esc(src)}" data-image-alt="${esc(title)} ${esc(t('a5s.common.imageNumber', 'รูปที่'))} ${index + 1}" aria-label="${esc(t('a5s.common.viewPhoto', 'ดูรูป'))} ${esc(title)}">
            ${content}
          </button>
        `;
      };
      let selected = null;
      let currentCards = [];
      let selectedFiles = [];
      let existingImages = [];
      let removedImageIds = new Set();
      let previewUrls = [];

      // ---- ซูม 5%–300% แบบเดียวกับ Layout viewer ----
      const defaultZoom = .5; // เริ่มที่ 50% ให้เห็นแผนผังทั้งภาพตั้งแต่เปิด (Manager 2026-07-21)
      let zoom = defaultZoom;
      const minZoom = .05; // ซูมออกได้ถึง 5% เพื่อดูภาพรวมแผนผังใหญ่
      const maxZoom = 3;
      // step หยาบเหนือ 50% / ละเอียดใต้ 50% — ไล่ลง 50→40→30→20→10→5
      const stepDown = z => z > .5 ? z - .25 : z - .1;
      const stepUp = z => z >= .5 ? z + .25 : z + .1;

      function setZoom(nextZoom, anchor) {
        const previous = zoom;
        const next = Math.min(maxZoom, Math.max(minZoom, Math.round(nextZoom * 100) / 100));
        // จุดยึดซูม: ปกติ = กลางจอ · ถ้าหมุนล้อเมาส์ = ตำแหน่งเคอร์เซอร์ (ซูมเข้าตรงจุดที่ชี้)
        const ax = anchor ? anchor.x : stage.clientWidth / 2;
        const ay = anchor ? anchor.y : stage.clientHeight / 2;
        const pointX = stage.scrollLeft + ax;
        const pointY = stage.scrollTop + ay;
        zoom = next;
        canvas.style.width = `${zoom * 100}%`;
        if (next !== previous) {
          stage.scrollLeft = pointX * (zoom / previous) - ax;
          stage.scrollTop = pointY * (zoom / previous) - ay;
        }
        zoomReset.textContent = `${Math.round(zoom * 100)}%`;
        zoomOut.disabled = zoom <= minZoom;
        zoomIn.disabled = zoom >= maxZoom;
      }

      zoomOut.addEventListener('click', () => setZoom(stepDown(zoom)));
      zoomIn.addEventListener('click', () => setZoom(stepUp(zoom)));
      zoomReset.addEventListener('click', () => setZoom(defaultZoom));
      // ลูกกลิ้งเมาส์ = ซูมเข้า/ออก ตรงตำแหน่งเคอร์เซอร์
      stage.addEventListener('wheel', e => {
        e.preventDefault();
        const r = stage.getBoundingClientRect();
        setZoom(zoom * (e.deltaY < 0 ? 1.15 : 1 / 1.15), { x: e.clientX - r.left, y: e.clientY - r.top });
      }, { passive: false });
      setZoom(defaultZoom);

      points.forEach(point => {
        const marker = document.createElement('button');
        marker.type = 'button';
        marker.className = 'a5w-marker' + (point.is_mine ? ' is-mine st-' + point.status : ' is-muted');
        marker.style.left = point.x + '%';
        marker.style.top = point.y + '%';
        marker.textContent = point.code;
        marker.dataset.point = point.id;
        marker.setAttribute('aria-label', point.is_mine ? `${t('a5s.common.point', 'จุด')} ${point.code} ${point.name}` : `${t('a5s.common.point', 'จุด')} ${point.code} ${point.name}`);
        marker.addEventListener('click', () => selectPoint(point));
        canvas.appendChild(marker);
      });

      function selectPoint(point) {
        selected = point;
        stage.querySelectorAll('.a5w-marker').forEach(marker => {
          marker.classList.toggle('is-open', Number(marker.dataset.point) === Number(point.id));
        });
        detail.hidden = false;
        document.querySelector('[data-point-name]').textContent = point.name;
        document.querySelector('[data-point-code]').textContent = point.code;
        // ผ่านแล้ว = ล็อกถาวร → ซ่อนปุ่มเพิ่มการ์ด/ส่งตรวจ
        document.querySelector('[data-add-card]').hidden = !point.is_mine || point.locked;

        const statusRow = document.querySelector('[data-status-row]');
        const pill = document.querySelector('[data-status-pill]');
        const submitForm = document.querySelector('[data-submit-form]');
        const submitBtn = document.querySelector('[data-submit-btn]');
        statusRow.hidden = false;
        // ยังไม่ส่งตรวจ (not_started/draft) = "ยังไม่มีข้อมูล" ไม่ใช่ "รอดำเนินการ" (Manager 2026-07-18)
        const statusKey = ['submitted', 'resubmitted', 'passed', 'failed'].includes(point.status) ? point.status : 'no_data';
        pill.className = 'a5w-status-pill st-' + statusKey;
        pill.textContent = t(`a5s.status.${statusKey}`, point.status_label);
        // "ครั้งที่" = ลำดับการตรวจประเมิน · ถ้ากำลังรอตรวจอยู่ให้นับครั้งที่ส่งไปแล้วนั้นด้วย
        // (Manager 2026-08-27) — ตรงกับเลขแถวล่าสุดในโมดัลประวัติและคอลัมน์ประวัติในตาราง
        const pendingNow = ['submitted', 'resubmitted'].includes(point.status);
        const attemptNo = (point.history || []).length + (pendingNow ? 1 : 0);
        const attemptEmpty = document.querySelector('[data-attempt-empty]');
        const attemptValue = document.querySelector('[data-attempt-no]');
        if (attemptEmpty) attemptEmpty.hidden = attemptNo > 0;
        if (attemptValue) {
          attemptValue.hidden = attemptNo === 0;
          attemptValue.textContent = attemptNo > 0 ? String(attemptNo) : '';
        }
        // ปุ่มส่งโผล่ก็ต่อเมื่อกด "แก้ไขรายงาน" แล้วเท่านั้น (Manager 2026-08-27) — เห็นได้ใน syncEditMode()
        canSubmitPoint = point.is_mine && !point.locked && (point.cards || []).length > 0;
        if (canSubmitPoint) {
          submitForm.action = point.submit_url;
          submitBtn.textContent = point.status === 'failed' ? t('a5s.work.resubmit', 'ส่งใหม่') : (point.status === 'submitted' || point.status === 'resubmitted' ? t('a5s.work.updateSubmit', 'ส่งอัปเดต') : t('a5s.work.submit', 'ส่งตรวจ'));
        }
        editMode = false;

        // หมายเหตุผู้ประเมิน: ผ่าน = คำแนะนำ · ปฏิเสธ = เหตุผล (+คำแนะนำถ้ามี) — เปิดดูใน modal
        // (Manager 2026-08-27: เดิมเหตุผลที่ถูกปฏิเสธเป็นกล่องแดงเต็มบรรทัด `.a5w-fail-box`)
        const noteEmpty = document.querySelector('[data-note-empty]');
        const noteBtn = document.querySelector('[data-open-note]');
        let noteText = null;
        let noteKind = null;
        if (point.status === 'passed' && point.advice) {
          noteText = point.advice;
          noteKind = 'is-pass';
        } else if (point.status === 'failed' && (point.fail_reason || point.advice)) {
          noteText = [
            point.fail_reason ? `${t('a5s.work.reason', 'เหตุผล:')} ${point.fail_reason}` : null,
            point.advice ? `${t('a5s.work.advice', 'คำแนะนำ:')} ${point.advice}` : null,
          ].filter(Boolean).join('\n\n');
          noteKind = 'is-fail';
        }
        if (noteEmpty) noteEmpty.hidden = !!noteText;
        if (noteBtn) {
          noteBtn.hidden = !noteText;
          noteBtn.onclick = noteText ? () => openNote(noteText, noteKind) : null;
        }

        // เวลาอัปเดตล่าสุดของจุด (ย้ายมาจากหัวการ์ดแต่ละใบ — Manager 2026-08-27)
        const updatedRow = document.querySelector('[data-updated-row]');
        const updatedAt = document.querySelector('[data-updated-at]');
        if (updatedRow) updatedRow.hidden = !point.updated_at;
        if (updatedAt) updatedAt.textContent = point.updated_at || '';

        renderCards(point.cards || []);
        updateHistoryButton(point);
      }

      // ---- modal หมายเหตุ ----
      const noteDialog = document.querySelector('[data-note-dialog]');
      const noteBody = document.querySelector('[data-note-body]');
      let noteOpener = null;

      function openNote(text, resultClass) {
        if (!noteDialog) return;
        noteOpener = document.activeElement;
        noteDialog.classList.remove('is-pass', 'is-fail');
        noteDialog.classList.add(resultClass || 'is-pass');
        if (noteBody) noteBody.textContent = text || '';
        noteDialog.showModal();
      }

      noteDialog?.querySelector('[data-close-note]')?.addEventListener('click', () => noteDialog.close());
      noteDialog?.addEventListener('click', e => { if (e.target === noteDialog) noteDialog.close(); });
      noteDialog?.addEventListener('close', () => noteOpener?.focus());

      // ปุ่มเปิด modal ประวัติ (แท็บครั้งที่ 1,2,...) — ซ่อนถ้าจุดนี้ยังไม่เคยส่ง (Manager 2026-07-31)
      const historyBtn = document.querySelector('[data-open-history]');
      const historyCount = document.querySelector('[data-history-count]');
      function updateHistoryButton(point) {
        if (!historyBtn) return;
        const modal = window.A5sHistoryModal;
        const has = modal ? modal.has(point.id) : (point.history || []).length > 0;
        historyBtn.hidden = !has;
        if (!has) return;
        const times = (point.history || []).length;
        if (historyCount) historyCount.textContent = times ? `${times} ${t('a5s.review.times', 'ครั้ง')}` : '';
        historyBtn.onclick = () => window.A5sHistoryModal?.open(
          point.id,
          `${t('a5s.common.point', 'จุด')} ${point.code}`,
          @json($layout->name)
        );
      }

      function renderCards(cards) {
        currentCards = cards;
        if (!cards.length) {
          list.innerHTML = `<div class="a5w-empty"><strong>${esc(t('a5s.work.noCards', 'ยังไม่มีรายการ'))}</strong><span>${esc(t('a5s.work.addFirst', 'กด + เพื่อเพิ่มรายการแรก'))}</span></div>`;
          return;
        }

        list.innerHTML = cards.map(card => `
          <article class="a5w-card ${Number(card.id) === createdCardId ? 'is-new' : ''}">
            <div class="a5w-card-title">
              <strong>${esc(card.title)}</strong>
            </div>
            ${card.detail ? `<p>${esc(card.detail)}</p>` : ''}
            <small>${esc(t('a5s.common.by', 'โดย'))} ${esc(ownerName(card))}</small>
            <div class="a5w-images">
              ${(card.images || []).map((src, index) => imageButtonHtml(src, card.title, index)).join('')}
            </div>
            ${card.can_manage && !(selected && selected.locked) ? `
              <div class="a5w-card-tools" data-card-tools ${editMode ? '' : 'hidden'}>
                <button type="button" class="a5w-mini-btn" data-edit-card="${card.id}">${esc(t('a5s.work.editCard', 'แก้ไข'))}</button>
                <form method="POST" action="${esc(card.delete_url || '#')}" data-delete-card>
                  <input type="hidden" name="_token" value="${esc(csrf)}">
                  <input type="hidden" name="_method" value="DELETE">
                  <button type="submit" class="a5w-mini-btn danger">${esc(t('a5s.manage.delete', 'ลบ'))}</button>
                </form>
              </div>
            ` : ''}
          </article>
        `).join('');
        syncEditMode();
      }

      // ---- โหมดแก้ไขรายงาน: ปุ่ม แก้ไข/ลบ ในการ์ดจะโผล่ก็ต่อเมื่อกด "แก้ไขรายงาน" (Manager 2026-08-27) ----
      let editMode = false;
      let canSubmitPoint = false;
      const editToggle = document.querySelector('[data-toggle-edit]');
      const panelActions = document.querySelector('[data-panel-actions]');

      function syncEditMode() {
        document.querySelectorAll('[data-card-tools]').forEach(tools => { tools.hidden = !editMode; });
        const manageable = !!(selected && selected.is_mine && !selected.locked && (selected.cards || []).some(c => c.can_manage));
        // เข้าโหมดแก้ไขแล้วซ่อนปุ่มไปเลย ไม่มีปุ่ม "เสร็จสิ้นการแก้ไข" (Manager 2026-08-27)
        if (editToggle) editToggle.hidden = !manageable || editMode;
        // ปุ่มส่งขึ้นเฉพาะตอนอยู่ในโหมดแก้ไขรายงาน
        const submitForm = document.querySelector('[data-submit-form]');
        if (submitForm) submitForm.hidden = !(editMode && canSubmitPoint);
        if (panelActions) panelActions.hidden = !manageable && !(editMode && canSubmitPoint);
      }

      // กด "แก้ไขรายงาน" → ยืนยันใน modal ก่อน จึงเข้าโหมดแก้ไข (Manager 2026-08-27)
      const editConfirm = document.querySelector('[data-edit-confirm]');
      editToggle?.addEventListener('click', () => {
        if (!editConfirm) { editMode = true; syncEditMode(); return; }
        editConfirm.showModal();
      });
      editConfirm?.querySelector('[data-edit-cancel]')?.addEventListener('click', () => editConfirm.close());
      editConfirm?.addEventListener('click', e => { if (e.target === editConfirm) editConfirm.close(); });
      editConfirm?.querySelector('[data-edit-ok]')?.addEventListener('click', () => {
        editConfirm.close();
        editMode = true;
        syncEditMode();
      });

      // ---- ยืนยันก่อนส่ง (ส่งตรวจ / ส่งอัปเดต / ส่งใหม่) ----
      const submitConfirm = document.querySelector('[data-submit-confirm]');
      const submitConfirmTitle = submitConfirm?.querySelector('[data-confirm-title]');
      document.querySelector('[data-submit-btn]')?.addEventListener('click', () => {
        const form = document.querySelector('[data-submit-form]');
        if (!form || form.hidden) return;
        if (!submitConfirm) { form.submit(); return; }
        if (submitConfirmTitle) submitConfirmTitle.textContent = document.querySelector('[data-submit-btn]').textContent;
        submitConfirm.showModal();
      });
      submitConfirm?.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => submitConfirm.close());
      submitConfirm?.addEventListener('click', e => { if (e.target === submitConfirm) submitConfirm.close(); });
      submitConfirm?.querySelector('[data-confirm-ok]')?.addEventListener('click', () => {
        submitConfirm.close();
        document.querySelector('[data-submit-form]')?.submit();
      });

      function setModal(open) {
        modal.hidden = !open;
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) modal.querySelector('.a5w-dialog')?.focus();
      }

      function openCardModal(mode, card = null) {
        if (!selected || !selected.is_mine) return;
        const isEdit = mode === 'edit' && card;
        form.action = isEdit ? card.update_url : `${panel.dataset.storeBase}/${selected.id}/cards`;
        methodInput.disabled = !isEdit;
        methodInput.value = isEdit ? 'PUT' : '';
        form.querySelector('[data-form-mode]').value = isEdit ? 'edit' : 'create';
        form.querySelector('[data-form-point]').value = selected.id;
        form.querySelector('[data-form-card]').value = isEdit ? card.id : '';
        form.querySelector('input[name="title"]').value = isEdit ? (card.title || '') : '';
        form.querySelector('textarea[name="detail"]').value = isEdit ? (card.detail || '') : '';
        imageInput.required = !isEdit;
        imageInput.value = '';
        resetSelectedImages(isEdit ? (card.image_items || []) : []);
        document.querySelector('[data-modal-title]').textContent = isEdit ? t('a5s.work.editCard', 'แก้ไข') : t('a5s.work.addCard', 'เพิ่ม');
        document.querySelector('[data-modal-note]').textContent = isEdit ? t('a5s.work.editCardNote', 'แก้หัวข้อ รายละเอียด หรือรูปภาพ') : t('a5s.work.addCardNote', 'กรอกรายละเอียดงาน และแนบรูปอย่างน้อย 1 รูป');
        document.querySelector('[data-image-label]').textContent = isEdit ? t('a5s.work.attachMore', 'แนบรูปเพิ่ม (ไม่บังคับ)') : t('a5s.work.attach', 'แนบรูป (JPG / PNG / WEBP / HEIC)');
        document.querySelector('[data-submit-label]').textContent = t('a5s.work.save', 'บันทึก');
        setModal(true);
        form.querySelector('input[name="title"]')?.focus();
      }

      function resetSelectedImages(images = []) {
        previewUrls.forEach(url => URL.revokeObjectURL(url));
        previewUrls = [];
        selectedFiles = [];
        existingImages = images;
        removedImageIds = new Set();
        syncImageInput();
        renderSelectedImages();
      }

      function syncImageInput() {
        if (typeof DataTransfer === 'undefined') return;
        const transfer = new DataTransfer();
        selectedFiles.forEach(file => transfer.items.add(file));
        imageInput.files = transfer.files;
      }

      function renderSelectedImages() {
        previewUrls.forEach(url => URL.revokeObjectURL(url));
        previewUrls = [];
        imagePreviewList.innerHTML = '';
        const visibleExisting = existingImages.filter(image => !removedImageIds.has(Number(image.id)));
        imagePreviewList.hidden = visibleExisting.length + selectedFiles.length === 0;

        visibleExisting.forEach(image => {
          const src = image.src || '';
          const ext = imageExt(src);
          const item = document.createElement('span');
          item.className = 'a5w-preview-item';
          const content = ['HEIC', 'HEIF'].includes(ext)
            ? `<span class="a5w-preview-file">${esc(ext || 'FILE')}</span>`
            : `<img src="${esc(src)}" alt="">`;
          item.innerHTML = `${content}<button type="button" class="a5w-preview-remove" data-remove-existing-image="${Number(image.id)}" aria-label="${esc(t('a5s.common.removeImage', 'นำรูปออก'))}">×</button>`;
          imagePreviewList.appendChild(item);
        });

        selectedFiles.forEach((file, index) => {
          const ext = (file.name.split('.').pop() || '').toUpperCase();
          const item = document.createElement('span');
          item.className = 'a5w-preview-item';
          const canPreview = file.type.startsWith('image/') && !['HEIC', 'HEIF'].includes(ext);
          if (canPreview) {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);
            item.innerHTML = `<img src="${esc(url)}" alt=""><button type="button" class="a5w-preview-remove" data-remove-image="${index}" aria-label="${esc(t('a5s.common.removeImage', 'นำรูปออก'))} ${esc(file.name)}">×</button>`;
          } else {
            item.innerHTML = `<span class="a5w-preview-file">${esc(ext || 'FILE')}</span><button type="button" class="a5w-preview-remove" data-remove-image="${index}" aria-label="${esc(t('a5s.common.removeFile', 'นำไฟล์ออก'))} ${esc(file.name)}">×</button>`;
          }
          imagePreviewList.appendChild(item);
        });

        removedImageIds.forEach(id => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'remove_images[]';
          input.value = String(id);
          imagePreviewList.appendChild(input);
        });
      }

      function restoreModalAfterValidation() {
        if (!oldInput.hasErrors || !selected) return;

        const card = currentCards.find(item => Number(item.id) === Number(oldInput.cardId));
        openCardModal(oldInput.mode === 'edit' && card ? 'edit' : 'create', card || null);
        form.querySelector('input[name="title"]').value = oldInput.title || '';
        form.querySelector('textarea[name="detail"]').value = oldInput.detail || '';
      }

      document.querySelector('[data-add-card]')?.addEventListener('click', () => {
        openCardModal('create');
      });

      imageInput.addEventListener('change', () => {
        const incoming = Array.from(imageInput.files || []);
        if (!incoming.length) return;
        const total = selectedFiles.length + incoming.length;
        selectedFiles = selectedFiles.concat(incoming).slice(0, 8);
        if (total > 8) {
          alert(t('a5s.work.maxImages', 'แนบภาพได้สูงสุด 8 ภาพต่อรายการ'));
        }
        syncImageInput();
        renderSelectedImages();
      });

      imagePreviewList.addEventListener('click', e => {
        const existingBtn = e.target.closest('[data-remove-existing-image]');
        if (existingBtn) {
          removedImageIds.add(Number(existingBtn.dataset.removeExistingImage));
          renderSelectedImages();
          return;
        }

        const btn = e.target.closest('[data-remove-image]');
        if (btn) {
          selectedFiles.splice(Number(btn.dataset.removeImage), 1);
          syncImageInput();
          renderSelectedImages();
        }
      });

      document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => setModal(false));
      });

      list.addEventListener('click', e => {
        const edit = e.target.closest('[data-edit-card]');
        if (edit) {
          const card = currentCards.find(item => Number(item.id) === Number(edit.dataset.editCard));
          if (card) openCardModal('edit', card);
          return;
        }
      });

      list.addEventListener('submit', e => {
        if (!e.target.matches('[data-delete-card]')) return;
        if (!confirm(`${t('a5s.manage.delete', 'ลบ')} card?`)) e.preventDefault();
      });

      document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.hidden) setModal(false);
      });

      document.addEventListener('insight:languagechange', () => {
        if (selected) selectPoint(selected);
      });

      const initial = points.find(point => Number(point.id) === (oldInput.pointId || restorePoint)) || points.find(point => point.is_mine);
      if (initial) {
        selectPoint(initial);
        restoreModalAfterValidation();
      }
    })();
  </script>
@endsection
