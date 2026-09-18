{{--
  ตัวกรองกะ ใช้ร่วมกันทั้ง 3 หน้า (ภาพรวม / ขอ OT / อนุมัติ OT)

  ตัวเลือกสร้างจาก "ข้อมูลที่โหลดมาจริง" ไม่ใช่จาก master ทั้ง 17 กะ
  เพราะมีกะที่นิยามไว้แต่ไม่มีคนใช้เลย (เช่น AN06) ถ้าเอามาแสดงจะกดแล้วว่างเปล่า

  ค่าใน select เข้ารหัสไว้ 4 แบบ
    all         ทุกกะ
    g:<group>   ทั้งกะเวลา A / ทั้งกะเวลา B
    s:<code>    เจาะจงรหัสกะ พร้อมเวลา
    none        พนักงานที่ไม่มีกะในวันนั้น
--}}
<label class="ot-shift-filter">
  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
  <span class="sr-only" data-i18n="ot.shiftFilter.label">เลือกกะ</span>
  <select data-shift-filter aria-label="เลือกกะ"></select>
</label>

@once
  <style>
    /* ── ชุดสีประจำกะ ใช้ร่วมทั้ง 3 หน้า ────────────────────────────
       กะเวลา A = ส้มพระอาทิตย์ · กะเวลา B = น้ำเงินเข้ม · ทุกกะ = เทาอมฟ้ากลาง ๆ
       ใช้เป็น "tint" ไม่ใช่ถมสีทึบ เพราะต้องอ่านออกทั้งธีมสว่างและมืด */
    .ot-shift-filter {
      --shift-tone: var(--line-strong);
      min-height: 2.7rem; display: inline-flex; align-items: center; gap: .45rem;
      padding: 0 .7rem; border: 1px solid var(--shift-tone); border-radius: 4px;
      background: color-mix(in srgb, var(--shift-tone) 12%, var(--panel-soft));
      transition: background .18s ease, border-color .18s ease;
    }
    .ot-shift-filter svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; opacity: .75; }
    .ot-shift-filter select { max-width: 17rem; border: 0; outline: 0; background: transparent; color: var(--light-text); font-size: .78rem; font-weight: 700; }
    .ot-shift-filter:focus-within { border-color: var(--moss); }
    .ot-shift-filter.is-shift-morning { --shift-tone: #e8830c; }
    .ot-shift-filter.is-shift-night { --shift-tone: #6d28d9; }
    .ot-shift-filter.is-shift-all { --shift-tone: #64748b; }

    /* สีในรายการ dropdown — เบราว์เซอร์บน Windows รองรับ ส่วน mac/Safari อาจไม่ทาสีให้
       จึงย้อมตัวช่องด้านนอกไว้ด้วย (ด้านบน) เพื่อให้เห็นสถานะแน่นอนทุกเครื่อง */
    .ot-shift-filter option.is-group-morning { background: #e8830c; color: #1b1206; font-weight: 800; }
    .ot-shift-filter option.is-group-night { background: #6d28d9; color: #fff; font-weight: 800; }
    .ot-shift-filter option.is-morning { background: #ffe9cc; color: #532d02; }
    .ot-shift-filter option.is-night { background: #dbe4fb; color: #17265c; }

    /* ── พื้นหลังตารางตามกะที่เลือก ──────────────────────────────── */
    .ot-shift-scene {
      position: relative;
      background-repeat: no-repeat;
      background-position: right 1.4rem top 1rem;
      background-size: 7.5rem;
      transition: background-color .2s ease;
    }
    /* พื้นของเอกสาร/โมดัลย้อมตามกะที่เลือก ให้เห็นทันทีว่ากำลังดูกะไหน
       ทุกกะ = พาสเทลกลางโทนเดียวกับการ์ดแผนก · กะเวลา A = ส้มอ่อน · กะเวลา B = น้ำเงินอ่อน
       ใช้ tint บาง ๆ ทับ --ot-tint-sheet ไม่ถมสีทึบ เพราะต้องอ่านออกทั้งธีมสว่างและมืด */
    .ot-shift-scene.is-shift-all,
    .ot-shift-tint.is-shift-all { background-color: var(--ot-tint-sheet); }
    /* ผสมบน --panel ตรง ๆ ห้ามผสมบน --ot-tint-sheet เพราะตัวมันเองเป็น color-mix อยู่แล้ว
       color-mix ซ้อน color-mix ทำให้บางเบราว์เซอร์ทิ้งทั้ง declaration แล้วสีไม่ขึ้นเลย */
    .ot-shift-scene.is-shift-morning,
    .ot-shift-tint.is-shift-morning { background-color: color-mix(in srgb, #e8830c 12%, var(--panel)); }
    .ot-shift-scene.is-shift-night,
    .ot-shift-tint.is-shift-night { background-color: color-mix(in srgb, #6d28d9 12%, var(--panel)); }

    /* ภาพลายน้ำดวงอาทิตย์/ดวงจันทร์ถูกถอดออกแล้ว (Manager สั่ง 2026-08-21)
       สีบอกกะให้เหลืออยู่ในตารางเท่านั้น — คอลัมน์ `กะงาน` และ `เวลาสแกนเข้า-ออก`
       ตัว .ot-shift-tint ยังใช้อยู่ที่หน้าอนุมัติ OT จึงคงส่วนย้อมพื้นไว้ */

    /* ในโมดัล: ไอคอนย่อลงและเลี่ยงปุ่มปิดมุมขวา ส่วนตัวเนื้อหาย้อมสีอย่างเดียวไม่ต้องมีไอคอนซ้ำ
       ห้ามทา scene ที่ .otr-dialog ตรง ๆ เพราะพื้นทึบของโมดัลจะถูกทับจนกลายเป็นโปร่งใส */
    .ot-shift-scene.is-compact { background-position: right 3.4rem center; background-size: 4.2rem; }
    .ot-shift-tint { transition: background-color .2s ease; }

    /* ── ป้าย "กะที่คุณดูแล" + ไอคอนดวงอาทิตย์/ดวงจันทร์ ─────────────
       ใช้ร่วมกันระหว่างหน้าขอ OT และหน้าขอลา ให้สองหน้าอ่านเหมือนกันเป๊ะ
       3 ช่อง: ช่องเลือกวันที่ซ้าย · ป้ายกะกลางจอ · ช่องว่างขวาไว้ถ่วงให้กลางจริง */
    /* 4 ช่อง: ปุ่มกลับ · ปฏิทิน · ป้ายกะกลางจอ · ช่องว่างขวาถ่วงให้กลางจริง
       ช่องแรกเป็น auto และซ่อนได้ ตอนไม่มีปุ่มกลับจะยุบเอง ป้ายกะจึงยังอยู่กลางเหมือนเดิม */
    .ot-shift-bar { display: grid; grid-template-columns: auto 1fr auto 1fr; align-items: center; gap: .65rem; margin-bottom: 1rem; }
    /* ⚠️ ต้องชิดซ้ายทั้ง "ปุ่มกลับ" และ "ปฏิทิน" — เดิมเขียนแค่ `:first-child`
       พอเพิ่มปุ่มกลับเข้ามาเป็นลูกคนแรก ปฏิทินเลยตกไปอยู่ช่อง 1fr แบบ stretch แล้วยืดยาวทั้งช่อง */
    .ot-shift-bar > :first-child,
    .ot-shift-bar > :nth-child(2) { justify-self: start; }
    .ot-shift-bar-spacer { justify-self: end; }

    .ot-my-shift { display: inline-flex; align-items: baseline; justify-content: center; gap: .5rem; text-align: center; }
    .ot-my-shift[hidden] { display: none; }
    .ot-my-shift small { color: var(--muted-light); font-size: .8rem; font-weight: 650; }
    .ot-my-shift b { font-size: 1.02rem; font-weight: 800; }

    /* ไอคอนดวงอาทิตย์/ดวงจันทร์ข้างป้าย และการย้อมพื้นที่เนื้อหา (.main.is-myshift-*)
       ถูกถอดออกทั้งหมด 2026-08-21 ตามที่ Manager สั่ง — ป้ายเหลือเป็นข้อความล้วน
       ห้ามใส่กลับเข้ามา เว้นแต่ Manager สั่งใหม่ */

    @media (max-width: 860px) {
      .ot-shift-bar { grid-template-columns: 1fr; justify-items: center; }
      .ot-shift-bar > :first-child { justify-self: stretch; }
      .ot-shift-bar-spacer { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
      .ot-shift-filter, .ot-shift-scene, .main { transition: none; }
    }
  </style>

  <script>
    'use strict';

    /* ตัวช่วยกรองกะที่ใช้ร่วมกันทุกหน้า — เขียนครั้งเดียวกันหลุดไม่ตรงกันระหว่างหน้า */
    window.otShiftFilter = (function () {
      var ALL = 'all';

      function copy(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
      }

      function shiftCode(item) { return String(item.shift_code || '').trim(); }

      function timeRange(item) {
        if (!item.shift_in || !item.shift_out) return '';
        return String(item.shift_in).slice(0, 5) + '–' + String(item.shift_out).slice(0, 5);
      }

      /** ตรวจว่ารายการนี้ผ่านตัวกรองที่เลือกอยู่หรือไม่ */
      function matches(item, value) {
        if (!value || value === ALL) return true;
        if (value === 'none') return shiftCode(item) === '';
        if (value.indexOf('g:') === 0) return item.shift_group === value.slice(2);
        if (value.indexOf('s:') === 0) return shiftCode(item) === value.slice(2);
        return true;
      }

      /**
       * สร้างตัวเลือกใหม่จากข้อมูลชุดปัจจุบัน แล้วคืนค่าที่เลือกอยู่
       * ถ้าค่าเดิมยังมีอยู่ในชุดใหม่จะคงไว้ ไม่เด้งกลับเป็น "ทุกกะ" ตอนรีเฟรช
       */
      function build(select, items, previous) {
        var groups = { morning: [], night: [], other: [] };
        var byCode = {};
        var noShift = 0;
        var total = 0;

        (items || []).forEach(function (item) {
          total++;
          var code = shiftCode(item);
          if (code === '') { noShift++; return; }
          if (!byCode[code]) {
            var bucket = item.shift_group === 'morning' || item.shift_group === 'night' ? item.shift_group : 'other';
            byCode[code] = { code: code, group: bucket, range: timeRange(item), count: 0 };
            groups[bucket].push(byCode[code]);
          }
          byCode[code].count++;
        });

        Object.keys(groups).forEach(function (key) {
          groups[key].sort(function (a, b) { return b.count - a.count || a.code.localeCompare(b.code); });
        });

        select.replaceChildren();
        select.appendChild(option(ALL, copy('ot.shiftFilter.all', 'ทุกกะ') + ' (' + total + ')'));

        [
          ['morning', copy('ot.settings.shift.morning', 'กะเวลา A')],
          ['night', copy('ot.settings.shift.night', 'กะเวลา B')],
          ['other', copy('ot.shiftFilter.other', 'กะอื่น')]
        ].forEach(function (entry) {
          var list = groups[entry[0]];
          if (!list.length) return;

          var box = document.createElement('optgroup');
          box.label = entry[1];

          // เลือกทั้งกลุ่มได้ในคลิกเดียว ใช้บ่อยกว่าการเจาะจงรหัสกะ
          if (entry[0] !== 'other') {
            var sum = list.reduce(function (acc, shift) { return acc + shift.count; }, 0);
            var whole = option('g:' + entry[0], copy('ot.shiftFilter.wholeGroup', 'ทั้ง') + entry[1] + ' (' + sum + ')');
            whole.className = 'is-group-' + entry[0];   // ย้อมทั้งแถบให้แยกกะออกทันที
            box.appendChild(whole);
          }

          list.forEach(function (shift) {
            var label = shift.code + (shift.range ? ' · ' + shift.range : '') + ' (' + shift.count + ')';
            var node = option('s:' + shift.code, label);
            if (entry[0] !== 'other') node.className = 'is-' + entry[0];
            box.appendChild(node);
          });

          select.appendChild(box);
        });

        if (noShift) {
          select.appendChild(option('none', copy('ot.shiftFilter.none', 'ไม่มีกะ') + ' (' + noShift + ')'));
        }

        var keep = previous && Array.prototype.some.call(select.options, function (item) { return item.value === previous; });
        select.value = keep ? previous : ALL;

        return select.value;
      }

      function option(value, label) {
        var node = document.createElement('option');
        node.value = value;
        node.textContent = label;
        return node;
      }

      /**
       * กลุ่มของค่าที่เลือกอยู่ — ใช้ตัดสินว่าจะย้อมหน้าจอเป็นกะเวลา Aหรือกะเวลา B
       * เลือกเจาะจงรหัสกะก็ยังรู้ว่าอยู่กลุ่มไหน จึงย้อมสีตามกลุ่มเดียวกัน
       */
      function groupOf(value, items) {
        if (!value || value === ALL) return 'all';
        if (value.indexOf('g:') === 0) return value.slice(2);
        if (value.indexOf('s:') === 0) {
          var code = value.slice(2);
          var hit = (items || []).find(function (item) { return shiftCode(item) === code; });
          return hit && (hit.shift_group === 'morning' || hit.shift_group === 'night') ? hit.shift_group : '';
        }

        return '';
      }

      /** ย้อมช่องฟิลเตอร์และพื้นที่ตารางให้ตรงกับกะที่เลือก */
      function paint(select, scene, value, items) {
        var group = groupOf(value, items);
        [select ? select.closest('.ot-shift-filter') : null, scene].forEach(function (node) {
          if (!node) return;
          node.classList.remove('is-shift-morning', 'is-shift-night', 'is-shift-all');
          /* กะที่จัดกลุ่มไม่ได้ (เช่นรหัสกะที่ไม่ใช่เช้า/ดึก) ให้ตกเป็นโทนกลาง
             ไม่ปล่อยว่างจนพื้นหายไปเฉย ๆ แล้วดูเหมือนสีไม่เปลี่ยน */
          node.classList.add('is-shift-' + (group || 'all'));
        });
        if (scene) scene.classList.add('ot-shift-scene');
      }

      /**
       * ย้อมกลุ่มโหนดตามกะที่เลือก โดยไม่ยุ่งกับ background-image
       * ใช้กับโมดัลที่มีพื้นทึบอยู่แล้ว — ทา scene ทับตรง ๆ จะทำให้พื้นโมดัลหายไป
       */
      function tint(nodes, value, items) {
        var group = groupOf(value, items);
        (nodes || []).forEach(function (node) {
          if (!node) return;
          node.classList.remove('is-shift-morning', 'is-shift-night', 'is-shift-all');
          /* กะที่จัดกลุ่มไม่ได้ (เช่นรหัสกะที่ไม่ใช่เช้า/ดึก) ให้ตกเป็นโทนกลาง
             ไม่ปล่อยว่างจนพื้นหายไปเฉย ๆ แล้วดูเหมือนสีไม่เปลี่ยน */
          node.classList.add('is-shift-' + (group || 'all'));
        });
      }

      /**
       * สร้างตัวเลือกกะจาก "ยอดรวมที่นับมาจาก server" แทนการนับจากแถวที่โหลดมา
       * ใช้กับหน้าที่แบ่งหน้าฝั่ง server เพราะแถวในมือมีแค่หน้าเดียว จะนับเองไม่ได้
       *
       * @param items [{code, group, shift_in, shift_out, total}]
       */
      function buildFromCounts(select, items, previous) {
        var groups = { morning: [], night: [], other: [] };
        var noShift = 0;
        var total = 0;

        (items || []).forEach(function (item) {
          var count = Number(item.total) || 0;
          total += count;
          if (!item.code) { noShift += count; return; }

          var bucket = item.group === 'morning' || item.group === 'night' ? item.group : 'other';
          groups[bucket].push({
            code: item.code,
            group: bucket,
            range: item.shift_in && item.shift_out ? item.shift_in + '–' + item.shift_out : '',
            count: count,
          });
        });

        Object.keys(groups).forEach(function (key) {
          groups[key].sort(function (a, b) { return b.count - a.count || a.code.localeCompare(b.code); });
        });

        select.replaceChildren();
        select.appendChild(option(ALL, copy('ot.shiftFilter.all', 'ทุกกะ') + ' (' + total + ')'));

        [
          ['morning', copy('ot.settings.shift.morning', 'กะเวลา A')],
          ['night', copy('ot.settings.shift.night', 'กะเวลา B')],
          ['other', copy('ot.shiftFilter.other', 'กะอื่น')]
        ].forEach(function (entry) {
          var list = groups[entry[0]];
          if (!list.length) return;

          var box = document.createElement('optgroup');
          box.label = entry[1];

          if (entry[0] !== 'other') {
            var sum = list.reduce(function (acc, shift) { return acc + shift.count; }, 0);
            var whole = option('g:' + entry[0], copy('ot.shiftFilter.wholeGroup', 'ทั้ง') + entry[1] + ' (' + sum + ')');
            whole.className = 'is-group-' + entry[0];
            box.appendChild(whole);
          }

          list.forEach(function (shift) {
            var label = shift.code + (shift.range ? ' · ' + shift.range : '') + ' (' + shift.count + ')';
            var node = option('s:' + shift.code, label);
            if (entry[0] !== 'other') node.className = 'is-' + entry[0];
            box.appendChild(node);
          });

          select.appendChild(box);
        });

        if (noShift) {
          select.appendChild(option('none', copy('ot.shiftFilter.none', 'ไม่มีกะ') + ' (' + noShift + ')'));
        }

        var keep = previous && Array.prototype.some.call(select.options, function (item) { return item.value === previous; });
        select.value = keep ? previous : ALL;

        return select.value;
      }

      return { ALL: ALL, build: build, buildFromCounts: buildFromCounts, matches: matches, groupOf: groupOf, paint: paint, tint: tint };
    })();
  </script>
@endonce
