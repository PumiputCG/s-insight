@extends('layouts.portal')

@section('title', 'แก้ไขพื้นที่ — '.$layout->name)

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.editor.editLayout">แก้ไขพื้นที่</span>
@endsection

@section('page-style')
    .a5e-wrap { display:grid; gap:.9rem; width:min(100%, 90rem); margin:0 auto; }
    .a5e-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5e-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:600; text-decoration:none; }
    .a5e-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5e-btn.primary { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5e-btn.primary:hover { color:#fff; filter:brightness(1.06); }
    .a5e-btn.danger { background:#c8463a; border-color:#c8463a; color:#fff; }
    .a5e-btn.danger:hover { color:#fff; filter:brightness(1.06); }

    /* ---- หัวการ์ด: ตำแหน่งพื้นที่ (แสดงอย่างเดียว) + ชื่อ/คำอธิบาย/เปลี่ยนภาพ ---- */
    .a5e-meta { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-tint); padding:.7rem .9rem; display:grid; gap:.6rem; }
    .a5e-trail { display:flex; align-items:baseline; gap:.42rem; flex-wrap:wrap; line-height:1.25; }
    .a5e-trail .tr { display:inline-flex; align-items:baseline; gap:.3rem; }
    .a5e-trail .tr b { font-size:.8rem; font-weight:700; color:var(--light-text); }
    .a5e-trail .tr i { font-style:normal; font-size:.68rem; color:var(--muted-light); }
    .a5e-trail .tr.is-last b { color:var(--moss); }
    .a5e-trail .sep { color:var(--muted-light); font-size:.72rem; }
    .a5e-trail .tr.is-empty b { color:var(--muted-light); font-weight:600; }

    /* ชื่อพื้นที่ / คำอธิบาย: ใช้กริดเดียวกันเพื่อให้ label และช่องกรอกอยู่ในแนวที่ชัดเจน */
    .a5e-fields { display:grid; gap:.5rem; padding-top:.65rem; border-top:1px solid var(--line-light); }
    .a5e-field-row { display:grid; grid-template-columns:5.4rem minmax(0, 26rem); align-items:start; gap:.55rem; }
    .a5e-field-row > .lb { padding-top:.5rem; color:var(--muted-light); font-size:.75rem; font-weight:600; line-height:1.3; }
    .a5e-field-row > .lb::after { content:":"; }
    .a5e-fields .fv { width:100%; padding:.45rem .65rem; border:1px solid transparent; border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font:inherit; font-size:.85rem; line-height:1.5; }
    .a5e-fields textarea.fv { min-height:2.15rem; resize:none; overflow:hidden; }
    .a5e-field-row.f-name .fv { font-size:.95rem; font-weight:700; }
    .a5e-fields .fv:hover { border-color:var(--line-light); }
    .a5e-fields .fv:focus { outline:none; border-color:var(--moss); }
    .a5e-fields .fv.saved { border-color:var(--moss); }
    /* ปุ่มเปลี่ยนภาพ: อยู่ใน action row มุมขวาล่างของการ์ดตำแหน่ง */
    .a5e-meta-actions { min-width:0; display:flex; align-items:center; justify-content:flex-end; }
    .a5e-img-form { min-width:0; display:flex; align-items:center; justify-content:flex-end; gap:.45rem; }
    .a5e-file { position:relative; min-height:2.3rem; display:inline-flex; align-items:center; justify-content:center; gap:.42rem; padding:.45rem .75rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; font-size:.78rem; font-weight:700; line-height:1; white-space:nowrap; transition:border-color .18s ease, color .18s ease, background .18s ease; }
    .a5e-file:hover, .a5e-file:focus-within { border-color:var(--moss); background:var(--panel-soft); color:var(--moss); outline:none; }
    .a5e-file svg { width:1rem; height:1rem; fill:currentColor; }
    .a5e-file input[type="file"] { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
    .a5e-file-name { min-width:0; max-width:10rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.72rem; color:var(--moss); font-weight:600; }
    .a5e-img-form .a5e-btn { min-height:2.3rem; padding:.4rem .7rem; font-size:.74rem; border-radius: 0.25rem; }
    /* กัน display:inline-flex/…​ ทับ UA [hidden]{display:none} (บั๊กเดิม 2026-07-18) */
    .a5e-btn[hidden], .a5e-file-name[hidden] { display:none !important; }
    @media (max-width: 620px) {
      .a5e-meta { padding:.75rem; }
      .a5e-field-row { grid-template-columns:1fr; gap:.2rem; }
      .a5e-field-row > .lb { padding-top:0; }
      .a5e-img-form { max-width:100%; flex-wrap:wrap; }
      .a5e-file { min-height:2.75rem; }
      .a5e-file-name { max-width:min(10rem, 45vw); }
      .a5e-img-form .a5e-btn { min-height:2.75rem; }
    }

    .a5e-body { display:grid; grid-template-columns:minmax(0, 1fr) 22rem; gap:.9rem; align-items:start; }
    @media (max-width: 1000px) { .a5e-body { grid-template-columns:1fr; } }

    /* ---- ผืนภาพ + markers ---- */
    .a5e-stage-wrap { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-tint); padding:.8rem; }
    .a5e-stage-hint { margin:0 0 .6rem; color:var(--muted-light); font-size:.78rem; }
    .a5e-stage { position:relative; user-select:none; border-radius: 0.25rem; overflow:auto; background:var(--menu-bg); }
    .a5e-canvas { position:relative; width:100%; margin:0 auto; transition:width .18s ease; transform-origin:top center; }
    .a5e-stage img { display:block; width:100%; height:auto; pointer-events:none; }
    /* ซูม 5%–300% แบบเดียวกับ Layout viewer */
    .a5z-bar { display:flex; justify-content:flex-end; margin-bottom:.6rem; }
    .a5z-controls { display:inline-flex; align-items:center; gap:.18rem; padding:.18rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5z-btn { width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); cursor:pointer; font-size:.95rem; font-weight:800; }
    .a5z-btn:hover, .a5z-btn:focus-visible { background:var(--panel-soft); color:var(--moss); outline:none; }
    .a5z-btn:disabled { cursor:not-allowed; color:var(--muted-light); opacity:.45; }
    .a5z-value { min-width:3.35rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:var(--panel-soft); color:var(--muted-light); cursor:pointer; font-size:.74rem; font-weight:750; }
    .a5z-value:hover, .a5z-value:focus-visible { color:var(--moss); outline:none; }
    .a5e-marker { position:absolute; transform:translate(-50%, -50%); width:2.1rem; height:2.1rem; display:grid; place-items:center; border-radius:50%; background:var(--moss); color:#fff; font-weight:800; font-size:.82rem; border:2px solid #fff; box-shadow:0 3px 10px rgb(0 0 0 / 35%); touch-action:none; cursor:grab; }
    .a5e-marker.is-selected { background:#c8964a; transform:translate(-50%, -50%) scale(1.15); z-index:5; }
    .a5e-marker.is-off { background:#8a8a8a; opacity:.65; }
    .a5e-marker.is-dragging { cursor:grabbing; z-index:9; }

    /* ---- แผงจุดด้านขวา ---- */
    .a5e-side { display:grid; gap:.8rem; }
    .a5e-panel { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-tint); padding:.9rem 1rem; }
    .a5e-panel h3 { margin:0 0 .55rem; font-size:.9rem; color:var(--light-text); }
    .a5e-panel-title { display:flex; align-items:center; justify-content:space-between; gap:.7rem; margin-bottom:.55rem; }
    .a5e-panel-title h3 { margin:0; }
    .a5e-icon-btn { flex:none; width:2rem; height:2rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; font-size:1rem; line-height:1; font-weight:800; }
    .a5e-icon-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5e-icon-btn.danger:hover { border-color:#d98a80; color:#d98a80; }
    .a5e-add-btn { flex:none; display:inline-flex; align-items:center; gap:.28rem; height:2rem; padding:0 .7rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; cursor:pointer; font-size:.78rem; font-weight:700; line-height:1; }
    .a5e-add-btn:hover { color:#fff; filter:brightness(1.06); }
    .a5e-add-btn span[aria-hidden] { font-size:1rem; font-weight:800; }
    .a5e-plist { display:grid; gap:.3rem; max-height:14rem; overflow:auto; }
    .a5e-pitem { display:flex; align-items:center; gap:.5rem; padding:.4rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); cursor:pointer; font-size:.8rem; }
    .a5e-pitem:hover, .a5e-pitem.is-selected { border-color:var(--moss); }
    .a5e-pitem .pc { flex:none; width:1.5rem; height:1.5rem; display:grid; place-items:center; border-radius:50%; background:var(--moss); color:#fff; font-size:.68rem; font-weight:800; }
    .a5e-pitem.is-off .pc { background:#8a8a8a; }
    .a5e-pitem .pn { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5e-pitem small { margin-left:auto; color:var(--muted-light); flex:none; }
    .a5e-point-remove { flex:none; width:1.55rem; height:1.55rem; display:grid; place-items:center; border:1px solid #c8463a; border-radius: 0.25rem; background:#c8463a; color:#fff; cursor:pointer; font-size:.95rem; line-height:1; font-weight:800; }
    .a5e-point-remove:hover { border-color:#a9342b; background:#a9342b; color:#fff; }

    .a5e-form { display:grid; gap:.55rem; }
    .a5e-form label { display:grid; gap:.25rem; font-size:.74rem; color:var(--muted-light); font-weight:600; }
    .a5e-form input, .a5e-form textarea { padding:.45rem .6rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; }
    .a5e-form input[readonly] { color:var(--muted-light); cursor:default; }
    .a5e-form textarea { min-height:3.2rem; resize:vertical; }
    .a5e-form input:focus, .a5e-form textarea:focus { outline:none; border-color:var(--moss); }
    .a5e-form input.saved, .a5e-form textarea.saved { border-color:var(--moss); }
    .a5e-form-row { display:grid; grid-template-columns:5rem 1fr; gap:.55rem; }
    .a5e-form-actions { display:flex; gap:.5rem; margin-top:.3rem; flex-wrap:wrap; }
    .a5e-none { color:var(--muted-light); font-size:.8rem; }

    .a5e-chips { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:.45rem; }
    .a5e-chip { display:inline-flex; align-items:center; gap:.42rem; max-width:100%; padding:.28rem .45rem .28rem .32rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); font-size:.76rem; }
    .a5e-chip img, .a5e-chip .av, .a5e-results img, .a5e-results .av { width:1.55rem; height:1.55rem; flex:0 0 1.55rem; border-radius:50%; object-fit:cover; background:var(--line-light); color:var(--muted-light); display:inline-flex; align-items:center; justify-content:center; }
    .a5e-chip .av { border:1px solid var(--line-light); overflow:hidden; padding:0; }
    button.a5e-avatar-preview { cursor:zoom-in; appearance:none; transition:border-color .18s ease, transform .18s ease; }
    button.a5e-avatar-preview:hover, button.a5e-avatar-preview:focus-visible { border-color:var(--moss); transform:translateY(-1px); outline:none; }
    .a5e-chip .av svg, .a5e-results .av svg { width:62%; height:62%; fill:currentColor; opacity:.72; }
    .a5e-chip .meta, .a5e-results .meta { display:flex; flex-direction:column; min-width:0; line-height:1.12; }
    .a5e-chip .meta strong, .a5e-results .meta strong { max-width:10.5rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.74rem; font-weight:650; }
    .a5e-chip .meta small, .a5e-results .meta small { max-width:11rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--muted-light); font-size:.68rem; }
    .a5e-chip button[data-remove-assignee], .a5e-chip button[data-remove-evaluator] { width:1rem; height:1rem; display:inline-flex; align-items:center; justify-content:center; border:none; background:transparent; color:#d98a80; cursor:pointer; font-size:.9rem; line-height:1; padding:0; }
    .a5e-search { position:relative; }
    .a5e-search input { width:100%; padding:.5rem .65rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.82rem; }
    .a5e-results { position:absolute; z-index:40; width:100%; margin-top:.3rem; border:1px solid var(--line-light); border-radius: 0.25rem; overflow:hidden; max-height:14rem; overflow-y:auto; display:none; }
    .a5e-results.show { display:block; }
    .a5e-results button { display:flex; align-items:center; gap:.5rem; width:100%; text-align:left; padding:.45rem .6rem; border:none; border-bottom:1px solid var(--line-light); background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.78rem; }
    .a5e-results button:hover { background:var(--panel-soft); }
    .a5e-result-empty { display:block; padding:.55rem .65rem; background:var(--menu-bg); color:var(--muted-light); font-size:.78rem; }
    .image-viewer { z-index:2400; }
@endsection

@section('content')
  <div class="a5e-wrap">
    <div class="a5e-top">
      <a class="a5s-back nav-go" href="{{ $backUrl ?? route('area5s.manage') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
    </div>

    {{-- ข้อมูลพื้นที่: แถวบน = ตำแหน่งพื้นที่ (แสดงอย่างเดียว) · แถวล่าง = ชื่อ/คำอธิบาย/เปลี่ยนภาพ --}}
    @php
      $trail = $trail ?? [];
      $trailItems = array_values(array_filter([
        ['key' => 'a5s.editor.trailZone', 'label' => 'โซน', 'name' => $trail['zone'] ?? null],
        ['key' => 'a5s.editor.trailArea', 'label' => 'อาคาร', 'name' => $trail['area'] ?? null],
        ['key' => 'a5s.editor.trailFloor', 'label' => 'ชั้น', 'name' => $trail['floor'] ?? null],
      ], fn ($item) => filled($item['name'])));
    @endphp
    <div class="a5e-meta">
      <div class="a5e-trail" aria-label="ตำแหน่งพื้นที่" data-i18n-aria="a5s.common.layoutPosition">
        @forelse ($trailItems as $i => $item)
          @if ($i > 0)<span class="sep" aria-hidden="true">›</span>@endif
          <span class="tr @if($i === count($trailItems) - 1) is-last @endif">
            <i data-i18n="{{ $item['key'] }}">{{ $item['label'] }}</i>
            <b>{{ $item['name'] }}</b>
          </span>
        @empty
          <span class="tr is-empty"><b data-i18n="a5s.editor.trailNone">ยังไม่จัดพื้นที่</b></span>
        @endforelse
      </div>

      <div class="a5e-fields">
        <label class="a5e-field-row f-name">
          <span class="lb" data-i18n="a5s.editor.layoutName">ชื่อพื้นที่</span>
          <input class="fv" type="text" value="{{ $layout->name }}" data-layout-field="name" maxlength="191"
                 data-i18n-placeholder="a5s.editor.layoutName" placeholder="ชื่อพื้นที่">
        </label>
        {{-- คำอธิบาย --}}
        <div class="a5e-field-row f-desc">
          <span class="lb" data-i18n="a5s.editor.description">คำอธิบาย</span>
          <textarea class="fv" rows="1" data-layout-field="description" maxlength="2000" data-autogrow
                    data-i18n-placeholder="a5s.editor.description" placeholder="คำอธิบาย"
                    data-i18n-aria="a5s.editor.description" aria-label="คำอธิบาย">{{ $layout->description }}</textarea>
        </div>
      </div>

      {{-- ปุ่มเปลี่ยนภาพอยู่มุมขวาล่างของการ์ดตำแหน่ง --}}
      <div class="a5e-meta-actions">
        <form class="a5e-img-form" action="{{ route('area5s.layouts.image', $layout) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label class="a5e-file" data-i18n-title="a5s.editor.changeImage" title="เปลี่ยนภาพ">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h3.2l1.4-1.7A1 1 0 0 1 9.4 3h5.2a1 1 0 0 1 .8.3L16.8 5H20a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm8 3.5A4.75 4.75 0 1 0 16.75 13 4.75 4.75 0 0 0 12 8.5Zm0 2A2.75 2.75 0 1 1 9.25 13 2.75 2.75 0 0 1 12 10.5Z"/></svg>
            <span data-i18n="a5s.editor.changeImage">เปลี่ยนภาพ</span>
            <input type="file" name="image" accept="image/*" data-image-input data-i18n-aria="a5s.editor.changeImage" aria-label="เปลี่ยนภาพ" required>
          </label>
          <span class="a5e-file-name" data-image-name hidden></span>
          <button type="submit" class="a5e-btn primary" data-image-submit hidden data-i18n="a5s.editor.upload">อัปโหลด</button>
        </form>
      </div>
    </div>

    <div class="a5e-body">
      {{-- ผืนภาพ: ลาก marker = ย้าย · คลิก marker = เลือก --}}
      <div class="a5e-stage-wrap">
        <p class="a5e-stage-hint" data-i18n="a5s.editor.stageHint">กด + เพิ่มจุด · ลากเพื่อย้าย · คลิกเพื่อแก้ไข</p>
        <div class="a5z-bar">
          <div class="a5z-controls">
            <button type="button" class="a5z-btn" data-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">-</button>
            <button type="button" class="a5z-value" data-zoom-reset aria-label="รีเซ็ตซูม" data-i18n-aria="a5s.common.resetZoom">100%</button>
            <button type="button" class="a5z-btn" data-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
          </div>
        </div>
        <div class="a5e-stage" data-stage>
          <div class="a5e-canvas" data-canvas>
            <img src="{{ asset('storage/'.$layout->image_path) }}" alt="{{ $layout->name }}" draggable="false">
          </div>
        </div>
      </div>

      {{-- แผงข้าง --}}
      <div class="a5e-side">
        <div class="a5e-panel">
          <div class="a5e-panel-title">
            <h3><span data-i18n="a5s.editor.allPoints">จุดทั้งหมด</span> (<span data-point-count>0</span>)</h3>
            <button type="button" class="a5e-add-btn" data-add-point data-i18n-aria="a5s.editor.addPoint" aria-label="เพิ่มจุด"><span aria-hidden="true">+</span> <span data-i18n="a5s.work.add">เพิ่ม</span></button>
          </div>
          <div class="a5e-plist" data-point-list></div>
        </div>

        <div class="a5e-panel" data-point-form hidden>
          <h3><span data-i18n="a5s.editor.pointData">ข้อมูลจุด</span> <span data-form-code style="color:var(--moss)"></span></h3>
          <div class="a5e-form">
            <div class="a5e-form-row">
              <label><span data-i18n="a5s.editor.pointCode">รหัสจุด</span>
                <input type="text" data-point-code readonly>
              </label>
              <label><span data-i18n="a5s.editor.areaName">ชื่อจุด</span>
                <input type="text" data-pf="name" maxlength="191">
              </label>
            </div>

            <label data-i18n="a5s.editor.assigneeLabel">ผู้รับผิดชอบ</label>
            <div class="a5e-chips" data-assignee-chips></div>
            <div class="a5e-search" data-emp-search>
              <input type="text" data-i18n-placeholder="a5s.editor.searchPeople" placeholder="ค้นหาพนักงาน" data-emp-input>
              <div class="a5e-results" data-emp-results></div>
            </div>

            <label data-i18n="a5s.editor.evaluatorLabel">ผู้ประเมิน</label>
            <div class="a5e-chips" data-evaluator-chips></div>
            <div class="a5e-search" data-eval-search>
              <input type="text" data-i18n-placeholder="a5s.editor.searchPeople" placeholder="ค้นหาพนักงาน" data-eval-input>
              <div class="a5e-results" data-eval-results></div>
            </div>

            <div class="a5e-form-actions">
              <button type="button" class="a5e-btn danger" data-point-delete data-i18n="a5s.editor.deletePoint">ลบจุด</button>
              <button type="button" class="a5e-btn primary" data-point-done data-i18n="a5s.editor.done">เสร็จสิ้น</button>
            </div>
          </div>
        </div>
        <p class="a5e-none" data-no-selection data-i18n="a5s.editor.noSelection">เลือกจุดเพื่อแก้ไข</p>
      </div>
    </div>
  </div>

  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const J = (url, method = 'GET', body = null) => fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: body ? JSON.stringify(body) : null,
      }).then(r => r.json());

      const layoutId = @json($layout->id);
      let points = @json($points);
      let selectedId = null;

      const stage = document.querySelector('[data-stage]');
      const canvas = document.querySelector('[data-canvas]');
      const zoomIn = document.querySelector('[data-zoom-in]');
      const zoomOut = document.querySelector('[data-zoom-out]');
      const zoomReset = document.querySelector('[data-zoom-reset]');
      const list = document.querySelector('[data-point-list]');
      const form = document.querySelector('[data-point-form]');
      const noSel = document.querySelector('[data-no-selection]');
      const countEl = document.querySelector('[data-point-count]');
      const addPointBtn = document.querySelector('[data-add-point]');
      const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
      const i18n = window.__portalLang || {};
      const t = (key, fallback, replacements = null) => i18n.text ? i18n.text(key, fallback, replacements) : fallback;
      const personName = item => i18n.name ? i18n.name(item, item?.name || item?.code || '-') : (item?.name || item?.code || '-');
      const personPosition = item => i18n.position ? i18n.position(item, item?.position || '') : (item?.position || '');
      const personDepartment = item => i18n.department ? i18n.department(item, item?.department || '') : (item?.department || '');
      const defaultAvatarSvg = '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 26c6.08 0 11-4.92 11-11S30.08 4 24 4 13 8.92 13 15s4.92 11 11 11Zm0 4c-9.39 0-17 5.15-17 11.5A2.5 2.5 0 0 0 9.5 44h29a2.5 2.5 0 0 0 2.5-2.5C41 35.15 33.39 30 24 30Z"/></svg>';
      const avatarStaticHtml = item => item.avatar ? `<span class="av"><img src="${esc(item.avatar)}" alt=""></span>` : `<span class="av" aria-hidden="true">${defaultAvatarSvg}</span>`;
      const avatarPreviewHtml = item => item.avatar ? `<button type="button" class="av a5e-avatar-preview" data-image-preview data-image-src="${esc(item.avatar)}" data-image-alt="${esc(personName(item))}" aria-label="${esc(t('a5s.common.viewPhoto', 'ดูรูป'))} ${esc(personName(item))}"><img src="${esc(item.avatar)}" alt=""></button>` : `<span class="av" aria-hidden="true">${defaultAvatarSvg}</span>`;
      const staffSubtitle = item => [item.code, personPosition(item), personDepartment(item)].filter(Boolean).map(esc).join(' · ');
      const flash = el => { el.classList.add('saved'); setTimeout(() => el.classList.remove('saved'), 1200); };
      const byId = id => points.find(p => p.id === Number(id));

      // ---- ซูม 5%–300% แบบเดียวกับ Layout viewer ----
      let zoom = 1;
      const minZoom = .05; // ซูมออกได้ถึง 5% เพื่อดูภาพรวมแผนผังใหญ่
      const maxZoom = 3;
      // step หยาบเหนือ 50% / ละเอียดใต้ 50% — ไล่ลง 50→40→30→20→10→5
      const stepDown = z => z > .5 ? z - .25 : z - .1;
      const stepUp = z => z >= .5 ? z + .25 : z + .1;

      function setZoom(nextZoom) {
        const previous = zoom;
        const next = Math.min(maxZoom, Math.max(minZoom, Math.round(nextZoom * 100) / 100));
        const centerX = stage.scrollLeft + stage.clientWidth / 2;
        const centerY = stage.scrollTop + stage.clientHeight / 2;
        zoom = next;
        canvas.style.width = `${zoom * 100}%`;
        if (next !== previous) {
          stage.scrollLeft = centerX * (zoom / previous) - stage.clientWidth / 2;
          stage.scrollTop = centerY * (zoom / previous) - stage.clientHeight / 2;
        }
        zoomReset.textContent = `${Math.round(zoom * 100)}%`;
        zoomOut.disabled = zoom <= minZoom;
        zoomIn.disabled = zoom >= maxZoom;
      }

      zoomOut.addEventListener('click', () => setZoom(stepDown(zoom)));
      zoomIn.addEventListener('click', () => setZoom(stepUp(zoom)));
      zoomReset.addEventListener('click', () => setZoom(1));
      setZoom(1);

      function clearSelection() {
        selectedId = null;
        form.hidden = true;
        noSel.hidden = false;
        render();
      }

      // ---- วาด markers + รายการ ----
      function render() {
        stage.querySelectorAll('.a5e-marker').forEach(m => m.remove());
        points.forEach(p => {
          const m = document.createElement('div');
          m.className = 'a5e-marker' + (p.id === selectedId ? ' is-selected' : '') + (p.is_active ? '' : ' is-off');
          m.style.left = p.x + '%';
          m.style.top = p.y + '%';
          m.textContent = p.code;
          m.dataset.marker = p.id;
          canvas.appendChild(m);
        });
        list.innerHTML = '';
        points.forEach(p => {
          const it = document.createElement('div');
          it.className = 'a5e-pitem' + (p.id === selectedId ? ' is-selected' : '') + (p.is_active ? '' : ' is-off');
          it.dataset.pitem = p.id;
          const assigneeCount = (p.assignees || []).length;
          it.innerHTML = `<span class="pc">${esc(p.code)}</span><span class="pn">${esc(p.name)}</span><small>${assigneeCount ? assigneeCount + ' ' + t('a5s.editor.peopleUnit', 'คน') : t('a5s.editor.noPeople', 'ยังไม่มีคน')}</small><button type="button" class="a5e-point-remove" data-delete-point="${p.id}" aria-label="${esc(t('a5s.manage.delete', 'ลบ'))} ${esc(t('a5s.common.point', 'จุด'))} ${esc(p.code)}">-</button>`;
          list.appendChild(it);
        });
        countEl.textContent = points.length;
      }

      // ---- เลือกจุด → เติมฟอร์ม ----
      function select(id) {
        selectedId = id;
        const p = byId(id);
        if (!p) { form.hidden = true; noSel.hidden = false; render(); return; }
        form.hidden = false;
        noSel.hidden = true;
        form.querySelector('[data-form-code]').textContent = p.code;
        form.querySelector('[data-point-code]').value = p.code;
        form.querySelector('[data-pf="name"]').value = p.name;
        renderChips(p);
        render();
      }

      function renderChips(p) {
        renderPersonChips(
          form.querySelector('[data-assignee-chips]'),
          p.assignees || [],
          t('a5s.editor.noAssignees', 'ยังไม่มีผู้รับผิดชอบ'),
          'data-remove-assignee'
        );
        renderPersonChips(
          form.querySelector('[data-evaluator-chips]'),
          p.evaluators || [],
          t('a5s.editor.noEvaluators', 'ยังไม่มีผู้ประเมิน'),
          'data-remove-evaluator'
        );
      }

      function renderPersonChips(box, people, emptyText, removeAttr) {
        box.innerHTML = '';
        if (!people.length) {
          box.innerHTML = `<span class="a5e-none">${emptyText}</span>`;
          return;
        }
        people.forEach(a => {
          const c = document.createElement('span');
          c.className = 'a5e-chip';
          c.innerHTML = `${avatarPreviewHtml(a)}<span class="meta"><strong>${esc(personName(a))}</strong><small>${staffSubtitle(a)}</small></span><button type="button" ${removeAttr}="${a.id}" title="${esc(t('a5s.manage.delete', 'ลบ'))}">&times;</button>`;
          box.appendChild(c);
        });
      }

      function nextPointPosition() {
        const index = points.length;
        const x = Math.min(88, Math.max(12, 50 + ((index % 5) - 2) * 7));
        const y = Math.min(88, Math.max(12, 50 + ((Math.floor(index / 5) % 5) - 2) * 7));
        return { x, y };
      }

      function addPointAt(x, y) {
        J(`{{ route('area5s.points.store', $layout) }}`, 'POST', { x, y }).then(res => {
          if (!res.ok) { alert(res.message || t('a5s.editor.addPointFailed', 'เพิ่มจุดไม่สำเร็จ')); return; }
          points.push(res.point);
          select(res.point.id);
        });
      }

      addPointBtn.addEventListener('click', () => {
        const pos = nextPointPosition();
        addPointAt(pos.x, pos.y);
      });

      // ---- ลาก marker = ย้าย / คลิก marker = เลือก ----
      let drag = null;   // { id, el, moved }
      stage.addEventListener('pointerdown', e => {
        const marker = e.target.closest('[data-marker]');
        if (!marker) return;
        drag = { id: Number(marker.dataset.marker), el: marker, moved: false };
        marker.classList.add('is-dragging');
        marker.setPointerCapture(e.pointerId);
      });
      stage.addEventListener('pointermove', e => {
        if (!drag) return;
        // คำนวณ % เทียบ canvas (ผืนภาพจริง) ไม่ใช่ stage — พิกัดถูกต้องทุกระดับซูม
        const r = canvas.getBoundingClientRect();
        const x = Math.min(100, Math.max(0, ((e.clientX - r.left) / r.width) * 100));
        const y = Math.min(100, Math.max(0, ((e.clientY - r.top) / r.height) * 100));
        drag.el.style.left = x + '%';
        drag.el.style.top = y + '%';
        drag.moved = true;
        drag.x = x; drag.y = y;
      });
      stage.addEventListener('pointerup', e => {
        if (drag) {
          const d = drag;
          drag.el.classList.remove('is-dragging');
          drag = null;
          if (d.moved) {
            const p = byId(d.id);
            J(`{{ url('area5s/points') }}/${d.id}`, 'PUT', { x: d.x, y: d.y }).then(res => {
              if (res.ok && p) { p.x = d.x; p.y = d.y; }
            });
            return;   // ลากแล้วไม่ถือว่าเป็นคลิกเลือก
          }
          select(d.id);
          return;
        }
      });

      // ---- รายการจุด ----
      list.addEventListener('click', e => {
        const del = e.target.closest('[data-delete-point]');
        if (del) {
          e.stopPropagation();
          const id = Number(del.dataset.deletePoint);
          const p = byId(id);
          if (!p || !confirm(t('a5s.editor.deletePointConfirm', `ลบจุด ${p.code}?`, { code: p.code }))) return;
          J(`{{ url('area5s/points') }}/${id}`, 'DELETE').then(res => {
            if (!res.ok) { alert(res.message || t('a5s.editor.deletePointFailed', 'ลบจุดไม่สำเร็จ')); return; }
            points = points.filter(item => item.id !== id);
            if (selectedId === id) {
              clearSelection();
            } else {
              render();
            }
          });
          return;
        }
        const it = e.target.closest('[data-pitem]');
        if (it) select(Number(it.dataset.pitem));
      });

      // ---- ฟอร์มจุด: บันทึกอัตโนมัติ ----
      function savePointField(inp, pointId = selectedId) {
        if (!inp || !pointId) return Promise.resolve({ ok: true });
        const field = inp.dataset.pf;
        const id = Number(pointId);

        return J(`{{ url('area5s/points') }}/${id}`, 'PUT', { [field]: inp.value }).then(res => {
          if (!res.ok) return res;
          const p = byId(id);
          if (p) {
            if (field === 'name') p.name = res.point.name;
          }
          flash(inp);
          render();

          return res;
        });
      }

      form.addEventListener('change', e => {
        const inp = e.target.closest('[data-pf]');
        if (!inp || !selectedId) return;
        savePointField(inp).then(res => {
          if (!res.ok) alert(res.message || t('a5s.editor.saveFailed', 'บันทึกไม่สำเร็จ'));
        });
      });

      // ---- ลบจุดที่เลือก ----
      form.querySelector('[data-point-delete]').addEventListener('click', () => {
        if (!selectedId) return;
        const p = byId(selectedId);
        if (!p || !confirm(t('a5s.editor.deletePointConfirm', `ลบจุด ${p.code}?`, { code: p.code }))) return;
        J(`{{ url('area5s/points') }}/${selectedId}`, 'DELETE').then(res => {
          if (!res.ok) { alert(res.message || t('a5s.editor.deletePointFailed', 'ลบจุดไม่สำเร็จ')); return; }
          points = points.filter(item => item.id !== selectedId);
          clearSelection();
        });
      });
      form.querySelector('[data-point-done]').addEventListener('click', () => {
        if (!selectedId) return;
        const pointId = selectedId;
        const fields = Array.from(form.querySelectorAll('[data-pf]'));

        Promise.all(fields.map(inp => savePointField(inp, pointId))).then(results => {
          const failed = results.find(res => !res.ok);
          if (failed) {
            alert(failed.message || t('a5s.editor.saveFailed', 'บันทึกไม่สำเร็จ'));
            return;
          }
          clearSelection();
        });
      });

      // ---- ค้นหาพนักงาน + เพิ่ม/ถอดผู้รับผิดชอบ ----
      const empInput = document.querySelector('[data-emp-input]');
      const empResults = document.querySelector('[data-emp-results]');
      const evalInput = document.querySelector('[data-eval-input]');
      const evalResults = document.querySelector('[data-eval-results]');
      let timer;
      function searchEmployees() {
        const q = empInput.value.trim();
        J(`{{ route('area5s.employees.search') }}?q=${encodeURIComponent(q)}`).then(d => {
          empResults.innerHTML = '';
          const rows = d.employees || [];
          if (!rows.length) {
            empResults.innerHTML = `<span class="a5e-result-empty">${esc(t('set.recruit.empty', 'ไม่พบพนักงาน'))}</span>`;
            empResults.classList.add('show');
            return;
          }
          rows.forEach(u => {
            const b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = `${avatarStaticHtml(u)}<span class="meta"><strong>${esc(personName(u))}</strong><small>${staffSubtitle(u)}</small></span>`;
            b.onclick = () => {
              if (!selectedId) return;
              J(`{{ url('area5s/points') }}/${selectedId}/assignees`, 'POST', { employee_code: u.code }).then(res => {
                if (!res.ok) { alert(res.message || t('a5s.editor.addAssigneeFailed', 'เพิ่มไม่สำเร็จ')); return; }
                const p = byId(selectedId);
                if (!p) return;
                p.assignees = p.assignees || [];
                p.assignees.push(res.assignee);
                renderChips(p);
                render();
                empResults.classList.remove('show');
                empInput.value = '';
              });
            };
            empResults.appendChild(b);
          });
          empResults.classList.add('show');
        });
      }
      empInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(searchEmployees, 250);
      });
      empInput.addEventListener('focus', searchEmployees);
      document.addEventListener('click', e => { if (!e.target.closest('[data-emp-search]')) empResults.classList.remove('show'); });

      let evalTimer;
      function searchEvaluators() {
        const q = evalInput.value.trim();
        J(`{{ route('area5s.employees.search') }}?q=${encodeURIComponent(q)}`).then(d => {
          evalResults.innerHTML = '';
          const rows = d.employees || [];
          if (!rows.length) {
            evalResults.innerHTML = `<span class="a5e-result-empty">${esc(t('set.recruit.empty', 'ไม่พบพนักงาน'))}</span>`;
            evalResults.classList.add('show');
            return;
          }
          rows.forEach(u => {
            const b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = `${avatarStaticHtml(u)}<span class="meta"><strong>${esc(personName(u))}</strong><small>${staffSubtitle(u)}</small></span>`;
            b.onclick = () => {
              if (!selectedId) return;
              J(`{{ url('area5s/points') }}/${selectedId}/evaluators`, 'POST', { employee_code: u.code }).then(res => {
                if (!res.ok) { alert(res.message || t('a5s.editor.addEvaluatorFailed', 'เพิ่มผู้ประเมินไม่สำเร็จ')); return; }
                const p = byId(selectedId);
                if (!p) return;
                p.evaluators = p.evaluators || [];
                p.evaluators.push(res.evaluator);
                renderChips(p);
                evalResults.classList.remove('show');
                evalInput.value = '';
              });
            };
            evalResults.appendChild(b);
          });
          evalResults.classList.add('show');
        });
      }
      evalInput.addEventListener('input', () => {
        clearTimeout(evalTimer);
        evalTimer = setTimeout(searchEvaluators, 250);
      });
      evalInput.addEventListener('focus', searchEvaluators);
      document.addEventListener('click', e => { if (!e.target.closest('[data-eval-search]')) evalResults.classList.remove('show'); });

      form.addEventListener('click', e => {
        const btn = e.target.closest('[data-remove-assignee]');
        if (btn && selectedId) {
          J(`{{ url('area5s/assignees') }}/${btn.dataset.removeAssignee}`, 'DELETE').then(res => {
            if (!res.ok) return;
            const p = byId(selectedId);
            p.assignees = (p.assignees || []).filter(a => a.id !== Number(btn.dataset.removeAssignee));
            renderChips(p);
            render();
          });
          return;
        }

        const evaluatorBtn = e.target.closest('[data-remove-evaluator]');
        if (!evaluatorBtn || !selectedId) return;
        J(`{{ url('area5s/evaluators') }}/${evaluatorBtn.dataset.removeEvaluator}`, 'DELETE').then(res => {
          if (!res.ok) return;
          const p = byId(selectedId);
          p.evaluators = (p.evaluators || []).filter(a => a.id !== Number(evaluatorBtn.dataset.removeEvaluator));
          renderChips(p);
        });
      });

      // ---- เปลี่ยนภาพ: ซ่อน input file ดิบ (ไม่โชว์ "No file chosen") แสดงชื่อไฟล์ + ปุ่มอัปโหลดเมื่อเลือกแล้ว ----
      const imageInput = document.querySelector('[data-image-input]');
      const imageName = document.querySelector('[data-image-name]');
      const imageSubmit = document.querySelector('[data-image-submit]');
      if (imageInput) {
        imageInput.addEventListener('change', () => {
          const file = imageInput.files && imageInput.files[0];
          imageName.textContent = file ? file.name : '';
          imageName.hidden = !file;
          imageSubmit.hidden = !file;
        });
      }

      // ---- ข้อมูลพื้นที่ (ชื่อ/คำอธิบาย) บันทึกอัตโนมัติ ----
      document.querySelectorAll('[data-layout-field]').forEach(inp => {
        inp.addEventListener('change', () => {
          J(`{{ route('area5s.layouts.update', $layout) }}`, 'PUT', { [inp.dataset.layoutField]: inp.value })
            .then(res => { if (res.ok) flash(inp); });
        });
      });

      // ---- คำอธิบาย: ยืดความสูงตามข้อความ (ไม่มี scrollbar ในช่อง) · Enter = จบการแก้ ไม่ขึ้นบรรทัดใหม่ ----
      document.querySelectorAll('[data-autogrow]').forEach(box => {
        const grow = () => { box.style.height = 'auto'; box.style.height = box.scrollHeight + 'px'; };
        box.addEventListener('input', grow);
        box.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); box.blur(); } });
        grow();
      });

      document.addEventListener('insight:languagechange', () => {
        const p = selectedId ? byId(selectedId) : null;
        if (p) renderChips(p);
        render();
      });

      render();
    })();
  </script>
@endsection
