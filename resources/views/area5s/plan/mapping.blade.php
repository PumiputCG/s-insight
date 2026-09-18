@extends('layouts.portal')

@section('title', 'ตรวจ/แก้ mapping ข้อมูลเก่า — SUPAVUT 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.mapping.title">ตรวจ/แก้ mapping ข้อมูลเก่า</span>
@endsection

@section('page-style')
    .a5mp-wrap { display:grid; gap:.9rem; width:min(100%, 76rem); margin:0 auto; }
    .a5mp-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5mp-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:650; text-decoration:none; }
    .a5mp-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5mp-btn.primary { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5mp-btn.primary:hover { color:#fff; filter:brightness(1.06); }
    .a5mp-head { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-tint); padding:.9rem 1.1rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .a5mp-head p { margin:0; color:var(--muted-light); font-size:.82rem; }
    .a5mp-filter { display:flex; align-items:center; gap:.4rem; font-size:.8rem; color:var(--muted-light); }

    .a5mp-table-wrap { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-tint); overflow:auto; }
    .a5mp-table { width:100%; border-collapse:collapse; font-size:.84rem; }
    .a5mp-table th { text-align:left; padding:.6rem .8rem; border-bottom:1px solid var(--line-light); color:var(--muted-light); font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; white-space:nowrap; }
    .a5mp-table td { padding:.55rem .8rem; border-bottom:1px solid var(--line-light); vertical-align:middle; }
    .a5mp-table tr:last-child td { border-bottom:0; }
    .a5mp-table select { width:100%; min-width:9rem; padding:.4rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.8rem; }
    .a5mp-guess { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; background:rgb(91 141 239 / 14%); color:var(--moss); font-size:.72rem; font-weight:700; }
    .a5mp-mapped { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; background:rgb(76 175 125 / 14%); color:#4caf7d; font-size:.72rem; font-weight:700; }
    .a5mp-empty { padding:2.4rem 1rem; text-align:center; color:var(--muted-light); border:1px dashed var(--line-strong); border-radius: 0.32rem; }
@endsection

@section('content')
  <div class="a5mp-wrap">
    <div class="a5mp-top">
      <a class="a5s-back nav-go" href="{{ route('area5s.plan.index') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
    </div>

    <div class="a5mp-head">
      <p data-i18n="a5s.mapping.hint">เลือกโซนและชั้นให้พื้นที่เดิมแต่ละอัน — ชั้นเดาให้อัตโนมัติจากชื่อ (ถ้ามีคำว่า "Floor N" หรือ "ชั้น N") แต่โซนต้องเลือกเองเสมอ</p>
      <label class="a5mp-filter">
        <input type="checkbox" data-filter-unmapped {{ $onlyUnmapped ? 'checked' : '' }}>
        <span data-i18n="a5s.mapping.onlyUnmapped">แสดงเฉพาะที่ยังไม่ได้ผูกชั้น</span>
      </label>
    </div>

    @if (! $plan || $zones->isEmpty())
      <div class="a5mp-empty">
        <span data-i18n="a5s.mapping.noZoneYet">ยังไม่มีโซน — ไปที่หน้าแปลนบริษัทเพื่อวาดโซน/ตั้งชั้นก่อน</span>
      </div>
    @elseif ($layouts->isEmpty())
      <div class="a5mp-empty" data-i18n="a5s.mapping.allMapped">พื้นที่ทั้งหมดผูกชั้นครบแล้ว</div>
    @else
      <form action="{{ route('area5s.plan.mapping.save') }}" method="POST">
        @csrf
        <div class="a5mp-table-wrap">
          <table class="a5mp-table">
            <thead>
              <tr>
                <th data-i18n="a5s.manage.layoutName">ชื่อพื้นที่</th>
                <th data-i18n="a5s.mapping.round">รอบ</th>
                <th data-i18n="a5s.units.point">จุด</th>
                <th data-i18n="a5s.mapping.guessed">เดาชั้น</th>
                <th data-i18n="a5s.mapping.zone">โซน</th>
                <th data-i18n="a5s.mapping.area">พื้นที่ย่อย</th>
                <th data-i18n="a5s.mapping.floor">ชั้น</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($layouts as $layout)
                <tr data-row data-layout-id="{{ $layout['id'] }}">
                  @php
                    $currentZone = $layout['zone_id'] ? $zones->firstWhere('id', (int) $layout['zone_id']) : null;
                    $currentAreas = $currentZone ? $currentZone->zoneMaps->flatMap(fn ($map) => $map->areas) : collect();
                    $currentArea = $layout['area_id'] ? $currentAreas->firstWhere('id', (int) $layout['area_id']) : null;
                    $currentFloors = $currentArea
                      ? $currentArea->floors
                      : ($currentZone ? $currentZone->floors->whereNull('zone_map_area_id') : collect());
                  @endphp
                  <td>{{ $layout['name'] }}</td>
                  <td>{{ $layout['round_label'] }}</td>
                  <td>{{ number_format($layout['points_count']) }}</td>
                  <td>
                    @if ($layout['guessed_floor_name'])
                      <span class="a5mp-guess">{{ $layout['guessed_floor_name'] }}</span>
                    @else
                      <span class="a5mp-none" data-i18n="a5s.mapping.noGuess">เดาไม่ได้</span>
                    @endif
                  </td>
                  <td>
                    <select data-zone-select>
                      <option value="" data-i18n="a5s.mapping.selectZone">— เลือกโซน —</option>
                      @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}" {{ (int) $layout['zone_id'] === $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <select data-area-select>
                      <option value="" data-i18n="a5s.mapping.selectArea">— เลือกพื้นที่ย่อย —</option>
                      @foreach ($currentAreas as $area)
                        <option value="{{ $area->id }}" {{ (int) $layout['area_id'] === $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <select name="floor_id[{{ $layout['id'] }}]" data-floor-select>
                      <option value="">—</option>
                      @foreach ($currentFloors as $floor)
                          <option value="{{ $floor->id }}" {{ (int) $layout['floor_id'] === $floor->id ? 'selected' : '' }}>{{ $floor->name }}</option>
                      @endforeach
                    </select>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="a5mp-top" style="margin-top:.8rem; justify-content:flex-end;">
          <button type="submit" class="a5mp-btn primary" data-i18n="a5s.mapping.saveAll">บันทึก mapping ทั้งหมด</button>
        </div>
      </form>
    @endif
  </div>

  @php
    $zonesForMapping = $zones->map(fn ($z) => [
      'id' => $z->id,
      'areas' => $z->zoneMaps->flatMap(fn ($map) => $map->areas->map(fn ($area) => [
        'id' => $area->id,
        'name' => $area->name,
        'floors' => $area->floors->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values(),
      ]))->values(),
      'legacy_floors' => $z->floors->whereNull('zone_map_area_id')->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values(),
    ])->values();
  @endphp
  <script>
    'use strict';
    (function () {
      const zonesData = @json($zonesForMapping);
      const i18n = window.__portalLang || {};
      const t = (key, fallback) => i18n.text ? i18n.text(key, fallback) : fallback;

      document.querySelectorAll('[data-row]').forEach(row => {
        const zoneSelect = row.querySelector('[data-zone-select]');
        const areaSelect = row.querySelector('[data-area-select]');
        const floorSelect = row.querySelector('[data-floor-select]');
        const guessEl = row.querySelector('.a5mp-guess');
        const guessName = guessEl ? guessEl.textContent.trim() : null;

        function selectedZone() {
          return zonesData.find(z => String(z.id) === zoneSelect.value);
        }

        function fillAreas() {
          const zone = selectedZone();
          areaSelect.innerHTML = `<option value="">${t('a5s.mapping.selectArea', '— เลือกพื้นที่ย่อย —')}</option>`;
          floorSelect.innerHTML = `<option value="">—</option>`;
          if (!zone) return;
          (zone.areas || []).forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.id;
            opt.textContent = a.name;
            areaSelect.appendChild(opt);
          });
        }

        function fillFloors() {
          const zone = zonesData.find(z => String(z.id) === zoneSelect.value);
          floorSelect.innerHTML = '<option value="">—</option>';
          if (!zone) return;
          const area = (zone.areas || []).find(a => String(a.id) === areaSelect.value);
          const floors = area ? area.floors : (zone.legacy_floors || []);
          floors.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.name;
            if (guessName && f.name === guessName) opt.selected = true;
            floorSelect.appendChild(opt);
          });
        }

        zoneSelect.addEventListener('change', () => {
          fillAreas();
        });
        areaSelect.addEventListener('change', fillFloors);
      });

      document.querySelector('[data-filter-unmapped]')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        if (this.checked) url.searchParams.set('unmapped', '1');
        else url.searchParams.delete('unmapped');
        window.location.href = url.toString();
      });
    })();
  </script>
@endsection
