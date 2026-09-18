@extends('layouts.portal')

@section('title', 'การประเมินตัวเอง')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.self.adminNav">ตั้งค่าการประเมินตัวเอง</span>
@endsection

@section('page-script')
  <script>
    'use strict';

    (() => {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const levelUrl = @json(route('assessment.self.level.save'));
      const participantUrl = @json(route('assessment.self.participants.save'));
      const text = (key, fallback) => window.__portalLang?.text
        ? window.__portalLang.text(key, fallback)
        : fallback;

      function setNote(message, isError = false) {
        const note = document.querySelector('[data-save-note]');
        if (!note) return;
        note.textContent = message;
        note.classList.toggle('is-error', isError);
      }

      function refreshLevelCards() {
        document.querySelectorAll('[data-level-card]').forEach((card) => {
          const list = card.querySelector('[data-position-list]');
          const items = list.querySelectorAll('[data-position]');
          card.querySelector('[data-level-count]').textContent = items.length.toLocaleString();
          const empty = list.querySelector('[data-level-empty]');
          if (!items.length && !empty) {
            const placeholder = document.createElement('div');
            placeholder.className = 'self-empty';
            placeholder.dataset.levelEmpty = '';
            placeholder.textContent = '—';
            list.appendChild(placeholder);
          } else if (items.length && empty) {
            empty.remove();
          }
        });
      }

      document.querySelectorAll('[data-position-level]').forEach((select) => {
        select.dataset.original = select.value;
        select.addEventListener('change', async () => {
          const oldValue = select.dataset.original || '';
          const newValue = select.value;
          select.disabled = true;

          try {
            const response = await fetch(levelUrl, {
              method: 'PUT',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
              },
              body: JSON.stringify({
                job_code: select.dataset.jobCode,
                level: newValue === '' ? null : Number(newValue),
              }),
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.message || 'save failed');

            select.dataset.original = newValue;
            const targetKey = newValue === '' ? 'unassigned' : newValue;
            const target = Array.from(document.querySelectorAll('[data-level-card]'))
              .find((card) => card.dataset.levelCard === targetKey);
            const position = select.closest('[data-position]');
            target?.querySelector('[data-position-list]')?.appendChild(position);
            document.querySelectorAll('[data-level-cell]').forEach((cell) => {
              if (cell.dataset.jobCode === select.dataset.jobCode) {
                const levelPill = cell.querySelector('.self-level-pill');
                levelPill.textContent = newValue || '—';
                levelPill.classList.toggle('is-empty', !newValue);
              }
            });
            refreshLevelCards();
          } catch (error) {
            select.value = oldValue;
            window.alert(text('assessment.scores.saveLevelError', 'บันทึกระดับไม่สำเร็จ'));
          } finally {
            select.disabled = false;
          }
        });
      });

      // ---- การ์ดระดับ: เพิ่ม (ฝั่งหน้าเว็บ) / ลบ (บันทึกที่เซิร์ฟเวอร์) ----
      const levelDeleteUrl = @json(route('assessment.self.level.delete'));

      document.querySelector('[data-level-add]')?.addEventListener('click', function () {
        const cards = Array.from(document.querySelectorAll('[data-level-card]'))
          .map((c) => parseInt(c.dataset.levelCard, 10))
          .filter((n) => !Number.isNaN(n));
        const next = (cards.length ? Math.max.apply(null, cards) : 0) + 1;

        const card = document.createElement('article');
        card.className = 'self-level-card';
        card.dataset.levelCard = String(next);
        card.innerHTML = '<div class="self-level-title">'
          + '<span data-i18n="assessment.self.levelLabel">' + text('assessment.self.levelLabel', 'ระดับ') + '</span> ' + next
          + ' (<span data-level-count>0</span>)'
          + '<button type="button" class="self-level-del" data-level-del="' + next + '" aria-label="ลบระดับ ' + next + '">✕</button>'
          + '</div><div class="self-position-list" data-position-list><div class="self-empty" data-level-empty>—</div></div>';
        this.parentNode.insertBefore(card, this);

        // เพิ่มตัวเลือกระดับใหม่ให้ทุก dropdown
        document.querySelectorAll('[data-position-level]').forEach((sel) => {
          if (!Array.from(sel.options).some((o) => o.value === String(next))) {
            const opt = document.createElement('option');
            opt.value = String(next);
            opt.textContent = String(next);
            sel.appendChild(opt);
          }
        });
      });

      document.addEventListener('click', async (e) => {
        const del = e.target.closest('[data-level-del]');
        if (!del) return;
        const lv = parseInt(del.dataset.levelDel, 10);
        const card = del.closest('[data-level-card]');
        const count = parseInt(card.querySelector('[data-level-count]')?.textContent || '0', 10);
        const msg = count > 0
          ? text('assessment.self.deleteLevelBusy', 'ระดับนี้มีตำแหน่งอยู่ {count} รายการ · ลบแล้วตำแหน่งจะกลับไปยังไม่กำหนดระดับ และคำถามของระดับนี้จะถูกลบ ยืนยันหรือไม่')
              .replace('{count}', count)
          : text('assessment.self.deleteLevelConfirm', 'ลบระดับนี้? คำถามของระดับนี้จะถูกลบ และระดับที่สูงกว่าจะเลื่อนลงมาแทน');
        if (!window.confirm(msg)) return;

        try {
          const res = await fetch(levelDeleteUrl, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ level: lv }),
          });
          const payload = await res.json();
          if (!res.ok || !payload.ok) throw new Error(payload.message || 'delete failed');
          window.location.reload();
        } catch (err) {
          window.alert(text('assessment.self.deleteLevelError', 'ลบระดับไม่สำเร็จ'));
        }
      });

      const dirty = new Map();
      const saveButton = document.querySelector('[data-save-participants]');

      function updateSelectionLabel(checkbox) {
        const label = checkbox.closest('td')?.querySelector('[data-selected-label]');
        if (!label) return;
        const key = checkbox.checked ? 'assessment.self.selected' : 'assessment.self.notSelected';
        label.dataset.i18n = key;
        label.textContent = text(key, checkbox.checked ? 'เลือก' : 'ไม่เลือก');
      }

      function trackSelection(checkbox) {
        updateSelectionLabel(checkbox);
        const original = checkbox.dataset.original === '1';
        if (checkbox.checked === original) {
          dirty.delete(checkbox.value);
        } else {
          dirty.set(checkbox.value, checkbox.checked);
        }
        if (saveButton) saveButton.disabled = dirty.size === 0;
        setNote(dirty.size
          ? text('assessment.self.unsavedChanges', 'มีรายการที่ยังไม่บันทึก {count} รายการ').replace('{count}', dirty.size)
          : '');
        window.xlsFilterRefresh?.('self-manage');
      }

      document.querySelectorAll('[data-participant-check]').forEach((checkbox) => {
        checkbox.dataset.original = checkbox.checked ? '1' : '0';
        checkbox.addEventListener('change', () => { trackSelection(checkbox); refreshSelectVisibleLabel(); });
      });

      // ปุ่มสลับ: ติ๊กครบแล้ว → กดอีกทีเอาออกทั้งหมด (ดูเฉพาะแถวที่แสดงอยู่หลังกรอง)
      const selectVisibleBtn = document.querySelector('[data-select-visible]');
      const visibleChecks = () => Array.from(
        document.querySelectorAll('[data-filter-data-row="self-manage"]:not([hidden]) [data-participant-check]')
      );

      function refreshSelectVisibleLabel() {
        if (!selectVisibleBtn) return;
        const list = visibleChecks();
        const allOn = list.length > 0 && list.every((c) => c.checked);
        const label = selectVisibleBtn.querySelector('span');
        label.dataset.i18n = allOn ? 'assessment.self.clearAllVisible' : 'assessment.self.selectAllVisible';
        label.textContent = allOn
          ? text('assessment.self.clearAllVisible', 'ไม่เลือกทั้งหมด')
          : text('assessment.self.selectAllVisible', 'เลือกทั้งหมดที่แสดง');
        selectVisibleBtn.disabled = list.length === 0;
      }

      selectVisibleBtn?.addEventListener('click', () => {
        const list = visibleChecks();
        if (!list.length) return;
        const turnOn = !list.every((c) => c.checked);   // ยังไม่ครบ = ติ๊กให้ครบ · ครบแล้ว = เอาออกหมด
        list.forEach((checkbox) => {
          if (checkbox.checked !== turnOn) {
            checkbox.checked = turnOn;
            trackSelection(checkbox);
          }
        });
        refreshSelectVisibleLabel();
      });

      // กรองแล้วแถวที่แสดงเปลี่ยน → คิดข้อความปุ่มใหม่ · และตั้งค่าครั้งแรกตอนโหลดหน้า
      document.addEventListener('xls:filtered', (e) => {
        if (!e.detail || e.detail.name === 'self-manage') refreshSelectVisibleLabel();
      });
      document.addEventListener('insight:languagechange', refreshSelectVisibleLabel);
      refreshSelectVisibleLabel();

      saveButton?.addEventListener('click', async () => {
        if (!dirty.size) return;
        saveButton.disabled = true;
        setNote(text('assessment.self.saving', 'กำลังบันทึก...'));

        try {
          const response = await fetch(participantUrl, {
            method: 'PUT',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
              changes: Array.from(dirty, ([employeeCode, isSelected]) => ({
                employee_code: employeeCode,
                is_selected: isSelected,
              })),
            }),
          });
          const payload = await response.json();
          if (!response.ok || !payload.ok) {
            const validationMessage = payload.errors
              ? Object.values(payload.errors).flat()[0]
              : payload.message;
            throw new Error(validationMessage || 'save failed');
          }

          dirty.forEach((isSelected, employeeCode) => {
            const checkbox = Array.from(document.querySelectorAll('[data-participant-check]'))
              .find((item) => item.value === employeeCode);
            if (checkbox) checkbox.dataset.original = isSelected ? '1' : '0';
          });
          dirty.clear();
          document.querySelector('[data-selected-count]').textContent =
            Number(payload.selected_count || 0).toLocaleString();
          setNote(text('assessment.self.saved', 'บันทึกรายชื่อแล้ว'));
        } catch (error) {
          setNote(error.message || text('assessment.self.saveFailed', 'บันทึกรายชื่อไม่สำเร็จ'), true);
        } finally {
          saveButton.disabled = dirty.size === 0;
        }
      });

      document.querySelectorAll('[data-question-level]').forEach((button) => {
        button.addEventListener('click', () => {
          document.querySelectorAll('[data-question-level]').forEach((item) => {
            item.classList.toggle('is-active', item === button);
          });
          document.querySelectorAll('[data-question-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.questionPanel !== button.dataset.questionLevel;
          });
          const activeLevel = document.querySelector('[data-active-level]');
          if (activeLevel) activeLevel.textContent = button.dataset.questionLevel;
          const url = new URL(window.location.href);
          url.searchParams.set('level', button.dataset.questionLevel);
          window.history.replaceState({}, '', url);
        });
      });

      document.querySelectorAll('[data-choice-type]').forEach((select) => {
        const syncScore = () => {
          const form = select.closest('form');
          const score = form?.querySelector('[name="score"]');
          if (!score) return;
          const isNa = select.value === 'na';
          score.disabled = isNa;
          score.required = !isNa;
          form.classList.toggle('is-na', isNa);
          if (isNa) {
            score.value = '';
            form.querySelectorAll('[data-na-default]').forEach((input) => {
              if (!input.value.trim()) input.value = input.dataset.naDefault || '';
            });
          }
        };
        select.addEventListener('change', syncScore);
        syncScore();
      });

      document.querySelectorAll('[data-confirm-delete]').forEach((button) => {
        button.addEventListener('click', (event) => {
          const message = text('assessment.self.deleteConfirm', 'ยืนยันการลบรายการนี้?');
          if (!window.confirm(message)) event.preventDefault();
        });
      });

      window.addEventListener('beforeunload', (event) => {
        if (!dirty.size) return;
        event.preventDefault();
        event.returnValue = '';
      });
    })();
  </script>
