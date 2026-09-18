@extends('layouts.portal')

@section('title', 'ดาวน์โหลดเอกสาร')

@section('topbar-title')
  <span class="tt-kicker">TIME &amp; LEAVE</span>
  <span class="tt-title" data-i18n="ot.downloads.title">ดาวน์โหลด</span>
@endsection

@section('page-style')
  .dl-page { width:min(100%, 78rem); margin:0 auto; }
  .dl-head { margin-bottom:1rem; }
  .dl-head h1 { font-size:1.6rem; font-weight:650; }
  .dl-head p { margin-top:.3rem; color:var(--muted-light); font-size:.82rem; }

  /* ── แท็บชนิดเอกสาร: OT กับ ลา 75 คนละปฏิทิน ─────────────── */
  .dl-tabs { display:inline-flex; gap:.3rem; margin-bottom:.85rem; padding:.25rem; border:1px solid var(--line-light); border-radius: 4px; background:var(--panel-soft); }
  .dl-tab {
    position:relative;
    display:inline-flex; align-items:center; gap:.4rem; min-height:2.2rem;
    /* เผื่อที่ขวาบนให้ป้ายแจ้งเตือน ตัวหนังสือจะได้ไม่ลอดใต้ป้าย */
    padding:.45rem 1.2rem .45rem .85rem;
    border:0; border-radius: 4px; background:transparent; color:var(--muted-light);
    font:inherit; font-size:.76rem; font-weight:700; cursor:pointer;
  }
  .dl-tab:hover { color:var(--light-text); }
  .dl-tab.is-active { background:var(--moss); color:#fff; }
  /* ป้ายแดงมุมขวาบนของแท็บ = ไฟล์ที่ยังต้องโหลดรวมทั้งเดือนของชนิดนั้น */
  .dl-tab-badge {
    position:absolute; top:.1rem; right:.15rem;
    min-width:1.05rem; height:1.05rem; padding:0 .26rem;
    display:inline-flex; align-items:center; justify-content:center;
    border-radius:999px; background:#e0245e; color:#fff;
    font-size:.58rem; font-weight:800; line-height:1; font-variant-numeric:tabular-nums;
  }
  .dl-tab-badge[hidden] { display:none; }

  .dl-bar { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin-bottom:1rem; }
  .dl-month { display:flex; align-items:center; gap:.45rem; color:var(--muted-light); font-size:.74rem; font-weight:600; }
  .dl-month input {
    padding:.45rem .55rem; border:1px solid var(--line-light); border-radius: 4px;
    background:var(--panel-soft); color:var(--light-text); color-scheme:light dark;
  }

  /* ── ปฏิทินรายเดือน ────────────────────────────────────────── */
  /* พื้นปฏิทินย้อมสีตามแท็บแบบอ่อน ๆ: OT = ฟ้า · ลา 75 = ชมพู (Manager สั่ง 2026-08-17)
     ผสมกับ token พื้นเดิมด้วย color-mix จึงยังอ่านง่ายทั้งธีมมืดและสว่าง ไม่ใช่สีทึบทับ */
  .dl-calendar {
    position:relative; overflow:hidden;
    border:1px solid color-mix(in srgb, var(--dl-tint) 22%, var(--line-light));
    border-radius: 5px;
    background:color-mix(in srgb, var(--dl-tint) 8%, var(--ot-tint-card));
    transition:background .2s ease, border-color .2s ease;
  }

  /* ธีมของแต่ละแท็บอยู่ที่ 2 ตัวแปรนี้: สีย้อม + ตัวลายน้ำหลังปฏิทิน
     OT = ฟ้า + กระต่าย · ลา 75 = ชมพู + แมว (Manager สั่ง 2026-08-17)
     ลายน้ำวาดด้วย mask ระบายสีจากตัวแปรเดียวกัน จึงเข้าชุดกันเองและไม่ต้องโหลดไฟล์ภาพเพิ่ม */
  .dl-page {
    /* ฟ้าอ่อนสำหรับ OT — ค่าเดียวคุมทั้งพื้นปฏิทิน เส้นขอบ และตัวลายน้ำ */
    --dl-tint: #5aa9e6;
    --dl-mascot: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120' fill='none' stroke='%23000' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cellipse cx='50' cy='28' rx='7' ry='21' transform='rotate(-12 50 28)'/%3E%3Cellipse cx='71' cy='28' rx='7' ry='21' transform='rotate(12 71 28)'/%3E%3Ccircle cx='60' cy='60' r='16'/%3E%3Cpath d='M60 76c-17 3-23 16-21 30h42c2-14-4-27-21-30z'/%3E%3Ccircle cx='87' cy='100' r='7'/%3E%3Ccircle cx='54' cy='58' r='1.8'/%3E%3Ccircle cx='66' cy='58' r='1.8'/%3E%3Cpath d='M57 65h6'/%3E%3Cpath d='M44 63l-12-3M44 68l-12 3M76 63l12-3M76 68l12 3'/%3E%3C/svg%3E");
  }
  .dl-page.is-tab-leave {
    /* ชมพูอ่อนสำหรับหยุดงานตามมาตรา 75 */
    --dl-tint: #f08fb4;
    --dl-mascot: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120' fill='none' stroke='%23000' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M45 30L43 13L58 22Z'/%3E%3Cpath d='M75 30L77 13L62 22Z'/%3E%3Ccircle cx='60' cy='40' r='18'/%3E%3Cpath d='M60 58c-19 4-25 20-23 46h46c2-26-4-42-23-46z'/%3E%3Cpath d='M83 104c19 0 25-11 20-21-4-8-14-7-16 1'/%3E%3Ccircle cx='53' cy='38' r='1.8'/%3E%3Ccircle cx='67' cy='38' r='1.8'/%3E%3Cpath d='M57 45l3 3 3-3'/%3E%3Cpath d='M42 41l-12-2M42 46l-12 2M78 41l12-2M78 46l12 2'/%3E%3C/svg%3E");
  }
  /* ตัวใหญ่เกือบครึ่งจอตามที่ Manager สั่ง — วางกลางปฏิทินเพื่อไม่ให้ถูก overflow ตัดขา
     ยิ่งใหญ่ยิ่งต้องจาง ไม่งั้นแย่งสายตากับตัวเลขวันที่และจุดแดง */
  .dl-calendar::before {
    content:''; position:absolute; z-index:0; pointer-events:none;
    left:50%; top:50%; transform:translate(-50%, -50%);
    width:min(46vw, 34rem); height:min(46vw, 34rem);
    background:var(--dl-tint); opacity:.16;
    -webkit-mask-image:var(--dl-mascot); mask-image:var(--dl-mascot);
    -webkit-mask-repeat:no-repeat; mask-repeat:no-repeat;
    -webkit-mask-position:center; mask-position:center;
    -webkit-mask-size:contain; mask-size:contain;
  }

  @media (max-width:720px) { .dl-calendar::before { width:70vw; height:70vw; } }
  .dl-weekdays, .dl-grid { display:grid; grid-template-columns:repeat(7, minmax(0, 1fr)); }
  .dl-weekdays {
    border-bottom:1px solid color-mix(in srgb, var(--dl-tint) 22%, var(--line-light));
    background:color-mix(in srgb, var(--dl-tint) 16%, var(--ot-tint-company));
    transition:background .2s ease;
  }
  .dl-weekdays span { padding:.55rem .4rem; color:var(--muted-light); font-size:.66rem; font-weight:700; text-align:center; }
  .dl-cell {
    position:relative; min-height:6rem; display:flex; flex-direction:column; gap:.28rem;
    padding:.5rem .55rem; border-right:1px solid var(--line-light); border-bottom:1px solid var(--line-light);
    background:transparent; color:inherit; text-align:left; font:inherit; cursor:pointer;
    transition:background .16s ease;
  }
  .dl-cell:nth-child(7n) { border-right:0; }
  .dl-cell:hover:not(:disabled) { background:var(--hover-soft); }
  .dl-cell:disabled { cursor:default; opacity:.5; }
  .dl-cell.is-blank { cursor:default; background:color-mix(in srgb, var(--line-light) 25%, transparent); }
  /* วงกลมแดงแบบแจ้งเตือน — จำนวนไฟล์ที่ยังไม่ได้โหลด (ตรงกับกระดิ่งดาวน์โหลดด้านบน) */
  .dl-badge {
    position:absolute; top:.4rem; right:.45rem;
    min-width:1.15rem; height:1.15rem; padding:0 .3rem;
    display:inline-flex; align-items:center; justify-content:center;
    border-radius:999px; background:#e0245e; color:#fff;
    font-size:.62rem; font-weight:800; line-height:1; font-variant-numeric:tabular-nums;
  }
  /* จำนวนเอกสารทั้งหมดของวันนั้น ตัวเล็กมุมขวาล่าง */
  .dl-files { margin-top:auto; align-self:flex-end; color:var(--muted-light); font-size:.62rem; font-variant-numeric:tabular-nums; }
  .dl-day { display:flex; align-items:center; justify-content:space-between; gap:.3rem; font-size:.82rem; font-weight:700; font-variant-numeric:tabular-nums; }
  /* ตัวเลขวันอยู่ในวงกลมเสมอ แต่วงจะโปร่งใส ยกเว้นวันนี้ที่ตีวงบาง ๆ ด้วยสีของแท็บ
     ทำเป็นวงตายตัวตั้งแต่ต้น เลขวันจึงไม่ขยับตอนสลับเดือน/แท็บ */
  .dl-day > span {
    min-width:1.75rem; height:1.75rem; display:inline-grid; place-items:center;
    border:1.5px solid transparent; border-radius:50%;
  }
  .dl-cell.is-today .dl-day > span {
    border-color:color-mix(in srgb, var(--dl-tint) 85%, var(--light-text));
    background:color-mix(in srgb, var(--dl-tint) 16%, transparent);
  }

  /* ── โมดัลรายละเอียดรายวัน ─────────────────────────────────── */
  .dl-modal { position:fixed; inset:0; z-index:1200; display:grid; place-items:center; padding:1rem; background:rgb(8 12 10 / 58%); backdrop-filter:blur(5px); }
  .dl-modal[hidden] { display:none; }
  .dl-dialog {
    width:min(60rem, 100%); max-height:92vh; display:flex; flex-direction:column; overflow:hidden;
    border:1px solid var(--line-light); border-radius: 6px; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 26%);
  }
  /* หัวโมดัลบรรทัดเดียว: ชนิดเอกสาร + วันที่ อยู่บรรทัดเดียวกัน */
  .dl-dialog-head { flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.9rem 1.1rem; border-bottom:1px solid var(--line-light); background:var(--ot-tint-company); }
  .dl-dialog-head h2 { font-size:.98rem; font-weight:650; }
  .dl-close { width:2.2rem; height:2.2rem; flex:0 0 auto; border:1px solid var(--line-light); border-radius:50%; background:transparent; color:inherit; font-size:1.1rem; cursor:pointer; }
  .dl-dialog-body { flex:1 1 auto; min-height:0; overflow:auto; padding:1rem 1.1rem; }

  .dl-table { width:100%; border-collapse:collapse; font-size:.75rem; }
  .dl-table th, .dl-table td { padding:.6rem .7rem; border:1px solid var(--line-light); text-align:center; vertical-align:middle; }
  .dl-table th { background:var(--panel-soft); color:var(--muted-light); font-size:.67rem; font-weight:700; }
  .dl-table .dl-no { width:3.2rem; color:var(--muted-light); font-variant-numeric:tabular-nums; }
  .dl-table td.is-date { font-size:.82rem; font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
  .dl-download {
    min-height:2.35rem; padding:.45rem .9rem; border:1px solid var(--moss); border-radius: 4px;
    background:var(--moss); color:#fff; font-size:.74rem; font-weight:700; cursor:pointer; white-space:nowrap;
  }
  .dl-download { position:relative; }
  /* จุดแดงบนปุ่ม = ไฟล์นี้ยังไม่เคยโหลด กดแล้วจะหายไปเอง */
  .dl-download.needs-download::after {
    content:''; position:absolute; top:-.25rem; right:-.25rem;
    width:.6rem; height:.6rem; border:2px solid var(--panel); border-radius:50%; background:#e0245e;
  }
  .dl-download:hover { background:color-mix(in srgb, var(--moss) 86%, #000); }
  .dl-download:disabled { border-color:var(--line-light); background:var(--panel-soft); color:var(--muted-light); cursor:not-allowed; }
  /* ปุ่มกางรายละเอียดรายแผนก — รายชื่อรายคนยาวเกินอ่าน (ลา 75 ทั้งบริษัทเป็นพันคน) */
  .dl-detail { margin-top:.25rem; padding:0; border:0; background:transparent; color:var(--moss); font:inherit; font-size:.68rem; font-weight:700; text-decoration:underline; cursor:pointer; }
  .dl-depts { display:grid; gap:.2rem; margin-top:.4rem; padding-top:.4rem; border-top:1px dashed var(--line-light); }
  .dl-depts[hidden] { display:none; }
  .dl-depts span { display:flex; align-items:baseline; justify-content:space-between; gap:.6rem; font-size:.7rem; }
  .dl-depts i { color:var(--muted-light); font-style:normal; }
  .dl-depts b { font-weight:700; font-variant-numeric:tabular-nums; }
  /* แถวของวันอื่นที่โผล่มาเพราะถูกยื่นในวันที่เปิดดู */
  .dl-table tr.is-other td { background:color-mix(in srgb, #d69a12 8%, transparent); }
  .dl-other-flag { display:inline-block; margin-top:.2rem; color:#a97a0d; font-size:.63rem; font-weight:700; }

  /* ไฟล์รวมของลา 75 — คำขอที่ยื่นถึงวันลาทั้งหมดอยู่ในฉบับเดียว */
  .dl-main-flag { display:inline-block; margin-top:.2rem; color:var(--moss); font-size:.63rem; font-weight:700; }
  .dl-empty { padding:1.4rem; color:var(--muted-light); font-size:.76rem; text-align:center; border:1px dashed var(--line-light); border-radius: 4px; }

  /* ── กล่องแจ้งผลหลังกดโหลด — โครงเดียวกับ .lr-success ของหน้าขอลา ───── */
  .dl-result { position:fixed; inset:0; z-index:1300; display:grid; place-items:center; padding:1rem; background:rgb(8 12 10 / 56%); backdrop-filter:blur(5px); }
  .dl-result[hidden] { display:none; }
  .dl-result-dialog { width:min(26rem, 100%); border:1px solid var(--line-light); border-radius: 6px; background:var(--panel); box-shadow:0 24px 70px rgb(0 0 0 / 24%); }
  .dl-result-mark { width:4rem; height:4rem; display:grid; place-items:center; margin:1.25rem auto .6rem; border-radius:50%; background:#35a863; color:#fff; font-size:2rem; }
  .dl-result.is-error .dl-result-mark { background:#da5a4e; }
  .dl-result-copy { padding:0 1.1rem 1.25rem; text-align:center; }
  .dl-result-copy h2 { font-size:1rem; font-weight:650; }
  .dl-result-copy p { margin:.4rem 0 1rem; color:var(--muted-light); font-size:.78rem; line-height:1.55; }
  .dl-result-copy button { min-height:2.25rem; padding:.45rem 1.4rem; border:1px solid var(--moss); border-radius: 4px; background:var(--moss); color:#fff; font:inherit; font-size:.75rem; font-weight:700; cursor:pointer; }

  @media (max-width:720px) {
    .dl-cell { min-height:4.8rem; padding:.4rem .35rem; }
  }
@endsection

@section('content')
  <main class="dl-page" data-ot-downloads
        data-calendar-url="{{ route('ot-approval.downloads.calendar') }}"
        data-day-url="{{ route('ot-approval.downloads.day') }}"
        data-ot-export-url="{{ route('ot-approval.exports.v74') }}"
        data-leave-export-url="{{ route('ot-approval.exports.leave75') }}"
        data-month="{{ $selectedMonth->format('Y-m') }}">
    {{-- หัวเรื่องเปลี่ยนตามแท็บ เพราะ OT กับ ลา 75 เป็นคนละไฟล์และคนละวิธีนับรอบ --}}
    <header class="dl-head">
      <h1 data-dl-heading data-i18n="ot.downloads.heading">ดาวน์โหลดเอกสาร OT</h1>
      <p data-dl-subtitle data-i18n="ot.downloads.subtitle">เลือกวันที่ต้องการส่งให้ HR — วันที่มีคำขอเข้ามาใหม่หลังโหลดไปแล้วจะขึ้นสีแดง</p>
    </header>

    {{-- แยกแท็บตามชนิดเอกสาร เพราะไฟล์ที่ HR นำเข้า Bplus เป็นคนละไฟล์และคนละ template --}}
    <div class="dl-tabs" role="tablist">
      <button class="dl-tab is-active" type="button" data-dl-tab="ot">
        <span data-i18n="ot.downloads.tabOt">เอกสารค่าล่วงเวลา (OT)</span>
        <b class="dl-tab-badge" data-dl-tab-badge="ot" hidden>0</b>
      </button>
      <button class="dl-tab" type="button" data-dl-tab="leave">
        <span data-i18n="ot.downloads.tabLeave">เอกสารหยุดงานตามมาตรา 75</span>
        <b class="dl-tab-badge" data-dl-tab-badge="leave" hidden>0</b>
      </button>
    </div>

    <div class="dl-bar">
      <label class="dl-month">
        <span data-i18n="ot.downloads.month">เดือน</span>
        <input type="month" value="{{ $selectedMonth->format('Y-m') }}" data-dl-month>
      </label>
    </div>

    <section class="dl-calendar" aria-label="ปฏิทินสถานะเอกสาร" data-i18n-aria="ot.downloads.calendarAria">
      <div class="dl-weekdays" aria-hidden="true">
        <span data-i18n="ot.calendar.sun">อา</span><span data-i18n="ot.calendar.mon">จ</span><span data-i18n="ot.calendar.tue">อ</span><span data-i18n="ot.calendar.wed">พ</span><span data-i18n="ot.calendar.thu">พฤ</span><span data-i18n="ot.calendar.fri">ศ</span><span data-i18n="ot.calendar.sat">ส</span>
      </div>
      <div class="dl-grid" data-dl-grid></div>
    </section>
  </main>

  {{-- โมดัลรายละเอียดรายวัน — 1 แถวคือ 1 เอกสารของวันนั้น ปุ่มโหลดอยู่ในแถว --}}
  <div class="dl-modal" data-dl-modal hidden>
    <section class="dl-dialog" role="dialog" aria-modal="true" aria-labelledby="dlDayTitle">
      <header class="dl-dialog-head">
        <h2 id="dlDayTitle" data-dl-title>—</h2>
        <button class="dl-close" type="button" data-dl-close aria-label="ปิด" data-i18n-aria="ot.common.close">×</button>
      </header>
      <div class="dl-dialog-body" data-dl-body></div>
    </section>
  </div>

  {{-- แจ้งผลหลังกดโหลด — ใช้โครงเดียวกับกล่อง "สำเร็จ" ของหน้าขอลา/อนุมัติลา --}}
  <div class="dl-result" data-dl-result hidden>
    <section class="dl-result-dialog" role="alertdialog" aria-modal="true" aria-labelledby="dlResultTitle">
      <div class="dl-result-mark" data-dl-result-mark>✓</div>
      <div class="dl-result-copy">
        <h2 id="dlResultTitle" data-dl-result-title data-i18n="ot.downloads.doneTitle">ดาวน์โหลดแล้ว</h2>
        <p data-dl-result-message data-i18n="ot.downloads.doneMessage">บันทึกไฟล์เรียบร้อย ส่งให้ HR นำเข้า Bplus ได้เลย</p>
        <button type="button" data-dl-result-close data-i18n="leave.ok">ตกลง</button>
      </div>
    </section>
  </div>
@endsection

@section('page-script')
  <script>
    (function otDownloads() {
      'use strict';

      var root = document.querySelector('[data-ot-downloads]');
      if (!root) return;

      var grid = root.querySelector('[data-dl-grid]');
      var modal = document.querySelector('[data-dl-modal]');
      var modalTitle = modal.querySelector('[data-dl-title]');
      var modalBody = modal.querySelector('[data-dl-body]');
      var result = document.querySelector('[data-dl-result]');
      var month = root.dataset.month;
      var calendar = @json($calendar);
      var activeTab = 'ot';   // ชนิดเอกสารที่กำลังดู: ot | leave
      var openedDate = null;  // วันที่เปิดใน modal ใช้รีเฟรชหลังกดโหลด
      var openedDocs = [];    // เอกสารที่กำลังแสดงในตาราง ใช้อ่าน params ตอนกดโหลด

      var thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

      function text(key, fallback) {
        return window.__portalLang ? window.__portalLang.text(key, fallback) : fallback;
      }

      function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>'"]/g, function (c) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[c];
        });
      }

      /** 2026-08-14 -> 14 สิงหาคม 2569 */
      function thaiDate(value) {
        var parts = String(value).split('-');
        return Number(parts[2]) + ' ' + thaiMonths[Number(parts[1])] + ' ' + (Number(parts[0]) + 543);
      }

      /* วันนี้ตามเวลาเครื่อง ไม่ใช่ toISOString ที่เป็น UTC
         ถ้าใช้ UTC ช่วงเที่ยงคืนถึง 7 โมงเช้าบ้านเราจะไปวงวันเมื่อวานแทน */
      function localToday() {
        var now = new Date();

        return now.getFullYear() + '-'
          + String(now.getMonth() + 1).padStart(2, '0') + '-'
          + String(now.getDate()).padStart(2, '0');
      }

      function tabLabel() {
        return activeTab === 'ot'
          ? text('ot.downloads.tabOt', 'เอกสารค่าล่วงเวลา (OT)')
          : text('ot.downloads.tabLeave', 'เอกสารหยุดงานตามมาตรา 75');
      }

      /* หัวเรื่องหน้าเปลี่ยนตามแท็บ เพราะกติกาไฟล์ต่างกัน
         OT แยกไฟล์ตามวันที่ยื่น ส่วนลา 75 รวมคำขอที่ยื่นถึงวันลาเป็นไฟล์เดียว */
      function renderHead() {
        var heading = root.querySelector('[data-dl-heading]');
        var subtitle = root.querySelector('[data-dl-subtitle]');
        var headKey = activeTab === 'ot' ? 'ot.downloads.heading' : 'ot.downloads.headingLeave';
        var subKey = activeTab === 'ot' ? 'ot.downloads.subtitle' : 'ot.downloads.subtitleLeave';

        // สลับลายน้ำหลังปฏิทิน: OT = กระต่าย · ลา 75 = แมว
        root.classList.toggle('is-tab-leave', activeTab === 'leave');

        heading.setAttribute('data-i18n', headKey);
        subtitle.setAttribute('data-i18n', subKey);
        heading.textContent = activeTab === 'ot'
          ? text(headKey, 'ดาวน์โหลดเอกสาร OT')
          : text(headKey, 'ดาวน์โหลดเอกสารหยุดงานตามมาตรา 75');
        subtitle.textContent = activeTab === 'ot'
          ? text(subKey, 'เลือกวันที่ต้องการส่งให้ HR — วันที่มีคำขอเข้ามาใหม่หลังโหลดไปแล้วจะขึ้นสีแดง')
          : text(subKey, 'เลือกวันลาที่ต้องการส่งให้ HR — คำขอที่ยื่นถึงวันลารวมอยู่ในไฟล์เดียว');
      }

      /* ตัวเลขบนแท็บ = ไฟล์ที่ยังไม่ได้โหลด รวมทุกวันของเดือนที่กำลังดู */
      function renderTabBadges(data) {
        ['ot', 'leave'].forEach(function (module) {
          var pending = data.days.reduce(function (sum, day) { return sum + day[module].pending_files; }, 0);
          var badge = root.querySelector('[data-dl-tab-badge="' + module + '"]');
          badge.textContent = pending > 99 ? '99+' : pending;
          badge.hidden = pending === 0;
        });
      }

      function renderCalendar(data) {
        calendar = data;
        month = data.month;
        renderTabBadges(data);
        grid.replaceChildren();

        var first = new Date(data.month + '-01T00:00:00');
        for (var blank = 0; blank < first.getDay(); blank++) {
          var pad = document.createElement('div');
          pad.className = 'dl-cell is-blank';
          grid.appendChild(pad);
        }

        var today = localToday();
        data.days.forEach(function (day) {
          var stats = day[activeTab];
          var cell = document.createElement('button');
          cell.type = 'button';
          cell.className = 'dl-cell';
          if (day.date === today) cell.classList.add('is-today');
          cell.disabled = stats.files === 0;

          var head = document.createElement('span');
          head.className = 'dl-day';
          head.innerHTML = '<span>' + day.day + '</span>';
          cell.appendChild(head);

          // วงกลมแดง = ไฟล์ที่ยังไม่ได้โหลด · โหลดครบแล้ววงกลมหายไปเอง
          if (stats.pending_files) {
            var badge = document.createElement('span');
            badge.className = 'dl-badge';
            badge.textContent = stats.pending_files > 99 ? '99+' : stats.pending_files;
            cell.appendChild(badge);
          }

          if (stats.files) {
            var files = document.createElement('span');
            files.className = 'dl-files';
            files.textContent = stats.files + ' ' + text('ot.downloads.fileUnit', 'ฉบับ');
            cell.appendChild(files);
          }

          cell.addEventListener('click', function () { openDay(day.date); });
          grid.appendChild(cell);
        });
      }

      async function openDay(date) {
        openedDate = date;
        modalTitle.textContent = tabLabel() + ' · ' + thaiDate(date);
        modalBody.innerHTML = '<div class="dl-empty">' + esc(text('leave.loading', 'กำลังโหลด...')) + '</div>';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        try {
          var response = await fetch(root.dataset.dayUrl + '?date=' + encodeURIComponent(date), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });
          var data = await response.json();
          if (!response.ok || !data.ok) throw new Error('failed');
          renderDay(data.day);
        } catch (error) {
          modalBody.innerHTML = '<div class="dl-empty">' + esc(text('leave.loadListError', 'ไม่สามารถโหลดรายการได้')) + '</div>';
        }
      }

      /** 1 วัน = 1 เอกสาร = 1 แถว ปุ่มโหลดอยู่ในแถวนั้น ไม่ต้องไล่รายชื่อพนักงาน */
      /**
       * 1 แถว = 1 เอกสาร (1 วันทำงาน/วันลา) ปุ่มโหลดอยู่ในแถว
       * เปิดวันไหนจะเห็นเอกสารของวันนั้น บวกเอกสารของวันอื่นที่ถูกยื่นเข้ามาในวันนั้น
       * เพราะไฟล์ที่ HR นำเข้า Bplus แยกตามวันทำงาน ต้องโหลดคนละไฟล์
       */
      function renderDay(day) {
        var documents = day[activeTab].documents || [];
        openedDocs = documents;

        if (!documents.length) {
          modalBody.innerHTML = '<div class="dl-empty">'
            + esc(text('ot.downloads.noDocument', 'วันนี้ไม่มีเอกสารที่ต้องส่ง')) + '</div>';

          return;
        }

        /* OT: แถวหนึ่ง = คำขอที่ยื่นเข้ามาวันหนึ่ง = ไฟล์หนึ่งใบ
           ลา 75: แถวแรกคือไฟล์รวมของวันลา แถวถัดไปคือรอบที่ยื่นหลังวันลาไปแล้ว */
        var dateColumn = activeTab === 'ot'
          ? text('ot.downloads.colSubmitted', 'วันที่ยื่นคำขอ')
          : text('ot.downloads.colDocument', 'เอกสาร');

        var head = '<table class="dl-table"><thead><tr>'
          + '<th class="dl-no">' + esc(text('common.rowNo', 'ลำดับ')) + '</th>'
          + '<th>' + esc(dateColumn) + '</th>'
          + '<th>' + esc(text('ot.downloads.colPeople', 'จำนวนที่ส่ง')) + '</th>'
          + '<th>' + esc(text('ot.downloads.lastDownload', 'โหลดล่าสุด')) + '</th>'
          + '<th>' + esc(text('leave.action', 'ดำเนินการ')) + '</th>'
          + '</tr></thead><tbody>';

        var body = documents.map(function (doc, index) {
          // นับครั้งต่อท้ายเวลาโหลดล่าสุด ไม่แยกเป็นคอลัมน์ให้ตารางกว้างเกิน
          var last = doc.last_at
            ? esc(doc.last_at) + ' <small>(' + doc.download_count + ' ' + esc(text('ot.downloads.times', 'ครั้ง')) + ')</small>'
            : '-';

          // ไฟล์รวมของลา 75 ติดป้ายให้ชัดว่ารวมทุกคำขอที่ยื่นถึงวันลาไว้แล้ว ไม่ต้องหาไฟล์อื่นเพิ่ม
          var flag = '';
          if (doc.is_main) {
            flag = '<br><span class="dl-main-flag">'
              + esc(text('ot.downloads.mainDoc', 'รวมคำขอที่ยื่นถึงวันลา')) + '</span>';
          } else if (doc.is_other_date) {
            flag = '<br><span class="dl-other-flag">'
              + esc(text('ot.downloads.lateFlag', 'ยื่นย้อนหลัง')) + '</span>';
          }

          return '<tr' + (doc.is_other_date ? ' class="is-other"' : '') + '>'
            + '<td class="dl-no">' + (index + 1) + '</td>'
            + '<td class="is-date">' + esc(thaiDate(doc.date)) + flag
            + '</td>'
            + '<td><b>' + doc.passed + '</b> ' + esc(text('ot.attendance.people', 'คน'))
            + '<br><button class="dl-detail" type="button" data-dl-detail="' + index + '">'
            + esc(text('ot.downloads.showDetail', 'ดูรายละเอียด')) + '</button>'
            + '<div class="dl-depts" data-dl-depts="' + index + '" hidden>'
            + doc.departments.map(function (dept) {
              return '<span><i>' + esc(dept.name) + '</i><b>' + dept.count + ' '
                + esc(text('ot.attendance.people', 'คน')) + '</b></span>';
            }).join('')
            + '</div></td>'
            + '<td>' + last + '</td>'
            + '<td><button class="dl-download' + (doc.needs_download ? ' needs-download' : '') + '" type="button" data-dl-download="' + index + '"'
            + (doc.passed ? '' : ' disabled') + '>' + esc(text('ot.downloads.download', 'ดาวน์โหลด')) + '</button></td>'
            + '</tr>';
        }).join('');

        modalBody.innerHTML = head + body + '</tbody></table>';

        modalBody.querySelectorAll('[data-dl-detail]').forEach(function (button) {
          button.addEventListener('click', function () {
            var box = modalBody.querySelector('[data-dl-depts="' + button.dataset.dlDetail + '"]');
            box.hidden = !box.hidden;
            button.textContent = box.hidden
              ? text('ot.downloads.showDetail', 'ดูรายละเอียด')
              : text('ot.downloads.hideDetail', 'ซ่อนรายละเอียด');
          });
        });

        modalBody.querySelectorAll('[data-dl-download]').forEach(function (button) {
          button.addEventListener('click', function () {
            downloadDocument(openedDocs[Number(button.dataset.dlDownload)], button);
          });
        });
      }

      /**
       * โหลดไฟล์ผ่าน fetch แทนการเปลี่ยน location เพื่อให้รู้ผลจริงแล้วขึ้นกล่องแจ้งผลได้
       * ขอบเขตของไฟล์มาจาก doc.params ที่ server คำนวณไว้แล้ว
       *   OT / ลาย้อนหลัง → submitted_on = เฉพาะรอบที่ยื่นวันนั้น
       *   ลา 75 ไฟล์รวม  → submitted_until = ทุกคำขอที่ยื่นถึงวันลา
       * ทั้งสองแบบกัน Bplus บวกจำนวนซ้ำตอน import เพราะคนละไฟล์ไม่มีรายการทับกัน
       */
      async function downloadDocument(doc, button) {
        if (!doc) return;

        var base = activeTab === 'ot' ? root.dataset.otExportUrl : root.dataset.leaveExportUrl;
        var url = base + '?' + new URLSearchParams(doc.params).toString();
        button.disabled = true;

        try {
          var response = await fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });

          if (!response.ok) {
            var payload = await response.json().catch(function () { return {}; });
            throw new Error(payload.message || '');
          }

          /* ถ้าหมดเซสชันแล้ว server จะตอบหน้า login เป็น 200 ต้องไม่เซฟทับเป็นไฟล์ .xlsx เปล่า */
          if ((response.headers.get('Content-Type') || '').indexOf('spreadsheet') === -1) {
            throw new Error(text('ot.downloads.failSession', 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่แล้วลองอีกครั้ง'));
          }

          saveFile(await response.blob(), filenameOf(response, doc));
          showResult(true, '');
          // server บันทึกว่าโหลดแล้ว จึงต้องดึงปฏิทินและตารางใหม่ให้จุดแดงกับจำนวนครั้งตรง
          loadCalendar(month);
          openDay(openedDate);
        } catch (error) {
          showResult(false, error.message);
        } finally {
          button.disabled = false;
        }
      }

      /** ชื่อไฟล์จาก Content-Disposition ของ server ถ้าอ่านไม่ได้ค่อยประกอบเอง */
      function filenameOf(response, doc) {
        var header = response.headers.get('Content-Disposition') || '';
        var utf8 = header.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8) return decodeURIComponent(utf8[1]);

        var plain = header.match(/filename="?([^";]+)"?/i);
        if (plain) return plain[1].trim();

        return (activeTab === 'ot' ? 'V74_' : 'LEAVE75_') + String(doc.date).replace(/-/g, '') + '.xlsx';
      }

      function saveFile(blob, filename) {
        var href = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = href;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(href); }, 1000);
      }

      /* กล่องแจ้งผล — โครงเดียวกับกล่อง "สำเร็จ" ของหน้าขอลา ให้ทั้งระบบหน้าตาเดียวกัน */
      function showResult(ok, message) {
        var title = result.querySelector('[data-dl-result-title]');
        var body = result.querySelector('[data-dl-result-message]');
        var titleKey = ok ? 'ot.downloads.doneTitle' : 'ot.downloads.failTitle';
        var bodyKey = ok ? 'ot.downloads.doneMessage' : 'ot.downloads.failMessage';

        result.classList.toggle('is-error', !ok);
        result.querySelector('[data-dl-result-mark]').textContent = ok ? '✓' : '!';
        title.setAttribute('data-i18n', titleKey);
        title.textContent = ok
          ? text(titleKey, 'ดาวน์โหลดแล้ว')
          : text(titleKey, 'ดาวน์โหลดไม่สำเร็จ');

        // ข้อความจริงจาก server (เช่น ไม่มีรายการที่ผ่าน) สำคัญกว่าข้อความ i18n กลาง
        if (!ok && message) {
          body.removeAttribute('data-i18n');
          body.textContent = message;
        } else {
          body.setAttribute('data-i18n', bodyKey);
          body.textContent = ok
            ? text(bodyKey, 'บันทึกไฟล์เรียบร้อย ส่งให้ HR นำเข้า Bplus ได้เลย')
            : text(bodyKey, 'สร้างไฟล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        result.hidden = false;
      }

      function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
      }

      async function loadCalendar(value) {
        try {
          var response = await fetch(root.dataset.calendarUrl + '?month=' + encodeURIComponent(value), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          });
          var data = await response.json();
          if (!response.ok || !data.ok) throw new Error('failed');
          renderCalendar(data.calendar);
        } catch (error) {
          grid.innerHTML = '<div class="dl-empty">' + esc(text('leave.loadListError', 'ไม่สามารถโหลดรายการได้')) + '</div>';
        }
      }

      root.querySelectorAll('[data-dl-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
          activeTab = tab.dataset.dlTab;
          root.querySelectorAll('[data-dl-tab]').forEach(function (item) {
            item.classList.toggle('is-active', item === tab);
          });
          renderHead();               // หัวเรื่องต้องบอกชนิดเอกสารที่กำลังดู
          renderCalendar(calendar);   // ข้อมูลชุดเดิม แค่เปลี่ยนว่าอ่านฝั่งไหน
          if (!modal.hidden && openedDate) openDay(openedDate);
        });
      });

      root.querySelector('[data-dl-month]').addEventListener('change', function (event) {
        if (!event.target.value) return;
        loadCalendar(event.target.value);
      });

      modal.querySelector('[data-dl-close]').addEventListener('click', closeModal);
      modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(); });

      function closeResult() { result.hidden = true; }
      result.querySelector('[data-dl-result-close]').addEventListener('click', closeResult);
      result.addEventListener('click', function (event) { if (event.target === result) closeResult(); });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        // ปิดกล่องแจ้งผลก่อน ไม่งั้นกด Esc ครั้งเดียวจะปิดตารางที่ยังต้องใช้ต่อ
        if (!result.hidden) closeResult();
        else if (!modal.hidden) closeModal();
      });

      document.addEventListener('insight:languagechange', function () {
        renderHead();
        renderCalendar(calendar);
        if (!modal.hidden && openedDate) openDay(openedDate);
      });

      renderHead();
      renderCalendar(calendar);
    })();
  </script>
@endsection
