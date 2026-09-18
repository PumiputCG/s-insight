@extends('layouts.portal')

@section('title', 'แปลนบริษัท — SUPAVUT 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.plan.title">แปลนบริษัท</span>
@endsection

@section('page-style')
    .a5p-wrap { display:grid; gap:.55rem; width:min(100%, 96rem); margin:0 auto; }
    .a5p-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5p-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:650; text-decoration:none; }
    .a5p-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5p-btn.primary { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5p-btn.primary:hover { color:#fff; filter:brightness(1.06); }
    /* รีเซ็ตโซน: ปุ่มพื้นขาว ตัวอักษรดำ — ใช้ตัวแปรธีมเพื่อให้ธีมมืดกลับสีเองอัตโนมัติ */
    .a5p-btn.warning { border-color:var(--line-strong); background:var(--menu-bg); color:var(--light-text); }
    .a5p-btn.warning:hover { border-color:var(--moss); background:var(--panel-soft); color:var(--moss); }
    .a5p-btn.danger { background:#c8463a; border-color:#c8463a; color:#fff; }
    .a5p-btn.danger:hover { color:#fff; filter:brightness(1.06); }
    .a5p-btn[disabled] { opacity:.5; cursor:not-allowed; }

    .a5p-upload { display:grid; gap:.7rem; place-items:center; padding:3rem 1.5rem; border:1px dashed var(--line-strong); border-radius: 0.36rem; background:var(--panel-soft); text-align:center; }
    .a5p-upload p { margin:0; color:var(--muted-light); font-size:.86rem; }
    .a5p-upload form { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; justify-content:center; }

    /* ปุ่มเลือกไฟล์ (ซ่อน input ดิบ ไม่ให้ขึ้น "No file chosen") */
    .a5p-file { position:relative; display:inline-flex; align-items:center; gap:.3rem; padding:.32rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; font-size:.74rem; font-weight:650; white-space:nowrap; }
    .a5p-file:hover { border-color:var(--moss); color:var(--moss); }
    .a5p-file input[type="file"] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
    .a5p-file-name { max-width:11rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.72rem; font-weight:650; color:var(--moss); }
    .a5p-file-name:empty { display:none; }

    /* แถบขั้นตอน: ทำให้เห็นลำดับงานชัด (ขั้นตอนเหมือนเดิม แค่บอกให้รู้ว่าอยู่ตรงไหน) */
    .a5p-steps { display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; padding:.42rem .6rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-tint); }
    .a5p-step { display:inline-flex; align-items:center; gap:.35rem; padding:.16rem .5rem; border-radius:999px; color:var(--light-text); font-size:.75rem; font-weight:700; }
    .a5p-step b { display:grid; place-items:center; width:1.2rem; height:1.2rem; border-radius:50%; background:var(--moss); color:#fff; font-size:.66rem; font-weight:800; }
    .a5p-steps .arw { color:var(--moss); font-size:.78rem; font-weight:800; }

    .a5p-body { display:grid; grid-template-columns:minmax(0, 1fr) 20rem; gap:.75rem; align-items:start; margin-top:-.2rem; }
    @media (max-width: 1000px) { .a5p-body { grid-template-columns:1fr; margin-top:0; } }

    .a5p-stage-wrap { border:1px solid var(--line-light); border-radius: 0.29rem; background:var(--panel-tint); padding:.55rem .65rem .65rem; }

    /* แถบซูม */
    .a5p-zoombar { display:flex; align-items:center; gap:.5rem; margin-bottom:.45rem; flex-wrap:wrap; }
    .a5p-zoom-ctrls { display:inline-flex; align-items:center; gap:.15rem; padding:.15rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5p-zoom-ctrls button { min-width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius: 0.25rem; background:transparent; color:var(--light-text); cursor:pointer; font-size:1rem; font-weight:800; padding:0 .3rem; }
    .a5p-zoom-ctrls button:hover { background:var(--panel-soft); color:var(--moss); }
    .a5p-zoom-val { min-width:3.4rem; font-size:.74rem; font-weight:750; }

    /* เวที: viewport ตัดขอบ + canvas ที่ transform (img+svg สเกลพร้อมกัน) */
    .a5p-stage { position:relative; overflow:hidden; border-radius: 0.25rem; background:var(--menu-bg); touch-action:none; cursor:grab; user-select:none; }
    .a5p-stage.is-draw { cursor:crosshair; }
    .a5p-stage.is-pan { cursor:grabbing; }
    .a5p-canvas { position:relative; width:100%; transform-origin:0 0; will-change:transform; }
    .a5p-canvas img { display:block; width:100%; height:auto; pointer-events:none; -webkit-user-drag:none; }
    .a5p-svg { position:absolute; inset:0; width:100%; height:100%; overflow:visible; }
    .a5p-poly { transition:opacity .12s ease; }
    .a5p-poly.is-clickable { cursor:pointer; }
    .a5p-poly:hover { opacity:.85; }
    .a5p-vertex { cursor:grab; }
    .a5p-label { font-family:"IBM Plex Sans Thai", "Noto Sans Thai", Arial, sans-serif; font-weight:600; letter-spacing:.01em; fill:#111; paint-order:stroke; stroke:rgb(255 255 255 / 86%); stroke-width:.32px; pointer-events:none; }
    .a5p-draw-hint { position:absolute; top:.6rem; left:.6rem; z-index:5; padding:.35rem .7rem; border-radius: 0.25rem; background:rgb(0 0 0 / 72%); color:#fff; font-size:.74rem; font-weight:650; pointer-events:none; max-width:calc(100% - 1.2rem); }
    /* พาเนลควบคุมการวาดอยู่ข้างรายการโซน เพื่อไม่ให้ pointer-capture ของแผนที่แย่งคลิก */
    .a5p-draw-actions[hidden] { display:none !important; }
    .a5p-draw-actions { display:grid; gap:.55rem; margin-top:.7rem; padding:.7rem; border:1px solid var(--line-light); border-radius: 0.26rem; background:var(--menu-bg); }
    .a5p-draw-panel { border-color:var(--moss); }
    .a5p-draw-actions-title { color:var(--moss); font-size:.78rem; font-weight:800; }
    .a5p-draw-actions-row { display:flex; gap:.45rem; flex-wrap:wrap; }
    .a5p-draw-actions-row .a5p-btn { flex:1 1 5.5rem; justify-content:center; }

    .a5p-side { display:grid; gap:.55rem; position:sticky; top:.75rem; }
    .a5p-panel { border:1px solid var(--line-light); border-radius: 0.29rem; background:var(--panel-tint); padding:.72rem .8rem; }
    .a5p-panel h3 { margin:0 0 .45rem; font-size:.82rem; font-weight:650; color:var(--light-text); }
    /* หัวพาเนล: มีเลขขั้นตอน + เส้นคั่น (ธีมมืด/สว่างใช้ตัวแปรเดียวกัน ไม่ hardcode ขาว) */
    .a5p-panel-title { display:flex; align-items:center; gap:.45rem; margin:0 0 .5rem; padding-bottom:.4rem; border-bottom:1px solid var(--line-light); }
    .a5p-panel-title h3 { margin:0; color:var(--light-text); font-size:.8rem; font-weight:750; }
    .a5p-step-no { flex:none; display:grid; place-items:center; width:1.25rem; height:1.25rem; border-radius:50%; background:var(--moss); color:#fff; font-size:.68rem; font-weight:800; }
    .a5p-zlist { display:grid; gap:.3rem; max-height:14rem; overflow:auto; }
    .a5p-zitem { display:grid; grid-template-columns:1.2rem minmax(0, 1fr) auto; align-items:center; gap:.42rem; padding:.34rem .48rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); cursor:pointer; font-size:.76rem; }
    .a5p-zitem:hover, .a5p-zitem.is-selected { border-color:var(--moss); }
    .a5p-zitem .zc, .a5p-sub-area-item .zc { position:relative; flex:none; display:block; border:1px solid rgb(255 255 255 / 60%); box-shadow:0 0 0 1px rgb(0 0 0 / 16%); cursor:pointer; overflow:hidden; }
    .a5p-zitem .zc { width:1rem; height:1rem; border-radius:50%; }
    .a5p-color-input { position:absolute; inset:-.25rem; width:calc(100% + .5rem); height:calc(100% + .5rem); opacity:0; cursor:pointer; border:0; padding:0; }
    .a5p-zitem .zn { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .a5p-zitem small { color:var(--muted-light); flex:none; }
    .a5p-none { color:var(--muted-light); font-size:.8rem; }

    .a5p-form { display:grid; gap:.55rem; }
    .a5p-form label { display:grid; gap:.25rem; font-size:.74rem; color:var(--muted-light); font-weight:600; }
    .a5p-form input, .a5p-form textarea { padding:.45rem .6rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; }
    .a5p-form textarea { min-height:3rem; resize:vertical; }
    .a5p-form input:focus, .a5p-form textarea:focus { outline:none; border-color:var(--moss); }
    .a5p-form input.saved, .a5p-form textarea.saved { border-color:var(--moss); }
    /* ปุ่มท้ายฟอร์มชิดขวาล่างของการ์ดเสมอ */
    .a5p-form-actions { display:flex; gap:.4rem; justify-content:flex-end; margin-top:.65rem; padding-top:.55rem; border-top:1px solid var(--line-light); flex-wrap:wrap; }
    .a5p-form-actions .a5p-btn { padding:.34rem .7rem; font-size:.74rem; }

    .a5p-floors { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:.5rem; }
    .a5p-floor-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .3rem .28rem .6rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); font-size:.76rem; font-weight:650; }
    .a5p-floor-chip button { width:1.1rem; height:1.1rem; display:grid; place-items:center; border:none; background:transparent; color:#d98a80; cursor:pointer; font-size:.9rem; line-height:1; padding:0; }
    .a5p-floor-add { display:flex; gap:.4rem; }
    .a5p-floor-add input { flex:1; }

    .a5p-subzone { display:grid; gap:.48rem; padding-top:.55rem; margin-top:.1rem; border-top:1px solid var(--line-light); }
    .a5p-subzone-head { display:flex; align-items:center; gap:.45rem; }
    .a5p-subzone-head strong { font-size:.8rem; font-weight:750; color:var(--light-text); }
    .a5p-add-mini { margin-left:auto; padding:.2rem .55rem; font-size:.72rem; border-radius: 0.25rem; }
    /* การ์ดโซนย่อย: ภาพ + ชื่อ + จำนวนพื้นที่ย่อย เท่านั้น (คลิก = เข้าขั้น 3) */
    .a5p-zone-map-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(8.5rem, 1fr)); gap:.45rem; }
    .a5p-zone-map-card { display:grid; align-content:start; gap:.25rem; padding:.38rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); cursor:pointer; min-height:0; text-align:left; color:inherit; }
    .a5p-zone-map-card:hover, .a5p-zone-map-card.is-selected, .a5p-zone-map-card:focus-visible { border-color:var(--moss); outline:none; }
    .a5p-zone-map-card.is-selected { box-shadow:0 0 0 2px rgb(91 141 239 / 22%); }
    .a5p-zone-map-figure { position:relative; }
    .a5p-zone-map-thumb { display:block; width:100%; aspect-ratio:16 / 10; object-fit:contain; border-radius: 0.25rem; background:#fff; }
    /* ปุ่ม × มุมขวาบนของภาพ = ลบโซนย่อยนั้น */
    .a5p-card-x { position:absolute; top:.2rem; right:.2rem; width:1.35rem; height:1.35rem; display:grid; place-items:center; border:1px solid rgb(255 255 255 / 55%); border-radius:50%; background:rgb(24 26 22 / 62%); color:#fff; cursor:pointer; font-size:.9rem; line-height:1; padding:0; opacity:.85; transition:background .15s ease, opacity .15s ease; }
    .a5p-card-x:hover, .a5p-card-x:focus-visible { background:#c8463a; border-color:#c8463a; opacity:1; outline:none; }
    .a5p-zone-map-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.72rem; font-weight:700; }
    .a5p-zone-map-meta { color:var(--muted-light); font-size:.68rem; }

    .a5p-sub-editor[hidden] { display:none !important; }
    .a5p-sub-editor { display:grid; gap:.6rem; border:1px solid var(--line-light); border-radius: 0.29rem; background:var(--panel-tint); padding:.7rem; }
    .a5p-sub-editor-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; padding:0 0 .45rem; border-bottom:1px solid var(--line-light); }
    .a5p-sub-editor-head .ttl { display:flex; align-items:center; gap:.45rem; min-width:0; }
    .a5p-sub-editor-head h3 { margin:0; color:var(--light-text); font-size:.82rem; font-weight:750; }
    .a5p-sub-editor-head p { margin:0; color:var(--muted-light); font-size:.72rem; }
    .a5p-sub-editor-body { display:grid; grid-template-columns:minmax(0, 1fr) 20rem; gap:.85rem; align-items:start; }
    @media (max-width: 1000px) { .a5p-sub-editor-body { grid-template-columns:1fr; } }
    /* ไม่จำกัดความสูง — พื้นที่ย่อยเพิ่ม รายการยาวลง ปุ่มท้ายพาเนลเลื่อนลงตาม */
    .a5p-sub-area-list { display:grid; gap:.35rem; }
    .a5p-sub-area-list:empty { display:none; }
    .a5p-sub-area-item { display:grid; grid-template-columns:1.25rem minmax(0, 1fr); align-items:center; gap:.45rem; padding:.4rem .5rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); cursor:pointer; font-size:.78rem; }
    .a5p-sub-area-item:hover, .a5p-sub-area-item.is-selected { border-color:var(--moss); }
    .a5p-sub-area-item .zc { width:.95rem; height:.95rem; border-radius:50%; }
    .a5p-sub-area-item .zn { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

    /* modal กลางจอเสมอ (ทั้ง showModal และ fallback [open]) */
    .a5p-dialog { position:fixed; inset:0; margin:auto; width:min(32rem, calc(100vw - 2rem)); max-height:calc(100vh - 2rem); border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--panel-soft); color:inherit; padding:0; box-shadow:0 18px 48px rgb(0 0 0 / 34%); }
    .a5p-dialog::backdrop { background:rgb(0 0 0 / 45%); }
    .a5p-dialog:not([open]) { display:none; }
    .a5p-dialog-form { display:grid; gap:.75rem; padding:1rem; }
    .a5p-dialog-form h3 { margin:0; font-size:.95rem; }
    .a5p-dialog-form label { display:grid; gap:.25rem; color:var(--muted-light); font-size:.74rem; font-weight:650; }
    .a5p-dialog-form input { padding:.5rem .65rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; }
    .a5p-dialog-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
    .a5p-changeimg { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-top:.6rem; padding-top:.55rem; border-top:1px solid var(--line-light); }
    .a5p-changeimg form { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .a5p-changeimg .a5p-none { font-size:.74rem; }
    @media (max-width:1000px) { .a5p-side { position:static; } }
@endsection

@section('content')
  <div class="a5p-wrap">
    <div class="a5p-top">
      <a class="a5s-back nav-go" href="{{ route('area5s.manage') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
      @if ($plan)
        <a class="a5p-btn" href="{{ route('area5s.plan.mapping') }}" data-i18n="a5s.plan.mappingLink">mapping ข้อมูลเก่า</a>
      @endif
    </div>

    @if (! $plan)
      <div class="a5p-upload">
        <p data-i18n="a5s.plan.noPlanYet">ยังไม่มีภาพแปลน — อัปโหลดเพื่อเริ่มวาดโซน</p>
        <form action="{{ route('area5s.plan.image') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <label class="a5p-file">
            <span data-i18n="a5s.plan.chooseFile">เลือกไฟล์</span>
            <input type="file" name="image" accept="image/*" data-file-input required>
          </label>
          <span class="a5p-file-name" data-file-name></span>
          <button type="submit" class="a5p-btn primary" data-i18n="a5s.plan.upload">อัปโหลด</button>
        </form>
      </div>
    @else
      {{-- ลำดับงานของหน้านี้ (ขั้นตอนเหมือนเดิม แค่บอกให้เห็นภาพรวม) --}}
      <div class="a5p-steps">
        <span class="a5p-step"><b>1</b><span data-i18n="a5s.plan.step1">วาดโซน</span></span>
        <span class="arw" aria-hidden="true">›</span>
        <span class="a5p-step"><b>2</b><span data-i18n="a5s.plan.step2">เลือกโซนย่อย</span></span>
        <span class="arw" aria-hidden="true">›</span>
        <span class="a5p-step"><b>3</b><span data-i18n="a5s.plan.step3">พื้นที่ย่อย + ชั้น</span></span>
      </div>

      <div class="a5p-body">
        <div class="a5p-stage-wrap">
          <div class="a5p-zoombar">
            <span class="a5p-zoom-ctrls">
              <button type="button" data-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">−</button>
              <button type="button" data-zoom-reset aria-label="รีเซ็ตซูม" data-i18n-aria="a5s.common.resetZoom" class="a5p-zoom-val" data-zoom-val>50%</button>
              <button type="button" data-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
            </span>
            <button type="button" class="a5p-btn primary" data-draw-start data-i18n="a5s.plan.drawNewZone">+ เพิ่มโซน</button>
          </div>

          <div class="a5p-stage" data-stage>
            <div class="a5p-canvas" data-canvas>
              <img src="{{ asset('storage/'.$plan->image_path) }}" alt="{{ $plan->name }}" draggable="false">
              <svg class="a5p-svg" viewBox="0 0 100 100" preserveAspectRatio="none" data-svg></svg>
            </div>
            <div class="a5p-draw-hint" data-draw-hint hidden></div>
          </div>

          <div class="a5p-changeimg">
            <span class="a5p-none" data-i18n="a5s.plan.changeImageHint">เปลี่ยนภาพแปลน</span>
            <form action="{{ route('area5s.plan.image') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <label class="a5p-file">
                <span data-i18n="a5s.plan.chooseFile">เลือกไฟล์</span>
                <input type="file" name="image" accept="image/*" data-file-input required>
              </label>
              <span class="a5p-file-name" data-file-name></span>
              <button type="submit" class="a5p-btn" data-i18n="a5s.editor.upload">อัปโหลด</button>
            </form>
          </div>
        </div>

        <div class="a5p-side">
          <div class="a5p-panel">
            <div class="a5p-panel-title">
              <span class="a5p-step-no" aria-hidden="true">1</span>
              <h3><span data-i18n="a5s.plan.allZones">โซนทั้งหมด</span> (<span data-zone-count>{{ count($zones) }}</span>)</h3>
            </div>
            <div class="a5p-zlist" data-zone-list></div>
            <p class="a5p-none" data-zone-empty {{ count($zones) ? 'hidden' : '' }} data-i18n="a5s.plan.noZonesYet" style="margin-top:.4rem">ยังไม่มีโซน — กด "+ เพิ่มโซน"</p>
            <div class="a5p-draw-actions a5p-draw-panel" data-draw-actions hidden>
              <div class="a5p-draw-actions-title" data-i18n="a5s.plan.drawNewZone">+ เพิ่มโซน</div>
              <div class="a5p-draw-actions-row">
                <button type="button" class="a5p-btn primary" data-draw-finish data-i18n="a5s.plan.finishDraw">เสร็จสิ้น</button>
                <button type="button" class="a5p-btn" data-draw-undo data-i18n="a5s.plan.undoPoint">ย้อนจุด</button>
                <button type="button" class="a5p-btn" data-draw-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
              </div>
            </div>
          </div>

          <div class="a5p-panel" data-zone-form hidden>
            <div class="a5p-panel-title">
              <span class="a5p-step-no" aria-hidden="true">2</span>
              <h3 data-i18n="a5s.plan.selectedOverviewZone">โซนที่เลือก</h3>
            </div>
            {{-- ชื่อโซนที่เลือกอยู่ในช่องกรอกด้านล่างแล้ว จึงเก็บไว้เป็นข้อมูลซ่อนให้ JS ใช้ต่อ --}}
            <span hidden data-selected-zone-name></span>

            <div class="a5p-form">
              <label><span data-i18n="a5s.plan.zoneName">ชื่อ</span>
                <input type="text" data-zf="name" maxlength="191">
              </label>
              <label><span data-i18n="a5s.manage.description">คำอธิบาย (ไม่บังคับ)</span>
                <textarea data-zf="description" maxlength="2000"></textarea>
              </label>
            </div>

            <div class="a5p-subzone" data-zone-maps-section>
              <div class="a5p-subzone-head">
                <strong data-i18n="a5s.plan.subZoneMaps">โซนย่อย</strong>
                <button type="button" class="a5p-btn a5p-add-mini" data-zone-map-add><span aria-hidden="true">+</span> <span data-i18n="a5s.work.add">เพิ่ม</span></button>
              </div>
              <div class="a5p-zone-map-grid" data-zone-map-grid></div>
            </div>

            <div class="a5p-form-actions">
              <button type="button" class="a5p-btn warning" data-zone-reset data-i18n="a5s.plan.resetZone">รีเซ็ตโซน</button>
              <button type="button" class="a5p-btn danger" data-zone-delete data-i18n="a5s.plan.deleteOverviewZone">ลบโซน</button>
              <button type="button" class="a5p-btn primary" data-zone-done data-i18n="a5s.editor.done">เสร็จสิ้น</button>
            </div>
          </div>
          <p class="a5p-none" data-no-selection data-i18n="a5s.plan.noSelection">เลือกโซนจากภาพหรือรายการเพื่อจัดการ</p>
        </div>
      </div>

      <div class="a5p-sub-editor" data-zone-map-editor hidden>
        <div class="a5p-sub-editor-head">
          <div class="ttl">
            <span class="a5p-step-no" aria-hidden="true">3</span>
            <h3 data-sub-map-title data-i18n="a5s.plan.subZoneMaps">โซนย่อย</h3>
          </div>
          <button type="button" class="a5p-btn" data-sub-map-edit data-i18n="a5s.manage.edit">แก้ไข</button>
        </div>

        <div class="a5p-sub-editor-body">
          <div class="a5p-stage-wrap">
            <div class="a5p-zoombar">
              <span class="a5p-zoom-ctrls">
                <button type="button" data-sub-zoom-out aria-label="ซูมออก" data-i18n-aria="profile.zoom_out">−</button>
                <button type="button" data-sub-zoom-reset aria-label="รีเซ็ตซูม" data-i18n-aria="a5s.common.resetZoom" class="a5p-zoom-val" data-sub-zoom-val>73%</button>
                <button type="button" data-sub-zoom-in aria-label="ซูมเข้า" data-i18n-aria="profile.zoom_in">+</button>
              </span>
              <button type="button" class="a5p-btn primary" data-sub-draw-start data-i18n="a5s.plan.drawSubZoneArea">+ เพิ่มพื้นที่ย่อย</button>
            </div>
            <div class="a5p-stage" data-sub-stage>
              <div class="a5p-canvas" data-sub-canvas>
                <img src="" alt="" data-sub-img draggable="false">
                <svg class="a5p-svg" viewBox="0 0 100 100" preserveAspectRatio="none" data-sub-svg></svg>
              </div>
              <div class="a5p-draw-hint" data-sub-draw-hint hidden></div>
            </div>
          </div>

          <div class="a5p-side">
            <div class="a5p-panel">
              <div class="a5p-panel-title">
                <h3><span data-i18n="a5s.plan.subZoneAreas">พื้นที่ย่อย</span> (<span data-sub-area-count>0</span>)</h3>
              </div>
              <div class="a5p-sub-area-list" data-sub-area-list></div>
              <p class="a5p-none" data-sub-area-empty data-i18n="a5s.plan.noSubZoneAreas">ยังไม่มีพื้นที่ย่อย</p>
              <div class="a5p-draw-actions a5p-draw-panel" data-sub-draw-actions hidden>
                <div class="a5p-draw-actions-title" data-i18n="a5s.plan.drawSubZoneArea">+ เพิ่มพื้นที่ย่อย</div>
                <div class="a5p-draw-actions-row">
                  <button type="button" class="a5p-btn primary" data-sub-draw-finish data-i18n="a5s.plan.finishDraw">เสร็จสิ้น</button>
                  <button type="button" class="a5p-btn" data-sub-draw-undo data-i18n="a5s.plan.undoPoint">ย้อนจุด</button>
                  <button type="button" class="a5p-btn" data-sub-draw-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
                </div>
              </div>

            </div>

            <div class="a5p-panel" data-sub-area-form hidden>
              <div class="a5p-panel-title">
                <h3 data-i18n="a5s.plan.subZoneAreaData">พื้นที่ย่อยที่เลือก</h3>
              </div>
              <div class="a5p-form">
                <label><span data-i18n="a5s.plan.subZoneAreaName">ชื่อ</span>
                  <input type="text" data-sub-area-field="name" maxlength="191">
                </label>
                <label><span data-i18n="a5s.manage.description">คำอธิบาย (ไม่บังคับ)</span>
                  <textarea data-sub-area-field="description" maxlength="2000"></textarea>
                </label>
                <label data-i18n="a5s.plan.floorsInArea">ชั้น</label>
                <div class="a5p-floors" data-area-floor-chips></div>
                <div class="a5p-floor-add">
                  <input type="text" data-area-floor-input maxlength="100" data-i18n-placeholder="a5s.plan.floorNamePlaceholder" placeholder="เช่น F4">
                  <button type="button" class="a5p-btn" data-area-floor-add data-i18n="a5s.plan.addFloor">+ ชั้น</button>
                </div>
                <div class="a5p-form-actions">
                  <button type="button" class="a5p-btn danger" data-sub-area-delete data-i18n="a5s.plan.deleteSubZoneArea">ลบพื้นที่ย่อย</button>
                  <button type="button" class="a5p-btn primary" data-sub-area-done data-i18n="a5s.editor.done">เสร็จสิ้น</button>
                </div>
              </div>
            </div>
            <p class="a5p-none" data-sub-area-no-selection data-i18n="a5s.plan.noSubZoneAreaSelection">เลือกพื้นที่ย่อยจากภาพหรือรายการเพื่อแก้ไข</p>
          </div>
        </div>

      </div>

      <dialog class="a5p-dialog" data-zone-map-dialog>
        <form class="a5p-dialog-form" method="dialog" data-zone-map-dialog-form>
          <h3 data-zone-map-dialog-title data-i18n="a5s.plan.addSubZone">เพิ่มโซนย่อย</h3>
          <label><span data-i18n="a5s.plan.subZoneName">ชื่อโซนย่อย</span>
            <input type="text" data-zone-map-name maxlength="191" placeholder="โซน 1" data-i18n-placeholder="a5s.plan.zoneNamePlaceholder">
          </label>
          <label><span data-i18n="a5s.plan.subZoneImage">ภาพโซนย่อย</span>
            <span class="a5p-file">
              <span data-i18n="a5s.plan.chooseFile">เลือกไฟล์</span>
              <input type="file" data-zone-map-image data-file-input accept="image/*">
            </span>
            <span class="a5p-file-name" data-file-name></span>
          </label>
          <div class="a5p-dialog-actions">
            <button type="button" class="a5p-btn" data-zone-map-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
            <button type="button" class="a5p-btn primary" data-zone-map-save data-i18n="a5s.plan.saveSubZone">บันทึกโซนย่อย</button>
          </div>
        </form>
      </dialog>
    @endif
  </div>

  @if ($plan)
  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const J = (url, method = 'GET', body = null) => fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: body ? JSON.stringify(body) : null,
      }).then(r => r.json());
      const F = (url, method = 'POST', body = null) => fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body,
      }).then(r => r.json());

      const planId = @json($plan->id);
      let zones = @json($zones);
      let selectedId = null;
      let drawMode = false;
      let drawPoints = [];
      let selectedZoneMapId = null;
      let editingZoneMapId = null;
      let selectedSubAreaId = null;
      let subDrawMode = false;
      let subDrawPoints = [];

      const stage = document.querySelector('[data-stage]');
      const canvas = document.querySelector('[data-canvas]');
      const planImage = canvas.querySelector('img');
      const svg = document.querySelector('[data-svg]');
      const zoneList = document.querySelector('[data-zone-list]');
      const zoneEmpty = document.querySelector('[data-zone-empty]');
      const zoneForm = document.querySelector('[data-zone-form]');
      const noSel = document.querySelector('[data-no-selection]');
      const countEl = document.querySelector('[data-zone-count]');
      const drawHint = document.querySelector('[data-draw-hint]');
      const drawActions = document.querySelector('[data-draw-actions]');
      const zoomValBtn = document.querySelector('[data-zoom-val]');
      const selectedZoneName = document.querySelector('[data-selected-zone-name]');
      const zoneMapGrid = document.querySelector('[data-zone-map-grid]');
      const zoneMapDialog = document.querySelector('[data-zone-map-dialog]');
      const zoneMapDialogForm = document.querySelector('[data-zone-map-dialog-form]');
      const zoneMapDialogTitle = document.querySelector('[data-zone-map-dialog-title]');
      const zoneMapNameInput = document.querySelector('[data-zone-map-name]');
      const zoneMapImageInput = document.querySelector('[data-zone-map-image]');
      const subEditor = document.querySelector('[data-zone-map-editor]');
      const subMapTitle = document.querySelector('[data-sub-map-title]');
      const subStage = document.querySelector('[data-sub-stage]');
      const subCanvas = document.querySelector('[data-sub-canvas]');
      const subImg = document.querySelector('[data-sub-img]');
      const subSvg = document.querySelector('[data-sub-svg]');
      const subDrawHint = document.querySelector('[data-sub-draw-hint]');
      const subDrawActions = document.querySelector('[data-sub-draw-actions]');
      const subZoomValBtn = document.querySelector('[data-sub-zoom-val]');
      const subAreaList = document.querySelector('[data-sub-area-list]');
      const subAreaEmpty = document.querySelector('[data-sub-area-empty]');
      const subAreaCount = document.querySelector('[data-sub-area-count]');
      const subAreaForm = document.querySelector('[data-sub-area-form]');
      const subAreaNoSel = document.querySelector('[data-sub-area-no-selection]');
      const i18n = window.__portalLang || {};
      const t = (key, fallback) => i18n.text ? i18n.text(key, fallback) : fallback;
      const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
      const colorOf = value => /^#[0-9a-f]{6}$/i.test(String(value || '')) ? String(value).toLowerCase() : '#5b7343';
      const flash = el => { el.classList.add('saved'); setTimeout(() => el.classList.remove('saved'), 1200); };
      const byId = id => zones.find(z => z.id === Number(id));
      const selectedZone = () => byId(selectedId);
      const zoneMapById = id => (selectedZone()?.zone_maps || []).find(m => m.id === Number(id));
      const subAreaById = id => (zoneMapById(selectedZoneMapId)?.areas || []).find(a => a.id === Number(id));
      const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
      const NS = 'http://www.w3.org/2000/svg';

      // ---------- zoom / pan ----------
      const MINZ = 0.2;
      const DEFAULTZ = 0.5;
      const MAXZ = 10;
      let zoom = DEFAULTZ, panX = 0, panY = 0;
      function resizeMainStage() {
        const scaledHeight = canvas.offsetHeight * zoom;
        const viewportSpace = Math.max(260, window.innerHeight - stage.getBoundingClientRect().top - 16);
        stage.style.height = Math.min(Math.max(260, scaledHeight), viewportSpace) + 'px';
      }
      function applyTransform() {
        resizeMainStage();
        canvas.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
      }
      function clampPan() {
        const r = stage.getBoundingClientRect();
        const extraX = r.width - (canvas.offsetWidth * zoom);
        const extraY = r.height - (canvas.offsetHeight * zoom);
        panX = clamp(panX, Math.min(extraX, 0), Math.max(extraX, 0));
        panY = clamp(panY, Math.min(extraY, 0), Math.max(extraY, 0));
      }
      function resetZoom() {
        zoom = DEFAULTZ;
        resizeMainStage();
        const r = stage.getBoundingClientRect();
        panX = Math.max(r.width - (canvas.offsetWidth * zoom), 0) / 2;
        panY = 0;
        clampPan(); applyTransform();
        if (zoomValBtn) zoomValBtn.textContent = '50%';
        render();
      }
      function setZoom(nz, sx, sy) {
        const r = stage.getBoundingClientRect();
        if (sx == null) { sx = r.width / 2; sy = r.height / 2; }
        nz = clamp(nz, MINZ, MAXZ);
        panX = sx - (sx - panX) * (nz / zoom);
        panY = sy - (sy - panY) * (nz / zoom);
        zoom = nz;
        resizeMainStage();
        clampPan(); applyTransform();
        if (zoomValBtn) zoomValBtn.textContent = Math.round(zoom * 100) + '%';
        render(); // ปรับขนาด vertex/label ให้คงที่บนจอ
      }
      document.querySelector('[data-zoom-in]').addEventListener('click', () => setZoom(zoom * 1.35));
      document.querySelector('[data-zoom-out]').addEventListener('click', () => setZoom(zoom / 1.35));
      document.querySelector('[data-zoom-reset]').addEventListener('click', resetZoom);
      stage.addEventListener('wheel', e => {
        e.preventDefault();
        const r = stage.getBoundingClientRect();
        setZoom(zoom * (e.deltaY < 0 ? 1.2 : 1 / 1.2), e.clientX - r.left, e.clientY - r.top);
      }, { passive: false });

      // แปลงพิกัดจอ -> % ของภาพเต็ม (canvas ที่ transform แล้ว: getBoundingClientRect สะท้อน zoom+pan อยู่แล้ว)
      function toPct(e) {
        const r = canvas.getBoundingClientRect();
        return {
          x: Math.round(clamp((e.clientX - r.left) / r.width * 100, 0, 100) * 100) / 100,
          y: Math.round(clamp((e.clientY - r.top) / r.height * 100, 0, 100) * 100) / 100,
        };
      }

      function pointsAttr(pts) { return (pts || []).map(p => `${p.x},${p.y}`).join(' '); }
      function centroid(pts) {
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
      }

      function closeSubEditor() {
        selectedZoneMapId = null;
        selectedSubAreaId = null;
        subDrawMode = false;
        subDrawPoints = [];
        subEditor.hidden = true;
        subAreaForm.hidden = true;
        subAreaNoSel.hidden = false;
        subDrawActions.hidden = true;
        subDrawHint.hidden = true;
        if (subSvg) subSvg.innerHTML = '';
      }

      function clearSelection() {
        selectedId = null;
        zoneForm.hidden = true;
        noSel.hidden = false;
        if (selectedZoneName) selectedZoneName.textContent = '';
        if (zoneMapGrid) zoneMapGrid.innerHTML = '';
        closeSubEditor();
        render();
      }

      function render() {
        svg.innerHTML = '';
        // vertex/label ขนาดคงที่บนจอโดยหารด้วย zoom (แกน x ของ viewBox ~ 100 หน่วยพาดจอ)
        const vr = clamp(1.1 / zoom, 0.18, 1.1);
        const labelSize = clamp(1.9 / zoom, 0.42, 1.9);

        zones.forEach(z => {
          const color = colorOf(z.color);
          const poly = document.createElementNS(NS, 'polygon');
          poly.setAttribute('points', pointsAttr(z.shape_points || []));
          poly.setAttribute('fill', color + '55');
          poly.setAttribute('stroke', color);
          poly.setAttribute('stroke-width', z.id === selectedId ? 2 : 1.2);
          poly.setAttribute('vector-effect', 'non-scaling-stroke');
          poly.setAttribute('class', 'a5p-poly' + (drawMode ? '' : ' is-clickable'));
          poly.dataset.zone = z.id;
          svg.appendChild(poly);

          const c = centroid(z.shape_points || []);
          const label = document.createElementNS(NS, 'text');
          label.setAttribute('x', c.x); label.setAttribute('y', c.y);
          label.setAttribute('text-anchor', 'middle');
          label.setAttribute('dominant-baseline', 'middle');
          label.setAttribute('alignment-baseline', 'middle');
          label.setAttribute('class', 'a5p-label');
          label.setAttribute('font-size', labelSize);
          label.textContent = z.name;
          svg.appendChild(label);

          if (z.id === selectedId && !drawMode) {
            (z.shape_points || []).forEach((p, idx) => {
              const v = document.createElementNS(NS, 'circle');
              v.setAttribute('cx', p.x); v.setAttribute('cy', p.y); v.setAttribute('r', vr);
              v.setAttribute('fill', '#fff'); v.setAttribute('stroke', color); v.setAttribute('stroke-width', 0.9);
              v.setAttribute('vector-effect', 'non-scaling-stroke');
              v.setAttribute('class', 'a5p-vertex');
              v.dataset.zone = z.id; v.dataset.idx = idx;
              svg.appendChild(v);
            });
          }
        });

        if (drawMode && drawPoints.length) {
          const poly = document.createElementNS(NS, drawPoints.length >= 3 ? 'polygon' : 'polyline');
          poly.setAttribute('points', pointsAttr(drawPoints));
          poly.setAttribute('fill', drawPoints.length >= 3 ? 'rgb(200 150 74 / 30%)' : 'none');
          poly.setAttribute('stroke', '#c8964a');
          poly.setAttribute('stroke-width', 1.6);
          poly.setAttribute('vector-effect', 'non-scaling-stroke');
          svg.appendChild(poly);
          drawPoints.forEach(p => {
            const v = document.createElementNS(NS, 'circle');
            v.setAttribute('cx', p.x); v.setAttribute('cy', p.y); v.setAttribute('r', vr);
            v.setAttribute('fill', '#c8964a');
            svg.appendChild(v);
          });
        }

        zoneList.innerHTML = '';
        zones.forEach(z => {
          const color = colorOf(z.color);
          const colorLabel = esc(t('a5s.plan.zoneColor', 'เลือกสีโซน'));
          const it = document.createElement('div');
          it.className = 'a5p-zitem' + (z.id === selectedId ? ' is-selected' : '');
          it.dataset.zitem = z.id;
          it.innerHTML = `<label class="zc" style="background:${esc(color)}" title="${colorLabel}" aria-label="${colorLabel}"><input class="a5p-color-input" type="color" value="${esc(color)}" data-zone-color="${z.id}" aria-label="${colorLabel}"></label><span class="zn">${esc(z.name)}</span><small>${(z.zone_maps || []).length} ${esc(t('a5s.plan.subZoneMaps', 'โซนย่อย'))}</small>`;
          zoneList.appendChild(it);
        });
        countEl.textContent = zones.length;
        if (zoneEmpty) zoneEmpty.hidden = zones.length > 0;
      }

      function select(id) {
        selectedId = id;
        const z = byId(id);
        if (!z) {
          zoneForm.hidden = true;
          noSel.hidden = false;
          render();
          return;
        }
        zoneForm.hidden = false; noSel.hidden = true;
        if (selectedZoneName) selectedZoneName.textContent = z.name;
        zoneForm.querySelector('[data-zf="name"]').value = z.name || '';
        zoneForm.querySelector('[data-zf="description"]').value = z.description || '';
        if (! (z.zone_maps || []).some(m => m.id === selectedZoneMapId)) closeSubEditor();
        renderZoneMaps(z);
        render();
      }

      function renderAreaFloors(area) {
        const box = subAreaForm.querySelector('[data-area-floor-chips]');
        if (!box) return;
        const floors = area?.floors || [];
        box.innerHTML = floors.length
          ? floors.map(f => `<span class="a5p-floor-chip">${esc(f.name)}<button type="button" data-remove-area-floor="${f.id}" aria-label="${esc(t('a5s.common.remove', 'นำออก'))} ${esc(f.name)}">&times;</button></span>`).join('')
          : `<span class="a5p-none">${esc(t('a5s.plan.noFloors', 'ยังไม่มีชั้น'))}</span>`;
      }

      // การ์ดโซนย่อย: คลิก = เปิดพื้นที่ย่อย (ขั้น 3) · ปุ่ม × มุมขวาบนของภาพ = ลบโซนย่อยนั้น
      function renderZoneMaps(z) {
        const maps = z?.zone_maps || [];
        const delLabel = esc(t('a5s.plan.deleteSubZone', 'ลบโซนย่อย'));
        zoneMapGrid.innerHTML = maps.map(m => `
          <div class="a5p-zone-map-card${m.id === selectedZoneMapId ? ' is-selected' : ''}" data-zone-map-card="${m.id}" role="button" tabindex="0">
            <div class="a5p-zone-map-figure">
              <img class="a5p-zone-map-thumb" src="${esc(m.image_url)}" alt="${esc(m.name)}">
              <button type="button" class="a5p-card-x" data-zone-map-remove="${m.id}" title="${delLabel}" aria-label="${delLabel}">&times;</button>
            </div>
            <div class="a5p-zone-map-name" title="${esc(m.name)}">${esc(m.name)}</div>
            <div class="a5p-zone-map-meta">${(m.areas || []).length} ${esc(t('a5s.plan.areasUnit', 'พื้นที่'))}</div>
          </div>
        `).join('') || `<span class="a5p-none">${esc(t('a5s.plan.noSubZoneMaps', 'ยังไม่มีโซนย่อย'))}</span>`;
      }

      function openZoneMapDialog(map = null) {
        editingZoneMapId = map ? map.id : null;
        zoneMapDialogTitle.textContent = map ? t('a5s.plan.editSubZone', 'แก้ไขโซนย่อย') : t('a5s.plan.addSubZone', 'เพิ่มโซนย่อย');
        zoneMapNameInput.value = map?.name || '';
        zoneMapImageInput.value = '';
        zoneMapImageInput.dispatchEvent(new Event('change', { bubbles: true }));   // ล้างชื่อไฟล์ที่แสดงไว้
        if (zoneMapDialog.showModal) zoneMapDialog.showModal();
        else zoneMapDialog.setAttribute('open', 'open');
      }

      function closeZoneMapDialog() {
        editingZoneMapId = null;
        if (zoneMapDialog.close) zoneMapDialog.close();
        else zoneMapDialog.removeAttribute('open');
      }

      function saveZoneMapDialog() {
        const z = selectedZone();
        if (!z) return;
        const file = zoneMapImageInput.files && zoneMapImageInput.files[0] ? zoneMapImageInput.files[0] : null;
        if (!editingZoneMapId && !file) {
          alert(t('a5s.plan.subZoneNeedImage', 'กรุณาแนบภาพโซนย่อย'));
          return;
        }

        const body = new FormData();
        body.append('name', zoneMapNameInput.value.trim());
        if (file) body.append('image', file);

        let url = `{{ url('area5s/plan/zones') }}/${z.id}/maps`;
        if (editingZoneMapId) {
          url = `{{ url('area5s/plan/zone-maps') }}/${editingZoneMapId}`;
          body.append('_method', 'PUT');
        }

        F(url, 'POST', body).then(res => {
          if (!res.ok) {
            alert(res.message || t('a5s.plan.subZoneSaveFailed', 'บันทึกโซนย่อยไม่สำเร็จ'));
            return;
          }
          z.zone_maps = z.zone_maps || [];
          const idx = z.zone_maps.findIndex(m => m.id === res.map.id);
          if (idx >= 0) z.zone_maps[idx] = res.map;
          else z.zone_maps.push(res.map);
          closeZoneMapDialog();
          renderZoneMaps(z);
          selectZoneMap(res.map.id);
        }).catch(() => alert(t('a5s.plan.subZoneSaveFailed', 'บันทึกโซนย่อยไม่สำเร็จ')));
      }

      function deleteZoneMap(id) {
        const z = selectedZone();
        const map = zoneMapById(id);
        if (!z || !map || !confirm(t('a5s.plan.deleteSubZoneConfirm', 'ลบโซนย่อยนี้?'))) return;
        J(`{{ url('area5s/plan/zone-maps') }}/${map.id}`, 'DELETE').then(res => {
          if (!res.ok) {
            alert(res.message || t('a5s.plan.subZoneDeleteFailed', 'ลบโซนย่อยไม่สำเร็จ'));
            return;
          }
          z.zone_maps = (z.zone_maps || []).filter(m => m.id !== map.id);
          if (selectedZoneMapId === map.id) closeSubEditor();
          renderZoneMaps(z);
        }).catch(() => alert(t('a5s.plan.subZoneDeleteFailed', 'ลบโซนย่อยไม่สำเร็จ')));
      }

      function selectZoneMap(id) {
        const map = zoneMapById(id);
        if (!map) return;
        selectedZoneMapId = map.id;
        selectedSubAreaId = null;
        subEditor.hidden = false;
        subMapTitle.textContent = map.name;
        subImg.src = map.image_url;
        subImg.alt = map.name;
        setSubDrawMode(false);
        resetSubZoom();
        renderZoneMaps(selectedZone());
        renderSubAreas();
        subEditor.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }

      // ปุ่มเพิ่มโซนย่อยย้ายไปอยู่หัวข้อ "โซนย่อย" (ปุ่มเล็ก) แทนการ์ดใหญ่ในกริด
      document.querySelector('[data-zone-map-add]').addEventListener('click', () => openZoneMapDialog());

      zoneMapGrid.addEventListener('click', e => {
        const del = e.target.closest('[data-zone-map-remove]');
        if (del) { e.stopPropagation(); deleteZoneMap(Number(del.dataset.zoneMapRemove)); return; }
        const card = e.target.closest('[data-zone-map-card]');
        if (card) selectZoneMap(Number(card.dataset.zoneMapCard));
      });
      zoneMapGrid.addEventListener('keydown', e => {
        const card = e.target.closest('[data-zone-map-card]');
        if (card && (e.key === 'Enter' || e.key === ' ')) {
          e.preventDefault();
          selectZoneMap(Number(card.dataset.zoneMapCard));
        }
      });
      zoneMapDialogForm.addEventListener('submit', e => e.preventDefault());
      document.querySelector('[data-zone-map-cancel]').addEventListener('click', closeZoneMapDialog);
      document.querySelector('[data-zone-map-save]').addEventListener('click', saveZoneMapDialog);

      // ---------- draw mode ----------
      function setDrawMode(on) {
        drawMode = on; drawPoints = [];
        drawHint.hidden = !on; drawActions.hidden = !on;
        stage.classList.toggle('is-draw', on);
        if (on) { drawHint.textContent = t('a5s.plan.drawHint', 'คลิกบนภาพทีละจุดล้อมรอบพื้นที่ (อย่างน้อย 3 จุด)'); clearSelection(); }
        render();
      }
      document.querySelector('[data-draw-start]').addEventListener('click', () => setDrawMode(true));
      document.querySelector('[data-draw-cancel]').addEventListener('click', () => setDrawMode(false));
      document.querySelector('[data-draw-undo]').addEventListener('click', () => { drawPoints.pop(); render(); });
      document.querySelector('[data-draw-finish]').addEventListener('click', () => {
        if (drawPoints.length < 3) { alert(t('a5s.plan.needThreePoints', 'ต้องมีอย่างน้อย 3 จุด')); return; }
        J(`{{ url('area5s/plan') }}/${planId}/zones`, 'POST', { shape_points: drawPoints }).then(res => {
          if (!res.ok) { alert(res.message || t('a5s.plan.saveFailed', 'บันทึกไม่สำเร็จ')); return; }
          zones.push(res.zone);
          setDrawMode(false);
          select(res.zone.id);
        });
      });

      // ---------- pointer: pan / draw-point / select / vertex-drag / pinch ----------
      const pointers = new Map();
      let ptr = null;      // สถานะ 1 นิ้ว/เมาส์
      let pinch = null;    // สถานะ 2 นิ้ว

      stage.addEventListener('pointerdown', e => {
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pointers.size === 2) { startPinch(); ptr = null; return; }
        if (pointers.size > 2) return;

        const vertex = !drawMode ? e.target.closest('[data-idx]') : null;
        if (vertex) {
          ptr = { kind: 'vertex', zoneId: Number(vertex.dataset.zone), idx: Number(vertex.dataset.idx), moved: false };
        } else {
          ptr = { kind: 'pan', sx: e.clientX, sy: e.clientY, px: panX, py: panY, moved: false, target: e.target };
        }
        try { stage.setPointerCapture(e.pointerId); } catch (err) {}
      });

      stage.addEventListener('pointermove', e => {
        if (pointers.has(e.pointerId)) pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pinch) { movePinch(); return; }
        if (!ptr) return;

        if (ptr.kind === 'vertex') {
          const z = byId(ptr.zoneId); if (!z) return;
          z.shape_points[ptr.idx] = toPct(e); ptr.moved = true; render();
          return;
        }
        const dx = e.clientX - ptr.sx, dy = e.clientY - ptr.sy;
        if (!ptr.moved && Math.abs(dx) + Math.abs(dy) > 4) { ptr.moved = true; stage.classList.add('is-pan'); }
        if (ptr.moved) { panX = ptr.px + dx; panY = ptr.py + dy; clampPan(); applyTransform(); }
      });

      function endPointer(e) {
        pointers.delete(e.pointerId);
        try { stage.releasePointerCapture(e.pointerId); } catch (err) {}
        stage.classList.remove('is-pan');
        if (pinch && pointers.size < 2) pinch = null;
        if (!ptr) return;
        const cur = ptr; ptr = null;

        if (cur.kind === 'vertex') {
          if (cur.moved) { const z = byId(cur.zoneId); if (z) J(`{{ url('area5s/plan/zones') }}/${cur.zoneId}`, 'PUT', { shape_points: z.shape_points }); }
          return;
        }
        if (cur.moved) return; // ลาก = เลื่อนดู ไม่ใช่คลิก
        if (drawMode) { drawPoints.push(toPct(e)); render(); return; }
        const poly = cur.target.closest('[data-zone]');
        if (poly) select(Number(poly.dataset.zone));
      }
      stage.addEventListener('pointerup', endPointer);
      stage.addEventListener('pointercancel', endPointer);

      function startPinch() {
        const pts = [...pointers.values()];
        pinch = { d: dist(pts[0], pts[1]) };
      }
      function movePinch() {
        const pts = [...pointers.values()];
        if (pts.length < 2) return;
        const nd = dist(pts[0], pts[1]);
        const r = stage.getBoundingClientRect();
        const mx = (pts[0].x + pts[1].x) / 2 - r.left, my = (pts[0].y + pts[1].y) / 2 - r.top;
        if (pinch.d > 0) setZoom(zoom * (nd / pinch.d), mx, my);
        pinch.d = nd;
      }
      function dist(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }

      function saveZoneColor(zoneId, value) {
        const z = byId(zoneId);
        if (!z) return;
        const previous = colorOf(z.color);
        z.color = colorOf(value);
        render();
        J(`{{ url('area5s/plan/zones') }}/${zoneId}`, 'PUT', { color: z.color }).then(res => {
          if (!res.ok) {
            z.color = previous;
            render();
            alert(res.message || t('a5s.plan.colorSaveFailed', 'บันทึกสีไม่สำเร็จ'));
          }
        }).catch(() => {
          z.color = previous;
          render();
          alert(t('a5s.plan.colorSaveFailed', 'บันทึกสีไม่สำเร็จ'));
        });
      }

      zoneList.addEventListener('change', e => {
        const picker = e.target.closest('[data-zone-color]');
        if (picker) saveZoneColor(Number(picker.dataset.zoneColor), picker.value);
      });
      zoneList.addEventListener('click', e => {
        if (e.target.closest('.zc')) return;
        const it = e.target.closest('[data-zitem]');
        if (it) select(Number(it.dataset.zitem));
      });

      // ---------- zone form: auto-save ----------
      function saveZoneField(inp) {
        if (!inp || !selectedId) return;
        const field = inp.dataset.zf;
        J(`{{ url('area5s/plan/zones') }}/${selectedId}`, 'PUT', { [field]: inp.value }).then(res => {
          if (!res.ok) { alert(res.message || t('a5s.editor.saveFailed', 'บันทึกไม่สำเร็จ')); return; }
          const z = byId(selectedId);
          if (z && field === 'name') {
            z.name = inp.value;
            if (selectedZoneName) selectedZoneName.textContent = inp.value;
          }
          if (z && field === 'description') z.description = inp.value;
          flash(inp); render();
        });
      }
      zoneForm.addEventListener('change', e => { const inp = e.target.closest('[data-zf]'); if (inp) saveZoneField(inp); });

      zoneForm.querySelector('[data-zone-reset]').addEventListener('click', () => {
        if (!selectedId) return;
        const z = byId(selectedId);
        if (!z || !confirm(t('a5s.plan.resetZoneConfirm', 'รีเซ็ตโซนนี้? ระบบจะถอดพื้นที่ที่ผูกกับชั้นในโซนนี้ออก และลบชั้นเดิมออกจากโซน'))) return;
        const resetBtn = zoneForm.querySelector('[data-zone-reset]');
        const zoneId = selectedId;
        resetBtn.disabled = true;
        J(`{{ url('area5s/plan/zones') }}/${zoneId}/reset`, 'POST').then(res => {
          resetBtn.disabled = false;
          if (!res.ok) { alert(res.message || t('a5s.plan.resetFailed', 'รีเซ็ตโซนไม่สำเร็จ')); return; }
          const current = byId(zoneId);
          if (current) {
            current.floors = [];
            (current.zone_maps || []).forEach(m => (m.areas || []).forEach(a => { a.floors = []; }));
            const area = subAreaById(selectedSubAreaId);
            if (area) renderAreaFloors(area);
          }
          render();
          alert(res.message || t('a5s.plan.resetDone', 'รีเซ็ตโซนแล้ว ตอนนี้สามารถลบโซนนี้ได้'));
        }).catch(() => {
          resetBtn.disabled = false;
          alert(t('a5s.plan.resetFailed', 'รีเซ็ตโซนไม่สำเร็จ'));
        });
      });

      zoneForm.querySelector('[data-zone-delete]').addEventListener('click', () => {
        if (!selectedId) return;
        const z = byId(selectedId);
        if (!z || !confirm(t('a5s.plan.deleteZoneConfirm', 'ลบโซนนี้?'))) return;
        J(`{{ url('area5s/plan/zones') }}/${selectedId}`, 'DELETE').then(res => {
          if (!res.ok) { alert(res.message || t('a5s.plan.deleteFailed', 'ลบไม่สำเร็จ')); return; }
          zones = zones.filter(item => item.id !== selectedId);
          clearSelection();
        });
      });
      zoneForm.querySelector('[data-zone-done]').addEventListener('click', clearSelection);

      // ---------- area floors ----------
      subAreaForm.querySelector('[data-area-floor-add]').addEventListener('click', () => {
        const area = subAreaById(selectedSubAreaId);
        if (!area) return;
        const input = subAreaForm.querySelector('[data-area-floor-input]');
        const name = input.value.trim(); if (!name) return;
        J(`{{ url('area5s/plan/zone-map-areas') }}/${area.id}/floors`, 'POST', { name }).then(res => {
          if (!res.ok) { alert(res.message || t('a5s.plan.addFloorFailed', 'เพิ่มชั้นไม่สำเร็จ')); return; }
          area.floors = area.floors || [];
          area.floors.push(res.floor);
          const z = selectedZone();
          if (z) {
            z.floors = z.floors || [];
            z.floors.push(res.floor);
          }
          input.value = '';
          renderAreaFloors(area);
          renderSubAreas();
        });
      });
      subAreaForm.addEventListener('click', e => {
        const btn = e.target.closest('[data-remove-area-floor]');
        const area = subAreaById(selectedSubAreaId);
        if (!btn || !area) return;
        if (!confirm(t('a5s.manage.delete', 'ลบ') + '?')) return;
        const floorId = Number(btn.dataset.removeAreaFloor);
        J(`{{ url('area5s/plan/floors') }}/${floorId}`, 'DELETE').then(res => {
          if (!res.ok) { alert(res.message || t('a5s.plan.deleteFailed', 'ลบไม่สำเร็จ')); return; }
          area.floors = (area.floors || []).filter(f => f.id !== floorId);
          const z = selectedZone();
          if (z) z.floors = (z.floors || []).filter(f => f.id !== floorId);
          renderAreaFloors(area);
          renderSubAreas();
        });
      });

      // ---------- sub-zone map editor ----------
      const SUB_MINZ = 0.2;
      const SUB_DEFAULTZ = 0.73;
      const SUB_MAXZ = 10;
      let subZoom = SUB_DEFAULTZ, subPanX = 0, subPanY = 0;
      function subApplyTransform() { subCanvas.style.transform = `translate(${subPanX}px, ${subPanY}px) scale(${subZoom})`; }
      function clampSubPan() {
        const r = subStage.getBoundingClientRect();
        const extraX = r.width - (subCanvas.offsetWidth * subZoom);
        const extraY = r.height - (subCanvas.offsetHeight * subZoom);
        subPanX = clamp(subPanX, Math.min(extraX, 0), Math.max(extraX, 0));
        subPanY = clamp(subPanY, Math.min(extraY, 0), Math.max(extraY, 0));
      }
      function setSubZoom(nz, sx, sy) {
        const r = subStage.getBoundingClientRect();
        if (sx == null) { sx = r.width / 2; sy = r.height / 2; }
        nz = clamp(nz, SUB_MINZ, SUB_MAXZ);
        subPanX = sx - (sx - subPanX) * (nz / subZoom);
        subPanY = sy - (sy - subPanY) * (nz / subZoom);
        subZoom = nz;
        clampSubPan(); subApplyTransform();
        if (subZoomValBtn) subZoomValBtn.textContent = Math.round(subZoom * 100) + '%';
        renderSubAreas();
      }
      function resetSubZoom() {
        const r = subStage.getBoundingClientRect();
        subZoom = SUB_DEFAULTZ;
        subPanX = Math.max(r.width - (subCanvas.offsetWidth * subZoom), 0) / 2;
        subPanY = 0;
        clampSubPan(); subApplyTransform();
        if (subZoomValBtn) subZoomValBtn.textContent = '73%';
      }
      function toSubPct(e) {
        const r = subCanvas.getBoundingClientRect();
        return {
          x: Math.round(clamp((e.clientX - r.left) / r.width * 100, 0, 100) * 100) / 100,
          y: Math.round(clamp((e.clientY - r.top) / r.height * 100, 0, 100) * 100) / 100,
        };
      }
      function setSubDrawMode(on) {
        subDrawMode = on; subDrawPoints = [];
        subDrawHint.hidden = !on; subDrawActions.hidden = !on;
        subStage.classList.toggle('is-draw', on);
        if (on) {
          selectedSubAreaId = null;
          subAreaForm.hidden = true;
          subAreaNoSel.hidden = false;
          subDrawHint.textContent = t('a5s.plan.subZoneAreaDrawHint', 'คลิกบนภาพทีละจุดเพื่อวาดกรอบพื้นที่ย่อย อย่างน้อย 3 จุด');
        }
        renderSubAreas();
      }
      function renderSubAreas() {
        const map = zoneMapById(selectedZoneMapId);
        if (!map) return;
        const areas = map.areas || [];
        subSvg.innerHTML = '';
        const vr = clamp(1.1 / subZoom, 0.18, 1.1);
        const labelSize = clamp(1.8 / subZoom, 0.4, 1.8);

        areas.forEach(a => {
          const color = colorOf(a.color);
          const poly = document.createElementNS(NS, 'polygon');
          poly.setAttribute('points', pointsAttr(a.shape_points || []));
          poly.setAttribute('fill', color + '50');
          poly.setAttribute('stroke', color);
          poly.setAttribute('stroke-width', a.id === selectedSubAreaId ? 2 : 1.2);
          poly.setAttribute('vector-effect', 'non-scaling-stroke');
          poly.setAttribute('class', 'a5p-poly' + (subDrawMode ? '' : ' is-clickable'));
          poly.dataset.subAreaPoly = a.id;
          subSvg.appendChild(poly);

          const c = centroid(a.shape_points || []);
          const label = document.createElementNS(NS, 'text');
          label.setAttribute('x', c.x); label.setAttribute('y', c.y);
          label.setAttribute('text-anchor', 'middle');
          label.setAttribute('dominant-baseline', 'middle');
          label.setAttribute('alignment-baseline', 'middle');
          label.setAttribute('class', 'a5p-label');
          label.setAttribute('font-size', labelSize);
          label.textContent = a.name;
          subSvg.appendChild(label);

          if (a.id === selectedSubAreaId && !subDrawMode) {
            (a.shape_points || []).forEach((p, idx) => {
              const v = document.createElementNS(NS, 'circle');
              v.setAttribute('cx', p.x); v.setAttribute('cy', p.y); v.setAttribute('r', vr);
              v.setAttribute('fill', '#fff'); v.setAttribute('stroke', color); v.setAttribute('stroke-width', 0.9);
              v.setAttribute('vector-effect', 'non-scaling-stroke');
              v.setAttribute('class', 'a5p-vertex');
              v.dataset.subArea = a.id; v.dataset.subIdx = idx;
              subSvg.appendChild(v);
            });
          }
        });

        if (subDrawMode && subDrawPoints.length) {
          const poly = document.createElementNS(NS, subDrawPoints.length >= 3 ? 'polygon' : 'polyline');
          poly.setAttribute('points', pointsAttr(subDrawPoints));
          poly.setAttribute('fill', subDrawPoints.length >= 3 ? 'rgb(200 150 74 / 30%)' : 'none');
          poly.setAttribute('stroke', '#c8964a');
          poly.setAttribute('stroke-width', 1.6);
          poly.setAttribute('vector-effect', 'non-scaling-stroke');
          subSvg.appendChild(poly);
          subDrawPoints.forEach(p => {
            const v = document.createElementNS(NS, 'circle');
            v.setAttribute('cx', p.x); v.setAttribute('cy', p.y); v.setAttribute('r', vr);
            v.setAttribute('fill', '#c8964a');
            subSvg.appendChild(v);
          });
        }

        subAreaList.innerHTML = areas.map(a => {
          const color = colorOf(a.color);
          const colorLabel = esc(t('a5s.plan.subZoneAreaColor', 'เลือกสีพื้นที่ย่อย'));
          return `
          <div class="a5p-sub-area-item${a.id === selectedSubAreaId ? ' is-selected' : ''}" data-sub-area-item="${a.id}">
            <label class="zc" style="background:${esc(color)}" title="${colorLabel}" aria-label="${colorLabel}"><input class="a5p-color-input" type="color" value="${esc(color)}" data-sub-area-color="${a.id}" aria-label="${colorLabel}"></label>
            <span class="zn">${esc(a.name)}</span>
          </div>
        `;
        }).join('');
        subAreaCount.textContent = areas.length;
        subAreaEmpty.hidden = areas.length > 0;
      }
      function selectSubArea(id) {
        const area = subAreaById(id);
        if (!area) return;
        selectedSubAreaId = area.id;
        subAreaForm.hidden = false;
        subAreaNoSel.hidden = true;
        subAreaForm.querySelector('[data-sub-area-field="name"]').value = area.name;
        subAreaForm.querySelector('[data-sub-area-field="description"]').value = area.description || '';
        subAreaForm.querySelector('[data-area-floor-input]').value = '';
        renderAreaFloors(area);
        renderSubAreas();
      }
      function clearSubAreaSelection() {
        selectedSubAreaId = null;
        subAreaForm.hidden = true;
        subAreaNoSel.hidden = false;
        renderAreaFloors(null);
        renderSubAreas();
      }
      function saveSubAreaField(inp) {
        if (!inp || !selectedSubAreaId) return;
        const field = inp.dataset.subAreaField;
        J(`{{ url('area5s/plan/zone-map-areas') }}/${selectedSubAreaId}`, 'PUT', { [field]: inp.value }).then(res => {
          if (!res.ok) { alert(res.message || t('a5s.editor.saveFailed', 'บันทึกไม่สำเร็จ')); return; }
          const map = zoneMapById(selectedZoneMapId);
          const idx = (map?.areas || []).findIndex(a => a.id === selectedSubAreaId);
          if (idx >= 0) map.areas[idx] = res.area;
          flash(inp); renderAreaFloors(res.area); renderSubAreas();
        });
      }
      function saveSubAreaColor(areaId, value) {
        const area = subAreaById(areaId);
        if (!area) return;
        const previous = colorOf(area.color);
        area.color = colorOf(value);
        renderSubAreas();
        J(`{{ url('area5s/plan/zone-map-areas') }}/${areaId}`, 'PUT', { color: area.color }).then(res => {
          if (!res.ok) {
            area.color = previous;
            renderSubAreas();
            alert(res.message || t('a5s.plan.colorSaveFailed', 'บันทึกสีไม่สำเร็จ'));
            return;
          }
          const map = zoneMapById(selectedZoneMapId);
          const idx = (map?.areas || []).findIndex(a => a.id === areaId);
          if (idx >= 0) map.areas[idx] = res.area;
          renderZoneMaps(selectedZone());
          renderSubAreas();
        }).catch(() => {
          area.color = previous;
          renderSubAreas();
          alert(t('a5s.plan.colorSaveFailed', 'บันทึกสีไม่สำเร็จ'));
        });
      }

      document.querySelector('[data-sub-zoom-in]').addEventListener('click', () => setSubZoom(subZoom * 1.35));
      document.querySelector('[data-sub-zoom-out]').addEventListener('click', () => setSubZoom(subZoom / 1.35));
      document.querySelector('[data-sub-zoom-reset]').addEventListener('click', () => { resetSubZoom(); renderSubAreas(); });
      // ลบโซนย่อย ย้ายไปเป็นปุ่ม × บนภาพการ์ดในขั้น 2 แล้ว (ดู zoneMapGrid click handler)
      document.querySelector('[data-sub-map-edit]').addEventListener('click', () => openZoneMapDialog(zoneMapById(selectedZoneMapId)));
      document.querySelector('[data-sub-draw-start]').addEventListener('click', () => setSubDrawMode(true));
      document.querySelector('[data-sub-draw-cancel]').addEventListener('click', () => setSubDrawMode(false));
      document.querySelector('[data-sub-draw-undo]').addEventListener('click', () => { subDrawPoints.pop(); renderSubAreas(); });
      document.querySelector('[data-sub-draw-finish]').addEventListener('click', () => {
        const map = zoneMapById(selectedZoneMapId);
        if (!map) return;
        if (subDrawPoints.length < 3) { alert(t('a5s.plan.needThreePoints', 'ต้องมีอย่างน้อย 3 จุด')); return; }
        J(`{{ url('area5s/plan/zone-maps') }}/${map.id}/areas`, 'POST', { shape_points: subDrawPoints }).then(res => {
          if (!res.ok) { alert(res.message || t('a5s.plan.saveFailed', 'บันทึกไม่สำเร็จ')); return; }
          map.areas = map.areas || [];
          map.areas.push(res.area);
          setSubDrawMode(false);
          renderZoneMaps(selectedZone());
          selectSubArea(res.area.id);
        });
      });
      subStage.addEventListener('wheel', e => {
        e.preventDefault();
        const r = subStage.getBoundingClientRect();
        setSubZoom(subZoom * (e.deltaY < 0 ? 1.2 : 1 / 1.2), e.clientX - r.left, e.clientY - r.top);
      }, { passive: false });
      subAreaList.addEventListener('change', e => {
        const picker = e.target.closest('[data-sub-area-color]');
        if (picker) saveSubAreaColor(Number(picker.dataset.subAreaColor), picker.value);
      });
      subAreaList.addEventListener('click', e => {
        if (e.target.closest('.zc')) return;
        const it = e.target.closest('[data-sub-area-item]');
        if (it) selectSubArea(Number(it.dataset.subAreaItem));
      });
      subAreaForm.addEventListener('change', e => {
        const inp = e.target.closest('[data-sub-area-field]');
        if (inp) saveSubAreaField(inp);
      });
      document.querySelector('[data-sub-area-done]').addEventListener('click', clearSubAreaSelection);
      document.querySelector('[data-sub-area-delete]').addEventListener('click', () => {
        const area = subAreaById(selectedSubAreaId);
        const map = zoneMapById(selectedZoneMapId);
        if (!area || !map || !confirm(t('a5s.plan.deleteSubZoneAreaConfirm', 'ลบพื้นที่ย่อยนี้?'))) return;
        J(`{{ url('area5s/plan/zone-map-areas') }}/${area.id}`, 'DELETE').then(res => {
          if (!res.ok) { alert(res.message || t('a5s.plan.deleteFailed', 'ลบไม่สำเร็จ')); return; }
          map.areas = (map.areas || []).filter(a => a.id !== area.id);
          renderZoneMaps(selectedZone());
          clearSubAreaSelection();
        });
      });

      const subPointers = new Map();
      let subPtr = null;
      let subPinch = null;
      subStage.addEventListener('pointerdown', e => {
        subPointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (subPointers.size === 2) { startSubPinch(); subPtr = null; return; }
        if (subPointers.size > 2) return;

        const vertex = !subDrawMode ? e.target.closest('[data-sub-idx]') : null;
        if (vertex) {
          subPtr = { kind: 'vertex', areaId: Number(vertex.dataset.subArea), idx: Number(vertex.dataset.subIdx), moved: false };
        } else {
          subPtr = { kind: 'pan', sx: e.clientX, sy: e.clientY, px: subPanX, py: subPanY, moved: false, target: e.target };
        }
        try { subStage.setPointerCapture(e.pointerId); } catch (err) {}
      });
      subStage.addEventListener('pointermove', e => {
        if (subPointers.has(e.pointerId)) subPointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (subPinch) { moveSubPinch(); return; }
        if (!subPtr) return;

        if (subPtr.kind === 'vertex') {
          const area = subAreaById(subPtr.areaId);
          if (!area) return;
          area.shape_points[subPtr.idx] = toSubPct(e);
          subPtr.moved = true;
          renderSubAreas();
          return;
        }
        const dx = e.clientX - subPtr.sx, dy = e.clientY - subPtr.sy;
        if (!subPtr.moved && Math.abs(dx) + Math.abs(dy) > 4) { subPtr.moved = true; subStage.classList.add('is-pan'); }
        if (subPtr.moved) { subPanX = subPtr.px + dx; subPanY = subPtr.py + dy; clampSubPan(); subApplyTransform(); }
      });
      function endSubPointer(e) {
        subPointers.delete(e.pointerId);
        try { subStage.releasePointerCapture(e.pointerId); } catch (err) {}
        subStage.classList.remove('is-pan');
        if (subPinch && subPointers.size < 2) subPinch = null;
        if (!subPtr) return;
        const cur = subPtr; subPtr = null;

        if (cur.kind === 'vertex') {
          const area = subAreaById(cur.areaId);
          if (cur.moved && area) J(`{{ url('area5s/plan/zone-map-areas') }}/${cur.areaId}`, 'PUT', { shape_points: area.shape_points });
          return;
        }
        if (cur.moved) return;
        if (subDrawMode) { subDrawPoints.push(toSubPct(e)); renderSubAreas(); return; }
        const poly = cur.target.closest('[data-sub-area-poly]');
        if (poly) selectSubArea(Number(poly.dataset.subAreaPoly));
      }
      subStage.addEventListener('pointerup', endSubPointer);
      subStage.addEventListener('pointercancel', endSubPointer);
      function startSubPinch() {
        const pts = [...subPointers.values()];
        subPinch = { d: dist(pts[0], pts[1]) };
      }
      function moveSubPinch() {
        const pts = [...subPointers.values()];
        if (pts.length < 2) return;
        const nd = dist(pts[0], pts[1]);
        const r = subStage.getBoundingClientRect();
        const mx = (pts[0].x + pts[1].x) / 2 - r.left, my = (pts[0].y + pts[1].y) / 2 - r.top;
        if (subPinch.d > 0) setSubZoom(subZoom * (nd / subPinch.d), mx, my);
        subPinch.d = nd;
      }

      document.addEventListener('insight:languagechange', () => {
        render();
        renderZoneMaps(selectedZone());
        if (selectedZoneMapId) {
          renderAreaFloors(subAreaById(selectedSubAreaId));
          renderSubAreas();
        }
      });

      resetZoom();
      if (!planImage.complete) planImage.addEventListener('load', resetZoom, { once:true });
    })();
  </script>
  @endif

  <script>
    'use strict';
    // ปุ่มเลือกไฟล์: โชว์ชื่อไฟล์ที่เลือกแทนข้อความ "No file chosen" ของเบราว์เซอร์
    (function () {
      document.querySelectorAll('[data-file-input]').forEach(function (input) {
        var scope = input.closest('form') || input.closest('label') || document;
        var out = scope.querySelector('[data-file-name]');
        if (!out) return;
        input.addEventListener('change', function () {
          var file = input.files && input.files[0];
          out.textContent = file ? file.name : '';
        });
      });
    })();
  </script>
@endsection
