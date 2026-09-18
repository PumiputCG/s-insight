@extends('layouts.portal')

@section('title', 'SUPAVUT 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.common.overview">ภาพรวม</span>
@endsection

@section('page-style')
    /* ---- ภาพรวมแนวบอร์ดประกาศข่าว: การ์ดภาพ + หัวข้อ (4/2/1 ต่อแถว) ---- */
    .a5n-wrap { display:grid; gap:.55rem; width:min(100%, 84rem); margin:0 auto; }

    @include('area5s.partials.plan-reveal-style')

    .a5n-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; min-height:2.35rem; }
    .a5n-head h2 { margin:0; font-size:1.15rem; font-weight:700; color:var(--light-text); white-space:nowrap; }
    .a5n-head-actions { display:flex; align-items:center; justify-content:flex-end; gap:.65rem; min-width:0; margin-left:auto; }
    .a5n-round { margin:0; color:var(--muted-light); font-size:.83rem; line-height:1.4; white-space:nowrap; }
    .a5n-round strong { color:var(--moss); font-weight:750; }
    .a5n-round .is-past { color:#c8964a; font-weight:700; }
    .a5n-toolbar { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; }
    .a5n-search { position:relative; flex:1; min-width:14rem; max-width:24rem; }
    .a5n-search svg { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); color:var(--muted-light); pointer-events:none; }
    .a5n-search input { width:100%; padding:.55rem .8rem .55rem 2.1rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; }
    .a5n-search input:focus { outline:none; border-color:var(--moss); }
    .a5n-count { margin-left:auto; color:var(--muted-light); font-size:.78rem; }

    .a5n-plan { display:grid; gap:.85rem; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:.85rem; }
    .a5n-plan-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .a5n-plan-head h3 { margin:0 0 .18rem; color:var(--light-text); font-size:1rem; font-weight:800; }
    .a5n-plan-head p { margin:0; color:var(--muted-light); font-size:.78rem; line-height:1.45; }
    .a5n-plan-total { display:inline-flex; align-items:center; gap:.35rem; padding:.26rem .58rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); color:var(--muted-light); font-size:.72rem; font-weight:800; }
    .a5n-plan-stage { position:relative; overflow:hidden; border:1px solid var(--line-light); border-radius: 0.26rem; background:var(--menu-bg); }
    .a5n-plan-stage img { display:block; width:100%; height:auto; }
    .a5n-plan-svg, .a5n-submap-svg { position:absolute; inset:0; width:100%; height:100%; pointer-events:none; }
    .a5n-zone-poly, .a5n-submap-poly { vector-effect:non-scaling-stroke; }
    .a5n-zone-label { font-size:2.65px; font-weight:850; fill:#fff; paint-order:stroke; stroke:rgb(0 0 0 / 55%); stroke-width:.55px; text-anchor:middle; dominant-baseline:middle; pointer-events:none; }
    .a5n-submap-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(18rem, 1fr)); gap:.75rem; }
    .a5n-submap-card { display:grid; gap:.6rem; border:1px solid var(--line-light); border-radius: 0.3rem; background:var(--menu-bg); padding:.65rem; }
    .a5n-submap-meta { display:flex; align-items:flex-start; justify-content:space-between; gap:.65rem; min-width:0; }
    .a5n-submap-meta strong { display:block; color:var(--light-text); font-size:.88rem; line-height:1.35; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5n-submap-meta small { color:var(--muted-light); font-size:.7rem; line-height:1.35; }
    .a5n-submap-stage { position:relative; aspect-ratio:16 / 9; overflow:hidden; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); }
    .a5n-submap-stage img { display:block; width:100%; height:100%; object-fit:contain; }
    .a5n-submap-label { font-size:3px; font-weight:850; fill:#fff; paint-order:stroke; stroke:rgb(0 0 0 / 65%); stroke-width:.65px; text-anchor:middle; dominant-baseline:middle; pointer-events:none; }
    .a5n-submap-pills { display:flex; gap:.35rem; flex-wrap:wrap; }
    .a5n-submap-pill { display:inline-flex; align-items:center; gap:.32rem; max-width:100%; padding:.2rem .48rem; border:1px solid var(--line-light); border-radius:999px; color:var(--muted-light); background:var(--panel-soft); font-size:.68rem; font-weight:750; }
    .a5n-submap-pill b { max-width:11rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--light-text); font-size:inherit; }

    /* จัดกลุ่มตามโครงสร้างแปลนบริษัทจริง: โซน/อาคาร -> ชั้น (Manager 2026-07-19, เดิมเดาจากชื่อด้วย regex) */
    .a5n-floors { display:grid; gap:2.2rem; }
    .a5n-zone { display:grid; gap:1.1rem; }
    .a5n-zone-title { margin:0; font-size:1.15rem; font-weight:800; color:var(--light-text); padding-bottom:.4rem; border-bottom:2px solid var(--moss); }
    .a5n-area { display:grid; gap:.85rem; padding-top:.15rem; scroll-margin-top:5.5rem; }
    .a5n-area[hidden] { display:none; }
    .a5n-area-title { margin:0; display:flex; align-items:flex-end; justify-content:space-between; gap:.75rem; flex-wrap:wrap; color:var(--light-text); font-size:.92rem; font-weight:800; }
    .a5n-area-title span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5n-area-title small { color:var(--muted-light); font-size:.72rem; font-weight:700; }
    .a5n-floor { display:grid; gap:.75rem; }
    .a5n-floor[hidden] { display:none; }
    .a5n-floor-title { margin:0; display:flex; align-items:center; gap:.55rem; padding-bottom:.5rem; border-bottom:1px solid var(--line-light); font-size:.98rem; font-weight:800; color:var(--light-text); }
    .a5n-floor-title::before { content:""; flex:none; width:.3rem; height:1.05rem; border-radius:2px; background:var(--moss); }
    .a5n-floor-title small { color:var(--muted-light); font-weight:600; font-size:.75rem; }

    /* การ์ดข่าว: คอม 4 / แท็บเล็ต 2 / มือถือ 1 */
    .a5n-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:.9rem; }
    @media (max-width: 1100px) { .a5n-grid { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 640px) { .a5n-grid { grid-template-columns:1fr; } }

    .a5n-card { position:relative; display:flex; flex-direction:column; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--menu-bg); overflow:hidden; text-decoration:none; color:inherit; transition:border-color .16s ease, transform .16s ease, box-shadow .16s ease; }
    .a5n-card:hover { border-color:var(--moss); transform:translateY(-3px); box-shadow:0 12px 30px rgb(0 0 0 / 18%); }
    .a5n-card-img { aspect-ratio:16/10; background:var(--panel-soft); overflow:hidden; display:grid; place-items:center; color:var(--muted-light); }
    .a5n-card-img img { width:100%; height:100%; object-fit:cover; transition:transform .25s ease; }
    .a5n-card:hover .a5n-card-img img { transform:scale(1.04); }
    .a5n-card-body { padding:.75rem .9rem .85rem; display:grid; gap:.25rem; }
    .a5n-card-body h3 { margin:0; font-size:.95rem; font-weight:700; color:var(--light-text); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .a5n-card-body small { color:var(--muted-light); font-size:.74rem; }
    .a5n-badge { position:absolute; top:.6rem; left:.6rem; display:inline-flex; align-items:center; gap:.3rem; padding:.22rem .6rem; border-radius:999px; background:#c8964a; color:#fff; font-size:.7rem; font-weight:800; box-shadow:0 2px 8px rgb(0 0 0 / 30%); }

    .a5n-empty { grid-column:1 / -1; padding:2.4rem 1rem; text-align:center; color:var(--muted-light); font-size:.86rem; border:1px dashed var(--line-strong); border-radius: 0.32rem; }

    @media (max-width: 640px) {
      .a5n-head { gap:.55rem; }
      .a5n-head h2 { font-size:1rem; }
      .a5n-head-actions { gap:.4rem; }
      .a5n-round { font-size:.72rem; }
    }
@endsection

@section('content')
  <div class="a5n-wrap">
    {{-- ไม่มีตัวเลือกเดือน/วันที่ตรวจที่หน้านี้ (Manager 2026-08-27)
         หน้านี้ทำหน้าที่ "โชว์ว่าจะเข้าไปดูข้อมูลของอะไร" เท่านั้น การเลือกช่วงเวลาอยู่ที่หน้าปลายทาง --}}

    @include('area5s.partials.plan-reveal', ['revealBlockEmpty' => true])

    @if (false)
    <div class="a5n-toolbar">
      <label class="a5n-search">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
        <input type="text" data-a5n-search data-i18n-placeholder="a5s.index.search" data-i18n-aria="a5s.index.search" placeholder="ค้นหาชื่อพื้นที่" aria-label="ค้นหาพื้นที่">
      </label>
      <span class="a5n-count"><span data-i18n="a5s.index.showing">แสดง</span> <b data-a5n-shown>{{ $layouts->count() }}</b> / {{ $layouts->count() }} Layout</span>
    </div>

    <div class="a5n-floors">
      @forelse ($layoutsByZone as $zoneGroup)
        <section class="a5n-zone" data-a5n-zone>
          <h2 class="a5n-zone-title">
            @if ($zoneGroup['zone_name'])
              {{ $zoneGroup['zone_name'] }}
            @else
              <span data-i18n="a5s.index.unmappedZone">ยังไม่จัดชั้น</span>
            @endif
          </h2>
          @foreach ($zoneGroup['areas'] as $areaGroup)
            <section class="a5n-area" data-a5n-area @if ($areaGroup['area_id']) id="a5-area-{{ $areaGroup['area_id'] }}" @endif>
              <h3 class="a5n-area-title">
                @if ($areaGroup['area_name'])
                  <span>{{ $areaGroup['area_name'] }}</span>
                @else
                  <span data-i18n="a5s.index.unmappedArea">ยังไม่ผูกพื้นที่ย่อย</span>
                @endif
                <small>
                  @if ($areaGroup['zone_map_name'])
                    {{ $areaGroup['zone_map_name'] }} ·
                  @endif
                  {{ number_format($areaGroup['layout_count']) }} <span data-i18n="a5s.units.layout">พื้นที่</span>
                </small>
              </h3>
              @foreach ($areaGroup['floors'] as $group)
            <section class="a5n-floor" data-a5n-floor>
              <h3 class="a5n-floor-title">
                @if ($group['floor_name'])
                  <span>{{ $group['floor_name'] }}</span>
                @else
                  <span data-i18n="a5s.index.floorOther">อื่นๆ</span>
                @endif
                <small>(<span data-a5n-floor-count>{{ $group['layouts']->count() }}</span> <span data-i18n="a5s.units.layout">พื้นที่</span>)</small>
              </h3>
              <div class="a5n-grid">
                @foreach ($group['layouts'] as $layout)
                  @php
                    $myCount = (int) ($myByLayout[$layout->id] ?? 0);
                    $searchText = mb_strtolower(trim(implode(' ', array_filter([
                      $layout->name,
                      $zoneGroup['zone_name'] ?? '',
                      $areaGroup['area_name'] ?? '',
                      $areaGroup['zone_map_name'] ?? '',
                      $group['floor_name'] ?? '',
                    ]))));
                  @endphp
                  <a class="a5n-card nav-go" href="{{ route('area5s.layouts.show', $layout) }}"
                     data-a5n-card data-mine="{{ $myCount > 0 ? 1 : 0 }}" data-search="{{ $searchText }}">
                    @if ($myCount > 0)
                      <span class="a5n-badge">★ <span data-i18n="a5s.index.responsibleBadge">คุณรับผิดชอบ</span> {{ $myCount }} <span data-i18n="a5s.units.point">จุด</span></span>
                    @endif
                    <span class="a5n-card-img"><img src="{{ asset('storage/'.$layout->image_path) }}" alt="" loading="lazy"></span>
                    <span class="a5n-card-body">
                      <h3>{{ $layout->name }}</h3>
                      <small>{{ number_format($layout->points_count) }} <span data-i18n="a5s.units.point">จุด</span> · <span data-i18n="a5s.common.updated">อัปเดต</span> {{ $layout->updated_at?->format('d/m/Y') }}</small>
                    </span>
                  </a>
                @endforeach
              </div>
            </section>
              @endforeach
            </section>
          @endforeach
        </section>
      @empty
        <div class="a5n-empty">
          <span data-i18n="a5s.common.noLayout">ยังไม่มีพื้นที่ในระบบ</span>
        </div>
      @endforelse
      <div class="a5n-empty" data-a5n-noresult data-i18n="a5s.common.noResult" hidden>ไม่พบพื้นที่ที่ตรงกับเงื่อนไข</div>
    </div>
    @endif
  </div>

  <script>
    'use strict';

    (function () {
      const cards = Array.from(document.querySelectorAll('[data-a5n-card]'));
      const floors = Array.from(document.querySelectorAll('[data-a5n-floor]'));
      const areas = Array.from(document.querySelectorAll('[data-a5n-area]'));
      const zones = Array.from(document.querySelectorAll('[data-a5n-zone]'));
      const search = document.querySelector('[data-a5n-search]');
      const shown = document.querySelector('[data-a5n-shown]');
      const noresult = document.querySelector('[data-a5n-noresult]');
      let mode = 'all';

      function apply() {
        const q = (search?.value || '').trim().toLowerCase();
        let visible = 0;
        cards.forEach(c => {
          const hit = (q === '' || (c.dataset.search || '').includes(q)) && (mode === 'all' || c.dataset.mine === '1');
          c.hidden = !hit;
          if (hit) visible++;
        });
        // ซ่อนหัวข้อชั้นที่ไม่มีการ์ดตรงเงื่อนไข + อัปเดตตัวนับต่อชั้น
        floors.forEach(sec => {
          const shownInFloor = Array.from(sec.querySelectorAll('[data-a5n-card]')).filter(c => !c.hidden).length;
          sec.hidden = shownInFloor === 0;
          const cnt = sec.querySelector('[data-a5n-floor-count]');
          if (cnt) cnt.textContent = shownInFloor.toLocaleString('th-TH');
        });
        areas.forEach(sec => {
          const anyFloorVisible = Array.from(sec.querySelectorAll('[data-a5n-floor]')).some(f => !f.hidden);
          sec.hidden = !anyFloorVisible;
        });
        // ซ่อนหัวข้อโซนที่ไม่มีชั้นไหนเหลือการ์ดเลย
        zones.forEach(sec => {
          const anyAreaVisible = Array.from(sec.querySelectorAll('[data-a5n-area]')).some(area => !area.hidden);
          sec.hidden = !anyAreaVisible;
        });
        if (shown) shown.textContent = visible.toLocaleString('th-TH');
        if (noresult) noresult.hidden = visible !== 0 || cards.length === 0;
      }

      search?.addEventListener('input', apply);
    })();
  </script>
@endsection
