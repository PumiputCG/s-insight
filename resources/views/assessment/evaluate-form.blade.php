@extends('layouts.portal')

@section('title', 'แบบประเมินพนักงาน')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.evaluate.formTitle">แบบประเมินพนักงาน</span>
@endsection

@php
  // บทบาทผู้ประเมิน (ของ Codex) — คนเดียวคุม 1,2 = หัวหน้างานโดยตรง / ผู้ประเมินระดับฝ่าย
  $roleMeta = function (array $covers): array {
    $levels = array_values(array_unique(array_filter(array_map('intval', $covers))));
    sort($levels);
    if ($levels === [1]) return ['label' => 'หัวหน้างานโดยตรง', 'key' => 'assessment.evaluate.role.directSupervisor'];
    if ($levels === [2]) return ['label' => 'ผู้ประเมินระดับฝ่าย', 'key' => 'assessment.evaluate.role.division'];
    if (in_array(1, $levels, true) && in_array(2, $levels, true)) {
      return ['label' => 'หัวหน้างานโดยตรง / ผู้ประเมินระดับฝ่าย', 'key' => 'assessment.evaluate.role.directAndDivision'];
    }
    return ['label' => 'ผู้ประเมินลำดับที่ '.implode(', ', $levels), 'key' => null];
  };
  $role = $roleMeta($covers ?: [$level]);
  $fmtScore = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
  $propText = function (?string $mode, $weight) use ($fmtScore): string {
    if ($mode === 'extra') {
      $num = (float) ($weight ?? 0);
      return 'เพิ่มเติม '.($num > 0 ? '+' : '').$fmtScore($num);
    }
    if ($mode === 'percent') return $fmtScore($weight ?? 0).'%';
    return '-';
  };
  $scoreChoiceGroups = [
    ['en' => 'Always', 'th' => 'เสมอ', 'my' => 'အမြဲတမ်း', 'scores' => ['10', '9', '8']],
    ['en' => 'Often', 'th' => 'บ่อย', 'my' => 'မကြာခဏ', 'scores' => ['7', '6', '5']],
    ['en' => 'Sometimes', 'th' => 'นานๆครั้ง', 'my' => 'တစ်ခါတစ်ရံ', 'scores' => ['4', '3', '2']],
    ['en' => 'Almost never', 'th' => 'แทบไม่เคย', 'my' => 'အလွန်နည်းပါး', 'scores' => ['1']],
    ['en' => 'Never', 'th' => 'ไม่เคย', 'my' => 'ဘယ်တော့မှမ', 'scores' => ['0']],
    ['en' => 'Not assessed', 'th' => 'ไม่ประเมิน', 'my' => 'မအကဲဖြတ်ပါ', 'scores' => ['N/A'], 'na' => true],
  ];
  $selectedScoreText = function (string $name, string $score, bool $isNa): string {
    if ($isNa) return 'ไม่ประเมิน';
    if ($score !== '') return 'คะแนนที่เลือก คือ '.$score.' เต็ม 10 คะแนน';
    return 'ยังไม่ได้เลือกคะแนน';
  };
@endphp

