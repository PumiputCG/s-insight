@extends('layouts.portal')

@section('title', $layout->name.' — SUPAVUT 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title">{{ $layout->name }}</span>
@endsection

@section('page-style')
    /* ---- ดูพื้นที่: ซ้าย = แผนผัง · ขวา = รายละเอียดของจุดที่เลือก (Manager สั่ง 2026-08-27) ---- */
    .a5v-shell {
      --a5v-pass:#4dbe86;
      --a5v-fail:#e8635a;
      --a5v-pending:#c8964a;
      --a5v-none:#9da39b;
      --a5v-surface: var(--panel-tint);
      --a5v-surface-strong: var(--panel-tint-strong);
      display:grid; gap:.75rem; width:min(100%, 92rem); margin:0 auto;
    }
    html[data-theme="light"] .a5v-shell {
      --a5v-pass:#137a4a;
      --a5v-fail:#c62828;
      --a5v-pending:#885b18;
      --a5v-none:#5d625c;
    }

    /* กรอบบน: เลือกช่วงเวลา + ชื่อพื้นที่ */
    .a5v-context { overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--a5v-surface); }
    .a5v-selects { display:flex; align-items:end; gap:.55rem; flex-wrap:wrap; padding:.8rem 1rem; }
    .a5v-field { display:grid; gap:.3rem; min-width:11rem; }
    .a5v-field > span { color:var(--muted-light); font-size:.7rem; }
    .a5v-field select {
      width:100%; min-height:2.6rem; padding:.45rem 2.2rem .45rem .75rem;
      border:1px solid var(--line-light); border-radius: 0.25rem;
      background:var(--menu-bg); color:var(--light-text);
      font:inherit; font-size:.82rem; font-weight:700; cursor:pointer;
    }
    .a5v-field select:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }
    .a5v-head { display:grid; gap:.4rem; border-top:1px solid var(--line-light); padding:.75rem 1rem; }

    /* 2 คอลัมน์ — จอแคบวางซ้อนกัน */
    .a5v-split { display:grid; grid-template-columns:minmax(0, 1.6fr) minmax(20rem, .95fr); gap:.75rem; align-items:start; }
    @media (max-width: 1080px) { .a5v-split { grid-template-columns:1fr; } }

    .a5v-panel { overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--a5v-surface); }
    .a5v-panel-head { display:flex; align-items:center; justify-content:space-between; gap:.7rem; padding:.65rem 1rem; border-bottom:1px solid var(--line-light); }
    .a5v-panel-head h3 { margin:0; color:var(--light-text); font-size:.88rem; font-weight:800; }

    /* ---- ซ้าย: แผนผัง ---- */
    .a5v-zoom { display:inline-flex; align-items:center; gap:.15rem; padding:.15rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5v-zoom button { min-width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); font:inherit; font-size:1rem; font-weight:800; cursor:pointer; }
    .a5v-zoom button:hover:not(:disabled) { background:var(--hover-soft); color:var(--moss); }
    .a5v-zoom button:disabled { opacity:.4; cursor:not-allowed; }
    .a5v-zoom .a5v-zoom-value { min-width:3.4rem; font-size:.74rem; font-weight:750; }
    /* ภาพอยู่กลางกรอบ และลากเลื่อนได้ (safe center กันภาพโดนตัดตอนซูมใหญ่) */
    .a5v-stage { position:relative; overflow:auto; height:min(64vh, 40rem); background:var(--menu-bg); display:flex; align-items:safe center; justify-content:safe center; cursor:grab; touch-action:none; }
    .a5v-stage.is-pan { cursor:grabbing; }
    .a5v-canvas { position:relative; flex:none; width:50%; transform-origin:0 0; }
    .a5v-stage.is-pan .a5v-canvas img, .a5v-stage.is-pan .a5v-marker { pointer-events:none; }
    .a5v-canvas img { display:block; width:100%; height:auto; -webkit-user-drag:none; user-select:none; }

    /* หมุดจุด — เลือกแล้วเด้งขึ้นและมีวงแหวนกระเพื่อม */
    .a5v-marker {
      position:absolute; transform:translate(-50%, -50%);
      width:2rem; height:2rem; display:grid; place-items:center;
      border:2px solid #fff; border-radius:50%;
      background:var(--a5v-none); color:#fff;
      font:inherit; font-size:.78rem; font-weight:800; cursor:pointer;
      box-shadow:0 2px 8px rgb(0 0 0 / 32%);
      transition:transform .18s cubic-bezier(.2,1.3,.35,1), box-shadow .18s ease, background-color .18s ease;
    }
    .a5v-marker.is-pass { background:var(--a5v-pass); }
    .a5v-marker.is-fail { background:var(--a5v-fail); }
    .a5v-marker.is-pending { background:var(--a5v-pending); }
    .a5v-marker:hover { transform:translate(-50%, -50%) scale(1.12); }
    .a5v-marker.is-open { transform:translate(-50%, -50%) scale(1.3); box-shadow:0 4px 14px rgb(0 0 0 / 42%); z-index:3; }
    .a5v-marker.is-open::after {
      content:""; position:absolute; inset:-.45rem; border-radius:50%;
      border:2px solid currentColor; color:#fff; opacity:.75;
      animation:a5vPulse 1.4s ease-out infinite;
    }
    @keyframes a5vPulse { 0% { transform:scale(.85); opacity:.75; } 100% { transform:scale(1.35); opacity:0; } }

    /* แถบเลือกจุด A B C ใต้แผนผัง */
    .a5v-chips { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; border-top:1px solid var(--line-light); padding:.7rem 1rem; }
    .a5v-chip {
      min-width:2.3rem; height:2.3rem; display:inline-flex; align-items:center; justify-content:center;
      border:1px solid var(--line-light); border-radius: 0.28rem;
      background:var(--menu-bg); color:var(--muted-light);
      font:inherit; font-size:.82rem; font-weight:800; cursor:pointer;
      transition:transform .16s cubic-bezier(.2,1.3,.35,1), border-color .16s ease, background-color .16s ease, color .16s ease;
    }
    .a5v-chip:hover { transform:translateY(-2px); border-color:var(--moss); color:var(--moss); }
    .a5v-chip[aria-pressed="true"] { border-color:var(--moss); background:var(--moss); color:#fff; }
    .a5v-chip-dot { width:.4rem; height:.4rem; margin-left:.32rem; border-radius:50%; background:var(--dot, var(--a5v-none)); }
    .a5v-chip.is-pass { --dot:var(--a5v-pass); }
    .a5v-chip.is-fail { --dot:var(--a5v-fail); }
    .a5v-chip.is-pending { --dot:var(--a5v-pending); }
    .a5v-chip[aria-pressed="true"] .a5v-chip-dot { background:#fff; }

    /* ---- ขวา: รายละเอียดของจุดที่เลือก ---- */
    .a5v-detail { display:grid; gap:.7rem; padding:.85rem 1rem 1rem; animation:a5vFade .26s ease both; }
    @keyframes a5vFade { from { opacity:0; transform:translateY(.45rem); } to { opacity:1; transform:none; } }

    /* การ์ดขาวใบเดียว แถวละ "หัวข้อ: ค่า" บรรทัดเดียวกัน (Manager 2026-08-27) */
    .a5v-facts { display:grid; gap:.5rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); padding:.8rem .9rem; }
    .a5v-facts.st-pass { --bucket-color:var(--a5v-pass); }
    .a5v-facts.st-fail { --bucket-color:var(--a5v-fail); }
    .a5v-facts.st-pending { --bucket-color:var(--a5v-pending); }
    .a5v-facts.st-none { --bucket-color:var(--a5v-none); }
    .a5v-fact { display:grid; grid-template-columns:6.2rem minmax(0, 1fr); align-items:baseline; gap:.5rem; }
    .a5v-fact > span { color:var(--muted-light); font-size:.8rem; font-weight:700; }
    .a5v-fact > span::after { content:":"; }
    .a5v-fact > strong { min-width:0; color:var(--light-text); font-size:.88rem; font-weight:700; line-height:1.55; overflow-wrap:anywhere; font-variant-numeric:tabular-nums; }
    .a5v-fact > strong.is-status { color:var(--bucket-color, var(--light-text)); font-weight:800; }
    @media (max-width: 420px) { .a5v-fact { grid-template-columns:1fr; gap:.1rem; } }

    .a5v-sec-label { color:var(--muted-light); font-size:.76rem; font-weight:750; }
    .a5v-people { display:grid; gap:.45rem; }
    .a5v-person { display:flex; align-items:center; gap:.55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); padding:.5rem .6rem; }
    .a5v-avatar { width:2.1rem; height:2.1rem; flex:none; display:grid; place-items:center; overflow:hidden; border:0; border-radius:50%; padding:0; background:var(--a5v-surface); color:var(--muted-light); font:inherit; }
    button.a5v-avatar { cursor:zoom-in; }
    button.a5v-avatar:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }
    .a5v-avatar img { display:block; width:100%; height:100%; object-fit:cover; }
    .a5v-avatar svg { width:1.15rem; height:1.15rem; fill:currentColor; }
    .a5v-person-copy { display:grid; gap:.06rem; min-width:0; }
    .a5v-person-copy strong { overflow:hidden; color:var(--light-text); font-size:.88rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
    .a5v-person-copy small { color:var(--muted-light); font-size:.78rem; }

    .a5v-cards { display:grid; gap:.45rem; }
    .a5v-card { display:grid; gap:.2rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); padding:.55rem .65rem; }
    .a5v-card b { color:var(--light-text); font-size:.92rem; font-weight:750; }
    .a5v-card p { margin:0; color:var(--light-text); font-size:.86rem; line-height:1.7; white-space:pre-line; }
    .a5v-card small { color:var(--muted-light); font-size:.76rem; }
    .a5v-card-images { display:flex; gap:.35rem; flex-wrap:wrap; }
    .a5v-card-images button { width:3.4rem; height:3.4rem; overflow:hidden; border:1px solid var(--line-light); border-radius: 0.22rem; background:var(--a5v-surface); padding:0; cursor:zoom-in; }
    .a5v-card-images img { display:block; width:100%; height:100%; object-fit:cover; }
    .a5v-card-file { display:grid; place-items:center; width:100%; height:100%; color:var(--muted-light); font-size:.62rem; font-weight:800; }
    .a5v-history-btn { flex:none; display:inline-flex; align-items:center; gap:.42rem; padding:.4rem .75rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); font:inherit; font-size:.76rem; font-weight:700; cursor:pointer; transition:border-color .16s ease, color .16s ease; }
    .a5v-history-btn:hover, .a5v-history-btn:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    .a5v-history-btn[hidden] { display:none !important; }
    .a5v-history-btn svg { width:1rem; height:1rem; flex:none; }
    .a5v-none-text { color:var(--muted-light); font-size:.78rem; }
