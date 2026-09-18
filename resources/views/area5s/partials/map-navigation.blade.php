@php
  $mapNavId = $mapNav['id'] ?? 'a5map';
  $mapNavAreaDetailMode = $mapNav['area_detail_mode'] ?? 'layouts';
  $mapNavPanelMode = $mapNav['panel_mode'] ?? 'layouts';
  $mapNavStageMinHeight = max(320, (int) ($mapNav['stage_min_height'] ?? 320));
  $mapNavModalTitle = $mapNav['modal_title'] ?? 'เลือกพื้นที่ย่อย/อาคาร';
  $mapNavModalTitleKey = $mapNav['modal_title_key'] ?? 'a5s.manage.subZonePageTitle';
  $mapNavReportTitle = $mapNav['report_title'] ?? 'สถานะงานที่รับผิดชอบ';
  $mapNavReportTitleKey = $mapNav['report_title_key'] ?? 'a5s.myWork.reportStatus';
  // ป้ายชี้ "งานของฉัน" — เปิดเฉพาะหน้างานพื้นที่ของฉัน เพื่อบอกว่าจุดที่ต้องเข้าไปทำอยู่โซน/อาคารไหน
  $mapNavMineMarker = (bool) ($mapNav['mine_marker'] ?? false);
  $mapNavMineLabel = $mapNav['mine_label'] ?? 'งานของฉัน';
  $mapNavMineLabelKey = $mapNav['mine_label_key'] ?? 'a5s.myWork.mineHere';
  $mapNavReportStatuses = collect($mapNav['report_statuses'] ?? [])->filter(fn ($status) => (int) ($status['count'] ?? 0) > 0)->values();
  $mapNavZones = collect($mapNav['zones'] ?? []);
  $mapNavLegend = $mapNavZones->sortByDesc(function ($zone) {
      preg_match('/(\d+)/', (string) ($zone['name'] ?? ''), $matches);
      return (int) ($matches[1] ?? 0);
  })->values();
@endphp

