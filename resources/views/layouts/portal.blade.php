@php
  // บริบทระบบย่อย คำนวณตั้งแต่ <head> เพราะใช้เลือกไอคอนบนแท็บเบราว์เซอร์ด้วย
  $sysContext = request()->routeIs('recruit.*')
    ? 'recruit'
    : (request()->routeIs('assessment.*')
      ? 'assessment'
      : (request()->routeIs('area5s.*')
        ? 'area5s'
        : (request()->routeIs('ot-approval.*') ? 'ot-approval' : null)));

  // ไอคอนแท็บเบราว์เซอร์: ใช้โลโก้เดียวกับการ์ดในหน้า /systems ให้รู้ว่าอยู่แอปไหน
  $portalFaviconPath = match ($sysContext) {
    'ot-approval' => 'assets/systems/time-leave-approval.png?v=1',
    'assessment' => 'assets/systems/assessment.png?v=2',
    'recruit' => 'assets/systems/recruit.png',
    'area5s' => 'assets/area5s/logo-5s.png?v=2',
    // ?v= บังคับให้เบราว์เซอร์โหลดไอคอนใหม่ ไม่งั้นจะค้างรูปเดิมที่แคชไว้
    default => 'assets/insight/favicon.png?v=4',
  };
  $portalFavicon = asset($portalFaviconPath);
@endphp
<!DOCTYPE html>
<html lang="th" data-lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#090a09">
  <title>@yield('title', 'Dashboard') | SUPAVUT INSIGHT</title>
  <link rel="icon" type="image/png" href="{{ $portalFavicon }}">
  <link rel="shortcut icon" href="{{ $portalFavicon }}">
  <link rel="apple-touch-icon" href="{{ $sysContext ? $portalFavicon : asset('assets/insight/apple-touch-icon.png') }}">
  <script>
    document.documentElement.classList.add('js');
    /* ธีมสว่างอย่างเดียวทั้งระบบ (Manager สั่ง 2026-08-19 ให้เอาธีมมืดออกทุกหน้า)
       ตั้งค่าตายตัวและล้างค่าที่เคยเลือกไว้ ไม่งั้นคนที่เคยกดมืดจะยังเห็นมืดอยู่
       CSS ของธีมมืดยังคงไว้เผื่อกลับมาใช้ แต่จะไม่ถูกเรียกเพราะไม่มีใครตั้ง data-theme=dark ได้อีก */
    document.documentElement.setAttribute('data-theme', 'light');
    try { localStorage.removeItem('insight_theme'); } catch (e) {}
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600&family=Italiana&family=Jost:wght@400;500;600&family=Montserrat:wght@700;800&family=Noto+Serif+Thai:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --near-black: #ffffff;
      --panel: #ffffff;
      --panel-soft: #ffffff;
      --moss: #5b8def;
      --light-text: #000000;
      --muted-light: rgb(0 0 0 / 62%);
      --line-light: rgb(0 0 0 / 12%);
      --line-strong: rgb(0 0 0 / 22%);
      --danger: #9f2f26;
      --success: #1d7a42;
      /* พื้นการ์ด/แผงเป็นเทาอ่อน แยกจากพื้นหน้าสีขาว (Manager สั่ง 2026-08-26 — เริ่มจากหน้างานพื้นที่ของฉัน)
         ผสมจาก --light-text ของธีม จึงถูกทั้งธีมสว่าง (ดำจาง) และมืด (ขาวจาง) โดยไม่ต้องเขียนค่าแยก */
      --panel-tint: color-mix(in srgb, var(--light-text) 5%, var(--panel-soft));
      --panel-tint-strong: color-mix(in srgb, var(--light-text) 8%, var(--panel-soft));
      --page-pad: clamp(1rem, 2.8vw, 2.75rem);
      --sidebar-w: 15.5rem;
      --topbar-h: 4.35rem;
      --font-body: "Jost", "Anuphan", sans-serif;
      --font-display: "Italiana", "Noto Serif Thai", serif;
      --ease-out: cubic-bezier(.16, 1, .3, 1);
      /* เมาส์ชี้เป็นโทนน้ำเงินของแบรนด์ แทนเทาโปร่งเดิม (Manager สั่ง 2026-08-19) */
      --hover-soft: rgb(30 58 138 / 9%);
      --hover-soft-2: rgb(30 58 138 / 14%);
      --bar-bg: rgb(255 255 255 / 94%);
      --menu-bg: rgb(255 255 255 / 98%);
      --overlay-bg: rgb(0 0 0 / 80%);
      /* โทนพาสเทลของ OT — ผสมสีแบรนด์ลงพื้นบาง ๆ เพื่อไล่ชั้น บริษัท → แผนก → เอกสาร */
      --ot-tint-company: color-mix(in srgb, var(--moss) 7%, var(--panel));
      --ot-tint-card: color-mix(in srgb, var(--moss) 4%, var(--panel));
      --ot-tint-card-hover: color-mix(in srgb, var(--moss) 9%, var(--panel));
      --ot-tint-sheet: color-mix(in srgb, var(--moss) 5%, var(--panel));
    }

    /* ── ธีมสว่าง (light): พื้นขาว ตัวอักษรดำ — สลับด้วย data-theme ── */
    html[data-theme="light"] {
      --near-black: #ffffff;
      --panel: #ffffff;
      --panel-soft: #ffffff;
      --moss: #1e3a8a;
      --light-text: #000000;
      --muted-light: rgb(0 0 0 / 62%);
      --line-light: rgb(0 0 0 / 12%);
      --line-strong: rgb(0 0 0 / 22%);
      --danger: #9f2f26;
      --success: #1d7a42;
      /* เมาส์ชี้เป็นโทนน้ำเงินของแบรนด์ แทนเทาโปร่งเดิม (Manager สั่ง 2026-08-19) */
      --hover-soft: rgb(30 58 138 / 9%);
      --hover-soft-2: rgb(30 58 138 / 14%);
      --bar-bg: rgb(255 255 255 / 94%);
      --menu-bg: rgb(255 255 255 / 98%);
      --overlay-bg: rgb(0 0 0 / 80%);
      --ot-tint-company: color-mix(in srgb, var(--moss) 7%, var(--panel));
      --ot-tint-card: color-mix(in srgb, var(--moss) 4%, var(--panel));
      --ot-tint-card-hover: color-mix(in srgb, var(--moss) 9%, var(--panel));
      --ot-tint-sheet: color-mix(in srgb, var(--moss) 5%, var(--panel));
    }

    *, *::before, *::after { box-sizing: border-box; }
    * { margin: 0; }

    html { background: var(--near-black); transition: background-color .3s var(--ease-out); }

    /* ── Custom cursor (Anathema dark green) ── */
    :root {
      /* ลูกศรน้ำเงินของแบรนด์ — hotspot 4 2 ให้ปลายลูกศรตรงกับจุดคลิกจริง
         ต้องมี auto/pointer ปิดท้ายเสมอ ถ้าไฟล์โหลดไม่ได้จะได้ยังมีเคอร์เซอร์ */
      --cursor-default: url("/assets/insight/cursor.png") 4 2, auto;
      --cursor-action: url("/assets/insight/cursor.png") 4 2, pointer;
      --cursor-disabled: url("/assets/insight/cursor.png") 4 2, not-allowed;
    }

    /* ปุ่ม "‹ กลับ" ของระบบ 5ส — หน้าตาและความสูงเดียวกันทุกหน้า (Manager สั่ง 2026-08-27)
       เดิมแต่ละหน้ามีปุ่มกลับของตัวเอง ทั้งข้อความและขนาดไม่ตรงกัน */
    .a5s-back {
      display:inline-flex; align-items:center; gap:.3rem;
      /* justify-self กันปุ่มยืดเต็มแถวเมื่อพ่ออยู่ใน grid (เช่น panel ประวัติ) — flex จะเมินค่านี้เอง */
      justify-self:start; width:fit-content;
      height:2.1rem; padding:0 .85rem 0 .7rem;
      border:1px solid var(--line-light); border-radius: 0.25rem;
      background:var(--menu-bg); color:var(--light-text);
      font:inherit; font-size:.78rem; font-weight:700; text-decoration:none; cursor:pointer;
      transition:border-color .16s ease, color .16s ease;
    }
    .a5s-back:hover, .a5s-back:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    .a5s-back > span[aria-hidden] { font-size:1.15rem; font-weight:900; line-height:1; }
    html, body, * { cursor: var(--cursor-current, var(--cursor-default)); }
    a, button, [href], [role="button"], [role="menuitem"], [role="menuitemradio"], summary, label, select,
    .nav-go, .nav-item, .icon-toggle, .language-button, [data-language-toggle], [data-lang-option],
    [data-image-preview], [data-image-viewer-close], [data-system-toggle], [data-password-toggle],
    .set-card > summary, .admin-company > summary, .pos-item, .dept-row, .emp-close, .emp-avatar,
    .avatar-lg, .avatar-upload, .email-save, .pwd-change-btn, .pwd-close, .pwd-eye-btn, .pwd-verify-btn,
    .pwd-submit, .sig-btn, .sig-save, .set-mini, .set-save, .rc-x, .rc-add, .rc-close, .rc-result,
    .rc-step-card, .rc-add-step, .rc-dept-btn, .rc-save,
    [onclick], [tabindex]:not([tabindex="-1"]),
    input[type="checkbox"], input[type="radio"], input[type="file"], input[type="submit"], input[type="button"],
    input[type="range"], input[type="color"]
      { --cursor-current: var(--cursor-action); cursor: var(--cursor-action) !important; }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="search"], input[type="tel"], textarea, [contenteditable="true"]
      { --cursor-current: text; cursor: text !important; }
    button:disabled, [aria-disabled="true"], [disabled] { --cursor-current: var(--cursor-disabled); cursor: var(--cursor-disabled) !important; }

    body {
      min-width: 320px;
      min-height: 100vh;
      min-height: 100dvh;
      background: var(--near-black);
      color: var(--light-text);
      font-family: var(--font-body);
      font-size: clamp(.9rem, .86rem + .12vw, .96rem);
      line-height: 1.7;
      -webkit-font-smoothing: antialiased;
    }

    html[data-lang="my"] body,
    html[data-lang="my"] button,
    html[data-lang="my"] input {
      font-family: "Noto Sans Myanmar", "Myanmar Text", "Pyidaungsu", var(--font-body);
      line-height: 1.85;
    }

    a { color: inherit; text-decoration: none; }
    button, input { color: inherit; font: inherit; }
    img { display: block; max-width: 100%; }

    :focus-visible { outline: 2px solid var(--moss); outline-offset: 4px; }
    ::selection { background: var(--moss); color: var(--near-black); }

    .sr-only {
      position: absolute; width: 1px; height: 1px; padding: 0;
      overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
    }

    /* ── ภาษา: สลับค่าข้อมูล TH / EN (พม่าใช้ข้อมูล EN) ── */
    [data-val] { display: none; }
    html[data-lang="th"] [data-val="th"],
    html[data-lang="en"] [data-val="en"],
    html[data-lang="my"] [data-val="en"] { display: inline; }

    /* ── Loader (spinner): เบลอหน้าปัจจุบันแล้ว fade-in (ไม่เลื่อนฉากลง) ── */
    .portal-loader {
      position: fixed;
      inset: 0;
      z-index: 80;
      display: grid;
      place-items: center;
      background: rgb(0 0 0 / 52%);
      -webkit-backdrop-filter: blur(8px);
      backdrop-filter: blur(8px);
      color: #ffffff;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity .22s var(--ease-out), visibility .22s;
    }

    html[data-theme="light"] .portal-loader { background: rgb(255 255 255 / 55%); color: #000000; }

    .portal-loader.is-active {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }

    /* ฉากแบบ Sign in (logout): ทึบเต็ม + เลื่อนลง ไม่เบลอ */
    .portal-loader.is-scene {
      background: #000000;
      -webkit-backdrop-filter: none;
      backdrop-filter: none;
      opacity: 1;
      visibility: visible;
      transform: translateY(-100%);
      transition: none; /* เริ่มที่ตำแหน่งบนสุดทันที (ไม่ animate ตอนตั้งค่า) */
      will-change: transform;
    }

    html[data-theme="light"] .portal-loader.is-scene { background: #ffffff; }

    .portal-loader.is-scene.is-active {
      transform: none;
      transition: transform .6s cubic-bezier(.76, 0, .24, 1); /* แล้วค่อยเลื่อนลง */
    }

    .portal-loader-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.5rem;
      padding: 1.5rem;
    }

    .portal-spinner {
      width: 2.8rem;
      height: 2.8rem;
      border: 3px solid currentColor;
      border-right-color: transparent;
      border-radius: 50%;
      opacity: .9;
      animation: portal-spin .25s linear infinite;
    }

    @keyframes portal-spin { to { transform: rotate(360deg); } }

    .portal-loader p {
      color: inherit;
      font-family: var(--font-display);
      font-size: clamp(1.4rem, 3.4vw, 2.1rem);
      font-weight: 400;
      letter-spacing: .02em;
      line-height: 1.25;
      text-align: center;
    }

    html[data-lang="my"] .portal-loader p {
      font-family: "Noto Sans Myanmar", "Myanmar Text", "Pyidaungsu", var(--font-body);
      font-size: clamp(1.2rem, 3vw, 1.7rem);
      letter-spacing: 0;
      line-height: 1.5;
    }

    /* ── โหมดฉากแบบ Sign in (ใช้ตอน logout): ไม่มี spinner, ตัวใหญ่ + ขีดเส้นใต้ ── */
    .portal-loader.is-scene .portal-spinner { display: none; }
    .portal-loader.is-scene .portal-loader-box { gap: 0; }
    .portal-loader.is-scene p {
      position: relative;
      padding-bottom: .35rem;
      font-size: clamp(2.6rem, 7vw, 5.5rem);
      letter-spacing: .06em;
    }
    .portal-loader.is-scene p::after {
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
    html[data-lang="my"] .portal-loader.is-scene p { font-size: clamp(1.8rem, 5vw, 3.6rem); letter-spacing: 0; }

    /* ── Top header bar (แยก layout, เต็มความกว้าง, อยู่บนสุดของทุกหน้า) ── */
    .app-topbar {
      position: fixed;
      inset: 0 0 auto;
      z-index: 60;
      display: grid;
      /* คอลัมน์แบรนด์กว้าง "อย่างน้อยเท่า sidebar แต่ยืดตามชื่อได้"
         ของเดิมตรึงไว้ที่ var(--sidebar-w) พอชื่อแอปยาวกว่านั้น (เช่น SUPAVUT ASSESSMENT)
         ตัวอักษรจะล้นไปทับ breadcrumb (Manager แจ้ง 2026-08-26)
         max-content = ยืดพอดีชื่อ ส่วนคอลัมน์ตรงกลางเป็น minmax(0,1fr) จึงหดรับได้เอง */
      grid-template-columns: minmax(var(--sidebar-w), max-content) minmax(0, 1fr) auto;
      align-items: center;
      height: var(--topbar-h);
      border-bottom: 1px solid var(--line-light);
      background: var(--bar-bg);
      backdrop-filter: blur(14px);
    }

    /* ส่วนที่ 1: แบรนด์ — จัดให้ตรงคอลัมน์ sidebar */
    .brand-cluster {
      min-width: 0;
      height: 100%;
      display: flex;
      align-items: center;
      gap: .15rem;
    }

    /* ── ปุ่มขีด 3 ขีด (เก็บ/กางเมนูซ้าย) ────────────────────────────── */
    .nav-toggle {
      width: 2.4rem; height: 2.4rem; flex: 0 0 auto;
      display: grid; place-items: center;
      margin-left: calc(var(--page-pad) - .55rem);
      border: 0; border-radius: 6px;
      background: transparent; color: var(--muted-light);
      cursor: var(--cursor-action);
    }
    .nav-toggle:hover { background: var(--hover-soft); color: var(--light-text); }
    .nav-toggle-bars { width: 1.05rem; display: grid; gap: .22rem; }
    .nav-toggle-bars i { display: block; height: 2px; border-radius: 2px; background: currentColor; }

    .app-brand {
      padding-left: clamp(.9rem, 1.4vw, 1.25rem);
      /* กันกรณีสุดโต่ง: ชื่อยาวมากจนจะเบียดพื้นที่ตรงกลาง ให้ตัดด้วย … แทนการดันจนล้นจอ */
      max-width: 38vw;
      overflow: hidden;
      text-overflow: ellipsis;
      font-family: "Montserrat", var(--font-body);
      font-size: .82rem;
      font-weight: 800;
      letter-spacing: .05em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .app-brand .brand-accent { color: var(--moss); }

    /* ส่วนที่ 2: หัวข้อหน้าปัจจุบัน — ชิดซ้ายตรงกับเนื้อหา (2 บรรทัด kicker+title) */
    .topbar-title {
      min-width: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: .08rem;
      padding-left: clamp(1rem, 2vw, 1.8rem);
      overflow: hidden;
    }

    .topbar-title .tt-kicker {
      color: var(--moss);
      font-family: "Montserrat", var(--font-body);
      font-size: .62rem;
      font-weight: 800;
      letter-spacing: .15em;
      text-transform: uppercase;
      line-height: 1.1;
    }

    .topbar-title .tt-title {
      color: var(--light-text);
      font-size: .98rem;
      font-weight: 600;
      letter-spacing: .01em;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Breadcrumb tracker (บอกว่าอยู่จุดไหน) ── */
    .crumbs { display: flex; align-items: center; gap: .45rem; min-width: 0; overflow: hidden; }
    .crumbs .crumb { color: var(--muted-light); font-size: .9rem; white-space: nowrap; transition: color .2s var(--ease-out); }
    .crumbs a.crumb:hover { color: var(--light-text); }
    .crumbs .crumb-root { font-family: "Montserrat", var(--font-body); font-weight: 800; font-size: .82rem; letter-spacing: .03em; }
    .crumbs .crumb-root.crumb-logo-link { display: inline-flex; align-items: center; padding: .16rem .45rem; background: #fff; border: 1px solid rgb(0 0 0 / 10%); border-radius: 0.25rem; box-shadow: 0 1px 2px rgb(0 0 0 / 6%); transition: border-color .2s var(--ease-out), box-shadow .2s var(--ease-out); }
    .crumbs .crumb-root.crumb-logo-link:hover { border-color: rgb(45 148 97 / 45%); box-shadow: 0 2px 6px rgb(0 0 0 / 10%); }
    .crumbs .crumb-logo { height: 1.15rem; width: auto; display: block; }
    .crumbs .crumb-sep { flex: 0 0 auto; color: var(--muted-light); opacity: .4; }
    .crumbs .crumb-active { min-width: 0; color: var(--light-text); font-weight: 600; overflow: hidden; }
    .crumbs .crumb-active .tt-kicker { display: none; }
    .crumbs .crumb-active .tt-title { font-size: .92rem; font-weight: 600; }
    @media (max-width: 860px) {
      .crumbs .crumb:not(.crumb-active), .crumbs .crumb-sep { display: none; }
    }

    /* ── Modal แจ้งเตือนสิทธิ์: ไม่พาผู้ใช้ไปหน้า 403 แข็ง ๆ ── */
    .portal-access-modal {
      position: fixed;
      inset: 0;
      z-index: 75;
      display: grid;
      place-items: center;
      padding: var(--page-pad);
      background: rgb(0 0 0 / 58%);
      -webkit-backdrop-filter: blur(8px);
      backdrop-filter: blur(8px);
      opacity: 0;
      animation: portal-modal-in .2s var(--ease-out) forwards;
    }

    html[data-theme="light"] .portal-access-modal {
      background: rgb(255 255 255 / 62%);
    }

    .portal-access-modal.is-hiding {
      opacity: 0;
      pointer-events: none;
      transition: opacity .22s var(--ease-out);
    }

    .portal-access-dialog {
      width: min(100%, 25rem);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.05rem;
      padding: 1.45rem 1.5rem 1.3rem;
      border: 1px solid var(--line-light);
      border-radius: 0.25rem;
      background: var(--panel);
      color: var(--light-text);
      text-align: center;
      transform: translateY(.35rem) scale(.98);
      animation: portal-modal-card-in .24s var(--ease-out) forwards;
    }

    .portal-access-modal.is-error .portal-access-dialog { border-color: rgb(217 138 128 / 46%); }
    .portal-access-modal.is-success .portal-access-dialog { border-color: rgb(155 208 166 / 42%); }

    .portal-access-message {
      width: min(100%, 21.5rem);
      margin: 0 auto;
      color: var(--light-text);
      font-size: 1rem;
      font-weight: 500;
      line-height: 1.58;
      text-align: center;
      text-wrap: balance;
    }

    .portal-access-confirm {
      min-width: 8.5rem;
      min-height: 2.55rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: .55rem 1.15rem;
      border: 0;
      border-radius: 0.25rem;
      background: var(--light-text);
      color: var(--near-black);
      cursor: pointer;
      font-size: .92rem;
      font-weight: 700;
      transition: transform .2s var(--ease-out), background-color .2s var(--ease-out), color .2s var(--ease-out);
    }

    .portal-access-confirm:hover,
    .portal-access-confirm:focus-visible {
      outline: none;
      transform: translateY(-1px);
      background: var(--moss);
      color: #ffffff;
    }

    .portal-access-confirm.is-interacting {
      animation: portal-confirm-hover-pulse .72s var(--ease-out);
    }

    html[data-theme="light"] .portal-access-confirm {
      background: #000000;
      color: #ffffff;
    }

    html[data-theme="light"] .portal-access-confirm:hover,
    html[data-theme="light"] .portal-access-confirm:focus-visible {
      background: var(--moss);
      color: #ffffff;
    }

    @keyframes portal-confirm-hover-pulse {
      0%, 100% { filter: brightness(1); transform: translateY(-1px) scale(1); }
      42% { filter: brightness(1.16); transform: translateY(-1px) scale(1.035); }
      72% { filter: brightness(.94); transform: translateY(-1px) scale(.995); }
    }

    @keyframes portal-modal-in {
      to { opacity: 1; }
    }

    @keyframes portal-modal-card-in {
      to { transform: translateY(0) scale(1); }
    }

    /* ส่วนที่ 3 แบ่งเป็น 2 กลุ่มคั่นด้วยเส้น (Manager สั่ง 2026-08-19):
         กลุ่มซ้าย = รูปโปรไฟล์ + ชื่อ · กลุ่มขวา = กระดิ่ง ดาวน์โหลด ธง ออกจากระบบ
       ใช้ระยะห่างเป็นตัวแบ่ง: ในกลุ่มชิด .4rem ส่วนเส้นคั่นเว้น .85rem ทั้งสองข้าง */
    .topbar-account {
      justify-self: end;
      display: flex;
      align-items: center;
      gap: .4rem;
      padding-right: var(--page-pad);
    }

    /* ── กระดิ่งแจ้งเตือน (ซ้ายรูปโปรไฟล์) ─────────────────── */
    .noti-menu { position: relative; flex: 0 0 auto; }
    /* กล่องเอกสาร admin ใช้โทน moss แยกจากกระดิ่งทั่วไป จะได้เห็นแต่ไกลว่าคนละเรื่อง */
    .noti-trigger.is-download { border-color: color-mix(in srgb, var(--moss) 55%, var(--line-light)); color: var(--moss); }
    .noti-trigger.is-download:hover { background: color-mix(in srgb, var(--moss) 12%, transparent); }
    .noti-trigger {
      position: relative;
      width: 2.25rem; height: 2.25rem;
      display: grid; place-items: center;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      background: transparent;
      color: var(--muted-light);
      cursor: pointer;
      transition: border-color .16s ease, color .16s ease;
    }
    .noti-trigger:hover,
    .noti-trigger[aria-expanded="true"] { border-color: var(--moss); color: var(--moss); }
    .noti-trigger svg { width: 1.15rem; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
    .noti-badge {
      position: absolute;
      top: -.22rem; right: -.22rem;
      min-width: 1.05rem; height: 1.05rem;
      display: grid; place-items: center;
      padding: 0 .22rem;
      border-radius: 999px;
      background: var(--danger);
      color: #fff;
      font-size: .6rem; font-weight: 800; line-height: 1;
      font-variant-numeric: tabular-nums;
    }
    .noti-badge[hidden] { display: none; }

    .noti-panel {
      position: absolute;
      z-index: 60;
      top: calc(100% + .5rem);
      right: 0;
      width: min(23rem, calc(100vw - 2rem));
      display: none;
      border: 1px solid var(--line-strong);
      border-radius: 5px;
      background: var(--menu-bg);
      /* ต้องกำหนดสีตัวอักษรเอง ไม่งั้นสืบทอดสีขาวมาจาก .app-topbar ของหน้า /systems
         แล้วกลายเป็นขาวบนขาวในธีมสว่าง */
      color: var(--light-text);
      box-shadow: 0 1rem 2.5rem rgb(0 0 0 / 22%);
      overflow: hidden;
    }
    .noti-panel.is-open { display: block; }
    .noti-head {
      display: flex; align-items: center; justify-content: space-between; gap: .75rem;
      padding: .7rem .85rem;
      border-bottom: 1px solid var(--line-light);
      font-size: .82rem;
    }
    .noti-readall {
      border: 0; background: transparent;
      color: var(--moss); font-size: .7rem; font-weight: 700; cursor: pointer;
    }
    /* กระดิ่ง 2 ชั้น: เลือกแอป → แท็บหมวดของแอปนั้น (Manager 2026-08-27) */
    .noti-screen[hidden] { display:none !important; }
    .noti-app-row {
      width:100%; display:grid; grid-template-columns:2rem minmax(0,1fr) auto; align-items:center; gap:.65rem;
      padding:.7rem .85rem; border:0; border-bottom:1px solid var(--line-light);
      background:transparent; color:inherit; text-align:left; cursor:pointer;
    }
    .noti-app-row:last-child { border-bottom:0; }
    .noti-app-row:hover, .noti-app-row:focus-visible { background:var(--hover-soft); outline:none; }
    .noti-app-row img { width:2rem; height:2rem; object-fit:contain; }
    .noti-app-name { min-width:0; overflow:hidden; font-size:.8rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
    .noti-app-count {
      min-width:1.35rem; display:inline-grid; place-items:center; padding:.05rem .35rem;
      border-radius:999px; background:var(--danger); color:#fff; font-size:.66rem; font-weight:800;
    }
    .noti-app-count[hidden] { display:none !important; }
    .noti-back {
      display:inline-flex; align-items:center; gap:.3rem; border:0; padding:0;
      background:transparent; color:var(--muted-light); font-size:.74rem; font-weight:700; cursor:pointer;
    }
    .noti-back:hover, .noti-back:focus-visible { color:var(--light-text); outline:none; }
    .noti-tabs { display:grid; grid-template-columns:repeat(3,1fr); gap:.25rem; padding:.45rem .6rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); }
    .noti-tabs[hidden] { display:none !important; }
    .noti-tab { min-height:1.85rem; padding:.3rem .45rem; border:1px solid transparent; border-radius: 4px; background:transparent; color:var(--muted-light); font-size:.68rem; font-weight:700; cursor:pointer; }
    .noti-tab.is-active { border-color:var(--line-strong); background:var(--panel); color:var(--moss); }
    .noti-list { max-height: 24rem; overflow-y: auto; }
    .noti-empty { padding: 1.6rem .9rem; color: var(--muted-light); font-size: .78rem; text-align: center; }
    .noti-item {
      width: 100%;
      display: grid;
      grid-template-columns: 2rem minmax(0, 1fr);
      align-items: start;
      gap: .65rem;
      padding: .7rem .85rem;
      border: 0;
      border-bottom: 1px solid var(--line-light);
      background: transparent;
      color: inherit;
      text-align: left;
      cursor: pointer;
    }
    .noti-item:last-child { border-bottom: 0; }
    .noti-item:hover { background: var(--hover-soft); }
    .noti-item.is-unread { background: color-mix(in srgb, var(--moss) 7%, transparent); }
    .noti-item.is-unread:hover { background: color-mix(in srgb, var(--moss) 12%, transparent); }
    /* ไอคอนแอปกำกับ ให้รู้ว่าแจ้งเตือนมาจากระบบไหน */
    .noti-app-icon {
      width: 2rem; height: 2rem;
      display: grid; place-items: center;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel-soft);
      overflow: hidden;
    }
    .noti-app-icon img { width: 100%; height: 100%; object-fit: contain; }
    .noti-app-icon svg { width:1.15rem; fill:none; stroke:var(--moss); stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
    .noti-copy { min-width: 0; }
    .noti-copy strong { display: block; font-size: .78rem; font-weight: 650; line-height: 1.4; }
    .noti-copy small { display: block; margin-top: .12rem; color: var(--muted-light); font-size: .69rem; line-height: 1.45; }
    .noti-flag {
      display: inline-block; margin-left: .35rem; padding: .04rem .34rem; border-radius: 999px;
      background: color-mix(in srgb, #d9534f 20%, transparent); color: #b83d34;
      font-size: .6rem; font-style: normal; font-weight: 700; vertical-align: middle;
    }

    .noti-copy time { display: block; margin-top: .2rem; color: var(--muted-light); font-size: .64rem; }

    .topbar-avatar {
      width: 2.25rem; height: 2.25rem; flex: 0 0 auto;
      display: grid; place-items: center;
      border-radius: 50%;
      overflow: hidden;
      border: 1px solid var(--line-strong);
      background: var(--panel-soft);
      color: var(--light-text);
      font-size: .92rem; font-weight: 700;
    }

    button.topbar-avatar {
      padding: 0;
      cursor: zoom-in;
      appearance: none;
      transition: border-color .2s var(--ease-out), transform .2s var(--ease-out);
    }

    button.topbar-avatar:hover,
    button.topbar-avatar:focus-visible {
      border-color: var(--moss);
      transform: translateY(-1px);
    }

    .topbar-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .avatar-placeholder { color: var(--muted-light); }

    .avatar-placeholder svg {
      width: 62%;
      height: 62%;
      fill: currentColor;
      opacity: .72;
    }

    /* ปุ่มสลับธีมดำ/สว่าง (ซ้ายของลูกโลก) */
    .icon-toggle {
      width: 2.35rem; height: 2.35rem;
      display: grid; place-items: center;
      padding: 0; border: 0; border-radius: 50%;
      background: transparent; color: var(--light-text); cursor: pointer;
      transition: background-color .25s var(--ease-out);
    }

    .icon-toggle:hover { background: var(--hover-soft-2); }
    .icon-toggle svg { width: 1.18rem; height: 1.18rem; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }

    .topbar-logout-form {
      display: inline-flex;
      margin: 0;
    }

    /* ออกจากระบบเป็นปุ่มแดงชัด แยกออกจากไอคอนอื่นที่เป็นโทนกลาง */
    .topbar-logout {
      border-color: color-mix(in srgb, var(--danger) 45%, var(--line-light));
      color: var(--danger);
    }

    .topbar-logout:hover,
    .topbar-logout:focus-visible {
      background: rgb(217 138 128 / 14%);
      color: #ff6f61;
    }


    .topbar-account .welcome { text-align: right; }

    .topbar-divider {
      width: 1px;
      height: 1.9rem;
      margin: 0 .45rem;
      background: var(--line-strong);
    }

    .welcome {
      display: flex;
      flex-direction: column;
      line-height: 1.35;
    }

    .welcome small {
      color: var(--moss);
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    .welcome strong {
      color: var(--light-text);
      font-size: .98rem;
      font-weight: 500;
    }

    .welcome span.code {
      color: var(--muted-light);
      font-size: .76rem;
      letter-spacing: .05em;
    }

    /* ── Language picker (globe มุมขวาบน) ── */
    .language-picker { position: relative; display: inline-flex; }

    /* hover bridge: ปิดช่องว่างระหว่างปุ่มกับเมนู เพื่อให้ hover ค้างได้ต่อเนื่อง */
    .language-picker::after {
      content: "";
      position: absolute;
      top: 100%;
      right: 0;
      width: 9.5rem;
      height: .8rem;
    }

    .language-button {
      width: 2.35rem; height: 2.35rem;
      display: grid; place-items: center;
      padding: 0; border: 0; border-radius: 50%;
      background: transparent; color: var(--light-text); cursor: pointer;
      transition: background-color .25s var(--ease-out);
    }

    .language-button:hover,
    .language-picker:hover .language-button,
    .language-picker.is-open .language-button { background: var(--hover-soft-2); }

    .language-globe { width: 1.25rem; height: 1.25rem; }

    .language-flag {
      width: 1.42rem;
      height: 1rem;
      display: block;
      flex: none;
      object-fit: cover;
      border-radius: .16rem;
      box-shadow: 0 0 0 1px rgb(0 0 0 / 18%);
    }

    .language-button .language-flag {
      width: 1.62rem;
      height: 1.12rem;
    }

    .language-menu .language-flag {
      margin-left: auto;
    }

    .language-menu {
      position: absolute;
      top: calc(100% + .6rem);
      right: 0;
      width: max-content;
      min-width: 9.5rem;
      padding: .5rem;
      border: 1px solid var(--line-light);
      background: var(--menu-bg);
      backdrop-filter: blur(14px);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-.4rem);
      transition: opacity .24s var(--ease-out), transform .24s var(--ease-out);
    }

    .language-picker:hover .language-menu,
    .language-picker.is-open .language-menu,
    .language-picker:focus-within .language-menu {
      opacity: 1; pointer-events: auto; transform: none;
    }

    .language-menu button {
      width: 100%;
      min-height: 2.3rem;
      display: flex;
      align-items: center;
      padding: 0 .7rem;
      border: 0;
      background: transparent;
      color: var(--muted-light);
      cursor: pointer;
      font-size: .76rem;
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      transition: color .2s var(--ease-out), background-color .2s var(--ease-out);
    }

    .language-menu button:hover,
    .language-menu button[aria-current="true"] {
      background: var(--hover-soft);
      color: var(--light-text);
    }

    /* ── Shell (อยู่ใต้ top header bar) ── */
    .shell {
      display: grid;
      grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
      min-height: 100vh;
      min-height: 100dvh;
      padding-top: var(--topbar-h);
    }

    .sidebar {
      grid-row: 1 / -1;
      position: sticky;
      top: var(--topbar-h);
      align-self: start;
      height: calc(100vh - var(--topbar-h));
      height: calc(100dvh - var(--topbar-h));
      display: flex;
      flex-direction: column;
      padding: clamp(1rem, 1.8vw, 1.55rem) clamp(.85rem, 1.25vw, 1.15rem);
      border-right: 1px solid rgb(255 255 255 / 12%);
      /* เมนูซ้ายเป็นน้ำเงินเข้ม ตัวอักษรขาว ทั้งระบบ (Manager สั่ง 2026-08-19)
         ตั้ง token ทับเฉพาะใน .sidebar เพื่อให้ลูกทุกตัวที่อ้าง var() เปลี่ยนตาม
         โดยไม่ต้องไล่แก้ทีละ rule และไม่กระทบส่วนอื่นของหน้า */
      --light-text: #ffffff;
      --muted-light: rgb(255 255 255 / 72%);
      --line-light: rgb(255 255 255 / 14%);
      --line-strong: rgb(255 255 255 / 26%);
      --hover-soft: rgb(255 255 255 / 10%);
      --hover-soft-2: rgb(255 255 255 / 14%);
      --moss: #9dc0ff;
      background: #16255c;
      color: #ffffff;
    }

    .side-brand {
      display: block;
      padding-bottom: clamp(1rem, 1.8vw, 1.45rem);
      margin-bottom: clamp(.8rem, 1.5vw, 1.15rem);
      border-bottom: 1px solid var(--line-light);
      font-family: "Montserrat", var(--font-body);
      font-size: .9rem;
      font-weight: 800;
      letter-spacing: .05em;
      text-transform: uppercase;
    }

    .side-nav {
      display: flex;
      flex-direction: column;
      gap: .35rem;
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      overscroll-behavior: contain;
      scrollbar-width: thin;
      scrollbar-color: rgb(255 255 255 / 28%) transparent;
    }
    .side-nav::-webkit-scrollbar { width: 6px; }
    .side-nav::-webkit-scrollbar-thumb { border-radius: 999px; background: rgb(255 255 255 / 26%); }
    .side-nav::-webkit-scrollbar-thumb:hover { background: rgb(255 255 255 / 42%); }
    .side-nav::-webkit-scrollbar-track { background: transparent; }

    .nav-item {
      display: flex;
      align-items: center;
      gap: .85rem;
      width: 100%;
      padding: .62rem .72rem;
      border: 0;
      border-radius: 0.25rem;
      background: transparent;
      color: var(--muted-light);
      cursor: pointer;
      font-size: .86rem;
      text-align: left;
      transition: background-color .25s var(--ease-out), color .25s var(--ease-out);
    }

    .nav-item:hover { background: var(--hover-soft); color: var(--light-text); }
    .nav-item.is-active { background: rgb(255 255 255 / 16%); color: #ffffff; }

    .nav-item svg {
      flex: 0 0 auto;
      width: 1.1rem; height: 1.1rem;
      fill: none; stroke: currentColor; stroke-width: 1.7;
      stroke-linecap: round; stroke-linejoin: round;
    }

    /* โลโก้ระบบย่อยใต้ปุ่มกลับ — บอกว่าตอนนี้อยู่ระบบไหน */
    .nav-sys-mark { display: grid; place-items: center; padding: .85rem 0 .35rem; }
    .nav-sys-mark img { width: 100%; max-width: 4.4rem; height: auto; object-fit: contain; opacity: .9; }

    /* ปุ่มกลับสู่ SUPAVUT INSIGHT */
    .nav-back { color: var(--moss); font-weight: 600; }
    .nav-back:hover { background: var(--hover-soft); color: var(--light-text); }

    /* กลุ่ม "รวมระบบ" (admin) — รวมฟังก์ชัน DCC/Manager/Recruit แบบพับได้ */
    .nav-group { display: flex; flex-direction: column; gap: .35rem; }
    .nav-group > summary { list-style: none; }
    .nav-group > summary::-webkit-details-marker { display: none; }
    .nav-group-summary .nav-caret { margin-left: auto; width: 1rem; height: 1rem; transition: transform .2s var(--ease-out); }
    .nav-group[open] .nav-group-summary .nav-caret { transform: rotate(180deg); }
    .nav-group-body { display: flex; flex-direction: column; gap: .35rem; margin: .15rem 0 .15rem .9rem; padding-left: .55rem; border-left: 1px solid var(--line-light); }
    .nav-sub { padding: .62rem .75rem; font-size: .86rem; }

    /* หัวข้อกลุ่มเมนูของระบบย่อย */
    .side-section-label {
      margin: 1.1rem .85rem .35rem;
      padding-top: 1rem;
      border-top: 1px solid var(--line-light);
      color: var(--muted-light);
      font-family: "Montserrat", var(--font-body);
      font-size: .62rem;
      font-weight: 800;
      letter-spacing: .14em;
      text-transform: uppercase;
    }

    .side-spacer { flex: 1; }

    .side-foot {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--line-light);
      color: var(--muted-light);
      font-size: .68rem;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    /* ── Main ── */
    .main {
      grid-column: 2;
      padding: clamp(1.2rem, 2.4vw, 2.2rem) var(--page-pad) clamp(2rem, 4vw, 3.4rem);
    }

    /* ── เก็บเมนูซ้าย (จอใหญ่) — เนื้อหาขยายเต็มจอ ──────────────────────
       ใส่ที่ <body> เพื่อให้ทั้ง .shell และ .app-topbar เปลี่ยนพร้อมกัน
       ไม่งั้นแถบบนจะยังจองคอลัมน์กว้างเท่า sidebar ทั้งที่เมนูถูกซ่อนไปแล้ว */
    /* เลื่อนเก็บ/กางแบบมีจังหวะ ใช้ ease เดียวกับที่ระบบใช้อยู่ (cubic-bezier(.22,1,.36,1))
       ย่อคอลัมน์ของ grid พร้อมกับเลื่อนตัวเมนูออกไปทางซ้าย ทั้งสองอย่างจึงขยับพร้อมกัน
       ไม่ใช่เมนูหายวับแล้วเนื้อหากระโดดขยาย */
    .shell { transition: grid-template-columns .26s cubic-bezier(.22, 1, .36, 1); }
    .sidebar {
      overflow-x: hidden;
      transition: transform .26s cubic-bezier(.22, 1, .36, 1), opacity .2s ease, visibility .26s;
    }
    .app-topbar { transition: grid-template-columns .26s cubic-bezier(.22, 1, .36, 1); }

    body.nav-collapsed .shell { grid-template-columns: 0 minmax(0, 1fr); }
    body.nav-collapsed .sidebar { transform: translateX(-100%); opacity: 0; visibility: hidden; }
    body.nav-collapsed .app-topbar { grid-template-columns: auto minmax(0, 1fr) auto; }
    /* พับเมนูแล้วคอลัมน์แบรนด์หดเหลือเท่าตัวอักษร breadcrumb จึงมาชิดชื่อโปรแกรมทันที
       (Manager แจ้ง 2026-08-26) — เว้นระยะและใส่เส้นคั่นจาง ๆ ให้อ่านแยกกันได้ */
    body.nav-collapsed .app-brand { padding-right: clamp(.9rem, 1.6vw, 1.4rem); }
    body.nav-collapsed .topbar-title {
      padding-left: clamp(.9rem, 1.6vw, 1.4rem);
      border-left: 1px solid var(--line-light);
    }

    /* ฉากหลังของลิ้นชักบนมือถือ — จางเข้า/ออก ไม่ใช่โผล่ทันที (จอใหญ่ไม่ใช้) */
    .nav-backdrop {
      position: fixed; inset: 0; z-index: 55;
      background: rgb(8 12 10 / 45%);
      opacity: 0; visibility: hidden;
      transition: opacity .24s ease, visibility .24s;
    }
    body.nav-open .nav-backdrop { opacity: 1; visibility: visible; }
    @media (min-width: 861px) { .nav-backdrop { display: none; } }

    body.image-viewer-lock { overflow: hidden; }

    /* ── ลากตารางด้วยเมาส์ (data-drag-scroll) ─────────────────────────────
       เคอร์เซอร์มือจับคือสัญลักษณ์บอกว่ากล่องนี้ลากเลื่อนได้
       ปุ่ม/ลิงก์/ช่องกรอกข้างในต้องคงเคอร์เซอร์เดิม ไม่งั้นดูเหมือนกดไม่ได้ */
    [data-drag-scroll] { cursor: grab; }
    [data-drag-scroll].is-drag-scrolling { cursor: grabbing; user-select: none; }
    [data-drag-scroll] button,
    [data-drag-scroll] a,
    [data-drag-scroll] label,
    [data-drag-scroll] summary,
    [data-drag-scroll] [role="button"] { cursor: pointer; }
    [data-drag-scroll] input,
    [data-drag-scroll] select,
    [data-drag-scroll] textarea { cursor: auto; }
    [data-drag-scroll] input[type="checkbox"],
    [data-drag-scroll] input[type="radio"] { cursor: pointer; }
    /* ระหว่างลากห้ามให้ลูก ๆ แย่งเคอร์เซอร์ ไม่งั้นไอคอนกระพริบไปมา */
    [data-drag-scroll].is-drag-scrolling * { cursor: grabbing !important; }

    .image-viewer {
      position: fixed;
      inset: 0;
      z-index: 100;
      display: grid;
      place-items: center;
      padding: clamp(1.5rem, 5vw, 4rem);
      background: rgb(0 0 0 / 86%);
      backdrop-filter: blur(10px);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity .24s var(--ease-out), visibility .24s var(--ease-out);
    }

    .image-viewer.is-open {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }

    .image-viewer-backdrop {
      position: absolute;
      inset: 0;
      border: 0;
      background: transparent;
      cursor: zoom-out;
    }

    .image-viewer img {
      position: relative;
      z-index: 1;
      width: auto;
      height: auto;
      max-width: min(88vw, 46rem);
      max-height: 82vh;
      object-fit: contain;
      opacity: 0;
      transform: translateY(1rem) scale(.94);
      transition: opacity .24s var(--ease-out), transform .28s var(--ease-out);
      will-change: transform, opacity;
    }

    .image-viewer.is-open img {
      opacity: 1;
      transform: none;
    }

    .image-viewer-close {
      position: fixed;
      top: clamp(1rem, 3vw, 1.6rem);
      right: clamp(1rem, 3vw, 1.6rem);
      z-index: 2;
      width: 2.75rem;
      height: 2.75rem;
      display: grid;
      place-items: center;
      border: 1px solid rgb(255 255 255 / 22%);
      border-radius: 50%;
      background: rgb(255 255 255 / 10%);
      color: #ffffff;
      cursor: pointer;
      transition: background-color .2s var(--ease-out), border-color .2s var(--ease-out);
    }

    .image-viewer-close:hover,
    .image-viewer-close:focus-visible {
      border-color: rgb(255 255 255 / 52%);
      background: rgb(255 255 255 / 16%);
    }

    .image-viewer-close svg {
      width: 1.2rem;
      height: 1.2rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* แถบปุ่มซูมเข้า/ออก + รีเซ็ต (อ่านรูปละเอียด) */
    .image-viewer-zoom {
      position: fixed;
      bottom: clamp(1rem, 4vw, 2rem);
      left: 50%;
      z-index: 3;
      transform: translateX(-50%);
      display: inline-flex;
      align-items: center;
      gap: .18rem;
      padding: .28rem;
      border: 1px solid rgb(255 255 255 / 22%);
      border-radius: 0.3rem;
      background: rgb(18 18 20 / 66%);
      backdrop-filter: blur(6px);
      opacity: 0;
      visibility: hidden;
      transition: opacity .24s var(--ease-out), visibility .24s var(--ease-out);
    }
    .image-viewer.is-open .image-viewer-zoom { opacity: 1; visibility: visible; }
    .izoom-btn, .izoom-val {
      height: 2.35rem;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 0.25rem;
      background: transparent;
      color: #fff;
      cursor: pointer;
      transition: background-color .16s var(--ease-out);
    }
    .izoom-btn { width: 2.35rem; }
    .izoom-btn svg { width: 1.15rem; height: 1.15rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .izoom-val { min-width: 4rem; font-size: .82rem; font-weight: 750; letter-spacing: .01em; }
    .izoom-btn:hover, .izoom-val:hover, .izoom-btn:focus-visible, .izoom-val:focus-visible { background: rgb(255 255 255 / 18%); outline: none; }
    .izoom-btn:disabled { opacity: .38; cursor: not-allowed; background: transparent; }
    .image-viewer img { transform-origin: center center; user-select: none; -webkit-user-drag: none; -webkit-user-select: none; }
    .image-viewer.is-zoomable img { touch-action: none; cursor: grab; }
    .image-viewer.is-zoomable img:active { cursor: grabbing; }

    .flash {
      width: min(100%, 46rem);
      margin-bottom: 1.4rem;
      padding: .9rem 1.1rem;
      border: 1px solid var(--line-light);
      border-radius: 0.25rem;
      font-size: .92rem;
    }

    .flash.success { border-color: rgb(155 208 166 / 40%); color: var(--success); background: rgb(155 208 166 / 8%); }
    .flash.error { border-color: rgb(217 138 128 / 40%); color: var(--danger); background: rgb(217 138 128 / 8%); }
    .flash.warning { border-color: rgb(200 150 74 / 45%); color: #c8964a; background: rgb(200 150 74 / 10%); }
    .flash.warning a { margin-left: .35rem; color: inherit; font-weight: 800; text-decoration: underline; }

    /* 5S: success แสดงเป็น modal แบบ Assessment แทนแถบเขียว (เฉพาะ route area5s.*) */
    .pt-ok-modal { position:fixed; inset:0; z-index:2600; display:grid; place-items:center; padding:1rem; background:rgb(0 0 0 / 48%); }
    .pt-ok-backdrop { position:absolute; inset:0; border:0; background:transparent; cursor:pointer; }
    .pt-ok-dialog { position:relative; z-index:1; width:min(100%, 26rem); text-align:center; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1.5rem 1.25rem 1.25rem; box-shadow:0 22px 70px rgb(0 0 0 / 38%); }
    /* แจ้งเตือนสำเร็จ: วงกลมเขียว + ขีดถูกวาดเส้นทีละนิด (Manager 2026-08-27 — เดิมเป็นน้ำเงินและเด้งทั้งวง) */
    .pt-ok-icon { width:3.6rem; height:3.6rem; margin:0 auto .8rem; border-radius:50%; background:color-mix(in srgb, var(--success) 16%, transparent); display:grid; place-items:center; animation:ptOkPop .35s ease-out .05s both; }
    .pt-ok-icon svg { width:2rem; height:2rem; stroke:var(--success); stroke-dasharray:26; stroke-dashoffset:26; animation:ptOkDraw .5s cubic-bezier(.65,0,.35,1) .2s forwards; }
    @keyframes ptOkPop { from { transform:scale(.4); opacity:0; } to { transform:scale(1); opacity:1; } }
    @keyframes ptOkDraw { to { stroke-dashoffset:0; } }
    @media (prefers-reduced-motion: reduce) {
      .pt-ok-icon { animation:none; }
      .pt-ok-icon svg { animation:none; stroke-dashoffset:0; }
    }
    .pt-ok-dialog h3 { margin:0 0 .35rem; font-size:1.1rem; color:var(--light-text); }
    .pt-ok-dialog p { margin:0 0 1.1rem; color:var(--muted-light); font-size:.86rem; line-height:1.6; }
    .pt-ok-btn { min-height:2.3rem; padding:.5rem 1.6rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; font-size:.82rem; font-weight:750; cursor:pointer; }
    .pt-ok-btn:hover { filter:brightness(1.06); }
    .pt-ok-btn:focus-visible { outline:2px solid color-mix(in srgb, var(--moss) 55%, transparent); outline-offset:2px; }

    .panel-kicker {
      display: block;
      margin-bottom: .9rem;
      color: var(--moss);
      font-family: "Montserrat", var(--font-body);
      font-size: .72rem;
      font-weight: 800;
      letter-spacing: .14em;
      text-transform: uppercase;
    }

    .panel-title {
      font-family: var(--font-display);
      font-size: clamp(1.9rem, 3.6vw, 3rem);
      font-weight: 400;
      line-height: 1.1;
      letter-spacing: -.01em;
    }

    @yield('page-style')

    /* ── Mobile ── */
    .mobile-brand { display: none; }

    @media (max-width: 860px) {
      /* ── มือถือ: เมนูซ้ายเป็น "ลิ้นชัก" เลื่อนออกมาทับ ไม่ใช่แถบกินที่เหนือเนื้อหา ──
         ของเดิมพับ sidebar เป็นแถวปุ่มแนวนอนวางไว้เหนือเนื้อหา ทำให้จอมือถือเหลือที่อ่านน้อยมาก
         (Manager แจ้ง 2026-08-25) · ตอนนี้ค่าเริ่มต้นคือ "ปิด" เนื้อหาจึงเต็มจอเสมอ
         กดขีด 3 ขีดถึงจะเลื่อนออกมา พร้อมฉากหลังให้แตะปิดได้ */
      .shell { grid-template-columns: minmax(0, 1fr); }
      .main { grid-column: 1; }

      .sidebar {
        position: fixed;
        z-index: 58;
        top: var(--topbar-h);
        left: 0;
        width: min(17rem, 82vw);
        height: calc(100dvh - var(--topbar-h));
        overflow-y: auto;
        border-right: 1px solid rgb(255 255 255 / 12%);
        border-bottom: 0;
        /* ปิดอยู่ = เลื่อนออกไปทางซ้ายจนสุด · เปิด = เลื่อนกลับเข้ามาพร้อมเงาให้ดูลอยเหนือเนื้อหา */
        transform: translateX(-100%);
        opacity: 1;
        visibility: visible;
        box-shadow: none;
        transition: transform .26s cubic-bezier(.22, 1, .36, 1), box-shadow .26s ease;
      }
      body.nav-open .sidebar { transform: translateX(0); box-shadow: 0 0 2.2rem rgb(0 0 0 / 28%); }
      /* บนมือถือปุ่มขีด 3 ขีดคุมลิ้นชัก ไม่ใช่คลาส nav-collapsed ของจอใหญ่
         จึงต้องล้างผลของ nav-collapsed ทิ้ง ไม่งั้นลิ้นชักจะโปร่งใส/มองไม่เห็นตอนเปิด */
      body.nav-collapsed .sidebar { opacity: 1; visibility: visible; }
      body.nav-collapsed .shell { grid-template-columns: minmax(0, 1fr); }

      /* แถบบนไม่จองคอลัมน์ sidebar กว้าง ๆ */
      .app-topbar { grid-template-columns: auto minmax(0, 1fr) auto; }
      .app-brand { padding-left: .1rem; padding-right: .7rem; font-size: .8rem; }
      /* มือถือก็ชิดกันเหมือนกัน เพราะคอลัมน์แบรนด์หดเท่าตัวอักษร — เว้นระยะ + เส้นคั่นให้แยกออก */
      .topbar-title { padding-left: .75rem; border-left: 1px solid var(--line-light); }
      .topbar-title .tt-title { font-size: .92rem; }
      .welcome strong { font-size: .92rem; }
    }

    /* ── กันแถบบนตีกันบนมือถือทุกรุ่น (Manager แจ้ง 2026-08-25) ──────────
       ปัญหาเดิม: ทุกชิ้นในแถบบนเป็น flex ที่ไม่ยอมหด พอจอแคบลงเลยเบียดทับกัน
       และเครื่องต่างรุ่นความกว้างต่างกัน อาการจึงไม่เหมือนกัน
       กติกา: ชิ้นที่ "ต้องเห็นเสมอ" ตรึงขนาด · ชิ้นที่ยืดได้ต้อง min-width:0 + ตัดด้วย … */
    @media (max-width: 860px) {
      .app-topbar { gap: .25rem; }
      .brand-cluster, .topbar-account { min-width: 0; }
      .topbar-account { flex-wrap: nowrap; gap: .25rem; padding-right: calc(var(--page-pad) * .6); }
      .topbar-account > * { flex: 0 0 auto; }
      .welcome { min-width: 0; }
      .welcome strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .topbar-title { min-width: 0; }
      .topbar-title .tt-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    }

    @media (max-width: 640px) {
      /* มือถือ: เหลือเฉพาะแบรนด์ + ปุ่มธีม/ภาษา/ออกจากระบบ + รูปโปรไฟล์
         ซ่อนหัวข้อกลาง และ **ซ่อนชื่อ-สกุลผู้ใช้** (Manager สั่ง 2026-08-26)
         เพราะชื่อไทยยาว ทำให้แถบบนเบียดกันจนกดปุ่มผิด · ตัวตนผู้ใช้ยังดูได้จากรูปโปรไฟล์ */
      .topbar-title { display: none; }
      .topbar-divider { display: none; }
      .welcome { display: none; }
    }

    @media (max-width: 520px) {
      .nav-item span { display: inline; }   /* ในลิ้นชักมีที่พอ แสดงชื่อเมนูได้เต็ม */
    }

    /* จอแคบมาก (เช่น iPhone SE / เครื่องที่ตั้ง font ใหญ่) — ย่อชื่อโปรแกรมลงอีกขั้น
       (ชื่อ-สกุลผู้ใช้ถูกซ่อนตั้งแต่ 640px แล้ว) */
    @media (max-width: 400px) {
      .app-brand { font-size: .72rem; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
      }

      .portal-access-modal {
        opacity: 1;
        transform: none;
      }

      .portal-access-modal.is-hiding {
        transform: none;
      }

      .portal-access-dialog {
        transform: none;
      }
    }
  </style>
</head>
<body class="@yield('body-class')">
  <div class="portal-loader" id="portalLoader" aria-hidden="true">
    <div class="portal-loader-box">
      <span class="portal-spinner" aria-hidden="true"></span>
      <p id="portalLoaderText" data-i18n="loader.switching">กำลังเปลี่ยนระบบ</p>
    </div>
  </div>

  @php
    $meAvatarUrl = (isset($me) && $me->profile_picture) ? asset('storage/'.$me->profile_picture) : null;
    $meDisplayName = isset($me) ? ($me->fullNameTh() ?: $me->full_name_en ?: $me->employee_code) : '';
    // $sysContext คำนวณไว้แล้วด้านบนสุดของไฟล์ (ใช้ร่วมกับไอคอนแท็บเบราว์เซอร์)
    // ถ้าอยู่ในระบบย่อยใด sidebar จะกลายเป็นเมนูของระบบนั้น
    $portalBrandWords = match ($sysContext) {
      'recruit' => ['RECRUIT', 'SYSTEM'],
      'assessment' => ['SUPAVUT', 'ASSESSMENT'],
      'area5s' => ['SUPAVUT', '5S AREA'],
      'ot-approval' => ['TIME & LEAVE', 'APPROVAL'],
      default => ['SUPAVUT', 'INSIGHT'],
    };
    $portalBrandMain = $portalBrandWords[0];
    $portalBrandAccent = implode(' ', array_slice($portalBrandWords, 1));
    $portalBrandLabel = trim($portalBrandMain.' '.$portalBrandAccent);

    // ── แท็บ Recruit แยกตาม role (1 คน 1 role; admin เห็นทุกแท็บ) ──
    $recruitRole = null;
    $recruitTabs = [];
    if ($sysContext === 'recruit' && isset($me)) {
      $recruitRole = $me->isAdmin() ? 'admin' : (\App\Models\Recruit\RecruitMember::primaryRoleFor($me->id) ?: 'none');
      $recruitTabs = match ($recruitRole) {
        'admin' => ['overview', 'departments', 'request', 'review', 'inbox', 'downloads', 'settings'],
        'dcc' => ['departments', 'overview', 'request'],
        'manager', 'general_manager', 'hr_manager' => ['overview', 'review'],
        'recruit' => ['overview', 'inbox', 'downloads'],
        default => ['overview'],
      };
    }
    // meta: key => [routeName, activePattern, i18nKey, fallbackLabel, svgInner]
    $rcTabMeta = [
      'overview' => ['recruit.index', 'recruit.index', 'nav.overview', 'ภาพรวม', '<path d="M3 12h7V3H3zM14 21h7V3h-7zM3 21h7v-6H3z"></path>'],
      'departments' => ['recruit.departments', 'recruit.departments', 'nav.rcDepts', 'แผนกภายใต้สังกัด', '<path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path>'],
      'request' => ['recruit.request', 'recruit.request', 'nav.rcRequest', 'ขออัตรากำลังคน', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M12 18v-6"></path><path d="M9 15h6"></path>'],
      'review' => ['recruit.review', 'recruit.review', 'nav.rcReview', 'ตรวจเอกสาร', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="m9 15 2 2 4-4"></path>'],
      'inbox' => ['recruit.inbox', 'recruit.inbox', 'nav.rcInbox', 'กล่องเอกสาร', '<path d="M22 12h-6l-2 3h-4l-2-3H2"></path><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>'],
      'downloads' => ['recruit.downloads', 'recruit.downloads', 'nav.rcDownloads', 'ดาวน์โหลด', '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path>'],
      'settings' => ['recruit.settings.index', 'recruit.settings.*', 'nav.sysSettings', 'ตั้งค่าระบบ', '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"></path>'],
    ];
  @endphp

  <header class="app-topbar">
    {{-- ส่วนที่ 1: แบรนด์ (INSIGHT สีเขียว moss เหมือน "ยินดีต้อนรับ") --}}
    <div class="brand-cluster">
      {{-- ปุ่มขีด 3 ขีด — เก็บ/กางเมนูซ้าย (Manager สั่ง 2026-08-25)
           บนมือถือเมนูซ้ายเป็นลิ้นชักเลื่อนออกมาทับ ไม่ใช่แถบกินที่อยู่เหนือเนื้อหาเหมือนเดิม
           บนจอใหญ่กดแล้วเนื้อหาขยายเต็มจอ และจำค่าไว้ใน localStorage --}}
      <button class="nav-toggle" type="button" data-nav-toggle aria-controls="portalSidebar" aria-expanded="true"
              data-i18n-aria="nav.toggleMenu" aria-label="เปิด/ปิดเมนู" title="เปิด/ปิดเมนู">
        <span class="nav-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
      <a class="app-brand" href="{{ route('dashboard') }}" aria-label="{{ $portalBrandLabel }}">{{ $portalBrandMain }} <span class="brand-accent">{{ $portalBrandAccent }}</span></a>
    </div>

    {{-- ส่วนที่ 2: breadcrumb tracker — บอกตำแหน่งปัจจุบัน --}}
    <div class="topbar-title">
      @hasSection('topbar-center')
        @yield('topbar-center')
      @else
        <nav class="crumbs" aria-label="breadcrumb">
          @if ($sysContext === 'assessment')
            <a class="crumb crumb-root nav-go" href="{{ route('assessment.index') }}"
               data-i18n="systems.assessmentName">SUPAVUT ASSESSMENT</a>
          @elseif ($sysContext === 'area5s')
            <a class="crumb crumb-root crumb-logo-link nav-go" href="{{ route('area5s.home') }}" aria-label="SUPAVUT 5S AREA">
              <img class="crumb-logo" src="{{ asset('assets/area5s/logo-5s.png').'?v=2' }}" alt="SUPAVUT 5S AREA">
            </a>
          @elseif ($sysContext)
            {{-- อยู่ในระบบย่อย: ขึ้นต้นที่ชื่อระบบเลย ไม่ต้องมี SUPAVUT INSIGHT / ระบบ HR ทั้งหมด --}}
            @php
              $crumbMeta = [
                'recruit' => ['recruit.index', 'systems.recruitName', 'RECRUIT SYSTEM'],
                'ot-approval' => ['ot-approval.home', 'systems.otApprovalName', 'TIME & LEAVE APPROVAL'],
              ][$sysContext];
            @endphp
            <a class="crumb crumb-root nav-go" href="{{ route($crumbMeta[0]) }}" data-i18n="{{ $crumbMeta[1] }}">{{ $crumbMeta[2] }}</a>
          @else
            <a class="crumb crumb-root nav-go" href="{{ route('dashboard') }}">SUPAVUT INSIGHT</a>
          @endif
          <span class="crumb-sep" aria-hidden="true">/</span>
          <span class="crumb crumb-active">@yield('topbar-title')</span>
        </nav>
      @endif
    </div>

    {{-- ส่วนที่ 3: รูปโปรไฟล์ + ทักทาย+ชื่อ + ปุ่มธีม + ลูกโลก --}}
    <div class="topbar-account">
      @isset($me)
@if ($meAvatarUrl)
          <button class="topbar-avatar" type="button" data-image-preview data-image-src="{{ $meAvatarUrl }}" data-image-alt="{{ $meDisplayName }}" data-i18n-aria="profile.view_photo" aria-label="ดูรูปโปรไฟล์ขนาดใหญ่">
            <img src="{{ $meAvatarUrl }}" alt="">
          </button>
        @else
          <span class="topbar-avatar avatar-placeholder" aria-hidden="true">
            <svg viewBox="0 0 48 48" focusable="false">
              <circle cx="24" cy="18" r="9"></circle>
              <path d="M8 42c2.6-9.4 9-14.5 16-14.5S37.4 32.6 40 42H8Z"></path>
            </svg>
          </span>
        @endif

        {{-- เหลือแค่ชื่อ — Manager สั่งเอา "ยินดีต้อนรับ" กับรหัสพนักงานออก --}}
        <div class="welcome">
          <strong>
            <span data-val="th">{{ $me->fullNameTh() ?: $me->full_name_en ?: $me->employee_code }}</span>
            <span data-val="en">{{ $me->full_name_en ?: $me->fullNameTh() ?: $me->employee_code }}</span>
          </strong>
        </div>
        <span class="topbar-divider" aria-hidden="true"></span>
      @endisset

      {{-- กระดิ่งกับกล่องดาวน์โหลดอยู่ซ้ายของธงชาติ (Manager สั่ง 2026-08-19)
           เดิมอยู่ก่อนชื่อผู้ใช้ ทำให้ไอคอนกระจายคนละฝั่งกับตัวเลือกภาษา --}}
      @isset($me)
        {{-- กระดิ่งแจ้งเตือน OT — เห็นได้ทุกหน้าของ Insight --}}
        <div class="noti-menu">
          <button id="notiTrigger" class="noti-trigger" type="button"
                  aria-expanded="false" aria-controls="notiPanel"
                  data-i18n-aria="noti.aria" aria-label="การแจ้งเตือน">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7Z" />
              <path d="M13.7 20a1.96 1.96 0 0 1-3.4 0" />
            </svg>
            <span class="noti-badge" data-noti-badge @if (($portalUnreadCount ?? 0) < 1) hidden @endif>{{ ($portalUnreadCount ?? 0) > 99 ? '99+' : ($portalUnreadCount ?? 0) }}</span>
          </button>

          {{-- กระดิ่ง 2 ชั้น (Manager 2026-08-27): ชั้นแรกเลือกแอป · ชั้นสองเป็นแท็บหมวดของแอปนั้น --}}
          <div id="notiPanel" class="noti-panel" role="menu" aria-labelledby="notiTrigger">
            {{-- ชั้นที่ 1: เลือกแอป --}}
            <div class="noti-screen" data-noti-apps-screen>
              <div class="noti-head">
                <strong data-i18n="noti.title">การแจ้งเตือน</strong>
              </div>
              <div class="noti-list" data-noti-apps>
                <p class="noti-empty" data-i18n="noti.loading">กำลังโหลด...</p>
              </div>
            </div>

            {{-- ชั้นที่ 2: แจ้งเตือนของแอปที่เลือก --}}
            <div class="noti-screen" data-noti-app-screen hidden>
              <div class="noti-head">
                <button class="noti-back" type="button" data-noti-back>
                  <span aria-hidden="true">‹</span>
                  <span data-noti-app-name>-</span>
                </button>
                <button class="noti-readall" type="button" data-noti-readall data-i18n="noti.readAll">อ่านทั้งหมด</button>
              </div>
              <div class="noti-tabs" role="tablist" aria-label="หมวดการแจ้งเตือน" data-noti-tabs hidden></div>
              <div class="noti-list" data-noti-list>
                <p class="noti-empty" data-i18n="noti.loading">กำลังโหลด...</p>
              </div>
            </div>
          </div>
        </div>

        {{-- กล่องเอกสารของ admin — แยกจากกระดิ่งทั่วไปเพราะเป็นงานคนละบทบาท
             กระดิ่ง = งานที่ต้องอนุมัติ · กล่องนี้ = ไฟล์ที่ต้องโหลดส่ง HR
             แสดงเฉพาะเมื่ออยู่ใน Time & Leave Approval (Manager 2026-08-27 — เดิมโผล่ทุกหน้า) --}}
        @if (request()->routeIs('ot-approval.*') && ($me->isAdmin() || \App\Models\OtApproval\OtMember::isAdmin((int) $me->id)))
          <div class="noti-menu">
            <button id="dlNotiTrigger" class="noti-trigger is-download" type="button"
                    aria-expanded="false" aria-controls="dlNotiPanel"
                    data-i18n-aria="noti.downloadAria" aria-label="เอกสารพร้อมดาวน์โหลด">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3v10" />
                <path d="m8 11 4 4 4-4" />
                <path d="M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" />
              </svg>
              <span class="noti-badge" data-dl-noti-badge @if (($otDownloadUnreadCount ?? 0) < 1) hidden @endif>{{ ($otDownloadUnreadCount ?? 0) > 99 ? '99+' : ($otDownloadUnreadCount ?? 0) }}</span>
            </button>

            <div id="dlNotiPanel" class="noti-panel" role="menu" aria-labelledby="dlNotiTrigger">
              <div class="noti-head">
                <strong data-i18n="noti.download">พร้อมดาวน์โหลด</strong>
                <button class="noti-readall" type="button" data-dl-noti-readall data-i18n="noti.readAll">อ่านทั้งหมด</button>
              </div>
              <div class="noti-list" data-dl-noti-list>
                <p class="noti-empty" data-i18n="noti.loading">กำลังโหลด...</p>
              </div>
            </div>
          </div>
        @endif

      {{-- ปุ่มสลับธีมถูกถอดออก — ระบบใช้ธีมสว่างอย่างเดียวแล้ว --}}

      @endisset

      <div class="language-picker" data-language-picker>
        <button class="language-button" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Language" data-language-toggle>
          <img class="language-flag" data-language-current-flag data-flag-src-th="{{ asset('assets/insight/flags/th.png') }}" data-flag-src-en="{{ asset('assets/insight/flags/en.png') }}" data-flag-src-my="{{ asset('assets/insight/flags/my.png') }}" src="{{ asset('assets/insight/flags/th.png') }}" alt="" aria-hidden="true">
        </button>
        <div class="language-menu" role="menu" aria-label="Language">
          <button type="button" data-lang-option="th" role="menuitemradio" aria-current="true"><span>ไทย</span><img class="language-flag" src="{{ asset('assets/insight/flags/th.png') }}" alt="" aria-hidden="true"></button>
          <button type="button" data-lang-option="en" role="menuitemradio" aria-current="false"><span>ENGLISH</span><img class="language-flag" src="{{ asset('assets/insight/flags/en.png') }}" alt="" aria-hidden="true"></button>
          <button type="button" data-lang-option="my" role="menuitemradio" aria-current="false"><span>မြန်မာ</span><img class="language-flag" src="{{ asset('assets/insight/flags/my.png') }}" alt="" aria-hidden="true"></button>
        </div>
      </div>

      <form method="POST" action="{{ route('logout') }}" class="topbar-logout-form" data-logout-form>
        @csrf
        <button type="submit" class="icon-toggle topbar-logout" data-i18n-aria="nav.logout" data-i18n-title="nav.logout" aria-label="ออกจากระบบ" title="ออกจากระบบ">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5M21 12H9"></path></svg>
        </button>
      </form>
    </div>
  </header>

  @php
    $portalAccessKey = session('toast_key');
    $portalAccessFallback = session('toast_fallback');
    $portalAccessType = session('toast_type') === 'success' ? 'success' : 'error';
  @endphp
  @if ($portalAccessKey || $portalAccessFallback)
    <div class="portal-access-modal is-{{ $portalAccessType }}" role="alertdialog" aria-modal="true" aria-describedby="portalAccessMessage" data-portal-access-modal>
      <div class="portal-access-dialog">
        <p class="portal-access-message" id="portalAccessMessage" @if ($portalAccessKey) data-i18n="{{ $portalAccessKey }}" @endif>{{ $portalAccessFallback ?: 'แจ้งเตือน' }}</p>
        <button type="button" class="portal-access-confirm" data-portal-access-close data-i18n="toast.ack" aria-describedby="portalAccessMessage">รับทราบ</button>
      </div>
    </div>
  @endif

  <div class="shell">
    {{-- ฉากหลังของลิ้นชักเมนูบนมือถือ — แตะที่ไหนก็ปิด --}}
    {{-- ไม่ใช้ attribute hidden เพราะ display:none จางเข้า/ออกไม่ได้ — ใช้ body.nav-open คุมผ่าน CSS แทน --}}
    <div class="nav-backdrop" data-nav-backdrop></div>

    <aside class="sidebar" id="portalSidebar">
      <nav class="side-nav" aria-label="เมนูหลัก">
        @if ($sysContext)
          {{-- อยู่ในระบบย่อย → เมนูของระบบนั้น + ปุ่มกลับ (skeleton spinner ทุกอัน) --}}
          <a href="{{ route('systems.index') }}" class="nav-item nav-back nav-go">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            <span data-i18n="nav.back">กลับสู่ SUPAVUT INSIGHT</span>
          </a>

          {{-- โลโก้ระบบที่กำลังใช้อยู่ วางใต้ปุ่มกลับ ให้รู้ตัวว่าอยู่ระบบไหน
               ใช้ไฟล์ชุดเดียวกับการ์ดในหน้า /systems จะได้ไม่ต้องดูแลรูปสองชุด --}}
          @php
            $sysMark = match ($sysContext) {
              'ot-approval' => 'assets/systems/time-leave-approval.png',
              'area5s' => 'assets/area5s/logo-5s.png',
              'assessment' => 'assets/systems/assessment.png',
              'recruit' => 'assets/systems/recruit.png',
              default => null,
            };
          @endphp
          @if ($sysMark)
            <div class="nav-sys-mark" aria-hidden="true">
              <img src="{{ asset($sysMark) }}" alt="">
            </div>
          @endif

          <p class="side-section-label">
            @if ($sysContext === 'recruit')
              <span data-i18n="nav.recruit">สรรหาบุคลากร</span>
            @elseif ($sysContext === 'area5s')
              <span data-i18n="nav.area5s">พื้นที่ 5ส</span>
            @elseif ($sysContext === 'ot-approval')
              <span data-i18n="nav.otApproval">จัดการเวลาและการลา</span>
            @else
              <span data-i18n="nav.assessment">ระบบประเมินพนักงาน</span>
            @endif
          </p>

          @if ($sysContext === 'recruit')
            @if ($recruitRole === 'admin')
              {{-- admin: ภาพรวม + กลุ่ม "รวมระบบ" (ซ่อนฟังก์ชัน DCC/Manager/Recruit) + ตั้งค่าระบบ --}}
              @php [$rcRoute, $rcActive, $rcI18n, $rcLabel, $rcSvg] = $rcTabMeta['overview']; @endphp
              <a href="{{ route($rcRoute) }}" class="nav-item nav-go {{ request()->routeIs($rcActive) ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">{!! $rcSvg !!}</svg>
                <span data-i18n="{{ $rcI18n }}">{{ $rcLabel }}</span>
              </a>

              @php
                $rcGroup = ['departments', 'request', 'review', 'inbox', 'downloads'];
                $rcGroupOpen = false;
                foreach ($rcGroup as $gk) { if (request()->routeIs($rcTabMeta[$gk][1])) { $rcGroupOpen = true; break; } }
              @endphp
              <details class="nav-group"{{ $rcGroupOpen ? ' open' : '' }}>
                <summary class="nav-item nav-group-summary">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
                  <span data-i18n="nav.rcAll">รวมระบบ</span>
                  <svg class="nav-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                </summary>
                <div class="nav-group-body">
                  @foreach ($rcGroup as $tk)
                    @php [$rcRoute, $rcActive, $rcI18n, $rcLabel, $rcSvg] = $rcTabMeta[$tk]; @endphp
                    <a href="{{ route($rcRoute) }}" class="nav-item nav-sub nav-go {{ request()->routeIs($rcActive) ? 'is-active' : '' }}">
                      <svg viewBox="0 0 24 24" aria-hidden="true">{!! $rcSvg !!}</svg>
                      <span data-i18n="{{ $rcI18n }}">{{ $rcLabel }}</span>
                    </a>
                  @endforeach
                </div>
              </details>

              @php [$rcRoute, $rcActive, $rcI18n, $rcLabel, $rcSvg] = $rcTabMeta['settings']; @endphp
              <a href="{{ route($rcRoute) }}" class="nav-item nav-go {{ request()->routeIs($rcActive) ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">{!! $rcSvg !!}</svg>
                <span data-i18n="{{ $rcI18n }}">{{ $rcLabel }}</span>
              </a>
            @else
              @foreach ($recruitTabs as $tk)
                @php [$rcRoute, $rcActive, $rcI18n, $rcLabel, $rcSvg] = $rcTabMeta[$tk]; @endphp
                <a href="{{ route($rcRoute) }}" class="nav-item nav-go {{ request()->routeIs($rcActive) ? 'is-active' : '' }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true">{!! $rcSvg !!}</svg>
                  <span data-i18n="{{ $rcI18n }}">{{ $rcLabel }}</span>
                </a>
              @endforeach
            @endif
          @elseif ($sysContext === 'assessment')
            @php
              $asmIsAdmin = isset($me) && $me->isAdmin();
              $asmIsHr = isset($me) && \App\Models\Assessment\AsmMember::isHr($me->id);
              $asmHierarchyAccess = isset($me)
                ? app(\App\Services\Assessment\HierarchyAccess::class)->accessFor($me)
                : ['can_evaluate' => false, 'can_review' => false];
              $asmCanEvaluate = (bool) ($asmHierarchyAccess['can_evaluate'] ?? false);
              $asmCanReview = (bool) ($asmHierarchyAccess['can_review'] ?? false);
              $asmCanSelf = isset($me)
                ? app(\App\Services\Assessment\SelfAssessmentRoster::class)->canAssess($me)
                : false;
              $asmManageOpen = request()->routeIs([
                'assessment.rounds.*',
                'assessment.scores.*',
                'assessment.questions.*',
                'assessment.results.*',
                'assessment.self.index',
                'assessment.self.participants.*',
                'assessment.self.level.*',
                'assessment.self.questions.*',
                'assessment.self.choices.*',
                'assessment.settings.*',
              ]);
            @endphp
            <a href="{{ route('assessment.index') }}" class="nav-item nav-go {{ request()->routeIs('assessment.index') ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h7V3H3zM14 21h7V3h-7zM3 21h7v-6H3z"></path></svg>
              <span data-i18n="nav.overview">ภาพรวม</span>
            </a>

            @if ($asmCanSelf)
              <a href="{{ route('assessment.self.form') }}" class="nav-item nav-go {{ request()->routeIs('assessment.self.form*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path><path d="m16 14 2 2 3-4"></path></svg>
                <span data-i18n="nav.asmSelf">การประเมินตัวเอง</span>
              </a>
            @endif

            @if ($asmCanEvaluate)
              <a href="{{ route('assessment.evaluate.index') }}" class="nav-item nav-go {{ request()->routeIs('assessment.evaluate.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16"></path><path d="M4 12h10"></path><path d="M4 19h7"></path><path d="m16 18 2 2 4-5"></path></svg>
                <span data-i18n="nav.asmEvaluate">ประเมินพนักงาน</span>
              </a>
            @endif

            @if ($asmCanReview)
              <a href="{{ route('assessment.review.index') }}" class="nav-item nav-go {{ request()->routeIs('assessment.review.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12s3-6 9-6 9 6 9 6-3 6-9 6-9-6-9-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                <span data-i18n="nav.asmReview">ตรวจสอบผลลัพธ์</span>
              </a>
            @endif

            @if ($asmIsAdmin || $asmIsHr)
              <details class="nav-group"{{ $asmManageOpen ? ' open' : '' }}>
                <summary class="nav-item nav-group-summary {{ $asmManageOpen ? 'is-active' : '' }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h10"></path><circle cx="18" cy="17" r="2"></circle></svg>
                  <span data-i18n="nav.asmManage">จัดการระบบประเมิน</span>
                  <svg class="nav-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                </summary>
                <div class="nav-group-body">
                  <a href="{{ route('assessment.rounds.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs('assessment.rounds.*') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
                    <span data-i18n="nav.asmRounds">เปิดรอบ</span>
                  </a>
                  <a href="{{ route('assessment.scores.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs(['assessment.scores.*', 'assessment.questions.*', 'assessment.results.*']) ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"></path><circle cx="19" cy="18" r="2"></circle></svg>
                    <span data-i18n="nav.asmScores">การประเมินพนักงาน</span>
                  </a>
                  <a href="{{ route('assessment.self.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs(['assessment.self.index', 'assessment.self.participants.*', 'assessment.self.level.*', 'assessment.self.questions.*', 'assessment.self.choices.*']) ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                    <span data-i18n="nav.asmSelf">การประเมินตัวเอง</span>
                  </a>
                  @if ($asmIsAdmin)
                    <a href="{{ route('assessment.settings.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs('assessment.settings.*') ? 'is-active' : '' }}">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"></path></svg>
                      <span data-i18n="nav.sysSettings">ตั้งค่าระบบ</span>
                    </a>
                  @endif
                </div>
              </details>
            @endif
          @elseif ($sysContext === 'ot-approval')
            @php
              $otIsInsightAdmin = isset($me) && $me->isAdmin();
              $otIsMemberAdmin = isset($me) && ! $otIsInsightAdmin && \App\Models\OtApproval\OtMember::isAdmin((int) $me->id);
              $otIsSettingsAdmin = $otIsInsightAdmin || $otIsMemberAdmin;
              /* สิทธิ์ OT กับการลาแยกกัน เมนูจึงต้องดูทีละระบบ
                 กลุ่มเมนูโผล่เมื่อมีสิทธิ์อย่างน้อยหนึ่งระบบ ส่วนลิงก์ข้างในขึ้นตามระบบของตัวเอง */
              $otRolesByModule = isset($me) && ! $otIsInsightAdmin
                ? \App\Models\OtApproval\OtDepartmentAssignment::query()
                  ->where('app_user_id', (int) $me->id)
                  ->get(['module', 'role'])
                  ->groupBy(fn ($row) => $row->module ?: \App\Models\OtApproval\OtDepartmentAssignment::MODULE_OT)
                  ->map(fn ($rows) => $rows->pluck('role')->all())
                  ->all()
                : [];
              $otHas = fn (string $module, string $role) => $otIsInsightAdmin
                || in_array($role, $otRolesByModule[$module] ?? [], true);

              $otIsOtForeman = $otHas(\App\Models\OtApproval\OtDepartmentAssignment::MODULE_OT, \App\Models\OtApproval\OtDepartmentAssignment::ROLE_FOREMAN);
              $otIsLeaveForeman = $otHas(\App\Models\OtApproval\OtDepartmentAssignment::MODULE_LEAVE, \App\Models\OtApproval\OtDepartmentAssignment::ROLE_FOREMAN);
              $otIsOtSupervisor = $otHas(\App\Models\OtApproval\OtDepartmentAssignment::MODULE_OT, \App\Models\OtApproval\OtDepartmentAssignment::ROLE_SUPERVISOR);
              $otIsLeaveSupervisor = $otHas(\App\Models\OtApproval\OtDepartmentAssignment::MODULE_LEAVE, \App\Models\OtApproval\OtDepartmentAssignment::ROLE_SUPERVISOR);
              $otIsForeman = $otIsOtForeman || $otIsLeaveForeman;
              $otIsSupervisor = $otIsOtSupervisor || $otIsLeaveSupervisor;
            @endphp
            <a href="{{ route('ot-approval.home') }}" class="nav-item nav-go {{ request()->routeIs('ot-approval.home', 'ot-approval.index', 'ot-approval.leave-overview*') ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h7V3H3zM14 21h7V3h-7zM3 21h7v-6H3z"></path></svg>
              <span data-i18n="nav.overview">ภาพรวม</span>
            </a>
            {{-- รายละเอียดการทำงานของตัวเอง — ทุกคนเข้าดูได้ ไม่ต้องมีสิทธิ์ foreman/supervisor --}}
            <a href="{{ route('ot-approval.work-detail') }}" class="nav-item nav-go {{ request()->routeIs('ot-approval.work-detail') ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v4M19 3v4M3 9h18M5 5h14a2 2 0 0 1 2 2v13H3V7a2 2 0 0 1 2-2Z"></path><path d="M8 13h3M8 17h6"></path></svg>
              <span data-i18n="nav.workDetail">รายละเอียดการทำงาน</span>
            </a>
            @if ($otIsForeman)
              @php $otRequestGroupOpen = request()->routeIs('ot-approval.requests.*', 'ot-approval.leave-requests.*'); @endphp
              <details class="nav-group"{{ $otRequestGroupOpen ? ' open' : '' }}>
                <summary class="nav-item nav-group-summary {{ $otRequestGroupOpen ? 'is-active' : '' }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                  <span data-i18n="nav.requestGroup">การยื่นคำขอ</span>
                  <svg class="nav-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                </summary>
                <div class="nav-group-body">
                  @if ($otIsOtForeman)
                    <a href="{{ route('ot-approval.requests.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs('ot-approval.requests.*') ? 'is-active' : '' }}">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16M12 4v16"></path></svg>
                      <span data-i18n="nav.otRequests">ยื่นขอค่าล่วงเวลา</span>
                    </a>
                  @endif
                  @if ($otIsLeaveForeman)
                    <a href="{{ route('ot-approval.leave-requests.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs('ot-approval.leave-requests.*') ? 'is-active' : '' }}">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v4M19 3v4M3 9h18M5 5h14a2 2 0 0 1 2 2v13H3V7a2 2 0 0 1 2-2Z"></path><path d="m8 15 2 2 5-5"></path></svg>
                      <span data-i18n="nav.leaveRequests">ยื่นขอหยุดงานตามมาตรา 75</span>
                    </a>
                  @endif
                </div>
              </details>
            @endif
            @if ($otIsSupervisor)
              @php $otApprovalGroupOpen = request()->routeIs('ot-approval.approvals.*', 'ot-approval.leave-approvals.*'); @endphp
              <details class="nav-group"{{ $otApprovalGroupOpen ? ' open' : '' }}>
                <summary class="nav-item nav-group-summary {{ $otApprovalGroupOpen ? 'is-active' : '' }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path><path d="M4 4h16v16H4z"></path></svg>
                  <span data-i18n="nav.approvalGroup">การตรวจอนุมัติ</span>
                  <svg class="nav-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                </summary>
                <div class="nav-group-body">
                  @if ($otIsOtSupervisor)
                    <a href="{{ route('ot-approval.approvals.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs('ot-approval.approvals.*') ? 'is-active' : '' }}">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
                      <span data-i18n="nav.otApprovals">อนุมัติค่าล่วงเวลา</span>
                    </a>
                  @endif
                  @if ($otIsLeaveSupervisor)
                    <a href="{{ route('ot-approval.leave-approvals.index') }}" class="nav-item nav-sub nav-go {{ request()->routeIs('ot-approval.leave-approvals.*') ? 'is-active' : '' }}">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v16H6z"></path><path d="m9 12 2 2 4-5"></path></svg>
                      <span data-i18n="nav.leaveApprovals">อนุมัติหยุดงานตามมาตรา 75</span>
                    </a>
                  @endif
                </div>
              </details>
            @endif
            {{-- ดาวน์โหลดเอกสารส่ง HR เป็นงานของ admin แยกจากหน้าภาพรวม เพราะต้องย้อนไปโหลดวันไหนก็ได้ --}}
            @if ($otIsSettingsAdmin)
              <a href="{{ route('ot-approval.downloads.index') }}" class="nav-item nav-go {{ request()->routeIs('ot-approval.downloads.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 11 5 5 5-5"></path><path d="M5 21h14"></path></svg>
                <span data-i18n="nav.otDownloads">ดาวน์โหลดเอกสารส่ง HR</span>
              </a>
            @endif
            @if ($otIsSettingsAdmin)
              <a href="{{ route('ot-approval.settings.index') }}" class="nav-item nav-go {{ request()->routeIs('ot-approval.settings.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15Z"></path></svg>
                <span data-i18n="nav.sysSettings">ตั้งค่าระบบ</span>
              </a>
            @endif
          @elseif ($sysContext === 'area5s')
            {{-- ── SUPAVUT 5S AREA ── เมนูจะเพิ่มตามเฟส (รอบ/ตรวจ ฯลฯ) --}}
            @php
              $a5sIsAdmin = isset($me) && ($me->isAdmin() || \App\Models\Area5s\A5sMember::hasRole($me->id, \App\Models\Area5s\A5sMember::ROLE_ADMIN));
              $a5sIsAllocator = isset($me) && \App\Models\Area5s\A5sMember::hasRole($me->id, \App\Models\Area5s\A5sMember::ROLE_ALLOCATOR);
              $a5sEmployeeCode = trim((string) ($me->employee_code ?? ''));
              $a5sIsResponsible = isset($me) && $a5sEmployeeCode !== '' && \App\Models\Area5s\A5sPointAssignee::where('employee_code', $a5sEmployeeCode)->whereNull('removed_at')->exists();
              $a5sIsEvaluator = $a5sIsAdmin || ($a5sEmployeeCode !== '' && \App\Models\Area5s\A5sEvaluatorScope::where('employee_code', $a5sEmployeeCode)->exists());
            @endphp
            <a href="{{ route('area5s.home') }}" class="nav-item nav-go {{ request()->routeIs('area5s.home') ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l9-8 9 8"></path><path d="M5 10v10h14V10"></path><path d="M9 20v-6h6v6"></path></svg>
              <span data-i18n="nav.a5sHome">หน้าหลัก</span>
            </a>
            <a href="{{ route('area5s.index') }}" class="nav-item nav-go {{ request()->routeIs(['area5s.index', 'area5s.areas.show', 'area5s.layouts.show']) ? 'is-active' : '' }}">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h7V3H3zM14 21h7V3h-7zM3 21h7v-6H3z"></path></svg>
              <span data-i18n="nav.overview">ภาพรวม</span>
            </a>
            @if ($a5sIsResponsible)
              <a href="{{ route('area5s.responsible.index') }}" class="nav-item nav-go {{ request()->routeIs('area5s.responsible.*') && request('mode') !== 'review' ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11.5 11 13.5 15.5 8.5"></path><path d="M20 10.5c0 6-8 10.5-8 10.5S4 16.5 4 10.5a8 8 0 1 1 16 0Z"></path></svg>
                <span data-i18n="nav.a5sMyWork">งานพื้นที่ของฉัน</span>
              </a>
            @endif
            @if ($a5sIsEvaluator)
              <a href="{{ route('area5s.responsible.index', ['mode' => 'review']) }}" class="nav-item nav-go {{ request()->routeIs('area5s.evaluations.*') || request()->routeIs('area5s.review.*') || (request()->routeIs('area5s.responsible.index') && request('mode') === 'review') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12s3-6 9-6 9 6 9 6-3 6-9 6-9-6-9-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                <span data-i18n="nav.a5sReview">ตรวจประเมิน</span>
              </a>
            @endif
            @if ($a5sIsAdmin || $a5sIsAllocator)
              <a href="{{ route('area5s.manage') }}" class="nav-item nav-go {{ request()->routeIs(['area5s.manage', 'area5s.manage.*', 'area5s.layouts.editor']) ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 20 3 17V4l6 3 6-3 6 3v13l-6-3-6 3Z"></path><path d="M9 7v13M15 4v13"></path></svg>
                <span data-i18n="nav.a5sManage">จัดการพื้นที่</span>
              </a>
            @endif
            @if ($a5sIsAdmin)
              <a href="{{ route('area5s.rounds.index') }}" class="nav-item nav-go {{ request()->routeIs('area5s.rounds.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 10h18"></path></svg>
                <span data-i18n="nav.a5sRounds">รายเดือน</span>
              </a>
              <a href="{{ route('area5s.downloads.index') }}" class="nav-item nav-go {{ request()->routeIs('area5s.downloads.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
                <span data-i18n="nav.a5sDownloads">ดาวน์โหลดเอกสาร</span>
              </a>
              <a href="{{ route('area5s.settings.index') }}" class="nav-item nav-go {{ request()->routeIs('area5s.settings.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 20a6.5 6.5 0 0 1 11 0"></path><path d="M17 8h5M19.5 5.5v5"></path></svg>
                <span data-i18n="nav.a5sMembers">ตั้งค่าระบบ</span>
              </a>
            @endif
          @endif
        @else
          {{-- บริบทหลัก (skeleton spinner ทุกอัน) --}}
          <a href="{{ route('dashboard') }}" class="nav-item nav-go {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
            <span data-i18n="nav.info">ข้อมูล</span>
          </a>

          <a href="{{ route('systems.index') }}" class="nav-item nav-go {{ request()->routeIs('systems.*') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
            <span data-i18n="nav.systems">ระบบ HR ทั้งหมด</span>
          </a>

          @isset($me)
            @if ($me->isAdmin())
              <a href="{{ route('admin.overview') }}" class="nav-item nav-go {{ request()->routeIs('admin.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h8V3H3zM13 21h8V11h-8zM13 3v6h8V3zM3 21h8v-6H3z"></path></svg>
                <span data-i18n="nav.admin">ภาพรวมระบบ</span>
              </a>

              <a href="{{ route('settings.index') }}" class="nav-item nav-go {{ request()->routeIs('settings.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"></path></svg>
                <span data-i18n="nav.settings">ตั้งค่า</span>
              </a>
            @endif
          @endisset
        @endif

      </nav>

      {{-- ช่องให้แต่ละระบบแทรกสถานะของตัวเองเหนือบรรทัดลิขสิทธิ์ --}}
      @yield('side-foot-extra')

      <div class="side-foot">SUPAVUT INSIGHT {{ date('Y') }}</div>
    </aside>

    <main class="main">
      @php
        $portalSuccessMessage = session('success');
        $portalArea5sModal = request()->routeIs('area5s.*');
        $suppressPortalSuccess = request()->routeIs(['area5s.manage', 'area5s.manage.*'])
          && (
            session()->has('a5m_ok_title')
            || $portalSuccessMessage === 'สร้าง Layout แล้ว กด "แก้ไข / ปักจุด" เพื่อกำหนดจุดพื้นที่'
          );
      @endphp
      @if ($portalSuccessMessage && ! $suppressPortalSuccess)
        @if ($portalArea5sModal)
          {{-- 5S: success เป็น modal แบบ Assessment (Manager สั่ง) — module อื่นยังใช้แถบเขียวเดิม --}}
          <div class="pt-ok-modal" data-pt-ok-modal>
            <button type="button" class="pt-ok-backdrop" data-pt-ok-close aria-label="ปิด" data-i18n-aria="common.close"></button>
            <div class="pt-ok-dialog" role="alertdialog" aria-modal="true" aria-labelledby="ptOkTitle" aria-describedby="ptOkMessage">
              <div class="pt-ok-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
              </div>
              <h3 id="ptOkTitle" data-i18n="common.success">สำเร็จ</h3>
              <p id="ptOkMessage">{{ $portalSuccessMessage }}</p>
              <button type="button" class="pt-ok-btn" data-pt-ok-close data-i18n="common.ok">ตกลง</button>
            </div>
          </div>
          <script>
            'use strict';
            (function () {
              const modal = document.querySelector('[data-pt-ok-modal]');
              if (!modal) return;
              const onKey = e => {
                if (e.key === 'Escape' || e.key === 'Enter') close();
              };
              const close = () => {
                document.removeEventListener('keydown', onKey);
                modal.remove();
                document.body.style.overflow = '';
              };
              document.body.style.overflow = 'hidden';
              modal.querySelectorAll('[data-pt-ok-close]').forEach(btn => btn.addEventListener('click', close));
              document.addEventListener('keydown', onKey);
            })();
          </script>
        @else
          <div class="flash success">{{ $portalSuccessMessage }}</div>
        @endif
      @endif
      @if (session('error')) <div class="flash error">{{ session('error') }}</div> @endif
      @yield('content')
    </main>
  </div>

  <div class="image-viewer" id="imageViewer" role="dialog" aria-modal="true" aria-hidden="true" data-i18n-aria="profile.view_photo" aria-label="ดูรูปโปรไฟล์ขนาดใหญ่">
    <button class="image-viewer-backdrop" type="button" tabindex="-1" data-image-viewer-close data-i18n-aria="profile.close_photo" aria-label="ปิดรูปภาพ"></button>
    <img id="imageViewerImg" alt="" draggable="false">
    <button class="image-viewer-close" type="button" data-image-viewer-close data-i18n-aria="profile.close_photo" aria-label="ปิดรูปภาพ">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
    </button>
    <div class="image-viewer-zoom" role="group" data-i18n-aria="profile.zoom_group" aria-label="ปรับขนาดรูป">
      <button type="button" class="izoom-btn" data-image-zoom-out data-i18n-aria="profile.zoom_out" aria-label="ซูมออก">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg>
      </button>
      <button type="button" class="izoom-val" data-image-zoom-reset data-i18n-aria="profile.zoom_reset" aria-label="รีเซ็ตขนาด 100%">100%</button>
      <button type="button" class="izoom-btn" data-image-zoom-in data-i18n-aria="profile.zoom_in" aria-label="ซูมเข้า">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
      </button>
    </div>
  </div>

  <script>
    'use strict';

    /* ── ปุ่มขีด 3 ขีด: เก็บ/กางเมนูซ้าย (Manager สั่ง 2026-08-25) ──────────────
       จอใหญ่  = พับเมนูซ้ายทิ้ง เนื้อหาขยายเต็มจอ · จำค่าไว้ให้ครั้งหน้า
       มือถือ  = เมนูเป็นลิ้นชักเลื่อนทับ ค่าเริ่มต้นคือปิด เนื้อหาจึงเต็มจอเสมอ
                 (ของเดิมพับเป็นแถวปุ่มวางเหนือเนื้อหา กินที่จนอ่านยาก)
       แยกเป็น IIFE ของตัวเอง ถ้าก้อนใหญ่ข้างล่างพัง ปุ่มนี้ยังใช้ได้ */
    (function portalNavToggle() {
      var toggle = document.querySelector('[data-nav-toggle]');
      var sidebar = document.getElementById('portalSidebar');
      if (!toggle || !sidebar) return;

      var backdrop = document.querySelector('[data-nav-backdrop]');
      var body = document.body;
      var storeKey = 'insight_nav_collapsed';
      var mobile = function () { return window.matchMedia('(max-width: 860px)').matches; };

      function sync() {
        var open = mobile() ? body.classList.contains('nav-open') : !body.classList.contains('nav-collapsed');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        /* ฉากหลังจางเข้า/ออกด้วย CSS จาก body.nav-open — ที่นี่บอกแค่สถานะให้ screen reader */
        if (backdrop) backdrop.setAttribute('aria-hidden', (mobile() && open) ? 'false' : 'true');
        /* ล็อกการเลื่อนพื้นหลังเฉพาะตอนลิ้นชักเปิดบนมือถือ
           ห้ามล็อกบนจอใหญ่ ไม่งั้นหน้าเลื่อนไม่ได้ทั้งที่เมนูแค่ถูกพับ */
        body.style.overflow = (mobile() && open) ? 'hidden' : '';
      }

      /* คืนค่าที่เคยเลือกไว้ — เฉพาะจอใหญ่ ส่วนมือถือเริ่มที่ปิดเสมอ */
      try {
        if (!mobile() && window.localStorage.getItem(storeKey) === '1') body.classList.add('nav-collapsed');
      } catch (error) { /* โหมดส่วนตัว/บล็อก storage ก็ข้ามไป ไม่ใช่สาระสำคัญ */ }
      sync();

      toggle.addEventListener('click', function () {
        if (mobile()) {
          body.classList.toggle('nav-open');
        } else {
          var collapsed = body.classList.toggle('nav-collapsed');
          try { window.localStorage.setItem(storeKey, collapsed ? '1' : '0'); } catch (error) { /* ข้ามไป */ }
        }
        sync();
      });

      if (backdrop) backdrop.addEventListener('click', function () { body.classList.remove('nav-open'); sync(); });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && body.classList.contains('nav-open')) { body.classList.remove('nav-open'); sync(); }
      });
      /* กดเมนูแล้วต้องปิดลิ้นชักเอง ไม่งั้นหน้าใหม่โหลดมาโดยลิ้นชักยังค้างเปิด */
      sidebar.addEventListener('click', function (event) {
        if (mobile() && event.target.closest('a, button')) { body.classList.remove('nav-open'); sync(); }
      });
      /* หมุนจอ/ย่อ-ขยายหน้าต่างข้ามเส้น 860px ต้องคืนสถานะให้ตรงโหมด */
      window.addEventListener('resize', function () {
        if (!mobile()) body.classList.remove('nav-open');
        sync();
      });
    })();

    (function () {
      var languageStoreKey = 'insight_lang';
      var copy = {
        th: {
          'loader.switching': 'กำลังเปลี่ยนระบบ',
          'loader.enter': 'กำลังเข้าสู่ ',
          'loader.loading': 'กำลังโหลด',
          'loader.logout': 'กำลังออกจากระบบ',
          'toast.recruitDenied': 'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Recruit System',
          'toast.assessmentDenied': 'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Assessment',
          'toast.pageDenied': 'คุณไม่มีสิทธิ์เข้าหน้านี้',
          'toast.close': 'ปิดการแจ้งเตือน',
          'toast.ack': 'รับทราบ',
          'welcome.greeting': 'ยินดีต้อนรับ',
          'nav.info': 'ข้อมูล',
          'nav.recruit': 'สรรหาบุคลากร',
          'nav.assessment': 'ระบบประเมินพนักงาน',
          'nav.systems': 'ระบบ HR ทั้งหมด',
          'nav.overview': 'ภาพรวม',
          'nav.a5sHome': 'หน้าหลัก',
          'nav.toggleMenu': 'เปิด/ปิดเมนู',
          'nav.otHome': 'หน้าหลัก',
          'noti.aria': 'การแจ้งเตือน',
          'noti.title': 'การแจ้งเตือน',
          'noti.readAll': 'อ่านทั้งหมด',
          'noti.loading': 'กำลังโหลด...',
          'noti.empty': 'ยังไม่มีการแจ้งเตือน',
          'noti.error': 'โหลดการแจ้งเตือนไม่สำเร็จ',
          'nav.a5sMyWork': 'งานพื้นที่ของฉัน',
          'nav.a5sManage': 'จัดการพื้นที่',
          'nav.a5sMembers': 'ตั้งค่าระบบ',
          'nav.a5sRounds': 'รายเดือน',
          'nav.a5sDownloads': 'ดาวน์โหลดเอกสาร',
          'a5s.rounds.title': 'รอบรายเดือน',
          'a5s.downloads.title': 'ดาวน์โหลดเอกสาร',
          'nav.back': 'กลับสู่ SUPAVUT INSIGHT',
          'nav.admin': 'ภาพรวมระบบ',
          'nav.settings': 'ตั้งค่า',
          'nav.sysSettings': 'ตั้งค่าระบบ',
          'nav.asmManage': 'จัดการระบบประเมิน',
          'nav.asmRounds': 'เปิดรอบ',
          'nav.asmScores': 'การประเมินพนักงาน',
          'nav.asmEvaluate': 'ประเมินพนักงาน',
          'nav.asmReview': 'ตรวจสอบผลลัพธ์',
          'nav.asmSelf': 'การประเมินตัวเอง',
          'nav.rcDepts': 'แผนกภายใต้สังกัด',
          'nav.rcRequest': 'ขออัตรากำลังคน',
          'nav.rcReview': 'ตรวจเอกสาร',
          'nav.rcInbox': 'กล่องเอกสาร',
          'nav.rcDownloads': 'ดาวน์โหลด',
          'nav.rcAll': 'รวมระบบ',
          'rc.wip': 'อยู่ระหว่างพัฒนา',
          'rc.stubNote': 'ส่วนนี้กำลังพัฒนา จะเปิดใช้งานเร็ว ๆ นี้',
          'rq.intro': 'ฟอร์มออนไลน์ตาม SI-HR-010 เลือกเส้นทางอนุมัติและแนบไฟล์ประกอบ',
          'rq.workflowTitle': 'กรุณากรอกข้อมูล',
          'rq.workflowNote': 'กรุณากรอกข้อมูล',
          'rq.workflowStepNote': 'ตรวจข้อมูลผู้ขอ เลือกเส้นทางอนุมัติ และเลือก Manager',
          'rq.formStepTitle': 'กรอกฟอร์มขออัตรากำลังคน',
          'rq.formStepNote': 'กรอกข้อมูลในเอกสาร SI-HR-010 ให้ครบ',
          'rq.jdStepNote': 'แนบไฟล์ JD ของตำแหน่งที่ต้องการสรรหา',
          'rq.resignStepNote': 'ใช้เมื่อขอทดแทนพนักงานลาออก หรือแนบเอกสารตามจำเป็น',
          'rq.requester': 'ข้อมูลผู้ขอ',
          'rq.empCode': 'รหัสพนักงาน',
          'rq.empName': 'ชื่อ-สกุล',
          'rq.empPosition': 'ตำแหน่ง',
          'rq.empDept': 'แผนก',
          'rq.chooseRoute': 'เลือกเส้นทางอนุมัติ',
          'rq.pickManager': 'เลือก Manager',
          'rq.routeEmpty': 'ยังไม่มีเส้นทางที่เลือกได้ ตรวจสอบการตั้งค่าเส้นทางอนุมัติในหน้าตั้งค่าระบบ',
          'rq.attachTitle': 'ไฟล์แนบประกอบคำขอ',
          'rq.attachNote': 'แนบ Job Description และใบลาออกหรือเอกสารอื่นก่อนส่งคำขอ',
          'rq.attachJd': 'แนบไฟล์ Job Description',
          'rq.attachJdNote': 'รองรับ PDF, Word, Excel หรือรูปภาพ',
          'rq.attachResign': 'แนบไฟล์ใบลาออก',
          'rq.attachResignNote': 'ใช้กรณีขอทดแทนพนักงานลาออก หรือแนบเอกสารอื่นตามจำเป็น',
          'rq.resultMock': 'แสดงผลเหมือนส่งคำขอแล้ว แต่ยังไม่บันทึกฐานข้อมูลจริงในรอบนี้',
          'rq.reset': 'ล้างฟอร์ม',
          'rq.submit': 'ส่งคำขอ',
          'rq.confirmTitle': 'ยืนยันการส่งคำขอ',
          'rq.confirmNote': 'ตรวจสอบข้อมูลและไฟล์แนบก่อนส่ง เมื่อบันทึกจริงคำขอจะถูกส่งไปยัง Manager ตามเส้นทางที่เลือก',
          'rq.cancel': 'ยกเลิก',
          'rq.confirmSubmit': 'ยืนยันส่งคำขอ',
          'set.kicker': 'ตั้งค่าระบบ',
          'set.title': 'ตั้งค่า',
          'set.login.title': 'สิทธิ์การเข้าสู่ระบบตามตำแหน่ง',
          'set.login.note': 'ติ๊กตำแหน่งที่อนุญาตให้ล็อกอิน — ตำแหน่งที่ไม่ติ๊กจะเข้าสู่ระบบไม่ได้ (ผู้ดูแลระบบเข้าได้เสมอ)',
          'set.email.title': 'สิทธิ์การกรอกอีเมล',
          'set.email.note': 'ติ๊กตำแหน่งที่อนุญาตให้กรอก/บันทึกอีเมลในหน้าโปรไฟล์ — ตำแหน่งที่ไม่ติ๊กจะไม่เห็นช่องอีเมล (ผู้ดูแลระบบทำได้เสมอ)',
          'set.sig.title': 'สิทธิ์การบันทึกลายเซ็น',
          'set.sig.note': 'ติ๊กตำแหน่งที่อนุญาตให้บันทึกลายเซ็นในหน้าโปรไฟล์ — ตำแหน่งที่ไม่ติ๊กจะไม่เห็นแผ่นเซ็น (ผู้ดูแลระบบทำได้เสมอ)',
          'set.selectAll': 'เลือกทั้งหมด',
          'set.clearAll': 'ล้างทั้งหมด',
          'set.save': 'บันทึกการตั้งค่า',
          'set.people': 'คน',
          'set.unitPos': 'ตำแหน่ง',
          'set.stateAll': 'อนุญาตทุกตำแหน่ง',
          'set.employeeData': 'ข้อมูลพนักงาน',
          'set.photos.title': 'รูปพนักงาน',
          'set.photos.note': 'ดึงรูปที่ HR ถ่ายไว้ในโฟลเดอร์กลาง (ตั้งชื่อไฟล์เป็นรหัสพนักงาน) เข้าระบบแล้วผูกกับตัวพนักงาน รูปจะติดตัวไปแม้พนักงานลาออกและบัญชีถูกลบแล้ว',
          'set.photos.sourceOk': 'เข้าโฟลเดอร์ต้นทางได้',
          'set.photos.sourceFail': 'เข้าโฟลเดอร์ต้นทางไม่ได้ เครื่องที่รันระบบต้องต่อ share นี้ได้ก่อน',
          'set.photos.statWith': 'พนักงานที่มีรูปแล้ว',
          'set.photos.statMissing': 'ยังไม่มีรูป',
          'set.photos.statOrphan': 'ไฟล์ที่หาเจ้าของไม่ได้',
          'set.photos.statLastRun': 'ดึงรูปรอบล่าสุด',
          'set.photos.pull': 'ดึงรูปใหม่ตอนนี้',
          'set.photos.pulling': 'กำลังดึงรูป...',
          'set.photos.done': 'ดึงรูปเสร็จแล้ว',
          'set.photos.failed': 'ดึงรูปไม่สำเร็จ',
          'set.photos.connectionError': 'เชื่อมต่อไม่สำเร็จ',
          'set.photos.resImported': 'ใหม่',
          'set.photos.resUpdated': 'อัปเดต',
          'set.photos.resUnchanged': 'เหมือนเดิม',
          'set.photos.resOrphan': 'หาเจ้าของไม่ได้',
          'set.photos.missingTitle': 'พนักงานที่ยังไม่มีรูป (ให้ HR ตามถ่าย)',
          'set.photos.noMissing': 'พนักงานที่ทำงานอยู่มีรูปครบทุกคนแล้ว',
          'set.photos.orphanTitle': 'ไฟล์รูปที่หาเจ้าของไม่ได้',
          'set.photos.noOrphan': 'ไม่มีไฟล์ที่หาเจ้าของไม่ได้',
          'set.photos.colCode': 'รหัส',
          'set.photos.colName': 'ชื่อ-สกุล',
          'set.photos.colPosition': 'ตำแหน่ง',
          'set.photos.colDept': 'แผนก',
          'set.photos.colHire': 'วันเริ่มงาน',
          'set.photos.colFile': 'ชื่อไฟล์',
          'set.photos.colReason': 'สาเหตุ',
          'set.photos.colModified': 'วันที่ไฟล์',
          'set.bplus.title': 'ดึงข้อมูลพนักงาน B Plus',
          'set.bplus.actionsCount': '2 ปุ่ม',
          'set.bplus.note': 'ดึงข้อมูลล่าสุดจาก Bplus และซิงค์บัญชีล็อกอิน — เสร็จแล้วเปิดรายงานอัตโนมัติ และเปิดดูรายงานรอบล่าสุดย้อนหลังได้ตลอด',
          'set.bplus.pull': 'ดึงข้อมูลจาก B Plus',
          'set.bplus.syncAccounts': 'ซิงค์บัญชีล็อกอิน',
          'set.bplus.latestPull': 'ดูผลการดึงข้อมูลล่าสุด',
          'set.bplus.latestSync': 'ดูผลการซิงค์ล่าสุด',
          'set.bplus.reportKicker': 'รายงานการเปลี่ยนแปลง',
          'set.bplus.report': 'รายงาน',
          'set.bplus.latestRun': 'รอบล่าสุด',
          'set.bplus.running': 'กำลังทำงาน...',
          'set.bplus.connectionError': 'เชื่อมต่อไม่สำเร็จ',
          'set.bplus.error': 'ผิดพลาด',
          'set.bplus.emptyTitle': 'ไม่มีรายชื่อในหมวด {label}',
          'set.bplus.emptyBody': 'รอบล่าสุดไม่มีการเปลี่ยนแปลงในหมวดนี้',
          'set.bplus.employeeCode': 'รหัสพนักงาน',
          'set.bplus.fullName': 'ชื่อ-สกุล',
          'set.bplus.position': 'ตำแหน่ง',
          'set.bplus.department': 'แผนก',
          'set.bplus.company': 'บริษัท',
          'set.bplus.pullTitle': 'ดึงข้อมูลพนักงาน B Plus',
          'set.bplus.syncTitle': 'ซิงค์บัญชีล็อกอิน',
          'set.bplus.tabNewHires': 'พนักงานใหม่ (7 วัน)',
          'set.bplus.tabAll': 'รวมทั้งหมด',
          'set.bplus.tabWorking': 'ทำงาน',
          'set.bplus.tabPendingResign': 'ลาออก (รอปิดงวด)',
          'set.bplus.tabResigned': 'ลาออก',
          'set.bplus.tabNewAccounts': 'สร้างบัญชีใหม่ (7 วัน)',
          'set.bplus.tabUpdatedAccounts': 'อัปเดตบัญชี',
          'set.bplus.tabRemovedAccounts': 'ลบบัญชี (ลาออก)',
          'set.bplus.extraStartDate': 'วันเริ่มงาน',
          'set.bplus.extraStatus': 'สถานะ',
          'set.bplus.extraEffectiveResignDate': 'วันที่มีผลลาออก',
          'set.bplus.extraResignDate': 'วันลาออก',
          'set.bplus.extraSyncedAt': 'เวลาที่ซิงค์',
          'set.bplus.statusWorking': 'ทำงาน',
          'set.bplus.statusPendingResign': 'ลาออก (รอปิดงวด)',
          'set.bplus.statusResigned': 'ลาออก',
          'set.export.title': 'ดาวน์โหลดข้อมูลพนักงาน (Excel)',
          'set.export.note': 'ดาวน์โหลดพนักงานที่ทำงานอยู่ทั้งหมด — รหัส · ชื่อ-สกุล (ไทย) · ชื่อ-สกุล (อังกฤษ) · ตำแหน่ง · แผนก · เลขที่ประกันสังคม โดยระบบเติมชื่ออังกฤษที่ว่างจากชื่อในระบบให้อัตโนมัติ',
          'set.export.button': 'ดาวน์โหลด Excel พนักงาน',
          'set.recruit.title': 'สิทธิ์ผู้ใช้งาน Recruit',
          'set.recruit.note': 'กำหนดบทบาทให้พนักงานแต่ละคน — เฉพาะคนที่ถูกเพิ่มเท่านั้นจึงเข้าระบบ Recruit ได้ (ผู้ดูแลระบบเข้าได้เสมอ)',
          'set.recruit.add': 'เพิ่มสมาชิก',
          'set.recruit.unit': 'บทบาท',
          'set.recruit.routeTitle': 'ลำดับเส้นทางเอกสาร',
          'set.recruit.routeNote': 'เอกสารคำขอจะเดินตามลำดับนี้ — admin ปรับลำดับได้',
          'set.recruit.saveRoute': 'บันทึกเส้นทาง',
          'set.recruit.pickTitle': 'เพิ่มสมาชิก',
          'set.recruit.search': 'ค้นหาชื่อ / รหัสพนักงาน',
          'set.recruit.empty': 'ไม่พบพนักงาน',
          'set.recruit.loading': 'กำลังค้นหา...',
          'set.recruit.note2': 'สร้างได้หลายเส้นทางอนุมัติ แต่ละเส้นทางจัดลำดับการ์ด เลือกบทบาท และเพิ่มสมาชิก — DCC จะเลือกเองตอนส่งคำขอว่าจะเดินตามเส้นทางไหน',
          'set.recruit.addCard': 'เพิ่มการ์ด',
          'set.recruit.addRoute': 'เพิ่มเส้นทาง',
          'set.recruit.delRoute': 'ลบเส้นทาง',
          'set.recruit.delRouteConfirm': 'ลบเส้นทางนี้พร้อมการ์ดทั้งหมด?',
          'set.recruit.noRoute': 'ยังไม่มีเส้นทาง — กด "เพิ่มเส้นทาง" เพื่อสร้างเส้นทางอนุมัติแรก',
          'set.recruit.pickRole': 'เลือกบทบาท',
          'set.recruit.role.dcc': 'DCC',
          'set.recruit.role.manager': 'Manager',
          'set.recruit.role.general_manager': 'General Manager',
          'set.recruit.role.hr_manager': 'HR Manager',
          'set.recruit.role.recruit': 'Recruit',
          'set.recruit.dept': 'แผนก',
          'set.recruit.deptTitle': 'แผนกที่มองเห็นได้',
          'set.recruit.deptSave': 'บันทึกแผนก',
          'set.recruit.noStep': 'ยังไม่มีการ์ด — กด "เพิ่มการ์ด" เพื่อสร้างขั้นเส้นทางแรก',
          'set.recruit.delCard': 'ลบการ์ดนี้?',
          'rc.overview': 'ภาพรวม',
          'rc.historyTitle': 'ประวัติการขออัตรากำลังคน',
          'rc.legend.done': 'เสร็จ',
          'rc.legend.pending': 'รอดำเนินการ',
          'rc.legend.ack': 'รับทราบ (Recruit)',
          'rc.col.requester': 'ผู้ขอ',
          'rc.col.reqDate': 'วันที่ขอ',
          'rc.col.startDate': 'วันเริ่มงาน',
          'rc.col.status': 'สถานะ',
          'rc.empty': 'ยังไม่มีคำขออัตรากำลังคน',
          'nav.logout': 'ออกจากระบบ',
          'systems.kicker': 'ระบบ',
          'systems.title': 'ระบบทั้งหมด',
          'systems.recruitName': 'RECRUIT SYSTEM',
          'systems.recruitDesc': 'ระบบจัดการคำขออัตรากำลังคนภายในองค์กร',
          'systems.assessmentName': 'SUPAVUT ASSESSMENT',
          'systems.assessmentDesc': 'เครื่องมือประเมินผลและจัดการคะแนนพนักงาน',
          'systems.devName': 'SUPAVUT 5S AREA',
          'systems.devDesc': 'พื้นที่สำหรับจัดการงานทำความสะอาดและจุดรับผิดชอบภายในองค์กร',
          'systems.comingName': 'COMING SOON',
          'systems.comingDesc': 'ระบบใหม่กำลังเตรียมเปิดใช้งาน',
          'systems.moreName': 'MORE SYSTEMS',
          'systems.moreDesc': 'ระบบอื่น ๆ จะเพิ่มเติมในอนาคต',
          'systems.searchPh': 'ค้นหาระบบ เอกสาร หรือชื่อคน',
          'systems.heroTitle': 'Welcome to Supavut Insight Intranet',
          'systems.heroKicker': 'WELCOME TO',
          'systems.heroTitleMain': 'SUPAVUT INSIGHT',
          'systems.heroTitleAccent': 'Intranet',
          'systems.heroSubtitle': 'ศูนย์กลางระบบ HR ข้อมูลพนักงาน เอกสาร และบริการภายในของ SUPAVUT',
          'systems.appAccess': 'ระบบหลักทั้งหมด',
          'systems.availableNow': '3 ระบบหลัก',
          'systems.availableNowWithRecruit': '4 ระบบหลัก',
          'systems.launcher': 'Application Launcher',
          'systems.launcherNote': 'เลือกงานที่ต้องการเริ่มใช้งาน',
          'systems.peopleName': 'People',
          'systems.peopleDesc': 'ข้อมูลผู้ใช้งานและข้อมูลพนักงาน',
          'systems.documentsName': 'Documents',
          'systems.documentsDesc': 'นโยบายบริษัทและเอกสารภายใน',
          'systems.okrName': 'OKR',
          'systems.okrDesc': 'ติดตามเป้าหมายและผลลัพธ์ของทีม',
          'systems.requestsName': 'Requests',
          'systems.requestsDesc': 'ส่งและติดตามคำขอภายในองค์กร',
          'systems.noSearchResults': 'ไม่พบระบบที่ตรงกับคำค้นหา',
          'systems.footerVersion': 'Intranet v2.6.0',
          'systems.detailView': 'ดูรายละเอียด',
          'systems.detailClose': 'ปิดรายละเอียด',
          'systems.calendarTitle': 'ปฏิทิน',
          'systems.weatherTitle': 'ภูมิอากาศ',
          'systems.weatherLoading': 'กำลังโหลด',
          'systems.weatherHourlyTitle': 'สภาพอากาศรายชั่วโมง',
          'systems.weatherHourlyEmpty': 'ยังไม่มีข้อมูลรายชั่วโมง',
          'systems.weatherFeels': 'รู้สึกเหมือน',
          'systems.weatherHumidity': 'ความชื้น',
          'systems.weatherWind': 'ลม',
          'systems.contactTitle': 'HR / IT Contact',
          'systems.hrSupport': 'HR Support',
          'systems.itSupport': 'IT Support',
          'systems.contactButton': 'ติดต่อ',
          'admin.kicker': 'ผู้ดูแลระบบ',
          'admin.title': 'ภาพรวมระบบ',
          'admin.note': 'สรุปข้อมูล Employee Master และสถานะการซิงค์ (เฉพาะผู้ดูแลระบบ)',
          'admin.active': 'พนักงานที่ทำงานอยู่',
          'admin.companies': 'บริษัท',
          'admin.resigned': 'พนักงานที่ลาออกทั้งหมด',
          'admin.byCompany': 'พนักงานแยกตามบริษัทและแผนก',
          'admin.active_short': 'ทำงาน',
          'admin.resigned_short': 'ลาออก',
          'admin.resignedAllShort': 'ลาออกทั้งหมด',
          'admin.pendingResignShort': 'รอปิดงวด',
          'admin.payrollClosedShort': 'ปิดงวดแล้ว',
          'admin.resignedAllTitle': 'พนักงานลาออก (รวม)',
          'admin.pendingResignTitle': 'พนักงานลาออก (รอปิดงวด)',
          'admin.payrollClosedTitle': 'พนักงานลาออก (ปิดงวด)',
          'admin.resignedKicker': 'พนักงานลาออก',
          'admin.resignedTitle': 'พนักงานที่ลาออก',
          'admin.resignedOn': 'ลาออก',
          'admin.noResigned': 'ไม่มีพนักงานลาออก',
          'admin.resignedStatusTabs': 'สถานะพนักงานลาออก',
          'admin.employeeSearch': 'ค้นหาพนักงาน',
          'admin.employeeSearchPlaceholder': 'รหัสพนักงาน หรือชื่อ-สกุล',
          'admin.allDepartments': 'ทุกแผนก',
          'admin.allPositions': 'ทุกตำแหน่ง',
          'admin.searchButton': 'ค้นหา',
          'admin.clearButton': 'ล้าง',
          'admin.resultsFound': 'พบ {count} คน',
          'admin.noMatchingEmployees': 'ไม่พบพนักงานที่ตรงกับเงื่อนไข',
          'admin.activeEmployeeSearch': 'ค้นหาพนักงานที่ทำงานอยู่',
          'admin.activeEmployees': 'พนักงานที่ทำงานอยู่',
          'admin.activeSearchRequired': 'กรุณาระบุรหัส ชื่อ แผนก หรือตำแหน่ง',
          'admin.searchResults': 'ผลการค้นหา',
          'admin.searchResultsCount': 'ผลการค้นหา ({count} คน)',
          'admin.searchResultsLimited': '· แสดง 300 คนแรก',
          'admin.searchFailed': 'ค้นหาไม่สำเร็จ กรุณาลองใหม่',
          'admin.total_short': 'รวม',
          'admin.people': 'คน',
          'admin.depts': 'แผนก',
          'admin.noEmp': 'ยังไม่มีพนักงาน',
          'admin.loading': 'กำลังโหลด...',
          'admin.empty': 'ไม่มีข้อมูล',
          'admin.close': 'ปิด',
          'admin.employeeDetailKicker': 'ข้อมูลจาก B Plus',
          'admin.employeeDetailTitle': 'ข้อมูลพนักงานและสิทธิ์ลา',
          'admin.hiredOn': 'เริ่มงาน',
          'admin.leaveUnavailable': 'ไม่สามารถอ่านสิทธิ์ลาจาก B Plus ได้',
          'admin.leaveRightsYear': 'สิทธิ์การลา ปี {year}',
          'admin.leaveAsOf': 'ข้อมูล ณ {date}',
          'admin.noLeaveRights': 'ไม่พบข้อมูลสิทธิ์ลา',
          'admin.leaveType': 'ประเภทการลา',
          'admin.leaveEntitled': 'สิทธิ์',
          'admin.leaveUsed': 'ใช้แล้ว',
          'admin.leaveRemaining': 'คงเหลือ',
          'admin.leaveUnitNote': 'ตัวเลขแสดงตามหน่วยสิทธิ์ที่กำหนดใน B Plus และรวมรายการที่อยู่ระหว่างประมวลผลแล้ว',
          'admin.loadingLeave': 'กำลังอ่านสิทธิ์ลาจาก B Plus...',
          'info.kicker': 'โปรไฟล์',
          'info.title': 'ข้อมูลผู้ใช้งาน',
          'field.company': 'บริษัท',
          'field.title': 'คำนำหน้า',
          'field.name': 'ชื่อ-สกุล',
          'field.department': 'แผนก',
          'field.position': 'ตำแหน่ง',
          'field.status': 'สถานะการทำงาน',
          'field.hire': 'วันเริ่มงาน',
          'pwd.kicker': 'ความปลอดภัย',
          'pwd.title': 'รหัสผ่าน',
          'pwd.idcard': 'เลขบัตรประชาชน',
          'pwd.idcard_ph': 'กรอกเลขบัตรประชาชนเพื่อยืนยันตัวตน',
          'pwd.new': 'รหัสผ่านใหม่',
          'pwd.new_ph': 'อย่างน้อย 6 ตัวอักษร',
          'pwd.confirm': 'ยืนยันรหัสผ่านใหม่',
          'pwd.confirm_ph': 'กรอกรหัสผ่านใหม่อีกครั้ง',
          'pwd.submit': 'บันทึกรหัสผ่าน',
          'pwd.hint': 'กรอกเลขบัตรประชาชนให้ถูกต้องก่อน จึงจะตั้งรหัสผ่านใหม่ได้',
          'profile.upload': 'อัปโหลดรูป',
          'profile.boardAria': 'ข้อมูลผู้ใช้งาน',
          'profile.employeeSource': 'ข้อมูลพนักงานจากระบบกลาง',
          'profile.employeeCode': 'รหัสพนักงาน',
          'profile.active': 'กำลังทำงาน',
          'profile.resigned': 'ลาออกแล้ว',
          'profile.noEmail': 'ยังไม่ได้กรอกอีเมล',
          'profile.manageEmail': 'จัดการอีเมล',
          'profile.emailHelp': 'ใช้สำหรับการแจ้งเตือนภายในระบบ',
          'profile.manageSignature': 'จัดการลายเซ็น',
          'profile.signatureHelp': 'เซ็นด้วยเมาส์หรือนิ้ว หรือแนบรูปลายเซ็นที่มีอยู่แล้ว แล้วบันทึกไว้ใช้กับเอกสาร',
          'profile.noSignature': 'ยังไม่มีลายเซ็น',
          'profile.addSignature': 'เพิ่มลายเซ็น',
          'profile.noEditAccess': 'ยังไม่มีสิทธิ์แก้ไข',
          'profile.signatureMethod': 'วิธีเพิ่มลายเซ็น',
          'common.close': 'ปิด',
          'common.success': 'สำเร็จ',
          'common.ok': 'ตกลง',
          'sig.title': 'ลายเซ็น',
          'sig.saved': 'บันทึกลายเซ็นแล้ว',
          'sig.hint': 'เซ็นที่นี่ (เมาส์หรือนิ้ว)',
          'sig.pen': 'ขนาดปากกา',
          'sig.save': 'บันทึกลายเซ็น',
          'sig.savedOk': 'บันทึกลายเซ็นแล้ว',
          'sig.error': 'บันทึกไม่สำเร็จ ลองอีกครั้ง',
          'sig.undo': 'ย้อนกลับ',
          'sig.redo': 'ทำซ้ำ',
          'sig.clear': 'ลบทั้งหมด',
          'sig.modeDraw': 'เซ็นเอง',
          'sig.modeUpload': 'แนบไฟล์ภาพ',
          'sig.dropTitle': 'ลากรูปมาวาง หรือกดเพื่อเลือกไฟล์',
          'sig.dropHint': 'รองรับ PNG · JPG · HEIC (ไอโฟน) — จะเป็นรูปถ่ายลายเซ็นบนกระดาษ หรือภาพที่เซ็นด้วยปากกาอิเล็กทรอนิกส์ก็ได้ ระบบจะลบพื้นหลังและตัดเอาเฉพาะลายเซ็นให้อัตโนมัติ',
          'sig.pick': 'เลือกรูปอื่น',
          'sig.level': 'ความเข้ม',
          'sig.inkBlack': 'หมึกสีดำ',
          'sig.processing': 'กำลังประมวลผลรูป…',
          'sig.tooBig': 'ไฟล์ใหญ่เกินไป (เกิน 20 MB)',
          'sig.badFile': 'ไฟล์นี้ไม่ใช่รูปภาพ',
          'sig.readFail': 'เปิดไฟล์รูปนี้ไม่ได้ ลองไฟล์อื่น',
          'sig.noInk': 'หาลายเซ็นในรูปไม่เจอ ลองเลื่อน "ความเข้ม" เพิ่มขึ้น',
          'sig.heicFail': 'เบราว์เซอร์นี้เปิดไฟล์ HEIC ของ iPhone ไม่ได้ — ให้เลือกรูปจากแอปรูปภาพบน iPhone (ระบบจะแปลงเป็น JPG ให้เอง) หรือตั้งค่า > กล้อง > รูปแบบ > เข้ากันได้มากที่สุด',
          'field.email': 'อีเมล',
          'email.ph': 'กรอกอีเมล',
          'email.save': 'บันทึก',
          'email.saved': 'บันทึกอีเมลแล้ว',
          'email.invalid': 'รูปแบบอีเมลไม่ถูกต้อง',
          'email.error': 'บันทึกไม่สำเร็จ ลองอีกครั้ง',
          'profile.view_photo': 'ดูรูปโปรไฟล์ขนาดใหญ่',
          'profile.close_photo': 'ปิดรูปภาพ',
          'profile.zoom_group': 'ปรับขนาดรูป',
          'profile.zoom_in': 'ซูมเข้า',
          'profile.zoom_out': 'ซูมออก',
          'profile.zoom_reset': 'รีเซ็ตขนาด 100%',
          'pwd.change': 'เปลี่ยนรหัสผ่าน',
          'pwd.verify': 'ตรวจสอบ',
          'pwd.verified': 'รหัสบัตรประชาชนถูกต้อง',
          'pwd.idcard_wrong': 'รหัสบัตรประชาชนไม่ถูกต้อง',
          'pwd.masked': 'รหัสผ่านถูกซ่อนไว้',
          'pwd.show': 'แสดง',
          'pwd.hide': 'ซ่อน',
          'pwd.cancel': 'ยกเลิก',
          'recruit.kicker': 'การสรรหา',
          'recruit.title': 'สรรหาบุคลากร',
          'recruit.note': 'ระบบสรรหาบุคลากรกำลังอยู่ระหว่างการพัฒนา',
          'assessment.kicker': 'การประเมิน',
          'assessment.title': 'ระบบประเมินพนักงาน',
          'assessment.pending': 'รอการอัปเดต',
          'assessment.clearEvaluators': 'ล้างผู้ประเมิน',
          'assessment.clearEvaluatorsConfirm': 'ล้างผู้ประเมินของพนักงานทุกคน?\nคะแนนและระดับจะไม่ถูกลบ เฉพาะช่องผู้ประเมินเท่านั้น',
          'assessment.clearingEvaluators': 'กำลังล้าง...',
          'assessment.clearEvaluatorsError': 'ล้างผู้ประเมินไม่สำเร็จ',
          'assessment.clearEvaluatorsNetwork': 'เชื่อมต่อไม่สำเร็จ',
          'assessment.note': 'ระบบ SUPAVUT Assessment กำลังอยู่ระหว่างการพัฒนา',
          'assessment.home.kicker': 'ประกาศสำหรับผู้ดูแลระบบ',
          'assessment.home.titleAdmin': 'สวัสดีแอดมิน',
          'assessment.home.titleHr': 'สวัสดีทีม HR',
          'assessment.home.subtitle': 'พื้นที่ประกาศและจุดเริ่มงานของ SUPAVUT Assessment สำหรับผู้ดูแลระบบและ HR ที่ได้รับสิทธิ์จัดการคะแนน',
          'assessment.home.primaryScores': 'เข้าสู่การจัดการคะแนน',
          'assessment.home.primaryOpenRound': 'เปิดรอบประเมิน',
          'assessment.home.rounds': 'เปิดรอบ',
          'assessment.home.settings': 'ตั้งค่าสิทธิ์',
          'assessment.home.currentUser': 'ผู้ใช้งานปัจจุบัน',
          'assessment.home.role': 'บทบาทในระบบ',
          'assessment.home.noticeKicker': 'Admin Notice',
          'assessment.home.noticeTitle': 'ประกาศระบบ Assessment',
          'assessment.home.updated': 'อัปเดต',
          'assessment.home.notice1Title': 'เปิดรอบก่อนเริ่มจัดการคะแนน',
          'assessment.home.notice1Body': 'ต้องมีรอบที่เปิดอยู่ก่อนนำเข้าคะแนน แก้ลำดับผู้ประเมิน หรือกำหนดระดับตำแหน่ง เพื่อให้ข้อมูลแยกตามรอบชัดเจน',
          'assessment.home.notice2Title': 'สิทธิ์ HR ใช้สำหรับจัดการคะแนน',
          'assessment.home.notice2Body': 'พนักงานที่ถูกเพิ่มในสิทธิ์ผู้ใช้งาน HR จะเข้า Assessment และจัดการคะแนนได้ ส่วนการมอบสิทธิ์และตั้งค่าระบบยังเป็นหน้าของ admin',
          'assessment.home.notice3Title': 'ใช้เฉพาะข้อมูล Employee Master ที่อนุมัติ',
          'assessment.home.notice3Body': 'Assessment อ่านข้อมูลพนักงานจาก Insight เท่านั้น และไม่ดึงข้อมูลเงินเดือนหรือข้อมูล payroll เข้ามาในโมดูลนี้',
          'assessment.home.workflowKicker': 'Workflow',
          'assessment.home.workflowTitle': 'ลำดับงานแนะนำ',
          'assessment.home.flow1Title': 'เปิดรอบประเมิน',
          'assessment.home.flow1Body': 'กำหนดชื่อรอบและปี ก่อนเริ่มกรอกข้อมูลของรอบใหม่',
          'assessment.home.flow2Title': 'จัดการข้อมูลพนักงานและคะแนน',
          'assessment.home.flow2Body': 'นำเข้าไฟล์ แก้ลำดับผู้ประเมิน ตั้งคอลัมน์คะแนน และตรวจผลคำนวณ',
          'assessment.home.flow3Title': 'กำหนดคำถาม',
          'assessment.home.flow3Body': 'เตรียมคำถามตามคอลัมน์ Input และลำดับผู้ประเมินที่กำหนดไว้',
          'assessment.home.flow4Title': 'ตรวจผลลัพธ์',
          'assessment.home.flow4Body': 'ดูผลรายรอบ ย้อนหลังตามปี และดาวน์โหลดไฟล์สรุปเมื่อพร้อมใช้งาน',
          'assessment.home.statusKicker': 'Status',
          'assessment.home.statusTitle': 'สถานะปัจจุบัน',
          'assessment.home.statusOpenRound': 'รอบเปิดอยู่',
          'assessment.home.noOpenRound': 'ยังไม่เปิดรอบ',
          'assessment.home.statusLatestRound': 'รอบล่าสุด',
          'assessment.home.statusHr': 'HR ที่ได้รับสิทธิ์',
          'assessment.home.statusBoxes': 'หัวข้อคะแนนหลัก',
          'assessment.home.statusEmployees': 'พนักงาน active',
          'assessment.home.scopeKicker': 'Permission',
          'assessment.home.scopeTitle': 'ขอบเขตสิทธิ์',
          'assessment.home.scopeAdmin': 'เข้าได้ทุกหน้า รวมถึงการมอบสิทธิ์ HR และการตั้งค่าตำแหน่ง',
          'assessment.home.scopeHr': 'จัดการรอบ คะแนน คำถาม และผลลัพธ์ได้ แต่ไม่เห็นหน้าตั้งค่าระบบ',
          'assessment.evaluate.title': 'ประเมินพนักงาน',
          'assessment.evaluate.formTitle': 'แบบประเมินพนักงาน',
          'assessment.evaluate.pageTitle': 'รายการประเมินพนักงาน',
          'assessment.evaluate.help': 'เลือกพนักงานจากรายการ แล้วกดประเมินหรือประเมินต่อในรอบที่เปิดอยู่',
          'assessment.evaluate.backToList': 'กลับรายการประเมิน',
          'assessment.evaluate.role.directSupervisor': 'หัวหน้างานโดยตรง',
          'assessment.evaluate.role.division': 'ผู้ประเมินระดับฝ่าย',
          'assessment.evaluate.role.directAndDivision': 'หัวหน้างานโดยตรง / ผู้ประเมินระดับฝ่าย',
          'assessment.evaluate.itemUnit': 'รายการ',
          'assessment.evaluate.employee': 'พนักงานที่ประเมิน',
          'assessment.evaluate.position': 'ตำแหน่ง',
          'assessment.evaluate.department': 'แผนก',
          'assessment.evaluate.evaluator': 'ผู้ประเมิน',
          'assessment.form.fullName': 'ชื่อ-สกุล :',
          'assessment.form.employeeCode': 'รหัสพนักงาน :',
          'assessment.form.totalScore': 'คะแนนรวม :',
          'assessment.form.position': 'ตำแหน่ง :',
          'assessment.form.department': 'แผนก :',
          'assessment.employeeCode': 'รหัสพนักงาน',
          'assessment.fullName': 'ชื่อ-สกุล',
          'assessment.levelWord': 'ลำดับ',
          'assessment.openRound': 'รอบที่เปิด:',
          'assessment.review.title': 'รายการตรวจสอบผลลัพธ์',
          'assessment.review.help': 'แสดงเฉพาะพนักงานที่คุณถูกกำหนดเป็นผู้ตรวจสอบลำดับ 3 หรือ 4 ในรอบที่เปิดอยู่',
          'assessment.review.yourLevel': 'ลำดับที่คุณตรวจสอบ',
          'assessment.review.tableLabel': 'ตารางตรวจสอบผลลัพธ์',
          'assessment.review.levelOneTotal': 'คะแนนประเมิน',
          'assessment.review.selfScore': 'คะแนนตนเอง',
          'assessment.review.noScore': 'ยังไม่มีคะแนน',
          'assessment.codeShort': 'รหัส',
          'assessment.levelShort': 'ระดับ',
          'assessment.filterWord': 'กรอง',
          'assessment.hierLevel': 'ลำดับชั้น',
          'assessment.downloadExcel': '⬇ ดาวน์โหลด Excel',
          'assessment.import': '⬆ นำเข้า',
          'assessment.clearFilter': 'ล้างตัวกรอง',
          'assessment.showingThisPage': 'แสดงหน้านี้',
          'assessment.totalWord': 'ทั้งหมด',
          'assessment.peopleUnit': 'คน',
          'assessment.noEmployee': 'ไม่พบพนักงาน',
          'assessment.legendPastel': 'พาสเทล = กลุ่มข้อมูล',
          'assessment.legendDark': 'เข้มขึ้น = ระดับนี้ไม่คิด',
          'assessment.scores.step1Sub': 'ตั้งระดับตำแหน่ง · แก้คะแนน + ลำดับชั้นในตาราง · ดาวน์โหลด/นำเข้า Excel',
          'assessment.scores.h11': '1.1 ระดับตำแหน่ง',
          'assessment.scores.h11Hint': 'จัดตำแหน่งลงระดับ · ระดับ 0 = ไม่ถูกประเมิน · ปุ่ม + เพิ่มระดับ, ✕ ลบ (เลขเรียงใหม่ให้เอง)',
          'assessment.scores.unleveled': 'ยังไม่จัดระดับ',
          'assessment.scores.h12': '1.2 ข้อมูลพนักงาน',
          'assessment.scores.h12Hint': 'แก้ผู้ประเมิน (ลำดับชั้น 1–4) ในช่องได้เลย · หรือดาวน์โหลดไปแก้แล้วนำเข้ากลับ',
          'assessment.scores.step2': 'จัดการคอลัมน์',
          'assessment.scores.step2Sub': 'คอลัมน์เก็บคะแนน KPI — สร้างเอง หรือ import ไฟล์คอลัมน์',
          'assessment.scores.h21': '2.1 สร้าง / นำเข้าคอลัมน์',
          'assessment.scores.h21Hint': 'สร้างเอง หรือนำเข้าไฟล์ (แถว 1 = หัวข้อหลัก, แถว 2 = คอลัมน์รอง)',
          'assessment.scores.importColumns': '⬆ นำเข้าไฟล์คอลัมน์',
          'assessment.scores.mainColPh': 'ชื่อหัวข้อหลัก เช่น Attendance',
          'assessment.scores.addMainCol': '+ เพิ่มหัวข้อหลัก',
          'assessment.scores.h22': '2.2 ตารางตรวจคอลัมน์ (มุมมอง Excel)',
          'assessment.scores.h22Hint': 'อัปเดตตามการ์ดด้านบนทันที',
          'assessment.scores.step3': 'สัดส่วนและการคำนวณ',
          'assessment.scores.step3Sub': 'กำหนดสัดส่วนต่อระดับ 1–5 — สัดส่วนรวม 100 ต่อระดับ · เพิ่มเติม (+/-)',
          'assessment.scores.h31': '3.1 กำหนดสัดส่วนของระดับ',
          'assessment.scores.h31Hint': 'สัดส่วนรวม 100 ต่อระดับ · เพิ่มเติม = +/- แยก · ไม่คำนวณ = ไม่นำมาคิด',
          'assessment.scores.h32': '3.2 การคำนวณคอลัมน์รอง',
          'assessment.scores.h32Hint': 'คะแนนเต็ม = คิดตามสัดส่วน · Attendance = หักคะแนน/จำกัดเกรด · Input = ผู้ประเมินกรอกในระบบ · คะแนนเพิ่มเติม = +/- ตรง ๆ',
          'assessment.scores.step4': 'การนำเข้าคะแนน',
          'assessment.scores.step4Sub': 'นำเข้า / แก้คะแนนรายช่อง',
          'assessment.scores.h41': '4.1 นำเข้า / แก้ไขคะแนน',
          'assessment.scores.h41Hint': 'แก้คะแนนในช่องได้เมื่อผู้ประเมินครบ 4 ลำดับ · หลายผู้ประเมินคั่นด้วย , เช่น 8.5,7.5 · พิมพ์ N/A = ไม่นำช่องนั้นมาคำนวณ · ไฟล์นำเข้า: ช่องว่าง = คงค่าเดิม',
          'assessment.scores.importScores': '⬆ นำเข้าคะแนน',
          'assessment.scores.noColumns': 'ยังไม่มีคอลัมน์คะแนน — สร้าง/นำเข้าที่หัวข้อ 2 ก่อน',
          'assessment.questions.hint2': 'เขียนคำอธิบายที่จะไปแสดงในแบบฟอร์มของพนักงานระดับที่เลือก · ใส่ได้ทั้งหัวข้อหลักและคอลัมน์ย่อย · ปุ่มแสดง/ซ่อน คุมว่าจะให้ผู้ประเมินเห็นหรือไม่ · บันทึกอัตโนมัติ',
          'assessment.questions.previewFor': 'ตัวอย่างที่จะแสดงในแบบฟอร์มของพนักงาน',
          'assessment.questions.topicWord': 'หัวข้อ',
          'assessment.questions.noSubColumns': 'หัวข้อนี้ยังไม่มีคอลัมน์ย่อย',
          'assessment.questions.hint3': 'กดไอคอน ⓘ ข้างชื่อหัวข้อหรือคอลัมน์ เพื่อเขียนคำอธิบายและเปิด/ปิดให้ผู้ประเมินเห็น · เขียว = เปิดแสดง · แดง = มีข้อความแต่ปิดไว้ · เทา = ยังไม่มี',
          'assessment.questions.autosave': 'บันทึกอัตโนมัติ',
          'assessment.questions.noWeights': 'ระดับนี้ยังไม่ได้ตั้งสัดส่วนคะแนน — ไปกำหนดที่หน้า จัดการ หัวข้อ 3.1 ก่อน',
          'assessment.questions.demoName': '(ตัวอย่าง) นายสมชาย ใจดี',
          'assessment.questions.demoPosition': '(ตัวอย่าง) ตำแหน่งระดับ',
          'assessment.questions.scaleTitle': 'ตัวเลือกคะแนน',
          'assessment.questions.scaleHint': 'ผู้ประเมินจะเห็นตัวเลือกเหล่านี้เรียงจากบนลงล่าง แล้วเลือกคะแนนในตัวเลือกนั้น',
          'assessment.questions.scaleAdd': '+ เพิ่มตัวเลือก',
          'assessment.questions.scaleColName': 'ตัวเลือก',
          'assessment.questions.scaleColScores': 'คะแนน',
          'assessment.questions.scaleReset': 'คืนค่าเริ่มต้น',
          'assessment.questions.scaleSave': 'บันทึก',
          'assessment.evaluate.status': 'สถานะ',
          'assessment.evaluate.action': 'ประเมิน',
          'assessment.evaluate.status.done': 'ประเมินแล้ว',
          'assessment.evaluate.status.partial': 'ยังประเมินไม่ครบ',
          'assessment.evaluate.status.pending': 'รอประเมิน',
          'assessment.evaluate.button.edit': 'ดู / แก้ไข',
          'assessment.evaluate.button.continue': 'ประเมินต่อ',
          'assessment.evaluate.button.start': 'ประเมิน',
          'assessment.evaluate.viewPhoto': 'ดูรูป',
          'assessment.evaluate.zoomOpen': 'กดเพื่อขยายรูป',
          'assessment.sort.aria': 'เรียงลำดับ',
          'assessment.sort.code': 'เรียงตามรหัสพนักงาน',
          'assessment.sort.rankDesc': 'เรียงตามตำแหน่ง (สูง → ต่ำ)',
          'assessment.sort.rankAsc': 'เรียงตามตำแหน่ง (ต่ำ → สูง)',
          'assessment.evaluate.zoomHint': 'หมุนล้อเมาส์เพื่อซูม · ดับเบิลคลิกเพื่อขยาย/ย่อ',
          'assessment.evaluate.profileTitle': 'รูปโปรไฟล์พนักงาน',
          'assessment.evaluate.employeeCode': 'รหัสพนักงาน',
          'assessment.evaluate.fullName': 'ชื่อ-สกุล',
          'assessment.evaluate.placeholderTitle': 'พื้นที่แบบประเมิน',
          'assessment.evaluate.placeholderHelp': 'เตรียมไว้สำหรับผูกคำถามและช่องให้คะแนนของบทบาทนี้',
          'assessment.common.manage': 'จัดการ',
          'assessment.common.questions': 'กำหนดคำถาม',
          'assessment.common.results': 'ผลลัพธ์',
          'assessment.common.round': 'รอบ',
          'assessment.common.summary': 'ข้อมูลสรุปการประเมิน',
          'assessment.common.level': 'ระดับ',
          'assessment.common.employeeCount': 'จำนวนพนักงาน',
          'assessment.common.notAssessed': 'ไม่ประเมิน',
          'assessment.common.notCalculated': 'ไม่คำนวณ',
          'assessment.common.noOpenRound': 'ยังไม่เปิดรอบ',
          'assessment.common.perPage': 'ต่อหน้า',
          'assessment.common.clear': 'ล้าง',
          'assessment.common.apply': 'ตกลง',
          'assessment.common.selectAll': 'เลือกทั้งหมด',
          'assessment.common.loadingFilters': 'กำลังโหลดตัวกรอง',
          'assessment.common.ok': 'ตกลง',
          'assessment.common.success': 'สำเร็จ',
          'assessment.common.notice': 'แจ้งเตือน',
          'assessment.common.cancel': 'ยกเลิก',
          'assessment.common.delete': 'ลบ',
          'assessment.common.showing': 'แสดง',
          'assessment.common.people': 'คน',
          'assessment.common.afterFilter': 'หลังกรอง',
          'assessment.common.code': 'รหัส',
          'assessment.common.year': 'ปี',
          'assessment.common.roundName': 'ชื่อรอบ',
          'assessment.common.openedAt': 'เปิดเมื่อ',
          'assessment.common.by': 'โดย',
          'assessment.common.section': 'หัวข้อ',
          'assessment.common.page': 'หน้า',
          'assessment.form.level': 'ระดับ',
          'assessment.form.evaluate': 'ประเมิน',
          'assessment.form.notAssessed': 'ไม่ประเมิน',
          'assessment.form.selectedScore': 'คะแนนที่เลือก คือ {score} เต็ม 10 คะแนน',
          'assessment.form.noScoreSelected': 'ยังไม่ได้เลือกคะแนน',
          'assessment.form.networkError': 'เชื่อมต่อไม่สำเร็จ',
          'assessment.form.scoreRangeError': 'คะแนนต้องอยู่ระหว่าง 0–10',
          'assessment.form.saveError': 'บันทึกไม่สำเร็จ',
          'assessment.self.openRound': 'รอบที่เปิด',
          'assessment.self.year': 'ปี',
          'assessment.self.pendingForm': 'เปิดรอบ “ประเมินตัวเอง” แล้ว — ส่วนฟอร์มประเมินตัวเองยังไม่เปิดใช้งาน รอกำหนดรูปแบบ',
          'assessment.self.adminNav': 'ตั้งค่าการประเมินตัวเอง',
          'assessment.self.selectedCount': 'ผู้เข้าร่วม',
          'assessment.self.manageTab': 'จัดการ',
          'assessment.self.questionsTab': 'กำหนดคำถาม',
          'assessment.self.resultsTab': 'ผลลัพธ์',
          'assessment.self.h11': '1.1 ระดับตำแหน่ง',
          'assessment.self.h11Hint': 'กำหนดระดับ 1–5 ตามตำแหน่งของพนักงานสำหรับรอบประเมินตัวเอง',
          'assessment.self.unassigned': 'ยังไม่กำหนดระดับ',
          'assessment.self.addLevel': 'เพิ่มระดับ',
          'assessment.self.questionsHint2': 'คำถามที่ตั้งไว้จะไปแสดงในแบบฟอร์มของพนักงานระดับนี้',
          'assessment.self.questionPlaceholder': 'ข้อความคำถาม',
          'assessment.self.choicePlaceholder': 'ตัวเลือก',
          'assessment.self.choiceNo': 'ตัวเลือกที่',
          'assessment.self.naNote': 'ไม่นำมาคำนวณคะแนน',
          'assessment.self.needChoice': 'แต่ละข้อต้องมีข้อความคำถามและอย่างน้อย 1 ตัวเลือก',
          'assessment.self.deleteLevelConfirm': 'ลบระดับนี้? คำถามของระดับนี้จะถูกลบ และระดับที่สูงกว่าจะเลื่อนลงมาแทน',
          'assessment.self.deleteLevelBusy': 'ระดับนี้มีตำแหน่งอยู่ {count} รายการ · ลบแล้วตำแหน่งจะกลับไปยังไม่กำหนดระดับ และคำถามของระดับนี้จะถูกลบ ยืนยันหรือไม่',
          'assessment.self.deleteLevelError': 'ลบระดับไม่สำเร็จ',
          'assessment.self.levelLabel': 'ระดับ',
          'assessment.self.h12': '1.2 ข้อมูลพนักงาน',
          'assessment.self.h12Hint': 'เลือกพนักงานที่ต้องประเมินตัวเอง ข้อมูลดึงจาก Employee master ปัจจุบัน',
          'assessment.self.selectAllVisible': 'เลือกทั้งหมดที่แสดง',
          'assessment.self.clearAllVisible': 'ไม่เลือกทั้งหมด',
          'assessment.self.saveSelection': 'บันทึกรายชื่อ',
          'assessment.self.participantColumn': 'ประเมินตัวเอง',
          'assessment.self.selected': 'เลือก',
          'assessment.self.notSelected': 'ไม่เลือก',
          'assessment.self.unsavedChanges': 'มีรายการที่ยังไม่บันทึก {count} รายการ',
          'assessment.self.saving': 'กำลังบันทึก...',
          'assessment.self.saved': 'บันทึกรายชื่อแล้ว',
          'assessment.self.saveFailed': 'บันทึกรายชื่อไม่สำเร็จ',
          'assessment.self.questionsTitle': 'กำหนดคำถามตามระดับ',
          'assessment.self.questionsHint': 'สร้างคำถาม TH / EN / MY กำหนดคะแนนเต็ม แล้วเพิ่มตัวเลือกทีละรายการตามระดับ',
          'assessment.self.structureOnly': 'โครงสร้างหน้าจอ',
          'assessment.self.questionColumn': 'คำถาม',
          'assessment.self.answerTypeColumn': 'รูปแบบคำตอบ',
          'assessment.self.statusColumn': 'สถานะ',
          'assessment.self.questionsPending': 'ยังไม่มีคำถามสำหรับระดับนี้',
          'assessment.self.addQuestion': 'เพิ่มคำถาม',
          'assessment.self.defaultChoicesHint': 'หลังบันทึกคำถาม จึงจะสามารถเพิ่มตัวเลือกคำตอบได้',
          'assessment.self.saveQuestion': 'บันทึกคำถาม',
          'assessment.self.questionShort': 'ข้อที่',
          'assessment.self.questionDetailHint': 'กรอกรายละเอียดคำถามให้ครบ 3 ภาษา และระบุคะแนนเต็มที่ใช้คำนวณ',
          'assessment.self.questionFullScore': 'คะแนนเต็มของคำถาม',
          'assessment.self.questionFullScoreHint': 'ใช้เป็นตัวหารของข้อนี้ เช่น เลือก 4 จากคะแนนเต็ม 5 เท่ากับ 80%',
          'assessment.self.saveQuestionHint': 'หลังบันทึกคำถาม จึงจะสามารถเพิ่มตัวเลือกคำตอบได้',
          'assessment.self.choicesCount': 'ตัวเลือก',
          'assessment.self.notReady': 'ยังไม่พร้อมใช้งาน',
          'assessment.self.deleteQuestion': 'ลบคำถาม',
          'assessment.self.saveChanges': 'บันทึกการแก้ไข',
          'assessment.self.choicesTitle': 'ตัวเลือกคำตอบ',
          'assessment.self.choiceScoreHint': 'ตัวเลือกแบบคะแนนต้องมีค่าไม่เกินคะแนนเต็มของคำถาม',
          'assessment.self.optionShort': 'ตัวเลือก',
          'assessment.self.choiceType': 'ชนิดตัวเลือก',
          'assessment.self.choiceTypeScore': 'คะแนนแบบพิมพ์',
          'assessment.self.choiceTypeHint': 'เลือกชนิดตัวเลือก แล้วกรอกข้อความ TH / EN / MY',
          'assessment.self.choiceScore': 'คะแนน',
          'assessment.self.saveChoice': 'บันทึกตัวเลือก',
          'assessment.self.deleteChoice': 'ลบตัวเลือก',
          'assessment.self.addChoice': 'เพิ่มตัวเลือก',
          'assessment.self.noChoices': 'ยังไม่มีตัวเลือก คำถามนี้จะยังไม่แสดงในแบบประเมินของพนักงาน',
          'assessment.self.noQuestions': 'ระดับนี้ยังไม่มีคำถาม',
          'assessment.self.noQuestionsHint': 'กด “เพิ่มคำถาม” เพื่อเริ่มสร้างแบบประเมิน',
          'assessment.self.deleteConfirm': 'ยืนยันการลบรายการนี้?',
          'assessment.self.resultsTitle': 'ผลลัพธ์การประเมินตัวเอง',
          'assessment.self.resultsHint': 'แสดงคะแนนรวมและเปอร์เซ็นต์รายข้อของพนักงานที่ Admin เลือกไว้',
          'assessment.self.exportTitle': 'Export ผลลัพธ์การประเมินตัวเอง',
          'assessment.self.exportHint': 'ไฟล์ Excel ผลลัพธ์ของรอบที่เลือก',
          'assessment.self.exportButton': '⬇ ดาวน์โหลด',
          'assessment.self.roundPicker': 'เลือกรอบที่จะดาวน์โหลด · ● = รอบที่เปิดอยู่',
          'assessment.self.roundOpenMark': '● = รอบที่เปิดอยู่',
          'assessment.self.scoreColumn': 'คะแนนประเมินตัวเอง',
          'assessment.self.noParticipants': 'ไม่พบพนักงาน',
          'assessment.self.userReadyTitle': 'คุณมีสิทธิ์ประเมินตัวเองในรอบนี้',
          'assessment.self.userPendingTitle': 'รายชื่อพร้อมแล้ว',
          'assessment.self.userPendingText': 'Admin เลือกรายชื่อของคุณแล้ว แบบคำถามและการบันทึกคำตอบจะเปิดใช้งานในขั้นถัดไป',
          'assessment.self.formTitle': 'ประเมินตัวเอง',
          'assessment.self.lastSaved': 'บันทึกล่าสุด',
          'assessment.self.formInstructionTitle': 'วิธีประเมิน',
          'assessment.self.formInstruction': 'เลือกคำตอบให้ครบทุกข้อ ระบบจะเทียบคะแนนที่เลือกกับคะแนนเต็มที่ Admin กำหนดให้แต่ละข้อ และไม่นำ N/A มาคำนวณ',
          'assessment.self.levelMissing': 'Admin ยังไม่ได้กำหนดระดับตำแหน่งของคุณ',
          'assessment.self.noFormQuestions': 'ระดับของคุณยังไม่มีคำถามสำหรับประเมินตัวเอง',
          'assessment.self.answered': 'ตอบแล้ว',
          'assessment.self.totalPercent': 'คะแนนรวม',
          'assessment.self.submitForm': 'บันทึกการประเมินตัวเอง',
          'assessment.questions.evaluatorLevel': 'ผู้ประเมินลำดับ',
          'assessment.questions.deleteTitle': 'ลบคำถาม',
          'assessment.results.year': 'ปี',
          'assessment.results.importHint': 'Template ของรอบที่เปิด กรอกนอกระบบแล้วนำเข้ากลับ',
          'assessment.results.importButton': '⬆ นำเข้า',
          'assessment.results.exportTitle': 'Export ผลลัพธ์',
          'assessment.results.downloadButton': '⬇ ดาวน์โหลด',
          'assessment.results.editableHint': 'กรอกคะแนนได้ตามกติกาของแต่ละช่อง · N/A = ไม่คิดช่องนั้น',
          'assessment.results.readonlyHint': 'รอบปิดแล้ว — ดูอย่างเดียว',
          'assessment.scores.addLevel': '＋ เพิ่มระดับ',
          'assessment.scores.deleteMainTitle': 'ลบหัวข้อหลักและคอลัมน์รองภายใน',
          'assessment.scores.deleteSubTitle': 'ลบคอลัมน์รอง',
          'assessment.scores.editMainTitle': 'แก้ชื่อหัวข้อหลัก',
          'assessment.scores.editSubTitle': 'แก้ชื่อคอลัมน์รอง',
          'assessment.scores.addSubPlaceholder': '+ เพิ่มคอลัมน์รอง...',
          'assessment.form.performanceTitle': 'แบบประเมินผลการปฏิบัติงานพนักงาน',
          'assessment.form.scoreWeightTitle': 'หัวข้อสัดส่วนคะแนน',
          'assessment.form.noScoreWeights': 'ยังไม่มีหัวข้อสัดส่วน',
          'assessment.form.performanceSection': 'ประเมินผลการปฏิบัติงาน',
          'assessment.form.noScoreColumns': 'ยังไม่มีคอลัมน์คะแนน',
          'assessment.home.noOpenRoundTitle': 'ยังไม่มีรอบประเมินที่เปิดอยู่',
          'assessment.home.noOpenRoundBody': 'เมื่อ HR เปิดรอบประเมิน ระบบจะแสดงผู้ประเมินลำดับชั้น 1 และ 2 ของคุณในหน้านี้',
          'assessment.home.noEvaluatorsTitle': 'ยังไม่มีผู้ประเมิน',
          'assessment.home.noEvaluatorsBody': 'ยังไม่มีรหัสพนักงานและชื่อ-สกุลในลำดับชั้น 1 หรือ 2 สำหรับข้อมูลของคุณในรอบนี้',
          'assessment.home.me': 'เรา',
          'assessment.rounds.title': 'เปิดรอบ',
          'assessment.rounds.employeeTab': 'ประเมินพนักงาน',
          'assessment.rounds.selfTab': 'ประเมินตัวเอง',
          'assessment.rounds.selfHint': 'เปิดรอบเพื่อให้พนักงานเข้าประเมินตัวเองได้ · เปิดรอบใหม่ = ปิดรอบเดิมอัตโนมัติ',
          'assessment.rounds.employeeHint': 'ต้องมีรอบประเมินพนักงานเปิดก่อนจึงจัดการการประเมินได้ · เปิดรอบใหม่ = ปิดรอบเดิม และคะแนน ผู้ประเมิน และระดับจะเริ่มชุดใหม่ (รอบเดิมดูย้อนหลังได้)',
          'assessment.rounds.listHint': 'กดเปิดรอบเพื่อสลับมาทำงานกับรอบและปีนั้น · กดลบเพื่อลบรอบพร้อมข้อมูลของรอบนั้น',
          'assessment.rounds.openState': '● เปิดอยู่',
          'assessment.rounds.closedState': 'ปิดแล้ว',
          'assessment.rounds.viewResults': 'ดูผลลัพธ์',
          'assessment.rounds.close': 'ปิดรอบ',
          'assessment.rounds.open': 'เปิดรอบ',
          'assessment.rounds.empty': 'ยังไม่มีรอบ — เปิดรอบแรกด้านบน',
          'assessment.rounds.deleteTitle': 'ลบรอบนี้?',
          'assessment.rounds.deleteBefore': 'จะลบรอบ',
          'assessment.rounds.deleteAfter': 'และข้อมูลทั้งหมดของรอบนี้ (คะแนน · ผู้ประเมิน · ระดับ) อย่างถาวร — กู้คืนไม่ได้',
          'assessment.rounds.deletePermanent': 'ลบถาวร',
          'assessment.rounds.openNewTitle': 'เปิดรอบใหม่',
          'assessment.rounds.allTitle': 'รอบทั้งหมด',
          'assessment.rounds.namePlaceholder': 'ชื่อรอบ เช่น รอบประเมินปีปัจจุบัน รอบ 1',
          'assessment.rounds.openNewButton': '+ เปิดรอบใหม่',
          'assessment.rounds.confirmOpenNew': 'เปิดรอบใหม่? รอบที่เปิดอยู่จะถูกปิดและเริ่มชุดข้อมูลใหม่',
          'assessment.rounds.confirmClose': 'ปิดรอบ “{name}”?',
          'assessment.rounds.confirmReopen': 'เปิดหรือสลับมาที่รอบ “{name}” (ปี {year})? รอบที่เปิดอยู่ตอนนี้จะถูกปิด',
          'assessment.questions.title': 'กำหนดคำถาม',
          'assessment.questions.hint': 'คำถามส่งถึงผู้ประเมินตามลำดับชั้นของคอลัมน์ชนิด Input · กรอกได้ 3 ภาษา (TH บังคับ · EN/MY เว้นได้) · บันทึกอัตโนมัติ',
          'assessment.questions.noInputColumns': 'ยังไม่มีคอลัมน์ชนิด Input — ตั้งชนิดข้อมูลที่หน้า จัดการ หัวข้อ 3.2 ก่อน',
          'assessment.questions.languageLabel': 'แสดงคำถามภาษา',
          'assessment.results.title': 'ผลลัพธ์',
          'assessment.results.importOverall': 'นำเข้าข้อมูลโดยรวม',
          'assessment.results.exportHint': 'ไฟล์ผลลัพธ์เต็มของรอบที่เลือก',
          'assessment.results.roundPicker': 'เลือกรอบที่จะดาวน์โหลด',
          'assessment.results.roundOpenMark': '● = รอบที่เปิดอยู่',
          'assessment.results.legendGroup': 'พาสเทล = กลุ่มข้อมูล',
          'assessment.results.legendGroupTitle': 'สีพาสเทลแยกกลุ่มข้อมูลตามหัวข้อหลัก',
          'assessment.results.legendUnavailable': 'เข้มขึ้น = ระดับนี้ไม่คิด',
          'assessment.results.legendUnavailableTitle': 'คอลัมน์นี้ไม่ถูกคำนวณสำหรับระดับของพนักงานคนนั้น',
          'assessment.results.clearFilters': 'ล้างตัวกรอง',
          'assessment.results.empty': 'ไม่พบข้อมูลพนักงานตามเงื่อนไข',
          'assessment.results.noColumns': 'ยังไม่มีคอลัมน์คะแนน — สร้างหรือนำเข้าที่หน้า จัดการ หัวข้อ 2 ก่อน',
          'assessment.settings.hrTitle': 'สิทธิ์ผู้ใช้งาน (HR)',
          'assessment.settings.hrHint': 'พนักงานที่ถูกเพิ่มจะเข้าระบบ Assessment และจัดการคะแนนได้ (ผู้ดูแลระบบเข้าได้อยู่แล้ว)',
          'assessment.settings.noMembers': 'ยังไม่มีสมาชิก',
          'assessment.settings.positionsTitle': 'ตำแหน่งที่เข้าใช้ระบบได้',
          'assessment.settings.positionsHint': 'เลือกตำแหน่งจากข้อมูลพนักงาน Bplus ที่อนุญาตให้เข้าระบบ · ถ้ายังไม่เคยบันทึก = อนุญาตทุกตำแหน่ง',
          'assessment.settings.selectAll': 'เลือกทั้งหมด',
          'assessment.settings.clear': 'ไม่เลือก',
          'assessment.settings.save': 'บันทึกตำแหน่ง',
          'assessment.settings.searchPlaceholder': 'ค้นหาพนักงาน (รหัส / ชื่อ) เพื่อเพิ่มเป็น HR',
          'assessment.settings.removeTitle': 'ลบ',
          'assessment.settings.saved': 'บันทึกแล้ว ✓',
          'assessment.settings.error': 'ผิดพลาด',
          'assessment.scores.levelTitle': 'ระดับ {level}',
          'assessment.scores.deleteLevelTitle': 'ลบระดับ {level}',
          'assessment.scores.deleteLevelConfirm': 'ลบระดับ {level}?',
          'assessment.scores.deleteLevelPropWarning': 'สัดส่วนของระดับนี้ในหัวข้อ 3.1 จะถูกลบด้วย',
          'assessment.scores.deleteLevelPositionsWarning': 'ตำแหน่งที่จัดไว้ {count} รายการจะย้ายกลับ “ยังไม่จัดระดับ”',
          'assessment.scores.deleteLevelShiftWarning': 'ระดับที่สูงกว่าจะเลื่อนลงมาแทน (เช่น ระดับ {from} → {to})',
          'assessment.scores.deleteLevelError': 'ลบระดับไม่สำเร็จ',
          'assessment.scores.saveLevelError': 'บันทึกระดับไม่สำเร็จ',
          'assessment.scores.saveError': 'บันทึกไม่สำเร็จ',
          'assessment.scores.deleteError': 'ลบไม่สำเร็จ',
          'assessment.scores.addError': 'เพิ่มไม่สำเร็จ',
          'assessment.scores.minEvaluatorError': 'ต้องมีผู้ประเมินอย่างน้อย 1 ลำดับ',
          'assessment.scores.evaluatorSlotsTitle': 'ช่องตามผู้ประเมินลำดับ {levels}',
          'assessment.scores.evaluatorTitle': 'ผู้ประเมินลำดับ {level}',
          'assessment.scores.deleteMainConfirm': 'ลบหัวข้อหลักนี้ทั้งการ์ด?\nคอลัมน์รองข้างในและคะแนนที่เคยบันทึกในคอลัมน์เหล่านี้จะถูกลบด้วย',
          'assessment.scores.deleteSubConfirm': 'ลบคอลัมน์รองนี้?\nคะแนนที่เคยบันทึกในคอลัมน์นี้จะถูกลบด้วย',
          'assessment.self.title': 'การประเมินตัวเอง',
          'assessment.filter.clear': 'ล้าง',
          'assessment.filter.apply': 'ตกลง',
          'assessment.filter.selectAll': 'เลือกทั้งหมด',
          'assessment.filter.loading': 'กำลังโหลดตัวกรอง',
          'assessment.filter.searchPlaceholder': 'ค้นหา...',
          'assessment.filter.noResults': 'ไม่พบ',
          'assessment.filter.emptyValue': '(ว่าง)',
          'assessment.filter.columnTitle': 'กรองคอลัมน์',
          'assessment.filter.loadFailed': 'โหลดตัวกรองไม่สำเร็จ',
          'assessment.scores.noColumnsPreview': 'ยังไม่มีคอลัมน์ — สร้างหรือนำเข้าที่ 2.1 ก่อน',
          'assessment.scores.typeScore': 'คะแนนเต็ม',
          'assessment.scores.typeBonus': 'คะแนนเพิ่มเติม',
          'assessment.scores.calcDeduct': 'หักคะแนน',
          'assessment.scores.calcGrade': 'เกรด',
          'assessment.scores.noSubColumns': 'ยังไม่มีคอลัมน์รอง — สร้างที่หัวข้อ 2 ก่อน',
          'assessment.scores.noMainColumns': 'ยังไม่มีหัวข้อหลัก — สร้างหรือนำเข้าที่หัวข้อ 2 ก่อน',
          'assessment.scores.noLevels': 'ยังไม่มีระดับ — กด + เพิ่มระดับ ในหัวข้อ 1.1 ก่อน (ระดับ 0 ไม่คำนวณ)',
          'assessment.scores.choose': 'เลือก',
          'assessment.scores.other': 'อื่นๆ...',
          'assessment.scores.modePercent': 'สัดส่วน',
          'assessment.scores.modeExtra': 'เพิ่มเติม (+/-)',
          'assessment.scores.modeNone': 'ไม่คำนวณ',
          'assessment.scores.levelLabel': 'ระดับ',
          'assessment.scores.totalLabel': 'รวม',
          'assessment.scores.mainHeadingUnit': 'หัวข้อหลัก',
          'assessment.scores.columnUnit': 'คอลัมน์',
          'assessment.scores.maximum': 'สูงสุด',
          'assessment.scores.fullPlaceholder': 'เต็ม 10',
          'assessment.scores.removeHierarchy': 'นำลำดับนี้ออก',
          'assessment.scores.hierarchyLabel': 'ลำดับ',
          'assessment.scores.customPlaceholder': 'กรอกเอง',
          'assessment.hierarchy.1': 'ลำดับชั้น 1',
          'assessment.hierarchy.2': 'ลำดับชั้น 2',
          'assessment.hierarchy.3': 'ลำดับชั้น 3',
          'assessment.hierarchy.4': 'ลำดับชั้น 4'
        },
        en: {
          'loader.switching': 'Switching system',
          'loader.enter': 'Entering ',
          'loader.loading': 'Loading',
          'loader.logout': 'Logging out',
          'toast.recruitDenied': 'You have not been granted access to Recruit System',
          'toast.assessmentDenied': 'You have not been granted access to Assessment',
          'toast.pageDenied': 'You do not have permission to open this page',
          'toast.close': 'Dismiss notification',
          'toast.ack': 'Got it',
          'welcome.greeting': 'Welcome',
          'nav.info': 'Information',
          'nav.recruit': 'Recruitment',
          'nav.assessment': 'Assessment',
          'nav.systems': 'All HR Systems',
          'nav.overview': 'Overview',
          'nav.a5sHome': 'Home',
          'nav.toggleMenu': 'Toggle menu',
          'nav.otHome': 'Home',
          'noti.aria': 'Notifications',
          'noti.title': 'Notifications',
          'noti.readAll': 'Mark all read',
          'noti.loading': 'Loading...',
          'noti.empty': 'No notifications yet',
          'noti.error': 'Could not load notifications',
          'nav.a5sMyWork': 'My Area Tasks',
          'nav.a5sManage': 'Manage Areas',
          'nav.a5sMembers': 'System Settings',
          'nav.a5sRounds': 'Monthly rounds',
          'nav.a5sDownloads': 'Download documents',
          'a5s.rounds.title': 'Monthly rounds',
          'a5s.downloads.title': 'Download documents',
          'nav.back': 'Back to SUPAVUT INSIGHT',
          'nav.admin': 'System Overview',
          'nav.settings': 'Settings',
          'nav.sysSettings': 'System settings',
          'nav.asmManage': 'Assessment Management',
          'nav.asmRounds': 'Rounds',
          'nav.asmScores': 'Employee assessment',
          'nav.asmEvaluate': 'Evaluate employees',
          'nav.asmReview': 'Review results',
          'nav.asmSelf': 'Self assessment',
          'nav.rcDepts': 'My Departments',
          'nav.rcRequest': 'Headcount Request',
          'nav.rcReview': 'Review Documents',
          'nav.rcInbox': 'Document Inbox',
          'nav.rcDownloads': 'Downloads',
          'nav.rcAll': 'All functions',
          'rc.wip': 'In development',
          'rc.stubNote': 'This section is under development and will be available soon.',
          'rq.intro': 'Online form based on SI-HR-010. Choose an approval route and attach files.',
          'rq.workflowTitle': 'Please fill in the information',
          'rq.workflowNote': 'Please fill in the information',
          'rq.workflowStepNote': 'Check requester details, choose an approval route, and select the Manager.',
          'rq.formStepTitle': 'Fill in the headcount request form',
          'rq.formStepNote': 'Complete the SI-HR-010 document form.',
          'rq.jdStepNote': 'Attach the JD for the position being recruited.',
          'rq.resignStepNote': 'Use for resignation replacement requests, or attach supporting documents.',
          'rq.requester': 'Requester',
          'rq.empCode': 'Employee ID',
          'rq.empName': 'Full name',
          'rq.empPosition': 'Position',
          'rq.empDept': 'Department',
          'rq.chooseRoute': 'Choose approval route',
          'rq.pickManager': 'Select Manager',
          'rq.routeEmpty': 'No selectable route yet. Check the approval route in system settings.',
          'rq.attachTitle': 'Attachments',
          'rq.attachNote': 'Attach the Job Description and resignation or other documents before submitting.',
          'rq.attachJd': 'Attach Job Description',
          'rq.attachJdNote': 'Supports PDF, Word, Excel or images',
          'rq.attachResign': 'Attach resignation letter',
          'rq.attachResignNote': 'For replacement requests, or attach other documents as needed',
          'rq.resultMock': 'Looks submitted, but nothing is saved to the database in this round.',
          'rq.reset': 'Clear form',
          'rq.submit': 'Submit request',
          'rq.confirmTitle': 'Confirm submission',
          'rq.confirmNote': 'Review the form and attachments before submitting. Once live, the request goes to the Manager on the chosen route.',
          'rq.cancel': 'Cancel',
          'rq.confirmSubmit': 'Confirm submit',
          'set.kicker': 'System settings',
          'set.title': 'Settings',
          'set.login.title': 'Login permission by position',
          'set.login.note': 'Tick the positions allowed to log in — unticked positions cannot log in (administrators can always log in).',
          'set.email.title': 'Email permission',
          'set.email.note': 'Tick the positions allowed to enter/save email on the profile page — unticked positions will not see the email field (administrators always can).',
          'set.sig.title': 'Signature permission',
          'set.sig.note': 'Tick the positions allowed to save a signature on the profile page — unticked positions will not see the signature pad (administrators always can).',
          'set.selectAll': 'Select all',
          'set.clearAll': 'Clear all',
          'set.save': 'Save settings',
          'set.people': 'people',
          'set.unitPos': 'positions',
          'set.stateAll': 'All positions allowed',
          'set.employeeData': 'Employee data',
          'set.photos.title': 'Employee photos',
          'set.photos.note': 'Pull the photos HR takes into the shared folder (named by employee code) and attach them to the employee record, so a photo stays with the person even after they resign and their account is removed.',
          'set.photos.sourceOk': 'Source folder is reachable',
          'set.photos.sourceFail': 'Source folder is unreachable. The machine running the system must be able to reach this share.',
          'set.photos.statWith': 'Employees with a photo',
          'set.photos.statMissing': 'Still without a photo',
          'set.photos.statOrphan': 'Files with no matching employee',
          'set.photos.statLastRun': 'Last photo pull',
          'set.photos.pull': 'Pull new photos now',
          'set.photos.pulling': 'Pulling photos...',
          'set.photos.done': 'Photo pull complete',
          'set.photos.failed': 'Photo pull failed',
          'set.photos.connectionError': 'Connection failed',
          'set.photos.resImported': 'new',
          'set.photos.resUpdated': 'updated',
          'set.photos.resUnchanged': 'unchanged',
          'set.photos.resOrphan': 'unmatched',
          'set.photos.missingTitle': 'Employees without a photo (for HR to follow up)',
          'set.photos.noMissing': 'Every active employee has a photo',
          'set.photos.orphanTitle': 'Photo files with no matching employee',
          'set.photos.noOrphan': 'No unmatched files',
          'set.photos.colCode': 'Code',
          'set.photos.colName': 'Name',
          'set.photos.colPosition': 'Position',
          'set.photos.colDept': 'Department',
          'set.photos.colHire': 'Hire date',
          'set.photos.colFile': 'File name',
          'set.photos.colReason': 'Reason',
          'set.photos.colModified': 'File date',
          'set.bplus.title': 'Pull B Plus employee data',
          'set.bplus.actionsCount': '2 actions',
          'set.bplus.note': 'Pull the latest employee data from Bplus and sync login accounts. Reports open automatically when complete and the latest reports remain available here.',
          'set.bplus.pull': 'Pull data from B Plus',
          'set.bplus.syncAccounts': 'Sync login accounts',
          'set.bplus.latestPull': 'View latest data-pull report',
          'set.bplus.latestSync': 'View latest sync report',
          'set.bplus.reportKicker': 'Change report',
          'set.bplus.report': 'Report',
          'set.bplus.latestRun': 'Latest run',
          'set.bplus.running': 'Working...',
          'set.bplus.connectionError': 'Unable to connect',
          'set.bplus.error': 'An error occurred',
          'set.bplus.emptyTitle': 'No employees in {label}',
          'set.bplus.emptyBody': 'There were no changes in this category during the latest run.',
          'set.bplus.employeeCode': 'Employee code',
          'set.bplus.fullName': 'Full name',
          'set.bplus.position': 'Position',
          'set.bplus.department': 'Department',
          'set.bplus.company': 'Company',
          'set.bplus.pullTitle': 'Pull B Plus employee data',
          'set.bplus.syncTitle': 'Sync login accounts',
          'set.bplus.tabNewHires': 'New employees (7 days)',
          'set.bplus.tabAll': 'All employees',
          'set.bplus.tabWorking': 'Active',
          'set.bplus.tabPendingResign': 'Resigned (pending close)',
          'set.bplus.tabResigned': 'Resigned',
          'set.bplus.tabNewAccounts': 'New accounts (7 days)',
          'set.bplus.tabUpdatedAccounts': 'Updated accounts',
          'set.bplus.tabRemovedAccounts': 'Removed accounts (resigned)',
          'set.bplus.extraStartDate': 'Start date',
          'set.bplus.extraStatus': 'Status',
          'set.bplus.extraEffectiveResignDate': 'Effective resignation date',
          'set.bplus.extraResignDate': 'Resignation date',
          'set.bplus.extraSyncedAt': 'Synced at',
          'set.bplus.statusWorking': 'Active',
          'set.bplus.statusPendingResign': 'Resigned (pending close)',
          'set.bplus.statusResigned': 'Resigned',
          'set.export.title': 'Download employee data (Excel)',
          'set.export.note': 'Download all active employees with code, Thai and English names, position, department, and social security number. Missing English names are filled automatically from the stored name.',
          'set.export.button': 'Download employee Excel',
          'set.recruit.title': 'Recruit user permissions',
          'set.recruit.note': 'Assign a role to each employee — only added people can access the Recruit system (administrators always can).',
          'set.recruit.add': 'Add member',
          'set.recruit.unit': 'roles',
          'set.recruit.routeTitle': 'Document route order',
          'set.recruit.routeNote': 'Requests follow this order — admin can reorder.',
          'set.recruit.saveRoute': 'Save route',
          'set.recruit.pickTitle': 'Add member',
          'set.recruit.search': 'Search name / employee code',
          'set.recruit.empty': 'No employees found',
          'set.recruit.loading': 'Searching...',
          'set.recruit.note2': 'Build multiple approval routes — each route has its own ordered cards, roles, and members. DCC picks which route to send when creating a request.',
          'set.recruit.addCard': 'Add card',
          'set.recruit.addRoute': 'Add route',
          'set.recruit.delRoute': 'Delete route',
          'set.recruit.delRouteConfirm': 'Delete this route and all its cards?',
          'set.recruit.noRoute': 'No routes yet — click Add route to create the first approval route',
          'set.recruit.pickRole': 'Choose role',
          'set.recruit.role.dcc': 'DCC',
          'set.recruit.role.manager': 'Manager',
          'set.recruit.role.general_manager': 'General Manager',
          'set.recruit.role.hr_manager': 'HR Manager',
          'set.recruit.role.recruit': 'Recruit',
          'set.recruit.dept': 'Dept',
          'set.recruit.deptTitle': 'Visible departments',
          'set.recruit.deptSave': 'Save departments',
          'set.recruit.noStep': 'No cards yet — click Add card to create the first route step',
          'set.recruit.delCard': 'Delete this card?',
          'rc.overview': 'Overview',
          'rc.historyTitle': 'Headcount request history',
          'rc.legend.done': 'Done',
          'rc.legend.pending': 'Pending',
          'rc.legend.ack': 'Acknowledged (Recruit)',
          'rc.col.requester': 'Requester',
          'rc.col.reqDate': 'Request date',
          'rc.col.startDate': 'Start date',
          'rc.col.status': 'Status',
          'rc.empty': 'No headcount requests yet',
          'nav.logout': 'Log out',
          'systems.kicker': 'Systems',
          'systems.title': 'All systems',
          'systems.recruitName': 'RECRUIT SYSTEM',
          'systems.recruitDesc': 'Internal headcount request management system.',
          'systems.assessmentName': 'SUPAVUT ASSESSMENT',
          'systems.assessmentDesc': 'Performance assessment and score management tools.',
          'systems.devName': 'SUPAVUT 5S AREA',
          'systems.devDesc': 'A workspace for cleaning areas, responsibilities, and internal service status.',
          'systems.comingName': 'COMING SOON',
          'systems.comingDesc': 'A new system is being prepared.',
          'systems.moreName': 'MORE SYSTEMS',
          'systems.moreDesc': 'More systems will be added later.',
          'systems.searchPh': 'Search systems, documents, or people',
          'systems.heroTitle': 'Welcome to Supavut Insight Intranet',
          'systems.heroKicker': 'WELCOME TO',
          'systems.heroTitleMain': 'SUPAVUT INSIGHT',
          'systems.heroTitleAccent': 'Intranet',
          'systems.heroSubtitle': 'One place for HR systems, people data, documents, and internal services.',
          'systems.appAccess': 'Application Access',
          'systems.availableNow': '3 core systems',
          'systems.availableNowWithRecruit': '4 core systems',
          'systems.launcher': 'Application Launcher',
          'systems.launcherNote': 'Choose the work you want to start',
          'systems.peopleName': 'People',
          'systems.peopleDesc': 'User profile and employee information.',
          'systems.documentsName': 'Documents',
          'systems.documentsDesc': 'Company policies and internal documents.',
          'systems.okrName': 'OKR',
          'systems.okrDesc': 'Track team objectives and key results.',
          'systems.requestsName': 'Requests',
          'systems.requestsDesc': 'Submit and track internal requests.',
          'systems.noSearchResults': 'No matching systems found.',
          'systems.footerVersion': 'Intranet v2.6.0',
          'systems.detailView': 'View details',
          'systems.detailClose': 'Close details',
          'systems.calendarTitle': 'Calendar',
          'systems.weatherTitle': 'Weather',
          'systems.weatherLoading': 'Loading',
          'systems.weatherHourlyTitle': 'Hourly weather',
          'systems.weatherHourlyEmpty': 'Hourly weather is not available yet.',
          'systems.weatherFeels': 'Feels',
          'systems.weatherHumidity': 'Humidity',
          'systems.weatherWind': 'Wind',
          'systems.contactTitle': 'HR / IT Contact',
          'systems.hrSupport': 'HR Support',
          'systems.itSupport': 'IT Support',
          'systems.contactButton': 'Contact',
          'admin.kicker': 'Administrator',
          'admin.title': 'System Overview',
          'admin.note': 'Employee Master summary and sync status (administrators only).',
          'admin.active': 'Active employees',
          'admin.companies': 'Companies',
          'admin.resigned': 'Total resigned employees',
          'admin.byCompany': 'Employees by company and department',
          'admin.active_short': 'active',
          'admin.resigned_short': 'resigned',
          'admin.resignedAllShort': 'All resigned',
          'admin.pendingResignShort': 'Pending close',
          'admin.payrollClosedShort': 'Closed',
          'admin.resignedAllTitle': 'Resigned employees (all)',
          'admin.pendingResignTitle': 'Resigned employees pending payroll close',
          'admin.payrollClosedTitle': 'Payroll-closed resigned employees',
          'admin.resignedKicker': 'Resigned',
          'admin.resignedTitle': 'Resigned employees',
          'admin.resignedOn': 'Resigned',
          'admin.noResigned': 'No resigned employees',
          'admin.resignedStatusTabs': 'Resignation status',
          'admin.employeeSearch': 'Search employees',
          'admin.employeeSearchPlaceholder': 'Employee code or full name',
          'admin.allDepartments': 'All departments',
          'admin.allPositions': 'All positions',
          'admin.searchButton': 'Search',
          'admin.clearButton': 'Clear',
          'admin.resultsFound': '{count} employees found',
          'admin.noMatchingEmployees': 'No employees match these filters',
          'admin.activeEmployeeSearch': 'Search active employees',
          'admin.activeEmployees': 'Active employees',
          'admin.activeSearchRequired': 'Enter a code or name, or select a department or position',
          'admin.searchResults': 'Search results',
          'admin.searchResultsCount': 'Search results ({count})',
          'admin.searchResultsLimited': '· Showing the first 300',
          'admin.searchFailed': 'Search failed. Please try again',
          'admin.total_short': 'total',
          'admin.people': 'people',
          'admin.depts': 'depts',
          'admin.noEmp': 'No employees yet',
          'admin.loading': 'Loading...',
          'admin.empty': 'No data',
          'admin.close': 'Close',
          'admin.employeeDetailKicker': 'B Plus data',
          'admin.employeeDetailTitle': 'Employee profile and leave rights',
          'admin.hiredOn': 'Started',
          'admin.leaveUnavailable': 'Leave rights cannot be retrieved from B Plus',
          'admin.leaveRightsYear': 'Leave rights for {year}',
          'admin.leaveAsOf': 'As of {date}',
          'admin.noLeaveRights': 'No leave-right data found',
          'admin.leaveType': 'Leave type',
          'admin.leaveEntitled': 'Entitled',
          'admin.leaveUsed': 'Used',
          'admin.leaveRemaining': 'Remaining',
          'admin.leaveUnitNote': 'Figures use the units configured in B Plus and include records awaiting processing.',
          'admin.loadingLeave': 'Loading leave rights from B Plus...',
          'info.kicker': 'Profile',
          'info.title': 'User Information',
          'field.company': 'Company',
          'field.title': 'Title',
          'field.name': 'Full name',
          'field.department': 'Department',
          'field.position': 'Position',
          'field.status': 'Work status',
          'field.hire': 'Start date',
          'pwd.kicker': 'Security',
          'pwd.title': 'Password',
          'pwd.idcard': 'ID card number',
          'pwd.idcard_ph': 'Enter your ID card number to verify',
          'pwd.new': 'New password',
          'pwd.new_ph': 'At least 6 characters',
          'pwd.confirm': 'Confirm new password',
          'pwd.confirm_ph': 'Re-enter the new password',
          'pwd.submit': 'Save password',
          'pwd.hint': 'Enter the correct ID card number first to set a new password.',
          'profile.upload': 'Upload photo',
          'profile.boardAria': 'User information',
          'profile.employeeSource': 'Employee data from the central system',
          'profile.employeeCode': 'Employee code',
          'profile.active': 'Active',
          'profile.resigned': 'Resigned',
          'profile.noEmail': 'No email entered',
          'profile.manageEmail': 'Manage email',
          'profile.emailHelp': 'Used for notifications within the system',
          'profile.manageSignature': 'Manage signature',
          'profile.signatureHelp': 'Draw with a mouse or finger, or upload an existing signature image and save it for documents.',
          'profile.noSignature': 'No signature yet',
          'profile.addSignature': 'Add signature',
          'profile.noEditAccess': 'No permission to edit',
          'profile.signatureMethod': 'Signature input method',
          'common.close': 'Close',
          'common.success': 'Success',
          'common.ok': 'OK',
          'sig.title': 'Signature',
          'sig.saved': 'Signature saved',
          'sig.hint': 'Sign here (mouse or finger)',
          'sig.pen': 'Pen size',
          'sig.save': 'Save signature',
          'sig.savedOk': 'Signature saved',
          'sig.error': 'Save failed, try again',
          'sig.undo': 'Undo',
          'sig.redo': 'Redo',
          'sig.clear': 'Clear all',
          'sig.modeDraw': 'Draw',
          'sig.modeUpload': 'Upload image',
          'sig.dropTitle': 'Drop an image here, or click to choose a file',
          'sig.dropHint': 'PNG · JPG · HEIC (iPhone) — a photo of your signature on paper or a screenshot signed with a stylus both work. The background is removed and the signature cropped automatically.',
          'sig.pick': 'Choose another image',
          'sig.level': 'Ink strength',
          'sig.inkBlack': 'Black ink',
          'sig.processing': 'Processing image…',
          'sig.tooBig': 'File is too large (over 20 MB)',
          'sig.badFile': 'That file is not an image',
          'sig.readFail': 'Could not open this image, try another file',
          'sig.noInk': 'No signature found in the image — try raising "Ink strength"',
          'sig.heicFail': 'This browser cannot open iPhone HEIC files — pick the photo from the Photos app on your iPhone (it converts to JPG automatically), or set Settings > Camera > Formats > Most Compatible',
          'field.email': 'Email',
          'email.ph': 'Enter your email',
          'email.save': 'Save',
          'email.saved': 'Email saved',
          'email.invalid': 'Invalid email format',
          'email.error': 'Save failed, try again',
          'profile.view_photo': 'View profile photo',
          'profile.close_photo': 'Close photo',
          'profile.zoom_group': 'Adjust image size',
          'profile.zoom_in': 'Zoom in',
          'profile.zoom_out': 'Zoom out',
          'profile.zoom_reset': 'Reset to 100%',
          'pwd.change': 'Change password',
          'pwd.verify': 'Verify',
          'pwd.verified': 'ID card verified',
          'pwd.idcard_wrong': 'Incorrect ID card number',
          'pwd.masked': 'Password hidden',
          'pwd.show': 'Show',
          'pwd.hide': 'Hide',
          'pwd.cancel': 'Cancel',
          'recruit.kicker': 'Recruitment',
          'recruit.title': 'Recruitment',
          'recruit.note': 'The recruitment system is currently under development.',
          'assessment.kicker': 'Assessment',
          'assessment.title': 'SUPAVUT Assessment',
          'assessment.pending': 'Pending update',
          'assessment.clearEvaluators': 'Clear evaluators',
          'assessment.clearEvaluatorsConfirm': 'Clear evaluators for all employees?\nScores and levels will not be deleted. Only evaluator fields will be cleared.',
          'assessment.clearingEvaluators': 'Clearing...',
          'assessment.clearEvaluatorsError': 'Failed to clear evaluators',
          'assessment.clearEvaluatorsNetwork': 'Connection failed',
          'assessment.note': 'SUPAVUT Assessment is currently under development.',
          'assessment.home.kicker': 'Administrator announcement',
          'assessment.home.titleAdmin': 'Welcome, Admin',
          'assessment.home.titleHr': 'Welcome, HR team',
          'assessment.home.subtitle': 'The announcement and starting point for SUPAVUT Assessment administrators and HR members with score management access.',
          'assessment.home.primaryScores': 'Manage scores',
          'assessment.home.primaryOpenRound': 'Open assessment round',
          'assessment.home.rounds': 'Rounds',
          'assessment.home.settings': 'Permission settings',
          'assessment.home.currentUser': 'Current user',
          'assessment.home.role': 'System role',
          'assessment.home.noticeKicker': 'Admin Notice',
          'assessment.home.noticeTitle': 'Assessment announcements',
          'assessment.home.updated': 'Updated',
          'assessment.home.notice1Title': 'Open a round before score work',
          'assessment.home.notice1Body': 'An open round is required before importing scores, editing evaluator hierarchy, or setting position levels so data stays clear by round.',
          'assessment.home.notice2Title': 'HR access is for score management',
          'assessment.home.notice2Body': 'Employees added as HR users can enter Assessment and manage scores. Permission assignment and system settings remain admin-only.',
          'assessment.home.notice3Title': 'Use approved Employee Master data only',
          'assessment.home.notice3Body': 'Assessment reads employee data from Insight only and does not bring salary or payroll data into this module.',
          'assessment.home.workflowKicker': 'Workflow',
          'assessment.home.workflowTitle': 'Recommended workflow',
          'assessment.home.flow1Title': 'Open assessment round',
          'assessment.home.flow1Body': 'Set the round name and year before entering data for a new round.',
          'assessment.home.flow2Title': 'Manage people data and scores',
          'assessment.home.flow2Body': 'Import files, edit evaluator hierarchy, set score columns, and review calculated results.',
          'assessment.home.flow3Title': 'Set questions',
          'assessment.home.flow3Body': 'Prepare questions by Input column and evaluator level.',
          'assessment.home.flow4Title': 'Review results',
          'assessment.home.flow4Body': 'Review results by round and year, then download the summary file when ready.',
          'assessment.home.statusKicker': 'Status',
          'assessment.home.statusTitle': 'Current status',
          'assessment.home.statusOpenRound': 'Open round',
          'assessment.home.noOpenRound': 'No open round',
          'assessment.home.statusLatestRound': 'Latest round',
          'assessment.home.statusHr': 'HR members',
          'assessment.home.statusBoxes': 'Main score topics',
          'assessment.home.statusEmployees': 'Active employees',
          'assessment.home.scopeKicker': 'Permission',
          'assessment.home.scopeTitle': 'Access scope',
          'assessment.home.scopeAdmin': 'Can access every page, including HR assignment and position settings.',
          'assessment.home.scopeHr': 'Can manage rounds, scores, questions, and results, but cannot access system settings.',
          'assessment.evaluate.title': 'Employee Evaluation',
          'assessment.evaluate.formTitle': 'Employee Evaluation Form',
          'assessment.evaluate.pageTitle': 'Employees Assigned to You',
          'assessment.evaluate.help': 'Select an employee, then start or continue the assessment for the open round.',
          'assessment.evaluate.backToList': 'Back to evaluation list',
          'assessment.evaluate.role.directSupervisor': 'Direct Supervisor',
          'assessment.evaluate.role.division': 'Division Evaluator',
          'assessment.evaluate.role.directAndDivision': 'Direct Supervisor / Division Evaluator',
          'assessment.evaluate.itemUnit': 'items',
          'assessment.evaluate.employee': 'Employee to assess',
          'assessment.evaluate.position': 'Position',
          'assessment.evaluate.department': 'Department',
          'assessment.evaluate.evaluator': 'Evaluator',
          'assessment.form.fullName': 'Full name :',
          'assessment.form.employeeCode': 'Employee ID :',
          'assessment.form.totalScore': 'Total score :',
          'assessment.form.position': 'Position :',
          'assessment.form.department': 'Department :',
          'assessment.employeeCode': 'Employee ID',
          'assessment.fullName': 'Full name',
          'assessment.levelWord': 'Level',
          'assessment.openRound': 'Open round:',
          'assessment.review.title': 'Results to review',
          'assessment.review.help': 'Shows only employees where you are assigned as level 3 or 4 reviewer in the open round',
          'assessment.review.yourLevel': 'Your review level',
          'assessment.review.tableLabel': 'Results review table',
          'assessment.review.levelOneTotal': 'Assessment score',
          'assessment.review.selfScore': 'Self score',
          'assessment.review.noScore': 'No score yet',
          'assessment.codeShort': 'ID',
          'assessment.levelShort': 'Level',
          'assessment.filterWord': 'Filter',
          'assessment.hierLevel': 'Hierarchy',
          'assessment.downloadExcel': '⬇ Download Excel',
          'assessment.import': '⬆ Import',
          'assessment.clearFilter': 'Clear filters',
          'assessment.showingThisPage': 'This page',
          'assessment.totalWord': 'Total',
          'assessment.peopleUnit': 'people',
          'assessment.noEmployee': 'No employees found',
          'assessment.legendPastel': 'Pastel = data group',
          'assessment.legendDark': 'Darker = not counted at this level',
          'assessment.scores.step1Sub': 'Set position levels · Edit scores + hierarchy in the table · Download/import Excel',
          'assessment.scores.h11': '1.1 Position levels',
          'assessment.scores.h11Hint': 'Assign positions to levels · Level 0 = not assessed · + adds a level, ✕ removes it (numbers reorder automatically)',
          'assessment.scores.unleveled': 'Not assigned',
          'assessment.scores.h12': '1.2 Employee data',
          'assessment.scores.h12Hint': 'Edit evaluators (hierarchy 1–4) directly in the cells · or download, edit and import back',
          'assessment.scores.step2': 'Manage columns',
          'assessment.scores.step2Sub': 'KPI score columns — create them here or import a column file',
          'assessment.scores.h21': '2.1 Create / import columns',
          'assessment.scores.h21Hint': 'Create manually or import a file (row 1 = main heading, row 2 = sub column)',
          'assessment.scores.importColumns': '⬆ Import column file',
          'assessment.scores.mainColPh': 'Main heading, e.g. Attendance',
          'assessment.scores.addMainCol': '+ Add main heading',
          'assessment.scores.h22': '2.2 Column check table (Excel view)',
          'assessment.scores.h22Hint': 'Updates instantly with the cards above',
          'assessment.scores.step3': 'Weights and calculation',
          'assessment.scores.step3Sub': 'Set weights per level 1–5 — must total 100 per level · Extra (+/-)',
          'assessment.scores.h31': '3.1 Set level weights',
          'assessment.scores.h31Hint': 'Weights total 100 per level · Extra = +/- counted separately · Not calculated = excluded',
          'assessment.scores.h32': '3.2 Sub column calculation',
          'assessment.scores.h32Hint': 'Full score = weighted · Attendance = deduction/grade cap · Input = filled in by evaluators · Extra score = added directly',
          'assessment.scores.step4': 'Score import',
          'assessment.scores.step4Sub': 'Import / edit scores cell by cell',
          'assessment.scores.h41': '4.1 Import / edit scores',
          'assessment.scores.h41Hint': 'Cells unlock once all 4 evaluators are set · Separate multiple evaluators with a comma, e.g. 8.5,7.5 · Type N/A to exclude a cell · On import a blank cell keeps the existing value',
          'assessment.scores.importScores': '⬆ Import scores',
          'assessment.scores.noColumns': 'No score columns yet — create or import them in section 2 first',
          'assessment.questions.hint2': 'Write the notes shown on the form for the selected employee level · Available for both main headings and sub columns · The show/hide button controls whether evaluators see it · Saved automatically',
          'assessment.questions.previewFor': 'Preview of the form shown to employees at',
          'assessment.questions.topicWord': 'Topic',
          'assessment.questions.noSubColumns': 'This heading has no sub columns yet',
          'assessment.questions.hint3': 'Click the ⓘ icon next to a heading or column to write its note and toggle whether evaluators see it · Green = shown · Red = written but hidden · Grey = empty',
          'assessment.questions.autosave': 'Saved automatically',
          'assessment.questions.noWeights': 'No weights set for this level yet — set them in Manage, section 3.1 first',
          'assessment.questions.demoName': '(Sample) Mr. Somchai Jaidee',
          'assessment.questions.demoPosition': '(Sample) position at level',
          'assessment.questions.scaleTitle': 'Score choices',
          'assessment.questions.scaleHint': 'Evaluators see these choices in this order, then pick a score inside a choice',
          'assessment.questions.scaleAdd': '+ Add choice',
          'assessment.questions.scaleColName': 'Choice',
          'assessment.questions.scaleColScores': 'Scores',
          'assessment.questions.scaleReset': 'Reset to default',
          'assessment.questions.scaleSave': 'Save',
          'assessment.evaluate.status': 'Status',
          'assessment.evaluate.action': 'Assess',
          'assessment.evaluate.status.done': 'Completed',
          'assessment.evaluate.status.partial': 'Incomplete',
          'assessment.evaluate.status.pending': 'Pending',
          'assessment.evaluate.button.edit': 'View / Edit',
          'assessment.evaluate.button.continue': 'Continue',
          'assessment.evaluate.button.start': 'Assess',
          'assessment.evaluate.viewPhoto': 'View photo',
          'assessment.evaluate.zoomOpen': 'Click to enlarge',
          'assessment.sort.aria': 'Sort',
          'assessment.sort.code': 'Sort by employee code',
          'assessment.sort.rankDesc': 'Sort by position (high → low)',
          'assessment.sort.rankAsc': 'Sort by position (low → high)',
          'assessment.evaluate.zoomHint': 'Scroll to zoom · Double-click to zoom in/out',
          'assessment.evaluate.profileTitle': 'Employee profile photo',
          'assessment.evaluate.employeeCode': 'Employee code',
          'assessment.evaluate.fullName': 'Full name',
          'assessment.evaluate.placeholderTitle': 'Evaluation form area',
          'assessment.evaluate.placeholderHelp': 'Reserved for questions and score fields for this role.',
          'assessment.common.manage': 'Manage',
          'assessment.common.questions': 'Questions',
          'assessment.common.results': 'Results',
          'assessment.common.round': 'Round',
          'assessment.common.summary': 'Assessment summary',
          'assessment.common.level': 'Level',
          'assessment.common.employeeCount': 'Employees',
          'assessment.common.notAssessed': 'Not assessed',
          'assessment.common.notCalculated': 'Not calculated',
          'assessment.common.noOpenRound': 'No open round',
          'assessment.common.perPage': 'Per page',
          'assessment.common.clear': 'Clear',
          'assessment.common.apply': 'Apply',
          'assessment.common.selectAll': 'Select all',
          'assessment.common.loadingFilters': 'Loading filters',
          'assessment.common.ok': 'OK',
          'assessment.common.success': 'Success',
          'assessment.common.notice': 'Notice',
          'assessment.common.cancel': 'Cancel',
          'assessment.common.delete': 'Delete',
          'assessment.common.showing': 'Showing',
          'assessment.common.people': 'people',
          'assessment.common.afterFilter': 'after filtering',
          'assessment.common.code': 'Code',
          'assessment.common.year': 'Year',
          'assessment.common.roundName': 'Round name',
          'assessment.common.openedAt': 'Opened at',
          'assessment.common.by': 'By',
          'assessment.common.section': 'Section',
          'assessment.common.page': 'Page',
          'assessment.form.level': 'Level',
          'assessment.form.evaluate': 'Evaluate',
          'assessment.form.notAssessed': 'Not assessed',
          'assessment.form.selectedScore': 'Selected score: {score} out of 10',
          'assessment.form.noScoreSelected': 'No score selected',
          'assessment.form.networkError': 'Unable to connect',
          'assessment.form.scoreRangeError': 'Score must be between 0 and 10',
          'assessment.form.saveError': 'Unable to save',
          'assessment.self.openRound': 'Open round',
          'assessment.self.year': 'Year',
          'assessment.self.pendingForm': 'The Self Evaluation round is open. The self-evaluation form is not enabled yet while its format is being finalized.',
          'assessment.self.adminNav': 'Self-assessment settings',
          'assessment.self.selectedCount': 'Participants',
          'assessment.self.manageTab': 'Manage',
          'assessment.self.questionsTab': 'Questions',
          'assessment.self.resultsTab': 'Results',
          'assessment.self.h11': '1.1 Position levels',
          'assessment.self.h11Hint': 'Assign levels 1–5 by employee position for this self-assessment round.',
          'assessment.self.unassigned': 'Not assigned',
          'assessment.self.addLevel': 'Add level',
          'assessment.self.questionsHint2': 'Questions set here appear on the form for employees at this level',
          'assessment.self.questionPlaceholder': 'Question text',
          'assessment.self.choicePlaceholder': 'Choice',
          'assessment.self.choiceNo': 'Choice',
          'assessment.self.naNote': 'Excluded from scoring',
          'assessment.self.needChoice': 'Every question needs text and at least one choice',
          'assessment.self.deleteLevelConfirm': 'Delete this level? Its questions are removed and higher levels shift down.',
          'assessment.self.deleteLevelBusy': 'This level has {count} position(s). Deleting sends them back to Not assigned and removes this levelu2019s questions. Continue?',
          'assessment.self.deleteLevelError': 'Could not delete the level',
          'assessment.self.levelLabel': 'Level',
          'assessment.self.h12': '1.2 Employee data',
          'assessment.self.h12Hint': 'Select employees who must complete a self-assessment. Data comes from the current Employee master.',
          'assessment.self.selectAllVisible': 'Select all visible',
          'assessment.self.clearAllVisible': 'Clear all shown',
          'assessment.self.saveSelection': 'Save participants',
          'assessment.self.participantColumn': 'Self-assessment',
          'assessment.self.selected': 'Selected',
          'assessment.self.notSelected': 'Not selected',
          'assessment.self.unsavedChanges': '{count} unsaved changes',
          'assessment.self.saving': 'Saving...',
          'assessment.self.saved': 'Participants saved',
          'assessment.self.saveFailed': 'Unable to save participants',
          'assessment.self.questionsTitle': 'Questions by level',
          'assessment.self.questionsHint': 'Create TH / EN / MY questions, set each full score, then add choices one at a time by level.',
          'assessment.self.structureOnly': 'Screen structure',
          'assessment.self.questionColumn': 'Question',
          'assessment.self.answerTypeColumn': 'Answer type',
          'assessment.self.statusColumn': 'Status',
          'assessment.self.questionsPending': 'No questions are configured for this level.',
          'assessment.self.addQuestion': 'Add question',
          'assessment.self.defaultChoicesHint': 'Choices can be added after the question is saved.',
          'assessment.self.saveQuestion': 'Save question',
          'assessment.self.questionShort': 'Question',
          'assessment.self.questionDetailHint': 'Complete the question in all three languages and set the full score used for calculation.',
          'assessment.self.questionFullScore': 'Question full score',
          'assessment.self.questionFullScoreHint': 'Used as this question’s denominator; for example, 4 out of 5 equals 80%.',
          'assessment.self.saveQuestionHint': 'Choices can be added after the question is saved.',
          'assessment.self.choicesCount': 'choices',
          'assessment.self.notReady': 'Not ready',
          'assessment.self.deleteQuestion': 'Delete question',
          'assessment.self.saveChanges': 'Save changes',
          'assessment.self.choicesTitle': 'Answer choices',
          'assessment.self.choiceScoreHint': 'A scored choice cannot exceed the question full score.',
          'assessment.self.optionShort': 'Choice',
          'assessment.self.choiceType': 'Choice type',
          'assessment.self.choiceTypeScore': 'Typed score',
          'assessment.self.choiceTypeHint': 'Select a choice type, then enter its TH / EN / MY text.',
          'assessment.self.choiceScore': 'Score',
          'assessment.self.saveChoice': 'Save choice',
          'assessment.self.deleteChoice': 'Delete choice',
          'assessment.self.addChoice': 'Add choice',
          'assessment.self.noChoices': 'No choices yet. This question will not appear on the employee form.',
          'assessment.self.noQuestions': 'No questions for this level',
          'assessment.self.noQuestionsHint': 'Select “Add question” to start building the form.',
          'assessment.self.deleteConfirm': 'Delete this item?',
          'assessment.self.resultsTitle': 'Self-assessment results',
          'assessment.self.resultsHint': 'Shows the total score and per-question percentages for Admin-selected employees.',
          'assessment.self.exportTitle': 'Export self-assessment results',
          'assessment.self.exportHint': 'Excel results file for the selected round.',
          'assessment.self.exportButton': '⬇ Download',
          'assessment.self.roundPicker': 'Choose the round to download · ● = open round',
          'assessment.self.roundOpenMark': '● = open round',
          'assessment.self.scoreColumn': 'Self score',
          'assessment.self.noParticipants': 'No employees found',
          'assessment.self.userReadyTitle': 'You are eligible for this self-assessment round',
          'assessment.self.userPendingTitle': 'Your participation is ready',
          'assessment.self.userPendingText': 'Admin has selected you. Questions and answer submission will be enabled in the next step.',
          'assessment.self.formTitle': 'Self assessment',
          'assessment.self.lastSaved': 'Last saved',
          'assessment.self.formInstructionTitle': 'How to complete',
          'assessment.self.formInstruction': 'Answer every question. Each selected score is compared with the question full score set by Admin, and N/A is excluded.',
          'assessment.self.levelMissing': 'Admin has not assigned your position level yet.',
          'assessment.self.noFormQuestions': 'There are no self-assessment questions for your level yet.',
          'assessment.self.answered': 'Answered',
          'assessment.self.totalPercent': 'Total score',
          'assessment.self.submitForm': 'Save self assessment',
          'assessment.questions.evaluatorLevel': 'Evaluator level',
          'assessment.questions.deleteTitle': 'Delete question',
          'assessment.results.year': 'Year',
          'assessment.results.importHint': 'Template of the open round — fill it outside the system, then import it back.',
          'assessment.results.importButton': '⬆ Import',
          'assessment.results.exportTitle': 'Export results',
          'assessment.results.downloadButton': '⬇ Download',
          'assessment.results.editableHint': 'Scores can be entered according to each field’s rules · N/A excludes that field.',
          'assessment.results.readonlyHint': 'This round is closed — view only',
          'assessment.scores.addLevel': '＋ Add level',
          'assessment.scores.deleteMainTitle': 'Delete the main heading and its subcolumns',
          'assessment.scores.deleteSubTitle': 'Delete subcolumn',
          'assessment.scores.editMainTitle': 'Edit main heading name',
          'assessment.scores.editSubTitle': 'Edit subcolumn name',
          'assessment.scores.addSubPlaceholder': '+ Add subcolumn...',
          'assessment.form.performanceTitle': 'Employee performance evaluation form',
          'assessment.form.scoreWeightTitle': 'Score weight categories',
          'assessment.form.noScoreWeights': 'No score weight categories yet',
          'assessment.form.performanceSection': 'Performance evaluation',
          'assessment.form.noScoreColumns': 'No score columns yet',
          'assessment.home.noOpenRoundTitle': 'No assessment round is open',
          'assessment.home.noOpenRoundBody': 'When HR opens a round, your hierarchy 1 and 2 evaluators will appear here.',
          'assessment.home.noEvaluatorsTitle': 'No evaluators yet',
          'assessment.home.noEvaluatorsBody': 'No employee code or name is assigned to hierarchy 1 or 2 for your data in this round.',
          'assessment.home.me': 'Me',
          'assessment.rounds.title': 'Assessment rounds',
          'assessment.rounds.employeeTab': 'Employee evaluation',
          'assessment.rounds.selfTab': 'Self evaluation',
          'assessment.rounds.selfHint': 'Open a round so employees can complete self evaluations · opening a new round closes the current round automatically.',
          'assessment.rounds.employeeHint': 'An employee evaluation round must be open before assessments can be managed · opening a new round closes the current one and starts a new score, evaluator, and level set (previous rounds remain available).',
          'assessment.rounds.listHint': 'Open a round to work with that round and year · delete a round to permanently remove its data.',
          'assessment.rounds.openState': '● Open',
          'assessment.rounds.closedState': 'Closed',
          'assessment.rounds.viewResults': 'View results',
          'assessment.rounds.close': 'Close round',
          'assessment.rounds.open': 'Open round',
          'assessment.rounds.empty': 'No rounds yet — open the first round above',
          'assessment.rounds.deleteTitle': 'Delete this round?',
          'assessment.rounds.deleteBefore': 'This will delete round',
          'assessment.rounds.deleteAfter': 'and all of its data (scores · evaluators · levels) permanently. This cannot be undone.',
          'assessment.rounds.deletePermanent': 'Delete permanently',
          'assessment.rounds.openNewTitle': 'Open new round',
          'assessment.rounds.allTitle': 'All rounds',
          'assessment.rounds.namePlaceholder': 'Round name, e.g. Current year evaluation — Round 1',
          'assessment.rounds.openNewButton': '+ Open new round',
          'assessment.rounds.confirmOpenNew': 'Open a new round? The current round will close and a new data set will begin.',
          'assessment.rounds.confirmClose': 'Close round “{name}”?',
          'assessment.rounds.confirmReopen': 'Open or switch to round “{name}” ({year})? The current open round will close.',
          'assessment.questions.title': 'Questions',
          'assessment.questions.hint': 'Questions are sent to evaluators by the hierarchy of Input columns · enter up to 3 languages (TH required · EN/MY optional) · changes save automatically.',
          'assessment.questions.noInputColumns': 'No Input columns yet — set a column type in Manage, section 3.2 first.',
          'assessment.questions.languageLabel': 'Show question language',
          'assessment.results.title': 'Results',
          'assessment.results.importOverall': 'Import all data',
          'assessment.results.exportHint': 'Full results file for the selected round.',
          'assessment.results.roundPicker': 'Choose the round to download',
          'assessment.results.roundOpenMark': '● = open round',
          'assessment.results.legendGroup': 'Pastel = data group',
          'assessment.results.legendGroupTitle': 'Pastel colors separate data by main heading',
          'assessment.results.legendUnavailable': 'Darker = excluded for this level',
          'assessment.results.legendUnavailableTitle': 'This column is not calculated for the employee’s level',
          'assessment.results.clearFilters': 'Clear filters',
          'assessment.results.empty': 'No employees match the selected conditions',
          'assessment.results.noColumns': 'No score columns yet — create or import them in Manage, section 2 first.',
          'assessment.settings.hrTitle': 'User permissions (HR)',
          'assessment.settings.hrHint': 'Added employees can access Assessment and manage scores (administrators already have access).',
          'assessment.settings.noMembers': 'No members yet',
          'assessment.settings.positionsTitle': 'Positions allowed to access the system',
          'assessment.settings.positionsHint': 'Select positions from Bplus employee data that may access the system · if never saved, every position is allowed.',
          'assessment.settings.selectAll': 'Select all',
          'assessment.settings.clear': 'Clear selection',
          'assessment.settings.save': 'Save positions',
          'assessment.settings.searchPlaceholder': 'Search employee code or name to add as HR',
          'assessment.settings.removeTitle': 'Remove',
          'assessment.settings.saved': 'Saved ✓',
          'assessment.settings.error': 'Error',
          'assessment.scores.levelTitle': 'Level {level}',
          'assessment.scores.deleteLevelTitle': 'Delete level {level}',
          'assessment.scores.deleteLevelConfirm': 'Delete level {level}?',
          'assessment.scores.deleteLevelPropWarning': 'The proportions for this level in section 3.1 will also be deleted.',
          'assessment.scores.deleteLevelPositionsWarning': '{count} assigned positions will move back to “Unassigned level”.',
          'assessment.scores.deleteLevelShiftWarning': 'Higher levels will shift down (for example, level {from} → {to}).',
          'assessment.scores.deleteLevelError': 'Unable to delete level',
          'assessment.scores.saveLevelError': 'Unable to save level',
          'assessment.scores.saveError': 'Unable to save',
          'assessment.scores.deleteError': 'Unable to delete',
          'assessment.scores.addError': 'Unable to add',
          'assessment.scores.minEvaluatorError': 'At least one evaluator level is required',
          'assessment.scores.evaluatorSlotsTitle': 'Evaluator level slots: {levels}',
          'assessment.scores.evaluatorTitle': 'Evaluator level {level}',
          'assessment.scores.deleteMainConfirm': 'Delete this main heading?\nIts subcolumns and all scores previously saved in those columns will also be deleted.',
          'assessment.scores.deleteSubConfirm': 'Delete this subcolumn?\nAll scores previously saved in this column will also be deleted.',
          'assessment.self.title': 'Self evaluation',
          'assessment.filter.clear': 'Clear',
          'assessment.filter.apply': 'Apply',
          'assessment.filter.selectAll': 'Select all',
          'assessment.filter.loading': 'Loading filters',
          'assessment.filter.searchPlaceholder': 'Search...',
          'assessment.filter.noResults': 'No results',
          'assessment.filter.emptyValue': '(blank)',
          'assessment.filter.columnTitle': 'Filter column',
          'assessment.filter.loadFailed': 'Unable to load filters',
          'assessment.scores.noColumnsPreview': 'No columns yet — create or import them in section 2.1 first.',
          'assessment.scores.typeScore': 'Full score',
          'assessment.scores.typeBonus': 'Bonus score',
          'assessment.scores.calcDeduct': 'Deduct score',
          'assessment.scores.calcGrade': 'Grade',
          'assessment.scores.noSubColumns': 'No sub-columns yet — create them in section 2 first.',
          'assessment.scores.noMainColumns': 'No main headings yet — create or import them in section 2 first.',
          'assessment.scores.noLevels': 'No levels yet — use + Add level in section 1.1 first (level 0 is not calculated).',
          'assessment.scores.choose': 'Choose',
          'assessment.scores.other': 'Other...',
          'assessment.scores.modePercent': 'Weight',
          'assessment.scores.modeExtra': 'Extra (+/-)',
          'assessment.scores.modeNone': 'Not calculated',
          'assessment.scores.levelLabel': 'Level',
          'assessment.scores.totalLabel': 'Total',
          'assessment.scores.mainHeadingUnit': 'main headings',
          'assessment.scores.columnUnit': 'columns',
          'assessment.scores.maximum': 'Maximum',
          'assessment.scores.fullPlaceholder': 'Full score 10',
          'assessment.scores.removeHierarchy': 'Remove this hierarchy',
          'assessment.scores.hierarchyLabel': 'Hierarchy',
          'assessment.scores.customPlaceholder': 'Custom value',
          'assessment.hierarchy.1': 'Hierarchy 1',
          'assessment.hierarchy.2': 'Hierarchy 2',
          'assessment.hierarchy.3': 'Hierarchy 3',
          'assessment.hierarchy.4': 'Hierarchy 4'
        },
        my: {
          'loader.switching': 'စနစ်ပြောင်းနေသည်',
          'loader.enter': 'ဝင်ရောက်နေသည် ',
          'loader.loading': 'ဖွင့်နေသည်',
          'loader.logout': 'ထွက်နေသည်',
          'toast.recruitDenied': 'သင်သည် Recruit System အသုံးပြုခွင့် မရရှိသေးပါ',
          'toast.assessmentDenied': 'သင်သည် Assessment စနစ် အသုံးပြုခွင့် မရရှိသေးပါ',
          'toast.pageDenied': 'ဤစာမျက်နှာကို ဖွင့်ရန် ခွင့်ပြုချက်မရှိပါ',
          'toast.close': 'အသိပေးချက် ပိတ်ရန်',
          'toast.ack': 'နားလည်ပါပြီ',
          'welcome.greeting': 'ကြိုဆိုပါသည်',
          'nav.info': 'အချက်အလက်',
          'nav.recruit': 'ဝန်ထမ်းခေါ်ယူ',
          'nav.assessment': 'အကဲဖြတ်စနစ်',
          'nav.systems': 'HR စနစ်အားလုံး',
          'nav.overview': 'အကျဉ်းချုပ်',
          'nav.a5sHome': 'ပင်မစာမျက်နှာ',
          'nav.toggleMenu': 'မီနူး ဖွင့်/ပိတ်',
          'nav.otHome': 'ပင်မစာမျက်နှာ',
          'noti.aria': 'အသိပေးချက်များ',
          'noti.title': 'အသိပေးချက်များ',
          'noti.readAll': 'အားလုံးဖတ်ပြီး',
          'noti.loading': 'ဖွင့်နေသည်...',
          'noti.empty': 'အသိပေးချက် မရှိသေးပါ',
          'noti.error': 'အသိပေးချက်များ ဖွင့်၍မရပါ',
          'nav.a5sMyWork': 'ကျွန်ုပ်၏ ဧရိယာအလုပ်',
          'nav.a5sManage': 'ဧရိယာ စီမံရန်',
          'nav.a5sMembers': 'စနစ်ဆက်တင်',
          'nav.a5sRounds': 'လစဉ် အပတ်',
          'nav.a5sDownloads': 'စာရွက်စာတမ်း ဒေါင်းလုဒ်',
          'a5s.rounds.title': 'လစဉ် အပတ်',
          'a5s.downloads.title': 'စာရွက်စာတမ်း ဒေါင်းလုဒ်',
          'nav.back': 'SUPAVUT INSIGHT သို့ ပြန်သွားရန်',
          'nav.admin': 'စနစ်အကျဉ်းချုပ်',
          'nav.settings': 'ဆက်တင်',
          'nav.sysSettings': 'စနစ်ဆက်တင်',
          'nav.asmManage': 'အကဲဖြတ်စနစ် စီမံရန်',
          'nav.asmRounds': 'အကဲဖြတ်ကာလများ',
          'nav.asmScores': 'ဝန်ထမ်း အကဲဖြတ်ခြင်း',
          'nav.asmEvaluate': 'ဝန်ထမ်း အကဲဖြတ်ရန်',
          'nav.asmReview': 'ရလဒ် စစ်ဆေးရန်',
          'nav.asmSelf': 'မိမိကိုယ်တိုင် အကဲဖြတ်ခြင်း',
          'nav.rcDepts': 'ကျွန်ုပ်၏ ဌာနများ',
          'nav.rcRequest': 'လူအင်အားတောင်းခံရန်',
          'nav.rcReview': 'စာရွက်စာတမ်း စစ်ဆေးရန်',
          'nav.rcInbox': 'စာရွက်စာတမ်း ဝင်စာတွဲ',
          'nav.rcDownloads': 'ဒေါင်းလုဒ်',
          'nav.rcAll': 'လုပ်ဆောင်ချက်အားလုံး',
          'rc.wip': 'တီထွင်နေဆဲ',
          'rc.stubNote': 'ဤအပိုင်းကို တီထွင်နေဆဲဖြစ်ပြီး မကြာမီ ရရှိပါမည်။',
          'rq.intro': 'SI-HR-010 အတိုင်း အွန်လိုင်းပုံစံ — အတည်ပြုလမ်းကြောင်းရွေးပြီး ဖိုင်တွဲပါ။',
          'rq.workflowTitle': 'အချက်အလက်ဖြည့်ပါ',
          'rq.workflowNote': 'အချက်အလက်ဖြည့်ပါ',
          'rq.workflowStepNote': 'တောင်းဆိုသူအချက်အလက်စစ်ဆေးပါ၊ အတည်ပြုလမ်းကြောင်းရွေးပြီး Manager ရွေးပါ။',
          'rq.formStepTitle': 'လူအင်အားတောင်းခံဖောင် ဖြည့်ပါ',
          'rq.formStepNote': 'SI-HR-010 စာရွက်ဖောင်ကို ပြည့်စုံအောင် ဖြည့်ပါ။',
          'rq.jdStepNote': 'ခေါ်ယူမည့်ရာထူးအတွက် JD ဖိုင်ကို တွဲပါ။',
          'rq.resignStepNote': 'နုတ်ထွက်သူအစားထိုးတောင်းခံမှုအတွက် သို့မဟုတ် လိုအပ်သော စာရွက်စာတမ်းများ တွဲပါ။',
          'rq.requester': 'တောင်းသူအချက်အလက်',
          'rq.empCode': 'ဝန်ထမ်းကုဒ်',
          'rq.empName': 'အမည်အပြည့်အစုံ',
          'rq.empPosition': 'ရာထူး',
          'rq.empDept': 'ဌာန',
          'rq.chooseRoute': 'အတည်ပြုလမ်းကြောင်း ရွေးပါ',
          'rq.pickManager': 'Manager ရွေးပါ',
          'rq.routeEmpty': 'ရွေးနိုင်သော လမ်းကြောင်းမရှိသေးပါ — စနစ်ဆက်တင်တွင် စစ်ဆေးပါ။',
          'rq.attachTitle': 'ပူးတွဲဖိုင်များ',
          'rq.attachNote': 'မပို့မီ Job Description နှင့် နုတ်ထွက်စာ သို့မဟုတ် အခြားစာရွက်များ တွဲပါ။',
          'rq.attachJd': 'Job Description တွဲရန်',
          'rq.attachJdNote': 'PDF, Word, Excel သို့မဟုတ် ဓာတ်ပုံ ရသည်',
          'rq.attachResign': 'နုတ်ထွက်စာ တွဲရန်',
          'rq.attachResignNote': 'အစားထိုးတောင်းခံမှုအတွက် သို့မဟုတ် လိုအပ်သော အခြားစာရွက်များ',
          'rq.resultMock': 'ပို့ပြီးသကဲ့သို့ ပြသသည် — ဤအဆင့်တွင် ဒေတာ မသိမ်းသေးပါ။',
          'rq.reset': 'ပုံစံ ရှင်းရန်',
          'rq.submit': 'တောင်းခံမှု ပို့ရန်',
          'rq.confirmTitle': 'ပို့ရန် အတည်ပြုပါ',
          'rq.confirmNote': 'မပို့မီ ပုံစံနှင့် ဖိုင်များ စစ်ဆေးပါ။ စနစ်အသက်ဝင်ပါက ရွေးထားသော လမ်းကြောင်းအတိုင်း Manager ထံ သွားမည်။',
          'rq.cancel': 'ပယ်ဖျက်ရန်',
          'rq.confirmSubmit': 'ပို့ရန် အတည်ပြု',
          'set.kicker': 'စနစ်ဆက်တင်',
          'set.title': 'ဆက်တင်',
          'set.login.title': 'ရာထူးအလိုက် ဝင်ရောက်ခွင့်',
          'set.login.note': 'ဝင်ရောက်ခွင့်ပြုမည့် ရာထူးများကို အမှန်ခြစ်ပါ — မခြစ်ထားသော ရာထူးများ ဝင်၍မရပါ (စီမံခန့်ခွဲသူ အမြဲဝင်နိုင်သည်)။',
          'set.email.title': 'အီးမေးလ်ဖြည့်ခွင့်',
          'set.email.note': 'ပရိုဖိုင်တွင် အီးမေးလ်ဖြည့်/သိမ်းခွင့်ပြုမည့် ရာထူးများကို အမှန်ခြစ်ပါ — မခြစ်ထားသော ရာထူးများ အီးမေးလ်ကွက်မမြင်ရပါ (စီမံခန့်ခွဲသူ အမြဲရသည်)။',
          'set.sig.title': 'လက်မှတ်သိမ်းခွင့်',
          'set.sig.note': 'ပရိုဖိုင်တွင် လက်မှတ်သိမ်းခွင့်ပြုမည့် ရာထူးများကို အမှန်ခြစ်ပါ — မခြစ်ထားသော ရာထူးများ လက်မှတ်ဘုတ်မမြင်ရပါ (စီမံခန့်ခွဲသူ အမြဲရသည်)။',
          'set.selectAll': 'အားလုံးရွေး',
          'set.clearAll': 'အားလုံးရှင်း',
          'set.save': 'ဆက်တင်သိမ်းရန်',
          'set.people': 'ဦး',
          'set.unitPos': 'ရာထူး',
          'set.stateAll': 'ရာထူးအားလုံး ခွင့်ပြု',
          'set.employeeData': 'ဝန်ထမ်းဒေတာ',
          'set.photos.title': 'ဝန်ထမ်းဓာတ်ပုံများ',
          'set.photos.note': 'HR က ဘုံဖိုင်တွဲထဲသို့ ရိုက်ထည့်ထားသော ဓာတ်ပုံများ (ဝန်ထမ်းကုဒ်ဖြင့် အမည်ပေးထား) ကို စနစ်ထဲသို့ ရယူပြီး ဝန်ထမ်းနှင့် ချိတ်ဆက်ပါသည်။ ဝန်ထမ်းထွက်ခွာပြီး အကောင့်ဖျက်သွားသော်လည်း ဓာတ်ပုံ ကျန်ရှိနေမည်။',
          'set.photos.sourceOk': 'မူရင်းဖိုင်တွဲကို ဖတ်နိုင်သည်',
          'set.photos.sourceFail': 'မူရင်းဖိုင်တွဲကို ဖတ်၍မရပါ။ စနစ်လည်ပတ်သည့်စက်မှ ဤ share ကို ချိတ်ဆက်နိုင်ရမည်။',
          'set.photos.statWith': 'ဓာတ်ပုံရှိသော ဝန်ထမ်း',
          'set.photos.statMissing': 'ဓာတ်ပုံမရှိသေးသူ',
          'set.photos.statOrphan': 'ပိုင်ရှင်မတွေ့သော ဖိုင်များ',
          'set.photos.statLastRun': 'နောက်ဆုံး ဓာတ်ပုံရယူမှု',
          'set.photos.pull': 'ဓာတ်ပုံအသစ် ယခုရယူရန်',
          'set.photos.pulling': 'ဓာတ်ပုံရယူနေသည်...',
          'set.photos.done': 'ဓာတ်ပုံရယူမှု ပြီးဆုံးပါပြီ',
          'set.photos.failed': 'ဓာတ်ပုံရယူမှု မအောင်မြင်ပါ',
          'set.photos.connectionError': 'ချိတ်ဆက်မှု မအောင်မြင်ပါ',
          'set.photos.resImported': 'အသစ်',
          'set.photos.resUpdated': 'အသစ်ပြင်',
          'set.photos.resUnchanged': 'မပြောင်းလဲ',
          'set.photos.resOrphan': 'ပိုင်ရှင်မတွေ့',
          'set.photos.missingTitle': 'ဓာတ်ပုံမရှိသေးသော ဝန်ထမ်းများ (HR လိုက်ရိုက်ရန်)',
          'set.photos.noMissing': 'လက်ရှိဝန်ထမ်းအားလုံး ဓာတ်ပုံရှိပြီးဖြစ်သည်',
          'set.photos.orphanTitle': 'ပိုင်ရှင်မတွေ့သော ဓာတ်ပုံဖိုင်များ',
          'set.photos.noOrphan': 'ပိုင်ရှင်မတွေ့သော ဖိုင် မရှိပါ',
          'set.photos.colCode': 'ကုဒ်',
          'set.photos.colName': 'အမည်',
          'set.photos.colPosition': 'ရာထူး',
          'set.photos.colDept': 'ဌာန',
          'set.photos.colHire': 'အလုပ်စတင်သည့်ရက်',
          'set.photos.colFile': 'ဖိုင်အမည်',
          'set.photos.colReason': 'အကြောင်းရင်း',
          'set.photos.colModified': 'ဖိုင်ရက်စွဲ',
          'set.bplus.title': 'B Plus ဝန်ထမ်းဒေတာ ရယူရန်',
          'set.bplus.actionsCount': 'လုပ်ဆောင်ချက် ၂ ခု',
          'set.bplus.note': 'Bplus မှ နောက်ဆုံးဝန်ထမ်းဒေတာကို ရယူပြီး ဝင်ရောက်သုံးစွဲသည့်အကောင့်များကို sync လုပ်ပါ။ ပြီးဆုံးပါက အစီရင်ခံစာကို အလိုအလျောက်ဖွင့်ပြီး နောက်ဆုံးအစီရင်ခံစာကို ဤနေရာမှ ပြန်ကြည့်နိုင်သည်။',
          'set.bplus.pull': 'B Plus မှ ဒေတာရယူရန်',
          'set.bplus.syncAccounts': 'ဝင်ရောက်သုံးစွဲသည့်အကောင့်များ Sync လုပ်ရန်',
          'set.bplus.latestPull': 'နောက်ဆုံးဒေတာရယူမှု အစီရင်ခံစာကြည့်ရန်',
          'set.bplus.latestSync': 'နောက်ဆုံး Sync အစီရင်ခံစာကြည့်ရန်',
          'set.bplus.reportKicker': 'ပြောင်းလဲမှု အစီရင်ခံစာ',
          'set.bplus.report': 'အစီရင်ခံစာ',
          'set.bplus.latestRun': 'နောက်ဆုံးလုပ်ဆောင်မှု',
          'set.bplus.running': 'လုပ်ဆောင်နေသည်...',
          'set.bplus.connectionError': 'ချိတ်ဆက်၍မရပါ',
          'set.bplus.error': 'အမှားဖြစ်ပွားသည်',
          'set.bplus.emptyTitle': '{label} အုပ်စုတွင် ဝန်ထမ်းမရှိပါ',
          'set.bplus.emptyBody': 'နောက်ဆုံးလုပ်ဆောင်မှုတွင် ဤအုပ်စု၌ ပြောင်းလဲမှုမရှိပါ။',
          'set.bplus.employeeCode': 'ဝန်ထမ်းကုဒ်',
          'set.bplus.fullName': 'အမည်အပြည့်အစုံ',
          'set.bplus.position': 'ရာထူး',
          'set.bplus.department': 'ဌာန',
          'set.bplus.company': 'ကုမ္ပဏီ',
          'set.bplus.pullTitle': 'B Plus ဝန်ထမ်းဒေတာ ရယူရန်',
          'set.bplus.syncTitle': 'ဝင်ရောက်သုံးစွဲသည့်အကောင့်များ Sync လုပ်ရန်',
          'set.bplus.tabNewHires': 'ဝန်ထမ်းအသစ် (၇ ရက်)',
          'set.bplus.tabAll': 'အားလုံး',
          'set.bplus.tabWorking': 'အလုပ်လုပ်နေသူ',
          'set.bplus.tabPendingResign': 'အလုပ်ထွက် (ကာလပိတ်ရန်စောင့်)',
          'set.bplus.tabResigned': 'အလုပ်ထွက်ပြီး',
          'set.bplus.tabNewAccounts': 'အကောင့်အသစ် (၇ ရက်)',
          'set.bplus.tabUpdatedAccounts': 'အကောင့်အပ်ဒိတ်',
          'set.bplus.tabRemovedAccounts': 'ဖယ်ရှားထားသောအကောင့် (အလုပ်ထွက်)',
          'set.bplus.extraStartDate': 'အလုပ်စသည့်ရက်',
          'set.bplus.extraStatus': 'အခြေအနေ',
          'set.bplus.extraEffectiveResignDate': 'အလုပ်ထွက်အကျိုးသက်ရောက်သည့်ရက်',
          'set.bplus.extraResignDate': 'အလုပ်ထွက်သည့်ရက်',
          'set.bplus.extraSyncedAt': 'Sync လုပ်သည့်အချိန်',
          'set.bplus.statusWorking': 'အလုပ်လုပ်နေသူ',
          'set.bplus.statusPendingResign': 'အလုပ်ထွက် (ကာလပိတ်ရန်စောင့်)',
          'set.bplus.statusResigned': 'အလုပ်ထွက်ပြီး',
          'set.export.title': 'ဝန်ထမ်းဒေတာ ဒေါင်းလုဒ် (Excel)',
          'set.export.note': 'အလုပ်လုပ်နေသော ဝန်ထမ်းအားလုံး၏ ကုဒ်၊ ထိုင်းနှင့် အင်္ဂလိပ်အမည်၊ ရာထူး၊ ဌာနနှင့် လူမှုဖူလုံရေးနံပါတ်တို့ကို ဒေါင်းလုဒ်လုပ်ပါ။ အင်္ဂလိပ်အမည် မရှိပါက စနစ်ထဲရှိအမည်မှ အလိုအလျောက်ဖြည့်ပေးမည်။',
          'set.export.button': 'ဝန်ထမ်း Excel ဒေါင်းလုဒ်',
          'set.recruit.title': 'Recruit အသုံးပြုခွင့်',
          'set.recruit.note': 'ဝန်ထမ်းတစ်ဦးချင်းအား role သတ်မှတ်ပါ — ထည့်ထားသူသာ Recruit စနစ်ဝင်နိုင်သည် (စီမံခန့်ခွဲသူ အမြဲဝင်နိုင်)။',
          'set.recruit.add': 'အဖွဲ့ဝင်ထည့်ရန်',
          'set.recruit.unit': 'role',
          'set.recruit.routeTitle': 'စာရွက်စာတမ်း လမ်းကြောင်းအစဉ်',
          'set.recruit.routeNote': 'တောင်းဆိုမှုသည် ဤအစဉ်အတိုင်း သွားမည် — admin ပြန်စီနိုင်သည်။',
          'set.recruit.saveRoute': 'လမ်းကြောင်းသိမ်းရန်',
          'set.recruit.pickTitle': 'အဖွဲ့ဝင်ထည့်ရန်',
          'set.recruit.search': 'အမည် / ဝန်ထမ်းကုဒ် ရှာရန်',
          'set.recruit.empty': 'ဝန်ထမ်းမတွေ့ပါ',
          'set.recruit.loading': 'ရှာနေသည်...',
          'set.recruit.note2': 'အတည်ပြုလမ်းကြောင်းများစွာ ဖန်တီးနိုင်သည် — လမ်းကြောင်းတစ်ခုစီတွင် ကတ်အစဉ်၊ role နှင့် အဖွဲ့ဝင်များ ရှိသည်။ တောင်းဆိုစဉ် DCC က လမ်းကြောင်းရွေးသည်။',
          'set.recruit.addCard': 'ကတ်ထည့်ရန်',
          'set.recruit.addRoute': 'လမ်းကြောင်းထည့်ရန်',
          'set.recruit.delRoute': 'လမ်းကြောင်းဖျက်ရန်',
          'set.recruit.delRouteConfirm': 'ဤလမ်းကြောင်းနှင့် ကတ်အားလုံး ဖျက်မလား?',
          'set.recruit.noRoute': 'လမ်းကြောင်းမရှိသေးပါ — လမ်းကြောင်းထည့်ရန် နှိပ်ပါ',
          'set.recruit.pickRole': 'role ရွေးရန်',
          'set.recruit.role.dcc': 'DCC',
          'set.recruit.role.manager': 'Manager',
          'set.recruit.role.general_manager': 'General Manager',
          'set.recruit.role.hr_manager': 'HR Manager',
          'set.recruit.role.recruit': 'Recruit',
          'set.recruit.dept': 'ဌာန',
          'set.recruit.deptTitle': 'မြင်နိုင်သော ဌာနများ',
          'set.recruit.deptSave': 'ဌာနသိမ်းရန်',
          'set.recruit.noStep': 'ကတ်မရှိသေးပါ — ကတ်ထည့်ရန် နှိပ်ပါ',
          'set.recruit.delCard': 'ဤကတ်ဖျက်မလား?',
          'rc.overview': 'အကျဉ်းချုပ်',
          'rc.historyTitle': 'လူအင်အားတောင်းခံမှု မှတ်တမ်း',
          'rc.legend.done': 'ပြီးပြီ',
          'rc.legend.pending': 'စောင့်ဆိုင်းဆဲ',
          'rc.legend.ack': 'လက်ခံပြီး (Recruit)',
          'rc.col.requester': 'တောင်းသူ',
          'rc.col.reqDate': 'တောင်းသည့်ရက်',
          'rc.col.startDate': 'စတင်ရက်',
          'rc.col.status': 'အခြေအနေ',
          'rc.empty': 'လူအင်အားတောင်းခံမှု မရှိသေးပါ',
          'nav.logout': 'ထွက်ရန်',
          'systems.kicker': 'စနစ်များ',
          'systems.title': 'စနစ်အားလုံး',
          'systems.recruitName': 'RECRUIT SYSTEM',
          'systems.recruitDesc': 'အဖွဲ့အစည်းအတွင်း လူအင်အားတောင်းခံမှုများကို စီမံခန့်ခွဲသောစနစ်။',
          'systems.assessmentName': 'SUPAVUT ASSESSMENT',
          'systems.assessmentDesc': 'ဝန်ထမ်းအကဲဖြတ်ခြင်းနှင့် အမှတ်စီမံခန့်ခွဲမှုကိရိယာများ။',
          'systems.devName': 'SUPAVUT 5S AREA',
          'systems.devDesc': 'သန့်ရှင်းရေးဧရိယာများ၊ တာဝန်များနှင့် အတွင်းပိုင်းဝန်ဆောင်မှုအခြေအနေများကို စီမံရန်နေရာ။',
          'systems.comingName': 'COMING SOON',
          'systems.comingDesc': 'စနစ်အသစ်ကို အသုံးပြုရန် ပြင်ဆင်နေသည်။',
          'systems.moreName': 'MORE SYSTEMS',
          'systems.moreDesc': 'အခြားစနစ်များကို နောက်ပိုင်းတွင် ထပ်မံထည့်သွင်းမည်။',
          'systems.searchPh': 'စနစ်၊ စာရွက်စာတမ်း သို့မဟုတ် လူကို ရှာရန်',
          'systems.heroTitle': 'Welcome to Supavut Insight Intranet',
          'systems.heroKicker': 'WELCOME TO',
          'systems.heroTitleMain': 'SUPAVUT INSIGHT',
          'systems.heroTitleAccent': 'Intranet',
          'systems.heroSubtitle': 'HR စနစ်များ၊ ဝန်ထမ်းအချက်အလက်၊ စာရွက်စာတမ်းများနှင့် အတွင်းပိုင်းဝန်ဆောင်မှုများအတွက် ဗဟိုနေရာ။',
          'systems.appAccess': 'အဓိကစနစ်များအားလုံး',
          'systems.availableNow': 'အဓိကစနစ် ၃ ခု',
          'systems.availableNowWithRecruit': 'အဓိကစနစ် ၄ ခု',
          'systems.launcher': 'Application Launcher',
          'systems.launcherNote': 'စတင်အသုံးပြုလိုသောအလုပ်ကို ရွေးပါ',
          'systems.peopleName': 'People',
          'systems.peopleDesc': 'အသုံးပြုသူပရိုဖိုင်နှင့် ဝန်ထမ်းအချက်အလက်။',
          'systems.documentsName': 'Documents',
          'systems.documentsDesc': 'ကုမ္ပဏီမူဝါဒများနှင့် အတွင်းပိုင်းစာရွက်စာတမ်းများ။',
          'systems.okrName': 'OKR',
          'systems.okrDesc': 'အသင်း၏ ရည်မှန်းချက်များနှင့် ရလဒ်များကို ခြေရာခံပါ။',
          'systems.requestsName': 'Requests',
          'systems.requestsDesc': 'အတွင်းပိုင်းတောင်းဆိုမှုများကို ပို့ပြီး ခြေရာခံပါ။',
          'systems.noSearchResults': 'ကိုက်ညီသောစနစ် မတွေ့ပါ။',
          'systems.footerVersion': 'Intranet v2.6.0',
          'systems.detailView': 'အသေးစိတ်ကြည့်ရန်',
          'systems.detailClose': 'အသေးစိတ် ပိတ်ရန်',
          'systems.calendarTitle': 'ပြက္ခဒိန်',
          'systems.weatherTitle': 'ရာသီဥတု',
          'systems.weatherLoading': 'ဖွင့်နေသည်',
          'systems.weatherHourlyTitle': 'နာရီအလိုက်ရာသီဥတု',
          'systems.weatherHourlyEmpty': 'နာရီအလိုက်ရာသီဥတု မရှိသေးပါ။',
          'systems.weatherFeels': 'ခံစားရသည်',
          'systems.weatherHumidity': 'စိုထိုင်းဆ',
          'systems.weatherWind': 'လေ',
          'systems.contactTitle': 'HR / IT Contact',
          'systems.hrSupport': 'HR Support',
          'systems.itSupport': 'IT Support',
          'systems.contactButton': 'ဆက်သွယ်ရန်',
          'admin.kicker': 'စီမံခန့်ခွဲသူ',
          'admin.title': 'စနစ်အကျဉ်းချုပ်',
          'admin.note': 'Employee Master အကျဉ်းချုပ်နှင့် sync အခြေအနေ (စီမံခန့်ခွဲသူသာ)။',
          'admin.active': 'အလုပ်လုပ်နေသော ဝန်ထမ်းများ',
          'admin.companies': 'ကုမ္ပဏီ',
          'admin.resigned': 'နုတ်ထွက်သွားသော ဝန်ထမ်းစုစုပေါင်း',
          'admin.byCompany': 'ကုမ္ပဏီနှင့် ဌာနအလိုက် ဝန်ထမ်းများ',
          'admin.active_short': 'အလုပ်လုပ်နေ',
          'admin.resigned_short': 'နုတ်ထွက်',
          'admin.resignedAllShort': 'နုတ်ထွက်အားလုံး',
          'admin.pendingResignShort': 'ပိတ်ရန်စောင့်',
          'admin.payrollClosedShort': 'ပိတ်ပြီး',
          'admin.resignedAllTitle': 'နုတ်ထွက်ဝန်ထမ်းများ (အားလုံး)',
          'admin.pendingResignTitle': 'လစာကာလပိတ်ရန် စောင့်ဆိုင်းနေသော နုတ်ထွက်ဝန်ထမ်းများ',
          'admin.payrollClosedTitle': 'လစာကာလပိတ်ပြီး နုတ်ထွက်ဝန်ထမ်းများ',
          'admin.resignedKicker': 'နုတ်ထွက်ဝန်ထမ်း',
          'admin.resignedTitle': 'နုတ်ထွက်ဝန်ထမ်းများ',
          'admin.resignedOn': 'နုတ်ထွက်',
          'admin.noResigned': 'နုတ်ထွက်ဝန်ထမ်း မရှိပါ',
          'admin.resignedStatusTabs': 'နုတ်ထွက်အခြေအနေ',
          'admin.employeeSearch': 'ဝန်ထမ်းရှာရန်',
          'admin.employeeSearchPlaceholder': 'ဝန်ထမ်းကုဒ် သို့မဟုတ် အမည်အပြည့်အစုံ',
          'admin.allDepartments': 'ဌာနအားလုံး',
          'admin.allPositions': 'ရာထူးအားလုံး',
          'admin.searchButton': 'ရှာရန်',
          'admin.clearButton': 'ရှင်းရန်',
          'admin.resultsFound': 'ဝန်ထမ်း {count} ဦး တွေ့ရှိသည်',
          'admin.noMatchingEmployees': 'သတ်မှတ်ချက်နှင့် ကိုက်ညီသော ဝန်ထမ်းမရှိပါ',
          'admin.activeEmployeeSearch': 'အလုပ်လုပ်နေသော ဝန်ထမ်းများကို ရှာရန်',
          'admin.activeEmployees': 'အလုပ်လုပ်နေသော ဝန်ထမ်းများ',
          'admin.activeSearchRequired': 'ကုဒ် သို့မဟုတ် အမည်ထည့်ပါ၊ သို့မဟုတ် ဌာန/ရာထူးရွေးပါ',
          'admin.searchResults': 'ရှာဖွေမှုရလဒ်',
          'admin.searchResultsCount': 'ရှာဖွေမှုရလဒ် ({count} ဦး)',
          'admin.searchResultsLimited': '· ပထမ 300 ဦးကို ပြထားသည်',
          'admin.searchFailed': 'ရှာဖွေမှု မအောင်မြင်ပါ။ ထပ်မံကြိုးစားပါ',
          'admin.total_short': 'စုစုပေါင်း',
          'admin.people': 'ဦး',
          'admin.depts': 'ဌာန',
          'admin.noEmp': 'ဝန်ထမ်းမရှိသေးပါ',
          'admin.loading': 'ဖွင့်နေသည်...',
          'admin.empty': 'ဒေတာမရှိပါ',
          'admin.close': 'ပိတ်ရန်',
          'admin.employeeDetailKicker': 'B Plus ဒေတာ',
          'admin.employeeDetailTitle': 'ဝန်ထမ်းအချက်အလက်နှင့် ခွင့်ခံစားခွင့်',
          'admin.hiredOn': 'အလုပ်စတင်သည့်နေ့',
          'admin.leaveUnavailable': 'B Plus မှ ခွင့်ခံစားခွင့်ဒေတာကို မဖတ်နိုင်ပါ',
          'admin.leaveRightsYear': '{year} ခုနှစ် ခွင့်ခံစားခွင့်',
          'admin.leaveAsOf': '{date} ရက်နေ့အထိ',
          'admin.noLeaveRights': 'ခွင့်ခံစားခွင့်ဒေတာ မတွေ့ပါ',
          'admin.leaveType': 'ခွင့်အမျိုးအစား',
          'admin.leaveEntitled': 'ခံစားခွင့်',
          'admin.leaveUsed': 'အသုံးပြုပြီး',
          'admin.leaveRemaining': 'လက်ကျန်',
          'admin.leaveUnitNote': 'ကိန်းဂဏန်းများသည် B Plus တွင် သတ်မှတ်ထားသော ယူနစ်အတိုင်းဖြစ်ပြီး လုပ်ဆောင်ဆဲစာရင်းများ ပါဝင်သည်။',
          'admin.loadingLeave': 'B Plus မှ ခွင့်ခံစားခွင့်ကို ဖတ်နေသည်...',
          'info.kicker': 'ကိုယ်ရေးအချက်အလက်',
          'info.title': 'အသုံးပြုသူအချက်အလက်',
          'field.company': 'ကုမ္ပဏီ',
          'field.title': 'ဂုဏ်ပုဒ်',
          'field.name': 'အမည်',
          'field.department': 'ဌာန',
          'field.position': 'ရာထူး',
          'field.status': 'အလုပ်အခြေအနေ',
          'field.hire': 'စတင်သည့်ရက်',
          'pwd.kicker': 'လုံခြုံရေး',
          'pwd.title': 'စကားဝှက်',
          'pwd.idcard': 'မှတ်ပုံတင်နံပါတ်',
          'pwd.idcard_ph': 'အတည်ပြုရန် မှတ်ပုံတင်နံပါတ်ထည့်ပါ',
          'pwd.new': 'စကားဝှက်အသစ်',
          'pwd.new_ph': 'အနည်းဆုံး ၆ လုံး',
          'pwd.confirm': 'စကားဝှက်အသစ် အတည်ပြုပါ',
          'pwd.confirm_ph': 'စကားဝှက်အသစ် ထပ်ထည့်ပါ',
          'pwd.submit': 'စကားဝှက်သိမ်းရန်',
          'pwd.hint': 'စကားဝှက်အသစ်သတ်မှတ်ရန် မှတ်ပုံတင်နံပါတ်မှန်ကန်စွာ ဦးစွာထည့်ပါ။',
          'profile.upload': 'ဓာတ်ပုံတင်ရန်',
          'profile.boardAria': 'အသုံးပြုသူအချက်အလက်',
          'profile.employeeSource': 'ဗဟိုစနစ်မှ ဝန်ထမ်းဒေတာ',
          'profile.employeeCode': 'ဝန်ထမ်းကုဒ်',
          'profile.active': 'အလုပ်လုပ်နေသည်',
          'profile.resigned': 'အလုပ်ထွက်ပြီး',
          'profile.noEmail': 'အီးမေးလ် မဖြည့်ရသေးပါ',
          'profile.manageEmail': 'အီးမေးလ် စီမံရန်',
          'profile.emailHelp': 'စနစ်အတွင်း အသိပေးချက်များအတွက် အသုံးပြုသည်',
          'profile.manageSignature': 'လက်မှတ် စီမံရန်',
          'profile.signatureHelp': 'မောက်စ် သို့ လက်ချောင်းဖြင့် ရေးပါ၊ သို့မဟုတ် ရှိပြီးသား လက်မှတ်ပုံကို တင်ပြီး စာရွက်စာတမ်းများအတွက် သိမ်းထားပါ။',
          'profile.noSignature': 'လက်မှတ် မရှိသေးပါ',
          'profile.addSignature': 'လက်မှတ် ထည့်ရန်',
          'profile.noEditAccess': 'ပြင်ဆင်ခွင့် မရှိသေးပါ',
          'profile.signatureMethod': 'လက်မှတ်ထည့်သည့်နည်းလမ်း',
          'common.close': 'ပိတ်ရန်',
          'common.success': 'အောင်မြင်သည်',
          'common.ok': 'အိုကေ',
          'sig.title': 'လက်မှတ်',
          'sig.saved': 'လက်မှတ်သိမ်းပြီး',
          'sig.hint': 'ဤနေရာတွင် လက်မှတ်ထိုးပါ (မောက်စ် သို့ လက်)',
          'sig.pen': 'ကလောင်အရွယ်',
          'sig.save': 'လက်မှတ်သိမ်းရန်',
          'sig.savedOk': 'လက်မှတ်သိမ်းပြီး',
          'sig.error': 'သိမ်းမရပါ ထပ်စမ်းပါ',
          'sig.undo': 'နောက်ပြန်',
          'sig.redo': 'ထပ်လုပ်',
          'sig.clear': 'အားလုံးဖျက်',
          'sig.modeDraw': 'ကိုယ်တိုင်ရေး',
          'sig.modeUpload': 'ဓာတ်ပုံတင်ရန်',
          'sig.dropTitle': 'ဓာတ်ပုံကို ဤနေရာသို့ ဆွဲထည့်ပါ သို့ နှိပ်၍ ရွေးပါ',
          'sig.dropHint': 'PNG · JPG · HEIC (iPhone) — စာရွက်ပေါ်ရေးထားသော လက်မှတ်ဓာတ်ပုံ သို့ ကလောင်ဖြင့်ရေးထားသော ဖန်သားပြင်ပုံလည်း ရပါသည်။ နောက်ခံကို ဖယ်ရှား၍ လက်မှတ်ကိုသာ အလိုအလျောက် ဖြတ်ယူပါမည်။',
          'sig.pick': 'အခြားပုံရွေးရန်',
          'sig.level': 'အရင့်အနု',
          'sig.inkBlack': 'မှင်အနက်',
          'sig.processing': 'ပုံကို ပြင်ဆင်နေသည်…',
          'sig.tooBig': 'ဖိုင်အရွယ် ကြီးလွန်းသည် (20 MB ကျော်)',
          'sig.badFile': 'ဤဖိုင်သည် ဓာတ်ပုံမဟုတ်ပါ',
          'sig.readFail': 'ဤပုံကို ဖွင့်မရပါ အခြားဖိုင်ဖြင့် စမ်းပါ',
          'sig.noInk': 'ပုံထဲတွင် လက်မှတ်မတွေ့ပါ "အရင့်အနု" ကို တိုးကြည့်ပါ',
          'sig.heicFail': 'ဤဘရောက်ဇာသည် iPhone ၏ HEIC ဖိုင်ကို ဖွင့်၍မရပါ — iPhone ရှိ Photos မှ ပုံရွေးပါ (JPG သို့ အလိုအလျောက် ပြောင်းပေးသည်) သို့ Settings > Camera > Formats > Most Compatible သို့ ပြောင်းပါ',
          'field.email': 'အီးမေးလ်',
          'email.ph': 'အီးမေးလ်ထည့်ပါ',
          'email.save': 'သိမ်းရန်',
          'email.saved': 'အီးမေးလ်သိမ်းပြီး',
          'email.invalid': 'အီးမေးလ်ပုံစံမမှန်ပါ',
          'email.error': 'သိမ်းမရပါ ထပ်စမ်းပါ',
          'profile.view_photo': 'ပရိုဖိုင်ဓာတ်ပုံကို ကြီးကြီးကြည့်ရန်',
          'profile.close_photo': 'ဓာတ်ပုံပိတ်ရန်',
          'profile.zoom_group': 'ပုံအရွယ်အစား ချိန်ရန်',
          'profile.zoom_in': 'ချဲ့ရန်',
          'profile.zoom_out': 'ချုံ့ရန်',
          'profile.zoom_reset': '100% ပြန်ချိန်ရန်',
          'pwd.change': 'စကားဝှက်ပြောင်းရန်',
          'pwd.verify': 'စစ်ဆေးရန်',
          'pwd.verified': 'မှတ်ပုံတင်နံပါတ် မှန်ကန်သည်',
          'pwd.idcard_wrong': 'မှတ်ပုံတင်နံပါတ် မမှန်ပါ',
          'pwd.masked': 'စကားဝှက် ဖုံးထားသည်',
          'pwd.show': 'ပြရန်',
          'pwd.hide': 'ဖျောက်ရန်',
          'pwd.cancel': 'ပယ်ဖျက်ရန်',
          'recruit.kicker': 'ဝန်ထမ်းခေါ်ယူခြင်း',
          'recruit.title': 'ဝန်ထမ်းခေါ်ယူခြင်း',
          'recruit.note': 'ဝန်ထမ်းခေါ်ယူစနစ်ကို လက်ရှိ တီထွင်နေဆဲဖြစ်သည်။',
          'assessment.kicker': 'အကဲဖြတ်ခြင်း',
          'assessment.title': 'SUPAVUT Assessment',
          'assessment.pending': 'အပ်ဒိတ်ကို စောင့်ဆိုင်းနေသည်',
          'assessment.clearEvaluators': 'အကဲဖြတ်သူများကို ရှင်းရန်',
          'assessment.clearEvaluatorsConfirm': 'ဝန်ထမ်းအားလုံး၏ အကဲဖြတ်သူများကို ရှင်းမလား?\nအမှတ်နှင့် အဆင့် မဖျက်ပါ။ အကဲဖြတ်သူကွက်များသာ ရှင်းပါမည်။',
          'assessment.clearingEvaluators': 'ရှင်းနေသည်...',
          'assessment.clearEvaluatorsError': 'အကဲဖြတ်သူများ ရှင်းမရပါ',
          'assessment.clearEvaluatorsNetwork': 'ချိတ်ဆက်မရပါ',
          'assessment.note': 'SUPAVUT Assessment စနစ်ကို လက်ရှိ တီထွင်နေဆဲဖြစ်သည်။',
          'assessment.home.kicker': 'စီမံခန့်ခွဲသူအတွက် ကြေညာချက်',
          'assessment.home.titleAdmin': 'Admin မင်္ဂလာပါ',
          'assessment.home.titleHr': 'HR အဖွဲ့ မင်္ဂလာပါ',
          'assessment.home.subtitle': 'SUPAVUT Assessment ကို စီမံခန့်ခွဲသူများနှင့် အမှတ်စီမံခန့်ခွဲခွင့်ရ HR များအတွက် ကြေညာချက်နှင့် စတင်ရန်နေရာ။',
          'assessment.home.primaryScores': 'အမှတ်စီမံရန်',
          'assessment.home.primaryOpenRound': 'အကဲဖြတ်ကာလ ဖွင့်ရန်',
          'assessment.home.rounds': 'အကဲဖြတ်ကာလများ',
          'assessment.home.settings': 'ခွင့်ပြုချက် သတ်မှတ်ရန်',
          'assessment.home.currentUser': 'လက်ရှိအသုံးပြုသူ',
          'assessment.home.role': 'စနစ်အတွင်း role',
          'assessment.home.noticeKicker': 'Admin Notice',
          'assessment.home.noticeTitle': 'Assessment ကြေညာချက်များ',
          'assessment.home.updated': 'အပ်ဒိတ်',
          'assessment.home.notice1Title': 'အမှတ်များ မစီမံမီ အကဲဖြတ်ကာလကို ဖွင့်ပါ',
          'assessment.home.notice1Body': 'အမှတ်များ တင်သွင်းခြင်း၊ အကဲဖြတ်သူအဆင့်များ ပြင်ဆင်ခြင်း သို့မဟုတ် ရာထူးအဆင့်များ သတ်မှတ်ခြင်းမပြုမီ ဖွင့်ထားသော အကဲဖြတ်ကာလတစ်ခု လိုအပ်သည်။',
          'assessment.home.notice2Title': 'HR ခွင့်ပြုချက်သည် အမှတ်စီမံရန်အတွက်ဖြစ်သည်',
          'assessment.home.notice2Body': 'HR အဖြစ်ထည့်ထားသောဝန်ထမ်းများသည် Assessment ထဲဝင်ပြီး အမှတ်များကို စီမံနိုင်သည်။ ခွင့်ပြုချက်ပေးခြင်းနှင့် စနစ်သတ်မှတ်ချက်များသည် admin အတွက်သာဖြစ်သည်။',
          'assessment.home.notice3Title': 'အတည်ပြုထားသော Employee Master ဒေတာကိုသာအသုံးပြုပါ',
          'assessment.home.notice3Body': 'Assessment သည် Insight မှ ဝန်ထမ်းဒေတာကိုသာဖတ်ပြီး လစာ သို့မဟုတ် payroll ဒေတာကို ဤ module ထဲမထည့်ပါ။',
          'assessment.home.workflowKicker': 'Workflow',
          'assessment.home.workflowTitle': 'အကြံပြုလုပ်ငန်းစဉ်',
          'assessment.home.flow1Title': 'အကဲဖြတ်ကာလ ဖွင့်ရန်',
          'assessment.home.flow1Body': 'အကဲဖြတ်ကာလအသစ်အတွက် ဒေတာမထည့်မီ ကာလအမည်နှင့် နှစ်ကို သတ်မှတ်ပါ။',
          'assessment.home.flow2Title': 'ဝန်ထမ်းဒေတာနှင့် အမှတ်များစီမံရန်',
          'assessment.home.flow2Body': 'ဖိုင်တင်သွင်းခြင်း၊ အကဲဖြတ်သူအဆင့်ပြင်ခြင်း၊ အမှတ်ကော်လံသတ်မှတ်ခြင်းနှင့် တွက်ချက်ရလဒ်စစ်ခြင်း။',
          'assessment.home.flow3Title': 'မေးခွန်းများသတ်မှတ်ရန်',
          'assessment.home.flow3Body': 'Input ကော်လံနှင့် အကဲဖြတ်သူအဆင့်အလိုက် မေးခွန်းများကို ပြင်ဆင်ပါ။',
          'assessment.home.flow4Title': 'ရလဒ်စစ်ရန်',
          'assessment.home.flow4Body': 'ရလဒ်များကို အကဲဖြတ်ကာလနှင့် နှစ်အလိုက် ကြည့်ပြီး အဆင်သင့်ဖြစ်သည့်အခါ အနှစ်ချုပ်ဖိုင်ကို ဒေါင်းလုဒ်လုပ်ပါ။',
          'assessment.home.statusKicker': 'Status',
          'assessment.home.statusTitle': 'လက်ရှိအခြေအနေ',
          'assessment.home.statusOpenRound': 'ဖွင့်ထားသော အကဲဖြတ်ကာလ',
          'assessment.home.noOpenRound': 'ဖွင့်ထားသော အကဲဖြတ်ကာလ မရှိသေးပါ',
          'assessment.home.statusLatestRound': 'နောက်ဆုံး အကဲဖြတ်ကာလ',
          'assessment.home.statusHr': 'ခွင့်ပြုထားသော HR',
          'assessment.home.statusBoxes': 'အဓိကအမှတ်ခေါင်းစဉ်များ',
          'assessment.home.statusEmployees': 'active ဝန်ထမ်းများ',
          'assessment.home.scopeKicker': 'Permission',
          'assessment.home.scopeTitle': 'ခွင့်ပြုချက်အကျယ်အဝန်း',
          'assessment.home.scopeAdmin': 'HR ခွင့်ပြုချက်ပေးခြင်းနှင့် ရာထူးသတ်မှတ်ချက်များအပါအဝင် စာမျက်နှာအားလုံးကို ဝင်နိုင်သည်။',
          'assessment.home.scopeHr': 'အကဲဖြတ်ကာလများ၊ အမှတ်များ၊ မေးခွန်းများနှင့် ရလဒ်များကို စီမံနိုင်သော်လည်း စနစ်ဆက်တင်စာမျက်နှာကို ဝင်မကြည့်နိုင်ပါ။',
          'assessment.evaluate.title': 'ဝန်ထမ်းအကဲဖြတ်ရန်',
          'assessment.evaluate.formTitle': 'ဝန်ထမ်းအကဲဖြတ်ဖောင်',
          'assessment.evaluate.pageTitle': 'အကဲဖြတ်ရန် ဝန်ထမ်းစာရင်း',
          'assessment.evaluate.help': 'စာရင်းမှ ဝန်ထမ်းတစ်ဦးကို ရွေးပြီး ဖွင့်ထားသော အကဲဖြတ်ကာလအတွက် အကဲဖြတ်မှုကို စတင်ရန် သို့မဟုတ် ဆက်လက်လုပ်ဆောင်ရန် နှိပ်ပါ။',
          'assessment.evaluate.backToList': 'အကဲဖြတ်စာရင်းသို့ ပြန်ရန်',
          'assessment.evaluate.role.directSupervisor': 'တိုက်ရိုက် ကြီးကြပ်သူ',
          'assessment.evaluate.role.division': 'ဌာနအဆင့် အကဲဖြတ်သူ',
          'assessment.evaluate.role.directAndDivision': 'တိုက်ရိုက် ကြီးကြပ်သူ / ဌာနအဆင့် အကဲဖြတ်သူ',
          'assessment.evaluate.itemUnit': 'ခု',
          'assessment.evaluate.employee': 'အကဲဖြတ်မည့် ဝန်ထမ်း',
          'assessment.evaluate.position': 'ရာထူး',
          'assessment.evaluate.department': 'ဌာန',
          'assessment.evaluate.evaluator': 'အကဲဖြတ်သူ',
          'assessment.form.fullName': 'အမည် :',
          'assessment.form.employeeCode': 'ဝန်ထမ်းကုဒ် :',
          'assessment.form.totalScore': 'စုစုပေါင်းရမှတ် :',
          'assessment.form.position': 'ရာထူး :',
          'assessment.form.department': 'ဌာန :',
          'assessment.employeeCode': 'ဝန်ထမ်းကုဒ်',
          'assessment.fullName': 'အမည်',
          'assessment.levelWord': 'အဆင့်',
          'assessment.openRound': 'ဖွင့်ထားသောအကြိမ်:',
          'assessment.review.title': 'စစ်ဆေးရန် ရလဒ်စာရင်း',
          'assessment.review.help': 'ဖွင့်ထားသောအကြိမ်တွင် သင့်ကို အဆင့် ၃ သို့မဟုတ် ၄ စစ်ဆေးသူအဖြစ် သတ်မှတ်ထားသော ဝန်ထမ်းများသာ ပြသည်',
          'assessment.review.yourLevel': 'သင်စစ်ဆေးသည့်အဆင့်',
          'assessment.review.tableLabel': 'ရလဒ်စစ်ဆေးမှုဇယား',
          'assessment.review.levelOneTotal': 'အကဲဖြတ်အမှတ်',
          'assessment.review.selfScore': 'မိမိအကဲဖြတ်ရမှတ်',
          'assessment.review.noScore': 'ရမှတ်မရှိသေးပါ',
          'assessment.codeShort': 'ကုဒ်',
          'assessment.levelShort': 'အဆင့်',
          'assessment.filterWord': 'စစ်ထုတ်',
          'assessment.hierLevel': 'အဆင့်ဆင့်',
          'assessment.downloadExcel': '⬇ Excel ဒေါင်းလုဒ်',
          'assessment.import': '⬆ တင်သွင်း',
          'assessment.clearFilter': 'စစ်ထုတ်မှုရှင်းရန်',
          'assessment.showingThisPage': 'ဤစာမျက်နှာ',
          'assessment.totalWord': 'စုစုပေါင်း',
          'assessment.peopleUnit': 'ဦး',
          'assessment.noEmployee': 'ဝန်ထမ်းမတွေ့ပါ',
          'assessment.legendPastel': 'အရောင်နု = အချက်အလက်အုပ်စု',
          'assessment.legendDark': 'ပိုရင့်သောအရောင် = ဤအဆင့်တွင် မတွက်ချက်ပါ',
          'assessment.scores.step1Sub': 'ရာထူးအဆင့်သတ်မှတ် · ဇယားတွင် ရမှတ်နှင့် အဆင့်ဆင့်ပြင် · Excel ဒေါင်းလုဒ်/တင်သွင်း',
          'assessment.scores.h11': '၁.၁ ရာထူးအဆင့်',
          'assessment.scores.h11Hint': 'ရာထူးများကို အဆင့်သတ်မှတ်ပါ · အဆင့် ၀ = အကဲမဖြတ်ပါ · + အဆင့်ထည့်, ✕ ဖျက် (နံပါတ်အလိုအလျောက်ပြန်စီသည်)',
          'assessment.scores.unleveled': 'အဆင့်မသတ်မှတ်ရသေး',
          'assessment.scores.h12': '၁.၂ ဝန်ထမ်းအချက်အလက်',
          'assessment.scores.h12Hint': 'အကဲဖြတ်သူ (အဆင့် ၁–၄) ကို ကွက်လပ်တွင်တိုက်ရိုက်ပြင်နိုင် · သို့မဟုတ် ဒေါင်းလုဒ်ပြင်ပြီး ပြန်တင်ပါ',
          'assessment.scores.step2': 'ကော်လံစီမံ',
          'assessment.scores.step2Sub': 'KPI ရမှတ်ကော်လံ — ကိုယ်တိုင်ဖန်တီး သို့မဟုတ် ကော်လံဖိုင်တင်သွင်း',
          'assessment.scores.h21': '၂.၁ ကော်လံဖန်တီး / တင်သွင်း',
          'assessment.scores.h21Hint': 'ကိုယ်တိုင်ဖန်တီး သို့မဟုတ် ဖိုင်တင်ပါ (အတန်း ၁ = ခေါင်းစဉ်ကြီး, အတန်း ၂ = ကော်လံခွဲ)',
          'assessment.scores.importColumns': '⬆ ကော်လံဖိုင်တင်သွင်း',
          'assessment.scores.mainColPh': 'ခေါင်းစဉ်ကြီးအမည် ဥပမာ Attendance',
          'assessment.scores.addMainCol': '+ ခေါင်းစဉ်ကြီးထည့်',
          'assessment.scores.h22': '၂.၂ ကော်လံစစ်ဆေးဇယား (Excel မြင်ကွင်း)',
          'assessment.scores.h22Hint': 'အထက်ကတ်များအတိုင်း ချက်ချင်းပြောင်းသည်',
          'assessment.scores.step3': 'အချိုးနှင့် တွက်ချက်မှု',
          'assessment.scores.step3Sub': 'အဆင့် ၁–၅ အလိုက် အချိုးသတ်မှတ် — အဆင့်တစ်ခုလျှင် စုစုပေါင်း ၁၀၀ · အပို (+/-)',
          'assessment.scores.h31': '၃.၁ အဆင့်အလိုက်အချိုးသတ်မှတ်',
          'assessment.scores.h31Hint': 'အဆင့်တစ်ခုလျှင် စုစုပေါင်း ၁၀၀ · အပို = +/- သီးခြား · မတွက်ချက် = ထည့်မတွက်ပါ',
          'assessment.scores.h32': '၃.၂ ကော်လံခွဲ တွက်ချက်ခြင်း',
          'assessment.scores.h32Hint': 'ရမှတ်အပြည့် = အချိုးဖြင့်တွက် · Attendance = ရမှတ်နုတ်/အဆင့်ကန့်သတ် · Input = အကဲဖြတ်သူဖြည့်သွင်း · အပိုရမှတ် = တိုက်ရိုက် +/-',
          'assessment.scores.step4': 'ရမှတ်တင်သွင်းခြင်း',
          'assessment.scores.step4Sub': 'ရမှတ်တစ်ကွက်ချင်း တင်သွင်း / ပြင်ဆင်',
          'assessment.scores.h41': '၄.၁ ရမှတ်တင်သွင်း / ပြင်ဆင်',
          'assessment.scores.h41Hint': 'အကဲဖြတ်သူ ၄ ဆင့်ပြည့်မှ ကွက်လပ်ပြင်နိုင်သည် · အကဲဖြတ်သူများကို ကော်မာဖြင့်ခြား ဥပမာ 8.5,7.5 · N/A ရိုက်ပါက ထိုကွက်ကို မတွက်ပါ · တင်သွင်းဖိုင်တွင် ကွက်လပ် = မူလတန်ဖိုးဆက်ထား',
          'assessment.scores.importScores': '⬆ ရမှတ်တင်သွင်း',
          'assessment.scores.noColumns': 'ရမှတ်ကော်လံမရှိသေးပါ — အပိုင်း ၂ တွင် အရင်ဖန်တီး/တင်သွင်းပါ',
          'assessment.questions.hint2': 'ရွေးထားသော ဝန်ထမ်းအဆင့်၏ ပုံစံတွင် ပြမည့် ဖော်ပြချက်ကို ရေးပါ · ခေါင်းစဉ်ကြီးနှင့် ကော်လံခွဲ နှစ်မျိုးလုံး ထည့်နိုင် · ပြ/ဖျောက် ခလုတ်က အကဲဖြတ်သူ မြင်ရမည်မမြင်ရမည်ကို ထိန်းသည် · အလိုအလျောက် သိမ်းသည်',
          'assessment.questions.previewFor': 'ပုံစံတွင် ပြမည့် နမူနာ — ဝန်ထမ်း',
          'assessment.questions.topicWord': 'ခေါင်းစဉ်',
          'assessment.questions.noSubColumns': 'ဤခေါင်းစဉ်တွင် ကော်လံခွဲ မရှိသေးပါ',
          'assessment.questions.hint3': 'ခေါင်းစဉ် သို့မဟုတ် ကော်လံဘေးရှိ ⓘ ကိုနှိပ်၍ ဖော်ပြချက်ရေးပြီး အကဲဖြတ်သူမြင်ရမည်ကို ဖွင့်/ပိတ်ပါ · အစိမ်း = ပြသည် · အနီ = ရေးထားပြီး ဖျောက်ထား · မီးခိုး = မရှိသေး',
          'assessment.questions.autosave': 'အလိုအလျောက် သိမ်းသည်',
          'assessment.questions.noWeights': 'ဤအဆင့်အတွက် အချိုးမသတ်မှတ်ရသေးပါ — စီမံခန့်ခွဲမှု အပိုင်း ၃.၁ တွင် အရင်သတ်မှတ်ပါ',
          'assessment.questions.demoName': '(နမူနာ) ဦးစမ်ချိုင်း ဂျိုင်ဒီ',
          'assessment.questions.demoPosition': '(နမူနာ) အဆင့်ရာထူး',
          'assessment.questions.scaleTitle': 'ရမှတ်ရွေးချယ်စရာ',
          'assessment.questions.scaleHint': 'အကဲဖြတ်သူများသည် ဤရွေးချယ်စရာများကို အစဉ်လိုက်မြင်ပြီး ရွေးချယ်စရာအတွင်းမှ ရမှတ်ကို ရွေးမည်',
          'assessment.questions.scaleAdd': '+ ရွေးချယ်စရာ ထည့်ရန်',
          'assessment.questions.scaleColName': 'ရွေးချယ်စရာ',
          'assessment.questions.scaleColScores': 'ရမှတ်',
          'assessment.questions.scaleReset': 'မူလသို့ ပြန်ထားရန်',
          'assessment.questions.scaleSave': 'သိမ်းရန်',
          'assessment.evaluate.status': 'အခြေအနေ',
          'assessment.evaluate.action': 'အကဲဖြတ်',
          'assessment.evaluate.status.done': 'အကဲဖြတ်ပြီး',
          'assessment.evaluate.status.partial': 'မပြီးသေးပါ',
          'assessment.evaluate.status.pending': 'စောင့်ဆိုင်းနေသည်',
          'assessment.evaluate.button.edit': 'ကြည့် / ပြင်',
          'assessment.evaluate.button.continue': 'ဆက်လက်အကဲဖြတ်',
          'assessment.evaluate.button.start': 'အကဲဖြတ်',
          'assessment.evaluate.viewPhoto': 'ဓာတ်ပုံကြည့်ရန်',
          'assessment.evaluate.zoomOpen': 'ချဲ့ကြည့်ရန် နှိပ်ပါ',
          'assessment.sort.aria': 'စီရန်',
          'assessment.sort.code': 'ဝန်ထမ်းကုဒ်အလိုက် စီရန်',
          'assessment.sort.rankDesc': 'ရာထူးအလိုက် စီရန် (မြင့် → နိမ့်)',
          'assessment.sort.rankAsc': 'ရာထူးအလိုက် စီရန် (နိမ့် → မြင့်)',
          'assessment.evaluate.zoomHint': 'မောက်စ်ဘီးလှည့်၍ ဇူးမ် · နှစ်ချက်နှိပ်၍ ချဲ့/ချုံ့',
          'assessment.evaluate.profileTitle': 'ဝန်ထမ်းပရိုဖိုင်ဓာတ်ပုံ',
          'assessment.evaluate.employeeCode': 'ဝန်ထမ်းကုဒ်',
          'assessment.evaluate.fullName': 'အမည်',
          'assessment.evaluate.placeholderTitle': 'အကဲဖြတ်ဖောင် နေရာ',
          'assessment.evaluate.placeholderHelp': 'ဤ role အတွက် မေးခွန်းများနှင့် အမှတ်ကွက်များ ချိတ်ဆက်ရန် ပြင်ဆင်ထားသည်',
          'assessment.common.manage': 'စီမံရန်',
          'assessment.common.questions': 'မေးခွန်းများ',
          'assessment.common.results': 'ရလဒ်များ',
          'assessment.common.round': 'ကာလ',
          'assessment.common.summary': 'အကဲဖြတ်မှု အနှစ်ချုပ်',
          'assessment.common.level': 'အဆင့်',
          'assessment.common.employeeCount': 'ဝန်ထမ်းအရေအတွက်',
          'assessment.common.notAssessed': 'အကဲမဖြတ်ပါ',
          'assessment.common.notCalculated': 'မတွက်ချက်ပါ',
          'assessment.common.noOpenRound': 'ဖွင့်ထားသောကာလ မရှိပါ',
          'assessment.common.perPage': 'တစ်မျက်နှာလျှင်',
          'assessment.common.clear': 'ရှင်းရန်',
          'assessment.common.apply': 'အတည်ပြုရန်',
          'assessment.common.selectAll': 'အားလုံးရွေးရန်',
          'assessment.common.loadingFilters': 'စစ်ထုတ်မှုများ ဖတ်နေသည်',
          'assessment.common.ok': 'အိုကေ',
          'assessment.common.success': 'အောင်မြင်သည်',
          'assessment.common.notice': 'အသိပေးချက်',
          'assessment.common.cancel': 'မလုပ်တော့ပါ',
          'assessment.common.delete': 'ဖျက်ရန်',
          'assessment.common.showing': 'ပြသနေသည်',
          'assessment.common.people': 'ဦး',
          'assessment.common.afterFilter': 'စစ်ထုတ်ပြီး',
          'assessment.common.code': 'ကုဒ်',
          'assessment.common.year': 'နှစ်',
          'assessment.common.roundName': 'ကာလအမည်',
          'assessment.common.openedAt': 'ဖွင့်သည့်အချိန်',
          'assessment.common.by': 'ဖွင့်သူ',
          'assessment.common.section': 'ခေါင်းစဉ်',
          'assessment.common.page': 'စာမျက်နှာ',
          'assessment.form.level': 'အဆင့်',
          'assessment.form.evaluate': 'အကဲဖြတ်ရန်',
          'assessment.form.notAssessed': 'မအကဲဖြတ်ပါ',
          'assessment.form.selectedScore': 'ရွေးထားသောအမှတ် {score} / ၁၀',
          'assessment.form.noScoreSelected': 'အမှတ် မရွေးရသေးပါ',
          'assessment.form.networkError': 'ချိတ်ဆက်၍မရပါ',
          'assessment.form.scoreRangeError': 'အမှတ်သည် ၀ မှ ၁၀ အတွင်း ဖြစ်ရမည်',
          'assessment.form.saveError': 'သိမ်း၍မရပါ',
          'assessment.self.openRound': 'ဖွင့်ထားသောကာလ',
          'assessment.self.year': 'နှစ်',
          'assessment.self.pendingForm': '“ကိုယ်တိုင်အကဲဖြတ်ခြင်း” ကာလကို ဖွင့်ထားပြီးဖြစ်သည်။ ဖောင်ပုံစံကို သတ်မှတ်နေဆဲဖြစ်သောကြောင့် ကိုယ်တိုင်အကဲဖြတ်ဖောင်ကို မဖွင့်ရသေးပါ။',
          'assessment.self.adminNav': 'ကိုယ်တိုင်အကဲဖြတ်ခြင်း ဆက်တင်များ',
          'assessment.self.selectedCount': 'ပါဝင်သူများ',
          'assessment.self.manageTab': 'စီမံရန်',
          'assessment.self.questionsTab': 'မေးခွန်းသတ်မှတ်ရန်',
          'assessment.self.resultsTab': 'ရလဒ်များ',
          'assessment.self.h11': '1.1 ရာထူးအဆင့်',
          'assessment.self.h11Hint': 'ဤကိုယ်တိုင်အကဲဖြတ်ကာလအတွက် ဝန်ထမ်းရာထူးအလိုက် အဆင့် ၁–၅ သတ်မှတ်ပါ။',
          'assessment.self.unassigned': 'အဆင့် မသတ်မှတ်ရသေးပါ',
          'assessment.self.addLevel': 'အဆင့် ထည့်ရန်',
          'assessment.self.questionsHint2': 'ဤနေရာတွင် သတ်မှတ်သော မေးခွန်းများသည် ဤအဆင့်ဝန်ထမ်းများ၏ ပုံစံတွင် ပေါ်မည်',
          'assessment.self.questionPlaceholder': 'မေးခွန်းစာသား',
          'assessment.self.choicePlaceholder': 'ရွေးချယ်စရာ',
          'assessment.self.choiceNo': 'ရွေးချယ်စရာ',
          'assessment.self.naNote': 'ရမှတ်တွက်ချက်ခြင်း မပါဝင်',
          'assessment.self.needChoice': 'မေးခွန်းတိုင်းတွင် စာသားနှင့် အနည်းဆုံး ရွေးချယ်စရာ ၁ ခု လိုအပ်သည်',
          'assessment.self.deleteLevelConfirm': 'ဤအဆင့်ကို ဖျက်မလား? ၎င်း၏မေးခွန်းများ ပျက်ပြီး အထက်အဆင့်များ ဆင်းလာမည်',
          'assessment.self.deleteLevelBusy': 'ဤအဆင့်တွင် ရာထူး {count} ခုရှိသည် · ဖျက်ပါက ရာထူးများ အဆင့်မသတ်မှတ်သို့ ပြန်သွားပြီး မေးခွန်းများ ပျက်မည် ဆက်လုပ်မလား',
          'assessment.self.deleteLevelError': 'အဆင့် ဖျက်၍မရပါ',
          'assessment.self.levelLabel': 'အဆင့်',
          'assessment.self.h12': '1.2 ဝန်ထမ်းအချက်အလက်',
          'assessment.self.h12Hint': 'ကိုယ်တိုင်အကဲဖြတ်ရမည့် ဝန်ထမ်းများကို ရွေးပါ။ အချက်အလက်ကို လက်ရှိ Employee master မှ ရယူထားသည်။',
          'assessment.self.selectAllVisible': 'မြင်နေရသမျှ ရွေးရန်',
          'assessment.self.clearAllVisible': 'အားလုံး ရွေးမထားရန်',
          'assessment.self.saveSelection': 'ပါဝင်သူများ သိမ်းရန်',
          'assessment.self.participantColumn': 'ကိုယ်တိုင်အကဲဖြတ်',
          'assessment.self.selected': 'ရွေးထားသည်',
          'assessment.self.notSelected': 'မရွေးထားပါ',
          'assessment.self.unsavedChanges': 'မသိမ်းရသေးသော စာရင်း {count} ခု',
          'assessment.self.saving': 'သိမ်းနေသည်...',
          'assessment.self.saved': 'ပါဝင်သူများကို သိမ်းပြီးပါပြီ',
          'assessment.self.saveFailed': 'ပါဝင်သူများကို သိမ်း၍မရပါ',
          'assessment.self.questionsTitle': 'အဆင့်အလိုက် မေးခွန်းများ',
          'assessment.self.questionsHint': 'TH / EN / MY မေးခွန်းများကို ဖန်တီးပြီး အမှတ်ပြည့်သတ်မှတ်ကာ အဆင့်အလိုက် ရွေးချယ်စရာများကို တစ်ခုချင်း ထည့်ပါ။',
          'assessment.self.structureOnly': 'မျက်နှာပြင်ဖွဲ့စည်းပုံ',
          'assessment.self.questionColumn': 'မေးခွန်း',
          'assessment.self.answerTypeColumn': 'အဖြေပုံစံ',
          'assessment.self.statusColumn': 'အခြေအနေ',
          'assessment.self.questionsPending': 'ဤအဆင့်အတွက် မေးခွန်း မသတ်မှတ်ရသေးပါ။',
          'assessment.self.addQuestion': 'မေးခွန်းထည့်ရန်',
          'assessment.self.defaultChoicesHint': 'မေးခွန်းသိမ်းပြီးနောက် အဖြေရွေးချယ်စရာများ ထည့်နိုင်သည်။',
          'assessment.self.saveQuestion': 'မေးခွန်းသိမ်းရန်',
          'assessment.self.questionShort': 'မေးခွန်း',
          'assessment.self.questionDetailHint': 'မေးခွန်းကို ဘာသာစကားသုံးမျိုးဖြင့် ပြည့်စုံစွာ ဖြည့်ပြီး တွက်ချက်မည့် အမှတ်ပြည့်ကို သတ်မှတ်ပါ။',
          'assessment.self.questionFullScore': 'မေးခွန်းအမှတ်ပြည့်',
          'assessment.self.questionFullScoreHint': 'ဤမေးခွန်း၏ စားကိန်းအဖြစ် သုံးသည်။ ဥပမာ အမှတ်ပြည့် ၅ မှ ၄ ရလျှင် ၈၀% ဖြစ်သည်။',
          'assessment.self.saveQuestionHint': 'မေးခွန်းသိမ်းပြီးနောက် အဖြေရွေးချယ်စရာများ ထည့်နိုင်သည်။',
          'assessment.self.choicesCount': 'ရွေးချယ်စရာ',
          'assessment.self.notReady': 'အသုံးပြုရန် မပြည့်စုံသေးပါ',
          'assessment.self.deleteQuestion': 'မေးခွန်းဖျက်ရန်',
          'assessment.self.saveChanges': 'ပြင်ဆင်မှု သိမ်းရန်',
          'assessment.self.choicesTitle': 'အဖြေရွေးချယ်စရာများ',
          'assessment.self.choiceScoreHint': 'အမှတ်ပါ ရွေးချယ်စရာ၏ အမှတ်သည် မေးခွန်းအမှတ်ပြည့်ထက် မကျော်ရပါ။',
          'assessment.self.optionShort': 'ရွေးချယ်စရာ',
          'assessment.self.choiceType': 'ရွေးချယ်စရာအမျိုးအစား',
          'assessment.self.choiceTypeScore': 'အမှတ်ကို ရိုက်ထည့်ရန်',
          'assessment.self.choiceTypeHint': 'ရွေးချယ်စရာအမျိုးအစားကို ရွေးပြီး TH / EN / MY စာသားကို ဖြည့်ပါ။',
          'assessment.self.choiceScore': 'အမှတ်',
          'assessment.self.saveChoice': 'ရွေးချယ်စရာသိမ်းရန်',
          'assessment.self.deleteChoice': 'ရွေးချယ်စရာဖျက်ရန်',
          'assessment.self.addChoice': 'ရွေးချယ်စရာထည့်ရန်',
          'assessment.self.noChoices': 'ရွေးချယ်စရာမရှိသေးပါ။ ဤမေးခွန်းကို ဝန်ထမ်းအကဲဖြတ်ဖောင်တွင် မပြသသေးပါ။',
          'assessment.self.noQuestions': 'ဤအဆင့်တွင် မေးခွန်းမရှိသေးပါ',
          'assessment.self.noQuestionsHint': 'ဖောင်စတင်တည်ဆောက်ရန် “မေးခွန်းထည့်ရန်” ကို နှိပ်ပါ။',
          'assessment.self.deleteConfirm': 'ဤစာရင်းကို ဖျက်ရန် သေချာပါသလား။',
          'assessment.self.resultsTitle': 'ကိုယ်တိုင်အကဲဖြတ် ရလဒ်များ',
          'assessment.self.resultsHint': 'Admin ရွေးထားသော ဝန်ထမ်းများ၏ စုစုပေါင်းအမှတ်နှင့် မေးခွန်းတစ်ခုချင်း ရာခိုင်နှုန်းကို ပြသည်။',
          'assessment.self.exportTitle': 'ကိုယ်တိုင်အကဲဖြတ် ရလဒ် Export',
          'assessment.self.exportHint': 'ရွေးထားသောကာလ၏ ရလဒ် Excel ဖိုင်။',
          'assessment.self.exportButton': '⬇ ဒေါင်းလုဒ်',
          'assessment.self.roundPicker': 'ဒေါင်းလုဒ်လုပ်မည့် ကာလကို ရွေးပါ · ● = ဖွင့်ထားသောကာလ',
          'assessment.self.roundOpenMark': '● = ဖွင့်ထားသောကာလ',
          'assessment.self.scoreColumn': 'ကိုယ်တိုင်အကဲဖြတ်အမှတ်',
          'assessment.self.noParticipants': 'ဝန်ထမ်း မတွေ့ပါ',
          'assessment.self.userReadyTitle': 'ဤကာလတွင် သင် ကိုယ်တိုင်အကဲဖြတ်ခွင့် ရရှိထားသည်',
          'assessment.self.userPendingTitle': 'ပါဝင်သူစာရင်း အဆင်သင့်ဖြစ်ပါပြီ',
          'assessment.self.userPendingText': 'Admin က သင့်ကို ရွေးထားပြီးဖြစ်သည်။ မေးခွန်းနှင့် အဖြေသိမ်းခြင်းကို နောက်တစ်ဆင့်တွင် ဖွင့်ပေးမည်။',
          'assessment.self.formTitle': 'ကိုယ်တိုင်အကဲဖြတ်ခြင်း',
          'assessment.self.lastSaved': 'နောက်ဆုံးသိမ်းထားချိန်',
          'assessment.self.formInstructionTitle': 'အကဲဖြတ်နည်း',
          'assessment.self.formInstruction': 'မေးခွန်းအားလုံးကို ဖြေပါ။ ရွေးထားသောအမှတ်ကို Admin သတ်မှတ်ထားသည့် မေးခွန်းအမှတ်ပြည့်နှင့် နှိုင်းယှဉ်တွက်ချက်ပြီး N/A ကို မတွက်ပါ။',
          'assessment.self.levelMissing': 'Admin က သင့်ရာထူးအဆင့်ကို မသတ်မှတ်ရသေးပါ။',
          'assessment.self.noFormQuestions': 'သင့်အဆင့်အတွက် ကိုယ်တိုင်အကဲဖြတ် မေးခွန်း မရှိသေးပါ။',
          'assessment.self.answered': 'ဖြေပြီး',
          'assessment.self.totalPercent': 'စုစုပေါင်းအမှတ်',
          'assessment.self.submitForm': 'ကိုယ်တိုင်အကဲဖြတ်မှု သိမ်းရန်',
          'assessment.questions.evaluatorLevel': 'အကဲဖြတ်သူအဆင့်',
          'assessment.questions.deleteTitle': 'မေးခွန်းဖျက်ရန်',
          'assessment.results.year': 'နှစ်',
          'assessment.results.importHint': 'ဖွင့်ထားသောကာလ၏ Template — စနစ်ပြင်ပတွင် ဖြည့်ပြီး ပြန်တင်သွင်းပါ။',
          'assessment.results.importButton': '⬆ တင်သွင်းရန်',
          'assessment.results.exportTitle': 'ရလဒ် Export',
          'assessment.results.downloadButton': '⬇ ဒေါင်းလုဒ်',
          'assessment.results.editableHint': 'ကွက်တစ်ခုချင်း၏ စည်းမျဉ်းအတိုင်း အမှတ်ဖြည့်နိုင်သည် · N/A သည် ထိုကွက်ကို မတွက်ပါ။',
          'assessment.results.readonlyHint': 'ဤကာလ ပိတ်ပြီးဖြစ်သည် — ကြည့်ရန်သာ',
          'assessment.scores.addLevel': '＋ အဆင့်ထည့်ရန်',
          'assessment.scores.deleteMainTitle': 'အဓိကခေါင်းစဉ်နှင့် အတွင်းရှိ ကော်လံခွဲများကို ဖျက်ရန်',
          'assessment.scores.deleteSubTitle': 'ကော်လံခွဲ ဖျက်ရန်',
          'assessment.scores.editMainTitle': 'အဓိကခေါင်းစဉ်အမည် ပြင်ရန်',
          'assessment.scores.editSubTitle': 'ကော်လံခွဲအမည် ပြင်ရန်',
          'assessment.scores.addSubPlaceholder': '+ ကော်လံခွဲ ထည့်ရန်...',
          'assessment.form.performanceTitle': 'ဝန်ထမ်းစွမ်းဆောင်ရည် အကဲဖြတ်ဖောင်',
          'assessment.form.scoreWeightTitle': 'အမှတ်အချိုး ခေါင်းစဉ်များ',
          'assessment.form.noScoreWeights': 'အမှတ်အချိုး ခေါင်းစဉ် မရှိသေးပါ',
          'assessment.form.performanceSection': 'စွမ်းဆောင်ရည် အကဲဖြတ်ခြင်း',
          'assessment.form.noScoreColumns': 'အမှတ်ကော်လံ မရှိသေးပါ',
          'assessment.home.noOpenRoundTitle': 'ဖွင့်ထားသော အကဲဖြတ်ကာလ မရှိသေးပါ',
          'assessment.home.noOpenRoundBody': 'HR က အကဲဖြတ်ကာလဖွင့်သောအခါ သင်၏ အဆင့်ဆင့် ၁ နှင့် ၂ အကဲဖြတ်သူများကို ဤနေရာတွင် ပြသမည်။',
          'assessment.home.noEvaluatorsTitle': 'အကဲဖြတ်သူ မရှိသေးပါ',
          'assessment.home.noEvaluatorsBody': 'ဤကာလအတွက် သင့်ဒေတာတွင် အဆင့်ဆင့် ၁ သို့မဟုတ် ၂ အတွက် ဝန်ထမ်းကုဒ်နှင့် အမည် မသတ်မှတ်ရသေးပါ။',
          'assessment.home.me': 'ကျွန်ုပ်',
          'assessment.rounds.title': 'အကဲဖြတ်ကာလများ',
          'assessment.rounds.employeeTab': 'ဝန်ထမ်းအကဲဖြတ်ခြင်း',
          'assessment.rounds.selfTab': 'ကိုယ်တိုင်အကဲဖြတ်ခြင်း',
          'assessment.rounds.selfHint': 'ဝန်ထမ်းများ ကိုယ်တိုင်အကဲဖြတ်နိုင်ရန် ကာလဖွင့်ပါ · ကာလအသစ်ဖွင့်လျှင် လက်ရှိကာလကို အလိုအလျောက် ပိတ်မည်။',
          'assessment.rounds.employeeHint': 'အကဲဖြတ်မှုများ စီမံမီ ဝန်ထမ်းအကဲဖြတ်ကာလ ဖွင့်ထားရမည် · ကာလအသစ်ဖွင့်လျှင် လက်ရှိကာလပိတ်ပြီး အမှတ်၊ အကဲဖြတ်သူနှင့် အဆင့်ဒေတာအသစ် စတင်မည် (ယခင်ကာလများကို ပြန်ကြည့်နိုင်သည်)။',
          'assessment.rounds.listHint': 'ထိုကာလနှင့်နှစ်အတွက် လုပ်ရန် ကာလကိုဖွင့်ပါ · ကာလနှင့်ဒေတာကို အပြီးဖျက်ရန် ဖျက်ရန်ကိုနှိပ်ပါ။',
          'assessment.rounds.openState': '● ဖွင့်ထားသည်',
          'assessment.rounds.closedState': 'ပိတ်ထားသည်',
          'assessment.rounds.viewResults': 'ရလဒ် ကြည့်ရန်',
          'assessment.rounds.close': 'ကာလ ပိတ်ရန်',
          'assessment.rounds.open': 'ကာလ ဖွင့်ရန်',
          'assessment.rounds.empty': 'ကာလ မရှိသေးပါ — အပေါ်တွင် ပထမကာလကို ဖွင့်ပါ',
          'assessment.rounds.deleteTitle': 'ဤကာလကို ဖျက်မလား?',
          'assessment.rounds.deleteBefore': 'ဖျက်မည့်ကာလ',
          'assessment.rounds.deleteAfter': 'နှင့် ယင်းကာလ၏ ဒေတာအားလုံး (အမှတ် · အကဲဖြတ်သူ · အဆင့်) ကို အပြီးဖျက်မည်။ ပြန်ယူ၍မရပါ။',
          'assessment.rounds.deletePermanent': 'အပြီးဖျက်ရန်',
          'assessment.rounds.openNewTitle': 'အကဲဖြတ်ကာလအသစ် ဖွင့်ရန်',
          'assessment.rounds.allTitle': 'အကဲဖြတ်ကာလအားလုံး',
          'assessment.rounds.namePlaceholder': 'ကာလအမည် ဥပမာ လက်ရှိနှစ် အကဲဖြတ်ကာလ ၁',
          'assessment.rounds.openNewButton': '+ ကာလအသစ် ဖွင့်ရန်',
          'assessment.rounds.confirmOpenNew': 'ကာလအသစ် ဖွင့်မလား? လက်ရှိကာလပိတ်ပြီး ဒေတာအစုံအသစ် စတင်မည်။',
          'assessment.rounds.confirmClose': '“{name}” ကာလကို ပိတ်မလား?',
          'assessment.rounds.confirmReopen': '“{name}” ({year}) ကာလသို့ ဖွင့်/ပြောင်းမလား? လက်ရှိဖွင့်ထားသောကာလကို ပိတ်မည်။',
          'assessment.questions.title': 'မေးခွန်းများ သတ်မှတ်ရန်',
          'assessment.questions.hint': 'Input ကော်လံအဆင့်ဆင့်အလိုက် မေးခွန်းများကို အကဲဖြတ်သူထံ ပို့မည် · ဘာသာ ၃ မျိုးအထိ ဖြည့်နိုင်သည် (TH မဖြစ်မနေ · EN/MY မဖြည့်လည်းရ) · အလိုအလျောက် သိမ်းမည်။',
          'assessment.questions.noInputColumns': 'Input ကော်လံ မရှိသေးပါ — စီမံရန် စာမျက်နှာ အပိုင်း ၃.၂ တွင် ကော်လံအမျိုးအစား သတ်မှတ်ပါ။',
          'assessment.questions.languageLabel': 'မေးခွန်းဘာသာစကား ပြရန်',
          'assessment.results.title': 'ရလဒ်များ',
          'assessment.results.importOverall': 'ဒေတာအားလုံး တင်သွင်းရန်',
          'assessment.results.exportHint': 'ရွေးထားသောကာလ၏ ရလဒ်ဖိုင် အပြည့်အစုံ။',
          'assessment.results.roundPicker': 'ဒေါင်းလုဒ်လုပ်မည့် ကာလကို ရွေးပါ',
          'assessment.results.roundOpenMark': '● = ဖွင့်ထားသောကာလ',
          'assessment.results.legendGroup': 'Pastel = ဒေတာအုပ်စု',
          'assessment.results.legendGroupTitle': 'Pastel အရောင်များက အဓိကခေါင်းစဉ်အလိုက် ဒေတာကို ခွဲပြသည်',
          'assessment.results.legendUnavailable': 'ပိုမှောင် = ဤအဆင့်တွင် မတွက်ပါ',
          'assessment.results.legendUnavailableTitle': 'ဤကော်လံကို ဝန်ထမ်း၏အဆင့်အတွက် မတွက်ချက်ပါ',
          'assessment.results.clearFilters': 'စစ်ထုတ်မှုများ ရှင်းရန်',
          'assessment.results.empty': 'ရွေးထားသောအခြေအနေနှင့် ကိုက်ညီသည့် ဝန်ထမ်းမရှိပါ',
          'assessment.results.noColumns': 'အမှတ်ကော်လံ မရှိသေးပါ — စီမံရန် စာမျက်နှာ အပိုင်း ၂ တွင် ဖန်တီး သို့မဟုတ် တင်သွင်းပါ။',
          'assessment.settings.hrTitle': 'အသုံးပြုခွင့် (HR)',
          'assessment.settings.hrHint': 'ထည့်ထားသော ဝန်ထမ်းများသည် Assessment သို့ဝင်ပြီး အမှတ်များ စီမံနိုင်သည် (စနစ်စီမံသူများ ဝင်ခွင့်ရှိပြီးသားဖြစ်သည်)။',
          'assessment.settings.noMembers': 'အဖွဲ့ဝင် မရှိသေးပါ',
          'assessment.settings.positionsTitle': 'စနစ်ဝင်ခွင့်ရှိသော ရာထူးများ',
          'assessment.settings.positionsHint': 'Bplus ဝန်ထမ်းဒေတာမှ စနစ်ဝင်ခွင့်ရှိသော ရာထူးများကို ရွေးပါ · မသိမ်းဖူးပါက ရာထူးအားလုံးကို ခွင့်ပြုသည်။',
          'assessment.settings.selectAll': 'အားလုံးရွေးရန်',
          'assessment.settings.clear': 'ရွေးထားသည်များ ရှင်းရန်',
          'assessment.settings.save': 'ရာထူးများ သိမ်းရန်',
          'assessment.settings.searchPlaceholder': 'HR အဖြစ်ထည့်ရန် ဝန်ထမ်းကုဒ် သို့ အမည်ရှာပါ',
          'assessment.settings.removeTitle': 'ဖယ်ရှားရန်',
          'assessment.settings.saved': 'သိမ်းပြီး ✓',
          'assessment.settings.error': 'အမှား',
          'assessment.scores.levelTitle': 'အဆင့် {level}',
          'assessment.scores.deleteLevelTitle': 'အဆင့် {level} ဖျက်ရန်',
          'assessment.scores.deleteLevelConfirm': 'အဆင့် {level} ကို ဖျက်မလား?',
          'assessment.scores.deleteLevelPropWarning': 'အပိုင်း ၃.၁ ရှိ ဤအဆင့်၏ အချိုးများကိုလည်း ဖျက်မည်။',
          'assessment.scores.deleteLevelPositionsWarning': 'သတ်မှတ်ထားသော ရာထူး {count} ခုကို “အဆင့်မသတ်မှတ်ရသေး” သို့ ပြန်ရွှေ့မည်။',
          'assessment.scores.deleteLevelShiftWarning': 'ပိုမြင့်သောအဆင့်များကို အောက်သို့ ရွှေ့မည် (ဥပမာ အဆင့် {from} → {to})။',
          'assessment.scores.deleteLevelError': 'အဆင့်ဖျက်၍မရပါ',
          'assessment.scores.saveLevelError': 'အဆင့်သိမ်း၍မရပါ',
          'assessment.scores.saveError': 'သိမ်း၍မရပါ',
          'assessment.scores.deleteError': 'ဖျက်၍မရပါ',
          'assessment.scores.addError': 'ထည့်၍မရပါ',
          'assessment.scores.minEvaluatorError': 'အကဲဖြတ်သူအဆင့် အနည်းဆုံး ၁ ခု ရှိရမည်',
          'assessment.scores.evaluatorSlotsTitle': 'အကဲဖြတ်သူအဆင့်ကွက်များ: {levels}',
          'assessment.scores.evaluatorTitle': 'အကဲဖြတ်သူအဆင့် {level}',
          'assessment.scores.deleteMainConfirm': 'ဤအဓိကခေါင်းစဉ်ကို ဖျက်မလား?\nအတွင်းရှိ ကော်လံခွဲများနှင့် ယခင်သိမ်းထားသော အမှတ်များကိုလည်း ဖျက်မည်။',
          'assessment.scores.deleteSubConfirm': 'ဤကော်လံခွဲကို ဖျက်မလား?\nဤကော်လံတွင် ယခင်သိမ်းထားသော အမှတ်များကိုလည်း ဖျက်မည်။',
          'assessment.self.title': 'ကိုယ်တိုင်အကဲဖြတ်ခြင်း',
          'assessment.filter.clear': 'ရှင်းရန်',
          'assessment.filter.apply': 'အတည်ပြုရန်',
          'assessment.filter.selectAll': 'အားလုံးရွေးရန်',
          'assessment.filter.loading': 'စစ်ထုတ်မှုများ ဖတ်နေသည်',
          'assessment.filter.searchPlaceholder': 'ရှာရန်...',
          'assessment.filter.noResults': 'မတွေ့ပါ',
          'assessment.filter.emptyValue': '(ကွက်လပ်)',
          'assessment.filter.columnTitle': 'ကော်လံ စစ်ထုတ်ရန်',
          'assessment.filter.loadFailed': 'စစ်ထုတ်မှုများ ဖတ်၍မရပါ',
          'assessment.scores.noColumnsPreview': 'ကော်လံ မရှိသေးပါ — အပိုင်း ၂.၁ တွင် ဖန်တီး သို့မဟုတ် တင်သွင်းပါ။',
          'assessment.scores.typeScore': 'အမှတ်အပြည့်',
          'assessment.scores.typeBonus': 'အပိုအမှတ်',
          'assessment.scores.calcDeduct': 'အမှတ်နုတ်ရန်',
          'assessment.scores.calcGrade': 'Grade',
          'assessment.scores.noSubColumns': 'ကော်လံခွဲ မရှိသေးပါ — အပိုင်း ၂ တွင် ဖန်တီးပါ။',
          'assessment.scores.noMainColumns': 'အဓိကခေါင်းစဉ် မရှိသေးပါ — အပိုင်း ၂ တွင် ဖန်တီး သို့မဟုတ် တင်သွင်းပါ။',
          'assessment.scores.noLevels': 'အဆင့် မရှိသေးပါ — အပိုင်း ၁.၁ တွင် + အဆင့်ထည့်ရန်ကို နှိပ်ပါ (အဆင့် ၀ ကို မတွက်ပါ)။',
          'assessment.scores.choose': 'ရွေးရန်',
          'assessment.scores.other': 'အခြား...',
          'assessment.scores.modePercent': 'အချိုး',
          'assessment.scores.modeExtra': 'အပို (+/-)',
          'assessment.scores.modeNone': 'မတွက်ချက်ပါ',
          'assessment.scores.levelLabel': 'အဆင့်',
          'assessment.scores.totalLabel': 'စုစုပေါင်း',
          'assessment.scores.mainHeadingUnit': 'အဓိကခေါင်းစဉ်',
          'assessment.scores.columnUnit': 'ကော်လံ',
          'assessment.scores.maximum': 'အများဆုံး',
          'assessment.scores.fullPlaceholder': 'အမှတ်အပြည့် ၁၀',
          'assessment.scores.removeHierarchy': 'ဤအဆင့်ဆင့်ကို ဖယ်ရန်',
          'assessment.scores.hierarchyLabel': 'အဆင့်ဆင့်',
          'assessment.scores.customPlaceholder': 'ကိုယ်တိုင်ဖြည့်ရန်',
          'assessment.hierarchy.1': 'အဆင့်ဆင့် ၁',
          'assessment.hierarchy.2': 'အဆင့်ဆင့် ၂',
          'assessment.hierarchy.3': 'အဆင့်ဆင့် ၃',
          'assessment.hierarchy.4': 'အဆင့်ဆင့် ၄'
        }
      };

      var area5sCopy = {
        th: {
          'toast.area5sDenied': 'คุณยังไม่ได้รับสิทธิ์เข้าใช้งานระบบ SUPAVUT 5S AREA',
          'nav.area5s': 'พื้นที่ 5ส',
          'nav.a5sMyWork': 'งานพื้นที่ของฉัน',
          'nav.a5sReview': 'ตรวจประเมิน',
          'nav.a5sManage': 'จัดการพื้นที่',
          'nav.a5sMembers': 'ตั้งค่าระบบ',
          'nav.a5sRounds': 'รายเดือน',
          'nav.a5sDownloads': 'ดาวน์โหลดเอกสาร',
          'a5s.noRound.title': 'ยังไม่มีการเปิดรอบเดือน 5ส — บันทึกและส่งตรวจได้เมื่อเปิดรอบ',
          'a5s.noRound.help': 'ระบบจะกลับมาใช้งานได้ทันทีเมื่อแอดมินเปิดรอบเดือน',
          'a5s.noRound.open': 'ไปเปิดรอบ',
          'a5s.noRound.backSystems': 'กลับสู่ระบบทั้งหมด',
          'a5s.common.overview': 'ภาพรวม',
          'a5s.common.backOverview': 'กลับภาพรวม',
          'a5s.common.back': 'กลับ',
          'a5s.common.zone': 'โซน',
          'a5s.common.building': 'อาคาร',
          'a5s.common.backMyWork': 'กลับงานพื้นที่ของฉัน',
          'a5s.common.backReview': 'กลับหน้าตรวจประเมิน',
          'a5s.common.layout': 'พื้นที่',
          'a5s.common.pointDetail': 'รายละเอียดจุด',
          'a5s.common.noPoint': 'ยังไม่มีจุดในพื้นที่นี้',
          'a5s.common.point': 'จุด',
          'a5s.common.area': 'พื้นที่',
          'a5s.common.status': 'สถานะ',
          'a5s.common.amount': 'จำนวน',
          'a5s.common.close': 'ปิด',
          'a5s.common.fitImage': 'แสดงภาพพอดี',
          'a5s.common.resetZoom': 'รีเซ็ตซูม',
          'a5s.common.zoomTools': 'เครื่องมือซูม',
          'a5s.common.selectPoint': 'เลือกจุด',
          'a5s.common.selectFloor': 'เลือกชั้น',
          'a5s.common.viewProfile': 'ดูรูปโปรไฟล์',
          'a5s.common.remove': 'นำออก',
          'a5s.common.removeImage': 'นำรูปออก',
          'a5s.common.removeFile': 'นำไฟล์ออก',
          'a5s.common.imageNumber': 'รูปที่',
          'a5s.common.selectYear': 'เลือกปีของประกาศ',
          'a5s.common.viewAnnouncement': 'ดูประกาศ 5ส',
          'a5s.common.layoutPosition': 'ตำแหน่งพื้นที่',
          'a5s.common.closeForm': 'ปิดฟอร์ม',
          'a5s.common.note': 'หมายเหตุ',
          'a5s.common.read': 'อ่าน',
          'a5s.common.employeeCode': 'รหัสพนักงาน',
          'a5s.common.position': 'ตำแหน่ง',
          'a5s.common.department': 'แผนก',
          'a5s.common.detail': 'รายละเอียด',
          'a5s.common.cards': 'รายการ',
          'a5s.common.round': 'รอบ',
          'a5s.common.deadline': 'กำหนดส่ง',
          'a5s.common.sentAt': 'ส่งเมื่อ',
          'a5s.common.submitCount': 'ครั้งที่ส่ง',
          'a5s.common.assignees': 'ผู้รับผิดชอบ',
          'a5s.common.evaluators': 'ผู้ประเมิน',
          'a5s.common.you': 'คุณ',
          'a5s.common.by': 'โดย',
          'a5s.common.updated': 'อัปเดต',
          'a5s.common.totalPoints': 'จุดทั้งหมดในพื้นที่',
          'a5s.common.noLayout': 'ยังไม่มีพื้นที่ในระบบ',
          'a5s.common.noImage': 'ไม่มีรูปภาพ',
          'a5s.common.noResult': 'ไม่พบพื้นที่ที่ตรงกับเงื่อนไข',
          'a5s.common.noAssignee': 'ยังไม่มีผู้รับผิดชอบจุดนี้',
          'a5s.common.noAssignees': 'ยังไม่มีผู้รับผิดชอบ',
          'a5s.common.noEvaluator': 'ยังไม่มีผู้ประเมิน',
          'a5s.common.noData': 'ยังไม่มีข้อมูล',
          'a5s.common.noDataBody': 'จุดนี้ยังไม่ถูกส่งตรวจจากผู้รับผิดชอบ',
          'a5s.common.noCards': 'ยังไม่มีรายการที่ส่งมา',
          'a5s.common.waitCards': 'รอผู้รับผิดชอบเพิ่มรายละเอียดและส่งตรวจ',
          'a5s.common.noDetail': 'ยังไม่มีรายละเอียดที่บันทึก',
          'a5s.common.viewPhoto': 'ดูรูป',
          'a5s.common.saved': 'บันทึกแล้ว',
          'a5s.common.error': 'ผิดพลาด',
          'a5s.status.not_started': 'ยังไม่ดำเนินการ',
          'a5s.status.draft': 'ฉบับร่าง',
          'a5s.status.submitted': 'รอดำเนินการ',
          'a5s.status.resubmitted': 'รอดำเนินการ',
          'a5s.status.failed': 'ปฏิเสธ',
          'a5s.status.passed': 'ผ่าน',
          'a5s.status.no_data': 'ยังไม่มีข้อมูล',
          'a5s.manage.title': 'จัดการพื้นที่',
          'a5s.manage.createTitle': 'สร้างพื้นที่ใหม่',
          'a5s.manage.createHint': 'กด + เพื่อเพิ่มพื้นที่ แล้วกรอกข้อมูล',
          'a5s.manage.createModalHint': 'เลือกชั้น ตั้งชื่อ แล้วอัปโหลดภาพแผนผัง',
          'a5s.manage.layoutName': 'ชื่อพื้นที่',
          'a5s.common.roomName': 'ชื่อห้อง',
          'a5s.manage.layoutNamePlaceholder': 'เช่น โรงงาน 1 ชั้นผลิต',
          'a5s.manage.description': 'คำอธิบาย (ไม่บังคับ)',
          'a5s.manage.descriptionPlaceholder': 'ขอบเขตพื้นที่ / หมายเหตุ',
          'a5s.manage.image': 'ภาพแผนผัง',
          'a5s.manage.createButton': '+ สร้างพื้นที่',
          'a5s.manage.cancel': 'ยกเลิก',
          'a5s.manage.floor': 'เลือกชั้น',
          'a5s.manage.backZones': 'กลับเลือกโซน',
          'a5s.manage.backSubZones': 'กลับเลือกโซนย่อย',
          'a5s.manage.unmappedTitle': 'พื้นที่ที่ยังไม่ผูกชั้น',
          'a5s.manage.unmappedNote': 'ยังไม่ได้ผูกชั้น — แก้ไขได้ตามปกติ ส่วนการผูกชั้นให้ Admin ทำที่หน้า mapping',
          'a5s.manage.noFloorInZone': 'โซนนี้ยังไม่มีชั้น — แจ้ง Admin ตั้งค่าก่อน',
          'a5s.manage.pickSubZoneTitle': 'เลือกโซนย่อย',
          'a5s.manage.pickSubZoneHint': 'เลือกภาพโซนย่อย/พื้นที่ย่อยที่ Admin ตั้งไว้ ก่อนสร้างพื้นที่',
          'a5s.manage.subZonePageTitle': 'เลือกพื้นที่ย่อย/อาคาร',
          'a5s.manage.subZonePageHint': 'คลิกกรอบหรือชื่ออาคารบนภาพโซนย่อย เพื่อเข้าไปจัดการพื้นที่ในอาคารนั้น',
          'a5s.manage.noSubZoneInZone': 'โซนนี้ยังไม่มีภาพโซนย่อย',
          'a5s.manage.noAreaInSubZone': 'ยังไม่มีพื้นที่ย่อยในภาพนี้',
          'a5s.manage.selectSubZoneFirst': 'เลือกโซนย่อยก่อนสร้างพื้นที่',
          'a5s.manage.noFloorInSubZone': 'โซนย่อยนี้ยังไม่มีชั้น — แจ้ง Admin ตั้งค่าก่อน',
          'a5s.manage.zone': 'เลือกโซน',
          'a5s.manage.locationLabel': 'พื้นที่',
          'a5s.manage.subZone': 'เลือกโซนย่อย',
          'a5s.manage.editPlan': 'แก้ไขแปลนบริษัท',
          'a5s.manage.pickZoneTitle': 'เลือกพื้นที่',
          'a5s.manage.pickZoneHint': 'คลิกหรือแตะพื้นที่บนภาพเพื่อเข้าไปเลือกโซนย่อย/อาคารของโซนนั้น',
          'a5s.manage.noPlanAdmin': 'ยังไม่มีแปลนบริษัท — ไปที่ "แก้ไขแปลนบริษัท" เพื่ออัปโหลดภาพและวาดโซน',
          'a5s.manage.noPlanAllocator': 'ยังไม่มีแปลนบริษัท — รอ Admin ตั้งค่าอาคาร/โซนก่อน',
          'a5s.manage.noZoneYet': 'ยังไม่มีโซนบนแปลนนี้',
          'a5s.manage.companyName': 'บริษัท สุภาวุฒิ อินดัสทรี จำกัด',
          'a5s.manage.unmappedHint': 'มีพื้นที่เดิม',
          'a5s.manage.unmappedHint2': 'รายการยังไม่ได้ผูกชั้น — คลิกเพื่อจัดการ',
          'a5s.plan.title': 'แปลนบริษัท',
          'a5s.plan.backManage': 'กลับ',
          'a5s.plan.mappingLink': 'mapping ข้อมูลเก่า',
          'a5s.plan.noPlanYet': 'ยังไม่มีภาพแปลน — อัปโหลดเพื่อเริ่มวาดโซน',
          'a5s.plan.chooseFile': 'เลือกไฟล์',
          'a5s.plan.step1': 'วาดโซน',
          'a5s.plan.step2': 'เลือกโซนย่อย',
          'a5s.plan.step3': 'พื้นที่ย่อย + ชั้น',
          'a5s.plan.noSubZoneMaps': 'ยังไม่มีโซนย่อย',
          'a5s.plan.upload': 'อัปโหลด',
          'a5s.plan.stageHint': 'ซูมเข้าจุดที่ต้องการ → กด "+ เพิ่มโซน" → คลิกล้อมรอบพื้นที่ · คลิกโซนเดิมเพื่อแก้ไข',
          'a5s.plan.zoomHint': 'ล้อเมาส์ = ซูม · ลาก = เลื่อนดู',
          'a5s.plan.undoPoint': 'ย้อนจุด',
          'a5s.plan.changeImageHint': 'เปลี่ยนภาพแปลน',
          'a5s.plan.noZonesYet': 'ยังไม่มีโซน — กด "+ เพิ่มโซน"',
          'a5s.plan.drawNewZone': '+ เพิ่มโซน',
          'a5s.plan.drawHint': 'คลิกบนภาพทีละจุดล้อมรอบพื้นที่ (อย่างน้อย 3 จุด)',
          'a5s.plan.finishDraw': 'เสร็จสิ้น',
          'a5s.plan.needThreePoints': 'ต้องมีอย่างน้อย 3 จุด',
          'a5s.plan.saveFailed': 'บันทึกไม่สำเร็จ',
          'a5s.plan.colorSaveFailed': 'บันทึกสีไม่สำเร็จ',
          'a5s.plan.allZones': 'โซนทั้งหมด',
          'a5s.plan.zoneColor': 'เลือกสีโซน',
          'a5s.plan.selectedOverviewZone': 'โซนที่เลือก',
          'a5s.plan.selectedOverviewZoneHint': 'กรอบนี้ใช้กำหนดขอบเขตของโซนย่อย/อาคาร และแก้ชื่อโซนหลักได้จากตรงนี้',
          'a5s.plan.zoneData': 'ข้อมูลโซน',
          'a5s.plan.zoneName': 'ชื่อ',
          'a5s.plan.zoneNamePlaceholder': 'โซน 1',
          'a5s.plan.zoneLabel': 'โซน',
          'a5s.plan.floors': 'ชั้นในโซนนี้',
          'a5s.plan.floorsInArea': 'ชั้น',
          'a5s.plan.floorsUnit': 'ชั้น',
          'a5s.plan.floorNamePlaceholder': 'เช่น F4',
          'a5s.plan.addFloor': '+ ชั้น',
          'a5s.plan.addFloorFailed': 'เพิ่มชั้นไม่สำเร็จ',
          'a5s.plan.resetZone': 'รีเซ็ตโซน',
          'a5s.plan.resetZoneConfirm': 'รีเซ็ตโซนนี้? ระบบจะถอดพื้นที่ที่ผูกกับชั้นในโซนนี้ออก และลบชั้นเดิมออกจากโซน',
          'a5s.plan.resetDone': 'รีเซ็ตโซนแล้ว ตอนนี้สามารถลบโซนนี้ได้',
          'a5s.plan.resetFailed': 'รีเซ็ตโซนไม่สำเร็จ',
          'a5s.plan.deleteZone': 'ลบโซนนี้',
          'a5s.plan.deleteOverviewZone': 'ลบโซน',
          'a5s.plan.deleteZoneConfirm': 'ลบโซนนี้?',
          'a5s.plan.deleteFailed': 'ลบไม่สำเร็จ',
          'a5s.plan.noFloors': 'ยังไม่มีชั้น',
          'a5s.plan.noSelection': 'เลือกโซนจากภาพหรือรายการเพื่อจัดการ',
          'a5s.plan.subZoneMaps': 'โซนย่อย',
          'a5s.plan.subZoneMapsHint': 'แนบภาพแล้ววาดพื้นที่ย่อย',
          'a5s.plan.addSubZone': 'เพิ่มโซนย่อย',
          'a5s.plan.editSubZone': 'แก้ไขโซนย่อย',
          'a5s.plan.deleteSubZone': 'ลบโซนย่อย',
          'a5s.plan.deleteSubZoneConfirm': 'ลบโซนย่อยนี้?',
          'a5s.plan.subZoneName': 'ชื่อโซนย่อย',
          'a5s.plan.subZoneImage': 'ภาพโซนย่อย',
          'a5s.plan.saveSubZone': 'บันทึกโซนย่อย',
          'a5s.plan.subZoneNeedImage': 'กรุณาแนบภาพโซนย่อย',
          'a5s.plan.subZoneSaveFailed': 'บันทึกโซนย่อยไม่สำเร็จ',
          'a5s.plan.subZoneDeleteFailed': 'ลบโซนย่อยไม่สำเร็จ',
          'a5s.plan.subZoneEditorHint': 'วาดพื้นที่ย่อยบนภาพ แล้วเพิ่มชั้น',
          'a5s.plan.subZoneDrawHint': 'ซูม/ลากดูภาพ → กด "+ เพิ่มพื้นที่ย่อย" → คลิกล้อมรอบ',
          'a5s.plan.drawSubZoneArea': '+ เพิ่มพื้นที่ย่อย',
          'a5s.plan.subZoneAreaDrawHint': 'คลิกบนภาพทีละจุดเพื่อวาดกรอบพื้นที่ย่อย อย่างน้อย 3 จุด',
          'a5s.plan.subZoneAreas': 'พื้นที่ย่อย',
          'a5s.plan.subZoneAreaColor': 'เลือกสีพื้นที่ย่อย',
          'a5s.plan.areasUnit': 'พื้นที่',
          'a5s.plan.noSubZoneAreas': 'ยังไม่มีพื้นที่ย่อย',
          'a5s.plan.subZoneAreaData': 'พื้นที่ย่อยที่เลือก',
          'a5s.plan.subZoneAreaName': 'ชื่อ',
          'a5s.plan.deleteSubZoneArea': 'ลบพื้นที่ย่อย',
          'a5s.plan.deleteSubZoneAreaConfirm': 'ลบพื้นที่ย่อยนี้?',
          'a5s.plan.noSubZoneAreaSelection': 'เลือกพื้นที่ย่อยเพื่อแก้ไข',
          'a5s.mapping.title': 'ตรวจ/แก้ mapping ข้อมูลเก่า',
          'a5s.mapping.backPlan': 'กลับหน้าแปลนบริษัท',
          'a5s.mapping.hint': 'เลือกโซน พื้นที่ย่อย และชั้นให้พื้นที่เดิมแต่ละอัน — ชั้นเดาให้อัตโนมัติจากชื่อ แต่โซน/พื้นที่ย่อยต้องเลือกเอง',
          'a5s.mapping.onlyUnmapped': 'แสดงเฉพาะที่ยังไม่ได้ผูกชั้น',
          'a5s.mapping.noZoneYet': 'ยังไม่มีโซน — ไปที่หน้าแปลนบริษัทเพื่อวาดโซน/ตั้งชั้นก่อน',
          'a5s.mapping.allMapped': 'พื้นที่ทั้งหมดผูกชั้นครบแล้ว',
          'a5s.mapping.round': 'รอบ',
          'a5s.mapping.guessed': 'เดาชั้น',
          'a5s.mapping.zone': 'โซน',
          'a5s.mapping.area': 'พื้นที่ย่อย',
          'a5s.mapping.floor': 'ชั้น',
          'a5s.mapping.selectZone': '— เลือกโซน —',
          'a5s.mapping.selectArea': '— เลือกพื้นที่ย่อย —',
          'a5s.mapping.selectFloor': '— เลือกชั้น —',
          'a5s.mapping.noGuess': 'เดาไม่ได้',
          'a5s.mapping.saveAll': 'บันทึก mapping ทั้งหมด',
          'a5s.index.unmappedZone': 'ยังไม่จัดชั้น',
          'a5s.myWork.planHintWork': 'ภาพรวมอาคาร — งานของคุณในแต่ละอาคาร คลิกเพื่อดูรายการด้านล่าง',
          'a5s.myWork.planHintReview': 'ภาพรวมอาคาร — จุดที่ต้องตรวจในแต่ละอาคาร คลิกเพื่อดูรายการด้านล่าง',
          'a5s.myWork.planNoWork': 'ไม่มีงาน',
          'a5s.myWork.reportStatus': 'สถานะงานที่รับผิดชอบ',
          'a5s.myWork.mineHere': 'งานของฉัน',
          'a5s.myWork.mineHereReview': 'ที่ต้องตรวจ',
          'a5s.myWork.statusData': 'ข้อมูลสถานะ',
          'a5s.myWork.openAreaWork': 'เปิดรายการงานของพื้นที่นี้',
          'a5s.myWork.openAreaReview': 'เปิดรายการตรวจประเมินของพื้นที่นี้',
          'a5s.myWork.openAreaHint': 'คลิกกรอบพื้นที่เพื่อดูรายการงานด้านล่าง',
          'a5s.myWork.openAreaPageHint': 'คลิกกรอบอาคารเพื่อเปิดรายการพื้นที่',
          'a5s.manage.editPin': 'แก้ไข',
          'a5s.manage.edit': 'แก้ไข',
          'a5s.manage.closeUse': 'ปิด',
          'a5s.manage.openUse': 'เปิด',
          'a5s.manage.delete': 'ลบ',
          'a5s.manage.disabled': 'ปิดใช้งาน',
          'a5s.manage.off': 'ปิด',
          'a5s.manage.nameLabel': 'ชื่อพื้นที่',
          'a5s.manage.updatedLabel': 'อัปเดต',
          'a5s.manage.addedByLabel': 'เพิ่มโดย',
          'a5s.manage.viewLive': 'ดูหน้าจริง',
          'a5s.manage.adminView': 'Admin',
          'a5s.manage.adminTitle': 'พื้นที่ทั้งหมด',
          'a5s.manage.adminHint': 'เห็นและจัดการพื้นที่ที่ทุกคนสร้างไว้ในระบบ',
          'a5s.manage.mineTitle': 'พื้นที่ที่ฉันสร้าง',
          'a5s.manage.mineHint': 'รายการที่บัญชีของคุณสร้างไว้เอง',
          'a5s.manage.myLayoutTitle': 'พื้นที่ของฉัน',
          'a5s.manage.myLayoutHint': 'ผู้จัดสรรพื้นที่แก้ได้เฉพาะพื้นที่ที่ตัวเองสร้าง',
          'a5s.manage.emptyMine': 'คุณยังไม่ได้สร้างพื้นที่',
          'a5s.manage.emptyFirst': 'ยังไม่มีพื้นที่ — กดปุ่ม + เพื่อสร้าง',
          'a5s.manage.createdTitle': 'สร้างพื้นที่แล้ว',
          'a5s.manage.createdBody': 'กด "แก้ไข" เพื่อปักจุดพื้นที่',
          'a5s.manage.ok': 'ตกลง',
          'a5s.index.title': 'บอร์ดพื้นที่ 5ส',
          'a5s.index.subtitle': 'เลือกพื้นที่เพื่อดูแผนผัง จุด และผู้รับผิดชอบ',
          'a5s.index.search': 'ค้นหาชื่อพื้นที่',
          'a5s.index.all': 'ทั้งหมด',
          'a5s.index.mine': 'ที่ฉันรับผิดชอบ',
          'a5s.index.showing': 'แสดง',
          'a5s.index.responsibleBadge': 'คุณรับผิดชอบ',
          'a5s.index.floor': 'ชั้น',
          'a5s.index.floorOther': 'อื่นๆ',
          'a5s.index.planTitle': 'แปลนบริษัทและโซนทั้งหมด',
          'a5s.index.planHint': 'ดูขอบเขตโซนหลักและภาพโซนย่อยที่ Admin กำหนดไว้ ก่อนเข้าพื้นที่รายห้อง',
          'a5s.index.noSubArea': 'ยังไม่มีพื้นที่ย่อย',
          'a5s.index.unmappedArea': 'ยังไม่ผูกพื้นที่ย่อย',
          'a5s.home.title': 'หน้าหลัก',
          'a5s.home.heading': 'ร่วมกันสร้างนิสัย 5ส ให้เป็นกิจวัตรประจำวัน',
          'a5s.home.subtitle': 'พื้นที่สะอาด เป็นระเบียบ ปลอดภัย เริ่มต้นได้จากตัวเราทุกคน — อ่านคำแนะนำการปฏิบัติตามหลัก 5ส ด้านล่าง',
          'a5s.home.scrollHint': 'เลื่อนลงเพื่ออ่านคำแนะนำ · แตะรูปเพื่อดูรายละเอียด',
          'a5s.home.tapZoom': 'แตะเพื่อดูรายละเอียดแบบเต็มจอ',
          'a5s.home.announce': 'ประกาศ',
          'a5s.welcome.hello': 'สวัสดี',
          'a5s.welcome.welcomeTo': 'ยินดีต้อนรับสู่ SUPAVUT 5S Area',
          'a5s.welcome.desc': 'ระบบนี้ช่วยส่งเสริมความสะอาด ความเป็นระเบียบ ความปลอดภัย และสร้างนิสัยที่ดีในการทำงาน เพื่อองค์กรของเรา',
          'a5s.welcome.dontToday': 'ไม่แสดงวันนี้',
          'a5s.welcome.ack': 'รับทราบ',
          'a5s.editor.allPoints': 'จุดทั้งหมด',
          'a5s.editor.editLayout': 'แก้ไขพื้นที่',
          'a5s.editor.backManage': 'กลับจัดการพื้นที่',
          'a5s.editor.layoutName': 'ชื่อพื้นที่',
          'a5s.editor.description': 'คำอธิบาย',
          'a5s.editor.changeImage': 'เปลี่ยนภาพ',
          'a5s.editor.upload': 'อัปโหลด',
          'a5s.editor.trailZone': 'โซน',
          'a5s.editor.trailArea': 'อาคาร',
          'a5s.editor.trailFloor': 'ชั้น',
          'a5s.editor.trailNone': 'ยังไม่จัดพื้นที่',
          'a5s.editor.stageHint': 'กด + เพิ่มจุด · ลากเพื่อย้าย · คลิกเพื่อแก้ไข',
          'a5s.editor.addPoint': 'เพิ่มจุด',
          'a5s.editor.pointData': 'ข้อมูลจุด',
          'a5s.editor.pointCode': 'รหัสจุด',
          'a5s.editor.areaName': 'ชื่อจุด',
          'a5s.editor.assigneeLabel': 'ผู้รับผิดชอบ',
          'a5s.editor.evaluatorLabel': 'ผู้ประเมิน',
          'a5s.editor.searchPeople': 'ค้นหาพนักงาน',
          'a5s.editor.deletePoint': 'ลบจุด',
          'a5s.editor.done': 'เสร็จสิ้น',
          'a5s.editor.noSelection': 'เลือกจุดเพื่อแก้ไข',
          'a5s.editor.noPeople': 'ยังไม่มีคน',
          'a5s.editor.noAssignees': 'ยังไม่มีผู้รับผิดชอบ',
          'a5s.editor.noEvaluators': 'ยังไม่มีผู้ประเมิน',
          'a5s.editor.peopleUnit': 'คน',
          'a5s.editor.addPointFailed': 'เพิ่มจุดไม่สำเร็จ',
          'a5s.editor.deletePointConfirm': 'ลบจุด :code?',
          'a5s.editor.deletePointFailed': 'ลบจุดไม่สำเร็จ',
          'a5s.editor.saveFailed': 'บันทึกไม่สำเร็จ',
          'a5s.editor.addAssigneeFailed': 'เพิ่มไม่สำเร็จ',
          'a5s.editor.addEvaluatorFailed': 'เพิ่มผู้ประเมินไม่สำเร็จ',
          'a5s.rounds.title': 'รอบรายเดือน',
          'a5s.rounds.hint': 'เปิดได้ทีละรอบ — เปิดเดือนใหม่ระบบจะปิดรอบเดิมให้ · เปิดเดือนเก่ากลับมาดู/แก้ต่อได้',
          'a5s.rounds.openRound': 'รอบที่เปิด:',
          'a5s.rounds.noOpenRound': 'ยังไม่มีการเปิดรอบ',
          'a5s.rounds.prevYear': 'ปีก่อนหน้า',
          'a5s.rounds.nextYear': 'ปีถัดไป',
          'a5s.rounds.yearPrefix': 'พ.ศ.',
          'a5s.rounds.stateOpen': 'เปิดอยู่',
          'a5s.rounds.stateClosed': 'ปิดแล้ว',
          'a5s.rounds.stateNotOpen': 'ยังไม่เปิด',
          'a5s.rounds.passed': 'ผ่าน',
          'a5s.rounds.rejected': 'ปฏิเสธ',
          'a5s.rounds.waiting': 'รอตรวจ',
          'a5s.rounds.points': 'จุด',
          'a5s.rounds.noPointData': 'ยังไม่มีข้อมูลจุดในรอบนี้',
          'a5s.rounds.close': 'ปิดรอบ',
          'a5s.rounds.setupBtn': "ตั้งค่ารอบ",
          'a5s.rounds.editInspections': "ตั้งค่า\/เพิ่มครั้งตรวจ",
          'a5s.rounds.setupTitle': "ประเมินกี่ครั้งในเดือน",
          'a5s.rounds.setupHint': "ใส่วันที่ประเมินแต่ละครั้ง แล้วกดยืนยัน — ระบบสร้างไว้ก่อน (ยังไม่เปิด) ค่อยกดเปิดทีละครั้งเมื่อถึงวัน",
          'a5s.rounds.addInspection': "+ เพิ่มประเมิน",
          'a5s.rounds.notSetup': "ยังไม่ตั้งค่าครั้งตรวจ",
          'a5s.rounds.statePlanned': "รอเปิด",
          'a5s.rounds.openDo': "เปิด",
          'a5s.rounds.hasWork': "มีงานแล้ว",
          'a5s.rounds.deleteInspection': 'ลบครั้งตรวจ',
          'a5s.rounds.hint2': "เปิดได้ทีละครั้งตรวจ — เปิดครั้งใหม่ระบบจะปิดครั้งเดิมให้ · 1 เดือนตั้งได้หลายครั้ง (ใส่วันที่)",
          'a5s.rounds.open': 'เปิดรอบ',
          'a5s.rounds.reopen': 'เปิดรอบอีกครั้ง',
          'a5s.rounds.openNew': '+ เปิดครั้งตรวจใหม่',
          'a5s.rounds.times': 'ครั้ง',
          'a5s.rounds.openConfirm': 'เปิดรอบ :target?',
          'a5s.rounds.openConfirmWithClose': 'เปิดรอบ :target?\nรอบ :open ที่เปิดอยู่จะถูกปิดอัตโนมัติ',
          'a5s.rounds.closeConfirm': 'ปิดรอบ :target?\nปิดแล้วทุกจุดจะบันทึก/ส่งตรวจไม่ได้จนกว่าจะเปิดรอบอีกครั้ง',
          'a5s.rounds.openOneConfirm': 'เปิด {label}?\nครั้งที่เปิดอยู่จะถูกปิดอัตโนมัติ',
          'a5s.rounds.deleteConfirm': 'ลบครั้งตรวจ {label}?',
          'a5s.rounds.closeConfirm2': 'ปิด {label}?\nปิดแล้วบันทึก/ส่งตรวจไม่ได้จนกว่าจะเปิดอีกครั้ง',
          'a5s.month.1': 'มกราคม',
          'a5s.month.2': 'กุมภาพันธ์',
          'a5s.month.3': 'มีนาคม',
          'a5s.month.4': 'เมษายน',
          'a5s.month.5': 'พฤษภาคม',
          'a5s.month.6': 'มิถุนายน',
          'a5s.month.7': 'กรกฎาคม',
          'a5s.month.8': 'สิงหาคม',
          'a5s.month.9': 'กันยายน',
          'a5s.month.10': 'ตุลาคม',
          'a5s.month.11': 'พฤศจิกายน',
          'a5s.month.12': 'ธันวาคม',
          'a5s.downloads.title': 'ดาวน์โหลดเอกสาร',
          'a5s.downloads.hint': 'ผลตรวจ 5ส ของรอบเดือนที่เลือก — ไฟล์ Excel (.xlsx)',
          'a5s.downloads.downloadExcel': 'ดาวน์โหลด Excel',
          'a5s.downloads.downloadYear': 'ดาวน์โหลดทั้งปี',
          'a5s.index.pastRound': 'กำลังดูเดือนเก่า (อ่านอย่างเดียว)',
          'a5s.downloads.selectRound': 'เลือกรอบเดือน',
          'a5s.downloads.downloadCsv': 'ดาวน์โหลด CSV',
          'a5s.downloads.noRound': 'ยังไม่มีรอบเดือนในระบบ — เปิดรอบแรกได้ที่เมนู "รายเดือน"',
          'a5s.downloads.noRows': 'รอบนี้ยังไม่มีข้อมูลจุด',
          'a5s.downloads.pointName': 'ชื่อจุด',
          'a5s.downloads.evaluator': 'ผู้ตรวจ',
          'a5s.downloads.evaluatedAt': 'วันที่ตรวจ',
          'a5s.downloads.latestSubmitted': 'ส่งล่าสุด',
          'a5s.downloads.notSent': 'ยังไม่ส่ง',
          'a5s.myWork.title': 'งานพื้นที่ของฉัน',
          'a5s.myWork.layoutsTitle': 'พื้นที่ที่มีจุดของคุณ',
          'a5s.myWork.layoutsHint': 'เลือกพื้นที่เพื่อดูตำแหน่งจุดที่ได้รับมอบหมาย และเพิ่มรายละเอียดงานพร้อมรูปภาพ',
          'a5s.myWork.reviewTitle': 'ตรวจประเมิน',
          'a5s.myWork.reviewHint': 'พื้นที่ที่คุณได้รับมอบหมายเป็นผู้ประเมิน เลือกเพื่อดูภาพพื้นที่และกดผ่านหรือไม่ผ่านตามจุดที่ต้องตรวจ',
          'a5s.myWork.reviewAreaTitle': 'พื้นที่ที่ต้องตรวจประเมิน',
          'a5s.myWork.reviewAreaHint': 'เลือกพื้นที่เพื่อดูจุดที่ได้รับมอบหมายและบันทึกผลการประเมิน',
          'a5s.myWork.evaluationStatus': 'สถานะการประเมิน',
          'a5s.myWork.cardsRecorded': 'รายการที่บันทึก',
          'a5s.myWork.rejectNote': 'หมายเหตุปฏิเสธ',
          'a5s.myWork.passNote': 'หมายเหตุจากผู้ประเมิน',
          'a5s.myWork.noPointDisplay': 'ยังไม่มีจุดที่ต้องแสดง',
          'a5s.myWork.noAssigned': 'ยังไม่มีจุดพื้นที่ที่มอบหมายให้คุณ',
          'a5s.myWork.noReviewAssigned': 'ยังไม่มีพื้นที่ที่ได้รับมอบหมายให้ตรวจประเมิน',
          'a5s.myWork.subZoneWorkTitle': 'ภาพโซนย่อยของงานที่ได้รับ',
          'a5s.myWork.subZoneWorkHint': 'แสดงเฉพาะพื้นที่ย่อยและจุดที่คุณถูกกำหนดให้รับผิดชอบ',
          'a5s.myWork.subZoneReviewTitle': 'ภาพโซนย่อยที่ต้องตรวจ',
          'a5s.myWork.subZoneReviewHint': 'แสดงเฉพาะพื้นที่ย่อยและจุดที่คุณมีสิทธิ์ตรวจประเมิน',
          'a5s.work.add': 'เพิ่ม',
          'a5s.work.addCard': 'เพิ่ม',
          'a5s.work.editCard': 'แก้ไข',
          'a5s.work.addCardNote': 'กรอกรายละเอียดงาน และแนบรูปอย่างน้อย 1 รูป',
          'a5s.work.editCardNote': 'แก้หัวข้อ รายละเอียด หรือรูปภาพ',
          'a5s.work.cardTitle': 'หัวข้อ',
          'a5s.work.cardTitlePlaceholder': 'เช่น ทำความสะอาดโต๊ะทำงาน',
          'a5s.work.cardDetail': 'รายละเอียด',
          'a5s.work.cardDetailPlaceholder': 'สิ่งที่ดำเนินการ หรือหมายเหตุ',
          'a5s.work.attach': 'แนบรูป (JPG / PNG / WEBP / HEIC)',
          'a5s.work.attachMore': 'แนบรูปเพิ่ม (ไม่บังคับ)',
          'a5s.work.save': 'บันทึก',
          'a5s.work.submit': 'ส่งตรวจ',
          'a5s.work.resubmit': 'ส่งใหม่',
          'a5s.work.updateSubmit': 'ส่งอัปเดต',
          'a5s.work.sentCount': 'ส่งแล้ว :count ครั้ง',
          'a5s.work.reason': 'เหตุผล:',
          'a5s.work.note': 'หมายเหตุ:',
          'a5s.work.advice': 'คำแนะนำ:',
          'a5s.work.noCards': 'ยังไม่มีรายการ',
          'a5s.work.addFirst': 'กด + เพื่อเพิ่มรายการแรก',
          'a5s.work.maxImages': 'แนบภาพได้สูงสุด 8 ภาพต่อรายการ',
          'a5s.work.workspaceHint': 'เลือกอาคารและสถานะ แล้วกดชื่อพื้นที่เพื่อดูจุดงานที่รับผิดชอบ',
          'a5s.work.scoreSummary': 'คะแนนงานพื้นที่ของฉัน',
          'a5s.work.filterLabel': 'สถานะงานพื้นที่ของฉัน',
          'a5s.work.todo': 'ต้องทำ',
          'a5s.work.pendingReview': 'รอตรวจ',
          'a5s.work.needsRevision': 'ต้องแก้ไข',
          'a5s.work.completed': 'เสร็จแล้ว',
          'a5s.work.buildingHint': 'ตรวจสอบภาพอาคารและชั้นให้ตรงก่อนเปิดจุดงานของคุณ',
          'a5s.work.layoutCount': 'พื้นที่รับผิดชอบ',
          'a5s.work.pointCount': 'จุดงาน',
          'a5s.work.myWorkData': 'จุดงานของฉัน',
          'a5s.work.latestUpdate': 'อัปเดตล่าสุด',
          'a5s.work.notStarted': 'ยังไม่เริ่มงาน',
          'a5s.work.noItemsForStatus': 'ไม่มีงานในสถานะนี้',
          'a5s.work.noItemsHint': 'เลือกสถานะอื่นเพื่อดูจุดงานทั้งหมดที่คุณรับผิดชอบ',
          'a5s.history.noItemsHint': 'เลือกสถานะอื่นเพื่อดูจุดทั้งหมดในพื้นที่นี้',
          'a5s.workspace.all': 'ทั้งหมด',
          'a5s.workspace.passed': 'ผ่าน',
          'a5s.workspace.failed': 'ไม่ผ่าน',
          'a5s.workspace.pending': 'รอดำเนินการ',
          'a5s.workspace.noData': 'ยังไม่มีข้อมูล',
          'a5s.workspace.filters': 'เลือกอาคาร เดือน และวันที่ตรวจ',
          'a5s.workspace.buildingTabs': 'เลือกอาคาร',
          'a5s.workspace.roundInfo': 'ข้อมูลรอบปัจจุบัน',
          'a5s.workspace.month': 'เดือน',
          'a5s.workspace.subRound': 'รอบย่อย',
          'a5s.workspace.roundNumber': 'ครั้งที่',
          'a5s.workspace.openedOn': 'เปิดรอบเมื่อ',
          'a5s.workspace.monthHistory': 'เลือกเดือนที่ต้องการดู',
          'a5s.workspace.showMonth': 'แสดงข้อมูล',
          'a5s.workspace.currentMonth': 'กำลังแสดงเดือนที่เปิดใช้งานอยู่',
          'a5s.workspace.historyReadOnly': 'ข้อมูลย้อนหลัง · ดูได้อย่างเดียว',
          'a5s.workspace.viewHistoricalData': 'ดูข้อมูลเดือนนี้',
          'a5s.workspace.selectedInspection': 'ข้อมูลวันที่ตรวจที่เลือก',
          'a5s.workspace.inspectionDate': 'วันที่ตรวจ',
          'a5s.workspace.noBuildingData': 'ไม่มีข้อมูล',
          'a5s.workspace.noBuildingDataHint': 'อาคารนี้ไม่มีข้อมูลในวันที่ตรวจที่เลือก',
          'a5s.review.title': 'ตรวจประเมิน',
          'a5s.review.workspaceTitle': 'รายการตรวจประเมิน',
          'a5s.review.workspaceHint': 'เลือกอาคารและสถานะ แล้วกดชื่อพื้นที่เพื่อดูข้อมูลที่ต้องประเมิน',
          'a5s.review.pointsInScope': 'จุดในขอบเขตที่รับผิดชอบ',
          'a5s.review.filterLabel': 'สถานะรายการตรวจประเมิน',
          'a5s.review.ready': 'พร้อมตรวจ',
          'a5s.review.waitingSubmit': 'ยังไม่ส่ง',
          'a5s.review.waitingFix': 'รอแก้ไข',
          'a5s.review.completed': 'เสร็จแล้ว',
          'a5s.review.buildingHint': 'ตรวจสอบภาพอาคารและชั้นให้ตรงก่อนเปิดจุดประเมิน',
          'a5s.review.floorCount': 'ชั้น',
          'a5s.review.layoutCount': 'พื้นที่ประเมิน',
          'a5s.review.pointCount': 'จุดประเมิน',
          'a5s.review.floorTabs': 'ชั้นของอาคาร',
          'a5s.review.evaluationData': 'ข้อมูลที่ต้องประเมิน',
          'a5s.review.latestSubmit': 'ส่งล่าสุด',
          'a5s.review.morePeople': 'คน',
          'a5s.review.submissionNumber': 'ส่งครั้งที่',
          'a5s.review.notSubmitted': 'ยังไม่มีการส่ง',
          'a5s.review.openReview': 'เปิดตรวจ',
          'a5s.review.noItemsForStatus': 'ไม่มีรายการในสถานะนี้',
          'a5s.review.noItemsHint': 'เลือกสถานะอื่นเพื่อดูรายการทั้งหมดในขอบเขตที่คุณรับผิดชอบ',
          'a5s.review.pendingDecision': 'รอตรวจประเมิน',
          'a5s.review.submittedBy': 'ผู้ส่ง',
          'a5s.review.evaluatedBy': 'ผู้ประเมิน',
          'a5s.review.submittedContent': 'ข้อมูลที่ส่งมา',
          'a5s.review.noHistory': 'ยังไม่มีประวัติการส่ง',
          'a5s.review.noHistoryHint': 'ประวัติจะปรากฏเมื่อผู้รับผิดชอบส่งข้อมูลเข้าตรวจประเมิน',
          'a5s.review.headingHint': 'เลือกจุดที่คุณได้รับมอบหมายให้ตรวจ แล้วบันทึกผลผ่านหรือปฏิเสธจากรายการที่ส่งตรวจ',
          'a5s.review.pointsToReview': 'จุดที่ต้องตรวจ',
          'a5s.review.submittedAt': 'ส่งตรวจ:',
          'a5s.review.submitCount': 'ครั้งที่ส่ง:',
          'a5s.review.pass': 'ผ่าน',
          'a5s.review.reject': 'ปฏิเสธ',
          'a5s.review.rejectReason': 'เหตุผลปฏิเสธ',
          'a5s.review.rejectPlaceholder': 'ระบุสิ่งที่ต้องแก้ไข',
          'a5s.review.saveReject': 'บันทึกปฏิเสธ',
          'a5s.review.confirmPassTitle': 'ยืนยันให้ผ่าน',
          'a5s.review.confirmRejectTitle': 'ยืนยันการปฏิเสธ',
          'a5s.review.attachNote': 'แนบหมายเหตุ',
          'a5s.review.notePlaceholder': 'กรอกหมายเหตุถึงผู้รับผิดชอบ',
          'a5s.review.confirm': 'ยืนยัน',
          'a5s.review.failedPrefix': 'ปฏิเสธ:',
          'a5s.review.editDecision': 'แก้ไขการประเมิน',
          'a5s.review.editConfirmTitle': 'แก้ไขการประเมิน',
          'a5s.review.editConfirmBody': 'ต้องการแก้ไขผลการประเมินของจุดนี้ใช่ไหม?',
          'a5s.review.editConfirmOk': 'แก้ไข',
          'a5s.review.submitHistory': 'ประวัติการส่ง',
          'a5s.review.score': 'คะแนน',
          'a5s.score.thisRound': 'คะแนนรอบนี้',
          'a5s.score.short': 'คะแนน',
          'a5s.score.selectedMonth': 'คะแนนเดือนที่เลือก',
          'a5s.score.fromPoints': 'ตรวจแล้ว',
          'a5s.score.fromRounds': 'เฉลี่ยจาก',
          'a5s.score.pointAverage': 'เฉลี่ยทุกครั้ง',
          'a5s.calendar.title': 'ปฏิทินคะแนน',
          'a5s.calendar.open': 'เปิดปฏิทินคะแนน',
          'a5s.calendar.total': 'รวมทั้งหมด',
          'a5s.calendar.monthTotal': 'รวมเดือนนี้',
          'a5s.calendar.dayTotal': 'รวมวันนี้',
          'a5s.calendar.round': 'รอบ',
          'a5s.calendar.openRound': 'กำลังเปิด',
          'a5s.calendar.closedRound': 'ปิดแล้ว',
          'a5s.calendar.evaluations': 'รายการ',
          'a5s.calendar.empty': 'ยังไม่มีวันที่ประเมิน',
          'a5s.calendar.emptyMonth': 'ยังไม่มีวันที่ประเมินในเดือนนี้',
          'a5s.units.round': 'รอบ',
          'a5s.myWork.tasksTitle': 'งานที่ต้องทำ',
          'a5s.myWork.reviewTasksTitle': 'งานที่ต้องตรวจ',
          'a5s.myWork.planToggle': 'แผนผังโรงงาน',
          'a5s.myWork.planToggleHint': 'กดเพื่อดูตำแหน่งงานบนแผนผัง',
          'a5s.score.overall': 'คะแนนรวม',
          'a5s.review.attemptNo': 'ครั้งที่',
          'a5s.review.times': 'ครั้ง',
          'a5s.common.evaluatedDate': 'วันประเมิน',
          'a5s.common.viewLayout': 'ดูพื้นที่',
          'a5s.common.action': 'ดำเนินการ',
          'a5s.round.month': 'เดือน',
          'a5s.round.inspection': 'ครั้งตรวจ',
          'a5s.round.seqShort': 'ครั้งที่',
          'a5s.round.active': 'กำลังดำเนินการ',
          'a5s.round.viewOnly': 'รอบนี้ปิดแล้ว · ดูประวัติได้อย่างเดียว',
          'a5s.myWork.openWork': 'เปิดบันทึกงาน',
          'a5s.history.hintWork': 'เลือกครั้งตรวจเพื่อดูประวัติการประเมินของจุดที่คุณรับผิดชอบในรอบนั้น',
          'a5s.history.hintReview': 'เลือกครั้งตรวจเพื่อดูประวัติผลการประเมินของจุดที่คุณรับผิดชอบตรวจ',
          'a5s.history.hintOverview': 'เลือกครั้งตรวจเพื่อดูประวัติการประเมินของทุกจุดในพื้นที่นี้',
          'a5s.common.history': 'ประวัติ',
          'a5s.common.historySubmit': 'ประวัติการส่ง',
          'a5s.common.view': 'เปิดดู',
          'a5s.common.noteEvaluator': 'หมายเหตุ (ผู้ประเมิน)',
          'a5s.common.name': 'ชื่อ',
          'a5s.common.pointName': 'ชื่อจุด',
          'a5s.index.tapPlan': 'แตะเพื่อดูอาคาร',
          'a5s.index.backPlan': 'กลับ',
          'a5s.index.emptyArea': 'ยังไม่มีข้อมูลในพื้นที่นี้',
          'a5s.work.sent': 'ส่งแล้ว',
          'a5s.work.editReport': 'แก้ไขรายงาน',
          'a5s.work.editReportDone': 'เสร็จสิ้นการแก้ไข',
          'a5s.work.editReportConfirm': 'ต้องการแก้ไขรายงานของจุดนี้ใช่ไหม?',
          'a5s.work.submitConfirm': 'ยืนยันส่งข้อมูลจุดนี้ให้ผู้ประเมินตรวจใช่ไหม?',
          'a5s.history.empty': 'ยังไม่มีประวัติการส่ง',
          'a5s.review.noSubmittedContent': 'ไม่มีรายละเอียดที่ส่ง',
          'a5s.review.failedWaiting': 'จุดนี้ถูกปฏิเสธแล้ว รอผู้รับผิดชอบแก้ไขและส่งใหม่',
          'a5s.review.waitSubmit': 'รอผู้รับผิดชอบส่งตรวจ',
          'a5s.review.outOfScope': 'จุดนี้ไม่ได้อยู่ในขอบเขตที่คุณต้องตรวจ',
          'a5s.review.queueHeading': 'ตรวจประเมินพื้นที่ 5ส',
          'a5s.review.queueHint': 'แสดงเฉพาะจุดที่คุณได้รับมอบหมายให้ตรวจ · เลือกผ่าน หรือไม่ผ่านพร้อมเหตุผล',
          'a5s.review.noActiveRound': 'ยังไม่มีรอบที่เปิดอยู่',
          'a5s.review.tab.pending': 'รอตรวจ',
          'a5s.review.tab.failed': 'ไม่ผ่าน',
          'a5s.review.tab.passed': 'ผ่าน',
          'a5s.review.tab.draft': 'ยังไม่ส่ง',
          'a5s.review.check': 'ตรวจ',
          'a5s.review.viewDetail': 'ดูรายละเอียด',
          'a5s.review.emptyCategory': 'ไม่มีงานในหมวดนี้',
          'a5s.review.backQueue': 'กลับคิวตรวจ',
          'a5s.review.latestFailReason': 'เหตุผลที่ไม่ผ่าน (รอบล่าสุด):',
          'a5s.review.passedDone': 'ผ่านการประเมินแล้ว',
          'a5s.review.when': 'เมื่อ',
          'a5s.review.noCardsInTask': 'ยังไม่มีรายการในงานนี้',
          'a5s.review.decisionTitle': 'ผลการตรวจประเมิน',
          'a5s.review.failReasonRequired': 'เหตุผลที่ไม่ผ่าน (บังคับ)',
          'a5s.review.adviceOptional': 'คำแนะนำเพิ่มเติม (ไม่บังคับ)',
          'a5s.review.advicePlaceholder': 'ข้อเสนอแนะถึงผู้รับผิดชอบ',
          'a5s.review.decisionNote': 'ผ่าน = งานเดือนนี้ของจุดนี้เสร็จสิ้นและถูกล็อกถาวร · ไม่ผ่าน = ผู้รับผิดชอบแก้ไขและส่งใหม่ได้จนกว่ารอบจะปิด',
          'a5s.review.saveDecision': 'บันทึกผลการประเมิน',
          'a5s.settings.title': 'สิทธิ์ผู้ใช้',
          'a5s.settings.memberHint': 'ค้นหาพนักงาน เลือกบทบาท แล้วเพิ่ม — คนเดียวมีได้หลายบทบาท · admin ของ Insight เป็นผู้ดูแลระบบนี้อยู่แล้วโดยอัตโนมัติ',
          'a5s.settings.roleAdmin': 'ผู้ดูแลระบบ',
          'a5s.settings.roleAllocator': 'ผู้จัดสรรพื้นที่',
          'a5s.settings.roleAdminOption': 'ผู้ดูแลระบบ (Admin)',
          'a5s.settings.roleAllocatorOption': 'ผู้จัดสรรพื้นที่ (Allocator)',
          'a5s.settings.revoke': 'ถอนสิทธิ์',
          'a5s.settings.none': 'ยังไม่มี',
          'a5s.settings.assignTitle': 'ผู้รับผิดชอบรายจุด',
          'a5s.settings.assignHint': 'เลือกพื้นที่ย่อยหรืออาคาร แล้วเลือกพื้นที่ที่เรียงตามชั้นเพื่อจัดการผู้รับผิดชอบรายจุด',
          'a5s.settings.pointClosed': 'ปิดจุด',
          'a5s.settings.noAssignees': 'ยังไม่มีผู้รับผิดชอบ',
          'a5s.settings.assigneesLabel': 'ผู้รับผิดชอบ',
          'a5s.settings.evaluatorsLabel': 'ผู้ประเมิน',
          'a5s.settings.noEvaluators': 'ยังไม่มีผู้ประเมิน',
          'a5s.settings.searchAddEvaluator': 'ค้นหาพนักงานเพื่อเพิ่มผู้ประเมิน',
          'a5s.settings.removeEvaluator': 'ลบผู้ประเมิน',
          'a5s.settings.addEvaluatorFailed': 'เพิ่มผู้ประเมินไม่สำเร็จ',
          'a5s.settings.removeEvaluatorFailed': 'ลบผู้ประเมินไม่สำเร็จ',
          'a5s.settings.layoutWide': 'ทั้งพื้นที่',
          'a5s.settings.noPointsInLayout': 'พื้นที่นี้ยังไม่มีจุด ให้ไปปักจุดในหน้าจัดการพื้นที่ก่อน',
          'a5s.settings.pickLayout': 'เลือกพื้นที่เพื่อจัดการผู้รับผิดชอบตามจุด',
          'a5s.settings.noLayoutInArea': 'อาคารนี้ยังไม่มีพื้นที่',
          'a5s.settings.unmappedArea': 'ยังไม่จัดพื้นที่',
          'a5s.settings.unmappedFloor': 'ยังไม่จัดชั้น',
          'a5s.settings.hideDetail': 'ซ่อนรายละเอียด',
          'a5s.settings.searchAdd': 'ค้นหาพนักงานเพื่อเพิ่ม',
          'a5s.settings.removeAssignee': 'ลบผู้รับผิดชอบ',
          'a5s.settings.addAssigneeFailed': 'เพิ่มผู้รับผิดชอบไม่สำเร็จ',
          'a5s.settings.removeAssigneeFailed': 'ลบผู้รับผิดชอบไม่สำเร็จ',
          'a5s.settings.positionAccess': 'ตำแหน่งที่เข้าใช้ระบบได้',
          'a5s.settings.positionHint': 'ติ๊กตำแหน่ง (ดึงจากข้อมูลพนักงาน Bplus) ที่อนุญาตให้เข้าระบบ — ถ้ายังไม่เคยบันทึก = อนุญาตทุกตำแหน่ง · ผู้ที่ถูกมอบหมายพื้นที่/เป็นผู้ตรวจ เข้าได้เสมอไม่ว่าตำแหน่งใด',
          'a5s.settings.selectAll': 'เลือกทั้งหมด',
          'a5s.settings.clearAll': 'ไม่เลือก',
          'a5s.settings.savePosition': 'บันทึกตำแหน่ง',
          'a5s.units.point': 'จุด',
          'a5s.units.personAssigned': 'คนที่มอบหมาย',
          'a5s.units.layout': 'พื้นที่',
          'a5s.area.title': 'พื้นที่ย่อย',
          'a5s.area.openDetail': 'เปิดหน้าพื้นที่ย่อย',
          'a5s.area.openZone': 'เปิดพื้นที่ย่อยของโซนนี้',
          'a5s.__loose': {
            'ภาพรวม': 'ภาพรวม',
            'จัดการพื้นที่': 'จัดการพื้นที่',
            'สิทธิ์ผู้ใช้': 'สิทธิ์ผู้ใช้',
            'งานพื้นที่ของฉัน': 'งานพื้นที่ของฉัน',
            'ตรวจประเมิน': 'ตรวจประเมิน',
            'ผู้รับผิดชอบ': 'ผู้รับผิดชอบ',
            'ผู้ประเมิน': 'ผู้ประเมิน',
            'รหัสพนักงาน': 'รหัสพนักงาน',
            'ชื่อ': 'ชื่อ',
            'ตำแหน่ง': 'ตำแหน่ง',
            'รายละเอียด': 'รายละเอียด',
            'สถานะ': 'สถานะ',
            'หมายเหตุ': 'หมายเหตุ',
            'อ่าน': 'อ่าน',
            'ผ่าน': 'ผ่าน',
            'ปฏิเสธ': 'ปฏิเสธ',
            'รอดำเนินการ': 'รอดำเนินการ',
            'ยังไม่มีข้อมูล': 'ยังไม่มีข้อมูล',
            'เพิ่ม': 'เพิ่ม',
            'แก้ไข': 'แก้ไข',
            'ลบ': 'ลบ',
            'บันทึก': 'บันทึก',
            'ยกเลิก': 'ยกเลิก',
            'ตกลง': 'ตกลง'
          }
        },
        en: {
          'toast.area5sDenied': 'You have not been granted access to SUPAVUT 5S AREA',
          'nav.area5s': '5S Area',
          'nav.a5sMyWork': 'My Area Work',
          'nav.a5sReview': 'Evaluate',
          'nav.a5sManage': 'Manage Areas',
          'nav.a5sMembers': 'System Settings',
          'nav.a5sRounds': 'Monthly rounds',
          'nav.a5sDownloads': 'Download documents',
          'a5s.noRound.title': 'No active 5S monthly round. Work can be saved and submitted after a round is opened.',
          'a5s.noRound.help': 'The system will be available as soon as an administrator opens the monthly round.',
          'a5s.noRound.open': 'Open a round',
          'a5s.noRound.backSystems': 'Back to all systems',
          'a5s.common.overview': 'Overview',
          'a5s.common.backOverview': 'Back to overview',
          'a5s.common.back': 'Back',
          'a5s.common.zone': 'Zone',
          'a5s.common.building': 'Building',
          'a5s.common.backMyWork': 'Back to my area work',
          'a5s.common.backReview': 'Back to evaluation',
          'a5s.common.layout': 'Area',
          'a5s.common.pointDetail': 'Point details',
          'a5s.common.noPoint': 'No points in this area yet',
          'a5s.common.point': 'Point',
          'a5s.common.area': 'Area',
          'a5s.common.status': 'Status',
          'a5s.common.amount': 'Count',
          'a5s.common.close': 'Close',
          'a5s.common.fitImage': 'Fit image',
          'a5s.common.resetZoom': 'Reset zoom',
          'a5s.common.zoomTools': 'Zoom controls',
          'a5s.common.selectPoint': 'Select point',
          'a5s.common.selectFloor': 'Select floor',
          'a5s.common.viewProfile': 'View profile photo',
          'a5s.common.remove': 'Remove',
          'a5s.common.removeImage': 'Remove image',
          'a5s.common.removeFile': 'Remove file',
          'a5s.common.imageNumber': 'Image',
          'a5s.common.selectYear': 'Select announcement year',
          'a5s.common.viewAnnouncement': 'View 5S announcement',
          'a5s.common.layoutPosition': 'Area location',
          'a5s.common.closeForm': 'Close form',
          'a5s.common.note': 'Note',
          'a5s.common.read': 'Read',
          'a5s.common.employeeCode': 'Employee ID',
          'a5s.common.position': 'Position',
          'a5s.common.department': 'Department',
          'a5s.common.detail': 'Details',
          'a5s.common.cards': 'Items',
          'a5s.common.round': 'Round',
          'a5s.common.deadline': 'Deadline',
          'a5s.common.sentAt': 'Submitted at',
          'a5s.common.submitCount': 'Submission count',
          'a5s.common.assignees': 'Assignees',
          'a5s.common.evaluators': 'Evaluators',
          'a5s.common.you': 'You',
          'a5s.common.by': 'By',
          'a5s.common.updated': 'Updated',
          'a5s.common.totalPoints': 'Total points in area',
          'a5s.common.noLayout': 'No areas in the system yet',
          'a5s.common.noImage': 'No image',
          'a5s.common.noResult': 'No areas match the filters',
          'a5s.common.noAssignee': 'No assignee for this point yet',
          'a5s.common.noAssignees': 'No assignees yet',
          'a5s.common.noEvaluator': 'No evaluators yet',
          'a5s.common.noData': 'No data',
          'a5s.common.noDataBody': 'This point has not been submitted by the assignee.',
          'a5s.common.noCards': 'No submitted items yet',
          'a5s.common.waitCards': 'Waiting for the assignee to add details and submit.',
          'a5s.common.noDetail': 'No saved details yet',
          'a5s.common.viewPhoto': 'View photo',
          'a5s.common.saved': 'Saved',
          'a5s.common.error': 'Error',
          'a5s.status.not_started': 'Not started',
          'a5s.status.draft': 'Draft',
          'a5s.status.submitted': 'Pending review',
          'a5s.status.resubmitted': 'Pending review',
          'a5s.status.failed': 'Rejected',
          'a5s.status.passed': 'Passed',
          'a5s.status.no_data': 'No data',
          'a5s.manage.title': 'Manage Areas',
          'a5s.manage.createTitle': 'Create New Area',
          'a5s.manage.createHint': 'Click + to add an area, then fill in the details',
          'a5s.manage.createModalHint': 'Pick a floor, name it, then upload the area image.',
          'a5s.manage.layoutName': 'Area name',
          'a5s.common.roomName': 'Room name',
          'a5s.manage.layoutNamePlaceholder': 'Example: Factory 1 production floor',
          'a5s.manage.description': 'Description (optional)',
          'a5s.manage.descriptionPlaceholder': 'Area scope / note',
          'a5s.manage.image': 'Area image',
          'a5s.manage.createButton': '+ Create Area',
          'a5s.manage.cancel': 'Cancel',
          'a5s.manage.floor': 'Select floor',
          'a5s.manage.backZones': 'Back to zones',
          'a5s.manage.backSubZones': 'Back to sub-zones',
          'a5s.manage.unmappedTitle': 'Areas not yet assigned to a floor',
          'a5s.manage.unmappedNote': 'Not linked to a floor yet — still editable; an Admin links the floor from the mapping page.',
          'a5s.manage.noFloorInZone': 'This zone has no floors yet — ask an Admin to set them up first.',
          'a5s.manage.pickSubZoneTitle': 'Select sub-zone',
          'a5s.manage.pickSubZoneHint': 'Select an Admin-defined sub-zone image/sub-area before creating an area.',
          'a5s.manage.subZonePageTitle': 'Select sub-area/building',
          'a5s.manage.subZonePageHint': 'Click a frame or building name on the sub-zone image to manage areas inside that building.',
          'a5s.manage.noSubZoneInZone': 'This zone has no sub-zone images yet.',
          'a5s.manage.noAreaInSubZone': 'No sub-areas in this image yet.',
          'a5s.manage.selectSubZoneFirst': 'Select a sub-zone before creating an area.',
          'a5s.manage.noFloorInSubZone': 'This sub-zone has no floors yet — ask an Admin to set them up first.',
          'a5s.manage.zone': 'Select zone',
          'a5s.manage.locationLabel': 'Location',
          'a5s.manage.subZone': 'Select sub-zone',
          'a5s.manage.editPlan': 'Edit company plan',
          'a5s.manage.pickZoneTitle': 'Select area',
          'a5s.manage.pickZoneHint': 'Click or tap an area on the image to select that zone\'s sub-area/building.',
          'a5s.manage.noPlanAdmin': 'No company plan yet — go to "Edit company plan" to upload an image and draw zones.',
          'a5s.manage.noPlanAllocator': 'No company plan yet — waiting for an Admin to set up buildings/zones.',
          'a5s.manage.noZoneYet': 'No zones on this plan yet',
          'a5s.manage.companyName': 'Supavut Industry Co., Ltd.',
          'a5s.manage.unmappedHint': 'There are',
          'a5s.manage.unmappedHint2': 'existing areas not linked to a floor yet — click to manage',
          'a5s.plan.title': 'Company Plan',
          'a5s.plan.backManage': 'Back',
          'a5s.plan.mappingLink': 'Legacy mapping',
          'a5s.plan.noPlanYet': 'No plan image yet — upload one to start drawing zones',
          'a5s.plan.chooseFile': 'Choose file',
          'a5s.plan.step1': 'Draw zones',
          'a5s.plan.step2': 'Pick a sub-zone',
          'a5s.plan.step3': 'Sub-areas + floors',
          'a5s.plan.noSubZoneMaps': 'No sub-zones yet',
          'a5s.plan.upload': 'Upload',
          'a5s.plan.stageHint': 'Zoom in → click "+ Add zone" → click around the area · click an existing zone to edit',
          'a5s.plan.zoomHint': 'Wheel = zoom · Drag = pan',
          'a5s.plan.undoPoint': 'Undo point',
          'a5s.plan.changeImageHint': 'Change plan image',
          'a5s.plan.noZonesYet': 'No zones yet — click "+ Add zone"',
          'a5s.plan.drawNewZone': '+ Add zone',
          'a5s.plan.drawHint': 'Click on the image point by point around the area (at least 3 points)',
          'a5s.plan.finishDraw': 'Finish',
          'a5s.plan.needThreePoints': 'Need at least 3 points',
          'a5s.plan.saveFailed': 'Save failed',
          'a5s.plan.colorSaveFailed': 'Failed to save color',
          'a5s.plan.allZones': 'All zones',
          'a5s.plan.zoneColor': 'Choose zone color',
          'a5s.plan.selectedOverviewZone': 'Selected zone',
          'a5s.plan.selectedOverviewZoneHint': 'Use this frame to define the boundary for sub-zones/buildings, and edit the main zone details here.',
          'a5s.plan.zoneData': 'Zone details',
          'a5s.plan.zoneName': 'Name',
          'a5s.plan.zoneNamePlaceholder': 'Zone 1',
          'a5s.plan.zoneLabel': 'Zone',
          'a5s.plan.floors': 'Floors in this zone',
          'a5s.plan.floorsInArea': 'Floors',
          'a5s.plan.floorsUnit': 'floors',
          'a5s.plan.floorNamePlaceholder': 'e.g. Floor 4',
          'a5s.plan.addFloor': '+ Floor',
          'a5s.plan.addFloorFailed': 'Failed to add floor',
          'a5s.plan.resetZone': 'Reset zone',
          'a5s.plan.resetZoneConfirm': 'Reset this zone? The system will detach areas linked to floors in this zone and remove the old floors from the zone.',
          'a5s.plan.resetDone': 'Zone reset. You can delete this zone now.',
          'a5s.plan.resetFailed': 'Failed to reset zone',
          'a5s.plan.deleteZone': 'Delete this zone',
          'a5s.plan.deleteOverviewZone': 'Delete zone',
          'a5s.plan.deleteZoneConfirm': 'Delete this zone?',
          'a5s.plan.deleteFailed': 'Delete failed',
          'a5s.plan.noFloors': 'No floors yet',
          'a5s.plan.noSelection': 'Select a zone from the image or list to manage it',
          'a5s.plan.subZoneMaps': 'Sub-zones',
          'a5s.plan.subZoneMapsHint': 'Attach an image, then draw sub-areas',
          'a5s.plan.addSubZone': 'Add sub-zone',
          'a5s.plan.editSubZone': 'Edit sub-zone',
          'a5s.plan.deleteSubZone': 'Delete sub-zone',
          'a5s.plan.deleteSubZoneConfirm': 'Delete this sub-zone?',
          'a5s.plan.subZoneName': 'Sub-zone name',
          'a5s.plan.subZoneImage': 'Sub-zone image',
          'a5s.plan.saveSubZone': 'Save sub-zone',
          'a5s.plan.subZoneNeedImage': 'Please attach a sub-zone image.',
          'a5s.plan.subZoneSaveFailed': 'Failed to save sub-zone',
          'a5s.plan.subZoneDeleteFailed': 'Failed to delete sub-zone',
          'a5s.plan.subZoneEditorHint': 'Draw sub-areas on the image, then add floors',
          'a5s.plan.subZoneDrawHint': 'Zoom/drag the image → click "+ Add sub-area" → click around it',
          'a5s.plan.drawSubZoneArea': '+ Add sub-area',
          'a5s.plan.subZoneAreaDrawHint': 'Click on the image point by point to draw a sub-area, at least 3 points.',
          'a5s.plan.subZoneAreas': 'Sub-areas',
          'a5s.plan.subZoneAreaColor': 'Choose sub-area color',
          'a5s.plan.areasUnit': 'areas',
          'a5s.plan.noSubZoneAreas': 'No sub-areas yet',
          'a5s.plan.subZoneAreaData': 'Selected sub-area',
          'a5s.plan.subZoneAreaName': 'Name',
          'a5s.plan.deleteSubZoneArea': 'Delete sub-area',
          'a5s.plan.deleteSubZoneAreaConfirm': 'Delete this sub-area?',
          'a5s.plan.noSubZoneAreaSelection': 'Select a sub-area to edit',
          'a5s.mapping.title': 'Review/fix legacy mapping',
          'a5s.mapping.backPlan': 'Back to company plan',
          'a5s.mapping.hint': 'Assign a zone, sub-area, and floor to each existing area — floor is auto-guessed from the name, but zone/sub-area must be chosen manually.',
          'a5s.mapping.onlyUnmapped': 'Show only unmapped',
          'a5s.mapping.noZoneYet': 'No zones yet — go to the company plan page to draw zones/floors first',
          'a5s.mapping.allMapped': 'All areas are already mapped',
          'a5s.mapping.round': 'Round',
          'a5s.mapping.guessed': 'Guessed floor',
          'a5s.mapping.zone': 'Zone',
          'a5s.mapping.area': 'Sub-area',
          'a5s.mapping.floor': 'Floor',
          'a5s.mapping.selectZone': '— Select zone —',
          'a5s.mapping.selectArea': '— Select sub-area —',
          'a5s.mapping.selectFloor': '— Select floor —',
          'a5s.mapping.noGuess': 'Could not guess',
          'a5s.mapping.saveAll': 'Save all mappings',
          'a5s.index.unmappedZone': 'Not yet assigned',
          'a5s.myWork.planHintWork': 'Building overview — your tasks in each building, click to see the list below',
          'a5s.myWork.planHintReview': 'Building overview — points to review in each building, click to see the list below',
          'a5s.myWork.planNoWork': 'No work',
          'a5s.myWork.reportStatus': 'Assigned work status',
          'a5s.myWork.mineHere': 'My work',
          'a5s.myWork.mineHereReview': 'To review',
          'a5s.myWork.statusData': 'Status summary',
          'a5s.myWork.openAreaWork': 'Open this area’s work list',
          'a5s.myWork.openAreaReview': 'Open this area’s evaluation list',
          'a5s.myWork.openAreaHint': 'Select an area on the map to view its work list below',
          'a5s.myWork.openAreaPageHint': 'Select a building on the map to open its area list',
          'a5s.manage.editPin': 'Edit',
          'a5s.manage.edit': 'Edit',
          'a5s.manage.closeUse': 'Off',
          'a5s.manage.openUse': 'On',
          'a5s.manage.delete': 'Delete',
          'a5s.manage.disabled': 'Disabled',
          'a5s.manage.off': 'Off',
          'a5s.manage.nameLabel': 'Area name',
          'a5s.manage.updatedLabel': 'Updated',
          'a5s.manage.addedByLabel': 'Added by',
          'a5s.manage.viewLive': 'View live page',
          'a5s.manage.adminView': 'Admin',
          'a5s.manage.adminTitle': 'All areas',
          'a5s.manage.adminHint': 'View and manage areas created by everyone.',
          'a5s.manage.mineTitle': 'Areas I created',
          'a5s.manage.mineHint': 'Areas created by your account.',
          'a5s.manage.myLayoutTitle': 'My areas',
          'a5s.manage.myLayoutHint': 'Area allocators can edit only areas they created.',
          'a5s.manage.emptyMine': 'You have not created any areas yet.',
          'a5s.manage.emptyFirst': 'No areas yet — press + to create one.',
          'a5s.manage.createdTitle': 'Area created',
          'a5s.manage.createdBody': 'Press "Edit" to pin the area points.',
          'a5s.manage.ok': 'OK',
          'a5s.index.title': '5S Area Board',
          'a5s.index.subtitle': 'Choose an area to view the map, points, and assignees.',
          'a5s.index.search': 'Search area name',
          'a5s.index.all': 'All',
          'a5s.index.mine': 'Assigned to me',
          'a5s.index.showing': 'Showing',
          'a5s.index.responsibleBadge': 'You are assigned',
          'a5s.index.floor': 'Floor',
          'a5s.index.floorOther': 'Others',
          'a5s.index.planTitle': 'Company plan and all zones',
          'a5s.index.planHint': 'View the main zones and Admin-defined sub-zone images before opening each area.',
          'a5s.index.noSubArea': 'No sub-areas yet',
          'a5s.index.unmappedArea': 'Sub-area not linked yet',
          'a5s.home.title': 'Home',
          'a5s.home.heading': 'Let’s make 5S a daily habit, together',
          'a5s.home.subtitle': 'A clean, orderly, safe workplace starts with each of us — read the 5S practice guide below.',
          'a5s.home.scrollHint': 'Scroll to read the guide · tap an image for details',
          'a5s.home.tapZoom': 'Tap to view full-screen details',
          'a5s.home.announce': 'Announcements',
          'a5s.welcome.hello': 'Hello, ',
          'a5s.welcome.welcomeTo': 'Welcome to SUPAVUT 5S Area',
          'a5s.welcome.desc': 'This system promotes cleanliness, orderliness and safety, and builds good working habits for our organization.',
          'a5s.welcome.dontToday': 'Don’t show today',
          'a5s.welcome.ack': 'Got it',
          'a5s.editor.allPoints': 'All points',
          'a5s.editor.editLayout': 'Edit Area',
          'a5s.editor.backManage': 'Back to area management',
          'a5s.editor.layoutName': 'Area name',
          'a5s.editor.description': 'Description',
          'a5s.editor.changeImage': 'Change image',
          'a5s.editor.upload': 'Upload',
          'a5s.editor.trailZone': 'Zone',
          'a5s.editor.trailArea': 'Building',
          'a5s.editor.trailFloor': 'Floor',
          'a5s.editor.trailNone': 'Not mapped yet',
          'a5s.editor.stageHint': '+ to add · drag to move · click to edit',
          'a5s.editor.addPoint': 'Add point',
          'a5s.editor.pointData': 'Point data',
          'a5s.editor.pointCode': 'Point code',
          'a5s.editor.areaName': 'Point name',
          'a5s.editor.assigneeLabel': 'Assignees',
          'a5s.editor.evaluatorLabel': 'Evaluators',
          'a5s.editor.searchPeople': 'Search employees',
          'a5s.editor.deletePoint': 'Delete point',
          'a5s.editor.done': 'Done',
          'a5s.editor.noSelection': 'Select a point to edit',
          'a5s.editor.noPeople': 'No people yet',
          'a5s.editor.noAssignees': 'No assignees yet',
          'a5s.editor.noEvaluators': 'No evaluators yet',
          'a5s.editor.peopleUnit': 'people',
          'a5s.editor.addPointFailed': 'Could not add point',
          'a5s.editor.deletePointConfirm': 'Delete point :code?',
          'a5s.editor.deletePointFailed': 'Could not delete point',
          'a5s.editor.saveFailed': 'Could not save',
          'a5s.editor.addAssigneeFailed': 'Could not add assignee',
          'a5s.editor.addEvaluatorFailed': 'Could not add evaluator',
          'a5s.rounds.title': 'Monthly Rounds',
          'a5s.rounds.hint': 'Only one round can be open at a time. Opening a new month closes the current round automatically; older months can be reopened for review or edits.',
          'a5s.rounds.openRound': 'Open round:',
          'a5s.rounds.noOpenRound': 'No round is open yet',
          'a5s.rounds.prevYear': 'Previous year',
          'a5s.rounds.nextYear': 'Next year',
          'a5s.rounds.yearPrefix': 'Year',
          'a5s.rounds.stateOpen': 'Open',
          'a5s.rounds.stateClosed': 'Closed',
          'a5s.rounds.stateNotOpen': 'Not opened',
          'a5s.rounds.passed': 'Passed',
          'a5s.rounds.rejected': 'Rejected',
          'a5s.rounds.waiting': 'Waiting',
          'a5s.rounds.points': 'points',
          'a5s.rounds.noPointData': 'No point data in this round yet',
          'a5s.rounds.close': 'Close round',
          'a5s.rounds.setupBtn': "Set up round",
          'a5s.rounds.editInspections': "Edit \/ add inspections",
          'a5s.rounds.setupTitle': "How many inspections in",
          'a5s.rounds.setupHint': "Enter each inspection date and confirm — created first (not opened), open each one when the day comes.",
          'a5s.rounds.addInspection': "+ Add inspection",
          'a5s.rounds.notSetup': "No inspections set",
          'a5s.rounds.statePlanned': "Planned",
          'a5s.rounds.openDo': "Open",
          'a5s.rounds.hasWork': "has work",
          'a5s.rounds.deleteInspection': 'Delete inspection',
          'a5s.rounds.hint2': "Open one inspection at a time — opening a new one closes the current · a month can have several (with dates)",
          'a5s.rounds.open': 'Open round',
          'a5s.rounds.reopen': 'Reopen round',
          'a5s.rounds.openNew': '+ Open new inspection',
          'a5s.rounds.times': 'inspections',
          'a5s.rounds.openConfirm': 'Open round :target?',
          'a5s.rounds.openConfirmWithClose': 'Open round :target?\nThe currently open round :open will close automatically.',
          'a5s.rounds.closeConfirm': 'Close round :target?\nAfter closing, points cannot be saved or submitted until the round is reopened.',
          'a5s.rounds.openOneConfirm': 'Open {label}?\nThe currently open inspection will close automatically.',
          'a5s.rounds.deleteConfirm': 'Delete inspection {label}?',
          'a5s.rounds.closeConfirm2': 'Close {label}?\nOnce closed, work cannot be saved or submitted until it is reopened.',
          'a5s.month.1': 'January',
          'a5s.month.2': 'February',
          'a5s.month.3': 'March',
          'a5s.month.4': 'April',
          'a5s.month.5': 'May',
          'a5s.month.6': 'June',
          'a5s.month.7': 'July',
          'a5s.month.8': 'August',
          'a5s.month.9': 'September',
          'a5s.month.10': 'October',
          'a5s.month.11': 'November',
          'a5s.month.12': 'December',
          'a5s.downloads.title': 'Download Documents',
          'a5s.downloads.hint': '5S evaluation results for the selected monthly round. Excel file (.xlsx).',
          'a5s.downloads.downloadExcel': 'Download Excel',
          'a5s.downloads.downloadYear': 'Download whole year',
          'a5s.index.pastRound': 'Viewing a past month (read-only)',
          'a5s.downloads.selectRound': 'Select monthly round',
          'a5s.downloads.downloadCsv': 'Download CSV',
          'a5s.downloads.noRound': 'No monthly rounds yet. Open the first round from the Monthly menu.',
          'a5s.downloads.noRows': 'This round has no point data yet',
          'a5s.downloads.pointName': 'Point name',
          'a5s.downloads.evaluator': 'Evaluator',
          'a5s.downloads.evaluatedAt': 'Evaluated date',
          'a5s.downloads.latestSubmitted': 'Latest submission',
          'a5s.downloads.notSent': 'Not submitted',
          'a5s.myWork.title': 'My Area Work',
          'a5s.myWork.layoutsTitle': 'Areas with your points',
          'a5s.myWork.layoutsHint': 'Choose an area to see assigned points and add work details with photos.',
          'a5s.myWork.reviewTitle': 'Evaluate',
          'a5s.myWork.reviewHint': 'Areas assigned to you as evaluator. Open an area and pass or reject submitted points.',
          'a5s.myWork.reviewAreaTitle': 'Areas to evaluate',
          'a5s.myWork.reviewAreaHint': 'Choose an area to review assigned points and record evaluation results.',
          'a5s.myWork.evaluationStatus': 'Evaluation status',
          'a5s.myWork.cardsRecorded': 'Recorded items',
          'a5s.myWork.rejectNote': 'Rejection note',
          'a5s.myWork.passNote': 'Evaluator note',
          'a5s.myWork.noPointDisplay': 'No points to display yet',
          'a5s.myWork.noAssigned': 'No area points assigned to you yet',
          'a5s.myWork.noReviewAssigned': 'No areas assigned to you for evaluation yet',
          'a5s.myWork.subZoneWorkTitle': 'Sub-zone maps for your work',
          'a5s.myWork.subZoneWorkHint': 'Shows only sub-areas and points assigned to you.',
          'a5s.myWork.subZoneReviewTitle': 'Sub-zone maps to evaluate',
          'a5s.myWork.subZoneReviewHint': 'Shows only sub-areas and points you can evaluate.',
          'a5s.work.add': 'Add',
          'a5s.work.addCard': 'Add',
          'a5s.work.editCard': 'Edit',
          'a5s.work.addCardNote': 'Enter work details and attach at least one photo.',
          'a5s.work.editCardNote': 'Edit the title, details, or photos.',
          'a5s.work.cardTitle': 'Title',
          'a5s.work.cardTitlePlaceholder': 'Example: Clean work desk',
          'a5s.work.cardDetail': 'Details',
          'a5s.work.cardDetailPlaceholder': 'Work performed or note',
          'a5s.work.attach': 'Attach photos (JPG / PNG / WEBP / HEIC)',
          'a5s.work.attachMore': 'Attach more photos (optional)',
          'a5s.work.save': 'Save',
          'a5s.work.submit': 'Submit for review',
          'a5s.work.resubmit': 'Submit again',
          'a5s.work.updateSubmit': 'Submit update',
          'a5s.work.sentCount': 'Submitted :count times',
          'a5s.work.reason': 'Reason:',
          'a5s.work.note': 'Note:',
          'a5s.work.advice': 'Advice:',
          'a5s.work.noCards': 'No items yet',
          'a5s.work.addFirst': 'Click + to add the first item',
          'a5s.work.maxImages': 'Maximum 8 images per item',
          'a5s.work.workspaceHint': 'Choose a building and status, then select an area name to view your assigned points.',
          'a5s.work.scoreSummary': 'My area work scores',
          'a5s.work.filterLabel': 'My area work status',
          'a5s.work.todo': 'To do',
          'a5s.work.pendingReview': 'Pending review',
          'a5s.work.needsRevision': 'Needs revision',
          'a5s.work.completed': 'Completed',
          'a5s.work.buildingHint': 'Confirm the building image and floor before opening your work point.',
          'a5s.work.layoutCount': 'assigned areas',
          'a5s.work.pointCount': 'work points',
          'a5s.work.myWorkData': 'My work points',
          'a5s.work.latestUpdate': 'Latest update',
          'a5s.work.notStarted': 'Not started',
          'a5s.work.noItemsForStatus': 'No work with this status',
          'a5s.work.noItemsHint': 'Choose another status to see all assigned work points.',
          'a5s.history.noItemsHint': 'Choose another status to see every point in this building.',
          'a5s.workspace.all': 'All',
          'a5s.workspace.passed': 'Passed',
          'a5s.workspace.failed': 'Failed',
          'a5s.workspace.pending': 'Pending',
          'a5s.workspace.noData': 'No data yet',
          'a5s.workspace.filters': 'Select building, month, and inspection date',
          'a5s.workspace.buildingTabs': 'Select building',
          'a5s.workspace.roundInfo': 'Current inspection round',
          'a5s.workspace.month': 'Month',
          'a5s.workspace.subRound': 'Sub-round',
          'a5s.workspace.roundNumber': 'Round',
          'a5s.workspace.openedOn': 'Opened on',
          'a5s.workspace.monthHistory': 'Select a month to view',
          'a5s.workspace.showMonth': 'Show data',
          'a5s.workspace.currentMonth': 'Showing the currently active month',
          'a5s.workspace.historyReadOnly': 'Historical data · Read only',
          'a5s.workspace.viewHistoricalData': 'View this month',
          'a5s.workspace.selectedInspection': 'Selected inspection date',
          'a5s.workspace.inspectionDate': 'Inspection date',
          'a5s.workspace.noBuildingData': 'No data',
          'a5s.workspace.noBuildingDataHint': 'This building has no data for the selected inspection date.',
          'a5s.review.title': 'Evaluate',
          'a5s.review.workspaceTitle': 'Evaluation queue',
          'a5s.review.workspaceHint': 'Choose a building and status, then select an area name to view evaluation data.',
          'a5s.review.pointsInScope': 'points in your scope',
          'a5s.review.filterLabel': 'Evaluation status',
          'a5s.review.ready': 'Ready to review',
          'a5s.review.waitingSubmit': 'Not submitted',
          'a5s.review.waitingFix': 'Waiting for revision',
          'a5s.review.completed': 'Completed',
          'a5s.review.buildingHint': 'Confirm the building image and floor before opening an evaluation point.',
          'a5s.review.floorCount': 'floors',
          'a5s.review.layoutCount': 'evaluation areas',
          'a5s.review.pointCount': 'evaluation points',
          'a5s.review.floorTabs': 'Building floors',
          'a5s.review.evaluationData': 'Evaluation data',
          'a5s.review.latestSubmit': 'Latest submission',
          'a5s.review.morePeople': 'more',
          'a5s.review.submissionNumber': 'Submission',
          'a5s.review.notSubmitted': 'No submission yet',
          'a5s.review.openReview': 'Open review',
          'a5s.review.noItemsForStatus': 'No items with this status',
          'a5s.review.noItemsHint': 'Choose another status to see all items in your evaluation scope.',
          'a5s.review.pendingDecision': 'Pending evaluation',
          'a5s.review.submittedBy': 'Submitted by',
          'a5s.review.evaluatedBy': 'Evaluated by',
          'a5s.review.submittedContent': 'Submitted content',
          'a5s.review.noHistory': 'No submission history yet',
          'a5s.review.noHistoryHint': 'History will appear after an assignee submits data for evaluation.',
          'a5s.review.headingHint': 'Select an assigned point, then record pass or rejection from submitted items.',
          'a5s.review.pointsToReview': 'points to review',
          'a5s.review.submittedAt': 'Submitted:',
          'a5s.review.submitCount': 'Submission:',
          'a5s.review.pass': 'Pass',
          'a5s.review.reject': 'Reject',
          'a5s.review.rejectReason': 'Rejection reason',
          'a5s.review.rejectPlaceholder': 'Describe what must be corrected',
          'a5s.review.saveReject': 'Save rejection',
          'a5s.review.confirmPassTitle': 'Confirm pass',
          'a5s.review.confirmRejectTitle': 'Confirm rejection',
          'a5s.review.attachNote': 'Attach a note',
          'a5s.review.notePlaceholder': 'Write a note to the responsible person',
          'a5s.review.confirm': 'Confirm',
          'a5s.review.failedPrefix': 'Rejected:',
          'a5s.review.editDecision': 'Edit evaluation',
          'a5s.review.editConfirmTitle': 'Edit evaluation',
          'a5s.review.editConfirmBody': 'Edit the evaluation result for this point?',
          'a5s.review.editConfirmOk': 'Edit',
          'a5s.review.submitHistory': 'Submission history',
          'a5s.review.score': 'Score',
          'a5s.score.thisRound': 'This round',
          'a5s.score.short': 'Score',
          'a5s.score.selectedMonth': 'Selected month score',
          'a5s.score.fromPoints': 'Evaluated',
          'a5s.score.fromRounds': 'Average of',
          'a5s.score.pointAverage': 'Average of attempts',
          'a5s.calendar.title': 'Score calendar',
          'a5s.calendar.open': 'Open score calendar',
          'a5s.calendar.total': 'Overall',
          'a5s.calendar.monthTotal': 'Month total',
          'a5s.calendar.dayTotal': 'Day total',
          'a5s.calendar.round': 'Round',
          'a5s.calendar.openRound': 'Open',
          'a5s.calendar.closedRound': 'Closed',
          'a5s.calendar.evaluations': 'evaluations',
          'a5s.calendar.empty': 'No evaluation dates yet',
          'a5s.calendar.emptyMonth': 'No evaluation dates in this month',
          'a5s.units.round': 'rounds',
          'a5s.myWork.tasksTitle': 'My tasks',
          'a5s.myWork.reviewTasksTitle': 'To evaluate',
          'a5s.myWork.planToggle': 'Factory plan',
          'a5s.myWork.planToggleHint': 'Open to see where the work is',
          'a5s.score.overall': 'Overall',
          'a5s.review.attemptNo': 'Attempt',
          'a5s.review.times': 'times',
          'a5s.common.evaluatedDate': 'Evaluated',
          'a5s.common.viewLayout': 'View area',
          'a5s.common.action': 'Action',
          'a5s.round.month': 'Month',
          'a5s.round.inspection': 'Inspection',
          'a5s.round.seqShort': 'Round',
          'a5s.round.active': 'Active',
          'a5s.round.viewOnly': 'Closed round · history view only',
          'a5s.myWork.openWork': 'Open work',
          'a5s.history.hintWork': 'Pick an inspection to view the evaluation history of the points you are responsible for in that round.',
          'a5s.history.hintReview': 'Pick an inspection to view the evaluation history of the points you are assigned to review.',
          'a5s.history.hintOverview': 'Pick an inspection to view the evaluation history of every point in this area.',
          'a5s.common.history': 'History',
          'a5s.common.historySubmit': 'Submission history',
          'a5s.common.view': 'View',
          'a5s.common.noteEvaluator': 'Note (evaluator)',
          'a5s.common.name': 'Name',
          'a5s.common.pointName': 'Point name',
          'a5s.index.tapPlan': 'Tap to view buildings',
          'a5s.index.backPlan': 'Back',
          'a5s.index.emptyArea': 'No data in this building yet',
          'a5s.work.sent': 'Submitted',
          'a5s.work.editReport': 'Edit report',
          'a5s.work.editReportDone': 'Done editing',
          'a5s.work.editReportConfirm': 'Edit the report for this point?',
          'a5s.work.submitConfirm': 'Send this point to the evaluator?',
          'a5s.history.empty': 'No submission history yet',
          'a5s.review.noSubmittedContent': 'No submitted details',
          'a5s.review.failedWaiting': 'This point was rejected. Waiting for the assignee to revise and resubmit.',
          'a5s.review.waitSubmit': 'Waiting for assignee submission',
          'a5s.review.outOfScope': 'This point is outside your evaluation scope.',
          'a5s.review.queueHeading': '5S Area Evaluation',
          'a5s.review.queueHint': 'Shows only points assigned to you for evaluation. Pass or reject with a reason.',
          'a5s.review.noActiveRound': 'No active round yet',
          'a5s.review.tab.pending': 'Pending',
          'a5s.review.tab.failed': 'Rejected',
          'a5s.review.tab.passed': 'Passed',
          'a5s.review.tab.draft': 'Not submitted',
          'a5s.review.check': 'Evaluate',
          'a5s.review.viewDetail': 'View details',
          'a5s.review.emptyCategory': 'No work in this category',
          'a5s.review.backQueue': 'Back to queue',
          'a5s.review.latestFailReason': 'Latest rejection reason:',
          'a5s.review.passedDone': 'Evaluation passed',
          'a5s.review.when': 'at',
          'a5s.review.noCardsInTask': 'No items in this task yet',
          'a5s.review.decisionTitle': 'Evaluation result',
          'a5s.review.failReasonRequired': 'Rejection reason (required)',
          'a5s.review.adviceOptional': 'Additional advice (optional)',
          'a5s.review.advicePlaceholder': 'Advice for the assignee',
          'a5s.review.decisionNote': 'Pass = this month’s work for this point is complete and locked. Reject = assignees can revise and resubmit until the round closes.',
          'a5s.review.saveDecision': 'Save evaluation result',
          'a5s.settings.title': 'User permissions',
          'a5s.settings.memberHint': 'Search employees, choose a role, then add them. One person can have multiple roles. Insight admins automatically administer this system.',
          'a5s.settings.roleAdmin': 'Administrator',
          'a5s.settings.roleAllocator': 'Area allocator',
          'a5s.settings.roleAdminOption': 'Administrator (Admin)',
          'a5s.settings.roleAllocatorOption': 'Area allocator (Allocator)',
          'a5s.settings.revoke': 'Revoke permission',
          'a5s.settings.none': 'None yet',
          'a5s.settings.assignTitle': 'Point assignees',
          'a5s.settings.assignHint': 'Select a sub-area or building, then choose an area grouped by floor to manage point assignees.',
          'a5s.settings.pointClosed': 'Point closed',
          'a5s.settings.noAssignees': 'No assignees yet',
          'a5s.settings.assigneesLabel': 'Assignees',
          'a5s.settings.evaluatorsLabel': 'Evaluators',
          'a5s.settings.noEvaluators': 'No evaluators yet',
          'a5s.settings.searchAddEvaluator': 'Search employees to add evaluator',
          'a5s.settings.removeEvaluator': 'Remove evaluator',
          'a5s.settings.addEvaluatorFailed': 'Could not add evaluator',
          'a5s.settings.removeEvaluatorFailed': 'Could not remove evaluator',
          'a5s.settings.layoutWide': 'Whole area',
          'a5s.settings.noPointsInLayout': 'This area has no points yet. Pin points from Area Management first.',
          'a5s.settings.pickLayout': 'Select an area to manage point assignees',
          'a5s.settings.noLayoutInArea': 'This building has no areas yet',
          'a5s.settings.unmappedArea': 'Area not assigned',
          'a5s.settings.unmappedFloor': 'Floor not assigned',
          'a5s.settings.hideDetail': 'Hide details',
          'a5s.settings.searchAdd': 'Search employee to add',
          'a5s.settings.removeAssignee': 'Remove assignee',
          'a5s.settings.addAssigneeFailed': 'Could not add assignee',
          'a5s.settings.removeAssigneeFailed': 'Could not remove assignee',
          'a5s.settings.positionAccess': 'Positions allowed to access the system',
          'a5s.settings.positionHint': 'Tick positions from Bplus employee data that can access the system. If never saved, all positions are allowed. Assigned assignees/evaluators can always access regardless of position.',
          'a5s.settings.selectAll': 'Select all',
          'a5s.settings.clearAll': 'Clear',
          'a5s.settings.savePosition': 'Save positions',
          'a5s.units.point': 'points',
          'a5s.units.personAssigned': 'assigned people',
          'a5s.units.layout': 'Area',
          'a5s.area.title': 'Sub-area',
          'a5s.area.openDetail': 'Open sub-area page',
          'a5s.area.openZone': 'Open this zone’s sub-areas',
          'a5s.__loose': {
            'ภาพรวม': 'Overview',
            'จัดการพื้นที่': 'Manage Areas',
            'สิทธิ์ผู้ใช้': 'User permissions',
            'งานพื้นที่ของฉัน': 'My Area Work',
            'ตรวจประเมิน': 'Evaluate',
            'ผู้รับผิดชอบ': 'Assignees',
            'ผู้ประเมิน': 'Evaluators',
            'รหัสพนักงาน': 'Employee ID',
            'ชื่อ': 'Name',
            'ตำแหน่ง': 'Position',
            'รายละเอียด': 'Details',
            'สถานะ': 'Status',
            'หมายเหตุ': 'Note',
            'อ่าน': 'Read',
            'ผ่าน': 'Passed',
            'ปฏิเสธ': 'Rejected',
            'รอดำเนินการ': 'Pending review',
            'ยังไม่มีข้อมูล': 'No data',
            'เพิ่ม': 'Add',
            'แก้ไข': 'Edit',
            'ลบ': 'Delete',
            'บันทึก': 'Save',
            'ยกเลิก': 'Cancel',
            'ตกลง': 'OK'
          }
        },
        my: {
          'toast.area5sDenied': 'SUPAVUT 5S AREA အသုံးပြုခွင့် မရှိသေးပါ',
          'nav.area5s': '5S ဧရိယာ',
          'nav.a5sMyWork': 'ကျွန်ုပ်၏ ဧရိယာလုပ်ငန်း',
          'nav.a5sReview': 'စစ်ဆေးအကဲဖြတ်',
          'nav.a5sManage': 'ဧရိယာ စီမံရန်',
          'nav.a5sMembers': 'စနစ်ဆက်တင်',
          'nav.a5sRounds': 'လစဉ် အပတ်',
          'nav.a5sDownloads': 'စာရွက်စာတမ်း ဒေါင်းလုဒ်',
          'a5s.noRound.title': 'ဖွင့်ထားသော 5S လစဉ်ကာလ မရှိသေးပါ။ ကာလဖွင့်ပြီးမှ အလုပ်ကို သိမ်းပြီး စစ်ဆေးရန် ပို့နိုင်သည်။',
          'a5s.noRound.help': 'စီမံသူက လစဉ်ကာလကို ဖွင့်သည်နှင့် စနစ်ကို အသုံးပြုနိုင်မည်။',
          'a5s.noRound.open': 'ကာလ ဖွင့်ရန်',
          'a5s.noRound.backSystems': 'စနစ်အားလုံးသို့ ပြန်သွားရန်',
          'a5s.common.overview': 'အနှစ်ချုပ်',
          'a5s.common.backOverview': 'အနှစ်ချုပ်သို့ ပြန်သွားရန်',
          'a5s.common.back': 'ပြန်သွားရန်',
          'a5s.common.zone': 'ဇုန်',
          'a5s.common.building': 'အဆောက်အအုံ',
          'a5s.common.backMyWork': 'ကျွန်ုပ်၏ ဧရိယာလုပ်ငန်းသို့ ပြန်သွားရန်',
          'a5s.common.backReview': 'စစ်ဆေးအကဲဖြတ်သို့ ပြန်သွားရန်',
          'a5s.common.layout': 'ဧရိယာ',
          'a5s.common.pointDetail': 'အမှတ်အသေးစိတ်',
          'a5s.common.noPoint': 'ဤဧရိယာတွင် အမှတ် မရှိသေးပါ',
          'a5s.common.point': 'အမှတ်',
          'a5s.common.area': 'ဧရိယာ',
          'a5s.common.status': 'အခြေအနေ',
          'a5s.common.amount': 'အရေအတွက်',
          'a5s.common.close': 'ပိတ်ရန်',
          'a5s.common.fitImage': 'ပုံကို အံဝင်ခွင်ကျ ပြရန်',
          'a5s.common.resetZoom': 'မြင်ကွင်းအရွယ် ပြန်သတ်မှတ်ရန်',
          'a5s.common.zoomTools': 'မြင်ကွင်းချဲ့/ချုံ့ ကိရိယာများ',
          'a5s.common.selectPoint': 'အမှတ် ရွေးရန်',
          'a5s.common.selectFloor': 'အထပ် ရွေးရန်',
          'a5s.common.viewProfile': 'ပရိုဖိုင်ပုံ ကြည့်ရန်',
          'a5s.common.remove': 'ဖယ်ရှားရန်',
          'a5s.common.removeImage': 'ပုံ ဖယ်ရှားရန်',
          'a5s.common.removeFile': 'ဖိုင် ဖယ်ရှားရန်',
          'a5s.common.imageNumber': 'ပုံအမှတ်',
          'a5s.common.selectYear': 'ကြေညာချက်နှစ် ရွေးရန်',
          'a5s.common.viewAnnouncement': '5S ကြေညာချက် ကြည့်ရန်',
          'a5s.common.layoutPosition': 'ဧရိယာ တည်နေရာ',
          'a5s.common.closeForm': 'ဖောင် ပိတ်ရန်',
          'a5s.common.note': 'မှတ်ချက်',
          'a5s.common.read': 'ဖတ်ရန်',
          'a5s.common.employeeCode': 'ဝန်ထမ်းကုဒ်',
          'a5s.common.position': 'ရာထူး',
          'a5s.common.department': 'ဌာန',
          'a5s.common.detail': 'အသေးစိတ်',
          'a5s.common.cards': 'မှတ်တမ်းများ',
          'a5s.common.round': 'Round',
          'a5s.common.deadline': 'နောက်ဆုံးရက်',
          'a5s.common.sentAt': 'ပို့ချိန်',
          'a5s.common.submitCount': 'ပို့သည့်အကြိမ်',
          'a5s.common.assignees': 'တာဝန်ရှိသူများ',
          'a5s.common.evaluators': 'စစ်ဆေးသူများ',
          'a5s.common.you': 'သင်',
          'a5s.common.by': 'မှ',
          'a5s.common.updated': 'အပ်ဒိတ်',
          'a5s.common.totalPoints': 'ဧရိယာ ရှိ အမှတ်စုစုပေါင်း',
          'a5s.common.noLayout': 'စနစ်တွင် ဧရိယာ မရှိသေးပါ',
          'a5s.common.noImage': 'ပုံမရှိပါ',
          'a5s.common.noResult': 'စစ်ထုတ်မှုနှင့် ကိုက်ညီသော ဧရိယာ မရှိပါ',
          'a5s.common.noAssignee': 'ဤအမှတ်အတွက် တာဝန်ရှိသူ မရှိသေးပါ',
          'a5s.common.noAssignees': 'တာဝန်ရှိသူ မရှိသေးပါ',
          'a5s.common.noEvaluator': 'စစ်ဆေးသူ မရှိသေးပါ',
          'a5s.common.noData': 'ဒေတာမရှိသေးပါ',
          'a5s.common.noDataBody': 'ဤအမှတ်ကို တာဝန်ရှိသူမှ မပို့သေးပါ။',
          'a5s.common.noCards': 'ပို့ထားသော မှတ်တမ်း မရှိသေးပါ',
          'a5s.common.waitCards': 'တာဝန်ရှိသူမှ အသေးစိတ်ထည့်ပြီး ပို့ရန် စောင့်နေသည်။',
          'a5s.common.noDetail': 'သိမ်းထားသော အသေးစိတ် မရှိသေးပါ',
          'a5s.common.viewPhoto': 'ဓာတ်ပုံကြည့်ရန်',
          'a5s.common.saved': 'သိမ်းပြီး',
          'a5s.common.error': 'အမှား',
          'a5s.status.not_started': 'မစတင်ရသေးပါ',
          'a5s.status.draft': 'မူကြမ်း',
          'a5s.status.submitted': 'စစ်ဆေးရန် စောင့်နေသည်',
          'a5s.status.resubmitted': 'စစ်ဆေးရန် စောင့်နေသည်',
          'a5s.status.failed': 'ပယ်ချထားသည်',
          'a5s.status.passed': 'အောင်မြင်သည်',
          'a5s.status.no_data': 'ဒေတာမရှိသေးပါ',
          'a5s.manage.title': 'ဧရိယာ စီမံရန်',
          'a5s.manage.createTitle': 'ဧရိယာ အသစ် ဖန်တီးရန်',
          'a5s.manage.createHint': '+ ကိုနှိပ်ပြီး ဧရိယာ ထည့်ကာ ဧရိယာအချက်အလက် ဖြည့်ပါ',
          'a5s.manage.createModalHint': 'အထပ်ရွေး၊ အမည်ပေးပြီး ဧရိယာ ပုံတင်ပါ။',
          'a5s.manage.layoutName': 'ဧရိယာ အမည်',
          'a5s.common.roomName': 'အခန်း အမည်',
          'a5s.manage.layoutNamePlaceholder': 'ဥပမာ Factory 1 production floor',
          'a5s.manage.description': 'ဖော်ပြချက် (မဖြည့်လည်းရ)',
          'a5s.manage.descriptionPlaceholder': 'ဧရိယာအကျယ်အဝန်း / မှတ်ချက်',
          'a5s.manage.image': 'ဧရိယာ ပုံ',
          'a5s.manage.createButton': '+ ဧရိယာ ဖန်တီးရန်',
          'a5s.manage.cancel': 'ပယ်ဖျက်',
          'a5s.manage.floor': 'အထပ်ရွေးရန်',
          'a5s.manage.backZones': 'ဇုန်ရွေးရန် ပြန်သွားရန်',
          'a5s.manage.backSubZones': 'ဇုန်ခွဲရွေးရန် ပြန်သွားရန်',
          'a5s.manage.unmappedTitle': 'အထပ်မသတ်မှတ်ရသေးသော ဧရိယာ များ',
          'a5s.manage.unmappedNote': 'အထပ်နှင့် မချိတ်ရသေးပါ — ပုံမှန်ပြင်နိုင်သည်၊ အထပ်ချိတ်ခြင်းကို Admin မှ mapping စာမျက်နှာတွင် လုပ်ပါမည်။',
          'a5s.manage.noFloorInZone': 'ဤဇုန်တွင် အထပ်မရှိသေးပါ — Admin ကို အရင်သတ်မှတ်ခိုင်းပါ။',
          'a5s.manage.pickSubZoneTitle': 'ဇုန်ခွဲရွေးပါ',
          'a5s.manage.pickSubZoneHint': 'ဧရိယာ မဖန်တီးမီ Admin သတ်မှတ်ထားသော ဇုန်ခွဲပုံ/ဧရိယာခွဲကို ရွေးပါ။',
          'a5s.manage.subZonePageTitle': 'ဧရိယာခွဲ/အဆောက်အအုံ ရွေးပါ',
          'a5s.manage.subZonePageHint': 'ထိုဧရိယာအတွင်း ဧရိယာ စီမံရန် ဇုန်ခွဲပုံပေါ်ရှိ ဘောင် သို့မဟုတ် အဆောက်အအုံအမည်ကို နှိပ်ပါ။',
          'a5s.manage.noSubZoneInZone': 'ဤဇုန်တွင် ဇုန်ခွဲပုံ မရှိသေးပါ။',
          'a5s.manage.noAreaInSubZone': 'ဤပုံတွင် ဧရိယာခွဲ မရှိသေးပါ။',
          'a5s.manage.selectSubZoneFirst': 'ဧရိယာ မဖန်တီးမီ ဇုန်ခွဲကို ရွေးပါ။',
          'a5s.manage.noFloorInSubZone': 'ဤဇုန်ခွဲတွင် အထပ်မရှိသေးပါ — Admin ကို အရင်သတ်မှတ်ခိုင်းပါ။',
          'a5s.manage.zone': 'ဇုန်ရွေးပါ',
          'a5s.manage.locationLabel': 'ဧရိယာ',
          'a5s.manage.subZone': 'ဇုန်ခွဲရွေးပါ',
          'a5s.manage.editPlan': 'ကုမ္ပဏီစီမံကိန်း ပြင်ရန်',
          'a5s.manage.pickZoneTitle': 'ဧရိယာရွေးပါ',
          'a5s.manage.pickZoneHint': 'ထိုဇုန်၏ ဧရိယာခွဲ/အဆောက်အအုံကို ရွေးရန် ပုံပေါ်ရှိ ဧရိယာကို နှိပ်ပါ သို့မဟုတ် တို့ပါ။',
          'a5s.manage.noPlanAdmin': 'ကုမ္ပဏီစီမံကိန်း မရှိသေးပါ — ပုံတင်ပြီး ဇုန်ဆွဲရန် "ကုမ္ပဏီစီမံကိန်း ပြင်ရန်" သို့သွားပါ။',
          'a5s.manage.noPlanAllocator': 'ကုမ္ပဏီစီမံကိန်း မရှိသေးပါ — Admin မှ အဆောက်အအုံ/ဇုန် သတ်မှတ်ရန် စောင့်နေသည်။',
          'a5s.manage.noZoneYet': 'ဤစီမံကိန်းတွင် ဇုန်မရှိသေးပါ',
          'a5s.manage.companyName': 'စူပါဝပ် အင်ဒတ်စထရီ ကုမ္ပဏီ လီမိတက်',
          'a5s.manage.unmappedHint': 'ရှိနေသည်',
          'a5s.manage.unmappedHint2': 'ခု အထပ်မချိတ်ရသေးသော ဧရိယာ များ — စီမံရန် နှိပ်ပါ',
          'a5s.plan.title': 'ကုမ္ပဏီစီမံကိန်း',
          'a5s.plan.backManage': 'နောက်သို့',
          'a5s.plan.mappingLink': 'ဟောင်း mapping',
          'a5s.plan.noPlanYet': 'စီမံကိန်းပုံ မရှိသေးပါ — ဇုန်ဆွဲရန် ပုံတင်ပါ',
          'a5s.plan.chooseFile': 'ဖိုင်ရွေးရန်',
          'a5s.plan.step1': 'ဇုန်ဆွဲ',
          'a5s.plan.step2': 'ဇုန်ခွဲရွေး',
          'a5s.plan.step3': 'ဧရိယာခွဲ + အထပ်',
          'a5s.plan.noSubZoneMaps': 'ဇုန်ခွဲ မရှိသေးပါ',
          'a5s.plan.upload': 'တင်ရန်',
          'a5s.plan.stageHint': 'ဇူးမ်ချဲ့ → "+ ဇုန်ထည့်" နှိပ် → ဧရိယာပတ်လည် နှိပ်ပါ · ဟောင်းဇုန်ကို နှိပ်၍ ပြင်ပါ',
          'a5s.plan.zoomHint': 'မောက်စ်ဘီး = ဇူးမ် · ဆွဲ = ရွှေ့ကြည့်',
          'a5s.plan.undoPoint': 'အမှတ် နောက်ပြန်',
          'a5s.plan.changeImageHint': 'စီမံကိန်းပုံ ပြောင်းရန်',
          'a5s.plan.noZonesYet': 'ဇုန် မရှိသေးပါ — "+ ဇုန်ထည့်" နှိပ်ပါ',
          'a5s.plan.drawNewZone': '+ ဇုန်ထည့်',
          'a5s.plan.drawHint': 'ဧရိယာပတ်လည် ပုံပေါ်တွင် အမှတ်တစ်ခုချင်းနှိပ်ပါ (အနည်းဆုံး အမှတ် ၃ ခု)',
          'a5s.plan.finishDraw': 'ပြီးပါပြီ',
          'a5s.plan.needThreePoints': 'အနည်းဆုံး အမှတ် ၃ ခု လိုအပ်သည်',
          'a5s.plan.saveFailed': 'သိမ်းမရပါ',
          'a5s.plan.colorSaveFailed': 'အရောင် သိမ်း၍ မရပါ',
          'a5s.plan.allZones': 'ဇုန်အားလုံး',
          'a5s.plan.zoneColor': 'ဇုန်အရောင် ရွေးပါ',
          'a5s.plan.selectedOverviewZone': 'ရွေးထားသော ဇုန်',
          'a5s.plan.selectedOverviewZoneHint': 'ဤဧရိယာသည် ဇုန်ခွဲ/အဆောက်အအုံ နယ်နိမိတ်အတွက်ဖြစ်ပြီး ဇုန်အချက်အလက်ကို ဒီနေရာတွင် ပြင်နိုင်သည်',
          'a5s.plan.zoneData': 'ဇုန်အချက်အလက်',
          'a5s.plan.zoneName': 'အမည်',
          'a5s.plan.zoneNamePlaceholder': 'ဇုန် ၁',
          'a5s.plan.zoneLabel': 'ဇုန်',
          'a5s.plan.floors': 'ဤဇုန်ရှိ အထပ်များ',
          'a5s.plan.floorsInArea': 'အထပ်',
          'a5s.plan.floorsUnit': 'အထပ်',
          'a5s.plan.floorNamePlaceholder': 'ဥပမာ အထပ် ၄',
          'a5s.plan.addFloor': '+ အထပ်',
          'a5s.plan.addFloorFailed': 'အထပ်ထည့်၍ မရပါ',
          'a5s.plan.resetZone': 'ဇုန်ကို ပြန်သတ်မှတ်ရန်',
          'a5s.plan.resetZoneConfirm': 'ဤဇုန်ကို ပြန်သတ်မှတ်မလား? ဤဇုန်ရှိ အထပ်များနှင့် ချိတ်ထားသော ဧရိယာ များကို ဖြုတ်ပြီး အထပ်ဟောင်းများကို ဖျက်ပါမည်',
          'a5s.plan.resetDone': 'ဇုန်ကို ပြန်သတ်မှတ်ပြီးပါပြီ။ ယခု ဤဇုန်ကို ဖျက်နိုင်ပါပြီ',
          'a5s.plan.resetFailed': 'ဇုန်ကို ပြန်သတ်မှတ်၍ မရပါ',
          'a5s.plan.deleteZone': 'ဤဇုန်ကို ဖျက်ရန်',
          'a5s.plan.deleteOverviewZone': 'ဇုန်ဖျက်ရန်',
          'a5s.plan.deleteZoneConfirm': 'ဤဇုန်ကို ဖျက်မလား?',
          'a5s.plan.deleteFailed': 'ဖျက်၍ မရပါ',
          'a5s.plan.noFloors': 'အထပ် မရှိသေးပါ',
          'a5s.plan.noSelection': 'စီမံရန် ပုံ သို့မဟုတ် စာရင်းမှ ဇုန်ကို ရွေးပါ',
          'a5s.plan.subZoneMaps': 'ဇုန်ခွဲများ',
          'a5s.plan.subZoneMapsHint': 'ပုံထည့်ပြီး ဧရိယာခွဲဆွဲပါ',
          'a5s.plan.addSubZone': 'ဇုန်ခွဲထည့်ရန်',
          'a5s.plan.editSubZone': 'ဇုန်ခွဲပြင်ရန်',
          'a5s.plan.deleteSubZone': 'ဇုန်ခွဲဖျက်ရန်',
          'a5s.plan.deleteSubZoneConfirm': 'ဤဇုန်ခွဲကို ဖျက်မလား?',
          'a5s.plan.subZoneName': 'ဇုန်ခွဲအမည်',
          'a5s.plan.subZoneImage': 'ဇုန်ခွဲပုံ',
          'a5s.plan.saveSubZone': 'ဇုန်ခွဲသိမ်းရန်',
          'a5s.plan.subZoneNeedImage': 'ဇုန်ခွဲပုံ ထည့်ပါ',
          'a5s.plan.subZoneSaveFailed': 'ဇုန်ခွဲသိမ်း၍ မရပါ',
          'a5s.plan.subZoneDeleteFailed': 'ဇုန်ခွဲဖျက်၍ မရပါ',
          'a5s.plan.subZoneEditorHint': 'ပုံပေါ်တွင် ဧရိယာခွဲဆွဲပြီး အထပ်ထည့်ပါ',
          'a5s.plan.subZoneDrawHint': 'ပုံကို ဇူးမ်/ဆွဲ → "+ ဧရိယာခွဲထည့်" နှိပ် → ပတ်လည်နှိပ်ပါ',
          'a5s.plan.drawSubZoneArea': '+ ဧရိယာခွဲထည့်',
          'a5s.plan.subZoneAreaDrawHint': 'ဧရိယာခွဲဆွဲရန် ပုံပေါ်တွင် အမှတ်တစ်ခုချင်း နှိပ်ပါ၊ အနည်းဆုံး ၃ ခု',
          'a5s.plan.subZoneAreas': 'ဧရိယာခွဲများ',
          'a5s.plan.subZoneAreaColor': 'ဧရိယာခွဲအရောင် ရွေးပါ',
          'a5s.plan.areasUnit': 'ဧရိယာ',
          'a5s.plan.noSubZoneAreas': 'ဧရိယာခွဲ မရှိသေးပါ',
          'a5s.plan.subZoneAreaData': 'ရွေးထားသော ဧရိယာခွဲ',
          'a5s.plan.subZoneAreaName': 'အမည်',
          'a5s.plan.deleteSubZoneArea': 'ဧရိယာခွဲဖျက်ရန်',
          'a5s.plan.deleteSubZoneAreaConfirm': 'ဤဧရိယာခွဲကို ဖျက်မလား?',
          'a5s.plan.noSubZoneAreaSelection': 'ပြင်ရန် ဧရိယာခွဲကို ရွေးပါ',
          'a5s.mapping.title': 'ဟောင်း mapping ပြန်စစ်/ပြင်ရန်',
          'a5s.mapping.backPlan': 'ကုမ္ပဏီစီမံကိန်းသို့ ပြန်သွားရန်',
          'a5s.mapping.hint': 'ဧရိယာ တစ်ခုချင်းစီအတွက် ဇုန်၊ ဧရိယာခွဲနှင့် အထပ်ကို ရွေးပါ — အထပ်ကို အမည်မှ ခန့်မှန်းပေးနိုင်သော်လည်း ဇုန်/ဧရိယာခွဲကို ကိုယ်တိုင်ရွေးရမည်။',
          'a5s.mapping.onlyUnmapped': 'အထပ်မချိတ်ရသေးသည်များကိုသာ ပြရန်',
          'a5s.mapping.noZoneYet': 'ဇုန် မရှိသေးပါ — ဇုန်/အထပ် ဆွဲရန် ကုမ္ပဏီစီမံကိန်းစာမျက်နှာသို့ အရင်သွားပါ',
          'a5s.mapping.allMapped': 'ဧရိယာ အားလုံး ချိတ်ဆက်ပြီးပါပြီ',
          'a5s.mapping.round': 'ပတ်လှည့်',
          'a5s.mapping.guessed': 'ခန့်မှန်းအထပ်',
          'a5s.mapping.zone': 'ဇုန်',
          'a5s.mapping.area': 'ဧရိယာခွဲ',
          'a5s.mapping.floor': 'အထပ်',
          'a5s.mapping.selectZone': '— ဇုန်ရွေးပါ —',
          'a5s.mapping.selectArea': '— ဧရိယာခွဲရွေးပါ —',
          'a5s.mapping.selectFloor': '— အထပ်ရွေးပါ —',
          'a5s.mapping.noGuess': 'ခန့်မှန်း၍ မရပါ',
          'a5s.mapping.saveAll': 'mapping အားလုံး သိမ်းရန်',
          'a5s.index.unmappedZone': 'အထပ်မသတ်မှတ်ရသေးပါ',
          'a5s.myWork.planHintWork': 'အဆောက်အအုံ ခြုံငုံသုံးသပ်ချက် — အဆောက်အအုံတစ်ခုချင်းစီရှိ သင့်အလုပ်များ၊ အောက်ရှိစာရင်းကြည့်ရန် နှိပ်ပါ',
          'a5s.myWork.planHintReview': 'အဆောက်အအုံ ခြုံငုံသုံးသပ်ချက် — အဆောက်အအုံတစ်ခုချင်းစီတွင် စစ်ဆေးရန်အမှတ်များ၊ အောက်ရှိစာရင်းကြည့်ရန် နှိပ်ပါ',
          'a5s.myWork.planNoWork': 'အလုပ် မရှိပါ',
          'a5s.myWork.reportStatus': 'တာဝန်ယူထားသော အလုပ်အခြေအနေ',
          'a5s.myWork.mineHere': 'ကျွန်ုပ်၏အလုပ်',
          'a5s.myWork.mineHereReview': 'စစ်ဆေးရန်',
          'a5s.myWork.statusData': 'အခြေအနေအကျဉ်း',
          'a5s.myWork.openAreaWork': 'ဤဧရိယာ၏ အလုပ်စာရင်းကို ဖွင့်ရန်',
          'a5s.myWork.openAreaReview': 'ဤဧရိယာ၏ စစ်ဆေးအကဲဖြတ်စာရင်းကို ဖွင့်ရန်',
          'a5s.myWork.openAreaHint': 'အောက်ရှိ အလုပ်စာရင်းကို ကြည့်ရန် မြေပုံပေါ်ရှိ ဧရိယာကို နှိပ်ပါ',
          'a5s.myWork.openAreaPageHint': 'ဧရိယာ စာရင်းကို ဖွင့်ရန် မြေပုံပေါ်ရှိ ဧရိယာကို နှိပ်ပါ',
          'a5s.manage.editPin': 'ပြင်ရန်',
          'a5s.manage.edit': 'ပြင်ရန်',
          'a5s.manage.closeUse': 'ပိတ်',
          'a5s.manage.openUse': 'ဖွင့်',
          'a5s.manage.delete': 'ဖျက်ရန်',
          'a5s.manage.disabled': 'ပိတ်ထားသည်',
          'a5s.manage.off': 'ပိတ်',
          'a5s.manage.nameLabel': 'ဧရိယာအမည်',
          'a5s.manage.updatedLabel': 'အပ်ဒိတ်',
          'a5s.manage.addedByLabel': 'ထည့်သူ',
          'a5s.manage.viewLive': 'စာမျက်နှာကြည့်ရန်',
          'a5s.manage.adminView': 'Admin',
          'a5s.manage.adminTitle': 'ဧရိယာ အားလုံး',
          'a5s.manage.adminHint': 'လူတိုင်းဖန်တီးထားသော ဧရိယာ များကို ကြည့်ပြီး စီမံနိုင်သည်။',
          'a5s.manage.mineTitle': 'ကျွန်ုပ်ဖန်တီးထားသော ဧရိယာ များ',
          'a5s.manage.mineHint': 'သင့်အကောင့်မှ ဖန်တီးထားသော ဧရိယာ များ။',
          'a5s.manage.myLayoutTitle': 'ကျွန်ုပ်၏ ဧရိယာ များ',
          'a5s.manage.myLayoutHint': 'ဧရိယာခွဲဝေသူသည် ကိုယ်တိုင်ဖန်တီးထားသော ဧရိယာ ကိုသာ ပြင်နိုင်သည်။',
          'a5s.manage.emptyMine': 'သင် ဧရိယာ မဖန်တီးရသေးပါ။',
          'a5s.manage.emptyFirst': 'ဧရိယာ မရှိသေးပါ — ဖန်တီးရန် + ကိုနှိပ်ပါ။',
          'a5s.manage.createdTitle': 'ဧရိယာ ဖန်တီးပြီး',
          'a5s.manage.createdBody': 'ဧရိယာအမှတ်များ ချရန် "ပြင်ရန်" ကိုနှိပ်ပါ။',
          'a5s.manage.ok': 'OK',
          'a5s.index.title': '5S ဧရိယာ ဘုတ်',
          'a5s.index.subtitle': 'ဧရိယာကိုရွေး၍ မြေပုံ၊ အမှတ်များနှင့် တာဝန်ရှိသူများကို ကြည့်ပါ။',
          'a5s.index.search': 'ဧရိယာ အမည် ရှာရန်',
          'a5s.index.all': 'အားလုံး',
          'a5s.index.mine': 'ကျွန်ုပ်တာဝန်ယူထားသည်',
          'a5s.index.showing': 'ပြသနေသည်',
          'a5s.index.responsibleBadge': 'သင်တာဝန်ယူထားသည်',
          'a5s.index.floor': 'အထပ်',
          'a5s.index.floorOther': 'အခြား',
          'a5s.index.planTitle': 'ကုမ္ပဏီစီမံကိန်းနှင့် ဇုန်အားလုံး',
          'a5s.index.planHint': 'ဧရိယာတစ်ခုချင်း မဖွင့်မီ Admin သတ်မှတ်ထားသော ဇုန်အဓိကနှင့် ဇုန်ခွဲပုံများကို ကြည့်ပါ။',
          'a5s.index.noSubArea': 'ဧရိယာခွဲ မရှိသေးပါ',
          'a5s.index.unmappedArea': 'ဧရိယာခွဲ မချိတ်ရသေးပါ',
          'a5s.home.title': 'ပင်မစာမျက်နှာ',
          'a5s.home.heading': '5S ကို နေ့စဉ်အလေ့အထဖြစ်အောင် အတူတကွ တည်ဆောက်ကြပါစို့',
          'a5s.home.subtitle': 'သန့်ရှင်း၊ စနစ်တကျ၊ ဘေးကင်းသော လုပ်ငန်းခွင်သည် ကျွန်ုပ်တို့ တစ်ဦးချင်းစီမှ စတင်သည် — အောက်တွင် 5S လမ်းညွှန်ကို ဖတ်ပါ။',
          'a5s.home.scrollHint': 'လမ်းညွှန်ဖတ်ရန် အောက်သို့လှိမ့်ပါ · အသေးစိတ်ကြည့်ရန် ပုံကိုတို့ပါ',
          'a5s.home.tapZoom': 'မျက်နှာပြင်အပြည့် အသေးစိတ်ကြည့်ရန် တို့ပါ',
          'a5s.home.announce': 'ကြေညာချက်များ',
          'a5s.welcome.hello': 'မင်္ဂလာပါ ',
          'a5s.welcome.welcomeTo': 'SUPAVUT 5S Area မှ ကြိုဆိုပါသည်',
          'a5s.welcome.desc': 'ဤစနစ်သည် သန့်ရှင်းမှု၊ စနစ်တကျဖြစ်မှုနှင့် ဘေးကင်းမှုကို မြှင့်တင်ပြီး အလုပ်ခွင်အတွက် ကောင်းမွန်သော အလေ့အထများ တည်ဆောက်ပေးသည်။',
          'a5s.welcome.dontToday': 'ယနေ့ မပြပါနှင့်',
          'a5s.welcome.ack': 'သဘောတူပါသည်',
          'a5s.editor.allPoints': 'အမှတ်အားလုံး',
          'a5s.editor.editLayout': 'ဧရိယာ ပြင်ရန်',
          'a5s.editor.backManage': 'ဧရိယာစီမံမှုသို့ ပြန်သွားရန်',
          'a5s.editor.layoutName': 'ဧရိယာ အမည်',
          'a5s.editor.description': 'ဖော်ပြချက်',
          'a5s.editor.changeImage': 'ပုံပြောင်းရန်',
          'a5s.editor.upload': 'တင်ရန်',
          'a5s.editor.trailZone': 'ဇုန်',
          'a5s.editor.trailArea': 'အဆောက်အအုံ',
          'a5s.editor.trailFloor': 'အထပ်',
          'a5s.editor.trailNone': 'နေရာ မသတ်မှတ်ရသေး',
          'a5s.editor.stageHint': '+ ထည့်ရန် · ဆွဲ၍ရွှေ့ · နှိပ်၍ပြင်',
          'a5s.editor.addPoint': 'အမှတ်ထည့်ရန်',
          'a5s.editor.pointData': 'အမှတ်အချက်အလက်',
          'a5s.editor.pointCode': 'အမှတ်ကုဒ်',
          'a5s.editor.areaName': 'အမှတ်အမည်',
          'a5s.editor.assigneeLabel': 'တာဝန်ရှိသူများ',
          'a5s.editor.evaluatorLabel': 'စစ်ဆေးသူများ',
          'a5s.editor.searchPeople': 'ဝန်ထမ်း ရှာရန်',
          'a5s.editor.deletePoint': 'အမှတ်ဖျက်ရန်',
          'a5s.editor.done': 'ပြီးစီး',
          'a5s.editor.noSelection': 'ပြင်ရန် အမှတ်တစ်ခုရွေးပါ',
          'a5s.editor.noPeople': 'လူမရှိသေးပါ',
          'a5s.editor.noAssignees': 'တာဝန်ရှိသူ မရှိသေးပါ',
          'a5s.editor.noEvaluators': 'စစ်ဆေးသူ မရှိသေးပါ',
          'a5s.editor.peopleUnit': 'ဦး',
          'a5s.editor.addPointFailed': 'အမှတ်ထည့်၍ မရပါ',
          'a5s.editor.deletePointConfirm': 'အမှတ် :code ကို ဖျက်မလား?',
          'a5s.editor.deletePointFailed': 'အမှတ်ဖျက်၍ မရပါ',
          'a5s.editor.saveFailed': 'သိမ်း၍ မရပါ',
          'a5s.editor.addAssigneeFailed': 'တာဝန်ရှိသူ ထည့်၍ မရပါ',
          'a5s.editor.addEvaluatorFailed': 'စစ်ဆေးသူ ထည့်၍ မရပါ',
          'a5s.rounds.title': 'လစဉ် Round များ',
          'a5s.rounds.hint': 'တစ်ကြိမ်လျှင် Round တစ်ခုသာ ဖွင့်နိုင်သည်။ လအသစ်ဖွင့်လျှင် လက်ရှိ Round ကို အလိုအလျောက် ပိတ်မည်။ လဟောင်းကို ပြန်ဖွင့်၍ ကြည့်/ပြင်နိုင်သည်။',
          'a5s.rounds.openRound': 'ဖွင့်ထားသော Round:',
          'a5s.rounds.noOpenRound': 'ဖွင့်ထားသော Round မရှိသေးပါ',
          'a5s.rounds.prevYear': 'ယခင်နှစ်',
          'a5s.rounds.nextYear': 'နောက်နှစ်',
          'a5s.rounds.yearPrefix': 'နှစ်',
          'a5s.rounds.stateOpen': 'ဖွင့်ထားသည်',
          'a5s.rounds.stateClosed': 'ပိတ်ထားသည်',
          'a5s.rounds.stateNotOpen': 'မဖွင့်ရသေးပါ',
          'a5s.rounds.passed': 'အောင်',
          'a5s.rounds.rejected': 'ပယ်ချ',
          'a5s.rounds.waiting': 'စစ်ဆေးရန် စောင့်နေသည်',
          'a5s.rounds.points': 'အမှတ်',
          'a5s.rounds.noPointData': 'ဤ Round တွင် အမှတ်ဒေတာ မရှိသေးပါ',
          'a5s.rounds.close': 'Round ပိတ်ရန်',
          'a5s.rounds.setupBtn': "စစ်ဆေးမှုကာလ သတ်မှတ်ရန်",
          'a5s.rounds.editInspections': "စစ်ဆေးမှု ပြင်\/ထည့်",
          'a5s.rounds.setupTitle': "ဤလအတွင်း စစ်ဆေးမှု အကြိမ်ရေ",
          'a5s.rounds.setupHint': "စစ်ဆေးမည့်ရက်များထည့်ပြီး အတည်ပြုပါ — အရင်ဖန်တီးထားပြီး ရက်ရောက်မှ တစ်ခုချင်း ဖွင့်ပါ။",
          'a5s.rounds.addInspection': "+ စစ်ဆေးမှုထည့်",
          'a5s.rounds.notSetup': "စစ်ဆေးမှု မသတ်မှတ်ရသေး",
          'a5s.rounds.statePlanned': "ဖွင့်ရန်စောင့်",
          'a5s.rounds.openDo': "ဖွင့်",
          'a5s.rounds.hasWork': "အလုပ်ရှိ",
          'a5s.rounds.deleteInspection': 'စစ်ဆေးမှုဖျက်',
          'a5s.rounds.hint2': "တစ်ကြိမ်စီဖွင့် — အသစ်ဖွင့်လျှင် ယခင်ကိုပိတ် · တစ်လတွင် အကြိမ်များ (ရက်ဖြင့်)",
          'a5s.rounds.open': 'Round ဖွင့်ရန်',
          'a5s.rounds.reopen': 'Round ပြန်ဖွင့်ရန်',
          'a5s.rounds.openNew': '+ စစ်ဆေးမှုအသစ် ဖွင့်ရန်',
          'a5s.rounds.times': 'ကြိမ်',
          'a5s.rounds.openConfirm': 'Round :target ကို ဖွင့်မလား?',
          'a5s.rounds.openConfirmWithClose': 'Round :target ကို ဖွင့်မလား?\nလက်ရှိဖွင့်ထားသော Round :open ကို အလိုအလျောက် ပိတ်မည်။',
          'a5s.rounds.closeConfirm': 'Round :target ကို ပိတ်မလား?\nပိတ်ပြီးလျှင် ပြန်ဖွင့်သည်အထိ အမှတ်များကို သိမ်း/ပို့၍ မရပါ။',
          'a5s.rounds.openOneConfirm': '{label} ကို ဖွင့်မလား?\nလက်ရှိဖွင့်ထားသော စစ်ဆေးမှုကို အလိုအလျောက် ပိတ်မည်။',
          'a5s.rounds.deleteConfirm': 'စစ်ဆေးမှု {label} ကို ဖျက်မလား?',
          'a5s.rounds.closeConfirm2': '{label} ကို ပိတ်မလား?\nပြန်မဖွင့်မချင်း အလုပ်ကို သိမ်းခြင်း သို့မဟုတ် စစ်ဆေးရန် ပို့ခြင်း မပြုနိုင်ပါ။',
          'a5s.month.1': 'ဇန်နဝါရီ',
          'a5s.month.2': 'ဖေဖော်ဝါရီ',
          'a5s.month.3': 'မတ်',
          'a5s.month.4': 'ဧပြီ',
          'a5s.month.5': 'မေ',
          'a5s.month.6': 'ဇွန်',
          'a5s.month.7': 'ဇူလိုင်',
          'a5s.month.8': 'ဩဂုတ်',
          'a5s.month.9': 'စက်တင်ဘာ',
          'a5s.month.10': 'အောက်တိုဘာ',
          'a5s.month.11': 'နိုဝင်ဘာ',
          'a5s.month.12': 'ဒီဇင်ဘာ',
          'a5s.downloads.title': 'စာရွက်စာတမ်း ဒေါင်းလုဒ်',
          'a5s.downloads.hint': 'ရွေးထားသော လစဉ် Round ၏ 5S စစ်ဆေးရလဒ် — Excel ဖိုင် (.xlsx)',
          'a5s.downloads.downloadExcel': 'Excel ဒေါင်းလုဒ်',
          'a5s.downloads.downloadYear': 'တစ်နှစ်စာ ဒေါင်းလုဒ်',
          'a5s.index.pastRound': 'ယခင်လကို ကြည့်နေသည် (ဖတ်ရန်သာ)',
          'a5s.downloads.selectRound': 'လစဉ် Round ရွေးရန်',
          'a5s.downloads.downloadCsv': 'CSV ဒေါင်းလုဒ်',
          'a5s.downloads.noRound': 'စနစ်တွင် လစဉ် Round မရှိသေးပါ — Monthly မီနူးမှ ပထမ Round ကို ဖွင့်ပါ',
          'a5s.downloads.noRows': 'ဤ Round တွင် အမှတ်ဒေတာ မရှိသေးပါ',
          'a5s.downloads.pointName': 'အမှတ်အမည်',
          'a5s.downloads.evaluator': 'စစ်ဆေးသူ',
          'a5s.downloads.evaluatedAt': 'စစ်ဆေးသည့်ရက်',
          'a5s.downloads.latestSubmitted': 'နောက်ဆုံး ပို့ချိန်',
          'a5s.downloads.notSent': 'မပို့ရသေးပါ',
          'a5s.myWork.title': 'ကျွန်ုပ်၏ ဧရိယာလုပ်ငန်း',
          'a5s.myWork.layoutsTitle': 'သင့်အမှတ်များရှိသော ဧရိယာ များ',
          'a5s.myWork.layoutsHint': 'တာဝန်ပေးထားသော အမှတ်များကိုကြည့်ပြီး ဓာတ်ပုံပါသော အသေးစိတ် မှတ်တမ်း ထည့်ပါ။',
          'a5s.myWork.reviewTitle': 'စစ်ဆေးအကဲဖြတ်',
          'a5s.myWork.reviewHint': 'သင်စစ်ဆေးသူအဖြစ် တာဝန်ပေးထားသော ဧရိယာ များ။ ဧရိယာ ကိုဖွင့်ပြီး အောင်/ပယ်ချကို သတ်မှတ်ပါ။',
          'a5s.myWork.reviewAreaTitle': 'စစ်ဆေးအကဲဖြတ်ရမည့် ဧရိယာ များ',
          'a5s.myWork.reviewAreaHint': 'တာဝန်ပေးထားသော အမှတ်များကို စစ်ဆေးပြီး အကဲဖြတ်ရလဒ် မှတ်တမ်းတင်ရန် ဧရိယာ ကို ရွေးပါ။',
          'a5s.myWork.evaluationStatus': 'အကဲဖြတ်အခြေအနေ',
          'a5s.myWork.cardsRecorded': 'သိမ်းထားသော မှတ်တမ်း',
          'a5s.myWork.rejectNote': 'ပယ်ချမှတ်ချက်',
          'a5s.myWork.passNote': 'အကဲဖြတ်သူ၏ မှတ်ချက်',
          'a5s.myWork.noPointDisplay': 'ပြရန်အမှတ် မရှိသေးပါ',
          'a5s.myWork.noAssigned': 'သင့်ကို တာဝန်ပေးထားသော ဧရိယာအမှတ် မရှိသေးပါ',
          'a5s.myWork.noReviewAssigned': 'သင့်ကို စစ်ဆေးရန် တာဝန်ပေးထားသော ဧရိယာ မရှိသေးပါ',
          'a5s.myWork.subZoneWorkTitle': 'သင့်အလုပ်အတွက် ဇုန်ခွဲပုံများ',
          'a5s.myWork.subZoneWorkHint': 'သင့်ကို တာဝန်ပေးထားသော ဧရိယာခွဲနှင့် အမှတ်များကိုသာ ပြသည်။',
          'a5s.myWork.subZoneReviewTitle': 'စစ်ဆေးရန် ဇုန်ခွဲပုံများ',
          'a5s.myWork.subZoneReviewHint': 'သင်စစ်ဆေးနိုင်သော ဧရိယာခွဲနှင့် အမှတ်များကိုသာ ပြသည်။',
          'a5s.work.add': 'ထည့်ရန်',
          'a5s.work.addCard': 'ထည့်ရန်',
          'a5s.work.editCard': 'ပြင်ရန်',
          'a5s.work.addCardNote': 'လုပ်ငန်းအသေးစိတ်ဖြည့်ပြီး အနည်းဆုံး ဓာတ်ပုံ 1 ပုံ တင်ပါ။',
          'a5s.work.editCardNote': 'ခေါင်းစဉ်၊ အသေးစိတ် သို့မဟုတ် ဓာတ်ပုံများ ပြင်ပါ။',
          'a5s.work.cardTitle': 'ခေါင်းစဉ်',
          'a5s.work.cardTitlePlaceholder': 'ဥပမာ Work desk သန့်ရှင်းရေး',
          'a5s.work.cardDetail': 'အသေးစိတ်',
          'a5s.work.cardDetailPlaceholder': 'လုပ်ဆောင်ခဲ့သည်များ သို့မဟုတ် မှတ်ချက်',
          'a5s.work.attach': 'ဓာတ်ပုံတင်ရန် (JPG / PNG / WEBP / HEIC)',
          'a5s.work.attachMore': 'ဓာတ်ပုံထပ်တင်ရန် (မဖြည့်လည်းရ)',
          'a5s.work.save': 'သိမ်းရန်',
          'a5s.work.submit': 'စစ်ဆေးရန် ပို့ပါ',
          'a5s.work.resubmit': 'ထပ်ပို့ပါ',
          'a5s.work.updateSubmit': 'အပ်ဒိတ် ပို့ပါ',
          'a5s.work.sentCount': ':count ကြိမ် ပို့ပြီး',
          'a5s.work.reason': 'အကြောင်းရင်း:',
          'a5s.work.note': 'မှတ်ချက်:',
          'a5s.work.advice': 'အကြံပြုချက်:',
          'a5s.work.noCards': 'မှတ်တမ်း မရှိသေးပါ',
          'a5s.work.addFirst': '+ ကိုနှိပ်ပြီး ပထမ မှတ်တမ်း ထည့်ပါ',
          'a5s.work.maxImages': 'မှတ်တမ်းတစ်ခုလျှင် ဓာတ်ပုံ 8 ပုံအထိသာ တင်နိုင်သည်',
          'a5s.work.workspaceHint': 'အဆောက်အအုံနှင့် အခြေအနေကိုရွေးပြီး သင့်တာဝန်ရှိအမှတ်များကိုကြည့်ရန် ဧရိယာအမည်ကိုနှိပ်ပါ။',
          'a5s.work.scoreSummary': 'ကျွန်ုပ်၏ ဧရိယာလုပ်ငန်း ရမှတ်များ',
          'a5s.work.filterLabel': 'ကျွန်ုပ်၏ ဧရိယာလုပ်ငန်း အခြေအနေ',
          'a5s.work.todo': 'လုပ်ရန်',
          'a5s.work.pendingReview': 'စစ်ဆေးရန် စောင့်နေသည်',
          'a5s.work.needsRevision': 'ပြင်ဆင်ရန်',
          'a5s.work.completed': 'ပြီးစီးပြီ',
          'a5s.work.buildingHint': 'သင့်လုပ်ငန်းအမှတ်မဖွင့်မီ အဆောက်အအုံပုံနှင့် အထပ်ကို အတည်ပြုပါ။',
          'a5s.work.layoutCount': 'တာဝန်ရှိဧရိယာ',
          'a5s.work.pointCount': 'လုပ်ငန်းအမှတ်',
          'a5s.work.myWorkData': 'ကျွန်ုပ်၏လုပ်ငန်းအမှတ်',
          'a5s.work.latestUpdate': 'နောက်ဆုံးအပ်ဒိတ်',
          'a5s.work.notStarted': 'မစတင်ရသေးပါ',
          'a5s.work.noItemsForStatus': 'ဤအခြေအနေတွင် လုပ်ငန်းမရှိပါ',
          'a5s.work.noItemsHint': 'သင့်တာဝန်ရှိလုပ်ငန်းအမှတ်အားလုံးကြည့်ရန် အခြားအခြေအနေကိုရွေးပါ။',
          'a5s.history.noItemsHint': 'ဤအဆောက်အအုံရှိ အမှတ်အားလုံးကြည့်ရန် အခြားအခြေအနေကို ရွေးပါ။',
          'a5s.workspace.all': 'အားလုံး',
          'a5s.workspace.passed': 'အောင်မြင်',
          'a5s.workspace.failed': 'မအောင်မြင်',
          'a5s.workspace.pending': 'ဆောင်ရွက်ဆဲ',
          'a5s.workspace.noData': 'ဒေတာမရှိသေးပါ',
          'a5s.workspace.filters': 'အဆောက်အအုံ၊ လနှင့် စစ်ဆေးရက် ရွေးပါ',
          'a5s.workspace.buildingTabs': 'အဆောက်အအုံရွေးပါ',
          'a5s.workspace.roundInfo': 'လက်ရှိစစ်ဆေးမှုအကြိမ်',
          'a5s.workspace.month': 'လ',
          'a5s.workspace.subRound': 'အကြိမ်ခွဲ',
          'a5s.workspace.roundNumber': 'အကြိမ်',
          'a5s.workspace.openedOn': 'ဖွင့်သည့်နေ့',
          'a5s.workspace.monthHistory': 'ကြည့်လိုသည့်လကို ရွေးပါ',
          'a5s.workspace.showMonth': 'ဒေတာပြပါ',
          'a5s.workspace.currentMonth': 'လက်ရှိဖွင့်ထားသောလကို ပြသနေသည်',
          'a5s.workspace.historyReadOnly': 'မှတ်တမ်းဒေတာ · ကြည့်ရှုရန်သာ',
          'a5s.workspace.viewHistoricalData': 'ဤလ၏ဒေတာကိုကြည့်ရန်',
          'a5s.workspace.selectedInspection': 'ရွေးထားသော စစ်ဆေးရက်ဒေတာ',
          'a5s.workspace.inspectionDate': 'စစ်ဆေးရက်',
          'a5s.workspace.noBuildingData': 'ဒေတာမရှိပါ',
          'a5s.workspace.noBuildingDataHint': 'ရွေးထားသော စစ်ဆေးရက်အတွက် ဤအဆောက်အအုံတွင် ဒေတာမရှိပါ။',
          'a5s.review.title': 'စစ်ဆေးအကဲဖြတ်',
          'a5s.review.workspaceTitle': 'စစ်ဆေးအကဲဖြတ် စာရင်း',
          'a5s.review.workspaceHint': 'အဆောက်အအုံနှင့် အခြေအနေကိုရွေးပြီး စစ်ဆေးရမည့်ဒေတာကိုကြည့်ရန် ဧရိယာအမည်ကိုနှိပ်ပါ။',
          'a5s.review.pointsInScope': 'သင့်တာဝန်နယ်ပယ်ရှိ အမှတ်',
          'a5s.review.filterLabel': 'စစ်ဆေးအကဲဖြတ် အခြေအနေ',
          'a5s.review.ready': 'စစ်ဆေးရန် အသင့်',
          'a5s.review.waitingSubmit': 'မပို့ရသေး',
          'a5s.review.waitingFix': 'ပြင်ဆင်ရန် စောင့်နေသည်',
          'a5s.review.completed': 'ပြီးစီးပြီ',
          'a5s.review.buildingHint': 'စစ်ဆေးမှုအမှတ်မဖွင့်မီ အဆောက်အအုံပုံနှင့် အထပ်ကို အတည်ပြုပါ။',
          'a5s.review.floorCount': 'အထပ်',
          'a5s.review.layoutCount': 'စစ်ဆေးဧရိယာ',
          'a5s.review.pointCount': 'စစ်ဆေးအမှတ်',
          'a5s.review.floorTabs': 'အဆောက်အအုံ အထပ်များ',
          'a5s.review.evaluationData': 'စစ်ဆေးရမည့်ဒေတာ',
          'a5s.review.latestSubmit': 'နောက်ဆုံးပို့ချိန်',
          'a5s.review.morePeople': 'ဦး ထပ်ရှိ',
          'a5s.review.submissionNumber': 'ပို့သည့်အကြိမ်',
          'a5s.review.notSubmitted': 'မပို့ရသေးပါ',
          'a5s.review.openReview': 'စစ်ဆေးရန် ဖွင့်ပါ',
          'a5s.review.noItemsForStatus': 'ဤအခြေအနေတွင် စာရင်းမရှိပါ',
          'a5s.review.noItemsHint': 'သင့်တာဝန်နယ်ပယ်ရှိ စာရင်းအားလုံးကြည့်ရန် အခြားအခြေအနေကို ရွေးပါ။',
          'a5s.review.pendingDecision': 'စစ်ဆေးရန် စောင့်နေသည်',
          'a5s.review.submittedBy': 'ပို့သူ',
          'a5s.review.evaluatedBy': 'စစ်ဆေးသူ',
          'a5s.review.submittedContent': 'ပို့ထားသောအကြောင်းအရာ',
          'a5s.review.noHistory': 'ပို့မှတ်တမ်း မရှိသေးပါ',
          'a5s.review.noHistoryHint': 'တာဝန်ရှိသူမှ စစ်ဆေးရန် ဒေတာပို့ပြီးနောက် မှတ်တမ်းပေါ်လာမည်။',
          'a5s.review.headingHint': 'တာဝန်ပေးထားသော အမှတ်ကိုရွေးပြီး ပို့ထားသော မှတ်တမ်း မှ အောင်/ပယ်ချကို မှတ်တမ်းတင်ပါ။',
          'a5s.review.pointsToReview': 'စစ်ဆေးရန် အမှတ်',
          'a5s.review.submittedAt': 'ပို့ချိန်:',
          'a5s.review.submitCount': 'ပို့သည့်အကြိမ်:',
          'a5s.review.pass': 'အောင်',
          'a5s.review.reject': 'ပယ်ချ',
          'a5s.review.rejectReason': 'ပယ်ချအကြောင်းရင်း',
          'a5s.review.rejectPlaceholder': 'ပြင်ဆင်ရမည့်အချက်ကို ဖော်ပြပါ',
          'a5s.review.saveReject': 'ပယ်ချမှု သိမ်းရန်',
          'a5s.review.confirmPassTitle': 'အောင်မြင်ကြောင်း အတည်ပြုရန်',
          'a5s.review.confirmRejectTitle': 'ပယ်ချမှု အတည်ပြုရန်',
          'a5s.review.attachNote': 'မှတ်ချက် ပူးတွဲရန်',
          'a5s.review.notePlaceholder': 'တာဝန်ခံထံ မှတ်ချက် ရေးပါ',
          'a5s.review.confirm': 'အတည်ပြုရန်',
          'a5s.review.failedPrefix': 'ပယ်ချ:',
          'a5s.review.editDecision': 'အကဲဖြတ်ပြင်ရန်',
          'a5s.review.editConfirmTitle': 'အကဲဖြတ်ပြင်ရန်',
          'a5s.review.editConfirmBody': 'ဤအမှတ်၏ အကဲဖြတ်ရလဒ်ကို ပြင်ဆင်မလား?',
          'a5s.review.editConfirmOk': 'ပြင်ရန်',
          'a5s.review.submitHistory': 'ပို့မှတ်တမ်း',
          'a5s.review.score': 'ရမှတ်',
          'a5s.score.thisRound': 'ဤအကြိမ်',
          'a5s.score.short': 'ရမှတ်',
          'a5s.score.selectedMonth': 'ရွေးထားသောလ၏ရမှတ်',
          'a5s.score.fromPoints': 'စစ်ဆေးပြီး',
          'a5s.score.fromRounds': 'ပျမ်းမျှ',
          'a5s.score.pointAverage': 'စစ်ဆေးမှုတိုင်း၏ ပျမ်းမျှ',
          'a5s.calendar.title': 'ရမှတ်ပြက္ခဒိန်',
          'a5s.calendar.open': 'ရမှတ်ပြက္ခဒိန် ဖွင့်ရန်',
          'a5s.calendar.total': 'စုစုပေါင်း',
          'a5s.calendar.monthTotal': 'ဤလစုစုပေါင်း',
          'a5s.calendar.dayTotal': 'ဤရက်စုစုပေါင်း',
          'a5s.calendar.round': 'အကြိမ်',
          'a5s.calendar.openRound': 'ဖွင့်ထားသည်',
          'a5s.calendar.closedRound': 'ပိတ်ပြီး',
          'a5s.calendar.evaluations': 'စစ်ဆေးမှုများ',
          'a5s.calendar.empty': 'အကဲဖြတ်ရက် မရှိသေးပါ',
          'a5s.calendar.emptyMonth': 'ဤလတွင် အကဲဖြတ်ရက် မရှိသေးပါ',
          'a5s.units.round': 'အကြိမ်',
          'a5s.myWork.tasksTitle': 'ကျွန်ုပ်၏ အလုပ်များ',
          'a5s.myWork.reviewTasksTitle': 'စစ်ဆေးရန်',
          'a5s.myWork.planToggle': 'စက်ရုံ အပြင်အဆင်',
          'a5s.myWork.planToggleHint': 'အလုပ်တည်နေရာကြည့်ရန် ဖွင့်ပါ',
          'a5s.score.overall': 'စုစုပေါင်း',
          'a5s.review.attemptNo': 'အကြိမ်',
          'a5s.review.times': 'ကြိမ်',
          'a5s.common.evaluatedDate': 'အကဲဖြတ်ရက်',
          'a5s.common.viewLayout': 'ဧရိယာ ကြည့်ရန်',
          'a5s.common.action': 'လုပ်ဆောင်ချက်',
          'a5s.round.month': 'လ',
          'a5s.round.inspection': 'စစ်ဆေးမှု',
          'a5s.round.seqShort': 'အကြိမ်',
          'a5s.round.active': 'ဆောင်ရွက်ဆဲ',
          'a5s.round.viewOnly': 'ဤအကြိမ် ပိတ်ထားသည် · မှတ်တမ်းသာ ကြည့်နိုင်သည်',
          'a5s.myWork.openWork': 'အလုပ်ဖွင့်ရန်',
          'a5s.history.hintWork': 'သင်တာဝန်ယူထားသော အမှတ်များ၏ အကဲဖြတ်မှတ်တမ်းကို ကြည့်ရန် စစ်ဆေးမှုတစ်ကြိမ်ကို ရွေးပါ။',
          'a5s.history.hintReview': 'သင်စစ်ဆေးရန် တာဝန်ပေးထားသော အမှတ်များ၏ အကဲဖြတ်မှတ်တမ်းကို ကြည့်ရန် စစ်ဆေးမှုတစ်ကြိမ်ကို ရွေးပါ။',
          'a5s.history.hintOverview': 'ဤဧရိယာအတွင်း အမှတ်အားလုံး၏ အကဲဖြတ်မှတ်တမ်းကို ကြည့်ရန် စစ်ဆေးမှုတစ်ကြိမ်ကို ရွေးပါ။',
          'a5s.common.history': 'မှတ်တမ်း',
          'a5s.common.historySubmit': 'တင်သွင်းမှုမှတ်တမ်း',
          'a5s.common.view': 'ကြည့်ရန်',
          'a5s.common.noteEvaluator': 'မှတ်ချက် (စစ်ဆေးသူ)',
          'a5s.common.name': 'အမည်',
          'a5s.common.pointName': 'အမှတ်အမည်',
          'a5s.index.tapPlan': 'အဆောက်အအုံများ ကြည့်ရန် နှိပ်ပါ',
          'a5s.index.backPlan': 'ပြန်သွားရန်',
          'a5s.index.emptyArea': 'ဤအဆောက်အအုံတွင် ဒေတာ မရှိသေးပါ',
          'a5s.work.sent': 'တင်ပြီး',
          'a5s.work.editReport': 'အစီရင်ခံစာ ပြင်ရန်',
          'a5s.work.editReportDone': 'ပြင်ဆင်ပြီး',
          'a5s.work.editReportConfirm': 'ဤအမှတ်၏ အစီရင်ခံစာကို ပြင်မလား?',
          'a5s.work.submitConfirm': 'ဤအမှတ်ကို စစ်ဆေးသူထံ ပို့မလား?',
          'a5s.history.empty': 'ပို့မှတ်တမ်း မရှိသေးပါ',
          'a5s.review.noSubmittedContent': 'ပို့ထားသော အသေးစိတ်မရှိ',
          'a5s.review.failedWaiting': 'ဤအမှတ်ကို ပယ်ချထားသည်။ တာဝန်ရှိသူ ပြင်ဆင်ပြီး ထပ်ပို့ရန် စောင့်နေသည်။',
          'a5s.review.waitSubmit': 'တာဝန်ရှိသူမှ ပို့ရန် စောင့်နေသည်',
          'a5s.review.outOfScope': 'ဤအမှတ်သည် သင်စစ်ဆေးရမည့် scope ထဲတွင် မပါပါ။',
          'a5s.review.queueHeading': '5S ဧရိယာ စစ်ဆေးအကဲဖြတ်',
          'a5s.review.queueHint': 'သင့်အား စစ်ဆေးရန် တာဝန်ပေးထားသော အမှတ်များသာ ပြသည်။ အောင်/ပယ်ချကို အကြောင်းပြချက်နှင့်တကွ ရွေးပါ။',
          'a5s.review.noActiveRound': 'ဖွင့်ထားသော Round မရှိသေးပါ',
          'a5s.review.tab.pending': 'စစ်ဆေးရန်',
          'a5s.review.tab.failed': 'ပယ်ချထားသည်',
          'a5s.review.tab.passed': 'အောင်မြင်သည်',
          'a5s.review.tab.draft': 'မပို့ရသေးပါ',
          'a5s.review.check': 'စစ်ဆေးရန်',
          'a5s.review.viewDetail': 'အသေးစိတ်ကြည့်ရန်',
          'a5s.review.emptyCategory': 'ဤအမျိုးအစားတွင် လုပ်ငန်းမရှိပါ',
          'a5s.review.backQueue': 'စစ်ဆေးစာရင်းသို့ ပြန်သွားရန်',
          'a5s.review.latestFailReason': 'နောက်ဆုံး ပယ်ချအကြောင်းရင်း:',
          'a5s.review.passedDone': 'စစ်ဆေးမှု အောင်မြင်ပြီး',
          'a5s.review.when': 'အချိန်',
          'a5s.review.noCardsInTask': 'ဤလုပ်ငန်းတွင် မှတ်တမ်း မရှိသေးပါ',
          'a5s.review.decisionTitle': 'စစ်ဆေးမှုရလဒ်',
          'a5s.review.failReasonRequired': 'ပယ်ချအကြောင်းရင်း (လိုအပ်သည်)',
          'a5s.review.adviceOptional': 'ထပ်ဆောင်းအကြံပြုချက် (မဖြည့်လည်းရ)',
          'a5s.review.advicePlaceholder': 'တာဝန်ရှိသူအတွက် အကြံပြုချက်',
          'a5s.review.decisionNote': 'အောင် = ဤလအတွက် ဤအမှတ်၏လုပ်ငန်း ပြီးစီးပြီး လော့ခ်ချမည်။ ပယ်ချ = Round မပိတ်မီ တာဝန်ရှိသူ ပြင်ပြီး ထပ်ပို့နိုင်သည်။',
          'a5s.review.saveDecision': 'စစ်ဆေးမှုရလဒ် သိမ်းရန်',
          'a5s.settings.title': 'အသုံးပြုခွင့်',
          'a5s.settings.memberHint': 'ဝန်ထမ်းကိုရှာပြီး role ရွေးကာ ထည့်ပါ။ လူတစ်ဦးတွင် role အများအပြား ရှိနိုင်သည်။ Insight admin များသည် ဤစနစ်၏ admin အဖြစ် အလိုအလျောက် ပါဝင်သည်။',
          'a5s.settings.roleAdmin': 'စနစ်စီမံသူ',
          'a5s.settings.roleAllocator': 'ဧရိယာခွဲဝေသူ',
          'a5s.settings.roleAdminOption': 'စနစ်စီမံသူ (Admin)',
          'a5s.settings.roleAllocatorOption': 'ဧရိယာခွဲဝေသူ (Allocator)',
          'a5s.settings.revoke': 'ခွင့်ပြုချက် ရုပ်သိမ်းရန်',
          'a5s.settings.none': 'မရှိသေးပါ',
          'a5s.settings.assignTitle': 'အမှတ်အလိုက် တာဝန်ရှိသူများ',
          'a5s.settings.assignHint': 'ဧရိယာခွဲ သို့မဟုတ် အဆောက်အဦကို ရွေးပြီး အထပ်အလိုက် စီထားသော ဧရိယာ မှ အမှတ်တာဝန်ရှိသူများကို စီမံပါ။',
          'a5s.settings.pointClosed': 'အမှတ်ပိတ်ထားသည်',
          'a5s.settings.noAssignees': 'တာဝန်ရှိသူ မရှိသေးပါ',
          'a5s.settings.assigneesLabel': 'တာဝန်ရှိသူများ',
          'a5s.settings.evaluatorsLabel': 'အကဲဖြတ်သူများ',
          'a5s.settings.noEvaluators': 'အကဲဖြတ်သူ မရှိသေးပါ',
          'a5s.settings.searchAddEvaluator': 'အကဲဖြတ်သူ ထည့်ရန် ဝန်ထမ်းရှာပါ',
          'a5s.settings.removeEvaluator': 'အကဲဖြတ်သူ ဖယ်ရှားရန်',
          'a5s.settings.addEvaluatorFailed': 'အကဲဖြတ်သူ ထည့်၍မရပါ',
          'a5s.settings.removeEvaluatorFailed': 'အကဲဖြတ်သူ ဖယ်ရှား၍မရပါ',
          'a5s.settings.layoutWide': 'ဧရိယာ တစ်ခုလုံး',
          'a5s.settings.noPointsInLayout': 'ဤ ဧရိယာ တွင် အမှတ်မရှိသေးပါ။ Area Management မှ အမှတ်ချပါ။',
          'a5s.settings.pickLayout': 'အမှတ်အလိုက် တာဝန်ရှိသူများ စီမံရန် ဧရိယာ ရွေးပါ',
          'a5s.settings.noLayoutInArea': 'ဤအဆောက်အအုံတွင် ဧရိယာ မရှိသေးပါ',
          'a5s.settings.unmappedArea': 'ဧရိယာ မသတ်မှတ်ရသေးပါ',
          'a5s.settings.unmappedFloor': 'အထပ် မသတ်မှတ်ရသေးပါ',
          'a5s.settings.hideDetail': 'အသေးစိတ် ဖျောက်ရန်',
          'a5s.settings.searchAdd': 'ထည့်ရန် ဝန်ထမ်းရှာရန်',
          'a5s.settings.removeAssignee': 'တာဝန်ရှိသူ ဖယ်ရန်',
          'a5s.settings.addAssigneeFailed': 'တာဝန်ရှိသူ ထည့်၍ မရပါ',
          'a5s.settings.removeAssigneeFailed': 'တာဝန်ရှိသူ ဖယ်၍ မရပါ',
          'a5s.settings.positionAccess': 'စနစ်ဝင်ခွင့်ရှိသော ရာထူးများ',
          'a5s.settings.positionHint': 'Bplus ဝန်ထမ်းဒေတာမှ စနစ်ဝင်ခွင့်ရှိသော ရာထူးများကို ရွေးပါ။ မသိမ်းဖူးပါက ရာထူးအားလုံး ခွင့်ပြုသည်။ တာဝန်ပေးထားသူ/စစ်ဆေးသူများသည် ရာထူးမည်သို့ပင်ဖြစ်စေ ဝင်နိုင်သည်။',
          'a5s.settings.selectAll': 'အားလုံးရွေး',
          'a5s.settings.clearAll': 'ရှင်းရန်',
          'a5s.settings.savePosition': 'ရာထူး သိမ်းရန်',
          'a5s.units.point': 'အမှတ်',
          'a5s.units.personAssigned': 'တာဝန်ပေးထားသူ',
          'a5s.units.layout': 'ဧရိယာ',
          'a5s.area.title': 'ဧရိယာခွဲ',
          'a5s.area.openDetail': 'ဧရိယာခွဲ စာမျက်နှာကို ဖွင့်ရန်',
          'a5s.area.openZone': 'ဤဇုန်၏ ဧရိယာခွဲများကို ဖွင့်ရန်',
          'a5s.__loose': {
            'ภาพรวม': 'အနှစ်ချုပ်',
            'จัดการพื้นที่': 'ဧရိယာ စီမံရန်',
            'สิทธิ์ผู้ใช้': 'အသုံးပြုခွင့်',
            'งานพื้นที่ของฉัน': 'ကျွန်ုပ်၏ ဧရိယာလုပ်ငန်း',
            'ตรวจประเมิน': 'စစ်ဆေးအကဲဖြတ်',
            'ผู้รับผิดชอบ': 'တာဝန်ရှိသူများ',
            'ผู้ประเมิน': 'စစ်ဆေးသူများ',
            'รหัสพนักงาน': 'ဝန်ထမ်းကုဒ်',
            'ชื่อ': 'အမည်',
            'ตำแหน่ง': 'ရာထူး',
            'รายละเอียด': 'အသေးစိတ်',
            'สถานะ': 'အခြေအနေ',
            'หมายเหตุ': 'မှတ်ချက်',
            'อ่าน': 'ဖတ်ရန်',
            'ผ่าน': 'အောင်မြင်သည်',
            'ปฏิเสธ': 'ပယ်ချထားသည်',
            'รอดำเนินการ': 'စစ်ဆေးရန် စောင့်နေသည်',
            'ยังไม่มีข้อมูล': 'ဒေတာမရှိသေးပါ',
            'เพิ่ม': 'ထည့်ရန်',
            'แก้ไข': 'ပြင်ရန်',
            'ลบ': 'ဖျက်ရန်',
            'บันทึก': 'သိမ်းရန်',
            'ยกเลิก': 'ပယ်ဖျက်',
            'ตกลง': 'OK'
          }
        }
      };

      var otApprovalCopy = {
        th: {
          'toast.otApprovalDenied': 'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Time & Leave Approval',
          'nav.otApproval': 'จัดการเวลาและการลา',
          'nav.otRequests': 'ยื่นขอค่าล่วงเวลา',
          'nav.otApprovals': 'อนุมัติค่าล่วงเวลา',
          'nav.requestGroup': 'การยื่นคำขอ',
          'nav.approvalGroup': 'การตรวจอนุมัติ',
          'nav.leaveRequests': 'ยื่นขอหยุดงานตามมาตรา 75',
          'nav.leaveApprovals': 'อนุมัติหยุดงานตามมาตรา 75',
          'noti.appOt': 'Time & Leave Approval',
          'noti.app5s': 'SUPAVUT 5S AREA',
          'noti.all': 'ทั้งหมด',
          'noti.leave': 'การลา',
          'ot.requests.title': 'ขอ OT',
          'ot.requests.heading': 'กำหนดคนขอ OT',
          'ot.requests.description': 'เลือกพนักงาน ประเภท และช่วงเวลาที่ทำงานล่วงเวลา',
          'ot.requests.comingSoon': 'หน้าขอ OT กำลังเตรียมเชื่อมกับข้อมูลสแกน Bplus',
          'ot.requests.comingSoonDescription': 'ขั้นถัดไปจะแสดงเฉพาะพนักงานในแผนกที่คุณรับผิดชอบ พร้อมตัวเลือกประเภท OT และชั่วโมงที่ขอ',
          'ot.approvals.title': 'อนุมัติ OT',
          'ot.approvals.heading': 'ตรวจอนุมัติ OT',
          'ot.approvals.description': 'ตรวจเวลาเข้า–ออกและอนุมัติคำขอ OT ของแผนกที่รับผิดชอบ',
          'ot.approvals.comingSoon': 'หน้าตรวจอนุมัติ OT กำลังเตรียมใช้งาน',
          'ot.approvals.comingSoonDescription': 'ขั้นถัดไปจะแสดงคำขอที่ Foreman ส่งมา พร้อมผลตรวจจากข้อมูลสแกน Bplus และปุ่มอนุมัติ',
          'ot.requests.formLabel': 'แบบคำขอล่วงเวลา',
          'ot.requests.company': 'บริษัท',
          'ot.requests.foreman': 'Foreman',
          'ot.requests.paperTitle': 'เอกสารขออนุมัติค่าล่วงเวลา',
          'ot.requests.select': 'เลือก',
          'ot.requests.shift': 'กะงาน',
          'ot.requests.requestOt': 'ขอ OT',
          'ot.requests.workDate': 'วันที่ทำงาน',
          'ot.requests.department': 'แผนก',
          'ot.requests.selectEmployee': 'เลือกพนักงานเพื่อขอ OT',
          'ot.requests.editRequest': 'แก้ไข OT',
          'ot.requests.revisionNote': 'คำขอนี้ผ่านการส่ง/อนุมัติแล้ว การแก้จะเขียนทับของเดิมและต้องส่งอนุมัติใหม่',
          'ot.requests.reviseConfirmTitle': 'ยืนยันการแก้ไขคำขอ',
          'ot.requests.reviseConfirmNote': 'คำขอนี้ได้ผ่านการส่งเพื่อขออนุมัติแล้ว การแก้ไขจะเขียนทับข้อมูลเดิมทั้งหมด และระบบจะนำคำขอกลับเข้าสู่ขั้นตอนขออนุมัติใหม่ตั้งแต่ต้น',
          'ot.requests.reviseConfirmExported': 'คำขอนี้ได้ถูกดาวน์โหลดเพื่อส่งให้ฝ่ายบุคคลเรียบร้อยแล้ว การแก้ไขจะเขียนทับข้อมูลเดิมและต้องส่งขออนุมัติใหม่ทั้งหมด กรุณาแจ้งผู้ดูแลระบบให้ดาวน์โหลดเอกสารฉบับใหม่ทับของเดิม มิฉะนั้นชั่วโมงในระบบ Bplus จะไม่ตรงกับข้อมูลที่แก้ไข',
          'ot.requests.reviseConfirmAccept': 'ยินยอมเพื่อไปต่อ',
          'ot.requests.reviseConfirmClose': 'ปิด',
          'ot.requests.revisionExported': 'คำขอนี้ถูกดาวน์โหลดส่ง HR ไปแล้ว — แก้ได้ แต่ต้องแจ้ง admin ให้โหลดไฟล์ใหม่ทับ',
          'ot.requests.otType': 'ผลจากลักษณะการรูดบัตร',
          'ot.requests.startTime': 'ตั้งแต่เวลา',
          'ot.requests.endTime': 'ถึงเวลา',
          'ot.requests.dailyAmount': 'จำนวนชั่วโมง OT ที่ขอ',
          'ot.requests.hours': 'จำนวนชั่วโมง',
          'ot.requests.hour': 'ชั่วโมง',
          'ot.requests.hourShort': 'ชม.',
          'ot.requests.minute': 'นาที',
          'ot.requests.invalidTimeRange': 'เวลาเริ่มและเวลาสิ้นสุดต้องไม่เท่ากัน',
          'ot.requests.durationRequired': 'กรุณากรอกจำนวนชั่วโมงที่ขอ OT',
          'ot.requests.durationTooLong': 'จำนวนที่ขอต้องไม่เกินช่วงเวลาที่เลือก',
          'ot.requests.timeRange': 'ช่วงเวลา OT',
          'ot.requests.specialOt': 'OT พิเศษ',
          'ot.requests.specialHint': 'เปิดเมื่อต้องขอเกิน 2 ชั่วโมง',
          'ot.requests.note': 'หมายเหตุ',
          'ot.requests.saveRequest': 'ส่งอนุมัติ',
          'ot.requests.saved': 'บันทึกคำขอแล้ว',
          'ot.requests.submitted': 'ส่งคำขอให้ Supervisor แล้ว',
          'ot.requests.submitSuccessTitle': 'สำเร็จ',
          'ot.requests.completed': 'ผ่าน',
          'ot.requests.selectedPeople': 'คนที่เลือก',
          'ot.requests.batchHint': 'ส่งคำขอที่เลือกให้ Supervisor',
          'ot.requests.submitBatch': 'ส่งขออนุมัติ',
          'ot.approvals.documentTitle': 'รายการคำขออนุมัติค่าล่วงเวลา',
          'ot.approvals.pendingTab': 'รอดำเนินการ',
          'ot.approvals.attendanceFailedTab': 'เวลาไม่ผ่าน',
          'ot.approvals.approvedTab': 'อนุมัติแล้ว',
          'ot.approvals.rejectedTab': 'ไม่อนุมัติ',
          'ot.approvals.allTab': 'ทั้งหมด',
          'ot.approvals.allDates': 'ทุกวัน',
          'ot.approvals.allTypes': 'ทุกประเภท OT',
          'ot.approvals.allDatesHint': 'กำลังแสดงคำขอทุกวัน',
          'ot.approvals.dateOnly': 'เฉพาะ OT วันที่',
          'ot.approvals.items': 'รายการ',
          'ot.approvals.refresh': 'รีเฟรช',
          'ot.approvals.loading': 'กำลังโหลดคำขอ',
          'ot.approvals.empty': 'ไม่มีรายการในสถานะนี้',
          'ot.approvals.loadError': 'ไม่สามารถโหลดรายการได้',
          'ot.approvals.scanTime': 'เวลาสแกน',
          'ot.approvals.action': 'ดำเนินการ',
          'ot.approvals.review': 'ตรวจคำขอ',
          'ot.approvals.reviewLabel': 'ตรวจสอบและตัดสินใจ',
          'ot.approvals.note': 'หมายเหตุ',
          'ot.approvals.approve': 'อนุมัติ',
          'ot.approvals.reject': 'ไม่อนุมัติ',
          'ot.approvals.confirmLabel': 'ยืนยันผล',
          'ot.approvals.addNote': 'เพิ่มหมายเหตุ',
          'ot.approvals.addReason': 'ระบุเหตุผล',
          'ot.approvals.confirmApproveTitle': 'อนุมัติคำขอนี้?',
          'ot.approvals.confirmRejectTitle': 'ไม่อนุมัติคำขอนี้?',
          'ot.approvals.confirmReverseApproveTitle': 'เปลี่ยนคำขอไม่อนุมัติเป็นอนุมัติ?',
          'ot.approvals.confirmReverseRejectTitle': 'เปลี่ยนคำขออนุมัติแล้วเป็นไม่อนุมัติ?',
          'ot.approvals.confirmApproveHint': 'ระบบจะบันทึกผลอนุมัติ และรอสแกนล่าสุดหลังจบ OT',
          'ot.approvals.confirmRejectHint': 'ระบุเหตุผลก่อนส่งกลับไปยัง Foreman',
          'ot.approvals.confirmReverseApproveHint': 'การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นอนุมัติอีกครั้ง โปรดตรวจสอบเหตุผลเดิมและข้อมูลสแกนก่อนยืนยัน',
          'ot.approvals.confirmReverseRejectHint': 'การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นไม่อนุมัติ และต้องระบุเหตุผลให้ตรวจสอบย้อนหลังได้',
          'ot.approvals.rejectReason': 'เหตุผล (บังคับ)',
          'ot.approvals.rejectReasonRequired': 'กรุณาระบุเหตุผลที่ไม่อนุมัติ',
          'ot.approvals.confirmApprove': 'ยืนยันอนุมัติ',
          'ot.approvals.confirmReject': 'ยืนยันไม่อนุมัติ',
          'ot.approvals.selectAll': 'เลือกทั้งหมด',
          'ot.approvals.selectedCount': 'เลือกแล้ว',
          'ot.approvals.bulkApprove': 'อนุมัติที่เลือก',
          'ot.approvals.bulkReject': 'ปฏิเสธที่เลือก',
          'ot.approvals.confirmBulkTitle': 'อนุมัติ {n} รายการที่เลือก?',
          'ot.approvals.confirmBulkHint': 'ระบบจะบันทึกผลอนุมัติให้ทุกรายการที่เลือก และรอสแกนล่าสุดหลังจบ OT',
          'ot.approvals.confirmBulkRejectTitle': 'ปฏิเสธ {n} รายการที่เลือก?',
          'ot.approvals.confirmBulkRejectHint': 'เหตุผลเดียวกันนี้จะถูกบันทึกให้พนักงานทุกคนที่เลือก และส่งกลับไปยัง Foreman',
          'ot.reason.button': 'เหตุผล',
          'ot.reason.requestNote': 'หมายเหตุจาก Foreman',
          'ot.reason.decisionNote': 'หมายเหตุจาก Supervisor',
          'ot.reason.cancelReason': 'เหตุผลที่ยกเลิก',
          'ot.reason.close': 'ปิด',
          'ot.attendance.status': 'สถานะ',
          'ot.attendance.completed': 'ผ่าน',
          'ot.status.notRequested': 'ไม่มีการขอ OT',
          'ot.status.notWorkedOt': 'ไม่ได้ทำ OT',
          'ot.status.notEligible': 'ไม่สามารถขอ OT ได้',
          'ot.status.draft': 'รอดำเนินการ',
          'ot.status.pending': 'รอดำเนินการ',
          'ot.status.pendingDetail': '(รออนุมัติและสแกนล่าสุดหลังจบ OT)',
          'ot.status.waitingApproval': 'รอดำเนินการ',
          'ot.status.waitingApprovalDetail': '(รออนุมัติ)',
          'ot.status.approvedWaitingScan': 'รอดำเนินการ',
          'ot.status.waitingScanDetail': '(รอสแกนล่าสุดหลังจบ OT)',
          'ot.status.success': 'ผ่าน',
          'ot.status.failedTime': 'ไม่ผ่าน',
          'ot.status.failedTimeDetail': '(เวลา OT ไม่ครบ)',
          'ot.status.rejected': 'ไม่อนุมัติ',
          'ot.status.cancelled': 'ยกเลิก',
          'ot.status.exported': 'ส่งออกแล้ว',
          'systems.otApprovalName': 'TIME & LEAVE APPROVAL',
          'systems.otApprovalDesc': 'ระบบขอและอนุมัติ OT กับการลา พร้อมจัดเตรียมเอกสารสำหรับนำเข้า Bplus',
          'systems.availableNow': '3 ระบบหลัก',
          'systems.availableNowWithRecruit': '4 ระบบหลัก',
          'ot.overview': 'ภาพรวม',
          'ot.attendance.kicker': 'BPLUS ATTENDANCE',
          'common.rowNo': 'ลำดับ',
          'common.prevPage': 'ก่อนหน้า',
          'common.nextPage': 'ถัดไป',
          'ot.attendance.title': 'เวลาเข้า–ออกงาน',
          'ot.attendance.subtitle': 'ตรวจสอบเวลาเข้า–ออกของพนักงานตามวันที่เลือก',
          'ot.attendance.today': 'วันนี้',
          'ot.attendance.selectDate': 'เลือกวันที่',
          'ot.export.scopeLabel': 'รูปแบบเอกสาร OT',
          'ot.export.selectedDate': 'เอกสาร OT (วันที่เลือก)',
          'ot.export.all': 'เอกสาร OT (ทั้งหมด)',
          'ot.export.download': 'ดาวน์โหลด',
          'ot.attendance.liveSource': 'Bplus · อัปเดตอัตโนมัติ',
          'ot.attendance.historySource': 'Bplus · ข้อมูลย้อนหลัง',
          'ot.attendance.localSource': 'Local demo · ข้อมูลจำลอง',
          'ot.attendance.snapshotSource': 'Bplus · Snapshot ใน Local',
          'ot.attendance.mixedSource': 'Bplus + Local fallback',
          'ot.attendance.refreshing': 'กำลังอัปเดตจาก Bplus',
          'ot.attendance.totalEmployees': 'พนักงานทั้งหมด',
          'ot.attendance.clockedIn': 'สแกนเข้างาน',
          'ot.attendance.clockedOut': 'เวลาล่าสุด / ออกงาน',
          'ot.attendance.clockedInShort': 'เข้า',
          'ot.attendance.clockedOutShort': 'ออก',
          'ot.attendance.shiftUnknown': 'ไม่ระบุกะ',
          'ot.attendance.loadError': 'แสดงรายชื่อไม่สำเร็จ ลองปิดแล้วเปิดใหม่อีกครั้ง',
          'common.confirm': 'ตกลง',
          'ot.attendance.sortRankAsc': 'ตำแหน่งต่ำ → สูง',
          'ot.attendance.presence': 'การทำงาน',
          'ot.attendance.present': 'เข้างาน',
          'ot.attendance.absent': 'ไม่เข้างาน',
          'ot.attendance.dayoff': 'วันหยุด',
          'ot.attendance.filterColumn': 'กรองคอลัมน์นี้',
          'ot.attendance.clearFilter': 'ล้างตัวกรอง',
          'ot.attendance.sortCode': 'ตามรหัสพนักงาน',
          'ot.attendance.sortRank': 'ตำแหน่งสูง → ต่ำ',
          'ot.attendance.departmentLabel': 'แผนก',
          'ot.attendance.allDepartments': 'ทุกแผนก',
          'ot.attendance.summaryTitle': 'สรุปตัวเลขรวม',
          'ot.attendance.summaryOpen': 'ดูสรุปตัวเลขรวม',
          'ot.common.order': 'ลำดับ',
          'ot.branch.all': 'ทุกสาขา',
          'ot.requests.selectAll': 'เลือกทั้งหมด',
          'ot.requests.sent': 'ส่งคำขอ OT ให้ Supervisor แล้ว',
          'ot.requests.sendConfirmTitle': 'ยืนยันส่งขออนุมัติ',
          'ot.requests.sendConfirmBody': 'ระบบจะส่งคำขอนี้ให้ Supervisor ทันทีและแจ้งทางอีเมล ต้องการส่งเลยหรือไม่',
          'ot.requests.sendConfirmBulk': 'ระบบจะส่งคำขอทั้งหมดให้ Supervisor ทันทีและแจ้งทางอีเมล',
          'ot.requests.sendConfirmAccept': 'ยืนยันส่ง',
          'ot.requests.sendConfirmCancel': 'ยกเลิก',
          'ot.requests.cancelConfirmTitle': 'ยืนยันยกเลิกคำขอ',
          'ot.requests.cancelConfirmBody': 'คำขอที่ยกเลิกจะถูกถอนออกจากคิวของ Supervisor และเก็บไว้เป็นประวัติพร้อมเหตุผล',
          'ot.requests.cancelConfirmAccept': 'ยืนยันยกเลิก',
          'ot.requests.cancelReasonLabel': 'เหตุผลที่ยกเลิก (บังคับกรอก)',
          'ot.requests.cancelReasonRequired': 'กรุณาระบุเหตุผลอย่างน้อย 3 ตัวอักษร',
          'ot.cancel.selectReason': 'เลือกเหตุผล',
          'ot.cancel.employeeUnavailable': 'พนักงานไม่สะดวก',
          'ot.cancel.workPlanChanged': 'ปรับแผนงาน',
          'ot.cancel.wrongSchedule': 'วันที่หรือเวลาไม่ถูกต้อง',
          'ot.cancel.wrongRequestType': 'ประเภทคำขอไม่ถูกต้อง',
          'ot.cancel.duplicateRequest': 'ส่งคำขอซ้ำ',
          'ot.cancel.other': 'อื่นๆ',
          'ot.cancel.otherPlaceholder': 'พิมพ์เหตุผลอื่นๆ',
          'ot.calendar.sun': 'อา',
          'ot.calendar.mon': 'จ',
          'ot.calendar.tue': 'อ',
          'ot.calendar.wed': 'พ',
          'ot.calendar.thu': 'พฤ',
          'ot.calendar.fri': 'ศ',
          'ot.calendar.sat': 'ส',
          'ot.requests.cancelSelected': 'ยกเลิกที่เลือก',
          'ot.requests.cancelOne': 'ยกเลิก',
          'ot.requests.cancelled': 'ยกเลิกคำขอแล้ว',
          'ot.requests.cancelHint': 'ยกเลิกได้เฉพาะคำขอที่ Supervisor ยังไม่ตัดสิน',
          'ot.requests.items': 'รายการ',
          'ot.attendance.groupHeadcount': 'กำลังพล',
          'ot.attendance.groupScan': 'การสแกน',
          'ot.attendance.groupOt': 'ค่าล่วงเวลา',
          'ot.attendance.clockedOutLong': 'ออกงาน',
          'ot.attendance.wholeDepartment': 'ทั้งแผนก',
          'ot.attendance.otRequested': 'ขอ OT',
          'ot.attendance.approved': 'อนุมัติ',
          'ot.attendance.departments': 'แผนก',
          'ot.attendance.people': 'คน',
          'ot.attendance.openDepartment': 'ดูรายชื่อพนักงาน',
          'ot.attendance.employeeList': 'รายชื่อพนักงาน',
          'ot.attendance.employee': 'พนักงาน',
          'ot.attendance.position': 'ตำแหน่ง',
          'ot.attendance.department': 'แผนก',
          'ot.attendance.clockIn': 'เวลาเข้างาน',
          'ot.attendance.clockOut': 'เวลาล่าสุด / ออกงาน',
          'ot.attendance.noRequest': 'ยังไม่มีคำขอ',
          'ot.attendance.notApproved': 'ยังไม่อนุมัติ',
          'ot.attendance.noScan': 'ยังไม่พบสแกน',
          'ot.attendance.latestScan': 'สแกนล่าสุด',
          'ot.attendance.rawSource': 'สแกนสด',
          'ot.attendance.processedSource': 'Bplus ประมวลผลแล้ว',
          'ot.attendance.loadingEmployees': 'กำลังดึงข้อมูลจาก Bplus',
          'ot.attendance.bplusUnavailable': 'ไม่สามารถอ่านข้อมูล Bplus ได้ในขณะนี้',
          'ot.attendance.noDepartments': 'ไม่พบแผนกที่อยู่ในสิทธิ์ของคุณ',
          'ot.attendance.noEmployees': 'ไม่พบพนักงานในแผนกนี้',
          'ot.attendance.close': 'ปิด',
          'ot.home.overviewEmpty': 'ยังไม่มีคำขอ OT ในระบบ รายการคำขอและการอนุมัติจะแสดงที่นี่',
          'ot.kicker': 'OVERTIME MANAGEMENT',
          'ot.role.admin': 'OT Admin',
          'ot.role.foreman': 'Foreman',
          'ot.role.supervisor': 'Supervisor',
          'ot.role.employee': 'พนักงาน',
          'ot.home.currentRole': 'สิทธิ์ของคุณ',
          'ot.home.currentRoleHelp': 'บทบาทที่ได้รับในระบบ Time & Leave Approval',
          'ot.home.allDepartments': 'ทุกแผนก',
          'ot.home.positionAccess': 'สิทธิ์ตามตำแหน่ง',
          'ot.home.phaseTitle': 'สถานะการพัฒนา',
          'ot.home.phaseHelp': 'ตั้งค่าสิทธิ์และผู้รับผิดชอบพร้อมใช้งานแล้ว ขั้นตอนคำขอ OT จะพัฒนาต่อในเฟสถัดไป',
          'ot.settings.kicker': 'TIME & LEAVE APPROVAL',
          'ot.overview.otTab': 'ภาพรวม OT',
          'ot.overview.leaveTab': 'ภาพรวมการลา',
          'ot.export.cycle': 'เอกสาร OT (รอบเงินเดือน)',
          'leave.overview.title': 'ภาพรวมการลา',
          'leave.overview.subtitle': 'ตรวจสอบคำขอและผลอนุมัติการลาตามวันที่เลือก',
          'leave.requests.title': 'ขอลา',
          'leave.requests.subtitle': 'เลือกพนักงานและส่งคำขอลาเต็มวันตามมาตรา 75',
          'leave.approvals.title': 'ตรวจอนุมัติลา',
          'leave.approvals.subtitle': 'ตรวจคำขอแบบเต็มวัน และอนุมัติได้ถึงวันสุดท้ายของรอบเงินเดือน',
          'leave.selectDate': 'เลือกวันที่',
          'leave.download': 'ดาวน์โหลด',
          'leave.export.date': 'เอกสารลา (วันที่เลือก)',
          'leave.export.cycle': 'เอกสารลา (รอบเงินเดือน)',
          'leave.summary.aria': 'สรุปข้อมูลการลา',
          'leave.employee': 'พนักงาน',
          'leave.employees': 'พนักงานทั้งหมด',
          'leave.position': 'ตำแหน่ง',
          'leave.department': 'แผนก',
          'leave.departments': 'แผนก',
          'leave.requested': 'ขอลา',
          'leave.requestsCount': 'คำขอ',
          'leave.pending': 'รอดำเนินการ',
          'leave.approved': 'ผ่าน',
          'leave.approvedTab': 'อนุมัติแล้ว',
          'leave.approve': 'อนุมัติ',
          'leave.rejected': 'ไม่อนุมัติ',
          'leave.cancelled': 'ยกเลิก',
          'leave.cancelReason': 'เหตุผลที่ยกเลิก',
          'leave.all': 'ทั้งหมด',
          'leave.allTypes': 'ทุกประเภทการลา',
          'leave.allDates': 'ทุกวัน',
          'leave.dateOnly': 'เฉพาะวันที่',
          'leave.leaveDate': 'วันที่ลา',
          'leave.requestedDate': 'วันที่ขอ',
          'leave.items': 'รายการ',
          'leave.shift': 'กะงาน',
          'leave.selectAll': 'เลือกทั้งหมด',
          'leave.leaveType': 'ประเภทการลา',
          'leave.quantity': 'จำนวน',
          'leave.oneDay': '1 วัน',
          'leave.requestTab': 'ขอลา',
          'leave.selectedPeople': 'คนที่เลือก',
          'leave.noRequestToday': 'ไม่มีคำขอลาในวันนี้',
          'leave.noRequest': 'ไม่มีคำขอ',
          'leave.bulkRequest': 'ขอลาทั้งหมด',
          'leave.bulkEmpty': 'ไม่มีพนักงานที่ยังไม่มีคำขอในตัวกรองนี้',
          'leave.bulkDone': 'ส่งคำขอลาแล้ว',
          'leave.bulkNext': 'ติ๊กเลือกในตารางแล้วกดส่งขออนุมัติ',
          'leave.next': 'ไปต่อ',
          'leave.bulkSkipped': 'ข้าม',
          'leave.viewDate': 'วันที่ดูข้อมูล',
          'leave.bulkSelectAll': 'เลือกทั้งหมด',
          'leave.bulkBack': 'ย้อนกลับ',
          'leave.bulkHasRequest': 'มีคำขอของวันที่กำลังดูแล้ว',
          'leave.bulkInvalidRange': 'ช่วงวันที่ไม่ถูกต้อง',
          'leave.remove': 'ลบร่าง',
          'leave.removeSelected': 'ลบร่างที่เลือก',
          'leave.removeConfirm': 'ลบร่างคำขอลาที่เลือกใช่หรือไม่?',
          'leave.removeError': 'ลบร่างคำขอไม่สำเร็จ',
          'nav.otDownloads': 'ดาวน์โหลดเอกสารส่ง HR',
          'ot.downloads.colSubmitted': 'วันที่ยื่นคำขอ',
          'ot.downloads.fileUnit': 'ฉบับ',
          'ot.downloads.tabOt': 'เอกสารค่าล่วงเวลา (OT)',
          'ot.downloads.tabLeave': 'เอกสารหยุดงานตามมาตรา 75',
          'ot.downloads.colOtDate': 'วันที่ขอ OT',
          'ot.downloads.colLeaveDate': 'วันที่ลา',
          'ot.downloads.colPeople': 'จำนวนที่ส่ง',
          'ot.downloads.showDetail': 'ดูรายละเอียด',
          'ot.downloads.hideDetail': 'ซ่อนรายละเอียด',
          'ot.downloads.fromThisDay': 'ยื่นในวันที่เปิดดู',
          'ot.downloads.noDocument': 'วันนี้ไม่มีเอกสารที่ต้องส่ง',
          'ot.downloads.modalKicker': 'เอกสารส่ง HR',
          'ot.downloads.modalSubtitle': 'ตรวจรายชื่อก่อนส่งไฟล์ให้ HR นำเข้า Bplus',
          'ot.downloads.legendRestaleHint': 'โหลดไปแล้ว แต่มีรายการเข้ามาใหม่หลังจากนั้น',
          'ot.downloads.legendReadyHint': 'อนุมัติครบแล้ว ยังไม่ได้ส่งให้ HR',
          'ot.downloads.legendDoneHint': 'ไม่มีอะไรค้าง ไม่ต้องทำอะไรต่อ',
          'ot.downloads.legendWaitingHint': 'ยังรอ Supervisor ตัดสิน ยังโหลดไม่ได้',
          'ot.downloads.otSection': 'เอกสาร OT',
          'ot.downloads.leaveSection': 'เอกสารการลา (มาตรา 75)',
          'ot.downloads.downloadOt': 'ดาวน์โหลดเอกสาร OT',
          'ot.downloads.downloadLeave': 'ดาวน์โหลดเอกสารการลา',
          'ot.downloads.noOt': 'วันนี้ไม่มีรายการ OT',
          'ot.downloads.noLeave': 'วันนี้ไม่มีรายการลา',
          'ot.downloads.leaveShort': 'ลา',
          'ot.downloads.needDownload': 'วันที่ต้องโหลด',
          'ot.downloads.backdated': 'ยื่นย้อนหลัง',
          'ot.downloads.downloadCount': 'โหลดไปแล้ว',
          'ot.downloads.times': 'ครั้ง',
          'ot.downloads.readyCount': 'พร้อมส่ง',
          'ot.downloads.lastDownload': 'โหลดล่าสุด',
          'ot.downloads.lateFlag': 'ยื่นย้อนหลัง',
          'ot.downloads.rowReady': 'พร้อมส่ง',
          'ot.downloads.rowWaiting': 'รออนุมัติ',
          'ot.downloads.rowStale': 'แก้หลังโหลด',
          'ot.downloads.historyTitle': 'ประวัติการดาวน์โหลด',
          'ot.downloads.noHistory': 'ยังไม่เคยดาวน์โหลดของวันนี้',
          'ot.downloads.warnRestale': 'วันนี้เคยส่งไฟล์ให้ HR ไปแล้ว แต่มีรายการเข้ามาใหม่หลังจากนั้น ต้องโหลดซ้ำและให้ HR นำเข้าอีกครั้ง',
          'ot.downloads.warnBackdated': 'มีรายการที่ยื่นย้อนหลัง (วันที่ขอไม่ตรงกับวันที่ทำงาน) จำนวน',
          'ot.settings.scopeOt': '(ขอ OT)',
          'nav.workDetail': 'รายละเอียดการทำงาน',
          'emp.title': 'รายละเอียดการทำงาน',
          'emp.emptyTitle': 'ไม่มีข้อมูลสำหรับ admin',
          'emp.emptyBody': 'บัญชีนี้เป็นบัญชีผู้ดูแลระบบ ไม่ได้ผูกกับข้อมูลพนักงาน จึงไม่มีประวัติ OT และการลาให้แสดง — ดูข้อมูลรายคนได้จากชื่อพนักงานในหน้าภาพรวม',
          'emp.otTitle': 'OT ที่อนุมัติ',
          'emp.leaveTitle': 'การลาที่อนุมัติ',
          'emp.otCount': 'OT ที่อนุมัติ (ครั้ง)',
          'emp.otHours': 'ชั่วโมง OT รวม',
          'emp.leaveCount': 'วันลาที่อนุมัติ',
          'emp.markedDays': 'วันที่มีรายการ',
          'emp.approvedOnly': 'แสดงเฉพาะรายการที่ได้รับอนุมัติแล้ว',
          'emp.colDate': 'วันที่',
          'emp.colShiftCode': 'รหัสกะ',
          'emp.colShiftName': 'ชื่อกะ',
          'emp.colIn': 'เวลาเข้า',
          'emp.colOut': 'เวลาออก',
          'emp.colOtHours': 'ช่วงเวลาที่ขอ OT',
          'emp.colOt': 'OT',
          'emp.colLeave': 'การลา',
          'emp.today': 'วันนี้',
          'emp.backToThisMonth': 'กลับเดือนนี้',
          'emp.pickMonth': 'เลือกเดือน',
          'emp.prevYear': 'ปีก่อนหน้า',
          'emp.nextYear': 'ปีถัดไป',
          'ot.settings.hiddenRequestTitle': 'ตำแหน่งที่ไม่ให้ขอ OT',
          'ot.settings.hiddenRequestHelp': 'ติ๊กแล้ว Foreman จะไม่เห็นปุ่ม ขอ OT ของตำแหน่งนั้น เช่น Employee with Disabilities · ไม่ติ๊ก = ขอได้ตามปกติ',
          'ot.settings.hiddenLeaveTitle': 'ตำแหน่งที่ไม่ให้ขอลากิจ',
          'ot.settings.hiddenLeaveHelp': 'ติ๊กแล้ว Foreman จะไม่เห็นปุ่ม ขอลา ของตำแหน่งนั้น และเลือกในโมดัล ขอลาทั้งหมด ไม่ได้ · ไม่ติ๊ก = ขอได้ตามปกติ',
          'leave.positionNotEligible': 'ตำแหน่งนี้ไม่มีสิทธิ์ขอลา',
          'ot.common.back': 'กลับ',
          'ot.settings.scopeLeave': '(ขอลา)',
          'noti.download': 'พร้อมดาวน์โหลด',
          'noti.downloadAria': 'เอกสารพร้อมดาวน์โหลด',
          'noti.downloadEmpty': 'ยังไม่มีเอกสารที่พร้อมดาวน์โหลด',
          'noti.backdated': 'ย้อนหลัง',
          'ot.downloads.title': 'ดาวน์โหลด',
          'ot.downloads.heading': 'ดาวน์โหลดเอกสาร OT',
          'ot.downloads.subtitle': 'เลือกวันที่ต้องการส่งให้ HR — วันที่มีคำขอเข้ามาใหม่หลังโหลดไปแล้วจะขึ้นสีแดง',
          'ot.downloads.headingLeave': 'ดาวน์โหลดเอกสารหยุดงานตามมาตรา 75',
          'ot.downloads.subtitleLeave': 'เลือกวันลาที่ต้องการส่งให้ HR — คำขอที่ยื่นถึงวันลารวมอยู่ในไฟล์เดียว',
          'ot.downloads.colDocument': 'เอกสาร',
          'ot.downloads.mainDoc': 'รวมคำขอที่ยื่นถึงวันลา',
          'ot.downloads.doneTitle': 'ดาวน์โหลดแล้ว',
          'ot.downloads.doneMessage': 'บันทึกไฟล์เรียบร้อย ส่งให้ HR นำเข้า Bplus ได้เลย',
          'ot.downloads.failTitle': 'ดาวน์โหลดไม่สำเร็จ',
          'ot.downloads.failMessage': 'สร้างไฟล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
          'ot.downloads.failSession': 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่แล้วลองอีกครั้ง',
          'ot.downloads.month': 'เดือน',
          'ot.downloads.calendarAria': 'ปฏิทินสถานะเอกสาร',
          'ot.downloads.download': 'ดาวน์โหลด',
          'ot.downloads.legendRestale': 'ต้องโหลดซ้ำ',
          'ot.downloads.legendReady': 'พร้อมโหลด',
          'ot.downloads.legendDone': 'โหลดแล้ว',
          'ot.downloads.legendWaiting': 'รออนุมัติ',
          'ot.downloads.legendEmpty': 'ไม่มีคำขอ',
          'ot.downloads.tagRestale': 'โหลดซ้ำ',
          'ot.downloads.tagReady': 'พร้อม',
          'ot.downloads.tagDone': 'ครบ',
          'ot.downloads.tagWaiting': 'รอ',
          'ot.downloads.tagBackdated': 'ย้อนหลัง',
          'ot.downloads.statRequests': 'คำขอทั้งหมด',
          'ot.downloads.lastExport': 'โหลดล่าสุด',
          'ot.downloads.restaleNote': 'วันนี้เคยโหลดไปแล้ว แต่มีคำขอเพิ่มหรือถูกแก้ภายหลัง — ต้องโหลดไฟล์ใหม่ทับให้ HR',
          'leave.submitHint': 'ส่งคำขอที่เลือกให้ Supervisor',
          'leave.approveTab': 'อนุมัติลา',
          'leave.payrollCycle': 'รอบจ่าย',
          'leave.note': 'หมายเหตุ',
          'leave.reason': 'เหตุผล',
          'leave.status': 'สถานะ',
          'leave.route': 'สถานะทั้งหมด',
          'leave.action': 'ดำเนินการ',
          'leave.select': 'เลือก',
          'leave.loading': 'กำลังโหลด...',
          'leave.loadingRequests': 'กำลังโหลดคำขอ...',
          'leave.emptyDepartment': 'ไม่พบพนักงานในแผนกนี้',
          'leave.emptyScope': 'ไม่พบข้อมูลพนักงานในขอบเขตสิทธิ์ของคุณ',
          'leave.emptyPermission': 'ไม่พบแผนกที่อยู่ในสิทธิ์ของคุณ',
          'leave.emptyCategory': 'ไม่มีรายการในหมวดนี้',
          'leave.loadError': 'ไม่สามารถโหลดข้อมูลได้',
          'leave.loadListError': 'ไม่สามารถโหลดรายการได้',
          'leave.peopleSuffix': 'คน',
          'leave.saved': 'บันทึกแล้ว',
          'leave.document': 'เอกสารขอลา',
          'leave.documentHint': 'กดขอลาแล้วส่งถึง Supervisor ทันที',
          'leave.backDepartment': 'กลับไปเลือกแผนก',
          'leave.submit': 'ส่งขออนุมัติ',
          'leave.form.title': 'ส่งคำขอลา',
          'leave.form.from': 'ตั้งแต่วันที่',
          'leave.form.to': 'ถึงวันที่',
          'leave.form.notePlaceholder': 'รายละเอียดเพิ่มเติม (ถ้ามี)',
          'leave.form.splitHint': 'ระบบจะแตกช่วงวันที่เป็นคำขอเต็มวัน วันละ 1 รายการ เพื่อให้ตรงกับฟอร์มนำเข้า Bplus',
          'leave.cancel': 'ยกเลิก',
          'leave.save': 'ส่งอนุมัติ',
          'leave.success': 'สำเร็จ',
          'leave.done': 'ดำเนินการเรียบร้อยแล้ว',
          'leave.jumpToDate': 'กำลังพาไปยังวันที่',
          'leave.shiftEstimated': 'กะล่าสุดที่บันทึกไว้',
          'leave.shiftEstimatedShort': 'กะล่าสุด',
          'leave.ok': 'ตกลง',
          'leave.view': 'แก้ไขลา',
          'leave.edit': 'แก้ไข',
          'leave.approval.list': 'รายการคำขออนุมัติการลา',
          'leave.refresh': 'รีเฟรช',
          'leave.selected': 'เลือกแล้ว',
          'leave.bulkReject': 'ปฏิเสธที่เลือก',
          'leave.bulkApprove': 'อนุมัติที่เลือก',
          'leave.review': 'ตรวจคำขอ',
          'leave.confirm': 'ยืนยันผล',
          'leave.confirmApprove': 'ยืนยันอนุมัติ',
          'leave.confirmReject': 'ยืนยันไม่อนุมัติ',
          'leave.confirmReverseApprove': 'เปลี่ยนคำขอไม่อนุมัติเป็นอนุมัติ?',
          'leave.confirmReverseReject': 'เปลี่ยนคำขออนุมัติแล้วเป็นไม่อนุมัติ?',
          'leave.confirmApproveHint': 'อนุมัติแล้วจะถือว่าการลาผ่านทันทีและพร้อมสร้างเอกสาร Bplus',
          'leave.confirmRejectHint': 'กรุณาระบุเหตุผล เหตุผลเดียวกันจะถูกส่งให้ทุกรายการที่เลือก',
          'leave.confirmReverseApproveHint': 'การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นอนุมัติอีกครั้ง โปรดตรวจสอบเหตุผลเดิมก่อนยืนยัน',
          'leave.confirmReverseRejectHint': 'การกระทำนี้มีผลต่อสถานะเอกสารและการส่งออก Bplus รายการนี้จะถูกบันทึกเป็นไม่อนุมัติ และต้องระบุเหตุผลให้ตรวจสอบย้อนหลังได้',
          'leave.confirmButton': 'ยืนยัน',
          'leave.notePlaceholder': 'หมายเหตุหรือเหตุผล',
          'leave.employeeNote': 'หมายเหตุจาก Foreman',
          'leave.supervisorNote': 'หมายเหตุจาก Supervisor',
          'leave.reviewResult': 'ผลการตรวจ',
          'leave.rejectReasonRequired': 'กรุณาระบุเหตุผลที่ไม่อนุมัติ',
          'leave.expired': 'พ้นกำหนดอนุมัติ',
          'leave.status.draft': 'รอดำเนินการ',
          'leave.status.submitted': 'รอดำเนินการ',
          'leave.status.approved': 'ผ่าน',
          'leave.status.rejected': 'ไม่อนุมัติ',
          'leave.status.waitingSupervisor': '(รอ Supervisor อนุมัติ)',
          'ot.approvals.expired': 'พ้นกำหนดอนุมัติ',
          'ot.settings.title': 'ตั้งค่าระบบ',
          'ot.settings.subtitle': 'กำหนดผู้ดูแล ตำแหน่งที่เข้าใช้ และผู้รับผิดชอบ OT/การลาของแต่ละแผนก',
          'ot.settings.adminTitle': 'ผู้ดูแลระบบ',
          'ot.settings.adminHelp': 'จัดการค่าระบบและดาวน์โหลดเอกสาร OT/การลาได้',
          'ot.settings.noOtAdmins': 'ยังไม่มีผู้ดูแลระบบเพิ่มเติม',
          'ot.settings.searchAdmin': 'ค้นหาพนักงานเพื่อเพิ่มเป็นผู้ดูแล',
          'ot.settings.removeAdminConfirm': 'นำพนักงานคนนี้ออกจากผู้ดูแลระบบหรือไม่?',
          'ot.settings.positionsTitle': 'ตำแหน่งที่เข้าใช้ระบบได้',
          'ot.settings.positionsHelp': 'หากยังไม่เลือก คนทั่วไปจะเข้าไม่ได้ แต่ Admin, Foreman และ Supervisor ยังเข้าได้',
          'ot.settings.foremanTitle': 'กำหนด Foreman',
          'ot.settings.foremanHelp': 'กำหนดได้หลายคนต่อแผนก แยกกะเวลา A/กะเวลา Bได้',
          'ot.settings.supervisorTitle': 'กำหนด Supervisor',
          'ot.settings.supervisorHelp': 'เลือก 1 คนต่อบริษัทและแผนก',
          'ot.settings.notAssigned': 'ยังไม่ได้กำหนด',
          'ot.settings.chooseFromDepartment': 'เลือกจากพนักงานในแผนกนี้',
          'ot.settings.currentAssignment': 'ผู้ที่เลือกปัจจุบัน',
          'ot.settings.currentAssignments': 'ผู้รับผิดชอบในแผนกนี้',
          'ot.settings.foremanMultiHint': 'เพิ่มได้มากกว่า 1 คน และเลือกกะที่แต่ละคนดูแลได้ · กะที่เลือกใช้แสดงผลเท่านั้น ไม่จำกัดสิทธิ์',
          'ot.settings.alreadyAssigned': 'เพิ่มแล้ว',
          'ot.settings.shiftGroup': 'กะที่ดูแล',
          'ot.settings.shift.none': 'ไม่ระบุกะ',
          'ot.settings.shift.all': 'ทั้งหมด',
          'ot.settings.shift.morning': 'กะเวลา A',
          'ot.settings.shift.night': 'กะเวลา B',
          'ot.shiftFilter.label': 'เลือกกะ',
          'ot.shiftFilter.all': 'ทุกกะ',
          'ot.shiftFilter.wholeGroup': 'ทั้ง',
          'ot.shiftFilter.other': 'กะอื่น',
          'ot.shiftFilter.none': 'ไม่มีกะ',
          'ot.shiftFilter.empty': 'ไม่พบพนักงานในกะที่เลือก',
          'ot.search.label': 'ค้นหาพนักงาน',
          'ot.attendance.pickDate': 'เลือกวันที่',
          'ot.search.placeholder': 'ค้นหารหัส หรือ ชื่อ-สกุล',
          'ot.search.empty': 'ไม่พบพนักงานที่ค้นหา',
          'ot.requests.bulkOpen': 'ขอ OT ทั้งหมด',
          'ot.requests.bulkLabel': 'ขอ OT ทั้งกะ',
          'ot.requests.bulkNext': 'ไปต่อ',
          'ot.requests.bulkBack': 'ย้อนกลับ',
          'ot.requests.bulkSubmit': 'ส่งอนุมัติ',
          'ot.requests.bulkSelectAll': 'เลือกทั้งหมด',
          'ot.requests.bulkHiddenPicked': 'ที่ถูกกรองไว้',
          'ot.requests.bulkHasRequest': 'ขอ OT ไปแล้ว',
          'ot.requests.bulkPositionNotEligible': 'ตำแหน่งนี้ไม่มีสิทธิ์ขอ OT',
          'ot.requests.bulkInvalidRow': 'มีบางแถวที่เวลาหรือจำนวนไม่ถูกต้อง',
          'ot.requests.actors': 'ผู้ดำเนินการ',
          'ot.requests.actorRequested': 'ขอ OT',
          'ot.requests.actorApproved': 'อนุมัติ OT',
          'ot.route.column': 'สถานะทั้งหมด',
          'ot.route.kicker': 'OT STATUS',
          'ot.route.title': 'สถานะทั้งหมด',
          'ot.route.noRequest': 'ยังไม่มีคำขอ',
          'ot.route.noRequestDetail': 'ยังไม่มีข้อมูลผู้ดำเนินการในรายการนี้',
          'ot.route.draft': 'ส่งคำขอแล้ว',
          'ot.route.submitted': 'ส่งคำขอแล้ว',
          'ot.route.notStarted': 'ยังไม่ถึงขั้นตอน',
          'ot.route.waitingApproval': 'รออนุมัติ',
          'ot.route.approved': 'อนุมัติแล้ว',
          'ot.route.rejected': 'ไม่อนุมัติ',
          'ot.route.scanPassed': 'เวลาสแกนครบ',
          'ot.route.scanFailed': 'เวลาสแกนไม่ครบ',
          'ot.route.waitingScan': 'รอตรวจเวลาสแกน',
          'ot.route.notPassed': 'ไม่ผ่าน',
          'ot.route.passed': 'ผ่าน',
          'ot.route.inProgress': 'รอดำเนินการ',
          'ot.route.open': 'ดูสถานะทั้งหมด',
          'ot.route.zoomPhoto': 'ดูรูปพนักงานแบบขยาย',
          'ot.route.step': 'ขั้นตอน',
          'ot.route.request': 'ขอ OT',
          'ot.route.approval': 'อนุมัติ OT',
          'ot.route.attendance': 'ตรวจเวลาสแกน OT',
          'ot.route.otRange': 'ช่วงเวลา OT',
          'ot.route.otType': 'ประเภท OT',
          'ot.route.shift': 'กะงาน',
          'ot.route.finalResult': 'ผลลัพธ์',
          'ot.route.requestedBy': 'ผู้ขอ OT',
          'ot.route.approvedBy': 'ผู้อนุมัติ',
          'ot.route.actionTime': 'เวลาดำเนินการ',
          'ot.route.requestNote': 'หมายเหตุคำขอ',
          'ot.route.decisionNote': 'หมายเหตุการอนุมัติ',
          'ot.route.scanTime': 'เวลาสแกน',
          'ot.route.clockIn': 'เข้า',
          'ot.route.clockOut': 'ออก',
          'ot.route.requiredRange': 'ช่วงเวลาที่ต้องครอบคลุม',
          'ot.route.checkedAt': 'ตรวจล่าสุด',
          'ot.route.condition': 'เงื่อนไข',
          'ot.route.conditionDetail': 'อนุมัติ OT และเวลาสแกน OT ต้องผ่านทั้งคู่',
          'ot.route.exportResult': 'ข้อมูล V74',
          'ot.route.exported': 'ส่งออก V74 แล้ว',
          'ot.route.readyV74': 'พร้อมนำเข้า Excel V74',
          'ot.route.failedApproval': 'ไม่ผ่านการอนุมัติ',
          'ot.route.failedAttendance': 'เวลาสแกนไม่ครอบคลุมช่วง OT',
          'ot.route.waitBoth': 'รอผลอนุมัติและผลตรวจเวลาสแกน',
          'ot.requests.bulkAutoTime': 'คำนวณเวลาอัตโนมัติตามกะ',
          'ot.requests.myShift': 'กะที่คุณดูแล',
          'ot.settings.employeeList': 'พนักงานในแผนก',
          'ot.settings.companySearchResults': 'ผลการค้นหาจากทุกบริษัท',
          'ot.settings.searchAllCompany': 'ค้นหาพนักงานจากทุกบริษัท',
          'ot.settings.noDepartments': 'ไม่พบแผนกที่มีพนักงาน active',
          'ot.settings.noInsightAccount': 'ไม่มีบัญชี Insight',
          'ot.settings.removeAssignmentConfirm': 'นำผู้รับผิดชอบออกจากแผนกนี้หรือไม่?',
          'ot.common.departments': 'แผนก',
          'ot.common.people': 'คน',
          'ot.common.remove': 'นำออก',
          'ot.common.change': 'เปลี่ยน',
          'ot.common.choose': 'เลือก',
          'ot.common.searchEmployee': 'ค้นหาพนักงาน',
          'ot.common.searchPosition': 'ค้นหาตำแหน่ง',
          'ot.common.selectVisible': 'เลือกที่แสดง',
          'ot.common.clear': 'ล้าง',
          'ot.common.save': 'บันทึก',
          'ot.common.saved': 'บันทึกแล้ว',
          'ot.common.add': 'เพิ่ม',
          'ot.common.loading': 'กำลังโหลด...',
          'ot.common.noResults': 'ไม่พบข้อมูล',
          'ot.common.cancel': 'ยกเลิก',
          'ot.common.close': 'ปิด',
          'ot.common.error': 'ไม่สามารถดำเนินการได้'
        },
        en: {
          'toast.otApprovalDenied': 'You do not have access to Time & Leave Approval.',
          'nav.otApproval': 'Time & leave',
          'nav.otRequests': 'Overtime requests',
          'nav.otApprovals': 'Overtime approvals',
          'nav.requestGroup': 'Requests',
          'nav.approvalGroup': 'Review & approve',
          'nav.leaveRequests': 'Section 75 leave requests',
          'nav.leaveApprovals': 'Section 75 leave approvals',
          'noti.appOt': 'Time & Leave Approval',
          'noti.app5s': 'SUPAVUT 5S AREA',
          'noti.all': 'All',
          'noti.leave': 'Leave',
          'ot.requests.title': 'Request OT',
          'ot.requests.heading': 'OT requests',
          'ot.requests.description': 'Choose employees, OT type, and overtime range for the work date.',
          'ot.requests.comingSoon': 'The OT request form is being connected to Bplus scans.',
          'ot.requests.comingSoonDescription': 'The next step will show employees in your departments with OT type and hour options.',
          'ot.approvals.title': 'Approve OT',
          'ot.approvals.heading': 'OT approvals',
          'ot.approvals.description': 'Review attendance and approve OT requests for your departments.',
          'ot.approvals.comingSoon': 'The OT approval form is being prepared.',
          'ot.approvals.comingSoonDescription': 'The next step will show Foreman requests, Bplus scan results, and approval actions.',
          'ot.requests.formLabel': 'OVERTIME REQUEST FORM',
          'ot.requests.company': 'Company',
          'ot.requests.foreman': 'Foreman',
          'ot.requests.paperTitle': 'Overtime approval request',
          'ot.requests.select': 'Select',
          'ot.requests.shift': 'Shift',
          'ot.requests.requestOt': 'Request OT',
          'ot.requests.workDate': 'Work date',
          'ot.requests.department': 'Department',
          'ot.requests.selectEmployee': 'Select employee for OT',
          'ot.requests.editRequest': 'Edit OT',
          'ot.requests.revisionNote': 'This request was already submitted or approved — editing overwrites it and needs a new approval',
          'ot.requests.reviseConfirmTitle': 'Confirm request change',
          'ot.requests.reviseConfirmNote': 'This request has already been submitted for approval. Editing it overwrites the existing data and returns the request to the start of the approval process.',
          'ot.requests.reviseConfirmExported': 'This request has already been downloaded and sent to HR. Editing it overwrites the existing data and requires a fresh approval. Please ask the administrator to download the new document over the old one, otherwise the hours in Bplus will not match the edited data.',
          'ot.requests.reviseConfirmAccept': 'Agree and continue',
          'ot.requests.reviseConfirmClose': 'Close',
          'ot.requests.revisionExported': 'This request was already downloaded for HR — you can still edit, but tell an admin to download the file again',
          'ot.requests.otType': 'Clocking result',
          'ot.requests.startTime': 'From time',
          'ot.requests.endTime': 'To time',
          'ot.requests.dailyAmount': 'Requested OT hours',
          'ot.requests.hours': 'Hours',
          'ot.requests.hour': 'hour',
          'ot.requests.hourShort': 'hrs.',
          'ot.requests.minute': 'minutes',
          'ot.requests.invalidTimeRange': 'Start and end times must be different',
          'ot.requests.durationRequired': 'Enter the number of OT hours requested',
          'ot.requests.durationTooLong': 'The requested amount cannot exceed the selected time range',
          'ot.requests.timeRange': 'OT time',
          'ot.requests.specialOt': 'Special OT',
          'ot.requests.specialHint': 'Enable for more than 2 hours',
          'ot.requests.note': 'Note',
          'ot.requests.saveRequest': 'Send for approval',
          'ot.requests.saved': 'Request saved',
          'ot.requests.submitted': 'Request sent to Supervisor',
          'ot.requests.submitSuccessTitle': 'Success',
          'ot.requests.completed': 'Passed',
          'ot.requests.selectedPeople': 'selected',
          'ot.requests.batchHint': 'Submit selected requests to the Supervisor',
          'ot.requests.submitBatch': 'Submit for approval',
          'ot.approvals.documentTitle': 'Overtime approval requests',
          'ot.approvals.pendingTab': 'Pending',
          'ot.approvals.attendanceFailedTab': 'Time not met',
          'ot.approvals.approvedTab': 'Approved',
          'ot.approvals.rejectedTab': 'Rejected',
          'ot.approvals.allTab': 'All',
          'ot.approvals.allDates': 'All dates',
          'ot.approvals.allTypes': 'All OT types',
          'ot.approvals.allDatesHint': 'Showing requests from every date',
          'ot.approvals.dateOnly': 'OT on',
          'ot.approvals.items': 'items',
          'ot.approvals.refresh': 'Refresh',
          'ot.approvals.loading': 'Loading requests',
          'ot.approvals.empty': 'No requests in this status',
          'ot.approvals.loadError': 'Unable to load requests',
          'ot.approvals.scanTime': 'Scan time',
          'ot.approvals.action': 'Action',
          'ot.approvals.review': 'Review',
          'ot.approvals.reviewLabel': 'Review and decide',
          'ot.approvals.note': 'Note',
          'ot.approvals.approve': 'Approve',
          'ot.approvals.reject': 'Reject',
          'ot.approvals.confirmLabel': 'Confirm decision',
          'ot.approvals.addNote': 'Add note',
          'ot.approvals.addReason': 'Enter reason',
          'ot.approvals.confirmApproveTitle': 'Approve this request?',
          'ot.approvals.confirmRejectTitle': 'Reject this request?',
          'ot.approvals.confirmReverseApproveTitle': 'Change this rejected request to approved?',
          'ot.approvals.confirmReverseRejectTitle': 'Change this approved request to rejected?',
          'ot.approvals.confirmApproveHint': 'The approval will be saved while the system waits for the latest scan after OT ends.',
          'ot.approvals.confirmRejectHint': 'Enter a reason before sending the result back to the Foreman.',
          'ot.approvals.confirmReverseApproveHint': 'This affects the document status and Bplus export. The request will be approved again; review the previous reason and scan data before confirming.',
          'ot.approvals.confirmReverseRejectHint': 'This affects the document status and Bplus export. The request will be rejected and a reason is required for the audit trail.',
          'ot.approvals.rejectReason': 'Reason (required)',
          'ot.approvals.rejectReasonRequired': 'Enter a reason for rejecting this request',
          'ot.approvals.confirmApprove': 'Confirm approval',
          'ot.approvals.confirmReject': 'Confirm rejection',
          'ot.approvals.selectAll': 'Select all',
          'ot.approvals.selectedCount': 'Selected',
          'ot.approvals.bulkApprove': 'Approve selected',
          'ot.approvals.bulkReject': 'Reject selected',
          'ot.approvals.confirmBulkTitle': 'Approve {n} selected requests?',
          'ot.approvals.confirmBulkHint': 'Every selected request will be approved and will wait for the latest scan after OT ends.',
          'ot.approvals.confirmBulkRejectTitle': 'Reject {n} selected requests?',
          'ot.approvals.confirmBulkRejectHint': 'The same required reason will be recorded for every selected employee and sent back to the Foreman.',
          'ot.reason.button': 'Reason',
          'ot.reason.requestNote': 'Foreman note',
          'ot.reason.decisionNote': 'Supervisor note',
          'ot.reason.cancelReason': 'Cancellation reason',
          'ot.reason.close': 'Close',
          'ot.attendance.status': 'Status',
          'ot.attendance.completed': 'Passed',
          'ot.status.notRequested': 'No OT request',
          'ot.status.notWorkedOt': 'OT not worked',
          'ot.status.notEligible': 'OT request unavailable',
          'ot.status.draft': 'In progress',
          'ot.status.pending': 'In progress',
          'ot.status.pendingDetail': '(approval and latest scan after OT pending)',
          'ot.status.waitingApproval': 'In progress',
          'ot.status.waitingApprovalDetail': '(waiting for approval)',
          'ot.status.approvedWaitingScan': 'In progress',
          'ot.status.waitingScanDetail': '(waiting for latest scan after OT)',
          'ot.status.success': 'Passed',
          'ot.status.failedTime': 'Failed',
          'ot.status.failedTimeDetail': '(OT time not met)',
          'ot.status.rejected': 'Rejected',
          'ot.status.cancelled': 'Cancelled',
          'ot.status.exported': 'Exported',
          'systems.otApprovalName': 'TIME & LEAVE APPROVAL',
          'systems.otApprovalDesc': 'Request and approve overtime and leave, then prepare Bplus import documents.',
          'systems.availableNow': '3 core systems',
          'systems.availableNowWithRecruit': '4 core systems',
          'ot.overview': 'Overview',
          'ot.attendance.kicker': 'BPLUS ATTENDANCE',
          'common.rowNo': 'No.',
          'common.prevPage': 'Previous',
          'common.nextPage': 'Next',
          'ot.attendance.title': 'Clock-in and clock-out',
          'ot.attendance.subtitle': 'Review employee clock-in and clock-out times for the selected date.',
          'ot.attendance.today': 'Today',
          'ot.attendance.selectDate': 'Select date',
          'ot.export.scopeLabel': 'OT document format',
          'ot.export.selectedDate': 'OT document (selected date)',
          'ot.export.all': 'OT document (all)',
          'ot.export.download': 'Download',
          'ot.attendance.liveSource': 'Bplus · Auto refresh',
          'ot.attendance.historySource': 'Bplus · Historical data',
          'ot.attendance.localSource': 'Local demo · Sample data',
          'ot.attendance.snapshotSource': 'Bplus · Local snapshot',
          'ot.attendance.mixedSource': 'Bplus + local fallback',
          'ot.attendance.refreshing': 'Refreshing from Bplus',
          'ot.attendance.totalEmployees': 'Total employees',
          'ot.attendance.clockedIn': 'Clocked in',
          'ot.attendance.clockedOut': 'Latest scan / clock-out',
          'ot.attendance.clockedInShort': 'In',
          'ot.attendance.clockedOutShort': 'Out',
          'ot.attendance.shiftUnknown': 'No shift set',
          'ot.attendance.loadError': 'Could not show the list. Please close and reopen.',
          'common.confirm': 'Apply',
          'ot.attendance.sortRankAsc': 'Position low → high',
          'ot.attendance.presence': 'Work status',
          'ot.attendance.present': 'Clocked in',
          'ot.attendance.absent': 'Not clocked in',
          'ot.attendance.dayoff': 'Day off',
          'ot.attendance.filterColumn': 'Filter this column',
          'ot.attendance.clearFilter': 'Clear filter',
          'ot.attendance.sortCode': 'By employee code',
          'ot.attendance.sortRank': 'Position high → low',
          'ot.attendance.departmentLabel': 'Department',
          'ot.attendance.allDepartments': 'All departments',
          'ot.attendance.summaryTitle': 'Summary',
          'ot.attendance.summaryOpen': 'View summary',
          'ot.common.order': 'No.',
          'ot.branch.all': 'All sites',
          'ot.requests.selectAll': 'Select all',
          'ot.requests.sent': 'OT request sent to your supervisor',
          'ot.requests.sendConfirmTitle': 'Confirm submission',
          'ot.requests.sendConfirmBody': 'This request goes to your supervisor right away and an email is sent. Submit now?',
          'ot.requests.sendConfirmBulk': 'All requests go to your supervisor right away and an email is sent.',
          'ot.requests.sendConfirmAccept': 'Submit',
          'ot.requests.sendConfirmCancel': 'Cancel',
          'ot.requests.cancelConfirmTitle': 'Confirm cancellation',
          'ot.requests.cancelConfirmBody': 'Cancelled requests are pulled from the supervisor queue and kept as history with the reason.',
          'ot.requests.cancelConfirmAccept': 'Confirm cancel',
          'ot.requests.cancelReasonLabel': 'Reason for cancelling (required)',
          'ot.requests.cancelReasonRequired': 'Please give a reason of at least 3 characters',
          'ot.cancel.selectReason': 'Select a reason',
          'ot.cancel.employeeUnavailable': 'Employee unavailable',
          'ot.cancel.workPlanChanged': 'Work plan changed',
          'ot.cancel.wrongSchedule': 'Incorrect date or time',
          'ot.cancel.wrongRequestType': 'Incorrect request type',
          'ot.cancel.duplicateRequest': 'Duplicate request',
          'ot.cancel.other': 'Other',
          'ot.cancel.otherPlaceholder': 'Enter another reason',
          'ot.calendar.sun': 'Sun',
          'ot.calendar.mon': 'Mon',
          'ot.calendar.tue': 'Tue',
          'ot.calendar.wed': 'Wed',
          'ot.calendar.thu': 'Thu',
          'ot.calendar.fri': 'Fri',
          'ot.calendar.sat': 'Sat',
          'ot.requests.cancelSelected': 'Cancel selected',
          'ot.requests.cancelOne': 'Cancel',
          'ot.requests.cancelled': 'Request cancelled',
          'ot.requests.cancelHint': 'Only requests your supervisor has not decided yet can be cancelled',
          'ot.requests.items': 'items',
          'ot.attendance.groupHeadcount': 'Headcount',
          'ot.attendance.groupScan': 'Attendance',
          'ot.attendance.groupOt': 'Overtime',
          'ot.attendance.clockedOutLong': 'Clocked out',
          'ot.attendance.wholeDepartment': 'Whole dept',
          'ot.attendance.otRequested': 'OT requested',
          'ot.attendance.approved': 'Approved',
          'ot.attendance.departments': 'departments',
          'ot.attendance.people': 'people',
          'ot.attendance.openDepartment': 'View employees',
          'ot.attendance.employeeList': 'Employee list',
          'ot.attendance.employee': 'Employee',
          'ot.attendance.position': 'Position',
          'ot.attendance.department': 'Department',
          'ot.attendance.clockIn': 'Clock-in',
          'ot.attendance.clockOut': 'Latest scan / clock-out',
          'ot.attendance.noRequest': 'No request',
          'ot.attendance.notApproved': 'Not approved',
          'ot.attendance.noScan': 'No scan yet',
          'ot.attendance.latestScan': 'Latest scan',
          'ot.attendance.rawSource': 'Live scan',
          'ot.attendance.processedSource': 'Processed by Bplus',
          'ot.attendance.loadingEmployees': 'Loading data from Bplus',
          'ot.attendance.bplusUnavailable': 'Bplus data is temporarily unavailable.',
          'ot.attendance.noDepartments': 'No departments are available within your access.',
          'ot.attendance.noEmployees': 'No employees found in this department.',
          'ot.attendance.close': 'Close',
          'ot.home.overviewEmpty': 'No OT requests yet. Requests and approvals will appear here.',
          'ot.kicker': 'OVERTIME MANAGEMENT',
          'ot.role.admin': 'OT Admin',
          'ot.role.foreman': 'Foreman',
          'ot.role.supervisor': 'Supervisor',
          'ot.role.employee': 'Employee',
          'ot.home.currentRole': 'Your access',
          'ot.home.currentRoleHelp': 'Roles assigned to you in Time & Leave Approval.',
          'ot.home.allDepartments': 'All departments',
          'ot.home.positionAccess': 'Position-based access',
          'ot.home.phaseTitle': 'Development status',
          'ot.home.phaseHelp': 'Access and responsible-person settings are ready. The OT request workflow will follow in the next phase.',
          'ot.settings.kicker': 'TIME & LEAVE APPROVAL',
          'ot.overview.otTab': 'OT overview',
          'ot.overview.leaveTab': 'Leave overview',
          'ot.export.cycle': 'OT document (payroll cycle)',
          'leave.overview.title': 'Leave overview',
          'leave.overview.subtitle': 'Review leave requests and approval results for the selected date.',
          'leave.requests.title': 'Request leave',
          'leave.requests.subtitle': 'Select employees and submit full-day Section 75 leave.',
          'leave.approvals.title': 'Leave approval',
          'leave.approvals.subtitle': 'Review full-day requests by the final approval date of each payroll cycle.',
          'leave.selectDate': 'Select date',
          'leave.download': 'Download',
          'leave.export.date': 'Leave document (selected date)',
          'leave.export.cycle': 'Leave document (payroll cycle)',
          'leave.summary.aria': 'Leave summary',
          'leave.employee': 'Employee',
          'leave.employees': 'All employees',
          'leave.position': 'Position',
          'leave.department': 'Department',
          'leave.departments': 'departments',
          'leave.requested': 'Leave requests',
          'leave.requestsCount': 'requests',
          'leave.pending': 'Pending',
          'leave.approved': 'Passed',
          'leave.approvedTab': 'Approved',
          'leave.approve': 'Approve',
          'leave.rejected': 'Rejected',
          'leave.cancelled': 'Cancelled',
          'leave.cancelReason': 'Cancellation reason',
          'leave.all': 'All',
          'leave.allTypes': 'All leave types',
          'leave.allDates': 'All dates',
          'leave.dateOnly': 'Date',
          'leave.leaveDate': 'Leave date',
          'leave.requestedDate': 'Requested on',
          'leave.items': 'items',
          'leave.shift': 'Shift',
          'leave.selectAll': 'Select all',
          'leave.leaveType': 'Leave type',
          'leave.quantity': 'Quantity',
          'leave.oneDay': '1 day',
          'leave.requestTab': 'Request leave',
          'leave.selectedPeople': 'selected',
          'leave.noRequestToday': 'No leave request today',
          'leave.noRequest': 'No request',
          'leave.bulkRequest': 'Request leave for all',
          'leave.bulkEmpty': 'No employee without a request in this filter',
          'leave.bulkDone': 'Leave requests submitted for',
          'leave.bulkNext': 'tick them in the table then send for approval',
          'leave.next': 'Next',
          'leave.bulkSkipped': 'skipped',
          'leave.viewDate': 'Viewing date',
          'leave.bulkSelectAll': 'Select all',
          'leave.bulkBack': 'Back',
          'leave.bulkHasRequest': 'Already has a request on the viewed date',
          'leave.bulkInvalidRange': 'Invalid date range',
          'leave.remove': 'Delete draft',
          'leave.removeSelected': 'Delete selected drafts',
          'leave.removeConfirm': 'Delete the selected leave drafts?',
          'leave.removeError': 'Could not delete the drafts',
          'nav.otDownloads': 'Documents for HR',
          'ot.downloads.colSubmitted': 'Submitted on',
          'ot.downloads.fileUnit': 'files',
          'ot.downloads.tabOt': 'Overtime documents',
          'ot.downloads.tabLeave': 'Section 75 leave documents',
          'ot.downloads.colOtDate': 'OT work date',
          'ot.downloads.colLeaveDate': 'Leave date',
          'ot.downloads.colPeople': 'To send',
          'ot.downloads.showDetail': 'Show detail',
          'ot.downloads.hideDetail': 'Hide detail',
          'ot.downloads.fromThisDay': 'Submitted on the opened day',
          'ot.downloads.noDocument': 'No file to send for this day',
          'ot.downloads.modalKicker': 'Files for HR',
          'ot.downloads.modalSubtitle': 'Review the list before sending the file for HR to import into Bplus',
          'ot.downloads.legendRestaleHint': 'Already downloaded, but new records arrived afterwards',
          'ot.downloads.legendReadyHint': 'Fully approved, not sent to HR yet',
          'ot.downloads.legendDoneHint': 'Nothing pending, no action needed',
          'ot.downloads.legendWaitingHint': 'Still waiting on the supervisor, cannot download yet',
          'ot.downloads.otSection': 'OT file',
          'ot.downloads.leaveSection': 'Leave file (Section 75)',
          'ot.downloads.downloadOt': 'Download OT file',
          'ot.downloads.downloadLeave': 'Download leave file',
          'ot.downloads.noOt': 'No OT records on this day',
          'ot.downloads.noLeave': 'No leave records on this day',
          'ot.downloads.leaveShort': 'Leave',
          'ot.downloads.needDownload': 'Days to download',
          'ot.downloads.backdated': 'Backdated',
          'ot.downloads.downloadCount': 'Downloaded',
          'ot.downloads.times': 'times',
          'ot.downloads.readyCount': 'Ready',
          'ot.downloads.lastDownload': 'Last download',
          'ot.downloads.lateFlag': 'Backdated',
          'ot.downloads.rowReady': 'Ready',
          'ot.downloads.rowWaiting': 'Pending',
          'ot.downloads.rowStale': 'Edited after download',
          'ot.downloads.historyTitle': 'Download history',
          'ot.downloads.noHistory': 'This day has never been downloaded',
          'ot.downloads.warnRestale': 'This day was already sent to HR, but new records arrived afterwards. Download again and have HR re-import.',
          'ot.downloads.warnBackdated': 'Backdated records (request date differs from the work date):',
          'ot.settings.scopeOt': '(OT request)',
          'nav.workDetail': 'Work detail',
          'emp.title': 'Work detail',
          'emp.emptyTitle': 'No data for admin',
          'emp.emptyBody': 'This is a system administrator account and is not linked to an employee record, so there is no OT or leave history to show — open a person from the overview page instead.',
          'emp.otTitle': 'Approved OT',
          'emp.leaveTitle': 'Approved leave',
          'emp.otCount': 'Approved OT (times)',
          'emp.otHours': 'Total OT hours',
          'emp.leaveCount': 'Approved leave days',
          'emp.markedDays': 'Days with records',
          'emp.approvedOnly': 'Approved records only',
          'emp.colDate': 'Date',
          'emp.colShiftCode': 'Shift code',
          'emp.colShiftName': 'Shift name',
          'emp.colIn': 'Clock in',
          'emp.colOut': 'Clock out',
          'emp.colOtHours': 'Requested OT period',
          'emp.colOt': 'OT',
          'emp.colLeave': 'Leave',
          'emp.today': 'Today',
          'emp.backToThisMonth': 'Back to this month',
          'emp.pickMonth': 'Select month',
          'emp.prevYear': 'Previous year',
          'emp.nextYear': 'Next year',
          'ot.settings.hiddenRequestTitle': 'Positions that cannot request OT',
          'ot.settings.hiddenRequestHelp': 'Ticked positions lose the Request OT button, e.g. Employee with Disabilities · unticked = can request as usual',
          'ot.settings.hiddenLeaveTitle': 'Positions that cannot request leave',
          'ot.settings.hiddenLeaveHelp': 'Ticked positions lose the Request leave button and cannot be picked in Request leave for all · unticked = can request as usual',
          'leave.positionNotEligible': 'This position cannot request leave',
          'ot.common.back': 'Back',
          'ot.settings.scopeLeave': '(Leave request)',
          'noti.download': 'Ready to download',
          'noti.downloadAria': 'Documents ready to download',
          'noti.downloadEmpty': 'No document is ready to download yet',
          'noti.backdated': 'Backdated',
          'ot.downloads.title': 'Downloads',
          'ot.downloads.heading': 'Download OT documents',
          'ot.downloads.subtitle': 'Pick the day to send to HR — days with new requests after a download turn red',
          'ot.downloads.headingLeave': 'Download Section 75 leave documents',
          'ot.downloads.subtitleLeave': 'Pick the leave day to send to HR — every request filed up to the leave date is in one file',
          'ot.downloads.colDocument': 'Document',
          'ot.downloads.mainDoc': 'All requests filed up to the leave date',
          'ot.downloads.doneTitle': 'Downloaded',
          'ot.downloads.doneMessage': 'File saved. You can send it to HR to import into Bplus.',
          'ot.downloads.failTitle': 'Download failed',
          'ot.downloads.failMessage': 'The file could not be created. Please try again.',
          'ot.downloads.failSession': 'Your session expired. Please sign in again and retry.',
          'ot.downloads.month': 'Month',
          'ot.downloads.calendarAria': 'Document status calendar',
          'ot.downloads.download': 'Download',
          'ot.downloads.legendRestale': 'Re-download',
          'ot.downloads.legendReady': 'Ready',
          'ot.downloads.legendDone': 'Downloaded',
          'ot.downloads.legendWaiting': 'Pending approval',
          'ot.downloads.legendEmpty': 'No request',
          'ot.downloads.tagRestale': 'Re-download',
          'ot.downloads.tagReady': 'Ready',
          'ot.downloads.tagDone': 'Done',
          'ot.downloads.tagWaiting': 'Pending',
          'ot.downloads.tagBackdated': 'Backdated',
          'ot.downloads.statRequests': 'Total requests',
          'ot.downloads.lastExport': 'Last download',
          'ot.downloads.restaleNote': 'This day was downloaded before, but requests were added or edited afterwards — download again for HR',
          'leave.submitHint': 'Submit selected requests to the Supervisor',
          'leave.approveTab': 'Approve leave',
          'leave.payrollCycle': 'Payroll cycle',
          'leave.note': 'Note',
          'leave.reason': 'Reason',
          'leave.status': 'Status',
          'leave.route': 'Full status',
          'leave.action': 'Action',
          'leave.select': 'Select',
          'leave.loading': 'Loading...',
          'leave.loadingRequests': 'Loading requests...',
          'leave.emptyDepartment': 'No employees found in this department.',
          'leave.emptyScope': 'No employee data is available in your scope.',
          'leave.emptyPermission': 'No departments are assigned to you.',
          'leave.emptyCategory': 'No items in this category.',
          'leave.loadError': 'Unable to load data.',
          'leave.loadListError': 'Unable to load the list.',
          'leave.peopleSuffix': 'people',
          'leave.saved': 'Recorded',
          'leave.document': 'Leave request document',
          'leave.documentHint': 'Requests go to the supervisor as soon as you submit.',
          'leave.backDepartment': 'Back to departments',
          'leave.submit': 'Submit for approval',
          'leave.form.title': 'Submit leave request',
          'leave.form.from': 'From date',
          'leave.form.to': 'To date',
          'leave.form.notePlaceholder': 'Additional details (optional)',
          'leave.form.splitHint': 'The date range is split into one full-day request per day to match the Bplus import form.',
          'leave.cancel': 'Cancel',
          'leave.save': 'Send for approval',
          'leave.success': 'Success',
          'leave.done': 'Completed successfully.',
          'leave.jumpToDate': 'Taking you to',
          'leave.shiftEstimated': 'Last recorded shift',
          'leave.shiftEstimatedShort': 'last shift',
          'leave.ok': 'OK',
          'leave.view': 'Edit leave',
          'leave.edit': 'Edit',
          'leave.approval.list': 'Leave approval requests',
          'leave.refresh': 'Refresh',
          'leave.selected': 'Selected',
          'leave.bulkReject': 'Reject selected',
          'leave.bulkApprove': 'Approve selected',
          'leave.review': 'Review request',
          'leave.confirm': 'Confirm decision',
          'leave.confirmApprove': 'Confirm approval',
          'leave.confirmReject': 'Confirm rejection',
          'leave.confirmReverseApprove': 'Change this rejected request to approved?',
          'leave.confirmReverseReject': 'Change this approved request to rejected?',
          'leave.confirmApproveHint': 'Once approved, the leave passes immediately and is ready for the Bplus document.',
          'leave.confirmRejectHint': 'A reason is required and will be applied to every selected request.',
          'leave.confirmReverseApproveHint': 'This affects the document status and Bplus export. The request will be approved again; review the previous reason before confirming.',
          'leave.confirmReverseRejectHint': 'This affects the document status and Bplus export. The request will be rejected and a reason is required for the audit trail.',
          'leave.confirmButton': 'Confirm',
          'leave.notePlaceholder': 'Note or reason',
          'leave.employeeNote': 'Note from Foreman',
          'leave.supervisorNote': 'Note from Supervisor',
          'leave.reviewResult': 'Review result',
          'leave.rejectReasonRequired': 'Please enter a rejection reason.',
          'leave.expired': 'Approval expired',
          'leave.status.draft': 'In progress',
          'leave.status.submitted': 'Pending',
          'leave.status.approved': 'Passed',
          'leave.status.rejected': 'Rejected',
          'leave.status.waitingSupervisor': '(waiting for Supervisor approval)',
          'ot.approvals.expired': 'Approval expired',
          'ot.settings.title': 'System settings',
          'ot.settings.subtitle': 'Set administrators, allowed positions, and OT/leave owners for each department.',
          'ot.settings.adminTitle': 'System administrators',
          'ot.settings.adminHelp': 'Can manage system settings and download OT/leave documents.',
          'ot.settings.noOtAdmins': 'No additional system administrators yet.',
          'ot.settings.searchAdmin': 'Search employees to add as administrators',
          'ot.settings.removeAdminConfirm': 'Remove this employee from system administrators?',
          'ot.settings.positionsTitle': 'Positions allowed to enter',
          'ot.settings.positionsHelp': 'When none are selected, general employees are denied; admins, Foremen, and Supervisors retain access.',
          'ot.settings.foremanTitle': 'Assign Foreman',
          'ot.settings.foremanHelp': 'Several people per department, each on its own shift.',
          'ot.settings.supervisorTitle': 'Assign Supervisor',
          'ot.settings.supervisorHelp': 'Select one person per company and department.',
          'ot.settings.notAssigned': 'Not assigned',
          'ot.settings.chooseFromDepartment': 'Choose from employees in this department.',
          'ot.settings.currentAssignment': 'Current selection',
          'ot.settings.currentAssignments': 'People responsible for this department',
          'ot.settings.foremanMultiHint': 'You can add more than one person and pick the shift each one covers. The shift is a label only and does not restrict access.',
          'ot.settings.alreadyAssigned': 'Added',
          'ot.settings.shiftGroup': 'Shift covered',
          'ot.settings.shift.none': 'No shift set',
          'ot.settings.shift.all': 'All shifts',
          'ot.settings.shift.morning': 'Shift A',
          'ot.settings.shift.night': 'Shift B',
          'ot.shiftFilter.label': 'Select shift',
          'ot.shiftFilter.all': 'All shifts',
          'ot.shiftFilter.wholeGroup': 'All ',
          'ot.shiftFilter.other': 'Other shifts',
          'ot.shiftFilter.none': 'No shift',
          'ot.shiftFilter.empty': 'No employees on the selected shift',
          'ot.search.label': 'Search employees',
          'ot.attendance.pickDate': 'Pick a date',
          'ot.search.placeholder': 'Search by code or name',
          'ot.search.empty': 'No employees matched your search',
          'ot.requests.bulkOpen': 'Request OT for all',
          'ot.requests.bulkLabel': 'Request OT for the whole shift',
          'ot.requests.bulkNext': 'Next',
          'ot.requests.bulkBack': 'Back',
          'ot.requests.bulkSubmit': 'Send for approval',
          'ot.requests.bulkSelectAll': 'Select all',
          'ot.requests.bulkHiddenPicked': 'selected outside this filter',
          'ot.requests.bulkHasRequest': 'Already requested',
          'ot.requests.bulkPositionNotEligible': 'This position is not eligible for OT',
          'ot.requests.bulkInvalidRow': 'Some rows have an invalid time or amount',
          'ot.requests.actors': 'Handled by',
          'ot.requests.actorRequested': 'Requested',
          'ot.requests.actorApproved': 'Approved',
          'ot.route.column': 'All statuses',
          'ot.route.kicker': 'OT STATUS',
          'ot.route.title': 'All statuses',
          'ot.route.noRequest': 'No OT request',
          'ot.route.noRequestDetail': 'No operator information is available for this item.',
          'ot.route.draft': 'Request submitted',
          'ot.route.submitted': 'Request submitted',
          'ot.route.notStarted': 'Not started',
          'ot.route.waitingApproval': 'Waiting for approval',
          'ot.route.approved': 'Approved',
          'ot.route.rejected': 'Rejected',
          'ot.route.scanPassed': 'Scan time complete',
          'ot.route.scanFailed': 'Scan time incomplete',
          'ot.route.waitingScan': 'Waiting for scan check',
          'ot.route.notPassed': 'Not passed',
          'ot.route.passed': 'Passed',
          'ot.route.inProgress': 'In progress',
          'ot.route.open': 'View all statuses',
          'ot.route.zoomPhoto': 'View larger employee photo',
          'ot.route.step': 'Step',
          'ot.route.request': 'Request OT',
          'ot.route.approval': 'Approve OT',
          'ot.route.attendance': 'Verify OT scan time',
          'ot.route.otRange': 'OT period',
          'ot.route.otType': 'OT type',
          'ot.route.shift': 'Shift',
          'ot.route.finalResult': 'Result',
          'ot.route.requestedBy': 'Requested by',
          'ot.route.approvedBy': 'Approved by',
          'ot.route.actionTime': 'Action time',
          'ot.route.requestNote': 'Request note',
          'ot.route.decisionNote': 'Approval note',
          'ot.route.scanTime': 'Scan time',
          'ot.route.clockIn': 'In',
          'ot.route.clockOut': 'Out',
          'ot.route.requiredRange': 'Required coverage',
          'ot.route.checkedAt': 'Last checked',
          'ot.route.condition': 'Condition',
          'ot.route.conditionDetail': 'Both OT approval and OT scan time must pass.',
          'ot.route.exportResult': 'V74 data',
          'ot.route.exported': 'V74 exported',
          'ot.route.readyV74': 'Ready for Excel V74',
          'ot.route.failedApproval': 'Approval was rejected',
          'ot.route.failedAttendance': 'Scan time does not cover the OT period',
          'ot.route.waitBoth': 'Waiting for approval and scan verification',
          'ot.requests.bulkAutoTime': 'Calculate time automatically from shift',
          'ot.requests.myShift': 'Your shift',
          'ot.settings.employeeList': 'Department employees',
          'ot.settings.companySearchResults': 'Search results from all companies',
          'ot.settings.searchAllCompany': 'Search employees across all companies',
          'ot.settings.noDepartments': 'No departments with active employees were found.',
          'ot.settings.noInsightAccount': 'No Insight account',
          'ot.settings.removeAssignmentConfirm': 'Remove the responsible person from this department?',
          'ot.common.departments': 'departments',
          'ot.common.people': 'people',
          'ot.common.remove': 'Remove',
          'ot.common.change': 'Change',
          'ot.common.choose': 'Choose',
          'ot.common.searchEmployee': 'Search employees',
          'ot.common.searchPosition': 'Search positions',
          'ot.common.selectVisible': 'Select visible',
          'ot.common.clear': 'Clear',
          'ot.common.save': 'Save',
          'ot.common.saved': 'Saved',
          'ot.common.add': 'Add',
          'ot.common.loading': 'Loading...',
          'ot.common.noResults': 'No results found',
          'ot.common.cancel': 'Cancel',
          'ot.common.close': 'Close',
          'ot.common.error': 'Unable to complete the action'
        },
        my: {
          'toast.otApprovalDenied': 'Time & Leave Approval စနစ်ကို အသုံးပြုခွင့် မရှိသေးပါ။',
          'nav.otApproval': 'အလုပ်ချိန်နှင့် ခွင့်',
          'nav.otRequests': 'အချိန်ပို တောင်းဆိုမှု',
          'nav.otApprovals': 'အချိန်ပို အတည်ပြုခြင်း',
          'nav.requestGroup': 'တောင်းဆိုမှုများ',
          'nav.approvalGroup': 'စစ်ဆေးအတည်ပြုရန်',
          'nav.leaveRequests': 'ပုဒ်မ ၇၅ ခွင့် တောင်းဆိုမှု',
          'nav.leaveApprovals': 'ပုဒ်မ ၇၅ ခွင့် အတည်ပြုခြင်း',
          'noti.appOt': 'Time & Leave Approval',
          'noti.app5s': 'SUPAVUT 5S AREA',
          'noti.all': 'အားလုံး',
          'noti.leave': 'ခွင့်',
          'ot.requests.title': 'OT တောင်းဆိုမှု',
          'ot.requests.heading': 'OT တောင်းဆိုရန်',
          'ot.requests.description': 'ဝန်ထမ်း၊ OT အမျိုးအစားနှင့် အချိန်ပိုကာလကို ရွေးပါ။',
          'ot.requests.comingSoon': 'OT တောင်းဆိုမှုဖောင်ကို Bplus စကင်ဒေတာနှင့် ချိတ်ဆက်နေပါသည်။',
          'ot.requests.comingSoonDescription': 'နောက်တစ်ဆင့်တွင် သင်တာဝန်ယူသည့် ဌာနများမှ ဝန်ထမ်းများနှင့် OT အမျိုးအစား၊ နာရီရွေးချယ်မှုများကို ပြသပါမည်။',
          'ot.approvals.title': 'OT အတည်ပြုရန်',
          'ot.approvals.heading': 'OT အတည်ပြုမှု',
          'ot.approvals.description': 'သင်တာဝန်ယူသည့် ဌာနများ၏ အလုပ်ချိန်နှင့် OT တောင်းဆိုမှုများကို စစ်ဆေးအတည်ပြုပါ။',
          'ot.approvals.comingSoon': 'OT အတည်ပြုမှုဖောင်ကို ပြင်ဆင်နေပါသည်။',
          'ot.approvals.comingSoonDescription': 'နောက်တစ်ဆင့်တွင် Foreman တောင်းဆိုမှု၊ Bplus စကင်ရလဒ်နှင့် အတည်ပြုခလုတ်များကို ပြသပါမည်။',
          'ot.requests.formLabel': 'အချိန်ပို တောင်းဆိုလွှာ',
          'ot.requests.company': 'ကုမ္ပဏီ',
          'ot.requests.foreman': 'Foreman',
          'ot.requests.paperTitle': 'အချိန်ပို အတည်ပြု တောင်းဆိုလွှာ',
          'ot.requests.select': 'ရွေးရန်',
          'ot.requests.shift': 'အလုပ်ဆိုင်း',
          'ot.requests.requestOt': 'OT တောင်းရန်',
          'ot.requests.workDate': 'အလုပ်ရက်',
          'ot.requests.department': 'ဌာန',
          'ot.requests.selectEmployee': 'OT အတွက် ဝန်ထမ်းရွေးရန်',
          'ot.requests.editRequest': 'OT ပြင်ရန်',
          'ot.requests.revisionNote': 'ဤတောင်းဆိုမှုကို ပို့/အတည်ပြုပြီးဖြစ်သည် — ပြင်လျှင် အဟောင်းကို အစားထိုးပြီး ပြန်အတည်ပြုရမည်',
          'ot.requests.reviseConfirmTitle': 'တောင်းဆိုမှု ပြင်ဆင်ရန် အတည်ပြုပါ',
          'ot.requests.reviseConfirmNote': 'ဤတောင်းဆိုမှုကို အတည်ပြုရန် ပို့ပြီးဖြစ်သည်။ ပြင်ဆင်ပါက ယခင်အချက်အလက်ကို အစားထိုးပြီး အတည်ပြုမှုအဆင့်ကို အစမှ ပြန်စရမည်။',
          'ot.requests.reviseConfirmExported': 'ဤတောင်းဆိုမှုကို HR ထံ ပို့ရန် ဒေါင်းလုဒ်ပြီးဖြစ်သည်။ ပြင်ဆင်ပါက ယခင်အချက်အလက်ကို အစားထိုးပြီး ပြန်အတည်ပြုရမည်။ စီမံခန့်ခွဲသူအား ဖိုင်အသစ် ပြန်ဒေါင်းလုဒ်ရန် အကြောင်းကြားပါ၊ မဟုတ်ပါက Bplus ရှိ နာရီများ ကိုက်ညီမည်မဟုတ်ပါ။',
          'ot.requests.reviseConfirmAccept': 'သဘောတူပြီး ဆက်လုပ်ရန်',
          'ot.requests.reviseConfirmClose': 'ပိတ်ရန်',
          'ot.requests.revisionExported': 'ဤတောင်းဆိုမှုကို HR အတွက် ဒေါင်းလုဒ်ပြီးဖြစ်သည် — ပြင်နိုင်သော်လည်း admin ကို ဖိုင်ပြန်ဒေါင်းရန် အကြောင်းကြားပါ',
          'ot.requests.otType': 'ကတ်ရိုက်ခြင်းရလဒ်',
          'ot.requests.startTime': 'စတင်ချိန်',
          'ot.requests.endTime': 'ပြီးဆုံးချိန်',
          'ot.requests.dailyAmount': 'တောင်းဆိုသော OT နာရီ',
          'ot.requests.hours': 'နာရီအရေအတွက်',
          'ot.requests.hour': 'နာရီ',
          'ot.requests.hourShort': 'နာရီ',
          'ot.requests.minute': 'မိနစ်',
          'ot.requests.invalidTimeRange': 'စတင်ချိန်နှင့် ပြီးဆုံးချိန် မတူရပါ',
          'ot.requests.durationRequired': 'တောင်းဆိုမည့် OT နာရီအရေအတွက် ဖြည့်ပါ',
          'ot.requests.durationTooLong': 'တောင်းဆိုသည့်အရေအတွက်သည် ရွေးထားသောအချိန်ကာလထက် မကျော်ရပါ',
          'ot.requests.timeRange': 'OT အချိန်',
          'ot.requests.specialOt': 'အထူး OT',
          'ot.requests.specialHint': '၂ နာရီကျော် တောင်းရန် ဖွင့်ပါ',
          'ot.requests.note': 'မှတ်ချက်',
          'ot.requests.saveRequest': 'အတည်ပြုရန် ပို့မည်',
          'ot.requests.saved': 'တောင်းဆိုမှု သိမ်းပြီး',
          'ot.requests.submitted': 'Supervisor ထံ ပို့ပြီး',
          'ot.requests.submitSuccessTitle': 'အောင်မြင်ပါသည်',
          'ot.requests.completed': 'အောင်မြင်',
          'ot.requests.selectedPeople': 'ရွေးထားသူ',
          'ot.requests.batchHint': 'ရွေးထားသော တောင်းဆိုမှုများကို Supervisor ထံ ပို့မည်',
          'ot.requests.submitBatch': 'အတည်ပြုရန် ပို့မည်',
          'ot.approvals.documentTitle': 'အချိန်ပို အတည်ပြုစာရင်း',
          'ot.approvals.pendingTab': 'ဆောင်ရွက်ရန်',
          'ot.approvals.attendanceFailedTab': 'အချိန်မပြည့်',
          'ot.approvals.approvedTab': 'အတည်ပြုပြီး',
          'ot.approvals.rejectedTab': 'ပယ်ချပြီး',
          'ot.approvals.allTab': 'အားလုံး',
          'ot.approvals.allDates': 'ရက်အားလုံး',
          'ot.approvals.allTypes': 'OT အမျိုးအစားအားလုံး',
          'ot.approvals.allDatesHint': 'ရက်အားလုံး၏ တောင်းဆိုမှုများကို ပြသနေသည်',
          'ot.approvals.dateOnly': 'OT ရက်စွဲ',
          'ot.approvals.items': 'ခု',
          'ot.approvals.refresh': 'ပြန်လည်ဖတ်ရန်',
          'ot.approvals.loading': 'တောင်းဆိုမှုများ ဖတ်နေသည်',
          'ot.approvals.empty': 'ဤအခြေအနေတွင် မရှိပါ',
          'ot.approvals.loadError': 'စာရင်းကို မဖတ်နိုင်ပါ',
          'ot.approvals.scanTime': 'စကင်ချိန်',
          'ot.approvals.action': 'လုပ်ဆောင်ချက်',
          'ot.approvals.review': 'စစ်ဆေးရန်',
          'ot.approvals.reviewLabel': 'စစ်ဆေးပြီး ဆုံးဖြတ်ရန်',
          'ot.approvals.note': 'မှတ်ချက်',
          'ot.approvals.approve': 'အတည်ပြု',
          'ot.approvals.reject': 'ပယ်ချ',
          'ot.approvals.confirmLabel': 'ဆုံးဖြတ်ချက် အတည်ပြုရန်',
          'ot.approvals.addNote': 'မှတ်ချက် ထည့်ရန်',
          'ot.approvals.addReason': 'အကြောင်းပြချက် ရေးရန်',
          'ot.approvals.confirmApproveTitle': 'ဤတောင်းဆိုမှုကို အတည်ပြုမလား?',
          'ot.approvals.confirmRejectTitle': 'ဤတောင်းဆိုမှုကို ပယ်ချမလား?',
          'ot.approvals.confirmReverseApproveTitle': 'ပယ်ချထားသော တောင်းဆိုမှုကို အတည်ပြုအဖြစ် ပြောင်းမလား?',
          'ot.approvals.confirmReverseRejectTitle': 'အတည်ပြုထားသော တောင်းဆိုမှုကို ပယ်ချအဖြစ် ပြောင်းမလား?',
          'ot.approvals.confirmApproveHint': 'အတည်ပြုချက်ကို သိမ်းဆည်းပြီး OT ပြီးနောက် နောက်ဆုံးစကင်ကို စောင့်ပါမည်။',
          'ot.approvals.confirmRejectHint': 'Foreman ထံ ပြန်ပို့မီ အကြောင်းပြချက် ရေးပါ။',
          'ot.approvals.confirmReverseApproveHint': 'ဤလုပ်ဆောင်ချက်သည် စာရွက်စာတမ်းအခြေအနေနှင့် Bplus Export ကို သက်ရောက်စေသည်။ ထပ်မံအတည်ပြုမည်ဖြစ်သောကြောင့် ယခင်အကြောင်းပြချက်နှင့် စကင်ဒေတာကို စစ်ဆေးပြီးမှ အတည်ပြုပါ။',
          'ot.approvals.confirmReverseRejectHint': 'ဤလုပ်ဆောင်ချက်သည် စာရွက်စာတမ်းအခြေအနေနှင့် Bplus Export ကို သက်ရောက်စေသည်။ တောင်းဆိုမှုကို ပယ်ချမည်ဖြစ်ပြီး နောက်ကြောင်းစစ်ဆေးရန် အကြောင်းပြချက် ထည့်ရမည်။',
          'ot.approvals.rejectReason': 'အကြောင်းပြချက် (မဖြစ်မနေ)',
          'ot.approvals.rejectReasonRequired': 'ပယ်ချသည့် အကြောင်းပြချက်ကို ရေးပါ',
          'ot.approvals.confirmApprove': 'အတည်ပြုမည်',
          'ot.approvals.confirmReject': 'ပယ်ချမည်',
          'ot.approvals.selectAll': 'အားလုံးရွေးရန်',
          'ot.approvals.selectedCount': 'ရွေးထားသည်',
          'ot.approvals.bulkApprove': 'ရွေးထားသည်များ အတည်ပြုရန်',
          'ot.approvals.bulkReject': 'ရွေးထားသည်များ ပယ်ချရန်',
          'ot.approvals.confirmBulkTitle': 'ရွေးထားသော {n} ခုကို အတည်ပြုမလား?',
          'ot.approvals.confirmBulkHint': 'ရွေးထားသော တောင်းဆိုမှုအားလုံးကို အတည်ပြုပြီး OT ပြီးနောက် နောက်ဆုံးစကင်ကို စောင့်ပါမည်။',
          'ot.approvals.confirmBulkRejectTitle': 'ရွေးထားသော {n} ခုကို ပယ်ချမလား?',
          'ot.approvals.confirmBulkRejectHint': 'မဖြစ်မနေဖြည့်ရမည့် အကြောင်းပြချက်တစ်ခုတည်းကို ရွေးထားသော ဝန်ထမ်းတိုင်းအတွက် မှတ်တမ်းတင်ပြီး Foreman ထံ ပြန်ပို့ပါမည်။',
          'ot.reason.button': 'အကြောင်းပြချက်',
          'ot.reason.requestNote': 'Foreman မှ မှတ်ချက်',
          'ot.reason.decisionNote': 'Supervisor မှ မှတ်ချက်',
          'ot.reason.cancelReason': 'ပယ်ဖျက်ရသည့် အကြောင်းပြချက်',
          'ot.reason.close': 'ပိတ်ရန်',
          'ot.attendance.status': 'အခြေအနေ',
          'ot.attendance.completed': 'အောင်မြင်',
          'ot.status.notRequested': 'OT မတောင်းထား',
          'ot.status.notWorkedOt': 'OT မလုပ်ပါ',
          'ot.status.notEligible': 'OT တောင်း၍ မရပါ',
          'ot.status.draft': 'ဆောင်ရွက်ဆဲ',
          'ot.status.pending': 'ဆောင်ရွက်ဆဲ',
          'ot.status.pendingDetail': '(အတည်ပြုမှုနှင့် OT ပြီးနောက် နောက်ဆုံးစကင်ကို စောင့်နေသည်)',
          'ot.status.waitingApproval': 'ဆောင်ရွက်ဆဲ',
          'ot.status.waitingApprovalDetail': '(အတည်ပြုရန် စောင့်နေသည်)',
          'ot.status.approvedWaitingScan': 'ဆောင်ရွက်ဆဲ',
          'ot.status.waitingScanDetail': '(OT ပြီးနောက် နောက်ဆုံးစကင်ကို စောင့်နေသည်)',
          'ot.status.success': 'အောင်မြင်',
          'ot.status.failedTime': 'မအောင်မြင်',
          'ot.status.failedTimeDetail': '(OT အချိန် မပြည့်ပါ)',
          'ot.status.rejected': 'ပယ်ချပြီး',
          'ot.status.cancelled': 'ပယ်ဖျက်ပြီး',
          'ot.status.exported': 'Export လုပ်ပြီး',
          'systems.otApprovalName': 'TIME & LEAVE APPROVAL',
          'systems.otApprovalDesc': 'အချိန်ပိုနှင့် ခွင့်တောင်းဆိုမှုများကို အတည်ပြုပြီး Bplus သို့ ထည့်သွင်းရန် စာရွက်စာတမ်းများ ပြင်ဆင်သည့်စနစ်။',
          'systems.availableNow': 'အဓိကစနစ် ၃ ခု',
          'systems.availableNowWithRecruit': 'အဓိကစနစ် ၄ ခု',
          'ot.overview': 'အကျဉ်းချုပ်',
          'ot.attendance.kicker': 'BPLUS ATTENDANCE',
          'common.rowNo': 'အမှတ်စဉ်',
          'common.prevPage': 'ယခင်စာမျက်နှာ',
          'common.nextPage': 'နောက်စာမျက်နှာ',
          'ot.attendance.title': 'အလုပ်ဝင်–အလုပ်ထွက်ချိန်',
          'ot.attendance.subtitle': 'ရွေးချယ်ထားသောရက်အတွက် ဝန်ထမ်းများ၏ အလုပ်ဝင်–အလုပ်ထွက်ချိန်ကို ကြည့်ရှုပါ။',
          'ot.attendance.today': 'ယနေ့',
          'ot.attendance.selectDate': 'ရက်စွဲရွေးပါ',
          'ot.export.scopeLabel': 'OT စာရွက်စာတမ်းပုံစံ',
          'ot.export.selectedDate': 'OT စာရွက်စာတမ်း (ရွေးထားသောရက်)',
          'ot.export.all': 'OT စာရွက်စာတမ်း (အားလုံး)',
          'ot.export.download': 'ဒေါင်းလုဒ်',
          'ot.attendance.liveSource': 'Bplus · အလိုအလျောက် အပ်ဒိတ်',
          'ot.attendance.historySource': 'Bplus · ယခင်ဒေတာ',
          'ot.attendance.localSource': 'Local demo · နမူနာဒေတာ',
          'ot.attendance.snapshotSource': 'Bplus · Local snapshot',
          'ot.attendance.mixedSource': 'Bplus + Local fallback',
          'ot.attendance.refreshing': 'Bplus မှ အပ်ဒိတ်လုပ်နေသည်',
          'ot.attendance.totalEmployees': 'ဝန်ထမ်းစုစုပေါင်း',
          'ot.attendance.clockedIn': 'အလုပ်ဝင်စကင်',
          'ot.attendance.clockedOut': 'နောက်ဆုံးစကင် / အလုပ်ထွက်ချိန်',
          'ot.attendance.clockedInShort': 'ဝင်',
          'ot.attendance.clockedOutShort': 'ထွက်',
          'ot.attendance.shiftUnknown': 'အလှည့် မသတ်မှတ်ရသေး',
          'ot.attendance.loadError': 'စာရင်းပြသ၍မရပါ။ ပိတ်ပြီးပြန်ဖွင့်ပါ။',
          'common.confirm': 'အတည်ပြု',
          'ot.attendance.sortRankAsc': 'ရာထူးနိမ့်မှမြင့်',
          'ot.attendance.presence': 'တက်ရောက်မှု',
          'ot.attendance.present': 'တက်',
          'ot.attendance.absent': 'မတက်',
          'ot.attendance.dayoff': 'နားရက်',
          'ot.attendance.filterColumn': 'ဤကော်လံကို စစ်ထုတ်ရန်',
          'ot.attendance.clearFilter': 'စစ်ထုတ်မှုရှင်းရန်',
          'ot.attendance.sortCode': 'ဝန်ထမ်းကုဒ်အလိုက်',
          'ot.attendance.sortRank': 'ရာထူးမြင့်မှနိမ့်',
          'ot.attendance.departmentLabel': 'ဌာန',
          'ot.attendance.allDepartments': 'ဌာနအားလုံး',
          'ot.attendance.summaryTitle': 'အနှစ်ချုပ်',
          'ot.attendance.summaryOpen': 'အနှစ်ချုပ်ကြည့်ရန်',
          'ot.common.order': 'အမှတ်စဉ်',
          'ot.branch.all': 'စက်ရုံအားလုံး',
          'ot.requests.selectAll': 'အားလုံးရွေးရန်',
          'ot.requests.sent': 'OT တောင်းဆိုချက်ကို Supervisor ထံ ပို့ပြီးပါပြီ',
          'ot.requests.sendConfirmTitle': 'ပို့ရန် အတည်ပြုပါ',
          'ot.requests.sendConfirmBody': 'ဤတောင်းဆိုချက်ကို Supervisor ထံ ချက်ချင်းပို့ပြီး အီးမေးလ်ပါ ပို့ပါမည်။ ပို့မလား?',
          'ot.requests.sendConfirmBulk': 'တောင်းဆိုချက်အားလုံးကို Supervisor ထံ ချက်ချင်းပို့ပြီး အီးမေးလ်ပါ ပို့ပါမည်။',
          'ot.requests.sendConfirmAccept': 'ပို့မည်',
          'ot.requests.sendConfirmCancel': 'မပို့တော့ပါ',
          'ot.requests.cancelConfirmTitle': 'ပယ်ဖျက်ရန် အတည်ပြုပါ',
          'ot.requests.cancelConfirmBody': 'ပယ်ဖျက်လိုက်သော တောင်းဆိုချက်ကို Supervisor စာရင်းမှ ဖယ်ပြီး အကြောင်းပြချက်နှင့်အတူ မှတ်တမ်းအဖြစ် သိမ်းထားပါမည်။',
          'ot.requests.cancelConfirmAccept': 'ပယ်ဖျက်မည်',
          'ot.requests.cancelReasonLabel': 'ပယ်ဖျက်ရသည့် အကြောင်းရင်း (ဖြည့်ရန်လိုအပ်)',
          'ot.requests.cancelReasonRequired': 'အနည်းဆုံး စာလုံး ၃ လုံး ဖြည့်ပါ',
          'ot.cancel.selectReason': 'အကြောင်းပြချက် ရွေးရန်',
          'ot.cancel.employeeUnavailable': 'ဝန်ထမ်း မအားလပ်ပါ',
          'ot.cancel.workPlanChanged': 'အလုပ်အစီအစဉ် ပြောင်းလဲသည်',
          'ot.cancel.wrongSchedule': 'ရက်စွဲ သို့ အချိန် မမှန်ပါ',
          'ot.cancel.wrongRequestType': 'တောင်းဆိုမှုအမျိုးအစား မမှန်ပါ',
          'ot.cancel.duplicateRequest': 'တောင်းဆိုမှု ထပ်နေသည်',
          'ot.cancel.other': 'အခြား',
          'ot.cancel.otherPlaceholder': 'အခြားအကြောင်းပြချက် ရေးပါ',
          'ot.calendar.sun': 'တနင်္ဂနွေ',
          'ot.calendar.mon': 'တနင်္လာ',
          'ot.calendar.tue': 'အင်္ဂါ',
          'ot.calendar.wed': 'ဗုဒ္ဓဟူး',
          'ot.calendar.thu': 'ကြာသပတေး',
          'ot.calendar.fri': 'သောကြာ',
          'ot.calendar.sat': 'စနေ',
          'ot.requests.cancelSelected': 'ရွေးထားသည်များ ပယ်ဖျက်မည်',
          'ot.requests.cancelOne': 'ပယ်ဖျက်',
          'ot.requests.cancelled': 'တောင်းဆိုချက် ပယ်ဖျက်ပြီးပါပြီ',
          'ot.requests.cancelHint': 'Supervisor မဆုံးဖြတ်ရသေးသော တောင်းဆိုချက်များကိုသာ ပယ်ဖျက်နိုင်သည်',
          'ot.requests.items': 'ခု',
          'ot.attendance.groupHeadcount': 'လူအင်အား',
          'ot.attendance.groupScan': 'ဝင်/ထွက် မှတ်တမ်း',
          'ot.attendance.groupOt': 'အချိန်ပို',
          'ot.attendance.clockedOutLong': 'အလုပ်ဆင်း',
          'ot.attendance.wholeDepartment': 'ဌာနတစ်ခုလုံး',
          'ot.attendance.otRequested': 'OT တောင်းဆို',
          'ot.attendance.approved': 'အတည်ပြုပြီး',
          'ot.attendance.departments': 'ဌာန',
          'ot.attendance.people': 'ဦး',
          'ot.attendance.openDepartment': 'ဝန်ထမ်းစာရင်းကြည့်ရန်',
          'ot.attendance.employeeList': 'ဝန်ထမ်းစာရင်း',
          'ot.attendance.employee': 'ဝန်ထမ်း',
          'ot.attendance.position': 'ရာထူး',
          'ot.attendance.department': 'ဌာန',
          'ot.attendance.clockIn': 'အလုပ်ဝင်ချိန်',
          'ot.attendance.clockOut': 'နောက်ဆုံးစကင် / အလုပ်ထွက်ချိန်',
          'ot.attendance.noRequest': 'တောင်းဆိုမှုမရှိသေး',
          'ot.attendance.notApproved': 'အတည်မပြုရသေး',
          'ot.attendance.noScan': 'စကင်မတွေ့သေး',
          'ot.attendance.latestScan': 'နောက်ဆုံးစကင်',
          'ot.attendance.rawSource': 'တိုက်ရိုက်စကင်',
          'ot.attendance.processedSource': 'Bplus မှ စီမံပြီး',
          'ot.attendance.loadingEmployees': 'Bplus မှ ဒေတာရယူနေသည်',
          'ot.attendance.bplusUnavailable': 'Bplus ဒေတာကို ယခု မဖတ်နိုင်သေးပါ။',
          'ot.attendance.noDepartments': 'သင့်ခွင့်ပြုချက်အတွင်း ဌာနမတွေ့ပါ။',
          'ot.attendance.noEmployees': 'ဤဌာနတွင် ဝန်ထမ်းမတွေ့ပါ။',
          'ot.attendance.close': 'ပိတ်ရန်',
          'ot.home.overviewEmpty': 'OT တောင်းဆိုချက် မရှိသေးပါ။ တောင်းဆိုမှုနှင့် အတည်ပြုချက်များ ဤနေရာတွင် ပေါ်လာမည်။',
          'ot.kicker': 'OVERTIME MANAGEMENT',
          'ot.role.admin': 'OT Admin',
          'ot.role.foreman': 'Foreman',
          'ot.role.supervisor': 'Supervisor',
          'ot.role.employee': 'ဝန်ထမ်း',
          'ot.home.currentRole': 'သင့်ခွင့်ပြုချက်',
          'ot.home.currentRoleHelp': 'Time & Leave Approval စနစ်တွင် သင့်အား သတ်မှတ်ထားသော အခန်းကဏ္ဍများ။',
          'ot.home.allDepartments': 'ဌာနအားလုံး',
          'ot.home.positionAccess': 'ရာထူးအလိုက် ခွင့်ပြုချက်',
          'ot.home.phaseTitle': 'ဖွံ့ဖြိုးမှုအခြေအနေ',
          'ot.home.phaseHelp': 'ခွင့်ပြုချက်နှင့် တာဝန်ခံသတ်မှတ်မှု အသင့်ဖြစ်ပါပြီ။ OT တောင်းဆိုမှုလုပ်ငန်းစဉ်ကို နောက်အဆင့်တွင် ဆက်လက်ဖွံ့ဖြိုးမည်။',
          'ot.settings.kicker': 'TIME & LEAVE APPROVAL',
          'ot.overview.otTab': 'OT အကျဉ်းချုပ်',
          'ot.overview.leaveTab': 'ခွင့်အကျဉ်းချုပ်',
          'ot.export.cycle': 'OT စာရွက်စာတမ်း (လစာစက်ဝန်း)',
          'leave.overview.title': 'ခွင့်အကျဉ်းချုပ်',
          'leave.overview.subtitle': 'ရွေးချယ်ထားသောရက်အတွက် ခွင့်တောင်းဆိုမှုနှင့် အတည်ပြုရလဒ်များကို ကြည့်ရှုပါ။',
          'leave.requests.title': 'ခွင့်တောင်းရန်',
          'leave.requests.subtitle': 'ဝန်ထမ်းကိုရွေးပြီး ပုဒ်မ ၇၅ အရ တစ်ရက်ပြည့်ခွင့်ကို မှတ်တမ်းတင်ပါ။',
          'leave.approvals.title': 'ခွင့်အတည်ပြုခြင်း',
          'leave.approvals.subtitle': 'လစာစက်ဝန်း၏ နောက်ဆုံးအတည်ပြုရက်မတိုင်မီ တစ်ရက်ပြည့်ခွင့်များကို စစ်ဆေးပါ။',
          'leave.selectDate': 'ရက်စွဲရွေးပါ',
          'leave.download': 'ဒေါင်းလုဒ်',
          'leave.export.date': 'ခွင့်စာရွက်စာတမ်း (ရွေးထားသောရက်)',
          'leave.export.cycle': 'ခွင့်စာရွက်စာတမ်း (လစာစက်ဝန်း)',
          'leave.summary.aria': 'ခွင့်အကျဉ်းချုပ်',
          'leave.employee': 'ဝန်ထမ်း',
          'leave.employees': 'ဝန်ထမ်းအားလုံး',
          'leave.position': 'ရာထူး',
          'leave.department': 'ဌာန',
          'leave.departments': 'ဌာန',
          'leave.requested': 'ခွင့်တောင်းဆိုမှု',
          'leave.requestsCount': 'တောင်းဆိုမှု',
          'leave.pending': 'စောင့်ဆိုင်းနေသည်',
          'leave.approved': 'အောင်မြင်သည်',
          'leave.approvedTab': 'အတည်ပြုပြီး',
          'leave.approve': 'အတည်ပြု',
          'leave.rejected': 'အတည်မပြုပါ',
          'leave.cancelled': 'ပယ်ဖျက်',
          'leave.cancelReason': 'ပယ်ဖျက်ရသည့် အကြောင်းပြချက်',
          'leave.all': 'အားလုံး',
          'leave.allTypes': 'ခွင့်အမျိုးအစားအားလုံး',
          'leave.allDates': 'ရက်အားလုံး',
          'leave.dateOnly': 'ရက်စွဲ',
          'leave.leaveDate': 'ခွင့်ရက်',
          'leave.requestedDate': 'တောင်းဆိုသည့်ရက်',
          'leave.items': 'ခု',
          'leave.shift': 'အလုပ်ဆိုင်း',
          'leave.selectAll': 'အားလုံး ရွေးရန်',
          'leave.leaveType': 'ခွင့်အမျိုးအစား',
          'leave.quantity': 'အရေအတွက်',
          'leave.oneDay': '၁ ရက်',
          'leave.requestTab': 'ခွင့်တောင်းရန်',
          'leave.selectedPeople': 'ဦး ရွေးထားသည်',
          'leave.noRequestToday': 'ယနေ့ ခွင့်တောင်းမှု မရှိပါ',
          'leave.noRequest': 'တောင်းဆိုမှု မရှိ',
          'leave.bulkRequest': 'အားလုံးအတွက် ခွင့်တောင်းရန်',
          'leave.bulkEmpty': 'ဤစစ်ထုတ်မှုတွင် တောင်းဆိုမှုမရှိသေးသော ဝန်ထမ်း မရှိပါ',
          'leave.bulkDone': 'ခွင့်တောင်းမှု ပို့ပြီး',
          'leave.bulkNext': 'ဇယားတွင် အမှတ်ခြစ်ပြီး အတည်ပြုရန် ပို့ပါ',
          'leave.next': 'ဆက်သွား',
          'leave.bulkSkipped': 'ကျော်သွား',
          'leave.viewDate': 'ကြည့်ရှုသည့် ရက်စွဲ',
          'leave.bulkSelectAll': 'အားလုံး ရွေးရန်',
          'leave.bulkBack': 'နောက်သို့',
          'leave.bulkHasRequest': 'ကြည့်နေသည့်ရက်တွင် တောင်းဆိုမှု ရှိပြီးဖြစ်သည်',
          'leave.bulkInvalidRange': 'ရက်စွဲအပိုင်းအခြား မမှန်ကန်ပါ',
          'leave.remove': 'မူကြမ်း ဖျက်ရန်',
          'leave.removeSelected': 'ရွေးထားသော မူကြမ်းများ ဖျက်ရန်',
          'leave.removeConfirm': 'ရွေးထားသော ခွင့်မူကြမ်းများကို ဖျက်မလား?',
          'leave.removeError': 'မူကြမ်း ဖျက်၍ မရပါ',
          'nav.otDownloads': 'HR အတွက် စာရွက်စာတမ်း',
          'ot.downloads.colSubmitted': 'တင်သွင်းသည့်ရက်',
          'ot.downloads.fileUnit': 'စောင်',
          'ot.downloads.tabOt': 'အချိန်ပို စာရွက်စာတမ်း',
          'ot.downloads.tabLeave': 'ပုဒ်မ ၇၅ ခွင့် စာရွက်စာတမ်း',
          'ot.downloads.colOtDate': 'OT လုပ်သည့်ရက်',
          'ot.downloads.colLeaveDate': 'ခွင့်ရက်',
          'ot.downloads.colPeople': 'ပို့ရန်',
          'ot.downloads.showDetail': 'အသေးစိတ် ကြည့်ရန်',
          'ot.downloads.hideDetail': 'အသေးစိတ် ဖျောက်ရန်',
          'ot.downloads.fromThisDay': 'ဤရက်တွင် တင်သွင်းသည်',
          'ot.downloads.noDocument': 'ဤရက်တွင် ပို့ရန်ဖိုင် မရှိပါ',
          'ot.downloads.modalKicker': 'HR အတွက် ဖိုင်များ',
          'ot.downloads.modalSubtitle': 'Bplus သို့ HR မထည့်မီ စာရင်းကို စစ်ဆေးပါ',
          'ot.downloads.legendRestaleHint': 'ဒေါင်းလုဒ်ပြီးပြီ သို့သော် နောက်မှ မှတ်တမ်းအသစ် ဝင်လာသည်',
          'ot.downloads.legendReadyHint': 'အတည်ပြုပြီး HR ထံ မပို့ရသေး',
          'ot.downloads.legendDoneHint': 'ကျန်ရှိသည် မရှိပါ',
          'ot.downloads.legendWaitingHint': 'Supervisor ဆုံးဖြတ်ရန် စောင့်နေဆဲ',
          'ot.downloads.otSection': 'OT ဖိုင်',
          'ot.downloads.leaveSection': 'ခွင့်ဖိုင် (ပုဒ်မ ၇၅)',
          'ot.downloads.downloadOt': 'OT ဖိုင် ဒေါင်းလုဒ်',
          'ot.downloads.downloadLeave': 'ခွင့်ဖိုင် ဒေါင်းလုဒ်',
          'ot.downloads.noOt': 'ဤရက်တွင် OT မရှိပါ',
          'ot.downloads.noLeave': 'ဤရက်တွင် ခွင့် မရှိပါ',
          'ot.downloads.leaveShort': 'ခွင့်',
          'ot.downloads.needDownload': 'ဒေါင်းလုဒ်ရန် ရက်',
          'ot.downloads.backdated': 'နောက်ကျတင်',
          'ot.downloads.downloadCount': 'ဒေါင်းလုဒ်ပြီး',
          'ot.downloads.times': 'ကြိမ်',
          'ot.downloads.readyCount': 'အဆင်သင့်',
          'ot.downloads.lastDownload': 'နောက်ဆုံး ဒေါင်းလုဒ်',
          'ot.downloads.lateFlag': 'နောက်ကျတင်',
          'ot.downloads.rowReady': 'အဆင်သင့်',
          'ot.downloads.rowWaiting': 'စောင့်ဆိုင်း',
          'ot.downloads.rowStale': 'ဒေါင်းပြီးမှ ပြင်ထား',
          'ot.downloads.historyTitle': 'ဒေါင်းလုဒ် မှတ်တမ်း',
          'ot.downloads.noHistory': 'ဤရက်ကို မဒေါင်းလုဒ်ရသေးပါ',
          'ot.downloads.warnRestale': 'ဤရက်ကို HR ထံ ပို့ပြီးပြီ သို့သော် နောက်မှ မှတ်တမ်းအသစ် ဝင်လာသည်။ ထပ်ဒေါင်းလုဒ်လုပ်ပါ။',
          'ot.downloads.warnBackdated': 'နောက်ကျတင်သည့် မှတ်တမ်းများ:',
          'ot.settings.scopeOt': '(OT တောင်းဆိုမှု)',
          'nav.workDetail': 'အလုပ်အသေးစိတ်',
          'emp.title': 'အလုပ်အသေးစိတ်',
          'emp.emptyTitle': 'admin အတွက် ဒေတာ မရှိပါ',
          'emp.emptyBody': 'ဤအကောင့်သည် စနစ်စီမံခန့်ခွဲသူ အကောင့်ဖြစ်ပြီး ဝန်ထမ်းမှတ်တမ်းနှင့် မချိတ်ဆက်ထားသဖြင့် OT နှင့် ခွင့် မှတ်တမ်း မရှိပါ — ခြုံငုံသုံးသပ်ချက်စာမျက်နှာမှ ဝန်ထမ်းအမည်ကို နှိပ်၍ ကြည့်ပါ',
          'emp.otTitle': 'အတည်ပြုပြီး OT',
          'emp.leaveTitle': 'အတည်ပြုပြီး ခွင့်',
          'emp.otCount': 'အတည်ပြုပြီး OT (အကြိမ်)',
          'emp.otHours': 'OT စုစုပေါင်း နာရီ',
          'emp.leaveCount': 'အတည်ပြုပြီး ခွင့်ရက်',
          'emp.markedDays': 'မှတ်တမ်းရှိသည့်ရက်',
          'emp.approvedOnly': 'အတည်ပြုပြီး မှတ်တမ်းသာ ပြသည်',
          'emp.colDate': 'ရက်စွဲ',
          'emp.colShiftCode': 'အလှည့်ကုဒ်',
          'emp.colShiftName': 'အလှည့်အမည်',
          'emp.colIn': 'ဝင်ချိန်',
          'emp.colOut': 'ထွက်ချိန်',
          'emp.colOtHours': 'တောင်းဆိုသည့် OT အချိန်',
          'emp.colOt': 'OT',
          'emp.colLeave': 'ခွင့်',
          'emp.today': 'ယနေ့',
          'emp.backToThisMonth': 'ဤလသို့ ပြန်သွားရန်',
          'emp.pickMonth': 'လ ရွေးရန်',
          'emp.prevYear': 'ယခင်နှစ်',
          'emp.nextYear': 'နောက်နှစ်',
          'ot.settings.hiddenRequestTitle': 'OT မတောင်းဆိုနိုင်သော ရာထူးများ',
          'ot.settings.hiddenRequestHelp': 'အမှတ်ခြစ်ထားသော ရာထူးများသည် OT တောင်းဆိုခလုတ် မမြင်ရပါ · မခြစ်ပါက ပုံမှန်တောင်းဆိုနိုင်သည်',
          'ot.settings.hiddenLeaveTitle': 'ခွင့် မတောင်းဆိုနိုင်သော ရာထူးများ',
          'ot.settings.hiddenLeaveHelp': 'အမှတ်ခြစ်ထားသော ရာထူးများသည် ခွင့်တောင်းဆိုခလုတ် မမြင်ရပါ · မခြစ်ပါက ပုံမှန်တောင်းဆိုနိုင်သည်',
          'leave.positionNotEligible': 'ဤရာထူးသည် ခွင့်မတောင်းဆိုနိုင်ပါ',
          'ot.common.back': 'နောက်သို့',
          'ot.settings.scopeLeave': '(ခွင့်တောင်းဆိုမှု)',
          'noti.download': 'ဒေါင်းလုဒ်ရန် အသင့်',
          'noti.downloadAria': 'ဒေါင်းလုဒ်ရန် အသင့်ဖြစ်သော စာရွက်စာတမ်းများ',
          'noti.downloadEmpty': 'ဒေါင်းလုဒ်ရန် အသင့်ဖြစ်သော စာရွက်စာတမ်း မရှိသေးပါ',
          'noti.backdated': 'နောက်ပြန်',
          'ot.downloads.title': 'ဒေါင်းလုဒ်',
          'ot.downloads.heading': 'OT စာရွက်စာတမ်း ဒေါင်းလုဒ်',
          'ot.downloads.subtitle': 'HR ထံပို့ရန် ရက်ကိုရွေးပါ — ဒေါင်းလုဒ်ပြီးမှ တောင်းဆိုမှုအသစ်ဝင်သောရက်သည် အနီရောင်ပြသည်',
          'ot.downloads.headingLeave': 'ပုဒ်မ ၇၅ ခွင့် စာရွက်စာတမ်း ဒေါင်းလုဒ်',
          'ot.downloads.subtitleLeave': 'HR ထံပို့ရန် ခွင့်ရက်ကိုရွေးပါ — ခွင့်ရက်အထိ တင်သွင်းသမျှ ဖိုင်တစ်ခုတည်းတွင် ပါဝင်သည်',
          'ot.downloads.colDocument': 'စာရွက်စာတမ်း',
          'ot.downloads.mainDoc': 'ခွင့်ရက်အထိ တင်သွင်းသမျှ စုစည်း',
          'ot.downloads.doneTitle': 'ဒေါင်းလုဒ်ပြီးပါပြီ',
          'ot.downloads.doneMessage': 'ဖိုင် သိမ်းဆည်းပြီးပါပြီ။ Bplus ထည့်ရန် HR ထံ ပို့နိုင်ပါပြီ။',
          'ot.downloads.failTitle': 'ဒေါင်းလုဒ် မအောင်မြင်ပါ',
          'ot.downloads.failMessage': 'ဖိုင် ဖန်တီး၍ မရပါ။ ထပ်မံ ကြိုးစားပါ။',
          'ot.downloads.failSession': 'သက်တမ်းကုန်သွားပါပြီ။ ပြန်လည်ဝင်ရောက်ပြီး ထပ်ကြိုးစားပါ။',
          'ot.downloads.month': 'လ',
          'ot.downloads.calendarAria': 'စာရွက်စာတမ်းအခြေအနေ ပြက္ခဒိန်',
          'ot.downloads.download': 'ဒေါင်းလုဒ်',
          'ot.downloads.legendRestale': 'ထပ်ဒေါင်းရန်',
          'ot.downloads.legendReady': 'အသင့်',
          'ot.downloads.legendDone': 'ဒေါင်းပြီး',
          'ot.downloads.legendWaiting': 'အတည်ပြုရန်',
          'ot.downloads.legendEmpty': 'တောင်းဆိုမှု မရှိ',
          'ot.downloads.tagRestale': 'ထပ်ဒေါင်း',
          'ot.downloads.tagReady': 'အသင့်',
          'ot.downloads.tagDone': 'ပြီး',
          'ot.downloads.tagWaiting': 'စောင့်',
          'ot.downloads.tagBackdated': 'နောက်ပြန်',
          'ot.downloads.statRequests': 'တောင်းဆိုမှုစုစုပေါင်း',
          'ot.downloads.lastExport': 'နောက်ဆုံးဒေါင်းလုဒ်',
          'ot.downloads.restaleNote': 'ဤရက်ကို ဒေါင်းလုဒ်ပြီးဖြစ်သော်လည်း နောက်ပိုင်းတွင် တောင်းဆိုမှု ထပ်တိုး/ပြင်ဆင်ခဲ့သည် — HR အတွက် ပြန်ဒေါင်းပါ',
          'leave.submitHint': 'ရွေးထားသော တောင်းဆိုမှုများကို Supervisor ထံ ပို့မည်',
          'leave.approveTab': 'ခွင့်အတည်ပြုရန်',
          'leave.payrollCycle': 'လစာအပတ်စဉ်',
          'leave.note': 'မှတ်ချက်',
          'leave.reason': 'အကြောင်းပြချက်',
          'leave.status': 'အခြေအနေ',
          'leave.route': 'အခြေအနေအားလုံး',
          'leave.action': 'လုပ်ဆောင်ချက်',
          'leave.select': 'ရွေးချယ်',
          'leave.loading': 'ဖွင့်နေသည်...',
          'leave.loadingRequests': 'တောင်းဆိုမှုများ ဖွင့်နေသည်...',
          'leave.emptyDepartment': 'ဤဌာနတွင် ဝန်ထမ်းမတွေ့ပါ။',
          'leave.emptyScope': 'သင့်ခွင့်ပြုနယ်ပယ်တွင် ဝန်ထမ်းအချက်အလက်မရှိပါ။',
          'leave.emptyPermission': 'သင့်အား တာဝန်ပေးထားသောဌာန မရှိပါ။',
          'leave.emptyCategory': 'ဤအမျိုးအစားတွင် စာရင်းမရှိပါ။',
          'leave.loadError': 'အချက်အလက်ကို ဖွင့်မရပါ။',
          'leave.loadListError': 'စာရင်းကို ဖွင့်မရပါ။',
          'leave.peopleSuffix': 'ဦး',
          'leave.saved': 'မှတ်တမ်းတင်ပြီး',
          'leave.document': 'ခွင့်တောင်းဆိုစာ',
          'leave.documentHint': 'ပို့လိုက်သည်နှင့် Supervisor ထံ ချက်ချင်းရောက်ပါမည်။',
          'leave.backDepartment': 'ဌာနရွေးချယ်မှုသို့ ပြန်သွားရန်',
          'leave.submit': 'အတည်ပြုရန်တင်ပြ',
          'leave.form.title': 'ခွင့်တောင်းဆိုမှု ပို့ရန်',
          'leave.form.from': 'စတင်ရက်',
          'leave.form.to': 'ပြီးဆုံးရက်',
          'leave.form.notePlaceholder': 'ထပ်ဆောင်းအသေးစိတ် (ရှိပါက)',
          'leave.form.splitHint': 'Bplus တင်သွင်းဖောင်နှင့် ကိုက်ညီရန် ရက်အပိုင်းအခြားကို တစ်ရက်လျှင် တစ်ခုစီ ခွဲပေးမည်။',
          'leave.cancel': 'ပယ်ဖျက်',
          'leave.save': 'အတည်ပြုရန် ပို့မည်',
          'leave.success': 'အောင်မြင်သည်',
          'leave.done': 'လုပ်ဆောင်မှု အောင်မြင်ပါသည်။',
          'leave.jumpToDate': 'သွားမည့်ရက်',
          'leave.shiftEstimated': 'နောက်ဆုံးမှတ်တမ်းရှိ ဆိုင်း',
          'leave.shiftEstimatedShort': 'နောက်ဆုံးဆိုင်း',
          'leave.ok': 'အိုကေ',
          'leave.view': 'Leave ပြင်ရန်',
          'leave.edit': 'ပြင်ဆင်',
          'leave.approval.list': 'ခွင့်အတည်ပြုစာရင်း',
          'leave.refresh': 'ပြန်ဖွင့်',
          'leave.selected': 'ရွေးထားသည်',
          'leave.bulkReject': 'ရွေးထားသည်များ ပယ်ချရန်',
          'leave.bulkApprove': 'ရွေးထားသည်များ အတည်ပြုရန်',
          'leave.review': 'တောင်းဆိုမှုစစ်ဆေးရန်',
          'leave.confirm': 'ရလဒ်အတည်ပြုရန်',
          'leave.confirmApprove': 'အတည်ပြုမှု အတည်ပြုရန်',
          'leave.confirmReject': 'ပယ်ချမှု အတည်ပြုရန်',
          'leave.confirmReverseApprove': 'ပယ်ချထားသော တောင်းဆိုမှုကို အတည်ပြုအဖြစ် ပြောင်းမလား?',
          'leave.confirmReverseReject': 'အတည်ပြုထားသော တောင်းဆိုမှုကို ပယ်ချအဖြစ် ပြောင်းမလား?',
          'leave.confirmApproveHint': 'အတည်ပြုပြီးပါက ခွင့်သည် ချက်ချင်းအောင်မြင်ပြီး Bplus စာရွက်စာတမ်းအတွက် အဆင်သင့်ဖြစ်မည်။',
          'leave.confirmRejectHint': 'အကြောင်းပြချက်လိုအပ်ပြီး ရွေးထားသောတောင်းဆိုမှုအားလုံးသို့ သုံးမည်။',
          'leave.confirmReverseApproveHint': 'ဤလုပ်ဆောင်ချက်သည် စာရွက်စာတမ်းအခြေအနေနှင့် Bplus Export ကို သက်ရောက်စေသည်။ ယခင်အကြောင်းပြချက်ကို စစ်ဆေးပြီးမှ ထပ်မံအတည်ပြုပါ။',
          'leave.confirmReverseRejectHint': 'ဤလုပ်ဆောင်ချက်သည် စာရွက်စာတမ်းအခြေအနေနှင့် Bplus Export ကို သက်ရောက်စေသည်။ တောင်းဆိုမှုကို ပယ်ချမည်ဖြစ်ပြီး နောက်ကြောင်းစစ်ဆေးရန် အကြောင်းပြချက် ထည့်ရမည်။',
          'leave.confirmButton': 'အတည်ပြု',
          'leave.notePlaceholder': 'မှတ်ချက် သို့မဟုတ် အကြောင်းပြချက်',
          'leave.employeeNote': 'Foreman မှ မှတ်ချက်',
          'leave.supervisorNote': 'Supervisor မှ မှတ်ချက်',
          'leave.reviewResult': 'စစ်ဆေးရလဒ်',
          'leave.rejectReasonRequired': 'ပယ်ချရသည့်အကြောင်းပြချက် ထည့်ပါ။',
          'leave.expired': 'အတည်ပြုကာလ ကျော်လွန်ပြီ',
          'leave.status.draft': 'ဆောင်ရွက်ဆဲ',
          'leave.status.submitted': 'စောင့်ဆိုင်းနေသည်',
          'leave.status.approved': 'အောင်မြင်သည်',
          'leave.status.rejected': 'အတည်မပြုပါ',
          'leave.status.waitingSupervisor': '(Supervisor အတည်ပြုမှုကို စောင့်နေသည်)',
          'ot.approvals.expired': 'အတည်ပြုကာလ ကျော်လွန်ပြီ',
          'ot.settings.title': 'စနစ်ဆက်တင်',
          'ot.settings.subtitle': 'စီမံခန့်ခွဲသူ၊ ဝင်ခွင့်ရှိသောရာထူးနှင့် ဌာနတစ်ခုချင်း၏ OT/ခွင့် တာဝန်ခံကို သတ်မှတ်ပါ။',
          'ot.settings.adminTitle': 'စနစ် စီမံခန့်ခွဲသူ',
          'ot.settings.adminHelp': 'စနစ်ဆက်တင်ကို စီမံပြီး OT/ခွင့် စာရွက်စာတမ်းများကို ဒေါင်းလုဒ်လုပ်နိုင်သည်။',
          'ot.settings.noOtAdmins': 'ထပ်မံသတ်မှတ်ထားသော စနစ်စီမံခန့်ခွဲသူ မရှိသေးပါ။',
          'ot.settings.searchAdmin': 'Admin အဖြစ်ထည့်ရန် ဝန်ထမ်းရှာပါ',
          'ot.settings.removeAdminConfirm': 'ဤဝန်ထမ်းကို စနစ်စီမံခန့်ခွဲသူများမှ ဖယ်ရှားမလား?',
          'ot.settings.positionsTitle': 'စနစ်ဝင်ခွင့်ရှိသော ရာထူးများ',
          'ot.settings.positionsHelp': 'မရွေးထားပါက သာမန်ဝန်ထမ်းများ ဝင်မရပါ။ Admin၊ Foreman နှင့် Supervisor များ ဝင်နိုင်ပါသည်။',
          'ot.settings.foremanTitle': 'Foreman သတ်မှတ်ရန်',
          'ot.settings.foremanHelp': 'ဌာနတစ်ခုလျှင် လူများစွာ သတ်မှတ်နိုင်ပြီး ဆိုင်းအလိုက် ခွဲနိုင်သည်။',
          'ot.settings.supervisorTitle': 'Supervisor သတ်မှတ်ရန်',
          'ot.settings.supervisorHelp': 'ကုမ္ပဏီနှင့် ဌာနတစ်ခုစီအတွက် လူတစ်ဦးရွေးပါ။',
          'ot.settings.notAssigned': 'မသတ်မှတ်ရသေးပါ',
          'ot.settings.chooseFromDepartment': 'ဤဌာနရှိ ဝန်ထမ်းများထဲမှ ရွေးပါ။',
          'ot.settings.currentAssignment': 'လက်ရှိရွေးချယ်ထားသူ',
          'ot.settings.currentAssignments': 'ဤဌာနအတွက် တာဝန်ရှိသူများ',
          'ot.settings.foremanMultiHint': 'တစ်ဦးထက်ပို၍ ထည့်နိုင်ပြီး တစ်ဦးချင်းစီ တာဝန်ယူသည့်ဆိုင်းကို ရွေးနိုင်သည် · ဆိုင်းသည် အညွှန်းသာဖြစ်ပြီး ခွင့်ပြုချက်ကို ကန့်သတ်ခြင်းမရှိပါ',
          'ot.settings.alreadyAssigned': 'ထည့်ပြီး',
          'ot.settings.shiftGroup': 'တာဝန်ယူသည့်ဆိုင်း',
          'ot.settings.shift.none': 'ဆိုင်းမသတ်မှတ်ရသေး',
          'ot.settings.shift.all': 'ဆိုင်းအားလုံး',
          'ot.settings.shift.morning': 'အလှည့် A',
          'ot.settings.shift.night': 'အလှည့် B',
          'ot.shiftFilter.label': 'ဆိုင်းရွေးရန်',
          'ot.shiftFilter.all': 'ဆိုင်းအားလုံး',
          'ot.shiftFilter.wholeGroup': 'အားလုံး ',
          'ot.shiftFilter.other': 'အခြားဆိုင်းများ',
          'ot.shiftFilter.none': 'ဆိုင်းမရှိ',
          'ot.shiftFilter.empty': 'ရွေးထားသောဆိုင်းတွင် ဝန်ထမ်းမတွေ့ပါ',
          'ot.search.label': 'ဝန်ထမ်း ရှာရန်',
          'ot.attendance.pickDate': 'ရက်စွဲ ရွေးရန်',
          'ot.search.placeholder': 'ကုဒ် သို့မဟုတ် အမည်ဖြင့် ရှာပါ',
          'ot.search.empty': 'ရှာဖွေမှုနှင့် ကိုက်ညီသော ဝန်ထမ်း မတွေ့ပါ',
          'ot.requests.bulkOpen': 'အားလုံးအတွက် OT တောင်းရန်',
          'ot.requests.bulkLabel': 'ဆိုင်းတစ်ခုလုံးအတွက် OT တောင်းရန်',
          'ot.requests.bulkNext': 'ရှေ့သို့',
          'ot.requests.bulkBack': 'နောက်သို့',
          'ot.requests.bulkSubmit': 'အတည်ပြုရန် ပို့မည်',
          'ot.requests.bulkSelectAll': 'အားလုံးရွေးရန်',
          'ot.requests.bulkHiddenPicked': 'ဤစစ်ထုတ်မှုအပြင်ဘက်မှ ရွေးထားသည်',
          'ot.requests.bulkHasRequest': 'OT တောင်းပြီးပါပြီ',
          'ot.requests.bulkPositionNotEligible': 'ဤရာထူးသည် OT တောင်းခံခွင့်မရှိပါ',
          'ot.requests.bulkInvalidRow': 'အချို့အတန်းများတွင် အချိန် သို့မဟုတ် အရေအတွက် မမှန်ကန်ပါ',
          'ot.requests.actors': 'ဆောင်ရွက်သူ',
          'ot.requests.actorRequested': 'OT တောင်းသူ',
          'ot.requests.actorApproved': 'အတည်ပြုသူ',
          'ot.route.column': 'အခြေအနေအားလုံး',
          'ot.route.kicker': 'OT STATUS',
          'ot.route.title': 'အခြေအနေအားလုံး',
          'ot.route.noRequest': 'OT တောင်းဆိုမှုမရှိသေး',
          'ot.route.noRequestDetail': 'ဤစာရင်းအတွက် ဆောင်ရွက်သူအချက်အလက် မရှိသေးပါ။',
          'ot.route.draft': 'တောင်းဆိုမှု ပို့ပြီး',
          'ot.route.submitted': 'တောင်းဆိုမှု ပို့ပြီး',
          'ot.route.notStarted': 'အဆင့်မရောက်သေး',
          'ot.route.waitingApproval': 'အတည်ပြုရန် စောင့်နေ',
          'ot.route.approved': 'အတည်ပြုပြီး',
          'ot.route.rejected': 'အတည်မပြု',
          'ot.route.scanPassed': 'စကင်အချိန်ပြည့်',
          'ot.route.scanFailed': 'စကင်အချိန်မပြည့်',
          'ot.route.waitingScan': 'စကင်အချိန် စစ်ရန်စောင့်နေ',
          'ot.route.notPassed': 'မအောင်မြင်',
          'ot.route.passed': 'အောင်မြင်',
          'ot.route.inProgress': 'ဆောင်ရွက်နေဆဲ',
          'ot.route.open': 'အခြေအနေအားလုံး ကြည့်ရန်',
          'ot.route.zoomPhoto': 'ဝန်ထမ်းဓာတ်ပုံ ချဲ့ကြည့်ရန်',
          'ot.route.step': 'အဆင့်',
          'ot.route.request': 'OT တောင်းရန်',
          'ot.route.approval': 'OT အတည်ပြုရန်',
          'ot.route.attendance': 'OT စကင်အချိန်စစ်ရန်',
          'ot.route.otRange': 'OT အချိန်',
          'ot.route.otType': 'OT အမျိုးအစား',
          'ot.route.shift': 'ဆိုင်း',
          'ot.route.finalResult': 'ရလဒ်',
          'ot.route.requestedBy': 'OT တောင်းသူ',
          'ot.route.approvedBy': 'အတည်ပြုသူ',
          'ot.route.actionTime': 'ဆောင်ရွက်ချိန်',
          'ot.route.requestNote': 'တောင်းဆိုမှုမှတ်ချက်',
          'ot.route.decisionNote': 'အတည်ပြုမှတ်ချက်',
          'ot.route.scanTime': 'စကင်အချိန်',
          'ot.route.clockIn': 'ဝင်',
          'ot.route.clockOut': 'ထွက်',
          'ot.route.requiredRange': 'လိုအပ်သောအချိန်',
          'ot.route.checkedAt': 'နောက်ဆုံးစစ်ဆေးချိန်',
          'ot.route.condition': 'သတ်မှတ်ချက်',
          'ot.route.conditionDetail': 'OT အတည်ပြုမှုနှင့် OT စကင်အချိန် နှစ်ခုလုံး အောင်မြင်ရမည်။',
          'ot.route.exportResult': 'V74 အချက်အလက်',
          'ot.route.exported': 'V74 ပို့ပြီး',
          'ot.route.readyV74': 'Excel V74 ထည့်သွင်းရန် အသင့်',
          'ot.route.failedApproval': 'အတည်ပြုမှု မအောင်မြင်ပါ',
          'ot.route.failedAttendance': 'စကင်အချိန်သည် OT အချိန်ကို မလွှမ်းခြုံပါ',
          'ot.route.waitBoth': 'အတည်ပြုမှုနှင့် စကင်စစ်ဆေးမှုကို စောင့်နေသည်',
          'ot.requests.bulkAutoTime': 'ဆိုင်းအလိုက် အချိန်အလိုအလျောက်တွက်ရန်',
          'ot.requests.myShift': 'သင်တာဝန်ယူသည့်ဆိုင်း',
          'ot.settings.employeeList': 'ဌာနရှိ ဝန်ထမ်းများ',
          'ot.settings.companySearchResults': 'ကုမ္ပဏီအားလုံးမှ ရှာဖွေမှုရလဒ်များ',
          'ot.settings.searchAllCompany': 'ကုမ္ပဏီအားလုံးရှိ ဝန်ထမ်းများကို ရှာရန်',
          'ot.settings.noDepartments': 'အလုပ်လုပ်နေသော ဝန်ထမ်းရှိသည့် ဌာနမတွေ့ပါ။',
          'ot.settings.noInsightAccount': 'Insight အကောင့်မရှိပါ',
          'ot.settings.removeAssignmentConfirm': 'ဤဌာနမှ တာဝန်ခံကို ဖယ်ရှားမလား?',
          'ot.common.departments': 'ဌာန',
          'ot.common.people': 'ဦး',
          'ot.common.remove': 'ဖယ်ရှားရန်',
          'ot.common.change': 'ပြောင်းရန်',
          'ot.common.choose': 'ရွေးရန်',
          'ot.common.searchEmployee': 'ဝန်ထမ်းရှာရန်',
          'ot.common.searchPosition': 'ရာထူးရှာရန်',
          'ot.common.selectVisible': 'ပြထားသည်များ ရွေးရန်',
          'ot.common.clear': 'ရှင်းရန်',
          'ot.common.save': 'သိမ်းရန်',
          'ot.common.saved': 'သိမ်းပြီး',
          'ot.common.add': 'ထည့်ရန်',
          'ot.common.loading': 'ဖွင့်နေသည်...',
          'ot.common.noResults': 'ဒေတာမတွေ့ပါ',
          'ot.common.cancel': 'မလုပ်တော့ပါ',
          'ot.common.close': 'ပိတ်ရန်',
          'ot.common.error': 'လုပ်ဆောင်၍မရပါ'
        }
      };

      Object.keys(area5sCopy).forEach(function (lang) {
        copy[lang] = Object.assign(copy[lang] || {}, area5sCopy[lang]);
      });
      Object.keys(otApprovalCopy).forEach(function (lang) {
        copy[lang] = Object.assign(copy[lang] || {}, otApprovalCopy[lang]);
      });

      var looseTextOriginals = new WeakMap();

      function currentPortalLang() {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return copy[lang] ? lang : 'th';
      }

      function portalText(key, fallback, replacements) {
        var lang = currentPortalLang();
        var value = (copy[lang] && copy[lang][key]) || (copy.th && copy.th[key]) || fallback || key;
        if (replacements) {
          Object.keys(replacements).forEach(function (name) {
            value = String(value).replace(new RegExp(':' + name, 'g'), replacements[name]);
          });
        }
        return value;
      }

      function localizedValue(item, field, fallback) {
        var lang = currentPortalLang();
        var suffix = lang === 'th' ? 'th' : (lang === 'my' ? 'my' : 'en');
        var suffixTitle = suffix.charAt(0).toUpperCase() + suffix.slice(1);
        var value = '';
        if (item) {
          value = item[field + '_' + suffix] || item[field + suffixTitle] ||
            item[field + '_en'] || item[field + 'En'] ||
            item[field + '_th'] || item[field + 'Th'] ||
            item[field] || '';
        }
        return value || fallback || '';
      }

      function localizeDataFields(root) {
        (root || document).querySelectorAll('[data-a5s-name-th], [data-a5s-position-th], [data-a5s-department-th]').forEach(function (node) {
          if (node.hasAttribute('data-a5s-name-th')) {
            node.textContent = localizedValue(node.dataset, 'a5sName', node.textContent);
          } else if (node.hasAttribute('data-a5s-position-th')) {
            node.textContent = localizedValue(node.dataset, 'a5sPosition', node.textContent);
          } else if (node.hasAttribute('data-a5s-department-th')) {
            node.textContent = localizedValue(node.dataset, 'a5sDepartment', node.textContent);
          }
        });

        // generic — ใช้ได้ทุกโมดูล สำหรับข้อมูลจาก DB ที่มีหลายภาษา (ชื่อคน ตำแหน่ง แผนก ฯลฯ)
        // ใช้:  <span data-loc-th="ไทย" data-loc-en="English" data-loc-my="မြန်မာ">ไทย</span>
        // ไม่มี my → ตกไป en → ตกไป th อัตโนมัติใน localizedValue
        // Elements that localize attributes can contain an image/icon. Do not replace their children.
        (root || document).querySelectorAll('[data-loc-th]:not([data-loc-attr])').forEach(function (node) {
          var value = localizedValue(node.dataset, 'loc', node.textContent);
          if (node.textContent !== value) node.textContent = value;
          if (node.hasAttribute('title')) node.setAttribute('title', value);
        });

        // ค่าที่อยู่ใน attribute อื่น (เช่น aria-label / data-name ของ modal รูปโปรไฟล์)
        (root || document).querySelectorAll('[data-loc-attr]').forEach(function (node) {
          var attrs = (node.getAttribute('data-loc-attr') || '').split(',').map(function (attr) { return attr.trim(); }).filter(Boolean);
          attrs.forEach(function (attr) {
            node.setAttribute(attr, localizedValue(node.dataset, 'loc', node.getAttribute(attr) || ''));
          });
        });
      }

      function translateLooseText(lang) {
        var roots = document.querySelectorAll('.a5n-wrap, .a5m-wrap, .a5e-wrap, .a5v-wrap, .a5w-wrap, .a5r-wrap, .a5s-wrap, [data-a5s-i18n-root]');
        if (!roots.length) return;
        var thaiLoose = (copy.th && copy.th['a5s.__loose']) || {};
        var targetLoose = (copy[lang] && copy[lang]['a5s.__loose']) || thaiLoose;
        roots.forEach(function (root) {
          var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
              var parent = node.parentElement;
              if (!parent || ['SCRIPT', 'STYLE', 'TEXTAREA', 'OPTION'].includes(parent.tagName)) return NodeFilter.FILTER_REJECT;
              var trimmed = node.nodeValue.trim();
              return looseTextOriginals.has(node) || Object.prototype.hasOwnProperty.call(thaiLoose, trimmed) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
          });
          var node;
          while ((node = walker.nextNode())) {
            var raw = node.nodeValue;
            var leading = raw.match(/^\s*/)[0];
            var trailing = raw.match(/\s*$/)[0];
            var original = looseTextOriginals.get(node) || raw.trim();
            looseTextOriginals.set(node, original);
            node.nodeValue = leading + (targetLoose[original] || original) + trailing;
          }
        });
      }

      window.__portalCopy = copy; // ให้หน้าอื่น (เช่น modal เปลี่ยนรหัส) ใช้ข้อความ i18n ได้
      window.__portalLang = {
        get: currentPortalLang,
        text: portalText,
        field: localizedValue,
        name: function (item, fallback) { return localizedValue(item || {}, 'name', fallback || (item && item.code) || '-'); },
        position: function (item, fallback) { return localizedValue(item || {}, 'position', fallback || '-'); },
        department: function (item, fallback) { return localizedValue(item || {}, 'department', fallback || '-'); },
        apply: localizeDataFields
      };

      initLanguage();
      initLoader();
      initTheme();
      initPortalAccessModal();
      initImageViewer();
      initDragScroll();

      /**
       * ลากตารางด้วยเมาส์เพื่อเลื่อนดูคอลัมน์/แถวที่ล้นจอ
       *
       * ใช้กับกล่องที่ติด `data-drag-scroll` — ผูก listener ที่ document แบบ delegation
       * เพราะหลายหน้าวาดตารางใหม่ด้วย JS ทุกครั้งที่เปลี่ยนแท็บ/แผนก ถ้าผูกที่กล่องจะหลุด
       *
       * กติกาที่ต้องรักษาไว้: **กดปุ่มในตารางต้องยังทำงาน**
       *   - เริ่มลากบนปุ่ม/ลิงก์/ช่องกรอก = ไม่ถือเป็นการลาก ปล่อยให้กดได้ตามปกติ
       *   - ขยับไม่ถึง 4px = ถือว่าเป็นการคลิก ไม่ใช่ลาก
       *   - ถ้าลากจริงค่อยกิน click ทิ้งรอบเดียว กันเผลอเปิดของที่อยู่ใต้เมาส์
       * ทำเฉพาะเมาส์ ส่วนนิ้ว/ปากกาปล่อยให้ scroll ตามปกติของเบราว์เซอร์
       */
      function initDragScroll() {
        var IGNORE = 'button, a, input, select, textarea, label, summary, [role="button"], [contenteditable="true"]';
        var THRESHOLD = 4;
        var box = null, startX = 0, startY = 0, startLeft = 0, startTop = 0, moved = false;

        document.addEventListener('pointerdown', function (event) {
          /* เคลียร์ก่อน guard ทุกครั้ง — ถ้าลากจบนอกตารางจน click ไม่ยิง ธงจะค้าง
             แล้วไปกินคลิกปุ่มครั้งถัดไปแทน */
          moved = false;

          if (event.button !== 0 || event.pointerType !== 'mouse') return;

          var target = event.target.closest('[data-drag-scroll]');
          if (!target || event.target.closest(IGNORE)) return;

          // ไม่มีอะไรล้นให้เลื่อน ก็ไม่ต้องเข้าโหมดลาก
          var canScroll = target.scrollWidth > target.clientWidth || target.scrollHeight > target.clientHeight;
          if (!canScroll) return;

          box = target;
          startX = event.clientX;
          startY = event.clientY;
          startLeft = target.scrollLeft;
          startTop = target.scrollTop;
        });

        document.addEventListener('pointermove', function (event) {
          if (!box) return;

          var dx = event.clientX - startX;
          var dy = event.clientY - startY;

          if (!moved && Math.abs(dx) < THRESHOLD && Math.abs(dy) < THRESHOLD) return;

          if (!moved) {
            moved = true;
            box.classList.add('is-drag-scrolling');
          }

          box.scrollLeft = startLeft - dx;
          box.scrollTop = startTop - dy;
          // กันเบราว์เซอร์ไปเลือกข้อความในตารางระหว่างลาก
          event.preventDefault();
        });

        function release() {
          if (!box) return;

          box.classList.remove('is-drag-scrolling');
          box = null;
        }

        document.addEventListener('pointerup', release);
        document.addEventListener('pointercancel', release);
        window.addEventListener('blur', release);

        // ลากจบแล้ว click จะยิงตามมาเสมอ ต้องกินทิ้งเฉพาะครั้งนั้น
        document.addEventListener('click', function (event) {
          if (!moved) return;

          moved = false;
          event.preventDefault();
          event.stopPropagation();
        }, true);
      }

      /* คงฟังก์ชันไว้เป็น no-op เพราะมีที่เรียกอยู่ตอน boot — ปุ่มถูกถอดออกแล้ว */
      /* ระบบใช้ธีมสว่างอย่างเดียวแล้ว ปุ่มสลับถูกถอดออก เหลือไว้เป็น no-op
         เพราะยังมีจุดเรียกตอน boot — ถ้าลบต้องไล่แก้ลำดับการ init ด้วย */
      function initTheme() {}

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

      function setTranslatedText(node, value) {
        if (!node.children.length) {
          node.textContent = value;
          return;
        }

        var textNode = Array.prototype.find.call(node.childNodes, function (child) {
          return child.nodeType === Node.TEXT_NODE && child.nodeValue.trim() !== '';
        });

        if (textNode) {
          var leading = (textNode.nodeValue.match(/^\s*/) || [''])[0];
          var trailing = (textNode.nodeValue.match(/\s*$/) || [''])[0];
          textNode.nodeValue = leading + value + trailing;
          return;
        }

        if (node.querySelector(':scope > [data-i18n]')) return;

        var label = node.querySelector(':scope > [data-i18n-generated-label]');
        if (!label) {
          label = document.createElement('span');
          label.setAttribute('data-i18n-generated-label', '');
          node.appendChild(label);
        }
        label.textContent = value;
      }

      function setLanguage(lang) {
        if (!copy[lang]) lang = 'th';
        var dict = copy[lang];
        document.documentElement.lang = lang;
        document.documentElement.setAttribute('data-lang', lang);

        document.querySelectorAll('[data-i18n]').forEach(function (node) {
          var key = node.getAttribute('data-i18n');
          if (dict[key]) setTranslatedText(node, dict[key]);
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(function (node) {
          var key = node.getAttribute('data-i18n-placeholder');
          if (dict[key]) node.setAttribute('placeholder', dict[key]);
        });

        document.querySelectorAll('[data-i18n-aria]').forEach(function (node) {
          var key = node.getAttribute('data-i18n-aria');
          if (dict[key]) node.setAttribute('aria-label', dict[key]);
        });

        document.querySelectorAll('[data-i18n-title]').forEach(function (node) {
          var key = node.getAttribute('data-i18n-title');
          if (dict[key]) node.setAttribute('title', dict[key]);
        });

        document.querySelectorAll('[data-i18n-tooltip]').forEach(function (node) {
          var key = node.getAttribute('data-i18n-tooltip');
          if (dict[key]) node.setAttribute('data-tooltip', dict[key]);
        });

        document.querySelectorAll('[data-lang-option]').forEach(function (node) {
          node.setAttribute('aria-current', node.getAttribute('data-lang-option') === lang ? 'true' : 'false');
        });

        document.querySelectorAll('[data-language-current-flag]').forEach(function (node) {
          var src = node.getAttribute('data-flag-src-' + lang);
          if (src) node.setAttribute('src', src);
        });

        localizeDataFields(document);
        translateLooseText(lang);

        var pageTitleNode = document.querySelector('.topbar-title .tt-title[data-i18n], .topbar-title .crumb-active [data-i18n], .topbar-title > [data-i18n]');
        if (pageTitleNode && pageTitleNode.textContent.trim()) {
          document.title = pageTitleNode.textContent.trim() + ' · SUPAVUT INSIGHT';
        }

        document.dispatchEvent(new CustomEvent('insight:languagechange', { detail: { lang: lang } }));

        try { localStorage.setItem(languageStoreKey, lang); } catch (e) {}
      }

      function initLoader() {
        var loader = document.getElementById('portalLoader');
        if (!loader) return;
        var loaderText = document.getElementById('portalLoaderText');

        function dictOf() {
          var lang = document.documentElement.getAttribute('data-lang') || 'th';
          return copy[lang] || copy.th;
        }

        function activate(text, scene) {
          if (loaderText && text) loaderText.textContent = text;
          loader.classList.toggle('is-scene', !!scene); // scene = ฉากแบบ Sign in (เลื่อนลง)
          if (scene) {
            // บังคับ reflow ให้เริ่มจากตำแหน่งบนสุด (translateY -100%) ก่อน แล้วค่อยเลื่อนลง
            void loader.offsetWidth;
          }
          loader.classList.add('is-active');
          loader.setAttribute('aria-hidden', 'false');
        }

        // ทุกการนำทางในแอป: ขึ้น skeleton spinner ก่อนเปลี่ยนหน้า
        //  - การ์ดระบบย่อย (มี data-enter-name) -> "กำลังเข้าสู่ <ชื่อระบบ>" หน่วง 1.2 วิ
        //  - แท็บ/ฟังก์ชันทั่วไป (ข้อมูล/ระบบทั้งหมด/กลับ/ภาพรวม) -> "กำลังโหลด" หน่วง 1 วิ
        document.querySelectorAll('a.nav-go').forEach(function (link) {
          link.addEventListener('click', function (event) {
            if (link.classList.contains('is-active')) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;
            event.preventDefault();
            var dict = dictOf();
            var name = link.getAttribute('data-enter-name');
            var href = link.getAttribute('href') || link.href;
            if (!href || href === '#') return;
            if (name) {
              activate((dict['loader.enter'] || '') + name, false);
              window.setTimeout(function () { window.location.href = href; }, 100);
            } else {
              activate(dict['loader.loading'] || '', false);
              window.setTimeout(function () { window.location.href = href; }, 100);
            }
          });
        });

        // ออกจากระบบ -> ฉากดำเลื่อนลงพร้อมคำตามภาษาที่เลือก
        document.querySelectorAll('[data-logout-form]').forEach(function (logoutForm) {
          var submitting = false;
          logoutForm.addEventListener('submit', function (event) {
            if (submitting) return;
            event.preventDefault();
            activate(dictOf()['loader.logout'], true); // ฉากแบบ Sign in
            submitting = true;
            window.setTimeout(function () {
              HTMLFormElement.prototype.submit.call(logoutForm);
            }, 680);
          });
        });

        // ซ่อน loader เมื่อกลับมาด้วยปุ่ม back
        window.addEventListener('pageshow', function () {
          loader.classList.remove('is-active');
          loader.setAttribute('aria-hidden', 'true');
        });
      }

      function initPortalAccessModal() {
        var modal = document.querySelector('[data-portal-access-modal]');
        if (!modal) return;

        var closeButton = modal.querySelector('[data-portal-access-close]');
        var removeDelay = 240;

        function closeModal() {
          if (!modal || modal.classList.contains('is-hiding')) return;
          modal.classList.add('is-hiding');
          window.setTimeout(function () {
            if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
          }, removeDelay);
        }

      if (closeButton) {
        var playConfirmInteraction = function () {
          closeButton.classList.remove('is-interacting');
          void closeButton.offsetWidth;
          closeButton.classList.add('is-interacting');
        };

        window.setTimeout(function () {
          try {
            closeButton.focus({ preventScroll: true });
          } catch (e) {
            closeButton.focus();
          }
        }, 60);

        closeButton.addEventListener('pointerenter', playConfirmInteraction);
        closeButton.addEventListener('animationend', function (event) {
          if (event.animationName === 'portal-confirm-hover-pulse') {
            closeButton.classList.remove('is-interacting');
          }
        });
        closeButton.addEventListener('click', closeModal);
      }

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') closeModal();
        });
      }

      function initImageViewer() {
        var viewer = document.getElementById('imageViewer');
        var image = document.getElementById('imageViewerImg');
        if (!viewer || !image) return;

        var lastTrigger = null;
        var closeDelay = 220;
        var reducedMotion = false;
        try {
          reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {}

        function animateFrom(trigger) {
          if (reducedMotion || !image.animate) return;

          var start = trigger.getBoundingClientRect();
          window.requestAnimationFrame(function () {
            var end = image.getBoundingClientRect();
            if (!start.width || !end.width) return;

            var startCenterX = start.left + (start.width / 2);
            var startCenterY = start.top + (start.height / 2);
            var endCenterX = end.left + (end.width / 2);
            var endCenterY = end.top + (end.height / 2);
            var scale = Math.max(.08, Math.min(.9, start.width / end.width));

            image.animate([
              {
                opacity: .72,
                transform: 'translate(' + (startCenterX - endCenterX) + 'px, ' + (startCenterY - endCenterY) + 'px) scale(' + scale + ')',
                borderRadius: '50%'
              },
              {
                opacity: 1,
                transform: 'translate(0, 0) scale(1)',
                borderRadius: '0'
              }
            ], {
              duration: 280,
              easing: 'cubic-bezier(.16, 1, .3, 1)'
            });
          });
        }

        function openViewer(trigger) {
          var src = trigger.getAttribute('data-image-src');
          if (!src) return;

          lastTrigger = trigger;
          image.src = src;
          image.alt = trigger.getAttribute('data-image-alt') || '';
          resetZoomState(true);
          viewer.classList.add('is-open');
          viewer.setAttribute('aria-hidden', 'false');
          document.body.classList.add('image-viewer-lock');
          animateFrom(trigger);

          var closeButton = viewer.querySelector('.image-viewer-close');
          if (closeButton) {
            try {
              closeButton.focus({ preventScroll: true });
            } catch (e) {
              closeButton.focus();
            }
          }
        }

        function closeViewer() {
          if (!viewer.classList.contains('is-open')) return;

          resetZoomState(true);
          viewer.classList.remove('is-open');
          viewer.setAttribute('aria-hidden', 'true');
          document.body.classList.remove('image-viewer-lock');

          window.setTimeout(function () {
            if (!viewer.classList.contains('is-open')) {
              image.removeAttribute('src');
            }
          }, closeDelay);

          if (lastTrigger) {
            try {
              lastTrigger.focus({ preventScroll: true });
            } catch (e) {
              lastTrigger.focus();
            }
          }
        }

        // event delegation: รองรับทั้ง trigger แบบ static และที่โหลดมาทีหลังด้วย AJAX
        document.addEventListener('click', function (event) {
          var trigger = event.target.closest('[data-image-preview]');
          if (trigger) openViewer(trigger);
        });

        viewer.querySelectorAll('[data-image-viewer-close]').forEach(function (close) {
          close.addEventListener('click', closeViewer);
        });

        // ---- ซูม / แพน / พินช์ เพื่ออ่านรูปละเอียด ----
        var zoom = 1, panX = 0, panY = 0;
        var minZoom = 1, maxZoom = 6;
        var zoomOutBtn = viewer.querySelector('[data-image-zoom-out]');
        var zoomInBtn = viewer.querySelector('[data-image-zoom-in]');
        var zoomResetBtn = viewer.querySelector('[data-image-zoom-reset]');

        function clampPan() {
          var vw = window.innerWidth, vh = window.innerHeight;
          var maxX = Math.max(0, (image.offsetWidth * zoom - vw) / 2 + 24);
          var maxY = Math.max(0, (image.offsetHeight * zoom - vh) / 2 + 24);
          panX = Math.max(-maxX, Math.min(maxX, panX));
          panY = Math.max(-maxY, Math.min(maxY, panY));
        }
        function applyZoom(animate) {
          clampPan();
          image.style.transition = animate ? 'transform .16s var(--ease-out)' : 'none';
          image.style.transform = (zoom === 1 && !panX && !panY)
            ? '' : 'translate(' + panX.toFixed(1) + 'px,' + panY.toFixed(1) + 'px) scale(' + zoom + ')';
          image.style.cursor = zoom > 1 ? 'grab' : 'zoom-in';
          if (zoomResetBtn) zoomResetBtn.textContent = Math.round(zoom * 100) + '%';
          if (zoomOutBtn) zoomOutBtn.disabled = zoom <= minZoom;
          if (zoomInBtn) zoomInBtn.disabled = zoom >= maxZoom;
          viewer.classList.toggle('is-zoomable', zoom > 1);
        }
        function resetZoomState(clearInline) {
          zoom = 1; panX = 0; panY = 0;
          if (clearInline) { image.style.transform = ''; image.style.transition = ''; image.style.cursor = 'zoom-in'; }
          if (zoomResetBtn) zoomResetBtn.textContent = '100%';
          if (zoomOutBtn) zoomOutBtn.disabled = true;
          if (zoomInBtn) zoomInBtn.disabled = false;
          viewer.classList.remove('is-zoomable');
        }
        function zoomTo(next, sx, sy, animate) {
          next = Math.max(minZoom, Math.min(maxZoom, Math.round(next * 100) / 100));
          var prev = zoom;
          if (next === prev) return;
          var r = next / prev;
          if (sx == null) { sx = window.innerWidth / 2; sy = window.innerHeight / 2; }
          panX = (sx - window.innerWidth / 2) * (1 - r) + panX * r;
          panY = (sy - window.innerHeight / 2) * (1 - r) + panY * r;
          zoom = next;
          if (zoom === 1) { panX = 0; panY = 0; }
          applyZoom(animate);
        }

        if (zoomInBtn) zoomInBtn.addEventListener('click', function () { zoomTo(zoom + 0.5, null, null, true); });
        if (zoomOutBtn) zoomOutBtn.addEventListener('click', function () { zoomTo(zoom - 0.5, null, null, true); });
        if (zoomResetBtn) zoomResetBtn.addEventListener('click', function () { resetZoomState(false); applyZoom(true); });

        // หมุนเมาส์ = ซูมที่ตำแหน่งเคอร์เซอร์
        viewer.addEventListener('wheel', function (event) {
          if (!viewer.classList.contains('is-open')) return;
          event.preventDefault();
          zoomTo(zoom + (event.deltaY < 0 ? 0.3 : -0.3), event.clientX, event.clientY, false);
        }, { passive: false });

        // ดับเบิลคลิก = สลับซูม
        image.addEventListener('dblclick', function (event) {
          if (zoom > 1) { resetZoomState(false); applyZoom(true); }
          else zoomTo(2.5, event.clientX, event.clientY, true);
        });

        // ลากเลื่อน (เมาส์/นิ้ว) + พินช์ 2 นิ้ว
        var pointers = new Map();
        var dragStart = null, pinchStart = null;
        image.addEventListener('dragstart', function (event) { event.preventDefault(); });  // กัน native image-drag ที่ทำให้ลากแพนไม่ได้
        image.addEventListener('pointerdown', function (event) {
          pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
          try { image.setPointerCapture(event.pointerId); } catch (e) {}
          if (pointers.size === 2) {
            var p = Array.from(pointers.values());
            pinchStart = { dist: Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y), zoom: zoom };
            dragStart = null;
          } else if (pointers.size === 1 && zoom > 1) {
            event.preventDefault();
            dragStart = { x: event.clientX, y: event.clientY, panX: panX, panY: panY };
            image.style.cursor = 'grabbing';
          }
        });
        image.addEventListener('pointermove', function (event) {
          if (!pointers.has(event.pointerId)) return;
          pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
          if (pinchStart && pointers.size === 2) {
            var p = Array.from(pointers.values());
            var dist = Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y);
            if (pinchStart.dist > 0) zoomTo(pinchStart.zoom * (dist / pinchStart.dist), (p[0].x + p[1].x) / 2, (p[0].y + p[1].y) / 2, false);
          } else if (dragStart && zoom > 1) {
            panX = dragStart.panX + (event.clientX - dragStart.x);
            panY = dragStart.panY + (event.clientY - dragStart.y);
            applyZoom(false);
          }
        });
        function endPointer(event) {
          pointers.delete(event.pointerId);
          try { image.releasePointerCapture(event.pointerId); } catch (e) {}
          if (pointers.size < 2) pinchStart = null;
          if (pointers.size === 0) { dragStart = null; image.style.cursor = zoom > 1 ? 'grab' : 'zoom-in'; }
        }
        image.addEventListener('pointerup', endPointer);
        image.addEventListener('pointercancel', endPointer);
        window.addEventListener('resize', function () { if (viewer.classList.contains('is-open') && zoom > 1) applyZoom(false); });

        document.addEventListener('keydown', function (event) {
          if (!viewer.classList.contains('is-open')) return;
          if (event.key === 'Escape') {
            event.preventDefault();
            event.stopImmediatePropagation();
            closeViewer();
          }
          else if (event.key === '+' || event.key === '=') { event.preventDefault(); zoomTo(zoom + 0.5, null, null, true); }
          else if (event.key === '-' || event.key === '_') { event.preventDefault(); zoomTo(zoom - 0.5, null, null, true); }
          else if (event.key === '0') { event.preventDefault(); resetZoomState(false); applyZoom(true); }
        });
      }
    })();

    /* ── กระดิ่งแจ้งเตือนกลาง (รวมทุกระบบย่อย) — Manager 2026-08-27 ──────────
       ชั้นที่ 1: เลือกแอป (แต่ละแอปมีตัวเลขของตัวเอง)
       ชั้นที่ 2: แท็บหมวดของแอปนั้น เช่น Time & Leave = ทั้งหมด / OT / การลา
       ตัวเลขบนกระดิ่ง = รวมทุกแอป (มาจาก View Composer ตอน render อยู่แล้ว)
       เพิ่มแอปใหม่ทำที่ PortalNotificationService ฝั่ง PHP ไม่ต้องแก้ไฟล์นี้ */
    (function portalNotifications() {
      'use strict';

      var trigger = document.getElementById('notiTrigger');
      var panel = document.getElementById('notiPanel');
      if (!trigger || !panel) return;

      var badge = trigger.querySelector('[data-noti-badge]');
      var appsScreen = panel.querySelector('[data-noti-apps-screen]');
      var appScreen = panel.querySelector('[data-noti-app-screen]');
      var appsList = panel.querySelector('[data-noti-apps]');
      var appName = panel.querySelector('[data-noti-app-name]');
      var tabsBox = panel.querySelector('[data-noti-tabs]');
      var list = panel.querySelector('[data-noti-list]');
      var backButton = panel.querySelector('[data-noti-back]');
      var readAllButton = panel.querySelector('[data-noti-readall]');
      var csrfMeta = document.querySelector('meta[name="csrf-token"]');
      var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
      var leaveIcon = @json(asset('assets/systems/leave-notification.png').'?v=1');

      var endpoints = {
        apps: @json(route('portal.notifications.apps')),
        items: @json(route('portal.notifications.items')),
        read: @json(route('portal.notifications.read')),
        readAll: @json(route('portal.notifications.read-all')),
      };

      var apps = [];
      var appsLoaded = false;
      var activeApp = null;      // { key, label, icon, tabs }
      var activeCategory = 'all';

      function text(key, fallback) {
        return (window.__portalLang && window.__portalLang.text)
          ? window.__portalLang.text(key, fallback)
          : fallback;
      }

      function setBadge(count) {
        if (!badge) return;
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = count < 1;
      }

      function renderEmpty(key, fallback, target) {
        var box = target || list;
        if (!box) return;
        box.innerHTML = '';
        var p = document.createElement('p');
        p.className = 'noti-empty';
        p.setAttribute('data-i18n', key);
        p.textContent = text(key, fallback);
        box.appendChild(p);
      }

      function post(url, payload) {
        return fetch(url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
          body: JSON.stringify(payload || {}),
        });
      }

      /* ---------- ชั้นที่ 1: รายการแอป ---------- */

      function renderApps() {
        if (!appsList) return;
        appsList.innerHTML = '';

        if (!apps.length) {
          renderEmpty('noti.empty', 'ยังไม่มีการแจ้งเตือน', appsList);
          return;
        }

        apps.forEach(function (app) {
          var row = document.createElement('button');
          row.type = 'button';
          row.className = 'noti-app-row';

          var icon = document.createElement('img');
          icon.src = app.icon;
          icon.alt = '';
          icon.loading = 'lazy';

          var name = document.createElement('span');
          name.className = 'noti-app-name';
          name.textContent = app.label_key ? text(app.label_key, app.label) : app.label;

          var count = document.createElement('span');
          count.className = 'noti-app-count';
          count.textContent = app.unread > 99 ? '99+' : String(app.unread);
          count.hidden = app.unread < 1;

          row.appendChild(icon);
          row.appendChild(name);
          row.appendChild(count);
          row.addEventListener('click', function () { openApp(app); });
          appsList.appendChild(row);
        });
      }

      async function loadApps() {
        try {
          var response = await fetch(endpoints.apps, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          });
          var payload = await response.json();
          apps = Array.isArray(payload.apps) ? payload.apps : [];
          appsLoaded = true;
          setBadge(payload.total_unread || 0);
          renderApps();
        } catch (error) {
          renderEmpty('noti.error', 'โหลดการแจ้งเตือนไม่สำเร็จ', appsList);
        }
      }

      /* ---------- ชั้นที่ 2: แจ้งเตือนของแอปที่เลือก ---------- */

      function showScreen(which) {
        if (appsScreen) appsScreen.hidden = which !== 'apps';
        if (appScreen) appScreen.hidden = which !== 'app';
      }

      function renderTabs() {
        if (!tabsBox) return;
        tabsBox.innerHTML = '';
        var tabs = (activeApp && activeApp.tabs) || [];
        // แอปที่มีหมวดเดียวไม่ต้องโชว์แถบแท็บ
        tabsBox.hidden = tabs.length < 2;
        if (tabs.length < 2) return;

        tabsBox.style.gridTemplateColumns = 'repeat(' + tabs.length + ',1fr)';
        tabs.forEach(function (tab) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'noti-tab' + (tab.key === activeCategory ? ' is-active' : '');
          button.setAttribute('role', 'tab');
          button.setAttribute('aria-selected', String(tab.key === activeCategory));
          button.textContent = tab.label_key ? text(tab.label_key, tab.label) : tab.label;
          button.addEventListener('click', function () {
            activeCategory = tab.key;
            renderTabs();
            loadItems();
          });
          tabsBox.appendChild(button);
        });
      }

      function renderItems(items) {
        if (!list) return;
        list.innerHTML = '';

        if (!items.length) {
          renderEmpty('noti.empty', 'ยังไม่มีการแจ้งเตือน');
          return;
        }

        items.forEach(function (item) {
          var row = document.createElement('button');
          row.type = 'button';
          row.className = 'noti-item' + (item.is_read ? '' : ' is-unread');

          var icon = document.createElement('span');
          icon.className = 'noti-app-icon';
          var img = document.createElement('img');
          img.src = item.category === 'leave' ? leaveIcon : (activeApp ? activeApp.icon : '');
          img.alt = '';
          img.loading = 'lazy';
          icon.appendChild(img);

          var copy = document.createElement('span');
          copy.className = 'noti-copy';
          var title = document.createElement('strong');
          title.textContent = item.title || '';
          copy.appendChild(title);
          if (item.body) {
            var detail = document.createElement('small');
            detail.textContent = item.body;
            copy.appendChild(detail);
          }
          if (item.created_label) {
            var when = document.createElement('time');
            when.textContent = item.created_label;
            copy.appendChild(when);
          }
          row.appendChild(icon);
          row.appendChild(copy);
          row.addEventListener('click', function () { openItem(item); });
          list.appendChild(row);
        });
      }

      async function loadItems() {
        if (!activeApp) return;
        renderEmpty('noti.loading', 'กำลังโหลด...');
        try {
          var url = endpoints.items + '?app=' + encodeURIComponent(activeApp.key)
            + '&category=' + encodeURIComponent(activeCategory);
          var response = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          });
          var payload = await response.json();
          renderItems(Array.isArray(payload.items) ? payload.items : []);
          setBadge(payload.total_unread || 0);
          syncAppUnread(activeApp.key, payload.unread || 0);
        } catch (error) {
          renderEmpty('noti.error', 'โหลดการแจ้งเตือนไม่สำเร็จ');
        }
      }

      function syncAppUnread(key, unread) {
        apps.forEach(function (app) { if (app.key === key) app.unread = unread; });
      }

      function openApp(app) {
        activeApp = app;
        activeCategory = 'all';
        if (appName) appName.textContent = app.label_key ? text(app.label_key, app.label) : app.label;
        renderTabs();
        showScreen('app');
        loadItems();
      }

      function openItem(item) {
        // ทำเครื่องหมายอ่านแล้วก่อนค่อยพาไป ไม่ต้องรอผลลัพธ์
        if (!item.is_read && activeApp) {
          post(endpoints.read, { app: activeApp.key, id: item.id }).catch(function () {});
        }
        if (item.link) window.location.assign(item.link);
      }

      if (backButton) {
        backButton.addEventListener('click', function () {
          showScreen('apps');
          renderApps();
        });
      }

      if (readAllButton) {
        readAllButton.addEventListener('click', async function () {
          if (!activeApp) return;
          try {
            var response = await post(endpoints.readAll, { app: activeApp.key, category: activeCategory });
            var payload = await response.json();
            setBadge(payload.total_unread || 0);
            syncAppUnread(activeApp.key, 0);
            loadItems();
          } catch (error) { /* เงียบไว้ ผู้ใช้กดใหม่ได้ */ }
        });
      }

      function setOpen(open) {
        panel.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', String(open));
        if (!open) return;
        showScreen('apps');
        if (!appsLoaded) { loadApps(); } else { renderApps(); }
      }

      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        setOpen(!panel.classList.contains('is-open'));
        setDownloadOpen(false);
      });

      /* ---------- กล่องเอกสารของ admin (เฉพาะหน้า Time & Leave Approval) ---------- */

      var downloadTrigger = document.getElementById('dlNotiTrigger');
      var downloadPanel = document.getElementById('dlNotiPanel');
      var downloadBadge = downloadTrigger ? downloadTrigger.querySelector('[data-dl-noti-badge]') : null;
      var downloadList = downloadPanel ? downloadPanel.querySelector('[data-dl-noti-list]') : null;
      var downloadReadAll = downloadPanel ? downloadPanel.querySelector('[data-dl-noti-readall]') : null;
      var downloadLoaded = false;
      var downloadIcon = @json(asset('assets/systems/ot-approval.png').'?v=2');
      var otNotiBase = @json(url('/ot-approval/notifications'));

      function setDownloadBadge(count) {
        if (!downloadBadge) return;
        downloadBadge.textContent = count > 99 ? '99+' : String(count);
        downloadBadge.hidden = count < 1;
      }

      function renderDownload(items) {
        if (!downloadList) return;
        downloadList.innerHTML = '';
        if (!items.length) {
          renderEmpty('noti.empty', 'ยังไม่มีการแจ้งเตือน', downloadList);
          return;
        }
        items.forEach(function (item) {
          var row = document.createElement('button');
          row.type = 'button';
          row.className = 'noti-item' + (item.is_read ? '' : ' is-unread');
          var icon = document.createElement('span');
          icon.className = 'noti-app-icon';
          var img = document.createElement('img');
          img.src = downloadIcon;
          img.alt = '';
          icon.appendChild(img);
          var copy = document.createElement('span');
          copy.className = 'noti-copy';
          var title = document.createElement('strong');
          title.textContent = item.title || '';
          copy.appendChild(title);
          if (item.body) {
            var detail = document.createElement('small');
            detail.textContent = item.body;
            copy.appendChild(detail);
          }
          if (item.created_label) {
            var when = document.createElement('time');
            when.textContent = item.created_label;
            copy.appendChild(when);
          }
          row.appendChild(icon);
          row.appendChild(copy);
          row.addEventListener('click', function () {
            if (!item.is_read) {
              fetch(otNotiBase + '/' + item.id + '/read', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
              }).catch(function () {});
            }
            if (item.link) window.location.assign(item.link);
          });
          downloadList.appendChild(row);
        });
      }

      async function loadDownloads() {
        if (!downloadList) return;
        try {
          var response = await fetch(otNotiBase, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          });
          var payload = await response.json();
          downloadLoaded = true;
          setDownloadBadge(payload.download_unread || 0);
          renderDownload(Array.isArray(payload.downloads) ? payload.downloads : []);
        } catch (error) {
          renderEmpty('noti.error', 'โหลดการแจ้งเตือนไม่สำเร็จ', downloadList);
        }
      }

      function setDownloadOpen(open) {
        if (!downloadPanel || !downloadTrigger) return;
        downloadPanel.classList.toggle('is-open', open);
        downloadTrigger.setAttribute('aria-expanded', String(open));
        if (open && !downloadLoaded) loadDownloads();
      }

      if (downloadTrigger) {
        downloadTrigger.addEventListener('click', function (event) {
          event.stopPropagation();
          setDownloadOpen(!downloadPanel.classList.contains('is-open'));
          setOpen(false);
        });
      }

      if (downloadReadAll) {
        downloadReadAll.addEventListener('click', async function () {
          try {
            await fetch(otNotiBase + '/read-all', {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
              },
              credentials: 'same-origin',
              body: JSON.stringify({ category: 'download' }),
            });
            setDownloadBadge(0);
            downloadLoaded = false;
            loadDownloads();
          } catch (error) { /* เงียบไว้ */ }
        });
      }

      document.addEventListener('click', function (event) {
        if (!event.target.closest('.noti-menu')) { setOpen(false); setDownloadOpen(false); }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { setOpen(false); setDownloadOpen(false); }
      });
    })();

  </script>
  @yield('page-script')
</body>
</html>
