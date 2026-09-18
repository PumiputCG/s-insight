@extends('layouts.portal')

@section('title', 'รอบรายเดือน · 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.rounds.title">รอบรายเดือน</span>
@endsection

@section('page-style')
    .a5o-wrap { display:grid; gap:1rem; width:min(100%, 82rem); margin:0 auto; }
    .a5o-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1rem 1.15rem; }
    .a5o-head h2 { margin:0 0 .22rem; color:var(--light-text); font-size:1.08rem; }
    .a5o-head p { margin:0; color:var(--muted-light); font-size:.82rem; }
    .a5o-open-chip { display:inline-flex; align-items:center; gap:.4rem; padding:.32rem .75rem; border:1px solid rgb(76 175 125 / 45%); border-radius:999px; background:rgb(76 175 125 / 10%); color:#4caf7d; font-size:.78rem; font-weight:800; }
    .a5o-open-chip::before { content:""; width:.45rem; height:.45rem; border-radius:50%; background:currentColor; }
    .a5o-open-chip.none { border-color:var(--line-light); background:var(--hover-soft); color:var(--muted-light); }

    .a5o-yearbar { display:flex; align-items:center; justify-content:center; gap:.65rem; }
    .a5o-yearbar a, .a5o-yearbar b { display:inline-flex; align-items:center; justify-content:center; min-height:2.1rem; padding:.35rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--muted-light); font-size:.82rem; font-weight:700; text-decoration:none; }
    .a5o-yearbar a:hover { border-color:var(--moss); color:var(--moss); }
    .a5o-yearbar b { border-color:var(--moss); color:var(--light-text); font-size:.92rem; padding:.35rem 1.2rem; }

    .a5o-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.75rem; }
    .a5o-month { display:grid; gap:.55rem; align-content:start; padding:.85rem .9rem; border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--menu-bg); }
    .a5o-month.is-open-round { border-color:rgb(76 175 125 / 55%); background:rgb(76 175 125 / 5%); }
    .a5o-month.is-current { box-shadow:inset 0 0 0 1px color-mix(in srgb, var(--moss) 40%, transparent); }
    .a5o-month-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
    .a5o-month-head strong { color:var(--light-text); font-size:.92rem; }
    .a5o-state { display:inline-flex; align-items:center; gap:.3rem; padding:.14rem .5rem; border-radius:999px; font-size:.66rem; font-weight:800; background:var(--hover-soft); color:var(--muted-light); white-space:nowrap; }
    .a5o-state::before { content:""; width:.34rem; height:.34rem; border-radius:50%; background:currentColor; }
    .a5o-state.open { background:rgb(76 175 125 / 14%); color:#4caf7d; }
    .a5o-state.closed { background:rgb(217 138 128 / 12%); color:#d98a80; }

    /* รายการครั้งตรวจย่อยในเดือน */
    .a5o-insp-list { list-style:none; display:grid; gap:.5rem; margin:0; padding:0; }
    .a5o-insp { display:grid; gap:.35rem; padding:.55rem .6rem; border:1px solid var(--line-light); border-left-width:3px; border-radius: 0.25rem; background:var(--panel-soft); }
    .a5o-insp.is-open { border-left-color:#4caf7d; background:rgb(76 175 125 / 6%); }
    .a5o-insp-info { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .a5o-insp-info b { color:var(--light-text); font-size:.78rem; }
    .a5o-insp-date { color:var(--moss); font-size:.74rem; font-weight:750; }
    .a5o-insp-stat { color:var(--muted-light); font-size:.68rem; line-height:1.4; }
    .a5o-insp-stat b { color:var(--light-text); }
    .a5o-insp-actions { display:flex; gap:.4rem; }
    .a5o-insp-actions .a5o-del-form { flex:none; }
    .a5o-btn.del-btn { flex:none; width:2.4rem; min-width:0; padding:0; font-size:.9rem; border-color:rgb(217 138 128 / 45%); color:#d98a80; background:rgb(217 138 128 / 6%); }
    .a5o-btn.del-btn:hover { border-color:#d98a80; filter:brightness(1.05); }
    .a5o-insp-actions form { display:flex; flex:1; }

    .a5o-btn { flex:1; display:inline-flex; align-items:center; justify-content:center; min-height:2rem; padding:.4rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); cursor:pointer; font-size:.76rem; font-weight:750; }
    .a5o-btn:hover { border-color:var(--moss); color:var(--moss); }
    .a5o-btn.open-btn { background:var(--moss); border-color:var(--moss); color:#fff; }
    .a5o-btn.open-btn:hover { color:#fff; filter:brightness(1.06); }
    .a5o-btn.close-btn { border-color:rgb(217 138 128 / 55%); color:#d98a80; background:rgb(217 138 128 / 6%); }
    .a5o-btn.close-btn:hover { border-color:#d98a80; filter:brightness(1.05); }
    .a5o-btn.newinspect-btn { border-color:color-mix(in srgb, var(--moss) 45%, var(--line-light)); color:var(--moss); background:color-mix(in srgb, var(--moss) 8%, transparent); }
    .a5o-month-setup { margin-top:.1rem; }
    .a5o-empty-line { min-height:1rem; color:var(--muted-light); font-size:.72rem; }

    /* Modal ตั้งค่าครั้งตรวจ */
    .a5o-modal[hidden] { display:none; }
    .a5o-modal { position:fixed; inset:0; z-index:2000; display:grid; place-items:center; padding:1rem; background:rgb(10 14 10 / 55%); backdrop-filter:blur(3px); }
    .a5o-modal-backdrop { position:absolute; inset:0; border:0; background:transparent; cursor:pointer; }
    .a5o-modal-card { position:relative; z-index:1; width:min(100%, 30rem); max-height:90vh; overflow:auto; border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); padding:1.3rem; box-shadow:0 30px 80px rgb(0 0 0 / 40%); }
    .a5o-modal-card h3 { margin:0 0 .3rem; color:var(--light-text); font-size:1.05rem; }
    .a5o-modal-hint { margin:0 0 .9rem; color:var(--muted-light); font-size:.78rem; line-height:1.5; }
    .a5o-date-list { display:grid; gap:.5rem; margin-bottom:.7rem; }
    .a5o-date-card { display:flex; align-items:center; gap:.55rem; padding:.5rem .6rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); }
    .a5o-date-card b { flex:none; color:var(--light-text); font-size:.78rem; }
    .a5o-date-card input[type="date"] { flex:1; min-width:0; min-height:2.2rem; padding:.35rem .5rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:var(--light-text); font:inherit; font-size:.82rem; }
    .a5o-date-card .a5o-date-lock { flex:none; color:var(--muted-light); font-size:.66rem; }
    .a5o-date-rm { flex:none; width:1.9rem; height:1.9rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:#d98a80; cursor:pointer; font-size:1rem; }
    .a5o-date-rm:hover { border-color:#d98a80; }
    .a5o-modal-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
    .a5o-modal-actions .a5o-btn { flex:none; min-width:6rem; }

    @media (max-width: 960px) { .a5o-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 520px) { .a5o-grid { grid-template-columns:1fr; } }
@endsection

@section('content')
  <div class="a5o-wrap">
    <section class="a5o-head">
      <div>
        <h2 data-i18n="a5s.rounds.title">รอบรายเดือน</h2>
        <p data-i18n="a5s.rounds.hint2">เปิดได้ทีละครั้งตรวจ — เปิดครั้งใหม่ระบบจะปิดครั้งเดิมให้ · 1 เดือนตั้งได้หลายครั้ง (ใส่วันที่)</p>
      </div>
      @if ($openRound)
        <span class="a5o-open-chip"><span data-i18n="a5s.rounds.openRound">รอบที่เปิด:</span> <span data-month-label data-month="{{ $openRound->month }}">{{ $monthNames[$openRound->month] ?? $openRound->month }}</span> {{ $openRound->year }}@if (($openRound->seq ?? 1) > 1) · <span data-i18n="a5s.rounds.times">ครั้ง</span>{{ $openRound->seq }}@endif</span>
      @else
        <span class="a5o-open-chip none" data-i18n="a5s.rounds.noOpenRound">ยังไม่มีการเปิดรอบ</span>
      @endif
    </section>

    <div class="a5o-yearbar">
      <a class="nav-go" href="{{ route('area5s.rounds.index', ['year' => $yearBe - 1]) }}" data-i18n-aria="a5s.rounds.prevYear" aria-label="ปีก่อนหน้า">‹ {{ $yearBe - 1 }}</a>
      <b><span data-i18n="a5s.rounds.yearPrefix">พ.ศ.</span> {{ $yearBe }}</b>
      <a class="nav-go" href="{{ route('area5s.rounds.index', ['year' => $yearBe + 1]) }}" data-i18n-aria="a5s.rounds.nextYear" aria-label="ปีถัดไป">{{ $yearBe + 1 }} ›</a>
    </div>

    <div class="a5o-grid">
      @foreach ($monthNames as $m => $name)
        @php
          $insp = ($rounds->get($m) ?? collect())->sortBy('seq')->values();
          $hasOpen = $insp->contains(fn ($r) => $r->isOpen());
          $isCurrent = $yearBe === $currentYearBe && $m === $currentMonth;
        @endphp
        <article class="a5o-month {{ $hasOpen ? 'is-open-round' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
          <div class="a5o-month-head">
            <strong data-month-label data-month="{{ $m }}">{{ $name }}</strong>
            @if ($insp->isNotEmpty())
              <span class="a5o-state {{ $hasOpen ? 'open' : '' }}">{{ number_format($insp->count()) }} <span data-i18n="a5s.rounds.times">ครั้ง</span></span>
            @endif
          </div>

          @if ($insp->isEmpty())
            <div class="a5o-empty-line" data-i18n="a5s.rounds.notSetup">ยังไม่ตั้งค่าครั้งตรวจ</div>
            <button type="button" class="a5o-btn open-btn" data-setup="{{ $m }}" data-i18n="a5s.rounds.setupBtn">ตั้งค่ารอบ</button>
          @else
            <ul class="a5o-insp-list">
              @foreach ($insp as $round)
                @php $st = $stats->get($round->id); @endphp
                <li class="a5o-insp {{ $round->isOpen() ? 'is-open' : '' }}">
                  <div class="a5o-insp-info">
                    <b><span data-i18n="a5s.review.attemptNo">ครั้งที่</span> {{ $round->seq }}</b>
                    <span class="a5o-insp-date">{{ $round->inspected_on ? $round->inspected_on->format('d/m/').($round->inspected_on->year + 543) : '—' }}</span>
                    @if ($round->isOpen())
                      <span class="a5o-state open" data-i18n="a5s.rounds.stateOpen">เปิดอยู่</span>
                    @elseif ($round->status === 'closed')
                      <span class="a5o-state closed" data-i18n="a5s.rounds.stateClosed">ปิดแล้ว</span>
                    @else
                      <span class="a5o-state" data-i18n="a5s.rounds.statePlanned">รอเปิด</span>
                    @endif
                  </div>
                  @if ($st)
                    <div class="a5o-insp-stat"><b>{{ number_format($st->passed) }}</b> <span data-i18n="a5s.rounds.passed">ผ่าน</span> · <b>{{ number_format($st->failed) }}</b> <span data-i18n="a5s.rounds.rejected">ปฏิเสธ</span> · <b>{{ number_format($st->waiting) }}</b> <span data-i18n="a5s.rounds.waiting">รอตรวจ</span></div>
                  @endif
                  <div class="a5o-insp-actions">
                    @if ($round->isOpen())
                      <form method="POST" action="{{ route('area5s.rounds.close', $round) }}" data-close-round data-label="{{ $name }} {{ $yearBe }} · {{ $round->seq }}">
                        @csrf
                        <button type="submit" class="a5o-btn close-btn" data-i18n="a5s.rounds.close">ปิดรอบ</button>
                      </form>
                    @else
                      <form method="POST" action="{{ route('area5s.rounds.openRound', $round) }}" data-open-one data-label="{{ $name }} {{ $yearBe }} · {{ $round->seq }}">
                        @csrf
                        <button type="submit" class="a5o-btn open-btn" data-i18n="{{ $round->status === 'closed' ? 'a5s.rounds.reopen' : 'a5s.rounds.openDo' }}">{{ $round->status === 'closed' ? 'เปิดอีกครั้ง' : 'เปิด' }}</button>
                      </form>
                    @endif
                    @if (in_array($round->id, $deletableRoundIds, true))
                      {{-- ลบครั้งตรวจ (เฉพาะที่ยังไม่มีข้อมูล) (Manager 2026-07-24) --}}
                      <form method="POST" action="{{ route('area5s.rounds.destroy', $round) }}" class="a5o-del-form" data-delete-round data-label="{{ $name }} {{ $yearBe }} · {{ $round->seq }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="a5o-btn del-btn" data-i18n-aria="a5s.rounds.deleteInspection" aria-label="ลบครั้งตรวจ">🗑</button>
                      </form>
                    @endif
                  </div>
                </li>
              @endforeach
            </ul>
            <button type="button" class="a5o-btn newinspect-btn a5o-month-setup" data-setup="{{ $m }}" data-i18n="a5s.rounds.editInspections">ตั้งค่า/เพิ่มครั้งตรวจ</button>
          @endif
        </article>
      @endforeach
    </div>
  </div>

  {{-- Modal ตั้งค่าครั้งตรวจของเดือน (Manager 2026-07-24) --}}
  <div class="a5o-modal" data-setup-modal hidden aria-hidden="true">
    <button type="button" class="a5o-modal-backdrop" data-setup-cancel aria-label="ปิด" data-i18n-aria="a5s.common.close"></button>
    <form class="a5o-modal-card" method="POST" action="{{ route('area5s.rounds.setup') }}" role="dialog" aria-modal="true" data-setup-form>
      @csrf
      <input type="hidden" name="year" value="{{ $yearBe }}">
      <input type="hidden" name="month" data-setup-month value="">
      <h3><span data-i18n="a5s.rounds.setupTitle">ประเมินกี่ครั้งในเดือน</span> <span data-setup-name></span>?</h3>
      <p class="a5o-modal-hint" data-i18n="a5s.rounds.setupHint">ใส่วันที่ประเมินแต่ละครั้ง แล้วกดยืนยัน — ระบบสร้างไว้ก่อน (ยังไม่เปิด) ค่อยกด "เปิด" ทีละครั้งเมื่อถึงวัน</p>
      <div class="a5o-date-list" data-date-list></div>
      <button type="button" class="a5o-btn newinspect-btn" data-add-date data-i18n="a5s.rounds.addInspection">+ เพิ่มประเมิน</button>
      <div class="a5o-modal-actions">
        <button type="button" class="a5o-btn" data-setup-cancel data-i18n="a5s.manage.cancel">ยกเลิก</button>
        <button type="submit" class="a5o-btn open-btn" data-i18n="a5s.review.confirm">ยืนยัน</button>
      </div>
    </form>
  </div>

  @php
    // ครั้งตรวจที่มีอยู่แล้วต่อเดือน (มีงาน/ตัดสินแล้ว = แก้วันที่ได้ ลบไม่ได้) — คำนวณใน @php ก่อน กัน @json นับ [ ] ผิด
    $inspData = $rounds->map(function ($list) use ($stats) {
        return $list->sortBy('seq')->values()->map(function ($r) use ($stats) {
            return [
                'seq' => (int) $r->seq,
                'date' => $r->inspected_on?->format('Y-m-d'),
                'locked' => ((int) ($stats->get($r->id)?->total ?? 0)) > 0 || $r->status !== 'planned',
            ];
        })->all();
    })->all();
  @endphp
  <script>
    'use strict';
    (function () {
      const t = (key, fallback, replacements = null) => {
        const lang = window.__portalLang;
        return lang && lang.text ? lang.text(key, fallback, replacements) : fallback;
      };
      const monthNamesTh = @json($monthNames);
      const inspByMonth = @json($inspData);
      const monthText = month => t(`a5s.month.${month}`, monthNamesTh[month] || String(month));

      document.querySelectorAll('[data-month-label]').forEach(n => { n.textContent = monthText(n.dataset.month); });

      // ---- ยืนยันเปิด/ปิด ----
      document.querySelectorAll('[data-open-one]').forEach(form => form.addEventListener('submit', e => {
        if (!confirm(t('a5s.rounds.openOneConfirm', `เปิด ${form.dataset.label}?\nครั้งที่เปิดอยู่จะถูกปิดอัตโนมัติ`, { label: form.dataset.label }))) e.preventDefault();
      }));
      document.querySelectorAll('[data-delete-round]').forEach(form => form.addEventListener('submit', e => {
        if (!confirm(t('a5s.rounds.deleteConfirm', `ลบครั้งตรวจ ${form.dataset.label}?`, { label: form.dataset.label }))) e.preventDefault();
      }));
      document.querySelectorAll('[data-close-round]').forEach(form => form.addEventListener('submit', e => {
        if (!confirm(t('a5s.rounds.closeConfirm2', `ปิด ${form.dataset.label}?\nปิดแล้วบันทึก/ส่งตรวจไม่ได้จนกว่าจะเปิดอีกครั้ง`, { label: form.dataset.label }))) e.preventDefault();
      }));

      // ---- Modal ตั้งค่าครั้งตรวจ ----
      const modal = document.querySelector('[data-setup-modal]');
      const dateList = modal.querySelector('[data-date-list]');
      const monthInput = modal.querySelector('[data-setup-month]');
      const nameEl = modal.querySelector('[data-setup-name]');

      function renumber() {
        dateList.querySelectorAll('.a5o-date-card').forEach((card, i) => {
          card.querySelector('b').textContent = `${t('a5s.review.attemptNo', 'ครั้งที่')} ${i + 1}`;
        });
      }
      function addDateCard(value = '', locked = false) {
        const card = document.createElement('div');
        card.className = 'a5o-date-card';
        card.innerHTML = `<b></b><input type="date" name="dates[]" value="${value || ''}" required>`
          + (locked
            ? `<span class="a5o-date-lock">${esc(t('a5s.rounds.hasWork', 'มีงานแล้ว'))}</span>`
            : `<button type="button" class="a5o-date-rm" data-rm aria-label="${esc(t('a5s.manage.delete', 'ลบ'))}">×</button>`);
        dateList.appendChild(card);
        renumber();
      }
      function esc(v) { return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

      function openSetup(month) {
        monthInput.value = month;
        nameEl.textContent = monthText(month);
        dateList.innerHTML = '';
        const existing = inspByMonth[month] || [];
        if (existing.length) {
          existing.forEach(r => addDateCard(r.date || '', r.locked));
        } else {
          addDateCard();
        }
        modal.hidden = false; modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      }
      function closeSetup() {
        modal.hidden = true; modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }

      document.querySelectorAll('[data-setup]').forEach(btn => btn.addEventListener('click', () => openSetup(btn.dataset.setup)));
      modal.querySelector('[data-add-date]').addEventListener('click', () => addDateCard());
      dateList.addEventListener('click', e => {
        if (e.target.closest('[data-rm]')) { e.target.closest('.a5o-date-card').remove(); renumber(); }
      });
      modal.querySelectorAll('[data-setup-cancel]').forEach(b => b.addEventListener('click', closeSetup));
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeSetup(); });

      document.addEventListener('insight:languagechange', () => {
        document.querySelectorAll('[data-month-label]').forEach(n => { n.textContent = monthText(n.dataset.month); });
        renumber();
      });
    })();
  </script>
@endsection