@endsection

@section('content')
  <div class="a5v-shell" data-a5v>
    <a class="a5s-back nav-go" href="{{ $backUrl ?? route('area5s.index') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>

    {{-- เลือกเดือน/วันที่ตรวจ — เปลี่ยนแล้วไป layout ใบของรอบนั้น (Manager 2026-08-27) --}}
    @php
      $navMonths = $roundNav['months'] ?? [];
      $navMonth = collect($navMonths)->firstWhere('is_selected', true);
      $navInspections = $navMonth['inspections'] ?? [];
    @endphp
    <section class="a5v-context">
      @if (count($navMonths))
        <div class="a5v-selects">
          <label class="a5v-field">
            <span data-i18n="a5s.workspace.month">เดือน</span>
            <select data-v-month>
              @foreach ($navMonths as $month)
                <option value="{{ $month['inspections'][0]['url'] ?? '' }}" @selected($month['is_selected'])>{{ $month['label'] }}</option>
              @endforeach
            </select>
          </label>
          <label class="a5v-field">
            <span data-i18n="a5s.workspace.inspectionDate">วันที่ตรวจ</span>
            <select data-v-round>
              @foreach ($navInspections as $inspection)
                <option value="{{ $inspection['url'] }}" @selected($inspection['is_selected'])>{{ $inspection['date_label'] ?: 'ครั้งที่ '.$inspection['seq'] }}</option>
              @endforeach
            </select>
          </label>
        </div>
      @endif
      {{-- หัวข้อ: ค่า บรรทัดเดียว ชุดเดียวกับการ์ดรายละเอียดจุด (Manager 2026-08-27) --}}
      <div class="a5v-head">
        <div class="a5v-fact">
          <span data-i18n="a5s.common.roomName">ชื่อห้อง</span><strong>{{ $layout->name }}</strong>
        </div>
        @if ($layout->description)
          <div class="a5v-fact">
            <span data-i18n="a5s.common.detail">รายละเอียด</span><strong>{{ $layout->description }}</strong>
          </div>
        @endif
      </div>
    </section>

    <div class="a5v-split">
      {{-- ซ้าย: แผนผัง + หมุดจุด --}}
      <section class="a5v-panel">
        <div class="a5v-panel-head">
          <h3 data-i18n="a5s.common.layout">แผนผังพื้นที่</h3>
          <span class="a5v-zoom" aria-label="เครื่องมือซูม" data-i18n-aria="a5s.common.zoomTools">
            <button type="button" data-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">−</button>
            <button type="button" class="a5v-zoom-value" data-zoom-reset aria-label="รีเซ็ตซูม" data-i18n-aria="a5s.common.resetZoom">50%</button>
            <button type="button" data-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
          </span>
        </div>
        <div class="a5v-stage" data-stage>
          <div class="a5v-canvas" data-canvas>
            <img src="{{ asset('storage/'.$layout->image_path) }}" alt="{{ $layout->name }}" draggable="false">
          </div>
        </div>
        <div class="a5v-chips" data-chips role="group" aria-label="เลือกจุด" data-i18n-aria="a5s.common.selectPoint"></div>
      </section>

      {{-- ขวา: รายละเอียดของจุดที่เลือก --}}
      <aside class="a5v-panel">
        <div class="a5v-panel-head">
          <h3 data-i18n="a5s.common.pointDetail">รายละเอียดจุด</h3>
          {{-- ประวัติการส่ง: โมดัลชุดเดียวกับหน้าตรวจประเมิน (Manager 2026-08-27) --}}
          <button type="button" class="a5v-history-btn" data-v-history hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/></svg>
            <span data-i18n="a5s.common.historySubmit">ประวัติการส่ง</span>
          </button>
        </div>
        <div class="a5v-detail" data-detail></div>
      </aside>
    </div>
  </div>

  @include('area5s.partials.history-modal', ['historyDetails' => $historyDetails ?? []])

  <script>
    'use strict';

    (function a5vLayoutViewer() {
      const points = @json($points);
      const myCode = @json($myCode);
      const selectedId = @json($selectedPointId);
      const shell = document.querySelector('[data-a5v]');
      const stage = document.querySelector('[data-stage]');
      const canvas = document.querySelector('[data-canvas]');
      const chipBar = document.querySelector('[data-chips]');
      const detail = document.querySelector('[data-detail]');
      const historyBtn = document.querySelector('[data-v-history]');
      if (!shell || !stage || !canvas) return;

      const i18n = window.__portalLang || {};
      const t = (key, fallback) => (i18n.text ? i18n.text(key, fallback) : fallback);
      const personName = (person) => (i18n.name ? i18n.name(person, person?.name || person?.code || '-') : (person?.name || person?.code || '-'));
      const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
      const statusClass = (p) => String(p.status_class || 'is-none').replace('is-', '');

      /* เลือกเดือน/วันที่ตรวจแล้วไป layout ของรอบนั้นทันที */
      shell.querySelectorAll('[data-v-month], [data-v-round]').forEach((select) => {
        select.addEventListener('change', () => { if (select.value) window.location.href = select.value; });
      });

      /* ---- ซูม/เลื่อนแผนผัง (คงพฤติกรรมเดิม เริ่มที่ 50%) ---- */
      const zoomIn = document.querySelector('[data-zoom-in]');
      const zoomOut = document.querySelector('[data-zoom-out]');
      const zoomReset = document.querySelector('[data-zoom-reset]');
      const defaultZoom = .5;
      const minZoom = .05;
      const maxZoom = 3;
      let zoom = defaultZoom;
      const stepDown = (z) => (z > .5 ? z - .25 : z - .1);
      const stepUp = (z) => (z >= .5 ? z + .25 : z + .1);

      function setZoom(nextZoom, anchor) {
        const previous = zoom;
        const next = Math.min(maxZoom, Math.max(minZoom, Math.round(nextZoom * 100) / 100));
        const ax = anchor ? anchor.x : stage.clientWidth / 2;
        const ay = anchor ? anchor.y : stage.clientHeight / 2;
        const pointX = stage.scrollLeft + ax;
        const pointY = stage.scrollTop + ay;
        zoom = next;
        canvas.style.width = `${zoom * 100}%`;
        if (next !== previous) {
          stage.scrollLeft = pointX * (zoom / previous) - ax;
          stage.scrollTop = pointY * (zoom / previous) - ay;
        }
        zoomReset.textContent = `${Math.round(zoom * 100)}%`;
        zoomOut.disabled = zoom <= minZoom;
        zoomIn.disabled = zoom >= maxZoom;
      }

      zoomOut.addEventListener('click', () => setZoom(stepDown(zoom)));
      zoomIn.addEventListener('click', () => setZoom(stepUp(zoom)));
      zoomReset.addEventListener('click', () => setZoom(defaultZoom));
      /* ลากเพื่อเลื่อนดูภาพ — ขยับเกิน 4px ถึงจะนับเป็นลาก ไม่งั้นถือเป็นคลิกหมุด */
      let panning = null;
      stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        panning = { x: event.clientX, y: event.clientY, left: stage.scrollLeft, top: stage.scrollTop, moved: false };
      });
      stage.addEventListener('pointermove', (event) => {
        if (!panning) return;
        const dx = event.clientX - panning.x;
        const dy = event.clientY - panning.y;
        if (!panning.moved && Math.hypot(dx, dy) < 4) return;
        if (!panning.moved) {
          panning.moved = true;
          stage.classList.add('is-pan');
          stage.setPointerCapture?.(event.pointerId);
        }
        stage.scrollLeft = panning.left - dx;
        stage.scrollTop = panning.top - dy;
      });
      const endPan = (event) => {
        if (!panning) return;
        if (panning.moved) stage.releasePointerCapture?.(event.pointerId);
        panning = null;
        stage.classList.remove('is-pan');
      };
      stage.addEventListener('pointerup', endPan);
      stage.addEventListener('pointercancel', endPan);
      stage.addEventListener('pointerleave', endPan);

      stage.addEventListener('wheel', (event) => {
        event.preventDefault();
        const rect = stage.getBoundingClientRect();
        setZoom(zoom * (event.deltaY < 0 ? 1.15 : 1 / 1.15), { x: event.clientX - rect.left, y: event.clientY - rect.top });
      }, { passive: false });
      setZoom(defaultZoom);

      /* ---- หมุดบนแผนผัง + ชิปเลือกจุด ---- */
      const markers = new Map();
      const chips = new Map();

      points.forEach((point) => {
        const marker = document.createElement('button');
        marker.type = 'button';
        marker.className = `a5v-marker is-${statusClass(point)}`;
        marker.style.left = `${point.x}%`;
        marker.style.top = `${point.y}%`;
        marker.textContent = point.code;
        marker.setAttribute('aria-label', `${t('a5s.common.point', 'จุด')} ${point.code} ${point.name}`);
        marker.addEventListener('click', () => select(point.id));
        canvas.appendChild(marker);
        markers.set(point.id, marker);

        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = `a5v-chip is-${statusClass(point)}`;
        chip.setAttribute('aria-pressed', 'false');
        chip.innerHTML = `${esc(point.code)}<span class="a5v-chip-dot" aria-hidden="true"></span>`;
        chip.addEventListener('click', () => select(point.id));
        chipBar.appendChild(chip);
        chips.set(point.id, chip);
      });

      /* ---- แผงขวา ---- */
      function personHtml(person) {
        const avatar = person.avatar
          ? `<button type="button" class="a5v-avatar" data-image-preview data-image-src="${esc(person.avatar)}" data-image-alt="${esc(personName(person))}" aria-label="${esc(t('a5s.common.viewPhoto', 'ดูรูป'))} ${esc(personName(person))}"><img src="${esc(person.avatar)}" alt=""></button>`
          : `<span class="a5v-avatar" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 26c6.08 0 11-4.92 11-11S30.08 4 24 4 13 8.92 13 15s4.92 11 11 11Zm0 4c-9.39 0-17 5.15-17 11.5A2.5 2.5 0 0 0 9.5 44h29a2.5 2.5 0 0 0 2.5-2.5C41 35.15 33.39 30 24 30Z"/></svg></span>`;
        return `<div class="a5v-person">${avatar}<span class="a5v-person-copy"><strong>${esc(personName(person))}</strong><small>${esc(person.code || '')}</small></span></div>`;
      }

      function cardsHtml(people) {
        const cards = people.flatMap((person) => person.cards || []);
        if (!cards.length) return `<p class="a5v-none-text">${esc(t('a5s.common.noDetail', 'ยังไม่มีรายละเอียดที่บันทึก'))}</p>`;

        return `<div class="a5v-cards">${cards.map((card) => {
          const images = (card.images || []).map((src, index) => {
            const ext = String(src || '').split('?')[0].split('.').pop().toUpperCase();
            const inner = ['HEIC', 'HEIF'].includes(ext)
              ? `<span class="a5v-card-file">${esc(ext)}</span>`
              : `<img src="${esc(src)}" alt="">`;
            return `<button type="button" data-image-preview data-image-src="${esc(src)}" data-image-alt="${esc(card.title)} ${index + 1}" aria-label="${esc(t('a5s.common.viewPhoto', 'ดูรูป'))}">${inner}</button>`;
          }).join('');
          return `<div class="a5v-card">
            <b>${esc(card.title || '')}</b>
            ${card.detail ? `<p>${esc(card.detail)}</p>` : ''}
            ${card.created_at ? `<small>${esc(card.created_at)}</small>` : ''}
            ${images ? `<span class="a5v-card-images">${images}</span>` : ''}
          </div>`;
        }).join('')}</div>`;
      }

      function render(point) {
        const people = point.assignees || [];
        const evaluated = (point.history || []).length ? (point.history[point.history.length - 1].date || '-') : '-';
        const statusText = t(point.status_key || 'a5s.status.no_data', point.status_label || 'ยังไม่มีข้อมูล');

        detail.innerHTML = `
          <div class="a5v-facts st-${statusClass(point)}">
            <div class="a5v-fact">
              <span>${esc(t('a5s.common.point', 'จุด'))}</span><strong>${esc(point.code)}</strong>
            </div>
            <div class="a5v-fact">
              <span>${esc(t('a5s.common.pointName', 'ชื่อจุด'))}</span><strong>${esc(point.name || '—')}</strong>
            </div>
            ${point.description ? `<div class="a5v-fact"><span>${esc(t('a5s.common.detail', 'รายละเอียด'))}</span><strong>${esc(point.description)}</strong></div>` : ''}
            <div class="a5v-fact">
              <span>${esc(t('a5s.common.status', 'สถานะ'))}</span><strong class="is-status">${esc(statusText)}</strong>
            </div>
            <div class="a5v-fact">
              <span>${esc(t('a5s.score.short', 'คะแนน'))}</span><strong>${esc(point.score_label || '—')}</strong>
            </div>
            <div class="a5v-fact">
              <span>${esc(t('a5s.common.evaluatedDate', 'วันประเมิน'))}</span><strong>${esc(evaluated)}</strong>
            </div>
            <div class="a5v-fact">
              <span>${esc(t('a5s.review.times', 'ครั้ง'))}</span><strong>${esc(String((point.history || []).length))}</strong>
            </div>
          </div>
          <span class="a5v-sec-label">${esc(t('a5s.common.assignees', 'ผู้รับผิดชอบ'))}</span>
          ${people.length ? `<div class="a5v-people">${people.map(personHtml).join('')}</div>` : `<p class="a5v-none-text">—</p>`}
          <span class="a5v-sec-label">${esc(t('a5s.review.submittedContent', 'ข้อมูลที่ส่งมา'))}</span>
          ${cardsHtml(people)}
        `;
        // เล่นอนิเมชันซ้ำทุกครั้งที่เปลี่ยนจุด
        detail.style.animation = 'none';
        void detail.offsetWidth;
        detail.style.animation = '';

        // ปุ่ม "ประวัติการส่ง" ของจุดนี้ (ใช้ modal กลางจาก partials/history-modal)
        if (historyBtn) {
          const modal = window.A5sHistoryModal;
          const has = modal ? modal.has(point.id) : (point.history || []).length > 0;
          historyBtn.hidden = !has;
          historyBtn.onclick = () => window.A5sHistoryModal?.open(
            point.id,
            `${t('a5s.common.point', 'จุด')} ${point.code}`,
            @json($layout->name)
          );
        }
      }

      function select(pointId) {
        const point = points.find((item) => item.id === pointId);
        if (!point) return;
        markers.forEach((node, id) => node.classList.toggle('is-open', id === pointId));
        chips.forEach((node, id) => node.setAttribute('aria-pressed', String(id === pointId)));
        render(point);
        markers.get(pointId)?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
      }

      const first = points.find((point) => point.id === selectedId)
        || points.find((point) => (point.assignees || []).some((person) => person.code === myCode))
        || points[0];
      if (first) {
        select(first.id);
      } else {
        detail.innerHTML = `<p class="a5v-none-text">${esc(t('a5s.common.noPoint', 'ยังไม่มีจุดในพื้นที่นี้'))}</p>`;
      }
    })();
  </script>
@endsection
