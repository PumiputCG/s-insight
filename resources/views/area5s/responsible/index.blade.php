@extends('layouts.portal')

@php
  $isReviewMode = ($mode ?? 'work') === 'review';
  $pageTitle = $isReviewMode ? 'ตรวจประเมิน' : 'งานพื้นที่ของฉัน';
@endphp

@section('title', $pageTitle)

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="{{ $isReviewMode ? 'a5s.myWork.reviewTitle' : 'a5s.myWork.title' }}">{{ $pageTitle }}</span>
@endsection

@section('page-style')
    .a5r-wrap { display:grid; gap:.75rem; width:min(100%, 84rem); margin:0 auto; }

    /* ---- 1. คะแนนของฉัน: ตัวเลขใหญ่ + บอกที่มา + สีตามเกณฑ์ ---- */
    .a5r-score { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.6rem; }
    @media (max-width: 520px) { .a5r-score { grid-template-columns:1fr; } }
    .a5r-score-card { display:grid; gap:.15rem; padding:.7rem .9rem; border:1px solid var(--line-light); border-left:3px solid var(--sc, var(--muted-light)); border-radius: 0.28rem; background:var(--panel-soft); }
    .a5r-score-card .lb { color:var(--muted-light); font-size:.72rem; font-weight:650; }
    .a5r-score-card .vl { color:var(--sc, var(--light-text)); font-size:1.65rem; font-weight:850; line-height:1.15; }
    .a5r-score-card .ctx { color:var(--muted-light); font-size:.7rem; }
    .a5r-score-card.sc-good { --sc:#4caf7d; }
    .a5r-score-card.sc-warn { --sc:#c8964a; }
    .a5r-score-card.sc-bad  { --sc:#d98a80; }
    .a5r-score-card.sc-none { --sc:var(--muted-light); }
    .a5r-score-card.sc-none .vl { font-size:1.15rem; }

    /* ---- 2. งานที่ต้องทำ ---- */
    .a5r-panel { border:1px solid var(--line-light); border-radius: 0.3rem; background:var(--panel-soft); padding:.7rem .85rem .8rem; }
    .a5r-panel-head { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin-bottom:.6rem; padding-bottom:.5rem; border-bottom:1px solid var(--line-light); }
    .a5r-panel-head h2 { margin:0; color:var(--light-text); font-size:.92rem; font-weight:800; }
    .a5r-tally { display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; margin-left:auto; }
    .a5r-tally span { display:inline-flex; align-items:center; gap:.3rem; padding:.16rem .5rem; border-radius:999px; background:var(--menu-bg); color:var(--light-text); font-size:.7rem; font-weight:700; }
    .a5r-tally span::before { content:""; width:.42rem; height:.42rem; border-radius:50%; background:currentColor; }
    .a5r-tally span b { font-weight:850; }
    .a5r-tally .st-pass { color:#4caf7d; }
    .a5r-tally .st-fail { color:#d98a80; }
    .a5r-tally .st-pending { color:#c8964a; }
    .a5r-tally .st-none { color:var(--muted-light); }

    .a5r-rows { display:grid; gap:.35rem; }
    .a5r-row { display:grid; grid-template-columns:minmax(0, 1fr) auto auto; align-items:center; gap:.6rem; padding:.5rem .6rem; border:1px solid var(--line-light); border-left:3px solid var(--rc, var(--line-light)); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; text-decoration:none; transition:border-color .15s ease, transform .15s ease; }
    .a5r-row:hover, .a5r-row:focus-visible { border-color:var(--moss); border-left-color:var(--rc, var(--moss)); transform:translateX(2px); outline:none; }
    .a5r-row.rc-pass { --rc:#4caf7d; }
    .a5r-row.rc-fail { --rc:#d98a80; }
    .a5r-row.rc-pending { --rc:#c8964a; }
    .a5r-row.rc-none, .a5r-row.rc-idle, .a5r-row.rc-draft { --rc:var(--line-strong); }
    .a5r-row-main { display:grid; gap:.1rem; min-width:0; }
    .a5r-row-main strong { display:flex; align-items:baseline; gap:.35rem; min-width:0; color:var(--light-text); font-size:.82rem; font-weight:750; }
    .a5r-row-main strong .pt { flex:none; padding:.02rem .35rem; border-radius:.3rem; background:var(--panel-soft); color:var(--muted-light); font-size:.68rem; font-weight:800; }
    .a5r-row-main strong .nm { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5r-row-main small { color:var(--muted-light); font-size:.7rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5r-status { flex:none; display:inline-flex; align-items:center; gap:.3rem; padding:.14rem .55rem; border-radius:999px; background:var(--hover-soft); color:var(--muted-light); font-size:.7rem; font-weight:800; white-space:nowrap; }
    .a5r-status::before { content:""; width:.36rem; height:.36rem; border-radius:50%; background:currentColor; }
    .a5r-status.st-pass { background:rgb(76 175 125 / 14%); color:#4caf7d; }
    .a5r-status.st-fail { background:rgb(217 138 128 / 15%); color:#d98a80; }
    .a5r-status.st-pending { background:rgb(200 150 74 / 16%); color:#c8964a; }
    .a5r-status.st-draft { background:rgb(200 150 74 / 12%); color:#c8964a; }
    .a5r-status.st-none, .a5r-status.st-idle { background:transparent; border:1px dashed var(--line-light); color:var(--muted-light); }
    .a5r-note-btn { flex:none; display:inline-flex; align-items:center; padding:.12rem .45rem; border:1px solid rgb(217 138 128 / 42%); border-radius:999px; background:rgb(217 138 128 / 10%); color:#d98a80; cursor:pointer; font-size:.66rem; font-weight:800; white-space:nowrap; }
    .a5r-note-btn:hover, .a5r-note-btn:focus-visible { background:rgb(217 138 128 / 18%); outline:none; }
    .a5r-note-btn.is-pass { border-color:rgb(76 175 125 / 45%); background:rgb(76 175 125 / 10%); color:#4caf7d; }
    .a5r-note-btn.is-pass:hover, .a5r-note-btn.is-pass:focus-visible { background:rgb(76 175 125 / 18%); }
    .a5r-empty { padding:1.5rem 1rem; text-align:center; color:var(--muted-light); border:1px dashed var(--line-light); border-radius: 0.25rem; font-size:.82rem; }
    @media (max-width: 560px) {
      .a5r-row { grid-template-columns:minmax(0, 1fr) auto; }
      .a5r-note-btn { grid-column:2; }
    }

    /* ---- 3. แผนผังโรงงาน: พับเก็บได้ (ค่าเริ่มต้น = ปิด) ---- */
    .a5r-map { border:1px solid var(--line-light); border-radius: 0.3rem; background:var(--panel-soft); }
    .a5r-map > summary { display:flex; align-items:center; gap:.5rem; padding:.6rem .85rem; color:var(--light-text); font-size:.86rem; font-weight:750; cursor:pointer; list-style:none; }
    .a5r-map > summary::-webkit-details-marker { display:none; }
    .a5r-map > summary::before { content:"›"; display:inline-block; color:var(--moss); font-size:1.05rem; font-weight:900; transition:transform .18s ease; }
    .a5r-map[open] > summary::before { transform:rotate(90deg); }
    .a5r-map > summary small { color:var(--muted-light); font-size:.72rem; font-weight:600; }
    .a5r-map-body { padding:0 .5rem .5rem; }
    /* แผนที่ในหน้านี้ย่อลง — ไม่ให้กินจอทั้งหน้าเหมือนเดิม */
    .a5r-map-body .a5map-shell { border:0; background:transparent; padding:0; }
    .a5r-map-body .a5map-stage { --a5map-stage-min:260px; max-height:60vh; }

    /* ---- modal หมายเหตุ ---- */
    .a5r-note-modal[hidden] { display:none; }
    .a5r-note-modal { position:fixed; inset:0; z-index:1800; display:grid; place-items:center; padding:1rem; background:rgb(0 0 0 / 50%); }
    .a5r-note-backdrop { position:absolute; inset:0; border:0; background:transparent; cursor:pointer; }
    .a5r-note-dialog { position:relative; z-index:1; width:min(100%, 26rem); border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-soft); padding:1rem; box-shadow:0 18px 55px rgb(0 0 0 / 36%); }
    .a5r-note-dialog h3 { margin:0 0 .18rem; color:var(--light-text); font-size:.98rem; }
    .a5r-note-dialog small { color:var(--muted-light); font-size:.74rem; }
    .a5r-note-dialog p { margin:.75rem 0 0; color:var(--light-text); font-size:.84rem; line-height:1.55; white-space:pre-line; }
    .a5r-note-close { position:absolute; top:.65rem; right:.65rem; width:1.8rem; height:1.8rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; }
    .a5r-note-close:hover, .a5r-note-close:focus-visible { border-color:#d98a80; color:#d98a80; outline:none; }

    /* ---- Review workspace: โซน → อาคาร → ชั้น → Layout → จุดประเมิน ----
       โทเคนสีต้องประกาศให้ครอบ <dialog> ประวัติด้วย เพราะ dialog อยู่ "นอก" .a5rv-shell
       ตัวแปร CSS สืบทอดตาม DOM tree เท่านั้น — ถ้าประกาศแค่บน .a5rv-shell สถานะ/หมายเหตุ
       ในโมดัลจะได้ var() ที่ไม่มีค่า แล้วสีหายทั้งหมด (Manager แจ้ง 2026-07-31) */
    .a5rv-shell,
    .a5rv-history-dialog {
      --a5rv-ready:#a7b88b;
      --a5rv-wait:#b1b5ae;
      --a5rv-rework:#d7a06f;
      --a5rv-done:#83b39a;
      /* ผ่าน = เขียวชัด · ไม่ผ่าน = แดงชัด */
      --a5rv-pass:#4dbe86;
      --a5rv-fail:#e8635a;
      --a5rv-pending:#c8964a;
      --a5rv-no-data:#9da39b;
      /* พื้นหลังในกรอบ = เทาอ่อน แยกจากพื้นหน้าสีขาว — ใช้โทเคนกลางจาก layouts/portal.blade.php */
      --a5rv-surface: var(--panel-tint);
      --a5rv-surface-strong: var(--panel-tint-strong);
    }
    html[data-theme="light"] .a5rv-shell,
    html[data-theme="light"] .a5rv-history-dialog {
      --a5rv-ready:#5d713f;
      --a5rv-wait:#646862;
      --a5rv-rework:#9b5d28;
      --a5rv-done:#366e52;
      --a5rv-pass:#137a4a;
      --a5rv-fail:#c62828;
      --a5rv-pending:#885b18;
      --a5rv-no-data:#5d625c;
    }
    .a5rv-shell {
      display:grid;
      gap:.75rem;
      width:min(100%, 88rem);
      margin:0 auto;
      font-kerning:normal;
    }
    /* 2 กรอบ (Manager 2026-08-26): [1] ช่วงเวลา + อาคาร · [2] แท็บสถานะ → ชั้น → พื้นที่ + ตารางจุด */
    .a5rv-board { display:grid; gap:.75rem; background:transparent; }
    .a5rv-context { overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--a5rv-surface); }

    .a5rv-filters {
      display:flex;
      gap:.45rem;
      overflow-x:auto;
      padding:.15rem .05rem .25rem;
      scrollbar-width:thin;
    }
    /* แท็บสถานะ: ปุ่มสีชุดเดียวกับแท็บชั้น F1/F2/F3 (Manager 2026-08-27) */
    .a5rv-filter {
      min-height:2.2rem;
      flex:0 0 auto;
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      border:1px solid var(--line-light);
      border-radius: 0.28rem;
      padding:.4rem .95rem;
      background:var(--menu-bg);
      color:var(--muted-light);
      font:inherit;
      font-size:.8rem;
      font-weight:750;
      cursor:pointer;
      transition:border-color .16s ease, background-color .16s ease, color .16s ease;
    }
    .a5rv-filter::before { content:none; }
    .a5rv-filter[data-review-filter="all"] { --bucket-color:var(--moss); }
    .a5rv-filter[data-review-filter="pass"] { --bucket-color:var(--a5rv-pass); }
    .a5rv-filter[data-review-filter="fail"] { --bucket-color:var(--a5rv-fail); }
    .a5rv-filter[data-review-filter="pending"] { --bucket-color:var(--a5rv-pending); }
    .a5rv-filter[data-review-filter="no_data"] { --bucket-color:var(--a5rv-no-data); }
    .a5rv-filter:hover { border-color:var(--moss); color:var(--moss); }
    .a5rv-filter[aria-pressed="true"] { border-color:var(--moss); background:var(--moss); color:#fff; }
    .a5rv-filter-count { color:inherit; font-variant-numeric:tabular-nums; font-weight:800; }

    /* แถบอาคาร: ภาพย่อ + ชื่อ + ข้อมูลย่อบรรทัดเดียว (เดิมสูงเกือบครึ่งจอ) */
    .a5rv-building-previews { display:block; }
    .a5rv-building-preview {
      display:grid;
      grid-template-columns:5rem minmax(0, 1fr);
      align-items:center;
      gap:.9rem;
      padding:.75rem 1rem;
      background:transparent;
      color:var(--muted-light);
    }
    .a5rv-building-preview[hidden] { display:none !important; }
    .a5rv-building-card-image {
      width:5rem;
      height:3.1rem;
      overflow:hidden;
      border:0;
      border-radius: 0.22rem;
      background-color:var(--menu-bg);
      background-repeat:no-repeat;
      background-size:cover;
      background-position:center;
    }
    .a5rv-building-card-image.is-empty { display:grid; place-items:center; color:var(--muted-light); }
    .a5rv-building-card-image.is-empty svg { width:1.4rem; height:1.4rem; stroke:currentColor; }
    .a5rv-building-card-copy { display:grid; align-content:center; gap:.18rem; min-width:0; }
    .a5rv-building-card-title strong { display:block; overflow:hidden; color:var(--light-text); font-size:1rem; font-weight:760; line-height:1.35; text-overflow:ellipsis; white-space:nowrap; }
    /* ข้อมูลย่อคั่นด้วย · แทนกล่องตัวเลข 3 ช่องที่ระยะห่างไม่เท่ากัน */
    .a5rv-building-meta { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; color:var(--muted-light); font-size:.74rem; line-height:1.45; }
    .a5rv-building-meta b { color:var(--light-text); font-weight:700; font-variant-numeric:tabular-nums; }
    .a5rv-building-meta i { font-style:normal; opacity:.5; }

    .a5rv-period-bar { padding:.8rem 1rem .2rem; background:transparent; }
    .a5rv-month-form { display:flex; align-items:end; justify-content:flex-start; gap:.55rem; flex-wrap:wrap; }
    .a5rv-month-field { display:grid; gap:.3rem; min-width:9.5rem; }
    .a5rv-month-field.is-building { min-width:min(100%, 16rem); }
    .a5rv-month-field span { color:var(--muted-light); font-size:.7rem; }
    .a5rv-month-field select {
      width:100%;
      min-height:2.6rem;
      border:1px solid var(--line-light);
      border-radius: 0.25rem;
      padding:.45rem 2.2rem .45rem .75rem;
      background:var(--panel-soft);
      color:var(--light-text);
      font:inherit;
      font-size:.82rem;
      font-weight:700;
      cursor:pointer;
    }
    .a5rv-locations { display:block; overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--a5rv-surface); }
    .a5rv-location { background:transparent; }
    .a5rv-building-empty {
      display:grid;
      place-content:center;
      justify-items:center;
      gap:.3rem;
      padding:2.6rem 1rem;
      text-align:center;
    }
    .a5rv-building-empty svg { width:1.7rem; height:1.7rem; margin-bottom:.2rem; color:var(--muted-light); stroke:currentColor; }
    .a5rv-building-empty h2 { margin:0; color:var(--light-text); font-size:.92rem; font-weight:750; }
    .a5rv-building-empty p { max-width:34rem; margin:0; color:var(--muted-light); font-size:.78rem; line-height:1.5; }
    /* ตัวกรองชิดซ้ายเสมอทั้ง 2 โหมด — เดิมโหมดตรวจชิดขวา ทำให้ 2 หน้าไม่ตรงกัน */
    .a5rv-building-toolbar { display:flex; align-items:center; justify-content:space-between; gap:.85rem; padding:.35rem 1rem .2rem; background:transparent; }
    .a5rv-building-toolbar .a5rv-filters { flex:1 1 auto; padding:0; }
    .a5rv-calendar-trigger { width:2.1rem; height:2.1rem; flex:none; display:grid; place-items:center; border:0; border-radius:.25rem; background:transparent; color:var(--muted-light); cursor:pointer; }
    .a5rv-calendar-trigger:hover, .a5rv-calendar-trigger:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    .a5rv-calendar-trigger svg { width:1.1rem; height:1.1rem; stroke:currentColor; }

    /* แท็บชั้น: ปุ่มมีกรอบ ชั้นที่เลือกเป็นปุ่มทึบสี moss ให้เห็นชัด (Manager 2026-08-27 — เดิมเส้นใต้จาง มองไม่ค่อยเห็น) */
    .a5rv-floor-nav { display:flex; align-items:center; gap:.4rem; overflow-x:auto; padding:.7rem 1rem .1rem; background:transparent; }
    .a5rv-floor-tab {
      min-width:3rem;
      min-height:2.2rem;
      border:1px solid var(--line-light);
      border-radius: 0.28rem;
      padding:.4rem .95rem;
      background:var(--menu-bg);
      color:var(--muted-light);
      font:inherit;
      font-size:.8rem;
      font-weight:750;
      cursor:pointer;
      white-space:nowrap;
      transition:border-color .16s ease, background-color .16s ease, color .16s ease;
    }
    .a5rv-floor-tab:hover { border-color:var(--moss); color:var(--moss); }
    .a5rv-floor-tab[aria-selected="true"] { border-color:var(--moss); background:var(--moss); color:#fff; }
    .a5rv-floor-panel { display:grid; gap:.7rem; padding:.75rem 1rem 1rem; }
    .a5rv-floor-panel[hidden], .a5rv-layout[hidden], .a5rv-location[hidden], .a5rv-point-row[hidden], .a5rv-floor-tab[hidden] { display:none !important; }

    /* กรอบเดียวคลุมทั้งหัวพื้นที่ + ตารางจุด/ผู้รับผิดชอบ ให้อ่านเป็นกลุ่มเดียวกัน (Manager 2026-08-26) */
    .a5rv-layout { overflow:hidden; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); }
    .a5rv-layout-head { display:grid; grid-template-columns:minmax(0, 1fr) auto; align-items:center; gap:.6rem; padding:0 .5rem; }
    .a5rv-layout-toggle {
      min-width:0;
      min-height:3.6rem;
      display:grid;
      grid-template-columns:3.4rem minmax(0, 1fr) auto;
      align-items:center;
      gap:.7rem;
      border:0;
      border-radius: 0.25rem;
      padding:.4rem;
      background:transparent;
      color:inherit;
      font:inherit;
      text-align:left;
      cursor:pointer;
    }
    .a5rv-layout-toggle:hover { background:var(--hover-soft); }
    .a5rv-layout-image { width:3.4rem; aspect-ratio:16 / 10; overflow:hidden; border:0; border-radius: 0.25rem; background:var(--menu-bg); }
    .a5rv-layout-image img { display:block; width:100%; height:100%; object-fit:cover; }
    .a5rv-layout-image span { display:grid; place-items:center; width:100%; height:100%; color:var(--muted-light); }
    .a5rv-layout-image svg { width:1.25rem; height:1.25rem; stroke:currentColor; }
    .a5rv-layout-name { display:grid; gap:.12rem; min-width:0; }
    .a5rv-layout-name strong { overflow:hidden; color:var(--light-text); font-size:.92rem; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
    .a5rv-layout-name span { color:var(--muted-light); font-size:.73rem; }
    .a5rv-layout-chevron { width:1.15rem; height:1.15rem; color:var(--muted-light); stroke:currentColor; transition:transform .18s ease, color .18s ease; }
    .a5rv-layout-toggle[aria-expanded="true"] .a5rv-layout-chevron { transform:rotate(180deg); color:var(--moss); }
    .a5rv-layout-content[hidden] { display:none !important; }

    /* ลำดับคอลัมน์ (เหมือนกันทั้ง 2 โหมด): จุด · ผู้ประเมิน/ผู้รับผิดชอบ · คะแนน · สถานะ · ส่งล่าสุด · ประวัติ · ดำเนินการ
       Manager 2026-08-27: โหมดตรวจเพิ่มคอลัมน์คะแนนแล้ว กริดจึงใช้ชุดเดียวกันได้ */
    .a5rv-table-head,
    .a5rv-point-row { display:grid; grid-template-columns:minmax(4.5rem, .5fr) minmax(10.5rem, 1.6fr) minmax(4.5rem, .5fr) minmax(6.5rem, .7fr) minmax(8.5rem, .95fr) minmax(4.5rem, .5fr) minmax(7rem, .75fr); align-items:stretch; gap:0; padding:0; }
    /* เส้นแบ่งคอลัมน์แนวตั้ง — padding อยู่ที่ตัวช่อง หัวตารางกับแถวจึงตรงกันเป๊ะ (Manager 2026-08-26) */
    .a5rv-table-head > *,
    .a5rv-point-row > * { display:flex; align-items:center; justify-content:center; min-width:0; padding:.5rem .7rem; border-right:1px solid var(--line-light); text-align:center; }
    .a5rv-table-head > *:first-child,
    .a5rv-point-row > *:first-child { padding-left:1rem; }
    .a5rv-table-head > *:last-child,
    .a5rv-point-row > *:last-child { padding-right:1rem; border-right:0; }
    /* ทุกคอลัมน์จัดกึ่งกลาง รวมหัวคอลัมน์ผู้ประเมิน — ยกเว้น "ข้อมูล" ชื่อคนที่ชิดซ้าย (Manager 2026-08-26) */
    .a5rv-point-row .a5rv-assignees { justify-content:flex-start; text-align:left; }
    .a5rv-table-head { border-top:1px solid var(--line-light); border-bottom:1px solid var(--line-light); background:var(--a5rv-surface-strong); color:var(--muted-light); font-size:.69rem; font-weight:700; letter-spacing:.03em; }
    .a5rv-point-row { min-height:3.1rem; border-bottom:1px solid var(--line-light); }
    .a5rv-point-row:last-child { border-bottom:0; }
    .a5rv-point-main { display:grid; gap:.2rem; min-width:0; }
    .a5rv-point-main strong { overflow:hidden; color:var(--light-text); font-size:.85rem; font-weight:730; text-overflow:ellipsis; white-space:nowrap; }
    .a5rv-point-main small { color:var(--muted-light); font-size:.74rem; }
    .a5rv-assignees { display:flex; align-items:center; gap:.45rem; min-width:0; }
    .a5rv-avatar { width:1.75rem; height:1.75rem; flex:none; display:grid; place-items:center; overflow:hidden; border:0; border-radius:50%; padding:0; background:var(--menu-bg); color:var(--light-text); font:inherit; font-size:.68rem; font-weight:760; }
    button.a5rv-avatar { cursor:zoom-in; }
    button.a5rv-avatar:hover { border-color:var(--moss); }
    .a5rv-avatar img { display:block; width:100%; height:100%; object-fit:cover; }
    .a5rv-person-copy { display:grid; gap:.08rem; min-width:0; }
    .a5rv-person-copy strong { overflow:hidden; color:var(--light-text); font-size:.78rem; font-weight:680; text-overflow:ellipsis; white-space:nowrap; }
    .a5rv-person-copy small { color:var(--muted-light); font-size:.7rem; }
    .a5rv-time { color:var(--light-text); font-size:.78rem; font-variant-numeric:tabular-nums; }
    .a5rv-score-cell { color:var(--light-text); font-size:.82rem; font-weight:780; font-variant-numeric:tabular-nums; }
    .a5rv-score-cell.is-none, .a5rv-point-row .is-none { color:var(--muted-light); font-weight:600; }
    /* นอกตาราง (โมดัลประวัติ) ยังเป็น pill — ไม่มีจุดวงกลมตามที่ Manager สั่ง */
    .a5rv-status {
      width:max-content;
      display:inline-flex;
      align-items:center;
      gap:.42rem;
      border:1px solid color-mix(in srgb, var(--bucket-color) 40%, transparent);
      border-radius:999px;
      padding:.3rem .58rem;
      background:color-mix(in srgb, var(--bucket-color) 12%, transparent);
      color:var(--bucket-color);
      font-size:.72rem;
      font-weight:760;
      white-space:nowrap;
    }
    .a5rv-status::before { content:none; }
    /* ในตาราง: พื้นหลังสีอ่อนเต็มช่อง ตัวอักษรสีเดียวกัน ไม่มีกรอบ (Manager 2026-08-26) */
    .a5rv-point-row .a5rv-status {
      width:auto;
      display:flex;
      justify-content:center;
      border:0;
      border-right:1px solid var(--line-light);
      border-radius:0;
      padding:.5rem .7rem;
      background:color-mix(in srgb, var(--bucket-color) 16%, transparent);
      font-size:.76rem;
      font-weight:750;
    }
    .a5rv-status.is-ready { --bucket-color:var(--a5rv-ready); }
    .a5rv-status.is-review { --bucket-color:var(--a5rv-ready); }
    .a5rv-status.is-waiting { --bucket-color:var(--a5rv-wait); }
    .a5rv-status.is-todo { --bucket-color:var(--a5rv-wait); }
    .a5rv-status.is-rework { --bucket-color:var(--a5rv-rework); }
    .a5rv-status.is-done { --bucket-color:var(--a5rv-done); }
    .a5rv-status.is-pass { --bucket-color:var(--a5rv-pass); }
    .a5rv-status.is-fail { --bucket-color:var(--a5rv-fail); }
    .a5rv-status.is-pending { --bucket-color:var(--a5rv-pending); }
    .a5rv-status.is-no_data { --bucket-color:var(--a5rv-no-data); }
    .a5rv-status.has-icon::before { display:none; }
    .a5rv-status.has-icon svg { width:.9rem; height:.9rem; flex:none; fill:none; stroke:currentColor; }
    /* ประวัติ/ดำเนินการ เป็นปุ่มมีกรอบ (Manager 2026-08-26) */
    .a5rv-cell-history, .a5rv-cell-action { min-width:0; font-size:.76rem; }
    .a5rv-link {
      min-height:2.1rem;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:.28rem;
      border:1px solid var(--line-light);
      border-radius: 0.25rem;
      padding:.35rem .75rem;
      background:var(--menu-bg);
      color:var(--light-text);
      font:inherit;
      font-size:.73rem;
      font-weight:700;
      text-decoration:none;
      cursor:pointer;
      white-space:nowrap;
      transition:border-color .16s ease, background-color .16s ease, color .16s ease;
    }
    .a5rv-link:hover { border-color:var(--line-strong); background:var(--hover-soft); }
    /* ปุ่มในคอลัมน์ "ดำเนินการ" (ดูพื้นที่ / ตรวจประเมิน) เป็นน้ำเงินตัวอักษรขาวเสมอ (Manager 2026-08-27) */
    .a5rv-cell-action .a5rv-link,
    .a5rv-link.is-primary { border-color:var(--moss); background:var(--moss); color:#fff; }
    .a5rv-cell-action .a5rv-link:hover,
    .a5rv-link.is-primary:hover { border-color:var(--moss); background:color-mix(in srgb, var(--moss) 86%, #000); color:#fff; }

    .a5rv-empty { padding:3rem 1rem; border:0; text-align:center; }
    .a5rv-empty h2 { margin:0 0 .35rem; color:var(--light-text); font-size:.95rem; font-weight:750; }
    .a5rv-empty p { margin:0; color:var(--muted-light); font-size:.82rem; }

    /* กว้าง 54rem — 44rem เดิมทำให้ ครั้งที่/วันที่/สถานะ ในแถวสรุปเบียดชนกัน (Manager 2026-08-27) */
    .a5rv-history-dialog { position:fixed; inset:0; width:min(calc(100% - 2rem), 54rem); max-height:min(90vh, 54rem); overflow:hidden; margin:auto; border:1px solid var(--line-light); border-radius: 0.36rem; padding:0; background:var(--panel-soft); color:var(--light-text); box-shadow:0 1.5rem 4rem rgb(0 0 0 / 36%); }
    .a5rv-history-dialog::backdrop { background:rgb(0 0 0 / 56%); }
    .a5rv-history-head { position:sticky; top:0; z-index:1; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.25rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); }
    .a5rv-history-title { display:grid; gap:.4rem; min-width:0; }
    .a5rv-history-title h2 { margin:0; font-size:1.08rem; font-weight:760; }
    .a5rv-history-title p { overflow:hidden; margin:0; color:var(--muted-light); font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
    .a5rv-history-close { width:2.75rem; height:2.75rem; flex:none; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; }
    .a5rv-history-close:hover { border-color:var(--line-strong); }
    .a5rv-history-close svg { width:1.1rem; height:1.1rem; stroke:currentColor; }
    /* scroll-padding เผื่อขอบไว้ ตอน scrollIntoView แถวที่กางจะไม่ชิดขอบจนอ่านไม่ออก */
    .a5rv-history-body { max-height:calc(min(90vh, 54rem) - 5rem); overflow:auto; scroll-padding:1rem; display:grid; gap:.75rem; padding:1rem 1.35rem 1.35rem; }
    .a5rv-history-list { display:grid; gap:.4rem; }
    .a5rv-history-row { border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); }
    /* แถวประวัติ: ครั้งที่ · วันที่ · คะแนน · สถานะ · ลูกศร — คอลัมน์ตรงกันทุกแถว (Manager 2026-08-26) */
    .a5rv-history-row > summary {
      min-height:2.9rem;
      display:grid;
      grid-template-columns:6.5rem minmax(0, 1fr) 4.2rem 7rem 1.2rem;
      align-items:center;
      gap:1rem;
      padding:.5rem 1rem;
      color:var(--light-text);
      cursor:pointer;
      list-style:none;
    }
    .a5rv-history-row > summary::-webkit-details-marker { display:none; }
    /* ทุกช่องห้ามล้นไปทับช่องข้าง ๆ — ตัดด้วย … แทน */
    .a5rv-history-row > summary > * { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5rv-history-row-title { font-size:.8rem; font-weight:760; }
    .a5rv-history-row-date { color:var(--muted-light); font-size:.7rem; font-variant-numeric:tabular-nums; }
    .a5rv-history-row-score { color:var(--light-text); font-size:.76rem; font-weight:780; text-align:right; font-variant-numeric:tabular-nums; }
    /* สถานะในแถวประวัติ: ข้อความล้วน ไม่มีกรอบ ไม่มีไอคอน */
    .a5rv-history-row-state { font-size:.75rem; font-weight:780; }
    .a5rv-history-row-state.is-pass { color:var(--a5rv-pass); }
    .a5rv-history-row-state.is-fail { color:var(--a5rv-fail); }
    .a5rv-history-row-state.is-pending { color:var(--a5rv-pending); }
    .a5rv-history-row-caret { color:var(--moss); font-size:1rem; font-weight:900; text-align:center; transition:transform .18s ease; }
    .a5rv-history-row[open] .a5rv-history-row-caret { transform:rotate(90deg); }
    .a5rv-history-row-detail { padding:0 .8rem .8rem; }
    /* เนื้อหาที่กางออกมาต้องเว้นจากขอบกรอบเท่ากับแถวสรุป (1rem) — เดิม padding ซ้าย-ขวาเป็น 0
       เพราะกฎนี้ specificity สูงกว่า `.a5rv-history-row-detail` เลยทับค่าไป (Manager 2026-08-27) */
    .a5rv-history-row-detail.a5rv-attempt { border:0; border-top:1px solid var(--line-light); padding:.9rem 1rem 1rem; background:transparent; }
    .a5rv-history-row-detail .a5rv-attempt-head { display:none; }
    .a5rv-calendar-dialog { position:fixed; inset:0; width:min(calc(100% - 2rem), 38rem); max-height:min(86vh, 48rem); overflow:hidden; margin:auto; border:1px solid var(--line-light); border-radius:.36rem; padding:0; background:var(--panel-soft); color:var(--light-text); box-shadow:0 1rem 3rem rgb(0 0 0 / 32%); }
    .a5rv-calendar-dialog::backdrop { background:rgb(0 0 0 / 56%); }
    .a5rv-calendar-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.1rem; border-bottom:1px solid var(--line-light); }
    .a5rv-calendar-head h2 { margin:0; font-size:1rem; font-weight:780; }
    .a5rv-calendar-body { max-height:calc(min(86vh, 48rem) - 4.2rem); overflow:auto; display:grid; gap:.7rem; padding:1rem 1.1rem 1.2rem; }
    .a5rv-calendar-overall { display:grid; grid-template-columns:minmax(0, 1fr) auto; gap:.35rem .8rem; padding:.7rem .8rem; border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); }
    .a5rv-calendar-overall-copy { display:grid; gap:.15rem; }
    .a5rv-calendar-overall span, .a5rv-calendar-month-meta, .a5rv-calendar-date-meta { color:var(--muted-light); font-size:.7rem; }
    .a5rv-calendar-overall strong { font-size:1rem; font-weight:800; font-variant-numeric:tabular-nums; }
    .a5rv-calendar-months { display:grid; gap:.4rem; }
    /* ปฏิทินคะแนน: แถวรอบเป็น 4 คอลัมน์ตรงกัน — รอบ · สถานะ · วันที่ตรวจ · คะแนน (Manager 2026-08-26) */
    .a5rv-calendar-rounds { display:grid; gap:0; padding:.1rem 0 .5rem 1.2rem; }
    .a5rv-calendar-round {
      display:grid;
      grid-template-columns:4.2rem 5rem minmax(0, 1fr) 3.6rem;
      align-items:center;
      gap:.55rem;
      padding:.4rem .1rem;
      border-top:1px solid var(--line-light);
      color:var(--muted-light);
      font-size:.71rem;
    }
    .a5rv-calendar-round:first-child { border-top:0; }
    .a5rv-calendar-round strong { color:var(--light-text); font-size:.73rem; font-weight:700; }
    .a5rv-calendar-round-date { font-variant-numeric:tabular-nums; }
    .a5rv-calendar-round-score { justify-self:end; font-variant-numeric:tabular-nums; }
    .a5rv-calendar-round.is-open .a5rv-calendar-round-state { color:var(--moss); font-weight:750; }
    .a5rv-calendar-month { border-bottom:1px solid var(--line-light); }
    .a5rv-calendar-month > summary { display:flex; align-items:center; gap:.55rem; padding:.65rem .15rem; cursor:pointer; list-style:none; }
    .a5rv-calendar-month > summary::-webkit-details-marker { display:none; }
    .a5rv-calendar-month > summary::before { content:'›'; color:var(--moss); font-size:1rem; font-weight:900; transition:transform .18s ease; }
    .a5rv-calendar-month[open] > summary::before { transform:rotate(90deg); }
    .a5rv-calendar-month > summary > span:first-of-type { font-size:.82rem; font-weight:760; }
    .a5rv-calendar-month-meta { margin-left:auto; }
    .a5rv-calendar-dates { display:grid; gap:.28rem; padding:0 0 .65rem 1.2rem; }
    .a5rv-calendar-date { display:grid; grid-template-columns:minmax(0, 1fr) auto; align-items:center; gap:.6rem; padding:.42rem .55rem; border:1px solid var(--line-light); border-radius:.2rem; background:var(--menu-bg); }
    .a5rv-calendar-date-label { font-size:.75rem; font-weight:680; }
    .a5rv-calendar-date-meta { font-variant-numeric:tabular-nums; }
    .a5rv-calendar-empty { padding:.8rem; border:1px dashed var(--line-light); color:var(--muted-light); font-size:.76rem; text-align:center; }
    /* แท็บ .a5rv-history-tab* ถูกถอดออก 2026-08-27 — โมดัลประวัติใช้แถวกดกางทั้ง 2 โหมดแล้ว */
    .a5rv-attempt { display:grid; gap:.8rem; border:1px solid var(--line-light); border-radius: 0.28rem; padding:1rem; background:var(--menu-bg); }
    .a5rv-attempt-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .a5rv-attempt-head strong { font-size:.9rem; }
    .a5rv-attempt-meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.65rem 1rem; }
    .a5rv-meta-item { display:grid; gap:.15rem; }
    .a5rv-meta-item span { color:var(--muted-light); font-size:.7rem; }
    .a5rv-meta-item strong { color:var(--light-text); font-size:.8rem; font-weight:680; }
    /* ข้อมูลที่ส่งมา = การ์ดเดียว รายการข้างในเรียงเลข 1. 2. 3. (Manager 2026-07-31) */
    .a5rv-submission-list { display:grid; gap:.45rem; }
    .a5rv-submission-card { display:grid; gap:.8rem; margin:0; padding:.8rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; list-style:none; counter-reset:a5rv-item; }
    .a5rv-submission-item { display:grid; gap:.22rem; counter-increment:a5rv-item; }
    .a5rv-submission-item + .a5rv-submission-item { padding-top:.8rem; border-top:1px solid var(--line-light); }
    .a5rv-submission-item strong { font-size:.8rem; font-weight:700; }
    .a5rv-submission-item strong::before { content:counter(a5rv-item) ". "; color:var(--moss); font-weight:800; }
    .a5rv-submission-item p { margin:0; color:var(--muted-light); font-size:.78rem; line-height:1.5; white-space:pre-line; }
    /* การ์ดหมายเหตุใน modal — มีแถบสีซ้าย + หัวข้อสีตามผลตรวจ ให้เห็นชัดว่าผ่านหรือไม่ผ่าน (Manager 2026-07-31) */
    .a5rv-decision-note { margin:0; padding:.7rem .8rem; border:1px solid var(--line-light); border-left:3px solid var(--line-strong); border-radius: 0.25rem; color:var(--light-text); font-size:.8rem; line-height:1.55; white-space:pre-line; }
    .a5rv-decision-note b { display:block; margin-bottom:.15rem; font-size:.72rem; font-weight:800; letter-spacing:.01em; }
    .a5rv-decision-note.is-passed { border-color:color-mix(in srgb, var(--a5rv-pass) 45%, transparent); border-left-color:var(--a5rv-pass); background:color-mix(in srgb, var(--a5rv-pass) 12%, transparent); }
    .a5rv-decision-note.is-passed b { color:var(--a5rv-pass); }
    .a5rv-decision-note.is-failed { border-color:color-mix(in srgb, var(--a5rv-fail) 48%, transparent); border-left-color:var(--a5rv-fail); background:color-mix(in srgb, var(--a5rv-fail) 13%, transparent); }
    .a5rv-decision-note.is-failed b { color:var(--a5rv-fail); }

    .a5rv-filter:focus-visible,
    .a5rv-month-field select:focus-visible,
    button.a5rv-avatar:focus-visible,
    .a5rv-floor-tab:focus-visible,
    .a5rv-layout-toggle:focus-visible,
    .a5rv-link:focus-visible,
    .a5rv-history-row > summary:focus-visible,
    .a5rv-history-close:focus-visible {
      outline:2px solid var(--moss);
      outline-offset:2px;
    }

    /* จอกลาง: คงลำดับคอลัมน์ไว้ แล้วให้เลื่อนแนวนอนเฉพาะในกรอบตาราง (หน้าไม่เลื่อนตาม) */
    @media (max-width: 72rem) {
      .a5rv-layout-content { overflow-x:auto; }
      .a5rv-table-head,
      .a5rv-point-row { min-width:50rem; }
    }
    /* มือถือ/แท็บเล็ตเล็ก: แต่ละจุดเป็นบล็อก ไม่ต้องเลื่อนแนวนอน */
    @media (max-width: 52rem) {
      .a5rv-building-toolbar { align-items:stretch; flex-direction:column; }
      .a5rv-building-toolbar .a5rv-filters { width:100%; }
      .a5rv-calendar-trigger { width:100%; }
      .a5rv-layout-content { overflow-x:visible; }
      .a5rv-table-head { display:none; }
      .a5rv-point-row {
        min-width:0;
        align-items:center;
        grid-template-columns:minmax(0, 1fr) auto;
        gap:.35rem .7rem;
        padding:.85rem 1rem;
      }
      /* บนมือถือไม่ใช่ตารางแล้ว จึงไม่มีเส้นแบ่งคอลัมน์ */
      .a5rv-point-row > * { padding:0; border-right:0; }
      .a5rv-point-row > *:first-child { padding-left:0; }
      .a5rv-point-row > *:last-child { padding-right:0; }
      .a5rv-point-main { grid-column:1; }
      .a5rv-point-row .a5rv-status { grid-column:2; justify-self:end; width:max-content; padding:.22rem .55rem; border-right:0; border-radius:.25rem; }
      .a5rv-assignees { grid-column:1 / -1; }
      .a5rv-score-cell { grid-column:1; }
      .a5rv-time { grid-column:2; justify-self:end; text-align:right; }
      .a5rv-cell-history { grid-column:1; }
      .a5rv-cell-action { grid-column:2; justify-self:end; }
    }
    @media (max-width: 36rem) {
      .a5rv-month-form { display:grid; grid-template-columns:1fr; }
      .a5rv-month-field { min-width:0; }
      .a5rv-layout-head { grid-template-columns:1fr; }
      .a5rv-layout-toggle { grid-template-columns:3.8rem minmax(0, 1fr) auto; }
      .a5rv-layout-image { width:3.8rem; }
      .a5rv-attempt-meta { grid-template-columns:1fr; }
      .a5rv-history-dialog { width:calc(100% - 1rem); max-height:calc(100dvh - 1rem); margin:auto; border-radius: 0.36rem; }
      .a5rv-history-body { max-height:calc(100dvh - 6rem); padding-inline:1rem; }
    }
    @media (prefers-reduced-motion: reduce) {
      .a5rv-filter, .a5rv-link, .a5rv-layout-chevron { transition:none; }
    }

    .flash.success { padding:.6rem .9rem; border:1px solid var(--moss); border-radius: 0.25rem; color:var(--moss); background:rgb(91 141 239 / 12%); font-size:.85rem; }
    .flash.error { padding:.6rem .9rem; border:1px solid #d98a80; border-radius: 0.25rem; color:#d98a80; background:rgb(217 138 128 / 12%); font-size:.85rem; }
@endsection

@section('content')
  @php
    $subZoneBoards = $isReviewMode ? ($reviewSubZoneBoards ?? []) : ($workSubZoneBoards ?? []);
    $activeZoneSummary = collect($isReviewMode ? ($zoneSummary['review'] ?? []) : ($zoneSummary['work'] ?? []))->keyBy('zone_id');
    $statusDefinitions = collect([
        ['status_class' => 'fail', 'status_key' => 'failed', 'label' => 'ปฏิเสธ'],
        ['status_class' => 'pending', 'status_key' => 'submitted', 'label' => 'รอดำเนินการ'],
        ['status_class' => 'pass', 'status_key' => 'passed', 'label' => 'ผ่าน'],
        ['status_class' => 'none', 'status_key' => 'no_data', 'label' => 'ยังไม่มีข้อมูล'],
    ]);
    $rows = collect($myTaskRows ?? []);
    // นับสถานะจากรายการจริงที่แสดง — ตัวเลขบนหัวแผงกับตารางจึงตรงกันเสมอ
    $tally = $statusDefinitions
        ->map(fn ($definition) => $definition + ['count' => $rows->where('status_class', $definition['status_class'])->count()])
        ->filter(fn ($definition) => $definition['count'] > 0)
        ->values();

    $workReportPoints = collect($workSummaryByLayout ?? [])->flatMap(fn ($summary) => $summary['points'] ?? [])->values();
    $workReportStatuses = $statusDefinitions->map(function ($definition) use ($workReportPoints) {
        $definition['count'] = $workReportPoints->where('status_class', $definition['status_class'])->count();

        return $definition;
    })->filter(fn ($status) => $status['count'] > 0)->values()->all();
    $workspaceDetails = collect($isReviewMode ? ($reviewPointDetails ?? []) : ($workPointDetails ?? []));
    $roundMeta = $workspaceRound ?? null;
    $selectedRoundId = (int) ($roundMeta['id'] ?? 0);
    $isWorkspaceOpenRound = (bool) ($roundMeta['is_open'] ?? false);

    $responsibleMapZones = collect($planZones ?? [])->map(function ($zone) use ($subZoneBoards, $activeZoneSummary, $isReviewMode, $statusDefinitions, $workspaceDetails, $selectedRoundId, $isWorkspaceOpenRound) {
        $summary = $activeZoneSummary->get($zone->id, []);
        $zoneBoards = collect($subZoneBoards)->where('zone_id', (int) $zone->id)->values();

        $zoneMaps = collect($zone->zoneMaps ?? [])->map(function ($zoneMap) use ($zoneBoards, $isReviewMode, $statusDefinitions, $workspaceDetails, $selectedRoundId, $isWorkspaceOpenRound) {
            $board = $zoneBoards->firstWhere('id', (int) $zoneMap->id) ?? [];
            $boardAreas = collect($board['areas'] ?? [])->keyBy(fn ($area) => (int) ($area['id'] ?? 0));

            return [
                'id' => (int) $zoneMap->id,
                'name' => $zoneMap->name,
                'image_url' => $zoneMap->image_path ? asset('storage/'.$zoneMap->image_path) : null,
                'areas' => collect($zoneMap->areas ?? [])->map(function ($areaModel) use ($boardAreas, $isReviewMode, $statusDefinitions, $workspaceDetails, $selectedRoundId, $isWorkspaceOpenRound) {
                    $area = $boardAreas->get((int) $areaModel->id, [
                        'id' => (int) $areaModel->id,
                        'name' => $areaModel->name,
                        'color' => $areaModel->color,
                        'shape_points' => $areaModel->shape_points ?? [],
                        'layouts' => [],
                    ]);
                    $areaPoints = collect($area['layouts'] ?? [])->flatMap(fn ($layout) => $layout['points'] ?? [])->values();
                    $areaStatuses = $statusDefinitions->map(function ($definition) use ($areaPoints) {
                        $definition['count'] = $areaPoints->where('status_class', $definition['status_class'])->count();

                        return $definition;
                    })->filter(fn ($status) => $status['count'] > 0)->values()->all();
                    // เรียงชั้นแบบ natural ตามเลขในชื่อ (F1 → F2 → F3 → F10) ไม่ใช่เรียงตัวอักษร (Manager 2026-07-31)
                    $floors = collect($area['layouts'] ?? [])->groupBy(fn ($layout) => $layout['floor_name'] ?: '-')
                        ->sortBy(function ($floorLayouts, $floorName) {
                            preg_match('/(\d+)/', (string) $floorName, $matches);

                            return sprintf('%06d|%s', (int) ($matches[1] ?? 999999), $floorName);
                        }, SORT_NATURAL)
                        ->map(function ($floorLayouts, $floorName) use ($area, $isReviewMode, $workspaceDetails, $selectedRoundId, $isWorkspaceOpenRound) {
                        return [
                            'name' => $floorName,
                            'layouts' => $floorLayouts->map(function ($layout) use ($area, $isReviewMode, $workspaceDetails, $selectedRoundId, $isWorkspaceOpenRound) {
                                $historyUrl = $isReviewMode
                                    ? route('area5s.evaluations.area', ['area' => $area['id'], 'round' => $selectedRoundId])
                                    : route('area5s.responsible.area', ['area' => $area['id'], 'round' => $selectedRoundId]);
                                $pointRows = collect($layout['points'] ?? [])->map(function ($point) use ($layout, $historyUrl, $isReviewMode, $workspaceDetails, $isWorkspaceOpenRound) {
                                    $pointStatusKey = match ($point['status_class'] ?? 'none') {
                                        'pass' => 'passed',
                                        'fail' => 'failed',
                                        'pending' => 'submitted',
                                        default => 'no_data',
                                    };
                                    $pointId = (int) ($point['id'] ?? 0);
                                    $detail = $workspaceDetails->get($pointId, []);
                                    $workflowBucket = $isReviewMode
                                        ? ($detail['bucket'] ?? 'waiting')
                                        : match ($detail['status'] ?? $point['status'] ?? 'not_started') {
                                            'submitted', 'resubmitted' => 'review',
                                            'failed' => 'rework',
                                            'passed' => 'done',
                                            default => 'todo',
                                        };
                                    $bucket = match ($detail['status'] ?? $point['status'] ?? 'not_started') {
                                        'passed' => 'pass',
                                        'failed' => 'fail',
                                        'submitted', 'resubmitted' => 'pending',
                                        default => 'no_data',
                                    };

                                    return [
                                        'id' => $pointId,
                                        'code' => $point['code'] ?? '-',
                                        'name' => $point['name'] ?? '-',
                                        'status_class' => $point['status_class'] ?? 'none',
                                        'status_key' => $pointStatusKey,
                                        'status_label' => $point['status_label'] ?? 'ยังไม่มีข้อมูล',
                                        'has_note' => ! empty($point['reject_note']) || ! empty($point['pass_note']),
                                        'url' => $isWorkspaceOpenRound
                                            ? ($pointId
                                                ? ($isReviewMode
                                                    ? route('area5s.evaluations.show', ['layout' => $layout['id'], 'point' => $pointId])
                                                    : route('area5s.responsible.show', ['layout' => $layout['id'], 'point' => $pointId]))
                                                : $layout['url'])
                                            : $historyUrl,
                                        'bucket' => $bucket,
                                        'workflow_bucket' => $workflowBucket,
                                        'submitted_at' => $detail['submitted_at'] ?? null,
                                        'updated_at' => $detail['updated_at'] ?? null,
                                        'submit_count' => (int) ($detail['submit_count'] ?? 0),
                                        'cards_count' => (int) ($detail['cards_count'] ?? 0),
                                        'assignees' => $detail['assignees'] ?? [],
                                        'evaluators' => $detail['evaluators'] ?? [],
                                        'latest_submitter' => $detail['latest_submitter'] ?? null,
                                        'history' => $detail['history'] ?? [],
                                        'score' => $detail['score'] ?? null,
                                        'score_label' => $detail['score_label'] ?? '—',
                                    ];
                                })->values()->all();
                                if ($isReviewMode) {
                                    $statusClass = ((int) ($layout['rejected'] ?? 0)) > 0 ? 'fail' : (((int) ($layout['waiting'] ?? 0)) > 0 ? 'pending' : (((int) ($layout['passed'] ?? 0)) >= (int) ($layout['point_count'] ?? 0) && (int) ($layout['point_count'] ?? 0) > 0 ? 'pass' : 'idle'));
                                    // ใช้คำเรียกสถานะชุดเดียวกับหน้างาน — เดิมโหมดตรวจใช้คนละคำ (Manager 2026-07-31)
                                    $statusLabel = match ($statusClass) {
                                        'fail' => 'ปฏิเสธ',
                                        'pending' => 'รอดำเนินการ',
                                        'pass' => 'ผ่าน',
                                        default => 'ยังไม่มีข้อมูล',
                                    };
                                } else {
                                    $statusClass = $layout['status_class'] ?? 'idle';
                                    $statusLabel = $layout['status_label'] ?? null;
                                }
                                $statusKey = match ($statusClass) {
                                    'fail' => 'failed',
                                    'pending' => 'submitted',
                                    'pass' => 'passed',
                                    'draft' => 'draft',
                                    default => 'no_data',
                                };

                                return [
                                    'id' => $layout['id'],
                                    'name' => $layout['name'],
                                    'image_url' => ! empty($layout['image_path']) ? asset('storage/'.$layout['image_path']) : null,
                                    'url' => $isWorkspaceOpenRound ? $layout['url'] : $historyUrl,
                                    'point_count' => (int) ($layout['point_count'] ?? 0),
                                    'total_point_count' => (int) ($layout['total_point_count'] ?? 0),
                                    'card_count' => (int) ($layout['card_count'] ?? 0),
                                    'points' => $pointRows,
                                    'status_class' => $statusClass,
                                    'status_key' => $statusKey,
                                    'status_label' => $statusLabel,
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all();

                    return [
                        'id' => $area['id'],
                        'name' => $area['name'],
                        'color' => $area['color'] ?: '#5b7343',
                        'shape_points' => $area['shape_points'] ?? [],
                        'floors' => $floors,
                        'layout_count' => collect($area['layouts'] ?? [])->count(),
                        'point_count' => $areaPoints->count(),
                        // จำนวนจุดที่ใช้ปักป้าย: โหมดตรวจนับเฉพาะ "รอดำเนินการ" ส่วนโหมดงานนับจุดของฉันทั้งหมด
                        'mine_point_count' => $isReviewMode
                            ? $areaPoints->where('status_class', 'pending')->count()
                            : $areaPoints->count(),
                        'statuses' => $areaStatuses,
                        'detail_url' => collect($area['layouts'] ?? [])->isNotEmpty()
                            ? ($isReviewMode
                                ? route('area5s.evaluations.area', array_filter([
                                    'area' => $area['id'],
                                    'round' => $isWorkspaceOpenRound ? null : $selectedRoundId,
                                ]))
                                : route('area5s.responsible.area', array_filter([
                                    'area' => $area['id'],
                                    'round' => $isWorkspaceOpenRound ? null : $selectedRoundId,
                                ])))
                            : null,
                    ];
                })->values()->all(),
            ];
        })->values();

        $zoneMinePoints = $zoneMaps
            ->flatMap(fn ($board) => $board['areas'] ?? [])
            ->sum(fn ($area) => (int) ($area['mine_point_count'] ?? 0));

        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'color' => $zone->color ?: '#5b7343',
            'shape_points' => $zone->shape_points ?: [],
            'mine_point_count' => $zoneMinePoints,
            'summary' => ! empty($summary) ? ((int) ($summary['total'] ?? 0)).' พื้นที่' : '',
            'zone_maps' => $zoneMaps->all(),
        ];
    })->values();

    $responsibleMapNav = [
        'id' => $isReviewMode ? 'a5map-review' : 'a5map-my-work',
        'name' => $plan?->name,
        'image_url' => $plan?->image_path ? asset('storage/'.$plan->image_path) : null,
        'zones' => $responsibleMapZones,
        'area_detail_mode' => 'navigate',
        'panel_mode' => $isReviewMode ? 'review-summary' : 'work-summary',
        'report_statuses' => $isReviewMode ? [] : $workReportStatuses,
        'report_title' => 'สถานะงานที่รับผิดชอบ',
        'report_title_key' => 'a5s.myWork.reportStatus',
        'mine_marker' => true,
        'mine_label' => $isReviewMode ? 'ที่ต้องตรวจ' : 'งานของฉัน',
        'mine_label_key' => $isReviewMode ? 'a5s.myWork.mineHereReview' : 'a5s.myWork.mineHere',
    ];

    $workspaceLocations = $responsibleMapZones->flatMap(function ($zone) {
        return collect($zone['zone_maps'] ?? [])->flatMap(function ($zoneMap) use ($zone) {
            return collect($zoneMap['areas'] ?? [])
                ->map(fn ($area) => $area + [
                    'zone_id' => $zone['id'],
                    'zone_name' => $zone['name'],
                    'zone_map_name' => $zoneMap['name'],
                    'image_url' => $zoneMap['image_url'],
                ]);
        });
    })->values();
    $workspacePoints = $workspaceLocations->flatMap(fn ($location) => collect($location['floors'] ?? [])
        ->flatMap(fn ($floor) => collect($floor['layouts'] ?? [])
            ->flatMap(fn ($layout) => $layout['points'] ?? [])))->values();
    // แท็บ "ทั้งหมด" อยู่ซ้ายสุด (Manager 2026-07-31) — bucket=all แปลว่าไม่กรอง
    $workspaceFilterDefinitions = collect([
        ['bucket' => 'all', 'key' => 'a5s.workspace.all', 'label' => 'ทั้งหมด'],
        ['bucket' => 'pass', 'key' => 'a5s.workspace.passed', 'label' => 'ผ่าน'],
        ['bucket' => 'fail', 'key' => 'a5s.workspace.failed', 'label' => 'ไม่ผ่าน'],
        ['bucket' => 'pending', 'key' => 'a5s.workspace.pending', 'label' => 'รอดำเนินการ'],
        ['bucket' => 'no_data', 'key' => 'a5s.workspace.noData', 'label' => 'ยังไม่มีข้อมูล'],
    ])->map(fn ($filter) => $filter + [
        'count' => $filter['bucket'] === 'all'
            ? $workspacePoints->count()
            : $workspacePoints->where('bucket', $filter['bucket'])->count(),
    ]);
    $requestedWorkspaceBuildingId = (int) request('building');
    $selectedWorkspaceBuildingId = (int) ($workspaceLocations
        ->firstWhere('id', $requestedWorkspaceBuildingId)['id']
        ?? $workspaceLocations->first()['id']
        ?? 0);
    // แท็บเริ่มต้น = "ทั้งหมด" · ถ้าอาคารที่เปิดอยู่มีงานสถานะ "รอดำเนินการ" ให้เริ่มที่แท็บนั้นแทน
    // (Manager 2026-08-26 — เดิมล็อกไว้ที่ pending เสมอ ทำให้หน้าว่างเมื่อไม่มีงานสถานะนั้น)
    $selectedWorkspacePoints = collect(
        $workspaceLocations->firstWhere('id', $selectedWorkspaceBuildingId)['floors'] ?? []
    )->flatMap(fn ($floor) => collect($floor['layouts'] ?? [])->flatMap(fn ($layout) => $layout['points'] ?? []));
    $workspaceDefaultFilter = $selectedWorkspacePoints->where('bucket', 'pending')->isNotEmpty()
        ? 'pending'
        : 'all';

    // สีคะแนนตามเกณฑ์: ≥90 ดี · 70–89 เฝ้าระวัง · <70 ต้องปรับปรุง · ไม่มีคะแนน = เทา
    $scoreClass = fn (?float $score) => $score === null ? 'sc-none' : ($score >= 90 ? 'sc-good' : ($score >= 70 ? 'sc-warn' : 'sc-bad'));
  @endphp

    <div class="a5rv-shell" data-review-workspace data-workspace-mode="{{ $isReviewMode ? 'review' : 'work' }}">
      {{-- ตัดหัวหน้า (h1 + คำอธิบาย) ออก — ซ้ำกับชื่อหน้าบน topbar และเมนู sidebar ที่ highlight อยู่แล้ว (Manager 2026-08-26) --}}
      @if ($workspaceLocations->isNotEmpty())
        <section class="a5rv-board">
        {{-- กรอบที่ 1: ตัวเลือกช่วงเวลา + ข้อมูลอาคารที่เลือก (Manager 2026-08-26) --}}
        <div class="a5rv-context">
        <section class="a5rv-period-bar"
                 aria-label="เลือกอาคาร เดือน และวันที่ตรวจ"
                 data-i18n-aria="a5s.workspace.filters">
          <form class="a5rv-month-form" method="GET" action="{{ route('area5s.responsible.index') }}">
            @if ($isReviewMode)
              <input type="hidden" name="mode" value="review">
            @endif
            <label class="a5rv-month-field is-building">
              <span data-i18n="a5s.common.building">อาคาร</span>
              <select name="building" data-review-building-select>
                @foreach ($workspaceLocations as $location)
                  <option value="{{ $location['id'] }}" @selected((int) $location['id'] === $selectedWorkspaceBuildingId)>
                    {{ $location['name'] }}
                  </option>
                @endforeach
              </select>
            </label>
            @if ($roundMeta)
              <label class="a5rv-month-field">
                <span data-i18n="a5s.workspace.month">เดือน</span>
                <select name="month" data-workspace-month-select>
                  @foreach (collect($workspaceMonthOptions ?? []) as $monthOption)
                    <option value="{{ $monthOption['key'] }}" @selected($monthOption['is_selected'])>
                      {{ $monthOption['label'] }}
                    </option>
                  @endforeach
                </select>
              </label>
              <label class="a5rv-month-field">
                <span data-i18n="a5s.workspace.inspectionDate">วันที่ตรวจ</span>
                <select name="round" data-workspace-inspection-select>
                  @foreach (collect($workspaceInspectionOptions ?? []) as $inspectionOption)
                    <option value="{{ $inspectionOption['round_id'] }}" @selected($inspectionOption['is_selected'])>
                      {{ $inspectionOption['date_label'] }}
                    </option>
                  @endforeach
                </select>
              </label>
            @endif
          </form>
        </section>

        <div class="a5rv-building-previews" aria-live="polite">
          @foreach ($workspaceLocations as $locationIndex => $location)
            @php
              $buildingShapePoints = collect($location['shape_points'] ?? [])
                  ->filter(fn ($point) => is_array($point) && isset($point['x'], $point['y']));
              $buildingMinX = (float) ($buildingShapePoints->min('x') ?? 0);
              $buildingMaxX = (float) ($buildingShapePoints->max('x') ?? 100);
              $buildingMinY = (float) ($buildingShapePoints->min('y') ?? 0);
              $buildingMaxY = (float) ($buildingShapePoints->max('y') ?? 100);
              $buildingCropWidth = max(1, $buildingMaxX - $buildingMinX);
              $buildingCropHeight = max(1, $buildingMaxY - $buildingMinY);
              $buildingCropSizeX = min(1000, 10000 / $buildingCropWidth);
              $buildingCropPositionX = $buildingCropWidth < 100 ? ($buildingMinX / (100 - $buildingCropWidth)) * 100 : 50;
              $buildingCropPositionY = $buildingCropHeight < 100 ? ($buildingMinY / (100 - $buildingCropHeight)) * 100 : 50;
              $buildingFloors = collect($location['floors'] ?? []);
              $buildingLayouts = $buildingFloors->flatMap(fn ($floor) => $floor['layouts'] ?? []);
              $buildingPoints = $buildingLayouts->flatMap(fn ($layout) => $layout['points'] ?? []);
            @endphp
            <article class="a5rv-building-preview"
                     data-review-building-preview="{{ $location['id'] }}"
                     @if ((int) $location['id'] !== $selectedWorkspaceBuildingId) hidden @endif>
              @if (! empty($location['image_url']))
                <span class="a5rv-building-card-image"
                      role="img"
                      aria-label="{{ $location['name'] }}"
                      style="background-image:url('{{ $location['image_url'] }}'); background-size:{{ number_format($buildingCropSizeX, 2, '.', '') }}% auto; background-position:{{ number_format($buildingCropPositionX, 2, '.', '') }}% {{ number_format($buildingCropPositionY, 2, '.', '') }}%;"></span>
              @else
                <span class="a5rv-building-card-image is-empty" role="img" aria-label="{{ $location['name'] }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.7" aria-hidden="true">
                    <path d="M3 21h18M5 21V8l7-4v17M12 10h7v11M8 11h1M8 15h1M15 13h1M15 17h1"/>
                  </svg>
                </span>
              @endif
              {{-- ชื่ออาคาร + ข้อมูลย่อบรรทัดเดียว: โซน · ชั้น · พื้นที่ · จุด (Manager 2026-08-26) --}}
              <span class="a5rv-building-card-copy">
                <span class="a5rv-building-card-title">
                  <strong>{{ $location['name'] }}</strong>
                </span>
                <span class="a5rv-building-meta">
                  <span>{{ $location['zone_name'] }}</span>
                  <i aria-hidden="true">·</i>
                  <span><b>{{ number_format($buildingFloors->count()) }}</b> <span data-i18n="a5s.review.floorCount">ชั้น</span></span>
                  <i aria-hidden="true">·</i>
                  <span><b>{{ number_format($buildingLayouts->count()) }}</b> <span data-i18n="a5s.common.area">พื้นที่</span></span>
                  <i aria-hidden="true">·</i>
                  <span><b>{{ number_format($buildingPoints->count()) }}</b> <span data-i18n="a5s.common.point">จุด</span></span>
                </span>
              </span>
            </article>
          @endforeach
        </div>
        </div>

        {{-- กรอบที่ 2: แท็บสถานะ → แท็บชั้น → พื้นที่ + ตารางจุด คลุมไว้ทั้งก้อน --}}
        <div class="a5rv-locations" data-review-locations>
          @foreach ($workspaceLocations as $locationIndex => $location)
            @php
              $locationFloors = collect($location['floors'] ?? []);
              $locationLayouts = $locationFloors->flatMap(fn ($floor) => $floor['layouts'] ?? []);
              $locationPoints = $locationLayouts->flatMap(fn ($layout) => $layout['points'] ?? []);
              $locationDomId = 'a5rv-location-'.$location['id'].'-'.$locationIndex;
            @endphp
            <article class="a5rv-location"
                     id="{{ $locationDomId }}"
                     role="region"
                     aria-label="{{ $location['name'] }}"
                     data-review-location
                     data-review-building-panel="{{ $location['id'] }}"
                     data-review-empty-building="{{ $locationFloors->isEmpty() ? 'true' : 'false' }}"
                     @if ((int) $location['id'] !== $selectedWorkspaceBuildingId) hidden @endif>
              @if ($locationFloors->isEmpty())
                <div class="a5rv-building-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.7" aria-hidden="true">
                    <path d="M3 21h18M5 21V8l7-4v17M12 10h7v11M8 11h1M8 15h1M15 13h1M15 17h1"/>
                  </svg>
                  <h2 data-i18n="a5s.workspace.noBuildingData">ไม่มีข้อมูล</h2>
                  <p data-i18n="a5s.workspace.noBuildingDataHint">อาคารนี้ไม่มีข้อมูลในวันที่ตรวจที่เลือก</p>
                </div>
              @else
              <div class="a5rv-building-toolbar">
                <div class="a5rv-filters"
                     role="group"
                     aria-label="{{ $isReviewMode ? 'สถานะรายการตรวจประเมิน' : 'สถานะงานพื้นที่ของฉัน' }}"
                     data-i18n-aria="{{ $isReviewMode ? 'a5s.review.filterLabel' : 'a5s.work.filterLabel' }}">
                  @foreach ($workspaceFilterDefinitions as $filter)
                    <button type="button"
                            class="a5rv-filter"
                            data-review-filter="{{ $filter['bucket'] }}"
                            aria-pressed="{{ $filter['bucket'] === $workspaceDefaultFilter ? 'true' : 'false' }}">
                      <span data-i18n="{{ $filter['key'] }}">{{ $filter['label'] }}</span>
                      <span class="a5rv-filter-count">{{ number_format($filter['bucket'] === 'all' ? $locationPoints->count() : $locationPoints->where('bucket', $filter['bucket'])->count()) }}</span>
                    </button>
                  @endforeach
                </div>
                @if (! $isReviewMode)
                  <button type="button" class="a5rv-calendar-trigger" data-workspace-calendar-open aria-label="เปิดปฏิทินคะแนน" data-i18n-aria="a5s.calendar.open">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M8 3v3M16 3v3M3 9h18M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01"/></svg>
                  </button>
                @endif
              </div>

              <div class="a5rv-floor-nav" role="tablist" aria-label="ชั้นของอาคาร" data-i18n-aria="a5s.review.floorTabs">
                @foreach ($locationFloors as $floorIndex => $floor)
                  @php $floorDomId = $locationDomId.'-floor-'.$floorIndex; @endphp
                  <button type="button"
                          class="a5rv-floor-tab"
                          id="{{ $floorDomId }}-tab"
                          role="tab"
                          aria-selected="{{ $floorIndex === 0 ? 'true' : 'false' }}"
                          aria-controls="{{ $floorDomId }}"
                          tabindex="{{ $floorIndex === 0 ? '0' : '-1' }}"
                          data-review-floor-tab="{{ $floorDomId }}">
                    {{ $floor['name'] }}
                  </button>
                @endforeach
              </div>

              @foreach ($locationFloors as $floorIndex => $floor)
                @php $floorDomId = $locationDomId.'-floor-'.$floorIndex; @endphp
                <section class="a5rv-floor-panel"
                         id="{{ $floorDomId }}"
                         role="tabpanel"
                         aria-labelledby="{{ $floorDomId }}-tab"
                         data-review-floor-panel="{{ $floorDomId }}"
                         @if ($floorIndex !== 0) hidden @endif>
                  @foreach ($floor['layouts'] as $layoutIndex => $layout)
                    @php $layoutContentId = $floorDomId.'-layout-'.$layoutIndex.'-content'; @endphp
                    <section class="a5rv-layout" data-review-layout>
                      <div class="a5rv-layout-head">
                        {{-- กางไว้ตั้งแต่แรก (Manager 2026-08-26) — ยังกดพับเก็บเองได้ --}}
                        <button type="button"
                                class="a5rv-layout-toggle"
                                aria-expanded="true"
                                aria-controls="{{ $layoutContentId }}"
                                data-review-layout-toggle>
                          <span class="a5rv-layout-image">
                            @if (! empty($layout['image_url']))
                              <img src="{{ $layout['image_url'] }}" alt="" loading="lazy">
                            @else
                              <span>
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.7" aria-hidden="true">
                                  <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m3 16 5-5 4 4 3-3 6 6"/>
                                </svg>
                              </span>
                            @endif
                          </span>
                          <span class="a5rv-layout-name">
                            <strong>{{ $layout['name'] }}</strong>
                            <span>
                              {{ number_format($layout['point_count']) }}
                              <span data-i18n="a5s.common.point">จุด</span>
                            </span>
                          </span>
                          <svg class="a5rv-layout-chevron" viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                          </svg>
                        </button>
                        {{-- ถอดปุ่ม "ดูพื้นที่" ที่หัวพื้นที่ออก (Manager 2026-07-31) — เข้างานผ่านปุ่มรายจุดข้างในแทน --}}
                      </div>

                      <div class="a5rv-layout-content"
                           id="{{ $layoutContentId }}"
                           data-review-layout-content>
                        {{-- หัวคอลัมน์สั้นและตรงกับสิ่งที่อยู่ในช่องจริง (เดิม "จุดงานของฉัน" แต่ในช่องมีแค่ "จุด A") --}}
                        <div class="a5rv-table-head" aria-hidden="true">
                          <span data-i18n="a5s.common.point">จุด</span>
                          <span data-i18n="{{ $isReviewMode ? 'a5s.common.assignees' : 'a5s.common.evaluators' }}">{{ $isReviewMode ? 'ผู้รับผิดชอบ' : 'ผู้ประเมิน' }}</span>
                          <span data-i18n="a5s.score.short">คะแนน</span>
                          <span data-i18n="a5s.common.status">สถานะ</span>
                          <span data-i18n="{{ $isReviewMode ? 'a5s.review.latestSubmit' : 'a5s.work.latestUpdate' }}">{{ $isReviewMode ? 'ส่งล่าสุด' : 'อัปเดตล่าสุด' }}</span>
                          <span data-i18n="a5s.common.history">ประวัติ</span>
                          <span data-i18n="a5s.common.action">ดำเนินการ</span>
                        </div>

                        <div data-review-point-list>
                        @foreach ($layout['points'] as $point)
                          @php
                            $people = collect($isReviewMode ? ($point['assignees'] ?? []) : ($point['evaluators'] ?? []));
                            $primaryPerson = $people->first();
                            $personName = $primaryPerson['name'] ?? ($isReviewMode ? 'ยังไม่มีผู้รับผิดชอบ' : 'ยังไม่มีผู้ประเมิน');
                            $personInitial = mb_substr(trim((string) $personName), 0, 1) ?: '-';
                            $workflowBucket = $point['workflow_bucket'] ?? 'todo';
                            $statusKey = match ($point['bucket']) {
                                'pass' => 'a5s.workspace.passed',
                                'fail' => 'a5s.workspace.failed',
                                'pending' => 'a5s.workspace.pending',
                                default => 'a5s.workspace.noData',
                            };
                            $statusLabel = match ($point['bucket']) {
                                'pass' => 'ผ่าน',
                                'fail' => 'ไม่ผ่าน',
                                'pending' => 'รอดำเนินการ',
                                default => 'ยังไม่มีข้อมูล',
                            };
                            if ($isReviewMode) {
                                $primaryAction = $workflowBucket === 'ready';
                                $actionKey = $primaryAction ? 'a5s.review.openReview' : 'a5s.common.viewLayout';
                                $actionLabel = $primaryAction ? 'เปิดตรวจ' : 'ดูพื้นที่';
                            } else {
                                $primaryAction = in_array($workflowBucket, ['todo', 'rework'], true);
                                $actionKey = $primaryAction ? 'a5s.myWork.openWork' : 'a5s.common.viewLayout';
                                $actionLabel = $primaryAction ? 'เปิดบันทึกงาน' : 'ดูพื้นที่';
                            }
                            // หัวโมดัลประวัติเอาแค่ชื่อพื้นที่ ส่วนจุดต่อท้ายใน JS (Manager 2026-08-26)
                            // เดิมเป็น โซน › อาคาร › ชั้น › พื้นที่ ซึ่งยาวและซ้ำกับที่เลือกไว้ด้านบนอยู่แล้ว
                            $historyTrail = (string) $layout['name'];
                          @endphp
                          <div class="a5rv-point-row"
                               data-review-row
                               data-review-bucket="{{ $point['bucket'] }}"
                               data-point-id="{{ $point['id'] }}">
                            <div class="a5rv-point-main">
                              <strong><span data-i18n="a5s.common.point">จุด</span> {{ $point['code'] }}</strong>
                            </div>
                            <div class="a5rv-assignees">
                              @if (! empty($primaryPerson['avatar']))
                                <button type="button"
                                        class="a5rv-avatar"
                                        data-image-preview
                                        data-image-src="{{ $primaryPerson['avatar'] }}"
                                        data-image-alt="{{ $personName }}"
                                        aria-label="ดูรูปโปรไฟล์ {{ $personName }}"
                                        data-loc-attr="aria-label"
                                        data-loc-th="ดูรูปโปรไฟล์ {{ $personName }}"
                                        data-loc-en="View profile photo of {{ $personName }}"
                                        data-loc-my="{{ $personName }} ၏ ပရိုဖိုင်ပုံကို ကြည့်ရန်">
                                  <img src="{{ $primaryPerson['avatar'] }}" alt="" loading="lazy">
                                </button>
                              @else
                                <span class="a5rv-avatar" aria-hidden="true">{{ $personInitial }}</span>
                              @endif
                              <span class="a5rv-person-copy">
                                <strong @if ($primaryPerson)
                                          data-person-name
                                          data-name-th="{{ $primaryPerson['name_th'] ?? $personName }}"
                                          data-name-en="{{ $primaryPerson['name_en'] ?? $personName }}"
                                          data-name-my="{{ $primaryPerson['name_my'] ?? $personName }}"
                                        @endif>{{ $personName }}</strong>
                                <small>
                                  @if ($people->count() > 1)
                                    +{{ number_format($people->count() - 1) }}
                                    <span data-i18n="a5s.review.morePeople">คน</span>
                                  @else
                                    {{ $primaryPerson['code'] ?? '' }}
                                  @endif
                                </small>
                              </span>
                            </div>
                            {{-- ลำดับคอลัมน์: จุด · ผู้ประเมิน/ผู้รับผิดชอบ · คะแนน · สถานะ · ส่งล่าสุด · ประวัติ · ดำเนินการ
                                 (Manager 2026-08-27: โหมดตรวจมีคอลัมน์คะแนนด้วยแล้ว) --}}
                            <div class="a5rv-score-cell {{ ($point['score'] ?? null) === null ? 'is-none' : '' }}">
                              {{ ($point['score'] ?? null) === null ? '-' : ($point['score_label'] ?? '-') }}
                            </div>
                            <span class="a5rv-status is-{{ $point['bucket'] }}" data-i18n="{{ $statusKey }}">{{ $statusLabel }}</span>
                            <div class="a5rv-time">
                              @if ($point['submitted_at'])
                                {{ $point['submitted_at'] }}
                              @elseif (! $isReviewMode && $point['cards_count'] > 0)
                                {{ $point['updated_at'] ?: '-' }}
                              @else
                                <span class="is-none" data-i18n="{{ $isReviewMode ? 'a5s.review.notSubmitted' : 'a5s.work.notStarted' }}">{{ $isReviewMode ? 'ยังไม่มีการส่ง' : 'ยังไม่เริ่มงาน' }}</span>
                              @endif
                            </div>
                            <div class="a5rv-cell-history">
                              @if (! empty($point['history']))
                                {{-- ข้อความในช่องบอกจำนวนครั้งแทนคำว่า "ประวัติ" ที่ซ้ำกับหัวคอลัมน์ --}}
                                <button type="button"
                                        class="a5rv-link"
                                        data-review-history-open="{{ $point['id'] }}"
                                        data-point-title="จุด {{ $point['code'] }}"
                                        data-point-trail="{{ $historyTrail }}">
                                  {{ number_format(count($point['history'])) }}
                                  <span data-i18n="a5s.review.times">ครั้ง</span>
                                </button>
                              @else
                                <span class="is-none">-</span>
                              @endif
                            </div>
                            <div class="a5rv-cell-action">
                              @if ($isWorkspaceOpenRound)
                                <a class="a5rv-link {{ $primaryAction ? 'is-primary' : '' }} nav-go"
                                   href="{{ $point['url'] }}">
                                  <span data-i18n="{{ $actionKey }}">{{ $actionLabel }}</span>
                                </a>
                              @else
                                <span class="is-none">-</span>
                              @endif
                            </div>
                          </div>
                        @endforeach
                      </div>
                    </div>
                    </section>
                  @endforeach
                </section>
              @endforeach
              @endif
            </article>
          @endforeach
          {{-- ข้อความ "ไม่มีงานในสถานะนี้" อยู่ในกรอบเดียวกับตาราง (Manager 2026-08-26) --}}
          <div class="a5rv-empty" data-review-filter-empty hidden>
            <h2 data-review-empty-title>{{ $isReviewMode ? 'ยังไม่มีรายการพร้อมตรวจ' : 'ไม่มีงานในสถานะนี้' }}</h2>
            <p data-review-empty-body>{{ $isReviewMode ? 'เลือกสถานะอื่นเพื่อดูรายการทั้งหมดในขอบเขตที่คุณรับผิดชอบ' : 'เลือกสถานะอื่นเพื่อดูจุดงานทั้งหมดที่คุณรับผิดชอบ' }}</p>
          </div>
        </div>
        </section>
      @else
        {{-- ไม่มีอาคารในขอบเขตเลย — ยังต้องมีกรอบและข้อความบอก --}}
        <div class="a5rv-locations">
          <div class="a5rv-empty">
            <h2>{{ $isReviewMode ? 'ยังไม่มีรายการพร้อมตรวจ' : 'ไม่มีงานในสถานะนี้' }}</h2>
            <p>{{ $isReviewMode ? 'เลือกสถานะอื่นเพื่อดูรายการทั้งหมดในขอบเขตที่คุณรับผิดชอบ' : 'เลือกสถานะอื่นเพื่อดูจุดงานทั้งหมดที่คุณรับผิดชอบ' }}</p>
          </div>
        </div>
      @endif
    </div>
    <dialog class="a5rv-history-dialog" data-review-history-dialog aria-labelledby="a5rv-history-title">
      <div class="a5rv-history-head">
        <div class="a5rv-history-title">
          <h2 id="a5rv-history-title" data-review-history-title data-i18n="a5s.common.historySubmit">ประวัติการส่ง</h2>
          <p data-review-history-trail>-</p>
        </div>
        <button type="button" class="a5rv-history-close" data-review-history-close aria-label="ปิด" data-i18n-aria="a5s.common.close">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
      </div>
      <div class="a5rv-history-body" data-review-history-body></div>
    </dialog>
    @if (! $isReviewMode)
      <dialog class="a5rv-calendar-dialog" data-workspace-calendar-dialog aria-labelledby="a5rv-calendar-title">
        <div class="a5rv-calendar-head">
          <h2 id="a5rv-calendar-title" data-i18n="a5s.calendar.title">ปฏิทินคะแนน</h2>
          <button type="button" class="a5rv-history-close" data-workspace-calendar-close aria-label="ปิด" data-i18n-aria="a5s.common.close">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
          </button>
        </div>
        <div class="a5rv-calendar-body" data-workspace-calendar-body></div>
      </dialog>
    @endif

  <script>
    'use strict';
    (function () {
      const i18n = window.__portalLang || {};
      const t = (key, fallback) => i18n.text ? i18n.text(key, fallback) : fallback;
      const isReviewMode = @json($isReviewMode);
      const workspace = document.querySelector('[data-review-workspace]');

      if (workspace) {
        const reviewDetails = @json($workspaceDetails->all());
        const filterButtons = [...document.querySelectorAll('[data-review-filter]')];
        const buildingSelect = document.querySelector('[data-review-building-select]');
        const buildingPreviews = [...document.querySelectorAll('[data-review-building-preview]')];
        const buildingPanels = [...document.querySelectorAll('[data-review-building-panel]')];
        const emptyState = document.querySelector('[data-review-filter-empty]');
        const emptyTitle = emptyState?.querySelector('[data-review-empty-title]');
        const emptyBody = emptyState?.querySelector('[data-review-empty-body]');
        const historyDialog = document.querySelector('[data-review-history-dialog]');
        const historyTitle = historyDialog?.querySelector('[data-review-history-title]');
        const historyTrail = historyDialog?.querySelector('[data-review-history-trail]');
        const historyBody = historyDialog?.querySelector('[data-review-history-body]');
        const monthSelect = document.querySelector('[data-workspace-month-select]');
        const inspectionSelect = document.querySelector('[data-workspace-inspection-select]');
        const calendarDialog = document.querySelector('[data-workspace-calendar-dialog]');
        const calendarBody = calendarDialog?.querySelector('[data-workspace-calendar-body]');
        const calendarData = @json($workspaceCalendar ?? ['months' => [], 'overall' => null]);
        let activeFilter = @json($workspaceDefaultFilter);
        let activeBuildingId = buildingSelect?.value
          || buildingPanels[0]?.dataset.reviewBuildingPanel
          || null;
        let activeHistoryPointId = null;
        let historyOpener = null;
        let calendarOpener = null;

        monthSelect?.addEventListener('change', () => {
          if (inspectionSelect) inspectionSelect.disabled = true;
          monthSelect.form?.requestSubmit();
        });
        inspectionSelect?.addEventListener('change', () => inspectionSelect.form?.requestSubmit());
        buildingSelect?.addEventListener('change', () => selectBuilding(buildingSelect.value));

        function activeBuildingPanel() {
          return buildingPanels.find(panel => panel.dataset.reviewBuildingPanel === activeBuildingId)
            || buildingPanels[0]
            || null;
        }

        function updateEmptyState() {
          const panel = activeBuildingPanel();
          const hasVisibleItems = panel?.dataset.reviewHasResults === 'true';
          const isBuildingEmpty = panel?.dataset.reviewEmptyBuilding === 'true';
          if (emptyState) emptyState.hidden = hasVisibleItems || isBuildingEmpty;
          if (hasVisibleItems || isBuildingEmpty) return;

          if (emptyTitle) {
            emptyTitle.textContent = isReviewMode
              ? t('a5s.review.noItemsForStatus', 'ไม่มีรายการในสถานะนี้')
              : t('a5s.work.noItemsForStatus', 'ไม่มีงานในสถานะนี้');
          }
          if (emptyBody) {
            emptyBody.textContent = isReviewMode
              ? t('a5s.review.noItemsHint', 'เลือกสถานะอื่นเพื่อดูรายการทั้งหมดในขอบเขตที่คุณรับผิดชอบ')
              : t('a5s.work.noItemsHint', 'เลือกสถานะอื่นเพื่อดูจุดงานทั้งหมดที่คุณรับผิดชอบ');
          }
        }

        function selectBuilding(buildingId) {
          const selectedPanel = buildingPanels.find(panel => panel.dataset.reviewBuildingPanel === String(buildingId))
            || buildingPanels[0]
            || null;
          activeBuildingId = selectedPanel?.dataset.reviewBuildingPanel || null;
          if (buildingSelect && activeBuildingId) buildingSelect.value = activeBuildingId;
          buildingPreviews.forEach(preview => {
            preview.hidden = preview.dataset.reviewBuildingPreview !== activeBuildingId;
          });
          buildingPanels.forEach(panel => {
            panel.hidden = panel.dataset.reviewBuildingPanel !== activeBuildingId;
          });

          const panel = activeBuildingPanel();
          if (panel) updateLocation(panel);
          updateEmptyState();
        }

        function selectFloor(location, floorId, moveFocus) {
          const tabs = [...location.querySelectorAll('[data-review-floor-tab]')].filter(tab => !tab.hidden);
          const panels = [...location.querySelectorAll('[data-review-floor-panel]')];
          const selectedTab = tabs.find(tab => tab.dataset.reviewFloorTab === floorId) || tabs[0];

          tabs.forEach(tab => {
            const selected = tab === selectedTab;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
          });
          panels.forEach(panel => {
            panel.hidden = !selectedTab || panel.dataset.reviewFloorPanel !== selectedTab.dataset.reviewFloorTab;
          });
          if (moveFocus) selectedTab?.focus();
        }

        function updateLocation(location) {
          location.querySelectorAll('[data-review-layout]').forEach(layout => {
            layout.hidden = !layout.querySelector('[data-review-row]:not([hidden])');
          });

          const floorPanels = [...location.querySelectorAll('[data-review-floor-panel]')];
          floorPanels.forEach(panel => {
            const hasVisibleLayout = !!panel.querySelector('[data-review-layout]:not([hidden])');
            const tab = location.querySelector(`[data-review-floor-tab="${panel.dataset.reviewFloorPanel}"]`);
            if (tab) tab.hidden = !hasVisibleLayout;
          });

          const visibleTabs = [...location.querySelectorAll('[data-review-floor-tab]')].filter(tab => !tab.hidden);
          const selectedVisible = visibleTabs.find(tab => tab.getAttribute('aria-selected') === 'true');
          if (visibleTabs.length) {
            selectFloor(location, (selectedVisible || visibleTabs[0]).dataset.reviewFloorTab, false);
            location.dataset.reviewHasResults = 'true';
          } else {
            floorPanels.forEach(panel => { panel.hidden = true; });
            location.dataset.reviewHasResults = 'false';
          }
        }

        function applyFilter(bucket) {
          activeFilter = bucket;
          filterButtons.forEach(button => {
            button.setAttribute('aria-pressed', button.dataset.reviewFilter === bucket ? 'true' : 'false');
          });
          document.querySelectorAll('[data-review-row]').forEach(row => {
            // bucket = 'all' → แสดงทุกแถว (Manager 2026-07-31)
            row.hidden = bucket !== 'all' && row.dataset.reviewBucket !== bucket;
          });
          document.querySelectorAll('[data-review-location]').forEach(updateLocation);
          selectBuilding(activeBuildingId);
        }

        filterButtons.forEach(button => {
          button.addEventListener('click', () => applyFilter(button.dataset.reviewFilter));
        });

        document.querySelectorAll('[data-review-layout-toggle]').forEach(toggle => {
          toggle.addEventListener('click', () => {
            const content = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!content) return;
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            content.hidden = expanded;
          });
        });

        document.querySelectorAll('[data-review-location]').forEach(location => {
          const tabs = [...location.querySelectorAll('[data-review-floor-tab]')];
          tabs.forEach(tab => {
            tab.addEventListener('click', () => selectFloor(location, tab.dataset.reviewFloorTab, false));
            tab.addEventListener('keydown', event => {
              if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
              event.preventDefault();
              const visibleTabs = tabs.filter(item => !item.hidden);
              const currentIndex = visibleTabs.indexOf(tab);
              let nextIndex = currentIndex;
              if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + visibleTabs.length) % visibleTabs.length;
              if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % visibleTabs.length;
              if (event.key === 'Home') nextIndex = 0;
              if (event.key === 'End') nextIndex = visibleTabs.length - 1;
              selectFloor(location, visibleTabs[nextIndex]?.dataset.reviewFloorTab, true);
            });
          });
        });

        function element(tag, className, text) {
          const node = document.createElement(tag);
          if (className) node.className = className;
          if (text !== undefined && text !== null) node.textContent = text;
          return node;
        }

        function formatScore(score) {
          if (score === null || score === undefined || score === '') return '—';
          const numeric = Number(score);
          if (!Number.isFinite(numeric)) return '—';
          return `${numeric.toLocaleString(undefined, { maximumFractionDigits: 2 })}%`;
        }

        function renderCalendar() {
          if (!calendarBody) return;
          calendarBody.replaceChildren();
          const months = Array.isArray(calendarData?.months) ? calendarData.months : [];
          if (!months.length) {
            calendarBody.append(element('div', 'a5rv-calendar-empty', t('a5s.calendar.empty', 'ยังไม่มีวันที่ประเมิน')));
            return;
          }

          const monthList = element('div', 'a5rv-calendar-months');
          months.forEach((month, index) => {
            const monthDetails = element('details', 'a5rv-calendar-month');
            monthDetails.open = index === 0;
            const monthSummary = element('summary');
            monthSummary.append(
              element('span', '', month.label || '-'),
              element('span', 'a5rv-calendar-month-meta', `${t('a5s.score.overall', 'คะแนนรวม')} ${formatScore(month.score)}`)
            );
            monthDetails.append(monthSummary);

            const rounds = Array.isArray(month.rounds) ? month.rounds : [];
            if (rounds.length) {
              // 4 คอลัมน์ตรงกันทุกแถว: รอบ · สถานะ · วันที่ตรวจ · คะแนน (Manager 2026-08-26)
              const roundList = element('div', 'a5rv-calendar-rounds');
              rounds.forEach(round => {
                const roundRow = element('div', `a5rv-calendar-round${round.is_open ? ' is-open' : ''}`);
                roundRow.append(
                  element('strong', '', `${t('a5s.calendar.round', 'รอบ')} ${round.seq || 1}`),
                  element('span', 'a5rv-calendar-round-state', round.is_open
                    ? t('a5s.calendar.openRound', 'กำลังเปิด')
                    : t('a5s.calendar.closedRound', 'ปิดแล้ว')),
                  element('span', 'a5rv-calendar-round-date', round.date_label || '-'),
                  element('strong', 'a5rv-calendar-round-score', formatScore(round.score))
                );
                roundList.append(roundRow);
              });
              monthDetails.append(roundList);
            } else {
              monthDetails.append(element('div', 'a5rv-calendar-empty', t('a5s.calendar.emptyMonth', 'ยังไม่มีวันที่ประเมินในเดือนนี้')));
            }
            monthList.append(monthDetails);
          });
          calendarBody.append(monthList);
        }

        function openCalendar(trigger) {
          if (!calendarDialog) return;
          calendarOpener = trigger;
          renderCalendar();
          calendarDialog.showModal();
        }

        function closeCalendar() {
          if (calendarDialog?.open) calendarDialog.close();
        }

        document.querySelectorAll('[data-workspace-calendar-open]').forEach(trigger => {
          trigger.addEventListener('click', () => openCalendar(trigger));
        });
        calendarDialog?.querySelector('[data-workspace-calendar-close]')?.addEventListener('click', closeCalendar);
        calendarDialog?.addEventListener('click', event => {
          if (event.target === calendarDialog) closeCalendar();
        });
        calendarDialog?.addEventListener('close', () => calendarOpener?.focus());

        function personName(person) {
          if (!person) return t('a5s.common.noData', 'ไม่มีข้อมูล');
          return i18n.name ? i18n.name(person, person.name || person.code || '-') : (person.name || person.code || '-');
        }

        function metaItem(label, value) {
          const item = element('div', 'a5rv-meta-item');
          item.append(element('span', '', label), element('strong', '', value || '-'));
          return item;
        }

        function stateLabel(state) {
          if (state === 'passed') return t('a5s.status.passed', 'ผ่าน');
          if (state === 'failed') return t('a5s.status.failed', 'ปฏิเสธ');
          return t('a5s.review.pendingDecision', 'รอตรวจประเมิน');
        }

        function stateClass(state) {
          if (state === 'passed') return 'pass';
          if (state === 'failed') return 'fail';
          return 'pending';
        }

        function svgElement(tag, attributes = {}) {
          const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
          Object.entries(attributes).forEach(([name, value]) => node.setAttribute(name, value));
          return node;
        }

        function statusIcon(state) {
          const icon = svgElement('svg', {
            viewBox: '0 0 24 24',
            'stroke-width': '2',
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'aria-hidden': 'true',
          });

          if (state === 'passed') {
            icon.append(svgElement('path', { d: 'm5 12 4 4L19 6' }));
          } else if (state === 'failed') {
            icon.append(
              svgElement('path', { d: 'm7 7 10 10' }),
              svgElement('path', { d: 'M17 7 7 17' })
            );
          } else {
            icon.append(
              svgElement('circle', { cx: '12', cy: '12', r: '8' }),
              svgElement('path', { d: 'M12 8v5l3 2' })
            );
          }

          return icon;
        }

        function statusBadge(state) {
          const badge = element('span', `a5rv-status has-icon is-${stateClass(state)}`);
          badge.append(statusIcon(state), document.createTextNode(stateLabel(state)));
          return badge;
        }

        function renderHistoryAttempt(attempt, pointId, seq, compact = false) {
          const article = element('article', 'a5rv-attempt');
          const panelId = `a5rv-history-panel-${pointId}-${seq}`;
          const tabId = `a5rv-history-tab-${pointId}-${seq}`;
          if (!compact) {
            article.id = panelId;
            article.setAttribute('role', 'tabpanel');
            article.setAttribute('aria-labelledby', tabId);
          } else {
            article.classList.add('a5rv-history-row-detail');
          }

          if (!compact) {
            const head = element('div', 'a5rv-attempt-head');
            const attemptTitle = `${t('a5s.review.submissionNumber', 'ส่งครั้งที่')} ${seq}`;
            head.append(element('strong', '', attemptTitle), statusBadge(attempt.state));
            article.append(head);
          }

          const meta = element('div', 'a5rv-attempt-meta');
          meta.append(
            metaItem(t('a5s.review.submittedBy', 'ผู้ส่ง'), personName(attempt.submitter)),
            metaItem(t('a5s.common.sentAt', 'ส่งเมื่อ'), attempt.submitted_at || '-')
          );
          if (attempt.evaluator || attempt.evaluated_at) {
            meta.append(
              metaItem(t('a5s.review.evaluatedBy', 'ผู้ประเมิน'), personName(attempt.evaluator)),
              metaItem(t('a5s.common.evaluatedDate', 'วันประเมิน'), attempt.evaluated_at || '-')
            );
          }

          article.append(meta);

          const items = Array.isArray(attempt.cards) ? attempt.cards : [];
          const itemSection = element('div', 'a5rv-submission-list');
          itemSection.append(element('strong', '', t('a5s.review.submittedContent', 'ข้อมูลที่ส่งมา')));
          if (items.length) {
            // การ์ดเดียวรวมทุกรายการ + เลขลำดับหน้าหัวข้อ (Manager 2026-07-31)
            const card = element('ol', 'a5rv-submission-card');
            items.forEach(entry => {
              const item = element('li', 'a5rv-submission-item');
              item.append(element('strong', '', entry.title || t('a5s.common.detail', 'รายละเอียด')));
              if (entry.detail) item.append(element('p', '', entry.detail));
              card.append(item);
            });
            itemSection.append(card);
          } else {
            itemSection.append(element('p', 'a5rv-decision-note', t('a5s.common.noCards', 'ยังไม่มีรายการที่ส่งมา')));
          }
          article.append(itemSection);

          if (attempt.note) {
            const noteClass = attempt.state === 'passed'
              ? ' is-passed'
              : (attempt.state === 'failed' ? ' is-failed' : '');
            const note = element('p', `a5rv-decision-note${noteClass}`);
            // หัวข้อบอกผลตรวจ (สีเขียว/แดง) แล้วค่อยตามด้วยข้อความหมายเหตุ
            const noteHead = element('b', '', `${t('a5s.common.note', 'หมายเหตุ')} · ${stateLabel(attempt.state)}`);
            note.append(noteHead, document.createTextNode(attempt.note));
            article.append(note);
          }

          return article;
        }

        function renderHistory(pointId) {
          const detail = reviewDetails[String(pointId)] || reviewDetails[pointId];
          if (!detail || !historyBody) return;
          historyBody.replaceChildren();

          const safePointId = String(pointId).replace(/[^a-zA-Z0-9_-]/g, '-');
          const history = Array.isArray(detail.history)
            ? [...detail.history].sort((left, right) => Number(left.seq || 0) - Number(right.seq || 0))
            : [];
          if (!history.length) {
            const empty = element('div', 'a5rv-empty');
            empty.append(
              element('h2', '', t('a5s.review.noHistory', 'ยังไม่มีประวัติการส่ง')),
              element('p', '', t('a5s.review.noHistoryHint', 'ประวัติจะปรากฏเมื่อผู้รับผิดชอบส่งข้อมูลเข้าตรวจประเมิน'))
            );
            historyBody.append(empty);
            return;
          }

          const attempts = history.map((attempt, index) => ({
            attempt,
            // "ครั้งที่" นับตามลำดับการตรวจจริง 1,2,3.. ไม่ใช้ attempt.seq ที่ผูกกับจำนวนครั้งที่กดส่ง (Manager 2026-08-27)
            seq: index + 1,
          }));

          // แถวสรุปกดกาง — ใช้ทั้งโหมดงานและโหมดตรวจ (Manager 2026-08-27: เดิมโหมดตรวจเป็นแท็บคนละแบบ)
          {
            const list = element('div', 'a5rv-history-list');
            attempts.forEach(({ attempt, seq }) => {
              const row = element('details', 'a5rv-history-row');
              const summary = element('summary');
              const state = attempt.state === 'passed'
                ? 'passed'
                : (attempt.state === 'failed' ? 'failed' : 'pending');
              const score = attempt.state === 'passed' ? '100%' : (attempt.state === 'failed' ? '0%' : '—');
              // ลำดับ: ครั้งที่ N · วันที่ · คะแนน · สถานะ(ข้อความล้วน ไม่มีกรอบ/ไอคอน) · ลูกศรกาง
              summary.append(
                element('span', 'a5rv-history-row-title', `${t('a5s.workspace.roundNumber', 'ครั้งที่')} ${seq}`),
                element('span', 'a5rv-history-row-date', attempt.evaluated_at || attempt.submitted_at || '-'),
                element('strong', 'a5rv-history-row-score', score),
                element('span', `a5rv-history-row-state is-${stateClass(state)}`, stateLabel(state)),
                element('span', 'a5rv-history-row-caret', '›')
              );
              row.append(summary, renderHistoryAttempt(attempt, safePointId, seq, true));
              // กางทีละแถว แล้วเลื่อนให้เห็นเต็ม ๆ ไม่โดนขอบโมดัลบัง (Manager 2026-08-26)
              row.addEventListener('toggle', () => {
                if (!row.open) return;
                list.querySelectorAll('details[open]').forEach(other => {
                  if (other !== row) other.open = false;
                });
                window.requestAnimationFrame(() => {
                  row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                });
              });
              list.append(row);
            });
            historyBody.append(list);
          }
        }

        function openHistory(trigger) {
          if (!historyDialog) return;
          activeHistoryPointId = trigger.dataset.reviewHistoryOpen;
          historyOpener = trigger;
          // บรรทัดแรก "ประวัติ" · บรรทัดสอง "ชื่อพื้นที่ › จุด X" (Manager 2026-08-26)
          if (historyTitle) historyTitle.textContent = t('a5s.common.history', 'ประวัติ');
          if (historyTrail) {
            historyTrail.textContent = [trigger.dataset.pointTrail, trigger.dataset.pointTitle]
              .filter(Boolean)
              .join(' › ');
          }
          renderHistory(activeHistoryPointId);
          historyDialog.showModal();
        }

        function closeHistory() {
          if (!historyDialog?.open) return;
          historyDialog.close();
        }

        document.querySelectorAll('[data-review-history-open]').forEach(trigger => {
          trigger.addEventListener('click', () => openHistory(trigger));
        });
        historyDialog?.querySelector('[data-review-history-close]')?.addEventListener('click', closeHistory);
        historyDialog?.addEventListener('click', event => {
          if (event.target === historyDialog) closeHistory();
        });
        historyDialog?.addEventListener('close', () => historyOpener?.focus());

        function localizePersonNames() {
          const lang = i18n.get ? i18n.get() : 'th';
          const field = lang === 'en' ? 'nameEn' : (lang === 'my' ? 'nameMy' : 'nameTh');
          workspace?.querySelectorAll('[data-person-name]').forEach(node => {
            node.textContent = node.dataset[field] || node.dataset.nameTh || node.textContent;
          });
        }

        document.addEventListener('insight:languagechange', () => {
          localizePersonNames();
          applyFilter(activeFilter);
          if (calendarDialog?.open) renderCalendar();
          if (historyDialog?.open && activeHistoryPointId) {
            const trigger = document.querySelector(`[data-review-history-open="${activeHistoryPointId}"]`);
            if (historyTitle && trigger) historyTitle.textContent = `${t('a5s.common.history', 'ประวัติ')} · ${trigger.dataset.pointTitle || ''}`;
            renderHistory(activeHistoryPointId);
          }
        });

        localizePersonNames();
        applyFilter(activeFilter);
        return;
      }

      // ---- modal หมายเหตุ (ปฏิเสธ = แดง / ผ่าน = เขียว) ----
      const modal = document.querySelector('[data-reject-modal]');
      const modalPoint = document.querySelector('[data-reject-modal-point]');
      const modalText = document.querySelector('[data-reject-modal-text]');
      const modalTitle = document.querySelector('[data-note-modal-title]');
      const dialog = modal?.querySelector('.a5r-note-dialog');

      function openNote(trigger) {
        if (!modal) return;
        const isPass = trigger.dataset.noteKind === 'pass';
        modalTitle.textContent = isPass
          ? t('a5s.myWork.passNote', 'หมายเหตุจากผู้ประเมิน')
          : t('a5s.myWork.rejectNote', 'หมายเหตุปฏิเสธ');
        modalPoint.textContent = trigger.dataset.rejectPoint || modalTitle.textContent;
        modalText.textContent = trigger.dataset.rejectNoteText || t('a5s.common.noData', 'ไม่มีหมายเหตุ');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        dialog?.focus();
      }

      function closeNote() {
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }

      // ปุ่ม "อ่าน" อยู่ใน <a class="nav-go"> — ต้อง stopPropagation กัน nav-go พาเปลี่ยนหน้า (บั๊กเดิม 2026-07-18)
      document.querySelectorAll('[data-reject-note]').forEach(trigger => {
        trigger.addEventListener('click', event => {
          event.preventDefault();
          event.stopPropagation();
          openNote(trigger);
        });
      });
      document.addEventListener('click', event => {
        if (event.target.closest('[data-close-reject-modal]')) {
          event.preventDefault();
          closeNote();
        }
      });
      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal && !modal.hidden) closeNote();
      });

      // ---- แผนผัง: จำสถานะพับ/กาง + สั่งวัดขนาดใหม่ตอนกาง (ตอนปิดอยู่ stage กว้าง 0) ----
      const mapBox = document.querySelector('[data-map-details]');
      if (mapBox) {
        const KEY = 'a5s_mywork_map_open';
        if (localStorage.getItem(KEY) === '1') mapBox.open = true;
        mapBox.addEventListener('toggle', () => {
          localStorage.setItem(KEY, mapBox.open ? '1' : '0');
          if (mapBox.open) requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
        });
        if (mapBox.open) requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
      }
    })();
  </script>
@endsection
