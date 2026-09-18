{{--
  ตัวแบ่งหน้าตารางฝั่ง client ของ Time & Leave Approval

  หน้าอนุมัติ OT/ลา วาดตารางจาก JSON ที่โหลดมาทั้งก้อน ไม่ได้ใช้ Paginator ของ Laravel
  จึงตัดหน้าใน JS แต่ยังใช้หน้าตาปุ่มเดียวกับ `.asm-page` ของโมดูล Assessment
  เพื่อให้ตัวแบ่งหน้าทั้งเว็บอ่านเหมือนกัน

  ใช้งาน:
    var pager = window.otTablePager.create(node, { perPage: 20, onChange: redraw });
    pager.setTotal(rows.length);          // ทุกครั้งที่ชุดข้อมูลเปลี่ยน (จำนวนเปลี่ยน = เด้งกลับหน้า 1)
    var pageRows = pager.slice(rows);     // เอาเฉพาะแถวของหน้าปัจจุบันไปวาด
    var start = pager.offset;             // ลำดับแถวแรกของหน้านี้ ใช้ทำคอลัมน์เลขที่ให้ไล่ต่อเนื่อง
--}}
<div class="ot-pager" data-table-pager hidden>
  <a class="ot-pager-link" data-pager-prev role="button" tabindex="0" data-i18n="common.prevPage">ก่อนหน้า</a>
  <span class="ot-pager-numbers" data-pager-numbers></span>
  <a class="ot-pager-link" data-pager-next role="button" tabindex="0" data-i18n="common.nextPage">ถัดไป</a>
</div>

@once
  <style>
    /* ถอดสเปกปุ่มจาก .asm-page ของ Assessment ให้ตัวแบ่งหน้าทั้งเว็บหน้าตาเดียวกัน */
    .ot-pager {
      display: flex; align-items: center; justify-content: flex-end; gap: .35rem; flex-wrap: wrap;
      margin: .6rem 0 .2rem; color: var(--muted-light); font-size: .8rem;
    }
    .ot-pager[hidden] { display: none; }
    .ot-pager-numbers { display: flex; align-items: center; gap: .25rem; flex-wrap: wrap; }
    .ot-pager-link {
      min-width: 2.1rem; padding: .36rem .7rem; border: 1px solid var(--line-light); border-radius: 0.25rem;
      background: var(--menu-bg); color: inherit; text-decoration: none;
      font-weight: 600; font-variant-numeric: tabular-nums; text-align: center; cursor: pointer;
    }
    .ot-pager-link:not(.is-disabled):hover { border-color: var(--moss); color: var(--moss); }
    .ot-pager-link.is-disabled { opacity: .45; pointer-events: none; }
    .ot-pager-link.is-active { border-color: var(--moss); background: var(--moss); color: #fff; }
    .ot-pager-gap { padding: 0 .15rem; opacity: .6; }
  </style>

  <script>
    window.otTablePager = (function () {
      'use strict';

      /**
       * เลขหน้าที่จะโชว์ — หน้าแรก หน้าสุดท้าย และรอบ ๆ หน้าปัจจุบัน
       * ที่เหลือยุบเป็น … กันแถวปุ่มยาวเกินจอเมื่อมีหลายสิบหน้า
       */
      function windowed(page, last) {
        if (last <= 7) {
          var all = [];
          for (var i = 1; i <= last; i++) all.push(i);

          return all;
        }

        var pages = [1];
        var from = Math.max(2, page - 1);
        var to = Math.min(last - 1, page + 1);
        if (page <= 3) { from = 2; to = 4; }
        if (page >= last - 2) { from = last - 3; to = last - 1; }

        if (from > 2) pages.push('gap');
        for (var n = from; n <= to; n++) pages.push(n);
        if (to < last - 1) pages.push('gap');
        pages.push(last);

        return pages;
      }

      function create(node, options) {
        if (!node) return null;

        var settings = options || {};
        var perPage = Number(settings.perPage) || 20;
        var onChange = typeof settings.onChange === 'function' ? settings.onChange : function () {};
        var total = 0;
        var page = 1;

        var prev = node.querySelector('[data-pager-prev]');
        var next = node.querySelector('[data-pager-next]');
        var numbers = node.querySelector('[data-pager-numbers]');

        function lastPage() { return Math.max(1, Math.ceil(total / perPage)); }

        function go(target) {
          var wanted = Math.min(Math.max(1, target), lastPage());
          if (wanted === page) return;
          page = wanted;
          paint();
          onChange();
        }

        function paint() {
          var last = lastPage();
          node.hidden = last <= 1;   // มีหน้าเดียวก็ไม่ต้องโชว์ให้รก

          numbers.replaceChildren();
          windowed(page, last).forEach(function (item) {
            if (item === 'gap') {
              var gap = document.createElement('span');
              gap.className = 'ot-pager-gap';
              gap.textContent = '…';
              numbers.appendChild(gap);

              return;
            }

            var link = document.createElement('a');
            link.className = 'ot-pager-link' + (item === page ? ' is-active' : '');
            link.setAttribute('role', 'button');
            link.tabIndex = 0;
            link.textContent = String(item);
            if (item === page) link.setAttribute('aria-current', 'page');
            link.addEventListener('click', function () { go(item); });
            link.addEventListener('keydown', function (event) {
              if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); go(item); }
            });
            numbers.appendChild(link);
          });

          prev.classList.toggle('is-disabled', page <= 1);
          next.classList.toggle('is-disabled', page >= last);
        }

        prev.addEventListener('click', function () { go(page - 1); });
        next.addEventListener('click', function () { go(page + 1); });
        [prev, next].forEach(function (button) {
          button.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); button.click(); }
          });
        });

        return {
          /**
           * รับผลการแบ่งหน้าที่ server คำนวณมาแล้ว
           * ห้ามเด้งกลับหน้า 1 เองเมื่อ total เปลี่ยน เพราะ server เป็นคนตัดสินหน้าปัจจุบัน
           */
          apply: function (payload) {
            var data = payload || {};
            total = Number(data.total) || 0;
            perPage = Number(data.per_page) || perPage;
            page = Number(data.page) || 1;
            paint();
          },
          reset: function () { page = 1; paint(); },
          repaint: paint,
          /** ลำดับของแถวแรกในหน้านี้ (ฐาน 0) ใช้ทำคอลัมน์เลขที่ให้ไล่ต่อเนื่องข้ามหน้า */
          get offset() { return (page - 1) * perPage; },
          get page() { return page; },
          get perPage() { return perPage; },
        };
      }

      return { create: create };
    })();
  </script>
@endonce