<style>
  .a5map-shell { --a5map-status-pending:#c8964a; display:grid; gap:.65rem; border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-tint); padding:.55rem .8rem .8rem; }
  html[data-theme="light"] .a5map-shell { --a5map-status-pending:#765018; }
  .a5map-meta { display:grid; grid-template-columns:minmax(0,1fr) auto minmax(0,1fr); align-items:start; gap:1.25rem; color:var(--light-text); }
  .a5map-tools { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; justify-self:start; }
  .a5map-zoom { display:inline-flex; align-items:center; gap:.15rem; padding:.15rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
  .a5map-zoom button { min-width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); cursor:pointer; font-size:1rem; font-weight:800; padding:0 .3rem; }
  .a5map-zoom button:hover { background:var(--panel-soft); color:var(--moss); }
  .a5map-zoom .a5map-zoom-value { min-width:3.4rem; font-size:.74rem; font-weight:750; }
  .a5map-company-group { display:grid; justify-items:center; justify-self:center; min-width:0; }
  .a5map-company { margin:.3rem 0 0; text-align:center; font-size:.92rem; font-weight:700; line-height:1.45; color:var(--light-text); }
  .a5map-report { display:flex; align-items:center; justify-content:flex-end; justify-self:end; gap:.28rem .55rem; max-width:32rem; margin-top:.2rem; color:var(--muted-light); font-size:.67rem; line-height:1.35; flex-wrap:wrap; }
  .a5map-report-title { color:var(--muted-light); font-weight:600; }
  .a5map-report-item { display:inline-flex; align-items:center; gap:.28rem; color:var(--light-text); white-space:nowrap; }
  .a5map-report-item b { font-size:.68rem; font-weight:800; }
  .a5map-report-dot { width:.43rem; height:.43rem; border-radius:50%; background:var(--muted-light); }
  .a5map-report-item.st-pass .a5map-report-dot { background:var(--success); }
  .a5map-report-item.st-fail .a5map-report-dot { background:var(--danger); }
  .a5map-report-item.st-pending .a5map-report-dot { background:var(--a5map-status-pending); }
  .a5map-report-item.st-none .a5map-report-dot { border:1px solid var(--muted-light); background:transparent; }
  .a5map-legend { display:grid; gap:.38rem; min-width:8rem; justify-self:end; }
  .a5map-legend-item { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; color:var(--light-text); font-family:var(--font-body); font-size:.76rem; font-weight:600; line-height:1.35; }
  button.a5map-legend-item { width:100%; padding:0; border:0; background:transparent; cursor:pointer; }
  button.a5map-legend-item:hover,
  button.a5map-legend-item:focus-visible { color:var(--moss); outline:none; }
  .a5map-legend-item small { color:var(--muted-light); font-size:.65rem; font-weight:500; }
  .a5map-dot { flex:none; width:.62rem; height:.62rem; border-radius:50%; background:var(--zone-color); border:1px solid color-mix(in srgb, var(--zone-color) 72%, var(--light-text)); }
  .a5map-stage { position:relative; overflow:hidden; min-height:var(--a5map-stage-min,320px); border-radius: 0.25rem; background:var(--menu-bg); touch-action:none; cursor:grab; user-select:none; }
  .a5map-stage.is-pan { cursor:grabbing; }
  .a5map-canvas { position:relative; width:100%; transform-origin:0 0; will-change:transform; }
  .a5map-canvas img { display:block; width:100%; height:auto; pointer-events:none; -webkit-user-drag:none; }
  .a5map-svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
  .a5map-poly { cursor:pointer; fill-opacity:.3; stroke-linejoin:round; pointer-events:all; transition:fill-opacity .16s ease, stroke-width .16s ease, filter .16s ease; filter:drop-shadow(0 0 2px color-mix(in srgb, currentColor 35%, transparent)); }
  .a5map-poly:hover, .a5map-poly.is-selected { fill-opacity:.46; stroke-width:1.7; filter:drop-shadow(0 0 3px color-mix(in srgb, currentColor 34%, transparent)); }
  .a5map-poly:focus-visible { outline:none; fill-opacity:.58; stroke-width:2.1; filter:drop-shadow(0 0 6px color-mix(in srgb, currentColor 58%, transparent)); }
  .a5map-subsvg .a5map-poly:hover,
  .a5map-subsvg .a5map-poly:focus-visible,
  .a5map-subsvg .a5map-poly.is-hovered { animation:a5mapAreaPulse .85s ease-in-out infinite alternate; }
  @keyframes a5mapAreaPulse {
    from { fill-opacity:.38; stroke-width:1.55; filter:drop-shadow(0 0 2px color-mix(in srgb, currentColor 38%, transparent)); }
    to { fill-opacity:.66; stroke-width:2.1; filter:drop-shadow(0 0 7px color-mix(in srgb, currentColor 62%, transparent)); }
  }
  .a5map-label { font-family:var(--font-body); font-weight:600; letter-spacing:.01em; fill:#111; paint-order:stroke; stroke:rgb(255 255 255 / 88%); stroke-width:.32px; text-anchor:middle; dominant-baseline:middle; pointer-events:none; }
  .a5map-empty { padding:1.5rem; text-align:center; color:var(--muted-light); font-size:.8rem; }

  /* ป้าย "งานของฉัน" + ลูกศรเด้ง ชี้โซน/อาคารที่มีจุดของผู้ใช้ (Manager 2026-07-22) */
  .a5map-mine { position:absolute; inset:0; z-index:7; overflow:hidden; pointer-events:none; }
  .a5map-here { position:absolute; display:grid; justify-items:center; will-change:transform; animation:a5mapHereBob 1.6s ease-in-out infinite; }
  .a5map-here-chip { display:inline-flex; align-items:baseline; gap:.34rem; padding:.24rem .58rem; border:1px solid color-mix(in srgb, var(--moss) 60%, transparent); border-radius:999px; background:var(--panel-soft); color:var(--light-text); font-size:.7rem; font-weight:800; line-height:1.25; white-space:nowrap; box-shadow:0 8px 20px rgb(0 0 0 / 26%); }
  .a5map-here-chip b { color:var(--moss); font-size:.63rem; font-weight:750; }
  .a5map-here-tip { display:block; width:0; height:0; border-inline:.36rem solid transparent; border-top:.42rem solid color-mix(in srgb, var(--moss) 60%, transparent); filter:drop-shadow(0 2px 3px rgb(0 0 0 / 22%)); }
  .a5map-here-ring { position:absolute; left:50%; top:100%; width:1.25rem; height:1.25rem; margin:-.625rem 0 0 -.625rem; border:2px solid var(--moss); border-radius:50%; animation:a5mapHereRing 1.6s ease-out infinite; }
  @keyframes a5mapHereBob { 0%, 100% { transform:translate(-50%,-100%); } 50% { transform:translate(-50%,calc(-100% - .45rem)); } }
  @keyframes a5mapHereRing { 0% { opacity:.9; transform:scale(.45); } 100% { opacity:0; transform:scale(1.6); } }
  @media (prefers-reduced-motion: reduce) {
    .a5map-here { animation:none; transform:translate(-50%,-100%); }
    .a5map-here-ring { animation:none; opacity:.45; }
  }

  .a5map-modal[hidden] { display:none; }
  .a5map-modal { position:fixed; inset:0; z-index:2500; display:grid; place-items:center; padding:1rem; background:rgb(10 14 10 / 58%); backdrop-filter:blur(3px); }
  .a5map-dialog { width:min(100%, 84rem); max-height:92vh; overflow:auto; border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); box-shadow:0 34px 90px rgb(0 0 0 / 42%); animation:a5mapCardIn .28s ease-out both; }
  @keyframes a5mapCardIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
  .a5map-modal-head { position:sticky; top:0; z-index:9; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:.8rem 1rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); }
  .a5map-modal-head.is-centered { display:grid; grid-template-columns:2.1rem minmax(0,1fr) 2.1rem; align-items:start; }
  .a5map-modal-head.is-centered > div { grid-column:2; text-align:center; }
  .a5map-modal-head.is-centered .a5map-close { grid-column:3; }
  .a5map-modal-head small { display:block; color:var(--moss); font-size:.72rem; font-weight:700; }
  .a5map-modal-head h3 { margin:.1rem 0 0; color:var(--light-text); font-family:var(--font-body); font-size:1rem; font-weight:600; }
  .a5map-close { flex:none; width:2.1rem; height:2.1rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius:50%; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; font-size:1.15rem; }
  .a5map-modal-body { display:grid; gap:.7rem; padding:.65rem .8rem .9rem; }
  .a5map-subpanel { position:relative; display:grid; grid-template-columns:minmax(0,1.3fr) minmax(21rem,.7fr); gap:.8rem; }
  .a5map-subpanel.is-centered { grid-template-columns:minmax(0,1fr); }
  .a5map-subpanel.is-centered.has-external-legend { grid-template-columns:minmax(0,1fr) minmax(14rem,20rem); gap:1rem; }
  .a5map-substage { position:relative; height:min(66vh,45rem); min-height:24rem; overflow:hidden; border-radius: 0.25rem; background:var(--menu-bg); touch-action:none; cursor:grab; animation:a5mapReveal .72s cubic-bezier(.22,.72,.18,1) .05s both; }
  @keyframes a5mapReveal { 0% { opacity:0; clip-path:inset(48% round 1rem); filter:blur(5px); } 58% { opacity:1; } 100% { opacity:1; clip-path:inset(0 round .5rem); filter:blur(0); } }
  .a5map-subcanvas { position:relative; width:100%; transform-origin:0 0; will-change:transform; }
  .a5map-subcanvas img { display:block; width:100%; height:auto; pointer-events:none; }
  .a5map-subsvg { position:absolute; inset:0; width:100%; height:100%; }
  .a5map-subtools { position:absolute; top:.5rem; left:.5rem; z-index:5; display:inline-flex; gap:.1rem; padding:.12rem; border-radius: 0.25rem; background:rgb(18 22 18 / 58%); }
  .a5map-subtools button { width:1.85rem; height:1.85rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:#fff; cursor:pointer; font-size:.98rem; font-weight:800; }
  .a5map-area-legend { position:absolute; top:.65rem; right:.7rem; z-index:6; display:grid; justify-items:stretch; gap:.42rem; max-width:min(48%,18rem); }
  .a5map-area-legend.has-floors { width:min(72%,32rem); max-width:none; }
  .a5map-area-legend.is-external { position:relative; top:auto; right:auto; z-index:auto; align-self:start; width:100%; max-width:none; padding:.15rem .1rem; }
  .a5map-area-link { display:grid; grid-template-columns:.54rem minmax(0,1fr); align-items:baseline; justify-content:end; gap:.42rem; width:100%; padding:0; border:0; background:transparent; color:#fff; font-family:var(--font-body); font-size:.76rem; font-weight:400; line-height:1.4; text-align:left; cursor:pointer; }
  .a5map-area-legend.has-floors .a5map-area-link { grid-template-columns:.54rem minmax(5.5rem,9rem) minmax(0,1fr); }
  html[data-theme="light"] .a5map-area-link { color:#111; }
  .a5map-area-dot { align-self:center; width:.54rem; height:.54rem; border-radius:50%; background:var(--area-color); border:1px solid rgb(255 255 255 / 75%); box-shadow:0 1px 4px rgb(0 0 0 / 50%); transform-origin:center; }
  .a5map-area-link:hover .a5map-area-dot,
  .a5map-area-link:focus-visible .a5map-area-dot,
  .a5map-area-link.is-hovered .a5map-area-dot { animation:a5mapDotPulse .85s ease-in-out infinite alternate; }
  @keyframes a5mapDotPulse {
    from { opacity:.58; transform:scale(.9); box-shadow:0 0 0 0 color-mix(in srgb, var(--area-color) 32%, transparent); }
    to { opacity:1; transform:scale(1.18); box-shadow:0 0 0 .3rem color-mix(in srgb, var(--area-color) 16%, transparent); }
  }
  .a5map-area-name { font-weight:600; white-space:nowrap; }
  .a5map-area-floors { min-width:0; color:inherit; font-size:.72rem; font-weight:400; overflow-wrap:anywhere; }
  .a5map-area-link.is-selected { font-weight:700; text-decoration:underline; text-underline-offset:.2em; }
  .a5map-area-legend.is-external .a5map-area-link { min-height:2.75rem; padding:.45rem .15rem; border-bottom:1px solid var(--line-light); color:var(--light-text); }
  .a5map-area-legend.is-external .a5map-area-link:hover { color:var(--moss); }
  .a5map-area-link:focus-visible, .a5map-poly:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }
  .a5map-substage[role="link"]:focus-visible { outline:2px solid var(--moss); outline-offset:-2px; }
  /* คลิกด้วยเมาส์แล้วเบราว์เซอร์ตีกรอบดำรอบกรอบพื้นที่/ภาพ — ตัดออก แต่ยังคงกรอบ moss ตอนกด Tab (Manager 2026-07-22) */
  .a5map-poly:focus:not(:focus-visible),
  .a5map-area-link:focus:not(:focus-visible),
  .a5map-substage:focus:not(:focus-visible),
  .a5map-layout:focus:not(:focus-visible),
  button.a5map-legend-item:focus:not(:focus-visible),
  .a5map-subtools button:focus:not(:focus-visible),
  .a5map-close:focus:not(:focus-visible) { outline:none; }
  /* ล็อกความสูงให้เท่าภาพแผนที่ — สลับอาคารที่มี/ไม่มีข้อมูลแล้ว modal ต้องไม่ขยับ (Manager 2026-07-22) */
  .a5map-layouts { min-width:0; height:min(66vh,45rem); min-height:24rem; overflow:auto; border:1px solid var(--line-light); border-radius: 0.26rem; background:var(--menu-bg); padding:.65rem; }
  .a5map-layouts > .a5map-empty { display:grid; place-content:center; height:100%; padding:1rem; }
  .a5map-area-title { margin:0 0 .55rem; color:var(--light-text); font-size:.88rem; font-weight:650; }
  .a5map-floor { display:grid; gap:.36rem; padding:.55rem 0; border-top:1px solid var(--line-light); }
  .a5map-floor:first-of-type { border-top:0; padding-top:0; }
  .a5map-floor h4 { margin:0; display:flex; align-items:center; justify-content:space-between; gap:.5rem; color:var(--light-text); font-size:.75rem; font-weight:700; }
  .a5map-floor h4 small { color:var(--muted-light); font-size:.63rem; font-weight:500; }
  .a5map-layout-list { display:grid; gap:.32rem; }
  .a5map-layout { display:grid; gap:.15rem; padding:.42rem .5rem; border:1px solid var(--line-light); border-radius: 0.25rem; color:inherit; background:var(--panel-soft); text-decoration:none; }
  .a5map-layout:hover, .a5map-layout:focus-visible { border-color:var(--moss); outline:none; }
  .a5map-layout strong { color:var(--light-text); font-size:.75rem; font-weight:650; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .a5map-layout small { color:var(--muted-light); font-size:.64rem; line-height:1.35; }
  .a5map-status { width:max-content; max-width:100%; padding:.08rem .4rem; border-radius:999px; font-size:.62rem; font-weight:700; }
  .a5map-status.st-pass { background:color-mix(in srgb,var(--success) 14%,transparent); color:var(--success); }
  .a5map-status.st-fail { background:color-mix(in srgb,var(--danger) 15%,transparent); color:var(--danger); }
  .a5map-status.st-pending { background:color-mix(in srgb,var(--moss) 13%,transparent); color:var(--moss); }
  .a5map-status.st-draft, .a5map-status.st-idle { background:var(--hover-soft); color:var(--muted-light); }
  .a5map-status.st-none { border:1px dashed var(--line-light); background:transparent; color:var(--muted-light); }
  .a5map-status-overview { display:grid; gap:.65rem; min-height:100%; }
  .a5map-status-overview-head { display:grid; gap:.16rem; padding-bottom:.65rem; border-bottom:1px solid var(--line-light); }
  .a5map-status-overview-head small { color:var(--muted-light); font-size:.66rem; line-height:1.4; }
  .a5map-status-overview-head h3 { margin:0; color:var(--light-text); font-size:.92rem; font-weight:700; line-height:1.35; }
  .a5map-status-overview-list { display:grid; align-content:start; }
  .a5map-status-overview-row { display:grid; grid-template-columns:.48rem minmax(0,1fr) auto; align-items:center; gap:.48rem; min-height:2.25rem; border-bottom:1px solid var(--line-light); color:var(--light-text); font-size:.73rem; }
  .a5map-status-overview-row:last-child { border-bottom:0; }
  .a5map-status-overview-row i { width:.46rem; height:.46rem; border-radius:50%; background:var(--muted-light); }
  .a5map-status-overview-row.st-pass i { background:var(--success); }
  .a5map-status-overview-row.st-fail i { background:var(--danger); }
  .a5map-status-overview-row.st-pending i { background:var(--a5map-status-pending); }
  .a5map-status-overview-row.st-none i { border:1px solid var(--muted-light); background:transparent; }
  .a5map-status-overview-row b { color:var(--light-text); font-size:.76rem; font-weight:800; }
  .a5map-status-overview-hint { margin:auto 0 0; padding-top:.65rem; border-top:1px solid var(--line-light); color:var(--muted-light); font-size:.68rem; line-height:1.5; }
  .a5map-work-summary { display:grid; grid-template-rows:auto minmax(0,1fr); gap:.45rem; min-width:0; height:100%; }
  /* ชื่ออาคารย้ายมาไว้เหนือตาราง แทนการเป็นคอลัมน์ (Manager 2026-07-22) */
  .a5map-work-head { display:grid; gap:.1rem; padding:0 .15rem; }
  .a5map-work-head small { color:var(--moss); font-size:.65rem; font-weight:750; letter-spacing:.03em; }
  .a5map-work-head h3 { margin:0; color:var(--light-text); font-family:var(--font-body); font-size:.98rem; font-weight:700; line-height:1.3; }
  .a5map-work-head .a5map-work-head-meta { color:var(--muted-light); font-weight:650; letter-spacing:0; }
  .a5map-work-table-wrap { min-height:0; overflow:hidden auto; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); }
  .a5map-work-table { width:100%; border-collapse:collapse; table-layout:fixed; }
  .a5map-work-table th { position:sticky; top:0; z-index:2; padding:.42rem .52rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); color:var(--muted-light); font-size:.64rem; font-weight:750; line-height:1.35; text-align:left; }
  .a5map-work-table th:last-child { width:7.2rem; }
  .a5map-work-table td { padding:.44rem .52rem; border-top:1px solid var(--line-light); color:var(--light-text); font-size:.72rem; line-height:1.4; vertical-align:middle; }
  .a5map-work-table tbody tr:first-child td { border-top:0; }
  /* แถวจุดถัดไปในกลุ่ม Layout เดียวกัน — เส้นคั่นจางกว่า ให้เห็นว่าเป็นก้อนเดียวกัน */
  .a5map-work-table tr.is-sub > td { border-top-color:color-mix(in srgb, var(--line-light) 48%, transparent); }
  .a5map-work-table-layout { overflow:hidden; font-weight:700; text-overflow:ellipsis; white-space:nowrap; vertical-align:top; }
  .a5map-work-table-layout small { display:block; margin-top:.12rem; color:var(--muted-light); font-size:.62rem; font-weight:650; }
  /* คอลัมน์จุด (Manager 2026-07-22) — โชว์รหัสจุด A/B/C พร้อมสถานะรายจุดให้ครบทุกจุดที่รับผิดชอบ */
  .a5map-work-table th.col-point { width:4.6rem; }
  .a5map-work-table-point { width:4.6rem; }
  /* รหัสจุดเป็นตัวอักษรเปล่า ไม่ใส่กรอบ/พื้นหลัง (Manager 2026-07-22) */
  .a5map-point-code { color:var(--light-text); font-size:.72rem; font-weight:800; letter-spacing:.01em; }
  .a5map-work-table-status { width:7.2rem; }
  @media (max-width:900px) { .a5map-subpanel, .a5map-subpanel.is-centered.has-external-legend { grid-template-columns:1fr; } .a5map-substage { height:min(58vh,34rem); min-height:20rem; } .a5map-layouts { height:auto; max-height:none; } .a5map-work-summary { height:auto; } .a5map-work-table-wrap { max-height:60vh; } .a5map-area-legend.is-external { padding:.1rem 0; } }
  @media (max-width:760px) { .a5map-meta { grid-template-columns:1fr auto; gap:.65rem; } .a5map-company-group { grid-column:1 / -1; grid-row:1; } .a5map-report { justify-content:center; justify-self:center; } .a5map-tools { grid-column:1; grid-row:2; } .a5map-legend { grid-column:2; grid-row:2; } .a5map-stage { min-height:250px; } .a5map-area-legend.has-floors { width:calc(100% - 5rem); } .a5map-area-legend.has-floors.is-external { width:100%; } .a5map-area-link { font-size:.72rem; } .a5map-area-legend.has-floors .a5map-area-link { grid-template-columns:.54rem minmax(4.5rem,7rem) minmax(0,1fr); } .a5map-area-floors { font-size:.68rem; } }
  @media (prefers-reduced-motion:reduce) {
    .a5map-dialog,
    .a5map-substage,
    .a5map-subsvg .a5map-poly:hover,
    .a5map-subsvg .a5map-poly:focus-visible,
    .a5map-subsvg .a5map-poly.is-hovered,
    .a5map-area-link:hover .a5map-area-dot,
    .a5map-area-link:focus-visible .a5map-area-dot,
    .a5map-area-link.is-hovered .a5map-area-dot { animation:none; }
    .a5map-subsvg .a5map-poly:hover,
    .a5map-subsvg .a5map-poly:focus-visible,
    .a5map-subsvg .a5map-poly.is-hovered { fill-opacity:.6; stroke-width:2; }
  }
</style>

<section class="a5map-shell" id="{{ $mapNavId }}" data-a5map style="--a5map-stage-min:{{ $mapNavStageMinHeight }}px">
  <div class="a5map-meta">
    <div class="a5map-tools">
      <span class="a5map-zoom">
        <button type="button" data-a5map-zoom-out data-i18n-aria="profile.zoom_out" aria-label="ซูมออก">−</button>
        <button type="button" class="a5map-zoom-value" data-a5map-zoom-reset>35%</button>
        <button type="button" data-a5map-zoom-in data-i18n-aria="profile.zoom_in" aria-label="ซูมเข้า">+</button>
      </span>
    </div>
    <div class="a5map-company-group">
      <p class="a5map-company" data-i18n="a5s.manage.companyName">บริษัท สุภาวุฒิ อินดัสทรี จำกัด</p>
      @if ($mapNavReportStatuses->isNotEmpty())
        <div class="a5map-report" aria-label="{{ $mapNavReportTitle }}">
          <span class="a5map-report-title" data-i18n="{{ $mapNavReportTitleKey }}">{{ $mapNavReportTitle }}</span>
          @foreach ($mapNavReportStatuses as $status)
            @php
              $reportClass = in_array(($status['status_class'] ?? ''), ['pass', 'fail', 'pending', 'none'], true) ? $status['status_class'] : 'none';
            @endphp
            <span class="a5map-report-item st-{{ $reportClass }}">
              <i class="a5map-report-dot" aria-hidden="true"></i>
              <span @if (! empty($status['status_key'])) data-i18n="a5s.status.{{ $status['status_key'] }}" @endif>{{ $status['label'] ?? '-' }}</span>
              <b>{{ number_format((int) ($status['count'] ?? 0)) }}</b>
            </span>
          @endforeach
        </div>
      @endif
    </div>
    <div class="a5map-legend">
      @foreach ($mapNavLegend as $zone)
        @php
          $zoneHasMap = collect($zone['zone_maps'] ?? [])->contains(fn ($map) => ! empty($map['image_url']));
        @endphp
        @if ($zoneHasMap)
          <button type="button" class="a5map-legend-item" style="--zone-color:{{ $zone['color'] ?: '#5b7343' }}" data-a5map-zone-link="{{ $zone['id'] }}">
            <span class="a5map-dot" aria-hidden="true"></span>
            <span>{{ $zone['name'] }} @if (! empty($zone['summary']))<small>· {{ $zone['summary'] }}</small>@endif</span>
          </button>
        @else
          <div class="a5map-legend-item" style="--zone-color:{{ $zone['color'] ?: '#5b7343' }}">
            <span class="a5map-dot" aria-hidden="true"></span>
            <span>{{ $zone['name'] }} @if (! empty($zone['summary']))<small>· {{ $zone['summary'] }}</small>@endif</span>
          </div>
        @endif
      @endforeach
    </div>
  </div>
  @if (! empty($mapNav['image_url']))
    <div class="a5map-stage" data-a5map-stage>
      <div class="a5map-canvas" data-a5map-canvas>
        <img src="{{ $mapNav['image_url'] }}" alt="{{ $mapNav['name'] ?? 'แผนผังบริษัท' }}" draggable="false">
        <svg class="a5map-svg" viewBox="0 0 100 100" preserveAspectRatio="none" data-a5map-svg></svg>
      </div>
      <div class="a5map-mine" data-a5map-mine></div>
    </div>
  @endif
</section>

<div class="a5map-modal" data-a5map-modal hidden aria-hidden="true">
  <div class="a5map-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $mapNavId }}-modal-title" tabindex="-1">
    <div class="a5map-modal-head {{ $mapNavAreaDetailMode === 'navigate' ? 'is-centered' : '' }}">
      <div><small data-a5map-zone></small><h3 id="{{ $mapNavId }}-modal-title" data-i18n="{{ $mapNavModalTitleKey }}">{{ $mapNavModalTitle }}</h3></div>
      <button type="button" class="a5map-close" data-a5map-close data-i18n-aria="a5s.common.close" aria-label="ปิด">&times;</button>
    </div>
    <div class="a5map-modal-body" data-a5map-modal-body></div>
  </div>
</div>

<script>
  (() => {
    'use strict';
    const root = document.getElementById(@json($mapNavId));
    if (!root) return;
    const zones = @json($mapNavZones->values());
    const areaDetailMode = @json($mapNavAreaDetailMode);
    const panelMode = @json($mapNavPanelMode);
    const mainStageMinHeight = @json($mapNavStageMinHeight);
    const mineMarker = @json($mapNavMineMarker);
    const mineLabelKey = @json($mapNavMineLabelKey);
    const mineLabelText = @json($mapNavMineLabel);
    const stage = root.querySelector('[data-a5map-stage]');
    const canvas = root.querySelector('[data-a5map-canvas]');
    const svg = root.querySelector('[data-a5map-svg]');
    const modal = document.querySelector('[data-a5map-modal]');
    const dialog = modal?.querySelector('.a5map-dialog');
    const modalBody = modal?.querySelector('[data-a5map-modal-body]');
    const modalZone = modal?.querySelector('[data-a5map-zone]');
    if (!stage || !canvas || !svg || !modal || !modalBody) return;
    const NS = 'http://www.w3.org/2000/svg';
    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const t = (key, fallback, vars = {}) => {
      const value = window.__portalLang?.text?.(key, fallback, vars);
      return value && value !== key ? value : fallback;
    };
    const pointsAttr = points => (points || []).map(point => `${Number(point.x || 0)},${Number(point.y || 0)}`).join(' ');
    function centroid(points) {
      const pts = points || [];
      if (pts.length < 3) return { x:50, y:50 };
      let area2 = 0, cx = 0, cy = 0;
      pts.forEach((point, index) => {
        const next = pts[(index + 1) % pts.length];
        const cross = Number(point.x || 0) * Number(next.y || 0) - Number(next.x || 0) * Number(point.y || 0);
        area2 += cross;
        cx += (Number(point.x || 0) + Number(next.x || 0)) * cross;
        cy += (Number(point.y || 0) + Number(next.y || 0)) * cross;
      });
      if (Math.abs(area2) < .0001) return { x:50, y:50 };
      return { x:cx / (3 * area2), y:cy / (3 * area2) };
    }

    // ป้าย "งานของฉัน" — เป็น HTML ลอยเหนือ stage แล้วเลื่อนตาม zoom/pan เอง จึงคมและขนาดคงที่ทุกระดับซูม
    function updateMineMark(mark, count) {
      const label = esc(t(mineLabelKey, mineLabelText));
      const unit = esc(t('a5s.units.point', 'จุด'));
      const badge = Number(count || 0) > 0 ? `<b>${Number(count).toLocaleString()} ${unit}</b>` : '';
      mark.dataset.mineCount = Number(count || 0);
      mark.innerHTML = `<span class="a5map-here-chip">${label}${badge}</span><i class="a5map-here-tip" aria-hidden="true"></i><span class="a5map-here-ring" aria-hidden="true"></span>`;
    }
    function makeMineMark(layer, count) {
      const mark = document.createElement('div');
      mark.className = 'a5map-here';
      updateMineMark(mark, count);
      layer.appendChild(mark);
      return mark;
    }
    function placeMineMarks(marks, offsetX, offsetY, width, height) {
      marks.forEach(mark => {
        mark.el.style.left = `${offsetX + width * mark.x / 100}px`;
        mark.el.style.top = `${offsetY + height * mark.y / 100}px`;
      });
    }

    let zoom = .6, panX = 0, panY = 0, pointer = null, lastModalTrigger = null;
    const labelNodes = [];
    const zoneActionNodes = [];
    const mineLayer = root.querySelector('[data-a5map-mine]');
    const mineMarks = [];
    function resizeStage() {
      const stageFloor = window.matchMedia('(max-width:760px)').matches ? 250 : mainStageMinHeight;
      const viewportSpace = Math.max(stageFloor, window.innerHeight - stage.getBoundingClientRect().top - 12);
      const contentHeight = Math.max(stageFloor, canvas.offsetHeight * zoom);
      stage.style.height = `${Math.min(contentHeight, viewportSpace)}px`;
    }
    const apply = () => {
      resizeStage();
      canvas.style.transform = `translate(${panX}px,${panY}px) scale(${zoom})`;
      placeMineMarks(mineMarks, panX, panY, canvas.offsetWidth * zoom, canvas.offsetHeight * zoom);
    };
    function clampPan() {
      const rect = stage.getBoundingClientRect();
      const extraX = rect.width - canvas.offsetWidth * zoom;
      const extraY = rect.height - canvas.offsetHeight * zoom;
      panX = clamp(panX, Math.min(extraX, 0), Math.max(extraX, 0));
      panY = clamp(panY, Math.min(extraY, 0), Math.max(extraY, 0));
    }
    function renderLabels() { const size = clamp(1.9 / zoom, .5, 1.9); labelNodes.forEach(node => node.setAttribute('font-size', size)); }
    function reset() {
      zoom = .6; // default แผนที่บริษัทที่ 60% (Manager 2026-07-23)
      resizeStage();
      const rect = stage.getBoundingClientRect();
      panX = Math.max(rect.width - canvas.offsetWidth * zoom, 0) / 2;
      panY = 0;
      clampPan(); apply(); renderLabels();
      root.querySelector('[data-a5map-zoom-reset]').textContent = '60%';
    }
    function setZoom(next, x, y) {
      const rect = stage.getBoundingClientRect();
      x ??= rect.width / 2; y ??= rect.height / 2;
      next = clamp(next, .2, 10);
      panX = x - (x - panX) * (next / zoom); panY = y - (y - panY) * (next / zoom);
      zoom = next; clampPan(); apply(); renderLabels();
      root.querySelector('[data-a5map-zoom-reset]').textContent = `${Math.round(zoom * 100)}%`;
    }
    zones.forEach(zone => {
      if (!(zone.shape_points || []).length) return;
      const hasZoneMaps = (zone.zone_maps || []).some(map => map.image_url);
      const polygon = document.createElementNS(NS, 'polygon');
      polygon.setAttribute('points', pointsAttr(zone.shape_points));
      polygon.setAttribute('fill', zone.color || '#5b7343'); polygon.setAttribute('stroke', zone.color || '#5b7343');
      polygon.setAttribute('stroke-width', '1.2'); polygon.setAttribute('vector-effect', 'non-scaling-stroke');
      polygon.setAttribute('class', 'a5map-poly'); polygon.style.color = zone.color || '#5b7343'; polygon.dataset.zoneId = zone.id;
      if (hasZoneMaps) {
        polygon.setAttribute('tabindex', '0');
        polygon.setAttribute('role', 'button');
        polygon.setAttribute('aria-label', `${zone.name}: ${t('a5s.area.openZone', 'เปิดพื้นที่ย่อยของโซนนี้')}`);
        zoneActionNodes.push({ polygon, zone });
        polygon.addEventListener('keydown', event => {
          if (!['Enter', ' '].includes(event.key)) return;
          event.preventDefault();
          openZone(zone);
        });
      }
      svg.appendChild(polygon);
      const center = centroid(zone.shape_points);
      const label = document.createElementNS(NS, 'text');
      label.setAttribute('x', center.x); label.setAttribute('y', center.y); label.setAttribute('class', 'a5map-label'); label.textContent = zone.name;
      svg.appendChild(label); labelNodes.push(label);
      if (mineMarker && mineLayer && Number(zone.mine_point_count || 0) > 0) {
        mineMarks.push({ el:makeMineMark(mineLayer, zone.mine_point_count), x:center.x, y:center.y });
      }
    });
    root.querySelector('[data-a5map-zoom-in]').addEventListener('click', () => setZoom(zoom * 1.35));
    root.querySelector('[data-a5map-zoom-out]').addEventListener('click', () => setZoom(zoom / 1.35));
    root.querySelector('[data-a5map-zoom-reset]').addEventListener('click', reset);
    stage.addEventListener('wheel', event => { event.preventDefault(); const rect = stage.getBoundingClientRect(); setZoom(zoom * (event.deltaY < 0 ? 1.2 : 1 / 1.2), event.clientX - rect.left, event.clientY - rect.top); }, { passive:false });
    stage.addEventListener('pointerdown', event => { pointer = { x:event.clientX, y:event.clientY, px:panX, py:panY, moved:false, target:event.target }; stage.setPointerCapture?.(event.pointerId); });
    stage.addEventListener('pointermove', event => { if (!pointer) return; const dx = event.clientX - pointer.x, dy = event.clientY - pointer.y; if (Math.abs(dx) + Math.abs(dy) > 4) pointer.moved = true; if (pointer.moved) { panX = pointer.px + dx; panY = pointer.py + dy; clampPan(); apply(); stage.classList.add('is-pan'); } });
    stage.addEventListener('pointerup', event => { if (!pointer) return; const current = pointer; pointer = null; stage.classList.remove('is-pan'); if (!current.moved) { const poly = current.target.closest?.('[data-zone-id]'); const zone = zones.find(item => Number(item.id) === Number(poly?.dataset.zoneId)); if (zone && (zone.zone_maps || []).some(map => map.image_url)) openZone(zone); } });
    root.querySelectorAll('[data-a5map-zone-link]').forEach(button => {
      button.addEventListener('click', () => {
        const zone = zones.find(item => Number(item.id) === Number(button.dataset.a5mapZoneLink));
        if (zone && (zone.zone_maps || []).some(map => map.image_url)) openZone(zone);
      });
    });
    window.addEventListener('resize', () => { resizeStage(); clampPan(); apply(); });

    function layoutHtml(area) {
      const floors = area.floors || [];
      return `<h3 class="a5map-area-title">${esc(area.name)}</h3>${floors.map(floor => `<section class="a5map-floor"><h4><span>${esc(floor.name || '-')}</span><small>${(floor.layouts || []).length} ${esc(t('a5s.units.layout', 'พื้นที่'))}</small></h4><div class="a5map-layout-list">${(floor.layouts || []).map(layout => `<a class="a5map-layout nav-go" href="${esc(layout.url)}"><strong>${esc(layout.name)}</strong>${layout.point_count !== undefined ? `<small>${Number(layout.point_count || 0).toLocaleString()} ${esc(t('a5s.units.point', 'จุด'))}</small>` : (layout.meta ? `<small>${esc(layout.meta)}</small>` : '')}${layout.status_label ? `<span class="a5map-status st-${esc(layout.status_class || 'idle')}">${esc(layout.status_key ? t(`a5s.status.${layout.status_key}`, layout.status_label) : layout.status_label)}</span>` : ''}</a>`).join('')}</div></section>`).join('')}`;
    }
    function statusHtml(area) {
      const statuses = (area.statuses || []).filter(status => Number(status.count || 0) > 0);
      const statusRows = statuses.length
        ? statuses.map(status => `<div class="a5map-status-overview-row st-${esc(status.status_class || 'none')}"><i aria-hidden="true"></i><span>${esc(status.status_key ? t(`a5s.status.${status.status_key}`, status.label || '-') : (status.label || '-'))}</span><b>${Number(status.count || 0).toLocaleString()} ${esc(t('a5s.units.point', 'จุด'))}</b></div>`).join('')
        : `<div class="a5map-status-overview-row st-none"><i aria-hidden="true"></i><span>${esc(t('a5s.status.no_data', 'ยังไม่มีข้อมูล'))}</span><b>0 ${esc(t('a5s.units.point', 'จุด'))}</b></div>`;
      return `<div class="a5map-status-overview"><header class="a5map-status-overview-head"><small>${esc(t('a5s.myWork.statusData', 'ข้อมูลสถานะ'))}</small><h3>${esc(area.name)}</h3><small>${Number(area.point_count || 0).toLocaleString()} ${esc(t('a5s.units.point', 'จุด'))} · ${Number(area.layout_count || 0).toLocaleString()} ${esc(t('a5s.units.layout', 'พื้นที่'))}</small></header><div class="a5map-status-overview-list">${statusRows}</div><p class="a5map-status-overview-hint">${esc(t('a5s.myWork.openAreaHint', 'คลิกกรอบพื้นที่เพื่อดูรายการงานด้านล่าง'))}</p></div>`;
    }
    // ตารางรายจุด `Layout | จุด | สถานะ` — ใช้ร่วมกันทั้งงานของฉันและตรวจประเมิน จะได้ไม่หลุดจากกันเวลาแก้ (Manager 2026-07-22)
    function pointTableHtml(area, emptyText) {
      const layouts = (area.floors || []).flatMap(floor => floor.layouts || []);
      if (!layouts.length) {
        return `<div class="a5map-empty">${esc(emptyText)}</div>`;
      }

      const noData = t('a5s.status.no_data', 'ยังไม่มีข้อมูล');
      const pointWord = t('a5s.units.point', 'จุด');
      // แถวละ "จุด" — Layout ใช้ rowspan คลุมทุกจุดของตัวเอง เพื่อให้เห็นสถานะครบทุกจุดที่รับผิดชอบ
      const layoutRows = layouts.map(layout => {
        const points = layout.points || [];
        if (!points.length) {
          const statusLabel = layout.status_key
            ? t(`a5s.status.${layout.status_key}`, layout.status_label || '-')
            : (layout.status_label || noData);
          return `<tr><td class="a5map-work-table-layout" title="${esc(layout.name)}">${esc(layout.name)}</td><td class="a5map-work-table-point">—</td><td class="a5map-work-table-status"><span class="a5map-status st-${esc(layout.status_class || 'none')}">${esc(statusLabel)}</span></td></tr>`;
        }

        return points.map((point, index) => {
          const pointStatus = point.status_key
            ? t(`a5s.status.${point.status_key}`, point.status_label || '-')
            : (point.status_label || noData);
          const layoutCell = index === 0
            ? `<td class="a5map-work-table-layout" rowspan="${points.length}" title="${esc(layout.name)}">${esc(layout.name)}<small>${points.length} ${esc(pointWord)}</small></td>`
            : '';
          return `<tr class="${index === 0 ? '' : 'is-sub'}">${layoutCell}<td class="a5map-work-table-point"><span class="a5map-point-code" title="${esc(point.name || point.code || '')}">${esc(point.code || '-')}</span></td><td class="a5map-work-table-status"><span class="a5map-status st-${esc(point.status_class || 'none')}">${esc(pointStatus)}</span></td></tr>`;
        }).join('');
      }).join('');

      const totalPoints = layouts.reduce((sum, layout) => sum + ((layout.points || []).length || Number(layout.point_count || 0)), 0);
      const head = `<header class="a5map-work-head"><small>${esc(t('a5s.common.building', 'อาคาร'))}</small><h3>${esc(area.name || '-')}</h3><small class="a5map-work-head-meta">${layouts.length.toLocaleString()} ${esc(t('a5s.units.layout', 'พื้นที่'))} · ${totalPoints.toLocaleString()} ${esc(pointWord)}</small></header>`;

      return `<div class="a5map-work-summary">${head}<div class="a5map-work-table-wrap"><table class="a5map-work-table"><thead><tr><th scope="col">${esc(t('a5s.units.layout', 'พื้นที่'))}</th><th scope="col" class="col-point">${esc(pointWord)}</th><th scope="col">${esc(t('a5s.common.status', 'สถานะ'))}</th></tr></thead><tbody>${layoutRows}</tbody></table></div></div>`;
    }
    const workSummaryHtml = area => pointTableHtml(area, t('a5s.myWork.noAssigned', 'ยังไม่มีจุดพื้นที่ที่มอบหมายให้คุณ'));
    const reviewSummaryHtml = area => pointTableHtml(area, t('a5s.myWork.noReviewAssigned', 'ยังไม่มีพื้นที่ที่ได้รับมอบหมายให้ตรวจประเมิน'));
    const detailHtml = area => panelMode === 'review-summary'
      ? reviewSummaryHtml(area)
      : (panelMode === 'work-summary' ? workSummaryHtml(area) : (panelMode === 'status' ? statusHtml(area) : layoutHtml(area)));
    function initSubmap(panel, map) {
      const substage = panel.querySelector('[data-a5map-substage]');
      const subcanvas = panel.querySelector('[data-a5map-subcanvas]');
      const subsvg = panel.querySelector('[data-a5map-subsvg]');
      const details = panel.querySelector('[data-a5map-layouts]');
      const image = subcanvas.querySelector('img');
      let fit = 1, current = 1, x = 0, y = 0, subPointer = null;
      const labels = [];
      const subMineLayer = panel.querySelector('[data-a5map-submine]');
      const subMineMarks = [];
      const applySub = () => {
        subcanvas.style.transform = `translate(${x}px,${y}px) scale(${current})`;
        placeMineMarks(subMineMarks, x, y, subcanvas.offsetWidth * current, subcanvas.offsetHeight * current);
      };
      function clampSub() {
        const rect = substage.getBoundingClientRect();
        const width = subcanvas.offsetWidth * current, height = subcanvas.offsetHeight * current;
        x = width <= rect.width ? (rect.width - width) / 2 : clamp(x, rect.width - width, 0);
        y = height <= rect.height ? (rect.height - height) / 2 : clamp(y, rect.height - height, 0);
      }
      function resetSub() {
        const rect = substage.getBoundingClientRect();
        fit = subcanvas.offsetWidth && subcanvas.offsetHeight ? Math.min(rect.width / subcanvas.offsetWidth, rect.height / subcanvas.offsetHeight) : 1;
        current = fit; x = 0; y = 0;
        clampSub();
        applySub(); labels.forEach(label => label.setAttribute('font-size', clamp(2 / current, .3, 2)));
      }
      function setSubZoom(next, sx, sy) {
        const rect = substage.getBoundingClientRect();
        sx ??= rect.width / 2; sy ??= rect.height / 2;
        next = clamp(next, fit, fit * 12);
        x = sx - (sx - x) * (next / current); y = sy - (sy - y) * (next / current);
        current = next; clampSub(); applySub();
        labels.forEach(label => label.setAttribute('font-size', clamp(2 / current, .3, 2)));
      }
      function selectArea(areaId) {
        const area = (map.areas || []).find(item => Number(item.id) === Number(areaId));
        if (!area) return;
        panel.querySelectorAll('[data-a5map-area]').forEach(button => button.classList.toggle('is-selected', Number(button.dataset.a5mapArea) === Number(areaId)));
        panel.querySelectorAll('[data-area-id]').forEach(poly => poly.classList.toggle('is-selected', Number(poly.dataset.areaId) === Number(areaId)));
        if (details) details.innerHTML = detailHtml(area);
        panel.dataset.selectedAreaId = areaId;
      }
      function setAreaHover(areaId, isHovered) {
        panel.querySelectorAll('[data-area-id]').forEach(poly => {
          if (Number(poly.dataset.areaId) === Number(areaId)) poly.classList.toggle('is-hovered', isHovered);
        });
        panel.querySelectorAll('[data-a5map-area]').forEach(button => {
          if (Number(button.dataset.a5mapArea) === Number(areaId)) button.classList.toggle('is-hovered', isHovered);
        });
      }
      function openArea(area) {
        if (!area) return;
        if (areaDetailMode === 'navigate' && area.detail_url) {
          closeModal();
          window.location.href = area.detail_url;
          return;
        }
        if (areaDetailMode === 'section' && area.target_id) {
          const target = document.getElementById(area.target_id);
          if (target) {
            closeModal();
            requestAnimationFrame(() => {
              target.scrollIntoView({ behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block:'center' });
              target.focus?.({ preventScroll:true });
            });
            return;
          }
        }
        selectArea(area.id);
      }
      (map.areas || []).forEach(area => {
        const polygon = document.createElementNS(NS, 'polygon');
        polygon.setAttribute('points', pointsAttr(area.shape_points)); polygon.setAttribute('fill', area.color || '#5b7343'); polygon.setAttribute('stroke', area.color || '#5b7343'); polygon.setAttribute('stroke-width', '1'); polygon.setAttribute('vector-effect', 'non-scaling-stroke'); polygon.setAttribute('class', 'a5map-poly'); polygon.style.color = area.color || '#5b7343'; polygon.dataset.areaId = area.id;
        const areaIsActionable = (areaDetailMode === 'navigate' && area.detail_url) || (areaDetailMode === 'section' && area.target_id);
        if (areaIsActionable) {
          polygon.setAttribute('tabindex', '0');
          polygon.setAttribute('role', 'link');
          const openAreaLabel = panelMode === 'review-summary'
            ? t('a5s.myWork.openAreaReview', 'เปิดรายการตรวจประเมินของพื้นที่นี้')
            : (panelMode === 'work-summary' || areaDetailMode === 'section'
              ? t('a5s.myWork.openAreaWork', 'เปิดรายการงานของพื้นที่นี้')
              : t('a5s.area.openDetail', 'เปิดหน้าพื้นที่ย่อย'));
          polygon.setAttribute('aria-label', `${area.name}: ${openAreaLabel}`);
          polygon.addEventListener('keydown', event => {
            if (!['Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            event.stopPropagation();
            openArea(area);
          });
        }
        polygon.addEventListener('pointerenter', () => {
          setAreaHover(area.id, true);
          if (panelMode === 'status' || panelMode === 'work-summary' || panelMode === 'review-summary') selectArea(area.id);
        });
        polygon.addEventListener('pointerleave', () => setAreaHover(area.id, false));
        polygon.addEventListener('focus', () => setAreaHover(area.id, true));
        polygon.addEventListener('blur', () => setAreaHover(area.id, false));
        subsvg.appendChild(polygon);
        const center = centroid(area.shape_points); const label = document.createElementNS(NS, 'text'); label.setAttribute('x', center.x); label.setAttribute('y', center.y); label.setAttribute('class', 'a5map-label'); label.textContent = area.name; subsvg.appendChild(label); labels.push(label);
        const areaMineCount = Number(area.mine_point_count ?? area.point_count ?? 0);
        if (mineMarker && subMineLayer && areaMineCount > 0) {
          subMineMarks.push({ el:makeMineMark(subMineLayer, areaMineCount), x:center.x, y:center.y });
        }
      });
      panel.querySelectorAll('[data-a5map-area]').forEach(button => {
        const areaId = button.dataset.a5mapArea;
        button.addEventListener('pointerenter', () => {
          setAreaHover(areaId, true);
          const area = (map.areas || []).find(item => Number(item.id) === Number(areaId));
          if (panelMode === 'status' || panelMode === 'work-summary' || panelMode === 'review-summary') selectArea(areaId);
        });
        button.addEventListener('pointerleave', () => setAreaHover(areaId, false));
        button.addEventListener('focus', () => setAreaHover(areaId, true));
        button.addEventListener('blur', () => setAreaHover(areaId, false));
        button.addEventListener('click', () => {
          openArea((map.areas || []).find(area => Number(area.id) === Number(areaId)));
        });
      });
      panel.querySelector('[data-sub-reset]').addEventListener('click', resetSub);
      panel.querySelector('[data-sub-in]').addEventListener('click', () => setSubZoom(current * 1.35));
      panel.querySelector('[data-sub-out]').addEventListener('click', () => setSubZoom(current / 1.35));
      substage.addEventListener('wheel', event => { event.preventDefault(); const rect = substage.getBoundingClientRect(); setSubZoom(current * (event.deltaY < 0 ? 1.2 : 1 / 1.2), event.clientX - rect.left, event.clientY - rect.top); }, { passive:false });
      substage.addEventListener('pointerdown', event => {
        if (event.button !== 0) return;
        if (event.target.closest('.a5map-subtools, .a5map-area-legend')) return;
        const areaTarget = event.target.closest?.('[data-area-id]');
        subPointer = { x:event.clientX, y:event.clientY, px:x, py:y, moved:false, areaId:areaTarget?.dataset.areaId || null };
        substage.setPointerCapture?.(event.pointerId);
      });
      substage.addEventListener('pointermove', event => {
        if (!subPointer) return;
        const dx = event.clientX - subPointer.x, dy = event.clientY - subPointer.y;
        if (Math.abs(dx) + Math.abs(dy) > 4) subPointer.moved = true;
        if (subPointer.moved) { x = subPointer.px + dx; y = subPointer.py + dy; clampSub(); applySub(); }
      });
      substage.addEventListener('pointerup', () => {
        const currentPointer = subPointer;
        subPointer = null;
        if (!currentPointer || currentPointer.moved) return;
        if (currentPointer.areaId) {
          openArea((map.areas || []).find(area => Number(area.id) === Number(currentPointer.areaId)));
          return;
        }
        const navigableAreas = (map.areas || []).filter(area => area.detail_url);
        if (areaDetailMode === 'navigate' && navigableAreas.length === 1) openArea(navigableAreas[0]);
      });
      substage.addEventListener('pointercancel', () => { subPointer = null; });
      substage.addEventListener('keydown', event => {
        if (event.target.closest('.a5map-subtools, .a5map-area-legend, [data-area-id]')) return;
        if (!['Enter', ' '].includes(event.key)) return;
        const navigableAreas = (map.areas || []).filter(area => area.detail_url);
        if (areaDetailMode !== 'navigate' || navigableAreas.length !== 1) return;
        event.preventDefault();
        openArea(navigableAreas[0]);
      });
      requestAnimationFrame(() => requestAnimationFrame(resetSub));
      if (!image.complete) image.addEventListener('load', resetSub, { once:true });
      const initialArea = (map.areas || [])[0];
      if (initialArea) selectArea(initialArea.id);
    }
    function mapPanel(map) {
      const mapOnly = areaDetailMode === 'navigate' && panelMode === 'layouts';
      const legendAreas = (map.areas || []).filter(area => {
        if (mapOnly) return (area.floor_names || []).length && area.detail_url;
        return true;
      });
      const legends = legendAreas.map(area => {
        const floorNames = mapOnly ? (area.floor_names || []).filter(Boolean) : [];
        return `<button type="button" class="a5map-area-link" style="--area-color:${esc(area.color || '#5b7343')}" data-a5map-area="${Number(area.id)}"><span class="a5map-area-dot" aria-hidden="true"></span><span class="a5map-area-name">${esc(area.name)}${floorNames.length ? ':' : ''}</span>${floorNames.length ? `<span class="a5map-area-floors">${floorNames.map(esc).join(' · ')}</span>` : ''}</button>`;
      }).join('');
      const navigableMapAreas = (map.areas || []).filter(area => area.detail_url);
      const imageLinkArea = mapOnly && navigableMapAreas.length === 1 ? navigableMapAreas[0] : null;
      const imageLinkAttrs = imageLinkArea ? ` tabindex="0" role="link" aria-label="${esc(`${imageLinkArea.name}: ${t('a5s.area.openDetail', 'เปิดหน้าพื้นที่ย่อย')}`)}"` : '';
      const legendHtml = legends ? `<div class="a5map-area-legend${mapOnly ? ' has-floors is-external' : ''}">${legends}</div>` : '';
      return `<section class="a5map-subpanel${mapOnly ? ' is-centered' : ''}${mapOnly && legends ? ' has-external-legend' : ''}" data-map-id="${Number(map.id)}"><div class="a5map-substage" data-a5map-substage${imageLinkAttrs}><div class="a5map-subcanvas" data-a5map-subcanvas><img src="${esc(map.image_url)}" alt="${esc(map.name)}" draggable="false"><svg class="a5map-subsvg" viewBox="0 0 100 100" preserveAspectRatio="none" data-a5map-subsvg></svg></div><div class="a5map-mine" data-a5map-submine></div><div class="a5map-subtools"><button type="button" data-sub-out aria-label="${esc(t('profile.zoom_out', 'ซูมออก'))}">−</button><button type="button" data-sub-reset aria-label="${esc(t('a5s.common.fitImage', 'แสดงภาพพอดี'))}">⤢</button><button type="button" data-sub-in aria-label="${esc(t('profile.zoom_in', 'ซูมเข้า'))}">+</button></div>${mapOnly ? '' : legendHtml}</div>${mapOnly ? legendHtml : '<aside class="a5map-layouts" data-a5map-layouts></aside>'}</section>`;
    }
    function openZone(zone) {
      lastModalTrigger = document.activeElement;
      modalZone.textContent = zone.name;
      modalBody.innerHTML = (zone.zone_maps || []).filter(map => map.image_url).map(mapPanel).join('');
      modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden';
      (zone.zone_maps || []).filter(map => map.image_url).forEach(map => { const panel = modalBody.querySelector(`[data-map-id="${Number(map.id)}"]`); if (panel) initSubmap(panel, map); });
      requestAnimationFrame(() => modal.querySelector('[data-a5map-close]')?.focus());
    }
    function closeModal() {
      if (modal.hidden) return;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      modalBody.innerHTML = '';
      document.body.style.overflow = '';
      const trigger = lastModalTrigger;
      lastModalTrigger = null;
      requestAnimationFrame(() => trigger?.focus?.());
    }
    modal.querySelector('[data-a5map-close]').addEventListener('click', closeModal);
    modal.addEventListener('pointerdown', event => { if (event.target === modal) closeModal(); });
    modal.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
        return;
      }
      if (event.key !== 'Tab') return;
      const focusable = [...modal.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')]
        .filter(element => !element.hidden && element.getClientRects().length > 0);
      if (!focusable.length) {
        event.preventDefault();
        dialog?.focus();
        return;
      }
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
    document.addEventListener('insight:languagechange', () => {
      zoneActionNodes.forEach(({ polygon, zone }) => {
        polygon.setAttribute('aria-label', `${zone.name}: ${t('a5s.area.openZone', 'เปิดพื้นที่ย่อยของโซนนี้')}`);
      });
      [root, modal].forEach(container => {
        container?.querySelectorAll('.a5map-here').forEach(mark => updateMineMark(mark, Number(mark.dataset.mineCount || 0)));
      });
      modalBody.querySelectorAll('[data-map-id]').forEach(panel => {
        const map = (zones.flatMap(zone => zone.zone_maps || [])).find(item => Number(item.id) === Number(panel.dataset.mapId));
        const area = (map?.areas || []).find(item => Number(item.id) === Number(panel.dataset.selectedAreaId));
        const details = panel.querySelector('[data-a5map-layouts]');
        if (area && details) details.innerHTML = detailHtml(area);
      });
    });
    reset();
    const mainImage = canvas.querySelector('img');
    if (!mainImage.complete) {
      mainImage.addEventListener('load', reset, { once:true });
    }
  })();
</script>
