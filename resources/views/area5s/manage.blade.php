@extends('layouts.portal')

@section('title', 'จัดการพื้นที่ 5S')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.manage.title">จัดการพื้นที่</span>
@endsection

@section('page-style')
    .a5m-wrap { display:grid; gap:.7rem; width:min(100%, 78rem); margin:0 auto; }
    .a5m-panel { border:1px solid var(--line-light); border-radius: 0.3rem; background:var(--panel-tint); padding:.85rem 1rem; }
    .a5m-panel h2 { margin:0 0 .3rem; font-size:1rem; color:var(--light-text); }
    .a5m-panel .hint { margin:0 0 .9rem; color:var(--muted-light); font-size:.8rem; }
    .a5m-create-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .a5m-page-head { display:grid; grid-template-columns:auto minmax(0, 1fr) auto; align-items:center; gap:.75rem; }
    .a5m-location { min-width:0; text-align:center; color:var(--light-text); font-size:.88rem; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5m-add-layout { justify-content:center; min-width:5.4rem; }
    .a5m-add-layout span[aria-hidden] { font-size:1.08rem; line-height:1; }
    @media (max-width:620px) {
      .a5m-page-head { grid-template-columns:1fr auto; }
      .a5m-location { grid-column:1 / -1; grid-row:1; }
    }

    .a5m-form { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(0,1.8fr) auto; gap:.7rem; align-items:start; margin-top:.9rem; }
    @media (max-width: 860px) { .a5m-form { grid-template-columns:1fr; } }
    .a5m-form label { display:grid; gap:.3rem; font-size:.78rem; color:var(--muted-light); font-weight:600; }
    .a5m-form input[type="text"], .a5m-form textarea, .a5m-form select { width:100%; padding:.55rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.85rem; }
    .a5m-form textarea { min-height:2.4rem; resize:vertical; }
    .a5m-form input:focus, .a5m-form textarea:focus, .a5m-form select:focus { outline:none; border-color:var(--moss); }
    .a5m-file { font-size:.8rem; }
    .a5m-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem 1rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.84rem; font-weight:600; text-decoration:none; }
    .a5m-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5m-btn.primary { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5m-btn.primary:hover { color:#fff; filter:brightness(1.06); }
    .a5m-btn.danger { background:#c8463a; border-color:#c8463a; color:#fff; }
    .a5m-btn.danger:hover { background:#a9342b; border-color:#a9342b; color:#fff; }
    .a5m-submit { align-self:end; }

    /* การ์ด Layout: แถวละ 4 (ซ้าย→ขวา) · ปุ่มมุมขวาล่าง */
    .a5m-list { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.6rem; align-items:start; }
    @media (max-width: 1180px) { .a5m-list { grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 900px) { .a5m-list { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) { .a5m-list { grid-template-columns:1fr; } }
    .a5m-list-panel { display:grid; gap:.75rem; }
    .a5m-section-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding-bottom:.55rem; border-bottom:1px solid var(--line-light); }
    .a5m-section-head .hint { margin-bottom:0; }
    .a5m-section-badge { flex:none; display:inline-flex; align-items:center; color:var(--moss); font-size:.86rem; font-weight:800; }
    /* หัวข้อคั่นตามชั้น — เรียงตามลำดับใน DB (a5s_floors.sort) · กินเต็มแถวของ grid */
    .a5m-floor-head { grid-column:1 / -1; display:flex; align-items:center; gap:.55rem; margin:.15rem 0 -.15rem; padding-bottom:.4rem; border-bottom:1px solid var(--line-light); }
    .a5m-floor-head:not(:first-child) { margin-top:.7rem; }
    .a5m-floor-head span { display:inline-flex; align-items:center; gap:.4rem; font-size:.86rem; font-weight:800; color:var(--light-text); }
    .a5m-floor-head span::before { content:""; width:.28rem; height:1rem; border-radius:2px; background:var(--moss); }
    .a5m-floor-head small { color:var(--muted-light); font-size:.72rem; font-weight:650; }

    .a5m-card { display:flex; flex-direction:column; min-width:0; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); overflow:hidden; transition:border-color .16s ease, box-shadow .16s ease; }
    .a5m-card:hover { border-color:color-mix(in srgb, var(--moss) 45%, var(--line-light)); box-shadow:0 6px 18px rgb(0 0 0 / 10%); }
    .a5m-card.is-new { border-color:var(--moss); }
    /* ภาพปก: ล็อกอัตราส่วนตายตัว + ครอบภาพ (padding-top hack กันเบราว์เซอร์ที่ไม่รองรับ aspect-ratio)
       → ภาพสูง/ยาวแค่ไหน การ์ดก็สูงเท่ากันทุกใบ */
    .a5m-thumb { position:relative; display:block; width:100%; height:0; padding-top:56.25%; overflow:hidden; background:var(--panel-soft); color:var(--muted-light); }
    .a5m-thumb img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; }
    /* จำนวนจุด: วงกลมมุมบนขวาของภาพ */
    .a5m-count { position:absolute; top:.4rem; right:.4rem; min-width:1.7rem; height:1.7rem; padding:0 .3rem; display:grid; place-items:center; border-radius:999px; border:1.5px solid rgb(255 255 255 / 78%); background:rgb(24 26 22 / 62%); color:#fff; font-size:.74rem; font-weight:800; line-height:1; box-shadow:0 2px 6px rgb(0 0 0 / 28%); }
    .a5m-meta { display:grid; gap:.12rem; min-width:0; padding:.5rem .6rem .15rem; }
    .a5m-meta strong { display:flex; align-items:baseline; gap:.3rem; min-width:0; color:var(--light-text); font-size:.85rem; }
    .a5m-meta strong .nm { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5m-meta small { display:flex; align-items:baseline; gap:.3rem; color:var(--light-text); font-size:.72rem; overflow:hidden; white-space:nowrap; }
    .a5m-meta i { flex:none; font-style:normal; font-weight:600; color:var(--muted-light); font-size:.7rem; }
    .a5m-meta i::after { content:":"; }
    .a5m-meta small span { min-width:0; overflow:hidden; text-overflow:ellipsis; }
    .a5m-off { flex:none; display:inline-flex; padding:.06rem .4rem; border-radius:999px; background:rgb(217 138 128 / 15%); color:#d98a80; font-size:.65rem; font-weight:700; }
    /* ปุ่มอยู่มุมขวาล่างของการ์ดเสมอ (margin-top:auto ดันลงล่างแม้ชื่อสั้น-ยาวไม่เท่ากัน) */
    .a5m-row-actions { display:flex; gap:.3rem; flex-wrap:wrap; justify-content:flex-end; margin-top:auto; padding:.45rem .6rem .55rem; }
    .a5m-row-actions .a5m-btn { padding:.3rem .6rem; font-size:.74rem; border-radius: 0.25rem; }
    .a5m-empty { grid-column:1 / -1; padding:1.6rem; text-align:center; color:var(--muted-light); font-size:.84rem; }
    .a5m-zone-map-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(15rem, 1fr)); gap:.75rem; margin-top:.85rem; }
    .a5m-zone-map-card { display:grid; gap:.65rem; border:1px solid var(--line-light); border-radius: 0.3rem; background:var(--menu-bg); padding:.65rem; }
    .a5m-zone-map-card.is-selected { border-color:color-mix(in srgb, var(--moss) 55%, var(--line-light)); box-shadow:0 0 0 3px rgb(91 141 239 / 12%); }
    .a5m-zone-map-thumb { width:100%; aspect-ratio:16/9; border-radius: 0.25rem; object-fit:cover; background:var(--panel-soft); border:1px solid var(--line-light); }
    .a5m-zone-map-placeholder { width:100%; aspect-ratio:16/9; border-radius: 0.25rem; display:grid; place-items:center; background:var(--panel-soft); border:1px dashed var(--line-strong); color:var(--muted-light); font-size:.8rem; }
    .a5m-zone-map-meta { display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; }
    .a5m-zone-map-meta strong { color:var(--light-text); font-size:.88rem; line-height:1.35; }
    .a5m-zone-map-meta small { flex:none; color:var(--muted-light); font-size:.72rem; }
    .a5m-area-list { display:flex; flex-wrap:wrap; gap:.45rem; }
    .a5m-area-pill { display:inline-flex; align-items:center; gap:.45rem; max-width:100%; padding:.38rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:var(--light-text); text-decoration:none; font-size:.78rem; font-weight:700; transition:transform .16s ease, border-color .16s ease, color .16s ease; }
    .a5m-area-pill:hover { transform:translateY(-1px); border-color:var(--moss); color:var(--moss); }
    .a5m-area-pill.is-selected { border-color:var(--moss); background:rgb(91 141 239 / 14%); color:var(--moss); }
    .a5m-area-pill span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5m-area-pill small { flex:none; color:inherit; opacity:.72; font-size:.68rem; }
    .a5m-empty-mini { display:inline-flex; align-items:center; min-height:2rem; color:var(--muted-light); font-size:.78rem; }
    .a5m-readonly-field { min-height:2.35rem; display:flex; align-items:center; padding:.55rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:color-mix(in srgb, var(--menu-bg) 88%, var(--panel-soft)); color:var(--light-text); font-size:.85rem; font-weight:700; }

    .a5m-modal[hidden] { display:none; }
    .a5m-modal { position:fixed; inset:0; z-index:1400; display:grid; place-items:center; padding:1rem; background:rgb(0 0 0 / 48%); }
    .a5m-modal-backdrop { position:absolute; inset:0; border:0; background:transparent; padding:0; cursor:pointer; }
    .a5m-dialog { position:relative; z-index:1; width:min(100%, 31rem); border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1.1rem 1.2rem; box-shadow:0 18px 54px rgb(0 0 0 / 32%); }
    .a5m-dialog-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.9rem; }
    .a5m-dialog-head h3 { margin:0 0 .2rem; font-size:1rem; color:var(--light-text); }
    .a5m-dialog-head p { margin:0; color:var(--muted-light); font-size:.8rem; }
    .a5m-close { width:2rem; height:2rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; font-size:1.1rem; }
    .a5m-close:hover { border-color:#d98a80; color:#d98a80; }
    .a5m-dialog .a5m-form { grid-template-columns:1fr; margin-top:0; }
    .a5m-dialog .a5m-form textarea { min-height:4rem; }
    .a5m-dialog-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
    .a5m-ok-dialog { text-align:center; width:min(100%, 30rem); }
    /* แจ้งเตือนสำเร็จ: วงกลมเขียว + ขีดถูกวาดเส้น (ชุดเดียวกับ .pt-ok-icon ใน portal — Manager 2026-08-27) */
    .a5m-ok-icon { width:3.6rem; height:3.6rem; margin:0 auto .8rem; border-radius:50%; background:color-mix(in srgb, var(--success) 16%, transparent); display:grid; place-items:center; animation:a5mOk .35s var(--ease-out, ease) .05s both; }
    .a5m-ok-icon svg { width:2rem; height:2rem; stroke:var(--success); stroke-dasharray:26; stroke-dashoffset:26; animation:a5mOkDraw .5s cubic-bezier(.65,0,.35,1) .2s forwards; }
    @keyframes a5mOk { from { transform:scale(.4); opacity:0; } to { transform:scale(1); opacity:1; } }
    @keyframes a5mOkDraw { to { stroke-dashoffset:0; } }
    @media (prefers-reduced-motion: reduce) {
      .a5m-ok-icon { animation:none; }
      .a5m-ok-icon svg { animation:none; stroke-dashoffset:0; }
    }
    .a5m-ok-dialog h3 { margin:0 0 .4rem; font-size:1.15rem; color:var(--light-text); }
    .a5m-ok-dialog p { margin:0 0 1.15rem; color:var(--muted-light); font-size:.86rem; line-height:1.6; }
    .a5m-ok-dialog .a5m-dialog-actions { justify-content:center; }
@endsection

@section('content')
  @php
    $legacyLayoutCreatedMessage = 'สร้าง Layout แล้ว กด "แก้ไข / ปักจุด" เพื่อกำหนดจุดพื้นที่';
    $a5mOkTitle = session('a5m_ok_title') ?: (session('success') === $legacyLayoutCreatedMessage ? 'สร้างพื้นที่แล้ว' : null);
    $a5mOkBody = session('a5m_ok_body') ?: (session('success') === $legacyLayoutCreatedMessage ? 'กด "แก้ไข" เพื่อปักจุดพื้นที่' : null);
    $selectedArea = $selectedArea ?? null;
    $showCreateForm = $errors->any() && ! empty($selectedArea);
  @endphp

  <div class="a5m-wrap">
    <div class="a5m-page-head">
      {{-- กลับไปหน้าเลือกโซนเสมอ เพราะหน้าเลือกโซนย่อยกลายเป็น modal บน /area5s/manage แล้ว (Manager 2026-07-22) --}}
      <a class="a5s-back nav-go" href="{{ route('area5s.manage') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
      @if (! empty($zone))
        <strong class="a5m-location">{{ $zone->name }} @if (! empty($selectedArea)) / {{ $selectedArea->name }} @endif</strong>
      @elseif (! empty($isUnmapped))
        <strong class="a5m-location" style="color:#c8964a" data-i18n="a5s.manage.unmappedTitle">พื้นที่ที่ยังไม่ผูกชั้น</strong>
      @endif
      @if (! empty($selectedArea) && ($floors ?? collect())->isNotEmpty())
        <button type="button" class="a5m-btn primary a5m-add-layout" data-create-open aria-expanded="{{ $showCreateForm ? 'true' : 'false' }}" aria-controls="a5m-create-modal"><span data-i18n="a5s.work.add">เพิ่ม</span> <span aria-hidden="true">+</span></button>
      @elseif (! empty($selectedArea))
        <span class="a5m-off" data-i18n="a5s.manage.noFloorInSubZone">ยังไม่มีชั้น</span>
      @else
        <span></span>
      @endif
    </div>

    {{-- สร้างพื้นที่ใหม่ผ่านปุ่ม action ด้านบน --}}
    @if (! empty($isUnmapped))
      <section class="a5m-panel">
        <p class="hint" style="margin:0" data-i18n="a5s.manage.unmappedNote">ยังไม่ได้ผูกชั้น — แก้ไขได้ตามปกติ ส่วนการผูกชั้นให้ Admin ทำที่หน้า mapping</p>
      </section>
    @endif

    <div id="a5m-create-modal" class="a5m-modal" data-create-modal {{ $showCreateForm ? '' : 'hidden' }} aria-hidden="{{ $showCreateForm ? 'false' : 'true' }}">
      <div class="a5m-dialog" role="dialog" aria-modal="true" aria-labelledby="a5m-create-title">
        <div class="a5m-dialog-head">
          <div>
            <h3 id="a5m-create-title" data-i18n="a5s.manage.createTitle">สร้างพื้นที่ใหม่</h3>
            <p data-i18n="a5s.manage.createModalHint">เลือกชั้น ตั้งชื่อ แล้วอัปโหลดภาพแผนผัง</p>
          </div>
          <button type="button" class="a5m-close" data-create-close aria-label="ปิด" data-i18n-aria="a5s.common.close">&times;</button>
        </div>
        <form class="a5m-form" action="{{ route('area5s.layouts.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          @if (! empty($zone))
            <input type="hidden" name="zone_id" value="{{ $zone->id }}">
          @endif
          @if ($selectedArea)
            <input type="hidden" name="zone_map_area_id" value="{{ $selectedArea->id }}">
          @endif
          {{-- โซน + อาคาร รวมเป็นบรรทัดเดียว (แสดงอย่างเดียว) — ลดช่องในฟอร์ม --}}
          @if (! empty($zone) || $selectedArea)
            <label><span data-i18n="a5s.manage.locationLabel">พื้นที่</span>
              <span class="a5m-readonly-field">{{ collect([$zone->name ?? null, $selectedArea->name ?? null])->filter()->implode(' / ') }}</span>
            </label>
          @endif
          <label><span data-i18n="a5s.manage.floor">เลือกชั้น</span>
            <select name="floor_id" required>
              <option value="" data-i18n="a5s.mapping.selectFloor">— เลือกชั้น —</option>
              @foreach (($floors ?? collect()) as $floor)
                @php $floorLabel = $floor->name; @endphp
                <option value="{{ $floor->id }}" {{ (string) old('floor_id') === (string) $floor->id ? 'selected' : '' }}>{{ $floorLabel }}</option>
              @endforeach
            </select>
          </label>
          <label><span data-i18n="a5s.manage.layoutName">ชื่อพื้นที่</span>
            <input type="text" name="name" required maxlength="191" data-i18n-placeholder="a5s.manage.layoutNamePlaceholder" placeholder="เช่น โรงงาน 1 ชั้นผลิต" value="{{ old('name') }}">
          </label>
          <label><span data-i18n="a5s.manage.description">คำอธิบาย (ไม่บังคับ)</span>
            <textarea name="description" maxlength="2000" data-i18n-placeholder="a5s.manage.descriptionPlaceholder" placeholder="ขอบเขตพื้นที่ / หมายเหตุ">{{ old('description') }}</textarea>
          </label>
          <label><span data-i18n="a5s.manage.image">ภาพแผนผัง</span>
            <input class="a5m-file" type="file" name="image" accept="image/*" required>
          </label>
          <div class="a5m-dialog-actions">
            <button type="button" class="a5m-btn" data-create-close data-i18n="a5s.manage.cancel">ยกเลิก</button>
            <button type="submit" class="a5m-btn primary" data-i18n="a5s.manage.createButton">+ สร้างพื้นที่</button>
          </div>
        </form>
      </div>
    </div>

    {{-- รายการพื้นที่ --}}
    @php
      $layoutSections = $isA5sAdmin
        ? [
          [
            'key' => 'admin',
            'title' => 'พื้นที่ทั้งหมด',
            'title_i18n' => 'a5s.manage.adminTitle',
            'hint' => 'เห็นและจัดการพื้นที่ที่ทุกคนสร้างไว้ในระบบ',
            'hint_i18n' => 'a5s.manage.adminHint',
            'layouts' => $allLayouts,
            'empty' => 'ยังไม่มีพื้นที่ในระบบ',
            'empty_i18n' => 'a5s.common.noLayout',
          ],
          [
            'key' => 'mine',
            'title' => 'พื้นที่ที่ฉันสร้าง',
            'title_i18n' => 'a5s.manage.mineTitle',
            'hint' => 'รายการที่บัญชีของคุณสร้างไว้เอง',
            'hint_i18n' => 'a5s.manage.mineHint',
            'layouts' => $myLayouts,
            'empty' => 'คุณยังไม่ได้สร้างพื้นที่',
            'empty_i18n' => 'a5s.manage.emptyMine',
          ],
        ]
        : [
          [
            'key' => 'mine',
            'title' => 'พื้นที่ของฉัน',
            'title_i18n' => 'a5s.manage.myLayoutTitle',
            'hint' => 'ผู้จัดสรรพื้นที่แก้ได้เฉพาะพื้นที่ที่ตัวเองสร้าง',
            'hint_i18n' => 'a5s.manage.myLayoutHint',
            'layouts' => $myLayouts ?? $layouts,
            'empty' => 'ยังไม่มีพื้นที่ — กดปุ่ม + เพื่อสร้าง',
            'empty_i18n' => 'a5s.manage.emptyFirst',
          ],
        ];
    @endphp

    @foreach ($layoutSections as $section)
      <section class="a5m-panel a5m-list-panel {{ $section['key'] === 'admin' ? 'is-admin' : 'is-mine' }}">
        <div class="a5m-section-head">
          <div>
            <h2><span data-i18n="{{ $section['title_i18n'] }}">{{ $section['title'] }}</span> ({{ $section['layouts']->count() }})</h2>
          </div>
          @if ($isA5sAdmin && $section['key'] === 'admin')
            <span class="a5m-section-badge" data-i18n="a5s.manage.adminView">Admin</span>
          @endif
        </div>

        <div class="a5m-list">
          @php $lastFloorId = '__init__'; @endphp
          @forelse ($section['layouts'] as $layout)
            {{-- หัวข้อคั่นตามชั้น (เรียงตาม a5s_floors.sort ใน DB) --}}
            @if (! empty($selectedArea) && $lastFloorId !== $layout->floor_id)
              @php $lastFloorId = $layout->floor_id; @endphp
              <div class="a5m-floor-head">
                <span>{{ $layout->floor?->name ?? '—' }}</span>
              </div>
            @endif
            <div class="a5m-card {{ (int) session('created_layout_id') === (int) $layout->id ? 'is-new' : '' }}" id="layout-{{ $section['key'] }}-{{ $layout->id }}" data-layout-row="{{ $layout->id }}">
              <a class="a5m-thumb nav-go" href="{{ route('area5s.layouts.show', $layout) }}" data-i18n-title="a5s.manage.viewLive" title="ดูหน้าจริง">
                <img src="{{ asset('storage/'.$layout->image_path) }}" alt="" loading="lazy">
                <span class="a5m-count" title="{{ number_format($layout->points_count) }} จุด">{{ number_format($layout->points_count) }}</span>
              </a>
              <div class="a5m-meta">
                <strong><i data-i18n="a5s.manage.nameLabel">ชื่อพื้นที่</i><span class="nm" title="{{ $layout->name }}">{{ $layout->name }}</span>@unless ($layout->is_active)<span class="a5m-off" data-i18n="a5s.manage.off">ปิด</span>@endunless</strong>
                @php $owner = $owners[$layout->created_by] ?? null; @endphp
                <small><i data-i18n="a5s.manage.updatedLabel">อัปเดต</i><span>{{ $layout->updated_at?->format('d/m/Y') ?: '-' }}</span></small>
                <small><i data-i18n="a5s.manage.addedByLabel">เพิ่มโดย</i><span @if (is_array($owner)) title="{{ $owner['name'] }}" data-a5s-name-th="{{ $owner['name_th'] ?? $owner['name'] }}" data-a5s-name-en="{{ $owner['name_en'] ?? ($owner['name_th'] ?? $owner['name']) }}" data-a5s-name-my="{{ $owner['name_my'] ?? ($owner['name_en'] ?? ($owner['name_th'] ?? $owner['name'])) }}" @endif>{{ is_array($owner) ? $owner['name'] : '-' }}</span></small>
              </div>
              <div class="a5m-row-actions">
                <a class="a5m-btn primary nav-go" href="{{ route('area5s.layouts.editor', $layout) }}" data-i18n="a5s.manage.editPin">แก้ไข</a>
                <button type="button" class="a5m-btn" data-toggle-layout="{{ $layout->id }}" data-i18n="{{ $layout->is_active ? 'a5s.manage.closeUse' : 'a5s.manage.openUse' }}">{{ $layout->is_active ? 'ปิด' : 'เปิด' }}</button>
                <button type="button" class="a5m-btn danger" data-delete-layout="{{ $layout->id }}" data-layout-name="{{ $layout->name }}" data-i18n="a5s.manage.delete">ลบ</button>
              </div>
            </div>
          @empty
            <div class="a5m-empty" data-i18n="{{ $section['empty_i18n'] }}">{{ $section['empty'] }}</div>
          @endforelse
        </div>
      </section>
    @endforeach
  </div>

  @if ($a5mOkTitle || $a5mOkBody)
    <div class="a5m-modal" data-ok-modal>
      <button type="button" class="a5m-modal-backdrop" data-ok-close aria-label="ปิด" data-i18n-aria="a5s.common.close"></button>
      <div class="a5m-dialog a5m-ok-dialog" role="alertdialog" aria-modal="true" aria-labelledby="a5m-ok-title" aria-describedby="a5m-ok-message">
        <div class="a5m-ok-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
        </div>
        <h3 id="a5m-ok-title" @if (($a5mOkTitle ?: '') === 'สร้างพื้นที่แล้ว') data-i18n="a5s.manage.createdTitle" @endif>{{ $a5mOkTitle ?: 'สำเร็จ' }}</h3>
        <p id="a5m-ok-message" @if ($a5mOkBody) data-i18n="a5s.manage.createdBody" @endif>{{ $a5mOkBody }}</p>
        <div class="a5m-dialog-actions">
          <button type="button" class="a5m-btn primary" data-ok-close data-i18n="a5s.manage.ok">ตกลง</button>
        </div>
      </div>
    </div>
  @endif

  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const i18n = window.__portalLang || {};
      const t = (key, fallback) => i18n.text ? i18n.text(key, fallback) : fallback;
      const createOpen = document.querySelector('[data-create-open]');
      const createModal = document.querySelector('[data-create-modal]');
      function setCreateModal(open) {
        if (!createModal || !createOpen) return;
        createModal.hidden = !open;
        createModal.setAttribute('aria-hidden', open ? 'false' : 'true');
        createOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) createModal.querySelector('input[name="name"]')?.focus();
      }
      createOpen?.addEventListener('click', () => setCreateModal(true));
      document.querySelectorAll('[data-create-close]').forEach(btn => btn.addEventListener('click', () => setCreateModal(false)));
      createModal?.addEventListener('click', e => { if (e.target === createModal) setCreateModal(false); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && createModal && !createModal.hidden) setCreateModal(false); });
      @if ($showCreateForm)
        setCreateModal(true);
      @endif

      const okModal = document.querySelector('[data-ok-modal]');
      if (okModal) {
        const closeOk = () => {
          okModal.remove();
          document.body.style.overflow = '';
        };
        document.body.style.overflow = 'hidden';
        okModal.querySelectorAll('[data-ok-close]').forEach(btn => btn.addEventListener('click', closeOk));
        document.addEventListener('keydown', e => {
          if (e.key === 'Escape' || e.key === 'Enter') closeOk();
        }, { once: true });
      }

      document.querySelectorAll('[data-delete-layout]').forEach(btn => {
        btn.addEventListener('click', () => {
          const name = btn.dataset.layoutName || 'พื้นที่';
          if (!confirm(`${t('a5s.manage.delete', 'ลบ')} ${name}?`)) return;
          fetch(`{{ url('area5s/layouts') }}/${btn.dataset.deleteLayout}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          }).then(r => r.json()).then(d => {
            if (!d.ok) { alert(d.message || `${t('a5s.manage.delete', 'ลบ')} ${t('a5s.units.layout', 'พื้นที่')} ${t('a5s.common.error', 'ผิดพลาด')}`); return; }
            location.reload();
          });
        });
      });

      document.querySelectorAll('[data-toggle-layout]').forEach(btn => {
        btn.addEventListener('click', () => {
          fetch(`{{ url('area5s/layouts') }}/${btn.dataset.toggleLayout}/toggle`, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
        });
      });
    })();
  </script>
@endsection
