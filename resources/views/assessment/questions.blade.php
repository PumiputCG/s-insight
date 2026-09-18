@extends('layouts.portal')

@section('title', 'กำหนดคำถาม')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.questions.title">กำหนดคำถาม</span>
@endsection

@php
  $levels = ($evalLevels ?? []) ?: [1];
  $lvNow = (int) ($activeLevel ?? $levels[0]);
  $noteOf = fn (int $boxId) => ($notes[$boxId][$lvNow] ?? null);
  $fmtNum = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
  $propText = function (?string $mode, $weight) use ($fmtNum): string {
    if ($mode === 'extra') { $n = (float) ($weight ?? 0); return 'เพิ่มเติม '.($n > 0 ? '+' : '').$fmtNum($n); }
    if ($mode === 'percent') return $fmtNum($weight ?? 0).'%';
    return '-';
  };
  // ส่งค่าคำอธิบายทั้งหมดให้ JS ใช้ใน modal — key = box id หรือ 'slot:xxx'
  $noteData = [];
  $pack = function ($n, string $name): array {
    return [
      'name' => $name,
      'th' => $n->desc_th ?? '', 'en' => $n->desc_en ?? '', 'my' => $n->desc_my ?? '',
      'on' => $n === null ? true : (bool) $n->is_visible,
    ];
  };
  foreach ($sections ?? [] as $s) {
    foreach (array_merge([['id' => $s['id'], 'name' => $s['name']]], $s['columns']) as $b) {
      $noteData[(string) $b['id']] = $pack($noteOf((int) $b['id']), $b['name']);
    }
  }
  // จุดพิเศษ: คะแนนรวมในหัวฟอร์ม
  $totalNote = ($slotNotes['total'][$lvNow] ?? null);
  $noteData['slot:total'] = $pack($totalNote, 'คะแนนรวม');

  // ชุดตัวเลือกคะแนนของคอลัมน์ Input — ส่งให้ JS ใช้ใน modal ตั้งค่า
  $scaleData = [];
  foreach ($sections ?? [] as $s) {
    foreach ($s['columns'] as $c) {
      if (! empty($c['is_input'])) {
        $scaleData[(string) $c['id']] = ['name' => $c['name'], 'rows' => $c['scale'] ?? [], 'custom' => (bool) ($c['scale_custom'] ?? false)];
      }
    }
  }
@endphp

