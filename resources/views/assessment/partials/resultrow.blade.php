{{--
  แถวเดียวของตารางแท็บผลลัพธ์ — ใช้ทั้ง loop หลักใน results.blade และ endpoint assessment.results.row.html (แถวสดหลังแก้ผู้ประเมิน)
  ต้องการ: $emp, $no (null = ให้ JS คงเลขเดิม), $levelMap, $hierMap, $levelPropMatrix, $editable,
           $leaves4, $scoreMap4, $results4, $evalLevels4
  helper ใช้ prefix rr เพื่อไม่ชนตัวแปรหน้าแม่ (include แชร์ scope)
--}}
@php
  $rrFmt = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
  $rrNaBit = fn ($pv, int $part) => ((int) ($pv['na'] ?? 0)) & (1 << ($part - 1));
  $rrSlotVal = fn ($pv, int $part) => $pv[$part === 1 ? 'v' : 'v'.$part] ?? null;
  $rrSlotDisp = fn ($pv, int $part) => $pv && $rrNaBit($pv, $part) ? 'N/A' : $rrFmt($rrSlotVal($pv, $part));
  $rrFmt4 = function ($pv) use ($rrFmt, $rrNaBit) {
    if (! $pv) return '';
    $vals = [$pv['v'] ?? null, $pv['v2'] ?? null, $pv['v3'] ?? null, $pv['v4'] ?? null];
    $last = -1;
    foreach ($vals as $i => $v) { if ($v !== null || $rrNaBit($pv, $i + 1)) $last = $i; }
    if ($last < 0) return '';
    $parts = [];
    for ($i = 0; $i <= $last; $i++) $parts[] = $rrNaBit($pv, $i + 1) ? 'N/A' : ($vals[$i] === null ? '' : $rrFmt($vals[$i]));
    return implode(',', $parts);
  };
  $rrPropCfg = function ($level, int $boxId) use ($levelPropMatrix) {
    if ($level === null) return null;
    $matrix = is_array($levelPropMatrix) ? $levelPropMatrix : (array) $levelPropMatrix;
    $rows = $matrix[(int) $level] ?? $matrix[(string) $level] ?? null;
    if (is_object($rows)) $rows = (array) $rows;
    if (! is_array($rows)) return null;
    $cfg = $rows[$boxId] ?? $rows[(string) $boxId] ?? null;
    if (is_object($cfg)) $cfg = (array) $cfg;
    return is_array($cfg) ? $cfg : null;
  };
  $rrNotCalc = fn ($level, $leaf) => $level !== null && (int) $level >= 1
    && (($rrPropCfg((int) $level, (int) ($leaf->parent_id ?: $leaf->id))['mode'] ?? '') === 'none');
  $rrExtra = fn ($level, $leaf) => ($leaf->type ?? '') === 'bonus' || ($level !== null && (int) $level >= 1
    && (($rrPropCfg((int) $level, (int) ($leaf->parent_id ?: $leaf->id))['mode'] ?? '') === 'extra'));
  $rrHint = function ($leaf, $level = null, bool $isInput = false) use ($rrFmt, $rrExtra) {
    if ($isInput) return 'Input';
    if ($rrExtra($level, $leaf)) return '';
    return ' /'.$rrFmt(($leaf->full_score ?? null) === null ? 10 : (float) $leaf->full_score);
  };
  $rrGroups = function ($hier, array $levels) {
    $groups = [];
    $order = [];
    foreach ($levels as $i => $lvSlot) {
      $name = $hier ? trim((string) ($hier->{'l'.$lvSlot.'_name'} ?? '')) : '';
      $key = $name !== '' ? 'n:'.$name : 'p:'.$i;
      if (! isset($groups[$key])) { $groups[$key] = ['parts' => [], 'levels' => [], 'name' => $name]; $order[] = $key; }
      $groups[$key]['parts'][] = $i + 1;
      $groups[$key]['levels'][] = $lvSlot;
    }
    return array_map(fn ($k) => $groups[$k], $order);
  };
  $rrDup = function ($hier, array $evalLevels) {
    $seen = [];
    $dup = [];
    foreach ($evalLevels as $L) {
      $name = $hier ? trim((string) ($hier->{'l'.$L.'_name'} ?? '')) : '';
      $dup[$L] = ($name !== '' && in_array($name, $seen, true));
      if ($name !== '') $seen[] = $name;
    }
    return $dup;
  };

  $rrCode = (string) $emp->employee_code;
  $rrLv = $levelMap[$emp->job_code] ?? null;
  $rrZero = $rrLv !== null && (int) $rrLv === 0;
  $rrHier = $hierMap[$rrCode] ?? null;
  $rrFields = ['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'];
  $rrComplete = true;
  foreach ($rrFields as $rrF) {
    $rrV = trim((string) ($rrHier->$rrF ?? ''));
    if ($rrV === '' || $rrV === '-') { $rrComplete = false; break; }
  }
  $rrDupMap = $rrDup($rrHier, $evalLevels4);
  $rrScoreZoneMeta = $scoreZoneMeta ?? [];
  if ($rrScoreZoneMeta === []) {
    foreach ($zones4 as $rrZoneIndex => $rrZone) {
      foreach ($rrZone['leaves'] as $rrLeafIndex => $rrLeaf) {
        $rrScoreZoneMeta[(int) $rrLeaf->id] = [
          'tone' => ($rrZoneIndex % 7) + 1,
          'start' => $rrLeafIndex === 0,
          'name' => $rrZone['name'],
        ];
      }
    }
  }
