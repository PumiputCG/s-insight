{{-- Engine ตัวกรอง Excel (funnel per-column + popup ค้นหา/ติ๊กเลือก) — เหมือน 1.2 --}}
{{-- ใช้กับตาราง imp-tbl ที่มี data-filter-table / tr.imp-filter (fbtn) / tr[data-filter-data-row] / [data-filter-count] / [data-filter-clear] --}}
{{-- auto-init ทุก [data-filter-table] บนหน้า · เรียก window.xlsFilterRefresh(name) หลังแก้ข้อมูล inline --}}
@once
  <style>
    .ss-fbtn { display:inline-flex; align-items:center; justify-content:center; width:100%; min-width:1.5rem; padding:.2rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); cursor:pointer; }
    .ss-fbtn:hover { border-color:var(--moss); color:inherit; }
    .ss-fbtn.is-active { border-color:var(--moss); color:var(--moss); background:rgb(91 141 239 / 14%); }
    table.imp-tbl tr.imp-filter th { position:sticky; top:3.4rem; z-index:3; background:var(--panel-soft); padding:.16rem .3rem; }
    .ss-fpop { position:fixed; z-index:1300; width:16rem; max-width:92vw; background:var(--panel, var(--menu-bg)); border:1px solid var(--line-light); border-radius: 0.25rem; box-shadow:0 .7rem 2rem rgb(0 0 0 / 30%); display:none; flex-direction:column; overflow:hidden; }
    .ss-fpop.show { display:flex; }
    .ss-fpop-title { padding:.5rem .6rem 0; font-size:.74rem; color:var(--muted-light); }
    .ss-fpop-search { padding:.45rem .5rem; border-bottom:1px solid var(--line-light); }
    .ss-fpop-search input { width:100%; padding:.4rem .55rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.8rem; }
    .ss-fpop-list { max-height:15rem; overflow:auto; padding:.3rem .35rem; }
    .ss-fpop-list label { display:flex; align-items:center; gap:.45rem; padding:.26rem .35rem; font-size:.82rem; cursor:pointer; border-radius:.3rem; }
    .ss-fpop-list label:hover { background:var(--hover-soft, var(--panel-soft)); }
    .ss-fpop-list label.all { font-weight:600; border-bottom:1px solid var(--line-light); border-radius:0; margin-bottom:.2rem; }
    .ss-fpop-list .nm { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ss-fpop-list .cnt { margin-left:auto; color:var(--muted-light); font-size:.72rem; }
    .ss-fpop-list .none { color:var(--muted-light); font-size:.8rem; padding:.4rem .35rem; }
    .ss-fpop-foot { display:flex; gap:.4rem; padding:.5rem; border-top:1px solid var(--line-light); }
    .ss-fpop-foot button { flex:1; padding:.4rem; font-size:.78rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; }
    .ss-fpop-foot button.apply { background:var(--moss); color:#0c0d0c; border-color:var(--moss); }
    .ss-count { color:var(--muted-light); font-size:.8rem; }
  </style>

  <script>
    'use strict';
    (function () {
      const names = Array.from(document.querySelectorAll('[data-filter-table]')).map(t => t.dataset.filterTable);
      if (!names.length) return;
      const text = (key, fallback) => window.__portalLang?.text ? window.__portalLang.text(key, fallback) : fallback;
      const emptyVal = '__asm_empty__';
      const filterState = {};
      names.forEach(n => { filterState[n] = {}; });

      const norm = v => (v || '').replace(/\s+/g, ' ').trim();
      const cellVal = cell => {
        if (!cell) return '';
        const explicit = cell.querySelector('[data-filter-text]');
        if (explicit) return norm(explicit.textContent);
        const field = cell.querySelector('input, select, textarea');
        return norm(field ? field.value : cell.textContent);
      };
      const enc = v => v === '' ? emptyVal : v;
      const label = v => v === '' ? text('assessment.filter.emptyValue', '(ว่าง)') : v;
      const rowsOf = name => Array.from(document.querySelectorAll(`[data-filter-data-row="${name}"]`));
      const fmtCount = v => Number(v).toLocaleString('th-TH');
      const esc = s => String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);

      function distinct(name, col) {
        const values = new Map();
        rowsOf(name).forEach(row => {
          const raw = cellVal(row.children[col]); const e = enc(raw);
          const cur = values.get(e) || { raw, count: 0 }; cur.count++; values.set(e, cur);
        });
        return Array.from(values.entries()).sort((a, b) => {
          if (a[1].raw === '') return -1; if (b[1].raw === '') return 1;
          return a[1].raw.localeCompare(b[1].raw, 'th', { numeric: true, sensitivity: 'base' });
        });
      }

      function applyFilters(name) {
        const rows = rowsOf(name);
        const cols = Object.keys(filterState[name]).map(Number);
        rows.forEach(row => { row.hidden = !cols.every(col => filterState[name][col].has(enc(cellVal(row.children[col])))); });
        document.querySelectorAll(`[data-filter-btn][data-name="${name}"]`).forEach(btn => {
          btn.classList.toggle('is-active', filterState[name][Number(btn.dataset.col)] !== undefined);
        });
        const counter = document.querySelector(`[data-filter-count="${name}"]`);
        if (counter) {
          const visible = rows.filter(r => !r.hidden).length;
          const v = counter.querySelector('[data-visible-count]'); const t = counter.querySelector('[data-total-count]');
          if (v) v.textContent = fmtCount(visible); if (t) t.textContent = fmtCount(rows.length);
        }
        // แจ้งหน้าที่ใช้ตัวกรองว่าแถวที่แสดงเปลี่ยนแล้ว (เช่น ปุ่มเลือกทั้งหมดที่แสดง)
        document.dispatchEvent(new CustomEvent('xls:filtered', { detail: { name: name } }));
      }
      window.xlsFilterRefresh = applyFilters;

      // popup
      const fpop = document.createElement('div');
      fpop.className = 'ss-fpop';
      fpop.innerHTML = '<div class="ss-fpop-title" data-fpop-title></div>' +
        '<div class="ss-fpop-search"><input type="text" placeholder="' + esc(text('assessment.filter.searchPlaceholder', 'ค้นหา...')) + '"></div>' +
        '<div class="ss-fpop-list"></div>' +
        '<div class="ss-fpop-foot"><button type="button" class="clear">' + esc(text('assessment.filter.clear', 'ล้าง')) + '</button><button type="button" class="apply">' + esc(text('assessment.filter.apply', 'ตกลง')) + '</button></div>';
      document.body.appendChild(fpop);
      const fpopSearch = fpop.querySelector('.ss-fpop-search input');
      const fpopList = fpop.querySelector('.ss-fpop-list');
      const fpopTitle = fpop.querySelector('[data-fpop-title]');
      let ctx = null;

      function renderList(term) {
        const checked = ctx.checked;
        const items = distinct(ctx.name, ctx.col);
        const q = (term || '').toLowerCase();
        const shown = items.filter(it => label(it[1].raw).toLowerCase().indexOf(q) !== -1);
        fpopList.innerHTML = '';
        const all = document.createElement('label'); all.className = 'all';
        const allChk = shown.length > 0 && shown.every(it => checked.has(it[0]));
        all.innerHTML = '<input type="checkbox" data-all ' + (allChk ? 'checked' : '') + '><span class="nm">' + esc(text('assessment.filter.selectAll', 'เลือกทั้งหมด')) + '</span>';
        fpopList.appendChild(all);
        if (!shown.length) { const n = document.createElement('div'); n.className = 'none'; n.textContent = text('assessment.filter.noResults', 'ไม่พบ'); fpopList.appendChild(n); return; }
        shown.forEach(it => {
          const l = document.createElement('label');
          l.innerHTML = '<input type="checkbox" value="' + esc(it[0]) + '" ' + (checked.has(it[0]) ? 'checked' : '') + '><span class="nm">' + esc(label(it[1].raw)) + '</span><span class="cnt">' + it[1].count + '</span>';
          fpopList.appendChild(l);
        });
      }
      function openPop(btn) {
        const name = btn.dataset.name, col = Number(btn.dataset.col);
        const existing = filterState[name][col];
        const allEnc = distinct(name, col).map(it => it[0]);
        ctx = { name, col, checked: existing ? new Set(existing) : new Set(allEnc) };
        fpopTitle.textContent = text('assessment.filter.columnTitle', 'กรองคอลัมน์'); fpopSearch.value = '';
        fpop.classList.add('show'); renderList('');
        const r = btn.getBoundingClientRect();
        let left = Math.min(r.left, window.innerWidth - fpop.offsetWidth - 8); if (left < 8) left = 8;
        let top = r.bottom + 4; if (top + fpop.offsetHeight > window.innerHeight - 8) top = Math.max(8, r.top - fpop.offsetHeight - 4);
        fpop.style.left = left + 'px'; fpop.style.top = top + 'px'; fpopSearch.focus();
      }
      function closePop() { fpop.classList.remove('show'); ctx = null; }
      fpopSearch.addEventListener('input', () => renderList(fpopSearch.value));
      fpopList.addEventListener('change', e => {
        if (!ctx) return;
        if (e.target.matches('[data-all]')) {
          const on = e.target.checked;
          fpopList.querySelectorAll('input[type=checkbox]:not([data-all])').forEach(cb => { cb.checked = on; if (on) ctx.checked.add(cb.value); else ctx.checked.delete(cb.value); });
        } else {
          if (e.target.checked) ctx.checked.add(e.target.value); else ctx.checked.delete(e.target.value);
          const all = fpopList.querySelector('[data-all]');
          const boxes = Array.from(fpopList.querySelectorAll('input[type=checkbox]:not([data-all])'));
          if (all) all.checked = boxes.every(b => b.checked);
        }
      });
      fpop.querySelector('.apply').addEventListener('click', () => {
        if (!ctx) return;
        const total = distinct(ctx.name, ctx.col).length;
        if (ctx.checked.size >= total || ctx.checked.size === 0) {
          if (ctx.checked.size === 0) filterState[ctx.name][ctx.col] = new Set();
          else delete filterState[ctx.name][ctx.col];
        } else filterState[ctx.name][ctx.col] = new Set(ctx.checked);
        applyFilters(ctx.name); closePop();
      });
      fpop.querySelector('.clear').addEventListener('click', () => { if (!ctx) return; delete filterState[ctx.name][ctx.col]; applyFilters(ctx.name); closePop(); });
      document.querySelectorAll('[data-filter-btn]').forEach(btn => {
        btn.addEventListener('click', e => {
          e.stopPropagation();
          if (ctx && ctx.name === btn.dataset.name && ctx.col === Number(btn.dataset.col)) { closePop(); return; }
          openPop(btn);
        });
      });
      document.querySelectorAll('[data-filter-clear]').forEach(btn => {
        btn.addEventListener('click', () => { const n = btn.dataset.filterClear; filterState[n] = {}; applyFilters(n); });
      });
      document.addEventListener('click', e => { if (fpop.classList.contains('show') && !fpop.contains(e.target) && !e.target.closest('[data-filter-btn]')) closePop(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && fpop.classList.contains('show')) closePop(); });
      document.querySelectorAll('.imp-wrap').forEach(w => w.addEventListener('scroll', closePop));
      names.forEach(applyFilters);
    })();
  </script>
@endonce
