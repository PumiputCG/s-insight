{{--
  สถานะ OT — ปุ่มย่อในตาราง และ dialog แบบมินิมอล ใช้ร่วมกันทั้ง 3 หน้า

  Dialog อ่านจากบนลงล่างเป็นแกนกลางเส้นเดียว: รูปพนักงาน → 4 วงกลมสถานะ → รายละเอียด
  ของขั้นที่เลือก. ใช้ "วงกลม" เป็นสัญลักษณ์เดียวทั้งชุด ไม่มีการ์ด/ไอคอนรายขั้น เพื่อให้
  สายตาจับสถานะรวมได้ในครั้งเดียว ส่วนรายละเอียดเปิดทีละขั้นแบบ progressive disclosure
  จึงเห็นเฉพาะสิ่งที่กำลังสนใจ ไม่ใช่ทุกฟิลด์พร้อมกัน
--}}
<style>
  /* ── ปุ่มย่อในตาราง (คงรูปแบบเดิม) ─────────────────────────── */
  .ot-route-button {
    width: 7.25rem;
    min-height: 2.75rem;
    display: inline-grid;
    place-items: center;
    padding: .15rem;
    border: 0;
    border-radius: 0.25rem;
    background: transparent;
    color: inherit;
    cursor: pointer;
  }
  .ot-route-button:hover { background: var(--hover-soft); }
  .ot-route-button:focus-visible { outline: 2px solid var(--moss); outline-offset: 2px; }
  .ot-route-mini { position: relative; width: 6.8rem; height: 2.55rem; }
  .ot-route-mini-lines { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }
  .ot-route-line { fill: none; stroke: var(--line-strong); stroke-width: 1.6; vector-effect: non-scaling-stroke; }
  .ot-route-line[data-tone="warning"] { stroke: #d69a12; }
  .ot-route-line[data-tone="success"] { stroke: #35a863; }
  .ot-route-line[data-tone="danger"] { stroke: #da5a4e; }
  .ot-route-line[data-tone="cancelled"] { stroke: #fff; }
  .ot-route-pip {
    position: absolute;
    z-index: 1;
    width: 1.35rem;
    height: 1.35rem;
    display: grid;
    place-items: center;
    border: 1px solid var(--line-strong);
    border-radius: 50%;
    background: var(--panel-soft);
    color: var(--muted-light);
    box-shadow: 0 0 0 3px var(--menu-bg);
  }
  .ot-route-pip svg { width: .76rem; height: .76rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .ot-route-pip[data-stage="request"] { left: .1rem; top: .6rem; }
  .ot-route-pip[data-stage="approval"] { left: 2.75rem; top: 0; }
  .ot-route-pip[data-stage="attendance"] { left: 2.75rem; bottom: 0; }
  .ot-route-pip[data-stage="final"] { right: .1rem; top: .6rem; }
  /* ปุ่มย่อของการลา: 3 จุดเรียงเส้นตรงกลางแนวตั้ง */
  .ot-route-mini.is-linear .ot-route-pip[data-stage="request"] { left: .1rem; top: .6rem; }
  .ot-route-mini.is-linear .ot-route-pip[data-stage="approval"] { left: 2.75rem; top: .6rem; }
  .ot-route-mini.is-linear .ot-route-pip[data-stage="final"] { right: .1rem; top: .6rem; }
  .ot-route-pip[data-tone="warning"] { border-color: #d69a12; background: #d69a12; color: #fff; }
  .ot-route-pip[data-tone="success"] { border-color: #35a863; background: #1d7a42; color: #fff; }
  .ot-route-pip[data-tone="danger"] { border-color: #da5a4e; background: #b83d34; color: #fff; }
  .ot-route-pip[data-tone="cancelled"] { border-color: #fff; background: #fff; color: var(--menu-bg); }

  /* ── Dialog มินิมอล ────────────────────────────────────────── */
  .ot-route-dialog {
    width: min(92vw, 26rem);
    inset: 0;
    margin: auto;
    padding: 0;
    border: 1px solid var(--line-light);
    border-radius: 0.44rem;
    background: var(--menu-bg);
    color: var(--light-text);
    box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 28%);
  }
  .ot-route-dialog::backdrop { background: rgb(8 13 17 / 55%); backdrop-filter: blur(3px); }
  .ot-route-sheet { position: relative; padding: 1.6rem 1.5rem 1.4rem; }
  .ot-route-dialog-close {
    position: absolute;
    top: .7rem;
    right: .7rem;
    width: 2rem;
    height: 2rem;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: var(--muted-light);
    cursor: pointer;
  }
  .ot-route-dialog-close:hover { background: var(--hover-soft); color: var(--light-text); }
  .ot-route-dialog-close:focus-visible { outline: 2px solid var(--moss); outline-offset: 2px; }
  .ot-route-dialog-close svg { width: .95rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }

  /* หัวเรื่อง: รูป → ชื่อ → รหัส·วันที่ จัดกึ่งกลางเป็นแกนเดียว */
  .ot-route-person { display: grid; justify-items: center; gap: .1rem; text-align: center; }
  .ot-route-photo {
    width: 3.9rem;
    height: 3.9rem;
    display: grid;
    place-items: center;
    margin-bottom: .55rem;
    border: 1px solid var(--line-light);
    border-radius: 50%;
    background: var(--hover-soft);
    color: var(--muted-light);
    font-size: 1.15rem;
    font-weight: 650;
    object-fit: cover;
    overflow: hidden;
  }
  .ot-route-person h2 { margin: 0; font-size: .95rem; font-weight: 700; line-height: 1.3; }
  .ot-route-person p { margin: .1rem 0 0; color: var(--muted-light); font-size: .72rem; }
  img.ot-route-photo { cursor: zoom-in; transition: transform .18s ease; }
  img.ot-route-photo:hover { transform: scale(1.06); }

  /* ดูรูปเต็ม — ต้องอยู่ข้างใน <dialog> ไม่งั้นจะถูก top layer ของ dialog บังจนมองไม่เห็น */
  .ot-route-zoom {
    position: fixed;
    inset: 0;
    z-index: 5;
    display: grid;
    place-items: center;
    gap: .9rem;
    padding: 1.5rem;
    background: rgb(8 13 17 / 88%);
    cursor: zoom-out;
  }
  .ot-route-zoom[hidden] { display: none; }
  .ot-route-zoom figure { display: grid; justify-items: center; gap: .7rem; margin: 0; cursor: default; }
  .ot-route-zoom img {
    max-width: min(78vw, 22rem);
    max-height: 62vh;
    border-radius: 0.4rem;
    background: var(--panel-soft);
    object-fit: contain;
  }
  .ot-route-zoom figcaption { display: grid; gap: .1rem; color: #fff; font-size: .88rem; text-align: center; }
  .ot-route-zoom figcaption small { color: rgb(255 255 255 / 68%); font-size: .76rem; }
  .ot-route-zoom-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 2.4rem;
    height: 2.4rem;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 50%;
    background: rgb(255 255 255 / 12%);
    color: #fff;
    cursor: pointer;
  }
  .ot-route-zoom-close:hover { background: rgb(255 255 255 / 22%); }
  .ot-route-zoom-close svg { width: 1.05rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }

  /* ── ผัง 4 วงกลม — สัญลักษณ์เดียวของทั้ง dialog ───────────────
     ขอ OT แตกเป็นสองทาง (อนุมัติ / เวลาสแกน) แล้วบรรจบที่ผลลัพธ์
     เส้นเชื่อมวาดด้วย SVG จากตำแหน่งจริงของวงกลม จึงตรงทุกขนาดจอ */
  .ot-route-flow { position: relative; margin: 1.35rem 0 .1rem; }
  .ot-route-flow-lines { position: absolute; inset: 0; z-index: 0; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
  .ot-route-flow-lines path { fill: none; stroke: var(--line-strong); stroke-width: 2; stroke-linecap: round; }
  .ot-route-flow-lines path[data-tone="warning"] { stroke: #d69a12; }
  .ot-route-flow-lines path[data-tone="success"] { stroke: #35a863; }
  .ot-route-flow-lines path[data-tone="danger"] { stroke: #da5a4e; }
  .ot-route-flow-lines path[data-tone="cancelled"] { stroke: #fff; }
  .ot-route-track {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr) minmax(0, 1fr);
    grid-template-rows: auto auto;
    gap: 1.1rem .25rem;
  }
  .ot-route-step {
    position: relative;
    z-index: 1;
    display: grid;
    justify-items: center;
    gap: .4rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--muted-light);
    cursor: pointer;
    font: inherit;
  }
  .ot-route-step[data-route-step="request"] { grid-column: 1; grid-row: 1 / span 2; align-self: center; }
  .ot-route-step[data-route-step="approval"] { grid-column: 2; grid-row: 1; }
  .ot-route-step[data-route-step="attendance"] { grid-column: 2; grid-row: 2; }
  .ot-route-step[data-route-step="final"] { grid-column: 3; grid-row: 1 / span 2; align-self: center; }
  /* การลาไม่มีขั้นตรวจเวลาสแกน เส้นทางจึงเป็นแถวเดียวไม่แตกแขนง */
  .ot-route-track.is-linear { grid-template-rows: auto; }
  .ot-route-track.is-linear .ot-route-step { grid-column: auto; grid-row: 1; align-self: start; }
  .ot-route-dot {
    position: relative;
    z-index: 1;
    width: 2.15rem;
    height: 2.15rem;
    display: grid;
    place-items: center;
    border: 2px solid var(--line-strong);
    border-radius: 50%;
    background: var(--menu-bg);
    color: transparent;
    transition: box-shadow .18s var(--ease-out, ease), transform .18s var(--ease-out, ease);
  }
  .ot-route-dot svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
  .ot-route-step[data-tone="warning"] .ot-route-dot { border-color: #d69a12; background: #d69a12; color: #fff; }
  .ot-route-step[data-tone="success"] .ot-route-dot { border-color: #1d7a42; background: #1d7a42; color: #fff; }
  .ot-route-step[data-tone="danger"] .ot-route-dot { border-color: #b83d34; background: #b83d34; color: #fff; }
  .ot-route-step[data-tone="cancelled"] .ot-route-dot { border-color: #fff; background: #fff; color: var(--menu-bg); }
  .ot-route-step:hover .ot-route-dot { transform: scale(1.06); }
  .ot-route-step[aria-selected="true"] .ot-route-dot { box-shadow: 0 0 0 4px color-mix(in srgb, var(--moss) 22%, transparent); }
  .ot-route-step:focus-visible { outline: 0; }
  .ot-route-step:focus-visible .ot-route-dot { outline: 2px solid var(--moss); outline-offset: 3px; }
  .ot-route-step small { font-size: .63rem; font-weight: 650; line-height: 1.25; text-align: center; }
  .ot-route-step[aria-selected="true"] small { color: var(--light-text); }

  /* รายละเอียดของขั้นที่เลือก — โชว์เฉพาะฟิลด์ที่มีค่าจริง */
  .ot-route-detail {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line-light);
    text-align: center;
  }
  .ot-route-detail-state { font-size: .84rem; font-weight: 700; }
  .ot-route-step-name { display: block; margin-bottom: .15rem; color: var(--muted-light); font-size: .63rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
  .ot-route-detail[data-tone="warning"] .ot-route-detail-state { color: #a97a0d; }
  .ot-route-detail[data-tone="success"] .ot-route-detail-state { color: #1d7a42; }
  .ot-route-detail[data-tone="danger"] .ot-route-detail-state { color: #b83d34; }
  .ot-route-detail[data-tone="cancelled"] .ot-route-detail-state { color: #fff; }

  .ot-route-fields { display: grid; gap: .5rem; margin: .85rem 0 0; }
  .ot-route-fields div { display: grid; gap: .1rem; }
  .ot-route-fields dt { color: var(--muted-light); font-size: .64rem; }
  .ot-route-fields dd { margin: 0; font-size: .76rem; line-height: 1.45; overflow-wrap: anywhere; }
  .ot-route-fields:empty { display: none; }
  /* ช่วงเวลาและประเภท OT — ข้อมูลประจำคำขอ จึงอยู่ในหัวเรื่องคู่กับชื่อพนักงาน */
  .ot-route-summary {
    margin: .5rem 0 0;
    padding: .28rem .7rem;
    border-radius: 999px;
    background: var(--panel-soft);
    color: var(--light-text);
    font-size: .72rem;
    font-weight: 650;
  }
  .ot-route-summary:empty { display: none; }

  @media (prefers-reduced-motion: reduce) {
    .ot-route-dot { transition: none; }
    .ot-route-step:hover .ot-route-dot { transform: none; }
  }

  /* มือถือ: แผ่นเลื่อนขึ้นจากขอบล่างตามพฤติกรรมที่ผู้ใช้คุ้นเคย */
  @media (max-width: 640px) {
    .ot-route-dialog {
      width: 100%;
      max-width: none;
      margin: auto 0 0;
      border-radius: 0.45rem 1.25rem 0 0;
    }
    .ot-route-sheet { padding: 1.4rem 1.1rem 1.6rem; }
    .ot-route-step small { font-size: .58rem; }
  }
</style>

<dialog class="ot-route-dialog" data-ot-route-dialog aria-labelledby="otRouteDialogTitle">
  <div class="ot-route-sheet">
    <button class="ot-route-dialog-close" type="button" data-route-dialog-close aria-label="ปิด" data-i18n-aria="ot.common.close">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>

    <div class="ot-route-person">
      <span data-route-photo aria-hidden="true"></span>
      <h2 id="otRouteDialogTitle" data-route-person-name>—</h2>
      <p data-route-person-meta>—</p>
      <p class="ot-route-summary" data-route-summary></p>
    </div>

    <div class="ot-route-flow">
      <svg class="ot-route-flow-lines" data-route-lines aria-hidden="true"></svg>
      <div class="ot-route-track" role="tablist" aria-label="ขั้นตอนของคำขอ OT" data-route-track data-i18n-aria="ot.route.step"></div>
    </div>

    <section class="ot-route-detail" data-route-detail role="tabpanel" tabindex="0">
      <span class="ot-route-step-name" data-route-step-name></span>
      <strong class="ot-route-detail-state" data-route-detail-state>—</strong>
      <dl class="ot-route-fields" data-route-fields></dl>
    </section>
  </div>

  <div class="ot-route-zoom" data-route-zoom role="dialog" aria-label="รูปพนักงาน" data-i18n-aria="ot.route.zoomPhoto" hidden>
    <button class="ot-route-zoom-close" type="button" data-route-zoom-close aria-label="ปิด" data-i18n-aria="ot.common.close">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
    <figure>
      <img data-route-zoom-image src="" alt="">
      <figcaption data-route-zoom-caption></figcaption>
    </figure>
  </div>
</dialog>

<script>
  (function () {
    'use strict';

    var dialog = document.querySelector('[data-ot-route-dialog]');
    if (!dialog || window.otProcessRoute) return;

    var previousFocus = null;
    var currentRequest = null;
    var currentPerson = null;
    var activeStage = null;
    var STAGES = ['request', 'approval', 'attendance', 'final'];
    var icons = {
      request: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"/><path d="M14 3v4h4M9 11h6M9 15h6"/></svg>',
      approval: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 19 6v5c0 4.6-2.8 8-7 10-4.2-2-7-5.4-7-10V6z"/><path d="m9 12 2 2 4-4"/></svg>',
      attendance: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg>',
      final: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7.5"/></svg>'
    };
    /* ในวงกลมใช้แค่ ✓ กับ ✕ — สีอย่างเดียวไม่พอสำหรับผู้ใช้ที่แยกสีไม่ได้ */
    var marks = {
      success: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 10 17.5 19 7"/></svg>',
      danger: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 7 7 17M7 7l10 10"/></svg>'
    };

    function copy(key, fallback) {
      var lang = document.documentElement.getAttribute('data-lang') || 'th';
      return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
    }

    function localized(item, field, fallback) {
      if (!item) return fallback || '-';
      var lang = document.documentElement.getAttribute('data-lang') || 'th';
      return item[field + '_' + lang] || item[field + '_en'] || item[field + '_th'] || fallback || '-';
    }

    function dateTime(value) {
      if (!value) return '';
      try {
        return new Intl.DateTimeFormat(document.documentElement.lang || 'th', {
          day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
        }).format(new Date(value));
      } catch (error) {
        return value;
      }
    }

    function workDate(value) {
      if (!value) return '';
      try {
        return new Intl.DateTimeFormat(document.documentElement.lang || 'th', {
          day: '2-digit', month: '2-digit', year: 'numeric'
        }).format(new Date(value + 'T00:00:00'));
      } catch (error) {
        return value;
      }
    }

    /* รูปโปรไฟล์บนเครื่องอื่นถูกอ้างด้วยโฮสต์ของเครื่องนั้น จึงต้องดึงกลับมาที่ origin ปัจจุบัน */
    function localAssetUrl(value) {
      if (!value) return '';
      try {
        var url = new URL(value, window.location.href);
        return ['localhost', '127.0.0.1'].indexOf(url.hostname) !== -1
          ? window.location.origin + url.pathname + url.search
          : url.href;
      } catch (error) {
        return value;
      }
    }

    /* การลาใช้ dialog เดียวกับ OT แต่ไม่มีขั้นตรวจเวลาสแกน จึงเหลือ 3 วง
       แยกด้วย kind ที่ฝั่งเรียกใส่มา ไม่เดาจากรูปร่าง payload เพราะสองระบบมีคีย์ทับกันหลายตัว */
    function isLeave(request) {
      return Boolean(request && request.kind === 'leave');
    }

    function stagesOf(request) {
      return isLeave(request) ? ['request', 'approval', 'final'] : STAGES;
    }

    function leaveStates(request) {
      var has = Boolean(request && request.id);
      var approval = has ? request.approval_status : '';
      var requestState;
      var approvalState;
      var finalState;
      var cancelledState = { tone: 'cancelled', label: copy('ot.route.cancelled', 'ยกเลิกคำขอ') };

      if (approval === 'cancelled') {
        return { request: cancelledState, approval: cancelledState, attendance: cancelledState, final: cancelledState };
      }

      if (! has) requestState = { tone: 'neutral', label: copy('ot.route.noRequest', 'ยังไม่มีคำขอ') };
      else if (approval === 'draft') requestState = { tone: 'success', label: copy('ot.route.submitted', 'ส่งคำขอแล้ว') };
      else requestState = { tone: 'success', label: copy('ot.route.submitted', 'ส่งคำขอแล้ว') };

      if (! has) approvalState = { tone: 'neutral', label: copy('ot.route.notStarted', 'ยังไม่ถึงขั้นตอน') };
      else if (approval === 'approved') approvalState = { tone: 'success', label: copy('ot.route.approved', 'อนุมัติแล้ว') };
      else if (approval === 'rejected') approvalState = { tone: 'danger', label: copy('ot.route.rejected', 'ไม่อนุมัติ') };
      else approvalState = { tone: 'warning', label: copy('ot.route.waitingApproval', 'รออนุมัติ') };

      finalState = approvalState.tone === 'success'
        ? { tone: 'success', label: copy('ot.route.passed', 'ผ่าน') }
        : (approvalState.tone === 'danger'
          ? { tone: 'danger', label: copy('ot.route.notPassed', 'ไม่ผ่าน') }
          : approvalState);

      return { request: requestState, approval: approvalState, attendance: approvalState, final: finalState };
    }

    function stageStates(request) {
      if (isLeave(request)) return leaveStates(request);

      var hasRequest = Boolean(request && request.id);
      var approval = hasRequest ? request.approval_status : '';
      var attendance = hasRequest ? request.attendance_status : '';
      var requestState;
      var approvalState;
      var attendanceState;
      var finalState;
      var cancelledState = { tone: 'cancelled', label: copy('ot.route.cancelled', 'ยกเลิกคำขอ') };

      if (approval === 'cancelled') {
        return { request: cancelledState, approval: cancelledState, attendance: cancelledState, final: cancelledState };
      }

      if (!hasRequest) requestState = { tone: 'neutral', label: copy('ot.route.noRequest', 'ยังไม่มีคำขอ') };
      else if (approval === 'draft') requestState = { tone: 'success', label: copy('ot.route.submitted', 'ส่งคำขอแล้ว') };
      else requestState = { tone: 'success', label: copy('ot.route.submitted', 'ส่งคำขอแล้ว') };

      if (!hasRequest) approvalState = { tone: 'neutral', label: copy('ot.route.notStarted', 'ยังไม่ถึงขั้นตอน') };
      else if (approval === 'submitted') approvalState = { tone: 'warning', label: copy('ot.route.waitingApproval', 'รออนุมัติ') };
      else if (approval === 'approved') approvalState = { tone: 'success', label: copy('ot.route.approved', 'อนุมัติแล้ว') };
      else approvalState = { tone: 'danger', label: copy('ot.route.rejected', 'ไม่อนุมัติ') };

      if (!hasRequest) attendanceState = { tone: 'neutral', label: copy('ot.route.notStarted', 'ยังไม่ถึงขั้นตอน') };
      else if (attendance === 'passed') attendanceState = { tone: 'success', label: copy('ot.route.scanPassed', 'เวลาสแกนครบ') };
      else if (attendance === 'failed') attendanceState = { tone: 'danger', label: copy('ot.route.scanFailed', 'เวลาสแกนไม่ครบ') };
      else attendanceState = { tone: 'warning', label: copy('ot.route.waitingScan', 'รอตรวจเวลาสแกน') };

      if (!hasRequest) finalState = { tone: 'neutral', label: copy('ot.route.notStarted', 'ยังไม่ถึงขั้นตอน') };
      else if (approval === 'rejected' || attendance === 'failed') finalState = { tone: 'danger', label: copy('ot.route.notPassed', 'ไม่ผ่าน') };
      else if (approval === 'approved' && attendance === 'passed') finalState = { tone: 'success', label: copy('ot.route.passed', 'ผ่าน') };
      else finalState = { tone: 'warning', label: copy('ot.route.inProgress', 'รอดำเนินการ') };

      return { request: requestState, approval: approvalState, attendance: attendanceState, final: finalState };
    }

    function lineTone(parent, child) {
      if (parent.tone === 'neutral') return 'neutral';
      return child.tone;
    }

    function node(stage, state) {
      var pip = document.createElement('span');
      pip.className = 'ot-route-pip';
      pip.dataset.stage = stage;
      pip.dataset.tone = state.tone;
      pip.title = state.label;
      pip.innerHTML = icons[stage];
      return pip;
    }

    function setPathTone(root, name, tone) {
      var path = root.querySelector('[data-route-line="' + name + '"]');
      if (path) path.dataset.tone = tone;
    }

    function create(request, person) {
      var states = stageStates(request);
      var leave = isLeave(request);
      var stages = stagesOf(request);
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'ot-route-button';
      button.setAttribute('aria-haspopup', 'dialog');
      button.setAttribute('aria-label', copy('ot.route.open', 'ดูสถานะทั้งหมด') + ': ' + stages.map(function (stage) {
        return states[stage].label;
      }).join(', '));

      var mini = document.createElement('span');
      mini.className = 'ot-route-mini' + (leave ? ' is-linear' : '');

      if (leave) {
        /* การลาไม่มีขั้นตรวจเวลาสแกน ปุ่มย่อจึงเป็น 3 จุดเรียงเส้นตรง
           ไม่ใช่ผังแตกแขนงแบบ OT — ให้ตรงกับ dialog ที่เปิดขึ้นมา */
        mini.innerHTML = '<svg class="ot-route-mini-lines" viewBox="0 0 108 41" preserveAspectRatio="none" aria-hidden="true">' +
          '<path class="ot-route-line" data-route-line="request-approval" d="M18 21 L46 21"/>' +
          '<path class="ot-route-line" data-route-line="approval-final" d="M62 21 L90 21"/>' +
          '</svg>';
        mini.append(node('request', states.request), node('approval', states.approval), node('final', states.final));
        setPathTone(mini, 'request-approval', lineTone(states.request, states.approval));
        setPathTone(mini, 'approval-final', lineTone(states.approval, states.final));
      } else {
        mini.innerHTML = '<svg class="ot-route-mini-lines" viewBox="0 0 108 41" preserveAspectRatio="none" aria-hidden="true">' +
          '<path class="ot-route-line" data-route-line="request-approval" d="M18 21 L46 10"/>' +
          '<path class="ot-route-line" data-route-line="request-attendance" d="M18 21 L46 31"/>' +
          '<path class="ot-route-line" data-route-line="approval-final" d="M62 10 L90 21"/>' +
          '<path class="ot-route-line" data-route-line="attendance-final" d="M62 31 L90 21"/>' +
          '</svg>';
        mini.append(node('request', states.request), node('approval', states.approval), node('attendance', states.attendance), node('final', states.final));
        setPathTone(mini, 'request-approval', lineTone(states.request, states.approval));
        setPathTone(mini, 'request-attendance', lineTone(states.request, states.attendance));
        setPathTone(mini, 'approval-final', lineTone(states.approval, states.final));
        setPathTone(mini, 'attendance-final', lineTone(states.attendance, states.final));
      }
      button.appendChild(mini);
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        open(request, button, person);
      });
      return button;
    }

    /* ข้อมูลคนของแต่ละหน้าไม่เหมือนกัน — หน้า Requests/Overview ส่ง employee เข้ามา
       ส่วนหน้า Approvals มีชื่อ/รูปติดมากับตัวคำขออยู่แล้ว */
    function personOf(request, person) {
      if (person) {
        return {
          avatar: person.avatar || '',
          name: localized(person, 'name', person.code || ''),
          code: person.code || ''
        };
      }
      if (request && request.id) {
        return {
          avatar: request.avatar || '',
          name: request.employee_name || request.employee_code || '',
          code: request.employee_code || ''
        };
      }

      return { avatar: '', name: '', code: '' };
    }

    function paintPhoto(who) {
      var slot = dialog.querySelector('[data-route-photo]');
      var replacement;
      if (who.avatar) {
        replacement = document.createElement('img');
        replacement.src = localAssetUrl(who.avatar);
        replacement.alt = who.name || who.code || '';
        replacement.loading = 'lazy';
        replacement.tabIndex = 0;
        replacement.setAttribute('role', 'button');
        replacement.setAttribute('aria-label', copy('ot.route.zoomPhoto', 'ดูรูปพนักงานแบบขยาย'));
      } else {
        replacement = document.createElement('span');
        replacement.textContent = (who.name || who.code || '?').charAt(0).toUpperCase();
        replacement.setAttribute('aria-hidden', 'true');
      }
      replacement.className = 'ot-route-photo';
      replacement.setAttribute('data-route-photo', '');
      slot.replaceWith(replacement);
    }

    /* ── ดูรูปเต็ม ─────────────────────────────────────────────── */
    function openZoom() {
      var photo = dialog.querySelector('img[data-route-photo]');
      if (!photo || !photo.getAttribute('src')) return;
      var zoom = dialog.querySelector('[data-route-zoom]');
      var image = zoom.querySelector('[data-route-zoom-image]');
      var caption = zoom.querySelector('[data-route-zoom-caption]');

      image.src = photo.currentSrc || photo.src;
      image.alt = photo.alt || '';
      caption.replaceChildren();

      var name = dialog.querySelector('[data-route-person-name]').textContent;
      var meta = dialog.querySelector('[data-route-person-meta]').textContent;
      if (name) {
        var nameNode = document.createElement('strong');
        nameNode.textContent = name;
        caption.appendChild(nameNode);
      }
      if (meta && meta !== '—') {
        var metaNode = document.createElement('small');
        metaNode.textContent = meta;
        caption.appendChild(metaNode);
      }
      zoom.hidden = false;
      zoom.querySelector('[data-route-zoom-close]').focus();
    }

    function closeZoom() {
      var zoom = dialog.querySelector('[data-route-zoom]');
      if (zoom.hidden) return;
      zoom.hidden = true;
      zoom.querySelector('[data-route-zoom-image]').removeAttribute('src');
      var photo = dialog.querySelector('img[data-route-photo]');
      if (photo) photo.focus();
    }

    /* ── เส้นเชื่อมของผัง ───────────────────────────────────────
       วัดตำแหน่งจริงของวงกลมแล้ววาด เพราะความสูงของป้ายชื่อเปลี่ยนตามภาษา
       และความกว้าง dialog ทำให้พิกัดตายตัวเพี้ยนได้ */
    var LINKS = [['request', 'approval'], ['request', 'attendance'], ['approval', 'final'], ['attendance', 'final']];

    function drawLines(states) {
      var svg = dialog.querySelector('[data-route-lines]');
      var flow = svg.parentElement;
      var box = flow.getBoundingClientRect();
      if (!box.width || !box.height) return;

      svg.setAttribute('viewBox', '0 0 ' + box.width + ' ' + box.height);
      svg.replaceChildren();

      var centers = {};
      STAGES.forEach(function (stage) {
        var dot = dialog.querySelector('[data-route-step="' + stage + '"] .ot-route-dot');
        if (!dot) return;
        var rect = dot.getBoundingClientRect();
        centers[stage] = {
          x: rect.left - box.left + rect.width / 2,
          y: rect.top - box.top + rect.height / 2,
          r: rect.width / 2
        };
      });

      LINKS.forEach(function (link) {
        var from = centers[link[0]];
        var to = centers[link[1]];
        if (!from || !to) return;

        // หดปลายเส้นให้จบที่ขอบวงกลม ไม่ลากทับตัววง
        var dx = to.x - from.x;
        var dy = to.y - from.y;
        var length = Math.sqrt(dx * dx + dy * dy) || 1;
        var startGap = from.r + 5;
        var endGap = to.r + 5;
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M' + (from.x + dx / length * startGap) + ' ' + (from.y + dy / length * startGap) +
          ' L' + (to.x - dx / length * endGap) + ' ' + (to.y - dy / length * endGap));
        path.dataset.tone = lineTone(states[link[0]], states[link[1]]);
        svg.appendChild(path);
      });
    }

    function field(label, value) {
      var wrap = document.createElement('div');
      var term = document.createElement('dt');
      var detail = document.createElement('dd');
      term.textContent = label;
      detail.textContent = value;
      wrap.append(term, detail);
      return wrap;
    }

    /* เอาเฉพาะฟิลด์ที่มีค่าจริง — ช่องที่เป็น "-" ไม่ได้บอกอะไรและทำให้แผงรก */
    function detailFields(stage, request) {
      if (!request || !request.id) return [];
      var rows = [];

      if (isLeave(request)) {
        if (stage === 'request') {
          rows.push([copy('ot.route.requestedBy', 'ผู้ขอ OT'), (request.requester || {}).name]);
          rows.push([copy('ot.route.actionTime', 'เวลาดำเนินการ'), request.submitted_at]);
          rows.push([copy('ot.route.requestNote', 'หมายเหตุคำขอ'), request.note]);
        } else if (stage === 'approval') {
          rows.push([copy('ot.route.approvedBy', 'ผู้อนุมัติ'), (request.approver || {}).name]);
          rows.push([copy('ot.route.actionTime', 'เวลาดำเนินการ'), request.decided_at]);
          rows.push([copy('ot.route.decisionNote', 'หมายเหตุการอนุมัติ'), request.decision_note]);
        } else {
          rows.push([copy('leave.payrollCycle', 'รอบจ่าย'), request.payroll_cycle]);
        }

        return rows.filter(function (row) { return row[1]; });
      }

      if (stage === 'request') {
        rows.push([copy('ot.route.requestedBy', 'ผู้ขอ OT'), request.created_by_name]);
        rows.push([copy('ot.route.actionTime', 'เวลาดำเนินการ'), dateTime(request.submitted_at || request.created_at)]);
        rows.push([copy('ot.route.requestNote', 'หมายเหตุคำขอ'), request.request_note || request.note]);
      } else if (stage === 'approval') {
        rows.push([copy('ot.route.approvedBy', 'ผู้อนุมัติ'), request.decided_by_name]);
        rows.push([copy('ot.route.actionTime', 'เวลาดำเนินการ'), dateTime(request.decided_at)]);
        rows.push([copy('ot.route.decisionNote', 'หมายเหตุการอนุมัติ'), request.decision_note]);
      } else if (stage === 'attendance') {
        var scan = [];
        if (request.clock_in) scan.push(copy('ot.route.clockIn', 'เข้า') + ' ' + request.clock_in);
        if (request.clock_out) scan.push(copy('ot.route.clockOut', 'ออก') + ' ' + request.clock_out);
        rows.push([copy('ot.route.scanTime', 'เวลาสแกน'), scan.join(' · ')]);
        rows.push([copy('ot.route.checkedAt', 'ตรวจล่าสุด'), dateTime(request.attendance_checked_at)]);
      }
      /* ขั้นผลลัพธ์ไม่มีฟิลด์ — ตัวป้ายสถานะ (ผ่าน/ไม่ผ่าน/รอดำเนินการ) บอกครบแล้ว */

      return rows.filter(function (row) { return row[1]; });
    }

    /* ป้ายใต้วงกลมมีที่แค่ 1 ใน 4 ของความกว้าง จึงใช้คำสั้นกว่าหัวข้อในแผงรายละเอียด */
    function stageLabel(stage, short, request) {
      if (isLeave(request || currentRequest)) {
        if (stage === 'request') return copy('leave.requestTab', 'ขอลา');
        if (stage === 'approval') return copy('leave.approveTab', 'อนุมัติลา');

        return copy('ot.route.finalResult', 'ผลลัพธ์');
      }
      if (stage === 'request') return copy('ot.route.request', 'ขอ OT');
      if (stage === 'approval') return copy('ot.route.approval', 'อนุมัติ OT');
      if (stage === 'attendance') {
        return short ? copy('ot.route.scanTime', 'เวลาสแกน') : copy('ot.route.attendance', 'ตรวจเวลาสแกน OT');
      }

      return copy('ot.route.finalResult', 'ผลลัพธ์');
    }

    /* เปิดที่ขั้นแรกที่ยังไม่ผ่าน — คือขั้นที่ผู้ใช้กำลังอยากรู้จริง ๆ */
    function initialStage(states, request) {
      var stages = stagesOf(request);
      for (var index = 0; index < stages.length; index++) {
        if (states[stages[index]].tone !== 'success') return stages[index];
      }

      return 'final';
    }

    function showStage(stage) {
      var states = stageStates(currentRequest);
      var state = states[stage] || states.final;
      activeStage = stage;

      dialog.querySelectorAll('[data-route-step]').forEach(function (step) {
        step.setAttribute('aria-selected', String(step.dataset.routeStep === stage));
        step.tabIndex = step.dataset.routeStep === stage ? 0 : -1;
      });

      var detail = dialog.querySelector('[data-route-detail]');
      detail.dataset.tone = state.tone;
      dialog.querySelector('[data-route-step-name]').textContent = stageLabel(stage);
      dialog.querySelector('[data-route-detail-state]').textContent = state.label;

      var list = dialog.querySelector('[data-route-fields]');
      list.replaceChildren();
      detailFields(stage, currentRequest).forEach(function (row) { list.appendChild(field(row[0], row[1])); });
    }

    function buildTrack(states, request) {
      var track = dialog.querySelector('[data-route-track]');
      var stages = stagesOf(request);
      track.replaceChildren();
      track.classList.toggle('is-linear', stages.length === 3);

      stages.forEach(function (stage, index) {
        var state = states[stage];
        var step = document.createElement('button');
        step.type = 'button';
        step.className = 'ot-route-step';
        step.dataset.routeStep = stage;
        step.dataset.tone = state.tone;
        step.setAttribute('role', 'tab');
        step.setAttribute('aria-selected', 'false');
        step.tabIndex = -1;
        if (index > 0) step.dataset.line = lineTone(states[stages[index - 1]], state);

        var dot = document.createElement('span');
        dot.className = 'ot-route-dot';
        dot.innerHTML = marks[state.tone] || '';

        var name = document.createElement('small');
        name.textContent = stageLabel(stage, true, request);

        step.append(dot, name);
        step.setAttribute('aria-label', stageLabel(stage, false, request) + ': ' + state.label);
        step.addEventListener('click', function () { showStage(stage); });
        track.appendChild(step);
      });
    }

    function summaryText(request) {
      if (!request || !request.id) return '';
      var parts = [];

      if (isLeave(request)) {
        var leaveType = localized(request, 'leave_type_label', '');
        if (leaveType && leaveType !== '-') parts.push(leaveType);
        if (request.leave_quantity) parts.push(copy('leave.oneDay', '1 วัน'));

        return parts.join(' · ');
      }

      if (request.requested_start && request.requested_end) parts.push(request.requested_start + '–' + request.requested_end);
      var type = localized(request, 'ot_type_label', request.ot_type || '');
      if (type && type !== '-') parts.push(type);

      return parts.join(' · ');
    }

    function open(request, trigger, person) {
      currentRequest = request || null;
      currentPerson = person || null;
      previousFocus = trigger || document.activeElement;

      var states = stageStates(request);
      var hasRequest = Boolean(request && request.id);
      var who = personOf(request, person);

      paintPhoto(who);
      dialog.querySelector('[data-route-person-name]').textContent = who.name || copy('ot.route.title', 'สถานะทั้งหมด');
      dialog.querySelector('[data-route-person-meta]').textContent = hasRequest
        ? [who.code, isLeave(request) ? request.leave_date_label : workDate(request.work_date)].filter(Boolean).join(' · ')
        : copy('ot.route.noRequest', 'ยังไม่มีคำขอ');

      buildTrack(states, request);
      showStage(activeStage && dialog.querySelector('[data-route-step="' + activeStage + '"]') ? activeStage : initialStage(states, request));
      dialog.querySelector('[data-route-summary]').textContent = summaryText(request);

      if (!dialog.open) dialog.showModal();
      // วัดตำแหน่งได้หลัง dialog แสดงผลแล้วเท่านั้น ตอนซ่อนอยู่ทุก rect เป็นศูนย์
      drawLines(states);
      dialog.querySelector('[data-route-dialog-close]').focus();
    }

    function close() {
      closeZoom();
      if (dialog.open) dialog.close();
      activeStage = null;
      if (previousFocus && document.contains(previousFocus)) previousFocus.focus();
      previousFocus = null;
    }

    dialog.querySelector('[data-route-dialog-close]').addEventListener('click', close);
    dialog.addEventListener('click', function (event) {
      // กดรูปเพื่อขยาย · กดพื้นหลังของภาพขยายหรือปุ่มปิดเพื่อย้อนกลับ · กดนอก dialog เพื่อปิด
      if (event.target.closest('img[data-route-photo]')) { openZoom(); return; }
      var zoom = event.target.closest('[data-route-zoom]');
      if (zoom) {
        if (event.target.closest('[data-route-zoom-close]') || !event.target.closest('figure')) closeZoom();
        return;
      }
      if (event.target === dialog) close();
    });
    dialog.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      if (!event.target.closest || !event.target.closest('img[data-route-photo]')) return;
      event.preventDefault();
      openZoom();
    });
    // ESC ปิดภาพขยายก่อน ไม่ให้ปิด dialog ทั้งอันในจังหวะเดียว
    dialog.addEventListener('cancel', function (event) {
      if (dialog.querySelector('[data-route-zoom]').hidden) return;
      event.preventDefault();
      closeZoom();
    });
    dialog.addEventListener('close', function () {
      closeZoom();
      activeStage = null;
      if (previousFocus && document.contains(previousFocus)) previousFocus.focus();
      previousFocus = null;
    });
    if (window.ResizeObserver) {
      new ResizeObserver(function () {
        if (dialog.open) drawLines(stageStates(currentRequest));
      }).observe(dialog.querySelector('[data-route-track]'));
    }
    /* ลูกศรซ้าย-ขวาเดินระหว่างวงกลมได้ ตามพฤติกรรมมาตรฐานของ tablist */
    dialog.addEventListener('keydown', function (event) {
      if (['ArrowRight', 'ArrowLeft', 'Home', 'End'].indexOf(event.key) === -1) return;
      var steps = Array.prototype.slice.call(dialog.querySelectorAll('[data-route-step]'));
      if (!steps.length || steps.indexOf(document.activeElement) === -1) return;
      event.preventDefault();
      var current = steps.indexOf(document.activeElement);
      var next = current;
      if (event.key === 'ArrowRight') next = (current + 1) % steps.length;
      else if (event.key === 'ArrowLeft') next = (current - 1 + steps.length) % steps.length;
      else if (event.key === 'Home') next = 0;
      else next = steps.length - 1;
      steps[next].focus();
      showStage(steps[next].dataset.routeStep);
    });
    document.addEventListener('insight:languagechange', function () {
      if (dialog.open) open(currentRequest, previousFocus, currentPerson);
    });

    window.otProcessRoute = { create: create, open: open, close: close };
  })();
</script>
