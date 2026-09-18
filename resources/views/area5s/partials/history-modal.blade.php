{{--
  Modal ประวัติการส่ง — ใช้ร่วมกันได้ทุกหน้าของ 5S (หน้าบันทึกงาน / หน้าตรวจประเมิน)
  หน้าตาเดียวกับ modal ในหน้า workspace (`responsible/index.blade.php`) ตาม Manager 2026-08-27:
  แถวสรุป "ครั้งที่ N · วันที่ · คะแนน · สถานะ · ›" กดกางดูรายละเอียดทีละแถว

  ต้องส่ง $historyDetails มาด้วย (จาก Area5sResponsibleController::pointHistoryDetails())
  เรียกเปิดจาก JS ของหน้านั้น ๆ:  window.A5sHistoryModal.open(pointId, title, trail)
--}}

<style>
  .a5hm-dialog {
    --a5hm-pass:#4dbe86;
    --a5hm-fail:#e8635a;
    --a5hm-pending:#c8964a;
    position:fixed; inset:0; width:min(calc(100% - 2rem), 54rem); max-height:min(90vh, 54rem);
    overflow:hidden; margin:auto; border:1px solid var(--line-light); border-radius: 0.36rem; padding:0;
    background:var(--panel-soft); color:var(--light-text); box-shadow:0 1.5rem 4rem rgb(0 0 0 / 36%);
  }
  html[data-theme="light"] .a5hm-dialog { --a5hm-pass:#137a4a; --a5hm-fail:#c62828; --a5hm-pending:#885b18; }
  .a5hm-dialog::backdrop { background:rgb(0 0 0 / 56%); }
  .a5hm-head { position:sticky; top:0; z-index:1; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.25rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); }
  .a5hm-title { display:grid; gap:.4rem; min-width:0; }
  .a5hm-title h2 { margin:0; font-size:1.08rem; font-weight:760; }
  .a5hm-title p { overflow:hidden; margin:0; color:var(--muted-light); font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
  .a5hm-close { width:2.75rem; height:2.75rem; flex:none; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; }
  .a5hm-close:hover { border-color:var(--line-strong); }
  .a5hm-close svg { width:1.1rem; height:1.1rem; stroke:currentColor; fill:none; }
  .a5hm-body { max-height:calc(min(90vh, 54rem) - 5rem); overflow:auto; scroll-padding:1rem; display:grid; gap:.75rem; padding:1rem 1.35rem 1.35rem; }

  /* แถวสรุปแต่ละครั้งที่ส่ง — 5 คอลัมน์ตรงกันทุกแถว */
  .a5hm-rows { display:grid; gap:.4rem; }
  .a5hm-row { border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); }
  .a5hm-row > summary {
    min-height:2.9rem;
    display:grid;
    grid-template-columns:6.5rem minmax(0, 1fr) 4.2rem 7rem 1.2rem;
    align-items:center;
    gap:1rem;
    padding:.5rem 1rem;
    color:var(--light-text);
    cursor:pointer;
    list-style:none;
  }
  .a5hm-row > summary::-webkit-details-marker { display:none; }
  /* ทุกช่องห้ามล้นไปทับช่องข้าง ๆ — ตัดด้วย … แทน */
  .a5hm-row > summary > * { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .a5hm-row-title { font-size:.8rem; font-weight:760; }
  .a5hm-row-date { color:var(--muted-light); font-size:.7rem; font-variant-numeric:tabular-nums; }
  .a5hm-row-score { color:var(--light-text); font-size:.76rem; font-weight:780; text-align:right; font-variant-numeric:tabular-nums; }
  /* สถานะเป็นข้อความล้วน ไม่มีกรอบ ไม่มีจุด */
  .a5hm-row-state { font-size:.75rem; font-weight:780; }
  .a5hm-row-state.is-pass { color:var(--a5hm-pass); }
  .a5hm-row-state.is-fail { color:var(--a5hm-fail); }
  .a5hm-row-state.is-pending { color:var(--a5hm-pending); }
  .a5hm-row-caret { color:var(--moss); font-size:1rem; font-weight:900; text-align:center; transition:transform .18s ease; }
  .a5hm-row[open] .a5hm-row-caret { transform:rotate(90deg); }

  /* เนื้อหาที่กางออกมา — เว้นขอบเท่าแถวสรุป */
  .a5hm-attempt { display:grid; gap:.8rem; border-top:1px solid var(--line-light); padding:.9rem 1rem 1rem; }
  .a5hm-meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.65rem 1rem; }
  .a5hm-meta-item { display:grid; gap:.15rem; min-width:0; }
  .a5hm-meta-item span { color:var(--muted-light); font-size:.7rem; }
  .a5hm-meta-item strong { overflow:hidden; color:var(--light-text); font-size:.8rem; font-weight:680; text-overflow:ellipsis; }

  /* ข้อมูลที่ส่งมา = การ์ดเดียว + เลขลำดับ */
  .a5hm-list { display:grid; gap:.45rem; }
  .a5hm-list > strong { font-size:.82rem; font-weight:740; }
  .a5hm-card { display:grid; gap:.8rem; margin:0; padding:.8rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; list-style:none; counter-reset:a5hm-item; }
  .a5hm-item { display:grid; gap:.22rem; counter-increment:a5hm-item; }
  .a5hm-item + .a5hm-item { padding-top:.8rem; border-top:1px solid var(--line-light); }
  .a5hm-item strong { font-size:.8rem; font-weight:700; }
  .a5hm-item strong::before { content:counter(a5hm-item) ". "; color:var(--moss); font-weight:800; }
  .a5hm-item p { margin:0; color:var(--muted-light); font-size:.78rem; line-height:1.5; white-space:pre-line; }

  /* หมายเหตุ — สีตามผลตรวจ */
  .a5hm-note { margin:0; padding:.7rem .8rem; border:1px solid var(--line-light); border-left:3px solid var(--line-strong); border-radius: 0.25rem; color:var(--light-text); font-size:.8rem; line-height:1.55; white-space:pre-line; }
  .a5hm-note b { display:block; margin-bottom:.15rem; font-size:.72rem; font-weight:800; }
  .a5hm-note.is-passed { border-color:color-mix(in srgb, var(--a5hm-pass) 45%, transparent); border-left-color:var(--a5hm-pass); background:color-mix(in srgb, var(--a5hm-pass) 12%, transparent); }
  .a5hm-note.is-passed b { color:var(--a5hm-pass); }
  .a5hm-note.is-failed { border-color:color-mix(in srgb, var(--a5hm-fail) 48%, transparent); border-left-color:var(--a5hm-fail); background:color-mix(in srgb, var(--a5hm-fail) 13%, transparent); }
  .a5hm-note.is-failed b { color:var(--a5hm-fail); }
  .a5hm-empty { display:grid; gap:.3rem; padding:1.6rem 1rem; text-align:center; }
  .a5hm-empty h2 { margin:0; font-size:.95rem; font-weight:740; }
  .a5hm-empty p { margin:0; color:var(--muted-light); font-size:.8rem; }

  .a5hm-close:focus-visible, .a5hm-row > summary:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }

  @media (max-width: 640px) {
    .a5hm-dialog { width:calc(100% - 1rem); max-height:calc(100dvh - 1rem); }
    .a5hm-body { max-height:calc(100dvh - 6rem); padding-inline:1rem; }
    .a5hm-row > summary { grid-template-columns:minmax(0, 1fr) auto; gap:.35rem .7rem; }
    .a5hm-row-caret { display:none; }
    .a5hm-meta { grid-template-columns:1fr; }
  }
  @media (prefers-reduced-motion: reduce) {
    .a5hm-row-caret { transition:none; }
  }
