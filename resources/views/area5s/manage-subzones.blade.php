@extends('layouts.portal')

@section('title', 'โซนย่อย 5S')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.manage.pickSubZoneTitle">เลือกโซนย่อย</span>
@endsection

@section('page-style')
    .a5sz-wrap { display:grid; gap:1rem; width:min(100%, 92rem); margin:0 auto; }
    .a5sz-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5sz-actions { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .a5sz-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .85rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:650; text-decoration:none; }
    .a5sz-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5sz-head { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-soft); padding:.95rem 1.1rem; }
    .a5sz-head h2 { margin:0 0 .25rem; font-size:1.05rem; color:var(--light-text); }
    .a5sz-head p { margin:0; color:var(--muted-light); font-size:.82rem; }
    .a5sz-kicker { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted-light); font-size:.78rem; font-weight:650; }
    .a5sz-map-panel { display:grid; gap:.75rem; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:.8rem; }
    .a5sz-map-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5sz-map-head h3 { margin:0; color:var(--light-text); font-size:.98rem; }
    .a5sz-map-head small { color:var(--muted-light); font-size:.74rem; }

    /* แถบซูม + เวที (viewport ตัดขอบ + canvas transform) */
    .a5sz-zoombar { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .a5sz-zoom-ctrls { display:inline-flex; align-items:center; gap:.15rem; padding:.15rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5sz-zoom-ctrls button { min-width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); cursor:pointer; font-size:1rem; font-weight:800; padding:0 .3rem; }
    .a5sz-zoom-ctrls button:hover { background:var(--panel-soft); color:var(--moss); }
    .a5sz-zoom-val { min-width:3.4rem; font-size:.74rem; font-weight:750; }
    .a5sz-zoom-hint { color:var(--muted-light); font-size:.72rem; }
    .a5sz-stage { position:relative; width:100%; overflow:hidden; border:1px solid var(--line-light); border-radius: 0.26rem; background:var(--menu-bg); touch-action:none; cursor:grab; user-select:none; }
    .a5sz-stage.is-pan { cursor:grabbing; }
    .a5sz-canvas { position:relative; width:100%; transform-origin:0 0; will-change:transform; }
    .a5sz-canvas img { display:block; width:100%; height:auto; pointer-events:none; -webkit-user-drag:none; }
    .a5sz-svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
    /* ---- Map แบบเกม: อาคาร = hotspot เรืองแสง หายใจเบา ๆ + hover กระพริบ neon ---- */
    .a5sz-poly { cursor:pointer; color:#5b7343; fill-opacity:.30; stroke-linejoin:round;
      transition:fill-opacity .16s ease, stroke-width .16s ease, filter .16s ease;
      filter:drop-shadow(0 0 2px color-mix(in srgb, currentColor 45%, transparent));
      animation:a5szBreath 3.4s ease-in-out infinite; }
    .a5sz-poly:hover { fill-opacity:.48; stroke-width:1.8;
      filter:drop-shadow(0 0 3px color-mix(in srgb, currentColor 38%, transparent)); animation:none; }
    @keyframes a5szBreath { 0%,100% { fill-opacity:.26; } 50% { fill-opacity:.44; } }
    @keyframes a5szBlink { 0%,100% { stroke-opacity:1; } 50% { stroke-opacity:.28; } }
    .a5sz-jslabel { pointer-events:none; font-family:"IBM Plex Sans Thai", "Noto Sans Thai", Arial, sans-serif; font-weight:700; letter-spacing:.01em; fill:#fff; paint-order:stroke; stroke:rgb(0 0 0 / 68%); stroke-width:.62px; text-anchor:middle; dominant-baseline:middle;
      filter:drop-shadow(0 0 2px rgb(0 0 0 / 55%)); }
    .a5sz-tooltip[hidden] { display:none; }
    .a5sz-tooltip { position:absolute; z-index:6; transform:translate(-50%, -112%); padding:.42rem .74rem; border-radius: 0.25rem;
      background:linear-gradient(180deg, rgb(30 36 30 / 96%), rgb(15 19 15 / 96%)); color:#fff; font-size:.78rem; font-weight:700; white-space:nowrap; pointer-events:none;
      border:1px solid color-mix(in srgb, var(--moss) 60%, transparent);
      box-shadow:0 8px 22px rgb(0 0 0 / 42%), 0 0 14px color-mix(in srgb, var(--moss) 26%, transparent); }
    .a5sz-tooltip::after { content:""; position:absolute; top:100%; left:50%; transform:translateX(-50%); border:6px solid transparent; border-top-color:color-mix(in srgb, var(--moss) 62%, rgb(18 22 18)); }
    .a5sz-tooltip small { display:block; color:rgb(205 224 205 / 85%); font-size:.67rem; font-weight:600; margin-top:.12rem; }

    .a5sz-list { display:flex; flex-wrap:wrap; gap:.45rem; }
    .a5sz-pill { position:relative; display:inline-flex; align-items:center; gap:.45rem; max-width:100%; padding:.42rem .7rem; border:1px solid color-mix(in srgb, var(--area-color) 55%, var(--line-light)); border-radius: 0.25rem; background:var(--menu-bg); color:var(--area-color); text-decoration:none; font-size:.78rem; font-weight:750;
      transition:border-color .16s ease, transform .16s ease, box-shadow .16s ease, background-color .16s ease; }
    .a5sz-pill::before { content:""; flex:none; width:.5rem; height:.5rem; border-radius:50%; background:var(--area-color); box-shadow:0 0 0 3px color-mix(in srgb, var(--area-color) 20%, transparent); transition:box-shadow .16s ease; }
    .a5sz-pill:hover { border-color:var(--area-color); transform:translateY(-2px); background:color-mix(in srgb, var(--area-color) 9%, var(--menu-bg)); box-shadow:0 8px 20px rgb(0 0 0 / 20%); }
    .a5sz-pill:hover::before { box-shadow:0 0 0 3px color-mix(in srgb, var(--area-color) 28%, transparent); }
    .a5sz-pill span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5sz-pill small { color:inherit; opacity:.7; font-size:.68rem; font-weight:700; }
    .a5sz-empty { display:grid; gap:.55rem; place-items:center; padding:2.4rem 1.5rem; text-align:center; border:1px dashed var(--line-strong); border-radius: 0.32rem; color:var(--muted-light); font-size:.84rem; }
@endsection

@section('content')
  @php
    // เตรียมข้อมูลอาคาร (พื้นที่ย่อย) ต่อภาพโซนย่อย — คำนวณใน @php ก่อน @json (เลี่ยงบั๊ก Blade @json(fn))
    $subzonesJs = [];
    foreach ($zoneMaps as $zoneMap) {
        $areasJs = [];
        foreach ($zoneMap->areas as $area) {
            $areasJs[] = [
                'name' => $area->name,
                'points' => $area->shape_points ?: [],
                'color' => $area->color ?: '#5b7343',
                'floors' => $area->floors->count(),
                'url' => route('area5s.manage.zone.area', ['zone' => $zone, 'area' => $area]),
            ];
        }
        $subzonesJs['szmap'.$zoneMap->id] = $areasJs;
    }
  @endphp

  <div class="a5sz-wrap">
    <div class="a5sz-top">
      <a class="a5s-back nav-go" href="{{ route('area5s.manage') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
      <div class="a5sz-actions">
        @if ($isA5sAdmin)
          <a class="a5sz-btn nav-go" href="{{ route('area5s.plan.index') }}" data-i18n="a5s.manage.editPlan">แก้ไขแปลนบริษัท</a>
        @endif
      </div>
    </div>

    <section class="a5sz-head">
      <span class="a5sz-kicker">{{ $zone->name }}</span>
      <h2 data-i18n="a5s.manage.subZonePageTitle">เลือกพื้นที่ย่อย/อาคาร</h2>
      <p data-i18n="a5s.manage.subZonePageHint">คลิกกรอบหรือชื่ออาคารบนภาพโซนย่อย เพื่อเข้าไปจัดการพื้นที่ในอาคารนั้น</p>
    </section>

    @forelse ($zoneMaps as $zoneMap)
      <section class="a5sz-map-panel" data-sz-panel="szmap{{ $zoneMap->id }}">
        <div class="a5sz-map-head">
          <div>
            <h3>{{ $zoneMap->name }}</h3>
            <small>{{ $zoneMap->areas->count() }} <span data-i18n="a5s.mapping.area">พื้นที่ย่อย</span></small>
          </div>
        </div>

        @if ($zoneMap->image_path)
          <div class="a5sz-zoombar">
            <span class="a5sz-zoom-ctrls">
              <button type="button" data-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">−</button>
              <button type="button" data-zoom-reset class="a5sz-zoom-val" data-zoom-val>100%</button>
              <button type="button" data-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
            </span>
            <span class="a5sz-zoom-hint" data-i18n="a5s.plan.zoomHint">ล้อเมาส์ = ซูม · ลาก = เลื่อนดู</span>
          </div>
          <div class="a5sz-stage" data-sz-stage>
            <div class="a5sz-canvas" data-sz-canvas>
              <img src="{{ asset('storage/'.$zoneMap->image_path) }}" alt="{{ $zoneMap->name }}" draggable="false">
              <svg class="a5sz-svg" viewBox="0 0 100 100" preserveAspectRatio="none" data-sz-svg></svg>
            </div>
            <div class="a5sz-tooltip" data-sz-tooltip hidden></div>
          </div>
        @else
          <div class="a5sz-empty">{{ $zoneMap->name }}</div>
        @endif

        <div class="a5sz-list">
          @forelse ($zoneMap->areas as $area)
            <a class="a5sz-pill nav-go" href="{{ route('area5s.manage.zone.area', ['zone' => $zone, 'area' => $area]) }}" style="--area-color:{{ $area->color ?: '#5b7343' }}">
              <span>{{ $area->name }}</span>
            </a>
          @empty
            <span class="a5sz-empty" data-i18n="a5s.manage.noAreaInSubZone">ยังไม่มีพื้นที่ย่อยในภาพนี้</span>
          @endforelse
        </div>
      </section>
    @empty
      <div class="a5sz-empty" data-i18n="a5s.manage.noSubZoneInZone">โซนนี้ยังไม่มีภาพโซนย่อย</div>
    @endforelse
  </div>

  <script>
    'use strict';
    (function () {
      const data = @json($subzonesJs);
      const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
      const NS = 'http://www.w3.org/2000/svg';
      const i18n = window.__portalLang || {};
      const t = (k, f) => i18n.text ? i18n.text(k, f) : f;
      const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
      const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
      const centroid = pts => {
        const points = pts || [];
        const average = () => {
          const n = points.length || 1;
          return { x: points.reduce((s, p) => s + Number(p.x || 0), 0) / n, y: points.reduce((s, p) => s + Number(p.y || 0), 0) / n };
        };
        if (points.length < 3) return average();
        let area2 = 0, cx = 0, cy = 0;
        points.forEach((p, i) => {
          const next = points[(i + 1) % points.length];
          const cross = Number(p.x || 0) * Number(next.y || 0) - Number(next.x || 0) * Number(p.y || 0);
          area2 += cross;
          cx += (Number(p.x || 0) + Number(next.x || 0)) * cross;
          cy += (Number(p.y || 0) + Number(next.y || 0)) * cross;
        });
        return Math.abs(area2) < .0001 ? average() : { x: cx / (3 * area2), y: cy / (3 * area2) };
      };

      document.querySelectorAll('[data-sz-panel]').forEach(panel => {
        const stage = panel.querySelector('[data-sz-stage]');
        if (!stage) return; // ภาพว่าง = ข้าม
        initStage(panel, stage, data[panel.dataset.szPanel] || []);
      });

      function initStage(panel, stage, areas) {
        const canvas = stage.querySelector('[data-sz-canvas]');
        const svg = stage.querySelector('[data-sz-svg]');
        const tooltip = stage.querySelector('[data-sz-tooltip]');
        const valBtn = panel.querySelector('[data-zoom-val]');
        let zoom = 1, panX = 0, panY = 0;
        const MAXZ = 10;
        const applyT = () => { canvas.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`; };
        const clampPan = () => { const r = stage.getBoundingClientRect(); panX = clamp(panX, r.width * (1 - zoom), 0); panY = clamp(panY, r.height * (1 - zoom), 0); };
        function setZoom(nz, sx, sy) {
          const r = stage.getBoundingClientRect();
          if (sx == null) { sx = r.width / 2; sy = r.height / 2; }
          nz = clamp(nz, 1, MAXZ);
          panX = sx - (sx - panX) * (nz / zoom);
          panY = sy - (sy - panY) * (nz / zoom);
          zoom = nz; clampPan(); applyT();
          if (valBtn) valBtn.textContent = Math.round(zoom * 100) + '%';
          renderLabels();
        }
        panel.querySelector('[data-zoom-in]')?.addEventListener('click', () => setZoom(zoom * 1.35));
        panel.querySelector('[data-zoom-out]')?.addEventListener('click', () => setZoom(zoom / 1.35));
        panel.querySelector('[data-zoom-reset]')?.addEventListener('click', () => { zoom = 1; panX = 0; panY = 0; applyT(); if (valBtn) valBtn.textContent = '100%'; renderLabels(); });
        stage.addEventListener('wheel', e => { e.preventDefault(); const r = stage.getBoundingClientRect(); setZoom(zoom * (e.deltaY < 0 ? 1.2 : 1 / 1.2), e.clientX - r.left, e.clientY - r.top); }, { passive: false });

        const labelNodes = [];
        areas.forEach((a, i) => {
          const poly = document.createElementNS(NS, 'polygon');
          poly.setAttribute('points', (a.points || []).map(p => `${p.x},${p.y}`).join(' '));
          poly.setAttribute('fill', a.color || '#5b7343');
          poly.style.color = a.color || '#5b7343'; // glow (currentColor) = สีอาคาร
          poly.setAttribute('stroke', a.color || '#5b7343');
          poly.setAttribute('stroke-width', 1);
          poly.setAttribute('vector-effect', 'non-scaling-stroke');
          poly.setAttribute('class', 'a5sz-poly');
          poly.dataset.idx = i;
          svg.appendChild(poly);
          const c = centroid(a.points);
          const label = document.createElementNS(NS, 'text');
          label.setAttribute('x', c.x); label.setAttribute('y', c.y);
          label.setAttribute('class', 'a5sz-jslabel');
          label.textContent = a.name;
          svg.appendChild(label);
          labelNodes.push(label);
        });
        const renderLabels = () => { const size = clamp(2.4 / zoom, 0.4, 2.4); labelNodes.forEach(l => l.setAttribute('font-size', size)); };
        renderLabels();

        const pointers = new Map();
        let ptr = null, pinch = null;
        stage.addEventListener('pointerdown', e => {
          pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
          if (pointers.size === 2) { const p = [...pointers.values()]; pinch = { d: dist(p[0], p[1]) }; ptr = null; return; }
          if (pointers.size > 2) return;
          ptr = { sx: e.clientX, sy: e.clientY, px: panX, py: panY, moved: false, target: e.target };
          try { stage.setPointerCapture(e.pointerId); } catch (err) {}
        });
        stage.addEventListener('pointermove', e => {
          if (pointers.has(e.pointerId)) pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
          if (pinch) {
            const p = [...pointers.values()]; if (p.length < 2) return;
            const nd = dist(p[0], p[1]); const r = stage.getBoundingClientRect();
            const mx = (p[0].x + p[1].x) / 2 - r.left, my = (p[0].y + p[1].y) / 2 - r.top;
            if (pinch.d > 0) setZoom(zoom * (nd / pinch.d), mx, my);
            pinch.d = nd; return;
          }
          if (ptr) {
            const dx = e.clientX - ptr.sx, dy = e.clientY - ptr.sy;
            if (!ptr.moved && Math.abs(dx) + Math.abs(dy) > 4) { ptr.moved = true; stage.classList.add('is-pan'); tooltip.hidden = true; }
            if (ptr.moved) { panX = ptr.px + dx; panY = ptr.py + dy; clampPan(); applyT(); return; }
          }
          const poly = e.target.closest('[data-idx]');
          if (!poly) { tooltip.hidden = true; return; }
          const a = areas[Number(poly.dataset.idx)];
          if (!a) { tooltip.hidden = true; return; }
          const r = stage.getBoundingClientRect();
          tooltip.style.left = (e.clientX - r.left) + 'px';
          tooltip.style.top = (e.clientY - r.top) + 'px';
          tooltip.innerHTML = esc(a.name);
          tooltip.hidden = false;
        });
        function endPointer(e) {
          pointers.delete(e.pointerId);
          try { stage.releasePointerCapture(e.pointerId); } catch (err) {}
          stage.classList.remove('is-pan');
          if (pinch && pointers.size < 2) pinch = null;
          if (!ptr) return;
          const cur = ptr; ptr = null;
          if (cur.moved) return;
          const poly = cur.target.closest('[data-idx]');
          if (poly) { const a = areas[Number(poly.dataset.idx)]; if (a && a.url) window.location.href = a.url; }
        }
        stage.addEventListener('pointerup', endPointer);
        stage.addEventListener('pointercancel', endPointer);
        stage.addEventListener('pointerleave', () => { tooltip.hidden = true; });
        applyT();
      }
    })();
  </script>
@endsection
