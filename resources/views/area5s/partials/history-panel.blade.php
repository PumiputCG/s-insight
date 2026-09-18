{{-- ประวัติการประเมินของอาคาร/พื้นที่ย่อย — ใช้ร่วม 3 หน้า: /area5s/areas/{id}, /my-work/areas/{id}, /my-work/areas/{id}/evaluations
     รูปแบบเดียวกับตารางหน้างานพื้นที่ของฉัน แต่เป็นภาพรวมอย่างเดียว (Manager สั่งรื้อทั้งหน้า 2026-08-27)
     ต้องมี $panel จาก a5sHistoryFloors() — floors[].layouts[].points[] มี bucket/assignees/score พร้อมแล้ว --}}
@php
  $panelFloors = $panel['floors'] ?? [];
  $panelPoints = collect($panelFloors)
      ->flatMap(fn ($floor) => collect($floor['layouts'])->flatMap(fn ($layout) => $layout['points']));
  $panelLayoutCount = collect($panelFloors)->sum(fn ($floor) => count($floor['layouts']));

  $panelMonths = $panel['round_nav']['months'] ?? [];
  $panelMonth = collect($panelMonths)->firstWhere('is_selected', true);
  $panelInspections = $panelMonth['inspections'] ?? [];
@endphp

<style>
  /* โทเคนสีชุดเดียวกับ workspace — ประกาศบน shell ให้ทุกอย่างข้างในสืบทอด */
  /* 🔴 โมดัลอยู่ "นอก" .a5h-shell — ถ้าประกาศโทเคนแค่บน shell สีผ่าน/ปฏิเสธในโมดัลจะหายหมด
     (บั๊กเดียวกับ .a5rv-history-dialog ในหน้า workspace) */
  .a5h-shell,
  .a5h-modal {
    --a5h-pass:#4dbe86;
    --a5h-fail:#e8635a;
    --a5h-pending:#c8964a;
    --a5h-no-data:#9da39b;
    --a5h-surface: var(--panel-tint);
    --a5h-surface-strong: var(--panel-tint-strong);
    /* ระดับชั้นของปฏิทิน: เดือน → วัน → ครั้ง → เนื้อหา */
    --a5h-lv1:#4f6ea8;
    --a5h-lv2:#7a6bb0;
  }
  .a5h-shell { display:grid; gap:.75rem; width:min(100%, 88rem); margin:0 auto; font-kerning:normal; }
  html[data-theme="light"] .a5h-shell,
  html[data-theme="light"] .a5h-modal {
    --a5h-pass:#137a4a;
    --a5h-fail:#c62828;
    --a5h-pending:#885b18;
    --a5h-no-data:#5d625c;
  }

  /* ---- กรอบ 1: ช่วงเวลา + อาคาร ---- */
  .a5h-context { overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--a5h-surface); }
  .a5h-selects { display:flex; align-items:end; gap:.55rem; flex-wrap:wrap; padding:.8rem 1rem; }
  .a5h-field { display:grid; gap:.3rem; min-width:11rem; }
  .a5h-field > span { color:var(--muted-light); font-size:.7rem; }
  .a5h-field select {
    width:100%; min-height:2.6rem; padding:.45rem 2.2rem .45rem .75rem;
    border:1px solid var(--line-light); border-radius: 0.25rem;
    background:var(--menu-bg); color:var(--light-text);
    font:inherit; font-size:.82rem; font-weight:700; cursor:pointer;
  }
  .a5h-field select:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }

  .a5h-areabar {
    display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
    border-top:1px solid var(--line-light); padding:.75rem 1rem;
  }
  .a5h-areabar-copy { display:grid; gap:.14rem; min-width:0; }
  .a5h-areabar-copy strong { overflow:hidden; color:var(--light-text); font-size:1.02rem; font-weight:800; text-overflow:ellipsis; white-space:nowrap; }
  .a5h-areabar-copy small { color:var(--muted-light); font-size:.75rem; }
  .a5h-areabar-copy small b { color:var(--light-text); font-weight:750; font-variant-numeric:tabular-nums; }
  .a5h-areabar-copy small i { font-style:normal; opacity:.5; }
  .a5h-roundtag {
    flex:none; display:inline-flex; align-items:center; gap:.4rem;
    padding:.32rem .7rem; border:1px solid var(--line-light); border-radius:999px;
    background:var(--menu-bg); color:var(--muted-light); font-size:.75rem; font-weight:750;
  }
  .a5h-roundtag.is-open { border-color:color-mix(in srgb, var(--a5h-pass) 45%, var(--line-light)); color:var(--a5h-pass); }
  .a5h-roundtag .dot { width:.45rem; height:.45rem; border-radius:50%; background:currentColor; }

  /* ---- กรอบ 2: แท็บสถานะ → ชั้น → พื้นที่ + ตารางจุด ---- */
  .a5h-locations { display:block; overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--a5h-surface); }
  .a5h-floor-nav { display:flex; align-items:center; gap:.4rem; overflow-x:auto; padding:.8rem 1rem .1rem; }
  .a5h-floor-tab {
    min-height:2.2rem; flex:0 0 auto; display:inline-flex; align-items:center; gap:.4rem;
    border:1px solid var(--line-light); border-radius: 0.28rem; padding:.4rem 1.05rem;
    background:var(--menu-bg); color:var(--muted-light);
    font:inherit; font-size:.8rem; font-weight:750; cursor:pointer;
    transition:border-color .16s ease, background-color .16s ease, color .16s ease;
  }
  .a5h-floor-tab:hover { border-color:var(--moss); color:var(--moss); }
  .a5h-floor-tab[aria-selected="true"] { border-color:var(--moss); background:var(--moss); color:#fff; }
  .a5h-floor-panel { display:grid; gap:.7rem; padding:.75rem 1rem 1rem; }
  .a5h-floor-panel[hidden], .a5h-layout[hidden] { display:none !important; }

  .a5h-layout { overflow:hidden; border:1px solid var(--line-light); border-radius: 0.3rem; background:var(--menu-bg); }
  .a5h-layout-toggle {
    width:100%; display:flex; align-items:center; gap:.7rem;
    border:0; background:transparent; padding:.6rem .8rem; color:inherit; font:inherit; text-align:left; cursor:pointer;
  }
  .a5h-layout-toggle:hover { background:var(--hover-soft); }
  .a5h-layout-image { width:3.4rem; aspect-ratio:16 / 10; flex:none; overflow:hidden; border-radius: 0.25rem; background:var(--a5h-surface); }
  .a5h-layout-image img { display:block; width:100%; height:100%; object-fit:cover; }
  .a5h-layout-image span { display:grid; place-items:center; width:100%; height:100%; color:var(--muted-light); font-size:.62rem; }
  .a5h-layout-name { display:grid; gap:.12rem; min-width:0; margin-right:auto; }
  .a5h-layout-name strong { overflow:hidden; color:var(--light-text); font-size:.92rem; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
  .a5h-layout-name span { color:var(--muted-light); font-size:.73rem; }
  .a5h-layout-chevron { width:1.15rem; height:1.15rem; flex:none; color:var(--muted-light); stroke:currentColor; fill:none; transition:transform .18s ease, color .18s ease; }
  .a5h-layout-toggle[aria-expanded="true"] .a5h-layout-chevron { transform:rotate(180deg); color:var(--moss); }
  .a5h-layout-content[hidden] { display:none !important; }

  /* ลำดับคอลัมน์: จุด · ผู้รับผิดชอบ · คะแนน · สถานะ · วันประเมิน · ประวัติ · ดำเนินการ */
  .a5h-table-head,
  .a5h-point-row { display:grid; grid-template-columns:minmax(7.5rem, .8fr) minmax(10.5rem, 1.5fr) minmax(4.5rem, .5fr) minmax(6.5rem, .7fr) minmax(8rem, .9fr) minmax(4.5rem, .5fr) minmax(7rem, .75fr); align-items:stretch; gap:0; padding:0; }
  /* เส้นแบ่งคอลัมน์แนวตั้ง — padding อยู่ที่ตัวช่อง หัวตารางกับแถวจึงตรงกันเป๊ะ */
  .a5h-table-head > *,
  .a5h-point-row > * { display:flex; align-items:center; justify-content:center; min-width:0; padding:.5rem .7rem; border-right:1px solid var(--line-light); text-align:center; }
  .a5h-table-head > *:first-child,
  .a5h-point-row > *:first-child { padding-left:1rem; }
  .a5h-table-head > *:last-child,
  .a5h-point-row > *:last-child { padding-right:1rem; border-right:0; }
  .a5h-point-row .a5h-assignees { justify-content:flex-start; text-align:left; }
  .a5h-table-head { border-top:1px solid var(--line-light); border-bottom:1px solid var(--line-light); background:var(--a5h-surface-strong); color:var(--muted-light); font-size:.69rem; font-weight:700; letter-spacing:.03em; }
  .a5h-point-row { min-height:3.1rem; border-bottom:1px solid var(--line-light); }
  .a5h-point-row:last-child { border-bottom:0; }

  .a5h-point-main { display:grid; gap:.2rem; min-width:0; }
  .a5h-point-main strong { overflow:hidden; color:var(--light-text); font-size:.85rem; font-weight:730; text-overflow:ellipsis; white-space:nowrap; }
  .a5h-point-main small { max-width:100%; overflow:hidden; color:var(--muted-light); font-size:.74rem; text-overflow:ellipsis; white-space:nowrap; }
  .a5h-assignees { display:flex; align-items:center; gap:.45rem; min-width:0; }
  .a5h-avatar { width:1.75rem; height:1.75rem; flex:none; display:grid; place-items:center; overflow:hidden; border:0; border-radius:50%; padding:0; background:var(--a5h-surface); color:var(--light-text); font:inherit; font-size:.68rem; font-weight:760; }
  button.a5h-avatar { cursor:zoom-in; }
  button.a5h-avatar:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }
  .a5h-avatar img { display:block; width:100%; height:100%; object-fit:cover; }
  .a5h-person-copy { display:grid; gap:.08rem; min-width:0; }
  .a5h-person-copy strong { overflow:hidden; color:var(--light-text); font-size:.78rem; font-weight:680; text-overflow:ellipsis; white-space:nowrap; }
  .a5h-person-copy small { color:var(--muted-light); font-size:.7rem; }
  .a5h-more { flex:none; color:var(--muted-light); font-size:.72rem; font-weight:750; }
  .a5h-score { color:var(--light-text); font-size:.82rem; font-weight:780; font-variant-numeric:tabular-nums; }
  .a5h-score.is-none, .a5h-point-row .is-none { color:var(--muted-light); font-weight:600; }
  .a5h-time { color:var(--light-text); font-size:.78rem; font-variant-numeric:tabular-nums; }

  /* สถานะ: พื้นหลังสีอ่อนเต็มช่อง ตัวอักษรสีเดียวกัน ไม่มีกรอบ ไม่มีจุด */
  .a5h-point-row .a5h-status {
    justify-content:center; border-right:1px solid var(--line-light);
    background:color-mix(in srgb, var(--bucket-color) 16%, transparent);
    color:var(--bucket-color); font-size:.76rem; font-weight:750; white-space:nowrap;
  }
  .a5h-status.is-pass { --bucket-color:var(--a5h-pass); }
  .a5h-status.is-fail { --bucket-color:var(--a5h-fail); }
  .a5h-status.is-pending { --bucket-color:var(--a5h-pending); }
  .a5h-status.is-none, .a5h-status.is-no_data { --bucket-color:var(--a5h-no-data); }

  /* ประวัติ/ดำเนินการ เป็นปุ่มมีกรอบ */
  .a5h-link {
    min-height:2.1rem; display:inline-flex; align-items:center; justify-content:center; gap:.28rem;
    border:1px solid var(--line-light); border-radius: 0.25rem; padding:.32rem .7rem;
    background:var(--menu-bg); color:var(--light-text);
    font:inherit; font-size:.76rem; font-weight:730; text-decoration:none; cursor:pointer;
    transition:border-color .16s ease, color .16s ease;
  }
  .a5h-link:hover { border-color:var(--moss); color:var(--moss); }
  /* ปุ่มคอลัมน์ "ดำเนินการ" น้ำเงินตัวอักษรขาว ชุดเดียวกับหน้างานพื้นที่ของฉัน (Manager 2026-08-27) */
  .a5h-cell-action .a5h-link { border-color:var(--moss); background:var(--moss); color:#fff; }
  .a5h-cell-action .a5h-link:hover { background:color-mix(in srgb, var(--moss) 86%, #000); color:#fff; }
  .a5h-link-n { color:var(--muted-light); font-variant-numeric:tabular-nums; font-weight:800; }
  .a5h-link:hover .a5h-link-n { color:inherit; }

  .a5h-empty { display:grid; gap:.3rem; place-items:center; padding:2.2rem 1rem; text-align:center; color:var(--muted-light); font-size:.84rem; }

  /* ---- modal ประวัติของจุด ---- */
  @media (max-width: 1080px) {
    .a5h-table-head { display:none; }
    .a5h-point-row { grid-template-columns:1fr auto; gap:.35rem .6rem; padding:.7rem 1rem; }
    .a5h-point-row > * { justify-content:flex-start; border-right:0; padding:0; text-align:left; }
    .a5h-point-row > *:first-child, .a5h-point-row > *:last-child { padding:0; }
    .a5h-point-row .a5h-status { grid-column:2; grid-row:1; justify-self:end; width:max-content; padding:.22rem .55rem; border-right:0; border-radius:.25rem; }
    .a5h-assignees { grid-column:1 / -1; }
  }
  @media (prefers-reduced-motion: reduce) {
    .a5h-floor-tab, .a5h-link, .a5h-layout-chevron { transition:none; }
  }
</style>

<main class="a5h-shell" data-a5h>
  <a class="a5s-back nav-go" href="{{ $panel['back_url'] }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>

  {{-- กรอบ 1: เลือกเดือน/วันที่ตรวจ แล้วบอกว่ากำลังดูอาคารไหน --}}
  <section class="a5h-context">
    <div class="a5h-selects">
      <label class="a5h-field">
        <span data-i18n="a5s.workspace.month">เดือน</span>
        <select data-h-month>
          @foreach ($panelMonths as $month)
            <option value="{{ $month['inspections'][0]['url'] ?? '' }}" @selected($month['is_selected'])>{{ $month['label'] }}</option>
          @endforeach
        </select>
      </label>
      <label class="a5h-field">
        <span data-i18n="a5s.workspace.inspectionDate">วันที่ตรวจ</span>
        <select data-h-round>
          @foreach ($panelInspections as $inspection)
            <option value="{{ $inspection['url'] }}" @selected($inspection['is_selected'])>
              {{ $inspection['date_label'] ?: 'ครั้งที่ '.$inspection['seq'] }}
            </option>
          @endforeach
        </select>
      </label>
    </div>

    <div class="a5h-areabar">
      {{-- หัวข้อคือชื่อพื้นที่ย่อยจริง เช่น Office Area (Manager 2026-08-27) --}}
      <span class="a5h-areabar-copy">
        <strong>{{ $panel['context_area'] }}</strong>
        <small>
          {{ $panel['context_zone'] }}
          <i aria-hidden="true">·</i> <b>{{ number_format(count($panelFloors)) }}</b> <span data-i18n="a5s.review.floorCount">ชั้น</span>
          <i aria-hidden="true">·</i> <b>{{ number_format($panelLayoutCount) }}</b> <span data-i18n="a5s.common.area">พื้นที่</span>
          <i aria-hidden="true">·</i> <b>{{ number_format($panelPoints->count()) }}</b> <span data-i18n="a5s.common.point">จุด</span>
        </small>
      </span>
    </div>
  </section>

  {{-- กรอบ 2: ตัวกรองสถานะ → ชั้น → พื้นที่ + ตารางจุด --}}
  <section class="a5h-locations">
    @if (count($panelFloors))
      <div class="a5h-floor-nav" role="tablist" aria-label="เลือกชั้น" data-i18n-aria="a5s.common.selectFloor">
        @foreach ($panelFloors as $index => $floor)
          <button type="button" class="a5h-floor-tab" role="tab" data-h-floor-tab="{{ $index }}"
                  aria-selected="{{ $index === 0 ? 'true' : 'false' }}">{{ $floor['name'] ?? '—' }}</button>
        @endforeach
      </div>

      @foreach ($panelFloors as $index => $floor)
        <div class="a5h-floor-panel" data-h-floor-panel="{{ $index }}" {{ $index === 0 ? '' : 'hidden' }}>
          @foreach ($floor['layouts'] as $layout)
            <article class="a5h-layout" data-h-layout>
              <button type="button" class="a5h-layout-toggle" aria-expanded="true">
                <span class="a5h-layout-image">
                  @if ($layout['image'])
                    <img src="{{ $layout['image'] }}" alt="" loading="lazy">
                  @else
                    <span data-i18n="a5s.common.noImage">ไม่มีรูปภาพ</span>
                  @endif
                </span>
                <span class="a5h-layout-name">
                  <strong title="{{ $layout['name'] }}">{{ $layout['name'] }}</strong>
                  <span>
                    {{ number_format($layout['points_count']) }} <span data-i18n="a5s.common.point">จุด</span>
                    <i aria-hidden="true">·</i> {{ number_format($layout['card_count']) }} <span data-i18n="a5s.myWork.cardsRecorded">รายการที่บันทึก</span>
                  </span>
                </span>
                <svg class="a5h-layout-chevron" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
              </button>

              <div class="a5h-layout-content">
                <div class="a5h-table-head" role="row">
                  <span data-i18n="a5s.common.point">จุด</span>
                  <span data-i18n="a5s.common.assignees">ผู้รับผิดชอบ</span>
                  <span data-i18n="a5s.score.short">คะแนน</span>
                  <span data-i18n="a5s.common.status">สถานะ</span>
                  <span data-i18n="a5s.common.evaluatedDate">วันประเมิน</span>
                  <span data-i18n="a5s.common.history">ประวัติ</span>
                  <span data-i18n="a5s.common.action">ดำเนินการ</span>
                </div>

                @foreach ($layout['points'] as $point)
                  @php $people = collect($point['assignees'] ?? []); @endphp
                  <div class="a5h-point-row" data-h-bucket="{{ $point['bucket'] }}">
                    <span class="a5h-point-main">
                      <strong><span data-i18n="a5s.common.point">จุด</span> {{ $point['code'] }}</strong>
                      <small title="{{ $point['name'] }}">{{ $point['name'] }}</small>
                    </span>

                    <span class="a5h-assignees">
                      @php $lead = $people->first(); @endphp
                      @if ($lead)
                        @if (! empty($lead['avatar']))
                          {{-- กดรูปแล้วซูม — ใช้ตัวดูรูปกลางของ portal ชุดเดียวกับหน้างานพื้นที่ของฉัน (Manager 2026-08-27) --}}
                          <button type="button" class="a5h-avatar"
                                  data-image-preview
                                  data-image-src="{{ $lead['avatar'] }}"
                                  data-image-alt="{{ $lead['name'] ?? '' }}"
                                  aria-label="ดูรูปโปรไฟล์ {{ $lead['name'] ?? '' }}"
                                  data-loc-attr="aria-label"
                                  data-loc-th="ดูรูปโปรไฟล์ {{ $lead['name'] ?? '' }}"
                                  data-loc-en="View profile photo of {{ $lead['name'] ?? '' }}"
                                  data-loc-my="{{ $lead['name'] ?? '' }} ၏ ပရိုဖိုင်ပုံကို ကြည့်ရန်">
                            <img src="{{ $lead['avatar'] }}" alt="" loading="lazy">
                          </button>
                        @else
                          <span class="a5h-avatar">{{ mb_substr((string) ($lead['name'] ?? '?'), 0, 1) }}</span>
                        @endif
                        <span class="a5h-person-copy">
                          {{-- ชื่อคนสลับตามภาษา ใช้ hook เดียวกับหน้างานพื้นที่ของฉัน --}}
                          <strong data-person-name
                                  data-name-th="{{ $lead['name_th'] ?? ($lead['name'] ?? '') }}"
                                  data-name-en="{{ $lead['name_en'] ?? ($lead['name'] ?? '') }}"
                                  data-name-my="{{ $lead['name_my'] ?? ($lead['name'] ?? '') }}">{{ $lead['name'] ?? '-' }}</strong>
                          <small>{{ $lead['code'] ?? '' }}</small>
                        </span>
                        @if ($people->count() > 1)
                          <span class="a5h-more">+{{ $people->count() - 1 }}</span>
                        @endif
                      @else
                        <span class="is-none">—</span>
                      @endif
                    </span>

                    <span class="a5h-score {{ $point['score'] === null ? 'is-none' : '' }}">{{ $point['score_label'] }}</span>

                    <span class="a5h-status is-{{ $point['bucket'] }}" data-i18n="{{ $point['status_key'] }}">{{ $point['status_default'] }}</span>

                    <span class="a5h-time {{ ($point['evaluated_on'] ?? '-') === '-' ? 'is-none' : '' }}">{{ $point['evaluated_on'] }}</span>

                    {{-- ปฏิทินคะแนนรายจุด: ไอคอนปฏิทินชุดเดียวกับหน้างานพื้นที่ของฉัน (Manager 2026-08-27) --}}
                    <span class="a5h-cell-history">
                      @if (count($point['calendar']['months'] ?? []))
                        @php
                          $calLead = $people->first();
                          $calOwner = $calLead
                            ? trim(($calLead['name'] ?? '').' ('.($calLead['code'] ?? '').')')
                            : '';
                        @endphp
                        <button type="button" class="a5h-cal-btn"
                                data-h-calendar="{{ json_encode($point['calendar'], JSON_UNESCAPED_UNICODE) }}"
                                data-h-code="{{ $point['code'] }}"
                                data-h-owner="{{ $calOwner }}"
                                aria-label="เปิดปฏิทินคะแนน" data-i18n-aria="a5s.calendar.open">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                          </svg>
                        </button>
                      @else
                        <span class="is-none">—</span>
                      @endif
                    </span>

                    <span class="a5h-cell-action">
                      @if (! empty($layout['work_url']))
                        {{-- ส่ง ?point= ไปด้วย หน้าปลายทางจะได้เปิดที่จุดของคนที่กด (Manager 2026-08-27) --}}
                        <a class="a5h-link nav-go"
                           href="{{ $layout['work_url'].(str_contains($layout['work_url'], '?') ? '&' : '?').'point='.$point['id'] }}"
                           data-i18n="{{ $layout['work_key'] }}">{{ $layout['work_default'] }}</a>
                      @else
                        <span class="is-none">—</span>
                      @endif
                    </span>
                  </div>
                @endforeach
              </div>
            </article>
          @endforeach

        </div>
      @endforeach
    @else
      <div class="a5h-empty" data-i18n="{{ $panel['empty_key'] }}">{{ $panel['empty_default'] }}</div>
    @endif
  </section>
</main>

</div>
<script>
  'use strict';

  (function a5hPanel() {
    const shell = document.querySelector('[data-a5h]');
    if (!shell) return;
    const i18n = window.__portalLang || {};
    const t = (key, fallback) => (i18n.text ? i18n.text(key, fallback) : fallback);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));

    /* เลือกเดือน/วันที่ตรวจแล้วไปหน้านั้นทันที — เปลี่ยนเดือนจะพาไปครั้งตรวจแรกของเดือนนั้น */
    shell.querySelectorAll('[data-h-month], [data-h-round]').forEach((select) => {
      select.addEventListener('change', () => {
        const url = select.value;
        if (url) window.location.href = url;
      });
    });

    /* แท็บชั้น F1/F2/F3 */
    const floorTabs = Array.from(shell.querySelectorAll('[data-h-floor-tab]'));
    const floorPanels = Array.from(shell.querySelectorAll('[data-h-floor-panel]'));
    floorTabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const key = tab.dataset.hFloorTab;
        floorTabs.forEach((el) => el.setAttribute('aria-selected', String(el === tab)));
        floorPanels.forEach((panel) => { panel.hidden = panel.dataset.hFloorPanel !== key; });
      });
    });

    /* พับ/กางตารางของแต่ละพื้นที่ */
    shell.querySelectorAll('.a5h-layout-toggle').forEach((toggle) => {
      toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!open));
        const content = toggle.nextElementSibling;
        if (content) content.hidden = open;
      });
    });

  })();
</script>

@include('area5s.partials.score-calendar')
