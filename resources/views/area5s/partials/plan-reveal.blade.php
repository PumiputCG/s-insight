{{-- บล็อก "ภาพบริษัท → เผยอาคาร" ใช้ร่วมกันระหว่าง /area5s และ /area5s/manage (Manager 2026-08-27)
     ต้องมี: $plan (A5sCompanyPlan) และ $planAreas (collection ที่มี name/zone_name/image_url/shape_points/floor_count/layout_count/url)
     หน้าไหนส่ง url เป็นเส้นทางของหน้านั้น — ภาพรวมไป /area5s/areas/{id} · จัดการพื้นที่ไป /area5s/manage/zones/{zone}/areas/{area} --}}
    {{-- ขั้นที่ 1 ภาพบริษัท → กดแล้วเลื่อนหาย · ขั้นที่ 2 อาคารที่ crop ไว้โผล่ขึ้นมา (Manager 2026-08-27) --}}
    @if ($plan?->image_path && ($planAreas ?? collect())->isNotEmpty())
      <section class="a5n-reveal" data-reveal>
        {{-- หัวข้อใช้ชื่อที่ตั้งไว้ในหน้าจัดการพื้นที่ + ปุ่ม ‹ กลับภาพหลัก (Manager 2026-08-27) --}}
        <div class="a5n-stage-head">
          <button type="button" class="a5s-back a5n-back" data-reveal-back hidden>
            <span aria-hidden="true">&lsaquo;</span>
            <span data-i18n="a5s.index.backPlan">กลับ</span>
          </button>
          <h2 class="a5n-stage-title">{{ $plan->name }}</h2>
          {{-- ช่องปุ่มฝั่งขวา (ไม่บังคับ) — หน้าจัดการพื้นที่ใช้วาง "แก้ไขแปลนบริษัท" ให้อยู่แถวเดียวกับหัวข้อ --}}
          @if (! empty($revealActionUrl))
            <a class="a5n-stage-action nav-go" href="{{ $revealActionUrl }}"
               @if (! empty($revealActionI18n)) data-i18n="{{ $revealActionI18n }}" @endif>{{ $revealActionLabel ?? '' }}</a>
          @endif
        </div>

        <button type="button" class="a5n-plan-shot" data-reveal-open
                aria-expanded="false" aria-controls="a5nAreas">
          <img src="{{ asset('storage/'.$plan->image_path) }}" alt="{{ $plan->name }}">
          <span class="a5n-plan-hint" data-i18n="a5s.index.tapPlan">แตะเพื่อดูอาคาร</span>
        </button>

        <div class="a5n-areas" id="a5nAreas" data-reveal-areas hidden>
          @foreach ($planAreas as $index => $area)
            @php
              // crop เฉพาะกรอบอาคารจากภาพโซน — วิธีเดียวกับการ์ดอาคารในหน้างานพื้นที่ของฉัน
              $shape = collect($area['shape_points'] ?? [])->filter(fn ($p) => is_array($p) && isset($p['x'], $p['y']));
              $minX = (float) ($shape->min('x') ?? 0);
              $maxX = (float) ($shape->max('x') ?? 100);
              $minY = (float) ($shape->min('y') ?? 0);
              $maxY = (float) ($shape->max('y') ?? 100);
              $cropW = max(1, $maxX - $minX);
              $cropH = max(1, $maxY - $minY);
              $cropSize = min(1000, 10000 / $cropW);
              $cropX = $cropW < 100 ? ($minX / (100 - $cropW)) * 100 : 50;
              $cropY = $cropH < 100 ? ($minY / (100 - $cropH)) * 100 : 50;
            @endphp
            @php
              // อาคารที่ยังไม่มีพื้นที่เลย: หน้าภาพรวมกดเข้าไปแล้วเป็นทางตัน จึงกันไว้ด้วย modal แทน (Manager 2026-08-27)
              $isEmptyArea = ! empty($revealBlockEmpty) && (int) ($area['layout_count'] ?? 0) === 0;
            @endphp
            <a class="a5n-area-card {{ $isEmptyArea ? 'is-empty-area' : 'nav-go' }}" href="{{ $area['url'] }}"
               style="--a5n-delay:{{ $index * 90 }}ms"
               @if ($isEmptyArea) data-empty-area @endif>
              <span class="a5n-area-no">{{ $index + 1 }}</span>
              @if ($area['image_url'])
                <span class="a5n-area-shot" role="img" aria-label="{{ $area['name'] }}"
                      style="background-image:url('{{ $area['image_url'] }}'); background-size:{{ number_format($cropSize, 2, '.', '') }}% auto; background-position:{{ number_format($cropX, 2, '.', '') }}% {{ number_format($cropY, 2, '.', '') }}%;"></span>
              @else
                <span class="a5n-area-shot is-empty" role="img" aria-label="{{ $area['name'] }}"></span>
              @endif
              <span class="a5n-area-copy">
                <strong>{{ $area['name'] }}</strong>
                <small>
                  {{ $area['zone_name'] }}
                  <i aria-hidden="true">·</i>
                  <b>{{ number_format($area['floor_count']) }}</b> <span data-i18n="a5s.review.floorCount">ชั้น</span>
                  <i aria-hidden="true">·</i>
                  <b>{{ number_format($area['layout_count']) }}</b> <span data-i18n="a5s.common.area">พื้นที่</span>
                </small>
              </span>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    @if (false && $plan && ($planZones ?? collect())->isNotEmpty())
      <section class="a5n-plan">
        <div class="a5n-plan-head">
          <div>
            <h3 data-i18n="a5s.index.planTitle">แปลนบริษัทและโซนทั้งหมด</h3>
            <p data-i18n="a5s.index.planHint">ดูขอบเขตโซนหลักและภาพโซนย่อยที่ Admin กำหนดไว้ ก่อนเข้าพื้นที่รายห้อง</p>
          </div>
          <span class="a5n-plan-total">{{ number_format($planZones->count()) }} <span data-i18n="a5s.plan.zoneLabel">โซน</span></span>
        </div>

        @if ($plan->image_path)
          <div class="a5n-plan-stage">
            <img src="{{ asset('storage/'.$plan->image_path) }}" alt="{{ $plan->name }}" loading="lazy">
            <svg class="a5n-plan-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
              @foreach ($planZones as $zone)
                @php
                  $zonePoints = collect($zone->shape_points ?: []);
                  $zonePointText = $zonePoints->map(fn ($point) => ((float) ($point['x'] ?? 0)).','.((float) ($point['y'] ?? 0)))->implode(' ');
                  $zoneCx = $zonePoints->avg('x') ?? 50;
                  $zoneCy = $zonePoints->avg('y') ?? 50;
                @endphp
                @if ($zonePointText !== '')
                  <polygon class="a5n-zone-poly" points="{{ $zonePointText }}" fill="{{ $zone->color ?: '#5b7343' }}47" stroke="{{ $zone->color ?: '#5b7343' }}" stroke-width="1"></polygon>
                  <text class="a5n-zone-label" x="{{ $zoneCx }}" y="{{ $zoneCy }}">{{ $zone->name }}</text>
                @endif
              @endforeach
            </svg>
          </div>
        @endif

        <div class="a5n-submap-grid">
          @foreach ($planZones as $zone)
            @foreach ($zone->zoneMaps as $zoneMap)
              <article class="a5n-submap-card">
                <div class="a5n-submap-meta">
                  <div>
                    <strong title="{{ $zoneMap->name }}">{{ $zoneMap->name }}</strong>
                    <small>{{ $zone->name }} · {{ $zoneMap->areas->count() }} <span data-i18n="a5s.mapping.area">พื้นที่ย่อย</span></small>
                  </div>
                </div>
                @if ($zoneMap->image_path)
                  <div class="a5n-submap-stage">
                    <img src="{{ asset('storage/'.$zoneMap->image_path) }}" alt="{{ $zoneMap->name }}" loading="lazy">
                    <svg class="a5n-submap-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                      @foreach ($zoneMap->areas as $area)
                        @php
                          $areaPoints = collect($area->shape_points ?: []);
                          $areaPointText = $areaPoints->map(fn ($point) => ((float) ($point['x'] ?? 0)).','.((float) ($point['y'] ?? 0)))->implode(' ');
                          $areaCx = $areaPoints->avg('x') ?? 50;
                          $areaCy = $areaPoints->avg('y') ?? 50;
                        @endphp
                        @if ($areaPointText !== '')
                          <polygon class="a5n-submap-poly" points="{{ $areaPointText }}" fill="{{ $area->color ?: '#5b7343' }}42" stroke="{{ $area->color ?: '#5b7343' }}" stroke-width=".8"></polygon>
                          <text class="a5n-submap-label" x="{{ $areaCx }}" y="{{ $areaCy }}">{{ $area->name }}</text>
                        @endif
                      @endforeach
                    </svg>
                  </div>
                @endif
                <div class="a5n-submap-pills">
                  @forelse ($zoneMap->areas as $area)
                    <span class="a5n-submap-pill">
                      <b title="{{ $area->name }}">{{ $area->name }}</b>
                      {{ number_format((int) ($layoutCountByArea[$area->id] ?? 0)) }} Layout
                    </span>
                  @empty
                    <span class="a5n-submap-pill" data-i18n="a5s.index.noSubArea">ยังไม่มีพื้นที่ย่อย</span>
                  @endforelse
                </div>
              </article>
            @endforeach
          @endforeach
        </div>
      </section>
    @endif

    {{-- แจ้งว่าอาคารนี้ยังไม่มีข้อมูล — กลางจอ ปิดได้ด้วยปุ่ม/ฉากหลัง/Esc (Manager 2026-08-27) --}}
    <div class="a5n-modal" data-empty-modal hidden aria-hidden="true">
      <button type="button" class="a5n-modal-backdrop" data-empty-close aria-label="ปิด" data-i18n-aria="a5s.common.close"></button>
      <div class="a5n-modal-card" role="dialog" aria-modal="true" aria-labelledby="a5nEmptyTitle">
        <h3 id="a5nEmptyTitle" data-i18n="a5s.index.emptyArea">ยังไม่มีข้อมูลในพื้นที่นี้</h3>
        <p data-empty-name></p>
        <button type="button" class="a5n-modal-btn" data-empty-close data-i18n="a5s.common.close">ปิด</button>
      </div>
    </div>