@endphp
<tr data-frow="{{ $rrCode }}" data-filter-data-row="res" data-level-zero="{{ $rrZero ? '1' : '0' }}" data-complete="{{ $rrComplete ? '1' : '0' }}">
  <td class="no" data-no>{{ $no === null ? '' : number_format($no) }}</td>
  <td class="code">{{ $rrCode }}</td>
  <td>{{ $emp->fullNameTh() ?: $emp->name_en }}</td>
  <td>{{ $emp->job_th ?: $emp->job_en }}</td>
  <td>{{ $emp->deptThClean() ?: $emp->dept_en }}</td>
  <td class="no info-zone-start" data-info-zone="level">{{ $rrLv ?? '' }}</td>
  {{-- ผู้ประเมินลำดับ 1-4 กรอกได้เมื่อรอบเปิด; คะแนนทุกคอลัมน์ต้องรอ hierarchy ครบ --}}
  @foreach ($rrFields as $f)
    @php
      $rrInfoZone = 'hier-'.(intdiv($loop->index, 2) + 1);
      $rrInfoStartClass = $loop->index % 2 === 0 ? ' info-zone-start' : '';
    @endphp
    @if ($rrZero)
      <td class="hcell{{ $rrInfoStartClass }}" data-info-zone="{{ $rrInfoZone }}">-</td>
    @elseif ($editable)
      <td class="hcell hcell-edit{{ $rrInfoStartClass }}" data-info-zone="{{ $rrInfoZone }}"><input type="text" value="{{ $rrHier->$f ?? '' }}" data-emp="{{ $rrCode }}" data-field="{{ $f }}" placeholder="-"></td>
    @else
      @php $rrHv = trim((string) ($rrHier->$f ?? '')); @endphp
      <td class="hcell{{ $rrInfoStartClass }}" data-info-zone="{{ $rrInfoZone }}">{{ $rrHv === '' ? '-' : $rrHv }}</td>
    @endif
  @endforeach
  @foreach ($leaves4 as $leaf)
    @php
      $rrZoneMeta = $rrScoreZoneMeta[(int) $leaf->id] ?? ['tone' => 1, 'start' => false, 'name' => $leaf->name];
      $rrZoneStartClass = $rrZoneMeta['start'] ? ' score-zone-start' : '';
      $rrIsNotCalculated = $rrNotCalc($rrLv, $leaf);
    @endphp
    @if ($rrZero)
      <td class="sc4{{ $rrZoneStartClass }}" data-score-zone="{{ $rrZoneMeta['tone'] }}"><input type="text" class="asm-excluded-input" value="-" disabled tabindex="-1" aria-label="ไม่ถูกประเมิน"></td>
    @elseif ($rrIsNotCalculated)
      <td class="sc4 score-unavailable{{ $rrZoneStartClass }}" data-score-zone="{{ $rrZoneMeta['tone'] }}" aria-label="{{ $rrZoneMeta['name'] }} ไม่คำนวณสำหรับระดับ {{ $rrLv }}" title="ไม่คำนวณสำหรับระดับ {{ $rrLv }}"></td>
    @elseif (! $rrComplete)
      <td class="sc4{{ $rrZoneStartClass }}" data-score-zone="{{ $rrZoneMeta['tone'] }}"><input type="text" class="asm-excluded-input" value="-" disabled tabindex="-1" aria-label="ผู้ประเมินยังไม่ครบ"></td>
    @elseif ($leaf->type === 'input')
      @php
        $rrPv = $scoreMap4[$rrCode][$leaf->id] ?? null;
        $rrSlots = array_values(array_filter(explode(',', $leaf->input_levels ?: '1,2')));
      @endphp
      <td class="sc4 sc4-dual{{ $rrZoneStartClass }}" data-score-zone="{{ $rrZoneMeta['tone'] }}" title="ช่องตามผู้ประเมิน (รวมเป็นช่องเดียวเมื่อเป็นคนเดียวกัน)">
        @foreach ($rrGroups($rrHier, $rrSlots) as $g)
          <input type="text" placeholder="{{ $rrHint($leaf, $rrLv, true) }}" title="ผู้ประเมิน{{ $g['name'] !== '' ? ': '.$g['name'] : ' ลำดับ '.implode(',', $g['levels']) }}" value="{{ $rrSlotDisp($rrPv, $g['parts'][0]) }}" data-emp="{{ $rrCode }}" data-box="{{ $leaf->id }}" data-parts="{{ implode(',', $g['parts']) }}" @disabled(! $editable)>
        @endforeach
      </td>
    @else
      <td class="sc4{{ $rrZoneStartClass }}" data-score-zone="{{ $rrZoneMeta['tone'] }}"><input type="text" placeholder="{{ $rrHint($leaf, $rrLv) }}" value="{{ $rrFmt4($scoreMap4[$rrCode][$leaf->id] ?? null) }}" data-emp="{{ $rrCode }}" data-box="{{ $leaf->id }}" @disabled(! $editable)></td>
    @endif
  @endforeach
  @foreach ($results4[$rrCode] ?? [] as $ci => $cell)
    @php $rrLvCell = $evalLevels4[intdiv($ci, 3)] ?? null; @endphp
    <td class="rc">{{ $rrZero ? '-' : (($rrLvCell !== null && ($rrDupMap[$rrLvCell] ?? false)) ? '—' : $cell) }}</td>
  @endforeach
</tr>
