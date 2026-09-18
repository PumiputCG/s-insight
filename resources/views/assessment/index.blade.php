@extends('layouts.portal')

@section('title', 'SUPAVUT Assessment')
@section('body-class', 'assessment-home-body')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.title">ระบบประเมินพนักงาน</span>
@endsection

@php
  $overview = $overview ?? [];
  $round = $overview['round'] ?? null;
  $reviewers = collect($overview['reviewers'] ?? []);
  $isAdminOverview = isset($me) && $me->isAdmin();
  $subject = $overview['subject'] ?? [
    'employee_code' => $me->employee_code ?? '-',
    'name' => isset($me) ? ($me->fullNameTh() ?: $me->full_name_en ?: '-') : '-',
    'position' => $me->position ?? '-',
    'department' => $me->department ?? '-',
    'avatar' => isset($me) && $me->profile_picture ? asset('storage/'.$me->profile_picture) : null,
    'initial' => isset($me) ? mb_substr(($me->fullNameTh() ?: $me->full_name_en ?: '?'), 0, 1, 'UTF-8') : '?',
  ];

  $infoIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M8 9h8M8 15h8M8 12h3"></path></svg>';
  $userIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>';
  $jobIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"></path></svg>';
  $deptIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h9v18"></path><path d="M15 9h3a2 2 0 0 1 2 2v10"></path><path d="M8 7h3M8 11h3M8 15h3M18 14h.01M18 17h.01"></path></svg>';
@endphp