<script>
  'use strict';    /* ภาพบริษัท → กดแล้วยุบหายพร้อมอนิเมชัน แล้วอาคารที่ crop ไว้ไล่โผล่ทีละใบ
       ปุ่ม "‹ ภาพบริษัท" พากลับมาขั้นแรก (Manager 2026-08-27) */
    (function a5nReveal() {
      const shell = document.querySelector('[data-reveal]');
      const openButton = shell?.querySelector('[data-reveal-open]');
      const backButton = shell?.querySelector('[data-reveal-back]');
      const areas = shell?.querySelector('[data-reveal-areas]');
      if (!shell || !openButton || !areas) return;

      openButton.addEventListener('click', () => {
        areas.hidden = false;
        if (backButton) backButton.hidden = false;
        // รอเฟรมถัดไปให้ browser คำนวณ layout ก่อน ทรานซิชันถึงจะวิ่ง
        window.requestAnimationFrame(() => {
          shell.classList.add('is-open');
          openButton.setAttribute('aria-expanded', 'true');
        });
      });

      /* อาคารที่ยังไม่มีพื้นที่: กันไม่ให้เข้าไปเจอหน้าว่าง แล้วเด้ง modal บอกแทน */
      const emptyModal = document.querySelector('[data-empty-modal]');
      const emptyName = emptyModal?.querySelector('[data-empty-name]');
      const closeEmpty = () => {
        if (!emptyModal) return;
        emptyModal.hidden = true;
        emptyModal.setAttribute('aria-hidden', 'true');
      };
      shell.querySelectorAll('[data-empty-area]').forEach((card) => {
        card.addEventListener('click', (event) => {
          event.preventDefault();
          if (!emptyModal) return;
          if (emptyName) emptyName.textContent = card.querySelector('.a5n-area-copy strong')?.textContent?.trim() || '';
          emptyModal.hidden = false;
          emptyModal.setAttribute('aria-hidden', 'false');
          emptyModal.querySelector('.a5n-modal-btn')?.focus();
        });
      });
      emptyModal?.querySelectorAll('[data-empty-close]').forEach((btn) => btn.addEventListener('click', closeEmpty));
      document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeEmpty(); });

      backButton?.addEventListener('click', () => {
        shell.classList.remove('is-open');
        openButton.setAttribute('aria-expanded', 'false');
        backButton.hidden = true;
        // ซ่อนการ์ดหลังภาพบริษัทคลี่กลับมาเสร็จ จะได้ไม่กระตุก
        window.setTimeout(() => { areas.hidden = true; }, 420);
        openButton.focus();
      });
    })();
</script>