@section('page-style')
    .qn-tabs { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin:0 0 .55rem; }
    .qn-tab { padding:.4rem 1rem; border:1px solid var(--line-light); border-radius:0.25rem; background:var(--panel-soft); color:inherit; text-decoration:none; font-size:.82rem; font-weight:700; }
    .qn-tab:hover { border-color:var(--moss); }
    .qn-tab.is-active { background:var(--moss); border-color:var(--moss); color:#fff; }
    .hint { color:var(--muted-light); font-size:.82rem; margin:.2rem 0 1rem; }

    /* ---- แผ่นกระดาษ: ค่าเดียวกับแบบฟอร์มจริง ---- */
    .paper-page { display:grid; gap:.9rem; width:min(100%, 56rem); margin:0 auto; }
    .paper-sheet { background:#fdfcf8; color:#26251f; border:1px solid #e3dfd2; border-radius:0.25rem; box-shadow:0 14px 40px rgb(0 0 0 / 16%); padding:2.2rem 2.6rem 2.4rem; }
    @media (max-width:700px) { .paper-sheet { padding:1.3rem 1.1rem 1.6rem; } }
    .paper-header { text-align:center; padding-bottom:1.1rem; border-bottom:2px solid #26251f; }
    .paper-logo { display:block; width:3.1rem; height:3.1rem; margin:0 auto .45rem; object-fit:contain; }
    .paper-header h2 { margin:0 0 .2rem; color:#26251f; font-size:1.22rem; font-weight:800; }
    .paper-header p { margin:0; color:#6f6b5e; font-size:.82rem; }

    .paper-profile-row { display:grid; grid-template-columns:7rem minmax(0,1fr); gap:1.75rem; padding:1rem .2rem 1.1rem; border-bottom:1px solid #26251f; }
    .paper-profile-thumb { width:7rem; min-height:7rem; border:1.5px solid #9d9785; border-radius:.28rem; background:#f1eee4; color:#777263; display:grid; place-items:center; overflow:hidden; }
    .paper-profile-thumb svg { width:68%; height:68%; }
    .paper-info { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:.7rem 1.5rem; font-size:.86rem; }
    .paper-info div { min-width:0; padding-bottom:.42rem; border-bottom:1px dashed #d8d2bf; }
    .paper-info span { display:block; color:#6f6b5e; font-size:.78rem; font-weight:700; }
    .paper-info b { display:block; margin-top:.12rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.92rem; font-weight:400; }
    /* คะแนนรวม — เด่นกว่าช่องอื่นในหัวฟอร์ม */
    .paper-total-info b { color:#3c5f2c; font-size:1.24rem; font-weight:800; line-height:1.25; }
    /* label + ไอคอน อยู่บรรทัดเดียวกัน (ทับ display:block ของ .paper-info span) */
    .paper-total-info .paper-total-label { display:flex; align-items:center; gap:.1rem; }
    .paper-total-info .paper-total-label > span { display:inline; }
    @media (max-width:600px) { .paper-profile-row { grid-template-columns:1fr; } .paper-info { grid-template-columns:1fr; } }

    .paper-prop-section { padding:1rem .2rem 0; }
    .paper-prop-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:.7rem; }
    .paper-prop-head h3 { margin:0; color:#26251f; font-size:1rem; font-weight:800; }
    .paper-prop-level { color:#6f6b5e; font-size:.78rem; }
    .paper-prop-list { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.45rem 1.2rem; }
    .paper-prop-row { display:flex; align-items:baseline; justify-content:space-between; gap:.75rem; min-width:0; padding:.42rem 0; border-bottom:1px dashed #d8d2bf; }
    .paper-prop-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#26251f; font-size:.86rem; }
    .paper-prop-value { flex:none; color:#26251f; font-size:.86rem; font-variant-numeric:tabular-nums; }
    .paper-prop-value.is-muted { color:#777263; }

    .paper-section-rule { margin:1rem .2rem 0; border-top:2px solid #26251f; }
    .paper-columns-section { padding:1rem .2rem 0; }
    .paper-columns-title { margin:0 0 .7rem; color:#26251f; font-size:1rem; font-weight:800; }
    .paper-col-block + .paper-col-block { margin-top:1.3rem; padding-top:1.15rem; border-top:2px solid #26251f; }
    .paper-col-title { margin:0 0 .42rem; color:#26251f; font-size:.92rem; font-weight:800; }
    .paper-col-table { border-top:1px solid #26251f; margin-top:.6rem; }
    .paper-col-row { display:grid; grid-template-columns:minmax(0,1fr) 7rem; gap:.85rem; align-items:baseline; padding:.42rem 0; border-bottom:1px dashed #d8d2bf; }
    .paper-col-name { overflow:visible; }   /* ให้ไอคอนท้ายชื่อไม่โดนตัด */

    /* ช่องพิมพ์คำอธิบายของหัวข้อหลัก — อยู่ตำแหน่งเดียวกับที่ผู้ประเมินจะเห็น */
    .qn-inline { margin:.15rem 0 .2rem; }
    .qn-inline-bar { display:flex; align-items:center; gap:.4rem; margin-bottom:.2rem; }
    .qn-inline-ta { width:100%; padding:.45rem .55rem; border:1px dashed #c9d5c0; border-radius:.22rem; background:#fffefb; color:#6f6b5e; font:inherit; font-size:.84rem; line-height:1.55; resize:vertical; white-space:pre-wrap; }
    .qn-inline-ta:focus { outline:none; border-style:solid; border-color:#3c5f2c; color:#26251f; }

    /* ปุ่มซ่อนทั้งคอลัมน์ */
    .qn-hide { display:inline-grid; place-items:center; width:1rem; height:1rem; margin-left:.25rem; padding:0; border:0; background:transparent; color:#cfc9b6; cursor:pointer; vertical-align:-2px; }
    .qn-hide svg { width:100%; height:100%; }
    .qn-hide .qn-hide-slash { opacity:0; }
    .qn-hide:hover, .qn-hide:focus-visible { color:#6f6b5e; outline:none; }
    .qn-hide.is-hidden { color:#c58a80; }
    .qn-hide.is-hidden .qn-hide-slash { opacity:1; }
    /* แถวที่ถูกซ่อน — จางลงให้เห็นว่าผู้ประเมินจะไม่เห็น */
    .paper-col-row.is-hidden, .paper-col-block.is-hidden { opacity:.4; }
    .paper-col-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#26251f; font-size:.84rem; }
    .paper-col-value { color:#26251f; text-align:right; font-size:.84rem; font-variant-numeric:tabular-nums; }

    /* ปุ่มตั้งตัวเลือกคะแนน (เฉพาะคอลัมน์ Input) */
    .qn-scale-btn { display:inline-grid; place-items:center; width:1rem; height:1rem; margin-left:.25rem; padding:0; border:0; background:transparent; color:#cfc9b6; cursor:pointer; vertical-align:-2px; }
    .qn-scale-btn svg { width:100%; height:100%; }
    .qn-scale-btn:hover, .qn-scale-btn:focus-visible { color:#3c5f2c; outline:none; }
    .qn-scale-btn.is-custom { color:#3c5f2c; }   /* ตั้งเองแล้ว */

    /* ================= Modal ตั้งตัวเลือกคะแนน =================
       แสดงทีละภาษา (ปุ่ม TH/EN/MY) → 1 ตัวเลือก = 1 บรรทัด อ่านง่าย ไม่ยาว */
    .qn-sc-sub { margin:.15rem 0 0; color:#6f6b5e; font-size:.76rem; font-weight:600; }
    .qn-sc-bar { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin:.75rem 0 .5rem; }
    .qn-sc-hint { flex:1; min-width:12rem; margin:0; color:#6f6b5e; font-size:.74rem; }

    .qn-sc-tbl { border:1px solid #e3dfd2; border-radius:.24rem; background:#fffefb; overflow:hidden; }
    .qn-sc-r { display:grid; grid-template-columns:1.3rem minmax(6rem,1fr) minmax(0,1.35fr) 1.8rem 1.5rem; gap:.5rem; align-items:center; padding:.34rem .5rem; }
    .qn-sc-r + .qn-sc-r, .qn-sc-tbl > div > .qn-sc-r { border-top:1px solid #f0ece0; }
    .qn-sc-th { padding:.32rem .5rem; background:#f4f1e6; color:#8a8574; font-size:.66rem; font-weight:800; letter-spacing:.02em; text-transform:uppercase; }
    .qn-sc-th .qn-sc-c { text-align:center; }
    .qn-sc-no { color:#a8a294; font-size:.72rem; font-weight:700; font-variant-numeric:tabular-nums; }
    .qn-sc-r input[type="text"] { width:100%; min-width:0; padding:.28rem .42rem; border:1px solid #ded9c8; border-radius:.2rem; background:#fff; color:#26251f; font:inherit; font-size:.79rem; }
    .qn-sc-r input[type="text"]:focus { outline:none; border-color:#3c5f2c; }
    .qn-sc-nacell { display:grid; place-items:center; }

    /* ช่องคะแนน: chip เล็ก ลบด้วยกากบาทที่โผล่ตอนชี้ */
    .qn-sc-chips { display:flex; align-items:center; gap:.22rem; flex-wrap:wrap; }
    .qn-sc-chip { position:relative; display:inline-flex; }
    .qn-sc-chip input { width:2.7rem; padding:.24rem .2rem; border:1px solid #ded9c8; border-radius:.2rem; background:#fff; color:#26251f; font:inherit; font-size:.76rem; font-weight:700; text-align:center; font-variant-numeric:tabular-nums; }
    .qn-sc-chip input:focus { outline:none; border-color:#3c5f2c; }
    .qn-sc-chip .qn-sc-x { position:absolute; top:-.3rem; right:-.3rem; width:.9rem; height:.9rem; display:grid; place-items:center; border:1px solid #ded9c8; border-radius:50%; background:#fff; color:#a8a294; cursor:pointer; font-size:.56rem; line-height:1; opacity:0; transition:opacity .12s ease; }
    .qn-sc-chip:hover .qn-sc-x, .qn-sc-chip:focus-within .qn-sc-x { opacity:1; }
    .qn-sc-chip .qn-sc-x:hover { border-color:#c58a80; color:#c58a80; }
    .qn-sc-plus { width:1.45rem; height:1.45rem; display:grid; place-items:center; border:1px dashed #b9c9ac; border-radius:.2rem; background:#fff; color:#3c5f2c; cursor:pointer; font-size:.8rem; line-height:1; }
    .qn-sc-plus:hover { border-style:solid; background:#f2f7ee; }

    .qn-sc-del { width:1.4rem; height:1.4rem; display:grid; place-items:center; border:1px solid transparent; border-radius:.2rem; background:transparent; color:#c2bcab; cursor:pointer; font-size:.72rem; }
    .qn-sc-del:hover { border-color:#c58a80; color:#c58a80; }

    .qn-sc-addrow { width:100%; margin-top:.4rem; padding:.4rem; border:1px dashed #c9d5c0; border-radius:.22rem; background:#fff; color:#3c5f2c; cursor:pointer; font:inherit; font-size:.76rem; font-weight:700; }
    .qn-sc-addrow:hover { border-style:solid; background:#f2f7ee; }

    .qn-sc-foot { display:flex; align-items:center; gap:.5rem; margin-top:.8rem; padding-top:.7rem; border-top:1px solid #e3dfd2; }
    .qn-sc-btn { padding:.34rem .85rem; border:1px solid #cfc9b6; border-radius:.22rem; background:#fff; color:#26251f; cursor:pointer; font:inherit; font-size:.76rem; font-weight:700; }
    .qn-sc-btn:hover { border-color:#3c5f2c; color:#3c5f2c; }
    .qn-sc-btn.primary { margin-left:auto; background:#3c5f2c; border-color:#3c5f2c; color:#fff; }
    .qn-sc-btn.primary:hover { color:#fff; }
    @media (max-width:560px) { .qn-sc-r { grid-template-columns:1.2rem minmax(0,1fr) 1.6rem 1.4rem; } .qn-sc-r > .qn-sc-chips { grid-column:2 / -1; } }


    /* ---- ไอคอนคำอธิบาย: สีบอกสถานะในตัว ไม่ต้องมีข้อความกำกับ ---- */
    .qn-i { display:inline-grid; place-items:center; width:1rem; height:1rem; margin-left:.3rem; padding:0; border:0; border-radius:50%; background:transparent; cursor:pointer; vertical-align:-2px; color:#cfc9b6; }
    .qn-i svg { width:100%; height:100%; }
    .qn-i:hover, .qn-i:focus-visible { color:#3c5f2c; outline:none; }
    .qn-i.has-note { color:#3c5f2c; }        /* มีคำอธิบาย + เปิดแสดง */
    .qn-i.is-off { color:#c58a80; }          /* มีคำอธิบาย แต่ปิดไว้ (User ไม่เห็น) */

    /* ---- Modal ---- */
    .note-modal { position:fixed; inset:0; z-index:80; display:grid; place-items:center; padding:1rem; }
    .note-modal[hidden] { display:none; }
    .note-modal-back { position:absolute; inset:0; background:rgb(20 22 18 / 45%); }
    .note-modal-sheet { position:relative; width:min(100%, 32rem); max-height:82vh; overflow:auto; padding:1.1rem 1.3rem 1.2rem; border:1px solid #e3dfd2; border-radius:.28rem; background:#fdfcf8; color:#26251f; box-shadow:0 20px 60px rgb(0 0 0 / 28%); }
    .note-modal-head { display:flex; align-items:flex-start; gap:.8rem; padding-bottom:.6rem; border-bottom:1px solid #26251f; }
    .note-modal-head h3 { flex:1; margin:0; font-size:.96rem; font-weight:800; }
    .note-modal-x { flex:none; padding:.1rem .2rem; border:0; background:transparent; color:#6f6b5e; cursor:pointer; font-size:.9rem; }
    .note-modal-x:hover { color:#26251f; }
    .qn-modal-bar { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin:.75rem 0 .5rem; }
    .qn-langs { display:inline-flex; gap:.12rem; margin-left:auto; padding:.1rem; border:1px solid #cfc9b6; border-radius:.22rem; background:#fff; }
    .qn-lang-btn { min-width:2.2rem; padding:.1rem .4rem; border:0; border-radius:.18rem; background:transparent; color:#6f6b5e; cursor:pointer; font-size:.68rem; font-weight:700; }
    .qn-lang-btn.is-active { background:#3c5f2c; color:#fff; }
    .qn-hide-lg { width:1.35rem; height:1.35rem; margin:0; color:#6f6b5e; }
    .qn-hide-label { color:#6f6b5e; font-size:.76rem; font-weight:700; }
    .qn-modal-ta { width:100%; min-height:7rem; padding:.6rem .7rem; border:1px solid #c9d5c0; border-radius:.22rem; background:#fff; color:#26251f; font:inherit; font-size:.86rem; line-height:1.6; resize:vertical; white-space:pre-wrap; }
    .qn-modal-ta:focus { outline:none; border-color:#3c5f2c; }
    .qn-modal-foot { display:flex; align-items:center; gap:.5rem; margin-top:.5rem; color:#6f6b5e; font-size:.72rem; }
    .qn-saved { color:#3c5f2c; font-weight:700; opacity:0; transition:opacity .2s; }
    .qn-saved.on { opacity:1; }
@endsection

@section('content')
  @include('assessment.partials.asmtabs')

  @if (session('error'))
    <div class="flash error" style="margin-bottom:1rem">{{ session('error') }}</div>
  @endif

  <div class="qn-tabs">
    @foreach ($levels as $lv)
      <a href="{{ route('assessment.questions.index', ['level' => $lv]) }}" class="qn-tab nav-go {{ $lvNow === (int) $lv ? 'is-active' : '' }}"><span data-i18n="assessment.levelShort">ระดับ</span> {{ $lv }}</a>
    @endforeach
  </div>

  <p class="hint" data-i18n="assessment.questions.hint3">กดไอคอน ⓘ ข้างชื่อหัวข้อหรือคอลัมน์ เพื่อเขียนคำอธิบายและเปิด/ปิดให้ผู้ประเมินเห็น · เขียว = เปิดแสดง · แดง = มีข้อความแต่ปิดไว้ · เทา = ยังไม่มี</p>

  @if (($sections ?? collect())->isEmpty())
    <p class="hint" data-i18n="assessment.questions.noWeights">ระดับนี้ยังไม่ได้ตั้งสัดส่วนคะแนน — ไปกำหนดที่หน้า จัดการ หัวข้อ 3.1 ก่อน</p>
  @else
    <div class="paper-page">
      <div class="paper-sheet">
        <div class="paper-header">
          <img class="paper-logo" src="{{ asset('assets/assessment/form-logo.png') }}" alt="" aria-hidden="true">
          <h2 data-i18n="assessment.form.performanceTitle">แบบประเมินผลการปฏิบัติงานพนักงาน</h2>
          <p>{{ $openRound->name }} ({{ $openRound->year }}) ·
             <span data-i18n="assessment.questions.previewFor">ตัวอย่างที่จะแสดงในแบบฟอร์มของพนักงาน</span>
             <b><span data-i18n="assessment.levelShort">ระดับ</span> {{ $lvNow }}</b></p>
        </div>

        <div class="paper-profile-row">
          <div class="paper-profile-thumb">@include('assessment.partials.default-avatar')</div>
          <div class="paper-info">
            <div><span data-i18n="assessment.form.fullName">ชื่อ-สกุล :</span> <b data-i18n="assessment.questions.demoName">(ตัวอย่าง) นายสมชาย ใจดี</b></div>
            <div><span data-i18n="assessment.form.employeeCode">รหัสพนักงาน :</span> <b>00000</b></div>
            <div class="paper-total-info">
              <span class="paper-total-label"><span data-i18n="assessment.form.totalScore">คะแนนรวม :</span>@include('assessment.partials.qnicon', ['key' => 'slot:total', 'note' => $totalNote])@include('assessment.partials.qnhide', ['key' => 'slot:total', 'note' => $totalNote])</span>
              <b>85.00</b>
            </div>
            <div><span data-i18n="assessment.form.position">ตำแหน่ง :</span> <b><span data-i18n="assessment.questions.demoPosition">(ตัวอย่าง) ตำแหน่งระดับ</span> {{ $lvNow }}</b></div>
            <div><span data-i18n="assessment.form.department">แผนก :</span> <b>(ตัวอย่าง)</b></div>
          </div>
        </div>

        <section class="paper-prop-section">
          <div class="paper-prop-head">
            <h3 data-i18n="assessment.form.scoreWeightTitle">หัวข้อสัดส่วนคะแนน</h3>
            <span class="paper-prop-level"><span data-i18n="assessment.form.level">ระดับ</span> {{ $lvNow }}</span>
          </div>
          <div class="paper-prop-list">
            @foreach ($sections as $section)
              @php $disp = $propText($section['mode'], $section['weight']); @endphp
              <div class="paper-prop-row">
                <span class="paper-prop-name" title="{{ $section['name'] }}">{{ $section['name'] }}</span>
                <span class="paper-prop-value {{ $disp === '-' ? 'is-muted' : '' }}">{{ $disp }}</span>
              </div>
            @endforeach
          </div>
        </section>

        <div class="paper-section-rule" aria-hidden="true"></div>

        <section class="paper-columns-section">
          <h3 class="paper-columns-title" data-i18n="assessment.form.performanceSection">ประเมินผลการปฏิบัติงาน</h3>

          @foreach ($sections as $si => $section)
            <div class="paper-col-block">
              @php $mainNote = $noteOf((int) $section['id']); @endphp
              <h4 class="paper-col-title">
                <span data-i18n="assessment.common.section">หัวข้อ</span> {{ $si + 1 }} {{ $section['name'] }}
                @include('assessment.partials.qnhide', ['key' => $section['id'], 'note' => $mainNote])
              </h4>

              {{-- หัวข้อหลัก: พิมพ์คำอธิบายตรงนี้เลย (ตำแหน่งเดียวกับที่ผู้ประเมินจะเห็น) --}}
              <div class="qn-inline" data-qn-box="{{ $section['id'] }}">
                <div class="qn-inline-bar">
                  <div class="qn-langs" role="group" aria-label="ภาษา">
                    <button type="button" class="qn-lang-btn is-active" data-qn-ilang="th">TH</button>
                    <button type="button" class="qn-lang-btn" data-qn-ilang="en">EN</button>
                    <button type="button" class="qn-lang-btn" data-qn-ilang="my">MY</button>
                  </div>
                  <span class="qn-saved" data-qn-isaved>✓</span>
                </div>
                <textarea class="qn-inline-ta" data-qn-ita
                          data-box="{{ $section['id'] }}" data-level="{{ $lvNow }}" rows="2"
                          placeholder="คำอธิบายของหัวข้อนี้ — เว้นบรรทัดได้ ผู้ประเมินจะเห็นตามที่พิมพ์">{{ $mainNote->desc_th ?? '' }}</textarea>
              </div>

              <div class="paper-col-table">
                @foreach ($section['columns'] as $col)
                  <div class="paper-col-row">
                    <span class="paper-col-name" title="{{ $col['name'] }}">{{ $col['name'] }}@include('assessment.partials.qnicon', ['key' => $col['id'], 'note' => $noteOf((int) $col['id'])])@include('assessment.partials.qnhide', ['key' => $col['id'], 'note' => $noteOf((int) $col['id'])])@if (! empty($col['is_input']))<button type="button" class="qn-scale-btn {{ ! empty($col['scale_custom']) ? 'is-custom' : '' }}" data-qn-scale="{{ $col['id'] }}" title="ตั้งตัวเลือกคะแนน" aria-label="ตั้งตัวเลือกคะแนน"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg></button>@endif</span>
                    <span class="paper-col-value">{{ $col['value'] }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </section>
      </div>
    </div>

    {{-- Modal แก้คำอธิบาย --}}
    <div class="note-modal" data-qn-modal hidden>
      <div class="note-modal-back" data-qn-close></div>
      <div class="note-modal-sheet" role="dialog" aria-modal="true">
        <div class="note-modal-head">
          <h3 data-qn-title></h3>
          <button type="button" class="note-modal-x" data-qn-close aria-label="ปิด">✕</button>
        </div>
        <div class="qn-modal-bar">
          <button type="button" class="qn-hide qn-hide-lg" data-qn-eye aria-pressed="false" title="ซ่อนคอลัมน์นี้จากผู้ประเมิน">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
              <line class="qn-hide-slash" x1="3" y1="21" x2="21" y2="3" />
            </svg>
          </button>
          <span class="qn-hide-label" data-qn-eye-text>แสดงอยู่</span>
          <div class="qn-langs" role="group" aria-label="ภาษา">
            <button type="button" class="qn-lang-btn is-active" data-qn-lang="th">TH</button>
            <button type="button" class="qn-lang-btn" data-qn-lang="en">EN</button>
            <button type="button" class="qn-lang-btn" data-qn-lang="my">MY</button>
          </div>
        </div>
        <textarea class="qn-modal-ta" data-qn-ta placeholder="คำอธิบายของคอลัมน์นี้ — เว้นบรรทัดได้ ผู้ประเมินจะเห็นตามที่พิมพ์"></textarea>
        <div class="qn-modal-foot">
          <span data-i18n="assessment.questions.autosave">บันทึกอัตโนมัติ</span>
          <span class="qn-saved" data-qn-saved>✓</span>
        </div>
      </div>
    </div>

    {{-- Modal ตั้งตัวเลือกคะแนน (คอลัมน์ชนิด Input) --}}
    <div class="note-modal" data-qn-scale-modal hidden>
      <div class="note-modal-back" data-qn-scale-close></div>
      <div class="note-modal-sheet" role="dialog" aria-modal="true">
        <div class="note-modal-head">
          <div>
            <h3 data-i18n="assessment.questions.scaleTitle">ตัวเลือกคะแนน</h3>
            <p class="qn-sc-sub"><span data-qn-scale-name></span> · <span data-i18n="assessment.levelShort">ระดับ</span> {{ $lvNow }}</p>
          </div>
          <button type="button" class="note-modal-x" data-qn-scale-close aria-label="ปิด">✕</button>
        </div>

        <div class="qn-sc-bar">
          <span class="qn-sc-hint" data-i18n="assessment.questions.scaleHint">ผู้ประเมินจะเห็นตัวเลือกเหล่านี้เรียงจากบนลงล่าง</span>
          <div class="qn-langs" role="group" aria-label="ภาษาที่กำลังแก้">
            <button type="button" class="qn-lang-btn is-active" data-sc-lang="th">TH</button>
            <button type="button" class="qn-lang-btn" data-sc-lang="en">EN</button>
            <button type="button" class="qn-lang-btn" data-sc-lang="my">MY</button>
          </div>
        </div>

        <div class="qn-sc-tbl">
          <div class="qn-sc-r qn-sc-th">
            <span></span>
            <span data-i18n="assessment.questions.scaleColName">ตัวเลือก</span>
            <span data-i18n="assessment.questions.scaleColScores">คะแนน</span>
            <span class="qn-sc-c" title="ไม่นำมาคำนวณ">N/A</span>
            <span></span>
          </div>
          <div data-qn-scale-rows></div>
        </div>

        <button type="button" class="qn-sc-addrow" data-qn-scale-add>
          <span data-i18n="assessment.questions.scaleAdd">+ เพิ่มตัวเลือก</span>
        </button>

        <div class="qn-sc-foot">
          <button type="button" class="qn-sc-btn" data-qn-scale-reset data-i18n="assessment.questions.scaleReset">คืนค่าเริ่มต้น</button>
          <span class="qn-saved" data-qn-scale-saved>✓ บันทึกแล้ว</span>
          <button type="button" class="qn-sc-btn primary" data-qn-scale-save data-i18n="assessment.questions.scaleSave">บันทึก</button>
        </div>
      </div>
    </div>
  @endif
@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const url = `{{ route('assessment.questions.note') }}`;
      const level = {{ $lvNow }};
      const data = @json($noteData);

      const modal = document.querySelector('[data-qn-modal]');
      if (!modal) return;
      const elTitle = modal.querySelector('[data-qn-title]');
      const elTa = modal.querySelector('[data-qn-ta]');
      const elEye = modal.querySelector('[data-qn-eye]');
      const elEyeTx = modal.querySelector('[data-qn-eye-text]');
      const elSaved = modal.querySelector('[data-qn-saved]');
      let boxId = null, lang = 'th';

      // key = box id ('87') หรือจุดพิเศษ ('slot:total') — แปลงเป็น payload ที่ backend รับ
      const keyPayload = (key) => String(key).startsWith('slot:')
        ? { slot: String(key).slice(5) }
        : { box_id: +key };

      // รับ key ตรง ๆ กันชนกันตอนบันทึกหลายจุดพร้อมกัน
      const save = (key, payload) => fetch(url, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(Object.assign(keyPayload(key), { level: level }, payload)),
      }).then((r) => r.json());

      const flash = () => { elSaved.classList.add('on'); setTimeout(() => elSaved.classList.remove('on'), 900); };

      // ไอคอนสะท้อนสถานะ: เขียว = เปิด+มีข้อความ · แดง = ปิดแต่มีข้อความ · เทา = ว่าง
      const paint = (id) => {
        const d = data[id]; if (!d) return;
        const has = !!(d.th || d.en || d.my);
        document.querySelectorAll(`[data-qn-icon="${id}"]`).forEach((el) => {
          el.classList.toggle('has-note', has && d.on);
          el.classList.toggle('is-off', has && !d.on);
        });
        // ปุ่มซ่อน + ความจางของแถว ให้ตรงกับสถานะล่าสุด
        document.querySelectorAll(`[data-qn-hide="${id}"]`).forEach((el) => {
          el.classList.toggle('is-hidden', !d.on);
          el.setAttribute('aria-pressed', d.on ? 'false' : 'true');
          el.closest('.paper-col-row, .paper-col-block')?.classList.toggle('is-hidden', !d.on);
        });
      };

      // on = คอลัมน์ยังแสดงอยู่ · aria-pressed = "ถูกซ่อน" (ปุ่มคือคำสั่งซ่อน)
      const setEye = (on) => {
        elEye.setAttribute('aria-pressed', on ? 'false' : 'true');
        elEye.classList.toggle('is-hidden', !on);
        elEyeTx.textContent = on ? 'แสดงอยู่' : 'ซ่อนจากผู้ประเมิน';
      };

      const close = () => { modal.hidden = true; boxId = null; };

      document.addEventListener('click', (e) => {
        const icon = e.target.closest('[data-qn-icon]');
        if (icon) {
          e.preventDefault();
          boxId = icon.dataset.qnIcon;
          lang = 'th';
          const d = data[boxId] || { name: '', th: '', en: '', my: '', on: true };
          elTitle.textContent = d.name;
          elTa.value = d.th;
          setEye(d.on);
          modal.querySelectorAll('[data-qn-lang]').forEach((b) => b.classList.toggle('is-active', b.dataset.qnLang === 'th'));
          modal.hidden = false;
          elTa.focus();
          return;
        }
        if (e.target.closest('[data-qn-close]')) close();
      });

      modal.querySelectorAll('[data-qn-lang]').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (!boxId) return;
          data[boxId][lang] = elTa.value;          // เก็บของภาษาเดิมไว้ก่อนสลับ
          lang = btn.dataset.qnLang;
          elTa.value = data[boxId][lang] || '';
          modal.querySelectorAll('[data-qn-lang]').forEach((b) => b.classList.toggle('is-active', b === btn));
        });
      });

      elTa.addEventListener('blur', async () => {
        if (!boxId || data[boxId][lang] === elTa.value) return;
        const id = boxId;
        data[id][lang] = elTa.value;
        const res = await save(id, { ['desc_' + lang]: elTa.value }).catch(() => null);
        if (res && res.ok) { paint(id); flash(); }
      });

      elEye.addEventListener('click', async () => {
        if (!boxId) return;
        const id = boxId;
        const next = elEye.getAttribute('aria-pressed') === 'true';   // ซ่อนอยู่ → กดแล้วกลับมาแสดง
        const res = await save(id, { is_visible: next }).catch(() => null);
        if (!res || !res.ok) return;
        data[id].on = next;
        setEye(next);
        paint(id);
        flash();
      });

      document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });

      // ================= ตัวเลือกคะแนนของคอลัมน์ Input =================
      const scaleUrl = `{{ route('assessment.questions.scale') }}`;
      const scaleData = @json($scaleData);
      const scModal = document.querySelector('[data-qn-scale-modal]');
      if (scModal) {
        const scName = scModal.querySelector('[data-qn-scale-name]');
        const scRows = scModal.querySelector('[data-qn-scale-rows]');
        const scSaved = scModal.querySelector('[data-qn-scale-saved]');
        let scBox = null;
        let rows = [];       // สถานะจริงของตัวเลือก (เก็บครบ 3 ภาษา)
        let scLang = 'th';   // ภาษาที่กำลังแก้อยู่

        const esc = (s) => String(s == null ? '' : s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
        const ph = { th: 'เสมอ', en: 'Always', my: 'အမြဲတမ်း' };

        const chipHtml = (v) => `<span class="qn-sc-chip"><input type="text" data-sc-score value="${esc(v)}" aria-label="คะแนน"><button type="button" class="qn-sc-x" data-sc-score-del aria-label="ลบคะแนนนี้">✕</button></span>`;

        const rowHtml = (r, i) => `<div class="qn-sc-r" data-sc-row>
            <span class="qn-sc-no">${i + 1}</span>
            <input type="text" data-sc-name value="${esc(r[scLang] || '')}" placeholder="${esc(ph[scLang])}">
            <span class="qn-sc-chips" data-sc-chips>${(r.scores || []).map(chipHtml).join('')}<button type="button" class="qn-sc-plus" data-sc-score-add aria-label="เพิ่มช่องคะแนน" title="เพิ่มช่องคะแนน">+</button></span>
            <span class="qn-sc-nacell"><input type="checkbox" data-sc-na ${r.na ? 'checked' : ''} aria-label="ไม่นำมาคำนวณ"></span>
            <button type="button" class="qn-sc-del" data-sc-del aria-label="ลบตัวเลือกนี้" title="ลบตัวเลือกนี้">✕</button>
          </div>`;

        const render = () => { scRows.innerHTML = rows.map(rowHtml).join(''); };

        // ดึงค่าที่พิมพ์ใน DOM กลับเข้า state (ต้องเรียกก่อนสลับภาษา/เพิ่ม/ลบ/บันทึก)
        const sync = () => {
          const els = Array.from(scRows.querySelectorAll('[data-sc-row]'));
          rows = els.map((el, i) => {
            const base = rows[i] || { th: '', en: '', my: '', scores: [], na: false };
            return Object.assign({}, base, {
              [scLang]: el.querySelector('[data-sc-name]').value.trim(),
              na: el.querySelector('[data-sc-na]').checked,
              scores: Array.from(el.querySelectorAll('[data-sc-score]')).map((x) => x.value.trim()).filter(Boolean),
            });
          });
        };

        const setLang = (lang) => {
          sync();
          scLang = lang;
          scModal.querySelectorAll('[data-sc-lang]').forEach((b) => b.classList.toggle('is-active', b.dataset.scLang === lang));
          render();
        };

        const closeSc = () => { scModal.hidden = true; scBox = null; };

        document.addEventListener('click', (e) => {
          const open = e.target.closest('[data-qn-scale]');
          if (open) {
            scBox = open.dataset.qnScale;
            const d = scaleData[scBox] || { name: '', rows: [] };
            scName.textContent = d.name;
            rows = JSON.parse(JSON.stringify(d.rows || []));
            scLang = 'th';
            scModal.querySelectorAll('[data-sc-lang]').forEach((b) => b.classList.toggle('is-active', b.dataset.scLang === 'th'));
            render();
            scModal.hidden = false;
            return;
          }
          if (e.target.closest('[data-qn-scale-close]')) { closeSc(); return; }
          if (scModal.hidden) return;

          const langBtn = e.target.closest('[data-sc-lang]');
          if (langBtn) { setLang(langBtn.dataset.scLang); return; }

          const del = e.target.closest('[data-sc-del]');
          if (del) {
            const idx = Array.from(scRows.querySelectorAll('[data-sc-row]')).indexOf(del.closest('[data-sc-row]'));
            sync();
            rows.splice(idx, 1);
            render();
            return;
          }

          if (e.target.closest('[data-qn-scale-add]')) {
            sync();
            rows.push({ th: '', en: '', my: '', scores: [''], na: false });
            render();
            scRows.querySelector('[data-sc-row]:last-of-type [data-sc-name]')?.focus();
            return;
          }

          const addScore = e.target.closest('[data-sc-score-add]');
          if (addScore) {
            addScore.insertAdjacentHTML('beforebegin', chipHtml(''));
            addScore.parentElement.querySelector('.qn-sc-chip:last-of-type input')?.focus();
            return;
          }

          const delScore = e.target.closest('[data-sc-score-del]');
          if (delScore) {
            const chips = delScore.closest('[data-sc-chips]');
            delScore.closest('.qn-sc-chip')?.remove();
            if (chips && !chips.querySelector('[data-sc-score]')) {
              chips.querySelector('[data-sc-score-add]')?.insertAdjacentHTML('beforebegin', chipHtml(''));
            }
          }
        });

        scModal.querySelector('[data-qn-scale-reset]').addEventListener('click', () => { rows = []; render(); });

        scModal.querySelector('[data-qn-scale-save]').addEventListener('click', async () => {
          if (!scBox) return;
          sync();
          const payload = rows.filter((r) => (r.th || r.en || r.my) && (r.scores || []).length > 0);
          const res = await fetch(scaleUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ box_id: +scBox, scale: payload }),
          }).then((r) => r.json()).catch(() => null);
          if (!res || !res.ok) return;
          scaleData[scBox].rows = payload;
          document.querySelectorAll(`[data-qn-scale="${scBox}"]`).forEach((b) => b.classList.toggle('is-custom', !res.using_default));
          scSaved.classList.add('on');
          setTimeout(() => { scSaved.classList.remove('on'); closeSc(); }, 700);
        });

        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !scModal.hidden) closeSc(); });
      }

      // ---- ปุ่มซ่อนทั้งคอลัมน์ (ในหน้า ไม่ใช่ใน modal) ----
      document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-qn-hide]');
        if (!btn) return;
        const id = btn.dataset.qnHide;
        const next = btn.classList.contains('is-hidden');   // ซ่อนอยู่ → กดแล้วกลับมาแสดง
        const res = await save(id, { is_visible: next }).catch(() => null);
        if (!res || !res.ok) return;
        if (data[id]) data[id].on = next;
        paint(id);
      });

      // ---- ช่องพิมพ์คำอธิบายของหัวข้อหลัก (inline) ----
      document.querySelectorAll('.qn-inline').forEach((wrap) => {
        const ta = wrap.querySelector('[data-qn-ita]');
        const savedMark = wrap.querySelector('[data-qn-isaved]');
        const id = String(ta.dataset.box);
        let ilang = 'th';
        wrap.querySelectorAll('[data-qn-ilang]').forEach((btn) => {
          btn.addEventListener('click', () => {
            if (data[id]) data[id][ilang] = ta.value;
            ilang = btn.dataset.qnIlang;
            ta.value = (data[id] && data[id][ilang]) || '';
            wrap.querySelectorAll('[data-qn-ilang]').forEach((b) => b.classList.toggle('is-active', b === btn));
          });
        });
        ta.addEventListener('blur', async () => {
          if (data[id] && data[id][ilang] === ta.value) return;
          if (data[id]) data[id][ilang] = ta.value;
          const res = await save(id, { ['desc_' + ilang]: ta.value }).catch(() => null);
          if (res && res.ok) {
            savedMark.classList.add('on');
            setTimeout(() => savedMark.classList.remove('on'), 900);
          }
        });
      });
    })();
  </script>
@endsection
