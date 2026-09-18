@extends('layouts.portal')

@section('title', 'หน้าหลัก — SUPAVUT 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.home.title">หน้าหลัก</span>
@endsection

@section('page-style')
    .a5h-wrap { display:grid; gap:1.2rem; width:min(100%, 82rem); margin:0 auto; padding:.25rem 0 2rem; overflow-x:clip; }

    /* ---- หัวข้อประกาศ + โทรโข่ง ---- */
    .a5h-head { display:flex; align-items:center; padding-bottom:.95rem; border-bottom:2px solid #000; }
    .a5h-title { margin:0; display:inline-flex; align-items:center; gap:.6rem; font-size:1.85rem; font-weight:800; color:var(--light-text); line-height:1.2; }
    .a5h-megaphone { height:2.4rem; width:auto; display:block; }

    /* ---- แท็บเลือกปี ---- */
    .a5h-years { display:flex; gap:.5rem; flex-wrap:wrap; }
    .a5h-year { min-height:2.4rem; padding:.5rem 1.35rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); color:var(--muted-light); font-family:var(--font-body); font-size:.95rem; font-weight:800; letter-spacing:.02em; cursor:pointer; transition:background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .a5h-year:hover, .a5h-year:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    .a5h-year.is-active { background:var(--moss); border-color:var(--moss); color:#fff; box-shadow:0 8px 20px color-mix(in srgb, var(--moss) 40%, transparent); }
    .a5h-panel[hidden] { display:none; }

    /* ---- แถบภาพประกาศเลื่อนซ้ายวนลูป (marquee) — hover หยุด + popup เด้ง ---- */
    .a5h-marquee { position:relative; overflow:hidden; padding:3rem 0 1.55rem;
      -webkit-mask-image:linear-gradient(90deg, transparent, #000 3%, #000 97%, transparent);
      mask-image:linear-gradient(90deg, transparent, #000 3%, #000 97%, transparent); }
    .a5h-marquee::after { content:""; position:absolute; left:0; right:0; bottom:.05rem; z-index:2; height:2px; background:#000; pointer-events:none; }
    .a5h-track { display:flex; gap:1.5rem; width:max-content; animation:a5hScroll 62s linear infinite; }
    .a5h-marquee:hover .a5h-track { animation-play-state:paused; }
    @keyframes a5hScroll { from { transform:translateX(0); } to { transform:translateX(-50%); } }
    .a5h-mitem { position:relative; flex:0 0 auto; width:20rem; }
    .a5h-img-btn { display:block; width:100%; padding:0; border:1px solid var(--line-light); border-radius: 0.36rem; overflow:hidden; background:var(--menu-bg); cursor:zoom-in; box-shadow:0 10px 28px rgb(0 0 0 / 16%); transition:box-shadow .25s ease, border-color .2s ease, transform .25s ease; }
    .a5h-img-btn img { display:block; width:100%; height:auto; }
    /* ปี 2025 ภาพคละแนวตั้ง/แนวนอน → บังคับความสูงเท่ากัน กว้างตามสัดส่วนจริง = เรียงเป็นระเบียบ (2026 คงขนาดเดิม) (Manager 2026-07-23) */
    #a5h-panel-2025 .a5h-mitem { width:auto; height:22rem; }
    #a5h-panel-2025 .a5h-img-btn { width:auto; height:100%; }
    #a5h-panel-2025 .a5h-img-btn img { width:auto; height:100%; }
    .a5h-mitem:hover { z-index:5; }
    .a5h-mitem:hover .a5h-img-btn { transform:translateY(-8px) scale(1.05); border-color:var(--moss); box-shadow:0 22px 55px rgb(0 0 0 / 32%); }
    /* popup เด้งกระดุกดิ๊ก เมื่อชี้ภาพ */
    .a5h-pop { position:absolute; left:50%; bottom:calc(100% + .55rem); transform:translateX(-50%) scale(.4); transform-origin:bottom center; opacity:0; pointer-events:none; white-space:nowrap; padding:.32rem .75rem; border-radius:999px; background:var(--moss); color:#fff; font-size:.78rem; font-weight:800; box-shadow:0 8px 20px rgb(0 0 0 / 34%); }
    .a5h-pop::after { content:""; position:absolute; top:100%; left:50%; transform:translateX(-50%); border:6px solid transparent; border-top-color:var(--moss); }
    .a5h-mitem:hover .a5h-pop { animation:a5hPopIn .45s cubic-bezier(.2,1.5,.3,1) forwards, a5hWobble 1.1s ease-in-out .45s infinite; }
    @keyframes a5hPopIn { from { opacity:0; transform:translateX(-50%) scale(.4); } to { opacity:1; transform:translateX(-50%) scale(1); } }
    @keyframes a5hWobble { 0%,100% { transform:translateX(-50%) rotate(-4deg); } 50% { transform:translateX(-50%) rotate(4deg); } }

    @media (max-width: 640px) {
      .a5h-title { font-size:1.4rem; }
      .a5h-megaphone { height:1.7rem; }
      .a5h-mitem { width:15rem; }
      #a5h-panel-2025 .a5h-mitem { width:auto; height:15rem; }
    }
    /* ลดการเคลื่อนไหว: หยุด marquee แล้ว "พับลงบรรทัด" แทนแถบเลื่อนแนวนอน (ห้ามเลื่อนขวา) */
    @media (prefers-reduced-motion: reduce) {
      .a5h-track { animation:none; flex-wrap:wrap; justify-content:center; width:auto; }
      .a5h-mitem[aria-hidden="true"] { display:none; } /* ซ่อนชุดภาพซ้ำที่ใช้ทำ loop */
      .a5h-marquee { overflow:hidden; -webkit-mask-image:none; mask-image:none; }
      .a5h-mitem:hover .a5h-pop { animation:none; opacity:1; transform:translateX(-50%) scale(1); }
    }
    .image-viewer { z-index:2400; }

    /* ---- Modal ยินดีต้อนรับ (ตาม design) ---- */
    .a5wm[hidden] { display:none; }
    .a5wm { position:fixed; inset:0; z-index:2500; display:grid; place-items:center; padding:1.2rem; background:rgb(15 23 15 / 55%); backdrop-filter:blur(3px); }
    .a5wm-card { position:relative; width:min(100%, 40rem); max-height:92vh; overflow:auto; background:#fff; border-radius: 0.45rem; box-shadow:0 30px 90px rgb(0 0 0 / 34%); animation:a5wmIn .32s cubic-bezier(.16,.7,.3,1); }
    @keyframes a5wmIn { from { opacity:0; transform:translateY(18px) scale(.97); } to { opacity:1; transform:none; } }
    .a5wm-body { position:relative; overflow:hidden; padding:1.7rem 1.8rem 1.3rem; }
    .a5wm-body::before { content:""; position:absolute; inset:0; background:radial-gradient(120% 90% at 100% 0%, rgb(45 148 97 / 13%), transparent 55%), radial-gradient(80% 70% at 0% 100%, rgb(45 148 97 / 9%), transparent 60%); pointer-events:none; }
    .a5wm-brand { position:relative; display:flex; align-items:center; gap:.55rem; }
    .a5wm-brand img { height:2.15rem; width:auto; }
    .a5wm-brand span { font-family:"Montserrat", var(--font-body); font-weight:800; font-size:1.12rem; letter-spacing:.02em; color:#1f2937; }
    .a5wm-hero { position:relative; display:grid; place-items:center; margin:.55rem 0 .1rem; }
    .a5wm-hero img { height:6.4rem; width:auto; max-width:72%; object-fit:contain; }
    .a5wm-divider { position:relative; display:flex; align-items:center; justify-content:center; gap:.5rem; margin:.35rem 0 1.05rem; }
    .a5wm-divider::before, .a5wm-divider::after { content:""; height:1px; width:32%; background:linear-gradient(90deg, transparent, rgb(45 148 97 / 45%)); }
    .a5wm-divider::after { background:linear-gradient(90deg, rgb(45 148 97 / 45%), transparent); }
    .a5wm-divider i { width:.5rem; height:.5rem; border-radius:50%; background:#2f9461; }
    .a5wm-text { position:relative; text-align:center; display:grid; gap:.34rem; }
    .a5wm-hello { margin:0; display:flex; justify-content:center; gap:.3em; flex-wrap:wrap; font-size:1.42rem; font-weight:800; color:#1f2937; line-height:1.3; }
    .a5wm-welcome { margin:0; font-size:1.02rem; font-weight:700; color:#2f9461; }
    .a5wm-desc { margin:.22rem auto 0; max-width:30rem; font-size:.86rem; line-height:1.65; color:#6b7280; }
    .a5wm-close { position:absolute; top:.9rem; right:.9rem; z-index:2; width:2.2rem; height:2.2rem; display:grid; place-items:center; border:1px solid rgb(0 0 0 / 12%); border-radius:50%; background:#fff; color:#6b7280; cursor:pointer; transition:border-color .16s ease, color .16s ease; }
    .a5wm-close:hover, .a5wm-close:focus-visible { border-color:#d98a80; color:#c8463a; outline:none; }
    .a5wm-close svg { width:1.05rem; height:1.05rem; }
    .a5wm-foot { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.8rem; border-top:1px solid rgb(0 0 0 / 8%); background:#f7faf9; flex-wrap:wrap; }
    .a5wm-check { display:inline-flex; align-items:center; gap:.5rem; color:#6b7280; font-size:.83rem; cursor:pointer; user-select:none; }
    .a5wm-check input { width:1.05rem; height:1.05rem; accent-color:#2f9461; cursor:pointer; }
    .a5wm-ok { min-height:2.5rem; padding:.5rem 1.7rem; border:0; border-radius: 0.25rem; background:#2f9461; color:#fff; font-size:.9rem; font-weight:750; cursor:pointer; transition:filter .15s ease; }
    .a5wm-ok:hover, .a5wm-ok:focus-visible { filter:brightness(1.08); outline:none; }
    @media (max-width:520px) { .a5wm-foot { justify-content:center; } .a5wm-hero img { height:5rem; } .a5wm-hello { font-size:1.2rem; } }
    @media (prefers-reduced-motion: reduce) { .a5wm-card { animation:none; } }
@endsection

@section('content')
  <div class="a5h-wrap">
    <div class="a5h-head">
      <h2 class="a5h-title">
        <img class="a5h-megaphone" src="{{ $megaphone }}" alt="" aria-hidden="true">
        <img class="a5h-megaphone" src="{{ $megaphone }}" alt="" aria-hidden="true">
        <span data-i18n="a5s.home.announce">ประกาศ</span>
        <img class="a5h-megaphone" src="{{ $megaphone }}" alt="" aria-hidden="true">
        <img class="a5h-megaphone" src="{{ $megaphone }}" alt="" aria-hidden="true">
      </h2>
    </div>

    {{-- แท็บเลือกปี — default = ปีปัจจุบัน (จาก controller) --}}
    <div class="a5h-years" role="tablist" aria-label="เลือกปีของประกาศ" data-i18n-aria="a5s.common.selectYear">
      @foreach ($imagesByYear as $year => $yearImages)
        <button type="button" class="a5h-year {{ (string) $year === (string) $defaultYear ? 'is-active' : '' }}"
                role="tab" data-year-tab="{{ $year }}"
                aria-selected="{{ (string) $year === (string) $defaultYear ? 'true' : 'false' }}"
                aria-controls="a5h-panel-{{ $year }}">{{ $year }}</button>
      @endforeach
    </div>

    {{-- แถบเลื่อนซ้ายวนลูป ต่อปี: ภาพซ้ำ 2 ชุดเพื่อ loop ไร้รอยต่อ (hover หยุด + คลิกซูม/แพน) --}}
    @foreach ($imagesByYear as $year => $yearImages)
      @php $count = max(count($yearImages), 1); @endphp
      <div class="a5h-panel" id="a5h-panel-{{ $year }}" role="tabpanel" data-year-panel="{{ $year }}"
           {{ (string) $year === (string) $defaultYear ? '' : 'hidden' }}>
        <div class="a5h-marquee" data-marquee>
          <div class="a5h-track">
            @foreach (array_merge($yearImages, $yearImages) as $loopIdx => $src)
              @php $n = ($loopIdx % $count) + 1; @endphp
              <div class="a5h-mitem" {{ $loopIdx >= count($yearImages) ? 'aria-hidden=true' : '' }}>
                <button type="button" class="a5h-img-btn" data-image-preview data-image-src="{{ $src }}"
                        data-image-alt="ประกาศ 5ส {{ $year }} แผ่นที่ {{ $n }}" aria-label="ดูประกาศ 5ส {{ $year }} แผ่นที่ {{ $n }}"
                        data-loc-attr="aria-label,data-image-alt"
                        data-loc-th="ดูประกาศ 5ส {{ $year }} แผ่นที่ {{ $n }}"
                        data-loc-en="View 5S announcement {{ $year }}, image {{ $n }}"
                        data-loc-my="{{ $year }} ခုနှစ် 5S ကြေညာချက် ပုံအမှတ် {{ $n }} ကို ကြည့်ရန်"
                        tabindex="{{ $loopIdx >= count($yearImages) ? '-1' : '0' }}">
                  <img src="{{ $src }}" alt="ประกาศ 5ส {{ $year }} แผ่นที่ {{ $n }}"
                       data-loc-attr="alt"
                       data-loc-th="ประกาศ 5ส {{ $year }} แผ่นที่ {{ $n }}"
                       data-loc-en="5S announcement {{ $year }}, image {{ $n }}"
                       data-loc-my="{{ $year }} ခုနှစ် 5S ကြေညာချက် ပုံအမှတ် {{ $n }}"
                       loading="lazy" draggable="false">
                </button>
                <span class="a5h-pop"><span data-i18n="a5s.home.announce">ประกาศ</span> {{ $n }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>

  @php
    $welcomeUser = [
      'code' => $me->employee_code,
      'name_th' => $me->fullNameTh() ?: $me->full_name_en ?: $me->employee_code,
      'name_en' => $me->full_name_en ?: $me->fullNameTh() ?: $me->employee_code,
      'name_my' => $me->full_name_en ?: $me->fullNameTh() ?: $me->employee_code,
    ];
  @endphp
  <div class="a5wm" data-welcome-modal hidden aria-hidden="true">
    <div class="a5wm-card" role="dialog" aria-modal="true" aria-labelledby="a5wmHello" tabindex="-1">
      <div class="a5wm-body">
        <button type="button" class="a5wm-close" data-welcome-close data-i18n-aria="a5s.common.close" aria-label="ปิด">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <div class="a5wm-brand">
          <img src="{{ asset('assets/area5s/logo-mark.png') }}" alt="">
          <span>SUPAVUT</span>
        </div>
        <div class="a5wm-hero"><img src="{{ asset('assets/area5s/logo-5s.png').'?v=2' }}" alt="5S"></div>
        <div class="a5wm-divider"><i></i></div>
        <div class="a5wm-text">
          <h3 class="a5wm-hello" id="a5wmHello"><span data-i18n="a5s.welcome.hello">สวัสดี</span> <span data-hello-name></span></h3>
          <p class="a5wm-welcome" data-i18n="a5s.welcome.welcomeTo">ยินดีต้อนรับสู่ SUPAVUT 5S Area</p>
          <p class="a5wm-desc" data-i18n="a5s.welcome.desc">ระบบนี้ช่วยส่งเสริมความสะอาด ความเป็นระเบียบ ความปลอดภัย และสร้างนิสัยที่ดีในการทำงาน เพื่อองค์กรของเรา</p>
        </div>
      </div>
      <div class="a5wm-foot">
        <label class="a5wm-check"><input type="checkbox" data-welcome-today> <span data-i18n="a5s.welcome.dontToday">ไม่แสดงวันนี้</span></label>
        <button type="button" class="a5wm-ok" data-welcome-ok data-i18n="a5s.welcome.ack">รับทราบ</button>
      </div>
    </div>
  </div>

  <script>
    'use strict';
    (function () {
      // ---- แท็บเลือกปี: สลับ marquee ตามปี (default = ปีปัจจุบันจาก server) ----
      const tabs = Array.from(document.querySelectorAll('[data-year-tab]'));
      const panels = Array.from(document.querySelectorAll('[data-year-panel]'));
      const showYear = (year) => {
        tabs.forEach(t => {
          const on = t.dataset.yearTab === year;
          t.classList.toggle('is-active', on);
          t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panels.forEach(p => { p.hidden = p.dataset.yearPanel !== year; });
      };
      tabs.forEach(t => t.addEventListener('click', () => showYear(t.dataset.yearTab)));

      // ---- Modal ยินดีต้อนรับ (โชว์เมื่อเข้าระบบ 5ส) ----
      const modal = document.querySelector('[data-welcome-modal]');
      if (modal) {
        const nameEl = modal.querySelector('[data-hello-name]');
        const todayChk = modal.querySelector('[data-welcome-today]');
        const user = @json($welcomeUser);
        const DAY_KEY = 'a5s_welcome_off';
        const today = new Date().toISOString().slice(0, 10);

        const applyName = () => {
          const i18n = window.__portalLang;
          nameEl.textContent = (i18n && i18n.name) ? i18n.name(user, user.name_th) : (user.name_th || user.name_en || user.code || '');
        };
        applyName();
        document.addEventListener('insight:languagechange', applyName);

        const open = () => {
          modal.hidden = false; modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
          modal.querySelector('.a5wm-card')?.focus();
        };
        const close = () => {
          modal.hidden = true; modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
          // "ไม่แสดงวันนี้" = เงียบเฉพาะวันนี้ (พรุ่งนี้โชว์ใหม่)
          try { if (todayChk.checked) localStorage.setItem(DAY_KEY, today); } catch (e) {}
        };

        // เด้งเฉพาะครั้งแรกที่เข้าแอป 5ส หลังล็อกอิน (server flag) — logout→login ใหม่ = เห็นประกาศอีก
        // เว้นแต่ติ๊ก "ไม่แสดงวันนี้" ไว้แล้ว (เงียบทั้งวัน แม้ล็อกอินใหม่)
        let skip = !@json($showWelcome ?? false);
        try { if (localStorage.getItem(DAY_KEY) === today) skip = true; } catch (e) {}
        if (!skip) open();

        modal.querySelectorAll('[data-welcome-close], [data-welcome-ok]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', e => { if (e.target === modal) close(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) close(); });
      }
    })();
  </script>
@endsection
