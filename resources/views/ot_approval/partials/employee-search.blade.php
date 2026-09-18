{{--
  ช่องค้นหาพนักงาน ใช้ร่วมกันทุกหน้าของ Time & Leave Approval

  ค้นได้ทั้ง "รหัสพนักงาน" และ "ชื่อ-สกุล" โดยไม่สนภาษาที่กำลังแสดงอยู่
  (พิมพ์ชื่อไทยตอนเปิดหน้าภาษาอังกฤษก็ต้องเจอ) จึงกวาดทุกฟิลด์ชื่อที่ payload มี

  หน้าที่โหลดรายชื่อมาครบแล้วให้กรองในเครื่อง ส่วนหน้าที่แบ่งหน้าจากเซิร์ฟเวอร์
  (อนุมัติ OT / อนุมัติลา) ต้องส่งคำค้นไปกรองที่เซิร์ฟเวอร์ ไม่งั้นจะกรองได้แค่หน้าที่เปิดอยู่
--}}
<label class="ot-emp-search">
  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.2-3.2"></path></svg>
  <span class="sr-only" data-i18n="ot.search.label">ค้นหาพนักงาน</span>
  <input type="search" data-emp-search autocomplete="off"
         data-i18n-placeholder="ot.search.placeholder" placeholder="ค้นหารหัส หรือ ชื่อ-สกุล">
</label>

@once
  <style>
    .ot-emp-search {
      min-height: 2.7rem; display: inline-flex; align-items: center; gap: .45rem; flex: 0 1 16rem;
      padding: 0 .7rem; border: 1px solid var(--line-strong); border-radius: 4px;
      background: var(--panel-soft);
      transition: border-color .18s ease;
    }
    .ot-emp-search:focus-within { border-color: var(--moss); }
    .ot-emp-search svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; opacity: .7; }
    .ot-emp-search input {
      width: 100%; min-width: 0; border: 0; outline: 0; background: transparent;
      color: var(--light-text); font: inherit; font-size: .78rem; font-weight: 650;
    }

    @media (max-width: 46rem) { .ot-emp-search { flex: 1 1 100%; } }
  </style>

  <script>
    /* ตัวช่วยจับคู่คำค้นกับพนักงานหนึ่งคน ใช้ร่วมทุกหน้า
       รับได้ทั้ง payload ของหน้าภาพรวม (code/name_th) และของคิวอนุมัติ (employee_code/employee_name) */
    window.otEmployeeSearch = (function () {
      'use strict';

      var CODE_KEYS = ['code', 'employee_code'];
      var NAME_KEYS = ['name_th', 'name_en', 'name_my', 'employee_name', 'name'];

      function normalize(value) {
        return String(value == null ? '' : value).trim().toLowerCase();
      }

      /** คืน true เมื่อคำค้นว่าง เพื่อให้หน้าเรียกใช้ได้โดยไม่ต้องเช็คเองทุกที่ */
      function matches(employee, query) {
        var needle = normalize(query);
        if (needle === '') return true;
        if (!employee) return false;

        var haystack = [];
        CODE_KEYS.concat(NAME_KEYS).forEach(function (key) {
          if (employee[key]) haystack.push(normalize(employee[key]));
        });
        // คิวอนุมัติเก็บชื่อไว้ในก้อนย่อย employee
        if (employee.employee) {
          CODE_KEYS.concat(NAME_KEYS).forEach(function (key) {
            if (employee.employee[key]) haystack.push(normalize(employee.employee[key]));
          });
        }

        return haystack.some(function (value) { return value.indexOf(needle) !== -1; });
      }

      /** หน่วงการพิมพ์ ไม่ให้ยิงเซิร์ฟเวอร์ทุกตัวอักษร */
      function debounce(fn, wait) {
        var timer = null;

        return function () {
          var args = arguments;
          window.clearTimeout(timer);
          timer = window.setTimeout(function () { fn.apply(null, args); }, wait || 300);
        };
      }

      return { matches: matches, debounce: debounce };
    })();
  </script>
@endonce
