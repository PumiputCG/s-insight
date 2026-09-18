@extends('layouts.portal')

@section('title', 'ผลลัพธ์')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.results.title">ผลลัพธ์</span>
@endsection

@php
  $fmt = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
  // slot ที่ผู้ใช้ใส่ N/A (na_mask) แสดงคำว่า N/A ค้างไว้ (คำนวณ = เหมือนว่าง)
  $naBit = fn ($pv, int $part) => ((int) ($pv['na'] ?? 0)) & (1 << ($part - 1));
  $fmt4 = function ($pv) use ($fmt, $naBit) {
    if (! $pv) return '';
    $vals = [$pv['v'] ?? null, $pv['v2'] ?? null, $pv['v3'] ?? null, $pv['v4'] ?? null];
    $last = -1;
    foreach ($vals as $i => $v) { if ($v !== null || $naBit($pv, $i + 1)) $last = $i; }
    if ($last < 0) return '';
    $parts = [];
    for ($i = 0; $i <= $last; $i++) $parts[] = $naBit($pv, $i + 1) ? 'N/A' : ($vals[$i] === null ? '' : $fmt($vals[$i]));
    return implode(',', $parts);
  };
  $hf = ['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'];
  $hierCell = function ($hier, string $field): string {
    if (! $hier) return '-';
    $value = trim((string) ($hier->$field ?? ''));
    return $value === '' ? '-' : $value;
  };
  $levelPropLookup = function ($level, int $boxId) use ($levelPropMatrix) {
    if ($level === null) return null;
    $matrix = is_array($levelPropMatrix) ? $levelPropMatrix : (array) $levelPropMatrix;
    $rows = $matrix[(int) $level] ?? $matrix[(string) $level] ?? null;
    if (is_object($rows)) $rows = (array) $rows;
    if (! is_array($rows)) return null;
    $cfg = $rows[$boxId] ?? $rows[(string) $boxId] ?? null;
    if (is_object($cfg)) $cfg = (array) $cfg;
    return is_array($cfg) ? $cfg : null;
  };
  $isLeafNotCalculated = function ($level, $leaf) use ($levelPropLookup) {
    if ($level === null || (int) $level < 1) return false;
    $topId = (int) ($leaf->parent_id ?: $leaf->id);
    return (($levelPropLookup((int) $level, $topId)['mode'] ?? '') === 'none');
  };
  $isLeafExtraScore = function ($level, $leaf) use ($levelPropLookup) {
    if (($leaf->type ?? '') === 'bonus') return true;
    if ($level === null || (int) $level < 1) return false;
    $topId = (int) ($leaf->parent_id ?: $leaf->id);
    return (($levelPropLookup((int) $level, $topId)['mode'] ?? '') === 'extra');
  };
  $hasCompleteHierarchy = function ($code) use ($hierMap) {
    $hier = $hierMap[$code] ?? null;
    if (! $hier) return false;
    foreach (['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'] as $field) {
      $value = trim((string) ($hier->$field ?? ''));
      if ($value === '' || $value === '-') return false;
    }
    return true;
  };
  $scoreHint = function ($leaf, $level = null, bool $isInput = false) use ($fmt, $isLeafExtraScore) {
    if ($isInput) return 'Input';
    if ($isLeafExtraScore($level, $leaf)) return '';
    $full = ($leaf->full_score ?? null) === null ? 10 : (float) $leaf->full_score;
    return ' /'.$fmt($full);
  };
  // จัดกลุ่มช่อง Input ต่อพนักงาน: รวม slot ที่ผู้ประเมิน (ชื่อลำดับชั้น) เป็นคนเดียวกัน → เหลือช่องเดียว
  $inputGroups = function ($hier, array $levels) {
    $groups = [];
    $order = [];
    foreach ($levels as $i => $lv) {
      $name = $hier ? trim((string) ($hier->{'l'.$lv.'_name'} ?? '')) : '';
      $key = $name !== '' ? 'n:'.$name : 'p:'.$i;   // ไม่มีชื่อ = แยกช่อง
      if (! isset($groups[$key])) {
        $groups[$key] = ['parts' => [], 'levels' => [], 'name' => $name];
        $order[] = $key;
      }
      $groups[$key]['parts'][] = $i + 1;            // ตำแหน่ง slot (1-based)
      $groups[$key]['levels'][] = $lv;
    }
    return array_map(fn ($k) => $groups[$k], $order);
  };
  // ลำดับผลลัพธ์ที่ผู้ประเมินซ้ำกับลำดับก่อนหน้า → แสดง "—" (ยุบตามชื่อ)
  $dupEvalLevels = function ($hier, array $evalLevels) {
    $seen = [];
    $dup = [];
    foreach ($evalLevels as $lv) {
      $name = $hier ? trim((string) ($hier->{'l'.$lv.'_name'} ?? '')) : '';
      $dup[$lv] = ($name !== '' && in_array($name, $seen, true));
      if ($name !== '') {
        $seen[] = $name;
      }
    }
    return $dup;
  };
  $slotVal = fn ($pv, int $part) => $pv[$part === 1 ? 'v' : 'v'.$part] ?? null;
  $slotDisp = fn ($pv, int $part) => $pv && $naBit($pv, $part) ? 'N/A' : $fmt($slotVal($pv, $part));
  $years = $rounds->pluck('year')->unique()->values();
  $perPage = $perPage ?? (int) request('per_page', 100);
  $pageRows = method_exists($employees, 'getCollection') ? $employees->getCollection() : collect($employees);
  $shownCount = $pageRows->unique('employee_code')->count();
  $totalCount = method_exists($employees, 'total') ? $employees->total() : $shownCount;
  $activeFilterCount = $activeFilterCount ?? 0;
  $resultUrl = fn ($r) => route('assessment.results.index', array_filter([
    'round' => $r->id,
    'per_page' => $perPage !== 100 ? $perPage : null,
  ], fn ($v) => $v !== null && $v !== ''));
  // พาเลตพาสเทล 7 สีแยกหัวข้อหลัก ทำหน้าที่เป็นรหัสหมวดหมู่ของคอลัมน์
  $scoreZoneMeta = [];
  foreach ($zones4 as $zoneIndex => $zone) {
    foreach ($zone['leaves'] as $leafIndex => $leaf) {
      $scoreZoneMeta[(int) $leaf->id] = [
        'tone' => ($zoneIndex % 7) + 1,
        'start' => $leafIndex === 0,
        'name' => $zone['name'],
      ];
    }
  }
