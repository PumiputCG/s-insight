@extends('layouts.portal')

@section('title', 'ข้อมูลผู้ใช้งาน')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="info.kicker">โปรไฟล์</span>
  <span class="tt-title" data-i18n="info.title">ข้อมูลผู้ใช้งาน</span>
@endsection

@php
  // ── เตรียมข้อมูลโปรไฟล์ (ดึงจาก employees mirror ถ้ามี ไม่งั้น fallback มาที่ app_users) ──
  $companyLabels = [
    'SUPAVUT_INDUSTRY' => 'Supavut Industry',
    'MOLDVANTO' => 'Moldvanto',
    'SUPAVUT_INNOMED' => 'Supavut Innomed',
  ];
  $labelOf = fn ($c) => $companyLabels[$c] ?? ($c ? ucwords(strtolower(str_replace('_', ' ', $c))) : '');

  // หลายบริษัท: ยึดจาก app_users.company (CSV) — บัญชีข้ามบริษัทจะมี 2-3 บริษัท
  $companyCodes = $me->companyList();
  if (empty($companyCodes) && $emp && $emp->company) {
    $companyCodes = [$emp->company];
  }
  $companyNames = array_values(array_filter(array_map($labelOf, $companyCodes)));
  $companyDisplay = $companyNames ? implode(' · ', $companyNames) : '-';

  $titleTh = trim((string) ($emp->title ?? ''));
  $titleKey = preg_replace('/\s+/u', '', $titleTh);
  $titleEn = [
    'นาย' => 'Mr.',
    'น.ส.' => 'Miss',
    'น.ส' => 'Miss',
    'นางสาว' => 'Miss',
    'นาง' => 'Mrs.',
  ][$titleKey] ?? $titleTh;
  $nameTh = $emp
    ? trim(($emp->title ?? '').($emp->name_th ?? '').' '.($emp->surname_th ?? ''))
    : ($me->fullNameTh() ?: '');
  $nameEn = ($emp->name_en ?? null) ?: ($me->full_name_en ?? '');

  $deptTh = ($emp->dept_th ?? null) ?: ($me->department ?? '');
  $deptEn = ($emp->dept_en ?? null) ?: ($me->department ?? '');
  $deptEnDisplay = $deptEn ?: $deptTh;
  /* Bplus เก็บตัวย่อไว้ที่ dept_th และชื่อเต็มที่ dept_en — รวมเป็น "ชื่อเต็ม (ตัวย่อ)"
     ของเดิมวาง 2 ช่องต่อกันเลยอ่านออกมาเป็น "IT Information Technology" */
  $deptDisplay = ($deptEnDisplay && $deptTh && $deptEnDisplay !== $deptTh)
    ? $deptEnDisplay.' ('.$deptTh.')'
    : ($deptEnDisplay ?: $deptTh);
  $posTh = ($emp->job_th ?? null) ?: ($me->position ?? '');
  $posEn = ($emp->job_en ?? null) ?: ($me->position ?? '');

  $isResigned = $emp ? $emp->isResigned() : false;
  $hireDate = ($emp && $emp->hire_date) ? $emp->hire_date->format('d/m/Y') : '-';

  $dash = fn ($v) => trim((string) $v) !== '' ? $v : '-';
  $hasPwdError = $errors->has('id_card') || $errors->has('password');

  $avatarUrl = $me->profile_picture ? asset('storage/'.$me->profile_picture) : null;

  // สิทธิ์ตามตำแหน่ง (ตั้งค่าได้ที่หน้า Setting) — admin ทำได้เสมอ
  $myJobCode = optional($emp)->job_code;
  $emailAllowed = $me->isAdmin() || \App\Models\Insight\Setting::isPositionAllowed(\App\Models\Insight\Setting::EMAIL_POSITIONS, $myJobCode);
  $sigAllowed = $me->isAdmin() || \App\Models\Insight\Setting::isPositionAllowed(\App\Models\Insight\Setting::SIGNATURE_POSITIONS, $myJobCode);
@endphp

