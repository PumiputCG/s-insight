<!DOCTYPE html>
<html lang="th" data-lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Insight') | Supavut Insight</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/insight/favicon.png').'?v=4' }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico').'?v=4' }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/insight/apple-touch-icon.png').'?v=4' }}">
  <style>
    :root {
      --bg: #f7f9f9;
      --surface: #ffffff;
      --surface-soft: #f1f7f6;
      --ink: #111827;
      --ink-soft: #2b3646;
      --muted: #6f7a86;
      --line: #e3e8eb;
      --line-strong: #cfd8dc;
      --teal: #0ca39a;
      --teal-dark: #087f79;
      --teal-soft: #def4f2;
      --blue-soft: #eef5ff;
      --warning: #9a6417;
      --warning-soft: #fff3df;
      --danger: #ad352d;
      --danger-soft: #fde8e6;
      --success: #127044;
      --success-soft: #e3f6ec;
      --shadow: 0 18px 46px rgba(22, 33, 44, .08);
      --radius: 12px;
      --topbar-h: 74px;
      --sidebar-w: 286px;
      --ease: cubic-bezier(.16, 1, .3, 1);
    }

    * { box-sizing: border-box; }

    html {
      background: var(--bg);
    }

    /* ── Custom cursor (Anathema dark green) ── */
    :root {
      /* ลูกศรน้ำเงินของแบรนด์ — hotspot 4 2 ให้ปลายลูกศรตรงกับจุดคลิกจริง
         ต้องมี auto/pointer ปิดท้ายเสมอ ถ้าไฟล์โหลดไม่ได้จะได้ยังมีเคอร์เซอร์ */
      --cursor-default: url("/assets/insight/cursor.png") 4 2, auto;
      --cursor-action: url("/assets/insight/cursor.png") 4 2, pointer;
      --cursor-disabled: url("/assets/insight/cursor.png") 4 2, not-allowed;
    }

    html, body, * { cursor: var(--cursor-current, var(--cursor-default)); }
    a, button, [href], [role="button"], [role="menuitem"], [role="menuitemradio"], summary, label, select,
    .nav-go, [data-language-toggle], [data-lang-option], [onclick], [tabindex]:not([tabindex="-1"]),
    input[type="checkbox"], input[type="radio"], input[type="file"], input[type="submit"], input[type="button"],
    input[type="range"], input[type="color"]
      { --cursor-current: var(--cursor-action); cursor: var(--cursor-action) !important; }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="search"], input[type="tel"], textarea, [contenteditable="true"]
      { --cursor-current: text; cursor: text !important; }
    button:disabled, [aria-disabled="true"], [disabled] { --cursor-current: var(--cursor-disabled); cursor: var(--cursor-disabled) !important; }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink);
      font-family: "Segoe UI", "Noto Sans Thai", "Leelawadee UI", "Noto Sans Myanmar", "Myanmar Text", Arial, sans-serif;
      font-size: 16px;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    html[data-lang="my"] body,
    html[data-lang="my"] button,
    html[data-lang="my"] input,
    html[data-lang="my"] select {
      font-family: "Noto Sans Myanmar", "Myanmar Text", "Pyidaungsu", "Segoe UI", sans-serif;
      line-height: 1.82;
    }

    a { color: inherit; text-decoration: none; }
    button, input, select { font: inherit; }

    :focus-visible {
      outline: 3px solid rgba(12, 163, 154, .28);
      outline-offset: 3px;
      border-radius: 4px;
    }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 40;
      height: var(--topbar-h);
      border-bottom: 1px solid rgba(227, 232, 235, .82);
      background: rgba(255, 255, 255, .88);
      backdrop-filter: blur(18px);
    }

    .topbar-inner {
      width: min(1280px, calc(100% - 42px));
      height: 100%;
      margin: 0 auto;
      display: grid;
      grid-template-columns: minmax(190px, 1fr) auto minmax(250px, 1fr);
      align-items: center;
      gap: 24px;
    }

    .brand {
      justify-self: start;
      font-family: Georgia, "Times New Roman", serif;
      color: var(--ink);
      font-size: 1.62rem;
      font-weight: 700;
      letter-spacing: -.02em;
      line-height: 1;
    }

    .topnav {
      justify-self: center;
      display: inline-flex;
      align-items: center;
      gap: 34px;
      color: var(--muted);
      font-size: .94rem;
      font-weight: 650;
    }

    .topnav a {
      position: relative;
      min-height: 38px;
      display: inline-flex;
      align-items: center;
    }

    .topnav a::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 2px;
      height: 2px;
      border-radius: 999px;
      background: var(--teal);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .2s var(--ease);
    }

    .topnav a:hover,
    .topnav a.active { color: var(--ink); }
    .topnav a:hover::after,
    .topnav a.active::after { transform: scaleX(1); }

    .route-line {
      justify-self: center;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--muted);
      font-size: .9rem;
      font-weight: 720;
      white-space: nowrap;
    }

    .route-line a {
      color: var(--teal-dark);
    }

    .route-line span:last-child {
      color: var(--ink);
    }

    .top-actions {
      justify-self: end;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .lang {
      position: relative;
    }

    .icon-btn,
    .lang-toggle {
      width: 40px;
      height: 40px;
      display: inline-grid;
      place-items: center;
      border: 0;
      border-radius: 999px;
      background: transparent;
      color: #111;
      cursor: pointer;
      transition: background .2s var(--ease), transform .2s var(--ease);
    }

    .icon-btn:hover,
    .lang-toggle:hover {
      background: #eef4f3;
      transform: translateY(-1px);
    }

    .icon-btn svg,
    .lang-toggle svg {
      width: 21px;
      height: 21px;
    }

    .lang-toggle {
      width: auto;
      min-width: 48px;
      padding: 0 .6rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--ink);
      font-size: .76rem;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .lang-current-text {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    .language-flag {
      width: 22px;
      height: 16px;
      display: block;
      flex: none;
      object-fit: cover;
      border-radius: 3px;
      box-shadow: 0 0 0 1px rgb(0 0 0 / 16%);
    }

    .lang-toggle .language-flag {
      width: 26px;
      height: 18px;
    }

    .lang-menu .language-flag {
      margin-left: auto;
    }

    .bell {
      position: relative;
    }

    .bell::after {
      content: "3";
      position: absolute;
      top: 4px;
      right: 4px;
      min-width: 15px;
      height: 15px;
      display: grid;
      place-items: center;
      border: 2px solid #fff;
      border-radius: 999px;
      background: var(--teal);
      color: #fff;
      font-size: .62rem;
      font-weight: 800;
      line-height: 1;
    }

    .lang-menu {
      position: absolute;
      top: calc(100% + 9px);
      right: 0;
      min-width: 174px;
      padding: 8px;
      border: 1px solid var(--line);
      border-radius: 5px;
      background: var(--surface);
      box-shadow: var(--shadow);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-6px);
      transition: opacity .18s var(--ease), transform .18s var(--ease), visibility .18s;
    }

    .lang:hover .lang-menu,
    .lang.open .lang-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .lang-menu button {
      width: 100%;
      min-height: 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 0 12px;
      border: 0;
      border-radius: 4px;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      font-size: .92rem;
      font-weight: 680;
      text-align: left;
      text-transform: uppercase;
    }

    .lang-menu button:hover,
    .lang-menu button[aria-current="true"] {
      background: var(--teal-soft);
      color: var(--teal-dark);
    }

    .lang-code {
      min-width: 28px;
      color: var(--teal-dark);
      font-size: .78rem;
      font-weight: 800;
      text-align: right;
    }

    .user-chip {
      min-width: 0;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 5px 6px 5px 12px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: #fff;
    }

    .user-chip strong {
      max-width: 150px;
      display: block;
      overflow: hidden;
      color: var(--ink);
      font-size: .88rem;
      font-weight: 760;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .user-chip span {
      display: block;
      margin-top: -2px;
      color: var(--muted);
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .avatar {
      width: 34px;
      height: 34px;
      display: grid;
      place-items: center;
      border-radius: 999px;
      background: var(--teal-soft);
      color: var(--teal-dark);
      font-size: .92rem;
      font-weight: 850;
    }

    .layout {
      width: min(1280px, calc(100% - 42px));
      margin: 28px auto 54px;
      display: grid;
      grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
      gap: 26px;
      align-items: start;
    }

    .sidebar {
      position: sticky;
      top: calc(var(--topbar-h) + 22px);
      min-height: calc(100vh - var(--topbar-h) - 44px);
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding: 18px;
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: var(--surface);
      box-shadow: 0 12px 34px rgba(20, 30, 38, .06);
    }

    .side-section {
      display: grid;
      gap: 8px;
    }

    .side-label {
      margin: 0 0 4px;
      color: var(--muted);
      font-size: .76rem;
      font-weight: 850;
      letter-spacing: .14em;
      text-transform: uppercase;
    }

    .side-link {
      min-height: 44px;
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 0 12px;
      border: 1px solid transparent;
      border-radius: 4px;
      color: var(--ink-soft);
      font-size: .93rem;
      font-weight: 690;
      transition: background .18s var(--ease), color .18s var(--ease), border-color .18s var(--ease);
    }

    .side-link:hover,
    .side-link.active {
      border-color: rgba(12, 163, 154, .14);
      background: var(--teal-soft);
      color: var(--teal-dark);
    }

    .side-link.disabled {
      color: #a4abb4;
      cursor: not-allowed;
    }

    .side-link svg {
      width: 19px;
      height: 19px;
      flex: 0 0 auto;
    }

    .submenu {
      display: grid;
      gap: 3px;
      margin: -2px 0 4px 30px;
      padding-left: 14px;
      border-left: 1px solid var(--line);
    }

    .submenu a {
      min-height: 30px;
      display: flex;
      align-items: center;
      color: var(--muted);
      font-size: .85rem;
      font-weight: 640;
    }

    .submenu a:hover,
    .submenu a.active {
      color: var(--teal-dark);
    }

    .sidebar-bottom {
      margin-top: auto;
      display: grid;
      gap: 12px;
    }

    .mini-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
      padding: 12px;
      border: 1px solid var(--line);
      border-radius: 5px;
      background: #fbfcfc;
    }

    .mini-profile strong {
      display: block;
      overflow: hidden;
      color: var(--ink);
      font-size: .9rem;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .mini-profile span {
      display: block;
      overflow: hidden;
      color: var(--muted);
      font-size: .78rem;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .logout-form {
      margin: 0;
    }

    .logout-form button {
      width: 100%;
    }

    .main {
      min-width: 0;
    }

    .alert {
      margin-bottom: 18px;
      padding: 12px 14px;
      border-radius: 4px;
      font-size: .92rem;
      font-weight: 650;
    }

    .alert.success {
      background: var(--success-soft);
      color: var(--success);
    }

    .alert.error {
      background: var(--danger-soft);
      color: var(--danger);
    }

    .alert.warn {
      background: var(--warning-soft);
      color: var(--warning);
    }

    .breadcrumb {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      margin: 0 0 13px;
      color: var(--muted);
      font-size: .86rem;
      font-weight: 650;
    }

    .breadcrumb a {
      color: var(--teal-dark);
    }

    .page-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 24px;
      margin-bottom: 22px;
    }

    .page-title {
      margin: 0 0 9px;
      color: var(--ink);
      font-size: clamp(1.9rem, 3.4vw, 3rem);
      font-weight: 820;
      line-height: 1.05;
      letter-spacing: -.02em;
    }

    .sub {
      max-width: 70ch;
      margin: 0;
      color: var(--muted);
      font-size: .98rem;
    }

    .page-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .btn {
      min-height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      padding: 0 16px;
      border: 1px solid var(--teal);
      border-radius: 4px;
      background: var(--teal);
      color: #fff;
      cursor: pointer;
      font-size: .92rem;
      font-weight: 760;
      transition: transform .18s var(--ease), background .18s var(--ease), border-color .18s var(--ease), color .18s var(--ease);
    }

    .btn:hover {
      transform: translateY(-1px);
      background: var(--teal-dark);
      border-color: var(--teal-dark);
    }

    .btn.secondary {
      background: #fff;
      color: var(--ink);
      border-color: var(--line-strong);
    }

    .btn.secondary:hover {
      border-color: var(--teal);
      color: var(--teal-dark);
    }

    .btn.small {
      min-height: 36px;
      padding: 0 12px;
      font-size: .84rem;
    }

    .metric-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 18px;
    }

    .metric-card,
    .panel,
    .card,
    .module-card,
    .module-item {
      border: 1px solid var(--line);
      border-radius: var(--radius);
      background: var(--surface);
      box-shadow: 0 12px 34px rgba(20, 30, 38, .055);
    }

    .metric-card {
      min-height: 144px;
      padding: 18px;
      display: grid;
      gap: 12px;
    }

    .metric-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .metric-icon {
      width: 42px;
      height: 42px;
      display: grid;
      place-items: center;
      border-radius: 5px;
      background: var(--teal-soft);
      color: var(--teal-dark);
    }

    .metric-icon svg {
      width: 21px;
      height: 21px;
    }

    .metric-label {
      margin: 0;
      color: var(--muted);
      font-size: .74rem;
      font-weight: 850;
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    .metric-value {
      margin: 0;
      color: var(--ink);
      font-size: clamp(1.55rem, 3vw, 2.25rem);
      font-weight: 860;
      line-height: 1;
      letter-spacing: -.02em;
    }

    .metric-note {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin: 0;
      color: var(--muted);
      font-size: .82rem;
      font-weight: 650;
    }

    .trend {
      color: var(--teal-dark);
      font-weight: 820;
      white-space: nowrap;
    }

    .spark {
      width: 80px;
      height: 30px;
      color: var(--teal);
    }

    .panel-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .panel,
    .card {
      padding: 22px;
    }

    .panel.full {
      grid-column: 1 / -1;
    }

    .section-title {
      margin: 0 0 16px;
      color: var(--ink);
      font-size: 1.04rem;
      font-weight: 800;
      letter-spacing: -.01em;
    }

    .section-kicker {
      margin: 0 0 6px;
      color: var(--teal);
      font-size: .72rem;
      font-weight: 850;
      letter-spacing: .13em;
      text-transform: uppercase;
    }

    .module-grid,
    .module-list {
      display: grid;
      gap: 12px;
    }

    .module-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .module-card,
    .module-item {
      min-height: 128px;
      display: grid;
      grid-template-columns: 48px minmax(0, 1fr) auto;
      align-items: center;
      gap: 16px;
      padding: 18px;
      transition: border-color .18s var(--ease), transform .18s var(--ease), box-shadow .18s var(--ease);
    }

    a.module-card:hover,
    a.module-item:hover {
      border-color: rgba(12, 163, 154, .42);
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    .module-icon,
    .module-code {
      width: 48px;
      height: 48px;
      display: grid;
      place-items: center;
      border-radius: 5px;
      background: var(--teal-soft);
      color: var(--teal-dark);
      font-size: .86rem;
      font-weight: 850;
    }

    .module-icon svg {
      width: 22px;
      height: 22px;
    }

    .module-main h3 {
      margin: 0 0 5px;
      color: var(--ink);
      font-size: 1rem;
      font-weight: 800;
    }

    .module-main p {
      margin: 0;
      color: var(--muted);
      font-size: .9rem;
    }

    .badge {
      min-height: 29px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: 0 10px;
      background: var(--teal-soft);
      color: var(--teal-dark);
      font-size: .76rem;
      font-weight: 820;
      white-space: nowrap;
    }

    .badge.soon {
      background: var(--warning-soft);
      color: var(--warning);
    }

    .badge.gray {
      background: #eef1f3;
      color: var(--muted);
    }

    .table-wrap {
      overflow-x: auto;
    }

    table.tbl {
      width: 100%;
      border-collapse: collapse;
      font-size: .91rem;
    }

    .tbl th,
    .tbl td {
      padding: 13px 0;
      border-bottom: 1px solid var(--line);
      text-align: left;
      white-space: nowrap;
    }

    .tbl th {
      color: var(--muted);
      font-size: .76rem;
      font-weight: 850;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .tbl td {
      color: var(--ink-soft);
      font-weight: 650;
    }

    .tbl th:last-child,
    .tbl td:last-child {
      text-align: right;
    }

    .meta-list {
      display: grid;
      gap: 0;
      margin: 0;
    }

    .meta-row {
      display: grid;
      grid-template-columns: 150px minmax(0, 1fr);
      gap: 18px;
      padding: 12px 0;
      border-bottom: 1px solid var(--line);
    }

    .meta-row:last-child {
      border-bottom: 0;
    }

    .meta-row dt {
      color: var(--muted);
      font-size: .87rem;
      font-weight: 680;
    }

    .meta-row dd {
      margin: 0;
      color: var(--ink);
      font-weight: 760;
      overflow-wrap: anywhere;
    }

    .field {
      margin-bottom: 16px;
    }

    .field label {
      display: block;
      margin-bottom: 7px;
      color: var(--ink-soft);
      font-size: .9rem;
      font-weight: 720;
    }

    .field input,
    .field select {
      width: 100%;
      min-height: 50px;
      padding: 11px 12px;
      border: 1px solid var(--line-strong);
      border-radius: 4px;
      background: #fbfcfc;
      color: var(--ink);
      font-size: 1rem;
      transition: border-color .18s var(--ease), box-shadow .18s var(--ease), background .18s var(--ease);
    }

    .field input:focus,
    .field select:focus {
      outline: 0;
      border-color: var(--teal);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(12, 163, 154, .14);
    }

    .err-text {
      margin-top: 6px;
      color: var(--danger);
      font-size: .82rem;
    }

    .timeline,
    .list-stack {
      display: grid;
      gap: 12px;
      margin: 0;
      padding: 0;
      list-style: none;
    }

    .timeline li,
    .list-stack li {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      min-height: 54px;
      padding: 12px 0;
      border-bottom: 1px solid var(--line);
    }

    .timeline li:last-child,
    .list-stack li:last-child {
      border-bottom: 0;
    }

    .list-title {
      display: block;
      color: var(--ink);
      font-weight: 760;
    }

    .list-sub {
      display: block;
      color: var(--muted);
      font-size: .84rem;
      font-weight: 620;
    }

    .footer-note {
      margin: 26px 0 0;
      color: var(--muted);
      font-size: .86rem;
      text-align: center;
    }

    @media (max-width: 1080px) {
      .topbar {
        height: auto;
      }

      .topbar-inner {
        min-height: var(--topbar-h);
        grid-template-columns: auto 1fr auto;
        padding: 10px 0;
      }

      .topnav {
        display: none;
      }

      .layout {
        grid-template-columns: 1fr;
      }

      .sidebar {
        position: static;
        min-height: auto;
      }

      .side-section {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .side-label,
      .submenu {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 780px) {
      .topbar-inner,
      .layout {
        width: min(100% - 28px, 560px);
      }

      .user-chip > div {
        display: none;
      }

      .metric-grid,
      .panel-grid,
      .module-grid {
        grid-template-columns: 1fr;
      }

      .page-head {
        display: block;
      }

      .page-actions {
        margin-top: 14px;
      }

      .side-section {
        grid-template-columns: 1fr;
      }

      .module-card,
      .module-item {
        grid-template-columns: 44px minmax(0, 1fr);
      }

      .module-card .badge,
      .module-item .badge {
        grid-column: 2;
        justify-self: flex-start;
      }

      .meta-row {
        grid-template-columns: 1fr;
        gap: 4px;
      }

      .top-actions {
        gap: 6px;
      }
    }

    @media (max-width: 520px) {
      .brand {
        font-size: 1.4rem;
      }

      .bell {
        display: none;
      }

      .panel,
      .card {
        padding: 18px;
      }
    }
  </style>
</head>
<body>
  @php
    $displayName = isset($me) ? ($me->fullNameTh() ?: $me->full_name_en ?: $me->employee_code) : 'Insight';
    $initial = mb_substr($displayName, 0, 1);
  @endphp

  <header class="topbar">
    <div class="topbar-inner">
      <a href="{{ route('dashboard') }}" class="brand" aria-label="Insight">Insight</a>

      @isset($me)
        <div class="route-line" aria-label="เส้นทางหน้าเว็บ">
          <a href="{{ route('dashboard') }}" data-i18n="nav.home">Home</a>
          <span>/</span>
          @if (request()->routeIs('recruit.*'))
            <a href="{{ route('dashboard') }}" data-i18n="nav.dashboard">Dashboard</a>
            <span>/</span>
            <span data-i18n="nav.recruit">Recruit</span>
          @elseif (request()->routeIs('assessment.*'))
            <a href="{{ route('dashboard') }}" data-i18n="nav.dashboard">Dashboard</a>
            <span>/</span>
            <span data-i18n="nav.assessment">Assessment</span>
          @elseif (request()->routeIs('password.*'))
            <a href="{{ route('dashboard') }}" data-i18n="nav.dashboard">Dashboard</a>
            <span>/</span>
            <span data-i18n="nav.account">Account</span>
          @else
            <span data-i18n="nav.dashboard">Dashboard</span>
          @endif
        </div>

        <div class="top-actions">
          <div class="lang" id="lang">
            <button class="lang-toggle" id="langToggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="เปลี่ยนภาษา">
              <span class="lang-current-text" id="langCurrent">THAI</span>
              <img id="langCurrentFlag" class="language-flag" data-flag-src-th="{{ asset('assets/insight/flags/th.png') }}" data-flag-src-en="{{ asset('assets/insight/flags/en.png') }}" data-flag-src-my="{{ asset('assets/insight/flags/my.png') }}" src="{{ asset('assets/insight/flags/th.png') }}" alt="" aria-hidden="true">
            </button>
            <div class="lang-menu" role="menu" aria-label="ภาษา">
              <button type="button" data-lang="th" aria-current="true"><span>THAI</span><img class="language-flag" src="{{ asset('assets/insight/flags/th.png') }}" alt="" aria-hidden="true"></button>
              <button type="button" data-lang="en" aria-current="false"><span>ENG</span><img class="language-flag" src="{{ asset('assets/insight/flags/en.png') }}" alt="" aria-hidden="true"></button>
              <button type="button" data-lang="my" aria-current="false"><span>မြန်မာ</span><img class="language-flag" src="{{ asset('assets/insight/flags/my.png') }}" alt="" aria-hidden="true"></button>
            </div>
          </div>

          <button type="button" class="icon-btn bell" aria-label="แจ้งเตือน">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          </button>

          <a class="user-chip" href="{{ route('password.edit') }}">
            <div>
              <strong>{{ $displayName }}</strong>
              <span>{{ $me->isAdmin() ? 'Admin' : 'User' }}</span>
            </div>
            <span class="avatar">{{ $initial }}</span>
          </a>
        </div>
      @endisset
    </div>
  </header>

  <div class="layout">
    @isset($me)
      <aside class="sidebar" aria-label="เมนูด้านข้าง">
        <nav class="side-section">
          <p class="side-label" data-i18n="side.data">ข้อมูล</p>
          <a class="side-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12h7V3H3v9Zm11 9h7V3h-7v18ZM3 21h7v-5H3v5Z"></path></svg>
            <span data-i18n="nav.dashboard">Dashboard</span>
          </a>
          <a class="side-link {{ request()->routeIs('recruit.*') ? 'active' : '' }}" href="{{ route('recruit.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><rect x="2" y="8" width="20" height="12" rx="2"></rect></svg>
            <span data-i18n="nav.recruit">Recruit</span>
          </a>
          <div class="submenu" aria-label="Recruit submenu">
            <a class="{{ request()->routeIs('recruit.*') ? 'active' : '' }}" href="{{ route('recruit.index') }}" data-i18n="side.overview">ภาพรวม</a>
            <a href="{{ route('recruit.index') }}#jobs" data-i18n="side.jobs">ตำแหน่งงาน</a>
            <a href="{{ route('recruit.index') }}#applicants" data-i18n="side.applicants">ผู้สมัคร</a>
            <a href="{{ route('recruit.index') }}#interviews" data-i18n="side.interviews">สัมภาษณ์</a>
            <a href="{{ route('recruit.index') }}#talent">Talent Pool</a>
          </div>
          <a class="side-link {{ request()->routeIs('assessment.*') ? 'active' : '' }}" href="{{ route('assessment.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 11 12 14 22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            <span data-i18n="nav.assessment">Assessment</span>
          </a>
          <a class="side-link" href="{{ route('password.edit') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"></path></svg>
            <span data-i18n="nav.account">Account</span>
          </a>
        </nav>

        <div class="sidebar-bottom">
          <div class="mini-profile">
            <span class="avatar">{{ $initial }}</span>
            <div style="min-width:0">
              <strong>{{ $displayName }}</strong>
              <span>{{ $me->position ?? ($me->isAdmin() ? 'System administrator' : 'Insight user') }}</span>
            </div>
          </div>

          <form class="logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn secondary">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path></svg>
              <span data-i18n="side.logout">Logout</span>
            </button>
          </form>
        </div>
      </aside>
    @endisset

    <main class="main">
      @if (session('success')) <div class="alert success">{{ session('success') }}</div> @endif
      @if (session('error')) <div class="alert error">{{ session('error') }}</div> @endif
      @if (session('warning')) <div class="alert warn">{{ session('warning') }}</div> @endif
      @yield('content')
      <p class="footer-note">© {{ date('Y') }} Supavut Industry · SUPAVUT INSIGHT {{ date('Y') }}</p>
    </main>
  </div>

  <script>
    'use strict';

    (function initLanguage() {
      var wrap = document.getElementById('lang');
      var toggle = document.getElementById('langToggle');
      if (!wrap || !toggle) return;
      var currentLabel = document.getElementById('langCurrent');
      var currentFlag = document.getElementById('langCurrentFlag');
      var copy = {
        th: {
          label: 'THAI',
          text: {
            'nav.home': 'หน้าแรก',
            'nav.dashboard': 'Dashboard',
            'nav.recruit': 'Recruit',
            'nav.assessment': 'Assessment',
            'nav.account': 'บัญชี',
            'side.data': 'ข้อมูล',
            'side.overview': 'ภาพรวม',
            'side.jobs': 'ตำแหน่งงาน',
            'side.applicants': 'ผู้สมัคร',
            'side.interviews': 'สัมภาษณ์',
            'side.logout': 'ออกจากระบบ'
          }
        },
        en: {
          label: 'ENG',
          text: {
            'nav.home': 'Home',
            'nav.dashboard': 'Dashboard',
            'nav.recruit': 'Recruit',
            'nav.assessment': 'Assessment',
            'nav.account': 'Account',
            'side.data': 'Data',
            'side.overview': 'Overview',
            'side.jobs': 'Jobs',
            'side.applicants': 'Applicants',
            'side.interviews': 'Interviews',
            'side.logout': 'Logout'
          }
        },
        my: {
          label: 'မြန်မာ',
          text: {
            'nav.home': 'ပင်မ',
            'nav.dashboard': 'Dashboard',
            'nav.recruit': 'ဝန်ထမ်းခေါ်ယူခြင်း',
            'nav.assessment': 'အကဲဖြတ်ခြင်း',
            'nav.account': 'အကောင့်',
            'side.data': 'ဒေတာ',
            'side.overview': 'အကျဉ်းချုပ်',
            'side.jobs': 'အလုပ်ရာထူး',
            'side.applicants': 'လျှောက်ထားသူများ',
            'side.interviews': 'အင်တာဗျူး',
            'side.logout': 'ထွက်ရန်'
          }
        }
      };
      var loose = {
        'SUPAVUT INSIGHT Dashboard': { th: 'SUPAVUT INSIGHT Dashboard', en: 'SUPAVUT INSIGHT Dashboard', my: 'SUPAVUT INSIGHT Dashboard' },
        'ภาพรวม Employee Master และทางเข้าสู่ระบบย่อยของ Insight โดยนับเฉพาะพนักงานที่ยังทำงานอยู่': {
          th: 'ภาพรวม Employee Master และทางเข้าสู่ระบบย่อยของ Insight โดยนับเฉพาะพนักงานที่ยังทำงานอยู่',
          en: 'Employee Master overview and Insight program entry, counting active employees only',
          my: 'Active ဝန်ထမ်းများသာတွက်ထားသော Employee Master နှင့် Insight စနစ်များ အကျဉ်းချုပ်'
        },
        'ระบบย่อย': { th: 'ระบบย่อย', en: 'Programs', my: 'စနစ်များ' },
        'สถานะข้อมูล': { th: 'สถานะข้อมูล', en: 'Data status', my: 'ဒေတာအခြေအနေ' },
        'จำนวนพนักงานที่ยังทำงานอยู่แยกตามบริษัท': {
          th: 'จำนวนพนักงานที่ยังทำงานอยู่แยกตามบริษัท',
          en: 'Active employees by company',
          my: 'ကုမ္ပဏီအလိုက် active ဝန်ထမ်းအရေအတွက်'
        },
        'Recruit': { th: 'Recruit', en: 'Recruit', my: 'ဝန်ထမ်းခေါ်ယူခြင်း' },
        'Assessment': { th: 'Assessment', en: 'Assessment', my: 'အကဲဖြတ်ခြင်း' },
        'กลับ Dashboard': { th: 'กลับ Dashboard', en: 'Back to Dashboard', my: 'Dashboard သို့ပြန်ရန်' },
        'เปลี่ยนรหัสผ่าน': { th: 'เปลี่ยนรหัสผ่าน', en: 'Change password', my: 'စကားဝှက်ပြောင်းရန်' },
        'บันทึกรหัสผ่าน': { th: 'บันทึกรหัสผ่าน', en: 'Save password', my: 'စကားဝှက်သိမ်းရန်' },
        'ข้อมูลบัญชี': { th: 'ข้อมูลบัญชี', en: 'Account details', my: 'အကောင့်အချက်အလက်' }
      };
      var originalText = new WeakMap();

      function setLanguage(lang) {
        if (!copy[lang]) lang = 'th';
        document.documentElement.lang = lang;
        document.documentElement.setAttribute('data-lang', lang);
        if (currentLabel) currentLabel.textContent = copy[lang].label;
        if (currentFlag) {
          var src = currentFlag.getAttribute('data-flag-src-' + lang);
          if (src) currentFlag.setAttribute('src', src);
        }
        document.querySelectorAll('[data-i18n]').forEach(function (node) {
          var key = node.getAttribute('data-i18n');
          if (copy[lang].text[key]) node.textContent = copy[lang].text[key];
        });
        document.querySelectorAll('.lang-menu button[data-lang]').forEach(function (button) {
          button.setAttribute('aria-current', button.getAttribute('data-lang') === lang ? 'true' : 'false');
        });
        translateLooseText(lang);
        try { localStorage.setItem('insight_lang', lang); } catch (e) {}
      }

      function translateLooseText(lang) {
        var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
          acceptNode: function (node) {
            var value = node.nodeValue.trim();
            if (!value) return NodeFilter.FILTER_REJECT;
            if (node.parentElement && node.parentElement.closest('script, style, textarea, input')) {
              return NodeFilter.FILTER_REJECT;
            }
            return NodeFilter.FILTER_ACCEPT;
          }
        });
        var nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(function (node) {
          var raw = node.nodeValue;
          var trimmed = raw.trim();
          var key = originalText.get(node);
          if (!key && loose[trimmed]) {
            key = trimmed;
            originalText.set(node, key);
          }
          if (!key || !loose[key] || !loose[key][lang]) return;
          node.nodeValue = raw.replace(trimmed, loose[key][lang]);
        });
      }

      var saved = 'th';
      try { saved = localStorage.getItem('insight_lang') || 'th'; } catch (e) {}
      setLanguage(saved);

      toggle.addEventListener('click', function () {
        var isOpen = wrap.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      document.querySelectorAll('.lang-menu button').forEach(function (button) {
        button.addEventListener('click', function () {
          setLanguage(button.getAttribute('data-lang'));
          wrap.classList.remove('open');
          toggle.setAttribute('aria-expanded', 'false');
        });
      });

      document.addEventListener('click', function (event) {
        if (!wrap.contains(event.target)) {
          wrap.classList.remove('open');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    })();
  </script>
</body>
</html>
