@php
  /* นับ "แผนกที่มีอย่างน้อย 1 คน" ไม่ใช่จำนวนคน เพราะ Foreman มีได้หลายคนต่อแผนกแล้ว */
  $assignedCount = collect($companies)->sum(fn ($company) => collect($company['departments'])->filter(
    fn ($department) => ! empty($department['assignments'][$module][$role]),
  )->count());
  $departmentCount = collect($companies)->sum(fn ($company) => count($company['departments']));
  /* เฉพาะ Foreman ที่กำหนดได้หลายคนและติดป้ายกะได้ — Supervisor คงคนเดียวตามเดิม */
  $isMulti = $role === 'foreman';
@endphp

<details class="ot-section ot-role-section" data-role-section="{{ $role }}" data-module="{{ $module }}">
  <summary class="ot-section-summary">
    <span class="ot-section-number">{{ $number }}</span>
    {{-- กำกับระบบไว้ท้ายหัวข้อ เพราะตอนนี้มีสองชุดหน้าตาเหมือนกัน (ขอ OT กับ ขอลา) --}}
    <span class="ot-section-title">
      <strong>
        <span data-i18n="ot.settings.{{ $role }}Title">{{ ucfirst($role) }}</span>
        <span class="ot-section-scope" data-i18n="ot.settings.scope{{ ucfirst($module) }}">{{ $module === 'leave' ? '(ขอลา)' : '(ขอ OT)' }}</span>
      </strong>
      <small data-i18n="ot.settings.{{ $role }}Help">กำหนดผู้รับผิดชอบรายแผนก</small>
    </span>
    <span class="ot-section-count">{{ $assignedCount }}/{{ $departmentCount }}</span>
    <svg class="ot-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
  </summary>

  <div class="ot-section-body ot-company-list">
    @foreach ($companies as $company)
      @php
        $companyAssignedCount = collect($company['departments'])->filter(
          fn ($department) => ! empty($department['assignments'][$module][$role]),
        )->count();
      @endphp
      <details class="ot-company">
        <summary>
          <span>
            <strong>{{ $company['label'] }}</strong>
            <small>{{ $company['code'] }}</small>
          </span>
          <span class="ot-company-count">
            <span data-company-assigned-count>{{ $companyAssignedCount }}/{{ $company['department_count'] }}</span>
            <span data-i18n="ot.common.departments">แผนก</span>
          </span>
          <svg class="ot-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
        </summary>

        <ul class="ot-department-list">
          @forelse ($company['departments'] as $department)
            @php
              /* Foreman มีได้หลายคน Supervisor มีได้คนเดียว แต่เก็บเป็นรายการเหมือนกัน
                 เพื่อให้ JS ใช้โครงเดียว ไม่ต้องแยกทางเดินสองแบบ */
              $assignees = $department['assignments'][$module][$role] ?? [];
              $first = $assignees[0] ?? null;
            @endphp
            <li>
              <button class="ot-department-row"
                      type="button"
                      data-open-assignment
                      data-company="{{ $company['code'] }}"
                      data-company-label="{{ $company['label'] }}"
                      data-dept-code="{{ $department['dept_code'] }}"
                      data-dept-th="{{ $department['name_th'] }}"
                      data-dept-en="{{ $department['name_en'] }}"
                      data-role="{{ $role }}"
                      data-multi="{{ $isMulti ? '1' : '' }}"
                      data-assignees="{{ json_encode($assignees, JSON_UNESCAPED_UNICODE) }}">
                <span class="ot-department-main">
                  <span class="ot-department-name">
                    <strong>
                      <span data-val="th">{{ $department['name_th'] }}</span>
                      <span data-val="en">{{ $department['name_en'] }}</span>
                    </strong>
                  </span>
                  <span class="ot-department-count">{{ $department['count'] }} <span data-i18n="ot.common.people">คน</span></span>
                </span>
                <span class="ot-department-assignee {{ $assignees ? 'has-person' : 'is-empty' }}" data-department-assignee>
                  @if ($isMulti && $assignees)
                    {{-- Foreman หลายคน โชว์เฉพาะรูปซ้อนกัน ชื่อกับกะอยู่ใน tooltip --}}
                    <span class="ot-avatar-stack">
                      @foreach (array_slice($assignees, 0, 6) as $person)
                        <span class="ot-avatar" title="{{ $person['name_th'] }}{{ $person['shift_label_th'] ? ' · '.$person['shift_label_th'] : '' }}">
                          @if ($person['avatar'])
                            <img src="{{ $person['avatar'] }}" alt="{{ $person['name_th'] }}" loading="lazy" data-avatar-image>
                          @else
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                          @endif
                        </span>
                      @endforeach
                      @if (count($assignees) > 6)
                        <span class="ot-avatar-more">+{{ count($assignees) - 6 }}</span>
                      @endif
                    </span>
                  @else
                    <span class="ot-avatar" aria-hidden="true">
                      @if ($first && $first['avatar'])
                        <img src="{{ $first['avatar'] }}" alt="" loading="lazy" data-avatar-image>
                      @else
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                      @endif
                    </span>
                    <span class="ot-assignee-copy">
                      @if ($first)
                        <strong>
                          <span data-val="th">{{ $first['name_th'] }}</span>
                          <span data-val="en">{{ $first['name_en'] }}</span>
                        </strong>
                        <small>{{ $first['code'] }}{{ ! empty($first['company_th']) ? ' · '.$first['company_th'] : '' }}</small>
                      @else
                        <strong data-i18n="ot.settings.notAssigned">ยังไม่ได้กำหนด</strong>
                      @endif
                    </span>
                  @endif
                  <svg class="ot-department-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"></path></svg>
                </span>
              </button>
            </li>
          @empty
            <li><p class="ot-empty" data-i18n="ot.settings.noDepartments">ไม่พบแผนกที่มีพนักงาน active</p></li>
          @endforelse
        </ul>
      </details>
    @endforeach
  </div>

  <div class="ot-assignment-modal"
       {{-- ต้องมี module ด้วย เพราะ role ซ้ำกันระหว่างชุด OT กับชุดการลา --}}
       data-assignment-modal="{{ $role }}"
       data-assignment-module="{{ $module }}"
       role="dialog"
       aria-modal="true"
       aria-labelledby="ot-assignment-title-{{ $module }}-{{ $role }}"
       hidden>
    <section class="ot-assignment-dialog" tabindex="-1">
      <header class="ot-assignment-head">
        <span class="ot-assignment-heading">
          <small data-assignment-company></small>
          <strong id="ot-assignment-title-{{ $module }}-{{ $role }}" data-assignment-title></strong>
        </span>
        <button class="ot-assignment-close" type="button" data-close-assignment data-i18n-aria="ot.common.close" aria-label="ปิด">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
        </button>
      </header>

      <div class="ot-assignment-body">
        <div class="ot-current-block">
          <span class="ot-current-label" data-i18n="{{ $isMulti ? 'ot.settings.currentAssignments' : 'ot.settings.currentAssignment' }}">{{ $isMulti ? 'ผู้รับผิดชอบในแผนกนี้' : 'ผู้ที่เลือกปัจจุบัน' }}</span>
          <div class="ot-current-assignee" data-current-assignee></div>
          @if ($isMulti)
            <p class="ot-assignment-hint" data-i18n="ot.settings.foremanMultiHint">เพิ่มได้มากกว่า 1 คน และเลือกกะที่แต่ละคนดูแลได้ · กะที่เลือกใช้แสดงผลเท่านั้น ไม่จำกัดสิทธิ์</p>
          @endif
        </div>

        <label class="ot-search ot-assignment-search">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
          <span class="sr-only" data-i18n="ot.common.searchEmployee">ค้นหาพนักงาน</span>
          <input type="search" data-assignment-search data-i18n-placeholder="ot.settings.searchAllCompany" placeholder="ค้นหาพนักงานจากทุกบริษัท" autocomplete="off">
        </label>

        <p class="ot-list-label" data-assignment-results-label data-i18n="ot.settings.employeeList">พนักงานในแผนก</p>
        <div class="ot-employee-results" data-assignment-results></div>
      </div>
    </section>
  </div>
</details>