@section('page-style')
    /* ---- ฟอร์มกระดาษ: แผ่นขาวเสมอ (สื่อความเป็นเอกสาร) บนพื้นหลังธีม ---- */
    .paper-page { display:grid; gap:.9rem; width:min(100%, 56rem); margin:0 auto; }

    .paper-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .paper-back { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .85rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.8rem; font-weight:600; text-decoration:none; }
    .paper-back:hover { border-color:var(--moss); color:var(--moss); }
    .paper-round { color:var(--muted-light); font-size:.8rem; }
    .paper-round b { color:var(--moss); }

    .paper-sheet { background:#fdfcf8; color:#26251f; border:1px solid #e3dfd2; border-radius: 0.25rem; box-shadow:0 14px 40px rgb(0 0 0 / 16%); padding:2.2rem 2.6rem 2.4rem; }
    .paper-sheet + .paper-sheet { margin-top:1.15rem; }
    .paper-sheet-next { break-before:page; page-break-before:always; }
    @media (max-width: 700px) { .paper-sheet { padding:1.3rem 1.1rem 1.6rem; } }

    .paper-header { text-align:center; padding-bottom:1.1rem; border-bottom:2px solid #26251f; }
    .paper-logo { display:block; width:3.1rem; height:3.1rem; margin:0 auto .45rem; object-fit:contain; }
    .paper-header h2 { margin:0 0 .2rem; color:#26251f; font-size:1.22rem; font-weight:800; letter-spacing:.02em; }
    .paper-header p { margin:0; color:#6f6b5e; font-size:.82rem; }

    .paper-profile-row { display:grid; grid-template-columns:7rem minmax(0,1fr); gap:1.75rem; align-items:stretch; padding:1rem .2rem 1.1rem; border-bottom:1px solid #26251f; }
    .paper-profile-thumb { width:7rem; min-height:7rem; aspect-ratio:1; border:1.5px solid #9d9785; border-radius:.28rem; background:#f1eee4; color:#777263; display:grid; place-items:center; overflow:hidden; align-self:stretch; }
    .paper-profile-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .paper-profile-thumb svg { width:68%; height:68%; display:block; }

    /* กดรูปเพื่อขยาย */
    button.paper-profile-thumb { position:relative; padding:0; overflow:hidden; cursor:zoom-in; transition:border-color .16s var(--ease-out), box-shadow .16s var(--ease-out); }
    button.paper-profile-thumb:hover, button.paper-profile-thumb:focus-visible { border-color:#3c5f2c; box-shadow:0 8px 22px rgb(60 95 44 / 22%); outline:none; }
    .paper-photo-zoom { position:absolute; right:.25rem; bottom:.25rem; width:1.35rem; height:1.35rem; display:grid; place-items:center; border-radius:50%; background:rgb(38 37 31 / 72%); color:#fff; opacity:0; transition:opacity .16s ease; }
    .paper-photo-zoom svg { width:.85rem; height:.85rem; }
    button.paper-profile-thumb:hover .paper-photo-zoom,
    button.paper-profile-thumb:focus-visible .paper-photo-zoom { opacity:1; }

    /* กล่องรูปขนาดเต็ม */
    .photo-modal { position:fixed; inset:0; z-index:90; display:grid; place-items:center; padding:1.5rem; }
    .photo-modal[hidden] { display:none; }
    .photo-modal-back { position:absolute; inset:0; background:rgb(12 13 10 / 78%); }
    .photo-modal-box { position:relative; display:grid; gap:.5rem; max-width:min(92vw, 40rem); }
    /* ซูม = ภาพใหญ่ขึ้นจริง · ล้นแล้วเลื่อนดูได้ในกล่อง */
    .photo-modal { overflow:auto; }
    .photo-modal-stage { border-radius:.3rem; background:#111; box-shadow:0 24px 70px rgb(0 0 0 / 45%); touch-action:none; }
    .photo-modal-box img { display:block; width:auto; max-width:none; height:auto; margin:0 auto; border-radius:.3rem; cursor:zoom-in; transition:height .1s linear; user-select:none; -webkit-user-drag:none; }
    .photo-modal-name { margin:0; color:#f2efe6; font-size:.86rem; font-weight:700; text-align:center; }
    .photo-modal-hint { margin:0; color:rgb(242 239 230 / 62%); font-size:.72rem; text-align:center; }
    .photo-modal-hint[hidden] { display:none; }
    .photo-modal-x { position:absolute; top:-.6rem; right:-.6rem; width:2rem; height:2rem; display:grid; place-items:center; border:0; border-radius:50%; background:#fdfcf8; color:#26251f; cursor:pointer; font-size:1rem; box-shadow:0 6px 18px rgb(0 0 0 / 35%); }
    .photo-modal-x:hover { background:#fff; }
    .paper-info { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:.7rem 1.5rem; font-size:.86rem; }
    .paper-info div { min-width:0; padding-bottom:.42rem; border-bottom:1px dashed #d8d2bf; }
    .paper-info span { display:block; color:#6f6b5e; font-size:.78rem; font-weight:700; line-height:1.3; }
    .paper-info b { display:block; margin-top:.12rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.92rem; font-weight:400; }
    /* คะแนนรวม — เด่นกว่าช่องอื่นในหัวฟอร์ม */
    .paper-total-info b { color:#3c5f2c; font-size:1.24rem; font-weight:800; line-height:1.25; }
    /* label + ไอคอน อยู่บรรทัดเดียวกัน (ทับ display:block ของ .paper-info span) */
    .paper-total-info .paper-total-label { display:flex; align-items:center; gap:.1rem; }
    .paper-total-info .paper-total-label > span { display:inline; }
    @media (max-width: 600px) {
      .paper-profile-row { grid-template-columns:1fr; }
      .paper-info { grid-template-columns:1fr; }
    }

    .paper-prop-section { padding:1rem .2rem 0; }
    .paper-prop-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:.7rem; }
    .paper-prop-head h3 { margin:0; color:#26251f; font-size:1rem; font-weight:800; }
    .paper-prop-level { color:#6f6b5e; font-size:.78rem; font-weight:400; }
    .paper-prop-list { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.45rem 1.2rem; }
    .paper-prop-row { display:flex; align-items:baseline; justify-content:space-between; gap:.75rem; min-width:0; padding:.42rem 0; border-bottom:1px dashed #d8d2bf; }
    .paper-prop-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#26251f; font-size:.86rem; }
    .paper-prop-value { flex:none; color:#26251f; font-size:.86rem; font-weight:400; font-variant-numeric:tabular-nums; }
    .paper-prop-value.is-muted { color:#777263; }
    .paper-section-rule { margin:1rem .2rem 0; border-top:2px solid #26251f; }
    .paper-columns-section { padding:1rem .2rem 0; }
    .paper-columns-section.is-primary { display:flex; flex-direction:column; min-height:21rem; }
    .paper-columns-title { margin:0 0 .7rem; color:#26251f; font-size:1rem; font-weight:800; }
    .paper-columns-section.is-primary .paper-col-block { flex:1; display:flex; flex-direction:column; }
    .paper-col-block + .paper-col-block { margin-top:.9rem; }
    .paper-col-title { margin:0 0 .42rem; color:#26251f; font-size:.92rem; font-weight:800; }
    .paper-col-workarea { padding:.8rem 0 .35rem; }
    .paper-columns-section.is-primary .paper-col-workarea { flex:1; display:flex; flex-direction:column; }
    /* pre-wrap = คงบรรทัดและช่องว่างตามที่ admin พิมพ์ */
    .paper-col-desc { margin:0; min-width:0; color:#6f6b5e; font-size:.84rem; line-height:1.55; white-space:pre-wrap; }
    .paper-col-summary { margin:1.15rem 0 0; color:#3c5f2c; font-size:.94rem; font-weight:700; font-variant-numeric:tabular-nums; }
    .paper-columns-section.is-primary .paper-col-summary { margin-top:auto; padding-top:1.15rem; }
    .paper-attendance-table { margin-top:1rem; border-top:0; }
    .paper-col-table { border-top:1px solid #26251f; }
    .paper-col-table.paper-attendance-table { border-top:0; }
    .paper-col-row { display:grid; grid-template-columns:minmax(0,1fr) 7rem; gap:.85rem; align-items:baseline; min-width:0; padding:.38rem 0; border-bottom:1px dashed #d8d2bf; }
    .paper-columns-section.is-primary .paper-col-row { padding:.48rem 0; }
    .paper-col-row.is-head { color:#6f6b5e; font-size:.76rem; font-weight:700; }
    .paper-col-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#26251f; font-size:.84rem; }
    .paper-col-value { color:#26251f; text-align:right; font-size:.84rem; font-weight:400; font-variant-numeric:tabular-nums; }
    .paper-col-value.is-muted { color:#777263; }
    /* ไอคอนเปิดอ่านคำอธิบาย — ขึ้นเฉพาะคอลัมน์ที่ admin เปิดแสดงและมีข้อความ */
    .paper-note-btn { display:inline-grid; place-items:center; width:1rem; height:1rem; margin-left:.3rem; padding:0; border:0; border-radius:50%; background:transparent; color:#9d9785; cursor:pointer; vertical-align:-2px; }
    .paper-note-btn svg { width:100%; height:100%; }
    .paper-note-btn:hover, .paper-note-btn:focus-visible { color:#3c5f2c; outline:none; }

    /* Modal กระดาษ — โทนเดียวกับแผ่นฟอร์ม */
    .note-modal { position:fixed; inset:0; z-index:80; display:grid; place-items:center; padding:1rem; }
    .note-modal[hidden] { display:none; }
    .note-modal-back { position:absolute; inset:0; background:rgb(20 22 18 / 45%); }
    .note-modal-sheet { position:relative; width:min(100%, 30rem); max-height:80vh; overflow:auto; padding:1.1rem 1.3rem 1.3rem; border:1px solid #e3dfd2; border-radius:.28rem; background:#fdfcf8; color:#26251f; box-shadow:0 20px 60px rgb(0 0 0 / 28%); }
    .note-modal-head { display:flex; align-items:flex-start; gap:.8rem; padding-bottom:.6rem; border-bottom:1px solid #26251f; }
    .note-modal-head h3 { flex:1; margin:0; font-size:.96rem; font-weight:800; }
    .note-modal-x { flex:none; padding:.1rem .2rem; border:0; background:transparent; color:#6f6b5e; cursor:pointer; font-size:.9rem; line-height:1; }
    .note-modal-x:hover { color:#26251f; }
    .note-modal-body { margin:.8rem 0 0; color:#4a463c; font-size:.86rem; line-height:1.7; white-space:pre-wrap; }
    .paper-col-block.is-continuation { padding-top:1.15rem; border-top:2px solid #26251f; }
    .paper-col-block.is-continuation + .paper-col-block.is-continuation { margin-top:1.75rem; }
    .paper-col-block.is-continuation .paper-col-table { border-top:0; margin-top:.7rem; }
    .paper-col-block.is-continuation .paper-col-desc { margin:.45rem 0 0; }
    /* เส้นคั่นส่วน "ให้คะแนน" ออกจากตารางคอลัมน์ย่อยด้านบน (หนา 2px เท่าเส้นคั่นหัวข้ออื่น) */
    .paper-input-form { margin-top:1.6rem; padding-top:1.2rem; padding-left:1rem; border-top:2px solid #26251f; display:grid; gap:1.35rem; }
    .paper-input-box { padding-top:1rem; padding-left:.65rem; border-top:1px solid #26251f; }
    .paper-input-head { display:grid; gap:.28rem; align-items:start; }
    .paper-input-head label { color:#26251f; font-size:.88rem; font-weight:800; }
    .paper-selected-score { margin:0; color:#3c5f2c; font-size:.84rem; font-weight:700; }
    .paper-scale { margin-top:.95rem; display:grid; gap:.62rem; }
    .paper-scale-group { display:grid; grid-template-columns:8.5rem minmax(0,1fr); gap:.85rem; align-items:center; }
    .paper-scale-toggle { display:flex; align-items:center; gap:.45rem; width:100%; padding:.42rem 0; border:0; background:transparent; color:#3d3a30; font-size:.78rem; font-weight:700; text-align:left; cursor:pointer; }
    .paper-check { width:1rem; height:1rem; border:1.4px solid #8b8672; border-radius:.18rem; display:grid; place-items:center; flex:none; }
    .paper-check svg { width:.72rem; height:.72rem; opacity:0; transform:scale(.65); transition:opacity .15s ease, transform .15s ease; }
    .paper-scale-group.is-checked .paper-check { border-color:#5b7343; background:#5b7343; color:#fff; }
    .paper-scale-group.is-checked .paper-check svg { opacity:1; transform:scale(1); }
    [data-score-lang] { display:none; }
    html[data-lang="th"] [data-score-lang="th"],
    html[data-lang="en"] [data-score-lang="en"],
    html[data-lang="my"] [data-score-lang="my"] { display:inline; }
    /* เปิด/ปิดแบบมีการเปลี่ยนผ่าน (แทน display:none ที่กระโดด) */
    .paper-scale-options { display:flex; flex-wrap:wrap; gap:.38rem; max-height:0; opacity:0; transform:translateY(-4px); overflow:hidden; pointer-events:none; transition:max-height .22s var(--ease-out), opacity .16s ease, transform .18s var(--ease-out); }
    .paper-scale-group.is-open .paper-scale-options { max-height:12rem; opacity:1; transform:none; pointer-events:auto; }
    /* ปุ่มคะแนนไล่โผล่ทีละอันเล็กน้อย */
    .paper-scale-group.is-open .paper-choice { animation:paperChoiceIn .22s var(--ease-out) both; }
    .paper-scale-group.is-open .paper-choice:nth-child(2) { animation-delay:.03s; }
    .paper-scale-group.is-open .paper-choice:nth-child(3) { animation-delay:.06s; }
    .paper-scale-group.is-open .paper-choice:nth-child(4) { animation-delay:.09s; }
    @keyframes paperChoiceIn { from { opacity:0; transform:translateY(-3px) scale(.97); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) {
      .paper-scale-options { transition:none; }
      .paper-scale-group.is-open .paper-choice { animation:none; }
    }
    .paper-choice { min-width:2.4rem; padding:.34rem .55rem; border:1px solid #bcb49f; border-radius:.28rem; background:#fff; color:#26251f; font-size:.78rem; font-weight:700; font-variant-numeric:tabular-nums; cursor:pointer; transition:background .15s ease, border-color .15s ease, color .15s ease; }
    .paper-choice:hover { border-color:#5b7343; color:#3c5f2c; }
    .paper-choice.is-active { border-color:#5b7343; background:#5b7343; color:#fff; }
    .paper-choice.is-na { min-width:5.4rem; color:#6f4f27; }
    .paper-choice.is-na.is-active { border-color:#c8964a; background:#c8964a; color:#fff; }
    .paper-score-err { width:100%; color:#b3543c; font-size:.74rem; font-weight:600; display:none; }
    .paper-input-box:first-child { border-top:0; padding-top:0; }
    .paper-input-box + .paper-input-box { border-top:1px dashed #d8d2bf; padding-top:1.2rem; }
    @media (max-width: 600px) {
      .paper-prop-head { align-items:flex-start; flex-direction:column; gap:.25rem; }
      .paper-prop-list { grid-template-columns:1fr; }
      .paper-col-row { grid-template-columns:minmax(0,1fr) 5.5rem; }
      .paper-input-form { padding-left:.35rem; }
      .paper-scale-group { grid-template-columns:1fr; }
    }

    .paper-blank-space { min-height:18rem; }

    /* ---- หมวดคำถามต่อคอลัมน์ Input — ติ๊กเรียงลงมา ---- */
    .paper-section { margin-top:1.4rem; }
    .paper-sec-head { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; padding-bottom:.5rem; border-bottom:1.5px solid #26251f; }
    .paper-sec-head h3 { margin:0; color:#26251f; font-size:1rem; font-weight:800; }
    .paper-tickinfo { color:#6f6b5e; font-size:.76rem; font-variant-numeric:tabular-nums; }

    .paper-q { position:relative; display:flex; align-items:flex-start; gap:.75rem; padding:.62rem .15rem; border-bottom:1px dashed #d9d4c3; cursor:pointer; user-select:none; }
    .paper-q:hover { background:#f6f4ea; }
    .paper-q input { position:absolute; opacity:0; pointer-events:none; }
    .paper-tick { flex:none; width:1.25rem; height:1.25rem; margin-top:.1rem; border:1.6px solid #8b8672; border-radius:.28rem; display:grid; place-items:center; background:#fff; transition:border-color .15s ease, background .15s ease; }
    .paper-tick svg { width:.85rem; height:.85rem; opacity:0; transform:scale(.5); transition:opacity .15s ease, transform .15s ease; }
    .paper-q input:checked + .paper-tick { border-color:#5b7343; background:#5b7343; }
    .paper-q input:checked + .paper-tick svg { opacity:1; transform:scale(1); }
    .paper-q-no { flex:none; width:1.4rem; color:#6f6b5e; font-size:.8rem; padding-top:.12rem; font-variant-numeric:tabular-nums; }
    .paper-q-text { font-size:.88rem; line-height:1.55; }
    .paper-q input:checked ~ .paper-q-text { color:#3c4a2c; }

    /* ---- ท้ายกระดาษ ---- */
    .paper-foot { margin-top:1.8rem; padding-top:1rem; border-top:2px solid #26251f; display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .paper-foot small { color:#6f6b5e; font-size:.76rem; }
    .paper-done { display:inline-flex; align-items:center; gap:.45rem; padding:.55rem 1.4rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; font-size:.85rem; font-weight:700; text-decoration:none; }
    .paper-done:hover { filter:brightness(1.06); }

    .paper-empty { margin-top:1.4rem; padding:1.4rem; border:1px dashed #c9c4b2; border-radius: 0.25rem; text-align:center; color:#6f6b5e; font-size:.84rem; }
@endsection

@section('content')
  <div class="paper-page">
    @php
      $firstColumnSection = $columnSections->first();
      $continuationSections = $columnSections->slice(1)->values();
    @endphp

    <div class="paper-top">
      <a class="paper-back nav-go" href="{{ route('assessment.evaluate.index') }}" data-i18n="assessment.evaluate.backToList">← กลับรายการประเมิน</a>
      <span class="paper-round"><span data-i18n="assessment.common.round">รอบ</span> <b>{{ $openRound->name }}</b> · {{ $openRound->year }}</span>
    </div>

    <div class="paper-sheet">
      <div class="paper-header">
        <img class="paper-logo" src="{{ asset('assets/assessment/form-logo.png') }}" alt="" aria-hidden="true">
        <h2 data-i18n="assessment.form.performanceTitle">แบบประเมินผลการปฏิบัติงานพนักงาน</h2>
        <p>{{ $openRound->name }} ({{ $openRound->year }}) · <span @if ($role['key']) data-i18n="{{ $role['key'] }}" @endif>{{ $role['label'] }}</span></p>
      </div>

      <div class="paper-profile-row">
        @if (! empty($assignment['avatar']))
          {{-- มีรูปจริง → กดเพื่อดูขนาดเต็ม --}}
          <button type="button" class="paper-profile-thumb is-zoomable"
                  data-photo-open
                  data-photo-src="{{ $assignment['avatar'] }}"
                  data-photo-name="{{ $assignment['name'] }}"
                  aria-label="ดูรูปโปรไฟล์ {{ $assignment['name'] }} ขนาดเต็ม"
                  title="กดเพื่อขยายรูป">
            <img src="{{ $assignment['avatar'] }}" alt="รูปโปรไฟล์ {{ $assignment['name'] }}">
            <span class="paper-photo-zoom" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5M11 8v6M8 11h6" />
              </svg>
            </span>
          </button>
        @else
          <div class="paper-profile-thumb" aria-label="รูปโปรไฟล์พนักงาน">
            @include('assessment.partials.default-avatar')
          </div>
        @endif

        <div class="paper-info">
          <div><span data-i18n="assessment.form.fullName">ชื่อ-สกุล :</span> <b title="{{ $assignment['name'] }}" data-loc-th="{{ $assignment['name'] }}" data-loc-en="{{ $assignment['name_en'] ?? $assignment['name'] }}">{{ $assignment['name'] }}</b></div>
          <div><span data-i18n="assessment.form.employeeCode">รหัสพนักงาน :</span> <b>{{ $assignment['employee_code'] }}</b></div>
          @if ($totalScoreDisplay !== null)
            <div class="paper-total-info">
              <span class="paper-total-label"><span data-i18n="assessment.form.totalScore">คะแนนรวม :</span>@include('assessment.partials.noteicon', ['desc' => $totalNote ?? null, 'title' => 'คะแนนรวม'])</span>
              <b data-total-score>{{ $totalScoreDisplay }}</b>
            </div>
          @endif
          <div><span data-i18n="assessment.form.position">ตำแหน่ง :</span> <b title="{{ $assignment['position'] ?: '-' }}" data-loc-th="{{ $assignment['position'] ?: '-' }}" data-loc-en="{{ $assignment['position_en'] ?: ($assignment['position'] ?: '-') }}">{{ $assignment['position'] ?: '-' }}</b></div>
          <div><span data-i18n="assessment.form.department">แผนก :</span> <b title="{{ $assignment['department'] ?: '-' }}" data-loc-th="{{ $assignment['department'] ?: '-' }}" data-loc-en="{{ $assignment['department_en'] ?: ($assignment['department'] ?: '-') }}">{{ $assignment['department'] ?: '-' }}</b></div>
        </div>
      </div>

      <section class="paper-prop-section" aria-label="หัวข้อสัดส่วนคะแนน" data-i18n-aria="assessment.form.scoreWeightTitle">
        <div class="paper-prop-head">
          <h3 data-i18n="assessment.form.scoreWeightTitle">หัวข้อสัดส่วนคะแนน</h3>
          <span class="paper-prop-level"><span data-i18n="assessment.form.level">ระดับ</span> {{ $employeeLevel ?? '-' }}</span>
        </div>

        <div class="paper-prop-list">
          @forelse ($proportionRows as $row)
            @php
              $showValue = (bool) ($row['show_value'] ?? true);
              $display = $showValue ? $propText($row['mode'] ?? null, $row['weight'] ?? null) : '';
            @endphp
            <div class="paper-prop-row">
              <span class="paper-prop-name" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
              @if ($showValue)
                <span class="paper-prop-value {{ $display === '-' ? 'is-muted' : '' }}">{{ $display }}</span>
              @else
                <span class="paper-prop-value is-muted" aria-hidden="true"></span>
              @endif
            </div>
          @empty
            <div class="paper-prop-row">
              <span class="paper-prop-name" data-i18n="assessment.form.noScoreWeights">ยังไม่มีหัวข้อสัดส่วน</span>
              <span class="paper-prop-value is-muted">-</span>
            </div>
          @endforelse
        </div>
      </section>

      <div class="paper-section-rule" aria-hidden="true"></div>

      <section class="paper-columns-section is-primary" aria-label="ประเมินผลการปฏิบัติงาน" data-i18n-aria="assessment.form.performanceSection">
        <h3 class="paper-columns-title" data-i18n="assessment.form.performanceSection">ประเมินผลการปฏิบัติงาน</h3>

        @if ($firstColumnSection)
          @php
            $si = 0;
            $section = $firstColumnSection;
            $isAttendance = (bool) ($section['is_attendance'] ?? false);
          @endphp
          <div class="paper-col-block">
            <h4 class="paper-col-title"><span data-i18n="assessment.common.section">หัวข้อ</span> {{ $si + 1 }} {{ $section['name'] }}</h4>
            @if ($isAttendance)
              <div class="paper-col-workarea">
                @if (! empty($section['description']))<p class="paper-col-desc" data-loc-th="{{ $section['description']['th'] }}" data-loc-en="{{ $section['description']['en'] }}" data-loc-my="{{ $section['description']['my'] }}">{{ $section['description']['th'] }}</p>@endif

                <div class="paper-col-table paper-attendance-table">
                  @foreach ($section['columns'] as $column)
                    <div class="paper-col-row">
                      <span class="paper-col-name" title="{{ $column['name'] }}">{{ $column['name'] }}@include('assessment.partials.noteicon', ['desc' => $column['description'] ?? null, 'title' => $column['name']])</span>
                      <span class="paper-col-value {{ $column['value'] === '-' ? 'is-muted' : '' }}" data-column-value="{{ $column['id'] }}">{{ $column['value'] }}</span>                    </div>
                  @endforeach
                </div>
                @if (! empty($section['summary']))
                  <p class="paper-col-summary" data-section-summary="{{ $section['id'] }}">{{ $section['summary'] }}</p>
                @endif
              </div>
            @else
              <div class="paper-col-table">
                @foreach ($section['columns'] as $column)
                  <div class="paper-col-row">
                    <span class="paper-col-name" title="{{ $column['name'] }}">{{ $column['name'] }}@include('assessment.partials.noteicon', ['desc' => $column['description'] ?? null, 'title' => $column['name']])</span>
                    <span class="paper-col-value {{ $column['value'] === '-' ? 'is-muted' : '' }}" data-column-value="{{ $column['id'] }}">{{ $column['value'] }}</span>                  </div>
                @endforeach
              </div>
              @if (! empty($section['summary']))
                <p class="paper-col-summary" data-section-summary="{{ $section['id'] }}">{{ $section['summary'] }}</p>
              @endif
            @endif
          </div>
        @else
          <div class="paper-col-table">
            <div class="paper-col-row">
              <span class="paper-col-name" data-i18n="assessment.form.noScoreColumns">ยังไม่มีคอลัมน์คะแนน</span>
              <span class="paper-col-value is-muted">-</span>
            </div>
          </div>
        @endif
      </section>

    </div>

    @if ($continuationSections->isNotEmpty())
      <div class="paper-sheet paper-sheet-next">
        <section class="paper-columns-section" aria-label="ประเมินผลการปฏิบัติงาน หน้าถัดไป">
          @foreach ($continuationSections as $offset => $section)
            @php $si = $offset + 1; @endphp
            <div class="paper-col-block is-continuation">
              <h4 class="paper-col-title"><span data-i18n="assessment.common.section">หัวข้อ</span> {{ $si + 1 }} {{ $section['name'] }}</h4>
              @if (! empty($section['description']))<p class="paper-col-desc" data-loc-th="{{ $section['description']['th'] }}" data-loc-en="{{ $section['description']['en'] }}" data-loc-my="{{ $section['description']['my'] }}">{{ $section['description']['th'] }}</p>@endif
              <div class="paper-col-table">
                @foreach ($section['columns'] as $column)
                  <div class="paper-col-row">
                    <span class="paper-col-name" title="{{ $column['name'] }}">{{ $column['name'] }}@include('assessment.partials.noteicon', ['desc' => $column['description'] ?? null, 'title' => $column['name']])</span>
                    <span class="paper-col-value {{ $column['value'] === '-' ? 'is-muted' : '' }}" data-column-value="{{ $column['id'] }}">{{ $column['value'] }}</span>                  </div>
                @endforeach
              </div>

              @if (($section['inputs'] ?? collect())->isNotEmpty())
                <div class="paper-input-form" aria-label="แบบฟอร์มให้คะแนน {{ $section['name'] }}">
                  @foreach ($section['inputs'] as $input)
                    @php
                      $scoreInputId = 'score-'.$input['id'].'-'.$si;
                      $currentScore = (string) ($input['input_value'] ?? '');
                      $isNa = (bool) ($input['input_na'] ?? false);
                    @endphp
                    <div class="paper-input-box" data-sec>
                      <div class="paper-input-head">
                        <label for="{{ $scoreInputId }}"><span data-i18n="assessment.form.evaluate">ประเมิน</span> {{ $input['name'] }}</label>
                        <p class="paper-selected-score" data-selected-result>{{ $selectedScoreText($input['name'], $currentScore, $isNa) }}</p>
                        <input
                          id="{{ $scoreInputId }}"
                          type="hidden"
                          value="{{ $isNa ? 'N/A' : $currentScore }}"
                          data-score
                          data-box="{{ $input['id'] }}"
                          data-input-name="{{ $input['name'] }}"
                        >
                      </div>

                      <div class="paper-scale">
                        {{-- ตัวเลือกคะแนนของคอลัมน์นี้ (admin ตั้งที่หน้ากำหนดคำถาม) — ไม่ได้ตั้ง = ชุดเริ่มต้น --}}
                        @foreach (($input['scale'] ?? $scoreChoiceGroups) as $group)
                          @php
                            $groupActive = ($group['na'] ?? false)
                              ? $isNa
                              : in_array($currentScore, $group['scores'], true);
                          @endphp
                          <div class="paper-scale-group {{ $groupActive ? 'is-open is-checked' : '' }}">
                            <button class="paper-scale-toggle" type="button" data-scale-toggle>
                              <span class="paper-check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                  <path d="M5 12.5 9.5 17 19 7" />
                                </svg>
                              </span>
                              <span data-score-lang="en">{{ $group['en'] }}</span>
                              <span data-score-lang="th">{{ $group['th'] }}</span>
                              <span data-score-lang="my">{{ $group['my'] }}</span>
                            </button>
                            <div class="paper-scale-options">
                              @foreach ($group['scores'] as $scoreChoice)
                                @if (($group['na'] ?? false) === true)
                                  <button
                                    class="paper-choice is-na {{ $isNa ? 'is-active' : '' }}"
                                    type="button"
                                    data-na
                                    data-box="{{ $input['id'] }}"
                                  >N/A</button>
                                @else
                                  <button
                                    class="paper-choice {{ $currentScore === $scoreChoice ? 'is-active' : '' }}"
                                    type="button"
                                    data-score-choice="{{ $scoreChoice }}"
                                  >{{ $scoreChoice }}</button>
                                @endif
                              @endforeach
                            </div>
                          </div>
                        @endforeach
                      </div>

                      <div class="paper-score-err" data-err></div>
                    </div>
                  @endforeach
                </div>
              @endif
              @if (! empty($section['summary']))
                <p class="paper-col-summary" data-section-summary="{{ $section['id'] }}">{{ $section['summary'] }}</p>
              @endif
            </div>
          @endforeach
        </section>

        <div class="paper-blank-space" aria-hidden="true"></div>
      </div>
    @endif
  </div>

  @include('assessment.partials.notemodal')

  {{-- รูปโปรไฟล์ขนาดเต็ม --}}
  <div class="photo-modal" data-photo-modal hidden>
    <div class="photo-modal-back" data-photo-close></div>
    <div class="photo-modal-box" role="dialog" aria-modal="true" aria-label="รูปโปรไฟล์">
      <button type="button" class="photo-modal-x" data-photo-close aria-label="ปิด">✕</button>
      <div class="photo-modal-stage">
        <img data-photo-img src="" alt="" draggable="false">
      </div>
      <p class="photo-modal-name" data-photo-name></p>
      <p class="photo-modal-hint" data-photo-hint>หมุนล้อเมาส์เพื่อซูม · ดับเบิลคลิกเพื่อขยาย/ย่อ</p>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    // Modal อ่านคำอธิบาย — ผูกที่ document เผื่อแถวถูกวาดใหม่
    (() => {
      const modal = document.querySelector('[data-note-modal]');
      if (!modal) return;
      const title = modal.querySelector('[data-note-modal-title]');
      const body = modal.querySelector('[data-note-modal-text]');
      const close = () => { modal.hidden = true; };
      let openBtn = null;

      // เลือกข้อความตามธงที่เลือกอยู่ (ไม่มีภาษานั้น → ถอยไปที่มี)
      const textOf = (btn) => {
        const lang = document.documentElement.getAttribute('data-lang') || 'th';
        return btn.dataset['note' + lang.charAt(0).toUpperCase() + lang.slice(1)]
          || btn.dataset.noteEn || btn.dataset.noteTh || '';
      };

      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-note-open]');
        if (btn) {
          e.preventDefault();
          openBtn = btn;
          title.textContent = btn.dataset.noteTitle || '';
          body.textContent = textOf(btn);
          modal.hidden = false;
          modal.querySelector('.note-modal-x')?.focus();
          return;
        }
        if (e.target.closest('[data-note-close]')) close();
      });

      // เปลี่ยนธงระหว่างเปิด modal อยู่ → เปลี่ยนภาษาข้อความตาม
      document.addEventListener('insight:languagechange', () => {
        if (!modal.hidden && openBtn) body.textContent = textOf(openBtn);
      });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });
    })();
  </script>
  <script>
    'use strict';
    // กดรูปโปรไฟล์เพื่อดูขนาดเต็ม · หมุนล้อเมาส์ซูม · ลากเลื่อนเมื่อซูมแล้ว
    (() => {
      const box = document.querySelector('[data-photo-modal]');
      if (!box) return;
      const img = box.querySelector('[data-photo-img]');
      const name = box.querySelector('[data-photo-name]');

      const MIN = 1, MAX = 6;
      let scale = 1;          // 1 = ขนาดพอดีจอ · มากกว่านั้น = ภาพใหญ่ขึ้นจริง
      let baseH = 0;          // ความสูงจริงของภาพตอนพอดีจอ (px)
      let dragging = false, moved = false, sx = 0, sy = 0, sl = 0, st = 0;

      const clamp = (v) => Math.min(MAX, Math.max(MIN, v));
      const apply = () => {
        if (baseH) img.style.height = (baseH * scale) + 'px';
        img.style.cursor = scale > 1 ? (dragging ? 'grabbing' : 'grab') : 'zoom-in';
        box.querySelector('[data-photo-hint]')?.toggleAttribute('hidden', scale > 1);
      };
      const reset = () => { scale = 1; apply(); box.scrollTo({ top: 0, left: 0 }); };

      // วัดขนาดพอดีจอครั้งแรกที่ภาพโหลดเสร็จ
      const measure = () => {
        img.style.height = '';
        const fitH = Math.min(img.naturalHeight || 0, window.innerHeight * 0.78);
        baseH = fitH || img.clientHeight;
        apply();
      };
      img.addEventListener('load', measure);

      const closePhoto = () => { box.hidden = true; img.removeAttribute('src'); scale = 1; baseH = 0; };

      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-photo-open]');
        if (btn) {
          e.preventDefault();
          img.src = btn.dataset.photoSrc || '';
          img.alt = btn.dataset.photoName || '';
          name.textContent = btn.dataset.photoName || '';
          reset();
          box.hidden = false;
          box.querySelector('.photo-modal-x')?.focus();
          return;
        }
        // ลากแล้วปล่อยบนพื้นหลัง ไม่ควรนับเป็นการกดปิด
        if (!moved && e.target.closest('[data-photo-close]')) closePhoto();
        moved = false;
      });

      // ล้อเมาส์ = ภาพใหญ่/เล็กลงจริง · เลื่อนกล่องตามเพื่อให้จุดใต้เคอร์เซอร์อยู่ที่เดิม
      box.addEventListener('wheel', (e) => {
        if (box.hidden) return;
        e.preventDefault();
        const next = clamp(scale * (e.deltaY < 0 ? 1.18 : 1 / 1.18));
        if (next === scale) return;

        const rect = img.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width;    // จุดบนภาพที่เคอร์เซอร์ชี้ (0-1)
        const py = (e.clientY - rect.top) / rect.height;
        scale = next;
        apply();

        if (scale === MIN) { box.scrollTo({ top: 0, left: 0 }); return; }
        // ภาพโตขึ้นเท่าไร ให้เลื่อนตามส่วนที่โตก่อนถึงจุดนั้น จุดใต้เคอร์เซอร์จะอยู่ที่เดิม
        const after = img.getBoundingClientRect();
        box.scrollLeft += (after.width - rect.width) * px;
        box.scrollTop += (after.height - rect.height) * py;
      }, { passive: false });

      // ลากเพื่อเลื่อนดู (เลื่อน scroll ของกล่อง)
      img.addEventListener('pointerdown', (e) => {
        if (scale <= 1) return;
        dragging = true; moved = false;
        sx = e.clientX; sy = e.clientY;
        sl = box.scrollLeft; st = box.scrollTop;
        img.setPointerCapture(e.pointerId);
        apply();
      });
      img.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        box.scrollLeft = sl - (e.clientX - sx);
        box.scrollTop = st - (e.clientY - sy);
        moved = true;
      });
      const endDrag = (e) => {
        if (!dragging) return;
        dragging = false;
        try { img.releasePointerCapture(e.pointerId); } catch (err) {}
        apply();
      };
      img.addEventListener('pointerup', endDrag);
      img.addEventListener('pointercancel', endDrag);

      // ดับเบิลคลิก = ขยาย 2.5 เท่า / กลับขนาดเดิม
      img.addEventListener('dblclick', (e) => {
        e.preventDefault();
        if (scale > 1) { reset(); return; }
        const rect = img.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width;
        const py = (e.clientY - rect.top) / rect.height;
        scale = 2.5;
        apply();
        const after = img.getBoundingClientRect();
        box.scrollLeft = (after.width * px) - (box.clientWidth / 2);
        box.scrollTop = (after.height * py) - (box.clientHeight / 2);
      });

      document.addEventListener('keydown', (e) => {
        if (box.hidden) return;
        if (e.key === 'Escape') closePhoto();
        if (e.key === '0') reset();
      });
    })();
  </script>
  <script>
    'use strict';
    (() => {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const employee = @json($assignment['employee_code']);
      const level = @json($level);
      const saveUrl = @json(route('assessment.evaluate.save'));

      const text = (key, fallback, replacements = {}) => {
        const lang = document.documentElement.getAttribute('data-lang') || 'th';
        let value = window.__portalCopy?.[lang]?.[key] || fallback || key;
        Object.keys(replacements).forEach(name => {
          value = String(value).replace(new RegExp(`\\{${name}\\}`, 'g'), replacements[name]);
        });
        return value;
      };

      const save = (box, value, done) => {
        fetch(saveUrl, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({ employee_code: employee, box_id: Number(box), level: level, value: value }),
        })
          .then(r => r.json())
          .then(d => done(d))
          .catch(() => done({ ok: false, message: text('assessment.form.networkError', 'เชื่อมต่อไม่สำเร็จ') }));
      };

      const setChoiceState = (sec, value) => {
        sec.querySelectorAll('.paper-scale-group').forEach(group => {
          let active = false;
          group.querySelectorAll('[data-score-choice]').forEach(btn => {
            const isActive = btn.dataset.scoreChoice === value;
            btn.classList.toggle('is-active', isActive);
            active = active || isActive;
          });
          group.querySelectorAll('[data-na]').forEach(btn => {
            const isActive = value === 'N/A';
            btn.classList.toggle('is-active', isActive);
            active = active || isActive;
          });
          group.classList.toggle('is-checked', active);
          group.classList.toggle('is-open', active);
        });
      };

      const selectedText = (name, value) => {
        if (value === 'N/A') return text('assessment.form.notAssessed', 'ไม่ประเมิน');
        if (value !== '') return text('assessment.form.selectedScore', 'คะแนนที่เลือก คือ {score} เต็ม 10 คะแนน', { score: value });
        return text('assessment.form.noScoreSelected', 'ยังไม่ได้เลือกคะแนน');
      };

      const updateSelectedText = (sec, value) => {
        const input = sec.querySelector('[data-score]');
        const result = sec.querySelector('[data-selected-result]');
        if (!input || !result) return;
        result.textContent = selectedText(input.dataset.inputName || '', value);
      };

      const applySaveResponse = (box, display, data) => {
        document.querySelectorAll(`[data-column-value="${box}"]`).forEach(node => {
          node.textContent = display || '-';
          node.classList.toggle('is-muted', !display);
        });
        const total = document.querySelector('[data-total-score]');
        if (total && Object.prototype.hasOwnProperty.call(data, 'total_score_display')) {
          total.textContent = data.total_score_display === null ? '-' : data.total_score_display;
        }
        if (data.section_summaries) {
          Object.entries(data.section_summaries).forEach(([sectionId, summary]) => {
            document.querySelectorAll(`[data-section-summary="${sectionId}"]`).forEach(node => {
              node.textContent = summary || '';
              node.style.display = summary ? '' : 'none';
            });
          });
        }
      };

      document.querySelectorAll('[data-scale-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
          const group = btn.closest('.paper-scale-group');
          const sec = btn.closest('[data-sec]');
          if (!group || !sec) return;
          sec.querySelectorAll('.paper-scale-group').forEach(other => {
            if (other !== group && !other.classList.contains('is-checked')) other.classList.remove('is-open');
          });
          group.classList.toggle('is-open');
        });
      });

      document.querySelectorAll('[data-score-choice]').forEach(btn => {
        btn.addEventListener('click', () => {
          const sec = btn.closest('[data-sec]');
          const inp = sec?.querySelector('[data-score]');
          if (!sec || !inp) return;
          inp.value = btn.dataset.scoreChoice;
          inp.dispatchEvent(new Event('change', { bubbles: true }));
        });
      });

      // ---- ติ๊ก checklist → อัปเดตตัวนับของหมวด (ตัวช่วยพิจารณา ไม่บันทึกลงระบบ) ----
      document.querySelectorAll('[data-sec]').forEach(sec => {
        const counter = sec.querySelector('[data-ticked]');
        sec.querySelectorAll('[data-tick]').forEach(t => t.addEventListener('change', () => {
          if (counter) counter.textContent = sec.querySelectorAll('[data-tick]:checked').length;
        }));
      });

      // ---- คะแนน 0-10: บันทึกอัตโนมัติเมื่อเปลี่ยนค่า ----
      document.querySelectorAll('[data-score]').forEach(inp => {
        inp.addEventListener('change', () => {
          const sec = inp.closest('[data-sec]');
          const err = sec.querySelector('[data-err]');
          const raw = inp.value.trim();
          if (raw !== '' && (isNaN(Number(raw)) || Number(raw) < 0 || Number(raw) > 10)) {
            err.textContent = text('assessment.form.scoreRangeError', 'คะแนนต้องอยู่ระหว่าง 0–10');
            err.style.display = 'block';
            return;
          }
          err.style.display = 'none';
          save(inp.dataset.box, raw, d => {
            if (!d.ok) { err.textContent = d.message || text('assessment.form.saveError', 'บันทึกไม่สำเร็จ'); err.style.display = 'block'; return; }
            inp.value = d.display;
            setChoiceState(sec, d.display);
            updateSelectedText(sec, d.display);
            applySaveResponse(inp.dataset.box, d.display, d);
            inp.classList.add('saved');
            setTimeout(() => inp.classList.remove('saved'), 1400);
          });
        });
      });

      // ---- ปุ่ม N/A: กดติด = ไม่ประเมินหมวดนี้ · กดซ้ำ = ยกเลิกกลับมากรอกคะแนน ----
      document.querySelectorAll('[data-na]').forEach(btn => {
        btn.addEventListener('click', () => {
          const sec = btn.closest('[data-sec]');
          const inp = sec.querySelector('[data-score]');
          const err = sec.querySelector('[data-err]');
          const turnOn = !btn.classList.contains('is-active');
          save(btn.dataset.box, turnOn ? 'N/A' : '', d => {
            if (!d.ok) { err.textContent = d.message || text('assessment.form.saveError', 'บันทึกไม่สำเร็จ'); err.style.display = 'block'; return; }
            err.style.display = 'none';
            btn.classList.toggle('is-active', turnOn);
            inp.value = d.display;
            setChoiceState(sec, turnOn ? 'N/A' : '');
            updateSelectedText(sec, d.display);
            applySaveResponse(btn.dataset.box, d.display, d);
          });
        });
      });

      const refreshSelectedTexts = () => {
        document.querySelectorAll('[data-sec]').forEach(sec => {
          const input = sec.querySelector('[data-score]');
          if (input) updateSelectedText(sec, input.value.trim());
        });
      };
      refreshSelectedTexts();
      document.addEventListener('insight:languagechange', refreshSelectedTexts);
    })();
  </script>
@endsection
