@extends('layouts.portal')

@section('title', 'ระบบ HR ทั้งหมด')
@section('body-class', 'systems-portal-body')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="systems.kicker">ระบบ</span>
  <span class="tt-title" data-i18n="nav.systems">ระบบ HR ทั้งหมด</span>
@endsection

@section('topbar-center')
  <label class="portal-search systems-search" for="systemsSearch">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
    <input id="systemsSearch" type="search" data-systems-search data-i18n-placeholder="systems.searchPh" placeholder="ค้นหาระบบ เอกสาร หรือชื่อคน">
    <kbd>/</kbd>
  </label>
@endsection

@section('page-style')
  body.systems-portal-body {
    --near-black: #050706;
    --panel: #080b0d;
    --panel-soft: #101519;
    --moss: #5b8def;
    --light-text: #f8faf4;
    --muted-light: rgb(248 250 244 / 66%);
    --line-light: rgb(255 255 255 / 11%);
    --line-strong: rgb(255 255 255 / 18%);
    --hover-soft: rgb(91 141 239 / 11%);
    --hover-soft-2: rgb(91 141 239 / 16%);
    --bar-bg: rgb(5 8 10 / 88%);
    --menu-bg: rgb(7 10 12 / 98%);
    --sidebar-w: 15.5rem;
    --topbar-h: 4.35rem;
    --page-pad: clamp(1rem, 2.2vw, 2.45rem);
    background:
      radial-gradient(circle at 62% 2%, rgb(91 141 239 / 10%), transparent 27rem),
      linear-gradient(180deg, #070a0d 0%, #050706 48%, #030404 100%);
    color: var(--light-text);
  }

  body.systems-portal-body .main {
    min-height: calc(100vh - var(--topbar-h));
    padding: clamp(.95rem, 1.55vw, 1.45rem) var(--page-pad) 1.25rem;
    background:
      radial-gradient(circle at 78% 12%, rgb(87 150 169 / 9%), transparent 23rem),
      radial-gradient(circle at 18% 24%, rgb(91 141 239 / 8%), transparent 22rem),
      #050706;
  }

  html[data-theme="light"] body.systems-portal-body {
    --near-black: #f6f7f3;
    --panel: #ffffff;
    --panel-soft: #f8faf5;
    --moss: #1e3a8a;
    --light-text: #171c16;
    --muted-light: rgb(23 28 22 / 64%);
    --line-light: rgb(30 58 138 / 12%);
    --line-strong: rgb(30 58 138 / 22%);
    --hover-soft: rgb(30 58 138 / 8%);
    --hover-soft-2: rgb(30 58 138 / 13%);
    --bar-bg: rgb(18 24 28 / 94%);
    --menu-bg: rgb(255 255 255 / 98%);
    background: #f6f7f3;
    color: var(--light-text);
  }

  /* ธีมสว่าง: topbar เป็นสีขาวตามพอร์ทัลปกติ (Manager สั่ง 2026-08-19)
     เดิมบังคับให้เข้มไว้ ทำให้หน้านี้แถบบนดำอยู่หน้าเดียวไม่เข้ากับหน้าอื่น */
  html[data-theme="light"] body.systems-portal-body .app-topbar {
    border-bottom-color: var(--line-light);
    background: var(--panel);
    color: var(--light-text);
  }

  /* ตัวอักษร/ไอคอนบน topbar กลับไปใช้สีของธีม ไม่บังคับขาวแล้ว
     ไม่งั้นพอพื้นเป็นขาวจะกลายเป็นขาวบนขาวจนอ่านไม่ออก */
  html[data-theme="light"] body.systems-portal-body .app-brand,
  html[data-theme="light"] body.systems-portal-body .topbar-title .tt-title,
  html[data-theme="light"] body.systems-portal-body .welcome strong,
  html[data-theme="light"] body.systems-portal-body .icon-toggle:not(.topbar-logout),
  html[data-theme="light"] body.systems-portal-body .language-button,
  html[data-theme="light"] body.systems-portal-body .noti-trigger {
    color: var(--light-text);
  }

  html[data-theme="light"] body.systems-portal-body .main {
    background:
      radial-gradient(circle at 76% 8%, rgb(30 58 138 / 8%), transparent 22rem),
      linear-gradient(180deg, #fbfcf9 0%, #f6f7f3 100%);
  }

  .systems-search {
    width: min(100%, 38rem);
    min-height: 2.7rem;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: .7rem;
    padding: 0 .65rem 0 .85rem;
    border: 1px solid rgb(255 255 255 / 14%);
    border-radius: 4px;
    background: rgb(255 255 255 / 5%);
    color: #f8faf4;
  }

  .systems-search svg {
    width: 1.12rem;
    height: 1.12rem;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
    opacity: .76;
  }

  .systems-search input {
    min-width: 0;
    height: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #ffffff;
    font-size: .88rem;
    font-weight: 500;
  }

  .systems-search input::placeholder {
    color: rgb(248 250 244 / 55%);
    opacity: 1;
  }

  .systems-search kbd {
    min-width: 1.75rem;
    min-height: 1.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(255 255 255 / 13%);
    border-radius: 4px;
    background: rgb(255 255 255 / 7%);
    color: rgb(248 250 244 / 68%);
    font-size: .76rem;
    font-weight: 700;
    line-height: 1;
  }

  .systems-dashboard {
    width: min(100%, 92rem);
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1.58fr) minmax(18.5rem, .72fr);
    gap: clamp(.85rem, 1.4vw, 1.15rem);
    align-items: stretch;
  }

  .systems-main-stack,
  .systems-side-stack {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: clamp(.85rem, 1.35vw, 1.15rem);
    height: 100%;
  }

  .systems-hero {
    position: relative;
    min-height: clamp(8.6rem, 17vh, 11rem);
    overflow: hidden;
    display: grid;
    align-items: center;
    border: 1px solid rgb(255 255 255 / 10%);
    border-radius: 4px;
    background-image:
      linear-gradient(90deg, rgb(4 6 7 / 96%) 0%, rgb(4 6 7 / 86%) 35%, rgb(4 6 7 / 30%) 70%, rgb(4 6 7 / 70%) 100%),
      linear-gradient(180deg, rgb(4 6 7 / 8%), rgb(4 6 7 / 70%)),
      url("{{ asset('assets/insight/systems-intranet-bg.png') }}");
    background-size: cover;
    background-position: center right;
    box-shadow: 0 1.25rem 3.5rem rgb(0 0 0 / 34%);
    isolation: isolate;
  }

  .systems-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
      linear-gradient(180deg, transparent, rgb(0 0 0 / 20%)),
      radial-gradient(circle at 24% 62%, rgb(91 141 239 / 12%), transparent 13rem);
    pointer-events: none;
    z-index: -1;
  }

  .systems-hero-copy {
    width: min(100%, 47rem);
    padding: clamp(1rem, 2.1vw, 1.55rem) clamp(1.1rem, 2.8vw, 2rem);
  }

  .systems-kicker {
    display: inline-flex;
    align-items: center;
    gap: .7rem;
    color: var(--moss);
    font-family: "Montserrat", var(--font-body);
    font-size: clamp(.68rem, .76vw, .84rem);
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
  }

  .systems-kicker::after {
    content: "";
    width: 2.45rem;
    height: 2px;
    background: currentColor;
  }

  .systems-hero h1 {
    display: flex;
    align-items: baseline;
    gap: .38rem;
    flex-wrap: nowrap;
    margin-top: .5rem;
    color: #ffffff;
    font-family: "Montserrat", var(--font-body);
    font-size: clamp(2rem, 3.2vw, 3rem);
    font-weight: 800;
    letter-spacing: .01em;
    line-height: 1.08;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .systems-hero h1 span,
  .systems-hero h1 strong {
    display: inline;
  }

  .systems-hero h1 strong {
    color: var(--moss);
    font-size: 1em;
    text-transform: none;
  }

  .systems-hero p {
    width: min(100%, 28rem);
    margin-top: .6rem;
    color: rgb(248 250 244 / 72%);
    font-size: clamp(.94rem, 1.15vw, 1.1rem);
    line-height: 1.55;
  }

  .systems-panel {
    border: 1px solid rgb(255 255 255 / 10%);
    border-radius: 4px;
    background:
      linear-gradient(180deg, rgb(255 255 255 / 5%), rgb(255 255 255 / 2.5%)),
      rgb(8 11 13 / 82%);
    box-shadow: 0 .85rem 2.4rem rgb(0 0 0 / 22%);
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
  }

  .systems-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .85rem;
    padding: .9rem 1rem .75rem;
    border-bottom: 1px solid rgb(255 255 255 / 8%);
  }

  .panel-title-stack {
    display: grid;
    gap: .18rem;
    min-width: 0;
  }

  .systems-panel-head h2 {
    color: var(--moss);
    font-family: "Montserrat", var(--font-body);
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    line-height: 1.25;
    text-transform: uppercase;
  }

  .panel-note,
  .panel-action {
    color: rgb(248 250 244 / 58%);
    font-size: .78rem;
    font-weight: 600;
  }

  .panel-action {
    color: #6fb7ff;
  }

  .panel-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: .48rem;
    margin-left: auto;
  }

  .detail-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.7rem;
    padding: .28rem .56rem;
    border: 1px solid rgb(91 141 239 / 22%);
    border-radius: 999px;
    background: rgb(91 141 239 / 8%);
    color: var(--moss);
    font-size: .7rem;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out), color .2s var(--ease-out);
  }

  .detail-trigger:hover,
  .detail-trigger:focus-visible {
    border-color: rgb(91 141 239 / 42%);
    background: rgb(91 141 239 / 14%);
    color: #d6ef93;
    outline: none;
  }

  /* ยืดเต็มความสูงที่เหลือของหน้า (Manager สั่ง) — dashboard เป็น flex item ที่ยืดอยู่แล้ว */
  .app-access {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
  }
  .app-access .access-list { flex: 1 1 auto; overflow-y: auto; }

  .app-access-head {
    align-items: center;
  }

  /* เรียงลงมาทีละบรรทัด — Manager สั่งให้แต่ละแอปขึ้นบรรทัดใหม่ (เดิมเป็น 4 คอลัมน์) */
  .access-list {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    padding: .9rem;
  }

  .access-row {
    position: relative;
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: .7rem .85rem;
    border: 1px solid var(--line-light);
    border-radius: 4px;
    background: var(--panel);
    color: var(--light-text);
    overflow: hidden;
    transition: border-color .22s var(--ease-out), background-color .22s var(--ease-out), transform .22s var(--ease-out);
  }

  .access-row::after {
    content: "";
    grid-column: 1 / -1;
    grid-row: 3;
    align-self: start;
    height: 1px;
    margin-top: .15rem;
    background: rgb(255 255 255 / 8%);
  }

  a.access-row:hover,
  a.access-row:focus-visible {
    border-color: rgb(91 141 239 / 45%);
    background: rgb(91 141 239 / 8%);
    outline: none;
    transform: translateY(-1px);
  }

  a.access-row:hover .access-icon,
  a.access-row:focus-visible .access-icon {
    animation: accessIconJiggle .42s var(--ease-out) both;
  }

  .access-row.is-disabled {
    opacity: .78;
  }

  .access-icon {
    flex: 0 0 auto;
    width: 2.6rem;
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 4px;
    background: linear-gradient(135deg, rgb(91 141 239 / 34%), rgb(91 141 239 / 12%));
    color: #d6ef93;
  }

  .access-row.assessment .access-icon {
    background: linear-gradient(135deg, rgb(73 137 202 / 42%), rgb(73 137 202 / 12%));
    color: #9ed0ff;
  }

  .access-row.ot-approval .access-icon {
    background: linear-gradient(135deg, rgb(201 155 65 / 38%), rgb(201 155 65 / 10%));
    color: #f2d38b;
  }

  /* ไอคอนโลโก้ของแอประบบย่อย */
  .access-icon.access-icon-logo {
    background: #ffffff;
    border-color: rgb(0 0 0 / 10%);
    padding: .18rem;
  }
  .access-icon.access-icon-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }
  /* บังคับ tile โลโก้ขาวทั้งธีมดำ+สว่าง (ทับ gradient สีของแต่ละระบบ) */
  .access-row.assessment .access-icon.access-icon-logo,
  .access-row.recruit .access-icon.access-icon-logo,
  .access-row.ot-approval .access-icon.access-icon-logo {
    background: #ffffff;
    border-color: rgb(0 0 0 / 10%);
  }

  .access-row.is-disabled .access-icon {
    background: linear-gradient(135deg, rgb(255 255 255 / 14%), rgb(255 255 255 / 5%));
    color: rgb(248 250 244 / 62%);
  }

  .access-icon svg {
    width: 1.45rem;
    height: 1.45rem;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .access-copy { flex: 1 1 auto; min-width: 0; }

  .access-copy h3 {
    color: var(--light-text);
    overflow: hidden;
    font-size: .88rem;
    font-weight: 700;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* รายละเอียดซ่อนไว้ ขึ้นเมื่อเอาเมาส์ชี้ (Manager สั่ง) — ใช้ grid 0fr->1fr
     เพื่อให้ยืดได้ลื่นโดยไม่ต้องรู้ความสูงล่วงหน้า */
  .access-copy p {
    max-height: 0;
    margin-top: 0;
    overflow: hidden;
    color: var(--muted-light);
    font-size: .75rem;
    line-height: 1.4;
    opacity: 0;
    transition: max-height .22s var(--ease-out), opacity .22s var(--ease-out), margin-top .22s var(--ease-out);
  }
  a.access-row:hover .access-copy p,
  a.access-row:focus-visible .access-copy p {
    max-height: 4rem;
    margin-top: .2rem;
    opacity: 1;
  }
  @media (prefers-reduced-motion: reduce) { .access-copy p { transition: none; } }

  .access-app-popover[hidden] {
    display: none;
  }

  .access-app-popover {
    position: fixed;
    z-index: 76;
    width: min(24rem, calc(100vw - 1.5rem));
    display: grid;
    grid-template-columns: 4.25rem minmax(0, 1fr);
    gap: .92rem;
    align-items: center;
    padding: .78rem .82rem;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 4px;
    background:
      linear-gradient(180deg, rgb(18 24 28 / 98%), rgb(8 12 14 / 98%)),
      #080c0e;
    color: var(--light-text);
    box-shadow: 0 .45rem .8rem rgb(0 0 0 / 30%);
    opacity: 0;
    pointer-events: none;
    transform: translateY(.35rem) scale(.98);
    transform-origin: 50% 100%;
    visibility: hidden;
    transition: opacity .18s var(--ease-out), transform .18s var(--ease-out), visibility .18s var(--ease-out);
  }

  .access-app-popover.is-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
    visibility: visible;
    animation: accessPopoverJiggle .42s var(--ease-out) both;
  }

  .access-app-popover::after {
    content: "";
    position: absolute;
    left: var(--popover-arrow-left, 50%);
    bottom: -.38rem;
    width: .72rem;
    height: .72rem;
    border-right: 1px solid rgb(255 255 255 / 12%);
    border-bottom: 1px solid rgb(255 255 255 / 12%);
    background: #080c0e;
    transform: translateX(-50%) rotate(45deg);
  }

  .access-app-popover.is-below {
    transform-origin: 50% 0;
  }

  .access-app-popover.is-below::after {
    top: -.38rem;
    bottom: auto;
    border: 0;
    border-top: 1px solid rgb(255 255 255 / 12%);
    border-left: 1px solid rgb(255 255 255 / 12%);
  }

  .access-popover-icon {
    width: 4.25rem;
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 4px;
    background: #ffffff;
    overflow: hidden;
  }

  .access-popover-icon img,
  .access-popover-icon svg {
    width: 90%;
    height: 90%;
    display: block;
    object-fit: contain;
  }

  .access-popover-copy {
    min-width: 0;
    display: grid;
    gap: .18rem;
  }

  .access-popover-copy strong {
    color: #ffffff;
    font-size: .88rem;
    font-weight: 800;
    line-height: 1.2;
  }

  .access-popover-copy span {
    color: rgb(248 250 244 / 68%);
    font-size: .78rem;
    line-height: 1.42;
  }

  @keyframes accessPopoverJiggle {
    0% { transform: translateY(.35rem) rotate(0deg) scale(.98); }
    34% { transform: translateY(0) rotate(-1.4deg) scale(1); }
    66% { transform: translateY(0) rotate(1.1deg) scale(1); }
    100% { transform: translateY(0) rotate(0deg) scale(1); }
  }

  @keyframes accessIconJiggle {
    0%, 100% { transform: rotate(0deg) scale(1); }
    28% { transform: rotate(-3deg) scale(1.14); }
    58% { transform: rotate(3deg) scale(1.14); }
  }

  .access-arrow {
    flex: 0 0 auto;
    width: 1.65rem;
    height: 1.65rem;
    display: grid;
    place-items: center;
    color: rgb(248 250 244 / 72%);
  }

  .access-arrow svg {
    width: 1.08rem;
    height: 1.08rem;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .search-empty {
    display: none;
    padding: 1rem 1rem 1.1rem;
    color: rgb(248 250 244 / 56%);
    font-size: .88rem;
    text-align: center;
  }

  .search-empty.is-visible {
    display: block;
  }

  /* Application Access เป็นหัวเรื่องหลักของหน้าแล้ว (hero ถูกเอาออก) */
  #systemsAppTitle { font-size: clamp(1.3rem, 2.4vw, 1.85rem); font-weight: 700; letter-spacing: .01em; }

  /* เลขลำดับหน้าแต่ละแอป — Manager สั่งให้บอก 1,2,3,... */
  .access-no {
    flex: 0 0 auto; min-width: 1.6rem;
    color: var(--muted-light); font-size: .82rem; font-weight: 800;
    font-variant-numeric: tabular-nums; text-align: center;
  }
  /* ไม่มีแผงข้างแล้ว ให้เนื้อหาหลักกินเต็มความกว้าง */
  /* ให้ footer ไปอยู่ล่างสุดของหน้าจริง ๆ: .main ยืดเต็มความสูงที่เหลือ
     แล้ว dashboard ยืดตาม footer จึงถูก margin-top:auto ดันลงไปสุด */
  body.systems-portal-body .main { display: flex; flex-direction: column; min-height: calc(100dvh - var(--topbar-h)); }
  .systems-dashboard { flex: 1 1 auto; grid-template-columns: minmax(0, 1fr); align-content: stretch; }
  /* กองหลักต้องยืดเต็มความสูง กล่อง Application Access ถึงจะยาวลงมาทั้งหน้า */
  .systems-main-stack { display: flex; flex-direction: column; min-height: 0; }
  .systems-main-stack { min-width: 0; }

  .calendar-panel,
  .weather-panel,
  .contact-panel {
    overflow: hidden;
  }

  .calendar-body {
    padding: .75rem 1rem 1rem;
  }

  .month-card {
    padding: .7rem;
    border: 1px solid rgb(255 255 255 / 8%);
    border-radius: 4px;
    background: rgb(255 255 255 / 3%);
  }

  .month-label {
    color: rgb(248 250 244 / 72%);
    font-size: .78rem;
    font-weight: 700;
  }

  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .22rem;
    margin-top: .55rem;
  }

  .calendar-cell {
    min-height: 1.78rem;
    display: grid;
    place-items: center;
    border-radius: 4px;
    color: rgb(248 250 244 / 58%);
    font-size: .7rem;
    font-weight: 700;
  }

  .calendar-cell.is-label {
    color: var(--moss);
    font-size: .62rem;
    text-transform: uppercase;
  }

  .calendar-cell.is-today {
    background: var(--moss);
    color: #071009;
  }

  .calendar-cell.is-muted {
    opacity: .22;
  }

  .weather-body {
    padding: .9rem 1rem 1rem;
  }

  /* ── นาฬิกา real-time ในกล่องปฏิทิน ── */
  .systems-clock { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; padding: 0 1rem .8rem; margin: .1rem 0 0; border-bottom: 1px solid rgb(255 255 255 / 8%); }
  .systems-clock-time { font-family: "Montserrat", var(--font-body); font-size: 1.6rem; font-weight: 800; letter-spacing: .04em; font-variant-numeric: tabular-nums; color: #ffffff; }
  .systems-clock-date { text-align: right; color: rgb(248 250 244 / 62%); font-size: .72rem; line-height: 1.35; }

  /* ── weather widget แบบ SiMenu (ไอคอน animated + พยากรณ์ 3 วัน) ── */
  .wx-row { display: grid; justify-items: center; gap: .5rem; align-items: center; padding: .3rem 0 .2rem; text-align: center; }
  .wx-icon { position: relative; width: 118px; height: 92px; display: block; margin: 0 auto; isolation: isolate; }
  .weather-scene-glow { position: absolute; inset: 7px 10px 1px; z-index: -1; border-radius: 999px; background: radial-gradient(circle at 36% 30%, rgb(251 191 36 / 26%), transparent 34%), radial-gradient(circle at 72% 64%, rgb(79 70 229 / 14%), transparent 44%), linear-gradient(135deg, rgb(234 242 255 / 95%), rgb(255 255 255 / 20%)); animation: wxGlow 5.8s ease-in-out infinite; }
  .wx-icon .sun { position: absolute; left: 20px; top: 8px; width: 52px; height: 52px; border-radius: 50%; background: #fbbf24; box-shadow: 0 0 0 10px rgb(251 191 36 / 18%); animation: wxSun 3.2s ease-in-out infinite; }
  .wx-icon .cloud { position: absolute; left: 34px; bottom: 15px; width: 72px; height: 36px; border-radius: 999px; background: #eef3f9; border: 1px solid rgb(203 213 225 / 88%); box-shadow: 0 16px 26px rgb(16 23 42 / 10%); animation: wxCloud 6s ease-in-out infinite; }
  .wx-icon .cloud::before, .wx-icon .cloud::after { content: ""; position: absolute; bottom: 11px; border-radius: 50%; background: inherit; border: inherit; border-bottom: 0; }
  .wx-icon .cloud::before { left: 11px; width: 31px; height: 31px; }
  .wx-icon .cloud::after { right: 12px; width: 24px; height: 24px; }
  .wx-icon.wx-icon--clear .cloud { opacity: .36; }
  .wx-icon.wx-icon--cloudy .sun { opacity: .82; }
  .weather-wind { position: absolute; left: 8px; width: 34px; height: 2px; border-radius: 999px; background: linear-gradient(90deg, transparent, rgb(79 70 229 / 32%), transparent); opacity: 0; animation: wxWind 4.4s ease-in-out infinite; }
  .weather-wind.one { top: 60px; }
  .weather-wind.two { top: 72px; width: 48px; animation-delay: 1.2s; }
  .wx-icon.wx-icon--rain .weather-wind, .wx-icon.wx-icon--storm .weather-wind { background: linear-gradient(90deg, transparent, rgb(91 143 214 / 42%), transparent); }
  .wx-icon--rain .sun, .wx-icon--storm .sun, .wx-icon--snow .sun { opacity: .55; filter: saturate(.7); }
  .wx-icon--rain .cloud, .wx-icon--storm .cloud { background: #d7e2ee; border-color: #becbdb; }
  .rain-drop { position: absolute; bottom: -6px; width: 2px; height: 9px; border-radius: 999px; background: #5b8fd6; opacity: 0; z-index: 2; animation: wxRain 1.1s linear infinite; }
  .snow-flake { position: absolute; bottom: -2px; width: 4px; height: 4px; border-radius: 50%; background: #fff; border: 1px solid #d8e0ec; opacity: 0; z-index: 2; animation: wxSnow 2.4s ease-in infinite; }
  .lightning-bolt { position: absolute; right: 2px; bottom: -4px; z-index: 3; font-size: 14px; line-height: 1; animation: wxBolt 2.6s ease-in-out infinite; }
  @keyframes wxSun { 0%, 100% { box-shadow: 0 0 0 10px rgb(251 191 36 / 18%); transform: scale(1); } 50% { box-shadow: 0 0 0 15px rgb(251 191 36 / 10%); transform: scale(1.05); } }
  @keyframes wxGlow { 0%, 100% { opacity: .78; transform: scale(1); } 50% { opacity: 1; transform: scale(1.04); } }
  @keyframes wxCloud { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(4px); } }
  @keyframes wxWind { 0%, 22%, 100% { opacity: 0; transform: translateX(-10px); } 42%, 68% { opacity: .9; transform: translateX(22px); } }
  @keyframes wxRain { 0% { transform: translateY(-2px); opacity: 0; } 30% { opacity: .9; } 100% { transform: translateY(14px); opacity: 0; } }
  @keyframes wxSnow { 0% { transform: translateY(-2px) translateX(0); opacity: 0; } 20% { opacity: .95; } 100% { transform: translateY(16px) translateX(3px); opacity: 0; } }
  @keyframes wxBolt { 0%, 88%, 100% { opacity: 0; transform: scale(.9); } 90%, 96% { opacity: 1; transform: scale(1.05); } }
  .wx-copy { display: grid; justify-items: center; gap: .3rem; }
  .wx-temp { margin: 0; font-family: "Montserrat", var(--font-body); font-size: 2rem; font-weight: 900; letter-spacing: -.02em; line-height: 1; color: #ffffff; }
  .wx-text { margin: 0; font-size: .82rem; line-height: 1.45; color: rgb(248 250 244 / 66%); }
  .weather-summary { color: inherit; }
  .wx-forecast { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-top: .85rem; text-align: center; }
  .wx-forecast > div { min-height: 3.4rem; display: grid; align-content: center; gap: .22rem; padding: .4rem .25rem; border-radius: 4px; background: rgb(255 255 255 / 5%); }
  .wx-fc-day { color: rgb(248 250 244 / 62%); font-size: .74rem; font-weight: 800; line-height: 1.1; }
  .wx-fc-temps { display: inline-flex; align-items: baseline; justify-content: center; gap: .2rem; font-size: .88rem; line-height: 1; }
  .wx-fc-high { color: #ffffff; font-size: .92rem; font-weight: 900; }
  .wx-fc-low { color: rgb(248 250 244 / 55%); font-size: .76rem; font-weight: 800; }
  @media (prefers-reduced-motion: reduce) {
    .weather-scene-glow, .wx-icon .sun, .wx-icon .cloud, .weather-wind, .rain-drop, .snow-flake, .lightning-bolt { animation: none !important; }
    .access-app-popover.is-visible,
    a.access-row:hover .access-icon,
    a.access-row:focus-visible .access-icon { animation: none !important; }
  }

  .contact-panel {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: .72rem;
    align-items: center;
    min-height: 3.55rem;
    padding: .62rem .85rem;
    border-color: rgb(255 255 255 / 10%);
    background:
      linear-gradient(180deg, rgb(255 255 255 / 5%), rgb(255 255 255 / 2.5%)),
      rgb(8 11 13 / 82%);
    color: var(--light-text);
  }

  .contact-symbol {
    width: 2.45rem;
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    background: transparent;
    filter: drop-shadow(0 .24rem .42rem rgb(0 0 0 / 46%));
  }

  .contact-symbol svg {
    width: 2rem;
    height: 2rem;
    display: block;
    overflow: visible;
  }

  .contact-person-shadow {
    fill: rgb(0 0 0 / 38%);
  }

  .contact-person-head {
    fill: #eef2f7;
    stroke: rgb(255 255 255 / 62%);
    stroke-width: .9;
  }

  .contact-person-shirt {
    fill: #020304;
    stroke: rgb(255 255 255 / 26%);
    stroke-width: .9;
  }

  .contact-person-collar {
    fill: none;
    stroke: rgb(255 255 255 / 62%);
    stroke-width: .85;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .contact-panel h2 {
    color: #ffffff;
    font-size: .86rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .contact-panel p {
    margin-top: .08rem;
    color: rgb(248 250 244 / 58%);
    font-size: .74rem;
    line-height: 1.4;
  }

  /* ดันไปอยู่ล่างสุดของจอเสมอ ไม่ให้ลอยขึ้นมาติดรายการแอปเวลามีของน้อย
     (Manager สั่ง 2026-08-19) — margin-top:auto ใช้ได้เพราะ .systems-dashboard เป็น grid
     ที่สูงเต็มพื้นที่อยู่แล้ว */
  .systems-footer {
    grid-column: 1 / -1;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: auto;
    padding: 1.1rem .1rem .2rem;
    color: rgb(248 250 244 / 42%);
    font-size: .72rem;
  }

  .systems-detail-modal[hidden],
  .detail-panel[hidden] {
    display: none;
  }

  .systems-detail-modal {
    position: fixed;
    inset: 0;
    z-index: 78;
    display: grid;
    place-items: center;
    padding: clamp(1rem, 3vw, 2rem);
  }

  .detail-backdrop {
    position: absolute;
    inset: 0;
    border: 0;
    background: rgb(0 0 0 / 68%);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    cursor: pointer;
  }

  .detail-dialog {
    position: relative;
    width: min(100%, 46rem);
    max-height: min(82vh, 45rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 4px;
    background:
      linear-gradient(180deg, rgb(255 255 255 / 6%), rgb(255 255 255 / 3%)),
      #080b0d;
    box-shadow: 0 1.6rem 4rem rgb(0 0 0 / 42%);
  }

  .detail-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .85rem;
    padding: 1rem 1.05rem .88rem;
    border-bottom: 1px solid rgb(255 255 255 / 9%);
  }

  .detail-title {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .detail-close {
    width: 2.1rem;
    height: 2.1rem;
    display: grid;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 10%);
    border-radius: 50%;
    background: rgb(255 255 255 / 4%);
    color: rgb(248 250 244 / 72%);
    cursor: pointer;
  }

  .detail-close:hover,
  .detail-close:focus-visible {
    border-color: rgb(91 141 239 / 42%);
    color: var(--moss);
    outline: none;
  }

  .detail-close svg {
    width: 1rem;
    height: 1rem;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.9;
    stroke-linecap: round;
  }

  .detail-body {
    overflow: auto;
    padding: 1rem;
  }

  .detail-panel {
    min-width: 0;
  }

  .detail-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .8rem;
    margin-bottom: .8rem;
    color: rgb(248 250 244 / 58%);
    font-size: .78rem;
    font-weight: 700;
  }

  .detail-meta strong {
    color: #ffffff;
    font-size: .95rem;
    font-weight: 800;
  }

  .detail-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .42rem;
  }

  .detail-calendar-cell {
    min-height: 3.25rem;
    display: grid;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 8%);
    border-radius: 4px;
    background: rgb(255 255 255 / 3%);
    color: rgb(248 250 244 / 66%);
    font-size: .86rem;
    font-weight: 800;
  }

  .detail-calendar-cell.is-label {
    min-height: 2rem;
    border-color: transparent;
    background: transparent;
    color: var(--moss);
    font-size: .72rem;
  }

  .detail-calendar-cell.is-today {
    border-color: rgb(91 141 239 / 45%);
    background: var(--moss);
    color: #071009;
  }

  .detail-calendar-cell.is-muted {
    opacity: .22;
  }

  .detail-subtitle {
    margin-bottom: .8rem;
    color: rgb(248 250 244 / 58%);
    font-size: .78rem;
    font-weight: 700;
  }

  .detail-hourly-list {
    display: flex;
    flex-direction: column;
    gap: .55rem;
  }

  .detail-hourly-item {
    display: grid;
    grid-template-columns: 4.2rem minmax(0, 1fr) auto;
    align-items: center;
    gap: .8rem;
    padding: .72rem .78rem;
    border: 1px solid rgb(255 255 255 / 8%);
    border-radius: 4px;
    background: rgb(255 255 255 / 3%);
  }

  .detail-hourly-time {
    color: #6fb7ff;
    font-size: .82rem;
    font-weight: 800;
  }

  .detail-hourly-main strong {
    display: block;
    color: #ffffff;
    font-size: .9rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .detail-hourly-main span {
    display: block;
    margin-top: .12rem;
    color: rgb(248 250 244 / 56%);
    font-size: .74rem;
    line-height: 1.35;
  }

  .detail-hourly-rain {
    color: var(--moss);
    font-size: .76rem;
    font-weight: 800;
    white-space: nowrap;
  }

  .detail-empty {
    padding: 1.4rem 1rem;
    border: 1px dashed rgb(255 255 255 / 14%);
    border-radius: 4px;
    color: rgb(248 250 244 / 58%);
    font-size: .84rem;
    text-align: center;
  }

  html[data-theme="light"] body.systems-portal-body .systems-dashboard {
    width: min(100%, 93.5rem);
  }

  html[data-theme="light"] body.systems-portal-body .systems-search {
    border-color: rgb(255 255 255 / 16%);
    background: rgb(255 255 255 / 6%);
    color: #f8faf4;
  }

  html[data-theme="light"] body.systems-portal-body .systems-search input {
    color: #ffffff;
  }

  html[data-theme="light"] body.systems-portal-body .systems-hero {
    min-height: clamp(8.6rem, 17vh, 11rem);
    border-color: transparent;
    background:
      linear-gradient(90deg, rgb(246 247 243 / 96%), rgb(246 247 243 / 84%)),
      url("{{ asset('assets/insight/systems-intranet-bg.png') }}");
    background-size: cover;
    background-position: center right;
    box-shadow: none;
  }

  html[data-theme="light"] body.systems-portal-body .systems-hero::before {
    inset: .55rem auto .55rem 0;
    width: 3px;
    background: var(--moss);
    border-radius: 999px;
    z-index: 0;
  }

  html[data-theme="light"] body.systems-portal-body .systems-hero-copy {
    position: relative;
    z-index: 1;
    padding: clamp(1rem, 2.1vw, 1.55rem) clamp(1.1rem, 2.8vw, 2rem);
  }

  html[data-theme="light"] body.systems-portal-body .systems-kicker {
    color: var(--moss);
    letter-spacing: .12em;
  }

  html[data-theme="light"] body.systems-portal-body .systems-hero h1 {
    color: #19202a;
    font-size: clamp(2rem, 3.2vw, 3rem);
    line-height: 1.08;
    text-transform: none;
  }

  html[data-theme="light"] body.systems-portal-body .systems-hero h1 strong {
    color: var(--moss);
  }

  html[data-theme="light"] body.systems-portal-body .systems-hero p {
    width: min(100%, 32rem);
    margin-top: .6rem;
    color: rgb(25 32 42 / 76%);
  }

  html[data-theme="light"] body.systems-portal-body .systems-panel {
    border-color: rgb(30 58 138 / 13%);
    background:
      linear-gradient(180deg, rgb(255 255 255 / 96%), rgb(251 252 248 / 96%)),
      #ffffff;
    box-shadow: 0 .9rem 2.2rem rgb(37 46 29 / 7%);
    -webkit-backdrop-filter: none;
    backdrop-filter: none;
  }

  html[data-theme="light"] body.systems-portal-body .systems-panel-head {
    border-bottom-color: rgb(30 58 138 / 10%);
  }

  html[data-theme="light"] body.systems-portal-body .systems-panel-head h2 {
    color: #171c16;
    letter-spacing: 0;
    text-transform: none;
  }

  html[data-theme="light"] body.systems-portal-body .panel-note,
  html[data-theme="light"] body.systems-portal-body .panel-action {
    color: rgb(23 28 22 / 62%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-trigger {
    border-color: rgb(30 58 138 / 20%);
    background: rgb(30 58 138 / 8%);
    color: #1e3a8a;
  }

  html[data-theme="light"] body.systems-portal-body .detail-trigger:hover,
  html[data-theme="light"] body.systems-portal-body .detail-trigger:focus-visible {
    border-color: rgb(30 58 138 / 38%);
    background: rgb(30 58 138 / 13%);
    color: #1e3a8a;
  }

  html[data-theme="light"] body.systems-portal-body .access-row {
    border-color: rgb(30 58 138 / 13%);
    background:
      linear-gradient(135deg, #ffffff 0%, #fbfcf7 100%);
    color: #171c16;
    box-shadow: inset 0 0 0 1px rgb(255 255 255 / 70%);
  }

  html[data-theme="light"] body.systems-portal-body .access-row::after {
    background: rgb(30 58 138 / 10%);
  }

  html[data-theme="light"] body.systems-portal-body a.access-row:hover,
  html[data-theme="light"] body.systems-portal-body a.access-row:focus-visible {
    border-color: rgb(30 58 138 / 36%);
    background:
      linear-gradient(135deg, rgb(255 255 255 / 100%), rgb(30 58 138 / 8%));
    box-shadow: 0 .85rem 1.8rem rgb(37 46 29 / 10%);
  }

  html[data-theme="light"] body.systems-portal-body .access-app-popover {
    border-color: rgb(30 58 138 / 13%);
    background: #ffffff;
    color: #171c16;
    box-shadow: 0 .45rem .8rem rgb(37 46 29 / 12%);
  }

  html[data-theme="light"] body.systems-portal-body .access-app-popover::after {
    border-right-color: rgb(30 58 138 / 13%);
    border-bottom-color: rgb(30 58 138 / 13%);
    background: #ffffff;
  }

  html[data-theme="light"] body.systems-portal-body .access-app-popover.is-below::after {
    border-top-color: rgb(30 58 138 / 13%);
    border-left-color: rgb(30 58 138 / 13%);
  }

  html[data-theme="light"] body.systems-portal-body .access-popover-icon {
    border-color: rgb(30 58 138 / 12%);
    background: #ffffff;
  }

  html[data-theme="light"] body.systems-portal-body .access-popover-copy strong {
    color: #171c16;
  }

  html[data-theme="light"] body.systems-portal-body .access-popover-copy span {
    color: rgb(23 28 22 / 68%);
  }

  html[data-theme="light"] body.systems-portal-body .access-icon {
    border-color: rgb(30 58 138 / 20%);
    background: linear-gradient(135deg, rgb(30 58 138 / 16%), rgb(30 58 138 / 6%));
    color: #1e3a8a;
  }

  /* ไอคอนโลโก้ 5ส: พื้นขาวเต็มกรอบเหมือนธีมดำ (ไม่ให้ gradient เขียวของ light theme ทับ) */
  html[data-theme="light"] body.systems-portal-body .access-icon.access-icon-logo {
    background: #ffffff;
    border-color: rgb(0 0 0 / 12%);
  }

  html[data-theme="light"] body.systems-portal-body .access-row.assessment .access-icon {
    border-color: rgb(47 105 168 / 18%);
    background: linear-gradient(135deg, rgb(47 105 168 / 14%), rgb(47 105 168 / 5%));
    color: #2f69a8;
  }

  html[data-theme="light"] body.systems-portal-body .access-row.ot-approval .access-icon {
    border-color: rgb(157 111 27 / 20%);
    background: linear-gradient(135deg, rgb(201 155 65 / 16%), rgb(201 155 65 / 5%));
    color: #8b611a;
  }
  /* ระบบที่เป็น tile โลโก้: บังคับขาวใน light theme ด้วย */
  html[data-theme="light"] body.systems-portal-body .access-row.assessment .access-icon.access-icon-logo,
  html[data-theme="light"] body.systems-portal-body .access-row.recruit .access-icon.access-icon-logo,
  html[data-theme="light"] body.systems-portal-body .access-row.ot-approval .access-icon.access-icon-logo {
    background: #ffffff;
    border-color: rgb(0 0 0 / 12%);
  }

  html[data-theme="light"] body.systems-portal-body .access-row.is-disabled .access-icon {
    border-color: rgb(30 58 138 / 12%);
    background: #f0f2ed;
    color: rgb(23 28 22 / 48%);
  }

  html[data-theme="light"] body.systems-portal-body .access-copy h3,
  html[data-theme="light"] body.systems-portal-body .weather-temp,
  html[data-theme="light"] body.systems-portal-body .weather-metric strong,
  html[data-theme="light"] body.systems-portal-body .contact-panel h2 {
    color: #171c16;
  }

  html[data-theme="light"] body.systems-portal-body .access-copy p,
  html[data-theme="light"] body.systems-portal-body .month-label,
  html[data-theme="light"] body.systems-portal-body .weather-summary,
  html[data-theme="light"] body.systems-portal-body .weather-metric span,
  html[data-theme="light"] body.systems-portal-body .contact-panel p,
  html[data-theme="light"] body.systems-portal-body .search-empty,
  html[data-theme="light"] body.systems-portal-body .systems-footer {
    color: rgb(23 28 22 / 62%);
  }

  html[data-theme="light"] body.systems-portal-body .access-arrow {
    color: rgb(23 28 22 / 62%);
  }

  html[data-theme="light"] body.systems-portal-body .month-card,
  html[data-theme="light"] body.systems-portal-body .weather-metric {
    border-color: rgb(30 58 138 / 10%);
    background: #f8faf5;
  }

  html[data-theme="light"] body.systems-portal-body .calendar-cell {
    color: rgb(23 28 22 / 56%);
  }

  html[data-theme="light"] body.systems-portal-body .calendar-cell.is-today {
    color: #ffffff;
  }

  /* light theme: clock + weather widget เป็นตัวอักษรเข้มบนพื้นสว่าง */
  html[data-theme="light"] body.systems-portal-body .systems-clock { border-bottom-color: rgb(23 28 22 / 10%); }
  html[data-theme="light"] body.systems-portal-body .systems-clock-time,
  html[data-theme="light"] body.systems-portal-body .wx-temp,
  html[data-theme="light"] body.systems-portal-body .wx-fc-high { color: #1f2937; }
  html[data-theme="light"] body.systems-portal-body .systems-clock-date,
  html[data-theme="light"] body.systems-portal-body .wx-text,
  html[data-theme="light"] body.systems-portal-body .wx-fc-day { color: #64748b; }
  html[data-theme="light"] body.systems-portal-body .wx-fc-low { color: #94a3b8; }
  html[data-theme="light"] body.systems-portal-body .wx-forecast > div { background: #f1f4f9; }

  html[data-theme="light"] body.systems-portal-body .contact-panel {
    border-color: rgb(30 58 138 / 13%);
    background: #ffffff;
  }

  html[data-theme="light"] body.systems-portal-body .contact-symbol {
    filter: drop-shadow(0 .2rem .35rem rgb(17 24 39 / 20%));
  }

  html[data-theme="light"] body.systems-portal-body .contact-person-head {
    fill: #e5e7eb;
    stroke: rgb(17 24 39 / 12%);
  }

  html[data-theme="light"] body.systems-portal-body .contact-person-shirt {
    fill: #050607;
    stroke: rgb(17 24 39 / 16%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-backdrop {
    background: rgb(12 16 18 / 42%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-dialog {
    border-color: rgb(30 58 138 / 13%);
    background: #ffffff;
    box-shadow: 0 1.6rem 4rem rgb(37 46 29 / 18%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-head {
    border-bottom-color: rgb(30 58 138 / 10%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-title,
  html[data-theme="light"] body.systems-portal-body .detail-meta strong,
  html[data-theme="light"] body.systems-portal-body .detail-hourly-main strong {
    color: #171c16;
  }

  html[data-theme="light"] body.systems-portal-body .detail-close,
  html[data-theme="light"] body.systems-portal-body .detail-meta,
  html[data-theme="light"] body.systems-portal-body .detail-subtitle,
  html[data-theme="light"] body.systems-portal-body .detail-hourly-main span,
  html[data-theme="light"] body.systems-portal-body .detail-empty {
    color: rgb(23 28 22 / 62%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-close,
  html[data-theme="light"] body.systems-portal-body .detail-calendar-cell,
  html[data-theme="light"] body.systems-portal-body .detail-hourly-item {
    border-color: rgb(30 58 138 / 10%);
    background: #f8faf5;
  }

  html[data-theme="light"] body.systems-portal-body .detail-calendar-cell {
    color: rgb(23 28 22 / 68%);
  }

  html[data-theme="light"] body.systems-portal-body .detail-calendar-cell.is-label {
    border-color: transparent;
    background: transparent;
    color: var(--moss);
  }

  html[data-theme="light"] body.systems-portal-body .detail-calendar-cell.is-today {
    border-color: rgb(30 58 138 / 36%);
    background: var(--moss);
    color: #ffffff;
  }

  html[data-theme="light"] body.systems-portal-body .detail-empty {
    border-color: rgb(30 58 138 / 16%);
  }

  @media (max-width: 1180px) {
    .systems-dashboard {
      grid-template-columns: 1fr;
    }

    .access-list {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .systems-side-stack {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .contact-panel {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 860px) {
    body.systems-portal-body .main {
      padding-top: 1rem;
    }

    .systems-hero {
      min-height: clamp(9.5rem, 28vh, 12rem);
    }

    .access-list {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .access-row {
      grid-template-columns: 3rem minmax(0, 1fr) auto;
      min-height: 9.4rem;
    }

    .systems-side-stack {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 560px) {
    .systems-dashboard {
      width: 100%;
    }

    .systems-hero h1 {
      font-size: clamp(1.5rem, 7.2vw, 2.35rem);
    }

    .access-row {
      grid-template-columns: 2.8rem minmax(0, 1fr);
      min-height: auto;
    }

    .access-list {
      grid-template-columns: 1fr;
    }

    .access-arrow {
      display: none;
    }

    .weather-metrics {
      grid-template-columns: 1fr;
    }

    .panel-actions {
      flex-wrap: wrap;
      gap: .35rem;
    }

    .detail-dialog {
      max-height: 86vh;
    }

    .detail-hourly-item {
      grid-template-columns: 3.6rem minmax(0, 1fr);
    }

    .detail-hourly-rain {
      grid-column: 2;
      justify-self: start;
    }

    .detail-calendar-cell {
      min-height: 2.4rem;
      font-size: .76rem;
    }

    .systems-footer {
      flex-direction: column;
      gap: .25rem;
    }
  }
@endsection

@section('content')
  @php($recruitVisible = (bool) config('insight.modules.recruit_visible', false))
  <section class="systems-dashboard" aria-labelledby="systemsAppTitle">
    <div class="systems-main-stack">
      <section class="systems-panel app-access" aria-labelledby="systemsAppTitle">
        <div class="systems-panel-head app-access-head">
          <span class="panel-title-stack">
            <h1 id="systemsAppTitle" data-i18n="systems.appAccess">Application Access</h1>
            <span class="panel-note" data-i18n="{{ $recruitVisible ? 'systems.availableNowWithRecruit' : 'systems.availableNow' }}">{{ $recruitVisible ? '4 ระบบหลัก' : '3 ระบบหลัก' }}</span>
          </span>
        </div>

        <div class="access-list">
          <a class="access-row ot-approval nav-go" href="{{ route('ot-approval.home') }}" data-app-row data-search-text="time leave approval ot overtime bplus v74 เวลา ลา ค่าล่วงเวลา ขออนุมัติ foreman supervisor มาตรา 75">
            <span class="access-no" aria-hidden="true">1</span>
            <span class="access-icon access-icon-logo" aria-hidden="true">
              <img src="{{ asset('assets/systems/time-leave-approval.png').'?v=1' }}" alt="">
            </span>
            <span class="access-copy">
              <h3 data-i18n="systems.otApprovalName">TIME &amp; LEAVE APPROVAL</h3>
              <p data-i18n="systems.otApprovalDesc">ระบบขอและอนุมัติ OT กับการลา พร้อมจัดเตรียมเอกสารสำหรับนำเข้า Bplus</p>
            </span>
            <span class="access-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg></span>
          </a>

          <a class="access-row five-s-area nav-go" href="{{ route('area5s.home') }}" data-app-row data-search-text="supavut 5s area 5s cleaning area พื้นที่ทำความสะอาด งานแม่บ้าน จุดรับผิดชอบ layout">
            <span class="access-no" aria-hidden="true">2</span>
            <span class="access-icon access-icon-logo" aria-hidden="true">
              <img src="{{ asset('assets/area5s/logo-5s.png').'?v=2' }}" alt="">
            </span>
            <span class="access-copy">
              <h3 data-i18n="systems.devName">SUPAVUT 5S AREA</h3>
              <p data-i18n="systems.devDesc">พื้นที่สำหรับจัดการงานทำความสะอาดและจุดรับผิดชอบภายในองค์กร</p>
            </span>
            <span class="access-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg></span>
          </a>

          <a class="access-row assessment nav-go" href="{{ route('assessment.index') }}" data-app-row data-search-text="supavut assessment เครื่องมือประเมินผล คะแนนพนักงาน">
            <span class="access-no" aria-hidden="true">3</span>
            <span class="access-icon access-icon-logo" aria-hidden="true">
              <img src="{{ asset('assets/systems/assessment.png') }}" alt="">
            </span>
            <span class="access-copy">
              <h3 data-i18n="systems.assessmentName">SUPAVUT ASSESSMENT</h3>
              <p data-i18n="systems.assessmentDesc">เครื่องมือประเมินผลและจัดการคะแนนพนักงาน</p>
            </span>
            <span class="access-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg></span>
          </a>

          @if ($recruitVisible)
            <a class="access-row recruit nav-go" href="{{ route('recruit.index') }}" data-app-row data-search-text="recruit system ระบบจัดการคำขออัตรากำลังคน">
              <span class="access-no" aria-hidden="true">4</span>
              <span class="access-icon access-icon-logo" aria-hidden="true">
                <img src="{{ asset('assets/systems/recruit.png') }}" alt="">
              </span>
              <span class="access-copy">
                <h3 data-i18n="systems.recruitName">RECRUIT SYSTEM</h3>
                <p data-i18n="systems.recruitDesc">ระบบจัดการคำขออัตรากำลังคนภายในองค์กร</p>
              </span>
              <span class="access-arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg></span>
            </a>
          @endif

        </div>

        <p class="search-empty" data-search-empty data-i18n="systems.noSearchResults">ไม่พบระบบที่ตรงกับคำค้นหา</p>
      </section>
    </div>


    {{-- แผงปฏิทินและภูมิอากาศถูกเอาออก — Manager สั่งให้เหลือแค่รายการแอป --}}

    {{-- แผงติดต่อ HR/IT ถูกเอาออกตามที่ Manager สั่ง --}}

    <footer class="systems-footer">
      <span>© {{ date('Y') }} Supavut Group</span>
      <span data-i18n="systems.footerVersion">Intranet v2.6.0</span>
    </footer>
  </section>

  <div class="access-app-popover" id="systemsAppPopover" data-app-popover role="tooltip" aria-hidden="true" hidden>
    <span class="access-popover-icon" data-app-popover-icon aria-hidden="true"></span>
    <span class="access-popover-copy">
      <strong data-app-popover-title></strong>
      <span data-app-popover-desc></span>
    </span>
  </div>

  <div class="systems-detail-modal" data-detail-modal hidden aria-hidden="true">
    <button class="detail-backdrop" type="button" data-detail-close tabindex="-1" aria-hidden="true"></button>
    <section class="detail-dialog" role="dialog" aria-modal="true" aria-labelledby="systemsDetailTitle" tabindex="-1">
      <header class="detail-head">
        <h2 class="detail-title" id="systemsDetailTitle" data-detail-title data-i18n="systems.detailView">ดูรายละเอียด</h2>
        <button class="detail-close" type="button" data-detail-close data-i18n-aria="systems.detailClose" aria-label="ปิดรายละเอียด">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </header>
      <div class="detail-body">
        <section class="detail-panel" data-detail-panel="calendar" hidden>
          <div class="detail-meta">
            <strong data-detail-calendar-month>--</strong>
            <span data-detail-calendar-today>--</span>
          </div>
          <div class="detail-calendar-grid" data-detail-calendar-grid aria-hidden="true"></div>
        </section>

        <section class="detail-panel" data-detail-panel="weather" hidden>
          <p class="detail-subtitle" data-i18n="systems.weatherHourlyTitle">สภาพอากาศรายชั่วโมง</p>
          <div class="detail-hourly-list" data-weather-hourly-list>
            <p class="detail-empty" data-i18n="systems.weatherHourlyEmpty">ยังไม่มีข้อมูลรายชั่วโมง</p>
          </div>
        </section>
      </div>
    </section>
  </div>

  <script>
    'use strict';

    (function () {
      var searchInput = document.querySelector('[data-systems-search]');
      var appRows = Array.prototype.slice.call(document.querySelectorAll('[data-app-row]'));
      var appPopover = document.querySelector('[data-app-popover]');
      var appPopoverIcon = appPopover ? appPopover.querySelector('[data-app-popover-icon]') : null;
      var appPopoverTitle = appPopover ? appPopover.querySelector('[data-app-popover-title]') : null;
      var appPopoverDesc = appPopover ? appPopover.querySelector('[data-app-popover-desc]') : null;
      var searchEmpty = document.querySelector('[data-search-empty]');
      var detailModal = document.querySelector('[data-detail-modal]');
      var detailDialog = detailModal ? detailModal.querySelector('.detail-dialog') : null;
      var detailTitle = detailModal ? detailModal.querySelector('[data-detail-title]') : null;
      var detailPanels = detailModal ? Array.prototype.slice.call(detailModal.querySelectorAll('[data-detail-panel]')) : [];
      var weatherData = null;
      var activeAppRow = null;
      var appPopoverTimer = null;

      var labels = {
        th: {
          locale: 'th-TH',
          weekdays: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],
          detailTitle: 'รายละเอียด',
          detailCalendarTitle: 'ปฏิทินแบบขยาย',
          detailWeatherTitle: 'รายละเอียดภูมิอากาศ',
          todayLabel: 'วันนี้',
          weatherLoading: 'กำลังโหลด',
          weatherLoaded: 'อัปเดตแล้ว',
          weatherFailed: 'เชื่อมต่อไม่ได้',
          weatherHourlyEmpty: 'ยังไม่มีข้อมูลรายชั่วโมง',
          rainChance: 'โอกาสฝน',
          humidityWord: 'ความชื้น',
          clear: 'ท้องฟ้าแจ่มใส',
          cloudy: 'มีเมฆบางส่วน',
          fog: 'มีหมอก',
          rain: 'มีฝน',
          storm: 'มีพายุฝน',
          defaultWeather: 'สภาพอากาศปัจจุบัน'
        },
        en: {
          locale: 'en-US',
          weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
          detailTitle: 'Details',
          detailCalendarTitle: 'Expanded calendar',
          detailWeatherTitle: 'Weather details',
          todayLabel: 'Today',
          weatherLoading: 'Loading',
          weatherLoaded: 'Updated',
          weatherFailed: 'Unavailable',
          weatherHourlyEmpty: 'Hourly weather is not available yet.',
          rainChance: 'Rain',
          humidityWord: 'humidity',
          clear: 'Clear sky',
          cloudy: 'Partly cloudy',
          fog: 'Foggy',
          rain: 'Rain nearby',
          storm: 'Thunderstorm',
          defaultWeather: 'Current weather'
        },
        my: {
          locale: 'my-MM',
          weekdays: ['တနင်္ဂ', 'တနင်္လာ', 'အင်္ဂါ', 'ဗုဒ္ဓ', 'ကြာသ', 'သောကြာ', 'စနေ'],
          detailTitle: 'အသေးစိတ်',
          detailCalendarTitle: 'ပြက္ခဒိန်အပြည့်',
          detailWeatherTitle: 'ရာသီဥတုအသေးစိတ်',
          todayLabel: 'ယနေ့',
          weatherLoading: 'ဖွင့်နေသည်',
          weatherLoaded: 'အသစ်တင်ပြီး',
          weatherFailed: 'ချိတ်ဆက်မရပါ',
          weatherHourlyEmpty: 'နာရီအလိုက်ရာသီဥတု မရှိသေးပါ။',
          rainChance: 'မိုး',
          humidityWord: 'စိုထိုင်းဆ',
          clear: 'ကောင်းကင်ကြည်လင်',
          cloudy: 'တိမ်အနည်းငယ်',
          fog: 'မြူထူ',
          rain: 'မိုးရွာနေသည်',
          storm: 'မိုးကြိုးမုန်တိုင်း',
          defaultWeather: 'လက်ရှိရာသီဥတု'
        }
      };

      function activeLang() {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return labels[lang] ? lang : 'th';
      }

      function activeLabels() {
        return labels[activeLang()];
      }

      function normalize(value) {
        return (value || '').toString().toLowerCase().trim();
      }

      function syncAppTooltips() {
        appRows.forEach(function (row) {
          var title = row.querySelector('.access-copy h3');
          var desc = row.querySelector('.access-copy p');
          if (!desc) return;
          var titleText = title ? title.textContent.replace(/\s+/g, ' ').trim() : '';
          var descText = desc.textContent.replace(/\s+/g, ' ').trim();
          desc.removeAttribute('title');
          row.removeAttribute('title');
          row.setAttribute('aria-label', titleText ? titleText + ': ' + descText : descText);
        });
      }

      function appText(row, selector) {
        var node = row ? row.querySelector(selector) : null;
        return node ? node.textContent.replace(/\s+/g, ' ').trim() : '';
      }

      function setAppPopoverIcon(row) {
        var icon = row ? row.querySelector('.access-icon img, .access-icon svg') : null;
        if (!appPopoverIcon) return;
        appPopoverIcon.innerHTML = '';
        if (!icon) return;
        var clonedIcon = icon.cloneNode(true);
        clonedIcon.removeAttribute('id');
        clonedIcon.setAttribute('aria-hidden', 'true');
        if (clonedIcon.tagName && clonedIcon.tagName.toLowerCase() === 'img') {
          clonedIcon.setAttribute('alt', '');
        }
        appPopoverIcon.appendChild(clonedIcon);
      }

      function positionAppPopover(row) {
        if (!appPopover || !row || appPopover.hidden) return;
        var rowRect = row.getBoundingClientRect();
        var popRect = appPopover.getBoundingClientRect();
        var gap = 10;
        var margin = 12;
        var top = rowRect.top - popRect.height - gap;
        var arrowAtBottom = true;

        if (top < margin) {
          top = rowRect.bottom + gap;
          arrowAtBottom = false;
        }

        top = Math.max(margin, Math.min(top, window.innerHeight - popRect.height - margin));

        var left = rowRect.left + (rowRect.width - popRect.width) / 2;
        left = Math.max(margin, Math.min(left, window.innerWidth - popRect.width - margin));

        var arrowLeft = rowRect.left + (rowRect.width / 2) - left;
        arrowLeft = Math.max(18, Math.min(arrowLeft, popRect.width - 18));

        appPopover.style.left = left + 'px';
        appPopover.style.top = top + 'px';
        appPopover.style.setProperty('--popover-arrow-left', arrowLeft + 'px');
        appPopover.classList.toggle('is-below', !arrowAtBottom);
      }

      function showAppPopover(row) {
        if (!appPopover || !row || row.hidden) return;
        window.clearTimeout(appPopoverTimer);
        activeAppRow = row;
        if (appPopoverTitle) appPopoverTitle.textContent = appText(row, '.access-copy h3');
        if (appPopoverDesc) appPopoverDesc.textContent = appText(row, '.access-copy p');
        setAppPopoverIcon(row);
        appPopover.hidden = false;
        appPopover.setAttribute('aria-hidden', 'false');
        row.setAttribute('aria-describedby', 'systemsAppPopover');
        window.requestAnimationFrame(function () {
          if (activeAppRow !== row) return;
          positionAppPopover(row);
          appPopover.classList.add('is-visible');
        });
      }

      function hideAppPopover() {
        if (!appPopover) return;
        if (activeAppRow) {
          activeAppRow.removeAttribute('aria-describedby');
        }
        activeAppRow = null;
        appPopover.classList.remove('is-visible');
        appPopover.setAttribute('aria-hidden', 'true');
        appPopoverTimer = window.setTimeout(function () {
          if (!appPopover.classList.contains('is-visible')) {
            appPopover.hidden = true;
          }
        }, 180);
      }

      function updateActiveAppPopover() {
        if (activeAppRow && appPopover && appPopover.classList.contains('is-visible')) {
          positionAppPopover(activeAppRow);
        }
      }

      function applySearch() {
        var query = searchInput ? normalize(searchInput.value) : '';
        var visible = 0;

        appRows.forEach(function (row) {
          var text = normalize(row.getAttribute('data-search-text') + ' ' + row.textContent);
          var match = !query || text.indexOf(query) !== -1;
          row.hidden = !match;
          if (match) visible += 1;
        });

        if (searchEmpty) {
          searchEmpty.classList.toggle('is-visible', visible === 0);
        }

        if (activeAppRow && activeAppRow.hidden) {
          hideAppPopover();
        }

        syncAppTooltips();
      }

      function fillCalendarGrid(gridNode, cellClassName) {
        var lang = activeLabels();
        var now = new Date();
        var year = now.getFullYear();
        var month = now.getMonth();
        var firstDay = new Date(year, month, 1).getDay();
        var totalDays = new Date(year, month + 1, 0).getDate();

        if (!gridNode) return;
        gridNode.innerHTML = '';

        lang.weekdays.forEach(function (weekday) {
          var labelCell = document.createElement('span');
          labelCell.className = cellClassName + ' is-label';
          labelCell.textContent = weekday;
          gridNode.appendChild(labelCell);
        });

        for (var blank = 0; blank < firstDay; blank += 1) {
          var blankCell = document.createElement('span');
          blankCell.className = cellClassName + ' is-muted';
          blankCell.textContent = ' ';
          gridNode.appendChild(blankCell);
        }

        for (var day = 1; day <= totalDays; day += 1) {
          var dayCell = document.createElement('span');
          dayCell.className = cellClassName + (day === now.getDate() ? ' is-today' : '');
          dayCell.textContent = day.toString();
          gridNode.appendChild(dayCell);
        }
      }

      function renderCalendar() {
        var lang = activeLabels();
        var now = new Date();
        var monthNode = document.querySelector('[data-calendar-month]');
        var gridNode = document.querySelector('[data-calendar-grid]');

        if (monthNode) {
          monthNode.textContent = now.toLocaleDateString(lang.locale, { month: 'long', year: 'numeric' });
        }

        fillCalendarGrid(gridNode, 'calendar-cell');
      }

      function renderCalendarDetail() {
        var lang = activeLabels();
        var now = new Date();
        var detailMonthNode = document.querySelector('[data-detail-calendar-month]');
        var detailTodayNode = document.querySelector('[data-detail-calendar-today]');
        var detailGridNode = document.querySelector('[data-detail-calendar-grid]');

        if (detailMonthNode) {
          detailMonthNode.textContent = now.toLocaleDateString(lang.locale, { month: 'long', year: 'numeric' });
        }

        if (detailTodayNode) {
          detailTodayNode.textContent = lang.todayLabel + ' ' + now.toLocaleDateString(lang.locale, { day: 'numeric', month: 'short', year: 'numeric' });
        }

        fillCalendarGrid(detailGridNode, 'detail-calendar-cell');
      }

      function weatherKind(code) {
        if ([95, 96, 99].indexOf(code) !== -1) return 'storm';
        if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].indexOf(code) !== -1) return 'rain';
        if ([45, 48].indexOf(code) !== -1) return 'fog';
        if ([1, 2, 3].indexOf(code) !== -1) return 'cloudy';
        if (code === 0) return 'clear';
        return 'defaultWeather';
      }

      function weatherIcon(kind) {
        if (kind === 'clear') {
          return '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>';
        }
        if (kind === 'rain' || kind === 'storm') {
          return '<svg viewBox="0 0 24 24"><path d="M7 16h10a4 4 0 0 0 0-8 6 6 0 0 0-11.5 2A3.5 3.5 0 0 0 7 16Z"></path><path d="M8 20l1-2M12 21l1-3M16 20l1-2"></path></svg>';
        }
        return '<svg viewBox="0 0 24 24"><path d="M7 18h10a4 4 0 0 0 0-8 6 6 0 0 0-11.5 2A3.5 3.5 0 0 0 7 18Z"></path></svg>';
      }

      function renderWeather() {
        var lang = activeLabels();
        var stateNode = document.querySelector('[data-weather-state]');
        var tempNode = document.querySelector('[data-weather-temp]');
        var summaryNode = document.querySelector('[data-weather-summary]');
        var feelsNode = document.querySelector('[data-weather-feels]');
        var humidityNode = document.querySelector('[data-weather-humidity]');
        var windNode = document.querySelector('[data-weather-wind]');
        var iconNode = document.querySelector('[data-weather-icon]');

        if (!weatherData) {
          if (stateNode) stateNode.textContent = lang.weatherLoading;
          return;
        }

        if (weatherData.failed) {
          if (stateNode) stateNode.textContent = lang.weatherFailed;
          if (summaryNode) summaryNode.textContent = lang.weatherFailed;
          renderHourlyWeather();
          return;
        }

        var current = weatherData.current || {};
        var kind = weatherKind(Number(current.weather_code));
        var temp = Math.round(Number(current.temperature_2m));
        var feels = Math.round(Number(current.apparent_temperature));
        var humidity = Math.round(Number(current.relative_humidity_2m));
        var wind = Math.round(Number(current.wind_speed_10m));

        if (stateNode) stateNode.textContent = lang.weatherLoaded;
        if (tempNode) tempNode.textContent = Number.isFinite(temp) ? temp + '°C' : '--°';
        if (summaryNode) summaryNode.textContent = lang[kind] || lang.defaultWeather;
        if (feelsNode) feelsNode.textContent = Number.isFinite(feels) ? feels + '°C' : '--°';
        if (humidityNode) humidityNode.textContent = Number.isFinite(humidity) ? humidity + '%' : '--%';
        if (windNode) windNode.textContent = Number.isFinite(wind) ? wind + ' km/h' : '--';
        renderWeatherIcon(iconNode, Number(current.weather_code));
        renderForecast();

        renderHourlyWeather();
      }

      function weatherCategory(code) {
        if ([95, 96, 99].indexOf(code) !== -1) return 'storm';
        if ([71, 73, 75, 77, 85, 86].indexOf(code) !== -1) return 'snow';
        if ([51, 53, 55, 61, 63, 65, 66, 67, 80, 81, 82].indexOf(code) !== -1) return 'rain';
        if ([0, 1].indexOf(code) !== -1) return 'clear';
        return 'cloudy';
      }

      function renderWeatherIcon(container, code) {
        if (!container) return;
        var category = weatherCategory(code);
        container.classList.remove('wx-icon--clear', 'wx-icon--cloudy', 'wx-icon--rain', 'wx-icon--storm', 'wx-icon--snow');
        container.classList.add('wx-icon--' + category);
        container.querySelectorAll('.rain-drop, .snow-flake, .lightning-bolt').forEach(function (el) { el.remove(); });
        if (category === 'rain' || category === 'storm') {
          [18, 30, 42].forEach(function (left, i) {
            var drop = document.createElement('span');
            drop.className = 'rain-drop';
            drop.style.left = left + '%';
            drop.style.animationDelay = (i * 0.25) + 's';
            container.appendChild(drop);
          });
        }
        if (category === 'snow') {
          [16, 30, 44].forEach(function (left, i) {
            var flake = document.createElement('span');
            flake.className = 'snow-flake';
            flake.style.left = left + '%';
            flake.style.animationDelay = (i * 0.5) + 's';
            container.appendChild(flake);
          });
        }
        if (category === 'storm') {
          var bolt = document.createElement('span');
          bolt.className = 'lightning-bolt';
          bolt.textContent = '⚡';
          container.appendChild(bolt);
        }
      }

      function renderForecast() {
        var lang = activeLabels();
        var fcNode = document.querySelector('[data-weather-forecast]');
        if (!fcNode) return;
        var daily = weatherData && !weatherData.failed ? weatherData.daily : null;
        if (!daily || !Array.isArray(daily.time)) { fcNode.innerHTML = ''; return; }
        var html = '';
        for (var i = 1; i <= 3 && i < daily.time.length; i += 1) {
          var d = new Date(daily.time[i] + 'T00:00:00');
          var day = Number.isNaN(d.getTime()) ? '--' : d.toLocaleDateString(lang.locale, { weekday: 'short' });
          var high = Math.round(Number(daily.temperature_2m_max[i]));
          var low = Math.round(Number(daily.temperature_2m_min[i]));
          html += '<div><div class="wx-fc-day">' + day + '</div><div class="wx-fc-temps"><span class="wx-fc-high">' + (Number.isFinite(high) ? high : '--') + '°</span><span class="wx-fc-low">' + (Number.isFinite(low) ? low : '--') + '°</span></div></div>';
        }
        fcNode.innerHTML = html;
      }

      function updateClock() {
        var lang = activeLabels();
        var now = new Date();
        var timeNode = document.querySelector('[data-clock-time]');
        var dateNode = document.querySelector('[data-clock-date]');
        if (timeNode) timeNode.textContent = now.toLocaleTimeString(lang.locale, { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        if (dateNode) dateNode.textContent = now.toLocaleDateString(lang.locale, { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
      }

      function renderHourlyWeather() {
        var lang = activeLabels();
        var listNode = document.querySelector('[data-weather-hourly-list]');
        var hourly = weatherData && !weatherData.failed ? weatherData.hourly : null;
        if (!listNode) return;

        listNode.innerHTML = '';

        if (!hourly || !Array.isArray(hourly.time) || hourly.time.length === 0) {
          var emptyNode = document.createElement('p');
          emptyNode.className = 'detail-empty';
          emptyNode.textContent = weatherData && weatherData.failed ? lang.weatherFailed : lang.weatherHourlyEmpty;
          listNode.appendChild(emptyNode);
          return;
        }

        var now = new Date();
        var rendered = 0;

        hourly.time.forEach(function (timeValue, index) {
          if (rendered >= 8) return;
          var timeDate = new Date(timeValue);
          if (Number.isNaN(timeDate.getTime())) return;
          if (timeDate.getTime() < now.getTime() - (60 * 60 * 1000)) return;

          var temp = Math.round(Number(hourly.temperature_2m && hourly.temperature_2m[index]));
          var humidity = Math.round(Number(hourly.relative_humidity_2m && hourly.relative_humidity_2m[index]));
          var wind = Math.round(Number(hourly.wind_speed_10m && hourly.wind_speed_10m[index]));
          var rain = Math.round(Number(hourly.precipitation_probability && hourly.precipitation_probability[index]));
          var kind = weatherKind(Number(hourly.weather_code && hourly.weather_code[index]));

          var item = document.createElement('div');
          item.className = 'detail-hourly-item';

          var timeNode = document.createElement('time');
          timeNode.className = 'detail-hourly-time';
          timeNode.textContent = timeDate.toLocaleTimeString(lang.locale, { hour: '2-digit', minute: '2-digit' });

          var mainNode = document.createElement('span');
          mainNode.className = 'detail-hourly-main';

          var tempNode = document.createElement('strong');
          tempNode.textContent = Number.isFinite(temp) ? temp + '°C' : '--°';

          var summaryNode = document.createElement('span');
          summaryNode.textContent = (lang[kind] || lang.defaultWeather)
            + ' · '
            + (Number.isFinite(humidity) ? humidity + '% ' + lang.humidityWord : '--')
            + ' · '
            + (Number.isFinite(wind) ? wind + ' km/h' : '--');

          var rainNode = document.createElement('span');
          rainNode.className = 'detail-hourly-rain';
          rainNode.textContent = lang.rainChance + ' ' + (Number.isFinite(rain) ? rain + '%' : '--');

          mainNode.appendChild(tempNode);
          mainNode.appendChild(summaryNode);
          item.appendChild(timeNode);
          item.appendChild(mainNode);
          item.appendChild(rainNode);
          listNode.appendChild(item);
          rendered += 1;
        });

        if (rendered === 0) {
          var noDataNode = document.createElement('p');
          noDataNode.className = 'detail-empty';
          noDataNode.textContent = lang.weatherHourlyEmpty;
          listNode.appendChild(noDataNode);
        }
      }

      function loadWeather() {
        var stateNode = document.querySelector('[data-weather-state]');
        if (stateNode) stateNode.textContent = activeLabels().weatherLoading;

        fetch('https://api.open-meteo.com/v1/forecast?latitude=13.7563&longitude=100.5018&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m&hourly=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation_probability,weather_code,wind_speed_10m&daily=weather_code,temperature_2m_max,temperature_2m_min&forecast_days=4&timezone=Asia%2FBangkok', {
          headers: { Accept: 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) throw new Error('Weather API unavailable');
            return response.json();
          })
          .then(function (data) {
            weatherData = data;
            renderWeather();
          })
          .catch(function () {
            weatherData = { failed: true };
            renderWeather();
          });
      }

      function activeDetailType() {
        if (!detailModal || detailModal.hidden) return null;
        var activePanel = detailPanels.find(function (panel) {
          return !panel.hidden;
        });
        return activePanel ? activePanel.getAttribute('data-detail-panel') : null;
      }

      function renderActiveDetail() {
        var type = activeDetailType();
        var lang = activeLabels();
        if (!type) return;

        if (detailTitle) {
          detailTitle.textContent = type === 'calendar' ? lang.detailCalendarTitle : lang.detailWeatherTitle;
        }

        if (type === 'calendar') {
          renderCalendarDetail();
        } else if (type === 'weather') {
          renderHourlyWeather();
        }
      }

      function openDetail(type) {
        var lang = activeLabels();
        if (!detailModal || !detailDialog) return;

        detailPanels.forEach(function (panel) {
          panel.hidden = panel.getAttribute('data-detail-panel') !== type;
        });

        if (detailTitle) {
          detailTitle.textContent = type === 'calendar' ? lang.detailCalendarTitle : lang.detailWeatherTitle;
        }

        detailModal.hidden = false;
        detailModal.setAttribute('aria-hidden', 'false');

        if (type === 'calendar') {
          renderCalendarDetail();
        } else if (type === 'weather') {
          renderHourlyWeather();
        }

        window.setTimeout(function () {
          detailDialog.focus();
        }, 0);
      }

      function closeDetail() {
        if (!detailModal) return;
        detailModal.hidden = true;
        detailModal.setAttribute('aria-hidden', 'true');
      }

      if (searchInput) {
        searchInput.addEventListener('input', applySearch);
        document.addEventListener('keydown', function (event) {
          var target = event.target;
          var isTyping = target && /input|textarea|select/i.test(target.tagName);
          if (event.key === '/' && !isTyping) {
            event.preventDefault();
            searchInput.focus();
          }
        });
      }

      appRows.forEach(function (row) {
        row.addEventListener('mouseenter', function () {
          showAppPopover(row);
        });

        row.addEventListener('mouseleave', hideAppPopover);

        row.addEventListener('focusin', function () {
          showAppPopover(row);
        });

        row.addEventListener('focusout', hideAppPopover);
      });

      window.addEventListener('resize', updateActiveAppPopover);
      window.addEventListener('scroll', updateActiveAppPopover, true);

      document.querySelectorAll('[data-detail-open]').forEach(function (button) {
        button.addEventListener('click', function () {
          openDetail(button.getAttribute('data-detail-open'));
        });
      });

      if (detailModal) {
        detailModal.querySelectorAll('[data-detail-close]').forEach(function (button) {
          button.addEventListener('click', closeDetail);
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && !detailModal.hidden) {
            closeDetail();
          }
        });
      }

      var observer = new MutationObserver(function (mutations) {
        var shouldUpdate = mutations.some(function (mutation) {
          return mutation.attributeName === 'data-lang';
        });

        if (shouldUpdate) {
          updateClock();
          renderCalendar();
          renderWeather();
          renderActiveDetail();
          applySearch();
          window.setTimeout(syncAppTooltips, 0);
          if (activeAppRow && appPopover && !appPopover.hidden) {
            if (appPopoverTitle) appPopoverTitle.textContent = appText(activeAppRow, '.access-copy h3');
            if (appPopoverDesc) appPopoverDesc.textContent = appText(activeAppRow, '.access-copy p');
            setAppPopoverIcon(activeAppRow);
            updateActiveAppPopover();
          }
        }
      });

      observer.observe(document.documentElement, { attributes: true });

      renderCalendar();
      updateClock();
      setInterval(updateClock, 1000);
      loadWeather();
      applySearch();
    }());
  </script>
@endsection