@section('page-style')
    .info-wrap { width: min(100%, 56rem); margin-top: clamp(.25rem, 1vw, .75rem); }

    /* ── ส่วนหัว: รูปโปรไฟล์ + ชื่อ + บริษัท + ปุ่มอัปโหลด (บรรทัดเดียว) ── */
    .profile-head {
      display: flex;
      align-items: center;
      gap: 1.2rem;
      padding-bottom: 1.4rem;
      margin-bottom: 1.4rem;
      border-bottom: 1px solid var(--line-light);
    }

    .avatar-lg {
      width: 5rem; height: 5rem; flex: 0 0 auto;
      display: grid; place-items: center;
      border-radius: 50%;
      overflow: hidden;
      border: 1px solid var(--line-strong);
      background: var(--panel-soft);
      color: var(--light-text);
      font-size: 1.8rem; font-weight: 600;
    }

    button.avatar-lg {
      padding: 0;
      cursor: zoom-in;
      appearance: none;
      transition: border-color .2s var(--ease-out), transform .2s var(--ease-out);
    }

    button.avatar-lg:hover,
    button.avatar-lg:focus-visible {
      border-color: var(--moss);
      transform: translateY(-1px);
    }

    .avatar-lg img { width: 100%; height: 100%; object-fit: cover; }

    .profile-meta { min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
    .profile-meta .pm-name { font-size: 1.25rem; font-weight: 600; line-height: 1.2; }
    .profile-meta .pm-company { color: var(--muted-light); font-size: .9rem; }
    .profile-meta .pm-code {
      color: var(--moss);
      font-family: "Montserrat", var(--font-body);
      font-size: .72rem; font-weight: 800; letter-spacing: .12em;
    }

    .profile-upload-wrap { margin-left: auto; align-self: center; text-align: right; }

    @media (max-width: 620px) {
      .profile-head { flex-wrap: wrap; }
      .profile-upload-wrap { margin-left: 0; width: 100%; text-align: left; }
    }

    .avatar-upload {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .45rem .85rem;
      border: 1px solid var(--line-strong);
      border-radius: 0.25rem;
      color: var(--light-text);
      cursor: pointer;
      font-size: .76rem; font-weight: 600;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out);
    }
    .avatar-upload:hover { border-color: var(--moss); background: var(--hover-soft); }
    .avatar-upload svg { width: .95rem; height: .95rem; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
    .avatar-upload input { display: none; }

    /* ── ข้อมูล 2 คอลัมน์ (บรรทัดละ 2 ข้อมูล ตัวอักษรเล็กลง ไม่ต้องเลื่อน) ── */
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      column-gap: clamp(1.5rem, 4vw, 3rem);
    }

    .info-row {
      display: flex;
      flex-direction: column;
      gap: .15rem;
      padding: .7rem .1rem;
      border-bottom: 1px solid var(--line-light);
    }

    .info-row dt {
      color: var(--muted-light);
      font-size: .68rem;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .info-row dd { color: var(--light-text); font-size: .95rem; overflow-wrap: anywhere; }

    @media (max-width: 620px) {
      .info-grid { grid-template-columns: 1fr; }
    }

    .status-line { display: inline-flex; align-items: center; gap: .5rem; font-size: .95rem; }

    .status-line::before {
      content: "";
      width: .6rem; height: .6rem;
      border-radius: 50%;
      background: var(--moss);
      box-shadow: 0 0 0 4px rgb(91 141 239 / 18%);
    }

    .status-line.is-resigned::before { background: #c97b72; box-shadow: 0 0 0 4px rgb(201 123 114 / 18%); }

    /* ── แถวรหัสผ่าน: เต็มความกว้าง 2 คอลัมน์ + จุดวงกลมเล็ก + ปุ่มเปลี่ยน ── */
    .pwd-row { grid-column: 1 / -1; }
    .pwd-row dd { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }

    /* ── อีเมล ── */
    .email-row { grid-column: 1 / -1; }
    .email-form { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .email-form input {
      flex: 1; min-width: 12rem; min-height: 2.6rem;
      padding: .5rem .8rem;
      border: 1px solid var(--line-strong); border-radius: 0.25rem;
      background: var(--hover-soft); color: var(--light-text);
      transition: border-color .2s var(--ease-out);
    }
    .email-form input::placeholder { color: var(--muted-light); opacity: .7; }
    .email-form input:focus { outline: 0; border-color: var(--moss); }
    .email-save {
      flex: 0 0 auto; min-height: 2.6rem; padding: 0 1rem;
      border: 1px solid var(--moss); border-radius: 0.25rem;
      background: rgb(91 141 239 / 16%); color: var(--light-text); cursor: pointer;
      font-size: .8rem; font-weight: 700;
      transition: background-color .2s var(--ease-out);
    }
    .email-save:hover { background: rgb(91 141 239 / 28%); }
    .email-msg { display: inline-block; margin-top: .35rem; font-size: .8rem; min-height: 1rem; }
    .email-msg.ok { color: var(--success); }
    .email-msg.err { color: var(--danger); }

    .pwd-dots { display: inline-flex; align-items: center; gap: .32rem; }
    .pwd-dots i { width: .5rem; height: .5rem; border-radius: 50%; background: var(--light-text); opacity: .85; }

    .pwd-change-btn {
      display: inline-flex; align-items: center; gap: .45rem;
      padding: .5rem .9rem;
      border: 1px solid var(--line-strong);
      border-radius: 0.25rem;
      background: transparent;
      color: var(--light-text);
      cursor: pointer;
      font-size: .8rem; font-weight: 600;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out);
    }

    .pwd-change-btn:hover { border-color: var(--moss); background: var(--hover-soft); }
    .pwd-change-btn svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }

    /* ── Modal เปลี่ยนรหัสผ่าน ── */
    .pwd-modal {
      position: fixed; inset: 0; z-index: 90;
      display: grid; place-items: center;
      padding: 1.25rem;
      background: var(--overlay-bg);
      backdrop-filter: blur(4px);
      opacity: 0; visibility: hidden;
      transition: opacity .25s var(--ease-out), visibility .25s;
    }

    .pwd-modal.is-open { opacity: 1; visibility: visible; }

    .pwd-dialog {
      width: min(100%, 27rem);
      padding: clamp(1.6rem, 3vw, 2.2rem);
      border: 1px solid var(--line-light);
      border-radius: 0.4rem;
      background: var(--panel);
      transform: translateY(.6rem);
      transition: transform .25s var(--ease-out);
    }

    .pwd-modal.is-open .pwd-dialog { transform: none; }

    .pwd-dialog-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.3rem; }

    .pwd-dialog h2 { font-family: var(--font-display); font-size: clamp(1.4rem, 2.4vw, 1.8rem); font-weight: 400; }

    .pwd-close {
      width: 2rem; height: 2rem; flex: 0 0 auto;
      display: grid; place-items: center;
      border: 0; border-radius: 50%;
      background: transparent; color: var(--muted-light); cursor: pointer;
      transition: background-color .2s var(--ease-out), color .2s var(--ease-out);
    }
    .pwd-close:hover { background: var(--hover-soft); color: var(--light-text); }
    .pwd-close svg { width: 1.2rem; height: 1.2rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }

    .pwd-field { margin-bottom: 1.1rem; }

    .pwd-field label {
      display: block; margin-bottom: .45rem;
      color: var(--muted-light);
      font-size: .76rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
    }

    .pwd-id-row { display: flex; gap: .6rem; }
    .pwd-id-row input { flex: 1; }

    .pwd-field input {
      width: 100%; min-height: 3rem;
      padding: .7rem .9rem;
      border: 1px solid var(--line-strong);
      border-radius: 0.25rem;
      background: var(--hover-soft);
      color: var(--light-text);
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out);
    }

    .pwd-field input::placeholder { color: var(--muted-light); opacity: .7; }
    .pwd-field input:focus { outline: 0; border-color: var(--moss); }
    .pwd-field input:disabled { opacity: .42; cursor: not-allowed; }

    .pwd-input-wrap { position: relative; }

    .pwd-input-wrap input { padding-right: 3rem; }

    .pwd-eye-btn {
      position: absolute;
      top: 0;
      right: 0;
      width: 2.75rem;
      height: 3rem;
      display: grid;
      place-items: center;
      padding: 0;
      border: 0;
      background: transparent;
      color: var(--muted-light);
      cursor: pointer;
      transition: color .2s var(--ease-out), opacity .2s var(--ease-out);
    }

    .pwd-eye-btn:hover,
    .pwd-eye-btn:focus-visible { color: var(--light-text); }

    .pwd-eye-btn:focus-visible {
      outline: 1px solid var(--line-strong);
      outline-offset: .1rem;
      border-radius: 999px;
    }

    .pwd-eye-btn:disabled { opacity: .36; cursor: not-allowed; }

    .pwd-eye-btn svg {
      width: 1.18rem;
      height: 1.18rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.75;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .pwd-eye-btn .icon-eye-off,
    .pwd-eye-btn[aria-pressed="true"] .icon-eye { display: none; }

    .pwd-eye-btn[aria-pressed="true"] .icon-eye-off { display: block; }

    .pwd-verify-btn {
      flex: 0 0 auto; min-width: 5.5rem;
      border: 1px solid var(--moss); border-radius: 0.25rem;
      background: rgb(91 141 239 / 16%);
      color: var(--light-text); cursor: pointer;
      font-size: .8rem; font-weight: 700;
      transition: background-color .2s var(--ease-out);
    }
    .pwd-verify-btn:hover { background: rgb(91 141 239 / 28%); }

    .pwd-msg { margin-top: .5rem; font-size: .82rem; min-height: 1.1rem; }
    .pwd-msg.ok { color: var(--success); }
    .pwd-msg.err { color: var(--danger); }

    .pwd-submit {
      width: 100%; min-height: 3.1rem; margin-top: .4rem;
      display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
      border: 1px solid var(--moss); border-radius: 0.25rem;
      background: rgb(91 141 239 / 16%);
      color: var(--light-text); cursor: pointer;
      font-size: .82rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
      transition: background-color .25s var(--ease-out), transform .25s var(--ease-out), opacity .2s;
    }
    .pwd-submit:hover { background: rgb(91 141 239 / 26%); transform: translateY(-1px); }
    .pwd-submit:disabled { opacity: .42; cursor: not-allowed; transform: none; }

    /* ── ลายเซ็น ── */
    .sig-section {
      margin-top: .7rem;
      padding: .7rem .1rem 0;
      border-bottom: 1px solid var(--line-light);
    }

    .sig-head {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .8rem;
      margin: 0 0 .45rem;
    }
    .sig-head .sig-kicker {
      color: var(--muted-light);
      font-size: .68rem;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    .sig-head .sig-saved {
      flex: 0 0 auto;
      padding: .22rem .55rem;
      border: 1px solid var(--line-light);
      border-radius: 999px;
      background: var(--hover-soft);
      color: var(--muted-light);
      font-size: .72rem;
      line-height: 1.4;
    }

    .sig-editor {
      width: min(100%, 34rem);
      margin-inline: 0;
    }

    /* ลายเซ็นที่บันทึกไว้ (PNG โปร่งใส หมึกเข้ม) — วางบนพื้นสว่าง จัดกึ่งกลาง */
    .sig-current {
      width: 100%;
      margin: 0 0 .75rem;
      padding: .65rem .9rem;
      border: 1px dashed var(--line-strong);
      border-radius: 0.25rem;
      background: #ffffff;
      text-align: center;
    }
    .sig-current img { max-width: 100%; max-height: 5rem; width: auto; margin: 0 auto; }

    .sig-pad-wrap {
      position: relative;
      width: 100%;
      border: 1px solid var(--line-strong);
      border-radius: 0.28rem;
      background: #ffffff;             /* แผ่นเซ็นสีขาวเสมอ ให้เห็นเส้นหมึกเข้ม */
      overflow: hidden;
      touch-action: none;
    }
    .sig-canvas { display: block; width: 100%; height: 12rem; cursor: crosshair; touch-action: none; }
    .sig-hint {
      position: absolute; inset: 0;
      display: grid; place-items: center;
      color: rgb(17 19 15 / 32%); font-size: .92rem;
      pointer-events: none;
    }
    .sig-pad-wrap.has-ink .sig-hint { display: none; }

    .sig-tools {
      width: 100%;
      min-height: 3.35rem;
      display: grid;
      grid-template-columns: auto minmax(12rem, 1fr) auto;
      align-items: center;
      gap: .65rem;
      margin-top: .7rem;
      padding: .5rem;
      border: 1px solid var(--line-light);
      border-radius: 0.28rem;
      background: var(--panel-soft);
    }
    .sig-tool-group { display: inline-flex; gap: .35rem; }

    .sig-btn {
      width: 2.4rem; height: 2.4rem;
      display: grid; place-items: center;
      border: 1px solid var(--line-strong); border-radius: 0.25rem;
      background: transparent; color: var(--light-text); cursor: pointer;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out), opacity .2s;
    }
    .sig-btn:hover:not(:disabled) { border-color: var(--moss); background: var(--hover-soft); }
    .sig-btn:disabled { opacity: .35; cursor: not-allowed; }
    .sig-btn svg { width: 1.1rem; height: 1.1rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

    .sig-pen {
      min-width: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .55rem;
      color: var(--muted-light);
      font-size: .78rem;
      white-space: nowrap;
    }
    .sig-pen input[type="range"] { width: min(9rem, 100%); accent-color: var(--moss); }

    .sig-save {
      min-height: 2.4rem; padding: 0 1rem;
      display: inline-flex; align-items: center; gap: .5rem;
      border: 1px solid var(--moss); border-radius: 0.25rem;
      background: rgb(91 141 239 / 16%); color: var(--light-text); cursor: pointer;
      font-size: .8rem; font-weight: 700; letter-spacing: .04em;
      transition: background-color .2s var(--ease-out), opacity .2s;
    }
    .sig-save:hover:not(:disabled) { background: rgb(91 141 239 / 28%); }
    .sig-save:disabled { opacity: .42; cursor: not-allowed; }

    .sig-msg { width: 100%; margin-top: .5rem; margin-bottom: .65rem; font-size: .82rem; min-height: 1.1rem; }
    .sig-msg.ok { color: var(--success); }
    .sig-msg.err { color: var(--danger); }

    /* ── เลือกวิธีเซ็น: เซ็นเอง / แนบไฟล์ภาพ ── */
    .sig-modes {
      display: inline-flex;
      gap: .25rem;
      margin: 0 0 .7rem;
      padding: .25rem;
      border: 1px solid var(--line-light);
      border-radius: 0.25rem;
      background: var(--panel-soft);
    }
    .sig-mode {
      padding: .42rem .9rem;
      border: 0; border-radius: 0.25rem;
      background: transparent; color: var(--muted-light);
      font: inherit; font-size: .8rem; font-weight: 600; cursor: pointer;
      transition: background-color .2s var(--ease-out), color .2s var(--ease-out);
    }
    .sig-mode:hover { color: var(--light-text); }
    .sig-mode.is-active {
      background: #ffffff; color: var(--light-text);
      box-shadow: 0 1px 2px rgb(17 19 15 / 8%);
    }

    .sig-panel { display: none; }
    .sig-panel.is-active { display: block; }

    /* กล่องลากไฟล์มาวาง */
    .sig-file { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .sig-drop {
      width: 100%;
      min-height: 12rem;
      display: grid;
      place-items: center;
      align-content: center;
      gap: .4rem;
      padding: 1.2rem;
      border: 1.5px dashed var(--line-strong);
      border-radius: 0.28rem;
      background: #ffffff;
      text-align: center;
      cursor: pointer;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out);
    }
    .sig-drop:hover, .sig-drop.is-over { border-color: var(--moss); background: var(--hover-soft); }
    .sig-drop svg { width: 2.1rem; height: 2.1rem; fill: none; stroke: var(--muted-light); stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    .sig-drop-title { color: var(--light-text); font-size: .92rem; font-weight: 600; }
    .sig-drop-hint { max-width: 26rem; color: var(--muted-light); font-size: .76rem; line-height: 1.55; }

    /* พรีวิวหลังลบพื้นหลัง — พื้นตาหมากรุกให้เห็นว่าโปร่งใสจริง */
    .sig-shot {
      width: 100%;
      min-height: 8rem;
      display: grid;
      place-items: center;
      padding: .7rem;
      border: 1px solid var(--line-strong);
      border-radius: 0.28rem;
      background-color: #ffffff;
      background-image:
        linear-gradient(45deg, #eef0ea 25%, transparent 25%),
        linear-gradient(-45deg, #eef0ea 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #eef0ea 75%),
        linear-gradient(-45deg, transparent 75%, #eef0ea 75%);
      background-size: 14px 14px;
      background-position: 0 0, 0 7px, 7px -7px, -7px 0;
    }
    .sig-shot canvas { display: block; max-width: 100%; max-height: 9rem; }

    .sig-mid { min-width: 0; display: inline-flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap; }
    .sig-check {
      display: inline-flex; align-items: center; gap: .4rem;
      color: var(--muted-light); font-size: .78rem; white-space: nowrap; cursor: pointer;
    }
    .sig-check input { accent-color: var(--moss); width: .95rem; height: .95rem; }

    @media (max-width: 620px) {
      .sig-modes { width: 100%; }
      .sig-mode { flex: 1; }
      .sig-mid { width: 100%; justify-content: space-between; }
    }

    @media (max-width: 620px) {
      .sig-head { align-items: flex-start; flex-direction: column; gap: .45rem; }
      .sig-tools { grid-template-columns: 1fr; }
      .sig-tool-group { justify-self: start; }
      .sig-pen { width: 100%; justify-content: space-between; }
      .sig-save { width: 100%; justify-content: center; }
    }

    /* ── Profile card layout: แยกข้อมูลเป็นการ์ดและเปิด modal สำหรับจัดการข้อมูล ── */
    .info-wrap {
      width: min(100%, 76rem);
      margin-top: clamp(.25rem, 1vw, .75rem);
    }

    .profile-board {
      display: grid;
      gap: clamp(1rem, 1.6vw, 1.35rem);
    }

    .employee-card,
    .profile-action-card,
    .profile-dialog {
      border: 1px solid var(--line-light);
      background:
        linear-gradient(135deg, rgb(91 141 239 / 10%), transparent 42%),
        var(--panel);
      box-shadow: 0 1.1rem 2.8rem rgb(0 0 0 / 14%);
    }

    .employee-card {
      overflow: hidden;
      border-radius: 0.38rem;
    }

    /* แถวหัวการ์ด: รูปโปรไฟล์ (กดขยายได้) + ปุ่มเปิดรายละเอียด */
    .employee-card-head {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      align-items: center;
      gap: clamp(1rem, 2vw, 1.45rem);
      padding-left: clamp(1.1rem, 2.2vw, 1.55rem);
      transition: background-color .2s var(--ease-out);
    }

    /* hover ที่ปุ่มให้ไฮไลต์ทั้งแถว เหมือนตอนที่รูปยังอยู่ในปุ่ม */
    .employee-card-head:has(.employee-card-main:hover) { background: var(--hover-soft); }

    .employee-card-main {
      width: 100%;
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: center;
      gap: clamp(1rem, 2vw, 1.45rem);
      padding: clamp(1.1rem, 2.2vw, 1.55rem) clamp(1.1rem, 2.2vw, 1.55rem) clamp(1.1rem, 2.2vw, 1.55rem) 0;
      border: 0;
      background: transparent;
      color: var(--light-text);
      text-align: left;
      cursor: pointer;
    }

    .employee-card-main:hover { background: var(--hover-soft); }

    .employee-card-copy {
      min-width: 0;
      display: grid;
      gap: .35rem;
    }

    .profile-kicker,
    .action-kicker {
      color: var(--moss);
      font-family: "Montserrat", var(--font-body);
      font-size: .7rem;
      font-weight: 800;
      letter-spacing: .12em;
      text-transform: uppercase;
    }

    .employee-code { margin-right: .5rem; color: var(--muted-light); font-weight: 600; }
    .employee-name {
      color: var(--light-text);
      font-size: clamp(1.35rem, 2vw, 1.85rem);
      font-weight: 600;
      line-height: 1.22;
    }

    .employee-subline {
      color: var(--muted-light);
      font-size: .95rem;
      overflow-wrap: anywhere;
    }

    .employee-chevron,
    .action-chevron {
      width: 1.5rem;
      height: 1.5rem;
      display: grid;
      place-items: center;
      color: var(--muted-light);
      transition: color .2s var(--ease-out), transform .2s var(--ease-out);
    }

    .employee-card-main:hover .employee-chevron,
    .profile-action-card:hover .action-chevron {
      color: var(--moss);
      transform: translateX(.12rem);
    }

    .employee-chevron svg,
    .action-chevron svg {
      width: 1.05rem;
      height: 1.05rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* แถวเดียว 5 ช่องเท่ากัน: บริษัท | ตำแหน่ง | แผนก | สถานะ | วันเริ่มงาน
       ไม่มีแถวขาดครึ่งให้ดูรก อ่านไล่ซ้ายไปขวาได้ทีเดียว */
    .employee-stats {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      border-top: 1px solid var(--line-light);
    }
    @media (max-width: 68rem) { .employee-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 44rem) { .employee-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

    .employee-stat {
      min-width: 0;
      padding: .85rem clamp(.9rem, 1.7vw, 1.25rem);
      border-right: 1px solid var(--line-light);
      border-bottom: 1px solid var(--line-light);
    }

    .employee-stat:last-child { border-right: 0; }
    /* แถวสุดท้ายไม่ต้องมีเส้นล่าง — กันเส้นซ้ำกับขอบการ์ด */
    .employee-stats > .employee-stat:nth-last-child(-n+1) { border-bottom: 0; }

    .employee-stat span {
      display: block;
      color: var(--muted-light);
      font-size: .7rem;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .employee-stat strong {
      display: block;
      margin-top: .1rem;
      color: var(--light-text);
      font-size: .94rem;
      font-weight: 500;
      overflow-wrap: anywhere;
    }

    .profile-upload-strip {
      display: flex;
      justify-content: flex-end;
      padding: .85rem clamp(.9rem, 1.7vw, 1.25rem);
      border-top: 1px solid var(--line-light);
      background: var(--panel-soft);
    }

    .profile-action-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: clamp(.85rem, 1.4vw, 1rem);
    }

    .profile-action-card {
      min-height: 12.4rem;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      justify-content: space-between;
      gap: 1rem;
      padding: clamp(1rem, 1.7vw, 1.2rem);
      border-radius: 0.34rem;
      color: var(--light-text);
      text-align: left;
      cursor: pointer;
      transition: border-color .2s var(--ease-out), transform .2s var(--ease-out), background-color .2s var(--ease-out);
    }

    .profile-action-card:hover:not(:disabled) {
      border-color: var(--moss);
      transform: translateY(-2px);
    }

    .profile-action-card:disabled {
      cursor: not-allowed;
      opacity: .55;
    }

    /* หัวข้อกับลูกศรอยู่บรรทัดเดียวกัน (Manager สั่ง) */
    .action-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .8rem;
    }


    .action-title {
      display: block;
      margin-top: .85rem;
      color: var(--light-text);
      font-size: 1.1rem;
      font-weight: 600;
      line-height: 1.25;
    }

    .action-value {
      display: block;
      margin-top: .35rem;
      color: var(--muted-light);
      font-size: .88rem;
      overflow-wrap: anywhere;
    }

    .action-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .8rem;
      padding-top: .9rem;
      border-top: 1px solid var(--line-light);
      color: var(--muted-light);
      font-size: .78rem;
    }

    .action-signature-preview {
      min-height: 3.2rem;
      display: grid;
      place-items: center;
      margin-top: .65rem;
      padding: .55rem;
      border: 1px dashed var(--line-light);
      border-radius: 0.26rem;
      background: #ffffff;
      color: rgb(17 19 15 / 48%);
      font-size: .82rem;
    }

    .action-signature-preview img {
      max-width: 100%;
      max-height: 2.4rem;
      width: auto;
      margin: 0 auto;
    }

    .profile-modal {
      position: fixed;
      inset: 0;
      z-index: 91;
      display: grid;
      place-items: center;
      padding: 1.25rem;
      background: var(--overlay-bg);
      backdrop-filter: blur(4px);
      opacity: 0;
      visibility: hidden;
      transition: opacity .25s var(--ease-out), visibility .25s;
    }

    .profile-modal.is-open {
      opacity: 1;
      visibility: visible;
    }

    .profile-dialog {
      width: min(100%, 32rem);
      max-height: calc(100dvh - 2.5rem);
      overflow: auto;
      padding: clamp(1.45rem, 2.6vw, 2rem);
      border-radius: 0.38rem;
      transform: translateY(.6rem);
      transition: transform .25s var(--ease-out);
    }

    .profile-dialog.is-wide { width: min(100%, 45rem); }
    .profile-dialog.is-signature { width: min(100%, 48rem); }
    .profile-modal.is-open .profile-dialog { transform: none; }

    .profile-dialog-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.25rem;
    }

    .profile-dialog-title {
      display: grid;
      gap: .25rem;
    }

    .profile-dialog-title h2 {
      color: var(--light-text);
      font-size: clamp(1.25rem, 2vw, 1.55rem);
      font-weight: 600;
      line-height: 1.25;
    }

    .profile-dialog-title p {
      color: var(--muted-light);
      font-size: .9rem;
    }

    .profile-close {
      width: 2rem;
      height: 2rem;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 50%;
      background: transparent;
      color: var(--muted-light);
      cursor: pointer;
      transition: background-color .2s var(--ease-out), color .2s var(--ease-out);
    }

    .profile-close:hover {
      background: var(--hover-soft);
      color: var(--light-text);
    }

    .profile-close svg {
      width: 1.2rem;
      height: 1.2rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.8;
      stroke-linecap: round;
    }

    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .75rem 1rem;
    }

    .detail-item {
      padding: .85rem .9rem;
      border: 1px solid var(--line-light);
      border-radius: 0.28rem;
      background: var(--hover-soft);
    }

    .detail-item dt {
      color: var(--muted-light);
      font-size: .68rem;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .detail-item dd {
      margin-top: .15rem;
      color: var(--light-text);
      font-size: .96rem;
      overflow-wrap: anywhere;
    }

    .profile-modal .email-form {
      align-items: stretch;
      flex-direction: column;
    }

    .profile-modal .email-form input,
    .profile-modal .email-save {
      width: 100%;
    }

    .profile-modal .sig-section {
      margin-top: 0;
      padding: 0;
      border-bottom: 0;
    }

    .profile-modal .sig-editor {
      width: 100%;
    }

    .profile-modal .sig-current img {
      max-height: 4.4rem;
    }

    @media (max-width: 900px) {
      .employee-stats,
      .profile-action-grid {
        grid-template-columns: 1fr 1fr;
      }

      .profile-action-grid .profile-action-card:last-child {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 620px) {
      .employee-card-main {
        grid-template-columns: auto minmax(0, 1fr);
      }

      .employee-chevron {
        grid-column: 1 / -1;
        justify-self: end;
      }

      .employee-stats,
      .profile-action-grid,
      .detail-grid {
        grid-template-columns: 1fr;
      }

      .employee-stat {
        border-right: 0;
        border-bottom: 1px solid var(--line-light);
      }

      .employee-stat:last-child { border-bottom: 0; }
      .profile-upload-strip { justify-content: flex-start; }
    }
@endsection

@section('content')
  <div class="info-wrap">
    <section class="profile-board" aria-label="ข้อมูลผู้ใช้งาน" data-i18n-aria="profile.boardAria">
      <article class="employee-card">
        {{-- รูปอยู่นอกปุ่มเปิด modal เพราะซ้อน button ใน button ไม่ได้ --}}
        <div class="employee-card-head">
          @if ($avatarUrl)
            <button type="button" class="avatar-lg"
                    data-image-preview
                    data-image-src="{{ $avatarUrl }}"
                    data-image-alt="{{ $nameTh ?: $nameEn ?: $me->employee_code }}"
                    data-i18n-aria="profile.view_photo"
                    aria-label="ดูรูปโปรไฟล์ขนาดใหญ่">
              <img src="{{ $avatarUrl }}" alt="">
            </button>
          @else
            <span class="avatar-lg avatar-placeholder" aria-hidden="true">
              <svg viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="18" r="9"></circle>
                <path d="M8 42c2.6-9.4 9-14.5 16-14.5S37.4 32.6 40 42H8Z"></path>
              </svg>
            </span>
          @endif

          <button type="button" class="employee-card-main" data-profile-modal-open="employeeModal">
            <span class="employee-card-copy">
              <span class="profile-kicker" data-i18n="info.title">ข้อมูลผู้ใช้งาน</span>
              {{-- รหัสพนักงานนำหน้าชื่อ ตามที่ Manager สั่ง --}}
              <span class="employee-name">
                <span class="employee-code">{{ $dash($me->employee_code) }}</span>
                <span data-val="th">{{ $dash($nameTh ?: $nameEn) }}</span>
                <span data-val="en">{{ $dash($nameEn ?: $nameTh) }}</span>
              </span>
            </span>

            <span class="employee-chevron" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg>
            </span>
          </button>
        </div>

        <div class="employee-stats">
          <div class="employee-stat">
            <span data-i18n="field.company">บริษัท</span>
            <strong>{{ $dash($companyDisplay) }}</strong>
          </div>
          {{-- ตำแหน่งอยู่คู่กับบริษัท ตามที่ Manager สั่ง (เดิมอยู่ใต้ชื่อซ้ำกับ subline) --}}
          <div class="employee-stat">
            <span data-i18n="field.position">ตำแหน่ง</span>
            <strong>{{ $dash($posEn ?: $posTh) }}</strong>
          </div>
          <div class="employee-stat">
            <span data-i18n="field.department">แผนก</span>
            <strong>{{ $dash($deptDisplay) }}</strong>
          </div>
          <div class="employee-stat">
            <span data-i18n="field.status">สถานะการทำงาน</span>
            <strong>
              <span class="status-line {{ $isResigned ? 'is-resigned' : '' }}" data-i18n="{{ $isResigned ? 'profile.resigned' : 'profile.active' }}">{{ $isResigned ? 'ลาออกแล้ว' : 'กำลังทำงาน' }}</span>
            </strong>
          </div>
          <div class="employee-stat">
            <span data-i18n="field.hire">วันเริ่มงาน</span>
            <strong>{{ $hireDate }}</strong>
          </div>
        </div>

        {{-- ซ่อนปุ่มอัปโหลดรูป (2026-08-06): รูปพนักงานมาจากคลังกลาง Z drive แล้ว
             ไม่ให้ผู้ใช้อัปโหลดเอง — route profile.picture ยังอยู่ ถ้าจะเปิดใช้ใหม่ให้เอาคอมเมนต์ออก --}}
        {{--
        <div class="profile-upload-strip">
          <form method="POST" action="{{ route('profile.picture') }}" enctype="multipart/form-data" id="avatarForm">
            @csrf
            <label class="avatar-upload">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5"></path><path d="M12 3v12"></path></svg>
              <span data-i18n="profile.upload">อัปโหลดรูป</span>
              <input type="file" name="picture" accept=".jpg,.jpeg,.png,.webp,.heic,.heif,image/*" id="avatarInput">
            </label>
          </form>
          @error('picture') <div class="pwd-msg err" style="margin-top:.4rem">{{ $message }}</div> @enderror
        </div>
        --}}
      </article>

      <div class="profile-action-grid">
        <button type="button" class="profile-action-card" id="pwdOpen">
          <span>
            <span class="action-head">
              <span class="action-title" data-i18n="pwd.title">รหัสผ่าน</span>
              <span class="action-chevron" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></span>
            </span>
            <span class="action-value">
              <span class="pwd-dots" aria-label="รหัสผ่านถูกซ่อนไว้" title="รหัสผ่านถูกซ่อนไว้" data-i18n-aria="pwd.masked" data-i18n-title="pwd.masked">
                <i></i><i></i><i></i><i></i><i></i><i></i>
              </span>
            </span>
          </span>
          <span class="action-footer">
            <span data-i18n="pwd.change">เปลี่ยนรหัสผ่าน</span>
          </span>
        </button>

        <button type="button" class="profile-action-card" data-profile-modal-open="emailModal" {{ $emailAllowed ? '' : 'disabled' }}>
          <span>
            <span class="action-head">
              <span class="action-title" data-i18n="field.email">อีเมล</span>
              <span class="action-chevron" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></span>
            </span>
            <span class="action-value" id="emailCardValue" @if (! $me->email) data-i18n="profile.noEmail" @endif>{{ $me->email ?: 'ยังไม่ได้กรอกอีเมล' }}</span>
          </span>
          <span class="action-footer">
            <span data-i18n="{{ $emailAllowed ? 'profile.manageEmail' : 'profile.noEditAccess' }}">{{ $emailAllowed ? 'จัดการอีเมล' : 'ยังไม่มีสิทธิ์แก้ไข' }}</span>
          </span>
        </button>

        <button type="button" class="profile-action-card" data-profile-modal-open="sigModal" {{ $sigAllowed ? '' : 'disabled' }}>
          <span>
            <span class="action-head">
              <span class="action-title" data-i18n="sig.title">ลายเซ็น</span>
              <span class="action-chevron" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></span>
            </span>
            <span class="action-signature-preview">
              <img id="sigCardPreviewImg" src="{{ $me->signature ?: '' }}" alt="signature preview" style="{{ $me->signature ? '' : 'display:none' }}">
              <span id="sigCardEmpty" style="{{ $me->signature ? 'display:none' : '' }}" data-i18n="profile.noSignature">ยังไม่มีลายเซ็น</span>
            </span>
          </span>
          <span class="action-footer">
            <span id="sigCardStatus" data-i18n="{{ $me->signature ? 'sig.saved' : ($sigAllowed ? 'profile.addSignature' : 'profile.noEditAccess') }}">{{ $me->signature ? 'บันทึกลายเซ็นแล้ว' : ($sigAllowed ? 'เพิ่มลายเซ็น' : 'ยังไม่มีสิทธิ์แก้ไข') }}</span>
          </span>
        </button>
      </div>
    </section>
  </div>

  {{-- ── Modal รายละเอียดพนักงาน ── --}}
  <div class="profile-modal" id="employeeModal" role="dialog" aria-modal="true" aria-labelledby="employeeModalTitle">
    <div class="profile-dialog is-wide">
      <div class="profile-dialog-head">
        <div class="profile-dialog-title">
          <span class="profile-kicker" data-i18n="info.kicker">โปรไฟล์</span>
          <h2 id="employeeModalTitle" data-i18n="info.title">ข้อมูลผู้ใช้งาน</h2>
          <p data-i18n="profile.employeeSource">ข้อมูลพนักงานจากระบบกลาง</p>
        </div>
        <button type="button" class="profile-close" data-profile-modal-close aria-label="ปิด" data-i18n-aria="common.close">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>

      <dl class="detail-grid">
        <div class="detail-item">
          <dt data-i18n="field.name">ชื่อ-สกุล</dt>
          <dd><span data-val="th">{{ $dash($nameTh ?: $nameEn) }}</span><span data-val="en">{{ $dash($nameEn ?: $nameTh) }}</span></dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="field.company">บริษัท</dt>
          <dd>{{ $dash($companyDisplay) }}</dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="profile.employeeCode">รหัสพนักงาน</dt>
          <dd>{{ $me->employee_code }}</dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="field.department">แผนก</dt>
          <dd><span data-val="th">{{ $dash($deptTh ?: $deptEn) }}</span><span data-val="en">{{ $dash($deptEnDisplay) }}</span></dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="field.position">ตำแหน่ง</dt>
          <dd><span data-val="th">{{ $dash($posTh ?: $posEn) }}</span><span data-val="en">{{ $dash($posEn ?: $posTh) }}</span></dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="field.status">สถานะการทำงาน</dt>
          <dd>
            @if ($isResigned)
              <span data-i18n="profile.resigned">ลาออกแล้ว</span>
            @else
              <span data-i18n="profile.active">กำลังทำงาน</span>
            @endif
          </dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="field.hire">วันเริ่มงาน</dt>
          <dd>{{ $hireDate }}</dd>
        </div>
        <div class="detail-item">
          <dt data-i18n="field.email">อีเมล</dt>
          <dd>{{ $me->email ?: '-' }}</dd>
        </div>
      </dl>
    </div>
  </div>

  @if ($emailAllowed)
  {{-- ── Modal อีเมล ── --}}
  <div class="profile-modal" id="emailModal" role="dialog" aria-modal="true" aria-labelledby="emailModalTitle">
    <div class="profile-dialog">
      <div class="profile-dialog-head">
        <div class="profile-dialog-title">
          <span class="profile-kicker" data-i18n="field.email">อีเมล</span>
          <h2 id="emailModalTitle" data-i18n="profile.manageEmail">จัดการอีเมล</h2>
          <p data-i18n="profile.emailHelp">ใช้สำหรับการแจ้งเตือนภายในระบบ</p>
        </div>
        <button type="button" class="profile-close" data-profile-modal-close aria-label="ปิด" data-i18n-aria="common.close">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>

      <form class="email-form" id="emailForm">
        <input type="email" id="emailInput" name="email" value="{{ $me->email }}" data-modal-focus
               placeholder="กรอกอีเมล" data-i18n-placeholder="email.ph" autocomplete="email">
        <button type="submit" class="email-save" id="emailSave" data-i18n="email.save">บันทึก</button>
      </form>
      <span class="email-msg" id="emailMsg"></span>
    </div>
  </div>
  @endif

  @if ($sigAllowed)
  {{-- ── Modal ลายเซ็น ── --}}
  <div class="profile-modal" id="sigModal" role="dialog" aria-modal="true" aria-labelledby="sigModalTitle">
    <div class="profile-dialog is-signature">
      <div class="profile-dialog-head">
        <div class="profile-dialog-title">
          <span class="profile-kicker" data-i18n="sig.title">ลายเซ็น</span>
          <h2 id="sigModalTitle" data-i18n="profile.manageSignature">จัดการลายเซ็น</h2>
          <p data-i18n="profile.signatureHelp">เซ็นด้วยเมาส์หรือนิ้ว หรือแนบรูปลายเซ็นที่มีอยู่แล้ว แล้วบันทึกไว้ใช้กับเอกสาร</p>
        </div>
        <button type="button" class="profile-close" data-profile-modal-close aria-label="ปิด" data-i18n-aria="common.close">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>

      <section class="sig-section" aria-label="ลายเซ็น" data-i18n-aria="sig.title">
        <div class="sig-head">
          <span class="sig-kicker" data-i18n="sig.title">ลายเซ็น</span>
          <span class="sig-saved" id="sigSavedLabel" data-i18n="sig.saved" style="{{ $me->signature ? '' : 'display:none' }}">บันทึกลายเซ็นแล้ว</span>
        </div>

        <div class="sig-editor">
          <div class="sig-current" id="sigCurrent" style="{{ $me->signature ? '' : 'display:none' }}">
            <img id="sigCurrentImg" src="{{ $me->signature }}" alt="signature">
          </div>

          {{-- เลือกวิธี: เซ็นเองบนหน้าจอ หรือแนบไฟล์ภาพลายเซ็น --}}
          <div class="sig-modes" role="group" aria-label="วิธีเพิ่มลายเซ็น" data-i18n-aria="profile.signatureMethod">
            <button type="button" class="sig-mode is-active" id="sigModeDrawBtn" data-i18n="sig.modeDraw">เซ็นเอง</button>
            <button type="button" class="sig-mode" id="sigModeUploadBtn" data-i18n="sig.modeUpload">แนบไฟล์ภาพ</button>
          </div>

          <div class="sig-panel is-active" id="sigPanelDraw">
          <div class="sig-pad-wrap" id="sigPadWrap">
            <canvas id="sigCanvas" class="sig-canvas" tabindex="0" data-modal-focus></canvas>
            <span class="sig-hint" id="sigHint" data-i18n="sig.hint">เซ็นที่นี่ (เมาส์หรือนิ้ว)</span>
          </div>

          <div class="sig-tools">
            <div class="sig-tool-group">
              <button type="button" class="sig-btn" id="sigUndo" title="ย้อนกลับ" data-i18n-title="sig.undo" data-i18n-aria="sig.undo" aria-label="ย้อนกลับ" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 14 4 9l5-5"></path><path d="M4 9h11a5 5 0 0 1 0 10h-1"></path></svg>
              </button>
              <button type="button" class="sig-btn" id="sigRedo" title="ทำซ้ำ" data-i18n-title="sig.redo" data-i18n-aria="sig.redo" aria-label="ทำซ้ำ" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 14 5-5-5-5"></path><path d="M20 9H9a5 5 0 0 0 0 10h1"></path></svg>
              </button>
              <button type="button" class="sig-btn" id="sigClear" title="ลบทั้งหมด" data-i18n-title="sig.clear" data-i18n-aria="sig.clear" aria-label="ลบทั้งหมด" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
              </button>
            </div>

            <label class="sig-pen">
              <span data-i18n="sig.pen">ขนาดปากกา</span>
              <input type="range" id="sigPen" min="1.5" max="8" step="0.5" value="2.5" aria-label="ขนาดปากกา" data-i18n-aria="sig.pen">
            </label>

            <button type="button" class="sig-save" id="sigSave" disabled>
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
              <span data-i18n="sig.save">บันทึกลายเซ็น</span>
            </button>
          </div>
          </div>

          {{-- แนบไฟล์ภาพลายเซ็น: ถ่ายจากกระดาษ · แคปหน้าจอปากกาอิเล็กทรอนิกส์ · รูปที่มีอยู่แล้ว --}}
          <div class="sig-panel" id="sigPanelUpload">
            <input type="file" class="sig-file" id="sigFile" accept="image/png,image/jpeg,image/jpg,image/heic,image/heif,image/*">

            <div class="sig-drop" id="sigDrop" role="button" tabindex="0">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <path d="m7 9 5-5 5 5"></path>
                <path d="M12 4v12"></path>
              </svg>
              <span class="sig-drop-title" data-i18n="sig.dropTitle">ลากรูปมาวาง หรือกดเพื่อเลือกไฟล์</span>
              <span class="sig-drop-hint" data-i18n="sig.dropHint">รองรับ PNG · JPG · HEIC (ไอโฟน) — จะเป็นรูปถ่ายลายเซ็นบนกระดาษ หรือภาพที่เซ็นด้วยปากกาอิเล็กทรอนิกส์ก็ได้ ระบบจะลบพื้นหลังและตัดเอาเฉพาะลายเซ็นให้อัตโนมัติ</span>
            </div>

            <div class="sig-shot" id="sigShot" hidden>
              <canvas id="sigShotCanvas"></canvas>
            </div>

            <div class="sig-tools" id="sigUploadTools" hidden>
              <div class="sig-tool-group">
                <button type="button" class="sig-btn" id="sigPickAgain" title="เลือกรูปอื่น" data-i18n-title="sig.pick" data-i18n-aria="sig.pick" aria-label="เลือกรูปอื่น">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h4l2-3h6l2 3h4v13H3z"></path><circle cx="12" cy="13" r="3.5"></circle></svg>
                </button>
              </div>

              <div class="sig-mid">
                <label class="sig-pen">
                  <span data-i18n="sig.level">ความเข้ม</span>
                  <input type="range" id="sigLevel" min="12" max="80" step="2" value="38" aria-label="ความเข้ม" data-i18n-aria="sig.level">
                </label>
                <label class="sig-check">
                  <input type="checkbox" id="sigInkBlack" checked>
                  <span data-i18n="sig.inkBlack">หมึกสีดำ</span>
                </label>
              </div>

              <button type="button" class="sig-save" id="sigSaveUpload" disabled>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
                <span data-i18n="sig.save">บันทึกลายเซ็น</span>
              </button>
            </div>
          </div>

          <div class="sig-msg" id="sigMsg" aria-live="polite"></div>
        </div>
      </section>
    </div>
  </div>
  @endif

  {{-- ── Modal เปลี่ยนรหัสผ่าน ── --}}
  <div class="pwd-modal {{ $hasPwdError ? 'is-open' : '' }}" id="pwdModal" role="dialog" aria-modal="true" aria-labelledby="pwdModalTitle">
    <div class="pwd-dialog">
      <div class="pwd-dialog-head">
        <h2 id="pwdModalTitle" data-i18n="pwd.change">เปลี่ยนรหัสผ่าน</h2>
        <button type="button" class="pwd-close" id="pwdClose" aria-label="ปิด" data-i18n-aria="common.close">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>

      <form method="POST" action="{{ route('password.update') }}" id="pwdForm">
        @csrf
        @method('PUT')

        <div class="pwd-field">
          <label for="id_card" data-i18n="pwd.idcard">เลขบัตรประชาชน</label>
          <div class="pwd-id-row">
            <input type="text" id="id_card" name="id_card" inputmode="numeric" autocomplete="off"
                   value="{{ old('id_card') }}"
                   placeholder="กรอกเลขบัตรประชาชนเพื่อยืนยันตัวตน" data-i18n-placeholder="pwd.idcard_ph">
            <button type="button" class="pwd-verify-btn" id="pwdVerify" data-i18n="pwd.verify">ตรวจสอบ</button>
          </div>
          <div class="pwd-msg {{ $errors->has('id_card') ? 'err' : '' }}" id="pwdMsg">
            @error('id_card') {{ $message }} @enderror
          </div>
        </div>

        <div class="pwd-field">
          <label for="password" data-i18n="pwd.new">รหัสผ่านใหม่</label>
          <div class="pwd-input-wrap">
            <input type="password" id="password" name="password" autocomplete="new-password" {{ $hasPwdError ? '' : 'disabled' }}
                   placeholder="อย่างน้อย 6 ตัวอักษร" data-i18n-placeholder="pwd.new_ph">
            <button class="pwd-eye-btn" type="button" aria-controls="password" aria-label="แสดงรหัสผ่านใหม่" aria-pressed="false" data-pwd-toggle data-pwd-label="pwd.new" {{ $hasPwdError ? '' : 'disabled' }}>
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
          @error('password') <div class="pwd-msg err">{{ $message }}</div> @enderror
        </div>

        <div class="pwd-field">
          <label for="password_confirmation" data-i18n="pwd.confirm">ยืนยันรหัสผ่านใหม่</label>
          <div class="pwd-input-wrap">
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" {{ $hasPwdError ? '' : 'disabled' }}
                   placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" data-i18n-placeholder="pwd.confirm_ph">
            <button class="pwd-eye-btn" type="button" aria-controls="password_confirmation" aria-label="แสดงยืนยันรหัสผ่านใหม่" aria-pressed="false" data-pwd-toggle data-pwd-label="pwd.confirm" {{ $hasPwdError ? '' : 'disabled' }}>
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
        </div>

        <button type="submit" class="pwd-submit" id="pwdSubmit" {{ $hasPwdError ? '' : 'disabled' }} data-i18n="pwd.submit">บันทึกรหัสผ่าน</button>
      </form>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      var openButtons = document.querySelectorAll('[data-profile-modal-open]');
      var closeButtons = document.querySelectorAll('[data-profile-modal-close]');
      var lastFocus = null;

      function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
      }

      function closeAll() {
        document.querySelectorAll('.profile-modal.is-open').forEach(closeModal);
      }

      function openModal(id, trigger) {
        var modal = document.getElementById(id);
        if (!modal) return;
        lastFocus = trigger || document.activeElement;
        modal.classList.add('is-open');

        if (id === 'sigModal' && window.__fitSignatureCanvas) {
          window.setTimeout(window.__fitSignatureCanvas, 60);
        }

        var firstInput = modal.querySelector('[data-modal-focus]') || modal.querySelector('input, textarea, canvas') || modal.querySelector('button');
        if (firstInput && firstInput.focus) {
          window.setTimeout(function () {
            try { firstInput.focus({ preventScroll: true }); } catch (e) { firstInput.focus(); }
          }, 80);
        }
      }

      openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          if (button.disabled) return;
          openModal(button.getAttribute('data-profile-modal-open'), button);
        });
      });

      closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          closeModal(button.closest('.profile-modal'));
          if (lastFocus && lastFocus.focus) {
            try { lastFocus.focus({ preventScroll: true }); } catch (e) { lastFocus.focus(); }
          }
        });
      });

      document.querySelectorAll('.profile-modal').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
          if (event.target === modal) closeModal(modal);
        });
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeAll();
      });
    })();
  </script>

  <script>
    'use strict';
    (function () {
      var modal = document.getElementById('pwdModal');
      var openBtn = document.getElementById('pwdOpen');
      var closeBtn = document.getElementById('pwdClose');
      var verifyBtn = document.getElementById('pwdVerify');
      var idCard = document.getElementById('id_card');
      var pwd = document.getElementById('password');
      var confirm = document.getElementById('password_confirmation');
      var submit = document.getElementById('pwdSubmit');
      var msg = document.getElementById('pwdMsg');
      var eyeButtons = document.querySelectorAll('[data-pwd-toggle]');

      // อัปโหลดรูปโปรไฟล์อัตโนมัติเมื่อเลือกไฟล์
      var avatarInput = document.getElementById('avatarInput');
      if (avatarInput) {
        avatarInput.addEventListener('change', function () {
          if (avatarInput.files && avatarInput.files.length) {
            document.getElementById('avatarForm').submit();
          }
        });
      }

      if (!modal) return;

      var verifyUrl = "{{ route('password.verify') }}";
      var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      function dict(key) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var map = (window.__portalCopy && window.__portalCopy[lang]) || {};
        return map[key] || key;
      }

      function openModal() { modal.classList.add('is-open'); idCard && idCard.focus(); }
      function closeModal() { modal.classList.remove('is-open'); }

      function lockFields(locked) {
        pwd.disabled = locked; confirm.disabled = locked; submit.disabled = locked;
        eyeButtons.forEach(function (button) {
          var input = document.getElementById(button.getAttribute('aria-controls'));
          button.disabled = locked || !input || input.disabled;
          if (locked && input) {
            input.type = 'password';
            button.setAttribute('aria-pressed', 'false');
          }
          updateEyeLabel(button);
        });
      }

      function updateEyeLabel(button) {
        var input = document.getElementById(button.getAttribute('aria-controls'));
        var actionKey = input && input.type === 'text' ? 'pwd.hide' : 'pwd.show';
        var targetKey = button.getAttribute('data-pwd-label');
        button.setAttribute('aria-label', dict(actionKey) + ' ' + dict(targetKey));
      }

      function initPasswordEyes() {
        eyeButtons.forEach(function (button) {
          var input = document.getElementById(button.getAttribute('aria-controls'));
          if (!input) return;

          button.disabled = input.disabled;
          updateEyeLabel(button);

          button.addEventListener('click', function () {
            var shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
            updateEyeLabel(button);

            try {
              input.focus({ preventScroll: true });
            } catch (e) {
              input.focus();
            }
          });
        });

        if (window.MutationObserver) {
          new MutationObserver(function () {
            eyeButtons.forEach(updateEyeLabel);
          }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-lang'] });
        }
      }

      openBtn && openBtn.addEventListener('click', openModal);
      closeBtn && closeBtn.addEventListener('click', closeModal);
      modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
      initPasswordEyes();

      // ตรวจสอบเลขบัตรกับ server ก่อนปลดล็อกช่องรหัสใหม่
      verifyBtn && verifyBtn.addEventListener('click', function () {
        var val = (idCard.value || '').trim();
        msg.className = 'pwd-msg';
        msg.textContent = '';
        if (val.length < 6) { lockFields(true); return; }

        fetch(verifyUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: JSON.stringify({ id_card: val })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.ok) {
            msg.className = 'pwd-msg ok';
            msg.textContent = dict('pwd.verified');
            lockFields(false);
            pwd.focus();
          } else {
            msg.className = 'pwd-msg err';
            msg.textContent = dict('pwd.idcard_wrong');
            lockFields(true);
          }
        })
        .catch(function () {
          msg.className = 'pwd-msg err';
          msg.textContent = dict('pwd.idcard_wrong');
          lockFields(true);
        });
      });

      // ถ้าแก้เลขบัตรหลังตรวจผ่านแล้ว -> ล็อกกลับ ต้องตรวจใหม่
      idCard && idCard.addEventListener('input', function () {
        if (!pwd.disabled) { lockFields(true); msg.className = 'pwd-msg'; msg.textContent = ''; }
      });
    })();
  </script>

  {{-- ── แผ่นเซ็นลายเซ็น (mouse/touch) ── --}}
  <script>
    'use strict';
    (function () {
      var canvas = document.getElementById('sigCanvas');
      if (!canvas) return;
      var ctx = canvas.getContext('2d');
      var padWrap = document.getElementById('sigPadWrap');
      var penInput = document.getElementById('sigPen');
      var undoBtn = document.getElementById('sigUndo');
      var redoBtn = document.getElementById('sigRedo');
      var clearBtn = document.getElementById('sigClear');
      var saveBtn = document.getElementById('sigSave');
      var msg = document.getElementById('sigMsg');
      var curWrap = document.getElementById('sigCurrent');
      var curImg = document.getElementById('sigCurrentImg');
      var savedLabel = document.getElementById('sigSavedLabel');
      var cardPreview = document.getElementById('sigCardPreviewImg');
      var cardEmpty = document.getElementById('sigCardEmpty');
      var cardStatus = document.getElementById('sigCardStatus');
      var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      var saveUrl = "{{ route('profile.signature') }}";

      var INK = '#161616';      // หมึกเข้ม (มองเห็นบนเอกสารพื้นขาว)
      var strokes = [];          // [{size, points:[{x,y}]}]
      var redoStack = [];
      var drawing = false;
      var cur = null;
      var dpr = Math.max(1, window.devicePixelRatio || 1);

      function dictOf(key, fb) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var m = (window.__portalCopy && window.__portalCopy[lang]) || {};
        return m[key] || fb;
      }

      function fitCanvas() {
        var rect = canvas.getBoundingClientRect();
        if (!rect.width) return;
        canvas.width = Math.round(rect.width * dpr);
        canvas.height = Math.round(rect.height * dpr);
        redraw();
      }

      function drawStroke(c, s) {
        var p = s.points;
        if (!p.length) return;
        c.lineWidth = s.size;
        if (p.length === 1) {
          c.beginPath(); c.arc(p[0].x, p[0].y, s.size / 2, 0, Math.PI * 2); c.fillStyle = INK; c.fill(); return;
        }
        c.beginPath(); c.moveTo(p[0].x, p[0].y);
        for (var i = 1; i < p.length; i++) c.lineTo(p[i].x, p[i].y);
        c.stroke();
      }

      function redraw() {
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.strokeStyle = INK;
        strokes.forEach(function (s) { drawStroke(ctx, s); });
        updateState();
      }

      function pos(e) {
        var r = canvas.getBoundingClientRect();
        return { x: e.clientX - r.left, y: e.clientY - r.top };
      }

      function start(e) {
        if (e.button != null && e.button !== 0 && e.pointerType === 'mouse') return;
        e.preventDefault();
        drawing = true;
        redoStack = [];
        cur = { size: parseFloat(penInput.value), points: [pos(e)] };
        strokes.push(cur);
        if (canvas.setPointerCapture && e.pointerId != null) { try { canvas.setPointerCapture(e.pointerId); } catch (x) {} }
        redraw();
      }
      function move(e) {
        if (!drawing) return;
        e.preventDefault();
        cur.points.push(pos(e));
        redraw();
      }
      function end() { if (!drawing) return; drawing = false; cur = null; updateState(); }

      canvas.addEventListener('pointerdown', start);
      canvas.addEventListener('pointermove', move);
      window.addEventListener('pointerup', end);

      undoBtn.addEventListener('click', function () { if (strokes.length) { redoStack.push(strokes.pop()); redraw(); } });
      redoBtn.addEventListener('click', function () { if (redoStack.length) { strokes.push(redoStack.pop()); redraw(); } });
      clearBtn.addEventListener('click', function () { strokes = []; redoStack = []; redraw(); });

      function hasInk() { return strokes.some(function (s) { return s.points.length; }); }

      function updateState() {
        var ink = hasInk();
        padWrap.classList.toggle('has-ink', ink);
        undoBtn.disabled = !strokes.length;
        redoBtn.disabled = !redoStack.length;
        clearBtn.disabled = !strokes.length;
        saveBtn.disabled = !ink;
      }

      // ตัดพื้นหลัง: หา bounding box ของเส้น แล้ว export เฉพาะส่วนนั้นเป็น PNG โปร่งใส
      function exportTrimmed() {
        var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        strokes.forEach(function (s) {
          var r = s.size / 2 + 1;
          s.points.forEach(function (p) {
            if (p.x - r < minX) minX = p.x - r;
            if (p.y - r < minY) minY = p.y - r;
            if (p.x + r > maxX) maxX = p.x + r;
            if (p.y + r > maxY) maxY = p.y + r;
          });
        });
        if (!isFinite(minX)) return null;
        var pad = 6;
        minX = Math.max(0, minX - pad); minY = Math.max(0, minY - pad); maxX += pad; maxY += pad;
        var w = Math.max(1, Math.round(maxX - minX)), h = Math.max(1, Math.round(maxY - minY));
        var off = document.createElement('canvas');
        off.width = Math.round(w * dpr); off.height = Math.round(h * dpr);
        var oc = off.getContext('2d');
        oc.setTransform(dpr, 0, 0, dpr, 0, 0);
        oc.translate(-minX, -minY);
        oc.lineJoin = 'round'; oc.lineCap = 'round'; oc.strokeStyle = INK;
        strokes.forEach(function (s) { drawStroke(oc, s); });
        return off.toDataURL('image/png');
      }

      // ── บันทึกลายเซ็น (ใช้ร่วมกันทั้งแบบเซ็นเองและแบบแนบไฟล์ภาพ) ──
      function saveSignature(data, btn) {
        msg.className = 'sig-msg'; msg.textContent = '';
        if (!data) return;
        if (btn) btn.disabled = true;
        fetch(saveUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: JSON.stringify({ signature: data })
        })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (d && d.ok) {
              msg.className = 'sig-msg ok'; msg.textContent = dictOf('sig.savedOk', 'บันทึกลายเซ็นแล้ว');
              curImg.src = data; curWrap.style.display = ''; if (savedLabel) savedLabel.style.display = '';
              if (cardPreview) { cardPreview.src = data; cardPreview.style.display = ''; }
              if (cardEmpty) cardEmpty.style.display = 'none';
              if (cardStatus) {
                cardStatus.setAttribute('data-i18n', 'sig.saved');
                cardStatus.textContent = dictOf('sig.saved', 'บันทึกลายเซ็นแล้ว');
              }
            } else {
              msg.className = 'sig-msg err'; msg.textContent = dictOf('sig.error', 'บันทึกไม่สำเร็จ');
            }
            updateState();
            updateUploadState();
          })
          .catch(function () {
            msg.className = 'sig-msg err'; msg.textContent = dictOf('sig.error', 'บันทึกไม่สำเร็จ');
            updateState();
            updateUploadState();
          });
      }

      saveBtn.addEventListener('click', function () { saveSignature(exportTrimmed(), saveBtn); });

      /* ─────────────────────────────────────────────────────────────
         แนบไฟล์ภาพลายเซ็น
         เครื่องนี้ไม่มี GD/Imagick จึงประมวลผลรูปด้วย canvas ในเบราว์เซอร์
         แล้วส่งเป็น PNG โปร่งใสเหมือนแบบเซ็นเอง หลังบ้านจึงไม่ต้องแก้อะไร
         ───────────────────────────────────────────────────────────── */
      var modeDrawBtn = document.getElementById('sigModeDrawBtn');
      var modeUploadBtn = document.getElementById('sigModeUploadBtn');
      var panelDraw = document.getElementById('sigPanelDraw');
      var panelUpload = document.getElementById('sigPanelUpload');
      var fileInput = document.getElementById('sigFile');
      var dropZone = document.getElementById('sigDrop');
      var shotWrap = document.getElementById('sigShot');
      var shotCanvas = document.getElementById('sigShotCanvas');
      var uploadTools = document.getElementById('sigUploadTools');
      var levelInput = document.getElementById('sigLevel');
      var inkBlackInput = document.getElementById('sigInkBlack');
      var uploadSave = document.getElementById('sigSaveUpload');
      var pickAgain = document.getElementById('sigPickAgain');

      var workCanvas = null;   // ภาพต้นฉบับที่ย่อขนาดแล้ว
      var uploadData = null;   // PNG data URL ที่พร้อมบันทึก
      var srcData = null;      // ImageData ของรูปต้นฉบับ
      var srcAlpha = false;    // รูปมีความโปร่งใสมาแต่เดิมไหม
      var lumCache = null;     // ความสว่างรายจุด (ไม่เปลี่ยนตามสไลเดอร์ คำนวณครั้งเดียว)
      var bgCache = null;      // สีกระดาษรายบริเวณ (คำนวณครั้งเดียวต่อรูป)
      var MAX_FILE = 20 * 1024 * 1024;
      var WORK_MAX = 1200;     // ด้านยาวสุดตอนประมวลผล (พอสำหรับลายเซ็น เร็วพอบนมือถือ)
      var OUT_MAX = 1000;      // ความกว้างสุดของภาพที่บันทึก

      function updateUploadState() {
        if (!uploadSave) return;
        uploadSave.disabled = !uploadData;
      }

      function setMode(mode) {
        var isUpload = mode === 'upload';
        modeDrawBtn.classList.toggle('is-active', !isUpload);
        modeUploadBtn.classList.toggle('is-active', isUpload);
        panelDraw.classList.toggle('is-active', !isUpload);
        panelUpload.classList.toggle('is-active', isUpload);
        msg.className = 'sig-msg'; msg.textContent = '';
        // canvas ที่ถูกซ่อนอยู่จะวัดขนาดไม่ได้ ต้องวัดใหม่ตอนกลับมาโหมดเซ็นเอง
        if (!isUpload) fitCanvas();
      }

      if (modeDrawBtn && modeUploadBtn) {
        modeDrawBtn.addEventListener('click', function () { setMode('draw'); });
        modeUploadBtn.addEventListener('click', function () { setMode('upload'); });
      }

      function fail(key, fb) {
        msg.className = 'sig-msg err';
        msg.textContent = dictOf(key, fb);
      }

      // createImageBitmap หมุนภาพตาม EXIF ให้เอง (รูปจากมือถือมักตะแคง)
      function decodeImage(file) {
        if (window.createImageBitmap) {
          try {
            return createImageBitmap(file, { imageOrientation: 'from-image' })
              .catch(function () { return decodeViaTag(file); });
          } catch (e) { /* เบราว์เซอร์เก่าไม่รับ option — ใช้วิธีสำรอง */ }
        }
        return decodeViaTag(file);
      }

      function decodeViaTag(file) {
        return new Promise(function (resolve, reject) {
          var url = URL.createObjectURL(file);
          var img = new Image();
          img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
          img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
          img.src = url;
        });
      }

      function isHeic(file) {
        return /\.hei[cf]$/i.test(file.name || '') || /hei[cf]/i.test(file.type || '');
      }

      function handleFile(file) {
        if (!file) return;
        if (file.size > MAX_FILE) { fail('sig.tooBig', 'ไฟล์ใหญ่เกินไป (เกิน 20 MB)'); return; }
        if (file.type && file.type.indexOf('image/') !== 0 && !isHeic(file)) {
          fail('sig.badFile', 'ไฟล์นี้ไม่ใช่รูปภาพ');
          return;
        }

        msg.className = 'sig-msg';
        msg.textContent = dictOf('sig.processing', 'กำลังประมวลผลรูป…');

        decodeImage(file).then(function (img) {
          var w = img.width, h = img.height;
          var scale = Math.min(1, WORK_MAX / Math.max(w, h));
          workCanvas = document.createElement('canvas');
          workCanvas.width = Math.max(1, Math.round(w * scale));
          workCanvas.height = Math.max(1, Math.round(h * scale));
          workCanvas.getContext('2d').drawImage(img, 0, 0, workCanvas.width, workCanvas.height);
          if (img.close) img.close();

          srcData = null; lumCache = null; bgCache = null;   // รูปใหม่ ต้องวัดพื้นหลังใหม่
          msg.textContent = '';
          processUpload();
        }).catch(function () {
          workCanvas = null; uploadData = null;
          updateUploadState();
          if (isHeic(file)) {
            fail('sig.heicFail', 'เบราว์เซอร์นี้เปิดไฟล์ HEIC ของ iPhone ไม่ได้ — ให้เลือกรูปจากแอปรูปภาพบน iPhone (ระบบจะแปลงเป็น JPG ให้เอง) หรือตั้งค่า > กล้อง > รูปแบบ > เข้ากันได้มากที่สุด');
          } else {
            fail('sig.readFail', 'เปิดไฟล์รูปนี้ไม่ได้ ลองไฟล์อื่น');
          }
        });
      }

      /* วัด "สีกระดาษ" แยกเป็นบริเวณ ไม่ใช่ค่าเดียวทั้งรูป
         รูปถ่ายจริงมีเงา/แสงไม่เท่ากัน ถ้าใช้เกณฑ์เดียวทั้งรูป ฝั่งที่มีเงาจะเข้มกว่าเกณฑ์
         แล้วติดพื้นหลังมาเป็นแผ่น — จึงแบ่งเป็นบล็อกแล้วหาสีกระดาษของแต่ละบล็อก */
      function buildBackground(lum, w, h) {
        var bs = Math.max(8, Math.round(Math.max(w, h) / 22));
        var gw = Math.ceil(w / bs), gh = Math.ceil(h / bs);
        var raw = new Float32Array(gw * gh);
        var hist = new Uint32Array(256);

        for (var gy = 0; gy < gh; gy++) {
          for (var gx = 0; gx < gw; gx++) {
            hist.fill(0);
            var x0 = gx * bs, y0 = gy * bs;
            var x1 = Math.min(w, x0 + bs), y1 = Math.min(h, y0 + bs);
            var cnt = 0;

            for (var y = y0; y < y1; y++) {
              var row = y * w;
              for (var x = x0; x < x1; x++) { hist[lum[row + x] | 0]++; cnt++; }
            }

            // เปอร์เซ็นไทล์ 85 ของบล็อก = สีกระดาษตรงนั้น (หมึกกินพื้นที่ไม่ถึง 15% ของบล็อก)
            var need = cnt * 0.85, acc = 0, val = 255;
            for (var v = 0; v < 256; v++) { acc += hist[v]; if (acc >= need) { val = v; break; } }
            raw[gy * gw + gx] = Math.max(val, 1);
          }
        }

        // บล็อกที่โดนหมึกทับเกือบทั้งบล็อก ให้ยืมค่ากระดาษจากเพื่อนบ้านที่สว่างกว่า
        var grid = new Float32Array(raw.length);
        for (gy = 0; gy < gh; gy++) {
          for (gx = 0; gx < gw; gx++) {
            var m = raw[gy * gw + gx];
            for (var dy = -1; dy <= 1; dy++) {
              for (var dx = -1; dx <= 1; dx++) {
                var nx = gx + dx, ny = gy + dy;
                if (nx < 0 || ny < 0 || nx >= gw || ny >= gh) continue;
                if (raw[ny * gw + nx] > m) m = raw[ny * gw + nx];
              }
            }
            grid[gy * gw + gx] = m;
          }
        }

        return { grid: grid, gw: gw, gh: gh, bs: bs };
      }

      // อ่านรูปครั้งเดียวแล้วเก็บไว้ เลื่อนสไลเดอร์ทีหลังจะได้ไม่ต้องคำนวณใหม่ทั้งหมด
      function prepareSource() {
        var w = workCanvas.width, h = workCanvas.height, n = w * h;
        srcData = workCanvas.getContext('2d').getImageData(0, 0, w, h);
        var d = srcData.data;

        // ถ้าเป็น PNG ที่ตัดพื้นหลังมาแล้ว ให้ถือว่าจุดโปร่งใสคือกระดาษ
        srcAlpha = false;
        for (var s = 3; s < d.length; s += 4 * 41) { if (d[s] < 250) { srcAlpha = true; break; } }

        lumCache = new Float32Array(n);
        for (var p = 0, q = 0; q < n; p += 4, q++) {
          var L = 0.299 * d[p] + 0.587 * d[p + 1] + 0.114 * d[p + 2];
          if (srcAlpha) L = 255 - (d[p + 3] / 255) * (255 - L);
          lumCache[q] = L;
        }

        bgCache = buildBackground(lumCache, w, h);
      }

      /* แยกลายเซ็นออกจากพื้นกระดาษ
         1) เทียบความสว่างของแต่ละจุดกับสีกระดาษ "ตรงจุดนั้น" (หาร) เงาจึงไม่มีผล
         2) จุดที่เข้มกว่าเกณฑ์ = หมึก ไล่ความทึบที่ขอบเพื่อไม่ให้เส้นแตก
         3) ตัดจุดรบกวนและกลุ่มเล็กๆ ทิ้ง แล้วตัดกรอบเหลือเฉพาะลายเซ็น */
      function processUpload() {
        if (!workCanvas) return;
        if (!lumCache) prepareSource();

        var w = workCanvas.width, h = workCanvas.height, n = w * h;
        var d = srcData.data;
        var lum = lumCache;
        var grid = bgCache.grid, gw = bgCache.gw, gh = bgCache.gh, bs = bgCache.bs;

        // สไลเดอร์สูง = เก็บหมึกจางๆ ด้วย · ต่ำ = เอาเฉพาะเส้นเข้มจริงๆ
        var cut = 0.30 + (parseInt(levelInput.value, 10) / 100) * 0.85;
        var soft = 0.1;

        var alpha = new Float32Array(n);
        var q;

        for (var y = 0; y < h; y++) {
          var fy = (y + 0.5) / bs - 0.5;
          var gy0 = Math.floor(fy), ty = fy - gy0, gy1 = gy0 + 1;
          gy0 = gy0 < 0 ? 0 : (gy0 > gh - 1 ? gh - 1 : gy0);
          gy1 = gy1 < 0 ? 0 : (gy1 > gh - 1 ? gh - 1 : gy1);
          var r0 = gy0 * gw, r1 = gy1 * gw, row = y * w;

          for (var x = 0; x < w; x++) {
            var fx = (x + 0.5) / bs - 0.5;
            var gx0 = Math.floor(fx), tx = fx - gx0, gx1 = gx0 + 1;
            gx0 = gx0 < 0 ? 0 : (gx0 > gw - 1 ? gw - 1 : gx0);
            gx1 = gx1 < 0 ? 0 : (gx1 > gw - 1 ? gw - 1 : gx1);

            var paper = (grid[r0 + gx0] * (1 - tx) + grid[r0 + gx1] * tx) * (1 - ty)
                      + (grid[r1 + gx0] * (1 - tx) + grid[r1 + gx1] * tx) * ty;
            if (paper < 1) paper = 1;

            var nb = lum[row + x] / paper;          // 1 = สีกระดาษตรงจุดนั้น
            var a = (cut + soft - nb) / (2 * soft);
            alpha[row + x] = a < 0 ? 0 : (a > 1 ? 1 : a);
          }
        }

        // ลบจุดรบกวนเม็ดเดี่ยว (ฝุ่นบนกระดาษ / noise จากกล้อง)
        for (q = 0; q < n; q++) {
          if (alpha[q] < 0.2) continue;
          var x = q % w, y = (q / w) | 0, near = 0;
          if (x > 0 && alpha[q - 1] > 0.2) near++;
          if (x < w - 1 && alpha[q + 1] > 0.2) near++;
          if (y > 0 && alpha[q - w] > 0.2) near++;
          if (y < h - 1 && alpha[q + w] > 0.2) near++;
          if (near < 2) alpha[q] = 0;
        }

        /* ตัดกลุ่มจุดเล็กๆ ที่ไม่ใช่ลายเซ็นทิ้ง (ฝุ่น เศษเงา ขอบกระดาษที่ขาดเป็นเม็ด)
           ไล่หาจุดที่ติดกันเป็นกลุ่ม กลุ่มไหนเล็กกว่าเกณฑ์ถือว่าไม่ใช่เส้นลายเซ็น */
        var seen = new Uint8Array(n);
        var stack = new Int32Array(n);
        var blob = new Int32Array(n);
        var minBlob = Math.max(8, Math.round(n * 0.00002));

        for (q = 0; q < n; q++) {
          if (seen[q] || alpha[q] <= 0.2) continue;

          var top = 0, size = 0;
          stack[top++] = q;
          seen[q] = 1;

          while (top > 0) {
            var cur = stack[--top];
            blob[size++] = cur;
            var cx = cur % w, cy = (cur / w) | 0;

            if (cx > 0 && !seen[cur - 1] && alpha[cur - 1] > 0.2) { seen[cur - 1] = 1; stack[top++] = cur - 1; }
            if (cx < w - 1 && !seen[cur + 1] && alpha[cur + 1] > 0.2) { seen[cur + 1] = 1; stack[top++] = cur + 1; }
            if (cy > 0 && !seen[cur - w] && alpha[cur - w] > 0.2) { seen[cur - w] = 1; stack[top++] = cur - w; }
            if (cy < h - 1 && !seen[cur + w] && alpha[cur + w] > 0.2) { seen[cur + w] = 1; stack[top++] = cur + w; }
          }

          if (size < minBlob) {
            for (var bi = 0; bi < size; bi++) alpha[blob[bi]] = 0;
          }
        }

        var minX = w, minY = h, maxX = -1, maxY = -1;
        for (q = 0; q < n; q++) {
          if (alpha[q] <= 0.18) continue;
          var px = q % w, py = (q / w) | 0;
          if (px < minX) minX = px;
          if (px > maxX) maxX = px;
          if (py < minY) minY = py;
          if (py > maxY) maxY = py;
        }

        if (maxX < 0) {
          uploadData = null;
          shotWrap.hidden = true;
          updateUploadState();
          fail('sig.noInk', 'หาลายเซ็นในรูปไม่เจอ ลองเลื่อน "ความเข้ม" เพิ่มขึ้น');
          return;
        }

        var pad = Math.max(4, Math.round(Math.max(maxX - minX, maxY - minY) * 0.03));
        minX = Math.max(0, minX - pad); minY = Math.max(0, minY - pad);
        maxX = Math.min(w - 1, maxX + pad); maxY = Math.min(h - 1, maxY + pad);

        var cw = maxX - minX + 1, ch = maxY - minY + 1;
        var crop = document.createElement('canvas');
        crop.width = cw; crop.height = ch;
        var cctx = crop.getContext('2d');
        var out = cctx.createImageData(cw, ch);
        var od = out.data;
        var black = inkBlackInput.checked;

        for (var yy = 0; yy < ch; yy++) {
          for (var xx = 0; xx < cw; xx++) {
            var si = (minY + yy) * w + (minX + xx);
            var di = (yy * cw + xx) * 4;
            var av = alpha[si];
            if (srcAlpha) av *= d[si * 4 + 3] / 255;   // รูปที่โปร่งใสมาแต่เดิม ต้องคงความโปร่งใสนั้นไว้
            if (black) {
              od[di] = 0x16; od[di + 1] = 0x16; od[di + 2] = 0x16;
            } else {
              // คงสีปากกาเดิม (เช่นหมึกน้ำเงิน) แต่เร่งให้เข้มขึ้นเล็กน้อย
              var so = si * 4;
              od[di] = Math.round(d[so] * 0.8);
              od[di + 1] = Math.round(d[so + 1] * 0.8);
              od[di + 2] = Math.round(d[so + 2] * 0.8);
            }
            od[di + 3] = Math.round(av * 255);
          }
        }
        cctx.putImageData(out, 0, 0);

        var outScale = Math.min(1, OUT_MAX / cw);
        var fin = document.createElement('canvas');
        fin.width = Math.max(1, Math.round(cw * outScale));
        fin.height = Math.max(1, Math.round(ch * outScale));
        var fctx = fin.getContext('2d');
        fctx.imageSmoothingEnabled = true;
        fctx.drawImage(crop, 0, 0, fin.width, fin.height);

        uploadData = fin.toDataURL('image/png');

        shotCanvas.width = fin.width;
        shotCanvas.height = fin.height;
        shotCanvas.getContext('2d').drawImage(fin, 0, 0);
        shotWrap.hidden = false;
        uploadTools.hidden = false;
        dropZone.hidden = true;
        updateUploadState();
      }

      if (dropZone) {
        var reprocess;
        dropZone.addEventListener('click', function () { fileInput.click(); });
        dropZone.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
        });
        ['dragenter', 'dragover'].forEach(function (ev) {
          dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.classList.add('is-over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
          dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.classList.remove('is-over'); });
        });
        dropZone.addEventListener('drop', function (e) {
          if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
        });

        fileInput.addEventListener('change', function () {
          if (fileInput.files && fileInput.files.length) handleFile(fileInput.files[0]);
          fileInput.value = '';   // เลือกไฟล์เดิมซ้ำได้
        });

        pickAgain.addEventListener('click', function () { fileInput.click(); });

        levelInput.addEventListener('input', function () {
          clearTimeout(reprocess);
          reprocess = setTimeout(processUpload, 120);
        });
        inkBlackInput.addEventListener('change', processUpload);
        uploadSave.addEventListener('click', function () { saveSignature(uploadData, uploadSave); });
      }

      fitCanvas();
      window.__fitSignatureCanvas = fitCanvas;
      var rt;
      window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(fitCanvas, 200); });
    })();
  </script>

  {{-- ── บันทึกอีเมล ── --}}
  <script>
    'use strict';
    (function () {
      var form = document.getElementById('emailForm');
      if (!form) return;
      var input = document.getElementById('emailInput');
      var msg = document.getElementById('emailMsg');
      var cardValue = document.getElementById('emailCardValue');
      var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      var url = "{{ route('profile.email') }}";

      function dictOf(key, fb) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var m = (window.__portalCopy && window.__portalCopy[lang]) || {};
        return m[key] || fb;
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        msg.className = 'email-msg';
        msg.textContent = '';
        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: JSON.stringify({ email: input.value.trim() })
        })
          .then(function (r) { return r.json().then(function (d) { return { status: r.status, body: d }; }); })
          .then(function (res) {
            if (res.status === 200 && res.body.ok) {
              msg.className = 'email-msg ok';
              msg.textContent = dictOf('email.saved', 'บันทึกอีเมลแล้ว');
              if (cardValue) {
                var value = input.value.trim();
                if (value) {
                  cardValue.removeAttribute('data-i18n');
                  cardValue.textContent = value;
                } else {
                  cardValue.setAttribute('data-i18n', 'profile.noEmail');
                  cardValue.textContent = dictOf('profile.noEmail', 'ยังไม่ได้กรอกอีเมล');
                }
              }
            } else {
              msg.className = 'email-msg err';
              msg.textContent = dictOf('email.invalid', 'รูปแบบอีเมลไม่ถูกต้อง');
            }
          })
          .catch(function () {
            msg.className = 'email-msg err';
            msg.textContent = dictOf('email.error', 'บันทึกไม่สำเร็จ');
          });
      });
    })();
  </script>
@endsection
