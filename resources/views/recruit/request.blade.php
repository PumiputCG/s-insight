@extends('layouts.portal')

@section('title', 'ขออัตรากำลังคน')

@php
  // ── ข้อมูลผู้ขอ (DCC คนที่ล็อกอิน) — ของจริงดึงจากบัญชี ──
  $rqMe = app('current_user');
  $requesterName = $rqMe ? ($rqMe->fullNameTh() ?: $rqMe->full_name_en ?: $rqMe->employee_code) : '';
  $requester = [
    'code' => $rqMe->employee_code ?? '-',
    'name' => $requesterName ?: '-',
    'position' => ($rqMe && $rqMe->position) ? $rqMe->position : '-',
    'department' => ($rqMe && $rqMe->department) ? $rqMe->department : '-',
  ];
  $requesterAvatar = ($rqMe && $rqMe->profile_picture) ? asset('storage/'.$rqMe->profile_picture) : null;
  $companyLogo = asset('assets/recruit/company-logo.png');

  // ── เส้นทางอนุมัติ — MOCK จนกว่าจะต่อ backend ──
  // ของจริง: ดึงตาม DCC ที่ล็อกอิน + RecruitRoute::availableForDeptUser() แล้วใส่คนจริงในแต่ละ step
  // managers ของ step manager (admin อาจกำหนดได้มากกว่า 1 คน → DCC เลือกใน dropdown)
  $mockManagers = [
    ['id' => 68060, 'name' => 'นายกฤษฎา ประเสริฐลักษณ์', 'position' => 'Manager'],
    ['id' => 70112, 'name' => 'นายอนุชา วัฒนกุล', 'position' => 'Assist Manager'],
  ];
  // recruit อาจมีหลายคน → แสดงทุกชื่อในการ์ดข้อมูลเส้นทาง
  $mockRecruit = ['น.ส.ณัฐชญา เวินชุม', 'น.ส.พัชรี ทาเคมา', 'น.ส.ธนัชชา สุขเกษม'];

  $routes = $requestMockRoutes ?? [
    [
      'id' => 1,
      'name' => 'เส้นทาง 1',
      'path' => 'DCC → Manager → General Manager → HR Manager → Recruit',
      'has_gm' => true,
      'steps' => [
        ['role' => 'dcc', 'label' => 'DCC', 'names' => [$requester['name']]],
        ['role' => 'manager', 'label' => 'Manager', 'managers' => $mockManagers],
        ['role' => 'general_manager', 'label' => 'General Manager', 'names' => ['นายสิทธิพงษ์ พูลเลิศ']],
        ['role' => 'hr_manager', 'label' => 'HR Manager', 'names' => ['นายฐานพัฒน์ พิมายกลาง']],
        ['role' => 'recruit', 'label' => 'Recruit', 'names' => $mockRecruit],
      ],
    ],
    [
      'id' => 2,
      'name' => 'เส้นทาง 2',
      'path' => 'DCC → Manager → HR Manager → Recruit',
      'has_gm' => false,
      'steps' => [
        ['role' => 'dcc', 'label' => 'DCC', 'names' => [$requester['name']]],
        ['role' => 'manager', 'label' => 'Manager', 'managers' => $mockManagers],
        ['role' => 'hr_manager', 'label' => 'HR Manager', 'names' => ['นายฐานพัฒน์ พิมายกลาง']],
        ['role' => 'recruit', 'label' => 'Recruit', 'names' => $mockRecruit],
      ],
    ],
  ];
@endphp

@section('topbar-title')
  <span class="tt-kicker" data-i18n="recruit.kicker">การสรรหา</span>
  <span class="tt-title" data-i18n="nav.rcRequest">ขออัตรากำลังคน</span>
@endsection

