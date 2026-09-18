{{--
  ตัวกรองรายคอลัมน์แบบ Excel — ใช้ร่วมกันทุกหน้าของ Time & Leave Approval

  ต้นแบบมาจากหน้าภาพรวม OT (`/ot-approval`) ที่ Manager อนุมัติแล้ว
  ย้ายมาไว้ที่เดียวเพราะเดิมเขียนซ้ำในหน้าภาพรวม OT และภาพรวมการลา
  หน้าใหม่ที่อยากได้ตัวกรองนี้จะได้ `@include` แล้วเรียก 3 บรรทัดจบ

  วิธีใช้
    var cols = window.otColumnFilter.create(function () { renderRows(); });
    cols.attach(thElement, 'position', function (row) { return row.position_th; }, rows);
    rows.filter(cols.matches)

  กติกาที่ห้ามพลาด (บทเรียนจากหน้าภาพรวม)
  1. ติ๊กแล้ว **ต้องกดปุ่ม `ตกลง`** ถึงมีผล — เก็บไว้ใน draft ก่อน ไม่งั้นเมนูปิดทุกครั้งที่ติ๊ก
  2. ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะ (ตำแหน่ง · กะ · สถานะ · ประเภท) ห้ามใส่คอลัมน์ชื่อ/เวลา
  3. ห่อ try/catch ไว้ เพราะ JS ของหน้าอยู่ใน IIFE เดียว พังจุดเดียวตายทั้งก้อน
--}}
@once
  <style>
    /* ไอคอนอยู่มุมขวาล่างของหัวคอลัมน์ ไม่มีกรอบ (Manager สั่ง)
       วางแบบ absolute จึงไม่ไปดันข้อความหัวคอลัมน์ให้เยื้อง

       ⚠️ ใช้ `.is-filterable` เฉย ๆ (ไม่ใส่ `th` นำหน้า) ตั้งใจให้ specificity ต่ำ
       เพราะหัวตารางหลายหน้าเป็น `position: sticky` ด้วย specificity เท่ากัน (เช่น `.otr-table th`)
       ถ้าเขียน `th.is-filterable` กฎนี้จะมาทีหลังแล้วชนะ → หัวตารางหลุด sticky เงียบ ๆ
       sticky เองก็เป็น positioned element อยู่แล้ว ลูกที่ absolute จึงเกาะได้ปกติ */
    .is-filterable { position: relative; }
    .ot-col-filter {
      position: absolute; right: .2rem; bottom: .1rem;
      width: 1.1rem; height: 1.1rem;
      display: grid; place-items: center;
      border: 0; background: transparent;
      color: var(--muted-light); cursor: var(--cursor-action);
    }
    .ot-col-filter svg { width: .85rem; height: .85rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linejoin: round; }
    /* ไม่ระบายสีพื้น (Manager สั่งเอาสีออก 2026-08-24) — ไอคอนเป็นสีเทาเหมือนหัวคอลัมน์
       เข้มขึ้นตอน hover และตอนกำลังกรองอยู่เท่านั้น จะได้ไม่แย่งสายตากับข้อมูลในตาราง */
    .ot-col-filter { background: transparent; color: var(--muted-light); }
    .ot-col-filter:hover { color: var(--light-text); }
    .ot-col-filter.is-active { color: var(--light-text); }
    .ot-col-menu {
      position: absolute; z-index: 20; top: calc(100% + .2rem); right: 0;
      min-width: 11rem; max-height: 16rem; overflow-y: auto;
      display: grid; gap: .1rem; padding: .4rem;
      border: 1px solid var(--line-strong); border-radius: .3rem;
      background: var(--panel); box-shadow: 0 .7rem 1.6rem rgb(0 0 0 / 14%);
      text-align: left; font-weight: 500;
    }
    .ot-col-menu[hidden] { display: none; }
    .ot-col-menu label { display: flex; align-items: center; gap: .45rem; padding: .3rem .4rem; border-radius: .2rem; cursor: var(--cursor-action); font-size: .74rem; }
    .ot-col-menu label:hover { background: var(--hover-soft); }
    .ot-col-menu input { accent-color: var(--muted-light); }
    .ot-col-actions { display: grid; grid-template-columns: 1fr auto; gap: .4rem; align-items: center; margin-top: .3rem; padding-top: .35rem; border-top: 1px solid var(--line-light); }
    .ot-col-clear { padding: .3rem; border: 0; background: transparent; color: var(--muted-light); cursor: var(--cursor-action); font-family: inherit; font-size: .72rem; font-weight: 600; text-align: left; }
    .ot-col-clear:hover { color: var(--light-text); }
    /* ปุ่มตกลงใช้สีตัวอักษรของธีม ไม่ใช่สีเน้น — เมนูกรองไม่ควรเด่นกว่าข้อมูลในตาราง */
    .ot-col-apply { padding: .32rem .8rem; border: 1px solid var(--line-strong); border-radius: .25rem; background: var(--panel-soft); color: var(--light-text); cursor: var(--cursor-action); font-family: inherit; font-size: .72rem; font-weight: 700; }
    .ot-col-apply:hover { border-color: var(--light-text); }
  </style>

  <script>
    'use strict';

    window.otColumnFilter = (function () {
      function copy(key, fallback) {
        return window.__portalLang ? window.__portalLang.text(key, fallback) : fallback;
      }

      /**
       * สร้างชุดตัวกรองหนึ่งชุดต่อหนึ่งตาราง
       * @param onApply เรียกเมื่อผู้ใช้กด `ตกลง` หรือ `ล้างตัวกรอง` — ให้หน้าวาดตารางใหม่
       */
      function create(onApply) {
        var picked = {};   // { คอลัมน์: Set(ค่าที่เลือก) } · ว่าง = ไม่กรอง
        var readers = {};  // { คอลัมน์: function(row) -> ค่า }

        function valueOf(key, row) {
          var read = readers[key];
          return read ? String(read(row) == null ? '' : read(row)) : '';
        }

        /** true เมื่อแถวนี้ผ่านทุกคอลัมน์ที่กำลังกรองอยู่ */
        function matches(row) {
          return Object.keys(picked).every(function (key) {
            var chosen = picked[key];
            if (!chosen || !chosen.size) return true;
            return chosen.has(valueOf(key, row));
          });
        }

        function attach(th, key, read, rows, labelOf) {
          try {
            if (!th || typeof read !== 'function') return;
            readers[key] = read;
            th.classList.add('is-filterable');
            th.querySelectorAll('.ot-col-filter, .ot-col-menu').forEach(function (node) { node.remove(); });

            var values = [];
            (rows || []).forEach(function (row) {
              var value = valueOf(key, row);
              if (values.indexOf(value) === -1) values.push(value);
            });
            values.sort();

            var chosen = picked[key] || new Set();
            var draft = new Set(chosen);

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'ot-col-filter' + (chosen.size ? ' is-active' : '');
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="2"></rect><path d="M8 10.5 12 15l4-4.5"></path></svg>';

            var menu = document.createElement('div');
            menu.className = 'ot-col-menu';
            menu.hidden = true;

            values.forEach(function (value) {
              var label = document.createElement('label');
              var box = document.createElement('input');
              box.type = 'checkbox';
              box.checked = chosen.has(value);
              box.addEventListener('change', function () {
                if (box.checked) draft.add(value); else draft.delete(value);
              });
              var caption = document.createElement('span');
              caption.textContent = labelOf ? labelOf(value) : (value === '' ? '-' : value);
              label.append(box, caption);
              menu.appendChild(label);
            });

            var actions = document.createElement('div');
            actions.className = 'ot-col-actions';
            var clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'ot-col-clear';
            clear.textContent = copy('ot.attendance.clearFilter', 'ล้างตัวกรอง');
            clear.addEventListener('click', function () {
              delete picked[key];
              menu.hidden = true;
              if (onApply) onApply();
            });
            var apply = document.createElement('button');
            apply.type = 'button';
            apply.className = 'ot-col-apply';
            apply.textContent = copy('common.confirm', 'ตกลง');
            apply.addEventListener('click', function () {
              if (draft.size) picked[key] = new Set(draft); else delete picked[key];
              menu.hidden = true;
              if (onApply) onApply();
            });
            actions.append(clear, apply);
            menu.appendChild(actions);

            button.addEventListener('click', function (event) {
              event.stopPropagation();
              var scope = th.closest('table') || document;
              scope.querySelectorAll('.ot-col-menu').forEach(function (other) { if (other !== menu) other.hidden = true; });
              menu.hidden = !menu.hidden;
            });
            menu.addEventListener('click', function (event) { event.stopPropagation(); });

            th.append(button, menu);
          } catch (error) {
            console.error('column filter failed:', key, error);
          }
        }

        function reset() { picked = {}; }

        return { attach: attach, matches: matches, reset: reset };
      }

      /**
       * ตัวกรองรายคอลัมน์แบบ "เลือกได้ค่าเดียว" ที่ส่งค่าไปกรองที่ **เซิร์ฟเวอร์**
       *
       * ใช้กับหน้าที่แบ่งหน้าฝั่ง server (คิวอนุมัติ OT / อนุมัติลา) ซึ่งกรองในเครื่องไม่ได้
       * เพราะจะได้แค่แถวของหน้าที่เปิดอยู่ · หน้าตาเหมือนตัวกรองรายคอลัมน์ปกติทุกอย่าง
       *
       * @param th       หัวคอลัมน์ที่จะติดปุ่ม
       * @param options  [{value, label}] — รายการเต็มจากฝั่ง server ไม่ใช่จากแถวที่เห็น
       * @param current  ค่าที่เลือกอยู่ ('all' = ไม่กรอง)
       * @param onPick   function(value) เรียกเมื่อกดตกลง
       */
      function attachServerChoice(th, options, current, onPick) {
        try {
          if (!th) return;
          th.classList.add('is-filterable');
          th.querySelectorAll('.ot-col-filter, .ot-col-menu').forEach(function (node) { node.remove(); });

          var draft = current || 'all';
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'ot-col-filter' + (draft !== 'all' ? ' is-active' : '');
          button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="2"></rect><path d="M8 10.5 12 15l4-4.5"></path></svg>';

          var menu = document.createElement('div');
          menu.className = 'ot-col-menu';
          menu.hidden = true;

          var name = 'col-choice-' + Math.random().toString(36).slice(2);
          (options || []).forEach(function (option) {
            var label = document.createElement('label');
            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = name;
            radio.value = option.value;
            radio.checked = String(option.value) === String(draft);
            radio.addEventListener('change', function () { draft = option.value; });
            var caption = document.createElement('span');
            caption.textContent = option.label;
            label.append(radio, caption);
            menu.appendChild(label);
          });

          var actions = document.createElement('div');
          actions.className = 'ot-col-actions';
          var clear = document.createElement('button');
          clear.type = 'button';
          clear.className = 'ot-col-clear';
          clear.textContent = copy('ot.attendance.clearFilter', 'ล้างตัวกรอง');
          clear.addEventListener('click', function () { menu.hidden = true; onPick('all'); });
          var apply = document.createElement('button');
          apply.type = 'button';
          apply.className = 'ot-col-apply';
          apply.textContent = copy('common.confirm', 'ตกลง');
          apply.addEventListener('click', function () { menu.hidden = true; onPick(draft); });
          actions.append(clear, apply);
          menu.appendChild(actions);

          button.addEventListener('click', function (event) {
            event.stopPropagation();
            var scope = th.closest('table') || document;
            scope.querySelectorAll('.ot-col-menu').forEach(function (other) { if (other !== menu) other.hidden = true; });
            menu.hidden = !menu.hidden;
          });
          menu.addEventListener('click', function (event) { event.stopPropagation(); });

          th.append(button, menu);
        } catch (error) {
          console.error('server column filter failed:', error);
        }
      }

      // คลิกที่ไหนก็ได้นอกเมนูให้ปิด — เมนูกรองไม่ควรค้างเปิดทับตาราง
      document.addEventListener('click', function () {
        document.querySelectorAll('.ot-col-menu').forEach(function (menu) { menu.hidden = true; });
      });

      return { create: create, attachServerChoice: attachServerChoice };
    })();
  </script>
@endonce
