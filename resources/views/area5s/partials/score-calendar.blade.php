{{-- ปฏิทินคะแนนรายจุด — ใช้ร่วมกันทุกหน้าที่มีปุ่มปฏิทิน (Manager 2026-08-27)

     วิธีใช้: วางปุ่มที่ไหนก็ได้ที่มี
       data-h-calendar="{json ของ point.calendar}"  data-h-code="A"  data-h-owner="ชื่อ (รหัส)"
     แล้ว @include partial นี้ 1 ครั้งต่อหน้า — ที่เหลือมันจัดการเอง --}}
<style>
  /* 🔴 โมดัลอยู่ "นอก" shell ของหน้า — ต้องประกาศโทเคนสีบนตัวมันเอง
     ไม่งั้น var(--bucket-color) ไม่มีค่า สีผ่าน/ปฏิเสธหายหมด (บั๊กเดิมของ .a5rv-history-dialog) */
  .a5h-modal {
    --a5h-pass:#4dbe86;
    --a5h-fail:#e8635a;
    --a5h-pending:#c8964a;
    --a5h-no-data:#9da39b;
    --a5h-surface: var(--panel-tint);
    --a5h-surface-strong: var(--panel-tint-strong);
    --a5h-lv1:#4f6ea8;
    --a5h-lv2:#7a6bb0;
  }
  html[data-theme="light"] .a5h-modal {
    --a5h-pass:#137a4a;
    --a5h-fail:#c62828;
    --a5h-pending:#885b18;
    --a5h-no-data:#5d625c;
  }
  .a5h-modal[hidden] { display:none; }
  .a5h-modal { position:fixed; inset:0; z-index:2400; display:grid; place-items:center; padding:1.1rem; background:rgb(10 14 10 / 52%); }
  .a5h-modal-backdrop { position:absolute; inset:0; border:0; padding:0; background:transparent; cursor:pointer; }
  .a5h-modal-card {
    position:relative; z-index:1; width:min(100%, 52rem); max-height:86vh; overflow:auto;
    border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft);
    padding:1.2rem 1.3rem 1.1rem; box-shadow:0 18px 54px rgb(0 0 0 / 32%);
  }
  .a5h-modal-card h3 { margin:0 0 .2rem; color:var(--light-text); font-size:1.02rem; font-weight:800; }
  .a5h-modal-sub { display:block; margin-bottom:.9rem; color:var(--muted-light); font-size:.8rem; }
  .a5h-modal-x { position:absolute; top:.7rem; right:.8rem; width:1.9rem; height:1.9rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); font-size:1.05rem; cursor:pointer; }
  .a5h-modal-x:hover { border-color:var(--a5h-fail); color:var(--a5h-fail); }
  .a5h-hist-list { list-style:none; display:grid; gap:.55rem; margin:0; padding:0; }
  .a5h-hist-item { border:1px solid var(--line-light); border-radius: 0.28rem; background:var(--menu-bg); padding:.6rem .75rem; }
  .a5h-hist-top { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; color:var(--light-text); font-size:.82rem; }
  .a5h-hist-top b { font-weight:780; }
  .a5h-hist-top small { margin-left:auto; color:var(--muted-light); font-size:.74rem; font-variant-numeric:tabular-nums; }
  .a5h-hist-tag { padding:.14rem .5rem; border-radius: 0.22rem; background:color-mix(in srgb, var(--bucket-color) 16%, transparent); color:var(--bucket-color); font-size:.74rem; font-weight:760; }
  .a5h-hist-item.is-pass { --bucket-color:var(--a5h-pass); }
  .a5h-hist-item.is-fail { --bucket-color:var(--a5h-fail); }
  .a5h-hist-item p { margin:.45rem 0 0; color:var(--muted-light); font-size:.78rem; line-height:1.55; }
  .a5h-hist-empty { margin:0; padding:1.4rem 0; text-align:center; color:var(--muted-light); font-size:.82rem; }

  /* ปุ่มไอคอนปฏิทินในคอลัมน์ประวัติ */
  /* ไอคอนล้วน ไม่มีกรอบ (Manager 2026-08-27) */
  .a5h-cal-btn {
    width:2.2rem; height:2.2rem; display:grid; place-items:center;
    border:0; border-radius: 0.25rem;
    background:transparent; color:var(--muted-light); cursor:pointer;
    transition:color .16s ease;
  }
  .a5h-cal-btn:hover, .a5h-cal-btn:focus-visible { color:var(--moss); outline:none; }
  .a5h-cal-btn svg { width:1.1rem; height:1.1rem; }


  /* ---- ปฏิทินคะแนนในโมดัล: เดือน → วันที่ตรวจ → ครั้งที่ → รายละเอียด (ปิดไว้ทุกชั้น) ---- */
  .a5h-cal-head { display:grid; gap:.4rem; margin:.1rem 0 .9rem; }
  .a5h-cal-head > div { display:grid; grid-template-columns:6.5rem minmax(0, 1fr); align-items:baseline; gap:.5rem; }
  .a5h-cal-head dt { margin:0; color:var(--muted-light); font-size:.72rem; font-weight:700; }
  .a5h-cal-head dt::after { content:":"; }
  .a5h-cal-head dd { margin:0; min-width:0; overflow:hidden; color:var(--light-text); font-size:.84rem; font-weight:500; text-overflow:ellipsis; white-space:nowrap; font-variant-numeric:tabular-nums; }
  .a5h-cal-body { display:grid; gap:.4rem; }

  /* แถวพับ/กางทุกชั้นใช้โครงเดียวกัน — ลูกศร › หมุนลงเมื่อเปิด */
  .a5h-month-row, .a5h-day-row, .a5h-try-row {
    width:100%; display:grid; align-items:center; gap:.55rem;
    border:0; background:transparent; color:inherit; font:inherit; text-align:left; cursor:pointer;
  }
  .a5h-month-caret, .a5h-day-caret, .a5h-try-caret {
    flex:none; color:var(--muted-light); font-size:1rem; font-weight:900; line-height:1;
    transition:transform .16s ease, color .16s ease;
  }
  .a5h-month-caret { color:var(--a5h-lv1); }
  .a5h-day-caret { color:var(--a5h-lv2); }
  [aria-expanded="true"] > .a5h-month-caret,
  [aria-expanded="true"] > .a5h-day-caret,
  [aria-expanded="true"] .a5h-try-caret { transform:rotate(90deg); color:var(--moss); }

  /* ชั้น 1: เดือน */
  .a5h-month { overflow:hidden; border:1px solid var(--line-light); border-left:4px solid var(--a5h-lv1); border-radius: 0.28rem; background:var(--menu-bg); }
  .a5h-month-row { grid-template-columns:auto minmax(0, 1fr) auto auto; padding:.65rem .8rem; }
  .a5h-month-row:hover { background:var(--hover-soft); }
  .a5h-month-name { color:var(--a5h-lv1); font-size:.9rem; font-weight:800; }
  .a5h-month-meta { color:var(--muted-light); font-size:.72rem; font-weight:700; }
  .a5h-month-score { color:var(--light-text); font-size:.9rem; font-weight:800; font-variant-numeric:tabular-nums; }
  .a5h-month-panel { display:grid; gap:.35rem; padding:0 .6rem .6rem 1.5rem; }
  .a5h-month-panel[hidden] { display:none !important; }

  /* ชั้น 2: วันที่ตรวจ */
  .a5h-day { overflow:hidden; border:1px solid var(--line-light); border-left:4px solid var(--a5h-lv2); border-radius: 0.24rem; background:color-mix(in srgb, var(--a5h-lv2) 6%, var(--a5h-surface)); }
  .a5h-day-row { grid-template-columns:auto minmax(0, 1fr) auto auto; padding:.5rem .65rem; }
  .a5h-day-row:hover { background:var(--hover-soft); }
  .a5h-day-name { overflow:hidden; color:var(--a5h-lv2); font-size:.82rem; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
  .a5h-day-row.is-open .a5h-day-name::after { content:" ●"; color:var(--a5h-pass); font-size:.6rem; vertical-align:middle; }
  .a5h-day-meta { color:var(--muted-light); font-size:.72rem; font-weight:700; }
  .a5h-day-score { color:var(--light-text); font-size:.82rem; font-weight:800; font-variant-numeric:tabular-nums; }
  .a5h-day-panel { display:grid; gap:.3rem; padding:0 .5rem .5rem; }
  .a5h-day-panel[hidden] { display:none !important; }

  /* ชั้น 3: ครั้งที่ N */
  .a5h-try { overflow:hidden; border:1px solid var(--line-light); border-left:3px solid var(--bucket-color, var(--a5h-no-data)); border-radius: 0.22rem; background:var(--menu-bg); }
  .a5h-try.is-pass { --bucket-color:var(--a5h-pass); }
  .a5h-try.is-fail { --bucket-color:var(--a5h-fail); }
  .a5h-try.is-pending { --bucket-color:var(--a5h-pending); }
  .a5h-try-row { grid-template-columns:auto auto minmax(0, 1fr) auto auto; padding:.5rem .6rem; }
  .a5h-try-row:hover { background:var(--hover-soft); }
  .a5h-try-no { color:var(--bucket-color); font-size:.8rem; font-weight:800; }
  .a5h-try-time { color:var(--muted-light); font-size:.74rem; font-variant-numeric:tabular-nums; }
  .a5h-try-score { justify-self:end; color:var(--light-text); font-size:.82rem; font-weight:800; font-variant-numeric:tabular-nums; }
  .a5h-try-state { padding:.12rem .5rem; border-radius: 0.22rem; background:color-mix(in srgb, var(--bucket-color) 16%, transparent); color:var(--bucket-color); font-size:.73rem; font-weight:780; white-space:nowrap; }
  .a5h-try-caret { justify-self:end; color:var(--bucket-color); }
  .a5h-try-detail { display:grid; gap:.5rem; border-top:1px solid var(--line-light); background:var(--a5h-surface); padding:.6rem .7rem .7rem; }
  .a5h-try-detail[hidden] { display:none !important; }

  /* ชั้น 4: เนื้อหาของครั้งนั้น */
  .a5h-cal-facts { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.35rem .9rem; }
  @media (max-width: 860px) { .a5h-cal-facts { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 560px) { .a5h-cal-facts { grid-template-columns:1fr; } }
  .a5h-cal-field { display:grid; gap:.08rem; min-width:0; }
  .a5h-cal-field > span { color:var(--muted-light); font-size:.68rem; font-weight:700; }
  .a5h-cal-field > strong { overflow:hidden; color:var(--light-text); font-size:.78rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; font-variant-numeric:tabular-nums; }
  .a5h-cal-cards { display:grid; gap:.35rem; border:1px solid var(--line-light); border-radius: 0.22rem; background:var(--menu-bg); padding:.5rem .6rem; }
  .a5h-cal-cards-label { color:var(--muted-light); font-size:.68rem; font-weight:700; }
  .a5h-cal-card { display:grid; gap:.15rem; }
  .a5h-cal-card strong { color:var(--light-text); font-size:.78rem; font-weight:730; }
  .a5h-cal-card p { margin:0; color:var(--muted-light); font-size:.76rem; line-height:1.6; white-space:pre-line; }
  .a5h-cal-note { display:grid; gap:.15rem; padding:.45rem .55rem; border:1px solid color-mix(in srgb, var(--bucket-color) 45%, transparent); border-left-width:3px; border-radius: 0.22rem; background:color-mix(in srgb, var(--bucket-color) 10%, var(--menu-bg)); }
  .a5h-cal-note-label { color:var(--bucket-color); font-size:.68rem; font-weight:800; }
  .a5h-cal-note p { margin:0; color:var(--light-text); font-size:.78rem; line-height:1.55; white-space:pre-line; }
  .a5h-cal-none { margin:0; padding:.8rem .7rem; color:var(--muted-light); font-size:.78rem; text-align:center; }
</style>

{{-- ปฏิทินคะแนนของจุด: เดือน → รอบ → กดรอบแล้วกางรายละเอียดแต่ละครั้งที่ส่ง (Manager 2026-08-27) --}}
<div class="a5h-modal" data-h-modal hidden aria-hidden="true">
  <button type="button" class="a5h-modal-backdrop" data-h-modal-close aria-label="ปิด" data-i18n-aria="a5s.common.close"></button>
  <div class="a5h-modal-card" role="dialog" aria-modal="true" aria-labelledby="a5hCalTitle" tabindex="-1">
    <button type="button" class="a5h-modal-x" data-h-modal-close aria-label="ปิด" data-i18n-aria="a5s.common.close">&times;</button>
    <h3 id="a5hCalTitle" data-i18n="a5s.calendar.title">ปฏิทินคะแนน</h3>
    <dl class="a5h-cal-head">
      <div><dt data-i18n="a5s.common.pointName">ชื่อจุด</dt><dd data-h-modal-code>-</dd></div>
      <div><dt data-i18n="a5s.common.assignees">ผู้รับผิดชอบ</dt><dd data-h-modal-owner>-</dd></div>
      <div><dt data-i18n="a5s.score.overall">คะแนนรวม</dt><dd data-h-modal-score>—</dd></div>
    </dl>
    <div class="a5h-cal-body" data-h-modal-body></div>
  </div>

<script>
  'use strict';

  (function a5hScoreCalendar() {
  const i18n = window.__portalLang || {};
  const t = (key, fallback) => (i18n.text ? i18n.text(key, fallback) : fallback);
  const el = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  };
  /* ---- ปฏิทินคะแนนของจุด ---- */
  const modal = document.querySelector('[data-h-modal]');
  if (!modal) return;
  const dialog = modal.querySelector('.a5h-modal-card');
  const pointCode = modal.querySelector('[data-h-modal-code]');
  const pointOwner = modal.querySelector('[data-h-modal-owner]');
  const overall = modal.querySelector('[data-h-modal-score]');
  const body = modal.querySelector('[data-h-modal-body]');
  let lastTrigger = null;

  const fmtScore = (score) => {
    if (score === null || score === undefined || score === '') return '—';
    const n = Number(score);
    return Number.isFinite(n) ? `${n.toLocaleString(undefined, { maximumFractionDigits: 2 })}%` : '—';
  };

  /* แถวข้อมูล label → value ใช้ในกล่องรายละเอียดของแต่ละครั้ง */
  function field(labelKey, labelText, value) {
    if (!value) return null;
    const row = el('div', 'a5h-cal-field');
    row.append(el('span', '', t(labelKey, labelText)), el('strong', '', value));
    return row;
  }

  /* ปุ่มพับ/กางมาตรฐาน: หัวเรื่อง + เนื้อหา ปิดไว้เป็นค่าเริ่มต้นทุกชั้น */
  function fold(button, panel) {
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const open = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!open));
      panel.hidden = open;
    });
  }

  /* ชั้น 4: รายละเอียดของ 1 ครั้ง */
  function attemptDetail(item) {
    const box = el('div', 'a5h-try-detail');

    const facts = el('div', 'a5h-cal-facts');
    [
      field('a5s.review.submittedBy', 'ผู้ส่ง', item.submitter),
      field('a5s.common.sentAt', 'ส่งเมื่อ', item.submitted_at),
      field('a5s.review.evaluatedBy', 'ผู้ประเมิน', item.evaluator),
      field('a5s.common.evaluatedDate', 'วันประเมิน', item.evaluated_at),
    ].forEach((row) => { if (row) facts.append(row); });
    if (facts.childElementCount) box.append(facts);

    const cards = Array.isArray(item.cards) ? item.cards : [];
    if (cards.length) {
      const wrap = el('div', 'a5h-cal-cards');
      wrap.append(el('span', 'a5h-cal-cards-label', t('a5s.review.submittedContent', 'ข้อมูลที่ส่งมา')));
      cards.forEach((card) => {
        const one = el('div', 'a5h-cal-card');
        if (card.title) one.append(el('strong', '', card.title));
        if (card.detail) one.append(el('p', '', card.detail));
        wrap.append(one);
      });
      box.append(wrap);
    }

    if (item.note) {
      const note = el('div', 'a5h-cal-note');
      note.append(
        el('span', 'a5h-cal-note-label', `${t('a5s.common.note', 'หมายเหตุ')} · ${t(item.status_key || '', item.status_default || '')}`),
        el('p', '', item.note)
      );
      box.append(note);
    }

    if (!box.childElementCount) box.append(el('p', 'a5h-cal-none', t('a5s.history.empty', 'ยังไม่มีประวัติการส่ง')));
    return box;
  }

  /* ชั้น 3: แถว "ครั้งที่ N · เวลา · คะแนน · ผล · ›" กดแล้วกางรายละเอียด */
  function attemptRow(item, index) {
    const wrap = el('div', `a5h-try is-${item.state || 'pending'}`);

    const row = el('button', 'a5h-try-row');
    row.type = 'button';
    row.append(
      el('b', 'a5h-try-no', `${t('a5s.review.attemptNo', 'ครั้งที่')} ${index + 1}`),
      el('span', 'a5h-try-time', item.evaluated_at || item.submitted_at || '-'),
      el('strong', 'a5h-try-score', fmtScore(item.score)),
      el('span', 'a5h-try-state', t(item.status_key || 'a5s.status.submitted', item.status_default || 'รอดำเนินการ')),
      el('span', 'a5h-try-caret', '\u203A')
    );

    const detail = attemptDetail(item);
    fold(row, detail);
    wrap.append(row, detail);
    return wrap;
  }

  /* ชั้น 2: วันที่ตรวจ — กดแล้วเห็นรายการครั้งที่ 1,2,3,… */
  function dayRow(round) {
    const wrap = el('div', 'a5h-day');

    const row = el('button', `a5h-day-row${round.is_open ? ' is-open' : ''}`);
    row.type = 'button';
    row.append(
      el('span', 'a5h-day-caret', '\u203A'),
      el('strong', 'a5h-day-name', round.date_full || round.date_label || '-'),
      el('span', 'a5h-day-meta', `${t('a5s.work.sent', 'ส่งแล้ว')} ${round.submit_count || 0} ${t('a5s.review.times', 'ครั้ง')}`),
      el('strong', 'a5h-day-score', fmtScore(round.score))
    );

    const panel = el('div', 'a5h-day-panel');
    const attempts = Array.isArray(round.attempts) ? round.attempts : [];
    if (attempts.length) {
      attempts.forEach((item, index) => panel.append(attemptRow(item, index)));
    } else {
      panel.append(el('p', 'a5h-cal-none', t('a5s.history.empty', 'ยังไม่มีประวัติการส่ง')));
    }

    fold(row, panel);
    wrap.append(row, panel);
    return wrap;
  }

  /* ชั้น 1: เดือน + คะแนนของเดือน — ปิดไว้ทุกเดือน */
  function renderCalendar(data) {
    body.replaceChildren();
    const months = Array.isArray(data?.months) ? data.months : [];
    if (!months.length) {
      body.append(el('p', 'a5h-cal-none', t('a5s.calendar.empty', 'ยังไม่มีวันที่ประเมิน')));
      return;
    }

    months.forEach((month) => {
      const wrap = el('div', 'a5h-month');

      const row = el('button', 'a5h-month-row');
      row.type = 'button';
      row.append(
        el('span', 'a5h-month-caret', '\u203A'),
        el('strong', 'a5h-month-name', month.label || '-'),
        el('span', 'a5h-month-meta', t('a5s.score.short', 'คะแนน')),
        el('strong', 'a5h-month-score', fmtScore(month.score))
      );

      const panel = el('div', 'a5h-month-panel');
      const rounds = Array.isArray(month.rounds) ? month.rounds : [];
      if (rounds.length) {
        rounds.forEach((round) => panel.append(dayRow(round)));
      } else {
        panel.append(el('p', 'a5h-cal-none', t('a5s.calendar.emptyMonth', 'ยังไม่มีวันที่ประเมินในเดือนนี้')));
      }

      fold(row, panel);
      wrap.append(row, panel);
      body.append(wrap);
    });
  }
  function open(trigger) {
    lastTrigger = trigger;
    let data = { score: null, months: [] };
    try { data = JSON.parse(trigger.dataset.hCalendar || '{}'); } catch (e) { data = { score: null, months: [] }; }
    const code = trigger.dataset.hCode || '';
    pointCode.textContent = code ? `${t('a5s.common.point', 'จุด')} ${code}` : '-';
    pointOwner.textContent = trigger.dataset.hOwner || '—';
    overall.textContent = fmtScore(data?.score);
    renderCalendar(data);
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    dialog?.focus();
  }

  function close() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    lastTrigger?.focus();
  }

  document.querySelectorAll('[data-h-calendar]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      open(trigger);
    });
  });
  modal.querySelectorAll('[data-h-modal-close]').forEach((node) => node.addEventListener('click', close));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) close(); });
  })();
</script>
