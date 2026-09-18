{{-- ตัวกรองสาขา — ใช้แยกคนไทยกับคนพม่า

     Bplus ไม่มีฟิลด์สัญชาติที่ใช้งานได้เลย (FOREIGNINFO ว่างทั้ง 838 แถว ·
     EMP_ADDR_COUNTRY กรอกไม่ถึง 10% และไม่มีค่า "พม่า") สิ่งที่บริษัทใช้แบ่งจริง
     คือสาขาในตาราง BRANCH: `10 โรงงาน` กับ `11 โรงงาน-พม่า`
     ค่าถูกซิงค์เข้า employees.branch_code โดย bplus:sync — ดู App\Support\OtApproval\OtBranchFilter

     วางบรรทัดเดียวกับตัวกรองกะ จึงใช้โครงและขนาดชุดเดียวกับ shift-filter --}}
@php
  $branchOptions = $branchOptions ?? \App\Support\OtApproval\OtBranchFilter::options();
@endphp

@if (count($branchOptions) > 1)
  <label class="ot-branch-filter">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"></path><path d="M9 11h.01M15 11h.01"></path></svg>
    <select data-branch-filter aria-label="กรองตามสาขา">
      <option value="all" data-i18n="ot.branch.all">ทุกสาขา</option>
      @foreach ($branchOptions as $branch)
        <option
          value="{{ $branch['code'] }}"
          data-label-th="{{ $branch['label_th'] }}"
          data-label-en="{{ $branch['label_en'] }}"
        >{{ $branch['label_th'] }}</option>
      @endforeach
    </select>
  </label>

  @once
    <style>
      .ot-branch-filter { position: relative; display: inline-flex; align-items: center; flex: 0 0 auto; }
      .ot-branch-filter svg {
        position: absolute; left: .55rem; width: .95rem; height: .95rem;
        color: var(--muted-light); fill: none; stroke: currentColor;
        stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; pointer-events: none;
      }
      .ot-branch-filter select {
        min-height: 2.45rem; padding: .4rem 1.7rem .4rem 1.95rem;
        border: 1px solid var(--line-strong); border-radius: 4px;
        background: var(--panel); color: var(--light-text);
        font-family: inherit; font-size: .74rem; font-weight: 650;
        cursor: pointer; appearance: none;
      }
      .ot-branch-filter select:focus-visible { outline: 2px solid var(--moss); outline-offset: 1px; }
      .ot-branch-filter::after {
        content: ''; position: absolute; right: .6rem; width: .4rem; height: .4rem;
        border-right: 1.6px solid var(--muted-light); border-bottom: 1.6px solid var(--muted-light);
        transform: translateY(-.12rem) rotate(45deg); pointer-events: none;
      }
    </style>

    <script>
      'use strict';
      /* ป้ายสาขาเป็นข้อมูลจาก Bplus ไม่ใช่คำแปลตายตัว จึงเก็บทั้ง 2 ภาษาไว้ที่ option
         แล้วสลับตอนเปลี่ยนภาษา (พม่าใช้ป้ายอังกฤษ เพราะ Bplus ไม่มีชื่อภาษาพม่า) */
      window.otBranchFilter = {
        matches: function (employee, value) {
          if (!value || value === 'all') return true;
          /* เทียบด้วยชื่อสาขา ไม่ใช่รหัส เพราะรหัสซ้ำความหมายข้ามบริษัท (ดู OtBranchFilter) */
          return String((employee && employee.branch_th) || '') === String(value);
        },
        localize: function (root) {
          var lang = document.documentElement.getAttribute('data-lang') || 'th';
          (root || document).querySelectorAll('[data-branch-filter] option[data-label-th]').forEach(function (option) {
            option.textContent = lang === 'th'
              ? (option.dataset.labelTh || option.dataset.labelEn || option.value)
              : (option.dataset.labelEn || option.dataset.labelTh || option.value);
          });
        }
      };
      document.addEventListener('insight:languagechange', function () { window.otBranchFilter.localize(); });
      window.otBranchFilter.localize();
    </script>
  @endonce
@endif
