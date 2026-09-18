@extends('layouts.portal')

@section('title', 'รายละเอียดการทำงาน')

@section('topbar-title')
  <span class="tt-kicker">TIME &amp; LEAVE</span>
  <span class="tt-title" data-i18n="emp.title">รายละเอียดการทำงาน</span>
@endsection

@section('page-style')
  .emp-page { width: min(100%, 74rem); margin: 0 auto; }

  .emp-head { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
  .emp-photo {
    width: 3.4rem; height: 3.4rem; flex: 0 0 auto;
    display: grid; place-items: center;
    border: 1px solid var(--line-light); border-radius: 50%;
    background: var(--hover-soft); color: var(--muted-light);
    font-size: 1.05rem; font-weight: 650; object-fit: cover; overflow: hidden;
  }
  .emp-identity h1 { font-size: 1.4rem; font-weight: 650; line-height: 1.2; }
  .emp-identity p { margin-top: .2rem; color: var(--muted-light); font-size: .8rem; }
  /* ปุ่มเปิดปฏิทิน — ถอดสเปกจาก .la-date-picker ของหน้าอนุมัติลา
     (กล่องมีกรอบ ไอคอนปฏิทินอยู่ข้างใน ย้อมเขียว moss เมื่อกำลังใช้งาน) */
  .emp-month { position: relative; margin-left: auto; display: flex; align-items: center; gap: .45rem; flex: 0 0 auto; }
  .emp-month-button {
    min-height: 2.45rem; display: inline-flex; align-items: center; gap: .45rem; flex: 0 0 auto;
    padding: 0 .7rem; border: 1px solid var(--line-light); border-radius: 4px;
    background: var(--panel); color: var(--muted-light);
    font: inherit; font-size: .76rem; font-weight: 650; cursor: pointer;
  }
  .emp-month-button.is-active { border-color: var(--moss); background: color-mix(in srgb, var(--moss) 10%, var(--panel)); color: var(--light-text); }
  .emp-month-button:focus-visible, .emp-month-button[aria-expanded="true"] { border-color: var(--moss); }
  .emp-month-button svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.7; opacity: .8; }
  .emp-month-caret { width: .85rem !important; transition: transform .16s ease; }
  .emp-month-button[aria-expanded="true"] .emp-month-caret { transform: rotate(180deg); }

  /* แผงเลือกเดือน — เลื่อนปีด้วยลูกศร แล้วกดชื่อเดือนได้เลย */
  .emp-month-panel {
    position: absolute; top: calc(100% + .4rem); right: 0; z-index: 60;
    width: 17rem; padding: .7rem;
    border: 1px solid var(--line-light); border-radius: 4px;
    background: var(--panel); box-shadow: 0 .9rem 2.2rem rgb(0 0 0 / 18%);
  }
  .emp-month-panel[hidden] { display: none; }
  .emp-month-nav { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .55rem; }
  .emp-month-nav b { font-size: .84rem; font-variant-numeric: tabular-nums; }
  .emp-month-nav button {
    width: 1.9rem; height: 1.9rem; border: 1px solid var(--line-light); border-radius: 4px;
    background: transparent; color: inherit; font-size: 1rem; line-height: 1; cursor: pointer;
  }
  .emp-month-nav button:hover { border-color: var(--moss); color: var(--moss); }
  .emp-month-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .3rem; }
  .emp-month-grid button {
    padding: .45rem .3rem; border: 1px solid transparent; border-radius: 4px;
    background: transparent; color: inherit; font: inherit; font-size: .73rem; cursor: pointer;
  }
  .emp-month-grid button:hover { border-color: var(--line-strong); }
  .emp-month-grid button.is-current { border-color: var(--line-strong); font-weight: 700; }
  .emp-month-grid button.is-selected { border-color: var(--moss); background: var(--moss); color: #fff; font-weight: 700; }
  /* ปุ่มคู่กับปฏิทิน ใช้สเปกเดียวกับปุ่ม "ทุกวัน" (.la-date-all) ของหน้าอนุมัติลา */
  .emp-back-today {
    min-height: 2.45rem; display: inline-flex; align-items: center;
    padding: .5rem .8rem; border: 1px solid var(--line-light); border-radius: 4px;
    background: var(--panel); color: var(--muted-light);
    font-size: .72rem; font-weight: 650; text-decoration: none; white-space: nowrap;
  }
  .emp-back-today:hover { border-color: var(--line-strong); color: var(--light-text); }

  /* ── ตารางรายวันทั้งเดือน — วันที่ · กะ · เวลาสแกน · OT · การลา ── */
  .emp-sheet { border: 1px solid var(--line-light); border-radius: 5px; background: var(--panel); overflow: hidden; }
  .emp-sheet-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
    padding: .8rem 1rem; border-bottom: 1px solid var(--line-light); background: var(--ot-tint-company);
  }
  .emp-sheet-head strong { font-size: .9rem; }
  .emp-sheet-head span { color: var(--muted-light); font-size: .72rem; }
  /* เลื่อนในกล่องนี้กล่องเดียว เปิดหน้ามาให้อยู่ที่วันนี้ แล้วเลื่อนขึ้นดูวันล่วงหน้าได้ */
  .emp-scroll { overflow: auto; max-height: min(72vh, 46rem); scroll-behavior: smooth; }
  .emp-table { width: 100%; min-width: 58rem; border-collapse: collapse; font-size: .76rem; }
  .emp-table th, .emp-table td {
    padding: .5rem .7rem; border-right: 1px solid var(--line-light); border-bottom: 1px solid var(--line-light);
    text-align: center; vertical-align: middle;
  }
  .emp-table th:last-child, .emp-table td:last-child { border-right: 0; }
  .emp-table th:first-child, .emp-table td:first-child { text-align: left; }
  /* หัวตารางค้างไว้ตอนเลื่อน ไม่งั้นเลื่อนขึ้นดูวันล่วงหน้าแล้วไม่รู้ว่าคอลัมน์ไหนคืออะไร */
  .emp-table th {
    position: sticky; top: 0; z-index: 2;
    color: var(--muted-light); font-size: .68rem; font-weight: 700; background: var(--panel-soft);
  }
  .emp-table tbody tr:last-child td { border-bottom: 0; }
  .emp-table tbody tr.is-today td { box-shadow: inset 0 0 0 9999px color-mix(in srgb, var(--moss) 8%, transparent); }
  .emp-table tbody tr:hover td { box-shadow: inset 0 0 0 9999px var(--hover-soft); }

  /* แถบสีแยกกลุ่มคอลัมน์ให้อ่านง่ายแบบเดียวกับใบสรุปของ HR */
  .col-shift { background: color-mix(in srgb, #e0a800 7%, transparent); }
  .col-time { background: color-mix(in srgb, #e0a800 10%, transparent); }
  .col-hours { background: color-mix(in srgb, #35a863 11%, transparent); }
  .col-ot { background: color-mix(in srgb, #3d7ecb 11%, transparent); }
  .col-leave { background: color-mix(in srgb, #e0a800 9%, transparent); }

  .emp-date { font-size: .78rem; font-weight: 650; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .emp-weekday { margin-left: .3rem; color: var(--muted-light); font-size: .72rem; white-space: nowrap; }
  .emp-shift-name { text-align: left; font-size: .72rem; line-height: 1.35; }
  .emp-time { font-variant-numeric: tabular-nums; white-space: nowrap; }
  .emp-num { font-weight: 650; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .emp-ot-add { color: #1d7a42; font-weight: 700; }

  /* ช่วงเวลาที่ขอ OT ใช้เขียวชุดเดียวกับป้าย OT เดิม · จำนวนชั่วโมงหรี่ลงเป็นข้อมูลรอง */
  .emp-ot-hours { display: inline-block; color: #1d7a42; font-weight: 700; white-space: nowrap; }
  .emp-ot-amount { display: block; margin-top: .1rem; font-size: .68rem; font-weight: 700; opacity: .82; }

  .emp-ot-type { font-size: .72rem; line-height: 1.35; color: #2d5c94; }

  .emp-leave-text { font-size: .72rem; line-height: 1.35; color: #7a5c00; }

  /* ช่องวันที่แบ่ง 2 คอลัมน์ตายตัว: [ป้ายวันนี้][วันที่ + ชื่อวัน]
     แถวที่ไม่ใช่วันนี้ซ่อนป้ายแต่ยังกินที่เท่าเดิม วันที่ทุกแถวจึงเรียงตรงเป็นเส้นเดียว */
  .col-date { width: 15rem; }
  td.col-date {
    display: grid;
    grid-template-columns: 3rem minmax(0, 1fr);
    align-items: center;
    gap: .5rem;
    padding-left: 1rem;
  }
  .emp-day { display: inline-flex; align-items: baseline; gap: .3rem; min-width: 0; }
  .emp-day .emp-weekday { margin-left: 0; }
  .emp-today-tag {
    justify-self: start;
    padding: .12rem .42rem; border-radius: 3px;
    background: color-mix(in srgb, var(--moss) 18%, transparent); color: var(--moss);
    font-size: .62rem; font-style: normal; font-weight: 700; white-space: nowrap;
  }
  /* ซ่อนแต่ยังกินที่ เพื่อให้คอลัมน์วันที่ของทุกแถวตรงกัน */
  .emp-today-tag[hidden] { display: block; visibility: hidden; }
  .emp-none { color: color-mix(in srgb, var(--muted-light) 70%, transparent); }


  @media (max-width: 860px) {
    .emp-month { margin-left: 0; width: 100%; }
  }
@endsection

@section('content')
  @php
    $avatarUrl = null;
    if ($employee->license_id) {
      $user = \App\Models\Insight\AppUser::where('id_thai_hash', $employee->license_id)->first(['profile_picture']);
      $avatarUrl = $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null;
    }
    $name_th = $employee->fullNameTh() ?: $employee->fullNameEn() ?: $employee->employee_code;
    $name_en = $employee->fullNameEn() ?: $name_th;
    $name_my = $name_en;
    $job_th = $employee->job_th ?: $employee->job_en ?: '-';
    $job_en = $employee->job_en ?: $job_th;
    $dept_th = $employee->deptThClean() ?: $employee->dept_en ?: '-';
    $dept_en = $employee->dept_en ?: $dept_th;

    /* รวม OT + การลาที่อนุมัติแล้วเป็น map รายวัน เพื่อวางลงแถวของตารางให้ตรงวัน */
    $days = [];

    /* 8.00 -> 8, 2.50 -> 2.5 ให้อ่านง่ายแบบที่ HR เขียนกันในใบสรุป */
    $trimHours = fn (float $value) => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

    foreach ($otRequests as $row) {
      $days[$row->work_date->format('Y-m-d')]['ot'] = [
        'hours' => $trimHours((int) $row->requested_hours + ((int) $row->requested_minutes / 60)),
        'label' => $row->otTypeLabel(),
        // ใช้จุดคั่นเวลาแบบที่ HR เขียนกัน เหมือนชื่อกะในระบบ (08.00-17.00 น.)
        'range' => ($row->requested_start_at?->format('H.i') ?: '-').'–'.($row->requested_end_at?->format('H.i') ?: '-'),
      ];
    }

    foreach ($leaveRequests as $row) {
      $days[$row->leave_date->format('Y-m-d')]['leave'] = [
        'type' => $row->leaveTypeLabel() ?: $row->leave_type,
      ];
    }

    $firstDay = $selectedMonth->startOfMonth();
    $daysInMonth = (int) $selectedMonth->format('t');
    $today = now()->format('Y-m-d');

    /* เปิดครบทุกวันของเดือนที่เลือก วันล่าสุดอยู่บนสุด — วันที่ยังไม่ถึงจะว่างไว้จนกว่าจะมีรายการ */
    $visibleDays = range($daysInMonth, 1);
    $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $thaiWeekdays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
  @endphp

  <main class="emp-page" data-employee-month data-page-url="{{ $pageUrl }}">
    <header class="emp-head">
      @if ($avatarUrl)
        {{-- data-leave-photo = ตัวจับของ partial photo-zoom กดแล้วเปิดภาพใหญ่ --}}
        <img class="emp-photo" src="{{ $avatarUrl }}" alt="{{ $name_th }}"
             data-leave-photo data-caption-name="{{ $name_th }}" data-caption-code="{{ $employee->employee_code }}"
             data-loc-th="{{ $name_th }}" data-loc-en="{{ $name_en }}" data-loc-my="{{ $name_my }}"
             data-loc-attr="alt,data-caption-name">
      @else
        <span class="emp-photo" data-emp-avatar-initial
              data-name-th="{{ $name_th }}" data-name-en="{{ $name_en }}" data-name-my="{{ $name_my }}">{{ mb_substr($name_th, 0, 1) }}</span>
      @endif
      <div class="emp-identity">
        <h1 data-loc-th="{{ $name_th }}" data-loc-en="{{ $name_en }}" data-loc-my="{{ $name_my }}">{{ $name_th }}</h1>
        <p>{{ $employee->employee_code }} · <span data-loc-th="{{ $job_th }}" data-loc-en="{{ $job_en }}">{{ $job_th }}</span> · <span data-loc-th="{{ $dept_th }}" data-loc-en="{{ $dept_en }}">{{ $dept_th }}</span></p>
      </div>
      {{-- ปฏิทินเลือกเดือนทำเอง ไม่ใช้ <input type="month">
           เพราะของเบราว์เซอร์เป็นช่องกรอก MM/YYYY ต้องพิมพ์เอง ไม่ใช่ปฏิทินให้กดเลือก --}}
      <div class="emp-month" data-emp-month-picker>
        <span class="sr-only" data-i18n="ot.downloads.month">เดือน</span>
        <button class="emp-month-button is-active" type="button" data-emp-month-toggle aria-haspopup="dialog" aria-expanded="false">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>
          <b data-emp-month-label>{{ $thaiMonths[(int) $selectedMonth->format('n')] }} {{ (int) $selectedMonth->format('Y') + 543 }}</b>
          <svg class="emp-month-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
        </button>

        <div class="emp-month-panel" data-emp-month-panel role="dialog" aria-label="เลือกเดือน" data-i18n-aria="emp.pickMonth" hidden>
          <header class="emp-month-nav">
            <button type="button" data-emp-year-prev aria-label="ปีก่อนหน้า" data-i18n-aria="emp.prevYear">‹</button>
            <b data-emp-year-label>—</b>
            <button type="button" data-emp-year-next aria-label="ปีถัดไป" data-i18n-aria="emp.nextYear">›</button>
          </header>
          <div class="emp-month-grid" data-emp-month-grid></div>
        </div>
      </div>
      @if ($selectedMonth->format('Y-m') !== now()->format('Y-m'))
        {{-- เลื่อนไปเดือนอื่นแล้วกลับเดือนปัจจุบันได้ในคลิกเดียว ไม่ต้องไล่กดปฏิทินกลับ --}}
        <a class="emp-back-today" href="{{ $pageUrl }}" data-i18n="emp.backToThisMonth">กลับเดือนนี้</a>
      @endif
    </header>

    <section class="emp-sheet">
      <header class="emp-sheet-head">
        <strong data-emp-sheet-month>{{ $thaiMonths[(int) $selectedMonth->format('n')] }} {{ (int) $selectedMonth->format('Y') + 543 }}</strong>
      </header>

      <div class="emp-scroll" data-emp-scroll>
        <table class="emp-table">
          <thead>
            <tr>
              <th class="col-date" data-i18n="emp.colDate">วันที่</th>
              <th class="col-shift" data-i18n="emp.colShiftCode">รหัสกะ</th>
              <th class="col-shift" data-i18n="emp.colShiftName">ชื่อกะ</th>
              <th class="col-time" data-i18n="emp.colIn">เวลาเข้า</th>
              <th class="col-time" data-i18n="emp.colOut">เวลาออก</th>
              <th class="col-hours" data-i18n="emp.colOtHours">ช่วงเวลาที่ขอ OT</th>
              <th class="col-ot" data-i18n="emp.colOt">OT</th>
              <th class="col-leave" data-i18n="emp.colLeave">การลา</th>
            </tr>
          </thead>
          <tbody>
            {{-- เรียงจากวันล่าสุดลงไป วันล่วงหน้าที่อนุมัติแล้วจะอยู่เหนือวันนี้ --}}
            @foreach ($visibleDays as $day)
              @php
                $cursor = $firstDay->addDays($day - 1);
                $date = $cursor->format('Y-m-d');
                $weekday = (int) $cursor->format('w');
                $entry = $days[$date] ?? [];
                $isFuture = $date > $today;

                /* กะและเวลาสแกนมาจาก snapshot — วันไหนยังไม่ซิงค์จะว่างทั้งแถว */
                $snapshot = $attendance[$date] ?? null;
                $clockIn = $snapshot?->clock_in ? substr((string) $snapshot->clock_in, 0, 5) : null;
                $clockOut = $snapshot?->clock_out ? substr((string) $snapshot->clock_out, 0, 5) : null;
              @endphp
              <tr class="{{ $date === $today ? 'is-today' : '' }}" @if ($date === $today) data-emp-today @endif>
                <td class="col-date">
                  {{-- ป้าย "วันนี้" อยู่ช่องซ้าย · วันที่กับชื่อวันห่อรวมกันอยู่ช่องขวา --}}
                  <em class="emp-today-tag" data-i18n="emp.today" @if ($date !== $today) hidden @endif>วันนี้</em>
                  <span class="emp-day">
                    <span class="emp-date" data-emp-date="{{ $date }}">{{ $cursor->format('d/m/') }}{{ (int) $cursor->format('Y') + 543 }}</span>
                    <span class="emp-weekday" data-emp-weekday="{{ $weekday }}">({{ $thaiWeekdays[$weekday] }})</span>
                  </span>
                </td>
                <td class="col-shift">{{ $snapshot?->shift_code ?: '' }}</td>
                {{-- ชื่อกะจาก Bplus ตรง ๆ เพราะกะเปลี่ยนรายวันรายคน และ Bplus แยก
                     วันงาน / วันหยุด / วันหยุดนักขัตฤกษ์ ไว้ในชื่ออยู่แล้ว
                     ห้ามอนุมานเองว่า "ไม่มีสแกน = วันหยุด" เพราะวันทำงานที่ลาหรือขาดก็ไม่มีสแกนเหมือนกัน --}}
                <td class="col-shift emp-shift-name" data-loc-th="{{ $snapshot?->shift_name_th ?: $snapshot?->shift_name_en ?: '' }}" data-loc-en="{{ $snapshot?->shift_name_en ?: $snapshot?->shift_name_th ?: '' }}">{{ $snapshot?->shift_name_th ?: $snapshot?->shift_name_en ?: '' }}</td>
                <td class="col-time emp-time">{{ $clockIn ?: '' }}</td>
                <td class="col-time emp-time">{{ $clockOut ?: '' }}</td>
                <td class="col-hours emp-num">
                  {{-- ช่วงเวลาที่ขอ OT พร้อมจำนวนชั่วโมงในวงเล็บ · วันที่ไม่มีคำขอปล่อยว่าง --}}
                  @isset($entry['ot'])
                    <span class="emp-ot-hours">{{ $entry['ot']['range'] }}
                      <small class="emp-ot-amount">(OT {{ $entry['ot']['hours'] }} <span data-i18n="ot.requests.hourShort">ชม.</span>)</small>
                    </span>
                  @endisset
                </td>
                <td class="col-ot emp-ot-type">
                  @isset($entry['ot'])
                    <span title="{{ $entry['ot']['range'] }}">{{ $entry['ot']['label'] }}</span>
                  @endisset
                </td>
                <td class="col-leave">
                  @isset($entry['leave'])
                    <span class="emp-leave-text">{{ $entry['leave']['type'] }}</span>
                  @endisset
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

    </section>
  </main>

  @include('ot_approval.partials.photo-zoom')
@endsection

@section('page-script')
  <script>
    (function employeeMonth() {
      'use strict';

      var root = document.querySelector('[data-employee-month]');
      if (!root) return;

      /* เปิดหน้ามาให้แถว "วันนี้" อยู่บนสุดของกล่อง วันล่วงหน้าอยู่เหนือขึ้นไปรอให้เลื่อนหา
         ปิด smooth ชั่วคราว ไม่งั้นผู้ใช้จะเห็นตารางไถลตอนโหลด */
      var scroller = root.querySelector('[data-emp-scroll]');
      var todayRow = root.querySelector('[data-emp-today]');
      if (scroller && todayRow) {
        var head = scroller.querySelector('thead');
        var behavior = scroller.style.scrollBehavior;
        scroller.style.scrollBehavior = 'auto';
        scroller.scrollTop = todayRow.offsetTop - (head ? head.offsetHeight : 0);
        scroller.style.scrollBehavior = behavior;
      }

      /* ── ปฏิทินเลือกเดือน ────────────────────────────────────────
         เลื่อนปีด้วย ‹ › แล้วกดชื่อเดือนเพื่อไปเดือนนั้น (โหลดหน้าใหม่ ข้อมูลมาจาก server ทั้งหมด)
         ปีที่แสดงเป็น พ.ศ. ให้ตรงกับตารางด้านล่างที่ใช้ พ.ศ. เหมือนกัน */
      var picker = root.querySelector('[data-emp-month-picker]');
      var toggle = picker.querySelector('[data-emp-month-toggle]');
      var panel = picker.querySelector('[data-emp-month-panel]');
      var grid = picker.querySelector('[data-emp-month-grid]');
      var yearLabel = picker.querySelector('[data-emp-year-label]');

      var selected = @json($selectedMonth->format('Y-m'));
      var thisMonth = @json(now()->format('Y-m'));
      var viewYear = Number(selected.slice(0, 4));

      function activeLanguage() {
        return document.documentElement.getAttribute('data-lang') || 'th';
      }

      function dictionary() {
        return (window.__portalCopy && window.__portalCopy[activeLanguage()]) || {};
      }

      function displayYear(year) {
        return activeLanguage() === 'th' ? year + 543 : year;
      }

      function monthName(month) {
        return dictionary()['a5s.month.' + month] || @json($thaiMonths)[month];
      }

      function weekdayName(weekday) {
        var keys = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        return dictionary()['ot.calendar.' + keys[weekday]] || @json($thaiWeekdays)[weekday];
      }

      function selectedMonthLabel() {
        return monthName(Number(selected.slice(5, 7))) + ' ' + displayYear(Number(selected.slice(0, 4)));
      }

      function paintLocalizedPage() {
        var lang = activeLanguage();
        var avatar = root.querySelector('[data-emp-avatar-initial]');
        if (avatar) {
          var name = avatar.getAttribute('data-name-' + lang) || avatar.dataset.nameEn || avatar.dataset.nameTh || '';
          avatar.textContent = Array.from(name.trim())[0] || '—';
        }
        var monthLabel = root.querySelector('[data-emp-month-label]');
        var sheetMonth = root.querySelector('[data-emp-sheet-month]');
        if (monthLabel) monthLabel.textContent = selectedMonthLabel();
        if (sheetMonth) sheetMonth.textContent = selectedMonthLabel();
        root.querySelectorAll('[data-emp-date]').forEach(function (node) {
          var parts = node.dataset.empDate.split('-');
          node.textContent = parts[2] + '/' + parts[1] + '/' + displayYear(Number(parts[0]));
        });
        root.querySelectorAll('[data-emp-weekday]').forEach(function (node) {
          node.textContent = '(' + weekdayName(Number(node.dataset.empWeekday)) + ')';
        });
        if (!panel.hidden) paintPanel();
      }

      function paintPanel() {
        yearLabel.textContent = displayYear(viewYear);
        grid.replaceChildren();

        for (var month = 1; month <= 12; month++) {
          var value = viewYear + '-' + String(month).padStart(2, '0');
          var button = document.createElement('button');
          button.type = 'button';
          button.textContent = monthName(month);
          if (value === selected) button.classList.add('is-selected');
          else if (value === thisMonth) button.classList.add('is-current');
          button.addEventListener('click', function (target) {
            window.location.assign(root.dataset.pageUrl + '?month=' + encodeURIComponent(target));
          }.bind(null, value));
          grid.appendChild(button);
        }
      }

      function openPanel(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
          viewYear = Number(selected.slice(0, 4));
          paintPanel();
        }
      }

      toggle.addEventListener('click', function () { openPanel(panel.hidden); });
      picker.querySelector('[data-emp-year-prev]').addEventListener('click', function () { viewYear -= 1; paintPanel(); });
      picker.querySelector('[data-emp-year-next]').addEventListener('click', function () { viewYear += 1; paintPanel(); });
      document.addEventListener('insight:languagechange', paintLocalizedPage);
      paintLocalizedPage();

      // กดที่อื่นหรือกด Escape ให้ปิดแผง
      document.addEventListener('click', function (event) {
        if (!panel.hidden && !picker.contains(event.target)) openPanel(false);
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) { openPanel(false); toggle.focus(); }
      });
    })();
  </script>
@endsection
