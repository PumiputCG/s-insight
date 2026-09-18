<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#090a09">
  <meta name="description" content="SUPAVUT INSIGHT for assessment, recruitment, people operations, and paperless HR work.">
  <title>SUPAVUT INSIGHT</title>
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
      --charcoal: #252824;
      --moss: #5b8def;
      --light-text: #f7f4ec;
      --muted-light: #b7bbb3;
      --line-light: rgb(247 244 236 / 28%);
      --page-pad: clamp(1.25rem, 4vw, 4.5rem);
      --header-height: 5.5rem;
      --font-body: "Jost", "Anuphan", sans-serif;
      --font-display: "Italiana", "Noto Serif Thai", serif;
      --ease-out: cubic-bezier(.16, 1, .3, 1);
      --ease-in-out: cubic-bezier(.76, 0, .24, 1);
      --z-image: 0;
      --z-shade: 1;
      --z-content: 2;
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

    html {
      scroll-behavior: smooth;
      background: var(--near-black);
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
    [data-language-toggle], [data-lang-option], [onclick], [tabindex]:not([tabindex="-1"]),
    input[type="checkbox"], input[type="radio"], input[type="file"], input[type="submit"], input[type="button"],
    input[type="range"], input[type="color"]
      { --cursor-current: var(--cursor-action); cursor: var(--cursor-action) !important; }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="search"], input[type="tel"], textarea, [contenteditable="true"]
      { --cursor-current: text; cursor: text !important; }
    button:disabled, [aria-disabled="true"], [disabled] { --cursor-current: var(--cursor-disabled); cursor: var(--cursor-disabled) !important; }

    body {
      min-width: 320px;
      overflow-x: hidden;
      background: var(--near-black);
      color: var(--light-text);
      font-family: var(--font-body);
      font-size: clamp(1rem, .96rem + .18vw, 1.1rem);
      line-height: 1.7;
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

    ::selection {
      background: var(--moss);
      color: var(--near-black);
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    .skip-link {
      position: fixed;
      top: .75rem;
      left: .75rem;
      z-index: calc(var(--z-loader) + 1);
      padding: .65rem .9rem;
      background: var(--light-text);
      color: var(--near-black);
      transform: translateY(-180%);
      transition: transform .25s var(--ease-out);
    }

    .skip-link:focus {
      transform: none;
    }

    /* ฉากกั้นเป็นน้ำเงินเข้มชุดเดียวกับเมนูซ้ายและฉากล็อกอิน (Manager สั่ง) */
    .page-loader {
      position: fixed;
      inset: 0;
      z-index: var(--z-loader);
      display: none;
      place-items: center;
      background: #16255c;
      color: #ffffff;
      transition: transform .9s var(--ease-in-out);
    }

    .js .page-loader {
      display: grid;
    }

    .page-loader span {
      position: relative;
      display: inline-block;
      padding-bottom: .35rem;
      font-family: "Italiana", "Noto Serif Thai", serif;
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

    .page-loader.is-hidden {
      transform: translateY(-100%);
      pointer-events: none;
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
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 1.5rem;
      padding-inline: var(--page-pad);
      color: var(--near-black);
      /* แถบดำบนสุดตลอด (เหมือนตอน scroll) */
      background: rgb(255 255 255 / 0%);
      border-bottom: 1px solid transparent;
      transition: height .45s var(--ease-out), background-color .45s var(--ease-out), border-color .45s var(--ease-out);
    }

    .site-header.is-scrolled {
      height: 4.5rem;
      color: var(--light-text);
      background: rgb(9 10 9 / 96%);
      border-bottom: 1px solid var(--line-light);
    }

    .menu-button {
      width: 44px;
      height: 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 6px;
      padding: 0;
      border: 0;
      background: transparent;
      cursor: pointer;
    }

    .menu-button span {
      display: block;
      width: 26px;
      height: 1.5px;
      background: currentColor;
      transition: transform .4s var(--ease-out), opacity .3s var(--ease-out);
    }

    .menu-button.is-open span:nth-child(1) {
      transform: translateY(7.5px) rotate(45deg);
    }

    .menu-button.is-open span:nth-child(2) {
      opacity: 0;
    }

    .menu-button.is-open span:nth-child(3) {
      transform: translateY(-7.5px) rotate(-45deg);
    }

    .desktop-nav {
      grid-column: 2;
      justify-self: center;
      display: flex;
      align-items: center;
      gap: clamp(1.25rem, 2.5vw, 3rem);
    }

    .desktop-nav a,
    .language-button {
      position: relative;
      padding-block: .4rem;
      border: 0;
      background: transparent;
      cursor: pointer;
      font-size: clamp(.92rem, 1vw, 1rem);
      font-weight: 700;
      letter-spacing: .035em;
      text-transform: uppercase;
    }

    .desktop-nav a::after,
    .language-button::after {
      content: "";
      position: absolute;
      right: 0;
      bottom: 0;
      left: 0;
      height: 1px;
      background: currentColor;
      transform: scaleX(0);
      transform-origin: right;
      transition: transform .4s var(--ease-out);
    }

    .desktop-nav a:hover::after,
    .language-picker:hover .language-button::after,
    .language-picker.is-open .language-button::after,
    .desktop-nav a[aria-current="page"]::after {
      transform: scaleX(1);
      transform-origin: left;
    }

    .header-actions {
      grid-column: 3;
      justify-self: end;
    }

    .header-left {
      grid-column: 1;
      justify-self: start;
      display: flex;
      align-items: center;
      gap: clamp(.55rem, 1.5vw, 1rem);
    }

    .brand {
      font-family: "Montserrat", var(--font-body);
      font-size: clamp(1rem, 1.45vw, 1.42rem);
      font-weight: 800;
      letter-spacing: .035em;
      color: currentColor;
      white-space: nowrap;
    }

    .brand .brand-accent { color: var(--moss); }

    .language-button {
      width: 2.75rem;
      height: 2.75rem;
      display: grid;
      place-items: center;
      padding: 0;
      border-radius: 50%;
      color: currentColor;
      transition: background-color .25s var(--ease-out);
    }

    .language-button::after {
      display: none;
    }

    .language-button:hover,
    .language-picker.is-open .language-button {
      background: rgb(9 10 9 / 7%);
    }

    .site-header.is-scrolled .language-button:hover,
    .site-header.is-scrolled .language-picker.is-open .language-button {
      background: rgb(247 244 236 / 10%);
    }

    .language-globe {
      width: 1.55rem;
      height: 1.55rem;
    }

    .language-flag {
      width: 1.5rem;
      height: 1.05rem;
      display: block;
      flex: none;
      object-fit: cover;
      border-radius: .16rem;
      box-shadow: 0 0 0 1px rgb(9 10 9 / 16%);
    }

    .language-button .language-flag {
      width: 1.72rem;
      height: 1.18rem;
    }

    .language-menu .language-flag {
      margin-left: auto;
    }

    /* keep the dropdown reachable while moving the cursor down from the globe */
    .language-picker::after {
      content: "";
      position: absolute;
      top: 100%;
      right: 0;
      width: 11rem;
      height: 1.1rem;
    }

    .language-picker {
      position: relative;
      display: inline-flex;
      justify-content: flex-end;
    }

    .language-menu {
      position: absolute;
      top: calc(100% + .7rem);
      right: 0;
      width: max-content;
      min-width: 10.5rem;
      padding: .55rem;
      border: 1px solid rgb(9 10 9 / 12%);
      background: rgb(255 255 255 / 96%);
      color: var(--near-black);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-.4rem);
      transition: opacity .24s var(--ease-out), transform .24s var(--ease-out);
    }

    .site-header.is-scrolled .language-menu {
      border-color: var(--line-light);
      background: rgb(9 10 9 / 96%);
      color: var(--light-text);
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
      color: rgb(9 10 9 / 66%);
      cursor: pointer;
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .07em;
      text-align: left;
      text-transform: uppercase;
      transition: color .2s var(--ease-out), background-color .2s var(--ease-out);
      white-space: nowrap;
    }

    .language-menu button:hover,
    .language-menu button[aria-current="true"] {
      background: rgb(91 141 239 / 12%);
      color: var(--near-black);
    }

    .site-header.is-scrolled .language-menu button {
      color: rgb(247 244 236 / 72%);
    }

    .site-header.is-scrolled .language-menu button:hover,
    .site-header.is-scrolled .language-menu button[aria-current="true"] {
      background: rgb(247 244 236 / 8%);
      color: var(--light-text);
    }

    .language-menu small {
      color: var(--moss);
      font-size: .68rem;
      font-weight: 600;
      letter-spacing: .08em;
    }

    .mobile-menu {
      position: fixed;
      top: calc(var(--header-height) - .6rem);
      left: var(--page-pad);
      z-index: var(--z-menu);
      width: min(17rem, calc(100vw - 2 * var(--page-pad)));
      padding: .8rem;
      border: 1px solid rgb(9 10 9 / 12%);
      background: rgb(255 255 255 / 97%);
      color: var(--near-black);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-.6rem);
      transition: opacity .28s var(--ease-out), transform .28s var(--ease-out);
    }

    .mobile-menu.is-open {
      opacity: 1;
      pointer-events: auto;
      transform: none;
    }

    .mobile-menu a {
      display: flex;
      justify-content: space-between;
      padding: .6rem .5rem;
      border-bottom: 1px solid rgb(9 10 9 / 12%);
      font-size: .98rem;
      font-weight: 600;
      letter-spacing: .035em;
      text-transform: uppercase;
    }

    .mobile-menu a:last-child {
      border-bottom: 0;
    }

    .journal-hero {
      position: relative;
      min-height: 100vh;
      min-height: 100dvh;
      display: flex;
      align-items: center;
      overflow: hidden;
      isolation: isolate;
      background: #f7faf6;
      color: var(--near-black);
    }

    .journal-hero-image {
      position: absolute;
      inset: -3%;
      z-index: var(--z-image);
      width: 106%;
      height: 106%;
      max-width: none;
      object-fit: cover;
      object-position: center right;
      will-change: transform;
    }

    .journal-hero-shade {
      position: absolute;
      inset: 0;
      z-index: var(--z-shade);
      pointer-events: none;
      background:
        linear-gradient(90deg, rgb(255 255 255 / 94%) 0%, rgb(255 255 255 / 78%) 34%, rgb(255 255 255 / 36%) 58%, transparent 82%),
        linear-gradient(180deg, rgb(255 255 255 / 82%) 0%, transparent 30%),
        linear-gradient(0deg, rgb(255 255 255 / 74%) 0%, transparent 36%);
    }

    .journal-hero-content {
      position: relative;
      z-index: var(--z-content);
      width: min(100%, 58rem);
      padding: calc(var(--header-height) + 2rem) var(--page-pad) clamp(4.25rem, 7vw, 6.5rem);
    }

    .journal-location {
      display: none;
    }

    .hero-statement {
      position: relative;
      width: min(100%, 50rem);
      padding-left: clamp(1.35rem, 2vw, 2rem);
    }

    .hero-statement::before {
      content: "";
      position: absolute;
      top: clamp(.25rem, .6vw, .6rem);
      bottom: clamp(3.25rem, 3.8vw, 4.2rem);
      left: 0;
      width: 4px;
      background: var(--moss);
    }

    .rotating-headline {
      max-width: 100%;
      font-family: "Montserrat", var(--font-body);
      font-weight: 700;
      line-height: .98;
      letter-spacing: 0;
      text-transform: none;
      text-wrap: balance;
    }

    .rotating-headline-visual {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: .06em;
      max-width: 100%;
      white-space: normal;
    }

    .rotating-headline-fixed {
      flex: none;
      color: currentColor;
      font-size: clamp(2.75rem, 5.7vw, 5.75rem);
      font-weight: 800;
      line-height: 1;
    }

    .rotating-word-slot {
      flex: none;
      width: 100%;
      min-width: 0;
      display: flex;
      align-items: center;
      color: currentColor;
      font-size: clamp(2.75rem, 5.7vw, 5.75rem);
      font-weight: 800;
      line-height: 1;
    }

    .rotating-word-clip {
      display: inline-flex;
      align-items: center;
      max-width: 100%;
      overflow: hidden;
      line-height: 1;
      white-space: nowrap;
    }

    @supports (clip-path: inset(0)) {
      .rotating-word-clip {
        overflow: visible;
        clip-path: inset(-.24em 0 -.28em 0);
      }
    }

    .rotating-word {
      display: block;
      line-height: 1;
    }

    .js .rotating-word-clip {
      width: 0;
      transition: width .8s var(--ease-out);
      will-change: width;
    }

    .js .rotating-word-clip.is-visible {
      width: min(var(--rotating-word-width, 13ch), 100%);
    }

    .rotating-word-cursor {
      flex: 0 0 2px;
      width: 2px;
      height: .95em;
      margin-left: .22em;
      background: currentColor;
    }

    html[data-lang="my"] .rotating-headline-visual {
      flex-wrap: wrap;
      white-space: normal;
    }

    html[data-lang="th"] .rotating-headline,
    html[data-lang="th"] .rotating-headline-fixed,
    html[data-lang="th"] .rotating-word-slot,
    html[data-lang="th"] .rotating-word-clip,
    html[data-lang="th"] .rotating-word {
      line-height: 1.22;
    }

    html[data-lang="my"] .rotating-headline,
    html[data-lang="my"] .rotating-headline-fixed,
    html[data-lang="my"] .rotating-word-slot,
    html[data-lang="my"] .rotating-word-clip,
    html[data-lang="my"] .rotating-word {
      line-height: 1.38;
    }

    html[data-lang="th"] .rotating-word-clip,
    html[data-lang="my"] .rotating-word-clip {
      padding-block: .08em .18em;
      margin-block: -.08em -.18em;
    }

    html[data-lang="th"] .rotating-word-cursor,
    html[data-lang="my"] .rotating-word-cursor {
      align-self: center;
    }

    html[data-lang="my"] .rotating-word-slot {
      flex-basis: min(100%, 22ch);
      width: min(100%, 22ch);
    }

    html[data-lang="th"] .rotating-word-slot {
      flex-basis: min(100%, 17ch);
      width: min(100%, 17ch);
    }

    .journal-hero-copy {
      max-width: 44rem;
      margin-top: clamp(1.35rem, 2.3vw, 2rem);
      color: rgb(9 10 9 / 86%);
      font-size: clamp(1rem, 1.45vw, 1.36rem);
      line-height: 1.6;
      text-wrap: pretty;
    }

    .scroll-cue {
      position: absolute;
      right: var(--page-pad);
      bottom: clamp(2rem, 4vw, 4rem);
      z-index: var(--z-content);
      display: none;
      align-items: center;
      gap: .8rem;
      color: rgb(9 10 9 / 82%);
      font-size: .7rem;
      font-weight: 600;
      letter-spacing: .09em;
      text-transform: uppercase;
    }

    .scroll-line {
      position: relative;
      width: clamp(3rem, 7vw, 7rem);
      height: 1px;
      overflow: hidden;
      background: rgb(9 10 9 / 22%);
    }

    .scroll-line::after {
      content: "";
      position: absolute;
      inset: 0;
      background: currentColor;
      animation: scroll-line 2.2s var(--ease-in-out) infinite;
    }

    .insight-definition {
      min-height: 100svh;
      display: grid;
      align-items: center;
      padding: clamp(7rem, 11vw, 10rem) var(--page-pad);
      background: var(--light-text);
      color: var(--near-black);
    }

    .definition-inner {
      width: min(100%, 70rem);
      margin-inline: auto;
    }

    .definition-kicker {
      display: block;
      margin-bottom: clamp(2rem, 4vw, 3rem);
      color: rgb(9 10 9 / 72%);
      font-family: "Montserrat", var(--font-body);
      font-size: clamp(.78rem, 1.1vw, 1rem);
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .definition-title {
      max-width: 16ch;
      font-family: var(--font-display);
      font-size: clamp(3.2rem, 7vw, 6rem);
      font-weight: 400;
      line-height: 1.08;
      letter-spacing: -.02em;
      text-wrap: balance;
    }

    .definition-copy {
      width: min(100%, 62rem);
      margin-top: clamp(2.5rem, 5vw, 4.5rem);
      display: grid;
      gap: clamp(1.25rem, 2.2vw, 1.8rem);
      color: rgb(9 10 9 / 78%);
      font-size: clamp(1.02rem, 1.35vw, 1.28rem);
      font-weight: 500;
      line-height: 1.85;
      text-wrap: pretty;
    }

    /* พื้นน้ำเงินเข้มเฉพาะ 2 ส่วนนี้ — ไม่เกี่ยวกับ html/body ที่ต้องเป็นขาว */
    .flow {
      position: relative;
      padding: clamp(6rem, 10vw, 10rem) var(--page-pad);
      background: #16255c;
      color: #ffffff;
    }

    .flow-head {
      width: min(100%, 58rem);
      margin: 0 auto clamp(2.5rem, 5vw, 4rem);
      text-align: center;
    }

    .flow-title {
      max-width: 100%;
      margin-inline: auto;
      font-family: var(--font-display);
      font-size: clamp(2.1rem, 4.4vw, 4rem);
      font-weight: 400;
      line-height: 1.05;
      white-space: nowrap;
      text-wrap: balance;
    }

    .flow-title-line {
      display: inline-block;
      max-width: 100%;
      white-space: nowrap;
    }

    /* Benefits route */
    .flow-track {
      --node: 3.4rem;
      --node-half: 1.7rem;
      --flow-progress: 0;
      position: relative;
      width: min(100%, 72rem);
      margin-inline: auto;
    }

    .flow-route {
      position: absolute;
      inset: 0;
      z-index: 0;
      width: 100%;
      height: 100%;
      overflow: visible;
      pointer-events: none;
    }

    .route-path-base,
    .route-path-progress,
    .route-path-tail-progress {
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
      vector-effect: non-scaling-stroke;
    }

    .route-path-base {
      stroke: rgb(247 244 236 / 20%);
      stroke-width: 3;
    }

    .route-path-progress {
      stroke: url(#flow-route-gradient);
      stroke-width: 5.2;
      filter: url(#flow-route-glow);
      transition: stroke-dashoffset .12s linear;
    }

    .route-path-tail-progress {
      stroke: url(#flow-route-tail-gradient);
      stroke-width: 6.4;
      filter: url(#flow-route-glow);
      transition: stroke-dashoffset .12s linear;
    }

    .flow-list {
      position: relative;
      margin: 0;
      padding: 0;
      list-style: none;
      min-height: clamp(58rem, 82vw, 78rem);
    }

    .flow-list::before,
    .flow-list::after {
      content: "";
      position: absolute;
      display: none;
      top: calc(var(--node) / 2);
      bottom: calc(var(--node) / 2);
      left: calc(var(--node) / 2);
      width: 1px;
    }

    .flow-list::before {
      background: rgb(247 244 236 / 16%);
    }

    .flow-list::after {
      background: linear-gradient(180deg, #75d9ff, #a78bfa);
      box-shadow: 0 0 18px rgb(117 217 255 / 35%);
      transform: scaleY(var(--flow-progress));
      transform-origin: top;
    }

    .flow-step {
      --step-transform: none;
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: var(--node) minmax(0, 1fr);
      align-items: start;
      gap: clamp(1.2rem, 3vw, 2.1rem);
      width: min(24rem, 36%);
      padding: 0;
    }

    @media (min-width: 761px) {
      .flow-step {
        position: absolute;
        left: var(--step-x);
        top: var(--step-y);
        --step-transform: translate3d(calc(0px - var(--node-half)), -50%, 0);
        transform: var(--step-transform);
      }

      .flow-step:nth-child(even) {
        grid-template-columns: minmax(0, 1fr) var(--node);
        text-align: right;
        --step-transform: translate3d(calc(-100% + var(--node-half)), -50%, 0);
      }

      .flow-step:nth-child(even) .flow-node {
        grid-column: 2;
        grid-row: 1;
      }

      .flow-step:nth-child(even) .flow-step-body {
        grid-column: 1;
        grid-row: 1;
      }

      .flow-step:nth-child(1) { --step-x: 16.5%; --step-y: 4.8%; }
      .flow-step:nth-child(2) { --step-x: 79%; --step-y: 18.4%; }
      .flow-step:nth-child(3) { --step-x: 21%; --step-y: 33.6%; }
      .flow-step:nth-child(4) { --step-x: 78%; --step-y: 49.6%; }
      .flow-step:nth-child(5) { --step-x: 22%; --step-y: 65.6%; }
      .flow-step:nth-child(6) { --step-x: 77.5%; --step-y: 80.8%; }
      .flow-step:nth-child(7) { --step-x: 50%; --step-y: 95.2%; }

      .flow-step:nth-child(7) {
        grid-template-columns: var(--node) minmax(0, 1fr);
        text-align: left;
        --step-transform: translate3d(calc(0px - var(--node-half)), -50%, 0);
      }
    }

    .js .flow-track.is-flow-ready .flow-step[data-flow-step] {
      opacity: .14;
      pointer-events: none;
      filter: blur(4px);
      transform: var(--step-transform) translateY(1.2rem) scale(.985);
      transition: opacity .52s var(--ease-out), transform .52s var(--ease-out), filter .52s var(--ease-out);
    }

    .js .flow-track.is-flow-ready .flow-step[data-flow-step].is-visible {
      opacity: 1;
      pointer-events: auto;
      filter: blur(0);
      transform: var(--step-transform);
    }

    .flow-node {
      position: relative;
      z-index: 1;
      width: var(--node);
      height: var(--node);
      display: grid;
      place-items: center;
      border: 1px solid rgb(247 244 236 / 26%);
      border-radius: 50%;
      background: var(--near-black);
      color: rgb(247 244 236 / 70%);
      font-family: "Montserrat", var(--font-body);
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: -.02em;
      transition: border-color .6s var(--ease-out), color .6s var(--ease-out), background-color .6s var(--ease-out), box-shadow .6s var(--ease-out), transform .6s var(--ease-out);
    }

    .flow-step.is-visible .flow-node {
      border-color: #75d9ff;
      color: var(--light-text);
      box-shadow: 0 0 0 5px rgb(117 217 255 / 10%);
    }

    .flow-step.is-current .flow-node {
      border-color: #a78bfa;
      background: rgb(167 139 250 / 14%);
      box-shadow: 0 0 0 6px rgb(117 217 255 / 12%), 0 0 26px rgb(167 139 250 / 32%);
      transform: scale(1.04);
    }

    .flow-node-end {
      border-color: #a78bfa;
      background: linear-gradient(135deg, #75d9ff, #a78bfa);
    }

    .flow-step.is-visible .flow-node-end {
      box-shadow: 0 0 0 5px rgb(117 217 255 / 16%), 0 0 22px rgb(167 139 250 / 35%);
    }

    .flow-step-body { padding-top: .3rem; }

    .flow-step-title {
      font-family: var(--font-display);
      font-size: clamp(1.5rem, 3vw, 2.4rem);
      font-weight: 400;
      line-height: 1.12;
      color: rgb(247 244 236 / 68%);
      transition: color .52s var(--ease-out), transform .52s var(--ease-out);
    }

    .flow-step.is-visible .flow-step-title { color: var(--light-text); }

    .flow-result .flow-step-title { color: var(--light-text); }

    .flow-step-desc {
      max-width: 34rem;
      margin-top: .5rem;
      color: rgb(247 244 236 / 60%);
      font-size: clamp(.95rem, 1.1vw, 1.05rem);
      line-height: 1.7;
      transition: opacity .52s var(--ease-out), transform .52s var(--ease-out);
    }

    .js .flow-track.is-flow-ready .flow-step[data-flow-step] .flow-step-title,
    .js .flow-track.is-flow-ready .flow-step[data-flow-step] .flow-step-desc {
      transform: translateY(.45rem);
    }

    .js .flow-track.is-flow-ready .flow-step[data-flow-step] .flow-step-desc {
      opacity: 0;
    }

    .js .flow-track.is-flow-ready .flow-step[data-flow-step].is-visible .flow-step-title,
    .js .flow-track.is-flow-ready .flow-step[data-flow-step].is-visible .flow-step-desc {
      opacity: 1;
      transform: none;
    }

    .flow-step-desc:empty { display: none; }

    html[data-lang="my"] .definition-title {
      max-width: 18ch;
      font-family: var(--font-body);
      font-size: clamp(2.3rem, 5vw, 4.4rem);
      font-weight: 600;
      line-height: 1.25;
      letter-spacing: 0;
    }

    html[data-lang="my"] .definition-copy {
      font-size: clamp(.95rem, 1.2vw, 1.1rem);
      line-height: 1.9;
    }

    html[data-lang="my"] .flow-title,
    html[data-lang="my"] .flow-step-title,
    html[data-lang="my"] .contact-intro-copy h2 {
      font-family: var(--font-body);
      font-weight: 600;
      letter-spacing: 0;
    }

    html[data-lang="my"] .flow-title {
      max-width: 100%;
      font-size: clamp(1.8rem, 3.7vw, 3.15rem);
      line-height: 1.22;
      white-space: nowrap;
    }

    html[data-lang="my"] .flow-step-title {
      font-size: clamp(1.35rem, 2.2vw, 1.95rem);
      line-height: 1.35;
    }

    html[data-lang="my"] .flow-step-desc {
      font-size: clamp(.88rem, 1vw, 1rem);
      line-height: 1.85;
    }

    html[data-lang="my"] .contact-intro-copy h2 {
      max-width: 20ch;
      font-size: clamp(2.2rem, 3.8vw, 3.8rem);
      line-height: 1.25;
    }

    @keyframes scroll-line {
      0% { transform: translateX(-105%); }
      45%, 55% { transform: translateX(0); }
      100% { transform: translateX(105%); }
    }

    .journal-footer {
      background: #16255c;
      color: #ffffff;
    }

    .contact-intro {
      position: relative;
      min-height: 35rem;
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(20rem, .8fr);
      align-items: center;
      gap: clamp(4rem, 9vw, 10rem);
      overflow: hidden;
      isolation: isolate;
      padding: clamp(6rem, 10vw, 10rem) var(--page-pad);
      background: var(--near-black);
      color: var(--light-text);
    }

    .contact-intro-image,
    .contact-intro-shade {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }

    .contact-intro-image {
      z-index: 0;
      max-width: none;
      object-fit: cover;
      /* ภาพใหม่ (3500x2329) กว้างกว่าเดิมมาก จัดโฟกัสกลางภาพให้เห็นตัวงานพอดีกรอบ */
      object-position: center center;
    }

    .contact-intro-shade {
      z-index: 1;
      background: rgb(9 10 9 / 62%);
      pointer-events: none;
    }

    .contact-intro-copy,
    .contact-intro-action {
      position: relative;
      z-index: 2;
      width: min(100%, 42rem);
    }

    .contact-intro-copy {
      margin-left: auto;
    }

    .contact-intro-copy h2 {
      max-width: 15ch;
      font-family: var(--font-display);
      font-size: clamp(3rem, 4.4vw, 4.8rem);
      font-weight: 400;
      line-height: 1.08;
      text-wrap: balance;
    }

    .contact-description {
      max-width: 42rem;
      margin-top: 1.5rem;
      color: rgb(247 244 236 / 86%);
      font-size: clamp(.95rem, 1vw, 1.08rem);
      line-height: 1.8;
    }

    .contact-intro-action {
      margin-right: auto;
    }

    .contact-intro-action > p {
      max-width: 31rem;
      color: var(--light-text);
      font-size: clamp(1rem, 1.15vw, 1.12rem);
      font-weight: 600;
      line-height: 1.8;
    }

    .contact-email-form {
      position: relative;
      display: flex;
      align-items: center;
      width: min(100%, 42rem);
      margin-top: 1.75rem;
      border-bottom: 1px solid rgb(247 244 236 / 72%);
    }

    .contact-email-form input {
      min-width: 0;
      flex: 1;
      padding: .8rem 0;
      border: 0;
      outline: 0;
      background: transparent;
      color: var(--light-text);
    }

    .contact-email-form input::placeholder {
      color: rgb(247 244 236 / 65%);
    }

    .contact-email-form button {
      width: 3rem;
      height: 3rem;
      display: grid;
      place-items: center;
      padding: 0;
      border: 0;
      background: transparent;
      cursor: pointer;
    }

    .contact-email-form svg {
      width: 1.45rem;
      height: 1.45rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .footer-details {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      border-top: 1px solid var(--line-light);
      border-bottom: 1px solid var(--line-light);
    }

    .footer-detail {
      min-height: 10rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem clamp(1.5rem, 4vw, 4rem);
      text-align: center;
    }

    .footer-detail + .footer-detail {
      border-left: 1px solid var(--line-light);
    }

    .footer-detail span {
      color: #73816c;
      font-size: .7rem;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    .footer-detail strong {
      margin-top: 1rem;
      font-size: clamp(.95rem, 1.2vw, 1.1rem);
      font-weight: 400;
      overflow-wrap: anywhere;
    }

    .footer-bottom {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      padding: 3rem var(--page-pad);
      color: var(--muted-light);
      font-size: .75rem;
      letter-spacing: .06em;
      text-transform: uppercase;
    }

    .footer-top-link {
      width: 2.75rem;
      height: 2.75rem;
      display: grid;
      place-items: center;
      margin: -.85rem -.75rem -.85rem 0;
      transition: color .25s var(--ease-out), transform .25s var(--ease-out);
    }

    .footer-top-link:hover {
      color: #a7b39f;
      transform: translateY(-3px);
    }

    .footer-top-link svg {
      width: 1.5rem;
      height: 1.5rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.6;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .entrance,
    .reveal {
      opacity: 1;
      transform: none;
    }

    .js .entrance {
      opacity: 0;
      transform: translateY(1.2rem);
    }

    .js.is-ready .entrance {
      opacity: 1;
      transform: none;
      transition: opacity .9s var(--ease-out), transform .9s var(--ease-out);
    }

    .js.is-ready .entrance-delay {
      transition-delay: .1s;
    }

    .js.is-ready .entrance-delay-2 {
      transition-delay: .2s;
    }

    .js.is-ready .entrance-delay-3 {
      transition-delay: .3s;
    }

    .js .reveal {
      opacity: 0;
      transform: translateY(2rem);
    }

    .js .reveal.is-visible {
      opacity: 1;
      transform: none;
      transition: opacity .9s var(--ease-out), transform .9s var(--ease-out);
    }

    @media (max-width: 760px) {
      :root {
        --header-height: 4.75rem;
      }

      .site-header {
        gap: .5rem;
        padding-inline: 1.25rem;
      }

      .page-loader span {
        font-size: clamp(1.85rem, 9vw, 2.8rem);
        letter-spacing: .035em;
      }

      .desktop-nav {
        display: none;
      }

      .language-button {
        min-width: 1.8rem;
        text-align: center;
      }

      .language-menu {
        min-width: 9.5rem;
      }

      .journal-hero {
        align-items: flex-end;
      }

      .journal-hero-image {
        inset: -2%;
        width: 104%;
        height: 104%;
        object-position: 63% center;
      }

      .journal-hero-shade {
        background:
          linear-gradient(90deg, rgb(255 255 255 / 94%) 0%, rgb(255 255 255 / 82%) 52%, rgb(255 255 255 / 28%) 100%),
          linear-gradient(180deg, rgb(255 255 255 / 88%) 0%, transparent 34%),
          linear-gradient(0deg, rgb(255 255 255 / 88%) 0%, rgb(255 255 255 / 62%) 46%, transparent 80%);
      }

      .journal-hero-content {
        width: 100%;
        padding-top: clamp(9rem, 32svh, 15rem);
        padding-bottom: 6.5rem;
      }

      .hero-statement {
        width: min(100%, 28rem);
        padding-left: 1rem;
      }

      .hero-statement::before {
        top: .3rem;
        bottom: 3.35rem;
        width: 3px;
      }

      .rotating-headline-visual {
        gap: .06em;
      }

      .rotating-headline-fixed {
        font-size: clamp(2.05rem, 10.5vw, 3.25rem);
      }

      .rotating-word-slot {
        font-size: clamp(2.05rem, 10.5vw, 3.25rem);
      }

      .journal-hero-copy {
        font-size: clamp(.95rem, 3.7vw, 1.08rem);
        line-height: 1.7;
      }

      .scroll-cue {
        right: auto;
        left: var(--page-pad);
        bottom: 2rem;
      }

      .insight-definition {
        min-height: 100svh;
        padding-top: 6rem;
        padding-bottom: 6rem;
      }

      .definition-title {
        max-width: 11ch;
        font-size: clamp(2.7rem, 12vw, 4.6rem);
      }

      .definition-copy {
        font-size: .98rem;
        line-height: 1.8;
      }

      .flow {
        padding-top: 5rem;
      }

      .flow-head {
        text-align: left;
      }

      .flow-title {
        margin-inline: 0;
        font-size: clamp(1.55rem, 7vw, 2.35rem);
        line-height: 1.18;
        white-space: nowrap;
      }

      .flow-track {
        --node: 2.9rem;
        --node-half: 1.45rem;
        width: 100%;
      }

      .flow-route {
        display: none;
      }

      .flow-list {
        min-height: 0;
      }

      .flow-list::before,
      .flow-list::after {
        display: block;
      }

      .flow-step {
        --step-transform: none;
        position: relative;
        width: 100%;
        padding: clamp(1.5rem, 3vw, 2.3rem) 0;
        transform: none;
      }

      .js .flow-track.is-flow-ready .flow-step[data-flow-step] {
        transform: translateY(1.1rem);
      }

      .js .flow-track.is-flow-ready .flow-step[data-flow-step].is-visible {
        transform: none;
      }

      .contact-intro {
        min-height: 0;
        grid-template-columns: 1fr;
        gap: 3.5rem;
      }

      .flow-node {
        width: var(--node);
        height: var(--node);
        font-size: .92rem;
      }

      .contact-intro-copy,
      .contact-intro-action {
        width: 100%;
        margin-inline: 0;
      }

      .footer-details {
        grid-template-columns: 1fr 1fr;
      }

      .footer-detail:nth-child(3) {
        border-left: 0;
      }

      .footer-detail:nth-child(n + 3) {
        border-top: 1px solid var(--line-light);
      }
    }

    @media (max-width: 480px) {
      .footer-details {
        grid-template-columns: 1fr;
      }

      .footer-detail + .footer-detail {
        border-top: 1px solid var(--line-light);
        border-left: 0;
      }

      .footer-bottom {
        flex-direction: column;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      html {
        scroll-behavior: auto;
      }

      *,
      *::before,
      *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
      }

      .journal-hero-image {
        inset: 0;
        width: 100%;
        height: 100%;
        transform: none !important;
      }

      .js .entrance,
      .js .reveal {
        opacity: 1;
        transform: none;
      }

      .flow-track {
        --flow-progress: 1;
      }

      .js .flow-track.is-flow-ready .flow-step[data-flow-step],
      .js .flow-track.is-flow-ready .flow-step[data-flow-step].is-visible {
        opacity: 1;
        pointer-events: auto;
        filter: none;
      }

      .js .rotating-word-clip,
      .js .rotating-word-clip.is-visible {
        width: min(var(--rotating-word-width, 13ch), 100%);
        transition: none;
      }

    }
  </style>
</head>
<body>
  <a href="#main" class="skip-link">Skip to content</a>

  <div class="page-loader" aria-hidden="true">
    <span>SUPAVUT INSIGHT</span>
  </div>

  <header class="site-header" id="siteHeader">
    <div class="header-left">
      <button class="menu-button" id="menuButton" type="button" aria-label="Open menu" data-open-label="Open menu" data-close-label="Close menu" aria-expanded="false" aria-controls="mobileMenu">
        <span></span>
        <span></span>
        <span></span>
      </button>
      <a class="brand" href="#home" aria-label="SUPAVUT INSIGHT home">SUPAVUT <span class="brand-accent">INSIGHT</span></a>
    </div>

    <nav class="desktop-nav" aria-label="Primary navigation">
      <a href="#home" aria-current="page" data-i18n="nav.home">Home</a>
      <a href="{{ route('login') }}" data-i18n="nav.login">Login</a>
      <a href="#contact" data-i18n="nav.contact">Contact</a>
    </nav>

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

  <nav class="mobile-menu" id="mobileMenu" aria-label="Navigation menu">
    <a href="#home" data-i18n="nav.home">Home</a>
    <a href="{{ route('login') }}" data-i18n="nav.login">Login</a>
    <a href="#contact" data-i18n="nav.contact">Contact</a>
  </nav>

  <main id="main">
    <section class="journal-hero" id="home" aria-labelledby="home-title">
      <img
        class="journal-hero-image"
        src="{{ asset('assets/insight/home-hero-bg.png') }}"
        alt="Bright HR workspace with folders, documents, and a plant"
        width="1672"
        height="941"
        fetchpriority="high"
      >
      <div class="journal-hero-shade" aria-hidden="true"></div>

      <div class="journal-hero-content">
        <div class="hero-statement">
          <p class="journal-location entrance" data-i18n="hero.kicker">HR Work Center</p>
          <h1 id="home-title" class="rotating-headline entrance entrance-delay">
            <span class="sr-only" data-i18n="hero.sr">Support for assessment, recruitment, and paperless people operations.</span>
            <span class="rotating-headline-visual" aria-hidden="true">
              <span class="rotating-headline-fixed" data-i18n="hero.fixed">Support for</span>
              <span class="rotating-word-slot" data-rotating-words="ASSESSMENT,RECRUITMENT,PAPERLESS">
                <span class="rotating-word-clip is-visible">
                  <span class="rotating-word">Assessment</span>
                  <span class="rotating-word-cursor"></span>
                </span>
              </span>
            </span>
          </h1>
          <p class="journal-hero-copy entrance entrance-delay-2" data-i18n="hero.copy">
            A system for managing employees and organizational documents.
          </p>
        </div>
      </div>

      <a class="scroll-cue entrance entrance-delay-3" href="{{ route('login') }}">
        <span data-i18n="hero.cta">Enter SUPAVUT INSIGHT</span>
        <span class="scroll-line" aria-hidden="true"></span>
      </a>
    </section>

    <section class="insight-definition" id="definition" aria-labelledby="definition-title">
      <div class="definition-inner">
        <span class="definition-kicker reveal" data-i18n="definition.kicker">Definition of SUPAVUT INSIGHT</span>
        <h2 id="definition-title" class="definition-title reveal" data-i18n="definition.title">What is SUPAVUT INSIGHT?</h2>
        <div class="definition-copy">
          <p class="reveal" data-i18n="definition.p1">SUPAVUT INSIGHT is a platform that brings HR work into one system, from documents, employee assessment, recruitment, through to OKR.</p>
          <p class="reveal" data-i18n="definition.p2">The system is designed to reduce repeated work, reduce paper use, remove unnecessary steps, and make HR work clearer, traceable, and faster.</p>
        </div>
      </div>
    </section>

    <section class="flow" id="flow" aria-labelledby="flow-title">
      <div class="flow-head">
        <h2 id="flow-title" class="flow-title reveal"><span class="flow-title-line" data-i18n="flow.title">Benefits of Insight</span></h2>
      </div>

      <div class="flow-track" data-flow-track>
        <svg class="flow-route" viewBox="0 0 1000 1250" preserveAspectRatio="none" aria-hidden="true">
          <defs>
            <linearGradient id="flow-route-gradient" x1="120" y1="40" x2="820" y2="1210" gradientUnits="userSpaceOnUse">
              <stop offset="0" stop-color="#75d9ff" />
              <stop offset=".55" stop-color="#8f9dff" />
              <stop offset="1" stop-color="#a78bfa" />
            </linearGradient>
            <linearGradient id="flow-route-tail-gradient" x1="775" y1="1010" x2="500" y2="1190" gradientUnits="userSpaceOnUse">
              <stop offset="0" stop-color="#75d9ff" />
              <stop offset="1" stop-color="#a78bfa" />
            </linearGradient>
            <filter id="flow-route-glow" x="-35%" y="-35%" width="170%" height="170%">
              <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur" />
              <feMerge>
                <feMergeNode in="blur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>
          <path class="route-path-base" d="M 165 60 C 520 60 840 110 790 230 C 735 370 280 295 210 420 C 130 560 720 490 780 620 C 850 760 265 700 220 820 C 175 950 730 870 775 1010 C 810 1120 600 1170 500 1190" />
          <path class="route-path-progress" d="M 165 60 C 520 60 840 110 790 230 C 735 370 280 295 210 420 C 130 560 720 490 780 620 C 850 760 265 700 220 820 C 175 950 730 870 775 1010 C 810 1120 600 1170 500 1190" />
          <path class="route-path-tail-progress" d="M 775 1010 C 810 1120 600 1170 500 1190" />
        </svg>
        <ol class="flow-list">
          <li class="flow-step" data-flow-step>
            <span class="flow-node">01</span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.s1.t">Enter SUPAVUT INSIGHT</h3>
              <p class="flow-step-desc" data-i18n="flow.s1.d"></p>
            </div>
          </li>
          <li class="flow-step" data-flow-step>
            <span class="flow-node">02</span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.s2.t">Centralize data</h3>
              <p class="flow-step-desc" data-i18n="flow.s2.d">All data stays in one place.</p>
            </div>
          </li>
          <li class="flow-step" data-flow-step>
            <span class="flow-node">03</span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.s3.t">Manage documents</h3>
              <p class="flow-step-desc" data-i18n="flow.s3.d">Documents become digital.</p>
            </div>
          </li>
          <li class="flow-step" data-flow-step>
            <span class="flow-node">04</span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.s4.t">Reduce steps</h3>
              <p class="flow-step-desc" data-i18n="flow.s4.d">Cut repeated work and paper routing.</p>
            </div>
          </li>
          <li class="flow-step" data-flow-step>
            <span class="flow-node">05</span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.s5.t">Track status</h3>
              <p class="flow-step-desc" data-i18n="flow.s5.d">See progress clearly.</p>
            </div>
          </li>
          <li class="flow-step" data-flow-step>
            <span class="flow-node">06</span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.s6.t">Summarize reports</h3>
              <p class="flow-step-desc" data-i18n="flow.s6.d">Turn data into an easy-to-understand overview.</p>
            </div>
          </li>
          <li class="flow-step flow-result" data-flow-step>
            <span class="flow-node flow-node-end" aria-hidden="true"></span>
            <div class="flow-step-body">
              <h3 class="flow-step-title" data-i18n="flow.result.t">See results</h3>
              <p class="flow-step-desc" data-i18n="flow.result.d"></p>
            </div>
          </li>
        </ol>
      </div>
    </section>

    <footer class="journal-footer" id="contact">
      <section class="contact-intro" aria-labelledby="contact-title">
        <img
          class="contact-intro-image"
          src="{{ asset('assets/insight/hr-portfolio-contact.jpg') }}"
          alt="Bhumibol Bridge over the Chao Phraya River at sunset"
          width="3500"
          height="2329"
          loading="lazy"
        >
        <div class="contact-intro-shade" aria-hidden="true"></div>

        <div class="contact-intro-copy">
          <h2 id="contact-title" class="reveal" data-i18n="contact.title">Have a question?</h2>
          <p class="contact-description reveal" data-i18n="contact.desc">Contact HR or IT for support.</p>
        </div>

        <div class="contact-intro-action reveal">
          <p data-i18n="contact.prompt">Enter your email.</p>
          <form class="contact-email-form" id="contactEmailForm" data-recipient="pumiput.it@supavut.com" data-subject="Contact from Insight website" data-message="Hello Insight team, my email is">
            <label class="sr-only" for="contactEmail">Your email address</label>
            <input id="contactEmail" name="email" type="email" placeholder="Your Email Address" autocomplete="email" required data-i18n-placeholder="contact.placeholder">
            <button type="submit" title="Open email to get in touch" aria-label="Open email to get in touch">
              <svg aria-hidden="true" viewBox="0 0 24 24">
                <path d="m22 2-7 20-4-9-9-4Z"></path>
                <path d="M22 2 11 13"></path>
              </svg>
            </button>
          </form>
        </div>
      </section>

      <div class="footer-details">
        <div class="footer-detail">
          <span data-i18n="footer.address">Address</span>
          <strong>Supavut Industry, Thailand</strong>
        </div>
        <div class="footer-detail">
          <span>HR</span>
          <strong><a href="mailto:hr.manager@supavut.com">hr.manager@supavut.com</a></strong>
        </div>
        <div class="footer-detail">
          <span>IT</span>
          <strong><a href="mailto:pumiput.it@supavut.com">pumiput.it@supavut.com</a></strong>
        </div>
        <div class="footer-detail">
          <span>Insight</span>
          <strong>SUPAVUT INSIGHT {{ date('Y') }}</strong>
        </div>
      </div>

      <div class="footer-bottom">
        <span>© {{ date('Y') }} Supavut Industry</span>
        <a class="footer-top-link" href="#home" title="Back to top" aria-label="Back to top">
          <svg aria-hidden="true" viewBox="0 0 24 24">
            <path d="m18 15-6-6-6 6"></path>
          </svg>
        </a>
      </div>
    </footer>
  </main>

  <script>
    'use strict';

    (function () {
      var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var languageStoreKey = 'insight_lang';
      var activeRotatingWords = ['ASSESSMENT', 'TIME & LEAVE', 'PAPERLESS'];
      var copy = {
        en: {
          rotatingWords: ['ASSESSMENT', 'TIME & LEAVE', 'PAPERLESS'],
          text: {
            'nav.home': 'Home',
            'nav.login': 'Login',
            'nav.contact': 'Contact',
            'hero.kicker': 'HR Work Center',
            'hero.sr': 'Support for assessment, time and leave, and paperless people operations.',
            'hero.fixed': 'Support for',
            'hero.copy': 'A system for managing employees and organizational documents.',
            'hero.cta': 'Enter SUPAVUT INSIGHT',
            'definition.kicker': 'Definition of SUPAVUT INSIGHT',
            'definition.title': 'What is SUPAVUT INSIGHT?',
            'definition.p1': 'SUPAVUT INSIGHT brings HR work into one system, from documents and employee assessment to time and leave workflows, 5S area management, and OKR.',
            'definition.p2': 'The system is designed to reduce repeated work, reduce paper use, remove unnecessary steps, and make HR work clearer, traceable, and faster.',
            'flow.title': 'Benefits of Insight',
            'flow.s1.t': 'Enter SUPAVUT INSIGHT',
            'flow.s1.d': '',
            'flow.s2.t': 'Centralize data',
            'flow.s2.d': 'All data stays in one place.',
            'flow.s3.t': 'Manage documents',
            'flow.s3.d': 'Documents become digital.',
            'flow.s4.t': 'Reduce steps',
            'flow.s4.d': 'Cut repeated work and paper routing.',
            'flow.s5.t': 'Track status',
            'flow.s5.d': 'See progress clearly.',
            'flow.s6.t': 'Summarize reports',
            'flow.s6.d': 'Turn data into an easy-to-understand overview.',
            'flow.result.t': 'See results',
            'flow.result.d': '',
            'contact.title': 'Have a question?',
            'contact.desc': 'Contact HR or IT for support.',
            'contact.prompt': 'Enter your email.',
            'contact.placeholder': 'Your Email Address',
            'footer.address': 'Address'
          }
        },
        th: {
          rotatingWords: ['ประเมินผลพนักงาน', 'เวลาและการลา', 'ลดการใช้กระดาษ'],
          text: {
            'nav.home': 'หน้าแรก',
            'nav.login': 'เข้าสู่ระบบ',
            'nav.contact': 'ติดต่อ',
            'hero.kicker': 'ศูนย์กลางงาน HR',
            'hero.sr': 'ระบบสนับสนุนการประเมินผลพนักงาน เวลาและการลา และการลดการใช้กระดาษ',
            'hero.fixed': 'สนับสนุนการ',
            'hero.copy': 'ระบบสำหรับจัดการข้อมูลพนักงานและเอกสารภายในองค์กร',
            'hero.cta': 'เข้าสู่ SUPAVUT INSIGHT',
            'definition.kicker': 'นิยามของ SUPAVUT INSIGHT',
            'definition.title': 'SUPAVUT INSIGHT คืออะไร?',
            'definition.p1': 'SUPAVUT INSIGHT คือแพลตฟอร์มที่ช่วยรวมงาน HR ไว้ในระบบเดียว ตั้งแต่เอกสาร การประเมินพนักงาน งานเวลาและการลา การจัดการพื้นที่ 5ส ไปจนถึง OKR',
            'definition.p2': 'ระบบถูกออกแบบมาเพื่อลดงานซ้ำ ลดการใช้กระดาษ ลดขั้นตอนที่ไม่จำเป็น และทำให้งาน HR ชัดเจน ตรวจสอบได้ และทำงานได้รวดเร็วขึ้น',
            'flow.title': 'ประโยชน์ของระบบ Insight',
            'flow.s1.t': 'เข้าสู่ SUPAVUT INSIGHT',
            'flow.s1.d': '',
            'flow.s2.t': 'รวมข้อมูล',
            'flow.s2.d': 'รวมข้อมูลอยู่ในที่เดียว',
            'flow.s3.t': 'จัดการเอกสาร',
            'flow.s3.d': 'เอกสารเปลี่ยนเป็นดิจิทัล',
            'flow.s4.t': 'ลดขั้นตอน',
            'flow.s4.d': 'ลดงานซ้ำและการเดินเอกสาร',
            'flow.s5.t': 'ติดตามสถานะ',
            'flow.s5.d': 'เห็นความคืบหน้าได้ชัดเจน',
            'flow.s6.t': 'สรุปรายงาน',
            'flow.s6.d': 'เปลี่ยนข้อมูลเป็นภาพรวมที่เข้าใจง่าย',
            'flow.result.t': 'เห็นผลลัพธ์',
            'flow.result.d': '',
            'contact.title': 'หากมีข้อสงสัย',
            'contact.desc': 'สามารถติดต่อ HR หรือ IT ได้',
            'contact.prompt': 'กรอกอีเมลของคุณ',
            'contact.placeholder': 'อีเมลของคุณ',
            'footer.address': 'ที่อยู่'
          }
        },
        my: {
          rotatingWords: ['ဝန်ထမ်းအကဲဖြတ်ခြင်း', 'အချိန်နှင့် ခွင့်', 'စာရွက်လျှော့ချခြင်း'],
          text: {
            'nav.home': 'ပင်မ',
            'nav.login': 'ဝင်ရန်',
            'nav.contact': 'ဆက်သွယ်ရန်',
            'hero.kicker': 'HR အလုပ်ဗဟို',
            'hero.sr': 'ဝန်ထမ်းအကဲဖြတ်ခြင်း၊ အချိန်နှင့် ခွင့်၊ စာရွက်လျှော့ချ HR လုပ်ငန်းများအတွက် စနစ်',
            'hero.fixed': 'ပံ့ပိုးသည်',
            'hero.copy': 'ဝန်ထမ်းအချက်အလက်နှင့် အဖွဲ့အစည်းအတွင်း စာရွက်စာတမ်းများကို စီမံရန် စနစ်',
            'hero.cta': 'SUPAVUT INSIGHT ဝင်ရန်',
            'definition.kicker': 'SUPAVUT INSIGHT အဓိပ္ပါယ်',
            'definition.title': 'SUPAVUT INSIGHT ဆိုတာဘာလဲ?',
            'definition.p1': 'SUPAVUT INSIGHT သည် စာရွက်စာတမ်း၊ ဝန်ထမ်းအကဲဖြတ်ခြင်း၊ အချိန်နှင့် ခွင့်လုပ်ငန်းစဉ်၊ 5S ဧရိယာစီမံခြင်းမှ OKR အထိ HR အလုပ်များကို စနစ်တစ်ခုတည်းတွင် စုစည်းပေးသော ပလက်ဖောင်းဖြစ်သည်။',
            'definition.p2': 'ဤစနစ်သည် ထပ်နေသောအလုပ်များကို လျှော့ချရန်၊ စာရွက်အသုံးပြုမှုကို လျှော့ချရန်၊ မလိုအပ်သောအဆင့်များကို ဖယ်ရှားရန်နှင့် HR အလုပ်များကို ပိုရှင်းလင်း၊ စစ်ဆေးနိုင်၊ ပိုမြန်စေရန် ဒီဇိုင်းပြုထားသည်။',
            'flow.title': 'Insight စနစ်၏ အကျိုးကျေးဇူးများ',
            'flow.s1.t': 'SUPAVUT INSIGHT ဝင်ရန်',
            'flow.s1.d': '',
            'flow.s2.t': 'အချက်အလက်စုစည်းခြင်း',
            'flow.s2.d': 'အချက်အလက်များကို တစ်နေရာတည်း စုစည်းထားသည်',
            'flow.s3.t': 'စာရွက်စာတမ်း စီမံခြင်း',
            'flow.s3.d': 'စာရွက်စာတမ်းများကို ဒစ်ဂျစ်တယ်သို့ ပြောင်းလဲသည်',
            'flow.s4.t': 'အဆင့်လျှော့ချခြင်း',
            'flow.s4.d': 'ထပ်နေသောအလုပ်နှင့် စာရွက်စာတမ်းသွားလာမှုကို လျှော့ချသည်',
            'flow.s5.t': 'အခြေအနေ ခြေရာခံခြင်း',
            'flow.s5.d': 'တိုးတက်မှုကို ရှင်းလင်းစွာ မြင်နိုင်သည်',
            'flow.s6.t': 'အစီရင်ခံစာ စုစည်းခြင်း',
            'flow.s6.d': 'ဒေတာကို နားလည်လွယ်သော အကျဉ်းချုပ်အဖြစ် ပြောင်းလဲသည်',
            'flow.result.t': 'ရလဒ်မြင်နိုင်ခြင်း',
            'flow.result.d': '',
            'contact.title': 'မေးခွန်းရှိပါသလား',
            'contact.desc': 'HR သို့မဟုတ် IT ကို ဆက်သွယ်နိုင်ပါသည်။',
            'contact.prompt': 'သင့်အီးမေးလ်ကို ထည့်ပါ',
            'contact.placeholder': 'သင့်အီးမေးလ်',
            'footer.address': 'လိပ်စာ'
          }
        }
      };

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
      } else {
        init();
      }

      function init() {
        initLanguage();
        initLoader();
        initHeader();
        initMenu();
        initRotatingHeadline();
        initReveal();
        initFlowProgress();
        initLoginTransition();
        initContactEmail();
        if (!reduceMotion) initImageDrift();
      }

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

        document.querySelectorAll('[data-lang-option]').forEach(function (node) {
          node.setAttribute('aria-current', node.getAttribute('data-lang-option') === lang ? 'true' : 'false');
        });

        document.querySelectorAll('[data-language-current-flag]').forEach(function (node) {
          var src = node.getAttribute('data-flag-src-' + lang);
          if (src) node.setAttribute('src', src);
        });

        activeRotatingWords = current.rotatingWords.slice();
        if (window.insightSetRotatingWords) window.insightSetRotatingWords(activeRotatingWords);

        try { localStorage.setItem(languageStoreKey, lang); } catch (e) {}
      }

      function initLoader() {
        var loader = document.querySelector('.page-loader');
        var revealPage = function () {
          document.documentElement.classList.add('is-ready');
          if (!loader) return;
          loader.classList.add('is-hidden');
        };

        window.addEventListener('load', revealPage, { once: true });
        window.setTimeout(revealPage, 1400);
      }

      function initLoginTransition() {
        var loader = document.querySelector('.page-loader');
        var loginPath = new URL('{{ route('login') }}', window.location.href).pathname;
        if (!loader) return;

        document.querySelectorAll('a[href]').forEach(function (link) {
          var target;
          try { target = new URL(link.href, window.location.href); } catch (e) { return; }
          if (target.pathname !== loginPath) return;

          link.addEventListener('click', function (event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target) return;
            event.preventDefault();
            loader.classList.remove('is-hidden');
            loader.classList.add('is-active');
            window.setTimeout(function () { window.location.href = link.href; }, 520);
          });
        });
      }

      function initHeader() {
        var header = document.getElementById('siteHeader');
        if (!header) return;

        var ticking = false;
        var update = function () {
          header.classList.toggle('is-scrolled', window.scrollY > 24);
          ticking = false;
        };

        window.addEventListener('scroll', function () {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(update);
        }, { passive: true });

        update();
      }

      function initMenu() {
        var button = document.getElementById('menuButton');
        var menu = document.getElementById('mobileMenu');
        if (!button || !menu) return;

        var setOpen = function (open) {
          button.classList.toggle('is-open', open);
          menu.classList.toggle('is-open', open);
          button.setAttribute('aria-expanded', String(open));
          button.setAttribute('aria-label', open ? button.dataset.closeLabel : button.dataset.openLabel);
        };

        button.addEventListener('click', function () {
          setOpen(!menu.classList.contains('is-open'));
        });

        menu.querySelectorAll('a').forEach(function (link) {
          link.addEventListener('click', function () { setOpen(false); });
        });

        document.addEventListener('click', function (event) {
          if (!menu.classList.contains('is-open')) return;
          if (button.contains(event.target) || menu.contains(event.target)) return;
          setOpen(false);
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') setOpen(false);
        });
      }

      function initRotatingHeadline() {
        var slot = document.querySelector('[data-rotating-words]');
        if (!slot) return;

        var clip = slot.querySelector('.rotating-word-clip');
        var wordElement = slot.querySelector('.rotating-word');
        var words = activeRotatingWords.length ? activeRotatingWords : slot.dataset.rotatingWords.split(',').map(function (word) {
          return word.trim();
        }).filter(Boolean);
        var currentIndex = 0;
        var cycleTimer;

        if (!clip || !wordElement || !words.length) return;

        var measureWord = function () {
          clip.style.setProperty('--rotating-word-width', Math.ceil(clip.scrollWidth) + 'px');
        };

        var revealWord = function () {
          measureWord();
          window.requestAnimationFrame(function () { clip.classList.add('is-visible'); });
        };

        var showNextWord = function () {
          clip.classList.remove('is-visible');
          cycleTimer = window.setTimeout(function () {
            currentIndex = (currentIndex + 1) % words.length;
            wordElement.textContent = words[currentIndex];
            revealWord();
            cycleTimer = window.setTimeout(showNextWord, 2200);
          }, 800);
        };

        var start = function () {
          window.clearTimeout(cycleTimer);
          words = activeRotatingWords.length ? activeRotatingWords : words;
          currentIndex = 0;
          wordElement.textContent = words[currentIndex];
          revealWord();
          if (!reduceMotion) cycleTimer = window.setTimeout(showNextWord, 2200);
        };

        window.insightSetRotatingWords = function (nextWords) {
          if (!nextWords || !nextWords.length) return;
          words = nextWords.slice();
          start();
        };

        if (document.fonts && document.fonts.ready) {
          document.fonts.ready.then(start);
        } else {
          start();
        }

        window.addEventListener('resize', measureWord, { passive: true });
      }

      function initReveal() {
        var elements = document.querySelectorAll('.reveal');
        if (!elements.length) return;

        if (reduceMotion || !('IntersectionObserver' in window)) {
          elements.forEach(function (element) { element.classList.add('is-visible'); });
          return;
        }

        var observer = new IntersectionObserver(function (entries, currentObserver) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            currentObserver.unobserve(entry.target);
          });
        }, { threshold: .16, rootMargin: '0px 0px -8% 0px' });

        elements.forEach(function (element) { observer.observe(element); });
        window.setTimeout(function () {
          elements.forEach(function (element) { element.classList.add('is-visible'); });
        }, 2200);
      }

      function initFlowProgress() {
        var track = document.querySelector('[data-flow-track]');
        var path = track && track.querySelector('.route-path-progress');
        var tailPath = track && track.querySelector('.route-path-tail-progress');
        var steps = track ? Array.prototype.slice.call(track.querySelectorAll('.flow-step')) : [];
        if (!track || !path) return;

        var routeLength = 0;
        var tailLength = 0;
        try {
          routeLength = path.getTotalLength();
        } catch (e) {
          routeLength = 0;
        }
        try {
          tailLength = tailPath ? tailPath.getTotalLength() : 0;
        } catch (e) {
          tailLength = 0;
        }

        if (routeLength > 0) {
          path.style.strokeDasharray = routeLength;
          path.style.strokeDashoffset = routeLength;
          path.setAttribute('stroke-dasharray', routeLength);
          path.setAttribute('stroke-dashoffset', routeLength);
        }
        if (tailLength > 0) {
          tailPath.style.strokeDasharray = tailLength;
          tailPath.style.strokeDashoffset = tailLength;
          tailPath.setAttribute('stroke-dasharray', tailLength);
          tailPath.setAttribute('stroke-dashoffset', tailLength);
        }

        var setRouteProgress = function (pct) {
          track.style.setProperty('--flow-progress', pct.toFixed(4));

          if (routeLength > 0) {
            var offset = (routeLength * (1 - pct)).toFixed(2);
            path.style.strokeDashoffset = offset;
            path.setAttribute('stroke-dashoffset', offset);
          }

          if (tailLength > 0) {
            var tailPct = Math.max(0, Math.min(1, (pct - .899) / .101));
            var tailOffset = (tailLength * (1 - tailPct)).toFixed(2);
            tailPath.style.strokeDashoffset = tailOffset;
            tailPath.setAttribute('stroke-dashoffset', tailOffset);
          }
        };

        if (reduceMotion) {
          setRouteProgress(1);
          steps.forEach(function (step, index) {
            step.classList.add('is-visible');
            step.classList.toggle('is-current', index === steps.length - 1);
          });
          track.classList.add('is-flow-ready');
          return;
        }

        var routeStops = [0, .194, .369, .549, .726, .899, 1];
        var thresholds = steps.map(function (_step, index) {
          var stop = routeStops[index] === undefined ? index / Math.max(1, steps.length - 1) : routeStops[index];
          return Math.max(.03, stop - .035);
        });
        var ticking = false;

        var update = function () {
          var rect = track.getBoundingClientRect();
          var startLine = window.innerHeight * .78;
          var distance = Math.max(1, rect.height);
          var pct = Math.max(0, Math.min(1, (startLine - rect.top) / distance));
          var currentIndex = -1;

          steps.forEach(function (step, index) {
            var isVisible = pct >= thresholds[index];
            step.classList.toggle('is-visible', isVisible);
            if (isVisible) currentIndex = index;
          });

          var routePct = currentIndex >= 0
            ? Math.max(pct, routeStops[currentIndex] === undefined ? pct : routeStops[currentIndex])
            : pct;

          setRouteProgress(routePct);

          steps.forEach(function (step, index) {
            step.classList.toggle('is-current', index === currentIndex);
          });
          ticking = false;
        };

        var requestUpdate = function () {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(update);
        };

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate, { passive: true });
        update();
        track.classList.add('is-flow-ready');
      }

      function initImageDrift() {
        var images = Array.prototype.slice.call(document.querySelectorAll('.journal-hero-image'));
        if (!images.length) return;

        var ticking = false;
        var update = function () {
          var viewportHeight = window.innerHeight;
          images.forEach(function (image) {
            var section = image.parentElement;
            var rect = section.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > viewportHeight) return;
            var centerOffset = rect.top + rect.height / 2 - viewportHeight / 2;
            var movement = Math.max(-36, Math.min(36, centerOffset * -.035));
            image.style.transform = 'translate3d(0, ' + movement.toFixed(2) + 'px, 0)';
          });
          ticking = false;
        };

        var requestUpdate = function () {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(update);
        };

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate, { passive: true });
        update();
      }

      function initContactEmail() {
        var form = document.getElementById('contactEmailForm');
        var input = document.getElementById('contactEmail');
        if (!form || !input) return;

        form.addEventListener('submit', function (event) {
          event.preventDefault();
          if (!input.reportValidity()) return;

          var recipient = form.dataset.recipient;
          var subject = form.dataset.subject;
          var body = form.dataset.message + ' ' + input.value.trim() + '.\\n\\n';
          window.location.href = 'mailto:' + recipient + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
        });
      }
    })();
  </script>
</body>
</html>
