@extends('layouts.portal')

@section('title', 'Time & Leave Approval')
@section('topbar-title')<span data-i18n="ot.attendance.title">เวลาเข้า–ออกงาน</span>@endsection

{{-- สถานะแหล่งข้อมูล Bplus แสดงใต้เมนูซ้าย เหนือบรรทัด SUPAVUT INSIGHT --}}
@section('side-foot-extra')
  <div class="ot-source-state" data-source-state aria-live="polite">
    <span class="ot-source-dot" aria-hidden="true"></span>
    <span data-source-copy>{{ match ($attendance['source_mode'] ?? 'bplus') {
      'local' => 'Local demo · ข้อมูลจำลอง',
      'snapshot' => 'Bplus · Snapshot ใน Local',
      default => $selectedDate->isToday() ? 'Bplus · อัปเดตอัตโนมัติ' : 'Bplus · ข้อมูลย้อนหลัง',
    } }}</span>
  </div>
@endsection

@section('content')
  @php
    $attendanceCompanies = collect($attendance['companies'] ?? []);
    $attendanceTotals = [
      'total' => $attendanceCompanies->sum('total'),
      'clocked_in' => $attendanceCompanies->sum('clocked_in'),
      'clocked_out' => $attendanceCompanies->sum('clocked_out'),
      'ot_requested' => $attendanceCompanies->sum('ot_requested'),
      'completed' => $attendanceCompanies->sum('completed'),
      /* ยอดกำลังพลรายกะของทุกบริษัทรวมกัน — service นับมาให้ระดับบริษัทแล้ว
         คีย์ต้องเป็น path จริงของ payload เพราะ JS ใช้คีย์เดียวกันตอนรีเฟรช */
      'shifts.morning.total' => $attendanceCompanies->sum(fn (array $company) => $company['shifts']['morning']['total'] ?? 0),
      'shifts.morning.clocked_in' => $attendanceCompanies->sum(fn (array $company) => $company['shifts']['morning']['clocked_in'] ?? 0),
      'shifts.night.total' => $attendanceCompanies->sum(fn (array $company) => $company['shifts']['night']['total'] ?? 0),
      'shifts.night.clocked_in' => $attendanceCompanies->sum(fn (array $company) => $company['shifts']['night']['clocked_in'] ?? 0),
    ];
  @endphp

  <style>
    .ot-attendance,
    .ot-attendance-modal {
      --ot-status-warning: #8a6200;
      --ot-status-warning-border: #e0a51c;
      --ot-status-success: #1d7a42;
      --ot-status-success-border: #35a863;
      --ot-status-danger: #9f2f26;
      --ot-status-danger-border: #da5a4e;
    }
    .ot-attendance {
      width: min(100%, 92rem);
      margin: 0 auto;
    }

    .ot-attendance-head {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      margin-bottom: 1.15rem;
    }
    .ot-attendance-heading { max-width: 46rem; }
    .ot-attendance-kicker {
      color: var(--moss);
      font-size: .7rem;
      font-weight: 800;
      letter-spacing: .13em;
    }
    .ot-attendance-heading h1 {
      margin-top: .2rem;
      font-size: clamp(1.35rem, 2.4vw, 2rem);
      font-weight: 600;
      line-height: 1.25;
    }
    .ot-attendance-heading p {
      margin-top: .35rem;
      color: var(--muted-light);
      font-size: .86rem;
      line-height: 1.65;
    }
    .ot-head-side {
      display: grid;
      justify-items: end;
      gap: .5rem;
      min-width: min(100%, 35rem);
    }
    /* อยู่ใต้เมนูซ้าย เหนือบรรทัด SUPAVUT INSIGHT */
    .ot-source-state {
      display: flex;
      align-items: center;
      gap: .5rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--line-light);
      color: var(--muted-light);
      font-size: .7rem;
      line-height: 1.35;
    }
    .side-foot { margin-top: .55rem; padding-top: .55rem; border-top: 0; }
    .ot-source-dot {
      width: .52rem;
      height: .52rem;
      border-radius: 50%;
      background: var(--success);
      box-shadow: 0 0 0 .25rem color-mix(in srgb, var(--success) 14%, transparent);
    }
    .ot-source-state.is-refreshing .ot-source-dot { animation: ot-pulse 1s ease-in-out infinite; }

    /* ไม่ทำเป็นการ์ด วางชิดซ้ายเหนือรายการบริษัท */
    .ot-date-panel {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      flex-wrap: wrap;
      gap: .5rem;
      margin-bottom: 1rem;
    }
    .ot-date-picker {
      position: relative;
      display: flex;
      align-items: center;
      gap: .45rem;
      flex: 0 0 auto;
      color: var(--muted-light);
    }
    .ot-date-picker > span:not(.sr-only) { font-size: .74rem; font-weight: 600; }
    .ot-date-picker svg { width: 1.05rem; fill: none; stroke: currentColor; stroke-width: 1.7; }
    .ot-date-picker input {
      width: 8.9rem;
      padding: .45rem .55rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel-soft);
      color: var(--light-text);
      color-scheme: light dark;
    }
    /* ซ่อนลูกศรขึ้น-ลงที่เบราว์เซอร์วาดให้ input[type=date] เอง */
    .ot-date-picker input::-webkit-inner-spin-button,
    .ot-date-picker input::-webkit-outer-spin-button {
      -webkit-appearance: none;
      appearance: none;
      margin: 0;
    }
    .ot-export-form { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; flex-wrap: wrap; }
    .ot-export-select {
      min-height: 2.25rem;
      padding: .45rem 2rem .45rem .65rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel-soft);
      color: var(--light-text);
      font-size: .72rem;
      font-weight: 600;
    }
    .ot-export-month {
      min-height: 2.25rem;
      padding: .4rem .6rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel-soft);
      color: var(--light-text);
      font-size: .72rem;
    }

    /* ห้ามใส่ overflow ที่นี่ เพราะ overflow-x: auto จะทำให้แกน Y กลายเป็น auto ตามสเปก
       แล้วเกิดแถบเลื่อนแนวตั้ง (ลูกศรขึ้น-ลง) โผล่ทางขวา — ใช้ wrap ลงบรรทัดแทน */
    .ot-summary-strip {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: flex-end;
      gap: .5rem .9rem;
      width: 100%;
    }
    .ot-dept-filter { position: relative; display: inline-flex; align-items: center; flex: 0 0 auto; }
    .ot-dept-filter svg { position: absolute; left: .55rem; width: .95rem; height: .95rem; color: var(--muted-light); fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; pointer-events: none; }
    .ot-dept-filter select { min-height: 2.4rem; width: 9.5rem; padding: .4rem 1.7rem .4rem 1.95rem; border: 1px solid var(--line-strong); border-radius: .3rem; background: var(--panel); color: var(--light-text); font-family: inherit; font-size: .74rem; font-weight: 650; cursor: var(--cursor-action); appearance: none; text-overflow: ellipsis; }
    .ot-dept-filter::after { content: ''; position: absolute; right: .6rem; width: .4rem; height: .4rem; border-right: 1.6px solid var(--muted-light); border-bottom: 1.6px solid var(--muted-light); transform: translateY(-.12rem) rotate(45deg); pointer-events: none; }

    /* พื้นเหลืองของคอลัมน์เวลาเข้า-ออก ใช้ชุดเดียวกับหน้ารายละเอียดการทำงาน
       (#e0a800 10%) เพื่อให้ทุกหน้าที่มีคอลัมน์สแกนเข้า-ออกอ่านเหมือนกัน */
    .ot-employee-row > .ot-table-cell.is-clock,
    .ot-employee-head > .ot-table-cell.is-clock { background: color-mix(in srgb, #e0a800 10%, transparent); }
    /* คอลัมน์ลำดับ — เลขจาง ๆ ไม่ให้แย่งสายตาจากชื่อพนักงาน */
    .ot-employee-head > .ot-table-cell.is-order,
    .ot-employee-row > .ot-table-cell.is-order { color: var(--muted-light); font-variant-numeric: tabular-nums; justify-content: center; text-align: center; }

    /* หัวคอลัมน์ที่กรองได้ + เมนูติ๊กเลือกค่าแบบ Excel */
    .ot-employee-head > .ot-table-cell.is-filterable { position: relative; }
    /* ไอคอนอยู่มุมขวาล่างของหัวคอลัมน์ ไม่มีกรอบ (Manager สั่ง)
       วางแบบ absolute จึงไม่ไปดันข้อความหัวคอลัมน์ให้เยื้อง */
    .ot-col-filter {
      position: absolute; right: .2rem; bottom: .1rem;
      width: 1.1rem; height: 1.1rem;
      display: grid; place-items: center;
      border: 0; background: transparent;
      color: var(--muted-light); cursor: var(--cursor-action);
    }
    .ot-col-filter svg { width: .78rem; height: .78rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linejoin: round; }
    .ot-col-filter:hover { color: var(--light-text); }
    /* เข้มขึ้นเมื่อมีการกรองอยู่ ผู้ใช้จะได้รู้ว่าตารางไม่ได้แสดงครบ */
    .ot-col-filter.is-active { color: var(--moss); }
    .ot-col-menu {
      position: absolute; z-index: 6; top: calc(100% + .2rem); right: 0;
      min-width: 11rem; max-height: 16rem; overflow-y: auto;
      display: grid; gap: .1rem; padding: .4rem;
      border: 1px solid var(--line-strong); border-radius: .3rem;
      background: var(--panel); box-shadow: 0 .7rem 1.6rem rgb(0 0 0 / 14%);
      text-align: left; font-weight: 500;
    }
    .ot-col-menu[hidden] { display: none; }
    .ot-col-menu label { display: flex; align-items: center; gap: .45rem; padding: .3rem .4rem; border-radius: .2rem; cursor: var(--cursor-action); font-size: .74rem; }
    .ot-col-menu label:hover { background: var(--hover-soft); }
    .ot-col-menu input { accent-color: var(--moss); }
    .ot-col-actions { display: grid; grid-template-columns: 1fr auto; gap: .4rem; align-items: center; margin-top: .3rem; padding-top: .35rem; border-top: 1px solid var(--line-light); }
    .ot-col-clear { padding: .3rem; border: 0; background: transparent; color: var(--muted-light); cursor: var(--cursor-action); font-family: inherit; font-size: .72rem; font-weight: 600; text-align: left; }
    .ot-col-clear:hover { color: var(--light-text); }
    .ot-col-apply { padding: .32rem .8rem; border: 1px solid var(--moss); border-radius: .25rem; background: var(--moss); color: #fff; cursor: var(--cursor-action); font-family: inherit; font-size: .72rem; font-weight: 700; }

    /* สถานะการทำงาน — ข้อความล้วน ไม่ระบายพื้น (Manager สั่ง)
       วันหยุดใช้สีจางไว้ เพราะไม่ใช่วันทำงานจึงไม่ควรเด่นเท่าสองค่าแรก */
    .ot-presence { font-size: .74rem; font-weight: 650; }
    .ot-presence.is-present { color: #146032; }
    .ot-presence.is-absent { color: #9f2f26; }
    .ot-presence.is-dayoff { color: var(--muted-light); font-weight: 500; }

    /* คอลัมน์กะงานระบายสีเต็มช่อง ตัวอักษรคงสีเดิมเพื่อให้อ่านง่าย
       (กะเวลา A ส้ม · กะเวลา B ม่วง ชุดเดียวกับไอคอนบนการ์ดแผนก) */
    .ot-employee-row > .ot-table-cell.is-shift-morning { background: color-mix(in srgb, #e8830c 30%, transparent); }
    .ot-employee-row > .ot-table-cell.is-shift-night { background: color-mix(in srgb, #6d28d9 26%, transparent); }

    /* ปุ่มนาฬิกาเปิดสรุปตัวเลข — แทนแถบยาวที่เคยกินที่ตลอดเวลา */
    .ot-summary-open {
      width: 2.4rem; height: 2.4rem; flex: 0 0 auto;
      display: grid; place-items: center;
      border: 1px solid var(--line-strong); border-radius: 0.3rem;
      background: var(--panel); color: var(--muted-light);
      cursor: var(--cursor-action);
      transition: border-color .18s var(--ease-out), color .18s var(--ease-out), background-color .18s var(--ease-out);
    }
    .ot-summary-open:hover { border-color: var(--moss); background: var(--hover-soft); color: var(--moss); }
    .ot-summary-open svg { width: 1.15rem; height: 1.15rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

    .ot-summary-modal { position: fixed; inset: 0; z-index: 1200; display: grid; place-items: center; padding: 1rem; background: var(--overlay-bg); }
    .ot-summary-modal[hidden] { display: none; }
    .ot-summary-dialog { width: min(28rem, 100%); border: 1px solid var(--line-light); border-radius: .4rem; background: var(--panel); box-shadow: 0 24px 70px rgb(0 0 0 / 22%); }
    .ot-summary-dialog-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 1.1rem; border-bottom: 1px solid var(--line-light); }
    .ot-summary-dialog-head strong { font-size: .95rem; }
    .ot-summary-close { width: 2.1rem; height: 2.1rem; border: 1px solid var(--line-light); border-radius: .3rem; background: transparent; color: inherit; cursor: var(--cursor-action); font-size: 1.1rem; line-height: 1; }
    /* จัดสรุปในโมดัลเป็นแนวตั้ง: หมวดเรียงลงมา · ในหมวดเป็นตาราง ป้าย | ตัวเลข
       (สไตล์แถบแนวนอนเดิมออกแบบมาสำหรับหัวหน้าเพจ พอมาอยู่ในกล่องแล้วอ่านยาก) */
    .ot-summary-dialog .ot-summary-strip {
      display: grid;
      gap: 1.1rem;
      /* จำกัดความกว้างแล้วดันเข้ากลาง ทั้งบล็อกจึงอยู่กึ่งกลางการ์ด */
      width: min(22rem, 100%);
      margin: 0 auto;
      padding: 1.2rem 1.1rem;
      /* ต้องล้าง justify-content: flex-end ของแถบบนหัวเพจด้วย ไม่งั้นพอกลายเป็น grid
         คอลัมน์จะหดเท่าเนื้อหาแล้วไปกองชิดขวาการ์ด — ดูเหมือนสรุปไม่ได้อยู่กลางโมดัล */
      justify-content: stretch;
      justify-items: stretch;
    }
    .ot-summary-dialog .ot-summary-group {
      min-width: 0; padding: 0; border-right: 0;
      border-bottom: 1px solid var(--line-light); padding-bottom: 1rem;
    }
    .ot-summary-dialog .ot-summary-group:last-child { border-bottom: 0; padding-bottom: 0; }
    .ot-summary-dialog .ot-summary-group-title { margin-bottom: .45rem; font-size: .62rem; }
    .ot-summary-dialog .ot-summary-group-items { display: grid; gap: 0; }
    .ot-summary-dialog .ot-summary-group-items > .ot-summary-item:last-child { border-bottom: 0; }
    .ot-summary-dialog .ot-summary-item {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: baseline;
      gap: 1rem;
      /* ปลด min-width: max-content ของแถบบนหัวเพจ ไม่งั้นช่องไม่ยอมหด */
      min-width: 0;
      padding: .38rem .2rem;
      border-bottom: 1px dashed var(--line-light);
    }
    .ot-summary-dialog .ot-summary-group-items > .ot-summary-item:last-child { border-bottom: 0; }
    .ot-summary-dialog .ot-summary-item > span { font-size: .78rem; }
    .ot-summary-dialog .ot-summary-item strong { margin-top: 0; font-size: .95rem; text-align: right; }

    /* ตัวเลขรายบริษัทถูกย้ายเข้ามาแสดงในโมดัลตัวเดียวกัน จึงต้องจัดทรงให้เหมือนสรุปรวม
       คือบล็อกกึ่งกลางการ์ด แต่ละบรรทัดเป็น ป้ายซ้าย–ตัวเลขขวา ไม่ใช่แถวนอนแบบบนหัวบริษัท */
    .ot-summary-dialog .ot-company-numbers {
      display: grid; gap: 0;
      width: min(22rem, 100%); margin: 0 auto; padding: 1.2rem 1.1rem;
      justify-content: stretch; justify-items: stretch;
    }
    .ot-summary-dialog .ot-company-numbers > span {
      display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: baseline; gap: 1rem;
      min-width: 0; padding: .38rem .2rem; border-bottom: 1px dashed var(--line-light);
    }
    .ot-summary-dialog .ot-company-numbers > span:last-child { border-bottom: 0; }
    .ot-summary-dialog .ot-company-numbers small { font-size: .78rem; }
    .ot-summary-dialog .ot-company-numbers b { font-size: .95rem; text-align: right; }

    /* จัดเป็นหมวดแทนแถวยาวแถวเดียว — เส้นคั่นอยู่ระหว่างหมวดเท่านั้น ไม่คั่นทุกตัวเลข
       และไม่มีกรอบล้อมตามที่ Manager สั่ง หัวหมวดจาง ๆ ทำหน้าที่แบ่งสายตาแทน */
    .ot-summary-group { min-width: max-content; padding-right: .95rem; border-right: 1px solid var(--line-light); }
    .ot-summary-group:last-child { padding-right: 0; border-right: 0; }
    .ot-summary-group-title {
      margin: 0 0 .22rem;
      color: var(--muted-light);
      font-size: .58rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .ot-summary-group-items { display: flex; align-items: flex-start; gap: .85rem; }
    .ot-summary-item { min-width: max-content; }
    .ot-summary-item > span { display: flex; align-items: center; gap: .22rem; color: var(--muted-light); font-size: .63rem; line-height: 1.2; white-space: nowrap; }
    .ot-summary-item svg { width: .78rem; height: .78rem; flex: 0 0 auto; color: var(--summary-tone, currentColor); fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .ot-summary-item.is-morning { --summary-tone: #e8830c; }
    .ot-summary-item.is-night { --summary-tone: #6d28d9; }

    .ot-summary-item strong { display: block; margin-top: .08rem; font-size: .92rem; font-weight: 600; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .ot-summary-item strong i { color: var(--muted-light); font-size: .72rem; font-style: normal; font-weight: 500; }

    /* โทนพาสเทลใช้ token กลางจาก layouts/portal (--ot-tint-*) จึงตรงกันทุกหน้า OT */
    .ot-company-list { display: grid; gap: .75rem; }
    .ot-company {
      border: 1px solid var(--line-light);
      border-radius: 5px;
      background: var(--panel);
      overflow: clip;
    }
    .ot-company > summary {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto auto;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.1rem;
      background: var(--ot-tint-company);
      list-style: none;
      transition: background .16s ease;
    }
    .ot-company > summary:hover { background: color-mix(in srgb, var(--moss) 12%, var(--panel)); }
    .ot-company > summary::-webkit-details-marker { display: none; }
    .ot-company-name { min-width: 0; }
    .ot-company-name strong { display: block; overflow: hidden; font-size: .95rem; text-overflow: ellipsis; white-space: nowrap; }
    .ot-company-name small { display: block; margin-top: .1rem; color: var(--muted-light); font-size: .7rem; }
    /* ป้ายบน–เลขล่าง จับคู่กันในแนวตั้ง และคอลัมน์กว้างเท่ากันทุกช่อง
       ทั้ง 2 บริษัทจึงเรียงตรงกันเป็นตาราง กวาดสายตาลงล่างเทียบกันได้ */
    .ot-company-numbers { display: flex; align-items: flex-start; gap: 1.1rem; color: var(--muted-light); font-size: .72rem; }
    .ot-company-numbers > span { display: grid; gap: .1rem; min-width: 4.6rem; }
    .ot-company-numbers small { display: inline-flex; align-items: center; gap: .22rem; font-size: .61rem; line-height: 1.2; white-space: nowrap; }
    .ot-company-numbers b { color: var(--light-text); font-size: .9rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .ot-company-numbers i { color: var(--muted-light); font-size: .7rem; font-style: normal; font-weight: 500; }
    .ot-company-numbers svg { width: .78rem; height: .78rem; flex: 0 0 auto; color: var(--company-tone, currentColor); fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .ot-company-numbers .is-morning { --company-tone: #e8830c; }
    .ot-company-numbers .is-night { --company-tone: #6d28d9; }

    .ot-company-chevron { width: 1rem; fill: none; stroke: var(--muted-light); stroke-width: 1.8; transition: transform .18s ease; }
    .ot-company[open] .ot-company-chevron { transform: rotate(180deg); }
    .ot-company-body { padding: .2rem 1rem 1rem; border-top: 1px solid var(--line-light); }
    .ot-company-error {
      margin-top: .8rem;
      padding: .7rem .8rem;
      border: 1px solid color-mix(in srgb, var(--danger) 38%, transparent);
      border-radius: 4px;
      color: var(--danger);
      font-size: .78rem;
    }
    .ot-department-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: .65rem;
      margin-top: .8rem;
    }
    /* ── การ์ดแผนก ────────────────────────────────────────────────
       อ่านจากบนลงล่าง: ชื่อแผนก → ยอดรวมเข้า/ออก → แยกกะด้วยดวงอาทิตย์/พระจันทร์
       ตัวเลขเป็น "สแกนแล้ว/ทั้งหมด" เสมอ จึงรู้ทันทีว่ายังขาดกี่คนโดยไม่ต้องคิดเลข */
    .ot-department-card {
      position: relative;
      display: grid;
      gap: .5rem;
      padding: .85rem .95rem .8rem;
      border: 1px solid var(--line-light);
      border-radius: 5px;
      background: var(--ot-tint-card);
      box-shadow: 0 1px 2px rgb(0 0 0 / 4%);
      transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    /* ต้องประกาศคู่กับ display:grid ด้านบน ไม่งั้น [hidden] ที่ JS ตั้งจะไม่มีผล
       (hidden ให้ display:none ด้วย specificity ต่ำกว่า class selector) */
    .ot-department-card[hidden] { display: none; }
    .ot-company[hidden] { display: none; }

    .ot-department-card:hover {
      transform: translateY(-2px);
      border-color: color-mix(in srgb, var(--moss) 45%, var(--line-light));
      background: var(--ot-tint-card-hover);
      box-shadow: 0 .6rem 1.4rem rgb(0 0 0 / 8%);
    }
    .ot-department-card:focus-within { border-color: var(--moss); }

    /* ปุ่มเปิดรายชื่อกินพื้นที่ทั้งการ์ด ยกเว้นแถบกะที่ซ้อนอยู่ด้านบน จึงกดตรงไหนก็เข้าแผนกได้ */
    .ot-department-main {
      position: absolute;
      inset: 0;
      z-index: 0;
      width: 100%;
      border: 0;
      border-radius: 4px;
      background: transparent;
      cursor: pointer;
    }
    .ot-department-main:focus-visible { outline: 2px solid var(--moss); outline-offset: 2px; }
    /* ชิดซ้ายทั้งใบ — ชื่อแผนกเป็นรหัสอย่าง MFG/IM1 ไล่สายตาลงคอลัมน์เดียวได้เร็วกว่าจัดกึ่งกลาง
       ขีดสั้นใต้ชื่อทำหน้าที่เป็นหัวเรื่อง แยกชื่อแผนกออกจากตัวเลขโดยไม่ต้องมีเส้นคั่นเต็มใบ */
    .ot-department-title { position: relative; z-index: 1; min-width: 0; padding-right: 1.4rem; pointer-events: none; }
    .ot-department-title strong { display: block; overflow: hidden; font-size: .95rem; font-weight: 700; letter-spacing: .01em; text-overflow: ellipsis; white-space: nowrap; }
    .ot-department-title::after {
      content: '';
      display: block;
      width: 70%;
      height: 3px;
      margin-top: .4rem;
      border-radius: 999px;
      background: var(--moss);
      opacity: .85;
      transition: width .18s ease;
    }
    .ot-department-card:hover .ot-department-title::after { width: 100%; }

    /* กระดานตัวเลขของการ์ด — ทุกแถวใช้ grid ชุดเดียวกัน เลข "เข้า" กับ "ออก"
       จึงตรงคอลัมน์กันหมดตั้งแต่แถวรวมลงไปถึงแถวกะ กวาดสายตาลงล่างได้ทันที
       คอลัมน์ตัวเลขกว้างคงที่ ไม่ให้ความยาวของเลขไปดันคอลัมน์ให้เหลื่อมกันระหว่างการ์ด */
    /* กระดานปล่อยคลิกทะลุไปโดนปุ่มเปิดแผนกที่ปูอยู่ใต้การ์ด ยกเว้นแถวกะที่รับคลิกเอง
       ไม่งั้นช่องไฟระหว่างแถวจะกลายเป็นจุดตายที่กดแล้วไม่มีอะไรเกิดขึ้น */
    .ot-department-board { position: relative; z-index: 1; display: grid; gap: .12rem; pointer-events: none; }
    .ot-board-head,
    .ot-board-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 3.5rem 3.5rem;
      align-items: center;
      gap: .3rem;
      text-align: left;
    }
    .ot-board-head {
      padding: 0 .1rem .18rem;
      color: var(--muted-light);
      font-size: .63rem;
      font-weight: 700;
      letter-spacing: .04em;
      text-transform: uppercase;
    }
    .ot-board-head span + span { text-align: right; }

    /* แถวรวมของแผนกเป็นตัวเลขหลักของการ์ด จึงตัวใหญ่กว่าและมีเส้นคั่นก่อนเข้าแถวกะ */
    .ot-board-row.is-total {
      padding: .1rem .1rem .38rem;
      border-bottom: 1px solid var(--line-light);
      margin-bottom: .18rem;
      pointer-events: none;
    }
    .ot-board-row.is-total .ot-board-label { color: var(--muted-light); font-size: .7rem; font-weight: 650; }
    .ot-board-row.is-total .ot-board-metric b { font-size: 1.05rem; }

    .ot-board-label { display: inline-flex; align-items: center; gap: .32rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ot-board-metric { display: inline-flex; align-items: baseline; justify-content: flex-end; gap: .04rem; font-variant-numeric: tabular-nums; }
    .ot-board-metric b { font-size: .86rem; font-weight: 700; line-height: 1.1; }
    .ot-board-metric i { color: var(--muted-light); font-size: .7rem; font-style: normal; }
    .ot-board-metric.is-plain i { display: none; }

    /* แถวกะไม่มีกรอบ — ปล่อยให้ไอคอนดวงอาทิตย์/พระจันทร์เป็นตัวแยกกะแทนกล่องสี */
    .ot-shift-pill {
      --pill-tone: var(--line-strong);
      padding: .22rem .1rem;
      border: 0;
      border-radius: 4px;
      background: transparent;
      color: var(--light-text);
      cursor: pointer;
      pointer-events: auto;
      font-size: .76rem;
      font-family: inherit;
    }
    .ot-shift-pill.is-unknown { pointer-events: none; }
    .ot-shift-pill:hover { background: color-mix(in srgb, var(--pill-tone) 10%, transparent); color: var(--pill-tone); }
    .ot-shift-pill:focus-visible { outline: 2px solid var(--moss); outline-offset: 2px; }
    .ot-shift-pill[aria-expanded="true"] { color: var(--pill-tone); }
    .ot-shift-pill[aria-expanded="true"] svg { transform: scale(1.12); }
    .ot-shift-pill svg { width: .9rem; height: .9rem; flex: 0 0 auto; color: var(--pill-tone); fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; transition: transform .16s ease; }
    .ot-shift-pill b { font-weight: 700; }
    .ot-shift-pill i { color: var(--muted-light); font-style: normal; }
    .ot-shift-pill.is-morning { --pill-tone: #e8830c; }
    .ot-shift-pill.is-night { --pill-tone: #6d28d9; }

    .ot-shift-pill.is-unknown { --pill-tone: var(--muted-light); color: var(--muted-light); cursor: default; font-size: .72rem; }
    .ot-shift-pill.is-unknown:hover { background: transparent; }
    .ot-shift-pill[hidden] { display: none; }

    /* ลูกศรมุมขวาบน = สัญญาณว่าการ์ดกดเข้าไปดูรายชื่อได้ */
    .ot-department-corner {
      position: absolute;
      top: .55rem;
      right: .6rem;
      z-index: 1;
      display: grid;
      place-items: center;
      color: var(--muted-light);
      opacity: .55;
      pointer-events: none;
      transition: opacity .16s ease, color .16s ease, transform .16s ease;
    }
    .ot-department-corner svg { width: .95rem; height: .95rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .ot-department-card:hover .ot-department-corner,
    .ot-department-card:focus-within .ot-department-corner { color: var(--moss); opacity: 1; transform: translateX(2px); }

    /* รายละเอียดของกะที่กด — เปิดเฉพาะเมื่อผู้ใช้อยากรู้ ไม่ยัดทุกตัวเลขลงการ์ด */
    .ot-shift-note {
      position: relative;
      z-index: 1;
      margin: -.15rem 0 0;
      color: var(--muted-light);
      font-size: .68rem;
      font-variant-numeric: tabular-nums;
    }
    .ot-shift-note[hidden] { display: none; }
    .ot-shift-note b { color: var(--light-text); font-weight: 700; }

    .ot-empty {
      padding: 2rem 1rem;
      border: 1px dashed var(--line-strong);
      border-radius: 4px;
      color: var(--muted-light);
      text-align: center;
    }

    .ot-attendance-modal {
      position: fixed;
      z-index: 90;
      inset: 0;
      display: grid;
      place-items: center;
      padding: .5rem;
      background: var(--overlay-bg);
      -webkit-backdrop-filter: blur(4px);
      backdrop-filter: blur(4px);
    }
    .ot-attendance-modal[hidden] { display: none; }
    .ot-attendance-dialog {
      /* กางเต็มจอ (Manager สั่ง) — ตารางมี 10 คอลัมน์ ถ้าจำกัดที่ 86rem จะต้องเลื่อนดูตลอด */
      width: 100%;
      max-height: 96vh;
      max-height: 96dvh;
      display: flex;
      flex-direction: column;
      border: 1px solid var(--line-strong);
      border-radius: 4px;
      background: var(--menu-bg);
      box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 32%);
      overflow: hidden;
    }
    .ot-modal-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.1rem;
      border-bottom: 1px solid var(--line-strong);
    }
    .ot-modal-heading { min-width: 0; }
    /* หัวเรื่องอยู่ซ้าย · ตัวกรองทั้งหมด (ค้นหา · วันที่ · เรียง · กะ · ปิด) อยู่บรรทัดเดียวกันฝั่งขวา */
    .ot-modal-heading { min-width: 0; }
    .ot-modal-heading h2 { white-space: nowrap; }
    /* ไม่ให้ห่อบรรทัด ตัวกรองจะได้เรียงต่อกันแถวเดียวเสมอ */
    .ot-modal-tools { flex-wrap: nowrap; }
    .ot-modal-tools .ot-emp-search { flex: 0 1 auto; min-width: 0; }
    /* ตัวเรียงลำดับใช้โครงเดียวกับตัวกรองกะ/สาขา จะได้สูงเท่ากันทั้งแถว */
    .ot-sort-filter { position: relative; display: inline-flex; align-items: center; flex: 0 0 auto; }
    .ot-sort-filter svg { position: absolute; left: .55rem; width: .95rem; height: .95rem; color: var(--muted-light); fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; pointer-events: none; }
    .ot-sort-filter select { min-height: 2.4rem; padding: .4rem 1.7rem .4rem 1.95rem; border: 1px solid var(--line-strong); border-radius: .3rem; background: var(--panel); color: var(--light-text); font-family: inherit; font-size: .74rem; font-weight: 650; cursor: var(--cursor-action); appearance: none; }
    .ot-sort-filter::after { content: ''; position: absolute; right: .6rem; width: .4rem; height: .4rem; border-right: 1.6px solid var(--muted-light); border-bottom: 1.6px solid var(--muted-light); transform: translateY(-.12rem) rotate(45deg); pointer-events: none; }

    .ot-modal-tools { display: flex; align-items: center; gap: .6rem; flex: 1 1 auto; flex-wrap: wrap; justify-content: flex-end; }
    /* ช่องเลือกวันในหัว modal — ทรงเดียวกับตัวกรองกะและช่องค้นหา */
    .ot-modal-date {
      min-height: 2.7rem; display: inline-flex; align-items: center; gap: .45rem; flex: 0 0 auto;
      padding: 0 .7rem; border: 1px solid var(--line-strong); border-radius: 4px; background: var(--panel-soft);
    }
    .ot-modal-date:focus-within { border-color: var(--moss); }
    .ot-modal-date svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; opacity: .7; }
    .ot-modal-date input {
      border: 0; outline: 0; background: transparent; color: var(--light-text);
      font: inherit; font-size: .78rem; font-weight: 650; color-scheme: light dark;
    }
    .ot-modal-heading h2 { margin-top: .12rem; font-size: 1rem; font-weight: 650; }
    .ot-modal-heading small { color: var(--muted-light); font-size: .65rem; }
    .ot-modal-meta { margin-top: .15rem; color: var(--muted-light); font-size: .68rem; }
    .ot-modal-close {
      width: 2.4rem;
      height: 2.4rem;
      display: grid;
      place-items: center;
      flex: 0 0 auto;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      background: transparent;
      color: var(--muted-light);
    }
    .ot-modal-close:hover { border-color: var(--line-strong); color: var(--light-text); }
    .ot-modal-close svg { width: 1.15rem; fill: none; stroke: currentColor; stroke-width: 1.8; }
    .ot-modal-body { min-height: 0; flex: 1 1 auto; overflow: auto; overscroll-behavior: contain; }

    .ot-employee-head,
    .ot-employee-row {
      display: grid;
      /* ลำดับ · พนักงาน · ตำแหน่ง · กะงาน · เข้างาน · ออกงาน · การทำงาน · ขอ OT
         · ผลจากลักษณะการรูดบัตร · สถานะ · เหตุผล · ผู้ดำเนินการ
         คอลัมน์ลำดับแบ่งที่มาจากคอลัมน์พนักงาน ความกว้างรวมจึงเท่าเดิม ไม่ต้องเลื่อนเพิ่ม */
      grid-template-columns: 3rem minmax(8rem, 1.15fr) minmax(7.5rem, .8fr) 7.5rem 6rem 6rem 6.5rem 8.5rem minmax(13rem, 1fr) 10.5rem 5.5rem 8rem;
      align-items: stretch;
      gap: 0;
      /* ตรึงความกว้างไว้ ไม่งั้นพอกรองเหลือไม่กี่แถว ตารางจะหดตามจนอ่านยาก */
      min-width: 95rem;
      width: max(100%, 95rem);
    }
    .ot-employee-head {
      position: sticky;
      z-index: 2;
      top: 0;
      border-bottom: 1px solid var(--line-light);
      background: var(--panel-soft);
      color: var(--muted-light);
      font-size: .67rem;
      font-weight: 700;
    }
    .ot-table-cell {
      min-width: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: .55rem .6rem;
      border-right: 1px solid var(--line-light);
      text-align: center;
    }
    .ot-employee-head > .ot-table-cell:last-child,
    .ot-employee-row > .ot-table-cell:last-child { border-right: 0; }
    /* คอลัมน์แรกตอนนี้คือ "ลำดับ" ที่ต้องอยู่กึ่งกลาง — ชิดซ้ายเฉพาะช่องพนักงาน */
    .ot-employee-row > .ot-table-cell:nth-child(2) { justify-content: flex-start; text-align: left; }
    .ot-employee-head > span { min-width: 0; display: flex; align-items: center; justify-content: center; padding: .5rem .6rem; border-right: 1px solid var(--line-light); text-align: center; }
    .ot-employee-head > span:last-child { border-right: 0; }
    .ot-employee-row { border-bottom: 1px solid var(--line-light); font-size: .78rem; }
    .ot-employee-row:last-child { border-bottom: 0; }

    .ot-person { width: 100%; display: flex; align-items: center; justify-content: flex-start; gap: .7rem; min-width: 0; }
    .ot-avatar {
      width: 2.6rem;
      height: 2.6rem;
      display: grid;
      place-items: center;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      background: var(--hover-soft);
      color: var(--muted-light);
      font-size: .75rem;
      font-weight: 700;
      object-fit: cover;
      overflow: hidden;
    }
    .ot-person-copy { min-width: 0; max-width: calc(100% - 3.3rem); flex: 0 1 auto; text-align: left; }
    .ot-person-copy strong { display: block; overflow: hidden; font-size: .82rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
    .ot-person-copy small { display: block; color: var(--muted-light); font-size: .68rem; }
    .ot-person-link { color: inherit; text-decoration: none; border-bottom: 1px dotted var(--line-strong); }
    .ot-person-link:hover { color: var(--moss); border-bottom-color: var(--moss); }
    .ot-cell-copy { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ot-time { font-variant-numeric: tabular-nums; font-size: .85rem; font-weight: 600; }
    /* ช่วงเวลา OT บรรทัดบน จำนวนชั่วโมงบรรทัดล่าง */
    .ot-request-cell { display: grid; justify-items: center; gap: .08rem; min-width: 0; text-align: center; }
    .ot-request-cell strong { font-size: .8rem; font-weight: 650; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ot-request-cell small { color: var(--muted-light); font-size: .66rem; white-space: nowrap; }
    .ot-type-cell { line-height: 1.45; overflow-wrap: anywhere; text-align: center; }
    .ot-status { width: 100%; display: flex; flex-direction: column; align-items: center; gap: .18rem; min-width: 0; }

    /* ── กดรูปพนักงานเพื่อดูแบบขยาย ─────────────────────── */
    img.ot-avatar { cursor: zoom-in; transition: transform .18s ease; }
    img.ot-avatar:hover { transform: scale(1.08); }

    .ot-lightbox {
      position: fixed;
      z-index: 120;                 /* ต้องสูงกว่า .ot-attendance-modal (90) */
      inset: 0;
      display: grid;
      place-items: center;
      padding: clamp(1rem, 4vw, 3rem);
      background: rgb(0 0 0 / 78%);
      -webkit-backdrop-filter: blur(3px);
      backdrop-filter: blur(3px);
      cursor: zoom-out;
      animation: ot-lightbox-in .16s ease;
    }
    .ot-lightbox[hidden] { display: none; }

    @keyframes ot-lightbox-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .ot-lightbox-figure {
      max-width: min(100%, 34rem);
      display: grid;
      gap: .75rem;
      justify-items: center;
      margin: 0;
      cursor: default;
      animation: ot-lightbox-zoom .2s ease;
    }

    @keyframes ot-lightbox-zoom {
      from { opacity: 0; transform: scale(.94); }
      to { opacity: 1; transform: none; }
    }

    .ot-lightbox-figure img {
      max-width: 100%;
      max-height: min(72vh, 34rem);
      border-radius: 6px;
      background: var(--panel);
      box-shadow: 0 2rem 4rem rgb(0 0 0 / 45%);
      object-fit: contain;
    }

    .ot-lightbox-caption { display: grid; gap: .1rem; color: #fff; font-size: .9rem; text-align: center; }
    .ot-lightbox-caption small { color: rgb(255 255 255 / 68%); font-size: .78rem; }

    .ot-lightbox-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 2.6rem;
      height: 2.6rem;
      display: grid;
      place-items: center;
      border: 1px solid rgb(255 255 255 / 28%);
      border-radius: 50%;
      background: rgb(255 255 255 / 10%);
      color: #fff;
      cursor: pointer;
    }
    .ot-lightbox-close:hover { background: rgb(255 255 255 / 20%); }
    .ot-lightbox-close svg { width: 1.1rem; fill: none; stroke: currentColor; stroke-width: 2; }

    /* ป้ายสถานะ = แท็กวงรี ความกว้างคงที่เท่ากันทุกสถานะ ตัวอักษรกึ่งกลาง
       6.6rem เผื่อคำยาวสุดของทั้ง 3 ภาษา (สเปกเดียวกันทั้ง 3 หน้า OT) */
    .ot-status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 6.6rem;
      max-width: 100%;
      height: 1.5rem;
      flex: 0 0 auto;
      padding: 0 .4rem;
      border: 1px solid transparent;
      border-radius: 999px;
      background: transparent;
      font-size: .64rem;
      font-weight: 650;
      line-height: 1;
      overflow: hidden;
      text-align: center;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .ot-status-detail { display: block; min-width: 0; color: var(--light-text); font-size: .62rem; font-weight: 500; line-height: 1.3; text-align: center; }
    .ot-status-plain { color: var(--muted-light); font-size: .65rem; font-weight: 500; }
    /* ป้ายสถานะใช้พื้นหลังทึบและตัวอักษรขาวให้ตรงกันทั้งระบบ */
    .ot-status[data-tone="warning"] .ot-status-badge {
      border-color: var(--ot-status-warning-border);
      background: var(--ot-status-warning-border);
      color: #fff;
    }
    .ot-status[data-tone="success"] .ot-status-badge {
      border-color: var(--ot-status-success-border);
      background: var(--ot-status-success-border);
      color: #fff;
    }
    .ot-status[data-tone="danger"] .ot-status-badge {
      border-color: var(--ot-status-danger-border);
      background: var(--ot-status-danger-border);
      color: #fff;
    }
    .ot-status[data-tone="neutral"] .ot-status-badge {
      width: auto;
      height: auto;
      display: inline;
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      color: var(--muted-light);
      font-weight: 500;
      overflow: visible;
      text-overflow: clip;
    }
    .ot-reason-button { min-height: 2rem; padding: .36rem .55rem; border: 1px solid var(--line-strong); border-radius: 4px; background: transparent; color: var(--light-text); font-size: .68rem; font-weight: 700; white-space: nowrap; }
    .ot-reason-button:hover { border-color: var(--moss); color: var(--moss); }
    .ot-reason-modal { z-index: 110; }
    .ot-reason-dialog { width: min(100%, 31rem); }
    .ot-reason-body { display: grid; gap: .75rem; padding: 1rem 1.15rem; }
    .ot-reason-item { padding: .75rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); }
    .ot-reason-item small { display: block; margin-bottom: .3rem; color: var(--muted-light); font-size: .66rem; font-weight: 700; }
    .ot-reason-item p { margin: 0; color: var(--light-text); font-size: .78rem; line-height: 1.65; overflow-wrap: anywhere; white-space: pre-wrap; }
    .ot-reason-actions { display: flex; justify-content: flex-end; padding: .85rem 1.15rem; border-top: 1px solid var(--line-light); }
    .ot-reason-close { min-height: 2.35rem; padding: .5rem .8rem; border: 1px solid var(--line-strong); border-radius: 4px; background: transparent; color: var(--light-text); font-size: .72rem; font-weight: 700; }
    .ot-modal-loading { display: grid; min-height: 16rem; place-items: center; color: var(--muted-light); font-size: .8rem; }
    .ot-loading-mark {
      width: 1.2rem;
      height: 1.2rem;
      margin: 0 auto .55rem;
      border: 2px solid var(--line-light);
      border-top-color: var(--moss);
      border-radius: 50%;
      animation: ot-spin .75s linear infinite;
    }

    @keyframes ot-spin { to { transform: rotate(360deg); } }
    @keyframes ot-pulse { 50% { opacity: .4; transform: scale(.8); } }

    @media (max-width: 74rem) {
      .ot-department-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 62rem) {
      /* จอแคบ: ยอดของบริษัทลงมาอยู่ใต้ชื่อแทนการเบียดอยู่ข้าง ๆ จนตัวเลขติดกัน */
      .ot-company > summary { grid-template-columns: minmax(0, 1fr) auto; }
      .ot-company-numbers { grid-column: 1 / -1; flex-wrap: wrap; gap: .55rem .9rem; }
      .ot-company-numbers > span { min-width: 4.2rem; }
    }
    @media (max-width: 48rem) {
      .ot-attendance-head { grid-template-columns: 1fr; align-items: start; gap: .7rem; }
      .ot-head-side { justify-items: start; min-width: 0; width: 100%; }
      .ot-source-state { justify-content: flex-start; }
      .ot-summary-strip { justify-content: flex-start; }
      /* หมวดสรุปเรียงลงเป็นบล็อก เส้นคั่นเปลี่ยนจากแนวตั้งเป็นแนวนอน */
      .ot-summary-group { width: 100%; padding: 0 0 .55rem; border-right: 0; border-bottom: 1px solid var(--line-light); }
      .ot-summary-group:last-child { padding-bottom: 0; border-bottom: 0; }
      .ot-date-panel { align-items: stretch; justify-content: flex-start; }
      .ot-date-picker { justify-content: space-between; width: 100%; }
      .ot-date-picker input { width: min(12rem, 70vw); }
      .ot-export-form { width: 100%; justify-content: stretch; }
      .ot-export-select { min-width: 0; flex: 1 1 auto; }
    }
    @media (prefers-reduced-motion: reduce) {
      .ot-department-card, .ot-company-chevron { transition: none; }
      .ot-loading-mark, .ot-source-state.is-refreshing .ot-source-dot { animation: none; }
    }
  </style>

  <main
    class="ot-attendance"
    data-ot-attendance
    data-selected-date="{{ $selectedDate->format('Y-m-d') }}"
    data-is-today="{{ $selectedDate->isToday() ? '1' : '0' }}"
    data-summary-url="{{ route('ot-approval.attendance.summary') }}"
    data-employees-url="{{ route('ot-approval.attendance.employees') }}"
  >
    @include('ot_approval.partials.overview-tabs')

    <header class="ot-attendance-head">
      <div class="ot-attendance-heading">
        <h1 data-i18n="ot.attendance.title">เวลาเข้า–ออกงาน</h1>
        <p data-i18n="ot.attendance.subtitle">ตรวจสอบเวลาเข้า–ออกของพนักงานตามวันที่เลือก</p>
      </div>
      <div class="ot-head-side">
        {{-- แบ่ง 3 หมวดตามคำถามที่ผู้ใช้ถามจริง: มีคนเท่าไร → มาแล้วกี่คน → OT เป็นยังไง
             ไม่ใส่กรอบตามที่ Manager สั่ง ใช้แค่หัวหมวดจาง ๆ กับเส้นคั่นบางระหว่างหมวด --}}
        {{-- ปุ่มนาฬิกาเปิดสรุปตัวเลขรวม — ย่อแถบยาวให้เหลือไอคอนเดียว --}}
        <button type="button" class="ot-summary-open" data-summary-open aria-haspopup="dialog" data-i18n-aria="ot.attendance.summaryOpen" aria-label="ดูสรุปตัวเลขรวม" title="ดูสรุปตัวเลขรวม">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
        </button>

        <div class="ot-summary-modal" data-summary-modal hidden>
          <div class="ot-summary-dialog" role="dialog" aria-modal="true" aria-label="สรุปตัวเลขรวม">
            <header class="ot-summary-dialog-head">
              <strong data-i18n="ot.attendance.summaryTitle">สรุปตัวเลขรวม</strong>
              <button type="button" class="ot-summary-close" data-summary-close aria-label="ปิด">&times;</button>
            </header>
        <section class="ot-summary-strip" aria-label="สรุปพนักงาน">
          {{-- ช่องที่ 5 คือคีย์ตัวหาร ถ้ามีจะแสดงเป็น "เข้าแล้ว/ทั้งหมด"
               กะเวลา A/กะเวลา Bต้องเห็นทั้งยอดคนและยอดที่เข้ามาแล้ว ไม่งั้นเห็นแค่ 950 แล้วไม่รู้ว่ามากันกี่คน --}}
          @foreach ([
            ['ot.attendance.groupHeadcount', 'กำลังพล', [
              ['total', 'ot.attendance.totalEmployees', 'พนักงานทั้งหมด', '', null],
              ['shifts.morning.clocked_in', 'ot.settings.shift.morning', 'กะเวลา A', 'morning', 'shifts.morning.total'],
              ['shifts.night.clocked_in', 'ot.settings.shift.night', 'กะเวลา B', 'night', 'shifts.night.total'],
            ]],
            ['ot.attendance.groupScan', 'การสแกน', [
              ['clocked_in', 'ot.attendance.clockedIn', 'สแกนเข้างาน', '', 'total'],
              ['clocked_out', 'ot.attendance.clockedOut', 'เวลาล่าสุด / ออกงาน', '', 'total'],
            ]],
            ['ot.attendance.groupOt', 'ค่าล่วงเวลา', [
              ['ot_requested', 'ot.attendance.otRequested', 'ขอ OT', '', null],
              ['completed', 'ot.attendance.completed', 'ผ่าน', '', null],
            ]],
          ] as [$groupKey, $groupLabel, $items])
            <div class="ot-summary-group">
              <p class="ot-summary-group-title" data-i18n="{{ $groupKey }}">{{ $groupLabel }}</p>
              <div class="ot-summary-group-items">
                @foreach ($items as [$key, $labelKey, $label, $tone, $ofKey])
                  <div class="ot-summary-item @if ($tone) is-{{ $tone }} @endif">
                    <span>
                      @if ($tone === 'morning')
                        
                      @elseif ($tone === 'night')
                        
                      @endif
                      <span data-i18n="{{ $labelKey }}">{{ $label }}</span>
                    </span>
                    <strong>
                      <span data-global-count="{{ $key }}">{{ number_format($attendanceTotals[$key]) }}</span>@if ($ofKey)<i>/<span data-global-count="{{ $ofKey }}">{{ number_format($attendanceTotals[$ofKey]) }}</span></i>@endif
                    </strong>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </section>
          </div>
        </div>
      </div>
    </header>

    <section class="ot-date-panel" aria-label="เลือกวันทำงาน">
      <label class="ot-date-picker">
        <span data-i18n="ot.attendance.selectDate">เลือกวันที่</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
        <input type="date" value="{{ $selectedDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" data-date-picker>
      </label>

      @if ($isOtAdmin ?? false)
        {{-- แอดมินเห็นทุกแผนกจึงต้องมีตัวกรอง · Foreman เห็นแค่แผนกตัวเองอยู่แล้วไม่ต้องมี --}}
        <label class="ot-dept-filter">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"></path></svg>
          <select data-dept-filter aria-label="กรองตามแผนก">
            <option value="all" data-i18n="ot.attendance.allDepartments">ทุกแผนก</option>
            @foreach (collect($attendance['companies'] ?? [])->flatMap(fn ($c) => $c['departments'] ?? [])->sortBy('name_th') as $dept)
              <option value="{{ $dept['code'] }}">{{ $dept['name_th'] }}</option>
            @endforeach
          </select>
        </label>
      @endif

      {{-- การดาวน์โหลดอยู่ที่เมนู `ดาวน์โหลด` ของ sidebar อย่างเดียว
           ไม่ต้องมีทางลัดซ้ำในหน้าภาพรวมทั้งสองฝั่ง --}}
    </section>

    <section class="ot-company-list" data-company-list>
      {{-- ย่อทุกบริษัทไว้เสมอ ทั้งตอนเปิดหน้าใหม่ รีเฟรช และย้อนกลับเข้ามา --}}
      @forelse ($attendance['companies'] as $company)
        <details class="ot-company" data-company="{{ $company['code'] }}">
          <summary>
            {{-- จำนวนคนกับจำนวนแผนกเป็นข้อมูลนิ่ง ย้ายมาอยู่ใต้ชื่อบริษัท
                 เหลือฝั่งขวาไว้เฉพาะตัวเลขที่ขยับระหว่างวัน จะได้ไม่ปนกันจนอ่านไม่ออก --}}
            <span class="ot-company-name">
              <strong>{{ $company['label'] }}</strong>
              <small>
                <span data-company-department-count>{{ count($company['departments']) }}</span> <span data-i18n="ot.attendance.departments">แผนก</span>
                · <span data-company-count="total">{{ number_format($company['total']) }}</span> <span data-i18n="ot.attendance.people">คน</span>
              </small>
            </span>
            {{-- ป้ายอยู่บน ตัวเลขอยู่ล่าง จับคู่กันในแนวตั้งเหมือนแถบสรุปด้านบนและการ์ดแผนก
                 ของเดิมเรียง เลข-ป้าย-เลข-ป้าย ต่อกันในแนวนอน พอขึ้นบรรทัดใหม่แล้วจับคู่ไม่ถูก
                 กะบอกเป็น "เข้าแล้ว/ทั้งหมด" เพื่อให้เห็นสัดส่วนทันทีโดยไม่ต้องเปิดดูรายแผนก --}}
            <span class="ot-company-numbers" data-company-numbers hidden>
              <span>
                <small data-i18n="ot.attendance.clockedIn">สแกนเข้างาน</small>
                <b><span data-company-count="clocked_in">{{ number_format($company['clocked_in']) }}</span><i>/<span data-company-count="total">{{ number_format($company['total']) }}</span></i></b>
              </span>
              <span>
                <small data-i18n="ot.attendance.clockedOutLong">ออกงาน</small>
                <b><span data-company-count="clocked_out">{{ number_format($company['clocked_out']) }}</span><i>/<span data-company-count="total">{{ number_format($company['total']) }}</span></i></b>
              </span>
              <span class="is-morning">
                <small>
                  
                  <span data-i18n="ot.settings.shift.morning">กะเวลา A</span>
                </small>
                <b><span data-company-count="shifts.morning.clocked_in">{{ number_format($company['shifts']['morning']['clocked_in'] ?? 0) }}</span><i>/<span data-company-count="shifts.morning.total">{{ number_format($company['shifts']['morning']['total'] ?? 0) }}</span></i></b>
              </span>
              <span class="is-night">
                <small>
                  
                  <span data-i18n="ot.settings.shift.night">กะเวลา B</span>
                </small>
                <b><span data-company-count="shifts.night.clocked_in">{{ number_format($company['shifts']['night']['clocked_in'] ?? 0) }}</span><i>/<span data-company-count="shifts.night.total">{{ number_format($company['shifts']['night']['total'] ?? 0) }}</span></i></b>
              </span>
            </span>
            <svg class="ot-company-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
          </summary>
          <div class="ot-company-body">
            @if (! $company['available'])
              <p class="ot-company-error" data-company-error data-i18n="ot.attendance.bplusUnavailable">ไม่สามารถอ่านข้อมูล Bplus ของบริษัทนี้ได้ในขณะนี้</p>
            @endif
            <div class="ot-department-grid">
              @foreach ($company['departments'] as $department)
                @php
                  $shifts = $department['shifts'] ?? [];
                  $blank = ['total' => 0, 'clocked_in' => 0, 'clocked_out' => 0, 'times' => []];
                  $morning = ($shifts['morning'] ?? []) + $blank;
                  $night = ($shifts['night'] ?? []) + $blank;
                  $unknown = ($shifts['unknown'] ?? []) + $blank;
                  /* หลายกะในกลุ่มเดียวกันจึงต่อกันด้วย · และใส่จำนวนคนกำกับเมื่อมีมากกว่าหนึ่งช่วงเวลา */
                  $rangeText = function (array $shift) {
                    $times = $shift['times'] ?? [];
                    $many = count($times) > 1;

                    return implode(' · ', array_map(
                      fn (array $time) => $time['range'].($many ? ' ('.number_format($time['people']).')' : ''),
                      $times,
                    ));
                  };
                @endphp
                <div
                  class="ot-department-card"
                  data-department-card
                  data-company-code="{{ $company['code'] }}"
                  data-company-label="{{ $company['label'] }}"
                  data-dept-code="{{ $department['code'] }}"
                  data-dept-name-th="{{ $department['name_th'] }}"
                  data-dept-name-en="{{ $department['name_en'] }}"
                  data-dept-name-my="{{ $department['name_my'] }}"
                >
                  <button class="ot-department-main" type="button" data-open-department aria-label="ดูรายชื่อพนักงาน {{ $department['name_th'] }}"></button>
                  {{-- ป้าย "ดูรายชื่อพนักงาน" กินที่และซ้ำทุกใบ จึงเหลือแค่ลูกศรมุมขวาบนเป็นสัญญาณว่ากดได้ --}}
                  <span class="ot-department-corner" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></span>

                  <span class="ot-department-title">
                    {{-- ชื่อแผนกเป็นรูปแบบ `Accounting (ACC)` เหมือนกันทุกภาษาแล้ว จึงพิมพ์ครั้งเดียว --}}
                    <strong>{{ $department['name_th'] }}</strong>
                  </span>

                  {{-- ตารางเดียวทั้งใบ: หัวคอลัมน์ เข้า/ออก บอกครั้งเดียว แล้วไล่ลงเป็นแถว
                       ทั้งแผนก → กะเวลา A → กะเวลา B ตัวเลขจึงอยู่ตรงคอลัมน์เดียวกันทุกแถว
                       เทียบกันได้ด้วยการกวาดสายตาลงล่าง ไม่ต้องอ่านป้ายกำกับซ้ำทุกจุด --}}
                  <div class="ot-department-board">
                    <div class="ot-board-head" aria-hidden="true">
                      <span></span>
                      <span data-i18n="ot.attendance.clockedInShort">เข้า</span>
                      <span data-i18n="ot.attendance.clockedOutShort">ออก</span>
                    </div>

                    <div class="ot-board-row is-total">
                      <span class="ot-board-label" data-i18n="ot.attendance.wholeDepartment">ทั้งแผนก</span>
                      <span class="ot-board-metric">
                        <b data-dept-count="clocked_in">{{ number_format($department['clocked_in']) }}</b><i>/<span data-dept-count="total">{{ number_format($department['total']) }}</span></i>
                      </span>
                      <span class="ot-board-metric">
                        <b data-dept-count="clocked_out">{{ number_format($department['clocked_out']) }}</b><i>/<span data-dept-count="total">{{ number_format($department['total']) }}</span></i>
                      </span>
                    </div>

                    <button
                      class="ot-board-row ot-shift-pill is-morning" type="button"
                      data-shift-pill="morning" aria-expanded="false"
                      aria-label="กะเวลา A"
                      data-shift-times="{{ $rangeText($morning) }}"
                      @if ($morning['total'] < 1) hidden @endif
                    >
                      <span class="ot-board-label">
                        
                        <span data-i18n="ot.settings.shift.morning">กะเวลา A</span>
                      </span>
                      <span class="ot-board-metric">
                        <b data-dept-shift="morning.clocked_in">{{ number_format($morning['clocked_in']) }}</b><i>/<span data-dept-shift="morning.total">{{ number_format($morning['total']) }}</span></i>
                      </span>
                      <span class="ot-board-metric">
                        <b data-dept-shift="morning.clocked_out">{{ number_format($morning['clocked_out']) }}</b><i>/<span data-dept-shift="morning.total">{{ number_format($morning['total']) }}</span></i>
                      </span>
                    </button>

                    <button
                      class="ot-board-row ot-shift-pill is-night" type="button"
                      data-shift-pill="night" aria-expanded="false"
                      aria-label="กะเวลา B"
                      data-shift-times="{{ $rangeText($night) }}"
                      @if ($night['total'] < 1) hidden @endif
                    >
                      <span class="ot-board-label">
                        
                        <span data-i18n="ot.settings.shift.night">กะเวลา B</span>
                      </span>
                      <span class="ot-board-metric">
                        <b data-dept-shift="night.clocked_in">{{ number_format($night['clocked_in']) }}</b><i>/<span data-dept-shift="night.total">{{ number_format($night['total']) }}</span></i>
                      </span>
                      <span class="ot-board-metric">
                        <b data-dept-shift="night.clocked_out">{{ number_format($night['clocked_out']) }}</b><i>/<span data-dept-shift="night.total">{{ number_format($night['total']) }}</span></i>
                      </span>
                    </button>

                    {{-- วันที่ข้อมูลมาจาก snapshot ท้องถิ่นจะไม่มีรหัสกะจริง ต้องบอกตรง ๆ ไม่ใช่โชว์กะเวลา A 0 --}}
                    <span
                      class="ot-board-row ot-shift-pill is-unknown"
                      data-shift-unknown
                      @if ($unknown['total'] < 1) hidden @endif
                    >
                      <span class="ot-board-label">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path d="M9.6 9.4a2.5 2.5 0 1 1 3.4 2.3c-.6.3-1 .9-1 1.6v.4"></path><path d="M12 17.1v.1"></path></svg>
                        <span data-i18n="ot.attendance.shiftUnknown">ไม่ระบุกะ</span>
                      </span>
                      <span class="ot-board-metric is-plain"><b data-dept-shift="unknown.total">{{ number_format($unknown['total']) }}</b></span>
                      <span class="ot-board-metric"></span>
                    </span>
                  </div>

                  <p class="ot-shift-note" data-shift-note hidden></p>
                </div>
              @endforeach
            </div>
          </div>
        </details>
      @empty
        <div class="ot-empty" data-i18n="ot.attendance.noDepartments">ไม่พบแผนกที่อยู่ในสิทธิ์ของคุณ</div>
      @endforelse
    </section>
  </main>

  <div class="ot-attendance-modal" data-attendance-modal role="dialog" aria-modal="true" aria-labelledby="otAttendanceModalTitle" hidden>
    <div class="ot-attendance-dialog">
      <header class="ot-modal-head">
        <div class="ot-modal-heading">
          {{-- หัวเรื่องกับช่องค้นหาอยู่ชุดเดียวกัน ส่วนตัวกรองอื่นไปรวมกันฝั่งขวา --}}
          <h2 id="otAttendanceModalTitle"><span data-i18n="ot.attendance.departmentLabel">แผนก</span> <span data-modal-title>—</span></h2>
        </div>
        <div class="ot-modal-tools">
          @include('ot_approval.partials.employee-search')
          {{-- เปลี่ยนวันได้จากใน modal เลย ไม่ต้องปิดออกไปเลือกวันข้างนอกแล้วเปิดแผนกใหม่ --}}
          <label class="ot-modal-date">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"></path></svg>
            <span class="sr-only" data-i18n="ot.attendance.pickDate">เลือกวันที่</span>
            <input type="date" data-modal-date>
          </label>
          {{-- เรียงตามตำแหน่ง — Bplus ไม่ได้เซ็ตลำดับไว้ ใช้ตารางใน OtPositionRank --}}
          <label class="ot-sort-filter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
            <select data-sort-filter aria-label="เรียงลำดับ">
              <option value="code" data-i18n="ot.attendance.sortCode">ตามรหัสพนักงาน</option>
              <option value="rank" data-i18n="ot.attendance.sortRank">ตำแหน่งสูง → ต่ำ</option>
              <option value="rank_asc" data-i18n="ot.attendance.sortRankAsc">ตำแหน่งต่ำ → สูง</option>
            </select>
          </label>
          {{-- ตัวกรองสาขา (ทุกสาขา / สำนักงานใหญ่ / โรงงาน / โรงงาน-พม่า) — ชุดเดียวกับหน้ายื่นขอ OT
               กรองในเครื่องจาก `branch_th` ที่ payload ส่งมาแล้ว ไม่ต้องยิงเซิร์ฟเวอร์ซ้ำ --}}
          @include('ot_approval.partials.branch-filter')
          @include('ot_approval.partials.shift-filter')
          <button class="ot-modal-close" type="button" data-close-modal data-i18n-aria="ot.attendance.close" aria-label="ปิด">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
          </button>
        </div>
      </header>
      <div class="ot-modal-body" data-modal-body></div>
    </div>
  </div>

  <div class="ot-attendance-modal ot-reason-modal" data-reason-modal role="dialog" aria-modal="true" aria-labelledby="otReasonTitle" hidden>
    <div class="ot-attendance-dialog ot-reason-dialog">
      <header class="ot-modal-head">
        <div class="ot-modal-heading">
          <small data-i18n="ot.reason.button">เหตุผล</small>
          <h2 id="otReasonTitle" data-reason-title>—</h2>
        </div>
        <button class="ot-modal-close" type="button" data-close-reason data-i18n-aria="ot.reason.close" aria-label="ปิด">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
        </button>
      </header>
      <div class="ot-reason-body">
        <section class="ot-reason-item">
          <small data-i18n="ot.reason.requestNote">หมายเหตุจาก Foreman</small>
          <p data-reason-request>—</p>
        </section>
        <section class="ot-reason-item">
          <small data-i18n="ot.reason.decisionNote">หมายเหตุจาก Supervisor</small>
          <p data-reason-decision>—</p>
        </section>
      </div>
      <footer class="ot-reason-actions">
        <button class="ot-reason-close" type="button" data-close-reason data-i18n="ot.reason.close">ปิด</button>
      </footer>
    </div>
  </div>

  {{-- ดูรูปพนักงานแบบขยาย ใช้กับรูปในโมดัลรายชื่อ --}}
  <div class="ot-lightbox" data-ot-lightbox role="dialog" aria-modal="true" aria-label="รูปพนักงาน" hidden>
    <button class="ot-lightbox-close" type="button" data-lightbox-close data-i18n-aria="ot.attendance.close" aria-label="ปิด">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
    </button>
    <figure class="ot-lightbox-figure">
      <img data-lightbox-image src="" alt="">
      <figcaption class="ot-lightbox-caption" data-lightbox-caption hidden></figcaption>
    </figure>
  </div>

  @include('ot_approval.partials.process-route')

  <script>
    'use strict';

    (function () {
      var root = document.querySelector('[data-ot-attendance]');
      if (!root) return;

      var selectedDate = root.dataset.selectedDate;
      var isToday = root.dataset.isToday === '1';
      var summaryUrl = root.dataset.summaryUrl;
      var employeesUrl = root.dataset.employeesUrl;
      var sourceMode = @json($attendance['source_mode'] ?? 'bplus');
      var modal = document.querySelector('[data-attendance-modal]');
      var modalBody = modal.querySelector('[data-modal-body]');
      var modalTitle = modal.querySelector('[data-modal-title]');
      var modalMeta = modal.querySelector('[data-modal-meta]');
      var reasonModal = document.querySelector('[data-reason-modal]');
      // ย้ายไปอยู่ใต้เมนูซ้าย (นอก main) จึงต้องหาจาก document ไม่ใช่ root
      var sourceState = document.querySelector('[data-source-state]');
      var activeDepartment = null;
      var lastEmployeePayload = null;
      var shiftFilterSelect = document.querySelector('[data-attendance-modal] [data-shift-filter]');
      var shiftFilterValue = window.otShiftFilter.ALL;   // จำค่าไว้ระหว่างเปิดโมดัลเดิม
      var previousFocus = null;
      var reasonPreviousFocus = null;

      function text(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
      }

      function localized(item, field, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var suffix = lang === 'my' ? 'my' : lang;
        return item[field + '_' + suffix] || item[field + '_en'] || item[field + '_th'] || fallback || '—';
      }

      function formatNumber(value) {
        return new Intl.NumberFormat(document.documentElement.lang || 'th').format(Number(value || 0));
      }

      function formatDate(value) {
        var date = new Date(value + 'T00:00:00');
        try {
          return new Intl.DateTimeFormat(document.documentElement.lang || 'th', {
            day: 'numeric', month: 'short', year: 'numeric'
          }).format(date);
        } catch (error) {
          return value;
        }
      }

      function formatGeneratedAt(value) {
        if (!value) return '—';
        try {
          return new Intl.DateTimeFormat(document.documentElement.lang || 'th', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
          }).format(new Date(value));
        } catch (error) {
          return '—';
        }
      }

      async function fetchJson(url) {
        var response = await fetch(url, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      }

      function localAssetUrl(value) {
        if (!value) return '';
        try {
          var url = new URL(value, window.location.href);
          if (['localhost', '127.0.0.1'].indexOf(url.hostname) !== -1) {
            return window.location.origin + url.pathname + url.search;
          }
          return url.href;
        } catch (error) {
          return value;
        }
      }

      function setSourceRefreshing(refreshing) {
        sourceState.classList.toggle('is-refreshing', refreshing);
        var copy = sourceState.querySelector('[data-source-copy]');
        if (refreshing) {
          copy.textContent = text('ot.attendance.refreshing', 'กำลังอัปเดตจาก Bplus');
          return;
        }
        copy.textContent = sourceMode === 'local'
          ? text('ot.attendance.localSource', 'Local demo · ข้อมูลจำลอง')
          : (sourceMode === 'snapshot'
            ? text('ot.attendance.snapshotSource', 'Bplus · Snapshot ใน Local')
            : (sourceMode === 'mixed'
            ? text('ot.attendance.mixedSource', 'Bplus + Local fallback')
            : (isToday
              ? text('ot.attendance.liveSource', 'Bplus · อัปเดตอัตโนมัติ')
              : text('ot.attendance.historySource', 'Bplus · ข้อมูลย้อนหลัง'))));
      }

      function findDepartmentCard(companyCode, deptCode) {
        return Array.prototype.find.call(root.querySelectorAll('[data-open-department]'), function (card) {
          return card.dataset.companyCode === companyCode && card.dataset.deptCode === deptCode;
        });
      }

      /* ยอดบางตัวอยู่ซ้อนชั้น เช่น shifts.morning.total ของยอดรายกะ
         จึงเดินตาม path แทนการอ่านคีย์ชั้นเดียว คีย์ในหน้าจอจะได้ตรงกับรูปข้อมูลจริง */
      function pickCount(source, path) {
        return String(path).split('.').reduce(function (value, key) {
          return (value && typeof value === 'object') ? value[key] : undefined;
        }, source);
      }

      var deptFilterSelect = root.querySelector('[data-dept-filter]');
      if (deptFilterSelect) {
        deptFilterSelect.addEventListener('change', function () {
          var value = deptFilterSelect.value;
          root.querySelectorAll('[data-company]').forEach(function (company) {
            var shown = 0;
            company.querySelectorAll('[data-department-card]').forEach(function (card) {
              var match = value === 'all' || card.dataset.deptCode === value;
              card.hidden = !match;
              if (match) shown++;
            });
            company.hidden = shown === 0;
            if (shown > 0 && value !== 'all') company.open = true;
          });
        });
      }

      /* ── สรุปตัวเลขรวม: เปิดจากปุ่มนาฬิกา ──────────────────────────
         ปุ่มของบริษัทอยู่ใน <summary> ของ <details> จึงต้อง preventDefault
         ไม่งั้นการกดจะไปพับ/กางการ์ดบริษัทแทนที่จะเปิดสรุป */
      (function initSummaryModal() {
        var modal = root.querySelector('[data-summary-modal]');
        if (!modal) return;
        var dialog = modal.querySelector('.ot-summary-dialog');
        var globalStrip = modal.querySelector('.ot-summary-strip');
        var slot = null;

        function open(node) {
          // ย้ายก้อนตัวเลขของบริษัทเข้ามาแสดงชั่วคราว แล้วคืนที่เดิมตอนปิด
          if (node) {
            slot = { node: node, parent: node.parentNode, next: node.nextSibling, hidden: node.hidden };
            node.hidden = false;
            dialog.appendChild(node);
            globalStrip.hidden = true;
          } else {
            globalStrip.hidden = false;
          }
          modal.hidden = false;
          document.body.style.overflow = 'hidden';
        }

        function close() {
          if (slot) {
            slot.node.hidden = slot.hidden;
            slot.parent.insertBefore(slot.node, slot.next);
            slot = null;
          }
          globalStrip.hidden = false;
          modal.hidden = true;
          document.body.style.overflow = '';
        }

        var globalBtn = root.querySelector('[data-summary-open]');
        if (globalBtn) globalBtn.addEventListener('click', function () { open(null); });

        modal.querySelector('[data-summary-close]').addEventListener('click', close);
        modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && !modal.hidden) close();
        });
      })();

      function updateSummary(payload) {
        if (payload.source_mode) sourceMode = payload.source_mode;
        var totals = {
          total: 0, clocked_in: 0, clocked_out: 0, ot_requested: 0, completed: 0,
          'shifts.morning.total': 0, 'shifts.morning.clocked_in': 0,
          'shifts.night.total': 0, 'shifts.night.clocked_in': 0
        };
        (payload.companies || []).forEach(function (company) {
          Object.keys(totals).forEach(function (key) { totals[key] += Number(pickCount(company, key) || 0); });
          var companyNode = Array.prototype.find.call(root.querySelectorAll('[data-company]'), function (node) {
            return node.dataset.company === company.code;
          });
          if (!companyNode) return;
          companyNode.querySelectorAll('[data-company-count]').forEach(function (node) {
            node.textContent = formatNumber(pickCount(company, node.dataset.companyCount) || 0);
          });
          (company.departments || []).forEach(function (department) {
            var card = findDepartmentCard(company.code, department.code);
            if (!card) return;
            card.querySelectorAll('[data-dept-count]').forEach(function (node) {
              node.textContent = formatNumber(department[node.dataset.deptCount]);
            });

            /* ตัวเลขแยกกะ — คีย์เป็น "กลุ่ม.ฟิลด์" เช่น morning.clocked_in */
            var shifts = department.shifts || {};
            card.querySelectorAll('[data-dept-shift]').forEach(function (node) {
              var parts = node.dataset.deptShift.split('.');
              var bucket = shifts[parts[0]] || {};
              node.textContent = formatNumber(bucket[parts[1]] || 0);
            });

            /* กะที่ไม่มีคนในวันนั้นให้ซ่อนไปเลย ดีกว่าโชว์ 0/0 ให้เข้าใจผิด */
            ['morning', 'night'].forEach(function (group) {
              var pill = card.querySelector('[data-shift-pill="' + group + '"]');
              if (!pill) return;
              var bucket = shifts[group] || { total: 0, times: [] };
              pill.hidden = !(bucket.total > 0);

              var times = bucket.times || [];
              pill.dataset.shiftTimes = times.map(function (time) {
                return time.range + (times.length > 1 ? ' (' + formatNumber(time.people) + ')' : '');
              }).join(' · ');
            });
            var unknownPill = card.querySelector('[data-shift-unknown]');
            if (unknownPill) unknownPill.hidden = !((shifts.unknown || {}).total > 0);
            var note = card.querySelector('[data-shift-note]');
            if (note) {
              note.hidden = true;
              card.querySelectorAll('[data-shift-pill]').forEach(function (item) { item.setAttribute('aria-expanded', 'false'); });
            }
          });
        });
        root.querySelectorAll('[data-global-count]').forEach(function (node) {
          node.textContent = formatNumber(totals[node.dataset.globalCount]);
        });
      }

      async function refreshSummary() {
        if (document.hidden) return;
        setSourceRefreshing(true);
        try {
          var response = await fetchJson(summaryUrl + '?date=' + encodeURIComponent(selectedDate));
          if (response.ok && response.attendance) updateSummary(response.attendance);
          if (activeDepartment && !modal.hidden) await loadEmployees(activeDepartment, true);
        } catch (error) {
          sourceState.querySelector('[data-source-copy]').textContent = text('ot.attendance.bplusUnavailable', 'ไม่สามารถอ่านข้อมูล Bplus ได้');
        } finally {
          setSourceRefreshing(false);
        }
      }

      function avatar(employee) {
        if (employee.avatar) {
          var image = document.createElement('img');
          image.className = 'ot-avatar';
          image.src = localAssetUrl(employee.avatar);
          image.alt = localized(employee, 'name', employee.code);
          image.loading = 'lazy';
          return image;
        }
        var fallback = document.createElement('span');
        fallback.className = 'ot-avatar';
        fallback.textContent = (localized(employee, 'name', employee.code).trim().charAt(0) || '?').toUpperCase();
        return fallback;
      }

      function headerCell(key, fallback, extra) {
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell' + (extra ? ' ' + extra : '');
        cell.setAttribute('data-i18n', key);
        cell.textContent = text(key, fallback);
        return cell;
      }

      function valueCell(value, className) {
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell ' + (className || 'ot-cell-copy');
        cell.textContent = value || '-';
        cell.title = value || '-';
        return cell;
      }

      // แสดงเฉพาะเวลา ไม่ใส่ป้ายกำกับแหล่งที่มาใต้ตัวเลข (สแกนสด / สแกนล่าสุด / Bplus ประมวลผลแล้ว)
    /* หัวคอลัมน์ที่กรองได้ — กดกรวยแล้วขึ้นเมนูติ๊กเลือกค่าแบบ Excel
         รายการค่าสร้างจากข้อมูลที่โหลดมาแล้ว จึงมีเฉพาะค่าที่มีอยู่จริงในแผนกนั้น */
      function filterableHeaderCell(key, fallback, filterKey, rows, labelOf) {
        try {
          return buildFilterableHeader(key, fallback, filterKey, rows, labelOf);
        } catch (error) {
          // ตัวกรองพังต้องไม่ทำให้ทั้งตารางหายไป — คืนหัวคอลัมน์ธรรมดาแทน
          console.error('column filter failed:', filterKey, error);
          return headerCell(key, fallback);
        }
      }

      function buildFilterableHeader(key, fallback, filterKey, rows, labelOf) {
        var cell = headerCell(key, fallback);
        cell.classList.add('is-filterable');

        var spec = columnSpec(filterKey);
        if (!spec) return cell;   // คอลัมน์ที่ยังไม่ได้นิยามตัวกรองไว้ ให้เป็นหัวธรรมดา
        var values = [];
        (rows || []).forEach(function (row) {
          var v = String(spec.value(row));
          if (values.indexOf(v) === -1) values.push(v);
        });
        values.sort();

        var picked = columnFilters[filterKey] || new Set();
        // draft = ค่าที่กำลังติ๊กอยู่ ยังไม่มีผลจนกว่าจะกด "ตกลง"
        var draft = new Set(picked);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ot-col-filter' + (picked.size ? ' is-active' : '');
        btn.setAttribute('aria-label', text('ot.attendance.filterColumn', 'กรองคอลัมน์นี้'));
        // ไอคอนแบบ Excel: กรอบสี่เหลี่ยมมีลูกศรลง (เดิมเป็นรูปกรวย)
        btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="2"></rect><path d="M8 10.5 12 15l4-4.5"></path></svg>';

        var menu = document.createElement('div');
        menu.className = 'ot-col-menu';
        menu.hidden = true;
        values.forEach(function (v) {
          var label = document.createElement('label');
          var box = document.createElement('input');
          box.type = 'checkbox';
          box.checked = picked.has(v);
          box.addEventListener('change', function () {
            if (box.checked) draft.add(v); else draft.delete(v);
          });
          var span = document.createElement('span');
          span.textContent = labelOf ? labelOf(v) : v;
          label.append(box, span);
          menu.appendChild(label);
        });

        var actions = document.createElement('div');
        actions.className = 'ot-col-actions';

        var clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'ot-col-clear';
        clear.setAttribute('data-i18n', 'ot.attendance.clearFilter');
        clear.textContent = text('ot.attendance.clearFilter', 'ล้างตัวกรอง');
        clear.addEventListener('click', function () {
          delete columnFilters[filterKey];
          if (lastEmployeePayload) renderEmployees(lastEmployeePayload);
        });

        var apply = document.createElement('button');
        apply.type = 'button';
        apply.className = 'ot-col-apply';
        apply.setAttribute('data-i18n', 'common.confirm');
        apply.textContent = text('common.confirm', 'ตกลง');
        apply.addEventListener('click', function () {
          if (draft.size) columnFilters[filterKey] = new Set(draft);
          else delete columnFilters[filterKey];
          if (lastEmployeePayload) renderEmployees(lastEmployeePayload);
        });

        actions.append(clear, apply);
        menu.appendChild(actions);

        btn.addEventListener('click', function (event) {
          event.stopPropagation();
          // ปิดเมนูอื่นก่อน จะได้ไม่ค้างเปิดพร้อมกันหลายอัน
          modal.querySelectorAll('.ot-col-menu').forEach(function (m) { if (m !== menu) m.hidden = true; });
          menu.hidden = !menu.hidden;
        });
        menu.addEventListener('click', function (event) { event.stopPropagation(); });

        cell.append(btn, menu);
        return cell;
      }

      /* ── ตัวกรองรายคอลัมน์แบบ Excel ────────────────────────────────
         เก็บค่าที่เลือกไว้เป็น Set ต่อคอลัมน์ · ว่าง = ไม่กรอง (แสดงทุกค่า)
         ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะพอจะกรองได้จริง — คอลัมน์พนักงานไม่ใส่
         เพราะไม่มีค่าซ้ำเลยและมีช่องค้นหาอยู่แล้ว */
      var columnFilters = {};
      var sortMode = 'code';
      var branchFilterValue = 'all';   // สาขา (สำนักงานใหญ่ / โรงงาน / โรงงาน-พม่า) กรองในเครื่องเช่นกัน

      var COLUMN_FILTERS = [
        { key: 'position', value: function (e) { return localized(e, 'position', '—'); } },
        { key: 'shift', value: function (e) { return e.shift_in && e.shift_out ? e.shift_in + '–' + e.shift_out : '-'; } },
        { key: 'presence', value: function (e) { return e.presence || 'dayoff'; } },
        { key: 'status', value: function (e) { return e.status_key || 'not_requested'; } }
      ];

      function columnSpec(key) {
        return COLUMN_FILTERS.filter(function (c) { return c.key === key; })[0] || null;
      }

      function matchesColumnFilters(employee) {
        return Object.keys(columnFilters).every(function (key) {
          var picked = columnFilters[key];
          if (!picked || !picked.size) return true;
          var spec = columnSpec(key);
          return spec ? picked.has(String(spec.value(employee))) : true;
        });
      }

      /* คอลัมน์มาทำงาน: มา / ไม่มา / วันหยุด — แยกวันหยุดออกจากขาดงาน
         เพราะทั้งคู่ไม่มีเวลาสแกนเหมือนกัน (ดู presenceOf ใน BplusAttendanceService) */
      function presenceCell(employee) {
        var map = {
          present: ['ot.attendance.present', 'เข้างาน'],
          absent: ['ot.attendance.absent', 'ไม่เข้างาน'],
          dayoff: ['ot.attendance.dayoff', 'วันหยุด']
        };
        var key = employee.presence || 'dayoff';
        var pair = map[key] || map.dayoff;
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell';
        var chip = document.createElement('span');
        chip.className = 'ot-presence is-' + key;
        chip.setAttribute('data-i18n', pair[0]);
        chip.textContent = text(pair[0], pair[1]);
        cell.appendChild(chip);
        return cell;
      }

      function timeCell(employee, field) {
        var cell = document.createElement('span');
        /* เวลาเข้า-ออกใช้สีตามกะเดียวกับคอลัมน์กะงาน (Manager สั่ง)
           แถวที่จัดกลุ่มกะไม่ได้ตกไปใช้พื้นเหลืองกลางเหมือนเดิม */
        var tone = (employee.shift_group === 'morning' || employee.shift_group === 'night')
          ? ' is-shift-' + employee.shift_group
          : ' is-clock';
        cell.className = 'ot-table-cell ot-time' + tone;
        cell.textContent = employee[field] || '-';
        return cell;
      }

      // คอลัมน์ ขอ OT: ช่วงเวลาบรรทัดบน จำนวนชั่วโมงบรรทัดล่าง
      function otRequestCell(request) {
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell';

        if (!request) {
          var empty = document.createElement('span');
          empty.className = 'ot-status-plain';
          empty.textContent = '-';
          cell.appendChild(empty);
          return cell;
        }

        var wrap = document.createElement('span');
        wrap.className = 'ot-request-cell';
        var range = document.createElement('strong');
        range.textContent = request.requested_start + '–' + request.requested_end;
        var duration = document.createElement('small');
        duration.textContent = request.requested_hours + ' ' + text('ot.requests.hour', 'ชั่วโมง') + ' ' +
          (request.requested_minutes || 0) + ' ' + text('ot.requests.minute', 'นาที');
        wrap.append(range, duration);
        cell.appendChild(wrap);
        return cell;
      }

      function otTypeCell(request) {
        var label = request ? localized(request, 'ot_type_label', request.ot_type || '-') : '-';
        return valueCell(label, 'ot-type-cell');
      }

      function statusPresentation(key) {
        var map = {
          not_eligible: ['-', '-', '', '', 'neutral'],
          not_requested: ['ot.status.notRequested', 'ไม่มีการขอ OT', '', '', 'neutral'],
          not_worked_ot: ['ot.status.notWorkedOt', 'ไม่ได้ทำ OT', '', '', 'neutral'],
          draft: ['ot.status.pending', 'รอดำเนินการ', '', '', 'warning'],
          pending: ['ot.status.pending', 'รอดำเนินการ', 'ot.status.pendingDetail', '(รออนุมัติและสแกนล่าสุดหลังจบ OT)', 'warning'],
          waiting_approval: ['ot.status.waitingApproval', 'รอดำเนินการ', 'ot.status.waitingApprovalDetail', '(รออนุมัติ)', 'warning'],
          approved_waiting_scan: ['ot.status.approvedWaitingScan', 'รอดำเนินการ', 'ot.status.waitingScanDetail', '(รอสแกนล่าสุดหลังจบ OT)', 'warning'],
          success: ['ot.status.success', 'ผ่าน', '', '', 'success'],
          failed_time: ['ot.status.failedTime', 'ไม่ผ่าน', 'ot.status.failedTimeDetail', '(เวลา OT ไม่ครบ)', 'danger'],
          rejected: ['ot.status.rejected', 'ไม่อนุมัติ', '', '', 'danger'],
          exported: ['ot.status.exported', 'ส่งออกแล้ว', '', '', 'success']
        };
        var item = map[key] || map.not_requested;
        return {
          label: item[0] === '-' ? '-' : text(item[0], item[1]),
          detail: item[2] ? text(item[2], item[3]) : '',
          tone: item[4]
        };
      }

      function statusElement(key) {
        var presentation = statusPresentation(key);
        var status = document.createElement('span');
        status.className = 'ot-status';
        status.dataset.status = key;
        status.dataset.tone = presentation.tone;
        status.setAttribute('aria-label', [presentation.label, presentation.detail].filter(Boolean).join(' '));
        var badge = document.createElement('span');
        badge.className = 'ot-status-badge';
        badge.textContent = presentation.label;
        badge.title = presentation.label;
        status.appendChild(badge);
        if (presentation.detail) {
          var detail = document.createElement('small');
          detail.className = 'ot-status-detail';
          detail.textContent = presentation.detail;
          status.appendChild(detail);
        }
        return status;
      }

      function statusCell(statusKey) {
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell';
        cell.appendChild(statusElement(statusKey));
        return cell;
      }

      function plainTableCell(value) {
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell';
        var copy = document.createElement('span');
        copy.className = 'ot-status-plain';
        copy.textContent = value || '-';
        cell.appendChild(copy);
        return cell;
      }

      function requestNote(request) {
        return request?.request_note || request?.note || '';
      }

      function decisionNote(request) {
        return request?.decision_note || '';
      }

      function openReason(employee) {
        var request = employee.request || {};
        reasonPreviousFocus = document.activeElement;
        reasonModal.querySelector('[data-reason-title]').textContent = localized(employee, 'name', employee.code);
        reasonModal.querySelector('[data-reason-request]').textContent = requestNote(request) || '-';
        reasonModal.querySelector('[data-reason-decision]').textContent = decisionNote(request) || '-';
        reasonModal.hidden = false;
        reasonModal.querySelector('[data-close-reason]').focus();
      }

      function closeReason() {
        reasonModal.hidden = true;
        if (reasonPreviousFocus && document.contains(reasonPreviousFocus)) reasonPreviousFocus.focus();
        reasonPreviousFocus = null;
      }

      function actorCell(request, employee) {
        /* ยังไม่มีคำขอ OT ของวันนั้น = ไม่มีสถานะให้ไล่ดู แสดงขีดเดียวพอ (Manager สั่ง)
           ถ้าปล่อยให้วาดผังเปล่า ทุกแถวจะมีปุ่มเหมือนกันหมดจนแยกไม่ออกว่าใครยื่นจริง */
        if (!request) return plainTableCell('-');
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell';
        cell.appendChild(window.otProcessRoute.create(request, employee));
        return cell;
      }

      function reasonButton(employee) {
        var request = employee.request;
        if (!request || (!requestNote(request) && !decisionNote(request))) return plainTableCell('-');
        var cell = document.createElement('span');
        cell.className = 'ot-table-cell';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ot-reason-button';
        button.textContent = text('ot.reason.button', 'เหตุผล');
        button.addEventListener('click', function () { openReason(employee); });
        cell.appendChild(button);
        return cell;
      }

      function renderEmployees(payload) {
        try {
          renderEmployeesInner(payload);
        } catch (error) {
          console.error('renderEmployees failed:', error);
          modalBody.replaceChildren();
          var oops = document.createElement('p');
          oops.className = 'ot-employee-empty';
          oops.textContent = text('ot.attendance.loadError', 'แสดงรายชื่อไม่สำเร็จ ลองปิดแล้วเปิดใหม่อีกครั้ง');
          modalBody.appendChild(oops);
        }
      }

      function renderEmployeesInner(payload) {
        lastEmployeePayload = payload;
        modalBody.replaceChildren();

        if (!payload.available) {
          var unavailable = document.createElement('div');
          unavailable.className = 'ot-modal-loading';
          unavailable.textContent = text('ot.attendance.bplusUnavailable', 'ไม่สามารถอ่านข้อมูล Bplus ได้');
          modalBody.appendChild(unavailable);
          return;
        }

        if (!payload.employees || !payload.employees.length) {
          var empty = document.createElement('div');
          empty.className = 'ot-modal-loading';
          empty.textContent = text('ot.attendance.noEmployees', 'ไม่พบพนักงานในแผนกนี้');
          modalBody.appendChild(empty);
          shiftFilterValue = window.otShiftFilter.build(shiftFilterSelect, [], shiftFilterValue);
          return;
        }

        /* สร้างตัวเลือกกะจากคนทั้งแผนกก่อนกรอง ไม่งั้นพอกรองแล้วตัวเลือกอื่นจะหายจนกลับไม่ได้ */
        shiftFilterValue = window.otShiftFilter.build(shiftFilterSelect, payload.employees, shiftFilterValue);
        // ส่ง null แทน modalBody: พื้นโมดัลต้องขาวเสมอ (Manager สั่ง)
        // สีบอกกะให้อยู่ที่คอลัมน์กะงานกับเวลาเข้า-ออกเท่านั้น
        window.otShiftFilter.paint(shiftFilterSelect, null, shiftFilterValue, payload.employees);
        var visible = payload.employees.filter(function (employee) {
          return window.otShiftFilter.matches(employee, shiftFilterValue)
            && (!window.otBranchFilter || window.otBranchFilter.matches(employee, branchFilterValue))
            && window.otEmployeeSearch.matches(employee, employeeQuery)
            && matchesColumnFilters(employee);
        });

        /* เรียงตามตัวเลือก: รหัสพนักงาน (ค่าเริ่มต้น) หรือ ตำแหน่งสูง→ต่ำ
           ตำแหน่งเท่ากันให้เรียงรหัสต่อ จะได้ลำดับคงที่ไม่สลับไปมาทุกครั้งที่โหลด */
        if (sortMode === 'rank' || sortMode === 'rank_asc') {
          var dir = sortMode === 'rank' ? 1 : -1;
          visible = visible.slice().sort(function (a, b) {
            var diff = ((a.position_rank || 999) - (b.position_rank || 999)) * dir;
            return diff !== 0 ? diff : String(a.code).localeCompare(String(b.code));
          });
        }

        if (!visible.length) {
          var none = document.createElement('div');
          none.className = 'ot-modal-loading';
          // แยกข้อความให้รู้ว่าไม่เจอเพราะคำค้น หรือเพราะกะที่เลือก
          none.textContent = employeeQuery.trim() !== ''
            ? text('ot.search.empty', 'ไม่พบพนักงานที่ค้นหา')
            : text('ot.shiftFilter.empty', 'ไม่พบพนักงานในกะที่เลือก');
          modalBody.appendChild(none);
          return;
        }

        var head = document.createElement('div');
        head.className = 'ot-employee-head';
        head.appendChild(headerCell('common.rowNo', 'ลำดับ', 'is-order'));
        head.appendChild(headerCell('ot.attendance.employee', 'พนักงาน'));
        head.appendChild(filterableHeaderCell('ot.attendance.position', 'ตำแหน่ง', 'position', payload.employees));
        head.appendChild(filterableHeaderCell('ot.requests.shift', 'กะงาน', 'shift', payload.employees));
        head.appendChild(headerCell('ot.attendance.clockIn', 'เข้างาน', 'is-clock'));
        head.appendChild(headerCell('ot.attendance.clockOut', 'เวลาล่าสุด / ออกงาน', 'is-clock'));
        head.appendChild(filterableHeaderCell('ot.attendance.presence', 'การทำงาน', 'presence', payload.employees, function (v) {
          return text('ot.attendance.' + v, { present: 'เข้างาน', absent: 'ไม่เข้างาน', dayoff: 'วันหยุด' }[v] || v);
        }));
        head.appendChild(headerCell('ot.attendance.otRequested', 'ขอ OT'));
        head.appendChild(headerCell('ot.requests.otType', 'ผลจากลักษณะการรูดบัตร'));
        head.appendChild(filterableHeaderCell('ot.attendance.status', 'สถานะ', 'status', payload.employees, function (v) {
          return text('ot.status.' + v, v);
        }));
        head.appendChild(headerCell('ot.reason.button', 'เหตุผล'));
        head.appendChild(headerCell('ot.route.column', 'สถานะทั้งหมด'));
        modalBody.appendChild(head);

        var fragment = document.createDocumentFragment();
        visible.forEach(function (employee, index) {
          var row = document.createElement('div');
          row.className = 'ot-employee-row';

          var person = document.createElement('span');
          person.className = 'ot-person';
          person.appendChild(avatar(employee));
          var personCopy = document.createElement('span');
          personCopy.className = 'ot-person-copy';
          /* ชื่อกดได้ → เปิดแท็บใหม่ดูประวัติ OT + การลาทั้งเดือนของคนนั้น
             ใช้ <a target="_blank"> เพื่อให้คลิกขวา/คลิกกลางเปิดแท็บได้ตามปกติ */
          var name = document.createElement('strong');
          var nameLink = document.createElement('a');
          nameLink.className = 'ot-person-link';
          nameLink.target = '_blank';
          nameLink.rel = 'noopener';
          nameLink.href = @json(url('/ot-approval/employees')) + '/'
            + encodeURIComponent(employee.company || activeDepartment.companyCode)
            + '/' + encodeURIComponent(employee.code)
            + '?month=' + encodeURIComponent(selectedDate.slice(0, 7));
          nameLink.textContent = localized(employee, 'name', employee.code);
          nameLink.addEventListener('click', function (event) { event.stopPropagation(); });
          name.appendChild(nameLink);
          var code = document.createElement('small');
          code.textContent = employee.code || '—';
          personCopy.appendChild(name);
          personCopy.appendChild(code);
          person.appendChild(personCopy);

          var personCell = document.createElement('span');
          personCell.className = 'ot-table-cell';
          personCell.appendChild(person);
          var orderCell = document.createElement('span');
          orderCell.className = 'ot-table-cell is-order';
          // นับจากแถวที่มองเห็นจริง จึงเริ่มที่ 1 ใหม่ทุกครั้งที่เปลี่ยนตัวกรองหรือการเรียง
          orderCell.textContent = String(index + 1);
          row.appendChild(orderCell);
          row.appendChild(personCell);
          row.appendChild(valueCell(localized(employee, 'position', '—')));
          var shiftCell = document.createElement('span');
          shiftCell.className = 'ot-table-cell ot-time';
          var shiftText = employee.shift_in && employee.shift_out ? employee.shift_in + '–' + employee.shift_out : '-';
          // ระบายสีทั้งช่อง: กะเวลา A ส้ม · กะเวลา B ม่วง — ตอนเลือก "ทุกกะ" จะแยกออกทันที
          if (employee.shift_group === 'morning' || employee.shift_group === 'night') {
            shiftCell.className += ' is-shift-' + employee.shift_group;
          }
          shiftCell.textContent = shiftText;
          row.appendChild(shiftCell);
          row.appendChild(timeCell(employee, 'clock_in'));
          row.appendChild(timeCell(employee, 'clock_out'));
          row.appendChild(presenceCell(employee));
          row.appendChild(otRequestCell(employee.request));
          row.appendChild(otTypeCell(employee.request));
          var statusKey = employee.status_key || 'not_requested';
          if (statusKey === 'not_eligible') {
            row.appendChild(plainTableCell('-'));
          } else {
            row.appendChild(statusCell(statusKey));
          }
          row.appendChild(reasonButton(employee));
          row.appendChild(actorCell(employee.request, employee));
          fragment.appendChild(row);
        });
        modalBody.appendChild(fragment);
      }

      function renderLoading() {
        modalBody.replaceChildren();
        var loading = document.createElement('div');
        loading.className = 'ot-modal-loading';
        var content = document.createElement('span');
        var mark = document.createElement('span');
        mark.className = 'ot-loading-mark';
        mark.setAttribute('aria-hidden', 'true');
        content.appendChild(mark);
        content.appendChild(document.createTextNode(text('ot.attendance.loadingEmployees', 'กำลังดึงข้อมูลจาก Bplus')));
        loading.appendChild(content);
        modalBody.appendChild(loading);
      }

      function updateModalHeading() {
        if (!activeDepartment) return;
        modalTitle.textContent = localized(activeDepartment, 'deptName', '—');
        // บรรทัดบริษัท/วันที่ถูกเอาออกจากหัวโมดัลแล้ว เก็บเงื่อนไขไว้กันพังถ้ามีการใส่กลับ
        if (modalMeta) modalMeta.textContent = activeDepartment.companyLabel + ' · ' + formatDate(selectedDate);
      }

      async function loadEmployees(department, quiet) {
        if (!quiet) renderLoading();
        var params = new URLSearchParams({
          date: selectedDate,
          company: department.companyCode,
          dept_code: department.deptCode
        });
        try {
          var response = await fetchJson(employeesUrl + '?' + params.toString());
          if (!response.ok || !response.attendance) throw new Error('Invalid response');
          renderEmployees(response.attendance);
        } catch (error) {
          if (!quiet) {
            modalBody.replaceChildren();
            var errorNode = document.createElement('div');
            errorNode.className = 'ot-modal-loading';
            errorNode.textContent = text('ot.attendance.bplusUnavailable', 'ไม่สามารถอ่านข้อมูล Bplus ได้');
            modalBody.appendChild(errorNode);
          }
        }
      }

      /* ── ค้นหาพนักงาน + เลือกวันจากใน modal ────────────────────────────
         ทั้งสองอย่างต้องไม่ปิด modal เพราะผู้ใช้กำลังดูแผนกนี้อยู่
         ค้นหากรองในเครื่อง (รายชื่อทั้งแผนกโหลดมาครบแล้ว) ส่วนเปลี่ยนวันต้องโหลดใหม่ */
      var modalSearch = modal.querySelector('[data-emp-search]');
      var modalDate = modal.querySelector('[data-modal-date]');
      var employeeQuery = '';

      if (modalSearch) {
        modalSearch.addEventListener('input', function () {
          employeeQuery = modalSearch.value;
          if (lastEmployeePayload) renderEmployees(lastEmployeePayload);
        });
      }

      if (modalDate) {
        modalDate.addEventListener('change', function () {
          if (!modalDate.value || !activeDepartment) return;

          selectedDate = modalDate.value;
          updateModalHeading();
          loadEmployees(activeDepartment, false);
          // อัปเดตหน้าเบื้องหลังให้ตรงกับวันที่เพิ่งเลือก โดยไม่ปิด modal
          syncPageDate(selectedDate);
        });
      }

      /* หน้าเบื้องหลังยังโชว์ยอดของวันเดิมอยู่ ต้องดึงสรุปใหม่และแก้ URL ให้ตรง
         ถ้าไม่ทำ พอปิด modal จะเห็นตัวเลขคนละวันกับที่เพิ่งดู */
      function syncPageDate(date) {
        var picker = root.querySelector('[data-date-picker]');
        if (picker && picker.value !== date) picker.value = date;

        try {
          var url = new URL(window.location.href);
          url.searchParams.set('date', date);
          window.history.replaceState({}, '', url);
        } catch (error) { /* URL API ใช้ไม่ได้ก็ข้ามไป ไม่ใช่สาระสำคัญ */ }

        refreshSummary();
      }

      function openDepartment(button) {
        previousFocus = button;
        employeeQuery = '';
        if (modalSearch) modalSearch.value = '';
        if (modalDate) modalDate.value = selectedDate;
        activeDepartment = {
          companyCode: button.dataset.companyCode,
          companyLabel: button.dataset.companyLabel,
          deptCode: button.dataset.deptCode,
          deptName_th: button.dataset.deptNameTh,
          deptName_en: button.dataset.deptNameEn,
          deptName_my: button.dataset.deptNameMy
        };
        lastEmployeePayload = null;
        updateModalHeading();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-close-modal]').focus();
        loadEmployees(activeDepartment, false);
      }

      function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        activeDepartment = null;
        lastEmployeePayload = null;
        if (previousFocus) previousFocus.focus();
      }

      root.querySelectorAll('[data-open-department]').forEach(function (button) {
        button.addEventListener('click', function () { openDepartment(button.closest('[data-department-card]') || button); });
      });

      /* ── กดแถวกะเพื่อดูช่วงเวลาของกะนั้น ───────────────────────
         ยอดเข้า/ออกอยู่ในแถวอยู่แล้ว ที่เปิดเพิ่มคือช่วงเวลากะ (เช่น 08:00-17:00)
         ซึ่งกลุ่มเดียวมีได้หลายช่วง จึงไม่ยัดลงการ์ดตั้งแต่แรก */
      root.querySelectorAll('[data-shift-pill]').forEach(function (pill) {
        pill.addEventListener('click', function (event) {
          event.stopPropagation();
          var card = pill.closest('[data-department-card]');
          var note = card.querySelector('[data-shift-note]');
          var group = pill.dataset.shiftPill;
          var opened = pill.getAttribute('aria-expanded') === 'true';

          card.querySelectorAll('[data-shift-pill]').forEach(function (item) {
            item.setAttribute('aria-expanded', 'false');
          });

          if (opened) { note.hidden = true; return; }

          /* แสดงช่วงเวลาของกะนั้นทุกช่วง ยาวเกินการ์ดก็ตัดขึ้นบรรทัดใหม่เอง */
          pill.setAttribute('aria-expanded', 'true');
          note.replaceChildren();
          note.append(
            Object.assign(document.createElement('b'), { textContent: shiftLabel(group) }),
            document.createTextNode(' · ' + (pill.dataset.shiftTimes || text('ot.attendance.shiftUnknown', 'ไม่ระบุกะ')))
          );
          note.hidden = false;
        });
      });

      function shiftLabel(group) {
        return group === 'night'
          ? text('ot.settings.shift.night', 'กะเวลา B')
          : text('ot.settings.shift.morning', 'กะเวลา A');
      }
      var sortFilterSelect = modal.querySelector('[data-sort-filter]');
      if (sortFilterSelect) {
        sortFilterSelect.addEventListener('change', function () {
          sortMode = sortFilterSelect.value;
          if (lastEmployeePayload) renderEmployees(lastEmployeePayload);
        });
      }
      /* ตัวกรองสาขา — ต้องเจาะจงว่าเป็นตัวในโมดัล เผื่ออนาคตมีอีกตัวบนหน้า
         เทียบด้วยชื่อสาขา ไม่ใช่รหัส เพราะรหัสซ้ำความหมายข้ามบริษัท (ดู OtBranchFilter) */
      var branchFilterSelect = modal.querySelector('[data-branch-filter]');
      if (branchFilterSelect) {
        branchFilterSelect.addEventListener('change', function () {
          branchFilterValue = branchFilterSelect.value;
          if (lastEmployeePayload) renderEmployees(lastEmployeePayload);
        });
      }
      // คลิกที่ไหนก็ได้นอกเมนูให้ปิด — เมนูกรองไม่ควรค้างเปิดทับตาราง
      modal.addEventListener('click', function () {
        modal.querySelectorAll('.ot-col-menu').forEach(function (m) { m.hidden = true; });
      });

      if (shiftFilterSelect) shiftFilterSelect.addEventListener('change', function () {
        shiftFilterValue = shiftFilterSelect.value;
        if (lastEmployeePayload) renderEmployees(lastEmployeePayload);
      });
      modal.querySelector('[data-close-modal]').addEventListener('click', closeModal);
      modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
      });
      /* ── กดรูปพนักงานเพื่อดูแบบขยาย ────────────────────────
         ใช้ delegation แบบ capture เพราะแถวพนักงานถูกสร้างด้วย JS หลังเปิดโมดัล */
      var lightbox = document.querySelector('[data-ot-lightbox]');
      var lightboxImage = lightbox.querySelector('[data-lightbox-image]');
      var lightboxCaption = lightbox.querySelector('[data-lightbox-caption]');

      function closeLightbox() {
        if (lightbox.hidden) return;
        lightbox.hidden = true;
        lightboxImage.removeAttribute('src');
      }

      function openLightbox(image) {
        if (!image.getAttribute('src')) return;
        lightboxImage.src = image.currentSrc || image.src;
        lightboxImage.alt = image.alt || '';

        // ชื่อ/รหัสอยู่ในบล็อกข้าง ๆ รูปในแถวเดียวกัน
        var copy = image.parentElement ? image.parentElement.querySelector('.ot-person-copy') : null;
        lightboxCaption.replaceChildren();
        if (copy) {
          var nameNode = copy.querySelector('strong');
          var codeNode = copy.querySelector('small');
          if (nameNode) {
            var nameEl = document.createElement('strong');
            nameEl.textContent = nameNode.textContent;
            lightboxCaption.appendChild(nameEl);
          }
          if (codeNode && codeNode.textContent.trim() !== '—') {
            var codeEl = document.createElement('small');
            codeEl.textContent = codeNode.textContent;
            lightboxCaption.appendChild(codeEl);
          }
        }
        lightboxCaption.hidden = !lightboxCaption.childNodes.length;
        lightbox.hidden = false;
      }

      document.addEventListener('click', function (event) {
        var image = event.target.closest('img.ot-avatar');
        if (!image) return;
        event.preventDefault();
        event.stopPropagation();
        openLightbox(image);
      }, true);

      lightbox.addEventListener('click', function (event) {
        // คลิกพื้นหลังหรือปุ่มปิด = ปิด, คลิกที่ตัวรูปไม่ปิด
        if (event.target.closest('[data-lightbox-close]') || !event.target.closest('.ot-lightbox-figure')) {
          closeLightbox();
        }
      });

      reasonModal.querySelectorAll('[data-close-reason]').forEach(function (button) {
        button.addEventListener('click', closeReason);
      });
      reasonModal.addEventListener('click', function (event) {
        if (event.target === reasonModal) closeReason();
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        // ถ้ากำลังดูรูปขยายอยู่ ให้ Esc ปิดแค่รูป ไม่ปิดโมดัลรายชื่อข้างหลัง
        if (!lightbox.hidden) {
          closeLightbox();
          return;
        }
        if (!reasonModal.hidden) {
          closeReason();
          return;
        }
        if (!modal.hidden) closeModal();
      });

      root.querySelector('[data-date-picker]').addEventListener('change', function (event) {
        if (!event.target.value) return;
        window.location.assign(@json(route('ot-approval.home')) + '?date=' + encodeURIComponent(event.target.value));
      });

      var exportScope = root.querySelector('[data-ot-export-scope]');
      var exportCycle = root.querySelector('[data-ot-export-cycle]');
      var exportDateOption = exportScope ? exportScope.querySelector('option[value="date"]') : null;
      var syncExportDateLabel = function () {
        if (!exportDateOption) return;
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var label = exportDateOption.getAttribute('data-label-' + lang)
          || exportDateOption.getAttribute('data-label-th');
        if (label) exportDateOption.textContent = label;
      };
      if (exportScope && exportCycle) {
        var syncExportCycle = function () {
          exportCycle.hidden = exportScope.value !== 'cycle';
          exportCycle.required = exportScope.value === 'cycle';
        };
        exportScope.addEventListener('change', syncExportCycle);
        syncExportCycle();
      }
      syncExportDateLabel();

      document.addEventListener('insight:languagechange', function () {
        setSourceRefreshing(false);
        syncExportDateLabel();
        updateModalHeading();
        if (lastEmployeePayload && !modal.hidden) renderEmployees(lastEmployeePayload);
      });

      setSourceRefreshing(false);
      /* กดย้อนกลับเข้ามา เบราว์เซอร์อาจคืนหน้าเดิมจาก bfcache พร้อมสถานะที่กางค้างไว้
         จึงสั่งย่อบริษัททุกอันและปิดโมดัล/รูปขยายให้กลับเป็นสถานะเริ่มต้น */
      window.addEventListener('pageshow', function () {
        document.body.style.overflow = '';
        root.querySelectorAll('.ot-company[open]').forEach(function (company) {
          company.open = false;
        });
        closeLightbox();
        closeReason();
        if (!modal.hidden) closeModal();
      });

      // Local demo และ Snapshot จะเปลี่ยนเมื่อสั่ง sync เท่านั้น จึงไม่ยิง API/สร้าง DOM ซ้ำทุก 30 วินาที
      if (isToday && ['local', 'snapshot'].indexOf(sourceMode) === -1) window.setInterval(refreshSummary, 30000);
    })();
  </script>
@endsection
