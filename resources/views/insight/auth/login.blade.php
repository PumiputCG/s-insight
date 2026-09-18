<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f7f4ec">
  <title>Login | SUPAVUT INSIGHT</title>
  {{-- ?v= บังคับให้เบราว์เซอร์โหลดไอคอนใหม่ ไม่งั้นจะค้างรูปเดิมที่แคชไว้ --}}
  <link rel="icon" type="image/png" href="{{ asset('assets/insight/favicon.png').'?v=4' }}">
  <link rel="shortcut icon" href="{{ asset('favicon.ico').'?v=4' }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/insight/apple-touch-icon.png').'?v=4' }}">
  <script>
    document.documentElement.classList.add('js');
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600&family=Italiana&family=Jost:wght@400;500;600&family=La+Belle+Aurore&family=Montserrat:wght@700;800&family=Noto+Serif+Thai:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --near-black: #090a09;
      --ink: #11130f;
      --muted: #5f655c;
      --paper: #f7f4ec;
      --paper-soft: #ede7da;
      --moss: #1e3a8a;
      --line: rgb(17 19 15 / 16%);
      --danger: #9f2f26;
      --success: #1d7a42;
      --page-pad: clamp(1.25rem, 4vw, 4.5rem);
      --header-height: 5.25rem;
      --font-body: "Jost", "Anuphan", sans-serif;
      --font-display: "Italiana", "Noto Serif Thai", serif;
      --ease-out: cubic-bezier(.16, 1, .3, 1);
      --ease-in-out: cubic-bezier(.76, 0, .24, 1);
      --z-header: 20;
      --z-menu: 30;
      --z-loader: 40;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    * {
      margin: 0;
    }

    html,
    body {
      min-height: 100%;
      background: var(--paper);
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
    [data-language-toggle], [data-lang-option], [data-password-toggle], [onclick], [tabindex]:not([tabindex="-1"]),
    input[type="checkbox"], input[type="radio"], input[type="file"], input[type="submit"], input[type="button"],
    input[type="range"], input[type="color"]
      { --cursor-current: var(--cursor-action); cursor: var(--cursor-action) !important; }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="search"], input[type="tel"], textarea, [contenteditable="true"]
      { --cursor-current: text; cursor: text !important; }
    button:disabled, [aria-disabled="true"], [disabled] { --cursor-current: var(--cursor-disabled); cursor: var(--cursor-disabled) !important; }

    body {
      min-width: 320px;
      overflow-x: hidden;
      color: var(--ink);
      font-family: var(--font-body);
      font-size: clamp(.96rem, .92rem + .16vw, 1.04rem);
      line-height: 1.65;
      -webkit-font-smoothing: antialiased;
    }

    html[data-lang="my"] body,
    html[data-lang="my"] button,
    html[data-lang="my"] input {
      font-family: "Noto Sans Myanmar", "Myanmar Text", "Pyidaungsu", var(--font-body);
      line-height: 1.85;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    button,
    input {
      color: inherit;
      font: inherit;
    }

    img {
      display: block;
      max-width: 100%;
    }

    :focus-visible {
      outline: 2px solid var(--moss);
      outline-offset: 5px;
    }

    .page-loader {
      position: fixed;
      inset: 0;
      z-index: var(--z-loader);
      display: none;
      place-items: center;
      /* ฉากกั้นตอนเข้าสู่ระบบเป็นน้ำเงินเข้มชุดเดียวกับเมนูซ้าย (Manager สั่ง) */
      background: #16255c;
      color: #ffffff;
      transform: translateY(-100%);
      pointer-events: none;
      transition: transform .9s var(--ease-in-out);
      will-change: transform;
    }

    .js .page-loader {
      display: grid;
    }

    /* ฉาก loader ตามธีม: สว่าง=ขาว/ตัวดำ, มืด=ดำ/ตัวขาว */
    html[data-theme="light"] .page-loader { background: #ffffff; color: #000000; }

    .page-loader span {
      position: relative;
      display: inline-block;
      padding-bottom: .35rem;
      font-family: "Italiana", "Noto Serif Thai", "Noto Sans Myanmar", "Myanmar Text", serif;
      font-size: clamp(2.8rem, 7vw, 6.25rem);
      font-weight: 400;
      letter-spacing: .08em;
      line-height: 1;
      text-transform: uppercase;
    }

    .page-loader span::after {
      content: "";
      position: absolute;
      left: 50%;
      bottom: 0;
      width: min(12rem, 68%);
      height: 1px;
      background: currentColor;
      opacity: .58;
      transform: translateX(-50%);
    }

    .page-loader.is-active {
      transform: none;
      pointer-events: auto;
    }

    .site-header {
      position: fixed;
      inset: 0 0 auto;
      z-index: var(--z-header);
      height: var(--header-height);
      display: grid;
      grid-template-columns: 1fr auto;
      align-items: center;
      gap: 1.5rem;
      padding-inline: var(--page-pad);
      color: var(--ink);
      background: transparent;
      border-bottom: 0;
      transition: height .35s var(--ease-out), color .35s var(--ease-out);
    }

    .site-header.is-scrolled {
      height: 4.5rem;
      background: transparent;
      border-bottom: 0;
      backdrop-filter: none;
    }

    .brand {
      justify-self: start;
      font-family: "Montserrat", var(--font-body);
      font-size: clamp(.84rem, 1.1vw, 1rem);
      font-weight: 800;
      letter-spacing: .06em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .brand .brand-accent { color: var(--moss); }

    .header-actions {
      justify-self: end;
      display: inline-flex;
      align-items: center;
      gap: .35rem;
    }

    .theme-toggle-btn {
      width: 2.75rem; height: 2.75rem;
      display: grid; place-items: center;
      padding: 0; border: 0; border-radius: 50%;
      background: transparent; color: var(--ink); cursor: pointer;
      transition: background-color .25s var(--ease-out);
    }
    .theme-toggle-btn:hover { background: rgb(17 19 15 / 8%); }

    .theme-toggle-btn svg { width: 1.3rem; height: 1.3rem; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }

    .language-picker {
      position: relative;
      display: inline-flex;
      justify-content: flex-end;
    }

    .language-picker::after {
      content: "";
      position: absolute;
      top: 100%;
      right: 0;
      width: 11rem;
      height: 1.1rem;
    }

    .language-button {
      width: 2.75rem;
      height: 2.75rem;
      display: grid;
      place-items: center;
      padding: 0;
      border: 0;
      border-radius: 50%;
      background: transparent;
      color: var(--ink);
      cursor: pointer;
      transition: background-color .25s var(--ease-out);
    }

    .language-button:hover,
    .language-picker.is-open .language-button {
      background: rgb(17 19 15 / 8%);
    }

    .language-globe {
      width: 1.35rem;
      height: 1.35rem;
    }

    .language-flag {
      width: 1.5rem;
      height: 1.05rem;
      display: block;
      flex: none;
      object-fit: cover;
      border-radius: .16rem;
      box-shadow: 0 0 0 1px rgb(17 19 15 / 16%);
    }

    .language-button .language-flag {
      width: 1.72rem;
      height: 1.18rem;
    }

    .language-menu .language-flag {
      margin-left: auto;
    }

    .language-menu {
      position: absolute;
      top: calc(100% + .7rem);
      right: 0;
      width: max-content;
      min-width: 10.5rem;
      padding: .55rem;
      border: 1px solid var(--line);
      background: rgb(247 244 236 / 97%);
      color: var(--ink);
      box-shadow: 0 8px 18px rgb(17 19 15 / 8%);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-.4rem);
      transition: opacity .24s var(--ease-out), transform .24s var(--ease-out);
    }

    .language-picker:hover .language-menu,
    .language-picker:focus-within .language-menu,
    .language-picker.is-open .language-menu {
      opacity: 1;
      pointer-events: auto;
      transform: none;
    }

    .language-menu button {
      width: 100%;
      min-height: 2.45rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      padding: 0 .75rem;
      border: 0;
      background: transparent;
      color: rgb(17 19 15 / 62%);
      cursor: pointer;
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .05em;
      text-align: left;
      text-transform: uppercase;
      transition: color .2s var(--ease-out), background-color .2s var(--ease-out);
      white-space: nowrap;
    }

    .language-menu button:hover,
    .language-menu button[aria-current="true"] {
      background: rgb(17 19 15 / 7%);
      color: var(--ink);
    }

    /* ===== หน้าล็อกอินใหม่: การ์ดกลางจอบนพื้นหลังโต๊ะเต็มจอ ===== */
    .login-bg {
      position: fixed;
      inset: 0;
      z-index: 0;
      background: url('{{ asset('assets/insight/login-desk.png') }}') center/cover no-repeat;
    }
    .login-bg::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgb(250 249 245 / 28%) 0%, rgb(250 249 245 / 6%) 35%, rgb(18 26 34 / 12%) 100%);
    }

    .login-page {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      min-height: 100dvh;
      display: grid;
      place-items: center;
      padding: calc(var(--header-height) + 1.25rem) 1.15rem 2.5rem;
    }

    .login-card {
      width: min(100%, 27rem);
      padding: 2.5rem 2.3rem 2.3rem;
      background: rgb(255 255 255 / 96%);
      border-radius: 0.45rem;
      box-shadow: 0 34px 90px rgb(15 25 35 / 26%), 0 4px 14px rgb(15 25 35 / 12%);
      -webkit-backdrop-filter: blur(2px);
      backdrop-filter: blur(2px);
      animation: cardIn .5s var(--ease-out);
    }
    @keyframes cardIn { from { opacity: 0; transform: translateY(14px) scale(.985); } to { opacity: 1; transform: none; } }

    /* โลโก้ใหม่เป็นสัญลักษณ์ล้วน ไม่มีตัวอักษรในรูป จึงย่อลงแล้ววางชื่อแบรนด์ไว้ใต้ */
    .login-brand { display: grid; justify-items: center; gap: .5rem; margin: .3rem 0 1.2rem; }
    .login-logo {
      display: block;
      width: min(34%, 6.2rem);
      height: auto;
    }
    .login-brand-name {
      color: var(--moss);
      font-size: clamp(1.05rem, 3.2vw, 1.3rem);
      font-weight: 750;
      letter-spacing: .12em;
      line-height: 1.1;
    }
    .login-divider {
      height: 1px;
      margin: 0 0 1.6rem;
      background: rgb(17 24 33 / 14%);
    }

    .login-form {
      display: grid;
      gap: 1.05rem;
    }

    .alert {
      font-size: .9rem;
      font-weight: 600;
    }

    .alert.error {
      color: var(--danger);
    }

    .alert.success {
      color: var(--success);
    }

    .field-input { position: relative; display: flex; align-items: center; }
    .field-icon {
      position: absolute;
      left: .95rem;
      top: 50%;
      transform: translateY(-50%);
      display: grid;
      place-items: center;
      color: rgb(31 45 60 / 60%);
      pointer-events: none;
    }
    .field-icon svg { width: 1.2rem; height: 1.2rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

    .field-input input {
      width: 100%;
      min-height: 3.4rem;
      padding: 0 1rem 0 2.9rem;
      border: 1px solid rgb(31 45 60 / 20%);
      border-radius: 0.3rem;
      outline: 0;
      background: rgb(252 252 251 / 92%);
      color: #1b2a38;
      font-size: .95rem;
      transition: border-color .2s var(--ease-out), box-shadow .2s var(--ease-out);
    }
    .field-input input::placeholder { color: rgb(31 45 60 / 44%); }
    .field-input input:focus {
      border-color: #365a72;
      box-shadow: 0 0 0 3px rgb(54 90 114 / 14%);
    }
    .password-input input { padding-right: 3rem; }

    /* ── ลืมรหัสผ่าน ─────────────────────────────────────
       ลิงก์ตัวหนังสือล้วน ใต้ช่องรหัสผ่าน ชิดขวา ไม่มีกรอบ */
    .forgot-link {
      display: block;
      margin: .5rem 0 0 auto;
      padding: 0;
      border: 0;
      background: transparent;
      color: rgb(31 45 60 / 66%);
      font-size: .82rem;
      cursor: pointer;
      transition: color .2s var(--ease-out);
    }
    .forgot-link:hover { color: #365a72; text-decoration: underline; }
    .forgot-link:focus-visible { outline: 1px solid rgb(54 90 114 / 45%); outline-offset: .2rem; border-radius: .3rem; }

    .forgot-modal {
      position: fixed; inset: 0; z-index: 120;
      display: grid; place-items: center;
      padding: 1.25rem;
      background: rgb(12 18 26 / 52%);
      backdrop-filter: blur(4px);
      opacity: 0; visibility: hidden;
      transition: opacity .25s var(--ease-out), visibility .25s;
    }
    .forgot-modal.is-open { opacity: 1; visibility: visible; }

    .forgot-dialog {
      width: min(100%, 26rem);
      max-height: 92vh; overflow-y: auto;
      padding: clamp(1.5rem, 3vw, 2rem);
      border-radius: 0.4rem;
      background: #fdfdfc;
      box-shadow: 0 30px 70px -24px rgb(12 18 26 / 55%);
      transform: translateY(.6rem);
      transition: transform .25s var(--ease-out);
    }
    .forgot-modal.is-open .forgot-dialog { transform: none; }

    .forgot-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
    .forgot-head h2 { margin: 0; color: #1b2a38; font-size: 1.25rem; font-weight: 600; }
    .forgot-close {
      width: 2rem; height: 2rem; flex: none;
      display: grid; place-items: center;
      border: 0; border-radius: 50%;
      background: transparent; color: rgb(31 45 60 / 55%); cursor: pointer;
    }
    .forgot-close:hover { background: rgb(31 45 60 / 8%); color: #1b2a38; }
    .forgot-close svg { width: 1.15rem; height: 1.15rem; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; }

    .forgot-note { margin: .5rem 0 1.3rem; color: rgb(31 45 60 / 66%); font-size: .85rem; line-height: 1.6; }

    .forgot-field { margin-bottom: 1rem; }
    .forgot-field label {
      display: block; margin-bottom: .4rem;
      color: rgb(31 45 60 / 68%);
      font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    }
    .forgot-field input {
      width: 100%; min-height: 3rem;
      padding: .7rem .9rem;
      border: 1px solid rgb(31 45 60 / 20%);
      border-radius: 0.28rem;
      outline: 0;
      background: rgb(252 252 251 / 92%);
      color: #1b2a38; font-size: .92rem;
      transition: border-color .2s var(--ease-out), box-shadow .2s var(--ease-out);
    }
    .forgot-field input:focus { border-color: #365a72; box-shadow: 0 0 0 3px rgb(54 90 114 / 14%); }
    .forgot-field input:disabled { opacity: .45; cursor: not-allowed; }
    .forgot-field input::placeholder { color: rgb(31 45 60 / 44%); }

    .forgot-row { display: flex; gap: .55rem; }
    .forgot-row input { flex: 1; }
    .forgot-verify {
      flex: none; min-width: 6rem;
      padding: 0 .9rem;
      border: 1px solid rgb(54 90 114 / 55%); border-radius: 0.28rem;
      background: rgb(54 90 114 / 10%);
      color: #24455c; font-size: .82rem; font-weight: 700; cursor: pointer;
      transition: background-color .2s var(--ease-out);
    }
    .forgot-verify:hover { background: rgb(54 90 114 / 20%); }

    .forgot-msg { margin-top: .4rem; font-size: .8rem; color: var(--success); min-height: 1rem; }
    .forgot-msg.err { color: var(--danger); }

    .forgot-submit {
      width: 100%; min-height: 3.1rem; margin-top: .4rem;
      border: 0; border-radius: 0.3rem;
      background: #223a4a; color: #fff;
      font-size: .95rem; font-weight: 600; cursor: pointer;
      transition: background-color .2s var(--ease-out), opacity .2s var(--ease-out);
    }
    .forgot-submit:hover:not(:disabled) { background: #2c485e; }
    .forgot-submit:disabled { opacity: .45; cursor: not-allowed; }

    .password-toggle {
      position: absolute;
      top: 0;
      right: 0;
      width: 2.9rem;
      height: 100%;
      display: grid;
      place-items: center;
      padding: 0;
      border: 0;
      background: transparent;
      color: rgb(31 45 60 / 50%);
      cursor: pointer;
      transition: color .2s var(--ease-out);
    }

    .password-toggle:hover,
    .password-toggle:focus-visible {
      color: #1b2a38;
      outline: none;
    }

    .password-toggle svg {
      width: 1.25rem;
      height: 1.25rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.75;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .password-toggle .icon-eye-off,
    .password-toggle[aria-pressed="true"] .icon-eye {
      display: none;
    }

    .password-toggle[aria-pressed="true"] .icon-eye-off {
      display: block;
    }

    .err-text {
      margin-top: .45rem;
      color: var(--danger);
      font-size: .82rem;
    }

    .login-btn {
      width: 100%;
      min-height: 3.4rem;
      margin-top: .5rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 0;
      border-radius: 0.3rem;
      background: #22384a;
      color: #fff;
      cursor: pointer;
      font-size: .98rem;
      font-weight: 700;
      letter-spacing: .02em;
      transition: background-color .2s var(--ease-out), transform .15s var(--ease-out), box-shadow .2s var(--ease-out);
    }
    .login-btn:hover { background: #2c485e; transform: translateY(-1px); box-shadow: 0 10px 24px rgb(34 56 74 / 32%); }
    .login-btn:active { transform: translateY(0); }
    .login-btn:focus-visible { outline: 2px solid rgb(54 90 114 / 55%); outline-offset: 2px; }

    @media (max-width: 920px) {
      :root { --header-height: 4.75rem; }
      .site-header { grid-template-columns: 1fr auto; padding-inline: 1.25rem; }
      .header-actions { grid-column: 2; }
    }

    @media (max-width: 520px) {
      .brand { font-size: .76rem; letter-spacing: .04em; }
      .login-card { padding: 2rem 1.5rem 1.9rem; border-radius: 0.45rem; }
      .login-logo { width: min(38%, 5.4rem); }
      .page-loader span { font-size: clamp(1.85rem, 9vw, 2.8rem); letter-spacing: .035em; }
    }

    @media (prefers-reduced-motion: reduce) {
      *,
      *::before,
      *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
      }
    }
  </style>
</head>
<body>
  <div class="page-loader" id="pageLoader" aria-hidden="true">
    <span data-i18n="loader.signin">กำลังเข้าสู่ระบบ</span>
  </div>

  <header class="site-header" id="siteHeader">
    <a class="brand" href="{{ route('landing') }}">SUPAVUT <span class="brand-accent">INSIGHT</span></a>
    <div class="header-actions">
      <div class="language-picker" data-language-picker>
      <button class="language-button" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Language" data-language-toggle>
        <img class="language-flag" data-language-current-flag data-flag-src-th="{{ asset('assets/insight/flags/th.png') }}" data-flag-src-en="{{ asset('assets/insight/flags/en.png') }}" data-flag-src-my="{{ asset('assets/insight/flags/my.png') }}" src="{{ asset('assets/insight/flags/en.png') }}" alt="" aria-hidden="true">
      </button>
      <div class="language-menu" role="menu" aria-label="Language">
        <button type="button" data-lang-option="th" role="menuitemradio" aria-current="false"><span>ไทย</span><img class="language-flag" src="{{ asset('assets/insight/flags/th.png') }}" alt="" aria-hidden="true"></button>
        <button type="button" data-lang-option="en" role="menuitemradio" aria-current="true"><span>ENGLISH</span><img class="language-flag" src="{{ asset('assets/insight/flags/en.png') }}" alt="" aria-hidden="true"></button>
        <button type="button" data-lang-option="my" role="menuitemradio" aria-current="false"><span>မြန်မာ</span><img class="language-flag" src="{{ asset('assets/insight/flags/my.png') }}" alt="" aria-hidden="true"></button>
      </div>
      </div>
    </div>
  </header>

  <div class="login-bg" aria-hidden="true"></div>

  <main class="login-page">
    <div class="login-card">
      <div class="login-brand">
        {{-- ?v= บังคับให้เบราว์เซอร์โหลดโลโก้ใหม่ ไม่งั้นจะค้างรูปเดิมที่แคชไว้ --}}
        <img class="login-logo" src="{{ asset('assets/insight/brand-logo.png').'?v=2' }}" alt="">
        <span class="login-brand-name">SUPAVUT INSIGHT</span>
      </div>
      <div class="login-divider"></div>

      <form class="login-form" method="POST" action="{{ route('login.attempt') }}" id="loginForm">
        @csrf

        @if (session('error')) <div class="alert error">{{ session('error') }}</div> @endif
        @if (session('success')) <div class="alert success">{{ session('success') }}</div> @endif

        <div class="field">
          <div class="field-input">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
            </span>
            <input type="text" id="employee_code" name="employee_code" value="{{ old('employee_code') }}" autofocus autocomplete="username" placeholder="รหัสพนักงาน" data-i18n-placeholder="form.employee.placeholder" data-i18n-aria="form.employee" aria-label="รหัสพนักงาน">
          </div>
          @error('employee_code') <div class="err-text">{{ $message }}</div> @enderror
        </div>

        <div class="field">
          <div class="field-input password-input">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><rect x="4" y="10.5" width="16" height="10.5" rx="2"></rect><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"></path></svg>
            </span>
            <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Password" data-i18n-placeholder="form.password.placeholder" data-i18n-aria="form.password" aria-label="รหัสผ่าน">
            <button class="password-toggle" type="button" aria-controls="password" aria-label="Show password" aria-pressed="false" data-password-toggle>
              <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true">
                <path d="m3 3 18 18"></path>
                <path d="M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-1.2"></path>
                <path d="M9.9 4.4A10.8 10.8 0 0 1 12 4.2c6.5 0 10 7.8 10 7.8a18 18 0 0 1-3.1 4.2"></path>
                <path d="M6.6 6.7C3.6 8.8 2 12 2 12s3.5 7.8 10 7.8a10.4 10.4 0 0 0 4-.8"></path>
              </svg>
            </button>
          </div>
          @error('password') <div class="err-text">{{ $message }}</div> @enderror
          {{-- ลืมรหัสผ่าน — ตัวหนังสือล้วนไม่มีกรอบ อยู่ใต้ช่องรหัสผ่านชิดขวา --}}
          <button type="button" class="forgot-link" id="forgotOpen" data-i18n="forgot.link">Forgot password?</button>
        </div>

        <button type="submit" class="login-btn"><span data-i18n="form.submit">Login</span></button>
      </form>
    </div>
  </main>

  {{-- ตั้งรหัสผ่านใหม่โดยยังไม่ได้ล็อกอิน — ยืนยันด้วยรหัสพนักงาน + เลขบัตรประชาชน --}}
  <div class="forgot-modal {{ $errors->has('forgot_id_card') || session('forgot_open') ? 'is-open' : '' }}"
       id="forgotModal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
    <div class="forgot-dialog">
      <div class="forgot-head">
        <h2 id="forgotTitle" data-i18n="forgot.title">Reset password</h2>
        <button type="button" class="forgot-close" id="forgotClose" aria-label="ปิด" data-i18n-aria="common.close">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>

      <p class="forgot-note" data-i18n="forgot.note">Confirm your identity with your employee code and ID card number.</p>

      <form method="POST" action="{{ route('password.forgot.update') }}" id="forgotForm">
        @csrf
        @method('PUT')

        <div class="forgot-field">
          <label for="forgot_employee_code" data-i18n="forgot.employee">Employee code</label>
          <input type="text" id="forgot_employee_code" name="employee_code" autocomplete="off"
                 value="{{ old('employee_code') }}" placeholder="รหัสพนักงาน" data-i18n-placeholder="form.employee.placeholder">
        </div>

        <div class="forgot-field">
          <label for="forgot_id_card" data-i18n="forgot.idcard">ID card number</label>
          <div class="forgot-row">
            <input type="text" id="forgot_id_card" name="id_card" inputmode="numeric" autocomplete="off"
                   placeholder="เลขบัตรประชาชน" data-i18n-placeholder="forgot.idcard.placeholder">
            <button type="button" class="forgot-verify" id="forgotVerify" data-i18n="forgot.verify">Verify</button>
          </div>
          <div class="forgot-msg {{ $errors->has('forgot_id_card') ? 'err' : '' }}" id="forgotMsg">
            @error('forgot_id_card') {{ $message }} @enderror
          </div>
        </div>

        @php $forgotUnlocked = $errors->has('password'); @endphp

        <div class="forgot-field">
          <label for="forgot_password" data-i18n="forgot.new">New password</label>
          <input type="password" id="forgot_password" name="password" autocomplete="new-password"
                 placeholder="อย่างน้อย 6 ตัวอักษร" data-i18n-placeholder="forgot.new.placeholder"
                 {{ $forgotUnlocked ? '' : 'disabled' }}>
          @error('password') <div class="forgot-msg err">{{ $message }}</div> @enderror
        </div>

        <div class="forgot-field">
          <label for="forgot_password_confirmation" data-i18n="forgot.confirm">Confirm new password</label>
          <input type="password" id="forgot_password_confirmation" name="password_confirmation" autocomplete="new-password"
                 placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" data-i18n-placeholder="forgot.confirm.placeholder"
                 {{ $forgotUnlocked ? '' : 'disabled' }}>
        </div>

        <button type="submit" class="forgot-submit" id="forgotSubmit" {{ $forgotUnlocked ? '' : 'disabled' }}
                data-i18n="forgot.submit">Save new password</button>
      </form>
    </div>
  </div>

  <script>
    'use strict';

    (function () {
      var languageStoreKey = 'insight_lang';
      var copy = {
        en: {
          text: {
            'nav.home': 'Home',
            'nav.login': 'Login',
            'login.label': 'Sign in',
            'form.employee': 'Employee Code',
            'form.employee.placeholder': 'Employee code',
            'form.password': 'ID Card Number Or Password',
            'form.password.placeholder': 'Password',
            'form.password.show': 'Show password',
            'form.password.hide': 'Hide password',
            'form.submit': 'Login',
            'loader.signin': 'Signing in',
            'forgot.link': 'Forgot password?',
            'forgot.title': 'Reset password',
            'forgot.note': 'Confirm your identity with your employee code and ID card number, then set a new password.',
            'forgot.employee': 'Employee code',
            'forgot.idcard': 'ID card number',
            'forgot.idcard.placeholder': 'ID card number',
            'forgot.verify': 'Verify',
            'forgot.new': 'New password',
            'forgot.new.placeholder': 'At least 6 characters',
            'forgot.confirm': 'Confirm new password',
            'forgot.confirm.placeholder': 'Repeat the new password',
            'forgot.submit': 'Save new password',
            'forgot.checking': 'Checking',
            'forgot.ok': 'Verified. Set your new password.',
            'forgot.failed': 'Employee code or ID card number is incorrect',
            'common.close': 'Close'
          }
        },
        th: {
          text: {
            'nav.home': 'หน้าแรก',
            'nav.login': 'เข้าสู่ระบบ',
            'login.label': 'การเข้าสู่ระบบ',
            'form.employee': 'รหัสพนักงาน',
            'form.employee.placeholder': 'รหัสพนักงาน',
            'form.password': 'เลขบัตรประชาชนหรือรหัสผ่าน',
            'form.password.placeholder': 'รหัสผ่าน',
            'form.password.show': 'แสดงรหัสผ่าน',
            'form.password.hide': 'ซ่อนรหัสผ่าน',
            'form.submit': 'เข้าสู่ระบบ',
            'loader.signin': 'กำลังเข้าสู่ระบบ',
            'forgot.link': 'ลืมรหัสผ่าน',
            'forgot.title': 'ตั้งรหัสผ่านใหม่',
            'forgot.note': 'ยืนยันตัวตนด้วยรหัสพนักงานและเลขบัตรประชาชน แล้วตั้งรหัสผ่านใหม่ได้เลย',
            'forgot.employee': 'รหัสพนักงาน',
            'forgot.idcard': 'เลขบัตรประชาชน',
            'forgot.idcard.placeholder': 'เลขบัตรประชาชน',
            'forgot.verify': 'ตรวจสอบ',
            'forgot.new': 'รหัสผ่านใหม่',
            'forgot.new.placeholder': 'อย่างน้อย 6 ตัวอักษร',
            'forgot.confirm': 'ยืนยันรหัสผ่านใหม่',
            'forgot.confirm.placeholder': 'กรอกรหัสผ่านใหม่อีกครั้ง',
            'forgot.submit': 'บันทึกรหัสผ่านใหม่',
            'forgot.checking': 'กำลังตรวจสอบ',
            'forgot.ok': 'ยืนยันตัวตนแล้ว ตั้งรหัสผ่านใหม่ได้เลย',
            'forgot.failed': 'รหัสพนักงานหรือเลขบัตรประชาชนไม่ถูกต้อง',
            'common.close': 'ปิด'
          }
        },
        my: {
          text: {
            'nav.home': 'ပင်မ',
            'nav.login': 'ဝင်ရန်',
            'login.label': 'ဝင်ရောက်ခြင်း',
            'form.employee': 'ဝန်ထမ်းကုဒ်',
            'form.employee.placeholder': 'ဝန်ထမ်းကုဒ်',
            'form.password': 'မှတ်ပုံတင်နံပါတ် သို့မဟုတ် စကားဝှက်',
            'form.password.placeholder': 'စကားဝှက်',
            'form.password.show': 'စကားဝှက်ကို ပြရန်',
            'form.password.hide': 'စကားဝှက်ကို ဖျောက်ရန်',
            'form.submit': 'ဝင်ရန်',
            'loader.signin': 'ဝင်ရောက်နေသည်',
            'forgot.link': 'စကားဝှက် မေ့နေပါသလား?',
            'forgot.title': 'စကားဝှက် အသစ်သတ်မှတ်ရန်',
            'forgot.note': 'ဝန်ထမ်းကုဒ်နှင့် မှတ်ပုံတင်နံပါတ်ဖြင့် အတည်ပြုပြီး စကားဝှက်အသစ် သတ်မှတ်ပါ',
            'forgot.employee': 'ဝန်ထမ်းကုဒ်',
            'forgot.idcard': 'မှတ်ပုံတင်နံပါတ်',
            'forgot.idcard.placeholder': 'မှတ်ပုံတင်နံပါတ်',
            'forgot.verify': 'စစ်ဆေးရန်',
            'forgot.new': 'စကားဝှက်အသစ်',
            'forgot.new.placeholder': 'အနည်းဆုံး ၆ လုံး',
            'forgot.confirm': 'စကားဝှက်အသစ် အတည်ပြုရန်',
            'forgot.confirm.placeholder': 'စကားဝှက်အသစ်ကို ထပ်ရိုက်ပါ',
            'forgot.submit': 'စကားဝှက်အသစ် သိမ်းရန်',
            'forgot.checking': 'စစ်ဆေးနေသည်',
            'forgot.ok': 'အတည်ပြုပြီးပါပြီ စကားဝှက်အသစ် သတ်မှတ်ပါ',
            'forgot.failed': 'ဝန်ထမ်းကုဒ် သို့မဟုတ် မှတ်ပုံတင်နံပါတ် မမှန်ကန်ပါ',
            'common.close': 'ပိတ်ရန်'
          }
        }
      };

      var activeLang = 'en';
      var header = document.getElementById('siteHeader');
      var updateHeader = function () {
        header.classList.toggle('is-scrolled', window.scrollY > 24);
      };
      window.addEventListener('scroll', updateHeader, { passive: true });
      updateHeader();

      initLanguage();
      initPasswordToggle();
      initForgotPassword();
      initSubmitLoader();

      function initLanguage() {
        var pickers = document.querySelectorAll('[data-language-picker]');
        var saved = 'th';
        try { saved = localStorage.getItem(languageStoreKey) || 'th'; } catch (e) {}

        pickers.forEach(function (picker) {
          var toggle = picker.querySelector('[data-language-toggle]');
          if (!toggle) return;

          toggle.addEventListener('click', function () {
            var isOpen = picker.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
          });

          picker.querySelectorAll('[data-lang-option]').forEach(function (option) {
            option.addEventListener('click', function () {
              setLanguage(option.getAttribute('data-lang-option'));
              picker.classList.remove('is-open');
              toggle.setAttribute('aria-expanded', 'false');
            });
          });
        });

        document.addEventListener('click', function (event) {
          pickers.forEach(function (picker) {
            if (picker.contains(event.target)) return;
            picker.classList.remove('is-open');
            var toggle = picker.querySelector('[data-language-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
          });
        });

        setLanguage(saved);
      }

      function setLanguage(lang) {
        if (!copy[lang]) lang = 'en';
        activeLang = lang;
        var current = copy[lang];
        document.documentElement.lang = lang;
        document.documentElement.setAttribute('data-lang', lang);

        document.querySelectorAll('[data-i18n]').forEach(function (node) {
          var key = node.getAttribute('data-i18n');
          if (current.text[key]) node.textContent = current.text[key];
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(function (node) {
          var key = node.getAttribute('data-i18n-placeholder');
          if (current.text[key]) node.setAttribute('placeholder', current.text[key]);
        });

        document.querySelectorAll('[data-i18n-aria]').forEach(function (node) {
          var key = node.getAttribute('data-i18n-aria');
          if (current.text[key]) node.setAttribute('aria-label', current.text[key]);
        });

        document.querySelectorAll('[data-lang-option]').forEach(function (node) {
          node.setAttribute('aria-current', node.getAttribute('data-lang-option') === lang ? 'true' : 'false');
        });

        document.querySelectorAll('[data-language-current-flag]').forEach(function (node) {
          var src = node.getAttribute('data-flag-src-' + lang);
          if (src) node.setAttribute('src', src);
        });

        document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
          var input = document.getElementById(toggle.getAttribute('aria-controls'));
          updatePasswordToggleLabel(toggle, input);
        });

        document.title = current.text['login.label'] + ' | SUPAVUT INSIGHT';

        try { localStorage.setItem(languageStoreKey, lang); } catch (e) {}
      }

      /**
       * ลืมรหัสผ่าน — ยืนยันตัวตนก่อน แล้วจึงปลดล็อกช่องตั้งรหัสใหม่
       *
       * flow เดียวกับ modal เปลี่ยนรหัสผ่านในหน้าโปรไฟล์ ต่างกันแค่ตรงนี้ยังไม่ได้ล็อกอิน
       * จึงต้องกรอกรหัสพนักงานคู่กับเลขบัตรประชาชน
       */
      function initForgotPassword() {
        var modal = document.getElementById('forgotModal');
        var openBtn = document.getElementById('forgotOpen');
        if (!modal || !openBtn) return;

        var closeBtn = document.getElementById('forgotClose');
        var verifyBtn = document.getElementById('forgotVerify');
        var codeInput = document.getElementById('forgot_employee_code');
        var idInput = document.getElementById('forgot_id_card');
        var passwordInput = document.getElementById('forgot_password');
        var confirmInput = document.getElementById('forgot_password_confirmation');
        var submitBtn = document.getElementById('forgotSubmit');
        var message = document.getElementById('forgotMsg');
        var verifyUrl = "{{ route('password.forgot.verify') }}";
        var token = document.querySelector('#loginForm input[name="_token"]').value;

        function say(key, isError) {
          var current = copy[activeLang] || copy.en;
          message.textContent = current.text[key] || '';
          message.classList.toggle('err', !!isError);
        }

        function lock() {
          [passwordInput, confirmInput, submitBtn].forEach(function (node) { node.disabled = true; });
        }

        function unlock() {
          [passwordInput, confirmInput, submitBtn].forEach(function (node) { node.disabled = false; });
          passwordInput.focus();
        }

        function open() {
          modal.classList.add('is-open');
          // ยกรหัสพนักงานที่พิมพ์ไว้ในหน้า login มาให้เลย จะได้ไม่ต้องพิมพ์ซ้ำ
          if (!codeInput.value) { codeInput.value = document.getElementById('employee_code').value.trim(); }
          (codeInput.value ? idInput : codeInput).focus();
        }

        function close() {
          modal.classList.remove('is-open');
          openBtn.focus();
        }

        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        modal.addEventListener('click', function (event) { if (event.target === modal) { close(); } });
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) { close(); }
        });

        // แก้ข้อมูลยืนยันตัวตนเมื่อไร = ต้องตรวจใหม่
        [codeInput, idInput].forEach(function (input) {
          input.addEventListener('input', function () {
            lock();
            message.textContent = '';
            message.classList.remove('err');
          });
        });

        verifyBtn.addEventListener('click', function () {
          if (!codeInput.value.trim() || !idInput.value.trim()) {
            say('forgot.failed', true);
            return;
          }

          say('forgot.checking', false);

          fetch(verifyUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': token,
              Accept: 'application/json'
            },
            body: JSON.stringify({
              employee_code: codeInput.value.trim(),
              id_card: idInput.value.trim()
            })
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (data.ok) {
                say('forgot.ok', false);
                unlock();
              } else {
                say('forgot.failed', true);
                lock();
              }
            })
            .catch(function () { say('forgot.failed', true); });
        });
      }

      function initPasswordToggle() {
        var input = document.getElementById('password');
        var toggle = document.querySelector('[data-password-toggle]');
        if (!input || !toggle) return;

        toggle.addEventListener('click', function () {
          var shouldShow = input.type === 'password';
          input.type = shouldShow ? 'text' : 'password';
          toggle.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
          updatePasswordToggleLabel(toggle, input);

          try {
            input.focus({ preventScroll: true });
          } catch (e) {
            input.focus();
          }
        });

        updatePasswordToggleLabel(toggle, input);
      }

      function updatePasswordToggleLabel(toggle, input) {
        var current = copy[activeLang] || copy.en;
        var key = input && input.type === 'text' ? 'form.password.hide' : 'form.password.show';
        if (current.text[key]) toggle.setAttribute('aria-label', current.text[key]);
      }

      function initSubmitLoader() {
        var form = document.getElementById('loginForm');
        var loader = document.getElementById('pageLoader');
        if (!form || !loader) return;

        form.addEventListener('submit', function () {
          loader.classList.add('is-active');
        });
      }
    })();
  </script>
</body>
</html>
