@extends('layouts.portal')

@section('title', $layout->name.' · ตรวจประเมิน')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.review.title">ตรวจประเมิน</span>
@endsection

@section('page-style')
    .a5e-wrap { display:grid; gap:.9rem; width:min(100%, 88rem); margin:0 auto; }
    .a5e-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5e-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:2.25rem; padding:.45rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:650; text-decoration:none; }
    .a5e-btn:hover, .a5e-btn:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }

    /* แถวปุ่มบนสุดของพาเนล + รายการข้อมูลจุด — โครงเดียวกับหน้าบันทึกงาน (Manager 2026-08-27) */
    .a5e-panel-top { display:flex; align-items:center; justify-content:flex-start; gap:.75rem; padding-bottom:.6rem; border-bottom:1px solid var(--line-light); }
    .a5e-panel-top:empty, .a5e-panel-top:has(> [hidden]:only-child) { display:none; }
    .a5e-point-meta { display:grid; gap:.35rem; margin:.5rem 0 .2rem; }
    .a5e-meta-item { display:grid; grid-template-columns:7.5rem minmax(0, 1fr); align-items:center; gap:.6rem; }
    .a5e-meta-item dt { color:var(--muted-light); font-size:.76rem; font-weight:650; }
    .a5e-meta-item dd { margin:0; color:var(--light-text); font-size:.8rem; font-weight:700; }

    .a5e-board { display:grid; grid-template-columns:minmax(0, 1.45fr) minmax(20rem, .72fr); gap:.9rem; align-items:start; }
    .a5e-stage-wrap, .a5e-panel { border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-tint); padding:.8rem; }
    .a5e-stage { position:relative; border-radius: 0.25rem; overflow:auto; background:var(--menu-bg); }
    .a5e-canvas { position:relative; width:100%; margin:0 auto; transition:width .18s ease; transform-origin:top center; }
    .a5e-stage img { display:block; width:100%; height:auto; }
    /* ซูม 5%–300% แบบเดียวกับ Layout viewer */
    .a5z-bar { display:flex; justify-content:flex-end; margin-bottom:.5rem; }
    .a5z-controls { display:inline-flex; align-items:center; gap:.18rem; padding:.18rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5z-btn { width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); cursor:pointer; font-size:.95rem; font-weight:800; }
    .a5z-btn:hover, .a5z-btn:focus-visible { background:var(--panel-soft); color:var(--moss); outline:none; }
    .a5z-btn:disabled { cursor:not-allowed; color:var(--muted-light); opacity:.45; }
    .a5z-value { min-width:3.35rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:var(--panel-soft); color:var(--muted-light); cursor:pointer; font-size:.74rem; font-weight:750; }
    .a5z-value:hover, .a5z-value:focus-visible { color:var(--moss); outline:none; }
    .a5e-marker { position:absolute; transform:translate(-50%, -50%); width:2rem; height:2rem; display:grid; place-items:center; border:2px solid #fff; border-radius:50%; background:#697063; color:#fff; box-shadow:0 3px 10px rgb(0 0 0 / 35%); font-size:.78rem; font-weight:800; cursor:pointer; opacity:.46; transition:transform .14s ease, background-color .14s ease, opacity .14s ease; }
    .a5e-marker:hover, .a5e-marker:focus-visible { transform:translate(-50%, -50%) scale(1.1); outline:none; }
    .a5e-marker.is-reviewable { width:2.38rem; height:2.38rem; background:#757d70; opacity:1; z-index:4; }
    .a5e-marker.is-open { outline:3px solid color-mix(in srgb, var(--moss) 55%, transparent); z-index:6; }
    /* สีสถานะบน marker จุดที่ตรวจได้: เหลือง=รอดำเนินการ, เขียว=ผ่าน, แดง=ปฏิเสธ, เทา=ยังไม่มีข้อมูล */
    .a5e-marker.is-reviewable.st-submitted, .a5e-marker.is-reviewable.st-resubmitted { background:#dfa321; color:#42350a; }
    .a5e-marker.is-reviewable.st-passed { background:#2f9461; }
    .a5e-marker.is-reviewable.st-failed { background:#bf4036; }
    .a5e-legend { display:flex; align-items:center; flex-wrap:wrap; gap:.9rem; margin-top:.55rem; padding:0 .15rem; color:var(--muted-light); font-size:.72rem; }
    .a5e-legend i { display:inline-block; width:.68rem; height:.68rem; margin-right:.32rem; border-radius:50%; vertical-align:-1px; }
    .a5e-legend .lg-pass { background:#2f9461; }
    .a5e-legend .lg-wait { background:#dfa321; }
    .a5e-legend .lg-fail { background:#bf4036; }
    .a5e-legend .lg-none { background:#757d70; }

    .a5e-panel { display:grid; gap:.75rem; min-height:18rem; }
    .a5e-empty[hidden], .a5e-point-head[hidden], .a5e-decision[hidden], .a5e-note[hidden] { display:none !important; }
    .a5e-empty { display:grid; gap:.35rem; place-items:center; margin-top:.85rem; padding:2rem .8rem; color:var(--muted-light); text-align:center; border:1px dashed var(--line-light); border-radius: 0.3rem; font-size:.84rem; }
    .a5e-point-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.65rem; }
    .a5e-point-head h3 { margin:0 0 .15rem; color:var(--light-text); font-size:.96rem; }
    .a5e-inline-code { display:inline-flex; align-items:center; gap:.25rem; margin-left:.25rem; color:var(--moss); font-size:.82rem; font-weight:800; white-space:nowrap; }
    /* สถานะ: ข้อความล้วน ไม่มีกรอบ ไม่มีจุด (Manager 2026-08-27) */
    .a5e-status { display:inline-flex; align-items:center; padding:0; border:0; border-radius:0; background:none; color:var(--muted-light); font-size:.8rem; font-weight:750; white-space:nowrap; }
    .a5e-status::before { content:none; }
    .a5e-status.is-pass { color:#4caf7d; }
    .a5e-status.is-fail { color:#c62828; }
    .a5e-status.is-pending { color:#c8964a; }
    .a5e-meta { display:flex; gap:.65rem; flex-wrap:wrap; color:var(--muted-light); font-size:.74rem; }
    .a5e-meta b { color:var(--light-text); }

    .a5e-cards { display:grid; gap:0; margin-top:.2rem; }
    .a5e-card { display:grid; gap:.48rem; padding:.78rem 0; border-top:1px solid var(--line-light); }
    .a5e-card:first-child { border-top:0; }
    .a5e-card-title { display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; }
    .a5e-card-title strong { color:var(--light-text); font-size:.86rem; line-height:1.35; }
    .a5e-card-title small, .a5e-card p, .a5e-card small { color:var(--muted-light); font-size:.74rem; line-height:1.45; }
    .a5e-card p { margin:0; white-space:pre-line; }
    .a5e-images { display:flex; gap:.4rem; overflow:auto; padding-bottom:.1rem; }
    .a5e-images button { flex:none; width:4.4rem; aspect-ratio:1; padding:0; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); overflow:hidden; cursor:zoom-in; }
    .a5e-images img { width:100%; height:100%; object-fit:cover; display:block; }
    .a5e-image-file { height:100%; display:grid; place-items:center; padding:.35rem; color:var(--muted-light); font-size:.65rem; font-weight:800; text-align:center; overflow-wrap:anywhere; }

    /* ปุ่มตัดสิน: ทึบสี ตัวอักษรขาว เต็มความกว้างครึ่งหนึ่ง ให้เห็นชัด (Manager 2026-08-27) */
    .a5e-decision { display:grid; gap:.55rem; padding-top:.75rem; border-top:1px solid var(--line-light); }
    .a5e-choice-row { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.55rem; }
    /* ปุ่มแก้ไขการประเมิน (จุดที่ตัดสินแล้ว) */
    .a5e-edit-decision { width:100%; min-height:2.6rem; margin-top:.2rem; padding:.55rem 1.1rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; font-size:.84rem; font-weight:800; cursor:pointer; transition:filter .16s ease; }
    .a5e-edit-decision:hover, .a5e-edit-decision:focus-visible { filter:brightness(1.08); outline:none; }
    .a5c-desc { margin:.4rem 0 0; color:var(--muted-light); font-size:.85rem; line-height:1.55; }
    /* ปุ่มเปิด modal ประวัติ (Manager 2026-07-31) */
    .a5e-history-btn { flex:none; display:inline-flex; align-items:center; gap:.42rem; padding:.45rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; font-size:.78rem; font-weight:700; }
    .a5e-history-btn:hover, .a5e-history-btn:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    .a5e-history-btn[hidden] { display:none !important; }
    .a5e-history-btn svg { width:1rem; height:1rem; flex:none; stroke:currentColor; }

    /* ประวัติการส่งครั้งก่อน (ไม่มีภาพ) (Manager 2026-07-24) */
    .a5e-decision-btn { min-height:2.6rem; padding:.55rem 1rem; border:1px solid transparent; border-radius: 0.25rem; color:#fff; cursor:pointer; font-size:.84rem; font-weight:800; }
    .a5e-decision-btn.pass { border-color:#2f9461; background:#2f9461; }
    .a5e-decision-btn.fail { border-color:#bf4036; background:#bf4036; }
    .a5e-decision-btn:hover, .a5e-decision-btn:focus-visible { filter:brightness(1.08); outline:none; }
    /* Modal ยืนยันผ่าน/ปฏิเสธ + แนบหมายเหตุ (Manager 2026-07-18) */
    .a5c-modal[hidden] { display:none; }
    .a5c-modal { position:fixed; inset:0; z-index:2300; display:grid; place-items:center; padding:1rem; background:rgb(0 0 0 / 48%); }
    .a5c-backdrop { position:absolute; inset:0; border:0; background:transparent; cursor:pointer; }
    .a5c-dialog { position:relative; z-index:1; width:min(100%, 26rem); border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1rem 1.1rem; box-shadow:0 22px 70px rgb(0 0 0 / 38%); }
    .a5c-dialog h3 { margin:0 0 .2rem; color:var(--light-text); font-size:.98rem; }
    .a5c-point { margin:0 0 .7rem; color:var(--muted-light); font-size:.78rem; }
    .a5c-check { display:flex; align-items:center; gap:.45rem; color:var(--light-text); font-size:.8rem; font-weight:650; cursor:pointer; user-select:none; }
    .a5c-check input { width:.95rem; height:.95rem; accent-color:var(--moss); cursor:pointer; }
    .a5c-note[hidden] { display:none; }
    .a5c-note { width:100%; min-height:5rem; margin-top:.55rem; resize:vertical; padding:.55rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; }
    .a5c-note:focus { outline:none; border-color:var(--moss); }
    .a5c-actions { display:flex; justify-content:flex-end; gap:.45rem; margin-top:.85rem; }
    .a5c-confirm { min-height:2.05rem; padding:.4rem .95rem; border:1px solid transparent; border-radius: 0.25rem; color:#fff; cursor:pointer; font-size:.78rem; font-weight:800; }
    .a5c-confirm.pass { background:#2f9461; border-color:#2f9461; }
    .a5c-confirm.fail { background:#bf4036; border-color:#bf4036; }
    /* ปุ่มยืนยัน "แก้ไข" ใช้สีเดียวกับปุ่มแก้ไขการประเมิน (Manager 2026-08-27) */
    .a5c-confirm.edit { background:var(--moss); border-color:var(--moss); }
    .a5c-confirm:hover, .a5c-confirm:focus-visible { filter:brightness(1.07); outline:none; }
    /* ปุ่ม + modal หมายเหตุผลตรวจ (Manager 2026-08-27) */
    /* ปุ่มเปิดดูหมายเหตุ: โทนเทากลาง ๆ ไม่แย่งความสนใจจากปุ่มตัดสิน (Manager 2026-08-27) */
    .a5e-note-btn { display:inline-flex; align-items:center; padding:.26rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; font-size:.74rem; font-weight:750; }
    .a5e-note-btn:hover, .a5e-note-btn:focus-visible { border-color:var(--line-strong); color:var(--light-text); background:var(--hover-soft); outline:none; }
    .a5e-note-btn[hidden] { display:none !important; }
    .a5e-meta-item .is-none { color:var(--muted-light); font-weight:600; }
    .a5e-meta-item .is-none[hidden] { display:none !important; }
    .a5e-note-dialog { position:fixed; inset:0; width:min(calc(100% - 2rem), 30rem); max-height:min(80vh, 34rem); overflow:auto; margin:auto; border:1px solid var(--line-light); border-left:4px solid var(--line-strong); border-radius: 0.34rem; padding:1rem 1.1rem 1.15rem; background:var(--panel-soft); color:var(--light-text); box-shadow:0 1.2rem 3.5rem rgb(0 0 0 / 32%); }
    .a5e-note-dialog.is-pass { border-color:rgb(76 175 125 / 55%); border-left-color:#4caf7d; }
    .a5e-note-dialog.is-fail { border-color:rgb(191 64 54 / 55%); border-left-color:#bf4036; }
    .a5e-note-dialog::backdrop { background:rgb(0 0 0 / 52%); }
    .a5e-note-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.55rem; }
    .a5e-note-head h3 { margin:0; font-size:.92rem; font-weight:800; }
    .a5e-note-dialog.is-pass .a5e-note-head h3 { color:#4caf7d; }
    .a5e-note-dialog.is-fail .a5e-note-head h3 { color:#bf4036; }
    .a5e-note-close { width:1.9rem; height:1.9rem; flex:none; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; }
    .a5e-note-close:hover, .a5e-note-close:focus-visible { border-color:var(--line-strong); outline:none; }
    .a5e-note-body { margin:0; font-size:.85rem; line-height:1.6; white-space:pre-line; }
    .a5e-note { padding:.62rem .75rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); font-size:.78rem; line-height:1.45; }
    .a5e-note.is-pass { border-color:rgb(76 175 125 / 45%); background:rgb(76 175 125 / 8%); color:#4caf7d; }
    .a5e-note.is-fail { border-color:#9f2f28; background:#bf4036; color:#fff; font-weight:800; }
    .a5e-errors { display:grid; gap:.18rem; padding:.55rem .65rem; border:1px solid rgb(217 138 128 / 45%); border-radius: 0.25rem; background:rgb(217 138 128 / 12%); color:#d98a80; font-size:.76rem; }
    .flash.success { padding:.6rem .9rem; border:1px solid var(--moss); border-radius: 0.25rem; color:var(--moss); background:rgb(91 141 239 / 12%); font-size:.85rem; }
    .flash.error { padding:.6rem .9rem; border:1px solid #d98a80; border-radius: 0.25rem; color:#d98a80; background:rgb(217 138 128 / 12%); font-size:.85rem; }
    .image-viewer { z-index:2400; }

    @media (max-width: 980px) {
      .a5e-board { grid-template-columns:1fr; }
    }
@endsection

@section('content')
  <div class="a5e-wrap">
    <div class="a5e-top">
      <a class="a5s-back nav-go" href="{{ $backUrl ?? route('area5s.responsible.index', ['mode' => 'review']) }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
    </div>

    {{-- ถอดการ์ดหัวหน้า + ปุ่มภาพรวมออก ให้เหมือนหน้าบันทึกงาน (Manager 2026-08-27) --}}
    <div class="a5e-board">
      <div class="a5e-stage-wrap">
        <div class="a5z-bar">
          <div class="a5z-controls">
            <button type="button" class="a5z-btn" data-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">-</button>
            <button type="button" class="a5z-value" data-zoom-reset aria-label="รีเซ็ตซูม" data-i18n-aria="a5s.common.resetZoom">100%</button>
            <button type="button" class="a5z-btn" data-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
          </div>
        </div>
        <div class="a5e-stage" data-stage>
          <div class="a5e-canvas" data-canvas>
            <img src="{{ asset('storage/'.$layout->image_path) }}" alt="{{ $layout->name }}" draggable="false">
          </div>
        </div>
        <div class="a5e-legend">
          <span><i class="lg-pass"></i><span data-i18n="a5s.status.passed">ผ่าน</span></span>
          <span><i class="lg-wait"></i><span data-i18n="a5s.status.submitted">รอดำเนินการ</span></span>
          <span><i class="lg-fail"></i><span data-i18n="a5s.status.failed">ปฏิเสธ</span></span>
          <span><i class="lg-none"></i><span data-i18n="a5s.status.no_data">ยังไม่มีข้อมูล</span></span>
        </div>
      </div>

      <aside class="a5e-panel">
        <div class="a5e-empty" data-empty hidden></div>

        <div data-point-detail hidden>
          {{-- โครงเดียวกับหน้าบันทึกงาน: ปุ่มประวัติบนสุด แล้วข้อมูลจุดเป็นหัวข้อ-ค่า (Manager 2026-08-27) --}}
          <div data-point-head>
            <div class="a5e-panel-top">
              <button type="button" class="a5e-history-btn" data-open-history hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                <span data-i18n="a5s.common.historySubmit">ประวัติการส่ง</span>
              </button>
            </div>

            <dl class="a5e-point-meta">
              <div class="a5e-meta-item">
                <dt data-i18n="a5s.common.name">ชื่อ</dt>
                <dd>{{ $layout->name }}</dd>
              </div>
              <div class="a5e-meta-item">
                <dt data-i18n="a5s.common.pointName">ชื่อจุด</dt>
                <dd>
                  <span data-point-name>-</span>
                  <span class="a5e-inline-code">(<span data-i18n="a5s.common.point">จุด</span> <b data-point-code>-</b>)</span>
                </dd>
              </div>
              <div class="a5e-meta-item">
                <dt data-i18n="a5s.common.status">สถานะ</dt>
                <dd><span class="a5e-status is-pending" data-status data-i18n="a5s.status.submitted">รอดำเนินการ</span></dd>
              </div>
              {{-- "ครั้งที่" = ลำดับการตรวจประเมิน ไม่ใช่จำนวนครั้งที่กดส่ง (Manager 2026-08-27) --}}
              <div class="a5e-meta-item">
                <dt data-i18n="a5s.workspace.roundNumber">ครั้งที่</dt>
                <dd data-submit-count>-</dd>
              </div>
              {{-- หมายเหตุผู้ประเมิน: ยังไม่ตัดสิน = "-" · ตัดสินแล้ว = ปุ่มเปิดดู (Manager 2026-08-27) --}}
              <div class="a5e-meta-item" data-note-row>
                <dt data-i18n="a5s.common.noteEvaluator">หมายเหตุ (ผู้ประเมิน)</dt>
                <dd>
                  <span class="is-none" data-note-empty>-</span>
                  <button type="button" class="a5e-note-btn" data-open-note hidden>
                    <span data-i18n="a5s.common.view">เปิดดู</span>
                  </button>
                </dd>
              </div>
              <div class="a5e-meta-item">
                <dt data-i18n="a5s.review.latestSubmit">ส่งล่าสุด</dt>
                <dd data-submitted-at>-</dd>
              </div>
            </dl>
          </div>

          <div class="a5e-cards" data-card-list></div>

          <form class="a5e-decision" id="a5eDecisionForm" method="POST" data-decision-form hidden>
            @csrf
            <input type="hidden" name="_return" value="my-work-layout">
            <input type="hidden" name="_point_id" value="{{ old('_point_id') }}" data-form-point>
            <input type="hidden" name="result" value="" data-result-input>
            @if ($errors->any())
              <div class="a5e-errors">
                @foreach ($errors->all() as $error)
                  <span>{{ $error }}</span>
                @endforeach
              </div>
            @endif
            <div class="a5e-choice-row">
              <button type="button" class="a5e-decision-btn pass" data-open-confirm="pass" data-i18n="a5s.review.pass">ผ่าน</button>
              <button type="button" class="a5e-decision-btn fail" data-open-confirm="fail" data-i18n="a5s.review.reject">ปฏิเสธ</button>
            </div>
          </form>

          <div class="a5e-note" data-review-note hidden></div>
          {{-- จุดที่ตัดสินแล้ว: แสดงปุ่มแก้ไข → เด้ง modal ยืนยันก่อนเปิดฟอร์มผ่าน/ปฏิเสธ (Manager 2026-07-23) --}}
          <button type="button" class="a5e-edit-decision" data-edit-decision hidden data-i18n="a5s.review.editDecision">แก้ไขการประเมิน</button>

          {{-- รายการประวัติแบบ inline ถูกถอดออก 2026-08-27 — ดูได้ที่ปุ่ม "ประวัติการส่ง" ด้านบนแทน --}}
        </div>
      </aside>
    </div>
  </div>

  {{-- Modal หมายเหตุผลตรวจ — กรอบเขียวเมื่อผ่าน / แดงเมื่อปฏิเสธ (Manager 2026-08-27) --}}
  <dialog class="a5e-note-dialog" data-note-dialog aria-labelledby="a5e-note-title">
    <div class="a5e-note-head">
      <h3 id="a5e-note-title" data-i18n="a5s.common.note">หมายเหตุ</h3>
      <button type="button" class="a5e-note-close" data-close-note aria-label="ปิด" data-i18n-aria="a5s.common.close">×</button>
    </div>
    <p class="a5e-note-body" data-note-body></p>
  </dialog>

  {{-- Modal ยืนยันผ่าน/ปฏิเสธ + ติ๊กแนบหมายเหตุ (Manager 2026-07-18) --}}
  <div class="a5c-modal" data-confirm-modal hidden aria-hidden="true">
    <button type="button" class="a5c-backdrop" data-confirm-cancel aria-label="ปิด" data-i18n-aria="a5s.common.close"></button>
    <div class="a5c-dialog" role="dialog" aria-modal="true" aria-labelledby="a5c-title" tabindex="-1">
      <h3 id="a5c-title" data-confirm-title data-i18n="a5s.review.confirmPassTitle">ยืนยันให้ผ่าน</h3>
      <p class="a5c-point" data-confirm-point>-</p>
      <label class="a5c-check">
        <input type="checkbox" data-note-toggle>
        <span data-i18n="a5s.review.attachNote">แนบหมายเหตุ</span>
      </label>
      <textarea class="a5c-note" data-confirm-note form="a5eDecisionForm" maxlength="3000" data-i18n-placeholder="a5s.review.notePlaceholder" placeholder="กรอกหมายเหตุถึงผู้รับผิดชอบ" hidden disabled></textarea>
      <div class="a5c-actions">
        <button type="button" class="a5e-btn" data-confirm-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
        <button type="submit" class="a5c-confirm pass" form="a5eDecisionForm" data-confirm-submit data-i18n="a5s.review.confirm">ยืนยัน</button>
      </div>
    </div>
  </div>

  {{-- Modal ยืนยันก่อนแก้ไขการประเมิน (Manager 2026-07-23) --}}
  <div class="a5c-modal" data-edit-confirm hidden aria-hidden="true">
    <button type="button" class="a5c-backdrop" data-edit-cancel aria-label="ปิด" data-i18n-aria="a5s.common.close"></button>
    <div class="a5c-dialog" role="dialog" aria-modal="true" aria-labelledby="a5e-edit-title" tabindex="-1">
      <h3 id="a5e-edit-title" data-i18n="a5s.review.editConfirmTitle">แก้ไขการประเมิน</h3>
      <p class="a5c-point" data-edit-confirm-point>-</p>
      <p class="a5c-desc" data-i18n="a5s.review.editConfirmBody">ต้องการแก้ไขผลการประเมินของจุดนี้ใช่ไหม?</p>
      <div class="a5c-actions">
        <button type="button" class="a5e-btn" data-edit-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
        <button type="button" class="a5c-confirm edit" data-edit-ok data-i18n="a5s.review.editConfirmOk">แก้ไข</button>
      </div>
    </div>
  </div>

  {{-- modal ประวัติการส่ง (แท็บครั้งที่ 1,2,...) ใช้ร่วมกับหน้าบันทึกงาน --}}
  @include('area5s.partials.history-modal', ['historyDetails' => $historyDetails ?? []])

  <script>
    'use strict';
    (function () {
      const points = @json($points);
      const restorePoint = @json((int) (old('_point_id') ?: $restorePoint));
      const oldResult = @json(old('result'));
      const stage = document.querySelector('[data-stage]');
      const canvas = document.querySelector('[data-canvas]');
      const zoomIn = document.querySelector('[data-zoom-in]');
      const zoomOut = document.querySelector('[data-zoom-out]');
      const zoomReset = document.querySelector('[data-zoom-reset]');
      const empty = document.querySelector('[data-empty]');
      const detail = document.querySelector('[data-point-detail]');
      const pointHead = document.querySelector('[data-point-head]');
      const form = document.querySelector('[data-decision-form]');
      const formPoint = document.querySelector('[data-form-point]');
      const cardList = document.querySelector('[data-card-list]');
      const note = document.querySelector('[data-review-note]');
      const editBtn = document.querySelector('[data-edit-decision]');
      const editConfirm = document.querySelector('[data-edit-confirm]');
      const editConfirmPoint = document.querySelector('[data-edit-confirm-point]');
      let editUnlocked = false; // กด "แก้ไขการประเมิน" + ยืนยันแล้ว = เปิดฟอร์มผ่าน/ปฏิเสธของจุดที่เลือก
      const confirmModal = document.querySelector('[data-confirm-modal]');
      const confirmTitle = document.querySelector('[data-confirm-title]');
      const confirmPoint = document.querySelector('[data-confirm-point]');
      const confirmSubmit = document.querySelector('[data-confirm-submit]');
      const noteToggle = document.querySelector('[data-note-toggle]');
      const noteField = document.querySelector('[data-confirm-note]');
      const resultInput = document.querySelector('[data-result-input]');
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
      const statusClass = point => point.status === 'passed' ? 'is-pass' : (point.status === 'failed' ? 'is-fail' : 'is-pending');
      let selected = null;

      function setPointHeadVisible(visible) {
        pointHead.hidden = !visible;
        pointHead.style.display = visible ? '' : 'none';
      }

      function clearPointHead() {
        document.querySelector('[data-point-name]').textContent = '';
        document.querySelector('[data-point-code]').textContent = '';
        document.querySelector('[data-submitted-at]').textContent = '';
        document.querySelector('[data-submit-count]').textContent = '';
        document.querySelector('[data-status]').textContent = '';
      }

      const imageButtonHtml = (src, title, index) => {
        const ext = imageExt(src);
        const content = ['HEIC', 'HEIF'].includes(ext)
          ? `<span class="a5e-image-file">${esc(ext)}</span>`
          : `<img src="${esc(src)}" alt="">`;
        return `
          <button type="button" data-image-preview data-image-src="${esc(src)}" data-image-alt="${esc(title)} ${esc(t('a5s.common.imageNumber', 'รูปที่'))} ${index + 1}" aria-label="${esc(t('a5s.common.viewPhoto', 'ดูรูป'))} ${esc(title)}">
            ${content}
          </button>
        `;
      };

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
        marker.className = 'a5e-marker' + (point.is_reviewable ? ' is-reviewable st-' + point.status : '');
        marker.style.left = point.x + '%';
        marker.style.top = point.y + '%';
        marker.textContent = point.code;
        marker.dataset.point = point.id;
        marker.setAttribute('aria-label', `${t('a5s.common.point', 'จุด')} ${point.code} ${point.name}`);
        marker.addEventListener('click', () => selectPoint(point));
        canvas.appendChild(marker);
      });

      function selectPoint(point) {
        selected = point;
        editUnlocked = false; // สลับจุด = ล็อกกลับ ต้องกด "แก้ไขการประเมิน" ใหม่
        stage.querySelectorAll('.a5e-marker').forEach(marker => {
          marker.classList.toggle('is-open', Number(marker.dataset.point) === Number(point.id));
        });
        empty.hidden = true;
        detail.hidden = false;
        form.hidden = true;
        note.hidden = true;

        if (!point.has_submission) {
          clearPointHead();
          setPointHeadVisible(false);
          cardList.innerHTML = `<div class="a5e-empty"><strong>${esc(t('a5s.common.noData', 'ยังไม่มีข้อมูล'))}</strong><span>${esc(t('a5s.common.noDataBody', 'จุดนี้ยังไม่ถูกส่งตรวจจากผู้รับผิดชอบ'))}</span></div>`;
          return;
        }

        setPointHeadVisible(true);
        document.querySelector('[data-point-name]').textContent = point.name;
        document.querySelector('[data-point-code]').textContent = point.code;
        document.querySelector('[data-submitted-at]').textContent = point.submitted_at || '-';
        // "ครั้งที่" = ลำดับการตรวจประเมิน · กำลังรอตรวจอยู่ให้นับครั้งที่ส่งไปแล้วนั้นด้วย (Manager 2026-08-27)
        const pendingNow = ['submitted', 'resubmitted'].includes(point.status);
        const attemptNo = (point.history || []).length + (pendingNow ? 1 : 0);
        document.querySelector('[data-submit-count]').textContent = attemptNo || '-';
        const status = document.querySelector('[data-status]');
        status.className = 'a5e-status ' + statusClass(point);
        status.textContent = t(`a5s.status.${point.status || 'submitted'}`, point.review_status_label || 'รอดำเนินการ');
        formPoint.value = point.id;
        renderCards(point.cards || []);
        renderDecision(point);
        updateHistoryButton(point);
      }

      // ปุ่มเปิด modal ประวัติ (แท็บครั้งที่ 1,2,...) — ชุดเดียวกับหน้า workspace (Manager 2026-07-31)
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

      // ประวัติการส่ง→ตรวจครั้งก่อน (ไม่มีภาพ) — timeline ครั้งที่ 1/2/3 + ผล + เหตุผล + สิ่งที่ส่ง
      function renderCards(cards) {
        if (!cards.length) {
          cardList.innerHTML = `<div class="a5e-empty"><strong>${esc(t('a5s.common.noCards', 'ยังไม่มีรายการที่ส่งมา'))}</strong><span>${esc(t('a5s.common.waitCards', 'รอผู้รับผิดชอบเพิ่มรายละเอียดและส่งตรวจ'))}</span></div>`;
          return;
        }

        cardList.innerHTML = cards.map(card => `
          <article class="a5e-card">
            <div class="a5e-card-title">
              <strong>${esc(card.title)}</strong>
            </div>
            ${card.detail ? `<p>${esc(card.detail)}</p>` : ''}
            <small>${esc(t('a5s.common.by', 'โดย'))} ${esc(ownerName(card))}</small>
            ${(card.images || []).length ? `<div class="a5e-images">
              ${(card.images || []).map((src, index) => imageButtonHtml(src, card.title, index)).join('')}
            </div>` : ''}
          </article>
        `).join('');
      }

      // ---- modal หมายเหตุ (Manager 2026-08-27) ----
      const noteDialog = document.querySelector('[data-note-dialog]');
      const noteBody = document.querySelector('[data-note-body]');
      const noteRow = document.querySelector('[data-note-row]');
      const noteBtn = document.querySelector('[data-open-note]');
      let noteOpener = null;

      function openNote(text, resultClass) {
        if (!noteDialog) return;
        noteOpener = document.activeElement;
        noteDialog.classList.remove('is-pass', 'is-fail');
        if (resultClass) noteDialog.classList.add(resultClass);
        if (noteBody) noteBody.textContent = text || '';
        noteDialog.showModal();
      }

      /** แถว "หมายเหตุ (ผู้ประเมิน)" แสดงเสมอ — ยังไม่ตัดสิน = "-" · ตัดสินแล้ว = ปุ่มเปิดดู */
      const noteEmpty = document.querySelector('[data-note-empty]');
      function setNoteRow(text, resultClass) {
        if (noteEmpty) noteEmpty.hidden = !!text;
        if (noteBtn) {
          noteBtn.hidden = !text;
          noteBtn.onclick = text ? () => openNote(text, resultClass) : null;
        }
      }

      noteDialog?.querySelector('[data-close-note]')?.addEventListener('click', () => noteDialog.close());
      noteDialog?.addEventListener('click', e => { if (e.target === noteDialog) noteDialog.close(); });
      noteDialog?.addEventListener('close', () => noteOpener?.focus());

      function renderDecision(point) {
        note.classList.remove('is-pass', 'is-fail');
        setNoteRow(null);
        if (!point.has_submission) {
          form.hidden = true; note.hidden = true; editBtn.hidden = true;
          return;
        }

        const decided = point.status === 'passed' || point.status === 'failed';
        // ยังไม่ตัดสิน → โชว์ฟอร์มเลย · ตัดสินแล้ว → โชว์ปุ่มแก้ไขก่อน กดยืนยันจึงเปิดฟอร์ม
        const showForm = point.can_decide && (!decided || editUnlocked);
        form.hidden = !showForm;
        if (showForm) form.action = point.decision_url || '#';
        editBtn.hidden = !(point.can_decide && decided && !editUnlocked);

        // อยู่ในโหมดแก้ไข (ฟอร์มโชว์) → ซ่อนโน้ตผลเดิม ไม่ให้รก
        if (showForm) { note.hidden = true; return; }

        // read-only: หมายเหตุจริงไปอยู่ในปุ่ม+modal ส่วน .a5e-note เหลือไว้บอกสถานะเชิงข้อมูลเท่านั้น
        if (point.status === 'passed') {
          note.hidden = true;
          setNoteRow(point.advice, 'is-pass');
        } else if (point.status === 'failed') {
          if (point.fail_reason) {
            note.hidden = true;
            setNoteRow(point.fail_reason, 'is-fail');
          } else {
            note.hidden = false;
            note.classList.add('is-fail');
            note.textContent = t('a5s.review.failedWaiting', 'จุดนี้ถูกปฏิเสธแล้ว รอผู้รับผิดชอบแก้ไขและส่งใหม่');
          }
        } else {
          note.hidden = false;
          note.textContent = point.is_reviewable ? t('a5s.review.waitSubmit', 'รอผู้รับผิดชอบส่งตรวจ') : t('a5s.review.outOfScope', 'จุดนี้ไม่ได้อยู่ในขอบเขตที่คุณต้องตรวจ');
        }
      }

      // ---- ปุ่ม/โมดัล "แก้ไขการประเมิน" ----
      function openEditConfirm() {
        if (!selected) return;
        editConfirmPoint.textContent = `${t('a5s.common.point', 'จุด')} ${selected.code} — ${selected.name}`;
        editConfirm.hidden = false; editConfirm.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        editConfirm.querySelector('.a5c-dialog')?.focus();
      }
      function closeEditConfirm() {
        editConfirm.hidden = true; editConfirm.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }
      editBtn.addEventListener('click', openEditConfirm);
      editConfirm.querySelectorAll('[data-edit-cancel]').forEach(b => b.addEventListener('click', closeEditConfirm));
      editConfirm.querySelector('[data-edit-ok]').addEventListener('click', () => {
        editUnlocked = true;
        closeEditConfirm();
        if (selected) renderDecision(selected);
      });

      // ---- Modal ยืนยันผ่าน/ปฏิเสธ + ติ๊กแนบหมายเหตุ ----
      function setNoteVisible(visible) {
        noteField.hidden = !visible;
        noteField.disabled = !visible;   // ไม่ติ๊ก = ไม่ส่งค่าไปกับฟอร์ม
        if (visible) noteField.focus();
      }

      function openConfirm(kind, presetNote = null) {
        if (!selected || !selected.can_decide) return;
        resultInput.value = kind;
        noteField.name = kind === 'pass' ? 'advice' : 'fail_reason';
        confirmTitle.textContent = kind === 'pass'
          ? t('a5s.review.confirmPassTitle', 'ยืนยันให้ผ่าน')
          : t('a5s.review.confirmRejectTitle', 'ยืนยันการปฏิเสธ');
        confirmPoint.textContent = `${t('a5s.common.point', 'จุด')} ${selected.code} — ${selected.name}`;
        confirmSubmit.className = 'a5c-confirm ' + kind;
        noteToggle.checked = !!presetNote;
        noteField.value = presetNote || '';
        setNoteVisible(noteToggle.checked);
        confirmModal.hidden = false;
        confirmModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        confirmModal.querySelector('.a5c-dialog')?.focus();
      }

      function closeConfirm() {
        confirmModal.hidden = true;
        confirmModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        noteField.disabled = true;
      }

      noteToggle.addEventListener('change', () => setNoteVisible(noteToggle.checked));
      form.addEventListener('click', e => {
        const btn = e.target.closest('[data-open-confirm]');
        if (!btn) return;
        const kind = btn.dataset.openConfirm;
        // แก้ไข: ถ้าจุดนี้ตัดสินด้วยผลเดียวกันอยู่แล้ว ดึงหมายเหตุเดิมมาให้แก้ต่อ
        let preset = null;
        if (selected) {
          if (kind === 'pass' && selected.status === 'passed') preset = selected.advice || null;
          else if (kind === 'fail' && selected.status === 'failed') preset = selected.fail_reason || null;
        }
        openConfirm(kind, preset);
      });
      confirmModal.querySelectorAll('[data-confirm-cancel]').forEach(btn => btn.addEventListener('click', closeConfirm));
      document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (!confirmModal.hidden) closeConfirm();
        if (!editConfirm.hidden) closeEditConfirm();
      });

      const initial = points.find(point => Number(point.id) === Number(restorePoint)) || points.find(point => point.is_reviewable);
      if (initial) selectPoint(initial);
      // มี validation error → เปิด modal เดิมพร้อมหมายเหตุที่พิมพ์ค้างไว้
      if (@json($errors->any()) && oldResult && selected && selected.can_decide) {
        openConfirm(oldResult, @json((string) (old('fail_reason') ?: old('advice'))) || null);
      }
      document.addEventListener('insight:languagechange', () => {
        if (selected) selectPoint(selected);
      });
    })();
  </script>
@endsection
