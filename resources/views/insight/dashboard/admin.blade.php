@extends('layouts.portal')

@section('title', 'ภาพรวมระบบ')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="admin.kicker">ผู้ดูแลระบบ</span>
  <span class="tt-title" data-i18n="admin.title">ภาพรวมระบบ</span>
@endsection

@section('page-style')
    .admin-wrap {
      width: min(100%, 72rem);
      margin: clamp(.5rem, 2vw, 1rem) auto 0;
    }

    /* ── เมตริกสรุป (ตัวเลขล้วน) ── */
    .admin-metrics {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: clamp(.8rem, 2vw, 1.2rem);
      width: min(100%, 48rem);
      margin: 0 auto clamp(1.6rem, 4vw, 2.4rem);
    }
    @media (max-width: 620px) { .admin-metrics { grid-template-columns: 1fr; } }

    .admin-card {
      padding: clamp(1.1rem, 2.2vw, 1.5rem);
      border: 1px solid var(--line-light);
      border-radius: 0.36rem;
      background: var(--panel-soft);
    }
    .admin-card { text-align: center; }
    .admin-card dt { color: var(--muted-light); font-size: .72rem; letter-spacing: .07em; text-transform: uppercase; }
    .admin-card dd { margin-top: .35rem; font-family: var(--font-display); font-size: clamp(2rem, 3.6vw, 2.6rem); line-height: 1; }

    /* ── บริษัท -> แผนก ── */
    .admin-section-head {
      display: flex; align-items: center; justify-content: center; flex-direction: column;
      gap: .75rem; margin-bottom: 1rem; text-align: center;
    }
    .admin-section-label {
      margin-bottom: 0;
      color: var(--muted-light);
      font-family: "Montserrat", var(--font-body);
      font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;
    }
    /* เรียงเป็นชุดติดกันแบบ segmented: ยอดรวมอยู่ซ้ายเป็นตัวตั้ง
       แล้วตามด้วยการแยกย่อย 2 ตัว อ่านไล่จากซ้ายไปขวาได้ในทีเดียว */
    .resigned-actions { display: inline-flex; align-items: stretch; justify-content: flex-end; gap: 0; flex-wrap: wrap; }
    .resigned-btn {
      display: inline-flex; align-items: center; gap: .45rem; flex: 0 0 auto;
      padding: .45rem .85rem; border: 1px solid var(--line-strong); border-radius: 0;
      margin-left: -1px;
      background: var(--panel-soft); color: var(--light-text); cursor: var(--cursor-action);
      font-size: .82rem; font-weight: 600; white-space: nowrap;
      transition: background-color .2s var(--ease-out), border-color .2s var(--ease-out);
    }
    .resigned-btn:first-child { margin-left: 0; }
    /* ปุ่มยอดรวมเป็นตัวเด่นของชุด ต้องอยู่บนสุดตอนขอบซ้อนกัน */
    .resigned-btn.is-all { position: relative; z-index: 1; }
    .resigned-btn:hover { background: var(--hover-soft); border-color: var(--light-text); }
    /* ลาออก (รวม) = ปุ่มแดงตัวเดียว อยู่ขวาสุด */
    .resigned-btn.is-all { border-color: rgb(217 83 79 / 55%); background: rgb(217 83 79 / 10%); color: #d9534f; }
    .resigned-btn.is-all:hover { border-color: #d9534f; background: rgb(217 83 79 / 18%); }
    .emp-card .en-sub.resigned { color: #d98a80; }

    .active-search-panel {
      margin: 0 auto 1rem; padding: 1rem;
      border: 1px solid var(--line-light); border-radius: 0.36rem; background: var(--panel-soft);
    }
    .active-search-panel .resigned-result-count:empty { display: none; }

    .admin-company {
      border: 1px solid var(--line-light);
      border-radius: 0.36rem;
      background: var(--panel-soft);
      margin: 0 auto .8rem;
      overflow: hidden;
    }

    .admin-company > summary {
      display: flex; align-items: center; justify-content: space-between; gap: 1rem;
      padding: 1rem 1.2rem;
      cursor: pointer;
      list-style: none;
    }
    .admin-company > summary::-webkit-details-marker { display: none; }
    .admin-company > summary:hover { background: var(--hover-soft); }

    .ac-name { font-size: 1.05rem; font-weight: 600; }
    .ac-meta { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; flex-wrap: wrap; color: var(--muted-light); font-size: .85rem; text-align: right; }
    .ac-meta b { color: var(--light-text); font-weight: 700; }
    .ac-meta .ac-total { color: var(--moss); }
    .admin-company[open] > summary .ac-caret { transform: rotate(90deg); }
    .ac-caret { width: 1rem; height: 1rem; flex: 0 0 auto; transition: transform .2s var(--ease-out); fill: none; stroke: var(--muted-light); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .ac-left { display: flex; align-items: center; gap: .7rem; min-width: 0; }

    .dept-list { list-style: none; margin: 0; padding: 0 .6rem .7rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: .35rem; }

    .dept-row {
      width: 100%;
      display: grid; grid-template-columns: 2.5rem minmax(0, 1fr) 2.5rem; align-items: center; gap: .4rem;
      padding: .65rem .8rem;
      border: 1px solid transparent; border-radius: 0.25rem;
      background: transparent; color: var(--light-text); cursor: pointer;
      font-size: .9rem; text-align: center;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out);
    }
    .dept-row::before { content: ''; }
    .dept-row:hover { border-color: var(--moss); background: var(--hover-soft); }
    .dept-row > span:first-of-type { min-width: 0; text-align: center; }
    .dept-row .dept-count { flex: 0 0 auto; color: var(--moss); font-weight: 700; font-size: .82rem; }
    .dept-empty { padding: 0 1.2rem 1rem; color: var(--muted-light); font-size: .88rem; }

    /* ── Modal รายชื่อพนักงานในแผนก ── */
    .emp-modal {
      position: fixed; inset: 0; z-index: 90;
      display: grid; place-items: center; padding: 1.25rem;
      background: var(--overlay-bg); backdrop-filter: blur(4px);
      opacity: 0; visibility: hidden; transition: opacity .22s var(--ease-out), visibility .22s;
    }
    .emp-modal.is-open { opacity: 1; visibility: visible; }

    .emp-dialog {
      width: min(100%, 44rem); max-height: min(86vh, 50rem);
      display: flex; flex-direction: column;
      border: 1px solid var(--line-light); border-radius: 0.4rem; background: var(--panel);
    }
    .employee-detail-modal { z-index: 110; }
    .employee-detail-dialog { width: min(100%, 64rem); }
    .emp-dialog-head {
      display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
      padding: 1.2rem 1.4rem; border-bottom: 1px solid var(--line-light);
    }
    .emp-dialog-head .ed-kicker { color: var(--moss); font-family: "Montserrat", var(--font-body); font-size: .64rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .emp-dialog-head h2 { margin-top: .25rem; font-family: var(--font-display); font-size: clamp(1.3rem, 2.4vw, 1.7rem); font-weight: 400; }
    .emp-close {
      width: 2rem; height: 2rem; flex: 0 0 auto;
      display: grid; place-items: center; border: 0; border-radius: 50%;
      background: transparent; color: var(--muted-light); cursor: pointer;
      transition: background-color .2s var(--ease-out), color .2s var(--ease-out);
    }
    .emp-close:hover { background: var(--hover-soft); color: var(--light-text); }
    .emp-close svg { width: 1.2rem; height: 1.2rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }

    .emp-body { padding: 1.1rem 1.4rem 1.4rem; overflow-y: auto; }
    .emp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); gap: .7rem; }

    .resigned-filter-panel { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--line-light); }
    .resigned-filters {
      display: grid; grid-template-columns: minmax(12rem, 1.5fr) repeat(2, minmax(9rem, 1fr)) auto auto;
      align-items: end; gap: .65rem;
    }
    .resigned-filter { display: grid; gap: .3rem; min-width: 0; }
    .resigned-filter label { color: var(--muted-light); font-size: .72rem; font-weight: 700; }
    .resigned-filter input, .resigned-filter select {
      width: 100%; min-height: 2.45rem; padding: .55rem .7rem;
      border: 1px solid var(--line-strong); border-radius: 0.25rem;
      background: var(--panel); color: var(--light-text); font: inherit; font-size: .84rem;
    }
    .resigned-filter input::placeholder { color: var(--muted-light); opacity: 1; }
    .resigned-filter input:focus, .resigned-filter select:focus { outline: 2px solid var(--moss); outline-offset: 1px; border-color: var(--moss); }
    .resigned-filter-btn {
      min-height: 2.45rem; padding: .55rem .85rem; border: 1px solid var(--moss); border-radius: 0.25rem;
      background: var(--moss); color: #fff; cursor: pointer; font: inherit; font-size: .82rem; font-weight: 700; white-space: nowrap;
    }
    .resigned-filter-btn:hover { filter: brightness(.94); }
    .resigned-filter-btn.is-secondary { border-color: var(--line-strong); background: transparent; color: var(--light-text); }
    .resigned-filter-btn.is-secondary:hover { background: var(--hover-soft); filter: none; }
    .resigned-result-count { margin-top: .65rem; color: var(--muted-light); font-size: .78rem; }
    .resigned-no-results[hidden], .emp-card[hidden] { display: none; }

    @media (max-width: 820px) {
      .resigned-filters { grid-template-columns: 1fr 1fr; }
      .resigned-filter.is-query { grid-column: 1 / -1; }
    }
    @media (max-width: 520px) {
      .admin-section-head { align-items: center; flex-direction: column; }
      .resigned-actions { width: 100%; justify-content: center; }
      .resigned-filters { grid-template-columns: 1fr; }
      .resigned-filter.is-query { grid-column: auto; }
      .resigned-filter-btn { width: 100%; }
    }

    .emp-card {
      display: flex; align-items: center; gap: .85rem;
      padding: .7rem .8rem;
      border: 1px solid var(--line-light); border-radius: 0.28rem; background: var(--panel-soft);
    }
    .emp-card-button {
      position: relative; width: 100%; padding-right: 2.2rem;
      appearance: none; color: var(--light-text); font: inherit; text-align: left; cursor: pointer;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out), transform .2s var(--ease-out);
    }
    .emp-card-button::after {
      content: '›'; position: absolute; right: .85rem; top: 50%; transform: translateY(-52%);
      color: var(--muted-light); font-size: 1.25rem;
    }
    .emp-card-button:hover, .emp-card-button:focus-visible {
      border-color: var(--moss); background: var(--hover-soft); transform: translateY(-1px); outline: none;
    }
    .emp-avatar {
      width: 3rem; height: 3rem; flex: 0 0 auto;
      display: grid; place-items: center; overflow: hidden; border-radius: 50%;
      border: 1px solid var(--line-strong); background: var(--panel);
      color: var(--light-text); font-size: 1.05rem; font-weight: 600;
    }
    .emp-avatar img { width: 100%; height: 100%; object-fit: cover; }
    button.emp-avatar { padding: 0; cursor: zoom-in; appearance: none; transition: border-color .2s var(--ease-out), transform .2s var(--ease-out); }
    button.emp-avatar:hover, button.emp-avatar:focus-visible { border-color: var(--moss); transform: translateY(-1px); }

    .emp-info { min-width: 0; }
    .emp-info .en-name { font-size: .95rem; font-weight: 600; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .emp-info .en-pos { color: var(--muted-light); font-size: .82rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .emp-info .en-sub { color: var(--moss); font-family: "Montserrat", var(--font-body); font-size: .66rem; font-weight: 800; letter-spacing: .08em; margin-top: .15rem; }

    .emp-state { padding: 2rem 0; text-align: center; color: var(--muted-light); }

    .employee-profile {
      display: flex; align-items: center; gap: 1rem; padding-bottom: 1.15rem;
      border-bottom: 1px solid var(--line-light);
    }
    .employee-profile .emp-avatar { width: 4.5rem; height: 4.5rem; font-size: 1.35rem; }
    .employee-profile .employee-profile-photo { box-shadow: 0 0 0 0 rgb(91 141 239 / 0); }
    .employee-profile .employee-profile-photo:hover,
    .employee-profile .employee-profile-photo:focus-visible {
      box-shadow: 0 0 0 .2rem rgb(91 141 239 / 18%);
    }
    /* ตัวดูภาพส่วนกลางต้องอยู่เหนือ Modal รายละเอียดพนักงาน (z-index 110) */
    .image-viewer { z-index: 140; }
    .employee-profile-copy { min-width: 0; }
    .employee-profile-copy h3 { font-size: clamp(1.15rem, 2.5vw, 1.45rem); line-height: 1.25; }
    .employee-profile-copy p { margin-top: .25rem; color: var(--muted-light); font-size: .88rem; }
    .employee-profile-meta { display: flex; gap: .45rem .8rem; flex-wrap: wrap; margin-top: .4rem; color: var(--moss); font-size: .76rem; font-weight: 700; }
    .leave-section-head {
      display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem;
      margin: 1.15rem 0 .65rem;
    }
    .leave-section-head h3 { font-size: 1rem; }
    .leave-source { color: var(--muted-light); font-size: .75rem; text-align: right; }
    .leave-source strong { color: var(--moss); }
    .leave-table-wrap { overflow-x: auto; border: 1px solid var(--line-light); border-radius: 0.3rem; }
    .leave-table { width: 100%; min-width: 38rem; border-collapse: collapse; }
    .leave-table th, .leave-table td { padding: .7rem .85rem; border-bottom: 1px solid var(--line-light); }
    .leave-table th { color: var(--muted-light); background: var(--panel-soft); font-size: .72rem; font-weight: 700; text-align: right; }
    .leave-table th:first-child, .leave-table td:first-child { text-align: left; }
    .leave-table td { font-variant-numeric: tabular-nums; font-size: .86rem; text-align: right; }
    .leave-table tr:last-child td { border-bottom: 0; }
    .leave-name { font-weight: 600; }
    .leave-name small { display: block; margin-top: .1rem; color: var(--muted-light); font-weight: 400; }
    .leave-remaining { color: var(--moss); font-weight: 800; }
    .leave-remaining.is-negative { color: #d9534f; }
    .leave-note { margin-top: .65rem; color: var(--muted-light); font-size: .74rem; }
    @media (max-width: 520px) {
      .employee-profile { align-items: flex-start; }
      .employee-profile .emp-avatar { width: 3.7rem; height: 3.7rem; }
      .leave-section-head { align-items: flex-start; flex-direction: column; }
      .leave-source { text-align: left; }
    }
@endsection

@section('content')
  <div class="admin-wrap">
    <dl class="admin-metrics">
      <div class="admin-card">
        <dt data-i18n="admin.active">พนักงานที่ทำงานอยู่</dt>
        <dd>{{ number_format($stats['active']) }}</dd>
      </div>
      <div class="admin-card">
        <dt data-i18n="admin.companies">บริษัท</dt>
        <dd>{{ number_format($stats['company_count']) }}</dd>
      </div>
      <div class="admin-card">
        <dt data-i18n="admin.resigned">พนักงานที่ลาออกทั้งหมด</dt>
        <dd>{{ number_format($stats['resigned']) }}</dd>
      </div>
    </dl>

    <div class="admin-section-head">
      <p class="admin-section-label" data-i18n="admin.byCompany">พนักงานแยกตามบริษัทและแผนก</p>
      <div class="resigned-actions" aria-label="สถานะพนักงานลาออก" data-i18n-aria="admin.resignedStatusTabs">
        <button type="button" class="resigned-btn is-all" data-resigned-open="all" aria-haspopup="dialog">
          <span data-i18n="admin.resignedAllShort">ลาออกทั้งหมด</span> {{ number_format($stats['resigned']) }}
        </button>
        <button type="button" class="resigned-btn is-pending" data-resigned-open="pending" aria-haspopup="dialog">
          <span data-i18n="admin.pendingResignShort">รอปิดงวด</span> {{ number_format($stats['pending_resignation']) }}
        </button>
        <button type="button" class="resigned-btn" data-resigned-open="closed" aria-haspopup="dialog">
          <span data-i18n="admin.payrollClosedShort">ปิดงวดแล้ว</span> {{ number_format($stats['payroll_closed']) }}
        </button>
      </div>
    </div>

    <section class="active-search-panel" aria-label="ค้นหาพนักงานที่ทำงานอยู่" data-i18n-aria="admin.activeEmployeeSearch">
      <form class="resigned-filters" id="activeEmployeeSearchForm" role="search">
        <div class="resigned-filter is-query">
          <label for="activeEmployeeQuery" data-i18n="admin.employeeSearch">ค้นหาพนักงาน</label>
          <input type="search" id="activeEmployeeQuery" autocomplete="off"
        placeholder="รหัสพนักงาน หรือชื่อ-สกุล" data-i18n-placeholder="admin.employeeSearchPlaceholder"
                 data-i18n-placeholder="admin.employeeSearchPlaceholder">
        </div>
        <div class="resigned-filter">
          <label for="activeEmployeeDepartment" data-i18n="field.department">แผนก</label>
          <select id="activeEmployeeDepartment">
            <option value="" data-i18n="admin.allDepartments">ทุกแผนก</option>
            @foreach ($stats['active_departments'] as $department)
              <option value="{{ $department['key'] }}"
                      data-option-th="{{ $department['name_th'] }} · {{ $department['company'] }}"
                      data-option-en="{{ $department['name_en'] }} · {{ $department['company'] }}"
                      data-option-my="{{ $department['name_en'] }} · {{ $department['company'] }}">
                {{ $department['name_th'] }} · {{ $department['company'] }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="resigned-filter">
          <label for="activeEmployeePosition" data-i18n="field.position">ตำแหน่ง</label>
          <select id="activeEmployeePosition">
            <option value="" data-i18n="admin.allPositions">ทุกตำแหน่ง</option>
            @foreach ($stats['active_positions'] as $position)
              <option value="{{ $position['code'] }}"
                      data-option-th="{{ $position['name_th'] }}"
                      data-option-en="{{ $position['name_en'] }}"
                      data-option-my="{{ $position['name_en'] }}">{{ $position['name_th'] }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="resigned-filter-btn" data-i18n="admin.searchButton">ค้นหา</button>
        <button type="button" class="resigned-filter-btn is-secondary" id="activeEmployeeSearchReset" data-i18n="admin.clearButton">ล้าง</button>
      </form>
      <p class="resigned-result-count" id="activeEmployeeSearchMessage" aria-live="polite"></p>
    </section>

    @foreach ($stats['companies'] as $c)
      <details class="admin-company">
        <summary>
          <span class="ac-left">
            <svg class="ac-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
            <span class="ac-name">{{ $c['label'] }}</span>
          </span>
          <span class="ac-meta">
            <span><b>{{ number_format($c['active']) }}</b> <span data-i18n="admin.active_short">ทำงาน</span></span>
            <span>· <b>{{ number_format($c['resigned']) }}</b> <span data-i18n="admin.resigned_short">ลาออก</span></span>
            <span class="ac-total">· <b>{{ number_format($c['total']) }}</b> <span data-i18n="admin.total_short">รวม</span></span>
            <span>· <b>{{ number_format($c['dept_count']) }}</b> <span data-i18n="admin.depts">แผนก</span></span>
          </span>
        </summary>

        @if (count($c['departments']))
          <ul class="dept-list">
            @foreach ($c['departments'] as $d)
              <li>
                <button type="button" class="dept-row"
                        data-company="{{ $c['code'] }}"
                        data-dept-code="{{ $d['dept_code'] }}"
                        data-dept-th="{{ $d['dept_th'] ?: $d['dept_en'] ?: 'ไม่ระบุแผนก' }}"
                        data-dept-en="{{ $d['dept_en'] ?: $d['dept_th'] ?: 'Unspecified' }}">
                  <span>
                    <span data-val="th">{{ $d['dept_th'] ?: $d['dept_en'] ?: 'ไม่ระบุแผนก' }}</span>
                    <span data-val="en">{{ $d['dept_en'] ?: $d['dept_th'] ?: 'Unspecified' }}</span>
                  </span>
                  <span class="dept-count">{{ number_format($d['count']) }}</span>
                </button>
              </li>
            @endforeach
          </ul>
        @else
          <p class="dept-empty">— <span data-i18n="admin.noEmp">ยังไม่มีพนักงาน</span> —</p>
        @endif
      </details>
    @endforeach
  </div>

  {{-- Modal รายชื่อพนักงานในแผนก --}}
  <div class="emp-modal" id="deptModal" role="dialog" aria-modal="true" aria-labelledby="deptModalTitle">
    <div class="emp-dialog">
      <div class="emp-dialog-head">
        <div>
          <span class="ed-kicker" id="deptModalCompany"></span>
          <h2 id="deptModalTitle"></h2>
        </div>
        <button type="button" class="emp-close" id="deptModalClose" aria-label="ปิด">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="emp-body" id="deptModalBody"></div>
    </div>
  </div>

  {{-- Modal รายละเอียดพนักงานและสิทธิ์ลาจาก B Plus --}}
  <div class="emp-modal employee-detail-modal" id="employeeDetailModal" role="dialog" aria-modal="true" aria-labelledby="employeeDetailModalTitle">
    <div class="emp-dialog employee-detail-dialog">
      <div class="emp-dialog-head">
        <div>
          <span class="ed-kicker" data-i18n="admin.employeeDetailKicker">ข้อมูลจาก B Plus</span>
          <h2 id="employeeDetailModalTitle" data-i18n="admin.employeeDetailTitle">ข้อมูลพนักงานและสิทธิ์ลา</h2>
        </div>
        <button type="button" class="emp-close" id="employeeDetailModalClose" aria-label="ปิด" data-i18n-aria="admin.close">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="emp-body" id="employeeDetailModalBody"></div>
    </div>
  </div>

  {{-- Modal พนักงานที่ลาออก --}}
  <div class="emp-modal" id="resignedModal" role="dialog" aria-modal="true" aria-labelledby="resignedModalTitle">
    <div class="emp-dialog">
      <div class="emp-dialog-head">
        <div>
          <span class="ed-kicker" style="color:#d98a80" data-i18n="admin.resignedKicker">พนักงานลาออก</span>
          <h2 id="resignedModalTitle">
            <span data-resigned-title="all" data-i18n="admin.resignedAllTitle">พนักงานลาออก (รวม)</span>
            <span data-resigned-title="pending" data-i18n="admin.pendingResignTitle" hidden>พนักงานลาออก (รอปิดงวด)</span>
            <span data-resigned-title="closed" data-i18n="admin.payrollClosedTitle" hidden>พนักงานลาออก (ปิดงวด)</span>
            (<span id="resignedModalTotal">{{ number_format($stats['resigned']) }}</span>)
          </h2>
        </div>
        <button type="button" class="emp-close" id="resignedModalClose" aria-label="ปิด">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
      </div>
      <div class="emp-body" id="resignedModalBody">
        <div class="resigned-filter-panel">
          <form class="resigned-filters" id="resignedFilterForm" role="search">
            <div class="resigned-filter is-query">
              <label for="resignedQuery" data-i18n="admin.employeeSearch">ค้นหาพนักงาน</label>
              <input type="search" id="resignedQuery" autocomplete="off"
            placeholder="รหัสพนักงาน หรือชื่อ-สกุล" data-i18n-placeholder="admin.employeeSearchPlaceholder"
                     data-i18n-placeholder="admin.employeeSearchPlaceholder">
            </div>
            <div class="resigned-filter">
              <label for="resignedDepartment" data-i18n="field.department">แผนก</label>
              <select id="resignedDepartment">
                <option value="" data-i18n="admin.allDepartments">ทุกแผนก</option>
                @foreach ($stats['resigned_departments'] as $department)
                  <option value="{{ $department }}">{{ $department }}</option>
                @endforeach
              </select>
            </div>
            <div class="resigned-filter">
              <label for="resignedPosition" data-i18n="field.position">ตำแหน่ง</label>
              <select id="resignedPosition">
                <option value="" data-i18n="admin.allPositions">ทุกตำแหน่ง</option>
                @foreach ($stats['resigned_positions'] as $position)
                  <option value="{{ $position }}">{{ $position }}</option>
                @endforeach
              </select>
            </div>
            <button type="submit" class="resigned-filter-btn" data-i18n="admin.searchButton">ค้นหา</button>
            <button type="button" class="resigned-filter-btn is-secondary" id="resignedFilterReset" data-i18n="admin.clearButton">ล้าง</button>
          </form>
          <p class="resigned-result-count" id="resignedResultCount" aria-live="polite"></p>
        </div>
        @if (count($stats['resigned_list']))
          <div class="emp-grid" id="resignedEmployeeGrid">
            @foreach ($stats['resigned_list'] as $r)
              <div class="emp-card"
                   data-resigned-card
                   data-payroll-closed="{{ $r['payroll_closed'] ? '1' : '0' }}"
                   data-pending-resignation="{{ $r['pending_resignation'] ? '1' : '0' }}"
                   data-search="{{ $r['search_text'] }}"
                   data-department="{{ $r['department'] }}"
                   data-position="{{ $r['position'] }}">
                @if ($r['avatar'])
                  {{-- รูปเกาะกับ employees แล้ว จึงยังขึ้นแม้บัญชีถูกลบตอนลาออก · ไฟล์หายเมื่อไหร่ JS จะสลับกลับเป็นไอคอนให้ --}}
                  <span class="emp-avatar"><img src="{{ $r['avatar'] }}" alt="" loading="lazy" data-employee-photo></span>
                @else
                  <span class="emp-avatar avatar-placeholder" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><circle cx="24" cy="18" r="9"></circle><path d="M8 42c2.6-9.4 9-14.5 16-14.5S37.4 32.6 40 42H8Z"></path></svg></span>
                @endif
                <div class="emp-info">
                  <div class="en-name">
                    <span data-val="th">{{ $r['name_th'] }}</span>
                    <span data-val="en">{{ $r['name_en'] }}</span>
                  </div>
                  <div class="en-pos">{{ $r['position'] }} · {{ $r['department'] }}</div>
                  <div class="en-sub resigned">
                    {{ $r['code'] }} · {{ $r['company'] }} ·
                    @if ($r['payroll_closed'])
                      <span data-i18n="admin.payrollClosedShort">ปิดงวดแล้ว</span>
                    @elseif ($r['pending_resignation'])
                      <span data-i18n="admin.pendingResignShort">รอปิดงวด</span>
                    @else
                      <span data-i18n="admin.resigned_short">ลาออก</span>
                    @endif
                    {{ $r['resign_date'] }}
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <p class="emp-state resigned-no-results" id="resignedNoResults" data-i18n="admin.noMatchingEmployees" hidden>ไม่พบพนักงานที่ตรงกับเงื่อนไข</p>
        @else
          <p class="emp-state" data-i18n="admin.noResigned">ไม่มีพนักงานลาออก</p>
        @endif
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      var modal = document.getElementById('deptModal');
      if (!modal) return;
      var titleEl = document.getElementById('deptModalTitle');
      var companyEl = document.getElementById('deptModalCompany');
      var bodyEl = document.getElementById('deptModalBody');
      var endpoint = "{{ route('admin.department') }}";
      var searchEndpoint = "{{ route('admin.employee-search') }}";
      var leaveEndpoint = "{{ route('admin.employee-leave-rights') }}";
      var detailModal = document.getElementById('employeeDetailModal');
      var detailBody = document.getElementById('employeeDetailModalBody');
      var detailClose = document.getElementById('employeeDetailModalClose');
      var currentDirectoryList = [];
      var currentDetail = null;
      var detailTrigger = null;

      var companyLabels = {
        'SUPAVUT_INDUSTRY': 'Supavut Industry',
        'MOLDVANTO': 'Moldvanto',
        'SUPAVUT_INNOMED': 'Supavut Innomed'
      };

      function dict(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var map = (window.__portalCopy && window.__portalCopy[lang]) || {};
        return map[key] || fallback;
      }
      function lang() { return document.documentElement.getAttribute('data-lang') || 'th'; }
      function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
      }); }
      function avatarPlaceholder() {
        return '<span class="emp-avatar avatar-placeholder" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><circle cx="24" cy="18" r="9"></circle><path d="M8 42c2.6-9.4 9-14.5 16-14.5S37.4 32.6 40 42H8Z"></path></svg></span>';
      }

      // รูปที่ Blade เรนเดอร์มากับหน้า (การ์ดพนักงานลาออก) ต้องมีตัวสำรองเหมือนรูปที่ JS สร้าง
      // ไม่งั้นถ้าไฟล์ถูกลบทีหลังจะเห็นเป็นรูปแตก
      function bindPhotoFallback(root) {
        (root || document).querySelectorAll('[data-employee-photo]').forEach(function (image) {
          if (image.dataset.fallbackBound) return;
          image.dataset.fallbackBound = '1';
          image.addEventListener('error', function () {
            var holder = image.closest('.emp-avatar');
            if (!holder) return;
            var replacement = document.createElement('div');
            replacement.innerHTML = avatarPlaceholder();
            holder.replaceWith(replacement.firstElementChild);
          });
        });
      }
      bindPhotoFallback(document);

      function openModal() { modal.classList.add('is-open'); }
      function closeModal() { modal.classList.remove('is-open'); }
      function closeDetail() {
        detailModal.classList.remove('is-open');
        modal.removeAttribute('aria-hidden');
        if (detailTrigger) detailTrigger.focus();
      }

      document.getElementById('deptModalClose').addEventListener('click', closeModal);
      modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
      detailClose.addEventListener('click', closeDetail);
      detailModal.addEventListener('click', function (e) { if (e.target === detailModal) closeDetail(); });
      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var imageViewer = document.getElementById('imageViewer');
        if (imageViewer && imageViewer.classList.contains('is-open')) return;
        if (detailModal.classList.contains('is-open')) {
          closeDetail();
          return;
        }
        closeModal();
      });

      document.querySelectorAll('.dept-row').forEach(function (row) {
        row.addEventListener('click', function () {
          var company = row.getAttribute('data-company');
          var deptCode = row.getAttribute('data-dept-code');
          var deptName = lang() === 'th' ? row.getAttribute('data-dept-th') : row.getAttribute('data-dept-en');

          companyEl.textContent = companyLabels[company] || company;
          titleEl.textContent = deptName;
          bodyEl.innerHTML = '<p class="emp-state">' + esc(dict('admin.loading', 'กำลังโหลด...')) + '</p>';
          openModal();

          fetch(endpoint + '?company=' + encodeURIComponent(company) + '&dept_code=' + encodeURIComponent(deptCode), {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (r) { return r.json(); })
            .then(function (data) { render(data.employees || []); })
            .catch(function () {
              bodyEl.innerHTML = '<p class="emp-state">' + esc(dict('admin.empty', 'ไม่มีข้อมูล')) + '</p>';
            });
        });
      });

      function render(list) {
        currentDirectoryList = list;
        if (!list.length) {
          bodyEl.innerHTML = '<p class="emp-state">' + esc(dict('admin.empty', 'ไม่มีข้อมูล')) + '</p>';
          return;
        }
        var th = lang() === 'th';
        var html = '<div class="emp-grid">';
        list.forEach(function (e) {
          var name = esc(th ? e.name_th : e.name_en);
          var pos = esc(th ? e.position_th : e.position_en);
          var dept = esc(th ? e.dept_th : e.dept_en);
          var avatar = e.avatar
            ? '<span class="emp-avatar"><img src="' + esc(e.avatar) + '" alt="" loading="lazy" data-employee-photo></span>'
            : avatarPlaceholder();
          html += '<button type="button" class="emp-card emp-card-button" data-employee-detail data-company="' + esc(e.company) + '" data-code="' + esc(e.code) + '">' + avatar +
            '<div class="emp-info">' +
              '<div class="en-name">' + name + '</div>' +
              '<div class="en-pos">' + (pos || '—') + (dept ? ' · ' + dept : '') + '</div>' +
              '<div class="en-sub">' + esc(e.code) + ' · ' + esc(e.hire_date) + '</div>' +
            '</div></button>';
        });
        html += '</div>';
        bodyEl.innerHTML = html;
        bodyEl.querySelectorAll('[data-employee-photo]').forEach(function (image) {
          image.addEventListener('error', function () {
            var preview = image.closest('.emp-avatar');
            if (!preview) return;
            var replacement = document.createElement('div');
            replacement.innerHTML = avatarPlaceholder();
            preview.replaceWith(replacement.firstElementChild);
          });
        });
      }

      function number(value) {
        var language = lang() === 'th' ? 'th-TH' : 'en-US';
        return new Intl.NumberFormat(language, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(Number(value || 0));
      }

      function displayYear(year) {
        return lang() === 'th' ? Number(year) + 543 : Number(year);
      }

      function renderDetail(data) {
        currentDetail = data;
        var employee = data.employee || {};
        var leave = data.leave || {};
        var th = lang() === 'th';
        var name = th ? employee.name_th : employee.name_en;
        var position = th ? employee.position_th : employee.position_en;
        var department = th ? employee.dept_th : employee.dept_en;
        var avatarAlt = name || employee.code || dict('admin.employeeDetailTitle', 'ข้อมูลพนักงานและสิทธิ์ลา');
        var avatar = employee.avatar
          ? '<button type="button" class="emp-avatar employee-profile-photo" data-image-preview data-image-src="' + esc(employee.avatar) + '" data-image-alt="' + esc(avatarAlt) + '" aria-label="' + esc(dict('profile.view_photo', 'ดูรูปโปรไฟล์ขนาดใหญ่')) + '"><img src="' + esc(employee.avatar) + '" alt="" loading="lazy"></button>'
          : avatarPlaceholder();
        var html = '<section class="employee-profile">' + avatar +
          '<div class="employee-profile-copy">' +
            '<h3>' + esc(name || employee.code || '—') + '</h3>' +
            '<p>' + esc(position || '—') + ' · ' + esc(department || '—') + '</p>' +
            '<div class="employee-profile-meta"><span>' + esc(employee.code || '—') + '</span><span>' + esc(employee.company_label || employee.company || '—') + '</span><span>' + esc(dict('admin.hiredOn', 'เริ่มงาน')) + ' ' + esc(employee.hire_date || '—') + '</span></div>' +
          '</div></section>';

        if (!leave.available) {
          html += '<p class="emp-state">' + esc(leave.error || dict('admin.leaveUnavailable', 'ไม่สามารถอ่านสิทธิ์ลาจาก B Plus ได้')) + '</p>';
          detailBody.innerHTML = html;
          return;
        }

        html += '<div class="leave-section-head"><h3>' +
          esc(dict('admin.leaveRightsYear', 'สิทธิ์การลา ปี {year}').replace('{year}', String(displayYear(leave.year)))) +
          '</h3><p class="leave-source"><strong>B Plus</strong><br>' + esc(dict('admin.leaveAsOf', 'ข้อมูล ณ {date}').replace('{date}', leave.as_of || '—')) + '</p></div>';

        if (!(leave.rows || []).length) {
          html += '<p class="emp-state">' + esc(dict('admin.noLeaveRights', 'ไม่พบข้อมูลสิทธิ์ลา')) + '</p>';
          detailBody.innerHTML = html;
          return;
        }

        html += '<div class="leave-table-wrap"><table class="leave-table"><thead><tr>' +
          '<th>' + esc(dict('admin.leaveType', 'ประเภทการลา')) + '</th>' +
          '<th>' + esc(dict('admin.leaveEntitled', 'สิทธิ์')) + '</th>' +
          '<th>' + esc(dict('admin.leaveUsed', 'ใช้แล้ว')) + '</th>' +
          '<th>' + esc(dict('admin.leaveRemaining', 'คงเหลือ')) + '</th>' +
          '</tr></thead><tbody>';
        leave.rows.forEach(function (row) {
          var rowName = th ? row.name_th : row.name_en;
          var rowSub = th ? row.name_en : row.name_th;
          html += '<tr><td class="leave-name">' + esc(rowName || '—') + (rowSub ? '<small>' + esc(rowSub) + '</small>' : '') + '</td>' +
            '<td>' + number(row.entitled) + '</td>' +
            '<td>' + number(row.used) + '</td>' +
            '<td class="leave-remaining' + (Number(row.remaining) < 0 ? ' is-negative' : '') + '">' + number(row.remaining) + '</td></tr>';
        });
        html += '</tbody></table></div><p class="leave-note">' + esc(dict('admin.leaveUnitNote', 'ตัวเลขแสดงตามหน่วยสิทธิ์ที่กำหนดใน B Plus และรวมรายการที่อยู่ระหว่างประมวลผลแล้ว')) + '</p>';
        detailBody.innerHTML = html;
      }

      function openEmployeeDetail(button) {
        detailTrigger = button;
        currentDetail = null;
        detailBody.innerHTML = '<p class="emp-state">' + esc(dict('admin.loadingLeave', 'กำลังอ่านสิทธิ์ลาจาก B Plus...')) + '</p>';
        modal.setAttribute('aria-hidden', 'true');
        detailModal.classList.add('is-open');
        window.setTimeout(function () { detailClose.focus(); }, 0);

        fetch(leaveEndpoint
          + '?company=' + encodeURIComponent(button.getAttribute('data-company'))
          + '&employee_code=' + encodeURIComponent(button.getAttribute('data-code')), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (response) {
            if (!response.ok) throw new Error('leave_failed');
            return response.json();
          })
          .then(renderDetail)
          .catch(function () {
            detailBody.innerHTML = '<p class="emp-state">' + esc(dict('admin.leaveUnavailable', 'ไม่สามารถอ่านสิทธิ์ลาจาก B Plus ได้')) + '</p>';
          });
      }

      bodyEl.addEventListener('click', function (event) {
        var button = event.target.closest('[data-employee-detail]');
        if (button) openEmployeeDetail(button);
      });

      // ---- ค้นหาพนักงานที่ยังทำงานอยู่ ----
      var activeSearchForm = document.getElementById('activeEmployeeSearchForm');
      if (activeSearchForm) {
        var activeQuery = document.getElementById('activeEmployeeQuery');
        var activeDepartment = document.getElementById('activeEmployeeDepartment');
        var activePosition = document.getElementById('activeEmployeePosition');
        var activeReset = document.getElementById('activeEmployeeSearchReset');
        var activeMessage = document.getElementById('activeEmployeeSearchMessage');

        function updateActiveFilterOptions() {
          var language = lang();
          activeSearchForm.querySelectorAll('[data-option-th]').forEach(function (option) {
            option.textContent = option.getAttribute('data-option-' + language)
              || option.getAttribute('data-option-en')
              || option.getAttribute('data-option-th');
          });
        }

        function activeResultTitle(total, limited) {
          var text = dict('admin.searchResultsCount', 'ผลการค้นหา ({count} คน)')
            .replace('{count}', String(total));
          if (limited) text += ' ' + dict('admin.searchResultsLimited', '· แสดง 300 คนแรก');
          return text;
        }

        activeSearchForm.addEventListener('submit', function (event) {
          event.preventDefault();
          var query = activeQuery.value.trim();
          var department = activeDepartment.value;
          var position = activePosition.value;

          if (!query && !department && !position) {
            activeMessage.textContent = dict('admin.activeSearchRequired', 'กรุณาระบุรหัส ชื่อ แผนก หรือตำแหน่ง');
            activeQuery.focus();
            return;
          }

          activeMessage.textContent = '';
          companyEl.textContent = dict('admin.activeEmployees', 'พนักงานที่ทำงานอยู่');
          titleEl.textContent = dict('admin.searchResults', 'ผลการค้นหา');
          bodyEl.innerHTML = '<p class="emp-state">' + esc(dict('admin.loading', 'กำลังโหลด...')) + '</p>';
          openModal();

          fetch(searchEndpoint
            + '?query=' + encodeURIComponent(query)
            + '&department=' + encodeURIComponent(department)
            + '&position=' + encodeURIComponent(position), {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (response) {
              if (!response.ok) throw new Error('search_failed');
              return response.json();
            })
            .then(function (data) {
              titleEl.textContent = activeResultTitle(data.total || 0, Boolean(data.limited));
              render(data.employees || []);
            })
            .catch(function () {
              bodyEl.innerHTML = '<p class="emp-state">' + esc(dict('admin.searchFailed', 'ค้นหาไม่สำเร็จ กรุณาลองใหม่')) + '</p>';
            });
        });

        activeReset.addEventListener('click', function () {
          activeQuery.value = '';
          activeDepartment.value = '';
          activePosition.value = '';
          activeMessage.textContent = '';
          activeQuery.focus();
        });

        updateActiveFilterOptions();
        document.addEventListener('insight:languagechange', function () {
          updateActiveFilterOptions();
          if (modal.classList.contains('is-open') && currentDirectoryList.length) render(currentDirectoryList);
          if (detailModal.classList.contains('is-open') && currentDetail) renderDetail(currentDetail);
        });
      }

      // ---- Modal คนลาออก + ค้นหาตามประเภท/แผนก/ตำแหน่ง ----
      var rButtons = document.querySelectorAll('[data-resigned-open]');
      var rModal = document.getElementById('resignedModal');
      if (rButtons.length && rModal) {
        var rClose = document.getElementById('resignedModalClose');
        var rQuery = document.getElementById('resignedQuery');
        var rDepartment = document.getElementById('resignedDepartment');
        var rPosition = document.getElementById('resignedPosition');
        var rForm = document.getElementById('resignedFilterForm');
        var rReset = document.getElementById('resignedFilterReset');
        var rCount = document.getElementById('resignedResultCount');
        var rTotal = document.getElementById('resignedModalTotal');
        var rNoResults = document.getElementById('resignedNoResults');
        var rCards = Array.prototype.slice.call(rModal.querySelectorAll('[data-resigned-card]'));
        var rMode = 'all';
        var rTrigger = null;

        function resultText(count) {
          return dict('admin.resultsFound', 'พบ {count} คน').replace('{count}', String(count));
        }
        function resetFilters() {
          rQuery.value = '';
          rDepartment.value = '';
          rPosition.value = '';
        }
        function applyResignedFilters() {
          var query = rQuery.value.trim().toLocaleLowerCase();
          var department = rDepartment.value;
          var position = rPosition.value;
          var visible = 0;

          rCards.forEach(function (card) {
            var modeMatch = rMode === 'all'
              || (rMode === 'pending' && card.getAttribute('data-pending-resignation') === '1')
              || (rMode === 'closed' && card.getAttribute('data-payroll-closed') === '1');
            var queryMatch = query === '' || card.getAttribute('data-search').indexOf(query) !== -1;
            var departmentMatch = department === '' || card.getAttribute('data-department') === department;
            var positionMatch = position === '' || card.getAttribute('data-position') === position;
            var show = modeMatch && queryMatch && departmentMatch && positionMatch;
            card.hidden = !show;
            if (show) visible += 1;
          });

          rCount.textContent = resultText(visible);
          if (rNoResults) rNoResults.hidden = visible !== 0;
          return visible;
        }
        function setResignedMode(mode) {
          rMode = mode === 'pending' || mode === 'closed' ? mode : 'all';
          rModal.querySelectorAll('[data-resigned-title]').forEach(function (title) {
            title.hidden = title.getAttribute('data-resigned-title') !== rMode;
          });
          var total = rCards.filter(function (card) {
            if (rMode === 'pending') return card.getAttribute('data-pending-resignation') === '1';
            if (rMode === 'closed') return card.getAttribute('data-payroll-closed') === '1';
            return true;
          }).length;
          rTotal.textContent = String(total);
        }
        var closeR = function () {
          rModal.classList.remove('is-open');
          if (rTrigger) rTrigger.focus();
        };

        rButtons.forEach(function (button) {
          button.addEventListener('click', function () {
            rTrigger = button;
            setResignedMode(button.getAttribute('data-resigned-open'));
            resetFilters();
            applyResignedFilters();
            rModal.classList.add('is-open');
            window.setTimeout(function () { rQuery.focus(); }, 0);
          });
        });
        rForm.addEventListener('submit', function (event) {
          event.preventDefault();
          applyResignedFilters();
        });
        rReset.addEventListener('click', function () {
          resetFilters();
          applyResignedFilters();
          rQuery.focus();
        });
        rClose.addEventListener('click', closeR);
        rModal.addEventListener('click', function (e) { if (e.target === rModal) closeR(); });
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && rModal.classList.contains('is-open')) closeR();
        });
        document.addEventListener('insight:languagechange', function () {
          if (rModal.classList.contains('is-open')) applyResignedFilters();
        });
      }
    })();
  </script>
@endsection
