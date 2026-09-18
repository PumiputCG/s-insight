@extends('layouts.portal')

@section('title', 'ตั้งค่าระบบ 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.settings.title">สิทธิ์ผู้ใช้</span>
@endsection

@php
  $roleLabels = ['admin' => 'ผู้ดูแลระบบ', 'allocator' => 'ผู้จัดสรรพื้นที่'];
  $roleLabelKeys = ['admin' => 'a5s.settings.roleAdmin', 'allocator' => 'a5s.settings.roleAllocator'];
  $byRole = collect($members)->groupBy('role');
@endphp

@section('page-style')
    .a5s-panel { max-width:64rem; border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); margin-bottom:1.4rem; overflow:visible; }
    .a5s-panel summary { list-style:none; cursor:pointer; }
    .a5s-panel summary::-webkit-details-marker { display:none; }
    .a5s-panel-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.35rem; }
    .a5s-panel h2 { margin:0 0 .3rem; font-size:1.05rem; }
    .a5s-panel .hint { margin:0; color:var(--muted-light); font-size:.83rem; }
    .a5s-panel-body { padding:0 1.35rem 1.25rem; }
    .a5s-fold-icon { width:2rem; height:2rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); font-size:1.1rem; flex:none; }
    .a5s-fold-icon::before { content:'-'; }
    .a5s-panel:not([open]) .a5s-fold-icon::before { content:'+'; }

    .a5s-role-group { margin-bottom:.9rem; }
    .a5s-role-group h4 { margin:0 0 .35rem; color:var(--moss); font-size:.85rem; }

    .a5s-chip { display:inline-flex; align-items:center; gap:.55rem; padding:.45rem .7rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); margin:.25rem; }
    .a5s-chip img, .a5s-chip .av { width:1.7rem; height:1.7rem; border-radius:50%; object-fit:cover; background:var(--line-light); color:var(--muted-light); display:inline-flex; align-items:center; justify-content:center; font-size:.7rem; }
    .a5s-chip .av { flex:0 0 1.7rem; border:1px solid var(--line-light); overflow:hidden; padding:0; }
    button.a5s-avatar-preview { cursor:zoom-in; appearance:none; transition:border-color .18s ease, transform .18s ease; }
    button.a5s-avatar-preview:hover, button.a5s-avatar-preview:focus-visible { border-color:var(--moss); transform:translateY(-1px); outline:none; }
    .a5s-chip .av svg { width:62%; height:62%; fill:currentColor; opacity:.72; }
    .a5s-chip .meta { display:flex; flex-direction:column; line-height:1.1; }
    .a5s-chip .meta strong { color:var(--light-text); font-size:.8rem; font-weight:650; }
    .a5s-chip .meta small { color:var(--muted-light); font-size:.72rem; }
    .a5s-chip button[data-remove-member], .a5s-chip button[data-remove-assignee] { border:none; background:transparent; color:#d98a80; cursor:pointer; font-size:1rem; padding:0 .2rem; }

    .a5s-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.85rem; }
    .a5s-btn:hover { border-color:var(--moss); }
    .a5s-btn.primary { background:var(--moss); color:#0c0d0c; border-color:var(--moss); }

    .a5s-add-row { display:flex; gap:.6rem; align-items:flex-start; flex-wrap:wrap; margin-top:.6rem; }
    .a5s-role-select { padding:.55rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.85rem; }
    .a5s-search { position:relative; flex:1; min-width:16rem; max-width:26rem; }
    .a5s-search input { width:100%; padding:.55rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; }
    .a5s-results { margin-top:.4rem; border:1px solid var(--line-light); border-radius: 0.25rem; overflow:hidden; display:none; position:absolute; z-index:30; width:100%; max-height:18rem; overflow-y:auto; }
    .a5s-results.show { display:block; }
    .a5s-results button { display:flex; align-items:center; gap:.6rem; width:100%; text-align:left; padding:.5rem .7rem; border:none; background:var(--menu-bg); color:inherit; cursor:pointer; border-bottom:1px solid var(--line-light); }
    .a5s-results button:hover { background:var(--panel-soft); }
    .a5s-results img, .a5s-results .av { width:1.6rem; height:1.6rem; border-radius:50%; object-fit:cover; background:var(--line-light); color:var(--muted-light); display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .a5s-results .av svg { width:62%; height:62%; fill:currentColor; opacity:.72; }

    .a5s-assign-list { display:grid; gap:1rem; }
    .a5s-area-list { display:grid; gap:.7rem; }
    .a5s-area-group { border:1px solid var(--line-light); border-radius: 0.31rem; background:var(--menu-bg); }
    .a5s-area-summary { display:grid; grid-template-columns:auto minmax(0,1fr) auto auto; align-items:center; gap:.65rem; min-height:3.65rem; padding:.65rem .75rem; }
    .a5s-area-summary:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }
    .a5s-area-dot { width:.65rem; height:.65rem; border:1px solid color-mix(in srgb,var(--area-color) 72%,var(--light-text)); border-radius:50%; background:var(--area-color); }
    .a5s-area-meta { display:grid; gap:.08rem; min-width:0; }
    .a5s-area-meta strong { overflow:hidden; color:var(--light-text); font-size:.9rem; font-weight:700; line-height:1.4; text-overflow:ellipsis; white-space:nowrap; }
    .a5s-area-meta small { color:var(--muted-light); font-size:.68rem; line-height:1.35; }
    .a5s-area-count { color:var(--muted-light); font-size:.7rem; white-space:nowrap; }
    .a5s-area-state { color:#d98a80; font-weight:700; }
    .a5s-area-arrow { width:1.8rem; height:1.8rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; color:var(--muted-light); font-size:1rem; transition:transform .18s ease; }
    .a5s-area-arrow::before { content:'›'; }
    .a5s-area-group[open] .a5s-area-arrow { transform:rotate(90deg); color:var(--moss); }
    .a5s-area-body { display:grid; gap:.8rem; padding:.75rem; border-top:1px solid var(--line-light); }
    .a5s-floor-group { display:grid; gap:.48rem; padding-top:.7rem; border-top:1px solid var(--line-light); }
    .a5s-floor-group:first-child { padding-top:0; border-top:0; }
    .a5s-floor-head { display:flex; align-items:center; justify-content:space-between; gap:.7rem; }
    .a5s-floor-head h3 { margin:0; color:var(--light-text); font-size:.82rem; font-weight:700; }
    .a5s-floor-head small { color:var(--muted-light); font-size:.68rem; }
    .a5s-layout-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(13rem, 1fr)); gap:.8rem; }
    .a5s-assign-panel .a5s-layout-grid { grid-template-columns:repeat(auto-fill, minmax(9.25rem, 10.25rem)); justify-content:flex-start; gap:.55rem; }
    .a5s-layout-card { display:grid; gap:.55rem; width:100%; padding:.55rem; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--menu-bg); color:inherit; text-align:left; cursor:pointer; }
    .a5s-assign-panel .a5s-layout-card { gap:.35rem; padding:.38rem; border-radius: 0.26rem; }
    .a5s-layout-card:hover, .a5s-layout-card.is-selected { border-color:var(--moss); }
    .a5s-layout-thumb { position:relative; aspect-ratio:16 / 10; overflow:hidden; border-radius: 0.25rem; background:var(--panel-soft); }
    .a5s-assign-panel .a5s-layout-thumb { aspect-ratio:16 / 9; border-radius: 0.25rem; }
    .a5s-layout-thumb img { width:100%; height:100%; object-fit:cover; }
    .a5s-layout-off { position:absolute; top:.45rem; left:.45rem; display:inline-flex; padding:.12rem .48rem; border-radius:999px; background:rgb(217 138 128 / 88%); color:#fff; font-size:.68rem; font-weight:700; }
    .a5s-layout-meta { display:grid; gap:.1rem; min-width:0; padding:0 .1rem .1rem; }
    .a5s-layout-meta strong { color:var(--light-text); font-size:.88rem; line-height:1.35; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .a5s-assign-panel .a5s-layout-meta strong { font-size:.78rem; }
    .a5s-layout-meta small { color:var(--muted-light); font-size:.74rem; }
    .a5s-assign-panel .a5s-layout-meta small { font-size:.66rem; line-height:1.35; }
    .a5s-layout-detail-empty { padding:1.15rem; text-align:center; color:var(--muted-light); border:1px dashed var(--line-light); border-radius: 0.32rem; font-size:.84rem; }
    .a5s-layout-detail[hidden] { display:none; }
    .a5s-layout-detail { display:grid; gap:.7rem; padding:.85rem; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--menu-bg); }
    .a5s-layout-detail-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.8rem; }
    .a5s-layout-detail-head h3 { margin:0 0 .15rem; color:var(--light-text); font-size:.95rem; }
    .a5s-layout-detail-head p { margin:0; color:var(--muted-light); font-size:.78rem; }
    .a5s-layout-close { flex:none; padding:.35rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:var(--muted-light); cursor:pointer; font-size:.78rem; }
    .a5s-layout-close:hover { border-color:var(--moss); color:var(--moss); }
    .a5s-layout-body { display:grid; gap:.55rem; }
    .a5s-point-row { display:grid; grid-template-columns:minmax(10rem, .85fr) minmax(16rem, 1.75fr) minmax(13rem, 1fr); gap:.7rem; align-items:start; padding:.72rem; border:1px solid var(--line-light); border-radius: 0.27rem; background:var(--panel-soft); }
    .a5s-point-title { display:flex; align-items:flex-start; gap:.55rem; min-width:0; }
    .a5s-point-title strong { display:block; font-size:.88rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .a5s-point-code { min-width:2.1rem; height:2.1rem; display:grid; place-items:center; border-radius: 0.25rem; background:rgb(91 141 239 / 16%); color:var(--moss); font-weight:700; font-size:.82rem; }
    .a5s-point-off { color:#d98a80; font-size:.72rem; font-weight:700; }
    .a5s-point-assignees { display:flex; flex-wrap:wrap; gap:.35rem; align-items:center; min-height:2.15rem; }
    .a5s-slot-label { display:block; margin:.45rem 0 .2rem; color:var(--muted-light); font-size:.68rem; font-weight:800; }
    .a5s-scope-tag { padding:.02rem .4rem; border:1px dashed var(--line-light); border-radius:999px; color:var(--muted-light); font-size:.6rem; font-weight:700; }
    .a5s-chip.a5s-mini-chip { margin:0; max-width:100%; padding:.32rem .45rem .32rem .32rem; border-radius: 0.26rem; }
    .a5s-mini-chip .meta { min-width:0; }
    .a5s-mini-chip .meta strong { max-width:11rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5s-mini-chip .meta small { max-width:16rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5s-point-search { position:relative; min-width:12rem; }
    .a5s-point-search input { width:100%; padding:.55rem .75rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; }
    .a5s-point-search input:focus { outline:none; border-color:var(--moss); }
    .a5s-point-search .a5s-results { width:100%; }
    .a5s-empty { padding:1rem; text-align:center; color:var(--muted-light); border:1px dashed var(--line-light); border-radius: 0.28rem; font-size:.84rem; }

    .a5s-pos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(15rem,1fr)); gap:.3rem .8rem; max-height:24rem; overflow:auto; padding:.3rem; border:1px solid var(--line-light); border-radius: 0.25rem; }
    .a5s-pos-grid label { display:flex; align-items:center; gap:.5rem; padding:.3rem .2rem; font-size:.85rem; cursor:pointer; }
    .a5s-pos-grid small { color:var(--muted-light); }
    .a5s-toolbar { display:flex; gap:.6rem; align-items:center; margin:.8rem 0; flex-wrap:wrap; }
    .a5s-note-inline { color:var(--muted-light); font-size:.78rem; }
    .image-viewer { z-index:2400; }

    @media (max-width: 820px) {
      .a5s-point-row { grid-template-columns:1fr; }
      .a5s-layout-grid { grid-template-columns:1fr; }
      .a5s-assign-panel .a5s-layout-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
      .a5s-area-summary { grid-template-columns:auto minmax(0,1fr) auto; }
      .a5s-area-count { grid-column:2; grid-row:2; }
      .a5s-area-arrow { grid-column:3; grid-row:1 / span 2; }
      .a5s-panel-head { padding:1rem; }
      .a5s-panel-body { padding:0 1rem 1rem; }
    }
    @media (prefers-reduced-motion:reduce) { .a5s-area-arrow { transition:none; } }
@endsection

@section('content')
  {{-- ===== บทบาทผู้ใช้ (admin / allocator) ===== --}}
  <details class="a5s-panel" data-members-panel>
    <summary class="a5s-panel-head">
      <div>
        <h2 data-i18n="a5s.settings.title">สิทธิ์ผู้ใช้งาน</h2>
        <p class="hint" data-i18n="a5s.settings.memberHint">ค้นหาพนักงาน เลือกบทบาท แล้วเพิ่ม — คนเดียวมีได้หลายบทบาท · admin ของ Insight เป็นผู้ดูแลระบบนี้อยู่แล้วโดยอัตโนมัติ</p>
      </div>
      <span class="a5s-fold-icon" aria-hidden="true"></span>
    </summary>

    <div class="a5s-panel-body">
      <div class="a5s-add-row">
        <select class="a5s-role-select" data-role-select>
          <option value="allocator" data-i18n="a5s.settings.roleAllocatorOption">ผู้จัดสรรพื้นที่ (Allocator)</option>
          <option value="admin" data-i18n="a5s.settings.roleAdminOption">ผู้ดูแลระบบ (Admin)</option>
        </select>
        <div class="a5s-search" data-user-search>
          <input type="text" data-i18n-placeholder="a5s.settings.searchAdd" placeholder="ค้นหาพนักงาน (รหัส / ชื่อ) เพื่อเพิ่มบทบาทที่เลือก" data-search-input>
          <div class="a5s-results" data-search-results></div>
        </div>
      </div>

      @foreach ($roleLabels as $role => $label)
        <div class="a5s-role-group" data-role-group="{{ $role }}">
          <h4 data-i18n="{{ $roleLabelKeys[$role] ?? '' }}">{{ $label }}</h4>
          <div data-role-list="{{ $role }}">
            @forelse ($byRole->get($role, collect()) as $m)
              <span class="a5s-chip" data-member-id="{{ $m['id'] }}">
                @if ($m['avatar'])<img src="{{ $m['avatar'] }}" alt="">@else<span class="av">@include('assessment.partials.default-avatar')</span>@endif
                <span class="meta">
                  <strong data-a5s-name-th="{{ $m['name_th'] ?? $m['name'] }}" data-a5s-name-en="{{ $m['name_en'] ?? ($m['name_th'] ?? $m['name']) }}" data-a5s-name-my="{{ $m['name_my'] ?? ($m['name_en'] ?? ($m['name_th'] ?? $m['name'])) }}">{{ $m['name'] }}</strong>
                  <small>{{ $m['code'] }}@if ($m['position']) · <span data-a5s-position-th="{{ $m['position_th'] ?? $m['position'] }}" data-a5s-position-en="{{ $m['position_en'] ?? ($m['position_th'] ?? $m['position']) }}" data-a5s-position-my="{{ $m['position_my'] ?? ($m['position_en'] ?? ($m['position_th'] ?? $m['position'])) }}">{{ $m['position'] }}</span>@endif</small>
                </span>
                <button type="button" data-i18n-title="a5s.settings.revoke" title="ถอนสิทธิ์" data-remove-member>&times;</button>
              </span>
            @empty
              <span class="a5s-note-inline" data-empty-role="{{ $role }}" data-i18n="a5s.settings.none">ยังไม่มี</span>
            @endforelse
          </div>
        </div>
      @endforeach
    </div>
  </details>

  {{-- ===== ผู้รับผิดชอบรายจุด ===== --}}
  <details class="a5s-panel a5s-assign-panel" data-assignment-panel>
    <summary class="a5s-panel-head">
      <div>
        <h2 data-i18n="a5s.settings.assignTitle">ผู้รับผิดชอบรายจุด</h2>
        <p class="hint" data-i18n="a5s.settings.assignHint">เลือกพื้นที่ย่อยหรืออาคาร แล้วเลือกพื้นที่ที่เรียงตามชั้นเพื่อจัดการผู้รับผิดชอบรายจุด</p>
      </div>
      <span class="a5s-fold-icon" aria-hidden="true"></span>
    </summary>

    <div class="a5s-panel-body">
      <div class="a5s-assign-list">
        @if (count($assignmentAreas))
          <div class="a5s-area-list">
            @foreach ($assignmentAreas as $area)
              <details class="a5s-area-group" data-assignment-area="{{ $area['id'] }}">
                <summary class="a5s-area-summary">
                  <span class="a5s-area-dot" style="--area-color:{{ $area['color'] }}" aria-hidden="true"></span>
                  <span class="a5s-area-meta">
                    <strong @if ($area['id'] === 'unmapped') data-i18n="a5s.settings.unmappedArea" @endif>{{ $area['name'] }}</strong>
                    <small>
                      {{ $area['zone_name'] ?: '-' }}
                      @unless ($area['is_active']) · <span class="a5s-area-state" data-i18n="a5s.manage.disabled">ปิดใช้งาน</span> @endunless
                    </small>
                  </span>
                  <span class="a5s-area-count">
                    {{ number_format($area['floor_count']) }} <span data-i18n="a5s.plan.floorsUnit">ชั้น</span>
                    · {{ number_format($area['layout_count']) }} <span data-i18n="a5s.units.layout">พื้นที่</span>
                  </span>
                  <span class="a5s-area-arrow" aria-hidden="true"></span>
                </summary>

                <div class="a5s-area-body">
                  @if ($area['layout_count'] > 0)
                    @foreach ($area['floors'] as $floor)
                      <section class="a5s-floor-group">
                        <div class="a5s-floor-head">
                          <h3>
                            <span @if ($floor['id'] === 'unmapped') data-i18n="a5s.settings.unmappedFloor" @endif>{{ $floor['name'] }}</span>
                            @unless ($floor['is_active']) · <span class="a5s-area-state" data-i18n="a5s.manage.disabled">ปิดใช้งาน</span> @endunless
                          </h3>
                          <small>{{ number_format($floor['layout_count']) }} <span data-i18n="a5s.units.layout">พื้นที่</span></small>
                        </div>

                        @if ($floor['layout_count'] > 0)
                          <div class="a5s-layout-grid">
                            @foreach ($floor['layouts'] as $layout)
                              @php
                                $pointCount = count($layout['points']);
                                $assigneeCount = collect($layout['points'])->sum(fn ($point) => count($point['assignees']));
                              @endphp
                              <button type="button" class="a5s-layout-card" data-layout-select="{{ $layout['id'] }}">
                                <span class="a5s-layout-thumb">
                                  @if ($layout['image_path'])
                                    <img src="{{ asset('storage/'.$layout['image_path']) }}" alt="">
                                  @else
                                    <span class="a5s-empty" data-i18n="a5s.common.noImage">ไม่มีรูปภาพ</span>
                                  @endif
                                  @unless ($layout['is_active']) <span class="a5s-layout-off" data-i18n="a5s.manage.disabled">ปิดใช้งาน</span> @endunless
                                </span>
                                <span class="a5s-layout-meta">
                                  <strong>{{ $layout['name'] }}</strong>
                                  <small>{{ number_format($pointCount) }} <span data-i18n="a5s.units.point">จุด</span> · <span data-layout-assignee-count="{{ $layout['id'] }}">{{ number_format($assigneeCount) }}</span> <span data-i18n="a5s.units.personAssigned">คนที่มอบหมาย</span></small>
                                </span>
                              </button>
                            @endforeach
                          </div>
                        @endif
                      </section>
                    @endforeach

                    <div class="a5s-layout-detail-empty" data-layout-placeholder data-i18n="a5s.settings.pickLayout">เลือกพื้นที่เพื่อจัดการผู้รับผิดชอบตามจุด</div>

                    @foreach ($area['floors'] as $floor)
                      @foreach ($floor['layouts'] as $layout)
                        @php
                          $pointCount = count($layout['points']);
                          $assigneeCount = collect($layout['points'])->sum(fn ($point) => count($point['assignees']));
                        @endphp
            <section class="a5s-layout-detail" data-layout-detail="{{ $layout['id'] }}" hidden>
              <div class="a5s-layout-detail-head">
                <div>
                  <h3>{{ $layout['name'] }}</h3>
                  <p>{{ number_format($pointCount) }} <span data-i18n="a5s.units.point">จุด</span> · <span data-detail-assignee-count="{{ $layout['id'] }}">{{ number_format($assigneeCount) }}</span> <span data-i18n="a5s.units.personAssigned">คนที่มอบหมาย</span></p>
                </div>
                <button type="button" class="a5s-layout-close" data-layout-close data-i18n="a5s.settings.hideDetail">ซ่อนรายละเอียด</button>
              </div>

              <div class="a5s-layout-body">
                @forelse ($layout['points'] as $point)
                  <div class="a5s-point-row" data-point-row="{{ $point['id'] }}">
                    <div class="a5s-point-title">
                      <span class="a5s-point-code">{{ $point['code'] }}</span>
                      <div>
                        <strong>{{ $point['name'] }}</strong>
                        @unless ($point['is_active']) <span class="a5s-point-off" data-i18n="a5s.settings.pointClosed">ปิดจุด</span> @endunless
                      </div>
                    </div>

                    <small class="a5s-slot-label" data-i18n="a5s.settings.assigneesLabel">ผู้รับผิดชอบ</small>
                    <div class="a5s-point-assignees" data-point-assignees>
                      @forelse ($point['assignees'] as $assignee)
                        <span class="a5s-chip a5s-mini-chip" data-assignee-chip="{{ $assignee['id'] }}">
                          @if (! empty($assignee['avatar']))
                            <button type="button" class="av a5s-avatar-preview" data-image-preview data-image-src="{{ $assignee['avatar'] }}" data-image-alt="{{ $assignee['name'] }}"
                                    aria-label="ดูรูปโปรไฟล์ {{ $assignee['name'] }}" data-loc-attr="aria-label"
                                    data-loc-th="ดูรูปโปรไฟล์ {{ $assignee['name'] }}"
                                    data-loc-en="View profile photo of {{ $assignee['name'] }}"
                                    data-loc-my="{{ $assignee['name'] }} ၏ ပရိုဖိုင်ပုံကို ကြည့်ရန်">
                              <img src="{{ $assignee['avatar'] }}" alt="">
                            </button>
                          @else
                            <span class="av" aria-hidden="true">@include('assessment.partials.default-avatar')</span>
                          @endif
                          <span class="meta">
                            <strong data-a5s-name-th="{{ $assignee['name_th'] ?? $assignee['name'] }}" data-a5s-name-en="{{ $assignee['name_en'] ?? ($assignee['name_th'] ?? $assignee['name']) }}" data-a5s-name-my="{{ $assignee['name_my'] ?? ($assignee['name_en'] ?? ($assignee['name_th'] ?? $assignee['name'])) }}">{{ $assignee['name'] }}</strong>
                            <small>{{ $assignee['code'] }}@if ($assignee['position']) · <span data-a5s-position-th="{{ $assignee['position_th'] ?? $assignee['position'] }}" data-a5s-position-en="{{ $assignee['position_en'] ?? ($assignee['position_th'] ?? $assignee['position']) }}" data-a5s-position-my="{{ $assignee['position_my'] ?? ($assignee['position_en'] ?? ($assignee['position_th'] ?? $assignee['position'])) }}">{{ $assignee['position'] }}</span>@endif @if ($assignee['department']) · <span data-a5s-department-th="{{ $assignee['department_th'] ?? $assignee['department'] }}" data-a5s-department-en="{{ $assignee['department_en'] ?? ($assignee['department_th'] ?? $assignee['department']) }}" data-a5s-department-my="{{ $assignee['department_my'] ?? ($assignee['department_en'] ?? ($assignee['department_th'] ?? $assignee['department'])) }}">{{ $assignee['department'] }}</span>@endif</small>
                          </span>
                          <button type="button" data-i18n-title="a5s.settings.removeAssignee" title="ลบผู้รับผิดชอบ" data-remove-assignee>&times;</button>
                        </span>
                      @empty
                        <span class="a5s-note-inline" data-empty-assignees data-i18n="a5s.settings.noAssignees">ยังไม่มีผู้รับผิดชอบ</span>
                      @endforelse
                    </div>

                    <div class="a5s-point-search" data-point-search data-point-id="{{ $point['id'] }}">
                      <input type="text" data-i18n-placeholder="a5s.settings.searchAdd" placeholder="ค้นหาพนักงานเพื่อเพิ่ม" data-assignee-input>
                      <div class="a5s-results" data-assignment-results></div>
                    </div>

                    {{-- ผู้ประเมินของจุดนี้ (scope เจาะจุด + ทั้ง Layout) — แอดมินเพิ่ม/ลบได้เหมือนผู้รับผิดชอบ --}}
                    <small class="a5s-slot-label" data-i18n="a5s.settings.evaluatorsLabel">ผู้ประเมิน</small>
                    <div class="a5s-point-assignees" data-point-evaluators>
                      @forelse ($point['evaluators'] as $ev)
                        <span class="a5s-chip a5s-mini-chip" data-evaluator-chip="{{ $ev['id'] }}">
                          @if (! empty($ev['avatar']))
                            <button type="button" class="av a5s-avatar-preview" data-image-preview data-image-src="{{ $ev['avatar'] }}" data-image-alt="{{ $ev['name'] }}"
                                    aria-label="ดูรูปโปรไฟล์ {{ $ev['name'] }}" data-loc-attr="aria-label"
                                    data-loc-th="ดูรูปโปรไฟล์ {{ $ev['name'] }}"
                                    data-loc-en="View profile photo of {{ $ev['name'] }}"
                                    data-loc-my="{{ $ev['name'] }} ၏ ပရိုဖိုင်ပုံကို ကြည့်ရန်">
                              <img src="{{ $ev['avatar'] }}" alt="">
                            </button>
                          @else
                            <span class="av" aria-hidden="true">@include('assessment.partials.default-avatar')</span>
                          @endif
                          <span class="meta">
                            <strong data-a5s-name-th="{{ $ev['name_th'] ?? $ev['name'] }}" data-a5s-name-en="{{ $ev['name_en'] ?? ($ev['name_th'] ?? $ev['name']) }}" data-a5s-name-my="{{ $ev['name_my'] ?? ($ev['name_en'] ?? ($ev['name_th'] ?? $ev['name'])) }}">{{ $ev['name'] }}</strong>
                            <small>{{ $ev['code'] }}@if (! empty($ev['layout_wide'])) · <span class="a5s-scope-tag" data-i18n="a5s.settings.layoutWide">ทั้งพื้นที่</span>@endif</small>
                          </span>
                          <button type="button" data-i18n-title="a5s.settings.removeEvaluator" title="ลบผู้ประเมิน" data-remove-evaluator>&times;</button>
                        </span>
                      @empty
                        <span class="a5s-note-inline" data-empty-evaluators data-i18n="a5s.settings.noEvaluators">ยังไม่มีผู้ประเมิน</span>
                      @endforelse
                    </div>

                    <div class="a5s-point-search" data-point-search data-kind="evaluator" data-point-id="{{ $point['id'] }}">
                      <input type="text" data-i18n-placeholder="a5s.settings.searchAddEvaluator" placeholder="ค้นหาพนักงานเพื่อเพิ่มผู้ประเมิน" data-assignee-input>
                      <div class="a5s-results" data-assignment-results></div>
                    </div>
                  </div>
                @empty
                  <div class="a5s-empty" data-i18n="a5s.settings.noPointsInLayout">พื้นที่นี้ยังไม่มีจุด ให้ไปปักจุดในหน้าจัดการพื้นที่ก่อน</div>
                @endforelse
              </div>
            </section>
                      @endforeach
                    @endforeach
                  @else
                    <div class="a5s-empty" data-i18n="a5s.settings.noLayoutInArea">อาคารนี้ยังไม่มีพื้นที่</div>
                  @endif
                </div>
              </details>
            @endforeach
          </div>
        @else
          <div class="a5s-empty" data-i18n="a5s.common.noLayout">ยังไม่มีพื้นที่ในระบบ</div>
        @endif
      </div>
    </div>
  </details>

  {{-- ===== ตำแหน่งที่เข้าระบบได้ (เหมือน Assessment) ===== --}}
  <details class="a5s-panel">
    <summary class="a5s-panel-head">
      <div>
        <h2 data-i18n="a5s.settings.positionAccess">ตำแหน่งที่เข้าใช้ระบบได้</h2>
        <p class="hint" data-i18n="a5s.settings.positionHint">ติ๊กตำแหน่ง (ดึงจากข้อมูลพนักงาน Bplus) ที่อนุญาตให้เข้าระบบ — ถ้ายังไม่เคยบันทึก = อนุญาตทุกตำแหน่ง · ผู้ที่ถูกมอบหมายพื้นที่/เป็นผู้ตรวจ เข้าได้เสมอไม่ว่าตำแหน่งใด</p>
      </div>
      <span class="a5s-fold-icon" aria-hidden="true"></span>
    </summary>

    <div class="a5s-panel-body">
      <div class="a5s-toolbar">
        <button type="button" class="a5s-btn" data-pos-all data-i18n="a5s.settings.selectAll">เลือกทั้งหมด</button>
        <button type="button" class="a5s-btn" data-pos-none data-i18n="a5s.settings.clearAll">ไม่เลือก</button>
        <button type="button" class="a5s-btn primary" data-pos-save data-i18n="a5s.settings.savePosition">บันทึกตำแหน่ง</button>
        <span class="a5s-note-inline" data-pos-status></span>
      </div>

      @php $allowedArr = is_array($allowed) ? $allowed : null; @endphp
      <div class="a5s-pos-grid">
        @foreach ($positions as $p)
          <label>
            <input type="checkbox" value="{{ $p['code'] }}" data-pos-check
                   {{ $allowedArr === null || in_array($p['code'], $allowedArr, true) ? 'checked' : '' }}>
            <span><span data-a5s-position-th="{{ $p['name_th'] ?? $p['name'] }}" data-a5s-position-en="{{ $p['name_en'] ?? ($p['name_th'] ?? $p['name']) }}" data-a5s-position-my="{{ $p['name_my'] ?? ($p['name_en'] ?? ($p['name_th'] ?? $p['name'])) }}">{{ $p['name'] }}</span> <small>({{ $p['count'] }})</small></span>
          </label>
        @endforeach
      </div>
    </div>
  </details>

  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const J = (url, opt = {}) => fetch(url, Object.assign({ headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }, opt)).then(r => r.json());

      // ---- members (2 บทบาท) ----
      const roleSelect = document.querySelector('[data-role-select]');
      const searchInput = document.querySelector('[data-search-input]');
      const results = document.querySelector('[data-search-results]');
      const defaultAvatarSvg = `<svg viewBox="0 0 48 48" focusable="false" aria-hidden="true" shape-rendering="geometricPrecision"><path fill="currentColor" stroke="none" d="M24 5.5c-5.2 0-9.4 4.2-9.4 9.4 0 3.6 2 6.7 5 8.3-6.9 1.7-12.2 7.7-13.2 15.4-.2 1.5 1 2.9 2.5 2.9h30.2c1.5 0 2.7-1.4 2.5-2.9-1-7.7-6.3-13.7-13.2-15.4 3-1.6 5-4.7 5-8.3 0-5.2-4.2-9.4-9.4-9.4Z"></path></svg>`;
      const defaultAvatar = () => `<span class="av">${defaultAvatarSvg}</span>`;
      const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      }[ch]));
      const i18n = window.__portalLang || {};
      const t = (key, fallback, replacements = null) => i18n.text ? i18n.text(key, fallback, replacements) : fallback;
      const personName = person => i18n.name ? i18n.name(person, person?.name || person?.code || '-') : (person?.name || person?.code || '-');
      const personPosition = person => i18n.position ? i18n.position(person, person?.position || '') : (person?.position || '');
      const personDepartment = person => i18n.department ? i18n.department(person, person?.department || '') : (person?.department || '');
      const nameAttrs = person => `data-a5s-name-th="${esc(person.name_th || person.name || person.code || '')}" data-a5s-name-en="${esc(person.name_en || person.name_th || person.name || person.code || '')}" data-a5s-name-my="${esc(person.name_my || person.name_en || person.name_th || person.name || person.code || '')}"`;
      const avatarStatic = person => person.avatar ? `<span class="av"><img src="${esc(person.avatar)}" alt=""></span>` : defaultAvatar();
      const avatarPreview = person => person.avatar ? `<button type="button" class="av a5s-avatar-preview" data-image-preview data-image-src="${esc(person.avatar)}" data-image-alt="${esc(personName(person))}" aria-label="${esc(t('a5s.common.viewPhoto', 'ดูรูป'))} ${esc(personName(person))}"><img src="${esc(person.avatar)}" alt=""></button>` : defaultAvatar();
      const assigneeSubtitle = person => [person.code, personPosition(person), personDepartment(person)].filter(Boolean).map(esc).join(' · ');

      function chip(m) {
        const av = m.avatar ? `<img src="${esc(m.avatar)}" alt="">` : defaultAvatar();
        const el = document.createElement('span');
        el.className = 'a5s-chip';
        el.dataset.memberId = m.id;
        el.innerHTML = `${av}<span class="meta"><strong ${nameAttrs(m)}>${esc(personName(m))}</strong><small>${esc(m.code)}${personPosition(m) ? ' · ' + esc(personPosition(m)) : ''}</small></span><button type="button" title="${esc(t('a5s.settings.revoke', 'ถอนสิทธิ์'))}" data-remove-member>&times;</button>`;
        return el;
      }

      document.querySelector('[data-members-panel]').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove-member]');
        if (!btn) return;
        const chipEl = btn.closest('[data-member-id]');
        J(`{{ url('area5s/settings/members') }}/${chipEl.dataset.memberId}`, { method: 'DELETE' })
          .then(d => { if (d.ok) chipEl.remove(); });
      });

      let searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        searchTimer = setTimeout(() => {
          J(`{{ route('area5s.settings.users') }}?q=${encodeURIComponent(q)}`).then(d => {
            results.innerHTML = '';
            (d.users || []).forEach(u => {
              const av = u.avatar ? `<img src="${esc(u.avatar)}" alt="">` : defaultAvatar();
              const b = document.createElement('button');
              b.type = 'button';
              b.innerHTML = `${av}<span>${esc(personName(u))} <small style="color:var(--muted-light)">${esc(u.code)} · ${esc(personPosition(u) || '')}</small></span>`;
              b.onclick = () => {
                const role = roleSelect.value;
                J(`{{ route('area5s.settings.members.add') }}`, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                  body: JSON.stringify({ app_user_id: u.app_user_id, role }),
                }).then(res => {
                  if (res.ok) {
                    const list = document.querySelector(`[data-role-list="${res.member.role}"]`);
                    if (list && !list.querySelector(`[data-member-id="${res.member.id}"]`)) {
                      list.appendChild(chip(res.member));
                      list.querySelector(`[data-empty-role]`)?.remove();
                    }
                  }
                  results.classList.remove('show');
                  searchInput.value = '';
                });
              };
              results.appendChild(b);
            });
            results.classList.add('show');
          });
        }, 250);
      });
      document.addEventListener('click', e => { if (!e.target.closest('[data-user-search]')) results.classList.remove('show'); });

      // ---- point assignees ----
      const assignmentPanel = document.querySelector('[data-assignment-panel]');
      let assigneeTimer;

      function setSelectedLayout(layoutId) {
        if (!assignmentPanel) return;
        const id = String(layoutId);
        const selectedCard = Array.from(assignmentPanel.querySelectorAll('[data-layout-select]'))
          .find(card => card.dataset.layoutSelect === id);
        const selectedArea = selectedCard?.closest('[data-assignment-area]');
        assignmentPanel.querySelectorAll('[data-layout-select]').forEach(card => {
          card.classList.toggle('is-selected', card.dataset.layoutSelect === id);
        });
        assignmentPanel.querySelectorAll('[data-layout-detail]').forEach(detail => {
          detail.hidden = detail.dataset.layoutDetail !== id;
        });
        assignmentPanel.querySelectorAll('[data-layout-placeholder]').forEach(placeholder => {
          placeholder.hidden = placeholder.closest('[data-assignment-area]') === selectedArea;
        });
      }

      function clearSelectedLayout() {
        if (!assignmentPanel) return;
        assignmentPanel.querySelectorAll('[data-layout-select]').forEach(card => card.classList.remove('is-selected'));
        assignmentPanel.querySelectorAll('[data-layout-detail]').forEach(detail => detail.hidden = true);
        assignmentPanel.querySelectorAll('[data-layout-placeholder]').forEach(placeholder => {
          placeholder.hidden = false;
        });
      }

      function updateLayoutCounts(detail) {
        if (!detail || !assignmentPanel) return;
        const layoutId = detail.dataset.layoutDetail;
        const count = detail.querySelectorAll('[data-assignee-chip]').length.toLocaleString(document.documentElement.lang || 'th');
        assignmentPanel.querySelector(`[data-layout-assignee-count="${layoutId}"]`)?.replaceChildren(document.createTextNode(count));
        assignmentPanel.querySelector(`[data-detail-assignee-count="${layoutId}"]`)?.replaceChildren(document.createTextNode(count));
      }

      function assigneeChip(assignee) {
        const el = document.createElement('span');
        el.className = 'a5s-chip a5s-mini-chip';
        el.dataset.assigneeChip = assignee.id;
        el.innerHTML = `${avatarPreview(assignee)}<span class="meta"><strong ${nameAttrs(assignee)}>${esc(personName(assignee))}</strong><small>${assigneeSubtitle(assignee)}</small></span><button type="button" title="${esc(t('a5s.settings.removeAssignee', 'ลบผู้รับผิดชอบ'))}" data-remove-assignee>&times;</button>`;

        return el;
      }

      function ensureAssigneeEmpty(row) {
        const holder = row.querySelector('[data-point-assignees]');
        if (!holder || holder.querySelector('[data-assignee-chip]')) return;

        const empty = document.createElement('span');
        empty.className = 'a5s-note-inline';
        empty.dataset.emptyAssignees = 'true';
        empty.setAttribute('data-i18n', 'a5s.settings.noAssignees');
        empty.textContent = t('a5s.settings.noAssignees', 'ยังไม่มีผู้รับผิดชอบ');
        holder.appendChild(empty);
      }

      function evaluatorChip(evaluator) {
        const el = document.createElement('span');
        el.className = 'a5s-chip a5s-mini-chip';
        el.dataset.evaluatorChip = evaluator.id;
        el.innerHTML = `${avatarPreview(evaluator)}<span class="meta"><strong ${nameAttrs(evaluator)}>${esc(personName(evaluator))}</strong><small>${esc(evaluator.code || '')}</small></span><button type="button" title="${esc(t('a5s.settings.removeEvaluator', 'ลบผู้ประเมิน'))}" data-remove-evaluator>&times;</button>`;

        return el;
      }

      function ensureEvaluatorEmpty(row) {
        const holder = row.querySelector('[data-point-evaluators]');
        if (!holder || holder.querySelector('[data-evaluator-chip]')) return;

        const empty = document.createElement('span');
        empty.className = 'a5s-note-inline';
        empty.dataset.emptyEvaluators = 'true';
        empty.setAttribute('data-i18n', 'a5s.settings.noEvaluators');
        empty.textContent = t('a5s.settings.noEvaluators', 'ยังไม่มีผู้ประเมิน');
        holder.appendChild(empty);
      }

      function renderAssignmentResults(wrap, employees) {
        const box = wrap.querySelector('[data-assignment-results]');
        box.innerHTML = '';

        (employees || []).forEach(employee => {
          const button = document.createElement('button');
          button.type = 'button';
          button.dataset.pickEmployee = employee.code;
          button.innerHTML = `${avatarStatic(employee)}<span>${esc(personName(employee))} <small style="color:var(--muted-light)">${assigneeSubtitle(employee)}</small></span>`;
          box.appendChild(button);
        });

        box.classList.toggle('show', (employees || []).length > 0);
      }

      function searchPointEmployees(wrap) {
        const input = wrap.querySelector('[data-assignee-input]');
        const q = input.value.trim();

        J(`{{ route('area5s.employees.search') }}?q=${encodeURIComponent(q)}`)
          .then(res => renderAssignmentResults(wrap, res.employees || []));
      }

      assignmentPanel?.addEventListener('input', function (e) {
        const input = e.target.closest('[data-assignee-input]');
        if (!input) return;

        clearTimeout(assigneeTimer);
        const wrap = input.closest('[data-point-search]');
        assigneeTimer = setTimeout(() => searchPointEmployees(wrap), 250);
      });

      assignmentPanel?.addEventListener('focusin', function (e) {
        const input = e.target.closest('[data-assignee-input]');
        if (!input) return;

        clearTimeout(assigneeTimer);
        searchPointEmployees(input.closest('[data-point-search]'));
      });

      assignmentPanel?.addEventListener('click', function (e) {
        const layoutCard = e.target.closest('[data-layout-select]');
        if (layoutCard) {
          setSelectedLayout(layoutCard.dataset.layoutSelect);
          return;
        }

        if (e.target.closest('[data-layout-close]')) {
          clearSelectedLayout();
          return;
        }

        const pick = e.target.closest('[data-pick-employee]');
        if (pick) {
          const wrap = pick.closest('[data-point-search]');
          const row = pick.closest('[data-point-row]');
          const detail = row.closest('[data-layout-detail]');
          const isEvaluator = wrap.dataset.kind === 'evaluator';
          const holder = row.querySelector(isEvaluator ? '[data-point-evaluators]' : '[data-point-assignees]');
          const endpoint = isEvaluator
            ? `{{ url('area5s/points') }}/${wrap.dataset.pointId}/evaluators`
            : `{{ url('area5s/points') }}/${wrap.dataset.pointId}/assignees`;

          J(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ employee_code: pick.dataset.pickEmployee }),
          }).then(res => {
            if (!res.ok) {
              alert(res.message || (isEvaluator ? t('a5s.settings.addEvaluatorFailed', 'เพิ่มผู้ประเมินไม่สำเร็จ') : t('a5s.settings.addAssigneeFailed', 'เพิ่มผู้รับผิดชอบไม่สำเร็จ')));
              return;
            }

            if (isEvaluator) {
              holder.querySelector('[data-empty-evaluators]')?.remove();
              holder.appendChild(evaluatorChip(res.evaluator));
            } else {
              holder.querySelector('[data-empty-assignees]')?.remove();
              holder.appendChild(assigneeChip(res.assignee));
              updateLayoutCounts(detail);
            }
            wrap.querySelector('[data-assignee-input]').value = '';
            wrap.querySelector('[data-assignment-results]').classList.remove('show');
          });
          return;
        }

        const removeEvaluator = e.target.closest('[data-remove-evaluator]');
        if (removeEvaluator) {
          const chipEl = removeEvaluator.closest('[data-evaluator-chip]');
          const row = chipEl.closest('[data-point-row]');

          J(`{{ url('area5s/evaluators') }}/${chipEl.dataset.evaluatorChip}`, { method: 'DELETE' })
            .then(res => {
              if (!res.ok) { alert(res.message || t('a5s.settings.removeEvaluatorFailed', 'ลบผู้ประเมินไม่สำเร็จ')); return; }

              // scope ทั้ง Layout ถูกลบ = หายจากทุกจุดของ layout — โหลดใหม่ให้ตรงจริง
              const isLayoutWide = chipEl.querySelector('.a5s-scope-tag') !== null;
              chipEl.remove();
              ensureEvaluatorEmpty(row);
              if (isLayoutWide) window.location.reload();
            });
          return;
        }

        const remove = e.target.closest('[data-remove-assignee]');
        if (!remove) return;

        const chipEl = remove.closest('[data-assignee-chip]');
        const row = chipEl.closest('[data-point-row]');
        const detail = row.closest('[data-layout-detail]');

        J(`{{ url('area5s/assignees') }}/${chipEl.dataset.assigneeChip}`, { method: 'DELETE' })
          .then(res => {
            if (!res.ok) { alert(res.message || t('a5s.settings.removeAssigneeFailed', 'ลบผู้รับผิดชอบไม่สำเร็จ')); return; }

            chipEl.remove();
            ensureAssigneeEmpty(row);
            updateLayoutCounts(detail);
          });
      });

      document.addEventListener('click', e => {
        if (e.target.closest('[data-point-search]')) return;
        document.querySelectorAll('[data-assignment-results]').forEach(box => box.classList.remove('show'));
      });

      // ---- positions ----
      const status = document.querySelector('[data-pos-status]');
      const checks = () => Array.from(document.querySelectorAll('[data-pos-check]'));
      document.querySelector('[data-pos-all]').onclick = () => checks().forEach(c => c.checked = true);
      document.querySelector('[data-pos-none]').onclick = () => checks().forEach(c => c.checked = false);
      document.querySelector('[data-pos-save]').onclick = function () {
        const codes = checks().filter(c => c.checked).map(c => c.value);
        J(`{{ route('area5s.settings.positions') }}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({ job_codes: codes }),
        }).then(res => {
          status.textContent = res.ok ? `${t('a5s.common.saved', 'บันทึกแล้ว')} ✓` : t('a5s.common.error', 'ผิดพลาด');
          setTimeout(() => status.textContent = '', 2500);
        });
      };
    })();
  </script>
@endsection