@section('page-style')
    .rq-wrap { width: min(100%, 76rem); margin-top: .5rem; display: grid; gap: 1rem; }
    .rq-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .rq-head h1 { margin: 0; font-size: 1.45rem; font-weight: 760; color: var(--light-text); letter-spacing: 0; }
    .rq-head p { margin: .28rem 0 0; max-width: 48rem; color: var(--muted-light); font-size: .88rem; line-height: 1.6; }

    .rq-form { display: grid; gap: 1rem; }
    .rq-step-card { border: 1px solid var(--line-light); border-radius: 0.25rem; background: color-mix(in srgb, var(--panel-soft) 72%, transparent); overflow: hidden; }
    .rq-step-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 1rem; border-bottom: 1px solid transparent; list-style: none; cursor: pointer; }
    .rq-step-head::-webkit-details-marker { display: none; }
    .rq-step-card[open] > .rq-step-head { border-bottom-color: var(--line-light); }
    .rq-step-main { display: flex; align-items: center; gap: .72rem; min-width: 0; }
    .rq-step-no { width: 2rem; height: 2rem; flex: 0 0 auto; display: grid; place-items: center; border: 1px solid color-mix(in srgb, var(--moss) 38%, var(--line-light)); border-radius: 0.25rem; background: color-mix(in srgb, var(--moss) 10%, transparent); color: var(--moss); font-size: .86rem; font-weight: 850; }
    .rq-step-copy { display: grid; gap: .12rem; min-width: 0; }
    .rq-step-title { color: var(--light-text); font-size: 1rem; font-weight: 760; line-height: 1.35; overflow-wrap: anywhere; }
    .rq-step-note { color: var(--muted-light); font-size: .78rem; line-height: 1.45; overflow-wrap: anywhere; }
    .rq-step-caret { width: 1.75rem; height: 1.75rem; flex: 0 0 auto; display: grid; place-items: center; color: var(--muted-light); transition: transform .18s var(--ease-out), color .18s var(--ease-out); }
    .rq-step-caret::before { content: ""; width: .48rem; height: .48rem; border-right: 1.7px solid currentColor; border-bottom: 1.7px solid currentColor; transform: rotate(45deg) translate(-1px, -1px); }
    .rq-step-head:hover .rq-step-caret,
    .rq-step-head:focus-visible .rq-step-caret { color: var(--moss); }
    .rq-step-card[open] > .rq-step-head .rq-step-caret { transform: rotate(180deg); }
    .rq-step-head:focus-visible { outline: 0; box-shadow: inset 0 0 0 3px color-mix(in srgb, var(--moss) 18%, transparent); }
    .rq-workflow-body,
    .rq-step-body { display: grid; gap: .9rem; padding: 1rem; }
    .rq-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: .8rem; }
    .rq-grid.two { grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr)); }
    .rq-field { display: grid; gap: .38rem; min-width: 0; }
    .rq-field label,
    .rq-label { color: var(--muted-light); font-size: .78rem; font-weight: 720; line-height: 1.35; }
    .rq-field small { color: var(--muted-light); font-size: .72rem; line-height: 1.35; }
    .rq-control,
    .rq-select { width: 100%; min-height: 2.55rem; border: 1px solid var(--line-light); border-radius: 0.25rem; padding: .6rem .72rem; background: var(--menu-bg); color: var(--light-text); font: inherit; font-size: .88rem; outline: none; transition: border-color .18s var(--ease-out), box-shadow .18s var(--ease-out); }
    .rq-control:focus,
    .rq-select:focus { border-color: color-mix(in srgb, var(--moss) 70%, transparent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--moss) 16%, transparent); }
    .rq-control[readonly] { color: var(--muted-light); background: color-mix(in srgb, var(--panel-soft) 88%, transparent); }

    .rq-route-list { width: min(100%, 58rem); margin-inline: auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 21rem), 1fr)); gap: .7rem; }
    .rq-route-card { display: grid; grid-template-columns: auto 1fr; gap: .65rem; align-items: flex-start; min-height: 5rem; padding: .8rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--menu-bg); cursor: pointer; transition: border-color .18s var(--ease-out), transform .18s var(--ease-out), background-color .18s var(--ease-out); }
    .rq-route-card:hover { border-color: color-mix(in srgb, var(--moss) 56%, transparent); transform: translateY(-1px); }
    .rq-route-card.is-selected { border-color: var(--moss); background: color-mix(in srgb, var(--moss) 10%, var(--menu-bg)); }
    .rq-route-card input { width: 1rem; height: 1rem; margin-top: .14rem; accent-color: var(--moss); cursor: pointer; }
    .rq-route-name { display: block; color: var(--light-text); font-size: .9rem; font-weight: 760; line-height: 1.35; }
    .rq-route-path { display: block; margin-top: .25rem; color: var(--muted-light); font-size: .76rem; line-height: 1.45; overflow-wrap: anywhere; }
    .rq-empty-note { display: none; padding: .8rem; border: 1px dashed var(--line-light); border-radius: 0.25rem; color: var(--muted-light); font-size: .84rem; line-height: 1.5; }
    .rq-empty-note.is-visible { display: block; }

    /* ── ข้อมูลผู้ขอ ── */
    .rq-requester { display: grid; grid-template-columns: auto 1fr; gap: .9rem; align-items: center; padding: .85rem 1rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--menu-bg); }
    .rq-requester-avatar { width: 2.9rem; height: 2.9rem; border-radius: 50%; display: grid; place-items: center; overflow: hidden; background: color-mix(in srgb, var(--moss) 16%, transparent); color: var(--moss); }
    .rq-requester-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .rq-requester-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr)); gap: .5rem .9rem; min-width: 0; }
    .rq-requester-grid > div { display: grid; gap: .1rem; min-width: 0; }
    .rq-requester-grid span { color: var(--muted-light); font-size: .72rem; font-weight: 700; }
    .rq-requester-grid strong { color: var(--light-text); font-size: .9rem; font-weight: 720; overflow-wrap: anywhere; }

    /* ── เลือก Manager ในการ์ดเส้นทาง ── */
    .rq-route-main { display: block; min-width: 0; }
    .rq-route-manager { display: grid; gap: .3rem; margin-top: .6rem; }
    .rq-route-manager-label { color: var(--muted-light); font-size: .72rem; font-weight: 720; }
    .rq-route-manager-select { min-height: 2.35rem; }
    .rq-route-manager-select:disabled { opacity: .5; cursor: not-allowed; }
    .rq-route-card.is-selected .rq-route-manager-select { border-color: color-mix(in srgb, var(--moss) 55%, transparent); }
    .rq-flow { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; min-height: 3rem; padding: .72rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--menu-bg); }
    .rq-step { display: inline-flex; align-items: center; gap: .4rem; max-width: 100%; min-height: 2.1rem; padding: .34rem .58rem; border-radius: 999px; background: color-mix(in srgb, var(--panel-soft) 72%, transparent); color: var(--light-text); font-size: .76rem; font-weight: 720; }
    .rq-step small { max-width: 12rem; color: var(--muted-light); font-size: .68rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rq-chevron { color: var(--muted-light); font-size: .78rem; }
    .rq-chain { width: min(100%, 58rem); margin: 0 auto .85rem; display: flex; align-items: stretch; justify-content: center; flex-wrap: wrap; gap: .4rem; text-align: center; }
    .rq-chain[hidden] { display: none; }
    .rq-node { display: flex; flex-direction: column; align-items: center; gap: .2rem; min-width: 0; padding: .5rem .65rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--menu-bg); }
    .rq-node-role { color: var(--moss); font-size: .64rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .rq-node-name { color: var(--light-text); font-size: .82rem; font-weight: 600; line-height: 1.32; overflow-wrap: anywhere; text-align: center; }
    .rq-chain-sep { align-self: center; color: var(--muted-light); font-size: 1rem; }
    .rq-chain-manager { min-height: 1.95rem; padding: .25rem .45rem; border: 1px solid color-mix(in srgb, var(--moss) 50%, var(--line-light)); border-radius: 0.25rem; background: var(--menu-bg); color: var(--light-text); font: inherit; font-size: .8rem; font-weight: 700; text-align: center; cursor: pointer; }
    .rq-chain-manager:focus { outline: 0; border-color: var(--moss); box-shadow: 0 0 0 3px color-mix(in srgb, var(--moss) 16%, transparent); }
    .rq-approval-grid.has-gm { grid-template-columns: repeat(4, 1fr); }
    .rq-doc-foot { margin-top: .7rem; color: #555555; font-size: .68rem; text-align: left; }

    .rq-paper-shell { overflow-x: auto; padding-bottom: .25rem; }
    .rq-paper { width: min(100%, 62rem); min-width: 52rem; margin: 0 auto; padding: 1.15rem; background: #ffffff; color: #161616; border: 1px solid #d6d6d6; border-radius: .25rem; box-shadow: 0 8px 22px rgba(0, 0, 0, .18); }
    .rq-paper * { box-sizing: border-box; }
    .rq-paper-head { display: grid; grid-template-columns: 5.5rem 1fr 5.5rem; align-items: center; gap: 1rem; margin-bottom: .7rem; }
    .rq-company-logo { width: 4.25rem; height: 4.25rem; object-fit: contain; justify-self: center; display: block; }
    .rq-paper-title { text-align: center; }
    .rq-paper-title h2 { margin: 0; color: #151515; font-size: 1.45rem; font-weight: 820; letter-spacing: 0; }
    .rq-paper-title p { margin: .2rem 0 0; color: #151515; font-size: 1.02rem; font-weight: 700; }
    .rq-doc-code { align-self: end; justify-self: end; color: #555555; font-size: .68rem; text-align: right; }
    .rq-table { display: grid; border-top: 1px solid #444444; border-left: 1px solid #444444; background: #ffffff; }
    .rq-row { display: grid; grid-template-columns: var(--cols, 1fr); }
    .rq-cell { min-width: 0; min-height: 2.75rem; padding: .38rem .52rem; border-right: 1px solid #444444; border-bottom: 1px solid #444444; display: grid; align-content: start; gap: .22rem; }
    .rq-cell.middle { align-content: center; }
    .rq-cell.tall { min-height: 4.8rem; }
    .rq-cell.x-tall { min-height: 7.2rem; }
    .rq-band { min-height: 1.55rem; padding: .22rem .5rem; border-right: 1px solid #444444; border-bottom: 1px solid #444444; background: #f4f4f4; color: #151515; font-size: .78rem; font-weight: 760; text-align: center; }
    .rq-th { color: #151515; font-size: .78rem; font-weight: 760; line-height: 1.25; }
    .rq-en { color: #333333; font-size: .68rem; font-weight: 600; line-height: 1.25; }
    .rq-note { color: #555555; font-size: .66rem; line-height: 1.35; }
    .rq-paper-input,
    .rq-paper-textarea { width: 100%; min-width: 0; border: 0; border-bottom: 1px dotted #878787; border-radius: 0; padding: .16rem .1rem .14rem; background: transparent; color: #111111; font: inherit; font-size: .92rem; outline: none; line-height: 1.35; }
    .rq-paper-textarea { min-height: 3.2rem; resize: vertical; }
    .rq-paper-input:focus,
    .rq-paper-textarea:focus { border-bottom-color: #557a3a; box-shadow: inset 0 -2px 0 rgba(85, 122, 58, .22); }
    .rq-paper-input[readonly] { color: #444444; }
    .rq-check-line { display: grid; grid-template-columns: auto 1fr; gap: .45rem; align-items: start; min-height: 1.95rem; }
    .rq-check-line input { width: .95rem; height: .95rem; margin-top: .1rem; accent-color: #557a3a; cursor: pointer; }
    .rq-check-copy strong { display: block; color: #151515; font-size: .78rem; font-weight: 760; line-height: 1.25; }
    .rq-check-copy span { display: block; margin-top: .08rem; color: #333333; font-size: .67rem; line-height: 1.25; }
    .rq-inline-fields { display: grid; grid-template-columns: 1fr 9rem; gap: .6rem; }
    .rq-mini-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .42rem .6rem; }
    .rq-number-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .45rem; align-items: end; }
    .rq-education-row { display: grid; grid-template-columns: minmax(11rem, .9fr) 1fr; gap: .5rem; align-items: end; }
    .rq-job-line { display: grid; grid-template-columns: 2rem 1fr; gap: .45rem; align-items: center; }
    .rq-name-list-cell { min-height: 2.08rem; padding-top: .22rem; padding-bottom: .18rem; align-content: center; }
    .rq-name-list-line { display: grid; grid-template-columns: 1.45rem max-content 1fr; gap: .35rem; align-items: end; color: #151515; font-size: .75rem; font-weight: 720; line-height: 1.25; }
    .rq-name-list-line.start-date { grid-template-columns: max-content 1fr; }
    .rq-name-list-line .rq-paper-input { padding-top: .04rem; padding-bottom: .05rem; font-size: .82rem; }
    .rq-recruit-sign-cell { min-height: 4.85rem; align-content: start; gap: .18rem; }
    .rq-recruit-sign-cell .rq-th,
    .rq-recruit-sign-cell .rq-en { text-align: left; }
    .rq-approval-grid { display: grid; grid-template-columns: repeat(3, 1fr); }
    .rq-sign-box { min-height: 5rem; align-content: stretch; }
    .rq-sign-box[hidden] { display: none; }
    .rq-sign-box .rq-th,
    .rq-sign-box .rq-en { text-align: center; }
    .rq-sign-space { min-height: 2.35rem; border-bottom: 1px dotted #8c8c8c; display: grid; align-items: end; color: #555555; font-size: .82rem; }
    .rq-date-line { display: grid; grid-template-columns: 2.4rem 1fr; gap: .3rem; align-items: end; margin-top: .35rem; color: #151515; font-size: .72rem; }
    .rq-recruit-only { background: #fafafa; }
    .rq-disabled-input { color: #777777; pointer-events: none; }

    .rq-upload-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr)); gap: .85rem; }
    .rq-upload { display: grid; gap: .55rem; min-height: 8rem; padding: .9rem; border: 1px dashed color-mix(in srgb, var(--moss) 45%, var(--line-light)); border-radius: 0.25rem; background: color-mix(in srgb, var(--moss) 6%, var(--menu-bg)); cursor: pointer; }
    .rq-upload strong { color: var(--light-text); font-size: .92rem; }
    .rq-upload span { color: var(--muted-light); font-size: .78rem; line-height: 1.45; }
    .rq-upload input { cursor: pointer; color: var(--muted-light); font-size: .82rem; }
    .rq-file-list { margin: 0; padding-left: 1.05rem; color: var(--muted-light); font-size: .78rem; line-height: 1.5; }
    .rq-result { display: none; padding: .8rem 1rem; border: 1px solid color-mix(in srgb, var(--moss) 45%, transparent); border-radius: 0.25rem; color: var(--light-text); background: color-mix(in srgb, var(--moss) 10%, transparent); font-size: .86rem; line-height: 1.5; }
    .rq-result.is-visible { display: block; }
    .rq-submit-row { display: flex; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; }
    .rq-btn { min-height: 2.55rem; border: 1px solid var(--line-light); border-radius: 0.25rem; padding: .62rem 1rem; background: var(--menu-bg); color: var(--light-text); font: inherit; font-size: .88rem; font-weight: 760; cursor: pointer; transition: transform .18s var(--ease-out), border-color .18s var(--ease-out), background-color .18s var(--ease-out); }
    .rq-btn:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--moss) 55%, transparent); }
    .rq-btn.primary { border-color: var(--moss); background: var(--moss); color: #ffffff; }
    .rq-btn.primary:hover { background: color-mix(in srgb, var(--moss) 86%, #000000); }

    .rq-confirm { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; }
    .rq-confirm[hidden] { display: none; }
    .rq-confirm-backdrop { position: absolute; inset: 0; background: rgba(0, 0, 0, .62); }
    .rq-confirm-dialog { position: relative; width: min(100%, 25rem); padding: 1.1rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--menu-bg); color: var(--light-text); box-shadow: 0 8px 20px rgba(0, 0, 0, .22); animation: rq-confirm-in .18s var(--ease-out); }
    .rq-confirm-dialog h2 { margin: 0; font-size: 1.05rem; font-weight: 760; }
    .rq-confirm-dialog p { margin: .45rem 0 1rem; color: var(--muted-light); font-size: .86rem; line-height: 1.55; }
    .rq-confirm-actions { display: flex; justify-content: flex-end; gap: .6rem; flex-wrap: wrap; }

    @keyframes rq-confirm-in {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 760px) {
      .rq-paper { min-width: 46rem; padding: .8rem; }
      .rq-paper-head { grid-template-columns: 4rem 1fr; }
      .rq-company-logo { width: 3.8rem; height: 3.8rem; }
      .rq-doc-code { grid-column: 1 / -1; justify-self: start; text-align: left; }
      .rq-inline-fields,
      .rq-mini-grid,
      .rq-number-grid { grid-template-columns: 1fr; }
      .rq-head { flex-direction: column; }
      .rq-submit-row,
      .rq-confirm-actions { justify-content: stretch; }
      .rq-btn { width: 100%; }
    }

    @media print {
      .app-topbar,
      .sidebar,
      .rq-head,
      .rq-workflow,
      .rq-attachments,
      .rq-submit-row,
      .rq-result { display: none !important; }
      .shell,
      .main { display: block !important; padding: 0 !important; margin: 0 !important; }
      .rq-paper-shell { overflow: visible; }
      .rq-paper-step { border: 0; background: transparent; overflow: visible; }
      .rq-paper-step > .rq-step-head { display: none !important; }
      .rq-paper-step > .rq-step-body { padding: 0; }
      .rq-paper { min-width: 0; width: 100%; box-shadow: none; border: 0; padding: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
      .rq-route-card,
      .rq-step-caret,
      .rq-btn,
      .rq-confirm-dialog { transition: none; animation: none; }
    }
@endsection

@section('content')
  <div class="rq-wrap" data-rq-request>
    <div class="rq-head">
      <div>
        <h1 data-i18n="nav.rcRequest">ขออัตรากำลังคน</h1>
        <p data-i18n="rq.intro">ฟอร์มออนไลน์ตาม SI-HR-010 เลือกเส้นทางอนุมัติและแนบไฟล์ประกอบ</p>
      </div>
    </div>

    <form class="rq-form" data-rq-form>
      <details class="rq-step-card rq-workflow" open>
        <summary class="rq-step-head">
          <span class="rq-step-main">
            <span class="rq-step-no">1.</span>
            <span class="rq-step-copy">
              <span class="rq-step-title" data-i18n="rq.workflowTitle">กรุณากรอกข้อมูล</span>
              <span class="rq-step-note" data-i18n="rq.workflowStepNote">ตรวจข้อมูลผู้ขอ เลือกเส้นทางอนุมัติ และเลือก Manager</span>
            </span>
          </span>
          <span class="rq-step-caret" aria-hidden="true"></span>
        </summary>
        <div class="rq-workflow-body">
          <div class="rq-field">
            <span class="rq-label" data-i18n="rq.requester">ข้อมูลผู้ขอ</span>
            <div class="rq-requester">
              <span class="rq-requester-avatar" aria-hidden="true">
                @if ($requesterAvatar)
                  <img src="{{ $requesterAvatar }}" alt="">
                @else
                  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                @endif
              </span>
              <div class="rq-requester-grid">
                <div><span data-i18n="rq.empCode">รหัสพนักงาน</span><strong>{{ $requester['code'] }}</strong></div>
                <div><span data-i18n="rq.empName">ชื่อ-สกุล</span><strong>{{ $requester['name'] }}</strong></div>
                <div><span data-i18n="rq.empPosition">ตำแหน่ง</span><strong>{{ $requester['position'] }}</strong></div>
                <div><span data-i18n="rq.empDept">แผนก</span><strong>{{ $requester['department'] }}</strong></div>
              </div>
            </div>
          </div>

          <div class="rq-field">
            <span class="rq-label" data-i18n="rq.chooseRoute">เลือกเส้นทางอนุมัติ</span>

            {{-- ข้อมูลเส้นทางที่เลือก (อยู่บนการ์ดเลือก) — โชว์เฉพาะที่เลือก, role นำหน้าชื่อ, เลือก Manager ได้ตรงนี้ --}}
            @foreach ($routes as $route)
              <div class="rq-chain" data-route-chain data-route-id="{{ $route['id'] }}"{{ $loop->first ? '' : ' hidden' }}>
                @foreach ($route['steps'] as $i => $step)
                  @if ($i > 0)<span class="rq-chain-sep" aria-hidden="true">→</span>@endif
                  <div class="rq-node">
                    <span class="rq-node-role">{{ $step['label'] }}</span>
                    @if (! empty($step['managers']))
                      <select class="rq-chain-manager" name="manager_by_route[{{ $route['id'] }}]" data-route-manager aria-label="เลือก Manager">
                        @foreach ($step['managers'] as $mgr)
                          <option value="{{ $mgr['id'] }}">{{ $mgr['name'] }}</option>
                        @endforeach
                      </select>
                    @else
                      @forelse ($step['names'] ?? [] as $nm)
                        <span class="rq-node-name">{{ $nm }}</span>
                      @empty
                        <span class="rq-node-name">—</span>
                      @endforelse
                    @endif
                  </div>
                @endforeach
              </div>
            @endforeach

            <div class="rq-route-list" data-route-list>
              @foreach ($routes as $route)
                <label class="rq-route-card" data-route-card data-route-id="{{ $route['id'] }}" data-has-gm="{{ ! empty($route['has_gm']) ? '1' : '0' }}">
                  <input type="radio" name="route_id" value="{{ $route['id'] }}" {{ $loop->first ? 'checked' : '' }} required>
                  <span class="rq-route-main">
                    <span class="rq-route-name">{{ $route['name'] }}</span>
                    <span class="rq-route-path">{{ $route['path'] ?: 'ยังไม่ได้กำหนดขั้นตอน' }}</span>
                  </span>
                </label>
              @endforeach
            </div>
            <div class="rq-empty-note{{ $routes ? '' : ' is-visible' }}" data-route-empty data-i18n="rq.routeEmpty">
              ยังไม่มีเส้นทางที่เลือกได้ ตรวจสอบการตั้งค่าเส้นทางอนุมัติในหน้าตั้งค่าระบบ
            </div>
          </div>
        </div>
      </details>

      <details class="rq-step-card rq-paper-step" open>
        <summary class="rq-step-head">
          <span class="rq-step-main">
            <span class="rq-step-no">2.</span>
            <span class="rq-step-copy">
              <span class="rq-step-title" data-i18n="rq.formStepTitle">กรอกฟอร์มขออัตรากำลังคน</span>
              <span class="rq-step-note" data-i18n="rq.formStepNote">กรอกข้อมูลในเอกสาร SI-HR-010 ให้ครบ</span>
            </span>
          </span>
          <span class="rq-step-caret" aria-hidden="true"></span>
        </summary>
        <div class="rq-step-body">
          <div class="rq-paper-shell">
            <section class="rq-paper" aria-label="Employee Requisition Form">
              <div class="rq-paper-head">
                <img class="rq-company-logo" src="{{ $companyLogo }}" alt="Supavut logo">
                <div class="rq-paper-title">
                  <h2>Employee Requisition Form</h2>
                  <p>(ใบเสนอขออัตรากำลังคน)</p>
                </div>
                <span aria-hidden="true"></span>
              </div>

              <div class="rq-table">
            <div class="rq-row" style="--cols: 1fr;">
              <div class="rq-cell">
                <span class="rq-th">ตำแหน่งที่ร้องขอ</span>
                <span class="rq-en">(Position Required)</span>
                <input class="rq-paper-input" name="position_required" type="text" placeholder="กรอกตำแหน่งที่ต้องการ" required>
              </div>
            </div>

            <div class="rq-row" style="--cols: 1.35fr .95fr;">
              <div class="rq-cell">
                <span class="rq-th">แผนก/ส่วน</span>
                <span class="rq-en">(Section/Division)</span>
                <input class="rq-paper-input" name="section_division" type="text" placeholder="เช่น Plastic/Injection 1">
              </div>
              <div class="rq-cell">
                <span class="rq-th">ฝ่าย</span>
                <span class="rq-en">(Department)</span>
                <input class="rq-paper-input" name="department_name" type="text" placeholder="เลือกแผนกด้านบน" data-paper-department>
              </div>
            </div>

            <div class="rq-band">เหตุผลในการร้องขอ (Justification)</div>

            <div class="rq-row" style="--cols: .9fr 1.3fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="request_reason_type" value="additional_out_plan" required>
                  <span class="rq-check-copy"><strong>ขอเพิ่ม นอกแผนกำลังคน</strong><span>Additional, Out of Manpower Plan</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <span class="rq-th">ระบุเหตุผล</span>
                <span class="rq-en">(Specify reason)</span>
                <input class="rq-paper-input" name="additional_out_reason" type="text">
              </div>
            </div>

            <div class="rq-row" style="--cols: .9fr 1.3fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="request_reason_type" value="additional_in_plan">
                  <span class="rq-check-copy"><strong>ขอเพิ่ม ในแผนกำลังคน</strong><span>Additional, In Manpower Plan</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <span class="rq-th">ระบุเหตุผล</span>
                <span class="rq-en">(Specify reason)</span>
                <input class="rq-paper-input" name="additional_in_reason" type="text">
              </div>
            </div>

            <div class="rq-row" style="--cols: .9fr 1.3fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="request_reason_type" value="replacement_resigned">
                  <span class="rq-check-copy"><strong>ขอทดแทนพนักงานออก</strong><span>Replacement Resigned</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <div class="rq-inline-fields">
                  <div>
                    <span class="rq-th">ชื่อพนักงาน</span>
                    <span class="rq-en">(Employee's Name)</span>
                    <input class="rq-paper-input" name="resigned_employee_name" type="text">
                  </div>
                  <div>
                    <span class="rq-th">วันที่ออก</span>
                    <span class="rq-en">(Resigned Date)</span>
                    <input class="rq-paper-input" name="resigned_date" type="date">
                  </div>
                </div>
              </div>
            </div>

            <div class="rq-row" style="--cols: .9fr 1.3fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="request_reason_type" value="replacement_transferred">
                  <span class="rq-check-copy"><strong>ขอทดแทนพนักงานโยกย้าย</strong><span>Replacement Transferred</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <div class="rq-inline-fields">
                  <div>
                    <span class="rq-th">ชื่อพนักงาน</span>
                    <span class="rq-en">(Employee's Name)</span>
                    <input class="rq-paper-input" name="transferred_employee_name" type="text">
                  </div>
                  <div>
                    <span class="rq-th">วันที่โยกย้าย</span>
                    <span class="rq-en">(Transferred Date)</span>
                    <input class="rq-paper-input" name="transferred_date" type="date">
                  </div>
                </div>
              </div>
            </div>

            <div class="rq-row" style="--cols: .9fr 1.3fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="request_reason_type" value="replacement_promotion">
                  <span class="rq-check-copy"><strong>ขอทดแทนพนักงานเลื่อนตำแหน่ง</strong><span>Replacement Promotion</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <div class="rq-inline-fields">
                  <div>
                    <span class="rq-th">ชื่อพนักงาน</span>
                    <span class="rq-en">(Employee's Name)</span>
                    <input class="rq-paper-input" name="promotion_employee_name" type="text">
                  </div>
                  <div>
                    <span class="rq-th">วันที่เลื่อนตำแหน่ง</span>
                    <span class="rq-en">(Promotion Date)</span>
                    <input class="rq-paper-input" name="promotion_date" type="date">
                  </div>
                </div>
              </div>
            </div>

            <div class="rq-band">จำนวนที่ร้องขอ (Number Required)</div>
            <div class="rq-row" style="--cols: 1fr 1fr 1fr;">
              <div class="rq-cell">
                <div class="rq-number-grid">
                  <span class="rq-th">เพศชาย</span>
                  <input class="rq-paper-input" name="male_count" type="number" min="0" value="0" data-count-input>
                  <span class="rq-th">คน</span>
                </div>
              </div>
              <div class="rq-cell">
                <div class="rq-number-grid">
                  <span class="rq-th">เพศหญิง</span>
                  <input class="rq-paper-input" name="female_count" type="number" min="0" value="0" data-count-input>
                  <span class="rq-th">คน</span>
                </div>
              </div>
              <div class="rq-cell">
                <div class="rq-number-grid">
                  <span class="rq-th">รวม (Total)</span>
                  <input class="rq-paper-input" name="total_count" type="number" min="1" value="0" readonly data-total-count>
                  <span class="rq-th">คน</span>
                </div>
              </div>
            </div>

            <div class="rq-band">คุณสมบัติ (Qualifications)</div>
            <div class="rq-row" style="--cols: .72fr 1.8fr;">
              <div class="rq-cell middle">
                <span class="rq-th">อายุ (Age)</span>
              </div>
              <div class="rq-cell">
                <input class="rq-paper-input" name="age_requirement" type="text" placeholder="เช่น 22 ปีขึ้นไป">
              </div>
            </div>

            <div class="rq-row" style="--cols: .72fr 1.8fr;">
              <div class="rq-cell middle">
                <span class="rq-th">ระดับการศึกษา</span>
                <span class="rq-en">(Educational Level)</span>
              </div>
              <div class="rq-cell">
                <div class="rq-education-row">
                  <label class="rq-check-line">
                    <input type="radio" name="education_level" value="secondary">
                    <span class="rq-check-copy"><strong>ม.3/ม.6</strong><span>Secondary/High School</span></span>
                  </label>
                  <input class="rq-paper-input" name="major_secondary" type="text" placeholder="สาขา (Major)">
                </div>
                <div class="rq-education-row">
                  <label class="rq-check-line">
                    <input type="radio" name="education_level" value="certificate">
                    <span class="rq-check-copy"><strong>ปวช.</strong><span>Certificate</span></span>
                  </label>
                  <input class="rq-paper-input" name="major_certificate" type="text" placeholder="สาขา (Major)">
                </div>
                <div class="rq-education-row">
                  <label class="rq-check-line">
                    <input type="radio" name="education_level" value="diploma">
                    <span class="rq-check-copy"><strong>ปวส.</strong><span>Diploma</span></span>
                  </label>
                  <input class="rq-paper-input" name="major_diploma" type="text" placeholder="สาขา (Major)">
                </div>
                <div class="rq-education-row">
                  <label class="rq-check-line">
                    <input type="radio" name="education_level" value="bachelor">
                    <span class="rq-check-copy"><strong>ปริญญาตรี</strong><span>Bachelor</span></span>
                  </label>
                  <input class="rq-paper-input" name="major_bachelor" type="text" placeholder="สาขา (Major)">
                </div>
                <div class="rq-education-row">
                  <label class="rq-check-line">
                    <input type="radio" name="education_level" value="others">
                    <span class="rq-check-copy"><strong>อื่น ๆ</strong><span>Others</span></span>
                  </label>
                  <input class="rq-paper-input" name="major_others" type="text" placeholder="สาขา (Major)">
                </div>
              </div>
            </div>

            <div class="rq-row" style="--cols: .72fr 1.8fr;">
              <div class="rq-cell middle">
                <span class="rq-th">ประสบการณ์</span>
                <span class="rq-en">(Experience)</span>
              </div>
              <div class="rq-cell">
                <div class="rq-mini-grid">
                  <input class="rq-paper-input" name="experience_years" type="text" placeholder="เช่น 0-1 ปี">
                  <input class="rq-paper-input" name="experience_field" type="text" placeholder="ทางด้าน">
                </div>
              </div>
            </div>

            <div class="rq-row" style="--cols: 1fr;">
              <div class="rq-cell tall">
                <span class="rq-th">อื่น ๆ Other</span>
                <textarea class="rq-paper-textarea" name="other_qualification" placeholder="เช่น ตาไม่บอดสี มีความขยัน สามารถทำงานเข้ากะได้ ใช้คอมพิวเตอร์เบื้องต้นได้"></textarea>
              </div>
            </div>

            <div class="rq-band">ลักษณะงาน (Job Descriptions)</div>
            <div class="rq-row" style="--cols: 1fr;">
              <div class="rq-cell x-tall">
                <label class="rq-job-line"><span>1.</span><input class="rq-paper-input" name="job_description[]" type="text" placeholder="ระบุลักษณะงานข้อที่ 1"></label>
                <label class="rq-job-line"><span>2.</span><input class="rq-paper-input" name="job_description[]" type="text" placeholder="ระบุลักษณะงานข้อที่ 2"></label>
                <label class="rq-job-line"><span>3.</span><input class="rq-paper-input" name="job_description[]" type="text" placeholder="ระบุลักษณะงานข้อที่ 3"></label>
              </div>
            </div>

            <div class="rq-band">การสรรหา (Recruitment)</div>
            <div class="rq-row" style="--cols: 1fr 1fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="recruitment_type" value="internal" required>
                  <span class="rq-check-copy"><strong>การสรรหาภายในองค์กร</strong><span>Internal Recruitment</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="recruitment_type" value="external">
                  <span class="rq-check-copy"><strong>การสรรหาภายนอกองค์กร</strong><span>External Recruitment</span></span>
                </label>
              </div>
            </div>

            <div class="rq-band">ลักษณะการจ้าง (Employment Type)</div>
            <div class="rq-row" style="--cols: 1fr 1.45fr 1fr;">
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="employment_type" value="permanent" required>
                  <span class="rq-check-copy"><strong>พนักงานประจำ</strong><span>Permanent Employee</span></span>
                </label>
              </div>
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="employment_type" value="temporary">
                  <span class="rq-check-copy"><strong>พนักงานชั่วคราว</strong><span>Temporary</span></span>
                </label>
                <input class="rq-paper-input" name="temporary_until" type="text" placeholder="ระบุวันที่">
              </div>
              <div class="rq-cell">
                <label class="rq-check-line">
                  <input type="radio" name="employment_type" value="student_trainee">
                  <span class="rq-check-copy"><strong>นักศึกษาฝึกงาน</strong><span>Student Trainee</span></span>
                </label>
              </div>
            </div>

            <div class="rq-band">การพิจารณาอนุมัติ (Approval)</div>
            <div class="rq-approval-grid" data-approval-grid>
              <div class="rq-cell rq-sign-box">
                <span class="rq-th">Manager (Requestor)</span>
                <span class="rq-en">ผู้ร้องขอ (Requestor)</span>
                <div class="rq-sign-space"></div>
                <div class="rq-date-line"><span>Date:</span><input class="rq-paper-input rq-disabled-input" value="อัตโนมัติเมื่อเซ็น" readonly></div>
              </div>
              <div class="rq-cell rq-sign-box" data-gm-approval hidden>
                <span class="rq-th">G.M.</span>
                <span class="rq-en">ผู้อนุมัติ (General Manager)</span>
                <div class="rq-sign-space">รอการอนุมัติ</div>
                <div class="rq-date-line"><span>Date:</span><input class="rq-paper-input rq-disabled-input" value="อัตโนมัติเมื่อเซ็น" readonly></div>
              </div>
              <div class="rq-cell rq-sign-box">
                <span class="rq-th">HR. MGR.</span>
                <span class="rq-en">ผู้ทบทวน (Reviewed)</span>
                <div class="rq-sign-space">รอการอนุมัติ</div>
                <div class="rq-date-line"><span>Date:</span><input class="rq-paper-input rq-disabled-input" value="อัตโนมัติเมื่อเซ็น" readonly></div>
              </div>
              <div class="rq-cell rq-sign-box">
                <span class="rq-th">President/Vice President</span>
                <span class="rq-en">ผู้อนุมัติ (Approved)</span>
                <div class="rq-sign-space">รอการอนุมัติ</div>
                <div class="rq-date-line"><span>Date:</span><input class="rq-paper-input rq-disabled-input" value="อัตโนมัติเมื่อเซ็น" readonly></div>
              </div>
            </div>

            <div class="rq-band rq-recruit-only">รายชื่อผู้ได้รับการว่าจ้าง (Name list of employee)</div>
            <div class="rq-row rq-recruit-only" style="--cols: 1fr 1fr;">
              <div class="rq-cell rq-name-list-cell">
                <label class="rq-name-list-line"><span>1.</span><span>ชื่อ-นามสกุล:</span><input class="rq-paper-input rq-disabled-input" type="text" disabled></label>
              </div>
              <div class="rq-cell rq-name-list-cell">
                <label class="rq-name-list-line start-date"><span>วันที่เริ่มงาน:</span><input class="rq-paper-input rq-disabled-input" type="text" disabled></label>
              </div>
            </div>
            <div class="rq-row rq-recruit-only" style="--cols: 1fr 1fr;">
              <div class="rq-cell rq-name-list-cell">
                <label class="rq-name-list-line"><span>2.</span><span>ชื่อ-นามสกุล:</span><input class="rq-paper-input rq-disabled-input" type="text" disabled></label>
              </div>
              <div class="rq-cell rq-name-list-cell">
                <label class="rq-name-list-line start-date"><span>วันที่เริ่มงาน:</span><input class="rq-paper-input rq-disabled-input" type="text" disabled></label>
              </div>
            </div>
            <div class="rq-row rq-recruit-only" style="--cols: 1fr 1fr;">
              <div class="rq-cell rq-name-list-cell">
                <label class="rq-name-list-line"><span>3.</span><span>ชื่อ-นามสกุล:</span><input class="rq-paper-input rq-disabled-input" type="text" disabled></label>
              </div>
              <div class="rq-cell rq-name-list-cell">
                <label class="rq-name-list-line start-date"><span>วันที่เริ่มงาน:</span><input class="rq-paper-input rq-disabled-input" type="text" disabled></label>
              </div>
            </div>
            <div class="rq-row rq-recruit-only" style="--cols: 1fr 1fr;">
              <div class="rq-cell rq-recruit-sign-cell">
                <span class="rq-th">ลงชื่อเจ้าหน้าที่สรรหา</span>
                <span class="rq-en">(Recruiter)</span>
              </div>
              <div class="rq-cell rq-recruit-sign-cell">
                <span class="rq-th">วันที่:</span>
                <span class="rq-en">(Date)</span>
              </div>
            </div>
          </div>

          <div class="rq-doc-foot">SI-HR-010 REV.01/14-01-11</div>
        </section>
        </div>
        </div>
      </details>

      <details class="rq-step-card rq-attachments" open>
        <summary class="rq-step-head">
          <span class="rq-step-main">
            <span class="rq-step-no">3.</span>
            <span class="rq-step-copy">
              <span class="rq-step-title" data-i18n="rq.attachJd">แนบไฟล์ Job Description</span>
              <span class="rq-step-note" data-i18n="rq.jdStepNote">แนบไฟล์ JD ของตำแหน่งที่ต้องการสรรหา</span>
            </span>
          </span>
          <span class="rq-step-caret" aria-hidden="true"></span>
        </summary>
        <div class="rq-step-body">
          <div class="rq-upload-grid">
            <label class="rq-upload">
              <strong data-i18n="rq.attachJd">แนบไฟล์ Job Description</strong>
              <span data-i18n="rq.attachJdNote">รองรับ PDF, Word, Excel หรือรูปภาพ</span>
              <input type="file" name="job_description_file" data-file-input data-file-target="jd" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
              <ul class="rq-file-list" data-file-list="jd"></ul>
            </label>
          </div>
        </div>
      </details>

      <details class="rq-step-card rq-attachments" open>
        <summary class="rq-step-head">
          <span class="rq-step-main">
            <span class="rq-step-no">4.</span>
            <span class="rq-step-copy">
              <span class="rq-step-title" data-i18n="rq.attachResign">แนบไฟล์ใบลาออก</span>
              <span class="rq-step-note" data-i18n="rq.resignStepNote">ใช้เมื่อขอทดแทนพนักงานลาออก หรือแนบเอกสารตามจำเป็น</span>
            </span>
          </span>
          <span class="rq-step-caret" aria-hidden="true"></span>
        </summary>
        <div class="rq-step-body">
        <div class="rq-upload-grid">
          <label class="rq-upload">
            <strong data-i18n="rq.attachResign">แนบไฟล์ใบลาออก</strong>
            <span data-i18n="rq.attachResignNote">ใช้กรณีขอทดแทนพนักงานลาออก หรือแนบเอกสารอื่นตามจำเป็น</span>
            <input type="file" name="resignation_file" data-file-input data-file-target="resign" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <ul class="rq-file-list" data-file-list="resign"></ul>
          </label>
        </div>
        </div>
      </details>

      <div class="rq-result" data-rq-result data-i18n="rq.resultMock">
        แสดงผลเหมือนส่งคำขอแล้ว แต่ยังไม่บันทึกฐานข้อมูลจริงในรอบนี้
      </div>

      <div class="rq-submit-row">
        <button class="rq-btn" type="reset" data-i18n="rq.reset">ล้างฟอร์ม</button>
        <button class="rq-btn primary" type="button" data-open-confirm data-i18n="rq.submit">ส่งคำขอ</button>
      </div>
    </form>

    <div class="rq-confirm" data-rq-confirm hidden>
      <div class="rq-confirm-backdrop" data-confirm-cancel></div>
      <section class="rq-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="rqConfirmTitle">
        <h2 id="rqConfirmTitle" data-i18n="rq.confirmTitle">ยืนยันการส่งคำขอ</h2>
        <p data-i18n="rq.confirmNote">ตรวจสอบข้อมูลและไฟล์แนบก่อนส่ง เมื่อบันทึกจริงคำขอจะถูกส่งไปยัง Manager ตามเส้นทางที่เลือก</p>
        <div class="rq-confirm-actions">
          <button class="rq-btn" type="button" data-confirm-cancel data-i18n="rq.cancel">ยกเลิก</button>
          <button class="rq-btn primary" type="button" data-confirm-submit data-i18n="rq.confirmSubmit">ยืนยันส่งคำขอ</button>
        </div>
      </section>
    </div>
  </div>

  <script>
    'use strict';

    (function () {
      var root = document.querySelector('[data-rq-request]');
      if (!root) return;

      var form = root.querySelector('[data-rq-form]');
      var routeCards = Array.prototype.slice.call(root.querySelectorAll('[data-route-card]'));
      var chains = Array.prototype.slice.call(root.querySelectorAll('[data-route-chain]'));
      var gmApproval = root.querySelector('[data-gm-approval]');
      var approvalGrid = root.querySelector('[data-approval-grid]');
      var confirmBox = root.querySelector('[data-rq-confirm]');
      var resultBox = root.querySelector('[data-rq-result]');
      var totalCount = root.querySelector('[data-total-count]');

      function syncRouteSelection() {
        var selectedId = null;
        routeCards.forEach(function (card) {
          var radio = card.querySelector('input[type="radio"]');
          var on = !!(radio && radio.checked);
          card.classList.toggle('is-selected', on);
          if (on) selectedId = card.getAttribute('data-route-id');
        });

        // แสดงสายอนุมัติของเส้นทางที่เลือก + เปิด manager ของเส้นทางนั้นเส้นเดียว
        chains.forEach(function (chain) {
          var on = chain.getAttribute('data-route-id') === selectedId;
          chain.hidden = !on;
          var mgr = chain.querySelector('[data-route-manager]');
          if (mgr) {
            mgr.disabled = !on;
            mgr.required = on;
          }
        });

        // เส้นทางที่มี General Manager → แสดง 4 ช่องอนุมัติขนาดเท่ากันในบรรทัดเดียว
        var sel = selectedId ? root.querySelector('.rq-route-card[data-route-id="' + selectedId + '"]') : null;
        var hasGm = !!(sel && sel.getAttribute('data-has-gm') === '1');
        if (gmApproval) gmApproval.hidden = !hasGm;
        if (approvalGrid) approvalGrid.classList.toggle('has-gm', hasGm);
      }

      function syncTotalCount() {
        if (!totalCount) return;
        var inputs = Array.prototype.slice.call(root.querySelectorAll('[data-count-input]'));
        var total = inputs.reduce(function (sum, input) {
          return sum + Math.max(0, parseInt(input.value || '0', 10) || 0);
        }, 0);
        totalCount.value = total;
        totalCount.setCustomValidity(total > 0 ? '' : 'กรุณาระบุจำนวนที่ร้องขออย่างน้อย 1 คน');
      }

      function renderFileList(input) {
        var target = input.getAttribute('data-file-target');
        var list = root.querySelector('[data-file-list="' + target + '"]');
        if (!list) return;
        list.innerHTML = '';

        Array.prototype.slice.call(input.files || []).forEach(function (file) {
          var item = document.createElement('li');
          item.textContent = file.name;
          list.appendChild(item);
        });
      }

      function openConfirm() {
        syncTotalCount();
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }
        if (confirmBox) confirmBox.hidden = false;
      }

      function closeConfirm() {
        if (confirmBox) confirmBox.hidden = true;
      }

      function confirmSubmitPreview() {
        closeConfirm();
        if (resultBox) {
          resultBox.classList.add('is-visible');
          resultBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }

      routeCards.forEach(function (card) {
        var radio = card.querySelector('input[type="radio"]');
        if (radio) radio.addEventListener('change', syncRouteSelection);
      });
      root.querySelectorAll('[data-count-input]').forEach(function (input) {
        input.addEventListener('input', syncTotalCount);
      });
      root.querySelectorAll('[data-file-input]').forEach(function (input) {
        input.addEventListener('change', function () {
          renderFileList(input);
        });
      });
      root.querySelector('[data-open-confirm]')?.addEventListener('click', openConfirm);
      root.querySelectorAll('[data-confirm-cancel]').forEach(function (button) {
        button.addEventListener('click', closeConfirm);
      });
      root.querySelector('[data-confirm-submit]')?.addEventListener('click', confirmSubmitPreview);
      form.addEventListener('reset', function () {
        window.setTimeout(function () {
          syncTotalCount();
          syncRouteSelection();
          if (resultBox) resultBox.classList.remove('is-visible');
          root.querySelectorAll('[data-file-list]').forEach(function (list) {
            list.innerHTML = '';
          });
        }, 0);
      });

      syncTotalCount();
      syncRouteSelection();
    })();
  </script>
@endsection