@section('page-style')
    body.assessment-home-body .main {
      min-height: calc(100vh - var(--topbar-h));
      min-height: calc(100dvh - var(--topbar-h));
      padding: clamp(1.15rem, 2.4vw, 2rem) !important;
      overflow: auto;
      background: #f7f8f4;
      color: #161915;
    }

    .asm-overview {
      position: relative;
      width: min(100%, 82rem);
      margin: 0 auto;
      padding-top: .2rem;
      color: #161915;
    }

    .asm-overview::before,
    .asm-overview::after {
      content: "";
      position: absolute;
      z-index: 0;
      pointer-events: none;
      background-image: radial-gradient(#a8bdaa 1.8px, transparent 1.9px);
      background-size: 15px 15px;
      opacity: .58;
    }

    .asm-overview::before {
      width: 8.8rem;
      height: 7.2rem;
      top: 1.1rem;
      left: -.2rem;
    }

    .asm-overview::after {
      width: 8rem;
      height: 5.8rem;
      top: 2.2rem;
      right: .45rem;
    }

    .asm-overview-brand {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .75rem;
      margin: .3rem 0 1.45rem;
      font-family: "Montserrat", var(--font-body);
      font-size: clamp(1.25rem, 2.2vw, 1.9rem);
      font-weight: 800;
      letter-spacing: .03em;
      line-height: 1.15;
      text-transform: uppercase;
    }

    .asm-overview-brand::after {
      content: "";
      position: absolute;
      z-index: -1;
      width: 13rem;
      height: 13rem;
      right: clamp(2rem, 12vw, 13rem);
      top: -7.2rem;
      border: 1px solid rgb(255 111 21 / 26%);
      border-radius: 50%;
      box-shadow:
        1.15rem 1.15rem 0 -1px rgb(255 111 21 / 17%),
        2.3rem 2.3rem 0 -1px rgb(255 111 21 / 11%),
        3.45rem 3.45rem 0 -1px rgb(255 111 21 / 8%);
      pointer-events: none;
    }

    .asm-brand-mark {
      width: 2.05rem;
      height: 2.05rem;
      display: block;
      object-fit: contain;
      flex: 0 0 auto;
    }

    .asm-brand-accent { color: #167c36; }

    .asm-overview-frame {
      position: relative;
      z-index: 1;
      display: grid;
      min-height: clamp(33rem, calc(100dvh - var(--topbar-h, 0px) - 8.5rem), 46rem);
      padding: clamp(1.35rem, 2.6vw, 2.35rem);
      border: 1px solid rgb(35 91 48 / 18%);
      border-radius: 0.25rem;
      background: rgb(255 255 255 / 74%);
      box-shadow: 0 22px 70px rgb(29 48 28 / 8%);
      overflow: hidden;
    }

    .asm-overview-frame::before,
    .asm-overview-frame::after {
      display: none;
    }

    .asm-frame-corner {
      position: absolute;
      z-index: 1;
      width: 2.2rem;
      height: 2.2rem;
      border-color: #087a30;
      pointer-events: none;
    }

    .asm-frame-corner.tl { top: 0; left: 0; border-top: 3px solid; border-left: 3px solid; }
    .asm-frame-corner.tr { top: 0; right: 0; border-top: 3px solid; border-right: 3px solid; }

    .asm-overview-inner {
      position: relative;
      z-index: 2;
      display: grid;
      gap: 1rem;
    }

    .asm-overview-inner.is-admin-overview {
      min-height: clamp(24rem, calc(100dvh - var(--topbar-h, 0px) - 15rem), 34rem);
      grid-template-rows: minmax(0, 1fr);
      align-content: stretch;
      justify-items: stretch;
    }

    .asm-reviewer-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .asm-reviewer-grid.is-single {
      grid-template-columns: 1fr;
    }

    .asm-reviewer-card,
    .asm-me-card,
    .asm-empty-card {
      border: 1px solid rgb(32 64 41 / 14%);
      border-radius: 0.26rem;
      background: rgb(255 255 255 / 82%);
      box-shadow: 0 14px 42px rgb(22 40 21 / 6%);
    }

    .asm-reviewer-card {
      padding: 1.35rem 1.45rem 1.2rem;
    }

    .asm-level-badge,
    .asm-me-badge {
      width: fit-content;
      min-height: 2rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: .3rem .8rem;
      border-radius: 0.25rem;
      background: #dcefe2;
      color: #087a30;
      font-weight: 800;
      line-height: 1.1;
    }

    .asm-level-badge.is-muted {
      background: #ececec;
      color: #141414;
    }

    .asm-reviewer-main {
      display: grid;
      grid-template-columns: 8.25rem minmax(13rem, 22rem);
      gap: 1.25rem;
      align-items: center;
      justify-content: center;
      margin-top: 1.25rem;
    }

    .asm-avatar {
      position: relative;
      width: 7.35rem;
      height: 7.35rem;
      display: grid;
      place-items: center;
      padding: 0;
      border-radius: 50%;
      overflow: hidden;
      border: 1px solid rgb(0 0 0 / 8%);
      background: #dfe5df;
      color: #1e3a8a;
      font-size: 2.2rem;
      font-weight: 800;
      line-height: 1;
    }

    button.asm-avatar {
      appearance: none;
      cursor: zoom-in;
      transition:
        border-color .18s var(--ease-out),
        box-shadow .18s var(--ease-out),
        transform .18s var(--ease-out);
    }

    button.asm-avatar:hover,
    button.asm-avatar:focus-visible {
      border-color: #087a30;
      box-shadow: 0 14px 32px rgb(8 122 48 / 18%);
      transform: translateY(-1px);
      outline: none;
    }

    .asm-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .asm-avatar svg {
      width: 62%;
      height: 62%;
      fill: currentColor;
      opacity: .72;
    }

    .asm-avatar-action-label {
      position: absolute;
      inset: auto 0 0;
      display: grid;
      min-height: 1.42rem;
      place-items: center;
      background: rgb(0 0 0 / 58%);
      color: #fff;
      font-size: .62rem;
      font-weight: 800;
      line-height: 1.2;
      opacity: 0;
      transform: translateY(100%);
      transition:
        opacity .18s var(--ease-out),
        transform .18s var(--ease-out);
    }

    button.asm-avatar:hover .asm-avatar-action-label,
    button.asm-avatar:focus-visible .asm-avatar-action-label {
      opacity: 1;
      transform: translateY(0);
    }

    .asm-person-list {
      display: grid;
      gap: .68rem;
      min-width: 0;
    }

    .asm-person-row {
      display: grid;
      grid-template-columns: 1.55rem minmax(0, 1fr);
      gap: .75rem;
      align-items: center;
      min-width: 0;
      color: #141414;
      font-size: .98rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .asm-person-row svg {
      width: 1.35rem;
      height: 1.35rem;
      fill: none;
      stroke: #505050;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .asm-person-row span {
      min-width: 0;
      overflow-wrap: anywhere;
    }

    .asm-status-line {
      display: flex;
      align-items: center;
      gap: .7rem;
      margin-top: 1.25rem;
      padding-top: 1.1rem;
      border-top: 1px solid rgb(0 0 0 / 10%);
      font-size: .98rem;
      font-weight: 700;
    }

    .asm-status-icon {
      width: 1.8rem;
      height: 1.8rem;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: #333;
      color: #fff;
      flex: 0 0 auto;
    }

    .asm-status-icon svg {
      width: 1.05rem;
      height: 1.05rem;
      fill: none;
      stroke: currentColor;
      stroke-width: 2.4;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .asm-status-line.is-done .asm-status-icon { background: #0b963b; }
    .asm-status-line.is-done strong { color: #0b963b; }
    .asm-status-line.is-pending .asm-status-icon { background: #d69a1f; }
    .asm-status-line.is-pending strong { color: #a86400; }

    .asm-self-cta {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      align-self:flex-start;
      margin-top:.9rem;
      padding:.58rem .9rem;
      border:1px solid #476f4b;
      border-radius:.28rem;
      background:#476f4b;
      color:#fff;
      text-decoration:none;
      font-size:.82rem;
      font-weight:700;
    }

    .asm-self-cta:hover { background:#385c3d; border-color:#385c3d; }

    .asm-empty-card {
      display: grid;
      place-items: center;
      min-height: 14rem;
      padding: 1.5rem;
      text-align: center;
    }

    .asm-empty-card strong {
      display: block;
      color: #087a30;
      font-size: 1.25rem;
      font-weight: 800;
      line-height: 1.3;
    }

    .asm-empty-card span {
      display: block;
      max-width: 36rem;
      margin-top: .35rem;
      color: rgb(0 0 0 / 58%);
      font-size: .92rem;
      line-height: 1.55;
    }

    .asm-me-card {
      padding: 1.3rem 1.45rem;
    }

    .asm-me-card.is-admin-profile {
      width: 100%;
      min-height: 100%;
      display: grid;
      align-items: center;
      justify-self: stretch;
    }

    .asm-me-main {
      display: grid;
      grid-template-columns: 8.25rem minmax(13rem, 22rem);
      gap: 1.25rem;
      align-items: center;
      justify-content: center;
      margin-top: 1.15rem;
    }

    .asm-me-card.is-admin-profile .asm-me-main {
      width: min(100%, 34rem);
      justify-self: center;
      margin-top: 0;
    }

    .asm-me-badge {
      min-width: 4.7rem;
      background: #087a30;
      color: #fff;
    }

    .asm-me-card .asm-avatar {
      width: 7.6rem;
      height: 7.6rem;
      justify-self: center;
    }

    .asm-me-details {
      display: grid;
      gap: .68rem;
      min-width: 0;
    }

    @media (max-width: 1100px) {
      .asm-reviewer-grid,
      .asm-reviewer-grid.is-single {
        grid-template-columns: 1fr;
      }

      .asm-me-card {
        padding: 1.2rem;
      }
    }

    @media (max-width: 680px) {
      body.assessment-home-body .main {
        padding: .9rem !important;
      }

      .asm-overview-brand {
        justify-content: flex-start;
        font-size: 1.1rem;
      }

      .asm-overview-brand::after {
        right: -2rem;
        top: -7.8rem;
      }

      .asm-overview-frame {
        min-height: auto;
        padding: .85rem;
      }

      .asm-overview-inner.is-admin-overview {
        min-height: 24rem;
      }

      .asm-reviewer-card,
      .asm-me-card,
      .asm-empty-card {
        padding: 1rem;
      }

      .asm-reviewer-main {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: left;
      }

      .asm-me-main {
        grid-template-columns: 1fr;
        justify-items: center;
      }

      .asm-person-list {
        width: 100%;
      }

      .asm-avatar {
        width: 6.5rem;
        height: 6.5rem;
      }

      .asm-me-details { width: 100%; }

      .asm-profile-facts {
        grid-template-columns: 1fr;
      }
    }
@endsection

@section('content')
  <section class="asm-overview" aria-label="ภาพรวม SUPAVUT Assessment">
    <div class="asm-overview-brand" aria-hidden="true">
      <img class="asm-brand-mark" src="{{ asset('assets/assessment/supavut-assessment-mark.png') }}" alt="">
      <span>SUPAVUT</span>
      <span class="asm-brand-accent">ASSESSMENT</span>
    </div>

    <div class="asm-overview-frame">
      <span class="asm-frame-corner tl" aria-hidden="true"></span>
      <span class="asm-frame-corner tr" aria-hidden="true"></span>

      <div class="asm-overview-inner {{ $isAdminOverview ? 'is-admin-overview' : '' }}">
        @unless ($isAdminOverview)
          @if (! $round)
            <div class="asm-empty-card">
              <div>
                <strong data-i18n="assessment.home.noOpenRoundTitle">ยังไม่มีรอบประเมินที่เปิดอยู่</strong>
                <span data-i18n="assessment.home.noOpenRoundBody">เมื่อ HR เปิดรอบประเมิน ระบบจะแสดงผู้ประเมินลำดับชั้น 1 และ 2 ของคุณในหน้านี้</span>
              </div>
            </div>
          @elseif ($reviewers->isEmpty())
            <div class="asm-empty-card">
              <div>
                <strong data-i18n="assessment.home.noEvaluatorsTitle">ยังไม่มีผู้ประเมิน</strong>
                <span data-i18n="assessment.home.noEvaluatorsBody">ยังไม่มีรหัสพนักงานและชื่อ-สกุลในลำดับชั้น 1 หรือ 2 สำหรับข้อมูลของคุณในรอบนี้</span>
              </div>
            </div>
          @else
            <div class="asm-reviewer-grid {{ $reviewers->count() === 1 ? 'is-single' : '' }}">
              @foreach ($reviewers as $reviewer)
                <article class="asm-reviewer-card">
                  @php($levelLabelKey = $reviewer['level_label_key'] ?? null)
                  <div class="asm-level-badge {{ in_array(1, $reviewer['levels'] ?? [], true) ? '' : 'is-muted' }}" @if ($levelLabelKey) data-i18n="{{ $levelLabelKey }}" @endif>{{ $reviewer['level_label'] ?? 'ผู้ประเมินระดับ '.$reviewer['level'] }}</div>

                  <div class="asm-reviewer-main">
                    @if ($reviewer['avatar'])
                      {{-- มีรูปจริง → เปิดด้วย image viewer กลางของ portal (ซูมได้ ชุดเดียวกับหน้าประเมินตัวเอง) --}}
                      <button
                        type="button"
                        class="asm-avatar"
                        data-image-preview
                        data-image-src="{{ $reviewer['avatar'] }}"
                        data-image-alt="{{ $reviewer['name'] }}"
                        aria-label="ดูรูปโปรไฟล์ {{ $reviewer['name'] }}"
                        data-i18n-aria="assessment.evaluate.viewPhoto"
                      >
                        <img src="{{ $reviewer['avatar'] }}" alt="">
                        <span class="asm-avatar-action-label" data-i18n="assessment.evaluate.viewPhoto">ดูรูป</span>
                      </button>
                    @else
                      <div class="asm-avatar" aria-hidden="true">
                        @include('assessment.partials.default-avatar')
                      </div>
                    @endif

                    <div class="asm-person-list">
                      <div class="asm-person-row">{!! $infoIcon !!}<span>{{ $reviewer['employee_code'] }}</span></div>
                      <div class="asm-person-row">{!! $userIcon !!}<span data-loc-th="{{ $reviewer['name'] }}" data-loc-en="{{ $reviewer['name_en'] ?? $reviewer['name'] }}">{{ $reviewer['name'] }}</span></div>
                      <div class="asm-person-row">{!! $jobIcon !!}<span data-loc-th="{{ $reviewer['position'] }}" data-loc-en="{{ $reviewer['position_en'] ?? $reviewer['position'] }}">{{ $reviewer['position'] }}</span></div>
                      <div class="asm-person-row">{!! $deptIcon !!}<span data-loc-th="{{ $reviewer['department'] }}" data-loc-en="{{ $reviewer['department_en'] ?? $reviewer['department'] }}">{{ $reviewer['department'] }}</span></div>
                    </div>
                  </div>

                  <div class="asm-status-line {{ $reviewer['status'] === 'done' ? 'is-done' : 'is-pending' }}">
                    <span><span data-i18n="assessment.evaluate.status">สถานะ</span>:</span>
                    <span class="asm-status-icon" aria-hidden="true">
                      @if ($reviewer['status'] === 'done')
                        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                      @else
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                      @endif
                    </span>
                    <strong @if (! empty($reviewer['status_key'])) data-i18n="{{ $reviewer['status_key'] }}" @endif>{{ $reviewer['status_label'] }}</strong>
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        @endunless

        <article class="asm-me-card {{ $isAdminOverview ? 'is-admin-profile' : '' }}">
          @unless ($isAdminOverview)
            <div class="asm-me-badge" data-i18n="assessment.home.me">เรา</div>
          @endunless
          <div class="asm-me-main">
            @if ($subject['avatar'])
              {{-- มีรูปจริง → เปิดด้วย image viewer กลางของ portal (ซูมได้ ชุดเดียวกับหน้าประเมินตัวเอง) --}}
              <button
                type="button"
                class="asm-avatar"
                data-image-preview
                data-image-src="{{ $subject['avatar'] }}"
                data-image-alt="{{ $subject['name'] }}"
                aria-label="ดูรูปโปรไฟล์ {{ $subject['name'] }}"
                data-i18n-aria="assessment.evaluate.viewPhoto"
              >
                <img src="{{ $subject['avatar'] }}" alt="">
                <span class="asm-avatar-action-label" data-i18n="assessment.evaluate.viewPhoto">ดูรูป</span>
              </button>
            @else
              <div class="asm-avatar" aria-hidden="true">
                @include('assessment.partials.default-avatar')
              </div>
            @endif

            <div class="asm-person-list asm-me-details">
              <div class="asm-person-row">{!! $infoIcon !!}<span>{{ $subject['employee_code'] }}</span></div>
              <div class="asm-person-row">{!! $userIcon !!}<span data-loc-th="{{ $subject['name'] }}" data-loc-en="{{ $subject['name_en'] ?? $subject['name'] }}">{{ $subject['name'] }}</span></div>
              <div class="asm-person-row">{!! $jobIcon !!}<span data-loc-th="{{ $subject['position'] }}" data-loc-en="{{ $subject['position_en'] ?? $subject['position'] }}">{{ $subject['position'] }}</span></div>
              <div class="asm-person-row">{!! $deptIcon !!}<span data-loc-th="{{ $subject['department'] }}" data-loc-en="{{ $subject['department_en'] ?? $subject['department'] }}">{{ $subject['department'] }}</span></div>
            </div>
          </div>
          @if (($canSelfAssess ?? false) && ! $isAdminOverview)
            <a href="{{ route('assessment.self.form') }}" class="asm-self-cta" data-i18n="nav.asmSelf">การประเมินตัวเอง</a>
          @endif
        </article>
      </div>
    </div>
  </section>

@endsection
