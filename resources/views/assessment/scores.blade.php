@extends('layouts.portal')

@section('title', 'จัดการข้อมูลพนักงาน')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="nav.asmScores">จัดการข้อมูลพนักงาน</span>
@endsection

@php
  $fmt = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
  $byLevel = collect($positions)->groupBy(fn ($p) => $p['level'] === null ? 'un' : (int) $p['level']);
  // ระดับ 0 (default มีเสมอ ลบไม่ได้) + ระดับ 1..$maxLevel ที่ admin เพิ่มเองด้วยปุ่ม "+" ใน 1.1
  $maxLevel = max(0, (int) ($maxLevel ?? 0));
  $levelLabels = [0 => 'ระดับ 0 (ไม่ถูกประเมิน)'];
  for ($__lv = 1; $__lv <= $maxLevel; $__lv++) $levelLabels[$__lv] = 'ระดับ '.$__lv;
  $importedOpen = session()->has('assessment_imported');
  $previewOpen = false;
  $hf = ['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'];
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
      $key = $name !== '' ? 'n:'.$name : 'p:'.$i;
      if (! isset($groups[$key])) {
        $groups[$key] = ['parts' => [], 'levels' => [], 'name' => $name];
        $order[] = $key;
      }
      $groups[$key]['parts'][] = $i + 1;
      $groups[$key]['levels'][] = $lv;
    }
    return array_map(fn ($k) => $groups[$k], $order);
  };
  $slotVal = fn ($pv, int $part) => $pv[$part === 1 ? 'v' : 'v'.$part] ?? null;
  // แบ่งหน้า
  $pageRows = method_exists($employees, 'getCollection') ? $employees->getCollection() : collect($employees);
  $shownCount = $pageRows->unique('employee_code')->count();
  $totalCount = method_exists($employees, 'total') ? $employees->total() : $shownCount;
  $rowStart = method_exists($employees, 'firstItem') ? (($employees->firstItem() ?? 1) - 1) : 0;
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
    .asm-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.85rem; text-decoration:none; }
    .asm-btn:hover { border-color:var(--moss); }
    .asm-btn.primary { background:var(--moss); color:#fff; border-color:var(--moss); }
    .asm-btn.sm { padding:.3rem .6rem; font-size:.78rem; }
    .asm-btn.xls-clear-hier { border-color:#b42318; background:#b42318; color:#fff; }
    .asm-btn.xls-clear-hier:hover { border-color:#951b12; background:#951b12; color:#fff; }
    .asm-btn.xls-clear-hier[disabled] { opacity:.55; cursor:default; }

    /* ---- หัวข้อ 2 จัดการคอลัมน์: การ์ดหัวข้อหลัก + คอลัมน์รอง ---- */
    .colcard-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(17rem, 1fr)); gap:.9rem; align-items:start; }
    .colcard { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--menu-bg); padding:.9rem .95rem; display:flex; flex-direction:column; gap:.55rem; min-height:11rem; }
    .colcard-head { display:flex; align-items:center; gap:.4rem; padding-bottom:.55rem; border-bottom:1px solid var(--line-light); }
    .colcard-name { flex:1; min-width:0; padding:.3rem .45rem; border:1px solid transparent; border-radius: 0.25rem; background:transparent; color:var(--moss); font-weight:700; font-size:.92rem; }
    .colcard-name:hover, .colcard-name:focus { border-color:var(--line-light); background:var(--panel-soft); outline:none; }
    .colcard-name.saved, .colsub-name.saved { border-color:var(--moss) !important; }
    .colcard-del { flex:none; width:1.5rem; height:1.5rem; display:grid; place-items:center; border:1px solid transparent; border-radius: 0.25rem; background:transparent; color:var(--muted-light); cursor:pointer; font-size:.72rem; }
    .colcard-del:hover { border-color:#d98a80; color:#d98a80; }
    .colcard-subs { display:flex; flex-direction:column; gap:.3rem; }
    .colsub { display:flex; align-items:center; gap:.35rem; }
    .colsub-name { flex:1; min-width:0; padding:.28rem .45rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; font-size:.82rem; }
    .colsub-name:focus { outline:none; border-color:var(--moss); }
    .colsub-add input { width:100%; padding:.3rem .45rem; border:1px dashed var(--line-strong); border-radius: 0.25rem; background:transparent; color:inherit; font-size:.8rem; }
    .colsub-add input:focus { outline:none; border-color:var(--moss); border-style:solid; }
    /* หัวข้อ 3 สัดส่วนและการคำนวณ */
    .prop-totalbar { margin:.2rem 0 .8rem; font-size:.9rem; }
    .prop-totalbar b { font-size:1.05rem; }
    .propcard { min-height:auto; }
    .propcard-head { display:flex; align-items:center; gap:.5rem; padding-bottom:.5rem; border-bottom:1px solid var(--line-light); }
    .propcard-name { flex:1; min-width:0; color:var(--moss); font-weight:700; font-size:.92rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .propcard select, .propcard input[type="text"] { padding:.3rem .45rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; font-size:.8rem; }
    .propcard select:focus, .propcard input:focus { outline:none; border-color:var(--moss); }
    .propcard select.saved, .propcard input.saved { border-color:var(--moss); }
    .prop-custom { width:5.2rem; }
    .prop-row { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; padding:.4rem .45rem; margin:0 -.45rem; border-radius: 0.25rem; border-bottom:1px dashed var(--line-light); font-size:.8rem; transition:background-color .15s; }
    .prop-row:hover { background:var(--hover-soft); }
    .prop-row:last-child { border-bottom:none; }
    .prop-row-name { flex:1 1 7.5rem; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; }
    .prop-row.is-none { opacity:.45; }
    .prop-row.is-none .prop-row-name { font-weight:400; }
    .prop-row.is-extra .prop-row-name::after { content:" +/-"; color:var(--moss); font-weight:700; }
    .prop-row select[hidden], .prop-row input[hidden] { display:none; }
    .propcard { transition:border-color .15s; }
    .propcard:hover { border-color:var(--moss); }
    .propcard .lv-title { padding-bottom:.5rem; border-bottom:1px solid var(--line-light); margin-bottom:.2rem; }
    .prop-lv-badge { display:inline-flex; align-items:center; justify-content:center; min-width:1.5rem; height:1.5rem; padding:0 .3rem; border-radius: 0.25rem; background:var(--moss); color:#fff; font-weight:700; font-size:.8rem; }

    /* 3.2 การคำนวณคอลัมน์รอง — การ์ดโซนละหัวข้อหลัก แถวละ 2 การ์ด */
    .sc-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.9rem; align-items:start; }
    @media (max-width: 860px) { .sc-grid { grid-template-columns:1fr; } }
    .sc-card { min-height:auto; transition:border-color .15s; }
    .sc-card:hover { border-color:var(--moss); }
    .sc-card .lv-title { padding-bottom:.5rem; border-bottom:1px solid var(--line-light); margin-bottom:.2rem; }
    .sc-card-name { color:var(--moss); font-weight:700; font-size:.92rem; }
    .sc-row { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; padding:.4rem .45rem; margin:0 -.45rem; border-radius: 0.25rem; border-bottom:1px dashed var(--line-light); font-size:.8rem; transition:background-color .15s; }
    .sc-row:hover { background:var(--hover-soft); }
    .sc-row:last-child { border-bottom:none; }
    .sc-name { flex:0 1 auto; max-width:11rem; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; }
    .sc-row select, .sc-row input { padding:.26rem .45rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; font-size:.78rem; }
    .sc-row select:focus, .sc-row input:focus { outline:none; border-color:var(--moss); }
    .sc-row select.saved, .sc-row input.saved { border-color:var(--moss); }
    .sc-rate { width:6rem; }
    .sc-full { width:5.5rem; }
    .sc-row select[hidden], .sc-row input[hidden] { display:none; }
    .sc-muted { color:var(--muted-light); font-size:.74rem; }
    .sc-sum { margin-right:auto; color:var(--muted-light); font-size:.76rem; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .sc-sum.warn { color:#d98a80; }
    .sc-lvls { display:inline-flex; align-items:center; gap:.25rem; flex-wrap:wrap; }
    .sc-lv { padding:.15rem .45rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; cursor:pointer; font-size:.72rem; white-space:nowrap; }
    .sc-lv:hover { border-color:#d98a80; color:#d98a80; }
    .sc-lv-add { width:auto; }
    /* ช่องคู่ชนิด Input (ซ้าย = ลำดับ 1, ขวา = ลำดับ 2) */
    table.imp-tbl td.sc4-dual { white-space:nowrap; }
    table.imp-tbl td.sc4-dual input { width:3.9rem; }
    table.imp-tbl td.sc4-dual input + input { border-left:1px dashed var(--line-light); }
    table.imp-tbl td.sc4 input::placeholder { color:var(--muted-light); opacity:.55; font-style:italic; }

    /* หัวข้อ 4: ตารางคะแนน (แก้ได้) + ผลลัพธ์ */
    .imp-wrap { overflow:auto; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); margin-top:.2rem; scroll-margin-top:calc(var(--topbar-h, 4.5rem) + 1rem); }
    table.imp-tbl { border-collapse:separate; border-spacing:0; font-size:.8rem; min-width:100%; }
    table.imp-tbl th, table.imp-tbl td { border-right:1px solid var(--line-light); border-bottom:1px solid var(--line-light); padding:.28rem .6rem; white-space:nowrap; text-align:left; }
    table.imp-tbl tr.imp-h1 th { position:sticky; top:0; z-index:3; height:1.7rem; background:var(--panel-soft); text-align:center; color:var(--moss); font-weight:600; }
    table.imp-tbl tr.imp-h2 th { position:sticky; top:1.7rem; z-index:3; background:var(--panel-soft); text-align:center; font-weight:600; }
    table.imp-tbl tr.imp-filter th { position:sticky; top:3.4rem; z-index:3; background:var(--panel-soft); padding:.16rem .3rem; }
    table.imp-tbl th.no, table.imp-tbl td.no { width:1%; text-align:right; color:var(--muted-light); font-variant-numeric:tabular-nums; }
    table.imp-tbl td.code { font-weight:700; }
    table.imp-tbl td.sc4 { padding:0; }
    table.imp-tbl td.sc4 input { width:5.6rem; padding:.28rem .5rem; border:1px solid transparent; border-radius:.3rem; background:transparent; color:inherit; text-align:right; font-size:.78rem; font-variant-numeric:tabular-nums; }
    table.imp-tbl td.sc4 input:focus { outline:none; border-color:var(--moss); background:var(--panel-soft); }
    table.imp-tbl td.sc4 input.saved { border-color:var(--moss); }
    table.imp-tbl th.rzone { color:#c8a24a !important; }
    table.imp-tbl td.rc { text-align:right; color:var(--muted-light); font-variant-numeric:tabular-nums; }
    table.imp-tbl td.hcell { padding:0; }
    table.imp-tbl td.hcell input { width:8rem; padding:.28rem .5rem; border:1px solid transparent; border-radius:.3rem; background:transparent; color:inherit; font-size:.78rem; }
    table.imp-tbl td.hcell input:focus { outline:none; border-color:var(--moss); background:var(--panel-soft); }
    table.imp-tbl td.hcell input.saved { border-color:var(--moss); }
    table.imp-tbl input.asm-excluded-input,
    table.ss input.asm-excluded-input { color:var(--muted-light); text-align:center; cursor:not-allowed; }

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
    table.imp-tbl tr[data-filter-data-row] td.score-unavailable { min-width:5.6rem; height:2rem; background:color-mix(in oklch, var(--score-zone-color) 15%, var(--menu-bg)) !important; cursor:not-allowed; }

    .calc-actions { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin:.4rem 0 .9rem; }
    .score-legend { display:inline-flex; align-items:center; gap:.75rem; flex-wrap:wrap; color:var(--muted-light); font-size:.74rem; }
    .score-legend-item { display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .score-legend-swatch { width:1.2rem; height:.72rem; border-radius:.18rem; background:color-mix(in oklch, oklch(72% .11 350) 8%, var(--menu-bg)); box-shadow:inset 0 0 0 1px color-mix(in oklch, oklch(72% .11 350) 28%, var(--line-light)); }
    .score-legend-swatch.is-unavailable { background:color-mix(in oklch, oklch(72% .11 350) 15%, var(--menu-bg)); box-shadow:inset 0 0 0 1px color-mix(in oklch, oklch(72% .11 350) 38%, var(--line-light)); }

    /* จุดสรุปสัดส่วนต่อระดับ — การ์ดเดียว 5 บรรทัด */
    .prop-summary { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--menu-bg); padding:.25rem 1rem; margin:0 0 1rem; }
    .prop-summary:empty { display:none; }
    .prop-sum-row { display:flex; align-items:baseline; gap:.6rem; padding:.5rem 0; border-bottom:1px dashed var(--line-light); font-size:.8rem; }
    .prop-sum-row:last-child { border-bottom:none; }
    .prop-sum-lv { flex:none; color:var(--moss); font-weight:700; }
    .prop-sum-items { min-width:0; color:var(--muted-light); line-height:1.6; }
    .prop-sum-items b { color:var(--light-text, inherit); font-variant-numeric:tabular-nums; }
    .prop-sum-items .sum-extra { color:var(--moss); font-weight:700; }
    .prop-sum-ok { flex:none; margin-left:auto; font-weight:700; font-variant-numeric:tabular-nums; }

    .colcard-new { border-style:dashed; background:transparent; justify-content:center; }
    .colcard-new-inner { display:flex; flex-direction:column; gap:.5rem; }
    .colcard-new-inner input { padding:.4rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.85rem; }
    .colcard-new-inner input:focus { outline:none; border-color:var(--moss); }

    /* ---- step card (collapsible) ---- */
    .step-card { border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); margin-bottom:1.3rem; overflow:hidden; }
    .step-summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:.8rem; padding:1.1rem 1.4rem; }
    .step-summary::-webkit-details-marker { display:none; }
    .step-no { display:inline-flex; align-items:center; justify-content:center; width:1.8rem; height:1.8rem; border-radius:50%; background:var(--moss); color:#fff; font-weight:700; font-size:.95rem; flex:none; }
    .step-summary b { font-size:1.05rem; }
    .step-sub { color:var(--muted-light); font-size:.82rem; margin-left:.2rem; }
    .step-caret { margin-left:auto; transition:transform .2s; color:var(--muted-light); }
    .step-card[open] .step-caret { transform:rotate(180deg); }
    .step-body { padding:0 1.4rem 1.4rem; }
    .step-body h3 { margin:1.2rem 0 .2rem; font-size:.98rem; }
    .step-body .hint { margin:0 0 .8rem; color:var(--muted-light); font-size:.82rem; }

    /* ---- level cards ---- */
    .lv-card { position:relative; border:1px solid var(--line-light); border-radius: 0.28rem; background:var(--menu-bg); padding:.7rem .8rem; }
    /* ปุ่ม ✕ ลบระดับ (เฉพาะระดับบนสุดที่เกิน 5) — มุมขวาบนของการ์ด */
    .lv-del { position:absolute; top:.35rem; right:.35rem; width:1.4rem; height:1.4rem; display:grid; place-items:center; border:1px solid transparent; border-radius: 0.25rem; background:transparent; color:var(--muted-light); cursor:pointer; font-size:.7rem; line-height:1; }
    .lv-del:hover { border-color:#d98a80; color:#d98a80; }
    .lv-card:has(.lv-del) .lv-title { padding-right:1.3rem; }
    .lv-title { font-size:.82rem; font-weight:600; margin-bottom:.5rem; display:flex; justify-content:space-between; gap:.5rem; }
    .lv-count { color:var(--muted-light); font-weight:400; }
    .lv-un { margin-bottom:.8rem; border-style:dashed; background:transparent; }
    .lv-un .lv-chips { flex-direction:row; flex-wrap:wrap; }
    .lv-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(14rem,1fr)); gap:.7rem; }
    .lv-add { display:grid; place-items:center; min-height:5.2rem; border:1px dashed var(--line-strong); background:transparent; color:var(--muted-light); cursor:pointer; font-size:.85rem; font-weight:600; }
    .lv-add:hover { border-color:var(--moss); color:var(--moss); }
    .lv-chips { display:flex; flex-direction:column; gap:.35rem; min-height:1.5rem; }
    .lv-chip { display:flex; align-items:center; justify-content:space-between; gap:.4rem; padding:.3rem .5rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); font-size:.8rem; }
    .lv-chip .nm { white-space:nowrap; }
    .lv-chip small { color:var(--muted-light); }
    .lv-chip select { padding:.2rem .3rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.78rem; }

    /* ---- excel-like spreadsheet ---- */
    .xls-head { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin:1.2rem 0 .5rem; }
    .xls-head h3 { margin:0; }
    .xls-preview-head { scroll-margin-top:calc(var(--topbar-h, 4.35rem) + 1rem); }
    .xls-actions { display:flex; align-items:center; justify-content:flex-end; gap:1.35rem; flex:1 1 44rem; flex-wrap:wrap; }
    .xls-file-actions { display:flex; align-items:center; gap:2.2rem; flex-wrap:wrap; margin-left:auto; }
    .xls-import-form { display:flex; align-items:center; gap:1.1rem; margin:0; }
    .xls-import-form input[type="file"] { max-width:15rem; font-size:.78rem; }
    .xls-file-actions .asm-btn.sm { min-height:2rem; justify-content:center; }
    .xls-download { margin-right:.45rem; }
    @media (max-width: 820px) {
      .xls-head { align-items:flex-start; }
      .xls-actions,
      .xls-file-actions { width:100%; justify-content:flex-start; margin-right:0; margin-left:0; }
      .xls-import-form input[type="file"] { max-width:100%; }
    }

    .ss-wrap { --ss-h1-top:1.5rem; --ss-h2-top:3.25rem; --ss-filter-top:5rem; position:relative; overflow:auto; border:1px solid var(--line-light); border-radius: 0.25rem; max-height:78vh; background:var(--menu-bg); scroll-margin-top:calc(var(--topbar-h, 4.5rem) + 1rem); }
    table.ss { border-collapse:separate; border-spacing:0; font-size:.8rem; }
    table.ss th, table.ss td { border-right:1px solid var(--line-light); border-bottom:1px solid var(--line-light); padding:.28rem .55rem; white-space:nowrap; text-align:left; }
    table.ss tr.ss-frame th { position:sticky; top:0; z-index:5; height:1.4rem; padding:.05rem .5rem; background:var(--panel-soft); color:var(--muted-light); text-align:center; font-weight:500; }
    table.ss tr.ss-h1 th { position:sticky; top:var(--ss-h1-top); z-index:4; height:1.7rem; background:var(--menu-bg); text-align:center; color:var(--moss); }
    table.ss tr.ss-h2 th { position:sticky; top:var(--ss-h2-top); z-index:4; height:1.7rem; background:var(--menu-bg); text-align:center; font-weight:600; }
    table.ss tr.ss-filter th { position:sticky; top:var(--ss-filter-top); z-index:4; height:2rem; padding:.18rem .3rem; background:var(--panel-soft); }
    table.ss th.hier { color:#c8a24a !important; font-weight:500; }
    table.ss th.zone { color:var(--moss); }
    table.ss .ss-rownum { position:sticky; left:0; z-index:3; width:3.2rem; min-width:3.2rem; text-align:center; color:var(--muted-light); background:var(--panel-soft); font-weight:500; }
    table.ss .ss-corner { position:sticky; left:0; top:0; z-index:7; background:var(--panel-soft); width:3.2rem; min-width:3.2rem; }
    table.ss tr.ss-h1 .ss-rownum { top:var(--ss-h1-top); z-index:6; }
    table.ss tr.ss-h2 .ss-rownum { top:var(--ss-h2-top); z-index:6; }
    table.ss tr.ss-filter .ss-rownum { top:var(--ss-filter-top); z-index:6; font-size:.7rem; }
    table.ss .ss-c1 { position:sticky; left:3.2rem; z-index:2; background:var(--menu-bg); }
    table.ss tr.ss-frame th.ss-c1 { z-index:6; background:var(--panel-soft); }
    table.ss tr.ss-h1 .ss-c1 { top:var(--ss-h1-top); z-index:5; }
    table.ss tr.ss-h2 .ss-c1 { top:var(--ss-h2-top); z-index:5; }
    table.ss tr.ss-filter .ss-c1 { top:var(--ss-filter-top); z-index:5; background:var(--panel-soft); }
    /* ---- excel-style filter button + popup ---- */
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
    table.ss tr[hidden] { display:none; }
    table.ss tbody tr:nth-child(even) td:not(.ss-c1) { background:color-mix(in srgb, var(--menu-bg) 40%, transparent); }
    table.ss tbody tr:hover td { background:var(--panel-soft); }
    table.ss td.empty-hier { background:var(--menu-bg); }
    table.ss td.lvl { text-align:center; font-weight:600; color:var(--moss); }
    table.ss td.score { padding:0; }
    table.ss td.score input { width:100%; min-width:4.5rem; padding:.28rem .5rem; border:none; background:transparent; color:inherit; text-align:right; }
    table.ss td.score input:focus { outline:2px solid var(--moss); outline-offset:-2px; background:var(--panel-soft); }
    table.ss td.score input.saved { background:rgb(155 208 166 / 22%); }
    table.ss td.hcell { padding:0; }
    table.ss td.hcell input { width:100%; min-width:5.5rem; padding:.28rem .5rem; border:none; background:transparent; color:inherit; }
    table.ss td.hcell input:focus { outline:2px solid var(--moss); outline-offset:-2px; background:var(--panel-soft); }
    table.ss td.hcell input.saved { background:rgb(155 208 166 / 22%); }
    table.ss th.hnum { color:#c8a24a; }
    table.ss [data-info-zone="level"] { --info-zone-color:oklch(70% .10 245); }
    table.ss [data-info-zone="hier-1"] { --info-zone-color:oklch(72% .11 350); }
    table.ss [data-info-zone="hier-2"] { --info-zone-color:oklch(78% .13 85); }
    table.ss [data-info-zone="hier-3"] { --info-zone-color:oklch(70% .11 145); }
    table.ss [data-info-zone="hier-4"] { --info-zone-color:oklch(69% .10 305); }
    table.ss tr.ss-frame th[data-info-zone],
    table.ss tr.ss-h1 th[data-info-zone],
    table.ss tr.ss-h2 th[data-info-zone],
    table.ss tr.ss-filter th[data-info-zone] { background:color-mix(in oklch, var(--info-zone-color) 22%, var(--menu-bg)); }
    table.ss tr[data-filter-data-row] td[data-info-zone] { background:color-mix(in oklch, var(--info-zone-color) 8%, var(--menu-bg)); }
    table.ss tbody tr:hover td[data-info-zone] { background:color-mix(in oklch, var(--info-zone-color) 14%, var(--menu-bg)); }
    table.ss .info-zone-start { box-shadow:inset 1px 0 0 color-mix(in oklch, var(--info-zone-color) 52%, var(--line-light)); }

    /* ---- ตาราง 1.2: แยกสีเป็นกลุ่มให้กวาดตาง่าย ----
       คอลัมน์ 2-5 = รหัส · ชื่อ-สกุล · ตำแหน่ง · แผนก (ข้อมูลพนักงาน)
       ใช้ nth-child กับ tbody เท่านั้น เพราะแถวหัวมี colspan ทำให้ลำดับไม่ตรง */
    table.ss { --emp-zone-color:oklch(74% .055 200); }
    table.ss tbody td:nth-child(n+2):nth-child(-n+5) { background:color-mix(in oklch, var(--emp-zone-color) 9%, var(--menu-bg)); }
    table.ss tbody tr:hover td:nth-child(n+2):nth-child(-n+5) { background:color-mix(in oklch, var(--emp-zone-color) 16%, var(--menu-bg)); }
    table.ss tr.ss-h2 th:nth-child(n+2):nth-child(-n+5) { background:color-mix(in oklch, var(--emp-zone-color) 22%, var(--menu-bg)); }
    table.ss tbody td:nth-child(2) { box-shadow:inset 1px 0 0 color-mix(in oklch, var(--emp-zone-color) 52%, var(--line-light)); }

    /* คอลัมน์ "ระดับ" — ช่องข้อมูลไม่ต้องมีเส้นกรอบ (หัวตารางยังมีไว้บอกขอบโซน) */
    table.ss tbody td[data-info-zone="level"] { box-shadow:none; }
    .ss-count { color:var(--muted-light); font-size:.8rem; margin-top:.5rem; }
    .ss-count strong { color:inherit; font-weight:600; }
    .asm-empty { color:var(--muted-light); font-size:.85rem; padding:.4rem 0; }
    .dl-bar { margin-top:1.3rem; padding-top:1rem; border-top:1px solid var(--line-light); }
@endsection

@section('content')
  @include('assessment.partials.asmtabs')

  {{-- ============ จัดการข้อมูลพนักงาน (สเปรดชีตเดียว) ============ --}}
  <details class="step-card" data-score-preview-step @if($previewOpen) open @endif>
    <summary class="step-summary">
      <span class="step-no" aria-hidden="true">1</span>
      <b data-i18n="nav.asmScores">จัดการข้อมูลพนักงาน</b>
      <span class="step-sub" data-i18n="assessment.scores.step1Sub">ตั้งระดับตำแหน่ง · แก้คะแนน + ลำดับชั้นในตาราง · ดาวน์โหลด/นำเข้า Excel</span>
      <svg class="step-caret" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
    </summary>

    <div class="step-body">
      {{-- ---- ระดับตำแหน่ง ---- --}}
      <h3><span data-i18n="assessment.scores.h11">1.1 ระดับตำแหน่ง</span></h3>
      <p class="hint" data-i18n="assessment.scores.h11Hint">จัดตำแหน่งลงระดับ · ระดับ 0 = ไม่ถูกประเมิน · ปุ่ม + เพิ่มระดับ, ✕ ลบ (เลขเรียงใหม่ให้เอง)</p>

      <div data-levels>
        {{-- ยังไม่จัดระดับ (แนวยาวด้านบน) --}}
        <div class="lv-card lv-un" data-level="un">
          <div class="lv-title"><span data-i18n="assessment.scores.unleveled">ยังไม่จัดระดับ</span><span class="lv-count">{{ ($byLevel['un'] ?? collect())->count() }}</span></div>
          <div class="lv-chips" data-lv-chips>
            @foreach (($byLevel['un'] ?? []) as $p)
              @include('assessment.partials.lvchip', ['p' => $p])
            @endforeach
          </div>
        </div>

        {{-- ระดับ 0..N (เพิ่มระดับได้ด้วยปุ่ม +) --}}
        <div class="lv-cards">
          @foreach ($levelLabels as $lv => $label)
            <div class="lv-card" data-level="{{ $lv }}">
              <div class="lv-title"><span>{{ $label }}</span><span class="lv-count">{{ ($byLevel[$lv] ?? collect())->count() }}</span></div>
              <div class="lv-chips" data-lv-chips>
                @foreach (($byLevel[$lv] ?? []) as $p)
                  @include('assessment.partials.lvchip', ['p' => $p])
                @endforeach
              </div>
            </div>
          @endforeach
          <button type="button" class="lv-card lv-add" data-add-level title="เพิ่มระดับ" data-i18n-title="assessment.scores.addLevel" data-i18n="assessment.scores.addLevel">＋ เพิ่มระดับ</button>
        </div>
      </div>

      {{-- ---- ดูตัวอย่าง (สเปรดชีต) ---- --}}
      @php
        $totalCols = 4 + 8 + 1;   // จบที่คอลัมน์ "ระดับ" — ยังไม่มีคอลัมน์คะแนนในขั้นนี้
        $colLetter = function ($n) { $s = ''; while ($n > 0) { $n--; $s = chr(65 + ($n % 26)).$s; $n = intdiv($n, 26); } return $s; };
      @endphp
      <div class="xls-head xls-preview-head" data-score-preview>
        <h3><span data-i18n="assessment.scores.h12">1.2 ข้อมูลพนักงาน</span></h3>
        <div class="xls-actions">
          <div class="xls-file-actions">
            <button type="button" class="asm-btn sm xls-clear-hier" data-clear-hier="{{ route('assessment.scores.hierarchy.clear') }}" data-i18n="assessment.clearEvaluators">ล้างผู้ประเมิน</button>
            <a href="{{ route('assessment.scores.download') }}" class="asm-btn sm primary xls-download"><span data-i18n="assessment.downloadExcel">⬇ ดาวน์โหลด Excel</span></a>
            <form action="{{ route('assessment.scores.import') }}" method="POST" enctype="multipart/form-data" class="xls-import-form">
              @csrf
              <button type="submit" class="asm-btn sm primary" data-i18n="assessment.import">⬆ นำเข้า</button>
              <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
            </form>
          </div>
        </div>
      </div>
      <p class="hint" data-i18n="assessment.scores.h12Hint">แก้ผู้ประเมิน (ลำดับชั้น 1–4) ในช่องได้เลย · หรือดาวน์โหลดไปแก้แล้วนำเข้ากลับ</p>

      <div class="ss-wrap" id="scorePreviewTableWrap" data-xls-wrap tabindex="-1">
        <table class="ss" data-filter-table="score">
          {{-- ตัวอักษรคอลัมน์ A,B,C... --}}
          <tr class="ss-frame">
            <th class="ss-corner"></th>
            <th class="ss-c1">{{ $colLetter(1) }}</th>
            @for ($i = 2; $i <= $totalCols; $i++)
              @php
                $previewInfoZone = $i === 13
                  ? 'level'
                  : ($i >= 5 && $i <= 12 ? 'hier-'.(intdiv($i - 5, 2) + 1) : null);
                $previewInfoStart = $previewInfoZone && ($i === 13 || (($i - 5) % 2 === 0));
              @endphp
              <th class="{{ $previewInfoStart ? 'info-zone-start' : '' }}" @if ($previewInfoZone) data-info-zone="{{ $previewInfoZone }}" @endif>{{ $colLetter($i) }}</th>
            @endfor
          </tr>
          {{-- แถว 1: หัวกลุ่ม --}}
          <tr class="ss-h1">
            <th class="ss-rownum"></th>
            <th class="ss-c1"></th><th></th><th></th><th></th>
            <th class="hier info-zone-start" colspan="2" data-info-zone="hier-1"><span data-i18n="assessment.hierLevel">ลำดับชั้น</span> 1</th>
            <th class="hier info-zone-start" colspan="2" data-info-zone="hier-2"><span data-i18n="assessment.hierLevel">ลำดับชั้น</span> 2</th>
            <th class="hier info-zone-start" colspan="2" data-info-zone="hier-3"><span data-i18n="assessment.hierLevel">ลำดับชั้น</span> 3</th>
            <th class="hier info-zone-start" colspan="2" data-info-zone="hier-4"><span data-i18n="assessment.hierLevel">ลำดับชั้น</span> 4</th>
            <th class="info-zone-start" data-info-zone="level"></th>
          </tr>
          {{-- แถว 2: หัวคอลัมน์ --}}
          <tr class="ss-h2">
            <th class="ss-rownum">#</th>
            <th class="ss-c1" data-i18n="assessment.codeShort">รหัส</th><th data-i18n="assessment.fullName">ชื่อ-สกุล</th><th data-i18n="assessment.evaluate.position">ตำแหน่ง</th><th data-i18n="assessment.evaluate.department">แผนก</th>
            <th class="hier info-zone-start" data-info-zone="hier-1">ID Sup.</th><th class="hier" data-info-zone="hier-1">Supervisor</th>
            <th class="hier info-zone-start" data-info-zone="hier-2">ID Div.</th><th class="hier" data-info-zone="hier-2">Division MGR</th>
            <th class="hier info-zone-start" data-info-zone="hier-3">ID Dept.</th><th class="hier" data-info-zone="hier-3">Dept.MGR</th>
            <th class="hier info-zone-start" data-info-zone="hier-4">ID Plant</th><th class="hier" data-info-zone="hier-4">Plant MGR</th>
            <th class="info-zone-start" data-info-zone="level" data-i18n="assessment.levelShort">ระดับ</th>
          </tr>
          <tr class="ss-filter" data-filter-row="score">
            <th class="ss-rownum" data-i18n="assessment.filterWord">กรอง</th>
            <th class="ss-c1">@include('assessment.partials.fbtn', ['name' => 'score', 'col' => 1])</th>
            @for ($i = 2; $i <= $totalCols; $i++)
              @php
                $previewInfoZone = $i === 13
                  ? 'level'
                  : ($i >= 5 && $i <= 12 ? 'hier-'.(intdiv($i - 5, 2) + 1) : null);
                $previewInfoStart = $previewInfoZone && ($i === 13 || (($i - 5) % 2 === 0));
              @endphp
              <th class="{{ $previewInfoStart ? 'info-zone-start' : '' }}" @if ($previewInfoZone) data-info-zone="{{ $previewInfoZone }}" @endif>@include('assessment.partials.fbtn', ['name' => 'score', 'col' => $i])</th>
            @endfor
          </tr>
          {{-- ข้อมูลพนักงานทุกคน --}}
          @forelse ($employees as $i => $emp)
            @php
              $lv = $levelMap[$emp->job_code] ?? null;
              $isLevelZero = $lv !== null && (int) $lv === 0;
              $h = $hierMap[$emp->employee_code] ?? null;
            @endphp
            <tr data-filter-data-row="score" data-row-job="{{ $emp->job_code }}">
              <th class="ss-rownum">{{ number_format($rowStart + $i + 1) }}</th>
              <td class="ss-c1">{{ $emp->employee_code }}</td>
              <td><span data-loc-th="{{ $emp->fullNameTh() }}" data-loc-en="{{ $emp->fullNameEn() ?: $emp->fullNameTh() }}">{{ $emp->fullNameTh() }}</span></td>
              <td><span data-loc-th="{{ $emp->job_th ?: $emp->job_en }}" data-loc-en="{{ $emp->job_en ?: $emp->job_th }}">{{ $emp->job_th ?: $emp->job_en }}</span></td>
              <td><span data-loc-th="{{ $emp->deptThClean() ?: $emp->dept_en }}" data-loc-en="{{ $emp->dept_en ?: $emp->deptThClean() }}">{{ $emp->deptThClean() ?: $emp->dept_en }}</span></td>
              @foreach ($hf as $f)
                @php
                  $hierInfoZone = 'hier-'.(intdiv($loop->index, 2) + 1);
                  $hierInfoStart = $loop->index % 2 === 0;
                @endphp
                @if ($isLevelZero)
                  <td class="hcell{{ $hierInfoStart ? ' info-zone-start' : '' }}" data-info-zone="{{ $hierInfoZone }}"><input type="text" class="asm-excluded-input" value="-" disabled tabindex="-1" aria-label="ไม่ถูกประเมิน" data-i18n-aria="assessment.common.notAssessed"></td>
                @else
                  <td class="hcell{{ $hierInfoStart ? ' info-zone-start' : '' }}" data-info-zone="{{ $hierInfoZone }}"><input type="text" value="{{ $h->$f ?? '' }}" data-emp="{{ $emp->employee_code }}" data-field="{{ $f }}"></td>
                @endif
              @endforeach
              <td class="lvl info-zone-start" data-info-zone="level" data-level-cell>{{ $lv ?? '' }}</td>
            </tr>
          @empty
            <tr><th class="ss-rownum"></th><td colspan="{{ $totalCols }}" class="asm-empty" data-i18n="assessment.noEmployee">ไม่พบพนักงาน</td></tr>
          @endforelse
        </table>
      </div>
      <div class="ss-count" data-filter-count="score">
        <span data-i18n="assessment.showingThisPage">แสดงหน้านี้</span> <strong>{{ number_format($shownCount) }}</strong> · <span data-i18n="assessment.totalWord">ทั้งหมด</span>@if ($activeFilterCount > 0) (<span data-i18n="assessment.common.afterFilter">หลังกรอง</span>)@endif {{ number_format($totalCount) }} <span data-i18n="assessment.peopleUnit">คน</span>
      </div>
      @includeWhen(method_exists($employees, 'hasPages') && $employees->hasPages(), 'assessment.partials.scorespager', ['openQuery' => [], 'pagerTarget' => 'score'])

    </div>
  </details>

  {{-- ============ หัวข้อ 2. จัดการคอลัมน์ (โครงคอลัมน์เก็บคะแนน KPI) ============ --}}
  <details class="step-card" data-column-step @if (session()->has('assessment_columns_imported')) open @endif>
    <summary class="step-summary">
      <span class="step-no" aria-hidden="true">2</span>
      <b data-i18n="assessment.scores.step2">จัดการคอลัมน์</b>
      <span class="step-sub" data-i18n="assessment.scores.step2Sub">คอลัมน์เก็บคะแนน KPI — สร้างเอง หรือ import ไฟล์คอลัมน์</span>
      <svg class="step-caret" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="step-body">
      <h3><span data-i18n="assessment.scores.h21">2.1 สร้าง / นำเข้าคอลัมน์</span></h3>
      <p class="hint" data-i18n="assessment.scores.h21Hint">สร้างเอง หรือนำเข้าไฟล์ (แถว 1 = หัวข้อหลัก, แถว 2 = คอลัมน์รอง)</p>

      <div class="calc-actions" style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;margin:.4rem 0 .9rem">
        <form action="{{ route('assessment.scores.import.columns') }}" method="POST" enctype="multipart/form-data" class="xls-import-form">
          @csrf
          <button type="submit" class="asm-btn sm primary" data-i18n="assessment.scores.importColumns">⬆ นำเข้าไฟล์คอลัมน์</button>
          <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
        </form>
      </div>

      <div class="colcard-grid" data-col-grid>
        @foreach ($columnBoxes as $cb)
          <div class="colcard" data-col-card="{{ $cb->id }}">
            <div class="colcard-head">
              <input type="text" class="colcard-name" value="{{ $cb->name }}" data-box-name="{{ $cb->id }}" title="แก้ชื่อหัวข้อหลัก" data-i18n-title="assessment.scores.editMainTitle">
              <button type="button" class="colcard-del" data-box-del="{{ $cb->id }}" data-del-kind="main" title="ลบหัวข้อหลักและคอลัมน์รองภายใน" data-i18n-title="assessment.scores.deleteMainTitle">✕</button>
            </div>
            <div class="colcard-subs" data-col-subs="{{ $cb->id }}">
              @foreach ($cb->children as $kid)
                <div class="colsub" data-col-card="{{ $kid->id }}">
                  <input type="text" class="colsub-name" value="{{ $kid->name }}" data-box-name="{{ $kid->id }}" title="แก้ชื่อคอลัมน์รอง" data-i18n-title="assessment.scores.editSubTitle">
                  <button type="button" class="colcard-del" data-box-del="{{ $kid->id }}" data-del-kind="sub" title="ลบคอลัมน์รอง" data-i18n-title="assessment.scores.deleteSubTitle">✕</button>
                </div>
              @endforeach
            </div>
            <div class="colsub-add">
              <input type="text" placeholder="+ เพิ่มคอลัมน์รอง..." data-i18n-placeholder="assessment.scores.addSubPlaceholder" data-add-sub="{{ $cb->id }}">
            </div>
          </div>
        @endforeach

        {{-- การ์ดเพิ่มหัวข้อหลัก --}}
        <div class="colcard colcard-new">
          <div class="colcard-new-inner">
            <input type="text" placeholder="ชื่อหัวข้อหลัก เช่น Attendance" data-i18n-placeholder="assessment.scores.mainColPh" data-add-main>
            <button type="button" class="asm-btn sm primary" data-add-main-btn data-i18n="assessment.scores.addMainCol">+ เพิ่มหัวข้อหลัก</button>
          </div>
        </div>
      </div>

      <h3><span data-i18n="assessment.scores.h22">2.2 ตารางตรวจคอลัมน์ (มุมมอง Excel)</span></h3>
      <p class="hint"><span data-i18n="assessment.scores.h22Hint">อัปเดตตามการ์ดด้านบนทันที</span> · <span data-col-count></span></p>
      <div data-col-preview></div>
    </div>
  </details>

  {{-- ============ หัวข้อ 3. สัดส่วนและการคำนวณ ============ --}}
  <details class="step-card" data-prop-step>
    <summary class="step-summary">
      <span class="step-no" aria-hidden="true">3</span>
      <b data-i18n="assessment.scores.step3">สัดส่วนและการคำนวณ</b>
      <span class="step-sub" data-i18n="assessment.scores.step3Sub">กำหนดสัดส่วนต่อระดับ 1–5 — สัดส่วนรวม 100 ต่อระดับ · เพิ่มเติม (+/-)</span>
      <svg class="step-caret" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="step-body">
      <h3><span data-i18n="assessment.scores.h31">3.1 กำหนดสัดส่วนของระดับ</span></h3>
      <p class="hint" data-i18n="assessment.scores.h31Hint">สัดส่วนรวม 100 ต่อระดับ · เพิ่มเติม = +/- แยก · ไม่คำนวณ = ไม่นำมาคิด</p>
      <div class="prop-summary" data-prop-summary></div>
      <div class="colcard-grid" data-prop-grid></div>

      <h3><span data-i18n="assessment.scores.h32">3.2 การคำนวณคอลัมน์รอง</span></h3>
      <p class="hint" data-i18n="assessment.scores.h32Hint">คะแนนเต็ม = คิดตามสัดส่วน · Attendance = หักคะแนน/จำกัดเกรด · Input = ผู้ประเมินกรอกในระบบ · คะแนนเพิ่มเติม = +/- ตรง ๆ</p>
      <div data-subcalc></div>
    </div>
  </details>

  {{-- ============ หัวข้อ 4. ผลลัพธ์และการนำเข้าคะแนน ============ --}}
  @php
    // แสดงค่าช่องคะแนน — slot ที่ผู้ใช้ใส่ N/A (na_mask) แสดงคำว่า N/A ค้างไว้ (คำนวณ = เหมือนว่าง)
    $naBit = fn ($pv, int $part) => ((int) ($pv['na'] ?? 0)) & (1 << ($part - 1));
    $slotDisp = fn ($pv, int $part) => $pv && $naBit($pv, $part) ? 'N/A' : $fmt($slotVal($pv, $part));
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
  @endphp
  <details class="step-card" data-result-step @if ($importedOpen) open @endif>
    <summary class="step-summary">
      <span class="step-no" aria-hidden="true">4</span>
      <b data-i18n="assessment.scores.step4">การนำเข้าคะแนน</b>
      <span class="step-sub" data-i18n="assessment.scores.step4Sub">นำเข้า / แก้คะแนนรายช่อง</span>
      <svg class="step-caret" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="step-body">
      <div class="xls-head xls-score-head">
        <h3><span data-i18n="assessment.scores.h41">4.1 นำเข้า / แก้ไขคะแนน</span></h3>
        <div class="xls-actions">
          <div class="xls-file-actions">
            <a href="{{ route('assessment.scores.score-template') }}" class="asm-btn sm primary xls-download"><span data-i18n="assessment.downloadExcel">⬇ ดาวน์โหลด Excel</span></a>
            <form action="{{ route('assessment.scores.import') }}" method="POST" enctype="multipart/form-data" class="xls-import-form">
              @csrf
              <button type="submit" class="asm-btn sm primary" data-i18n="assessment.scores.importScores">⬆ นำเข้าคะแนน</button>
              <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
            </form>
          </div>
        </div>
      </div>
      <p class="hint" data-i18n="assessment.scores.h41Hint">แก้คะแนนในช่องได้เมื่อผู้ประเมินครบ 4 ลำดับ · หลายผู้ประเมินคั่นด้วย , เช่น 8.5,7.5 · พิมพ์ N/A = ไม่นำช่องนั้นมาคำนวณ · ไฟล์นำเข้า: ช่องว่าง = คงค่าเดิม</p>
      <div class="calc-actions">
        <span class="score-legend" aria-label="คำอธิบายสีตารางคะแนน">
          <span class="score-legend-item"><i class="score-legend-swatch" aria-hidden="true"></i><span data-i18n="assessment.legendPastel">พาสเทล = กลุ่มข้อมูล</span></span>
          <span class="score-legend-item"><i class="score-legend-swatch is-unavailable" aria-hidden="true"></i><span data-i18n="assessment.legendDark">เข้มขึ้น = ระดับนี้ไม่คิด</span></span>
        </span>
        @if ($leaves4->count())
          @if ($activeFilterCount > 0)
            <button type="button" class="asm-btn sm" data-filter-clear data-clear-section="s4" data-i18n="assessment.clearFilter">ล้างตัวกรอง</button>
          @endif
          <span class="ss-count" data-filter-count="s4" style="margin-left:auto"><span data-i18n="assessment.showingThisPage">แสดงหน้านี้</span> <strong>{{ number_format($shownCount) }}</strong> · <span data-i18n="assessment.totalWord">ทั้งหมด</span>{{ $activeFilterCount > 0 ? '(หลังกรอง)' : '' }} {{ number_format($totalCount) }} <span data-i18n="assessment.peopleUnit">คน</span></span>
        @endif
      </div>

      @if ($leaves4->count())
        <div class="imp-wrap" id="scoreImportTableWrap" style="max-height:70vh">
          <table class="imp-tbl" data-filter-table="s4">
            <tr class="imp-h1">
              <th class="no">No.</th><th data-i18n="assessment.codeShort">รหัส</th><th data-i18n="assessment.fullName">ชื่อ-สกุล</th><th data-i18n="assessment.evaluate.position">ตำแหน่ง</th><th data-i18n="assessment.evaluate.department">แผนก</th><th class="info-zone-start" data-info-zone="level" data-i18n="assessment.common.level">ระดับ</th>
              @foreach ($zones4 as $z)
                <th colspan="{{ $z['span'] }}" class="zone score-zone-start" data-score-zone="{{ ($loop->index % 7) + 1 }}">{{ $z['name'] }}</th>
              @endforeach
            </tr>
            <tr class="imp-h2">
              <th class="no"></th><th></th><th></th><th></th><th></th><th class="info-zone-start" data-info-zone="level"></th>
              @foreach ($leaves4 as $leaf)
                @php $zoneMeta = $scoreZoneMeta[(int) $leaf->id] ?? ['tone' => 1, 'start' => false]; @endphp
                <th class="{{ $zoneMeta['start'] ? 'score-zone-start' : '' }}" data-score-zone="{{ $zoneMeta['tone'] }}">{{ $leaf->name }}</th>
              @endforeach
            </tr>
            {{-- col = ดัชนีคอลัมน์ผลลัพธ์ (รหัส=1, ชื่อ=2, ตำแหน่ง=3, แผนก=4, ระดับ=13, คะแนน=14+) → กรองข้ามทุกหน้าเหมือนหน้า ผลลัพธ์ --}}
            <tr class="imp-filter" data-filter-row="s4">
              <th class="no"></th>
              <th>@include('assessment.partials.fbtn', ['name' => 's4', 'col' => 1])</th>
              <th>@include('assessment.partials.fbtn', ['name' => 's4', 'col' => 2])</th>
              <th>@include('assessment.partials.fbtn', ['name' => 's4', 'col' => 3])</th>
              <th>@include('assessment.partials.fbtn', ['name' => 's4', 'col' => 4])</th>
              <th class="info-zone-start" data-info-zone="level">@include('assessment.partials.fbtn', ['name' => 's4', 'col' => 13])</th>
              @foreach ($leaves4 as $leaf)
                @php $zoneMeta = $scoreZoneMeta[(int) $leaf->id] ?? ['tone' => 1, 'start' => false]; @endphp
                <th class="{{ $zoneMeta['start'] ? 'score-zone-start' : '' }}" data-score-zone="{{ $zoneMeta['tone'] }}">@include('assessment.partials.fbtn', ['name' => 's4', 'col' => 14 + $loop->index])</th>
              @endforeach
            </tr>
            @php $seen4 = []; $no4 = $rowStart; @endphp
            @foreach ($employees as $emp)
              @php
                if (isset($seen4[$emp->employee_code])) continue;
                $seen4[$emp->employee_code] = true;
                $no4++;
                $lv4 = $levelMap[$emp->job_code] ?? null;
                $isLevelZero4 = $lv4 !== null && (int) $lv4 === 0;
                $hasCompleteHierarchy4 = $hasCompleteHierarchy($emp->employee_code);
                $h4 = $hierMap[$emp->employee_code] ?? null;
              @endphp
              <tr data-filter-data-row="s4" data-level-zero="{{ $isLevelZero4 ? '1' : '0' }}" data-row-job="{{ $emp->job_code }}">
                <td class="no">{{ number_format($no4) }}</td>
                <td class="code">{{ $emp->employee_code }}</td>
                <td><span data-loc-th="{{ $emp->fullNameTh() ?: $emp->name_en }}" data-loc-en="{{ $emp->fullNameEn() ?: $emp->fullNameTh() }}">{{ $emp->fullNameTh() ?: $emp->name_en }}</span></td>
                <td><span data-loc-th="{{ $emp->job_th ?: $emp->job_en }}" data-loc-en="{{ $emp->job_en ?: $emp->job_th }}">{{ $emp->job_th ?: $emp->job_en }}</span></td>
                <td><span data-loc-th="{{ $emp->deptThClean() ?: $emp->dept_en }}" data-loc-en="{{ $emp->dept_en ?: $emp->deptThClean() }}">{{ $emp->deptThClean() ?: $emp->dept_en }}</span></td>
                <td class="no info-zone-start" data-info-zone="level" data-level-cell>{{ $lv4 ?? '' }}</td>
                @foreach ($leaves4 as $leaf)
                  @php
                    $zoneMeta = $scoreZoneMeta[(int) $leaf->id] ?? ['tone' => 1, 'start' => false, 'name' => $leaf->name];
                    $zoneStartClass = $zoneMeta['start'] ? ' score-zone-start' : '';
                    $isNotCalculated4 = $isLeafNotCalculated($lv4, $leaf);
                  @endphp
                  @if ($isLevelZero4)
                    <td class="sc4{{ $zoneStartClass }}" data-score-zone="{{ $zoneMeta['tone'] }}"><input type="text" class="asm-excluded-input" value="-" disabled tabindex="-1" aria-label="ไม่ถูกประเมิน"></td>
                  @elseif ($isNotCalculated4)
                    <td class="sc4 score-unavailable{{ $zoneStartClass }}" data-score-zone="{{ $zoneMeta['tone'] }}" aria-label="{{ $zoneMeta['name'] }} ไม่คำนวณสำหรับระดับ {{ $lv4 }}" title="ไม่คำนวณสำหรับระดับ {{ $lv4 }}"></td>
                  @elseif (! $hasCompleteHierarchy4)
                    <td class="sc4{{ $zoneStartClass }}" data-score-zone="{{ $zoneMeta['tone'] }}"><input type="text" class="asm-excluded-input" value="-" disabled tabindex="-1" aria-label="ผู้ประเมินยังไม่ครบ"></td>
                  @elseif ($leaf->type === 'input')
                    @php
                      $pv5 = $scoreMap4[$emp->employee_code][$leaf->id] ?? null;
                      $lv5s = array_values(array_filter(explode(',', $leaf->input_levels ?: '1,2')));
                      $groups4 = $inputGroups($h4, $lv5s);
                    @endphp
                    <td class="sc4 sc4-dual{{ $zoneStartClass }}" data-score-zone="{{ $zoneMeta['tone'] }}" title="ช่องตามผู้ประเมิน (รวมเป็นช่องเดียวเมื่อเป็นคนเดียวกัน)">
                      @foreach ($groups4 as $g)
                        <input type="text" placeholder="{{ $scoreHint($leaf, $lv4, true) }}" title="ผู้ประเมิน{{ $g['name'] !== '' ? ': '.$g['name'] : ' ลำดับ '.implode(',', $g['levels']) }}" value="{{ $slotDisp($pv5, $g['parts'][0]) }}" data-emp="{{ $emp->employee_code }}" data-box="{{ $leaf->id }}" data-parts="{{ implode(',', $g['parts']) }}">
                      @endforeach
                    </td>
                  @else
                    <td class="sc4{{ $zoneStartClass }}" data-score-zone="{{ $zoneMeta['tone'] }}"><input type="text" placeholder="{{ $scoreHint($leaf, $lv4) }}" value="{{ $fmt4($scoreMap4[$emp->employee_code][$leaf->id] ?? null) }}" data-emp="{{ $emp->employee_code }}" data-box="{{ $leaf->id }}"></td>
                  @endif
                @endforeach
              </tr>
            @endforeach
          </table>
        </div>
        @includeWhen(method_exists($employees, 'hasPages') && $employees->hasPages(), 'assessment.partials.scorespager', ['openQuery' => [], 'pagerTarget' => 's4'])
      @else
        <p class="hint" data-i18n="assessment.scores.noColumns">ยังไม่มีคอลัมน์คะแนน — สร้าง/นำเข้าที่หัวข้อ 2 ก่อน</p>
      @endif
    </div>
  </details>
  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const H = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' };
      const req = (url, body, method = 'POST') => fetch(url, { method, headers: H, body: JSON.stringify(body || {}) }).then(r => r.json());

      try {
        localStorage.removeItem('assessment.scores.details.v1');
      } catch (e) {}

      const managedDetails = {
        score: document.querySelector('[data-score-preview-step]'),
        columns: document.querySelector('[data-column-step]'),
        props: document.querySelector('[data-prop-step]'),
        result: document.querySelector('[data-result-step]'),
      };

      const pagerReturnKey = 'assessment.scores.pager-return.v1';
      const pagerTargets = {
        score: {
          detailKey: 'score',
          anchor: '#scorePreviewTableWrap',
        },
        s4: {
          detailKey: 'result',
          anchor: '#scoreImportTableWrap',
        },
      };

      function rememberPagerPosition(target) {
        const cfg = pagerTargets[target];
        if (!cfg) return;

        const wrap = document.querySelector(cfg.anchor);
        const state = {
          target,
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
        if (!state || !pagerTargets[state.target]) return;

        try {
          localStorage.removeItem(pagerReturnKey);
        } catch (e) {}
        if (Date.now() - Number(state.at || 0) > 120000) return;

        const cfg = pagerTargets[state.target];
        const detail = managedDetails[cfg.detailKey];
        if (detail) detail.open = true;

        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            const wrap = document.querySelector(cfg.anchor);
            if (!wrap) return;

            wrap.scrollIntoView({ block: 'start', behavior: 'auto' });
            wrap.scrollLeft = Number(state.left || 0);
            wrap.scrollTop = Number(state.top || 0);
          });
        });
      }

      document.addEventListener('click', function (e) {
        const link = e.target.closest('.asm-page[data-pager-target] a.asm-page-link[href]');
        if (!link || link.classList.contains('is-disabled')) return;
        rememberPagerPosition(link.closest('.asm-page').dataset.pagerTarget);
      });

      document.addEventListener('change', function (e) {
        const select = e.target.closest('.asm-page[data-pager-target] select[data-page-select]');
        if (!select) return;

        rememberPagerPosition(select.closest('.asm-page').dataset.pagerTarget);
        window.location.href = select.value;
      });

      restorePagerPosition();

      // ---- level assign ----
      // ระดับสูงสุดปัจจุบัน (แชร์กับ 3.1) — กด + เพิ่มต่อจากเลขล่าสุด ; ระดับใหม่คงอยู่เมื่อจัดตำแหน่ง/ตั้งสัดส่วนแล้ว
      let asmMaxLevel = Number(@json($maxLevel));
      let rerenderProps = () => {};     // ผูกกับ renderPropCards ของ 3.1 ด้านล่าง
      let shiftPropLevels = () => {};   // เลื่อน config สัดส่วนฝั่ง client หลังลบระดับ (server เลื่อนใน DB แล้ว)
      const levels = document.querySelector('[data-levels]');

      // อัปเดตคอลัมน์ "ระดับ" ในตาราง 1.2 + 4.1 ของตำแหน่งหนึ่ง (ไม่ reload)
      function setRowLevel(code, text) {
        document.querySelectorAll(`[data-row-job="${code}"]`).forEach(tr => {
          tr.querySelectorAll('[data-level-cell]').forEach(td => { td.textContent = text; });
        });
      }

      // ปุ่ม ✕ ลบระดับ: ใส่ทุกการ์ดยกเว้นระดับ 0 (default) — ลบการ์ดกลางได้ เลขจะเรียงใหม่อัตโนมัติ
      function syncLevelDelete() {
        levels.querySelectorAll('[data-del-level]').forEach(btn => btn.remove());
        levels.querySelectorAll('.lv-card[data-level]').forEach(c => {
          const lv = Number(c.dataset.level);
          if (!Number.isInteger(lv) || lv < 1) return;   // "ยังไม่จัดระดับ" + ระดับ 0 ไม่มี ✕
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'lv-del';
          btn.dataset.delLevel = String(lv);
          btn.title = t('assessment.scores.deleteLevelTitle', 'ลบระดับ {level}', { level: lv });
          btn.textContent = '✕';
          c.prepend(btn);
        });
      }

      // ปุ่ม + เพิ่มระดับ: เพิ่มการ์ดใน 1.1 + option ในทุก dropdown + การ์ดสัดส่วนใน 3.1 ทันที
      const addLevelBtn = document.querySelector('[data-add-level]');
      if (addLevelBtn) {
        addLevelBtn.addEventListener('click', function () {
          asmMaxLevel++;   // เริ่มที่ระดับ 1 (ระดับ 0 เป็น default มีอยู่แล้ว)
          const card = document.createElement('div');
          card.className = 'lv-card';
          card.dataset.level = String(asmMaxLevel);
          card.innerHTML = `<div class="lv-title"><span>${t('assessment.scores.levelTitle', 'ระดับ {level}', { level: asmMaxLevel })}</span><span class="lv-count">0</span></div><div class="lv-chips" data-lv-chips></div>`;
          addLevelBtn.parentNode.insertBefore(card, addLevelBtn);
          document.querySelectorAll('[data-set-level]').forEach(sel => {
            const opt = document.createElement('option');
            opt.value = String(asmMaxLevel);
            opt.textContent = String(asmMaxLevel);
            sel.appendChild(opt);
          });
          rerenderProps();   // 3.1 ขึ้นการ์ด "ระดับ N" ใหม่ให้ตั้งสัดส่วนได้เลย
          syncLevelDelete();
          card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        });
      }

      // กด ✕ → ยืนยัน → ลบสัดส่วน 3.1 + ย้ายตำแหน่งกลับ "ยังไม่จัดระดับ" + เลื่อนเลขการ์ดที่สูงกว่าลงมาแทน — ไม่ reload
      levels.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-del-level]');
        if (!btn) return;
        const level = Number(btn.dataset.delLevel);
        const card = levels.querySelector(`.lv-card[data-level="${level}"]`);
        const chipCount = card ? card.querySelectorAll('.lv-chip').length : 0;
        const parts = [
          t('assessment.scores.deleteLevelConfirm', 'ลบระดับ {level}?', { level }),
          t('assessment.scores.deleteLevelPropWarning', 'สัดส่วนของระดับนี้ในหัวข้อ 3.1 จะถูกลบด้วย'),
        ];
        if (chipCount) parts.push(t('assessment.scores.deleteLevelPositionsWarning', 'ตำแหน่งที่จัดไว้ {count} รายการจะย้ายกลับ “ยังไม่จัดระดับ”', { count: chipCount }));
        if (level < asmMaxLevel) parts.push(t('assessment.scores.deleteLevelShiftWarning', 'ระดับที่สูงกว่าจะเลื่อนลงมาแทน (เช่น ระดับ {from} → {to})', { from: level + 1, to: level }));
        if (!confirm(parts.join('\n'))) return;
        req(`{{ route('assessment.scores.level.delete') }}`, { level }, 'DELETE').then(res => {
          if (!res.ok) { alert(res.message || t('assessment.scores.deleteLevelError', 'ลบระดับไม่สำเร็จ')); return; }
          // 1) ย้าย chips ของการ์ดที่ลบกลับ "ยังไม่จัดระดับ" + เคลียร์ค่าระดับในตาราง 1.2/4.1
          const unChips = levels.querySelector('.lv-card[data-level="un"] [data-lv-chips]');
          if (card) {
            card.querySelectorAll('.lv-chip').forEach(chip => {
              const sel = chip.querySelector('[data-set-level]');
              if (sel) sel.value = '';
              setRowLevel(chip.dataset.code, '');
              unChips.appendChild(chip);
            });
            card.remove();
          }
          // 2) เลื่อนการ์ดที่สูงกว่าลงมาแทน (renumber) — ชื่อการ์ด, dropdown ของ chips, คอลัมน์ระดับในตาราง
          for (let lv = level + 1; lv <= asmMaxLevel; lv++) {
            const c = levels.querySelector(`.lv-card[data-level="${lv}"]`);
            if (!c) continue;
            const newLv = lv - 1;
            c.dataset.level = String(newLv);
            const title = c.querySelector('.lv-title span');
            if (title) title.textContent = t('assessment.scores.levelTitle', 'ระดับ {level}', { level: newLv });
            c.querySelectorAll('.lv-chip').forEach(chip => {
              const sel = chip.querySelector('[data-set-level]');
              if (sel) sel.value = String(newLv);
              setRowLevel(chip.dataset.code, String(newLv));
            });
          }
          // 3) ตัด option เลขบนสุดเดิมออกจากทุก dropdown (ค่าของ chips ถูกเลื่อนลงแล้ว)
          document.querySelectorAll('[data-set-level]').forEach(sel => {
            sel.querySelector(`option[value="${asmMaxLevel}"]`)?.remove();
          });
          // 4) เลื่อน config สัดส่วนฝั่ง client ให้ตรง DB แล้ววาดการ์ด 3.1 + ปุ่ม ✕ ใหม่
          shiftPropLevels(level, asmMaxLevel);
          asmMaxLevel--;
          levels.querySelectorAll('.lv-card').forEach(c => {
            const cnt = c.querySelector('.lv-count');
            if (cnt) cnt.textContent = c.querySelectorAll('.lv-chip').length;
          });
          rerenderProps();
          syncLevelDelete();
        });
      });

      syncLevelDelete();

      levels.addEventListener('change', function (e) {
        if (!e.target.matches('[data-set-level]')) return;
        const chip = e.target.closest('.lv-chip');
        const lv = e.target.value;
        req(`{{ route('assessment.scores.level') }}`, { job_code: chip.dataset.code, level: lv === '' ? null : lv }, 'PUT').then(res => {
          if (!res.ok) { alert(res.message || t('assessment.scores.saveLevelError', 'บันทึกระดับไม่สำเร็จ')); return; }
          // อัปเดตคอลัมน์ "ระดับ" ในตาราง 1.2 + 4.1 ทันทีก่อน (กัน error จุดอื่นตัดตอน)
          setRowLevel(chip.dataset.code, lv === '' ? '' : lv);
          const key = lv === '' ? 'un' : lv;
          const dest = levels.querySelector(`.lv-card[data-level="${key}"] [data-lv-chips]`);
          if (dest) dest.appendChild(chip);
          // ปุ่ม "+ เพิ่มระดับ" มี class .lv-card แต่ไม่มี .lv-count — ต้อง guard (เดิมพังตรงนี้ ทำให้ตารางไม่อัปเดต)
          levels.querySelectorAll('.lv-card').forEach(card => {
            const cnt = card.querySelector('.lv-count');
            if (cnt) cnt.textContent = card.querySelectorAll('.lv-chip').length;
          });
        });
      });

      // ---- ตัวกรองรายคอลัมน์ (server-side) — กรองข้ามทุกหน้าเหมือนหน้า "ผลลัพธ์" ----
      // คีย์ตัวกรอง = ดัชนีคอลัมน์ผลลัพธ์ (f[col][]) ใช้ร่วมทั้งตาราง 1.2 (score) และ 4.1 (s4)
      const filterEndpoint = @json(route('assessment.scores.filter.options'));
      const filterNone = '__asm_none__';
      const escF = s => String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
      const filterKey = col => `f[${col}][]`;

      // no-op: ถูกเรียกหลังแก้ค่า inline — ตอนนี้กรองที่ server แล้วไม่ต้องกรอง DOM สด
      function refreshSpreadsheetFilters() {}

      // ไม่เติม query เปิดหัวข้อแล้ว ให้ JS localStorage เป็นตัวจำสถานะเปิด/ย่อแทน
      function sectionParams(name) {
        return {};
      }

      function clearFilterParams(p, col) {
        const key = filterKey(col);
        Array.from(p.keys()).forEach(k => { if (k === key || k === `f[${col}]`) p.delete(k); });
      }

      function hasFilter(col) {
        const p = new URLSearchParams(window.location.search);
        return p.has(filterKey(col)) || p.has(`f[${col}]`);
      }

      function syncFilterButtons() {
        document.querySelectorAll('[data-filter-btn]').forEach(btn => {
          btn.classList.toggle('is-active', hasFilter(btn.dataset.col));
        });
      }

      function gotoWith(p, name) {
        rememberPagerPosition(name);
        p.delete('page');
        p.delete('open');
        p.delete('preview');
        const sp = sectionParams(name);
        Object.keys(sp).forEach(k => p.set(k, sp[k]));
        const qs = p.toString();
        window.location.href = window.location.pathname + (qs ? '?' + qs : '');
      }

      function applyServerFilter(col, checked, totalOptions, name) {
        const p = new URLSearchParams(window.location.search);
        clearFilterParams(p, col);
        if (checked.size === 0) {
          p.append(filterKey(col), filterNone);           // ไม่เลือกเลย → ไม่แสดงแถวใด
        } else if (checked.size < totalOptions) {
          Array.from(checked).forEach(v => p.append(filterKey(col), v));
        }
        gotoWith(p, name);
      }

      function clearAllServerFilters(name) {
        const p = new URLSearchParams(window.location.search);
        Array.from(p.keys()).forEach(k => { if (/^f\[\d+\](\[\])?$/.test(k)) p.delete(k); });
        gotoWith(p, name);
      }

      // ---- popup ตัวกรองแบบ Excel (โหลดตัวเลือกจาก server) ----
      const fpop = document.createElement('div');
      fpop.className = 'ss-fpop';
      fpop.innerHTML =
        '<div class="ss-fpop-title" data-fpop-title></div>' +
        '<div class="ss-fpop-search"><input type="text" placeholder="' + escF(t('assessment.filter.searchPlaceholder', 'ค้นหา...')) + '"></div>' +
        '<div class="ss-fpop-list"></div>' +
        '<div class="ss-fpop-foot"><button type="button" class="clear">' + escF(t('assessment.filter.clear', 'ล้าง')) + '</button><button type="button" class="apply">' + escF(t('assessment.filter.apply', 'ตกลง')) + '</button></div>';
      document.body.appendChild(fpop);
      const fpopSearch = fpop.querySelector('.ss-fpop-search input');
      const fpopList = fpop.querySelector('.ss-fpop-list');
      const fpopTitle = fpop.querySelector('[data-fpop-title]');
      let filterCtx = null; // { col, name, options, checked:Set }

      function closeFilterPopup() { fpop.classList.remove('show'); filterCtx = null; }

      function renderServerFilterList(term) {
        if (!filterCtx) return;
        const q = (term || '').toLowerCase();
        const shown = filterCtx.options.filter(o => String(o.label).toLowerCase().includes(q));
        fpopList.innerHTML = '';
        const all = document.createElement('label');
        all.className = 'all';
        const allChecked = shown.length > 0 && shown.every(o => filterCtx.checked.has(o.value));
        all.innerHTML = '<input type="checkbox" data-all ' + (allChecked ? 'checked' : '') + '><span class="nm">' + escF(t('assessment.filter.selectAll', 'เลือกทั้งหมด')) + '</span>';
        fpopList.appendChild(all);
        if (!shown.length) {
          const none = document.createElement('div'); none.className = 'none'; none.textContent = t('assessment.filter.noResults', 'ไม่พบ');
          fpopList.appendChild(none);
          return;
        }
        shown.forEach(o => {
          const label = document.createElement('label');
          label.innerHTML = '<input type="checkbox" value="' + escF(o.value) + '" ' + (filterCtx.checked.has(o.value) ? 'checked' : '') +
            '><span class="nm">' + escF(o.label) + '</span><span class="cnt">' + o.count + '</span>';
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
        const name = btn.dataset.name;
        if (filterCtx && filterCtx.col === col && filterCtx.name === name) { closeFilterPopup(); return; }

        fpopTitle.textContent = t('loader.loading', 'กำลังโหลด...');
        fpopSearch.value = '';
        fpopList.innerHTML = '<div class="none">' + escF(t('assessment.filter.loading', 'กำลังโหลดตัวกรอง')) + '</div>';
        fpop.classList.add('show');
        positionFilterPop(btn);

        const p = new URLSearchParams(window.location.search);
        p.set('col', String(col));
        fetch(filterEndpoint + '?' + p.toString(), { headers: { 'Accept': 'application/json' } })
          .then(r => r.json())
          .then(d => {
            if (!d.ok) throw new Error(d.message || t('assessment.filter.loadFailed', 'โหลดตัวกรองไม่สำเร็จ'));
            const options = d.options || [];
            filterCtx = { col, name, options, checked: new Set(options.filter(o => o.selected).map(o => o.value)) };
            fpopTitle.textContent = t('assessment.filter.columnTitle', 'กรองคอลัมน์');
            renderServerFilterList('');
            positionFilterPop(btn);
            fpopSearch.focus();
          })
          .catch(err => {
            filterCtx = { col, name, options: [], checked: new Set() };
            fpopTitle.textContent = t('assessment.filter.columnTitle', 'กรองคอลัมน์');
            fpopList.innerHTML = '<div class="none">' + escF(err.message || t('assessment.filter.loadFailed', 'โหลดตัวกรองไม่สำเร็จ')) + '</div>';
          });
      }

      fpopSearch.addEventListener('input', () => renderServerFilterList(fpopSearch.value));
      fpopList.addEventListener('change', e => {
        if (!filterCtx) return;
        if (e.target.matches('[data-all]')) {
          const on = e.target.checked;
          fpopList.querySelectorAll('input[type=checkbox]:not([data-all])').forEach(cb => {
            cb.checked = on;
            if (on) filterCtx.checked.add(cb.value); else filterCtx.checked.delete(cb.value);
          });
          return;
        }
        if (!e.target.matches('input[type=checkbox]')) return;
        if (e.target.checked) filterCtx.checked.add(e.target.value); else filterCtx.checked.delete(e.target.value);
        const all = fpopList.querySelector('[data-all]');
        const boxes = Array.from(fpopList.querySelectorAll('input[type=checkbox]:not([data-all])'));
        if (all) all.checked = boxes.length > 0 && boxes.every(b => b.checked);
      });
      fpop.querySelector('.apply').addEventListener('click', () => {
        if (!filterCtx) return;
        applyServerFilter(filterCtx.col, filterCtx.checked, filterCtx.options.length, filterCtx.name);
      });
      fpop.querySelector('.clear').addEventListener('click', () => {
        if (!filterCtx) return;
        const p = new URLSearchParams(window.location.search);
        clearFilterParams(p, filterCtx.col);
        gotoWith(p, filterCtx.name);
      });
      document.querySelectorAll('[data-filter-btn]').forEach(btn => {
        btn.addEventListener('click', e => { e.stopPropagation(); openServerFilter(btn); });
      });
      document.querySelectorAll('[data-filter-clear]').forEach(btn => {
        btn.addEventListener('click', () => clearAllServerFilters(btn.dataset.clearSection || 'score'));
      });
      document.addEventListener('click', e => {
        if (fpop.classList.contains('show') && !fpop.contains(e.target) && !e.target.closest('[data-filter-btn]')) closeFilterPopup();
      });
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && fpop.classList.contains('show')) closeFilterPopup(); });
      document.querySelectorAll('.ss-wrap, .imp-wrap').forEach(w => w.addEventListener('scroll', closeFilterPopup));
      syncFilterButtons();

      // ---- inline score edit ----
      document.querySelectorAll('td.score input').forEach(inp => {
        inp.addEventListener('change', function () {
          req(`{{ route('assessment.scores.value') }}`, { employee_code: this.dataset.emp, box_id: this.dataset.box, value: this.value }, 'PUT')
            .then(res => { if (res.ok) { this.value = res.value === null ? '' : res.value; this.classList.add('saved'); refreshSpreadsheetFilters('score'); setTimeout(() => this.classList.remove('saved'), 1200); } });
        });
      });

      // ---- inline hierarchy edit (step 2) ----
      document.querySelectorAll('td.hcell input:not([disabled])').forEach(inp => {
        inp.addEventListener('change', function () {
          req(`{{ route('assessment.scores.hierarchy.save') }}`, { employee_code: this.dataset.emp, field: this.dataset.field, value: this.value }, 'PUT')
            .then(res => {
              if (!res.ok) return;
              // sync ช่องเดียวกันในหัวข้อ 1 และ 5
              const me = this;
              document.querySelectorAll(`td.hcell input[data-field="${me.dataset.field}"]:not([disabled])`).forEach(o => {
                if (o !== me && o.dataset.emp === me.dataset.emp) o.value = me.value;
              });
              this.classList.add('saved');
              refreshSpreadsheetFilters('score');
              setTimeout(() => this.classList.remove('saved'), 1200);
            });
        });
      });

      // ---- ล้างลำดับชั้น (คนประเมิน) ทั้งหมด ----
      function t(key, fallback, replacements = {}) {
        const lang = document.documentElement.getAttribute('data-lang') || 'th';
        const dict = (window.__portalCopy && window.__portalCopy[lang]) || {};
        let value = dict[key] || fallback;
        Object.entries(replacements).forEach(([name, replacement]) => {
          value = value.replaceAll(`{${name}}`, String(replacement));
        });

        return value;
      }

      const clearBtn = document.querySelector('[data-clear-hier]');
      if (clearBtn) {
        clearBtn.addEventListener('click', function () {
          if (!confirm(t('assessment.clearEvaluatorsConfirm', 'ล้างผู้ประเมินของพนักงานทุกคน?\nคะแนนและระดับจะไม่ถูกลบ เฉพาะช่องผู้ประเมินเท่านั้น'))) return;
          const label = this.textContent;
          this.disabled = true;
          this.textContent = t('assessment.clearingEvaluators', 'กำลังล้าง...');
          req(this.dataset.clearHier, {}, 'DELETE')
            .then(res => {
              if (res.ok) {
                document.querySelectorAll('td.hcell input:not([disabled])').forEach(inp => { inp.value = ''; });
                refreshSpreadsheetFilters('score');
              } else {
                alert(res.message || t('assessment.clearEvaluatorsError', 'ล้างผู้ประเมินไม่สำเร็จ'));
              }
            })
            .catch(() => alert(t('assessment.clearEvaluatorsNetwork', 'เชื่อมต่อไม่สำเร็จ')))
            .finally(() => { this.disabled = false; this.textContent = label; });
        });
      }

      // ═══════════ หัวข้อ 2 จัดการคอลัมน์ — การ์ดหัวข้อหลัก + คอลัมน์รอง ═══════════
      const colGrid = document.querySelector('[data-col-grid]');
      if (colGrid) {
        const boxesUrl = `{{ route('assessment.scores.boxes.store') }}`;
        const escCol = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const scoreHintText = cfg => {
          if ((cfg || {}).type === 'bonus') return '';
          const n = Number((cfg || {}).full_score ?? 10);
          if (!Number.isFinite(n)) return ' /10';
          return ` /${String(Number(n.toFixed(4))).replace(/\.0+$/, '')}`;
        };
        const flashCol = el => { el.classList.add('saved'); setTimeout(() => el.classList.remove('saved'), 1200); };

        const subRowHtml = box => `<div class="colsub" data-col-card="${box.id}">` +
          `<input type="text" class="colsub-name" value="${escCol(box.name)}" data-box-name="${box.id}" title="${escCol(t('assessment.scores.editSubTitle', 'แก้ชื่อคอลัมน์รอง'))}" data-i18n-title="assessment.scores.editSubTitle">` +
          `<button type="button" class="colcard-del" data-box-del="${box.id}" data-del-kind="sub" title="${escCol(t('assessment.scores.deleteSubTitle', 'ลบคอลัมน์รอง'))}" data-i18n-title="assessment.scores.deleteSubTitle">✕</button></div>`;

        const mainCardHtml = box => `<div class="colcard" data-col-card="${box.id}">` +
          `<div class="colcard-head">` +
            `<input type="text" class="colcard-name" value="${escCol(box.name)}" data-box-name="${box.id}" title="${escCol(t('assessment.scores.editMainTitle', 'แก้ชื่อหัวข้อหลัก'))}" data-i18n-title="assessment.scores.editMainTitle">` +
            `<button type="button" class="colcard-del" data-box-del="${box.id}" data-del-kind="main" title="${escCol(t('assessment.scores.deleteMainTitle', 'ลบหัวข้อหลักและคอลัมน์รองภายใน'))}" data-i18n-title="assessment.scores.deleteMainTitle">✕</button>` +
          `</div>` +
          `<div class="colcard-subs" data-col-subs="${box.id}"></div>` +
          `<div class="colsub-add"><input type="text" placeholder="${escCol(t('assessment.scores.addSubPlaceholder', '+ เพิ่มคอลัมน์รอง...'))}" data-i18n-placeholder="assessment.scores.addSubPlaceholder" data-add-sub="${box.id}"></div></div>`;

        // ---- 2.2 ตารางตรวจคอลัมน์ (มุมมอง Excel) — สร้างจากการ์ดปัจจุบัน อัปเดตทุกครั้งที่แก้ ----
        const colPreview = document.querySelector('[data-col-preview]');
        const colCount = document.querySelector('[data-col-count]');
        function renderColPreview() {
          if (!colPreview) return;
          const groups = [];
          colGrid.querySelectorAll('.colcard:not(.colcard-new)').forEach(card => {
            const name = card.querySelector('.colcard-name').value;
            const subs = Array.from(card.querySelectorAll('.colsub-name')).map(i => i.value);
            groups.push({ name, subs: subs.length ? subs : [name] });   // การ์ดไม่มีรอง → แสดงชื่อหลักเป็นรอง
          });
          const totalCols = groups.reduce((s, g) => s + g.subs.length, 0);
          if (colCount) colCount.textContent = totalCols
            ? `${groups.length} ${t('assessment.scores.mainHeadingUnit', 'หัวข้อหลัก')} · ${totalCols} ${t('assessment.scores.columnUnit', 'คอลัมน์')}`
            : '';
          if (!totalCols) {
            colPreview.innerHTML = '<p class="hint">' + escF(t('assessment.scores.noColumnsPreview', 'ยังไม่มีคอลัมน์ — สร้างหรือนำเข้าที่ 2.1 ก่อน')) + '</p>';
            return;
          }
          const colLetter = n => { let s = ''; while (n > 0) { n--; s = String.fromCharCode(65 + (n % 26)) + s; n = Math.floor(n / 26); } return s; };
          let html = '<div class="ss-wrap" style="max-height:46vh"><table class="ss">';
          html += '<tr class="ss-frame"><th class="ss-corner"></th>';
          for (let i = 1; i <= totalCols; i++) html += `<th>${colLetter(i)}</th>`;
          html += '</tr><tr class="ss-h1"><th class="ss-rownum">1</th>';
          groups.forEach(g => { html += `<th class="zone" colspan="${g.subs.length}">${escCol(g.name)}</th>`; });
          html += '</tr><tr class="ss-h2"><th class="ss-rownum">2</th>';
          groups.forEach(g => g.subs.forEach(s2 => { html += `<th>${escCol(s2)}</th>`; }));
          html += '</tr>';
          // แถวว่าง 3–10 ให้ดูเป็นหน้า Excel จริง อ่านง่าย
          for (let r = 3; r <= 10; r++) {
            html += `<tr><th class="ss-rownum">${r}</th>`;
            for (let i = 0; i < totalCols; i++) html += '<td>&nbsp;</td>';
            html += '</tr>';
          }
          html += '</table></div>';
          colPreview.innerHTML = html;
        }

        // ---- 3.1 กำหนดสัดส่วนของระดับ — การ์ดระดับ 1..asmMaxLevel × หัวข้อหลัก (ระดับ 0 ไม่คำนวณ) ----
        const propGrid = document.querySelector('[data-prop-grid]');
        const propConfig = @json($levelPropMatrix);   // {level: {box_id: {mode, weight}}}
        const PCT_OPTS = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100];
        const propCfg = (lv, id) => (propConfig[lv] || {})[id] || null;

        function setPropCfg(lv, id, patch) {
          propConfig[lv] = propConfig[lv] || {};
          propConfig[lv][id] = Object.assign(propConfig[lv][id] || { mode: 'percent', weight: 0 }, patch);
        }

        function levelTotal(lv) {
          let total = 0;
          const cfgLv = propConfig[lv] || {};
          colGrid.querySelectorAll('.colcard:not(.colcard-new)').forEach(card => {
            const c = cfgLv[card.dataset.colCard];
            if (c && (!c.mode || c.mode === 'percent')) total += Number(c.weight) || 0;
          });
          return Math.round(total * 100) / 100;
        }

        function refreshPropTotals() {
          if (!propGrid) return;
          for (let lv = 1; lv <= asmMaxLevel; lv++) {
            const el = propGrid.querySelector(`[data-prop-total="${lv}"]`);
            if (!el) continue;
            const total = levelTotal(lv);
            el.textContent = total.toLocaleString();
            el.style.color = Math.abs(total - 100) < 0.001 ? 'var(--moss)' : '#d98a80';
          }
          renderPropSummary();
        }

        // จุดสรุป: ระดับ 1: Attendance 50 · IP 50 · Bonus +/- — อัปเดตสดตามที่เลือก
        function renderPropSummary() {
          const sumEl = document.querySelector('[data-prop-summary]');
          if (!sumEl) return;
          const mains = [];
          colGrid.querySelectorAll('.colcard:not(.colcard-new)').forEach(card => {
            mains.push({ id: card.dataset.colCard, name: card.querySelector('.colcard-name').value });
          });
          let html = '';
          for (let lv = 1; lv <= asmMaxLevel; lv++) {
            const cfgLv = propConfig[lv] || {};
            const items = [];
            mains.forEach(m => {
              const c = cfgLv[m.id];
              if (!c || c.mode === 'none') return;
              if (c.mode === 'extra') items.push(`${escCol(m.name)} <span class="sum-extra">+/-</span>`);
              else if (Number(c.weight) > 0) items.push(`${escCol(m.name)} <b>${Number(c.weight).toLocaleString()}</b>`);
            });
            const total = levelTotal(lv);
            const ok = Math.abs(total - 100) < 0.001;
            html += `<div class="prop-sum-row"><span class="prop-sum-lv">ระดับ ${lv}</span>` +
              `<span class="prop-sum-items">${items.length ? items.join(' · ') : 'ยังไม่กำหนด'}</span>` +
              `<span class="prop-sum-ok" style="color:${ok ? 'var(--moss)' : '#d98a80'}">${total.toLocaleString()}/100</span></div>`;
          }
          sumEl.innerHTML = html;
        }

        // ---- 3.2 การคำนวณคอลัมน์รอง — ชนิดข้อมูล / รูปแบบ Attendance / ค่า ----
        const subGrid = document.querySelector('[data-subcalc]');
        const subCfg = @json($subCalcMatrix);   // {sub_id: {type, att_form, rate, grade_cap}}
        const GRADES = ['A', 'B', 'C', 'D', 'F'];
        const defaultCap = name => name.includes('พักงาน') ? 'D' : 'C';

        // จุดสรุปต่อแถว: _/10 · × -0.25 · สูงสุด C · +/- ตามค่า (คะแนนเพิ่มเติม)
        function subSummary(c) {
          const type = ['attendance', 'input', 'bonus'].includes(c.type) ? c.type : 'score';
          if (type === 'bonus') {
            return { text: '+/- ตามค่า', warn: false };
          }
          if (type === 'attendance') {
            if ((c.att_form === 'grade' ? 'grade' : 'score') === 'grade') {
              return c.grade_cap ? { text: `สูงสุด ${c.grade_cap}`, warn: false } : { text: 'ยังไม่ครบ', warn: true };
            }
            return (c.rate === null || c.rate === undefined || c.rate === '')
              ? { text: 'ใส่ rate', warn: true }
              : { text: `× ${Number(c.rate)}`, warn: false };
          }
          const fs = Number(c.full_score);
          return (!c.full_score || fs <= 0)
            ? { text: 'ใส่คะแนนเต็ม', warn: true }
            : { text: `_/${fs}`, warn: false };
        }

        function updateSubSum(row, cfg) {
          const el = row.querySelector('[data-sub-sum]');
          if (!el) return;
          const s = subSummary(cfg || {});
          el.textContent = s.text;
          el.classList.toggle('warn', s.warn);
        }

        function renderSubCalc() {
          if (!subGrid) return;
          let cards = '';
          colGrid.querySelectorAll('.colcard:not(.colcard-new)').forEach(card => {
            const mainName = card.querySelector('.colcard-name').value;
            const subs = card.querySelectorAll('.colsub');
            if (!subs.length) return;
            let rows = '';
            subs.forEach(srow => {
              const id = srow.dataset.colCard;
              const name = srow.querySelector('.colsub-name').value;
              const c = subCfg[id] || {};
              const type = ['attendance', 'input', 'bonus'].includes(c.type) ? c.type : 'score';
              const isAtt = type === 'attendance';
              const noFull = isAtt || type === 'bonus';
              const form = c.att_form === 'grade' ? 'grade' : 'score';
              const rate = (c.rate === null || c.rate === undefined) ? '' : Number(c.rate);
              const full = (c.full_score === null || c.full_score === undefined) ? '' : Number(c.full_score);
              const cap = c.grade_cap || defaultCap(name);
              const sum = subSummary(c);
              let capSel = `<select data-sub-cap="${id}"${(isAtt && form === 'grade') ? '' : ' hidden'}>`;
              GRADES.forEach(g => { capSel += `<option value="${g}"${cap === g ? ' selected' : ''}>${escCol(t('assessment.scores.maximum', 'สูงสุด'))} ${g}</option>`; });
              capSel += '</select>';
              rows += `<div class="sc-row" data-sub-row="${id}">` +
                `<span class="sc-name" title="${escCol(name)}">${escCol(name)}</span>` +
                `<span class="sc-sum${sum.warn ? ' warn' : ''}" data-sub-sum>${sum.text}</span>` +
                `<select data-sub-type="${id}">` +
                  `<option value="score"${type === 'score' ? ' selected' : ''}>${escCol(t('assessment.scores.typeScore', 'คะแนนเต็ม'))}</option>` +
                  `<option value="attendance"${isAtt ? ' selected' : ''}>Attendance</option>` +
                  `<option value="input"${type === 'input' ? ' selected' : ''}>Input</option>` +
                  `<option value="bonus"${type === 'bonus' ? ' selected' : ''}>${escCol(t('assessment.scores.typeBonus', 'คะแนนเพิ่มเติม'))}</option>` +
                `</select>` +
                `<select data-sub-form="${id}"${isAtt ? '' : ' hidden'}>` +
                  `<option value="score"${form === 'score' ? ' selected' : ''}>${escCol(t('assessment.scores.calcDeduct', 'หักคะแนน'))}</option>` +
                  `<option value="grade"${form === 'grade' ? ' selected' : ''}>${escCol(t('assessment.scores.calcGrade', 'เกรด'))}</option>` +
                `</select>` +
                `<input type="text" inputmode="decimal" class="sc-rate" data-sub-rate="${id}" placeholder="-0.25" value="${rate}"${(isAtt && form === 'score') ? '' : ' hidden'}>` +
                capSel +
                `<input type="text" inputmode="decimal" class="sc-full" data-sub-full="${id}" placeholder="${escCol(t('assessment.scores.fullPlaceholder', 'เต็ม 10'))}" value="${full}"${noFull ? ' hidden' : ''}>` +
                (type === 'input' ? (() => {
                  const lvls = (c.input_levels || '1,2').split(',').filter(Boolean);
                  const remain = ['1', '2', '3', '4'].filter(x => !lvls.includes(x));
                  let h = '<span class="sc-lvls">';
                  lvls.forEach(l => { h += `<button type="button" class="sc-lv" data-lv-del="${l}" title="${escCol(t('assessment.scores.removeHierarchy', 'นำลำดับนี้ออก'))}">${escCol(t('assessment.scores.hierarchyLabel', 'ลำดับ'))} ${l} ✕</button>`; });
                  if (remain.length) h += `<select class="sc-lv-add" data-lv-add><option value="">+</option>${remain.map(x => `<option value="${x}">${escCol(t('assessment.scores.hierarchyLabel', 'ลำดับ'))} ${x}</option>`).join('')}</select>`;
                  return h + '</span>';
                })() : '') +
                `</div>`;
            });
            cards += `<div class="colcard sc-card"><div class="lv-title"><span class="sc-card-name">${escCol(mainName)}</span></div>${rows}</div>`;
          });
          subGrid.innerHTML = cards ? `<div class="sc-grid">${cards}</div>` : '<p class="hint">' + escF(t('assessment.scores.noSubColumns', 'ยังไม่มีคอลัมน์รอง — สร้างที่หัวข้อ 2 ก่อน')) + '</p>';
        }

        // ปรับช่องคะแนนของคอลัมน์นี้ในหัวข้อ 4/5 ทันทีตาม input_levels ที่ตั้ง (ไม่ต้อง refresh)
        function syncInputColumnCells(boxId) {
          const c = subCfg[boxId] || {};
          const isInput = c.type === 'input';
          const lvls = isInput ? (c.input_levels || '1,2').split(',').filter(Boolean) : [];
          const cells = new Set();
          document.querySelectorAll(`td.sc4 input[data-box="${boxId}"]`).forEach(i => {
            if (i.closest('tr[data-level-zero="1"]')) return;
            cells.add(i.closest('td'));
          });
          cells.forEach(td => {
            const inputs = Array.from(td.querySelectorAll('input'));
            if (!inputs.length) return;
            const emp = inputs[0].dataset.emp;
            // เก็บค่าที่กรอกไว้ตามตำแหน่งเดิม ไม่ให้หาย
            let parts;
            if (inputs[0].dataset.parts) {
              parts = [];
              inputs.forEach(i => { i.dataset.parts.split(',').forEach(p => { parts[Number(p) - 1] = i.value; }); });
            } else {
              parts = String(inputs[0].value || '').split(',');
            }
            if (isInput) {
              td.className = 'sc4 sc4-dual';
              td.title = t('assessment.scores.evaluatorSlotsTitle', 'ช่องตามผู้ประเมินลำดับ {levels}', { levels: lvls.join(', ') });
              // rebuild แยกช่องตามลำดับ (merge ตามชื่อผู้ประเมินจะกลับมาเมื่อโหลดหน้าใหม่)
              td.innerHTML = lvls.map((lv, k) =>
                `<input type="text" placeholder="Input" title="${escCol(t('assessment.scores.evaluatorTitle', 'ผู้ประเมินลำดับ {level}', { level: lv }))}" value="${escCol((parts[k] ?? '').trim())}" data-emp="${escCol(emp)}" data-box="${boxId}" data-parts="${k + 1}">`
              ).join('');
            } else {
              td.className = 'sc4';
              td.removeAttribute('title');
              const joined = parts.map(p => (p ?? '').trim());
              while (joined.length && joined[joined.length - 1] === '') joined.pop();
              td.innerHTML = `<input type="text" placeholder="${escCol(scoreHintText(c))}" value="${escCol(joined.join(','))}" data-emp="${escCol(emp)}" data-box="${boxId}">`;
            }
          });
        }

        // refresh โซน result ทั้งชุดในหัวข้อ 5 (หลังแก้ลำดับผู้ประเมิน — สร้าง/ยุบคอลัมน์ได้ ไม่ต้อง reload)
        function refreshAllResults() {
          const finalStep = document.querySelector('[data-final-step]');
          if (!finalStep) return;
          fetch(`{{ route('assessment.scores.result.all') }}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
              if (!d.ok) return;
              // หัวแถว 1: โซน "ลำดับ X" (อยู่ท้ายแถวเสมอ — ลบแล้วต่อใหม่)
              const h1 = finalStep.querySelector('tr.imp-h1');
              if (h1) {
                h1.querySelectorAll('th[data-rzone-group]').forEach(t => t.remove());
                (d.groups || []).forEach(g => {
                  const th = document.createElement('th');
                  th.colSpan = g.span;
                  th.className = 'zone rzone';
                  th.setAttribute('data-rzone-group', '');
                  th.textContent = g.label;
                  h1.appendChild(th);
                });
              }
              // หัวแถว 2: คะแนนรวม | เกรด | เกรด คำบรรยาย ต่อลำดับ
              const h2 = finalStep.querySelector('tr.imp-h2');
              if (h2) {
                h2.querySelectorAll('th[data-rh]').forEach(t => t.remove());
                (d.headers || []).forEach(hText => {
                  const th = document.createElement('th');
                  th.className = 'rzone';
                  th.setAttribute('data-rh', '');
                  th.textContent = hText;
                  h2.appendChild(th);
                });
              }
              // ช่องผลลัพธ์ทุกแถว (จำนวนช่องเปลี่ยนตามลำดับ)
              finalStep.querySelectorAll('tr[data-frow]').forEach(tr => {
                tr.querySelectorAll('td.rc').forEach(t => t.remove());
                (d.rows[tr.dataset.frow] || []).forEach(cText => {
                  const td = document.createElement('td');
                  td.className = 'rc';
                  td.textContent = cText;
                  tr.appendChild(td);
                });
              });
            });
        }

        function saveSubCalc(id, body, el, after) {
          req(`${boxesUrl}/${id}`, body, 'PUT')
            .then(res => {
              if (!res.ok) { alert(res.message || t('assessment.scores.saveError', 'บันทึกไม่สำเร็จ')); return; }
              subCfg[id] = Object.assign(subCfg[id] || {}, {
                type: res.box.type, att_form: res.box.att_form, rate: res.box.rate, grade_cap: res.box.grade_cap, full_score: res.box.full_score, input_levels: res.box.input_levels,
              });
              flashCol(el);
              if (after) after();
            });
        }

        if (subGrid) {
          // เอาลำดับผู้ประเมินออก (คลิกชิป) — ต้องเหลืออย่างน้อย 1
          subGrid.addEventListener('click', function (e) {
            const del = e.target.closest('[data-lv-del]');
            if (!del) return;
            const row = del.closest('[data-sub-row]');
            const id = row.dataset.subRow;
            const lv = ((subCfg[id] || {}).input_levels || '1,2').split(',').filter(Boolean);
            if (lv.length <= 1) { alert(t('assessment.scores.minEvaluatorError', 'ต้องมีผู้ประเมินอย่างน้อย 1 ลำดับ')); return; }
            saveSubCalc(id, { input_levels: lv.filter(x => x !== del.dataset.lvDel).join(',') }, del, () => { renderSubCalc(); syncInputColumnCells(id); refreshAllResults(); });
          });

          subGrid.addEventListener('change', function (e) {
            const el = e.target;
            const row = el.closest('[data-sub-row]');
            if (!row) return;
            const id = row.dataset.subRow;
            const toggle = () => {
              const c = subCfg[id] || {};
              const type = ['attendance', 'input', 'bonus'].includes(c.type) ? c.type : 'score';
              const isAtt = type === 'attendance';
              const form = c.att_form === 'grade' ? 'grade' : 'score';
              row.querySelector('[data-sub-form]').hidden = !isAtt;
              row.querySelector('[data-sub-rate]').hidden = !(isAtt && form === 'score');
              row.querySelector('[data-sub-cap]').hidden = !(isAtt && form === 'grade');
              row.querySelector('[data-sub-full]').hidden = isAtt || type === 'bonus';
              updateSubSum(row, c);
            };
            if (el.matches('[data-sub-type]')) {
              const body = { type: el.value };
              if (el.value === 'input' && !(subCfg[id] || {}).input_levels) {
                body.input_levels = '1,2';   // default ผู้ประเมินลำดับ 1,2
              }
              saveSubCalc(id, body, el, () => { renderSubCalc(); syncInputColumnCells(id); refreshAllResults(); });
              return;
            }
            if (el.matches('[data-lv-add]')) {
              if (!el.value) return;
              const lv = ((subCfg[id] || {}).input_levels || '1,2').split(',').filter(Boolean);
              lv.push(el.value);
              saveSubCalc(id, { input_levels: lv.join(',') }, el, () => { renderSubCalc(); syncInputColumnCells(id); refreshAllResults(); });
              return;
            }
            if (el.matches('[data-sub-form]')) {
              const body = { att_form: el.value };
              if (el.value === 'grade' && !(subCfg[id] || {}).grade_cap) {
                body.grade_cap = row.querySelector('[data-sub-cap]').value;   // default C/D ตามชื่อ
              }
              saveSubCalc(id, body, el, toggle);
              return;
            }
            if (el.matches('[data-sub-rate]')) {
              saveSubCalc(id, { rate: el.value === '' ? null : parseFloat(el.value) }, el, toggle);
              return;
            }
            if (el.matches('[data-sub-cap]')) {
              saveSubCalc(id, { grade_cap: el.value }, el, toggle);
              return;
            }
            if (el.matches('[data-sub-full]')) {
              saveSubCalc(id, { full_score: el.value === '' ? null : parseFloat(el.value) }, el, toggle);
            }
          });
        }

        function renderPropCards() {
          if (!propGrid) return;
          const mains = [];
          colGrid.querySelectorAll('.colcard:not(.colcard-new)').forEach(card => {
            mains.push({ id: card.dataset.colCard, name: card.querySelector('.colcard-name').value });
          });
          if (!mains.length) {
            propGrid.innerHTML = '<p class="hint">' + escF(t('assessment.scores.noMainColumns', 'ยังไม่มีหัวข้อหลัก — สร้างหรือนำเข้าที่หัวข้อ 2 ก่อน')) + '</p>';
            return;
          }
          if (asmMaxLevel < 1) {
            propGrid.innerHTML = '<p class="hint">' + escF(t('assessment.scores.noLevels', 'ยังไม่มีระดับ — กด + เพิ่มระดับ ในหัวข้อ 1.1 ก่อน (ระดับ 0 ไม่คำนวณ)')) + '</p>';
            return;
          }
          let html = '';
          for (let lv = 1; lv <= asmMaxLevel; lv++) {
            let rows = '';
            mains.forEach(m => {
              const cfg = propCfg(lv, m.id) || { mode: 'percent', weight: 0 };
              const mode = ['extra', 'none'].includes(cfg.mode) ? cfg.mode : 'percent';
              const w = Number(cfg.weight) || 0;
              const isStd = PCT_OPTS.includes(w);
              const isCustom = w > 0 && !isStd;
              let pctSel = `<select data-prop-pct data-level="${lv}" data-box="${m.id}"${mode === 'percent' ? '' : ' hidden'}><option value=""${w === 0 ? ' selected' : ''}>${escCol(t('assessment.scores.choose', 'เลือก'))}</option>`;
              PCT_OPTS.forEach(p => { pctSel += `<option value="${p}"${(isStd && w === p) ? ' selected' : ''}>${p}</option>`; });
              pctSel += `<option value="custom"${isCustom ? ' selected' : ''}>${escCol(t('assessment.scores.other', 'อื่นๆ...'))}</option></select>`;
              rows += `<div class="prop-row is-${mode}">` +
                `<span class="prop-row-name" title="${escCol(m.name)}">${escCol(m.name)}</span>` +
                `<select data-prop-mode data-level="${lv}" data-box="${m.id}">` +
                  `<option value="percent"${mode === 'percent' ? ' selected' : ''}>${escCol(t('assessment.scores.modePercent', 'สัดส่วน'))}</option>` +
                  `<option value="extra"${mode === 'extra' ? ' selected' : ''}>${escCol(t('assessment.scores.modeExtra', 'เพิ่มเติม (+/-)'))}</option>` +
                  `<option value="none"${mode === 'none' ? ' selected' : ''}>${escCol(t('assessment.scores.modeNone', 'ไม่คำนวณ'))}</option>` +
                `</select>` + pctSel +
                `<input type="text" inputmode="decimal" class="prop-custom" data-prop-custom data-level="${lv}" data-box="${m.id}" placeholder="${escCol(t('assessment.scores.customPlaceholder', 'กรอกเอง'))}" value="${isCustom ? w : ''}"${(mode === 'percent' && isCustom) ? '' : ' hidden'}>` +
                `</div>`;
            });
            html += `<div class="colcard propcard" data-prop-level="${lv}">` +
              `<div class="lv-title"><span class="prop-lv-badge">${escCol(t('assessment.scores.levelLabel', 'ระดับ'))} ${lv}</span><span class="lv-count">${escCol(t('assessment.scores.totalLabel', 'รวม'))} <b data-prop-total="${lv}">0</b>/100</span></div>` +
              rows + `</div>`;
          }
          propGrid.innerHTML = html;
          refreshPropTotals();
        }

        function saveLevelProp(lv, id, body, el, after) {
          req(`{{ route('assessment.scores.level.prop') }}`, Object.assign({ level: Number(lv), box_id: Number(id) }, body), 'PUT')
            .then(res => {
              if (!res.ok) { alert(res.message || t('assessment.scores.saveError', 'บันทึกไม่สำเร็จ')); return; }
              setPropCfg(lv, id, body);
              flashCol(el);
              if (after) after();
              refreshPropTotals();
            });
        }

        if (propGrid) {
          propGrid.addEventListener('change', function (e) {
            const el = e.target;
            const lv = el.dataset.level;
            const id = el.dataset.box;
            if (el.matches('[data-prop-mode]')) {
              const mode = el.value;
              saveLevelProp(lv, id, { mode }, el, () => {
                const row = el.closest('.prop-row');
                const pct = row.querySelector('[data-prop-pct]');
                const cust = row.querySelector('[data-prop-custom]');
                pct.hidden = mode !== 'percent';
                cust.hidden = mode !== 'percent' || pct.value !== 'custom';
                row.classList.remove('is-percent', 'is-extra', 'is-none');
                row.classList.add(`is-${mode}`);
              });
              return;
            }
            if (el.matches('[data-prop-pct]')) {
              const cust = el.closest('.prop-row').querySelector('[data-prop-custom]');
              if (el.value === 'custom') { cust.hidden = false; cust.focus(); return; }
              cust.hidden = true;
              cust.value = '';
              saveLevelProp(lv, id, { weight: el.value === '' ? 0 : Number(el.value) }, el);
              return;
            }
            if (el.matches('[data-prop-custom]')) {
              saveLevelProp(lv, id, { weight: parseFloat(el.value) || 0 }, el);
            }
          });
        }

        rerenderProps = renderPropCards;   // ให้ปุ่ม +/✕ ระดับใน 1.1 สั่ง render การ์ด 3.1 ใหม่ได้
        // ลบระดับแล้วเลื่อน config ที่สูงกว่าลงมาแทน (เหมือน DB) — deleted..maxLv คือช่วงก่อนลด asmMaxLevel
        shiftPropLevels = (deleted, maxLv) => {
          for (let lv = deleted + 1; lv <= maxLv; lv++) propConfig[lv - 1] = propConfig[lv];
          delete propConfig[maxLv];
        };
        renderColPreview();
        renderPropCards();
        renderSubCalc();

        // แก้ชื่อ (หลัก/รอง) — บันทึกอัตโนมัติ
        colGrid.addEventListener('change', function (e) {
          const el = e.target;
          if (!el.matches('[data-box-name]')) return;
          const name = el.value.trim();
          if (name === '') { el.value = el.defaultValue; return; }
          req(`${boxesUrl}/${el.dataset.boxName}`, { name }, 'PUT')
            .then(res => {
              if (res.ok) { el.value = res.box.name; el.defaultValue = res.box.name; flashCol(el); renderColPreview(); renderPropCards(); renderSubCalc(); }
              else { alert(res.message || t('assessment.scores.saveError', 'บันทึกไม่สำเร็จ')); el.value = el.defaultValue; }
            });
        });

        // ลบการ์ดหลัก / คอลัมน์รอง + ปุ่มเพิ่มหัวข้อหลัก
        colGrid.addEventListener('click', function (e) {
          const del = e.target.closest('[data-box-del]');
          if (del) {
            const msg = del.dataset.delKind === 'main'
              ? t('assessment.scores.deleteMainConfirm', 'ลบหัวข้อหลักนี้ทั้งการ์ด?\nคอลัมน์รองข้างในและคะแนนที่เคยบันทึกในคอลัมน์เหล่านี้จะถูกลบด้วย')
              : t('assessment.scores.deleteSubConfirm', 'ลบคอลัมน์รองนี้?\nคะแนนที่เคยบันทึกในคอลัมน์นี้จะถูกลบด้วย');
            if (!confirm(msg)) return;
            req(`${boxesUrl}/${del.dataset.boxDel}`, {}, 'DELETE')
              .then(res => { if (res.ok) { del.closest('[data-col-card]').remove(); renderColPreview(); renderPropCards(); renderSubCalc(); } else alert(res.message || t('assessment.scores.deleteError', 'ลบไม่สำเร็จ')); });
            return;
          }
          if (e.target.matches('[data-add-main-btn]')) addMain();
        });

        // Enter ในช่องเพิ่ม
        colGrid.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter') return;
          if (e.target.matches('[data-add-sub]')) { e.preventDefault(); addSub(e.target); }
          if (e.target.matches('[data-add-main]')) { e.preventDefault(); addMain(); }
        });

        function addSub(inp) {
          const name = inp.value.trim();
          if (!name) return;
          req(boxesUrl, { parent_id: Number(inp.dataset.addSub), name }, 'POST')
            .then(res => {
              if (res.ok) {
                inp.closest('.colcard').querySelector('[data-col-subs]').insertAdjacentHTML('beforeend', subRowHtml(res.box));
                inp.value = '';
                inp.focus();
                subCfg[res.box.id] = { type: res.box.type, att_form: res.box.att_form, rate: res.box.rate, grade_cap: res.box.grade_cap, full_score: res.box.full_score };
                renderColPreview();
                renderPropCards();
                renderSubCalc();
              } else alert(res.message || t('assessment.scores.addError', 'เพิ่มไม่สำเร็จ'));
            });
        }

        function addMain() {
          const inp = colGrid.querySelector('[data-add-main]');
          const name = inp.value.trim();
          if (!name) { inp.focus(); return; }
          req(boxesUrl, { name }, 'POST')
            .then(res => {
              if (!res.ok) { alert(res.message || t('assessment.scores.addError', 'เพิ่มไม่สำเร็จ')); return; }
              const mainBox = res.box;
              colGrid.querySelector('.colcard-new').insertAdjacentHTML('beforebegin', mainCardHtml(mainBox));
              inp.value = '';
              // สร้างคอลัมน์รองชื่อเดียวกันในการ์ดอัตโนมัติ (เหมือน import กรณีแถว 1 = แถว 2)
              req(boxesUrl, { parent_id: mainBox.id, name }, 'POST')
                .then(res2 => {
                  if (res2.ok) {
                    colGrid.querySelector(`[data-col-subs="${mainBox.id}"]`).insertAdjacentHTML('beforeend', subRowHtml(res2.box));
                    subCfg[res2.box.id] = { type: res2.box.type, att_form: res2.box.att_form, rate: res2.box.rate, grade_cap: res2.box.grade_cap, full_score: res2.box.full_score };
                  }
                  renderColPreview();
                  renderPropCards();
                  renderSubCalc();
                })
                .catch(() => { renderColPreview(); renderPropCards(); renderSubCalc(); });
            });
        }
      }

      // ═══════════ หัวข้อ 4 + 5 — แก้คะแนน inline (sync ทุกตาราง) + refresh ผลลัพธ์แถวนั้นในหัวข้อ 5 ═══════════
      document.addEventListener('change', function (e) {
        const inp = e.target;
        if (!inp.matches('td.sc4 input') || inp.disabled) return;
        // ช่อง Input: แต่ละช่องครอบได้หลาย slot (data-parts) — เติมค่าลงทุก slot ที่ครอบ (merge = คนเดียวกัน)
        let payload = inp.value.trim();
        if (inp.dataset.parts) {
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
            // sync ช่องเดียวกัน (แยกตามตำแหน่ง slot แรกของช่อง)
            document.querySelectorAll(`td.sc4 input[data-box="${inp.dataset.box}"]`).forEach(o => {
              if (o.dataset.emp !== inp.dataset.emp) return;
              if (o.dataset.parts) o.value = parts[Number(o.dataset.parts.split(',')[0]) - 1] ?? '';
              else o.value = val;
            });
            inp.classList.add('saved');
            setTimeout(() => inp.classList.remove('saved'), 1200);
            refreshSpreadsheetFilters('s4');
            // คำนวณผลลัพธ์แถวนี้ใหม่ (หัวข้อ 5)
            const tr = document.querySelector(`[data-final-step] tr[data-frow="${inp.dataset.emp}"]`);
            if (!tr) return;
            fetch(`{{ route('assessment.scores.result.row') }}?code=` + encodeURIComponent(inp.dataset.emp), { headers: { 'Accept': 'application/json' } })
              .then(r => r.json())
              .then(d => {
                if (!d.ok) return;
                tr.querySelectorAll('td.rc').forEach((td, i) => { td.textContent = d.cells[i] ?? '—'; });
              });
          });
      });

    })();
  </script>
@endsection
