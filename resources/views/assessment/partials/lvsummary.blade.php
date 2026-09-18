{{-- การ์ดสรุปการประเมิน (ระดับ · จำนวนพนักงาน · ค่าเฉลี่ยรายลำดับชั้น)
     แยกออกจาก asmtabs เพื่อให้หน้าผลลัพธ์วางไว้คู่กับแถบแท็บ/ปุ่มปีได้ ไม่กินความสูงทั้งแถว --}}
@php
  $lvs = $levelSummary ?? null;
  $evalLv = $lvs['evalLevels'] ?? [];
@endphp
@if ($lvs)
  <style>
    /* การ์ดสรุปการประเมิน — กรอบเดียว ข้างในเป็นตาราง ไม่มีกรอบย่อยรายแถว */
    .lvs { border:1px solid var(--line-light); border-radius:0.3rem;
           background:var(--panel-soft); padding:.42rem .55rem .45rem; }
    .lvs-t { font-size:.76rem; font-weight:700; color:var(--moss); line-height:1.3; }
    .lvs-s { font-size:.7rem; color:var(--muted-light); line-height:1.4; margin-bottom:.25rem; }
    .lvs-s b { color:inherit; font-weight:700; font-variant-numeric:tabular-nums; }
    .lvs-r { display:grid; grid-template-columns:4.3rem 5.4rem repeat(var(--cols,2), 4.1rem);
             gap:.45rem; padding:.16rem .25rem; font-size:.72rem; border-radius:0.18rem; }
    .lvs-r > span:not(:first-child) { text-align:right; font-variant-numeric:tabular-nums; }
    .lvs-hd { color:var(--muted-light); font-size:.67rem; border-bottom:1px solid var(--line-light); padding-bottom:.22rem; margin-bottom:.12rem; }
    .lvs-r .lv { font-weight:700; }
    .lvs-r .n { color:var(--muted-light); }
    .lvs-r .v { color:var(--moss); font-weight:700; }
    .lvs-r .x { color:var(--muted-light); font-style:italic; grid-column:3 / -1; }
    .lvs-b:hover { background:var(--menu-bg); }
    @media (max-width:900px) { .lvs { width:100%; } }
  </style>

  <div class="lvs" style="--cols:{{ max(1, count($evalLv)) }}">
    <div class="lvs-t" data-i18n="assessment.common.summary">ข้อมูลสรุปการประเมิน</div>
    <div class="lvs-s">
      พนักงานทั้งหมด : <b>{{ number_format($lvs['total']) }}</b>
      &nbsp;·&nbsp; ประเมิน : <b>{{ number_format($lvs['assessed']) }}</b>
      {{ ($activeFilterCount ?? 0) > 0 ? ' · กรองอยู่' : '' }}
    </div>

    <div class="lvs-r lvs-hd">
      <span data-i18n="assessment.common.level">ระดับ</span>
      <span data-i18n="assessment.common.employeeCount">จำนวนพนักงาน</span>
      @foreach ($evalLv as $L)
        <span><span data-i18n="assessment.hierLevel">ลำดับ</span> {{ $L }}</span>
      @endforeach
    </div>

    @foreach ($lvs['levels'] as $row)
      <div class="lvs-r lvs-b">
        <span class="lv"><span data-i18n="assessment.common.level">ระดับ</span> {{ $row['level'] }}</span>
        <span class="n">{{ number_format($row['count']) }}</span>
        @if ($row['level'] < 1)
          <span class="x" data-i18n="assessment.common.notAssessed">ไม่ประเมิน</span>
        @elseif (! $row['calculable'])
          <span class="x" data-i18n="assessment.common.notCalculated">ไม่คำนวณ</span>
        @else
          @foreach ($evalLv as $L)
            <span class="v">{{ ($row['avg'][$L] ?? null) === null ? '—' : number_format($row['avg'][$L], 2) }}</span>
          @endforeach
        @endif
      </div>
    @endforeach
  </div>
@endif