</style>

<dialog class="a5hm-dialog" data-a5hm-dialog aria-labelledby="a5hm-title">
  <div class="a5hm-head">
    <div class="a5hm-title">
      <h2 id="a5hm-title" data-a5hm-title data-i18n="a5s.common.history">ประวัติ</h2>
      <p data-a5hm-trail>-</p>
    </div>
    <button type="button" class="a5hm-close" data-a5hm-close aria-label="ปิด" data-i18n-aria="a5s.common.close">
      <svg viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
    </button>
  </div>
  <div class="a5hm-body" data-a5hm-body></div>
</dialog>

<script>
  'use strict';
  (function () {
    const details = @json($historyDetails ?? []);
    const dialog = document.querySelector('[data-a5hm-dialog]');
    const body = document.querySelector('[data-a5hm-body]');
    const titleEl = document.querySelector('[data-a5hm-title]');
    const trailEl = document.querySelector('[data-a5hm-trail]');
    if (!dialog || !body) return;

    const i18n = window.__portalLang || {};
    const t = (key, fallback) => (i18n.text ? i18n.text(key, fallback) : fallback);

    function el(tag, className, text) {
      const node = document.createElement(tag);
      if (className) node.className = className;
      if (text !== undefined && text !== null) node.textContent = text;
      return node;
    }

    const stateLabel = state => state === 'passed'
      ? t('a5s.status.passed', 'ผ่าน')
      : (state === 'failed' ? t('a5s.status.failed', 'ปฏิเสธ') : t('a5s.review.pendingDecision', 'รอตรวจประเมิน'));
    const stateClass = state => state === 'passed' ? 'pass' : (state === 'failed' ? 'fail' : 'pending');
    const stateScore = state => state === 'passed' ? '100%' : (state === 'failed' ? '0%' : '—');
    const personName = person => (person && (person.name || person.code)) || t('a5s.common.noData', 'ไม่มีข้อมูล');

    function metaItem(label, value) {
      const item = el('div', 'a5hm-meta-item');
      item.append(el('span', '', label), el('strong', '', value || '-'));
      return item;
    }

    /** เนื้อหาของครั้งที่ส่งหนึ่งครั้ง (ผู้ส่ง/ผู้ประเมิน · ข้อมูลที่ส่งมา · หมายเหตุ) */
    function renderAttempt(attempt) {
      const article = el('div', 'a5hm-attempt');

      const meta = el('div', 'a5hm-meta');
      meta.append(
        metaItem(t('a5s.review.submittedBy', 'ผู้ส่ง'), personName(attempt.submitter)),
        metaItem(t('a5s.common.sentAt', 'ส่งเมื่อ'), attempt.submitted_at)
      );
      if (attempt.evaluator || attempt.evaluated_at) {
        meta.append(
          metaItem(t('a5s.review.evaluatedBy', 'ผู้ประเมิน'), personName(attempt.evaluator)),
          metaItem(t('a5s.common.evaluatedDate', 'วันประเมิน'), attempt.evaluated_at)
        );
      }
      article.append(meta);

      const items = Array.isArray(attempt.cards) ? attempt.cards : [];
      const section = el('div', 'a5hm-list');
      section.append(el('strong', '', t('a5s.review.submittedContent', 'ข้อมูลที่ส่งมา')));
      if (items.length) {
        const card = el('ol', 'a5hm-card');
        items.forEach(entry => {
          const item = el('li', 'a5hm-item');
          item.append(el('strong', '', entry.title || t('a5s.common.detail', 'รายละเอียด')));
          if (entry.detail) item.append(el('p', '', entry.detail));
          card.append(item);
        });
        section.append(card);
      } else {
        section.append(el('p', 'a5hm-note', t('a5s.common.noCards', 'ยังไม่มีรายการที่ส่งมา')));
      }
      article.append(section);

      if (attempt.note) {
        const noteClass = attempt.state === 'passed' ? ' is-passed' : (attempt.state === 'failed' ? ' is-failed' : '');
        const note = el('p', `a5hm-note${noteClass}`);
        note.append(
          el('b', '', `${t('a5s.common.note', 'หมายเหตุ')} · ${stateLabel(attempt.state)}`),
          document.createTextNode(attempt.note)
        );
        article.append(note);
      }

      return article;
    }

    function render(pointId) {
      const detail = details[String(pointId)] || details[pointId] || {};
      const history = Array.isArray(detail.history)
        ? [...detail.history].sort((a, b) => Number(a.seq || 0) - Number(b.seq || 0))
        : [];
      body.replaceChildren();

      if (!history.length) {
        const empty = el('div', 'a5hm-empty');
        empty.append(
          el('h2', '', t('a5s.review.noHistory', 'ยังไม่มีประวัติการส่ง')),
          el('p', '', t('a5s.review.noHistoryHint', 'ประวัติจะปรากฏเมื่อผู้รับผิดชอบส่งข้อมูลเข้าตรวจประเมิน'))
        );
        body.append(empty);
        return;
      }

      const rows = el('div', 'a5hm-rows');
      history.forEach((attempt, index) => {
        // "ครั้งที่" นับตามลำดับการตรวจจริง 1,2,3.. ไม่ใช้ attempt.seq (Manager 2026-08-27)
        const seq = index + 1;
        const state = attempt.state === 'passed' ? 'passed' : (attempt.state === 'failed' ? 'failed' : 'pending');
        const row = el('details', 'a5hm-row');
        const summary = el('summary');
        summary.append(
          el('span', 'a5hm-row-title', `${t('a5s.workspace.roundNumber', 'ครั้งที่')} ${seq}`),
          el('span', 'a5hm-row-date', attempt.evaluated_at || attempt.submitted_at || '-'),
          el('strong', 'a5hm-row-score', stateScore(state)),
          el('span', `a5hm-row-state is-${stateClass(state)}`, stateLabel(state)),
          el('span', 'a5hm-row-caret', '›')
        );
        row.append(summary, renderAttempt(attempt));
        // กางทีละแถว แล้วเลื่อนให้เห็นเต็ม ๆ ไม่โดนขอบโมดัลบัง
        row.addEventListener('toggle', () => {
          if (!row.open) return;
          rows.querySelectorAll('details[open]').forEach(other => {
            if (other !== row) other.open = false;
          });
          window.requestAnimationFrame(() => row.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
        });
        rows.append(row);
      });

      body.append(rows);
    }

    window.A5sHistoryModal = {
      /** title = "จุด X" · trail = ชื่อพื้นที่ — หัวโมดัลแสดง "ประวัติ" แล้วบรรทัดสอง "พื้นที่ › จุด X" */
      open(pointId, title, trail) {
        if (titleEl) titleEl.textContent = t('a5s.common.history', 'ประวัติ');
        if (trailEl) trailEl.textContent = [trail, title].filter(Boolean).join(' › ');
        render(pointId);
        if (dialog.showModal) dialog.showModal();
        else dialog.setAttribute('open', 'open');
      },
      close() {
        if (dialog.close) dialog.close();
        else dialog.removeAttribute('open');
      },
      has(pointId) {
        const detail = details[String(pointId)] || details[pointId] || {};
        return Array.isArray(detail.history) && detail.history.length > 0;
      },
    };

    document.querySelector('[data-a5hm-close]')?.addEventListener('click', () => window.A5sHistoryModal.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) window.A5sHistoryModal.close(); });
  })();
</script>