@endsection

@section('page-style')
  .self-admin { display:grid; gap:1rem; }
  .self-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .self-round { color:var(--muted-light); font-size:.84rem; }
  .self-round b { color:var(--moss); }
  .self-tabs { display:flex; gap:.25rem; border-bottom:1px solid var(--line-light); }
  .self-tab { padding:.65rem .9rem; color:var(--muted-light); text-decoration:none; border-bottom:2px solid transparent; font-size:.86rem; font-weight:600; }
  .self-tab:hover { color:inherit; }
  .self-tab.is-active { color:var(--moss); border-bottom-color:var(--moss); }
  .self-section { border:1px solid var(--line-light); border-radius:.35rem; background:var(--menu-bg); padding:1rem; }
  .self-section + .self-section { margin-top:1rem; }
  .self-section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.8rem; }
  .self-section h2 { margin:0; font-size:1rem; }
  .self-hint { margin:.25rem 0 0; color:var(--muted-light); font-size:.8rem; }
  .self-count { display:inline-flex; align-items:center; gap:.35rem; padding:.32rem .55rem; border:1px solid var(--line-light); border-radius:999px; color:var(--muted-light); font-size:.76rem; white-space:nowrap; }
  .self-count b { color:var(--moss); font-size:.88rem; }
  .self-level-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(14rem, 1fr)); gap:.65rem; }
  .self-level-card { border:1px solid var(--line-light); border-radius:.3rem; background:var(--panel-soft); min-height:7rem; overflow:hidden; }
  .self-level-title { display:flex; align-items:center; gap:.35rem; padding:.55rem .65rem; border-bottom:1px solid var(--line-light); font-size:.82rem; font-weight:700; }
  .self-level-del { margin-left:auto; width:1.35rem; height:1.35rem; display:grid; place-items:center; border:1px solid transparent; border-radius:.2rem; background:transparent; color:var(--muted-light); cursor:pointer; font:inherit; font-size:.72rem; }
  .self-level-del:hover { border-color:#c58a80; color:#c58a80; }
  /* การ์ดเพิ่มระดับ */
  .self-level-add { display:grid; place-items:center; gap:.2rem; min-height:7rem; border:1px dashed var(--line-strong); border-radius:.3rem; background:transparent; color:var(--moss); cursor:pointer; font:inherit; font-size:.82rem; font-weight:700; }
  .self-level-add:hover { border-style:solid; background:var(--hover-soft); }
  .self-level-add > span:first-child { font-size:1.2rem; line-height:1; }
  .self-position-list { display:grid; gap:.35rem; max-height:16rem; overflow:auto; padding:.55rem; }
  .self-position { display:grid; grid-template-columns:minmax(0, 1fr) 5.2rem; gap:.4rem; align-items:center; padding:.4rem; background:var(--menu-bg); border:1px solid var(--line-light); border-radius:.25rem; }
  .self-position-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.78rem; }
  .self-position small { color:var(--muted-light); }
  .self-position select { width:100%; padding:.28rem; border:1px solid var(--line-light); border-radius:.2rem; background:var(--panel-soft); color:inherit; font-size:.75rem; }
  .self-empty { padding:1.3rem; color:var(--muted-light); text-align:center; font-size:.82rem; }
  .self-tools { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  /* การ์ดเครื่องมือของแท็บผลลัพธ์ — โครงกะทัดรัดเดียวกับ .res-card ในหน้าผลลัพธ์รวม */
  .self-res-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(15rem,22rem)); justify-content:start; gap:.55rem; margin:0 0 .8rem; }
  .self-res-card { display:flex; flex-direction:column; gap:.25rem; padding:.55rem .7rem; border:1px solid var(--line-light); border-radius:.32rem; background:var(--menu-bg); }
  .self-res-card > b { color:var(--moss); font-size:.82rem; }
  .self-res-card > p { margin:0; color:var(--muted-light); font-size:.72rem; line-height:1.35; }
  .self-res-card-actions { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin-top:.15rem; }
  .self-res-card .self-btn { min-height:1.75rem; padding:.28rem .65rem; font-size:.76rem; }
  /* เลือกรอบที่จะดาวน์โหลด — รวมรอบที่ปิดแล้ว จึงย้อนดูข้อมูลเก่าได้ */
  .self-dl-form { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; margin:0; }
  .self-round-select { max-width:12rem; padding:.26rem .4rem; border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); color:inherit; font:inherit; font-size:.76rem; }
  .self-round-select:focus-visible { outline:2px solid var(--moss); outline-offset:1px; }
  .self-res-card-note { color:var(--muted-light); font-size:.68rem; }
  .self-btn { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; min-height:2rem; padding:.42rem .75rem; border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font:inherit; font-size:.8rem; }
  .self-btn:hover { border-color:var(--moss); }
  .self-btn.primary { border-color:var(--moss); background:var(--moss); color:#fff; }
  .self-btn[disabled] { opacity:.48; cursor:not-allowed; }
  .self-save-note { color:var(--muted-light); font-size:.78rem; }
  .self-save-note.is-error { color:#c75b52; }
  .self-wrap { overflow:auto; border:1px solid var(--line-light); border-radius:.25rem; background:var(--menu-bg); }
  table.imp-tbl.self-sheet { width:100%; min-width:52rem; border-collapse:separate; border-spacing:0; font-size:.8rem; }
  table.self-sheet th, table.self-sheet td { padding:.48rem .6rem; border-right:1px solid var(--line-light); border-bottom:1px solid var(--line-light); white-space:nowrap; text-align:left; }
  table.self-sheet tr.imp-h1 th { position:sticky; top:0; z-index:4; height:1.7rem; padding:.2rem .5rem; background:var(--panel-soft); color:var(--moss); text-align:center; font-size:.72rem; font-weight:600; }
  table.self-sheet tr.imp-h2 th { position:sticky; top:1.7rem; z-index:4; background:var(--panel-soft); font-weight:600; }
  table.self-sheet tbody tr:hover td { background:var(--hover-soft); }
  table.self-sheet td.no { color:var(--muted-light); text-align:right; }
  table.self-sheet td.code { font-weight:700; font-variant-numeric:tabular-nums; }
  .self-check-cell { text-align:center !important; }
  .self-check { width:1rem; height:1rem; accent-color:var(--moss); cursor:pointer; }
  .self-status { margin-left:.35rem; color:var(--muted-light); font-size:.72rem; }
  /* ระดับ — ตัวเลขล้วน ไม่ต้องมีกรอบ/พื้น */
  .self-level-pill { display:inline-flex; min-width:1.7rem; justify-content:center; color:var(--moss); font-weight:700; font-variant-numeric:tabular-nums; }
  /* ขีด "—" แทนค่าว่าง — ตัวเล็กและจางกว่าตัวเลข จัดกึ่งกลางเซลล์เสมอ */
  .self-dash,
  .self-level-pill.is-empty { display:block; width:100%; color:var(--muted-light); font-size:.68rem; font-weight:400; line-height:1.15; text-align:center; opacity:.75; }

  /* ---- แยกสีเป็นกลุ่มให้กวาดตาง่าย ----
     คอลัมน์ (ตอนเลือกได้): 1 ประเมินตัวเอง · 2 # · 3 รหัส · 4 ชื่อ · 5 ตำแหน่ง · 6 แผนก · 7 ระดับ
     ใช้ nth-child กับ tbody + แถวหัว h2 เพราะแถว h1 เป็นตัวอักษร A-G ตรงลำดับอยู่แล้ว */
  table.self-sheet { --emp-zone:oklch(74% .055 200); --lvl-zone:oklch(70% .10 245); --pick-zone:oklch(72% .09 145); }
  table.self-sheet.is-manage tbody td:nth-child(n+3):nth-child(-n+6) { background:color-mix(in oklch, var(--emp-zone) 9%, var(--menu-bg)); }
  table.self-sheet.is-manage tbody td:nth-child(7) { background:color-mix(in oklch, var(--lvl-zone) 10%, var(--menu-bg)); text-align:center; }
  table.self-sheet.is-manage tbody td.self-check-cell { background:color-mix(in oklch, var(--pick-zone) 10%, var(--menu-bg)); }
  table.self-sheet.is-manage tr.imp-h2 th:nth-child(n+3):nth-child(-n+6) { background:color-mix(in oklch, var(--emp-zone) 20%, var(--panel-soft)); }
  table.self-sheet.is-manage tr.imp-h2 th:nth-child(7) { background:color-mix(in oklch, var(--lvl-zone) 20%, var(--panel-soft)); }
  table.self-sheet.is-manage tr.imp-h2 th:nth-child(1) { background:color-mix(in oklch, var(--pick-zone) 20%, var(--panel-soft)); }
  /* เส้นเปิดโซน */
  table.self-sheet.is-manage tbody td:nth-child(3),
  table.self-sheet.is-manage tbody td:nth-child(7) { box-shadow:inset 1px 0 0 color-mix(in oklch, var(--emp-zone) 45%, var(--line-light)); }
  /* hover ยังเห็นสีโซน แต่เข้มขึ้น */
  table.self-sheet.is-manage tbody tr:hover td:nth-child(n+3):nth-child(-n+6) { background:color-mix(in oklch, var(--emp-zone) 17%, var(--menu-bg)); }
  table.self-sheet.is-manage tbody tr:hover td:nth-child(7) { background:color-mix(in oklch, var(--lvl-zone) 18%, var(--menu-bg)); }
  table.self-sheet.is-manage tbody tr:hover td.self-check-cell { background:color-mix(in oklch, var(--pick-zone) 18%, var(--menu-bg)); }
  table.self-sheet.is-results { --score-zone:oklch(76% .08 145); --answer-zone:oklch(84% .07 90); }
  table.self-sheet.is-results tbody td:nth-child(n+2):nth-child(-n+5) { background:color-mix(in oklch, var(--emp-zone) 9%, var(--menu-bg)); }
  table.self-sheet.is-results tbody td:nth-child(6) { background:color-mix(in oklch, var(--lvl-zone) 10%, var(--menu-bg)); text-align:center; }
  table.self-sheet.is-results tbody td:nth-child(7) { background:color-mix(in oklch, var(--score-zone) 12%, var(--menu-bg)); color:var(--moss); font-weight:700; text-align:right; }
  table.self-sheet.is-results tbody td:nth-child(n+8) { background:color-mix(in oklch, var(--answer-zone) 10%, var(--menu-bg)); text-align:right; }
  table.self-sheet.is-results tr.imp-h2 th:nth-child(n+2):nth-child(-n+5) { background:color-mix(in oklch, var(--emp-zone) 20%, var(--panel-soft)); }
  table.self-sheet.is-results tr.imp-h2 th:nth-child(6) { background:color-mix(in oklch, var(--lvl-zone) 20%, var(--panel-soft)); }
  table.self-sheet.is-results tr.imp-h2 th:nth-child(7) { background:color-mix(in oklch, var(--score-zone) 22%, var(--panel-soft)); }
  table.self-sheet.is-results tr.imp-h2 th:nth-child(n+8) { background:color-mix(in oklch, var(--answer-zone) 20%, var(--panel-soft)); }
  .self-question-tabs { display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:.8rem; }
  .self-question-level.is-active { color:#fff; border-color:var(--moss); background:var(--moss); }
  .self-question-level small { opacity:.72; }
  .self-placeholder { display:grid; place-items:center; min-height:10rem; padding:1rem; color:var(--muted-light); text-align:center; }
  .self-alert { margin-bottom:.75rem; padding:.65rem .75rem; border:1px solid var(--line-light); border-radius:.25rem; font-size:.8rem; }
  .self-alert.is-success { border-color:color-mix(in oklch, var(--moss) 45%, var(--line-light)); background:color-mix(in oklch, var(--moss) 8%, var(--menu-bg)); color:var(--moss); }
  .self-alert.is-error { border-color:#d7aaa5; background:#fff8f7; color:#a84038; }
  .self-question-panel { display:grid; gap:.8rem; }
  .self-create-question { border:1px dashed color-mix(in oklch, var(--moss) 55%, var(--line-light)); border-radius:.3rem; padding:.65rem; background:color-mix(in oklch, var(--moss) 4%, var(--menu-bg)); }
  .self-create-question > summary, .self-add-choice > summary { width:max-content; list-style:none; }
  .self-create-question > summary::-webkit-details-marker, .self-add-choice > summary::-webkit-details-marker { display:none; }
  .self-create-question[open] > summary { margin-bottom:.7rem; }
  .self-builder-step-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:0 0 .65rem; border-bottom:1px dashed var(--line-light); }
  .self-builder-step-head.compact { margin-top:.65rem; padding:.55rem .65rem; border:1px solid var(--line-light); border-bottom:0; border-radius:.25rem .25rem 0 0; background:var(--panel-soft); }
  .self-question-list { display:grid; gap:.8rem; }
  .self-question-card { border:1px solid var(--line-light); border-radius:.3rem; background:var(--menu-bg); overflow:hidden; }
  .self-question-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.65rem .75rem; border-bottom:1px solid var(--line-light); background:var(--panel-soft); }
  .self-question-head > div { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; }
  .self-question-no { font-size:.86rem; font-weight:700; color:var(--moss); }
  .self-option-no { font-size:.78rem; font-weight:800; color:var(--moss); }
  .self-readiness { display:inline-flex; align-items:center; min-height:1.35rem; padding:.15rem .4rem; border:1px solid #d7aaa5; border-radius:999px; background:#fff8f7; color:#a84038; font-size:.68rem; font-weight:700; }
  .self-icon-btn { display:grid; place-items:center; width:1.8rem; height:1.8rem; border:1px solid var(--line-light); border-radius:.22rem; background:var(--menu-bg); color:inherit; font-size:1.1rem; cursor:pointer; }
  .self-icon-btn.danger { color:#a84038; }
  .self-icon-btn:hover { border-color:currentColor; }
  .self-question-form { display:grid; gap:.7rem; }
  .self-question-form.compact { padding:.7rem; }
  .self-language-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.55rem; }
  .self-question-score-line { display:grid; grid-template-columns:minmax(9rem, 11rem) minmax(0, 1fr); gap:.75rem; align-items:end; padding:.55rem .65rem; border:1px solid var(--line-light); border-radius:.25rem; background:var(--panel-soft); }
  .self-question-score-line > .self-hint { align-self:center; margin:0; line-height:1.5; }
  .self-question-form label, .self-choice-form label { display:grid; gap:.25rem; color:var(--muted-light); font-size:.7rem; font-weight:700; letter-spacing:.02em; }
  .self-question-form textarea, .self-question-form input, .self-choice-form input, .self-choice-form select { width:100%; min-height:2.2rem; border:1px solid var(--line-light); border-radius:.22rem; background:var(--menu-bg); color:inherit; padding:.5rem .55rem; font:inherit; font-size:.8rem; resize:vertical; }
  .self-question-form textarea:focus, .self-question-form input:focus, .self-choice-form input:focus, .self-choice-form select:focus { outline:2px solid color-mix(in oklch, var(--moss) 22%, transparent); border-color:var(--moss); }
  .self-form-actions { grid-column:1 / -1; display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  .self-choice-section { border-top:1px solid var(--line-light); padding:.7rem; }
  .self-choice-head { display:flex; align-items:center; justify-content:space-between; gap:.7rem; margin-bottom:.55rem; font-size:.76rem; }
  .self-choice-head span { color:var(--muted-light); }
  .self-choice-list { display:grid; gap:.6rem; }
  .self-choice-row { padding:.55rem; border:1px solid var(--line-light); border-radius:.25rem; background:var(--panel-soft); }
  .self-choice-row-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.45rem; padding-bottom:.4rem; border-bottom:1px dashed var(--line-light); }
  .self-choice-form { display:grid; grid-template-columns:minmax(8rem, .8fr) repeat(3, minmax(8rem, 1fr)) 6.5rem auto; gap:.4rem; align-items:end; }
  .self-choice-form.is-new { margin-top:.5rem; padding:.55rem; border:1px dashed var(--line-light); border-radius:.25rem; }
  .self-choice-form.is-na .self-score-field { opacity:.5; }
  .self-choice-empty { padding:.7rem; border:1px dashed var(--line-light); border-radius:.25rem; color:var(--muted-light); font-size:.76rem; text-align:center; }
  .self-btn.small { min-height:1.9rem; padding:.32rem .55rem; }
  .self-add-choice { margin-top:.55rem; }

  /* Keep the question builder visually consistent with the employee paper form. */
  .self-section.self-question-document {
    width:min(100%, 56rem);
    margin:0 auto;
    padding:2.2rem 2.6rem 2.4rem;
    border:1px solid #e3dfd2;
    border-radius:.25rem;
    background:#fdfcf8;
    color:#26251f;
    box-shadow:0 14px 40px rgb(0 0 0 / 16%);
  }
  .self-question-document .self-section-head {
    position:relative;
    display:block;
    margin:0;
    padding:0 5.5rem 1.1rem;
    border-bottom:2px solid #26251f;
    text-align:center;
  }
  .self-question-paper-logo {
    display:block;
    width:3.1rem;
    height:3.1rem;
    margin:0 auto .45rem;
    object-fit:contain;
  }
  .self-question-document .self-section-head h2 {
    color:#26251f;
    font-size:1.22rem;
    font-weight:800;
    letter-spacing:.02em;
    line-height:1.35;
    text-wrap:balance;
  }
  .self-question-document .self-section-head .self-hint {
    max-width:65ch;
    margin:.25rem auto 0;
    color:#6f6b5e;
    font-size:.82rem;
    line-height:1.6;
  }
  .self-question-document .self-section-head .self-count {
    position:absolute;
    right:0;
    bottom:.9rem;
    border-color:#c9c4b2;
    background:#f6f4ea;
    color:#6f6b5e;
  }
  .self-question-document .self-section-head .self-count b { color:#3c5f2c; }
  .self-question-document .self-alert { margin:1rem .2rem 0; }
  .self-question-document .self-question-tabs {
    margin:0 .2rem 1rem;
    padding:.85rem 0;
    border-bottom:1px dashed #d8d2bf;
  }
  .self-question-document .self-btn {
    border-color:#c8c1ad;
    background:#fff;
    color:#3f3c34;
  }
  .self-question-document .self-btn:hover { border-color:#5b7343; color:#3c5f2c; }
  .self-question-document .self-btn:focus-visible,
  .self-question-document .self-icon-btn:focus-visible,
  .self-question-document input:focus-visible,
  .self-question-document select:focus-visible,
  .self-question-document textarea:focus-visible {
    outline:2px solid rgb(91 115 67 / 24%);
    outline-offset:2px;
  }
  .self-question-document .self-btn.primary,
  .self-question-document .self-question-level.is-active {
    border-color:#5b7343;
    background:#5b7343;
    color:#fff;
  }
  .self-question-document .self-question-panel { gap:1rem; }
  .self-question-document .self-create-question {
    padding:.7rem;
    border:1px dashed #c9c4b2;
    border-radius:.25rem;
    background:#f6f4ea;
  }
  .self-question-document .self-question-list { gap:1.5rem; }
  .self-question-document .self-question-card {
    padding-top:1rem;
    overflow:visible;
    border:0;
    border-top:2px solid #26251f;
    border-radius:0;
    background:transparent;
  }
  .self-question-document .self-question-head {
    padding:0 0 .65rem;
    border-bottom:1px dashed #d8d2bf;
    background:transparent;
  }
  .self-question-document .self-question-no { color:#26251f; }
  .self-question-document .self-hint,
  .self-question-document .self-choice-head span { color:#6f6b5e; }
  .self-question-document .self-icon-btn {
    border-color:#c8c1ad;
    background:#fff;
  }
  .self-question-document .self-question-form.compact { padding:.8rem 0; }
  .self-question-document .self-question-form label,
  .self-question-document .self-choice-form label { color:#6f6b5e; }
  .self-question-document .self-question-form textarea,
  .self-question-document .self-question-form input,
  .self-question-document .self-choice-form input,
  .self-question-document .self-choice-form select {
    border-color:#bcb49f;
    background:#fff;
    color:#26251f;
  }
  .self-question-document .self-question-form textarea:focus,
  .self-question-document .self-question-form input:focus,
  .self-question-document .self-choice-form input:focus,
  .self-question-document .self-choice-form select:focus {
    border-color:#5b7343;
    outline:2px solid rgb(91 115 67 / 20%);
  }
  .self-question-document .self-choice-section {
    padding:.85rem 0 0;
    border-top:1px solid #26251f;
  }
  .self-question-document .self-choice-row { padding:.65rem 0; border:0; border-bottom:1px dashed #d8d2bf; border-radius:0; background:transparent; }
  .self-question-document .self-choice-row-head { border-bottom-color:#d8d2bf; }
  .self-question-document .self-question-score-line,
  .self-question-document .self-builder-step-head.compact { border-color:#c9c4b2; background:#f6f4ea; }
  .self-question-document .self-choice-empty { border-color:#c9c4b2; color:#6f6b5e; }
  .self-question-document .self-choice-form.is-new {
    padding:.65rem;
    border-color:#c9c4b2;
    border-radius:.25rem;
    background:#f6f4ea;
  }
  .self-question-document .self-placeholder {
    min-height:9rem;
    border:1px dashed #c9c4b2;
    border-radius:.25rem;
    color:#6f6b5e;
  }

  /* ---- Self admin: ใช้จังหวะเดียวกับหน้า Assessment scores ---- */
  .self-score-layout { gap:.75rem; }
  .self-score-layout .self-tabs {
    display:flex;
    align-items:flex-start;
    gap:.45rem;
    flex-wrap:wrap;
    margin:0 0 .15rem;
    border-bottom:0;
  }
  .self-score-layout .self-tab {
    padding:.45rem 1rem;
    border:1px solid var(--line-light);
    border-radius:.25rem;
    background:var(--panel-soft);
    color:inherit;
    font-size:.85rem;
    font-weight:600;
    text-decoration:none;
    transition:border-color .15s, background-color .15s;
  }
  .self-score-layout .self-tab:hover { border-color:var(--moss); }
  .self-score-layout .self-tab.is-active { background:var(--moss); border-color:var(--moss); color:#fff; }
  .self-score-layout .self-tab-round { margin-left:auto; padding:.45rem 0; color:var(--muted-light); font-size:.8rem; white-space:nowrap; }
  .self-score-layout .self-tab-round b { color:var(--moss); }
  .self-score-layout .self-head {
    align-items:center;
    min-height:1.8rem;
    padding:.05rem .1rem .2rem;
    border-bottom:1px solid var(--line-light);
  }
  .self-score-layout .self-round { color:var(--muted-light); font-size:.78rem; }
  .self-score-layout .self-count { border-radius:.25rem; background:var(--panel-soft); }
  .self-score-layout .self-section {
    padding:1.1rem 1.2rem 1.2rem;
    border-radius:.4rem;
    background:var(--panel-soft);
  }
  .self-score-layout .self-section-head { margin-bottom:.75rem; }
  .self-score-layout .self-section h2 { font-size:1.02rem; }
  .self-score-layout .self-hint { line-height:1.5; }
  .self-score-layout .self-level-card { background:var(--menu-bg); }
  .self-score-layout .self-wrap { max-height:78vh; }
  .self-score-layout table.self-sheet tr.imp-filter th { position:sticky; top:3.4rem; z-index:3; background:var(--panel-soft); padding:.16rem .3rem; }

  /* แท็บคำถามเดิมเป็นกระดาษเต็มหน้า, ปรับเป็น panel แบบ scores โดยยังคง input/flow เดิม */
  .self-score-layout .qs-page { width:100%; margin:0; }
  .self-score-layout .qs-sheet {
    width:100%;
    padding:1.1rem 1.2rem 1.2rem;
    border:1px solid var(--line-light);
    border-radius:.4rem;
    background:var(--menu-bg);
    color:inherit;
    box-shadow:none;
  }
  .self-score-layout .qs-head { text-align:left; padding-bottom:.75rem; border-bottom:1px solid var(--line-light); }
  .self-score-layout .qs-logo { display:none; }
  .self-score-layout .qs-head h2 { color:inherit; font-size:1.02rem; }
  .self-score-layout .qs-head p { color:var(--muted-light); }
  .self-score-layout .qs-langs { border-color:var(--line-light); background:var(--panel-soft); }
  .self-score-layout .qs-lang { color:var(--muted-light); }
  .self-score-layout .qs-lang.is-active { background:var(--moss); }
  .self-score-layout .qs-lv a { background:var(--panel-soft); color:inherit; }
  .self-score-layout .qs-lv a.is-active { background:var(--moss); border-color:var(--moss); }
  .self-score-layout .qs-q { border-bottom-color:var(--line-light); }
  .self-score-layout .qs-no { color:var(--moss); }
  .self-score-layout .qs-q input[type="text"],
  .self-score-layout .qs-q input[type="number"] { border-color:var(--line-light); background:var(--panel-soft); color:inherit; }
  .self-score-layout .qs-q input:focus { border-color:var(--moss); }
  .self-score-layout .qs-score > span,
  .self-score-layout .qs-cno,
  .self-score-layout .qs-na,
  .self-score-layout .qs-msg { color:var(--muted-light); }
  .self-score-layout .qs-addc,
  .self-score-layout .qs-addq { border-color:color-mix(in oklch, var(--moss) 50%, var(--line-light)); background:var(--panel-soft); color:var(--moss); }
  .self-score-layout .qs-addc:hover,
  .self-score-layout .qs-addq:hover { background:var(--hover-soft); }
  .self-score-layout .qs-foot { border-top:1px solid var(--line-light); }
  .self-score-layout .qs-save { border-color:var(--moss); background:var(--moss); }

  @media (max-width:700px) {
    .self-score-layout .self-tab-round { width:100%; margin-left:0; padding-top:0; }
    .self-score-layout .self-section { padding:.8rem; }
    .self-score-layout .qs-sheet { padding:.8rem; }
  }

  @media print {
    .self-section.self-question-document { box-shadow:none; }
  }
  @media (max-width:700px) {
    .self-section { padding:.75rem; }
    .self-section-head { flex-direction:column; }
    .self-level-grid { grid-template-columns:1fr; }
    .self-language-grid, .self-choice-form, .self-question-score-line { grid-template-columns:1fr; }
    .self-form-actions { align-items:flex-start; flex-direction:column; }
    .self-builder-step-head { align-items:flex-start; flex-direction:column; }
    .self-section.self-question-document { padding:1.3rem 1.1rem 1.6rem; }
    .self-question-document .self-section-head { padding-right:0; padding-left:0; }
    .self-question-document .self-section-head .self-count {
      position:static;
      margin-top:.65rem;
    }
  }
@endsection

@section('content')
  <div class="self-admin self-score-layout">
    <nav class="self-tabs" aria-label="Self assessment settings">
      @foreach ([
        'manage' => ['assessment.self.manageTab', 'จัดการ'],
        'questions' => ['assessment.self.questionsTab', 'กำหนดคำถาม'],
        'results' => ['assessment.self.resultsTab', 'ผลลัพธ์'],
      ] as $tabKey => [$tabI18n, $tabLabel])
        <a href="{{ route('assessment.self.index', ['tab' => $tabKey]) }}"
           class="self-tab {{ $tab === $tabKey ? 'is-active' : '' }}"
           data-i18n="{{ $tabI18n }}">{{ $tabLabel }}</a>
      @endforeach
      <span class="self-tab-round">
        <span data-i18n="assessment.self.openRound">รอบที่เปิด</span>:
        <b>{{ $selfRound->name }}</b> ({{ $selfRound->year }})
      </span>
    </nav>

    <div class="self-head">
      <div class="self-round"><span data-i18n="assessment.self.adminNav">ตั้งค่าการประเมินตัวเอง</span></div>
      <span class="self-count">
        <span data-i18n="assessment.self.selectedCount">ผู้เข้าร่วม</span>
        <b data-selected-count>{{ number_format($selectedCount) }}</b>
      </span>
    </div>

    @if ($tab === 'manage')
      <section class="self-section">
        <div class="self-section-head">
          <div>
            <h2 data-i18n="assessment.self.h11">1.1 ระดับตำแหน่ง</h2>
            <p class="self-hint" data-i18n="assessment.self.h11Hint">กำหนดระดับ 1–5 ตามตำแหน่งของพนักงานสำหรับรอบประเมินตัวเอง</p>
          </div>
        </div>
        <div class="self-level-grid">
          @foreach (array_merge(['unassigned'], array_map('strval', $levelOptions)) as $levelKey)
            <article class="self-level-card" data-level-card="{{ $levelKey }}">
              <div class="self-level-title">
                @if ($levelKey === 'unassigned')
                  <span data-i18n="assessment.self.unassigned">ยังไม่กำหนดระดับ</span>
                @else
                  <span data-i18n="assessment.self.levelLabel">ระดับ</span> {{ $levelKey }}
                @endif
                (<span data-level-count>{{ $positionsByLevel->get($levelKey, collect())->count() }}</span>)
                @if ($levelKey !== 'unassigned')
                  <button type="button" class="self-level-del" data-level-del="{{ $levelKey }}"
                          title="ลบระดับนี้ (ระดับที่สูงกว่าจะเลื่อนลงมาแทน)" aria-label="ลบระดับ {{ $levelKey }}">✕</button>
                @endif
              </div>
              <div class="self-position-list" data-position-list>
                @forelse ($positionsByLevel->get($levelKey, collect()) as $position)
                  <div class="self-position" data-position="{{ $position['code'] }}">
                    <span class="self-position-name" title="{{ $position['name'] }}">
                      {{ $position['name'] }} <small>({{ number_format($position['count']) }})</small>
                    </span>
                    <select data-position-level data-job-code="{{ $position['code'] }}" aria-label="ระดับตำแหน่ง">
                      <option value="" @selected($position['level'] === null)>—</option>
                      @foreach ($levelOptions as $option)
                        <option value="{{ $option }}" @selected($position['level'] === $option)>{{ $option }}</option>
                      @endforeach
                    </select>
                  </div>
                @empty
                  <div class="self-empty" data-level-empty>—</div>
                @endforelse
              </div>
            </article>
          @endforeach

          {{-- การ์ดเพิ่มระดับ — เหมือนปุ่ม + ของหน้าประเมินพนักงาน --}}
          <button type="button" class="self-level-card self-level-add" data-level-add
                  title="เพิ่มระดับถัดไป" aria-label="เพิ่มระดับถัดไป">
            <span aria-hidden="true">＋</span>
            <span data-i18n="assessment.self.addLevel">เพิ่มระดับ</span>
          </button>
        </div>
      </section>

      <section class="self-section">
        <div class="self-section-head">
          <div>
            <h2 data-i18n="assessment.self.h12">1.2 ข้อมูลพนักงาน</h2>
            <p class="self-hint" data-i18n="assessment.self.h12Hint">เลือกพนักงานที่ต้องประเมินตัวเอง ข้อมูลดึงจาก Employee master ปัจจุบัน</p>
          </div>
          <div class="self-tools">
            <button type="button" class="self-btn" data-select-visible>
              <span data-i18n="assessment.self.selectAllVisible">เลือกทั้งหมดที่แสดง</span>
            </button>
            <button type="button" class="self-btn primary" data-save-participants disabled>
              <span data-i18n="assessment.self.saveSelection">บันทึกรายชื่อ</span>
            </button>
          </div>
        </div>
        <div class="self-tools" style="margin-bottom:.65rem">
          <span class="ss-count" data-filter-count="self-manage">
            <span data-visible-count>{{ number_format($rows->count()) }}</span> / <span data-total-count>{{ number_format($rows->count()) }}</span>
          </span>
          <span class="self-save-note" data-save-note></span>
        </div>
        @include('assessment.partials.self-employee-table', ['tableName' => 'self-manage', 'tableRows' => $rows, 'selectable' => true])
      </section>
    @elseif ($tab === 'questions')
      @php
        $requestedLevel = (int) request()->query('level', 1);
        $activeQuestionLevel = in_array($requestedLevel, $levelOptions, true) ? $requestedLevel : (int) ($levelOptions[0] ?? 1);
        // ส่งชุดคำถามของระดับที่เลือกให้ JS — แก้ทั้งชุดแล้วบันทึกทีเดียว
        $qRows = $questionsByLevel->get($activeQuestionLevel, collect())->map(fn ($q) => [
          'id' => $q->id,
          'q_th' => $q->q_th, 'q_en' => $q->q_en, 'q_my' => $q->q_my,
          'full_score' => (float) $q->full_score,
          'choices' => $q->choices->map(fn ($c) => [
            'id' => $c->id,
            'choice_th' => $c->choice_th, 'choice_en' => $c->choice_en, 'choice_my' => $c->choice_my,
            'score' => $c->score === null ? null : (float) $c->score,
            'is_na' => (bool) $c->is_na,
          ])->values(),
        ])->values();
      @endphp

      <style>
        .qs-page { width:min(100%, 54rem); margin:0 auto; }
        .qs-sheet { background:#fdfcf8; color:#26251f; border:1px solid #e3dfd2; border-radius:.25rem; box-shadow:0 14px 40px rgb(0 0 0 / 16%); padding:1.8rem 2rem 1.9rem; }
        @media (max-width:700px) { .qs-sheet { padding:1.2rem 1rem 1.4rem; } }
        .qs-head { text-align:center; padding-bottom:.9rem; border-bottom:2px solid #26251f; }
        .qs-logo { display:block; width:2.8rem; height:2.8rem; margin:0 auto .4rem; object-fit:contain; }
        .qs-head h2 { margin:0 0 .15rem; font-size:1.1rem; font-weight:800; }
        .qs-head p { margin:0; color:#6f6b5e; font-size:.78rem; }

        .qs-bar { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin:0 0 .6rem; }
        .qs-lv { display:flex; gap:.3rem; flex-wrap:wrap; }
        .qs-lv a { padding:.32rem .8rem; border:1px solid var(--line-light); border-radius:.22rem; background:var(--panel-soft); color:inherit; text-decoration:none; font-size:.78rem; font-weight:700; }
        .qs-lv a.is-active { background:var(--moss); border-color:var(--moss); color:#fff; }
        .qs-langs { display:inline-flex; gap:.12rem; margin-left:auto; padding:.1rem; border:1px solid #cfc9b6; border-radius:.22rem; background:#fff; }
        .qs-lang { min-width:2.2rem; padding:.1rem .4rem; border:0; border-radius:.18rem; background:transparent; color:#6f6b5e; cursor:pointer; font:inherit; font-size:.68rem; font-weight:700; }
        .qs-lang.is-active { background:#3c5f2c; color:#fff; }

        /* 1 คำถาม = 1 บล็อก · เลขข้อ + คำถาม + คะแนนเต็ม อยู่บรรทัดเดียวกัน */
        .qs-q { padding:.85rem 0 .9rem; border-bottom:1px dashed #ded9c8; }
        .qs-q:last-of-type { border-bottom:0; }
        .qs-q-line { display:grid; grid-template-columns:3.8rem minmax(0,1fr) 5.6rem 1.5rem; gap:.5rem; align-items:center; }
        .qs-no { color:#3c5f2c; font-size:.8rem; font-weight:800; white-space:nowrap; }
        .qs-q input[type="text"], .qs-q input[type="number"] { width:100%; min-width:0; padding:.34rem .5rem; border:1px solid #ded9c8; border-radius:.2rem; background:#fff; color:#26251f; font:inherit; font-size:.84rem; }
        .qs-q input:focus { outline:none; border-color:#3c5f2c; }
        .qs-score { display:flex; align-items:center; gap:.25rem; }
        .qs-score > span { flex:none; color:#8a8574; font-size:.66rem; font-weight:700; }
        .qs-del { width:1.5rem; height:1.5rem; display:grid; place-items:center; border:1px solid transparent; border-radius:.2rem; background:transparent; color:#c2bcab; cursor:pointer; font:inherit; font-size:.75rem; }
        .qs-del:hover { border-color:#c58a80; color:#c58a80; }

        /* ตัวเลือกอยู่ใต้คำถาม เยื้องเข้ามา */
        .qs-cs { margin:.45rem 0 0 3.8rem; display:grid; gap:.3rem; }
        .qs-c { display:grid; grid-template-columns:5rem minmax(0,1fr) 4.4rem auto 1.5rem; gap:.4rem; align-items:center; }
        /* ติ๊ก N/A → ซ่อนช่องข้อความ/คะแนน · N/A ขยับมาชิดเลขตัวเลือก */
        .qs-c.is-na { grid-template-columns:5rem auto auto 1.5rem; }
        .qs-cno { color:#8a8574; font-size:.7rem; font-weight:700; white-space:nowrap; }
        .qs-na-note { color:#a8a294; font-size:.72rem; font-style:italic; }
        .qs-na { display:inline-flex; align-items:center; gap:.25rem; color:#6f6b5e; font-size:.7rem; white-space:nowrap; cursor:pointer; }
        .qs-addc { justify-self:start; margin:.15rem 0 0 4.9rem; padding:.2rem .55rem; border:1px dashed #b9c9ac; border-radius:.2rem; background:#fff; color:#3c5f2c; cursor:pointer; font:inherit; font-size:.72rem; font-weight:700; }
        .qs-addc:hover { border-style:solid; background:#f2f7ee; }

        .qs-addq { width:100%; margin-top:.8rem; padding:.5rem; border:1px dashed #c9d5c0; border-radius:.22rem; background:#fff; color:#3c5f2c; cursor:pointer; font:inherit; font-size:.8rem; font-weight:700; }
        .qs-addq:hover { border-style:solid; background:#f2f7ee; }
        .qs-empty { padding:1.4rem 0; color:#8a8574; font-size:.82rem; text-align:center; }
        .qs-foot { display:flex; align-items:center; gap:.6rem; margin-top:1rem; padding-top:.8rem; border-top:2px solid #26251f; }
        .qs-msg { color:#6f6b5e; font-size:.76rem; }
        .qs-msg.is-err { color:#c05a4d; font-weight:700; }
        .qs-save { margin-left:auto; padding:.42rem 1.3rem; border:1px solid #3c5f2c; border-radius:.22rem; background:#3c5f2c; color:#fff; cursor:pointer; font:inherit; font-size:.82rem; font-weight:700; }
        .qs-save[disabled] { opacity:.5; cursor:default; }
      </style>

      <div class="qs-page">
        <div class="qs-bar">
          <div class="qs-lv">
            @foreach ($levelOptions as $level)
              <a href="{{ route('assessment.self.index', ['tab' => 'questions', 'level' => $level]) }}"
                 class="nav-go {{ $activeQuestionLevel === $level ? 'is-active' : '' }}"><span data-i18n="assessment.self.levelLabel">ระดับ</span> {{ $level }}</a>
            @endforeach
          </div>
          <div class="qs-langs" role="group" aria-label="ภาษาที่กำลังแก้">
            <button type="button" class="qs-lang is-active" data-qs-lang="th">TH</button>
            <button type="button" class="qs-lang" data-qs-lang="en">EN</button>
            <button type="button" class="qs-lang" data-qs-lang="my">MY</button>
          </div>
        </div>

        <div class="qs-sheet">
          <div class="qs-head">
            <img class="qs-logo" src="{{ asset('assets/assessment/form-logo.png') }}" alt="" aria-hidden="true">
            <h2 data-i18n="assessment.self.questionsTitle">กำหนดคำถามตามระดับ</h2>
            <p><span data-i18n="assessment.self.levelLabel">ระดับ</span> {{ $activeQuestionLevel }} ·
               <span data-i18n="assessment.self.questionsHint2">คำถามที่ตั้งไว้จะไปแสดงในแบบฟอร์มของพนักงานระดับนี้</span></p>
          </div>

          <div data-qs-list></div>

          <button type="button" class="qs-addq" data-qs-addq>
            <span aria-hidden="true">+</span> <span data-i18n="assessment.self.addQuestion">เพิ่มคำถาม</span>
          </button>

          <div class="qs-foot">
            <span class="qs-msg" data-qs-msg></span>
            <button type="button" class="qs-save" data-qs-save data-i18n="assessment.questions.scaleSave">บันทึก</button>
          </div>
        </div>
      </div>

      <script>
        'use strict';
        (function () {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
          const url = @json(route('assessment.self.questions.set'));
          const level = {{ $activeQuestionLevel }};
          const t = (k, f) => window.__portalLang?.text ? window.__portalLang.text(k, f) : f;

          let rows = @json($qRows);
          let lang = 'th';

          const list = document.querySelector('[data-qs-list]');
          const msg = document.querySelector('[data-qs-msg]');
          const saveBtn = document.querySelector('[data-qs-save]');
          const esc = (s) => String(s == null ? '' : s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
          const blankChoice = () => ({ id: null, choice_th: '', choice_en: '', choice_my: '', score: 0, is_na: false });

          const cHtml = (c, ci) => '<div class="qs-c' + (c.is_na ? ' is-na' : '') + '" data-qs-c>'
            + '<span class="qs-cno">' + esc(t('assessment.self.choiceNo', 'ตัวเลือกที่')) + ' ' + (ci + 1) + '</span>'
            + (c.is_na
                ? '<span class="qs-na-note" data-i18n="assessment.self.naNote">ไม่นำมาคำนวณคะแนน</span>'
                : '<input type="text" data-qs-ctext value="' + esc(c['choice_' + lang] || '') + '" placeholder="' + esc(t('assessment.self.choicePlaceholder', 'ตัวเลือก')) + '">'
                  + '<input type="number" step="0.01" min="0" data-qs-cscore value="' + (c.score == null ? '' : c.score) + '" placeholder="0">')
            + '<label class="qs-na"><input type="checkbox" data-qs-cna' + (c.is_na ? ' checked' : '') + '> N/A</label>'
            + '<button type="button" class="qs-del" data-qs-cdel aria-label="ลบตัวเลือก">&#10005;</button>'
            + '</div>';

          const qHtml = (q, i) => '<div class="qs-q" data-qs-q>'
            + '<div class="qs-q-line">'
            + '<span class="qs-no"><span data-i18n="assessment.self.questionShort">ข้อที่</span> ' + (i + 1) + '</span>'
            + '<input type="text" data-qs-qtext value="' + esc(q['q_' + lang] || '') + '" placeholder="' + esc(t('assessment.self.questionPlaceholder', 'ข้อความคำถาม')) + '">'
            + '<span class="qs-score"><span>เต็ม</span><input type="number" step="0.01" min="0.01" data-qs-qfull value="' + (q.full_score == null ? 5 : q.full_score) + '"></span>'
            + '<button type="button" class="qs-del" data-qs-qdel aria-label="ลบคำถามนี้">&#10005;</button>'
            + '</div>'
            + '<div class="qs-cs" data-qs-cs>' + (q.choices || []).map(cHtml).join('') + '</div>'
            + '<button type="button" class="qs-addc" data-qs-addc>+ <span data-i18n="assessment.self.addChoice">เพิ่มตัวเลือก</span></button>'
            + '</div>';

          const render = () => {
            list.innerHTML = rows.length
              ? rows.map(qHtml).join('')
              : '<p class="qs-empty" data-i18n="assessment.self.noQuestions">ยังไม่มีคำถามในระดับนี้ กดเพิ่มคำถามเพื่อเริ่ม</p>';
          };

          // ดึงค่าที่พิมพ์กลับเข้า state (เรียกก่อนสลับภาษา / เพิ่ม / ลบ / บันทึก)
          const sync = () => {
            const qs = Array.from(list.querySelectorAll('[data-qs-q]'));
            rows = qs.map((el, i) => {
              const base = rows[i] || { id: null, q_th: '', q_en: '', q_my: '', full_score: 5, choices: [] };
              const out = Object.assign({}, base);
              out['q_' + lang] = el.querySelector('[data-qs-qtext]').value.trim();
              out.full_score = parseFloat(el.querySelector('[data-qs-qfull]').value) || 0;
              out.choices = Array.from(el.querySelectorAll('[data-qs-c]')).map((ce, ci) => {
                const cb = (base.choices && base.choices[ci]) || blankChoice();
                const co = Object.assign({}, cb);
                co.is_na = ce.querySelector('[data-qs-cna]').checked;
                // ติ๊ก N/A แล้วช่องข้อความ/คะแนนถูกซ่อน — คงค่าเดิมไว้ ไม่ล้างทิ้ง
                const textEl = ce.querySelector('[data-qs-ctext]');
                const scoreEl = ce.querySelector('[data-qs-cscore]');
                if (textEl) co['choice_' + lang] = textEl.value.trim();
                if (co.is_na) {
                  co.score = null;
                  if (!(co.choice_th || co.choice_en || co.choice_my)) {
                    co.choice_th = 'ไม่ประเมิน'; co.choice_en = 'Not assessed'; co.choice_my = 'မအကဲဖြတ်ပါ';
                  }
                } else if (scoreEl) {
                  co.score = parseFloat(scoreEl.value) || 0;
                }
                return co;
              });
              return out;
            });
          };

          document.querySelectorAll('[data-qs-lang]').forEach((btn) => {
            btn.addEventListener('click', () => {
              sync();
              lang = btn.dataset.qsLang;
              document.querySelectorAll('[data-qs-lang]').forEach((b) => b.classList.toggle('is-active', b === btn));
              render();
            });
          });

          list.addEventListener('click', (e) => {
            const qEl = e.target.closest('[data-qs-q]');
            if (!qEl) return;
            const qIndex = Array.from(list.querySelectorAll('[data-qs-q]')).indexOf(qEl);

            if (e.target.closest('[data-qs-qdel]')) {
              sync(); rows.splice(qIndex, 1); render(); return;
            }
            if (e.target.closest('[data-qs-cdel]')) {
              const cEl = e.target.closest('[data-qs-c]');
              const ci = Array.from(qEl.querySelectorAll('[data-qs-c]')).indexOf(cEl);
              sync();
              rows[qIndex].choices.splice(ci, 1);
              if (!rows[qIndex].choices.length) rows[qIndex].choices.push(blankChoice());
              render(); return;
            }
            if (e.target.closest('[data-qs-addc]')) {
              sync();
              rows[qIndex].choices.push(blankChoice());
              render();
              const target = list.querySelectorAll('[data-qs-q]')[qIndex];
              const boxes = target ? target.querySelectorAll('[data-qs-c] [data-qs-ctext]') : [];
              if (boxes.length) boxes[boxes.length - 1].focus();
              return;
            }
          });

          // ติ๊ก N/A แล้ววาดใหม่ (ซ่อนช่องข้อความ/คะแนนของตัวเลือกนั้น)
          list.addEventListener('change', (e) => {
            if (!e.target.closest('[data-qs-cna]')) return;
            sync();
            render();
          });

          document.querySelector('[data-qs-addq]')?.addEventListener('click', () => {
            sync();
            rows.push({ id: null, q_th: '', q_en: '', q_my: '', full_score: 5, choices: [blankChoice()] });
            render();
            const all = list.querySelectorAll('[data-qs-q] [data-qs-qtext]');
            if (all.length) all[all.length - 1].focus();
          });

          saveBtn?.addEventListener('click', async () => {
            sync();
            const payload = rows
              .filter((q) => q.q_th || q.q_en || q.q_my)
              .map((q) => Object.assign({}, q, {
                choices: (q.choices || []).filter((c) => c.choice_th || c.choice_en || c.choice_my),
              }))
              .filter((q) => q.choices.length > 0);

            if (rows.length && payload.length !== rows.length) {
              msg.textContent = t('assessment.self.needChoice', 'แต่ละข้อต้องมีข้อความคำถามและอย่างน้อย 1 ตัวเลือก');
              msg.classList.add('is-err');
              return;
            }

            saveBtn.disabled = true;
            msg.classList.remove('is-err');
            msg.textContent = t('assessment.self.saving', 'กำลังบันทึก...');
            try {
              const res = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ level: level, questions: payload }),
              });
              const json = await res.json();
              if (!res.ok || !json.ok) throw new Error(json.message || 'save failed');
              msg.textContent = t('assessment.self.saved', 'บันทึกแล้ว');
              setTimeout(() => window.location.reload(), 600);
            } catch (err) {
              msg.textContent = err.message;
              msg.classList.add('is-err');
              saveBtn.disabled = false;
            }
          });

          render();
        })();
      </script>
    @else
      <section class="self-section">
        <div class="self-section-head">
          <div>
            <h2 data-i18n="assessment.self.resultsTitle">ผลลัพธ์การประเมินตัวเอง</h2>
            <p class="self-hint" data-i18n="assessment.self.resultsHint">แสดงเฉพาะผู้ที่ Admin เลือกไว้ คะแนนจะเชื่อมหลังจากกำหนดคำถามและสูตรคำนวณ</p>
          </div>
        </div>
        {{-- Export ผลลัพธ์ — อยู่ใต้หัวข้อ ชิดซ้าย · คำอธิบายอยู่ใน tooltip ของช่องเลือกรอบ --}}
        <div class="self-res-cards">
          <div class="self-res-card">
            <b data-i18n="assessment.self.exportTitle">Export ผลลัพธ์การประเมินตัวเอง</b>
            <div class="self-res-card-actions">
              <form action="{{ route('assessment.self.results.download') }}" method="GET" class="self-dl-form">
                <select name="round" class="self-round-select" data-i18n-title="assessment.self.roundPicker" title="เลือกรอบที่จะดาวน์โหลด · ● = รอบที่เปิดอยู่">
                  @foreach ($selfRounds as $r)
                    <option value="{{ $r->id }}" @selected((int) $r->id === (int) $selfRound->id)>{{ $r->isOpen() ? '● ' : '' }}{{ $r->name }} ({{ $r->year }})</option>
                  @endforeach
                </select>
                <button type="submit" class="self-btn primary" data-i18n="assessment.self.exportButton">⬇ ดาวน์โหลด</button>
              </form>
            </div>
          </div>
        </div>
        <div class="self-tools" style="margin-bottom:.65rem">
          <span class="ss-count" data-filter-count="self-results">
            <span data-visible-count>{{ number_format($resultRows->count()) }}</span> / <span data-total-count>{{ number_format($resultRows->count()) }}</span>
          </span>
        </div>
        @include('assessment.partials.self-employee-table', ['tableName' => 'self-results', 'tableRows' => $resultRows, 'selectable' => false])
      </section>
    @endif
  </div>

  @if ($tab !== 'questions')
    @include('assessment.partials.xlsfilter')
  @endif
@endsection
