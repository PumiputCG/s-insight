@extends('layouts.portal')

@section('title', 'Time & Leave Approval Settings')
@section('topbar-title')<span data-i18n="nav.sysSettings">ตั้งค่าระบบ</span>@endsection

@section('content')
  <style>
    .ot-settings {
      width: min(100%, 76rem);
      margin: 0 auto;
      display: grid;
      gap: 1rem;
    }

    .ot-settings-head {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 1rem;
      padding: .4rem 0 1rem;
      border-bottom: 1px solid var(--line-light);
    }
    .ot-settings-head h1 {
      margin-top: .25rem;
      font-family: "Montserrat", var(--font-body);
      font-size: clamp(1.7rem, 3.5vw, 2.75rem);
      letter-spacing: -.04em;
      line-height: 1.08;
    }
    .ot-settings-head p { max-width: 44rem; color: var(--muted-light); }
    .ot-eyebrow { color: var(--moss); font-size: .72rem; font-weight: 800; letter-spacing: .14em; }

    /* ห้ามใส่ overflow: clip ที่นี่ เพราะจะตัดกล่องผลค้นหา (.ot-search-results)
       ที่เป็น position: absolute แล้วยื่นพ้นขอบ section จนมองไม่เห็นรายการ */
    .ot-section {
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel);
    }
    /* มุมมนของ section ยังคงอยู่ได้โดยไม่ต้อง clip ทั้งกล่อง */
    .ot-section > .ot-section-summary { border-radius: 4px 10px 0 0; }
    .ot-section:not([open]) > .ot-section-summary { border-radius: 4px; }

    /* ── แยกสายตา: หัวข้อสิทธิ์ OT กับสิทธิ์ลา 75 ─────────────────────────
       สี่หัวข้อ (2-5) หน้าตาเหมือนกันเป๊ะ ต่างแค่ป้ายในวงเล็บ admin จึงอ่านสลับกันง่าย
       ใช้สีชุดเดียวกับปฏิทินหน้าดาวน์โหลด: OT = ฟ้า · ลา 75 = ชมพู
       ผสมกับ token พื้นเดิมด้วย color-mix จึงยังอ่านง่ายทั้งธีมมืดและสว่าง */
    .ot-role-section[data-module="ot"] { --ot-scope-tint: #5aa9e6; }
    .ot-role-section[data-module="leave"] { --ot-scope-tint: #f08fb4; }
    .ot-role-section {
      border-color: color-mix(in srgb, var(--ot-scope-tint) 32%, var(--line-light));
      border-left: 4px solid var(--ot-scope-tint);
      background: color-mix(in srgb, var(--ot-scope-tint) 7%, var(--panel));
    }

    .ot-role-section > .ot-section-summary { background: color-mix(in srgb, var(--ot-scope-tint) 7%, transparent); }
    .ot-role-section > .ot-section-body { border-top-color: color-mix(in srgb, var(--ot-scope-tint) 28%, var(--line-light)); }
    /* ป้ายในวงเล็บย้อมสีเดียวกับการ์ด เป็นตัวยืนยันซ้ำว่าอยู่ชุดไหน */
    .ot-role-section .ot-section-scope { color: color-mix(in srgb, var(--ot-scope-tint) 70%, var(--light-text)); }
    .ot-role-section .ot-section-count { background: color-mix(in srgb, var(--ot-scope-tint) 16%, var(--hover-soft)); }
    .ot-section-summary {
      min-height: 5.25rem;
      display: grid;
      grid-template-columns: 2rem minmax(0, 1fr) auto 1.15rem;
      align-items: center;
      gap: .85rem;
      padding: 1rem 1.15rem;
      list-style: none;
    }
    .ot-section-summary::-webkit-details-marker, .ot-company > summary::-webkit-details-marker { display: none; }
    /* เลขหัวข้อใช้เขียว moss เดียวกับคำว่า APPROVAL บนแบรนด์มุมซ้ายบน
       (--near-black คือสีตัวอักษรที่ตัดกับ moss ของแต่ละธีม เหมือนที่ .ot-button ใช้) */
    .ot-section-number {
      width: 2rem;
      height: 2rem;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: var(--moss);
      color: var(--near-black);
      font-size: .78rem;
      font-weight: 800;
    }
    .ot-section-title { min-width: 0; display: grid; gap: .15rem; }
    .ot-section-title strong { font-size: 1rem; }
    /* ป้ายบอกว่าหัวข้อนี้เป็นสิทธิ์ของระบบไหน — มีสองชุดหน้าตาเหมือนกัน */
    .ot-section-scope { margin-left: .35rem; color: var(--moss); font-size: .8rem; font-weight: 700; }
    .ot-section-title small { color: var(--muted-light); font-size: .82rem; line-height: 1.4; }
    .ot-section-count {
      min-width: 2.8rem;
      padding: .25rem .55rem;
      border-radius: 999px;
      background: var(--hover-soft);
      color: var(--muted-light);
      font-size: .76rem;
      font-weight: 700;
      text-align: center;
    }
    .ot-caret { width: 1.05rem; fill: none; stroke: currentColor; stroke-width: 1.8; transition: transform .2s var(--ease-out); }
    .ot-section[open] > .ot-section-summary .ot-caret, .ot-company[open] > summary .ot-caret { transform: rotate(180deg); }
    .ot-section-body { padding: 0 1.15rem 1.15rem; border-top: 1px solid var(--line-light); }

    .ot-admin-tools { display: grid; grid-template-columns: minmax(0, 1fr) minmax(18rem, .8fr); gap: 1rem; padding-top: 1rem; }
    .ot-members { display: flex; flex-wrap: wrap; align-content: start; gap: .55rem; }
    .ot-member {
      display: grid;
      grid-template-columns: 2.35rem minmax(0, 1fr) auto;
      align-items: center;
      gap: .65rem;
      min-width: min(100%, 19rem);
      padding: .55rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
    }
    .ot-member-copy, .ot-assignee-copy, .ot-result-copy { min-width: 0; display: grid; gap: .08rem; }
    .ot-member-copy strong, .ot-assignee-copy strong, .ot-result-copy strong { overflow: hidden; font-size: .86rem; text-overflow: ellipsis; white-space: nowrap; }
    .ot-member-copy small, .ot-assignee-copy small, .ot-result-copy small { overflow: hidden; color: var(--muted-light); font-size: .73rem; text-overflow: ellipsis; white-space: nowrap; }

    .ot-avatar {
      width: 2.35rem;
      height: 2.35rem;
      display: grid;
      place-items: center;
      flex: 0 0 auto;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      background: var(--hover-soft);
      overflow: hidden;
    }
    .ot-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ot-avatar svg { width: 1.25rem; fill: none; stroke: currentColor; stroke-width: 1.6; opacity: .56; }

    .ot-icon-button {
      width: 2.1rem;
      height: 2.1rem;
      display: grid;
      place-items: center;
      border: 1px solid transparent;
      border-radius: 4px;
      background: transparent;
      color: var(--muted-light);
    }
    .ot-icon-button:hover { border-color: color-mix(in srgb, var(--danger) 35%, transparent); color: var(--danger); }
    .ot-icon-button svg { width: 1rem; fill: none; stroke: currentColor; stroke-width: 1.7; }

    .ot-user-search { position: relative; }
    .ot-search {
      min-height: 2.7rem;
      display: flex;
      align-items: center;
      gap: .55rem;
      padding: 0 .75rem;
      border: 1px solid var(--line-strong);
      border-radius: 4px;
      background: var(--panel-soft);
    }
    .ot-search:focus-within { border-color: var(--moss); }
    .ot-search svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; opacity: .6; }
    .ot-search input { width: 100%; border: 0; outline: 0; background: transparent; }
    .ot-search-results {
      position: absolute;
      z-index: 12;
      top: calc(100% + .35rem);
      right: 0;
      left: 0;
      max-height: 20rem;
      padding: .35rem;
      border: 1px solid var(--line-strong);
      border-radius: 4px;
      background: var(--menu-bg);
      box-shadow: 0 1rem 2.5rem rgb(0 0 0 / 22%);
      overflow-y: auto;
    }
    .ot-search-results:empty { display: none; }

    .ot-result {
      width: 100%;
      display: grid;
      grid-template-columns: 2.35rem minmax(0, 1fr) auto;
      align-items: center;
      gap: .65rem;
      padding: .55rem;
      border: 0;
      border-radius: 4px;
      background: transparent;
      text-align: left;
    }
    .ot-result:hover, .ot-result:focus-visible { background: var(--hover-soft-2); }
    .ot-result-action { color: var(--moss); font-size: .75rem; font-weight: 800; }

    .ot-position-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .55rem; padding-top: 1rem; }
    .ot-position-tools .ot-search { min-width: min(100%, 19rem); margin-right: auto; }
    .ot-button {
      min-height: 2.45rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .4rem;
      padding: .5rem .8rem;
      border: 1px solid var(--moss);
      border-radius: 4px;
      background: var(--moss);
      color: var(--near-black);
      font-weight: 700;
    }
    .ot-button-secondary { border-color: var(--line-strong); background: transparent; color: var(--light-text); }
    .ot-button:hover { filter: brightness(1.06); }
    .ot-button:disabled { opacity: .48; }
    .ot-position-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: .5rem;
      max-height: 29rem;
      margin-top: .85rem;
      overflow-y: auto;
    }
    .ot-position {
      min-width: 0;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr) auto;
      align-items: center;
      gap: .55rem;
      padding: .65rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
    }
    .ot-position:has(input:checked) { border-color: color-mix(in srgb, var(--moss) 58%, transparent); background: color-mix(in srgb, var(--moss) 9%, transparent); }
    .ot-position input { width: 1rem; height: 1rem; accent-color: var(--moss); }
    .ot-position-copy { min-width: 0; display: grid; }
    .ot-position-copy strong { overflow: hidden; font-size: .8rem; text-overflow: ellipsis; white-space: nowrap; }
    .ot-position-copy small, .ot-position-count { color: var(--muted-light); font-size: .7rem; }

    .ot-company-list { display: grid; gap: .7rem; padding-top: 1rem; }
    .ot-company { border: 1px solid var(--line-light); border-radius: 4px; overflow: clip; }
    .ot-company > summary {
      min-height: 3.65rem;
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto 1rem;
      align-items: center;
      gap: .75rem;
      padding: .75rem .85rem;
      list-style: none;
      background: var(--panel-soft);
    }
    .ot-company > summary > span:first-child { min-width: 0; display: grid; }
    .ot-company > summary strong { font-size: .88rem; }
    .ot-company > summary small { color: var(--muted-light); font-size: .69rem; }
    .ot-company-count { color: var(--muted-light); font-size: .75rem; }
    .ot-department-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));
      gap: .4rem;
      margin: 0;
      padding: .65rem;
      list-style: none;
    }
    .ot-department-row {
      width: 100%;
      min-height: 7.3rem;
      display: grid;
      align-content: space-between;
      gap: .7rem;
      padding: .75rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: transparent;
      color: var(--light-text);
      text-align: left;
      transition: border-color .18s var(--ease-out), background-color .18s var(--ease-out);
    }
    .ot-department-row:hover, .ot-department-row:focus-visible {
      border-color: var(--moss);
      background: var(--hover-soft);
    }
    .ot-department-main, .ot-department-assignee { min-width: 0; display: flex; align-items: center; gap: .6rem; }
    .ot-department-main { position: relative; justify-content: center; }
    .ot-department-name { min-width: 0; display: grid; gap: .08rem; text-align: center; }
    .ot-department-name strong { overflow: hidden; font-size: .85rem; text-overflow: ellipsis; white-space: nowrap; }
    .ot-department-count { position: absolute; top: 0; right: 0; color: var(--moss); font-size: .74rem; font-weight: 800; }
    .ot-department-assignee {
      justify-content: flex-start;
      padding-top: .55rem;
      border-top: 1px solid var(--line-light);
      color: var(--muted-light);
    }
    .ot-department-assignee.is-empty { justify-content: center; }
    .ot-department-assignee.has-person { color: var(--light-text); }
    .ot-department-assignee .ot-avatar { width: 2rem; height: 2rem; }
    /* Foreman มีได้หลายคน โชว์เป็นรูปซ้อนเหลื่อมกันจะนับหัวได้ทันทีด้วยตา
       ดีกว่าโชว์ชื่อคนแรกแล้วต่อท้ายว่า +1 ซึ่งอ่านแล้วไม่รู้ว่ามีกี่คน */
    .ot-avatar-stack { display: flex; align-items: center; flex: 1 1 auto; padding-left: .15rem; }
    .ot-avatar-stack .ot-avatar { margin-left: -.5rem; border: 2px solid var(--panel); }
    .ot-avatar-stack .ot-avatar:first-child { margin-left: 0; }
    .ot-avatar-more {
      min-width: 2rem; height: 2rem; display: grid; place-items: center; flex: 0 0 auto;
      margin-left: -.5rem; padding: 0 .3rem; border: 2px solid var(--panel); border-radius: 999px;
      background: var(--hover-soft); color: var(--muted-light); font-size: .68rem; font-weight: 800;
    }
    .ot-department-assignee .ot-assignee-copy { flex: 1 1 auto; }
    .ot-department-assignee.is-empty .ot-assignee-copy { flex: 0 0 auto; text-align: center; }
    .ot-department-arrow { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; }

    .ot-assignment-modal {
      position: fixed;
      z-index: 90;
      inset: 0;
      display: grid;
      place-items: center;
      padding: 1.25rem;
      background: var(--overlay-bg);
      backdrop-filter: blur(4px);
    }
    .ot-assignment-modal[hidden] { display: none; }
    .ot-assignment-dialog {
      width: min(100%, 48rem);
      max-height: min(88vh, 52rem);
      display: flex;
      flex-direction: column;
      border: 1px solid var(--line-strong);
      border-radius: 5px;
      background: var(--menu-bg);
      box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 30%);
      overflow: hidden;
    }
    .ot-assignment-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.15rem;
      border-bottom: 1px solid var(--line-light);
    }
    .ot-assignment-heading { min-width: 0; display: grid; gap: .18rem; }
    .ot-assignment-heading small { color: var(--moss); font-size: .7rem; font-weight: 800; letter-spacing: .08em; }
    .ot-assignment-heading strong { overflow: hidden; font-size: 1.05rem; text-overflow: ellipsis; white-space: nowrap; }
    .ot-assignment-close {
      width: 2.45rem;
      height: 2.45rem;
      display: grid;
      place-items: center;
      flex: 0 0 auto;
      border: 1px solid transparent;
      border-radius: 50%;
      background: transparent;
      color: var(--muted-light);
    }
    .ot-assignment-close:hover { border-color: var(--line-strong); background: var(--hover-soft); color: var(--light-text); }
    .ot-assignment-close svg { width: 1.15rem; fill: none; stroke: currentColor; stroke-width: 1.8; }
    .ot-assignment-body { min-height: 0; padding: 1rem 1.15rem 1.15rem; overflow-y: auto; }
    .ot-current-block { display: grid; gap: .4rem; margin-bottom: .85rem; }
    .ot-current-label, .ot-list-label { color: var(--muted-light); font-size: .73rem; font-weight: 800; letter-spacing: .03em; }
    /* กล่องนี้เป็น "รายการ" แล้ว เพราะ Foreman มีได้หลายคนต่อแผนก
       Supervisor ยังมีคนเดียวแต่ใช้โครงเดียวกันเพื่อไม่ต้องแยกโค้ดสองทาง */
    .ot-current-assignee { display: grid; gap: .5rem; }
    .ot-assignee-item {
      min-height: 4.2rem;
      display: grid;
      grid-template-columns: 3rem minmax(0, 1fr) auto;
      align-items: center;
      gap: .75rem;
      padding: .6rem;
      border: 1px solid var(--line-strong);
      border-radius: 4px;
      background: var(--panel-soft);
    }
    .ot-assignee-item .ot-avatar { width: 3rem; height: 3rem; }
    .ot-assignee-item.is-empty { grid-template-columns: 1fr; justify-items: center; }
    .ot-assignee-item.is-empty .ot-assignee-copy { text-align: center; }
    .ot-current-actions { display: flex; align-items: center; gap: .4rem; }
    .ot-shift-select { min-height: 2.1rem; padding: .25rem .45rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); color: var(--light-text); font-size: .73rem; font-weight: 650; }
    .ot-shift-select:focus { border-color: var(--moss); outline: 2px solid color-mix(in srgb, var(--moss) 26%, transparent); outline-offset: 1px; }
    .ot-assignment-hint { margin: 0; color: var(--muted-light); font-size: .72rem; line-height: 1.55; }
    .ot-shift-chip { display: inline-flex; align-items: center; padding: .05rem .38rem; border: 1px solid var(--line-light); border-radius: 999px; background: var(--panel); font-size: .66rem; font-weight: 700; }
    .ot-assignment-search { width: 100%; margin-bottom: .85rem; }
    .ot-list-label { display: block; margin: 0 0 .5rem; }
    .ot-employee-results {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .45rem;
    }
    .ot-employee-results .ot-result { min-height: 4.1rem; border: 1px solid var(--line-light); background: var(--panel-soft); }
    .ot-employee-results .ot-result:hover, .ot-employee-results .ot-result:focus-visible { border-color: var(--moss); }
    .ot-result[disabled] { opacity: .5; }
    .ot-empty { padding: .8rem; color: var(--muted-light); font-size: .82rem; text-align: center; }

    /* ── ดูรูปพนักงานแบบขยาย (คลิกที่รูป) ─────────────────── */
    .ot-avatar:has(img) { cursor: zoom-in; }
    .ot-avatar img { transition: transform .18s var(--ease-out); }
    .ot-avatar:has(img):hover img { transform: scale(1.08); }

    .ot-lightbox {
      position: fixed;
      z-index: 120;                 /* ต้องสูงกว่าโมดัลเลือกผู้รับผิดชอบ (90) */
      inset: 0;
      display: grid;
      place-items: center;
      padding: clamp(1rem, 4vw, 3rem);
      background: rgb(0 0 0 / 78%);
      -webkit-backdrop-filter: blur(3px);
      backdrop-filter: blur(3px);
      cursor: zoom-out;
      animation: otLightboxIn .16s var(--ease-out);
    }
    .ot-lightbox[hidden] { display: none; }

    @keyframes otLightboxIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .ot-lightbox-figure {
      max-width: min(100%, 34rem);
      display: grid;
      gap: .75rem;
      justify-items: center;
      margin: 0;
      cursor: default;
      animation: otLightboxZoom .2s var(--ease-out);
    }

    @keyframes otLightboxZoom {
      from { opacity: 0; transform: scale(.94); }
      to { opacity: 1; transform: none; }
    }

    .ot-lightbox-figure img {
      max-width: 100%;
      max-height: min(72vh, 34rem);
      border-radius: 6px;
      background: var(--panel);
      box-shadow: 0 2rem 4rem rgb(0 0 0 / 45%);
      object-fit: contain;
    }

    .ot-lightbox-caption {
      display: grid;
      gap: .1rem;
      color: #fff;
      font-size: .9rem;
      text-align: center;
    }
    .ot-lightbox-caption small { color: rgb(255 255 255 / 68%); font-size: .78rem; }

    .ot-lightbox-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 2.6rem;
      height: 2.6rem;
      display: grid;
      place-items: center;
      border: 1px solid rgb(255 255 255 / 28%);
      border-radius: 50%;
      background: rgb(255 255 255 / 10%);
      color: #fff;
      cursor: pointer;
    }
    .ot-lightbox-close:hover { background: rgb(255 255 255 / 20%); }
    .ot-lightbox-close svg { width: 1.1rem; fill: none; stroke: currentColor; stroke-width: 2; }

    .ot-status {
      position: fixed;
      z-index: 30;
      right: 1.25rem;
      bottom: 1.25rem;
      max-width: min(24rem, calc(100vw - 2rem));
      padding: .7rem .9rem;
      border: 1px solid var(--line-strong);
      border-radius: 4px;
      background: var(--menu-bg);
      box-shadow: 0 1rem 2rem rgb(0 0 0 / 20%);
      font-size: .82rem;
    }
    .ot-status.is-error { border-color: color-mix(in srgb, var(--danger) 55%, transparent); color: var(--danger); }

    @media (max-width: 900px) {
      .ot-admin-tools { grid-template-columns: 1fr; }
      .ot-position-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 620px) {
      .ot-settings-head { align-items: start; flex-direction: column; }
      .ot-section-summary { grid-template-columns: 2rem minmax(0, 1fr) 1rem; }
      .ot-section-count { display: none; }
      .ot-section-title small { white-space: normal; }
      .ot-position-grid, .ot-employee-results, .ot-department-list { grid-template-columns: 1fr; }
      .ot-assignment-modal { align-items: end; padding: .5rem; }
      .ot-assignment-dialog { max-height: 92vh; border-radius: 5px 12px 8px 8px; }
      .ot-current-assignee { grid-template-columns: 2.5rem minmax(0, 1fr); }
      .ot-current-assignee .ot-avatar { width: 2.5rem; height: 2.5rem; }
      .ot-current-actions { grid-column: 1 / -1; justify-content: flex-end; }
      .ot-position-tools .ot-search { width: 100%; margin-right: 0; }
    }
  </style>

  <main class="ot-settings" data-ot-settings>
    <header class="ot-settings-head">
      <span>
        <h1 data-i18n="ot.settings.title">ตั้งค่าระบบ</h1>
        <p data-i18n="ot.settings.subtitle">กำหนดผู้ดูแล ตำแหน่งที่เข้าใช้ และผู้รับผิดชอบ OT/การลาของแต่ละแผนก</p>
      </span>
    </header>

    <details class="ot-section" open>
      <summary class="ot-section-summary">
        <span class="ot-section-number">1</span>
        <span class="ot-section-title">
          <strong data-i18n="ot.settings.adminTitle">ผู้ดูแลระบบ</strong>
          <small data-i18n="ot.settings.adminHelp">จัดการค่าระบบและดาวน์โหลดเอกสาร OT/การลาได้</small>
        </span>
        <span class="ot-section-count">{{ count($members) }}</span>
        <svg class="ot-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
      </summary>
      <div class="ot-section-body ot-admin-tools">
        <div class="ot-members">
          @forelse ($members as $member)
            <article class="ot-member">
              <span class="ot-avatar" aria-hidden="true">
                @if ($member['avatar'])
                  <img src="{{ $member['avatar'] }}" alt="">
                @else
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                @endif
              </span>
              <span class="ot-member-copy">
                <strong><span data-val="th">{{ $member['name_th'] }}</span><span data-val="en">{{ $member['name_en'] }}</span></strong>
                <small>{{ $member['code'] }} · {{ $member['position'] ?: '-' }}</small>
              </span>
              <button class="ot-icon-button" type="button" data-remove-member="{{ $member['id'] }}" data-i18n-aria="ot.common.remove" aria-label="นำออก">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M7 6l1 15h8l1-15M10 11v6M14 11v6"></path></svg>
              </button>
            </article>
          @empty
            <p class="ot-empty" data-i18n="ot.settings.noOtAdmins">ยังไม่มีผู้ดูแลระบบเพิ่มเติม</p>
          @endforelse
        </div>

        <div class="ot-user-search">
          <label class="ot-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <span class="sr-only" data-i18n="ot.settings.searchAdmin">ค้นหาพนักงานเพื่อเพิ่มเป็นผู้ดูแล</span>
            <input type="search" data-admin-search data-i18n-placeholder="ot.settings.searchAdmin" placeholder="ค้นหาพนักงานเพื่อเพิ่มเป็นผู้ดูแล" autocomplete="off">
          </label>
          <div class="ot-search-results" data-admin-results></div>
        </div>
      </div>
    </details>

    {{-- สองชุดแยกกัน: OT (2,3) และการลา (4,5) — คนเดียวเป็นทั้งสองระบบได้ ตั้งค่าคนละที่ --}}
    @include('ot_approval.partials.assignment-section', ['role' => 'foreman', 'module' => 'ot', 'number' => 2])
    @include('ot_approval.partials.assignment-section', ['role' => 'supervisor', 'module' => 'ot', 'number' => 3])
    @include('ot_approval.partials.assignment-section', ['role' => 'foreman', 'module' => 'leave', 'number' => 4])
    @include('ot_approval.partials.assignment-section', ['role' => 'supervisor', 'module' => 'leave', 'number' => 5])

    {{-- ย่อไว้ตั้งแต่เปิดหน้า เพราะรายการตำแหน่งยาว กดหัวข้อเพื่อกาง --}}
    <details class="ot-section">
      <summary class="ot-section-summary">
        <span class="ot-section-number">6</span>
        <span class="ot-section-title">
          <strong data-i18n="ot.settings.positionsTitle">ตำแหน่งที่เข้าใช้ระบบได้</strong>
          <small data-i18n="ot.settings.positionsHelp">หากยังไม่เลือก คนทั่วไปจะเข้าไม่ได้ แต่ Admin, Foreman และ Supervisor ยังเข้าได้</small>
        </span>
        <span class="ot-section-count"><span data-position-selected>{{ count($allowed) }}</span>/{{ count($positions) }}</span>
        <svg class="ot-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
      </summary>
      <div class="ot-section-body">
        <div class="ot-position-tools">
          <label class="ot-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <span class="sr-only" data-i18n="ot.common.searchPosition">ค้นหาตำแหน่ง</span>
            <input type="search" data-position-search data-i18n-placeholder="ot.common.searchPosition" placeholder="ค้นหาตำแหน่ง">
          </label>
          <button class="ot-button ot-button-secondary" type="button" data-position-select-visible data-i18n="ot.common.selectVisible">เลือกที่แสดง</button>
          <button class="ot-button ot-button-secondary" type="button" data-position-clear data-i18n="ot.common.clear">ล้าง</button>
          <button class="ot-button" type="button" data-position-save data-i18n="ot.common.save">บันทึก</button>
        </div>
        <div class="ot-position-grid" data-position-grid>
          @foreach ($positions as $position)
            <label class="ot-position" data-position-item data-search-text="{{ mb_strtolower($position['code'].' '.$position['name_th'].' '.$position['name_en']) }}">
              <input type="checkbox" value="{{ $position['code'] }}" {{ in_array($position['code'], $allowed, true) ? 'checked' : '' }}>
              <span class="ot-position-copy">
                <strong><span data-val="th">{{ $position['name_th'] }}</span><span data-val="en">{{ $position['name_en'] }}</span></strong>
                <small>{{ $position['code'] }}</small>
              </span>
              <span class="ot-position-count">{{ $position['count'] }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </details>

    {{-- หัวข้อ 7: ซ่อนปุ่ม `ขอ OT` ของบางตำแหน่ง — เป็น blacklist ตรงข้ามกับหัวข้อ 6
         ไม่ติ๊ก = ขอ OT ได้ตามปกติ ตำแหน่งใหม่จาก Bplus จึงไม่ถูกบล็อกโดยไม่ตั้งใจ --}}
    <details class="ot-section">
      <summary class="ot-section-summary">
        <span class="ot-section-number">7</span>
        <span class="ot-section-title">
          <strong data-i18n="ot.settings.hiddenRequestTitle">ตำแหน่งที่ไม่ให้ขอ OT</strong>
          <small data-i18n="ot.settings.hiddenRequestHelp">ติ๊กแล้ว Foreman จะไม่เห็นปุ่ม ขอ OT ของตำแหน่งนั้น เช่น Employee with Disabilities · ไม่ติ๊ก = ขอได้ตามปกติ</small>
        </span>
        <span class="ot-section-count"><span data-hidden-position-selected>{{ count($hiddenRequestPositions) }}</span>/{{ count($positions) }}</span>
        <svg class="ot-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
      </summary>
      <div class="ot-section-body">
        <div class="ot-position-tools">
          <label class="ot-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <span class="sr-only" data-i18n="ot.common.searchPosition">ค้นหาตำแหน่ง</span>
            <input type="search" data-hidden-position-search data-i18n-placeholder="ot.common.searchPosition" placeholder="ค้นหาตำแหน่ง">
          </label>
          <button class="ot-button ot-button-secondary" type="button" data-hidden-position-select-visible data-i18n="ot.common.selectVisible">เลือกที่แสดง</button>
          <button class="ot-button ot-button-secondary" type="button" data-hidden-position-clear data-i18n="ot.common.clear">ล้าง</button>
          <button class="ot-button" type="button" data-hidden-position-save data-i18n="ot.common.save">บันทึก</button>
        </div>
        <div class="ot-position-grid" data-hidden-position-grid>
          @foreach ($positions as $position)
            <label class="ot-position" data-hidden-position-item data-search-text="{{ mb_strtolower($position['code'].' '.$position['name_th'].' '.$position['name_en']) }}">
              <input type="checkbox" value="{{ $position['code'] }}" {{ in_array($position['code'], $hiddenRequestPositions, true) ? 'checked' : '' }}>
              <span class="ot-position-copy">
                <strong><span data-val="th">{{ $position['name_th'] }}</span><span data-val="en">{{ $position['name_en'] }}</span></strong>
                <small>{{ $position['code'] }}</small>
              </span>
              <span class="ot-position-count">{{ $position['count'] }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </details>

    {{-- หัวข้อ 8: ซ่อนปุ่ม `ขอลา` ของบางตำแหน่ง — คู่แฝดของหัวข้อ 7 แต่เป็นระบบลา 75
         แยกคีย์กันเพราะบางตำแหน่งขอ OT ไม่ได้แต่ยังลาได้ (และกลับกัน)
         ไม่ติ๊ก = ขอลาได้ตามปกติ ตำแหน่งใหม่จาก Bplus จึงไม่ถูกบล็อกโดยไม่ตั้งใจ --}}
    <details class="ot-section">
      <summary class="ot-section-summary">
        <span class="ot-section-number">8</span>
        <span class="ot-section-title">
          <strong data-i18n="ot.settings.hiddenLeaveTitle">ตำแหน่งที่ไม่ให้ขอลากิจ</strong>
          <small data-i18n="ot.settings.hiddenLeaveHelp">ติ๊กแล้ว Foreman จะไม่เห็นปุ่ม ขอลา ของตำแหน่งนั้น และเลือกในโมดัล ขอลาทั้งหมด ไม่ได้ · ไม่ติ๊ก = ขอได้ตามปกติ</small>
        </span>
        <span class="ot-section-count"><span data-hidden-leave-selected>{{ count($hiddenLeavePositions) }}</span>/{{ count($positions) }}</span>
        <svg class="ot-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
      </summary>
      <div class="ot-section-body">
        <div class="ot-position-tools">
          <label class="ot-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            <span class="sr-only" data-i18n="ot.common.searchPosition">ค้นหาตำแหน่ง</span>
            <input type="search" data-hidden-leave-search data-i18n-placeholder="ot.common.searchPosition" placeholder="ค้นหาตำแหน่ง">
          </label>
          <button class="ot-button ot-button-secondary" type="button" data-hidden-leave-select-visible data-i18n="ot.common.selectVisible">เลือกที่แสดง</button>
          <button class="ot-button ot-button-secondary" type="button" data-hidden-leave-clear data-i18n="ot.common.clear">ล้าง</button>
          <button class="ot-button" type="button" data-hidden-leave-save data-i18n="ot.common.save">บันทึก</button>
        </div>
        <div class="ot-position-grid" data-hidden-leave-grid>
          @foreach ($positions as $position)
            <label class="ot-position" data-hidden-leave-item data-search-text="{{ mb_strtolower($position['code'].' '.$position['name_th'].' '.$position['name_en']) }}">
              <input type="checkbox" value="{{ $position['code'] }}" {{ in_array($position['code'], $hiddenLeavePositions, true) ? 'checked' : '' }}>
              <span class="ot-position-copy">
                <strong><span data-val="th">{{ $position['name_th'] }}</span><span data-val="en">{{ $position['name_en'] }}</span></strong>
                <small>{{ $position['code'] }}</small>
              </span>
              <span class="ot-position-count">{{ $position['count'] }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </details>

    <div class="ot-status" data-ot-status hidden role="status" aria-live="polite"></div>

    {{-- ดูรูปพนักงานแบบขยาย: ใช้ร่วมกันทุกจุดในหน้านี้ รวมทั้งในโมดัลเลือกผู้รับผิดชอบ --}}
    <div class="ot-lightbox" data-ot-lightbox role="dialog" aria-modal="true" aria-label="รูปพนักงาน" hidden>
      <button class="ot-lightbox-close" type="button" data-lightbox-close data-i18n-aria="ot.common.close" aria-label="ปิด">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
      </button>
      <figure class="ot-lightbox-figure">
        <img data-lightbox-image src="" alt="">
        <figcaption class="ot-lightbox-caption" data-lightbox-caption hidden></figcaption>
      </figure>
    </div>
  </main>

  <script>
    'use strict';

    (function () {
      var root = document.querySelector('[data-ot-settings]');
      if (!root) return;

      var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      var endpoints = {
        users: @json(route('ot-approval.settings.users')),
        addMember: @json(route('ot-approval.settings.members.add')),
        removeMemberBase: @json(url('/ot-approval/settings/members')),
        positions: @json(route('ot-approval.settings.positions')),
        hiddenRequestPositions: @json(route('ot-approval.settings.hidden-request-positions')),
        hiddenLeavePositions: @json(route('ot-approval.settings.hidden-leave-positions')),
        employees: @json(route('ot-approval.settings.department-employees')),
        assignments: @json(route('ot-approval.settings.assignments.save')),
        removeAssignmentBase: @json(url('/ot-approval/settings/assignments'))
      };
      // ตัวเลือกป้ายผู้รับผิดชอบมาจาก config: ทั้งหมด / กะเวลา A / กะเวลา B
      // ส่วน "ไม่ระบุกะ" เติมใน UI เพราะบันทึกเป็น null
      var shiftGroups = @json($shiftGroups);
      var status = root.querySelector('[data-ot-status]');
      var statusTimer = 0;
      var allEmployees = null;
      var allEmployeesPromise = null;

      function text(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
      }

      function localized(item, field) {
        if (window.__portalLang && window.__portalLang.field) return window.__portalLang.field(item, field, item.code || '-');
        return item[field + '_th'] || item[field + '_en'] || item[field] || item.code || '-';
      }

      function optionalLocalized(item, field) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return item[field + '_' + lang] || item[field + '_en'] || item[field + '_th'] || item[field] || '';
      }

      function showStatus(message, isError) {
        window.clearTimeout(statusTimer);
        status.textContent = message;
        status.hidden = false;
        status.classList.toggle('is-error', Boolean(isError));
        statusTimer = window.setTimeout(function () { status.hidden = true; }, 3800);
      }

      async function fetchJson(url, options) {
        var config = Object.assign({ headers: {} }, options || {});
        config.headers = Object.assign({
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        }, config.headers || {});
        var response = await fetch(url, config);
        var data;
        try { data = await response.json(); } catch (error) { data = {}; }
        if (!response.ok) {
          var validation = data.errors ? Object.values(data.errors)[0] : null;
          throw new Error((validation && validation[0]) || data.message || text('ot.common.error', 'ไม่สามารถดำเนินการได้'));
        }
        return data;
      }

      function avatar(item) {
        var holder = document.createElement('span');
        holder.className = 'ot-avatar';
        holder.setAttribute('aria-hidden', 'true');
        if (item.avatar) {
          var image = document.createElement('img');
          image.src = item.avatar;
          image.alt = '';
          image.loading = 'lazy';
          image.addEventListener('error', function () { avatarPlaceholder(holder); });
          holder.appendChild(image);
        } else {
          avatarPlaceholder(holder);
        }
        return holder;
      }

      function avatarPlaceholder(holder) {
        holder.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>';
      }

      root.querySelectorAll('[data-avatar-image]').forEach(function (image) {
        image.addEventListener('error', function () {
          var holder = image.closest('.ot-avatar');
          if (holder) avatarPlaceholder(holder);
        });
      });

      function resultButton(item, actionLabel, disabled) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ot-result';
        button.disabled = Boolean(disabled);
        button.appendChild(avatar(item));

        var copy = document.createElement('span');
        copy.className = 'ot-result-copy';
        var name = document.createElement('strong');
        name.textContent = localized(item, 'name');
        var detail = document.createElement('small');
        var detailParts = [item.code, optionalLocalized(item, 'company'), optionalLocalized(item, 'position'), optionalLocalized(item, 'department')].filter(Boolean);
        detail.textContent = detailParts.join(' · ');
        copy.appendChild(name);
        copy.appendChild(detail);
        button.appendChild(copy);

        var action = document.createElement('span');
        action.className = 'ot-result-action';
        action.textContent = actionLabel;
        button.appendChild(action);
        return button;
      }

      var adminSearch = root.querySelector('[data-admin-search]');
      var adminResults = root.querySelector('[data-admin-results]');
      var adminTimer = 0;

      function renderAdminResults(users) {
        adminResults.replaceChildren();
        if (!users.length) {
          var empty = document.createElement('p');
          empty.className = 'ot-empty';
          empty.textContent = text('ot.common.noResults', 'ไม่พบข้อมูล');
          adminResults.appendChild(empty);
          return;
        }
        users.forEach(function (user) {
          var button = resultButton(user, text('ot.common.add', 'เพิ่ม'), false);
          button.addEventListener('click', async function () {
            button.disabled = true;
            try {
              await fetchJson(endpoints.addMember, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ app_user_id: user.app_user_id })
              });
              window.location.reload();
            } catch (error) {
              button.disabled = false;
              showStatus(error.message, true);
            }
          });
          adminResults.appendChild(button);
        });
      }

      async function searchAdmins() {
        try {
          var data = await fetchJson(endpoints.users + '?q=' + encodeURIComponent(adminSearch.value.trim()));
          renderAdminResults(data.users || []);
        } catch (error) {
          showStatus(error.message, true);
        }
      }

      adminSearch.addEventListener('focus', searchAdmins);
      adminSearch.addEventListener('input', function () {
        window.clearTimeout(adminTimer);
        adminTimer = window.setTimeout(searchAdmins, 260);
      });
      document.addEventListener('click', function (event) {
        if (!adminResults.contains(event.target) && event.target !== adminSearch) adminResults.replaceChildren();
      });

      root.querySelectorAll('[data-remove-member]').forEach(function (button) {
        button.addEventListener('click', async function () {
          if (!window.confirm(text('ot.settings.removeAdminConfirm', 'นำพนักงานคนนี้ออกจากผู้ดูแลระบบหรือไม่?'))) return;
          button.disabled = true;
          try {
            await fetchJson(endpoints.removeMemberBase + '/' + button.dataset.removeMember, { method: 'DELETE' });
            window.location.reload();
          } catch (error) {
            button.disabled = false;
            showStatus(error.message, true);
          }
        });
      });

      var positionItems = Array.prototype.slice.call(root.querySelectorAll('[data-position-item]'));
      var selectedCounter = root.querySelector('[data-position-selected]');
      function updatePositionCount() {
        selectedCounter.textContent = positionItems.filter(function (item) { return item.querySelector('input').checked; }).length;
      }
      root.querySelector('[data-position-search]').addEventListener('input', function (event) {
        var query = event.target.value.trim().toLocaleLowerCase();
        positionItems.forEach(function (item) { item.hidden = query !== '' && !item.dataset.searchText.includes(query); });
      });
      root.querySelector('[data-position-select-visible]').addEventListener('click', function () {
        positionItems.filter(function (item) { return !item.hidden; }).forEach(function (item) { item.querySelector('input').checked = true; });
        updatePositionCount();
      });
      root.querySelector('[data-position-clear]').addEventListener('click', function () {
        positionItems.forEach(function (item) { item.querySelector('input').checked = false; });
        updatePositionCount();
      });
      positionItems.forEach(function (item) { item.querySelector('input').addEventListener('change', updatePositionCount); });
      root.querySelector('[data-position-save]').addEventListener('click', async function (event) {
        var button = event.currentTarget;
        var codes = positionItems.filter(function (item) { return item.querySelector('input').checked; }).map(function (item) { return item.querySelector('input').value; });
        button.disabled = true;
        try {
          await fetchJson(endpoints.positions, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_codes: codes })
          });
          showStatus(text('ot.common.saved', 'บันทึกแล้ว'), false);
        } catch (error) {
          showStatus(error.message, true);
        } finally {
          button.disabled = false;
        }
      });

      /* ── หัวข้อ 7: ตำแหน่งที่ไม่ให้ขอ OT ──────────────────────────
         โครงเดียวกับหัวข้อ 6 แต่คนละ endpoint และความหมายกลับกัน (ติ๊ก = ซ่อนปุ่ม) */
      var hiddenItems = Array.prototype.slice.call(root.querySelectorAll('[data-hidden-position-item]'));
      var hiddenCounter = root.querySelector('[data-hidden-position-selected]');
      function updateHiddenCount() {
        hiddenCounter.textContent = hiddenItems.filter(function (item) { return item.querySelector('input').checked; }).length;
      }
      root.querySelector('[data-hidden-position-search]').addEventListener('input', function (event) {
        var query = event.target.value.trim().toLocaleLowerCase();
        hiddenItems.forEach(function (item) { item.hidden = query !== '' && !item.dataset.searchText.includes(query); });
      });
      root.querySelector('[data-hidden-position-select-visible]').addEventListener('click', function () {
        hiddenItems.filter(function (item) { return !item.hidden; }).forEach(function (item) { item.querySelector('input').checked = true; });
        updateHiddenCount();
      });
      root.querySelector('[data-hidden-position-clear]').addEventListener('click', function () {
        hiddenItems.forEach(function (item) { item.querySelector('input').checked = false; });
        updateHiddenCount();
      });
      hiddenItems.forEach(function (item) { item.querySelector('input').addEventListener('change', updateHiddenCount); });
      root.querySelector('[data-hidden-position-save]').addEventListener('click', async function (event) {
        var button = event.currentTarget;
        var codes = hiddenItems.filter(function (item) { return item.querySelector('input').checked; }).map(function (item) { return item.querySelector('input').value; });
        button.disabled = true;
        try {
          await fetchJson(endpoints.hiddenRequestPositions, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_codes: codes })
          });
          showStatus(text('ot.common.saved', 'บันทึกแล้ว'), false);
        } catch (error) {
          showStatus(error.message, true);
        } finally {
          button.disabled = false;
        }
      });

      /* ── หัวข้อ 8: ตำแหน่งที่ไม่ให้ขอลากิจ ─────────────────────────
         โครงเดียวกับหัวข้อ 7 เป๊ะ ต่างแค่ selector กับ endpoint (คนละคีย์ในตารางตั้งค่า) */
      var hiddenLeaveItems = Array.prototype.slice.call(root.querySelectorAll('[data-hidden-leave-item]'));
      var hiddenLeaveCounter = root.querySelector('[data-hidden-leave-selected]');
      function updateHiddenLeaveCount() {
        hiddenLeaveCounter.textContent = hiddenLeaveItems.filter(function (item) { return item.querySelector('input').checked; }).length;
      }
      root.querySelector('[data-hidden-leave-search]').addEventListener('input', function (event) {
        var query = event.target.value.trim().toLocaleLowerCase();
        hiddenLeaveItems.forEach(function (item) { item.hidden = query !== '' && !item.dataset.searchText.includes(query); });
      });
      root.querySelector('[data-hidden-leave-select-visible]').addEventListener('click', function () {
        hiddenLeaveItems.filter(function (item) { return !item.hidden; }).forEach(function (item) { item.querySelector('input').checked = true; });
        updateHiddenLeaveCount();
      });
      root.querySelector('[data-hidden-leave-clear]').addEventListener('click', function () {
        hiddenLeaveItems.forEach(function (item) { item.querySelector('input').checked = false; });
        updateHiddenLeaveCount();
      });
      hiddenLeaveItems.forEach(function (item) { item.querySelector('input').addEventListener('change', updateHiddenLeaveCount); });
      root.querySelector('[data-hidden-leave-save]').addEventListener('click', async function (event) {
        var button = event.currentTarget;
        var codes = hiddenLeaveItems.filter(function (item) { return item.querySelector('input').checked; }).map(function (item) { return item.querySelector('input').value; });
        button.disabled = true;
        try {
          await fetchJson(endpoints.hiddenLeavePositions, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_codes: codes })
          });
          showStatus(text('ot.common.saved', 'บันทึกแล้ว'), false);
        } catch (error) {
          showStatus(error.message, true);
        } finally {
          button.disabled = false;
        }
      });

      /* ── ผู้รับผิดชอบเก็บเป็น "รายการ" ทุกบทบาท ─────────────────────
         Foreman มีได้หลายคน Supervisor มีได้คนเดียว แต่ใช้โครงเดียวกัน
         จะได้ไม่ต้องแยกทางเดินสองแบบให้หลุดกันภายหลัง */
      function assigneesFromRow(row) {
        try {
          var list = JSON.parse(row.dataset.assignees || '[]');
          return Array.isArray(list) ? list : [];
        } catch (error) { return []; }
      }

      function setAssignees(row, list) {
        row.dataset.assignees = JSON.stringify(list || []);
      }

      /** เพิ่มคนใหม่ หรืออัปเดตคนเดิมถ้ามีอยู่แล้ว (เทียบด้วยรหัสพนักงาน) */
      function upsertAssignee(row, employee, assignmentId, shiftGroup) {
        var list = row.dataset.multi ? assigneesFromRow(row) : [];
        var entry = {
          id: assignmentId ? String(assignmentId) : '',
          code: employee.code || '',
          company_code: employee.company_code || '',
          company_th: employee.company_th || '',
          company_en: employee.company_en || '',
          company_my: employee.company_my || employee.company_en || '',
          name_th: employee.name_th || '',
          name_en: employee.name_en || '',
          name_my: employee.name_my || employee.name_en || '',
          position_th: employee.position_th || '',
          position_en: employee.position_en || '',
          position_my: employee.position_my || employee.position_en || '',
          department_th: employee.department_th || '',
          department_en: employee.department_en || '',
          department_my: employee.department_my || employee.department_en || '',
          avatar: employee.avatar || '',
          app_user_id: employee.app_user_id || null,
          shift_group: shiftGroup || null
        };

        var at = list.findIndex(function (item) { return String(item.id) === String(entry.id); });
        if (at === -1) list.push(entry); else list[at] = entry;
        list.sort(function (a, b) { return String(a.name_th).localeCompare(String(b.name_th), 'th'); });
        setAssignees(row, list);
      }

      function removeAssignee(row, assignmentId) {
        setAssignees(row, assigneesFromRow(row).filter(function (item) {
          return String(item.id) !== String(assignmentId);
        }));
      }

      function shiftLabel(assignment) {
        if (!assignment.shift_group) return '';
        var fallback = {
          all: 'ทั้งหมด',
          morning: 'กะเวลา A',
          night: 'กะเวลา B'
        };
        return text('ot.settings.shift.' + assignment.shift_group, fallback[assignment.shift_group] || assignment.shift_group);
      }

      function renderDepartmentAssignee(row) {
        var target = row.querySelector('[data-department-assignee]');
        var list = assigneesFromRow(row);
        var assignment = list[0] || {};
        target.className = 'ot-department-assignee ' + (list.length ? 'has-person' : 'is-empty');
        target.replaceChildren();

        /* Foreman มีได้หลายคน โชว์เป็นรูปซ้อนกันจะนับหัวได้ทันที
           ชื่อกับกะไปอยู่ใน tooltip แทน · Supervisor มีคนเดียวจึงโชว์ชื่อเหมือนเดิม */
        if (row.dataset.multi && list.length) {
          var stack = document.createElement('span');
          stack.className = 'ot-avatar-stack';
          list.slice(0, 6).forEach(function (person) {
            var node = avatar(person);
            node.removeAttribute('aria-hidden');
            node.title = [localized(person, 'name'), shiftLabel(person)].filter(Boolean).join(' · ');
            stack.appendChild(node);
          });
          if (list.length > 6) {
            var more = document.createElement('span');
            more.className = 'ot-avatar-more';
            more.textContent = '+' + (list.length - 6);
            stack.appendChild(more);
          }
          target.appendChild(stack);
          target.appendChild(departmentArrow());

          return;
        }

        target.appendChild(avatar(assignment));

        var copy = document.createElement('span');
        copy.className = 'ot-assignee-copy';
        var name = document.createElement('strong');
        name.textContent = assignment.id ? localized(assignment, 'name') : text('ot.settings.notAssigned', 'ยังไม่ได้กำหนด');
        copy.appendChild(name);
        if (assignment.id) {
          var detail = document.createElement('small');
          detail.textContent = [assignment.code, optionalLocalized(assignment, 'company')].filter(Boolean).join(' · ');
          copy.appendChild(detail);
        }
        target.appendChild(copy);

        var arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        arrow.setAttribute('class', 'ot-department-arrow');
        arrow.setAttribute('viewBox', '0 0 24 24');
        arrow.setAttribute('aria-hidden', 'true');
        arrow.innerHTML = '<path d="m9 5 7 7-7 7"></path>';
        target.appendChild(arrow);
      }

      function departmentArrow() {
        var arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        arrow.setAttribute('class', 'ot-department-arrow');
        arrow.setAttribute('viewBox', '0 0 24 24');
        arrow.setAttribute('aria-hidden', 'true');
        arrow.innerHTML = '<path d="m9 5 7 7-7 7"></path>';

        return arrow;
      }

      /* สิทธิ์ OT กับการลาแยกกัน ต้องบอกฝั่งเซิร์ฟเวอร์ว่าแถวนี้อยู่หัวข้อของระบบไหน
         อ่านจาก data-module ของ section ที่ครอบอยู่ ไม่ต้องส่งผ่านทุกปุ่ม */
      function moduleOf(row) {
        var section = row.closest('[data-module]');

        return section ? (section.dataset.module || 'ot') : 'ot';
      }

      function updateAssignmentCounts(row) {
        var section = row.closest('[data-role-section]');
        var allRows = Array.prototype.slice.call(section.querySelectorAll('[data-open-assignment]'));
        var assigned = allRows.filter(function (item) { return assigneesFromRow(item).length > 0; }).length;
        section.querySelector('.ot-section-count').textContent = assigned + '/' + allRows.length;

        var company = row.closest('.ot-company');
        var companyRows = Array.prototype.slice.call(company.querySelectorAll('[data-open-assignment]'));
        var companyAssigned = companyRows.filter(function (item) { return assigneesFromRow(item).length > 0; }).length;
        company.querySelector('[data-company-assigned-count]').textContent = companyAssigned + '/' + companyRows.length;
      }

      function departmentName(row) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        if (lang === 'th') return row.dataset.deptTh || row.dataset.deptEn || row.dataset.deptCode;
        return row.dataset.deptEn || row.dataset.deptTh || row.dataset.deptCode;
      }

      function closeAssignmentModal(modal) {
        var trigger = modal.__trigger;
        modal.hidden = true;
        modal.__trigger = null;
        document.body.style.overflow = modal.__previousOverflow || '';
        if (trigger) trigger.focus();
      }

      /* แสดงผู้รับผิดชอบเป็นรายการ — Foreman หลายคนพร้อม dropdown เลือกกะ
         Supervisor คนเดียวและไม่มี dropdown เพราะดูแลทั้งแผนกอยู่แล้ว */
      function renderCurrentAssignment(modal, row) {
        var target = modal.querySelector('[data-current-assignee]');
        var list = assigneesFromRow(row);
        target.replaceChildren();

        if (!list.length) {
          var blank = document.createElement('div');
          blank.className = 'ot-assignee-item is-empty';
          var blankCopy = document.createElement('span');
          blankCopy.className = 'ot-assignee-copy';
          var blankName = document.createElement('strong');
          blankName.textContent = text('ot.settings.notAssigned', 'ยังไม่ได้กำหนด');
          blankCopy.appendChild(blankName);
          blank.appendChild(blankCopy);
          target.appendChild(blank);
          return;
        }

        list.forEach(function (assignment) {
          target.appendChild(assigneeItem(modal, row, assignment));
        });
      }

      function assigneeItem(modal, row, assignment) {
        var item = document.createElement('div');
        item.className = 'ot-assignee-item';
        item.appendChild(avatar(assignment));

        var copy = document.createElement('span');
        copy.className = 'ot-assignee-copy';
        var name = document.createElement('strong');
        name.textContent = localized(assignment, 'name');
        var detail = document.createElement('small');
        detail.textContent = [
          assignment.code,
          optionalLocalized(assignment, 'company'),
          optionalLocalized(assignment, 'position'),
          optionalLocalized(assignment, 'department')
        ].filter(Boolean).join(' · ');
        copy.append(name, detail);
        item.appendChild(copy);

        var actions = document.createElement('span');
        actions.className = 'ot-current-actions';

        if (row.dataset.multi) actions.appendChild(shiftSelect(modal, row, assignment));

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'ot-button ot-button-secondary';
        remove.textContent = text('ot.common.remove', 'นำออก');
        remove.addEventListener('click', async function () {
          if (!window.confirm(text('ot.settings.removeAssignmentConfirm', 'นำผู้รับผิดชอบออกจากแผนกนี้หรือไม่?'))) return;
          remove.disabled = true;
          try {
            await fetchJson(endpoints.removeAssignmentBase + '/' + assignment.id, { method: 'DELETE' });
            removeAssignee(row, assignment.id);
            refreshAssignmentViews(modal, row);
            showStatus(text('ot.common.saved', 'บันทึกแล้ว'), false);
          } catch (error) {
            remove.disabled = false;
            showStatus(error.message, true);
          }
        });
        actions.appendChild(remove);
        item.appendChild(actions);

        return item;
      }

      /** เลือกกะที่ Foreman คนนี้ดูแล — บันทึกทันทีที่เปลี่ยน */
      function shiftSelect(modal, row, assignment) {
        var select = document.createElement('select');
        select.className = 'ot-shift-select';
        select.setAttribute('aria-label', text('ot.settings.shiftGroup', 'กะที่ดูแล'));

        var blank = document.createElement('option');
        blank.value = '';
        blank.textContent = text('ot.settings.shift.none', 'ไม่ระบุกะ');
        select.appendChild(blank);

        shiftGroups.forEach(function (group) {
          var option = document.createElement('option');
          option.value = group.key;
          option.textContent = optionalLocalized(group, 'label') || group.label_th;
          select.appendChild(option);
        });

        select.value = assignment.shift_group || '';
        select.addEventListener('change', async function () {
          var previous = assignment.shift_group || '';
          select.disabled = true;
          try {
            var data = await fetchJson(endpoints.assignments, {
              method: 'PUT',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                company: row.dataset.company,
                dept_code: row.dataset.deptCode,
                role: row.dataset.role,
                module: moduleOf(row),
                shift_group: select.value || null,
                app_user_id: assignment.app_user_id,
                employee_company: assignment.company_code,
                employee_code: assignment.code
              })
            });
            upsertAssignee(row, assignment, data.assignment_id || assignment.id, data.shift_group || null);
            refreshAssignmentViews(modal, row);
            showStatus(text('ot.common.saved', 'บันทึกแล้ว'), false);
          } catch (error) {
            select.value = previous;
            select.disabled = false;
            showStatus(error.message, true);
          }
        });

        return select;
      }

      /** วาดใหม่ทั้ง 3 จุดที่อ้างอิงรายการเดียวกัน กันหลุดไม่ตรงกัน */
      function refreshAssignmentViews(modal, row) {
        renderDepartmentAssignee(row);
        updateAssignmentCounts(row);
        renderCurrentAssignment(modal, row);
        if (row.__employees) {
          var activeQuery = modal.querySelector('[data-assignment-search]').value.trim();
          renderAssignmentEmployees(modal, row, activeQuery && allEmployees ? allEmployees : row.__employees);
        }
      }

      function setAssignmentResultsLabel(modal, companySearch) {
        var label = modal.querySelector('[data-assignment-results-label]');
        if (!label) return;
        label.textContent = companySearch
          ? text('ot.settings.companySearchResults', 'ผลการค้นหาจากทุกบริษัท')
          : text('ot.settings.employeeList', 'พนักงานในแผนก');
      }

      function renderAssignmentEmployees(modal, row, sourceEmployees) {
        var target = modal.querySelector('[data-assignment-results]');
        var query = modal.querySelector('[data-assignment-search]').value.trim().toLocaleLowerCase();
        var employees = (sourceEmployees || row.__employees || []).filter(function (employee) {
          var haystack = [employee.code, employee.company_code, employee.company_th, employee.company_en, employee.name_th, employee.name_en, employee.position_th, employee.position_en, employee.department_th, employee.department_en].join(' ').toLocaleLowerCase();
          return query === '' || haystack.includes(query);
        });
        setAssignmentResultsLabel(modal, query !== '');
        target.replaceChildren();
        if (!employees.length) {
          var empty = document.createElement('p');
          empty.className = 'ot-empty';
          empty.textContent = text('ot.common.noResults', 'ไม่พบข้อมูล');
          target.appendChild(empty);
          return;
        }
        employees.forEach(function (employee) {
          var ready = Boolean(employee.account_ready);
          // คนที่อยู่ในรายการแล้วต้องเห็นชัดว่าเพิ่มไปแล้ว ไม่งั้นจะกดซ้ำแล้วงงว่าไม่มีอะไรเกิดขึ้น
          var already = assigneesFromRow(row).some(function (item) { return item.code === employee.code; });
          var label = !ready
            ? text('ot.settings.noInsightAccount', 'ไม่มีบัญชี Insight')
            : (already ? text('ot.settings.alreadyAssigned', 'เพิ่มแล้ว') : text('ot.common.choose', 'เลือก'));
          var button = resultButton(employee, label, !ready || already);
          if (ready && !already) {
            button.addEventListener('click', async function () {
              button.disabled = true;
              try {
                var data = await fetchJson(endpoints.assignments, {
                  method: 'PUT',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({
                    company: row.dataset.company,
                    dept_code: row.dataset.deptCode,
                    role: row.dataset.role,
                    module: moduleOf(row),
                    app_user_id: employee.app_user_id,
                    employee_company: employee.company_code,
                    employee_code: employee.code
                  })
                });
                // Foreman = เพิ่มเข้ารายการ · Supervisor = แทนที่คนเดิม (upsertAssignee ดูจาก data-multi)
                upsertAssignee(row, employee, data.assignment_id, data.shift_group || null);
                refreshAssignmentViews(modal, row);
                showStatus(text('ot.common.saved', 'บันทึกแล้ว'), false);
              } catch (error) {
                button.disabled = false;
                showStatus(error.message, true);
              }
            });
          }
          target.appendChild(button);
        });
      }

      function loadAllEmployees(row) {
        if (allEmployees) return Promise.resolve(allEmployees);
        if (allEmployeesPromise) return allEmployeesPromise;
        var query = '?company=' + encodeURIComponent(row.dataset.company)
          + '&dept_code=' + encodeURIComponent(row.dataset.deptCode)
          + '&all_companies=1';
        allEmployeesPromise = fetchJson(endpoints.employees + query)
          .then(function (data) {
            allEmployees = data.employees || [];
            return allEmployees;
          })
          .finally(function () { allEmployeesPromise = null; });
        return allEmployeesPromise;
      }

      root.querySelectorAll('[data-assignment-modal]').forEach(function (modal) {
        modal.querySelector('[data-close-assignment]').addEventListener('click', function () { closeAssignmentModal(modal); });
        modal.addEventListener('click', function (event) {
          if (event.target === modal) closeAssignmentModal(modal);
        });
        modal.querySelector('[data-assignment-search]').addEventListener('input', async function () {
          var row = modal.__trigger;
          if (!row) return;
          var query = this.value.trim();
          if (!query) {
            renderAssignmentEmployees(modal, row, row.__employees);
            return;
          }

          var target = modal.querySelector('[data-assignment-results]');
          setAssignmentResultsLabel(modal, true);
          target.innerHTML = '<p class="ot-empty">' + text('ot.common.loading', 'กำลังโหลด...') + '</p>';
          try {
            var employees = await loadAllEmployees(row);
            if (modal.__trigger === row && this.value.trim()) renderAssignmentEmployees(modal, row, employees);
          } catch (error) {
            target.replaceChildren();
            showStatus(error.message, true);
          }
        });
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        // ถ้ากำลังดูรูปขยายอยู่ ให้ Esc ปิดแค่รูป ไม่ปิดโมดัลที่อยู่ข้างหลัง
        if (root.querySelector('[data-ot-lightbox]:not([hidden])')) return;
        var modal = root.querySelector('[data-assignment-modal]:not([hidden])');
        if (modal) closeAssignmentModal(modal);
      });

      root.querySelectorAll('[data-open-assignment]').forEach(function (row) {
        row.addEventListener('click', async function () {
          /* ต้องเจาะทั้ง role และ module เพราะ role ซ้ำกันระหว่างชุด OT กับชุดการลา
             ถ้าหาแค่ role จะได้ modal ของ OT เสมอ แล้วหัวข้อ 4-5 จะกดไม่ขึ้น */
          var modal = root.querySelector('[data-assignment-modal="' + row.dataset.role + '"][data-assignment-module="' + moduleOf(row) + '"]');
          var search = modal.querySelector('[data-assignment-search]');
          var target = modal.querySelector('[data-assignment-results]');
          modal.__trigger = row;
          modal.querySelector('[data-assignment-company]').textContent = row.dataset.companyLabel + ' · ' + text('ot.settings.' + row.dataset.role + 'Title', row.dataset.role);
          modal.querySelector('[data-assignment-title]').textContent = departmentName(row);
          search.value = '';
          renderCurrentAssignment(modal, row);
          modal.__previousOverflow = document.body.style.overflow;
          document.body.style.overflow = 'hidden';
          modal.hidden = false;
          window.requestAnimationFrame(function () { search.focus(); });

          if (row.__employees) {
            renderAssignmentEmployees(modal, row);
            return;
          }
          target.innerHTML = '<p class="ot-empty">' + text('ot.common.loading', 'กำลังโหลด...') + '</p>';
          try {
            var query = '?company=' + encodeURIComponent(row.dataset.company) + '&dept_code=' + encodeURIComponent(row.dataset.deptCode);
            var data = await fetchJson(endpoints.employees + query);
            row.__employees = data.employees || [];
            if (modal.__trigger === row) renderAssignmentEmployees(modal, row);
          } catch (error) {
            target.replaceChildren();
            showStatus(error.message, true);
          }
        });
      });

      document.addEventListener('insight:languagechange', function () {
        root.querySelectorAll('[data-assignment-modal]:not([hidden])').forEach(function (modal) {
          var row = modal.__trigger;
          if (!row) return;
          modal.querySelector('[data-assignment-company]').textContent = row.dataset.companyLabel + ' · ' + text('ot.settings.' + row.dataset.role + 'Title', row.dataset.role);
          modal.querySelector('[data-assignment-title]').textContent = departmentName(row);
          renderCurrentAssignment(modal, row);
          if (row.__employees) {
            var query = modal.querySelector('[data-assignment-search]').value.trim();
            renderAssignmentEmployees(modal, row, query && allEmployees ? allEmployees : row.__employees);
          }
        });
      });

      /* ── คลิกรูปพนักงานเพื่อดูแบบขยาย ────────────────────────
         ใช้ event delegation ที่ root เพื่อให้ครอบคลุมรูปที่สร้างด้วย JS ด้วย
         (ผลค้นหา, รายชื่อในโมดัลเลือกผู้รับผิดชอบ, ผู้ที่เลือกปัจจุบัน) */
      var lightbox = root.querySelector('[data-ot-lightbox]');
      var lightboxImage = lightbox ? lightbox.querySelector('[data-lightbox-image]') : null;
      var lightboxCaption = lightbox ? lightbox.querySelector('[data-lightbox-caption]') : null;

      function closeLightbox() {
        if (!lightbox || lightbox.hidden) return;
        lightbox.hidden = true;
        lightboxImage.removeAttribute('src');
        if (lightbox.__previousOverflow !== undefined) {
          document.body.style.overflow = lightbox.__previousOverflow;
          delete lightbox.__previousOverflow;
        }
      }

      // ดึงชื่อ/รหัสจากบล็อกข้อความข้าง ๆ รูป มาแสดงใต้ภาพ
      function lightboxCaptionFrom(avatar) {
        var holder = avatar.closest('.ot-member, .ot-result, .ot-department-assignee, .ot-current-assignee');
        var copy = holder ? holder.querySelector('.ot-member-copy, .ot-result-copy, .ot-assignee-copy') : null;
        if (!copy) return null;

        // ชื่อมีทั้ง th/en ซ้อนกัน เอาเฉพาะอันที่กำลังแสดงจริง
        var nameNode = copy.querySelector('strong');
        var name = '';
        if (nameNode) {
          var shown = Array.prototype.filter.call(nameNode.querySelectorAll('[data-val]'), function (node) {
            return node.offsetParent !== null || node.getClientRects().length > 0;
          });
          name = (shown.length ? shown[0] : nameNode).textContent.trim();
        }
        var codeNode = copy.querySelector('small');
        var code = codeNode ? codeNode.textContent.trim() : '';
        if (!name && !code) return null;

        return { name: name, code: code };
      }

      function openLightbox(image) {
        if (!lightbox || !image || !image.getAttribute('src')) return;
        lightboxImage.src = image.currentSrc || image.src;
        lightboxImage.alt = image.alt || '';

        var caption = lightboxCaptionFrom(image);
        lightboxCaption.replaceChildren();
        if (caption) {
          var nameEl = document.createElement('strong');
          nameEl.textContent = caption.name;
          lightboxCaption.appendChild(nameEl);
          if (caption.code) {
            var codeEl = document.createElement('small');
            codeEl.textContent = caption.code;
            lightboxCaption.appendChild(codeEl);
          }
        }
        lightboxCaption.hidden = !caption;

        lightbox.__previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        lightbox.hidden = false;
      }

      if (lightbox) {
        root.addEventListener('click', function (event) {
          var image = event.target.closest('.ot-avatar img');
          if (!image || !root.contains(image)) return;
          // กันไม่ให้ไปกดปุ่ม/แถวที่ครอบรูปอยู่ (เปิดโมดัล หรือเลือกคนนั้น)
          event.preventDefault();
          event.stopPropagation();
          openLightbox(image);
        }, true);

        lightbox.addEventListener('click', function (event) {
          // คลิกพื้นหลังหรือปุ่มปิด = ปิด, คลิกที่ตัวรูปไม่ปิด
          if (event.target.closest('[data-lightbox-close]') || !event.target.closest('.ot-lightbox-figure')) {
            closeLightbox();
          }
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') closeLightbox();
        });
      }
    })();
  </script>
@endsection
