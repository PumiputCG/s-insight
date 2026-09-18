{{-- ตัวเลือกเรียงลำดับรายชื่อพนักงาน — โครงเดียวกับ .ot-sort-filter ของระบบ OT
     ใช้กับตารางที่ติด data-sortable-table (แถวต้องมี data-sort-rank / data-sort-code
     และเซลล์ลำดับที่ติด data-row-no จะถูกไล่เลขใหม่ให้เอง)
     เรียงในเครื่อง ไม่ต้องยิงเซิร์ฟเวอร์ซ้ำ — ลำดับตำแหน่งมาจาก App\Support\PositionRank --}}
<style>
  .asm-sort-filter { position: relative; display: inline-flex; align-items: center; flex: 0 0 auto; }
  .asm-sort-filter svg { position: absolute; left: .55rem; width: .95rem; height: .95rem; color: var(--muted-light); fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; pointer-events: none; }
  .asm-sort-filter select { min-height: 1.9rem; padding: .25rem 1.6rem .25rem 1.9rem; border: 1px solid var(--line-light); border-radius: .25rem; background: var(--menu-bg); color: inherit; font-family: inherit; font-size: .76rem; font-weight: 600; cursor: pointer; appearance: none; }
  .asm-sort-filter select:focus-visible { outline: 2px solid var(--moss); outline-offset: 1px; }
  .asm-sort-filter::after { content: ''; position: absolute; right: .6rem; width: .38rem; height: .38rem; border-right: 1.6px solid var(--muted-light); border-bottom: 1.6px solid var(--muted-light); transform: translateY(-.12rem) rotate(45deg); pointer-events: none; }
</style>

<label class="asm-sort-filter">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
  <select data-assessment-sort aria-label="เรียงลำดับ" data-i18n-aria="assessment.sort.aria">
    <option value="code" data-i18n="assessment.sort.code">เรียงตามรหัสพนักงาน</option>
    <option value="rank" data-i18n="assessment.sort.rankDesc">เรียงตามตำแหน่ง (สูง → ต่ำ)</option>
    <option value="rank_asc" data-i18n="assessment.sort.rankAsc">เรียงตามตำแหน่ง (ต่ำ → สูง)</option>
  </select>
</label>

<script>
  'use strict';
  // เรียงแถวในเครื่อง: ตำแหน่งเท่ากันให้เรียงรหัสต่อ ลำดับจะได้คงที่ไม่สลับไปมา
  (() => {
    const select = document.querySelector('[data-assessment-sort]');
    if (!select) return;

    const UNRANKED = 999;
    const rankOf = (tr) => Number(tr.dataset.sortRank || UNRANKED) || UNRANKED;
    const codeOf = (tr) => String(tr.dataset.sortCode || '');

    const apply = () => {
      const mode = select.value;
      document.querySelectorAll('[data-sortable-table] tbody').forEach((body) => {
        const rows = Array.from(body.querySelectorAll('tr[data-sort-code]'));
        if (!rows.length) return;

        rows.sort((a, b) => {
          if (mode === 'rank' || mode === 'rank_asc') {
            const diff = (rankOf(a) - rankOf(b)) * (mode === 'rank' ? 1 : -1);
            if (diff !== 0) return diff;
          }
          return codeOf(a).localeCompare(codeOf(b), undefined, { numeric: true });
        });

        rows.forEach((tr) => body.appendChild(tr));
        // ไล่เลขลำดับใหม่ตามที่เห็นจริง
        rows.forEach((tr, index) => {
          const no = tr.querySelector('[data-row-no]');
          if (no) no.textContent = (index + 1).toLocaleString();
        });
      });
    };

    select.addEventListener('change', apply);
  })();
</script>