@endphp

@section('page-style')
    .asm-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.82rem; text-decoration:none; font-weight:600; }
    .asm-btn:hover { border-color:var(--moss); }
    .asm-btn.primary { background:var(--moss); color:#fff; border-color:var(--moss); }
    .hint { color:var(--muted-light); font-size:.82rem; margin:.2rem 0 .8rem; }

    /* แท็บย่อยรายปี + รอบ */
    .yr-tabs { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin-bottom:.6rem; }
    /* หัวหน้าผลลัพธ์ — คอลัมน์ซ้ายสูงเท่าการ์ดสรุปทางขวา
       แท็บอยู่บนสุด · การ์ดนำเข้า/Export ถูกดันลงล่างให้ขอบล่างตรงกับการ์ดสรุปพอดี */
    .res-head { display:flex; align-items:stretch; justify-content:space-between; gap:.8rem 1.4rem; flex-wrap:wrap; margin-bottom:1rem; }
    .res-head-main { flex:1 1 24rem; min-width:0; display:flex; flex-direction:column; justify-content:space-between; gap:.5rem; }
    .res-head-main .asm-tabs,
    .res-head-main .yr-tabs { margin:0; }
    .res-head > .lvs { flex:0 0 auto; align-self:flex-start; margin-left:auto; }
    @media (max-width:900px) { .res-head-main { flex:1 1 100%; } .res-head > .lvs { margin-left:0; } }
    .yr-tab { padding:.32rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; text-decoration:none; font-size:.8rem; font-weight:700; }
    .yr-tab.is-active { background:var(--moss); border-color:var(--moss); color:#fff; }

    /* ศูนย์จัดการรวม — การ์ด 2 กลุ่ม */
    /* ศูนย์จัดการรวม — การ์ดกะทัดรัด วางอยู่แถวเดียวกับการ์ดสรุปการประเมิน */
    .res-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(15rem,1fr)); gap:.55rem; margin:0; }
    .res-card { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--menu-bg); padding:.55rem .7rem; display:flex; flex-direction:column; gap:.25rem; }
    .res-card > b { font-size:.82rem; color:var(--moss); }
    .res-card > p { margin:0; color:var(--muted-light); font-size:.72rem; line-height:1.35; }
    .res-card-actions { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin-top:.15rem; }
    .res-card .asm-btn { padding:.28rem .65rem; font-size:.76rem; }
    /* เลือกรอบที่จะดาวน์โหลด — รวมรอบที่ปิดแล้ว จึงย้อนดูข้อมูลเก่าได้ */
    .res-dl-form { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin:0; }
    .res-round-select { max-width:12rem; padding:.26rem .4rem; border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); color:inherit; font:inherit; font-size:.76rem; }
    .res-round-select:focus-visible { outline:2px solid var(--moss); outline-offset:1px; }
    .res-card-note { color:var(--muted-light); font-size:.68rem; }
    .xls-import-form { display:flex; align-items:center; gap:.7rem; margin:0; }
    .xls-import-form input[type="file"] { max-width:9.5rem; font-size:.7rem; }
    .flash.success { padding:.6rem .9rem; border:1px solid var(--moss); border-radius: 0.25rem; color:var(--moss); background:rgb(91 141 239 / 12%); font-size:.85rem; }

    .res-bar { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin-bottom:.6rem; }
    .res-bar .ttl { font-weight:700; }
    .res-bar .dl { margin-left:auto; }
    .asm-page { display:flex; align-items:center; justify-content:flex-end; gap:.55rem; margin:.75rem 0 0; color:var(--muted-light); font-size:.8rem; flex-wrap:wrap; }
    .asm-page-link { padding:.36rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; text-decoration:none; font-weight:600; }
    .asm-page-link:not(.is-disabled):hover { border-color:var(--moss); color:var(--moss); }
    .asm-page-link.is-disabled { opacity:.45; pointer-events:none; }
    .asm-page select { padding:.32rem .5rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.78rem; }
    .res-empty { text-align:center !important; color:var(--muted-light); padding:1rem !important; }
    .ss-count { color:var(--muted-light); font-size:.8rem; }
    .ss-fbtn { display:inline-flex; align-items:center; justify-content:center; width:100%; min-width:1.5rem; padding:.2rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; }
    .ss-fbtn:hover { border-color:var(--moss); color:inherit; }
    .ss-fbtn.is-active { border-color:var(--moss); color:var(--moss); background:rgb(91 141 239 / 14%); }
    .ss-fpop { position:fixed; z-index:1300; width:16rem; max-width:92vw; background:var(--panel, var(--menu-bg)); border:1px solid var(--line-light); border-radius: 0.25rem; box-shadow:0 .7rem 2rem rgb(0 0 0 / 30%); display:none; flex-direction:column; overflow:hidden; }
    .ss-fpop.show { display:flex; }
    .ss-fpop-title { padding:.5rem .6rem 0; font-size:.74rem; color:var(--muted-light); }
    .ss-fpop-search { padding:.45rem .5rem; border-bottom:1px solid var(--line-light); }
    .ss-fpop-search input { width:100%; padding:.4rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.8rem; }
    .ss-fpop-list { max-height:15rem; overflow:auto; padding:.3rem .35rem; }
    .ss-fpop-list label { display:flex; align-items:center; gap:.45rem; padding:.26rem .35rem; font-size:.82rem; cursor:pointer; border-radius:.3rem; }
    .ss-fpop-list label:hover { background:var(--hover-soft, var(--panel-soft)); }
    .ss-fpop-list label.all { font-weight:600; border-bottom:1px solid var(--line-light); border-radius:0; margin-bottom:.2rem; }
    .ss-fpop-list .nm { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ss-fpop-list .cnt { margin-left:auto; color:var(--muted-light); font-size:.72rem; }
    .ss-fpop-list .none { color:var(--muted-light); font-size:.8rem; padding:.4rem .35rem; }
    .ss-fpop-foot { display:flex; gap:.4rem; padding:.5rem; border-top:1px solid var(--line-light); }
    .ss-fpop-foot button { flex:1; padding:.4rem; font-size:.78rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; }
    .ss-fpop-foot button.apply { background:var(--moss); color:#0c0d0c; border-color:var(--moss); }

    .imp-wrap { overflow:auto; max-height:74vh; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); scroll-margin-top:calc(var(--topbar-h, 4.5rem) + 1rem); }
    table.imp-tbl { border-collapse:separate; border-spacing:0; font-size:.8rem; min-width:100%; }
    table.imp-tbl th, table.imp-tbl td { border-right:1px solid var(--line-light); border-bottom:1px solid var(--line-light); padding:.28rem .6rem; white-space:nowrap; text-align:left; }
    table.imp-tbl tr.imp-h1 th { position:sticky; top:0; z-index:3; height:1.7rem; background:var(--panel-soft); text-align:center; color:var(--moss); font-weight:600; }
    table.imp-tbl tr.imp-h2 th { position:sticky; top:1.7rem; z-index:3; height:1.7rem; background:var(--panel-soft); text-align:center; font-weight:600; }
    table.imp-tbl th.no, table.imp-tbl td.no { width:1%; text-align:right; color:var(--muted-light); font-variant-numeric:tabular-nums; }
    table.imp-tbl td.code { font-weight:700; }
    table.imp-tbl th.zone { color:var(--moss) !important; }
    table.imp-tbl th.rzone { color:#c8a24a !important; }
    table.imp-tbl td.rc { text-align:right; color:var(--muted-light); font-variant-numeric:tabular-nums; }
    table.imp-tbl td.hcell { color:var(--muted-light); font-size:.76rem; }
    /* ผู้ประเมินแก้ได้ในแถว (รอบเปิด) — ครบ 8 ช่องแล้วช่องคะแนนปลดล็อกเอง */
    table.imp-tbl td.hcell-edit { padding:0; }
    table.imp-tbl td.hcell-edit input { width:7rem; padding:.28rem .5rem; border:1px solid transparent; border-radius:.3rem; background:transparent; color:inherit; font-size:.76rem; }
    table.imp-tbl td.hcell-edit input:focus { outline:none; border-color:var(--moss); background:var(--panel-soft); }
    table.imp-tbl td.hcell-edit input.saved { border-color:var(--moss); }
    table.imp-tbl td.hcell-edit input::placeholder { color:var(--muted-light); opacity:.5; }
    table.imp-tbl td.sc4 { padding:0; }
    table.imp-tbl td.sc4 input { width:5.4rem; padding:.28rem .5rem; border:1px solid transparent; border-radius:.3rem; background:transparent; color:inherit; text-align:right; font-size:.78rem; font-variant-numeric:tabular-nums; }
    table.imp-tbl td.sc4 input:focus { outline:none; border-color:var(--moss); background:var(--panel-soft); }
    table.imp-tbl td.sc4 input.saved { border-color:var(--moss); }
    table.imp-tbl td.sc4 input[disabled] { color:var(--muted-light); }
    table.imp-tbl input.asm-excluded-input { color:var(--muted-light); text-align:center; cursor:not-allowed; }
    table.imp-tbl td.sc4-dual { white-space:nowrap; }
    table.imp-tbl td.sc4-dual input { width:3.9rem; }
    table.imp-tbl td.sc4-dual input + input { border-left:1px dashed var(--line-light); }
    table.imp-tbl td.sc4 input::placeholder { color:var(--muted-light); opacity:.55; font-style:italic; }

    /* สีพาสเทลแยกกลุ่มคะแนน: ชมพู เหลือง เขียว ฟ้า ม่วง ส้ม และเขียวอมฟ้า */
    table.imp-tbl [data-score-zone="1"] { --score-zone-color:oklch(72% .11 350); }
    table.imp-tbl [data-score-zone="2"] { --score-zone-color:oklch(78% .13 85); }
    table.imp-tbl [data-score-zone="3"] { --score-zone-color:oklch(70% .11 145); }
    table.imp-tbl [data-score-zone="4"] { --score-zone-color:oklch(70% .10 245); }
    table.imp-tbl [data-score-zone="5"] { --score-zone-color:oklch(69% .10 305); }
    table.imp-tbl [data-score-zone="6"] { --score-zone-color:oklch(75% .11 55); }
    table.imp-tbl [data-score-zone="7"] { --score-zone-color:oklch(70% .09 190); }
    table.imp-tbl tr.imp-h1 th[data-score-zone],
    table.imp-tbl tr.imp-h2 th[data-score-zone],
    table.imp-tbl tr.imp-filter th[data-score-zone] { background:color-mix(in oklch, var(--score-zone-color) 22%, var(--menu-bg)); }
    table.imp-tbl tr[data-filter-data-row] td[data-score-zone] { background:color-mix(in oklch, var(--score-zone-color) 8%, var(--menu-bg)); }
    table.imp-tbl .score-zone-start { box-shadow:inset 1px 0 0 color-mix(in oklch, var(--score-zone-color) 52%, var(--line-light)); }

    /* ระดับและลำดับชั้นใช้สีประจำคอลัมน์ชุดเดียวกันทั้งหัวตาราง ตัวกรอง และข้อมูล */
    table.imp-tbl [data-info-zone="level"] { --info-zone-color:oklch(70% .10 245); }
    table.imp-tbl [data-info-zone="hier-1"] { --info-zone-color:oklch(72% .11 350); }
    table.imp-tbl [data-info-zone="hier-2"] { --info-zone-color:oklch(78% .13 85); }
    table.imp-tbl [data-info-zone="hier-3"] { --info-zone-color:oklch(70% .11 145); }
    table.imp-tbl [data-info-zone="hier-4"] { --info-zone-color:oklch(69% .10 305); }
    table.imp-tbl tr.imp-h1 th[data-info-zone],
    table.imp-tbl tr.imp-h2 th[data-info-zone],
    table.imp-tbl tr.imp-filter th[data-info-zone] { background:color-mix(in oklch, var(--info-zone-color) 22%, var(--menu-bg)); }
    table.imp-tbl tr[data-filter-data-row] td[data-info-zone] { background:color-mix(in oklch, var(--info-zone-color) 8%, var(--menu-bg)); }
    table.imp-tbl .info-zone-start { box-shadow:inset 1px 0 0 color-mix(in oklch, var(--info-zone-color) 52%, var(--line-light)); }

    /* mode=none: ใช้สีประจำกลุ่มเต็มเซลล์ เข้มกว่าช่องปกติเล็กน้อย เพื่อบอกว่ากรอกไม่ได้ */
    table.imp-tbl tr[data-filter-data-row] td.score-unavailable { min-width:5.4rem; height:2rem; background:color-mix(in oklch, var(--score-zone-color) 15%, var(--menu-bg)) !important; cursor:not-allowed; }

    .score-legend { display:inline-flex; align-items:center; gap:.75rem; flex-wrap:wrap; color:var(--muted-light); font-size:.74rem; }
    .score-legend-item { display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .score-legend-swatch { width:1.2rem; height:.72rem; border-radius:.18rem; background:color-mix(in oklch, oklch(72% .11 350) 8%, var(--menu-bg)); box-shadow:inset 0 0 0 1px color-mix(in oklch, oklch(72% .11 350) 28%, var(--line-light)); }
    .score-legend-swatch.is-unavailable { background:color-mix(in oklch, oklch(72% .11 350) 15%, var(--menu-bg)); box-shadow:inset 0 0 0 1px color-mix(in oklch, oklch(72% .11 350) 38%, var(--line-light)); }
@endsection

@section('content')
  {{-- หัวหน้าผลลัพธ์ — ซ้าย: แท็บระบบ + เลือกรอบ + เลือกปี · ขวา: การ์ดสรุปการประเมิน (สูงกว่า จึงวางคู่กันไม่ให้เหลือช่องว่าง) --}}
  <div class="res-head">
    <div class="res-head-main">
      @include('assessment.partials.asmtabs', ['roundLabel' => false])

      {{-- ศูนย์จัดการรวม — กลุ่ม 1 นำเข้าโดยรวม · กลุ่ม 2 Export ผลลัพธ์ (เลือกรอบย้อนหลังได้) --}}
      <div class="res-cards">
        @if ($openRound)
          <div class="res-card">
            <b data-i18n="assessment.results.importOverall">นำเข้าข้อมูลโดยรวม</b>
            <p data-i18n="assessment.results.importHint">Template ของรอบที่เปิด กรอกนอกระบบแล้วนำเข้ากลับ</p>
            <div class="res-card-actions">
              <a href="{{ route('assessment.results.template') }}" class="asm-btn">⬇ Template</a>
              <form action="{{ route('assessment.scores.import') }}" method="POST" enctype="multipart/form-data" class="xls-import-form">
                @csrf
                <button type="submit" class="asm-btn primary" data-i18n="assessment.results.importButton">⬆ นำเข้า</button>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
              </form>
            </div>
          </div>
        @endif
        <div class="res-card">
          <b data-i18n="assessment.results.exportTitle">Export ผลลัพธ์</b>
          <p data-i18n="assessment.results.exportHint">ไฟล์ผลลัพธ์เต็มของรอบที่เลือก</p>
          <div class="res-card-actions">
            <form action="{{ route('assessment.results.download') }}" method="GET" class="res-dl-form">
              <select name="round" class="res-round-select" data-i18n-title="assessment.results.roundPicker" title="เลือกรอบที่จะดาวน์โหลด">
                @foreach ($rounds as $r)
                  <option value="{{ $r->id }}" @selected((int) $r->id === (int) $round->id)>{{ $r->isOpen() ? '● ' : '' }}{{ $r->name }} ({{ $r->year }})</option>
                @endforeach
              </select>
              <button type="submit" class="asm-btn primary" data-i18n="assessment.results.downloadButton">⬇ ดาวน์โหลด</button>
            </form>
          </div>
          <span class="res-card-note" data-i18n="assessment.results.roundOpenMark">● = รอบที่เปิดอยู่</span>
        </div>
      </div>
    </div>

    @include('assessment.partials.lvsummary', [
      'levelSummary' => $levelSummary ?? null,
      'activeFilterCount' => $activeFilterCount ?? 0,
    ])
  </div>

  @if (session('error'))
    <div class="flash error" style="margin-bottom:1rem">{{ session('error') }}</div>
  @endif

  @if (session('success'))
    <div class="flash success" style="margin-bottom:1rem">{{ session('success') }}</div>
  @endif

  {{-- เลือกปี — อยู่เหนือชื่อรอบที่กำลังดูอยู่ --}}
  <div class="yr-tabs">
    @foreach ($years as $y)
      <a href="{{ $resultUrl($rounds->firstWhere('year', $y)) }}" class="yr-tab nav-go {{ $round->year === $y ? 'is-active' : '' }}"><span data-i18n="assessment.results.year">ปี</span> {{ $y }}</a>
    @endforeach
  </div>

  <div class="res-bar">
    <span class="ttl">{{ $round->name }} ({{ $round->year }})</span>
    <span class="hint" style="margin:0" data-i18n="{{ $editable ? 'assessment.results.editableHint' : 'assessment.results.readonlyHint' }}">{{ $editable ? 'กรอกคะแนนได้ตามกติกาของแต่ละช่อง · N/A = ไม่คิดช่องนั้น' : 'รอบปิดแล้ว — ดูอย่างเดียว' }}</span>
    <span class="score-legend" aria-label="คำอธิบายสีตารางคะแนน">
      <span class="score-legend-item" title="สีพาสเทลแยกกลุ่มข้อมูลตามหัวข้อหลัก" data-i18n-title="assessment.results.legendGroupTitle"><i class="score-legend-swatch" aria-hidden="true"></i><span data-i18n="assessment.results.legendGroup">พาสเทล = กลุ่มข้อมูล</span></span>
      <span class="score-legend-item" title="คอลัมน์นี้ไม่ถูกคำนวณสำหรับระดับของพนักงานคนนั้น" data-i18n-title="assessment.results.legendUnavailableTitle"><i class="score-legend-swatch is-unavailable" aria-hidden="true"></i><span data-i18n="assessment.results.legendUnavailable">เข้มขึ้น = ระดับนี้ไม่คิด</span></span>
    </span>
    @if ($leaves4->count())
      @if ($activeFilterCount > 0)
        <button type="button" class="asm-btn" data-server-filter-clear data-i18n="assessment.results.clearFilters">ล้างตัวกรอง</button>
      @endif
      <span class="ss-count"><span data-i18n="assessment.common.showing">แสดง</span> <strong>{{ number_format($shownCount) }}</strong> / <span>{{ number_format($totalCount) }}</span> <span data-i18n="assessment.common.people">คน</span>@if ($activeFilterCount > 0) <span data-i18n="assessment.common.afterFilter">หลังกรอง</span>@endif</span>
    @endif
  </div>

  @if ($leaves4->count())
    <div class="imp-wrap" id="resultTableWrap">
      <table class="imp-tbl" data-filter-table="res">
        {{-- ลำดับคอลัมน์แสดงผล: ระดับ อยู่ขวาของ แผนก (ก่อนลำดับชั้น) — ดัชนีตัวกรอง (f[col]) คงเดิม: ระดับ=13, ลำดับชั้น=5-12 --}}
        <tr class="imp-h1">
          <th class="no">No.</th><th data-i18n="assessment.common.code">รหัส</th><th data-i18n="assessment.evaluate.fullName">ชื่อ-สกุล</th><th data-i18n="assessment.evaluate.position">ตำแหน่ง</th><th data-i18n="assessment.evaluate.department">แผนก</th>
          <th class="info-zone-start" data-info-zone="level" data-i18n="assessment.common.level">ระดับ</th>
          <th colspan="2" class="rzone info-zone-start" data-info-zone="hier-1" data-i18n="assessment.hierarchy.1">ลำดับชั้น 1</th><th colspan="2" class="rzone info-zone-start" data-info-zone="hier-2" data-i18n="assessment.hierarchy.2">ลำดับชั้น 2</th>
          <th colspan="2" class="rzone info-zone-start" data-info-zone="hier-3" data-i18n="assessment.hierarchy.3">ลำดับชั้น 3</th><th colspan="2" class="rzone info-zone-start" data-info-zone="hier-4" data-i18n="assessment.hierarchy.4">ลำดับชั้น 4</th>
          @foreach ($zones4 as $z)
            <th colspan="{{ $z['span'] }}" class="zone score-zone-start" data-score-zone="{{ ($loop->index % 7) + 1 }}">{{ $z['name'] }}</th>
          @endforeach
          @foreach ($resultGroups4 as $rg)
            <th colspan="{{ $rg['span'] }}" class="zone rzone" data-rzone-group>{{ $rg['label'] }}</th>
          @endforeach
        </tr>
        <tr class="imp-h2">
          <th class="no"></th><th></th><th></th><th></th><th></th>
          <th class="info-zone-start" data-info-zone="level"></th>
          <th class="rzone info-zone-start" data-info-zone="hier-1">ID Sup.</th><th class="rzone" data-info-zone="hier-1">Supervisor</th>
          <th class="rzone info-zone-start" data-info-zone="hier-2">ID Div.</th><th class="rzone" data-info-zone="hier-2">Division MGR</th>
          <th class="rzone info-zone-start" data-info-zone="hier-3">ID Dept.</th><th class="rzone" data-info-zone="hier-3">Dept.MGR</th>
          <th class="rzone info-zone-start" data-info-zone="hier-4">ID Plant</th><th class="rzone" data-info-zone="hier-4">Plant MGR</th>
          @foreach ($leaves4 as $leaf)
            @php $zoneMeta = $scoreZoneMeta[(int) $leaf->id] ?? ['tone' => 1, 'start' => false]; @endphp
            <th class="{{ $zoneMeta['start'] ? 'score-zone-start' : '' }}" data-score-zone="{{ $zoneMeta['tone'] }}">{{ $leaf->name }}</th>
          @endforeach
          @foreach ($resultHeaders4 as $rh)
            <th class="rzone" data-rh>{{ $rh }}</th>
          @endforeach
        </tr>
        @php
          $totalCols5 = 5 + 8 + 1 + $leaves4->count() + count($resultHeaders4);
          // ปุ่มกรองเรียงตามตำแหน่งแสดงผลใหม่ แต่ col ยังชี้ดัชนีเดิมของ resultColumnValue
          $filterCols = array_merge([1, 2, 3, 4, 13], range(5, 12), $totalCols5 - 1 >= 14 ? range(14, $totalCols5 - 1) : []);
        @endphp
        <tr class="imp-filter" data-filter-row="res">
          <th class="no"></th>
          @foreach ($filterCols as $i)
            @php
              $filterLeaf = $i >= 14 && $i < 14 + $leaves4->count()
                ? ($leaves4->values()[$i - 14] ?? null)
                : null;
              $filterZoneMeta = $filterLeaf
                ? ($scoreZoneMeta[(int) $filterLeaf->id] ?? ['tone' => 1, 'start' => false])
                : null;
              $filterInfoZone = $i === 13
                ? 'level'
                : ($i >= 5 && $i <= 12 ? 'hier-'.(intdiv($i - 5, 2) + 1) : null);
              $filterInfoStart = $filterInfoZone && ($i === 13 || (($i - 5) % 2 === 0));
              $filterClasses = array_filter([
                $filterZoneMeta && $filterZoneMeta['start'] ? 'score-zone-start' : null,
                $filterInfoStart ? 'info-zone-start' : null,
              ]);
            @endphp
            <th class="{{ implode(' ', $filterClasses) }}"
              @if ($filterZoneMeta) data-score-zone="{{ $filterZoneMeta['tone'] }}" @endif
              @if ($filterInfoZone) data-info-zone="{{ $filterInfoZone }}" @endif
            >@include('assessment.partials.fbtn', ['name' => 'res', 'col' => $i])</th>
          @endforeach
        </tr>
        @php $seen5 = []; $no5 = method_exists($employees, 'firstItem') ? (($employees->firstItem() ?? 1) - 1) : 0; @endphp
        @foreach ($employees as $emp)
          @php
            if (isset($seen5[$emp->employee_code])) continue;
            $seen5[$emp->employee_code] = true;
            $no5++;
          @endphp
          {{-- แถวแยกเป็น partial — ใช้ซ้ำกับ endpoint results.row.html (swap แถวสดหลังแก้ผู้ประเมิน) --}}
          @include('assessment.partials.resultrow', ['no' => $no5])
        @endforeach
        @if ($shownCount === 0)
          <tr>
            <td colspan="{{ $totalCols5 }}" class="res-empty" data-i18n="assessment.results.empty">ไม่พบข้อมูลพนักงานตามเงื่อนไข</td>
          </tr>
        @endif
      </table>
    </div>
    @if (method_exists($employees, 'hasPages') && $employees->hasPages())
      <div class="asm-page" data-results-pager>
        <span data-i18n="assessment.common.perPage">ต่อหน้า</span>
        <select data-page-select>
          @foreach ([100, 200, 500, 1000] as $pp)
            <option value="{{ request()->fullUrlWithQuery(['per_page' => $pp, 'page' => 1]) }}" @selected($perPage === $pp)>{{ $pp }}</option>
          @endforeach
        </select>
        <a class="asm-page-link nav-go {{ $employees->onFirstPage() ? 'is-disabled' : '' }}" @if (! $employees->onFirstPage()) href="{{ $employees->previousPageUrl() }}" @endif data-i18n="common.prevPage">ก่อนหน้า</a>
        <span><span data-i18n="assessment.common.page">หน้า</span> {{ number_format($employees->currentPage()) }} / {{ number_format($employees->lastPage()) }}</span>
        <a class="asm-page-link nav-go {{ $employees->hasMorePages() ? '' : 'is-disabled' }}" @if ($employees->hasMorePages()) href="{{ $employees->nextPageUrl() }}" @endif data-i18n="common.nextPage">ถัดไป</a>
      </div>
    @endif
  @else
    <p class="hint" data-i18n="assessment.results.noColumns">ยังไม่มีคอลัมน์คะแนน — สร้าง/นำเข้าที่หน้า จัดการ หัวข้อ 2 ก่อน</p>
  @endif

@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const H = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' };
      const req = (url, body, method = 'POST') => fetch(url, { method, headers: H, body: JSON.stringify(body || {}) }).then(r => r.json());
      const t = (key, fallback) => window.__portalLang?.text ? window.__portalLang.text(key, fallback) : fallback;
      const filterEndpoint = @json(route('assessment.results.filter.options'));
      const filterNone = '__asm_none__';
      const esc = s => String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
      const filterKey = col => `f[${col}][]`;
      const pagerReturnKey = 'assessment.results.pager-return.v1';
      const pagerAnchor = '#resultTableWrap';

      function rememberPagerPosition() {
        const wrap = document.querySelector(pagerAnchor);
        const state = {
          left: wrap ? wrap.scrollLeft : 0,
          top: wrap ? wrap.scrollTop : 0,
          at: Date.now(),
        };

        try {
          localStorage.setItem(pagerReturnKey, JSON.stringify(state));
        } catch (e) {}
      }

      function restorePagerPosition() {
        let state = null;
        try {
          state = JSON.parse(localStorage.getItem(pagerReturnKey) || 'null');
        } catch (e) {
          state = null;
        }
        if (!state) return;

        try {
          localStorage.removeItem(pagerReturnKey);
        } catch (e) {}
        if (Date.now() - Number(state.at || 0) > 120000) return;

        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            const wrap = document.querySelector(pagerAnchor);
            if (!wrap) return;

            wrap.scrollIntoView({ block: 'start', behavior: 'auto' });
            wrap.scrollLeft = Number(state.left || 0);
            wrap.scrollTop = Number(state.top || 0);
          });
        });
      }

      window.xlsFilterRefresh = window.xlsFilterRefresh || function () {};

      function clearFilterParams(params, col) {
        const key = filterKey(col);
        Array.from(params.keys()).forEach(k => {
          if (k === key || k === `f[${col}]`) params.delete(k);
        });
      }

      function hasFilter(col) {
        const params = new URLSearchParams(window.location.search);
        return params.has(filterKey(col)) || params.has(`f[${col}]`);
      }

      function syncFilterButtons() {
        document.querySelectorAll('[data-filter-btn][data-name="res"]').forEach(btn => {
          btn.classList.toggle('is-active', hasFilter(btn.dataset.col));
        });
      }

      function applyServerFilter(col, checked, totalOptions) {
        const params = new URLSearchParams(window.location.search);
        clearFilterParams(params, col);
        params.delete('page');
        if (checked.size === 0) {
          params.append(filterKey(col), filterNone);
        } else if (checked.size < totalOptions) {
          Array.from(checked).forEach(v => params.append(filterKey(col), v));
        }
        const qs = params.toString();
        rememberPagerPosition();
        window.location.href = window.location.pathname + (qs ? '?' + qs : '');
      }

      function clearAllServerFilters() {
        const params = new URLSearchParams(window.location.search);
        Array.from(params.keys()).forEach(k => {
          if (/^f\[\d+\](\[\])?$/.test(k)) params.delete(k);
        });
        params.delete('page');
        const qs = params.toString();
        rememberPagerPosition();
        window.location.href = window.location.pathname + (qs ? '?' + qs : '');
      }

      const fpop = document.createElement('div');
      fpop.className = 'ss-fpop';
      fpop.innerHTML = '<div class="ss-fpop-title" data-fpop-title></div>' +
        '<div class="ss-fpop-search"><input type="text" placeholder="' + esc(t('assessment.filter.searchPlaceholder', 'ค้นหา...')) + '"></div>' +
        '<div class="ss-fpop-list"></div>' +
        '<div class="ss-fpop-foot"><button type="button" class="clear">' + esc(t('assessment.filter.clear', 'ล้าง')) + '</button><button type="button" class="apply">' + esc(t('assessment.filter.apply', 'ตกลง')) + '</button></div>';
      document.body.appendChild(fpop);

      const fpopSearch = fpop.querySelector('.ss-fpop-search input');
      const fpopList = fpop.querySelector('.ss-fpop-list');
      const fpopTitle = fpop.querySelector('[data-fpop-title]');
      let filterCtx = null;

      function closeFilterPop() {
        fpop.classList.remove('show');
        filterCtx = null;
      }

      function renderServerFilterList(term) {
        if (!filterCtx) return;
        const q = (term || '').toLowerCase();
        const shown = filterCtx.options.filter(o => String(o.label).toLowerCase().includes(q));
        fpopList.innerHTML = '';
        const all = document.createElement('label');
        all.className = 'all';
        const allChecked = shown.length > 0 && shown.every(o => filterCtx.checked.has(o.value));
        all.innerHTML = '<input type="checkbox" data-all ' + (allChecked ? 'checked' : '') + '><span class="nm">' + esc(t('assessment.filter.selectAll', 'เลือกทั้งหมด')) + '</span>';
        fpopList.appendChild(all);
        if (!shown.length) {
          const none = document.createElement('div');
          none.className = 'none';
          none.textContent = t('assessment.filter.noResults', 'ไม่พบ');
          fpopList.appendChild(none);
          return;
        }
        shown.forEach(o => {
          const label = document.createElement('label');
          label.innerHTML = '<input type="checkbox" value="' + esc(o.value) + '" ' + (filterCtx.checked.has(o.value) ? 'checked' : '') + '><span class="nm">' + esc(o.label) + '</span><span class="cnt">' + o.count + '</span>';
          fpopList.appendChild(label);
        });
      }

      function positionFilterPop(btn) {
        const r = btn.getBoundingClientRect();
        let left = Math.min(r.left, window.innerWidth - fpop.offsetWidth - 8);
        if (left < 8) left = 8;
        let top = r.bottom + 4;
        if (top + fpop.offsetHeight > window.innerHeight - 8) top = Math.max(8, r.top - fpop.offsetHeight - 4);
        fpop.style.left = left + 'px';
        fpop.style.top = top + 'px';
      }

      function openServerFilter(btn) {
        const col = Number(btn.dataset.col);
        if (filterCtx && filterCtx.col === col) {
          closeFilterPop();
          return;
        }

        fpopTitle.textContent = t('loader.loading', 'กำลังโหลด...');
        fpopSearch.value = '';
        fpopList.innerHTML = '<div class="none">' + esc(t('assessment.filter.loading', 'กำลังโหลดตัวกรอง')) + '</div>';
        fpop.classList.add('show');
        positionFilterPop(btn);

        const params = new URLSearchParams(window.location.search);
        params.set('round', @json($round->id));
        params.set('col', String(col));

        fetch(filterEndpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
          .then(r => r.json())
          .then(d => {
            if (!d.ok) throw new Error(d.message || t('assessment.filter.loadFailed', 'โหลดตัวกรองไม่สำเร็จ'));
            const options = d.options || [];
            filterCtx = {
              col,
              options,
              checked: new Set(options.filter(o => o.selected).map(o => o.value)),
            };
            fpopTitle.textContent = t('assessment.filter.columnTitle', 'กรองคอลัมน์');
            renderServerFilterList('');
            positionFilterPop(btn);
            fpopSearch.focus();
          })
          .catch(err => {
            filterCtx = { col, options: [], checked: new Set() };
            fpopTitle.textContent = t('assessment.filter.columnTitle', 'กรองคอลัมน์');
            fpopList.innerHTML = '<div class="none">' + esc(err.message || t('assessment.filter.loadFailed', 'โหลดตัวกรองไม่สำเร็จ')) + '</div>';
          });
      }

      fpopSearch.addEventListener('input', () => renderServerFilterList(fpopSearch.value));
      fpopList.addEventListener('change', e => {
        if (!filterCtx) return;
        if (e.target.matches('[data-all]')) {
          const on = e.target.checked;
          fpopList.querySelectorAll('input[type=checkbox]:not([data-all])').forEach(cb => {
            cb.checked = on;
            if (on) filterCtx.checked.add(cb.value);
            else filterCtx.checked.delete(cb.value);
          });
          return;
        }
        if (!e.target.matches('input[type=checkbox]')) return;
        if (e.target.checked) filterCtx.checked.add(e.target.value);
        else filterCtx.checked.delete(e.target.value);
        const all = fpopList.querySelector('[data-all]');
        const boxes = Array.from(fpopList.querySelectorAll('input[type=checkbox]:not([data-all])'));
        if (all) all.checked = boxes.length > 0 && boxes.every(b => b.checked);
      });
      fpop.querySelector('.apply').addEventListener('click', () => {
        if (!filterCtx) return;
        applyServerFilter(filterCtx.col, filterCtx.checked, filterCtx.options.length);
      });
      fpop.querySelector('.clear').addEventListener('click', () => {
        if (!filterCtx) return;
        const params = new URLSearchParams(window.location.search);
        clearFilterParams(params, filterCtx.col);
        params.delete('page');
        const qs = params.toString();
        rememberPagerPosition();
        window.location.href = window.location.pathname + (qs ? '?' + qs : '');
      });
      document.addEventListener('click', function (e) {
        const link = e.target.closest('.asm-page[data-results-pager] a.asm-page-link[href]');
        if (!link || link.classList.contains('is-disabled')) return;
        rememberPagerPosition();
      });
      document.addEventListener('change', function (e) {
        const select = e.target.closest('.asm-page[data-results-pager] select[data-page-select]');
        if (!select) return;

        rememberPagerPosition();
        window.location.href = select.value;
      });
      document.querySelectorAll('[data-filter-btn][data-name="res"]').forEach(btn => {
        btn.addEventListener('click', e => {
          e.stopPropagation();
          openServerFilter(btn);
        });
      });
      document.querySelector('[data-server-filter-clear]')?.addEventListener('click', clearAllServerFilters);
      document.addEventListener('click', e => {
        if (fpop.classList.contains('show') && !fpop.contains(e.target) && !e.target.closest('[data-filter-btn]')) closeFilterPop();
      });
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && fpop.classList.contains('show')) closeFilterPop();
      });
      document.querySelectorAll('.imp-wrap').forEach(w => w.addEventListener('scroll', closeFilterPop));
      syncFilterButtons();
      restorePagerPosition();

      const editable = @json($editable);
      if (!editable) return;

      // ดึงแถวสดจาก server มาแทนแถวเดิม (หลังผู้ประเมินครบ/เปลี่ยน → ปลดล็อกช่องคะแนน + merge + ผลลัพธ์ใหม่)
      function refreshRow(code) {
        const p = new URLSearchParams();
        p.set('round', @json($round->id));
        p.set('code', code);
        fetch(`{{ route('assessment.results.row.html') }}?` + p.toString(), { headers: { Accept: 'application/json' } })
          .then(r => r.json())
          .then(d => {
            if (!d.ok) return;
            const old = document.querySelector(`tr[data-frow="${code}"]`);
            if (!old) return;
            const holder = document.createElement('tbody');
            holder.innerHTML = d.html.trim();
            const fresh = holder.querySelector('tr');
            if (!fresh) return;
            const noCell = fresh.querySelector('td.no[data-no]');
            const oldNo = old.querySelector('td.no');
            if (noCell && oldNo && !noCell.textContent.trim()) noCell.textContent = oldNo.textContent;
            old.replaceWith(fresh);
          });
      }

      // แก้ผู้ประเมิน (ลำดับชั้น 1-4) ในแถว — บันทึกอัตโนมัติ ; แถวยังไม่ครบ = พิมพ์ต่อได้ ไม่ swap รบกวน
      document.addEventListener('change', function (e) {
        const inp = e.target;
        if (!inp.matches('td.hcell-edit input[data-field]') || inp.disabled) return;
        const tr = inp.closest('tr[data-frow]');
        req(`{{ route('assessment.scores.hierarchy.save') }}`, { employee_code: inp.dataset.emp, field: inp.dataset.field, value: inp.value }, 'PUT')
          .then(res => {
            if (!res.ok) { alert(res.message || t('assessment.scores.saveError', 'บันทึกไม่สำเร็จ')); return; }
            inp.classList.add('saved');
            setTimeout(() => inp.classList.remove('saved'), 1200);
            const wasComplete = tr && tr.dataset.complete === '1';
            if (!wasComplete && !res.complete) return;   // ยังกรอกไม่ครบ 8 ช่อง → กรอกช่องถัดไปต่อ
            refreshRow(inp.dataset.emp);
          });
      });

      // แก้คะแนน inline (เฉพาะรอบที่เปิด) → ผลลัพธ์แถวนั้นคำนวณใหม่ทันที
      document.addEventListener('change', function (e) {
        const inp = e.target;
        if (!inp.matches('td.sc4 input') || inp.disabled) return;
        let payload = inp.value.trim();
        if (inp.dataset.parts) {
          // ช่อง Input: แต่ละช่องครอบได้หลาย slot (data-parts) — เติมค่าลงทุก slot ที่ครอบ (merge = คนเดียวกัน)
          const inputs = Array.from(inp.closest('td').querySelectorAll('input[data-parts]'));
          const slots = [];
          inputs.forEach(i => { i.dataset.parts.split(',').forEach(p => { slots[Number(p) - 1] = i.value.trim(); }); });
          while (slots.length && (slots[slots.length - 1] === undefined || slots[slots.length - 1] === '')) slots.pop();
          payload = slots.map(s => (s === undefined ? '' : s)).join(',');
        }
        req(`{{ route('assessment.scores.value') }}`, { employee_code: inp.dataset.emp, box_id: inp.dataset.box, value: payload }, 'PUT')
          .then(res => {
            if (!res.ok) { alert(res.message || t('assessment.scores.saveError', 'บันทึกไม่สำเร็จ')); return; }
            const val = res.value === null ? '' : String(res.value);
            const parts = val.split(',');
            if (inp.dataset.parts) {
              inp.closest('td').querySelectorAll('input[data-parts]').forEach(o => { o.value = parts[Number(o.dataset.parts.split(',')[0]) - 1] ?? ''; });
            } else {
              inp.value = val;
            }
            inp.classList.add('saved');
            setTimeout(() => inp.classList.remove('saved'), 1200);
            if (window.xlsFilterRefresh) window.xlsFilterRefresh('res');
            const tr = inp.closest('tr[data-frow]');
            if (!tr) return;
            fetch(`{{ route('assessment.scores.result.row') }}?code=` + encodeURIComponent(inp.dataset.emp), { headers: { 'Accept': 'application/json' } })
              .then(r => r.json())
              .then(d => { if (d.ok) tr.querySelectorAll('td.rc').forEach((td, i) => { td.textContent = d.cells[i] ?? '—'; }); });
          });
      });
    })();
  </script>
@endsection
