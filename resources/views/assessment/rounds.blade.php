@extends('layouts.portal')

@section('title', 'เปิดรอบ')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.rounds.title">เปิดรอบ</span>
@endsection

@php
  $isSelf = $tab === \App\Models\Assessment\AsmRound::TYPE_SELF;
  $tabLabel = $isSelf ? 'ประเมินตัวเอง' : 'ประเมินพนักงาน';
@endphp

@section('page-style')
    .rd-wrap { width: min(100%, 64rem); }
    .rd-tabs { display:flex; gap:.45rem; flex-wrap:wrap; margin-bottom:1.1rem; }
    .rd-tab { padding:.5rem 1.1rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; text-decoration:none; font-size:.86rem; font-weight:600; transition:border-color .15s, background-color .15s; }
    .rd-tab:hover { border-color:var(--moss); }
    .rd-tab.is-active { background:var(--moss); border-color:var(--moss); color:#fff; }
    .rd-card { border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--panel-soft); padding:1.1rem 1.3rem; margin-bottom:1rem; }
    .rd-card h2 { font-size:1.05rem; margin-bottom:.3rem; }
    .rd-hint { color:var(--muted-light); font-size:.82rem; margin-bottom:.8rem; }
    .rd-form { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
    .rd-form input { padding:.45rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; }
    .rd-form input[name="year"] { width:6rem; }
    .rd-btn { padding:.5rem 1rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; cursor:pointer; font-weight:600; font-size:.84rem; }
    .rd-btn:focus, .rd-btn:focus-visible { outline:none; }
    .rd-btn.ghost { background:transparent; color:inherit; border-color:var(--line-strong); }
    .rd-btn.ghost:hover { border-color:var(--moss); }
    .rd-btn.danger { border-color:#d98a80; background:transparent; color:#d98a80; }
    .rd-btn.danger:hover { background:rgb(217 138 128 / 12%); }
    .rd-btn.sm { padding:.3rem .7rem; font-size:.78rem; }
    table.rd-tbl { width:100%; border-collapse:collapse; font-size:.84rem; }
    .rd-tbl th { text-align:left; padding:.5rem .7rem; color:var(--muted-light); border-bottom:1px solid var(--line-light); font-weight:700; white-space:nowrap; }
    .rd-tbl td { padding:.45rem .7rem; border-bottom:1px solid var(--line-light); vertical-align:middle; }
    .rd-tbl tr:last-child td { border-bottom:none; }
    .rd-open { color:var(--moss); font-weight:700; }
    .rd-closed { color:var(--muted-light); }
    .rd-actions { display:flex; gap:.4rem; flex-wrap:wrap; justify-content:flex-end; }

    /* modal ยืนยันลบ */
    .rd-modal { position:fixed; inset:0; z-index:90; display:flex; align-items:center; justify-content:center; padding:1.2rem; }
    .rd-modal[hidden] { display:none; }
    .rd-overlay { position:absolute; inset:0; background:rgb(10 12 10 / 55%); }
    .rd-dialog { position:relative; width:min(100%, 30rem); border:1px solid var(--line-light); border-radius: 0.36rem; background:var(--panel); padding:1.2rem 1.3rem; animation:rdPop .2s var(--ease-out, ease); }
    @keyframes rdPop { from { opacity:0; transform:translateY(.5rem) scale(.98); } to { opacity:1; transform:none; } }
    .rd-dialog h3 { font-size:1.05rem; margin-bottom:.5rem; }
    .rd-dialog p { color:var(--muted-light); font-size:.86rem; line-height:1.6; margin-bottom:1rem; }
    .rd-dialog b { color:var(--light-text, inherit); }
    .rd-dialog .rd-foot { display:flex; justify-content:flex-end; gap:.5rem; }

    /* modal สำเร็จ (เครื่องหมายถูกสีเขียว) */
    .rd-ok-dialog { text-align:center; }
    /* แจ้งเตือนสำเร็จ: วงกลมเขียว + ขีดถูกวาดเส้น (ชุดเดียวกับ .pt-ok-icon ใน portal — Manager 2026-08-27) */
    .rd-ok-icon { width:3.6rem; height:3.6rem; margin:0 auto .8rem; border-radius:50%; background:color-mix(in srgb, var(--success) 16%, transparent); display:grid; place-items:center; animation:rdOk .35s var(--ease-out, ease) .05s both; }
    .rd-ok-icon svg { width:2rem; height:2rem; stroke:var(--success); stroke-dasharray:26; stroke-dashoffset:26; animation:rdOkDraw .5s cubic-bezier(.65,0,.35,1) .2s forwards; }
    @keyframes rdOk { from { transform:scale(.4); opacity:0; } to { transform:scale(1); opacity:1; } }
    @keyframes rdOkDraw { to { stroke-dashoffset:0; } }
    @media (prefers-reduced-motion: reduce) {
      .rd-ok-icon { animation:none; }
      .rd-ok-icon svg { animation:none; stroke-dashoffset:0; }
    }
    .rd-ok-dialog h3 { font-size:1.15rem; }
    .rd-ok-dialog p { margin-bottom:1.2rem; }
    .rd-ok-dialog .rd-foot { justify-content:center; }
    /* modal แจ้งเตือน (ยังไม่เปิดรอบ) — โทนเหลืองอำพัน */
    .rd-warn-icon { width:3.6rem; height:3.6rem; margin:0 auto .8rem; border-radius:50%; background:rgb(217 179 128 / 18%); display:grid; place-items:center; animation:rdOk .35s var(--ease-out, ease) .05s both; }
    .rd-warn-icon svg { width:2rem; height:2rem; stroke:#d9a35a; }
@endsection

@section('content')
  <div class="rd-wrap">
    {{-- แท็บชนิดรอบ: ประเมินพนักงาน / ประเมินตัวเอง --}}
    <div class="rd-tabs">
      <a href="{{ route('assessment.rounds.index', ['type' => 'employee']) }}" class="rd-tab nav-go {{ ! $isSelf ? 'is-active' : '' }}" data-i18n="assessment.rounds.employeeTab">ประเมินพนักงาน</a>
      <a href="{{ route('assessment.rounds.index', ['type' => 'self']) }}" class="rd-tab nav-go {{ $isSelf ? 'is-active' : '' }}" data-i18n="assessment.rounds.selfTab">ประเมินตัวเอง</a>
    </div>

    @if (session('error'))
      <div class="flash error" style="margin-bottom:1rem">{{ session('error') }}</div>
    @endif

    <div class="rd-card">
      <h2><span data-i18n="assessment.rounds.openNewTitle">เปิดรอบใหม่</span> — <span data-i18n="{{ $isSelf ? 'assessment.rounds.selfTab' : 'assessment.rounds.employeeTab' }}">{{ $tabLabel }}</span></h2>
      <p class="rd-hint">
        @if ($isSelf)
          <span data-i18n="assessment.rounds.selfHint">เปิดรอบเพื่อให้พนักงานเข้าประเมินตัวเองได้ · เปิดรอบใหม่ = ปิดรอบเดิมอัตโนมัติ</span>
        @else
          <span data-i18n="assessment.rounds.employeeHint">ต้องมีรอบประเมินพนักงานเปิดก่อนจึงจัดการการประเมินพนักงานได้ · เปิดรอบใหม่ = ปิดรอบเดิม และคะแนน ผู้ประเมิน และระดับเริ่มชุดใหม่ (รอบเดิมดูย้อนหลังได้)</span>
        @endif
      </p>
      <form method="POST" action="{{ route('assessment.rounds.store') }}" class="rd-form" data-confirm-open-new>
        @csrf
        <input type="hidden" name="type" value="{{ $tab }}">
        <input type="text" name="name" placeholder="ชื่อรอบ เช่น {{ $tabLabel }} {{ now()->year }} รอบ 1" data-i18n-placeholder="assessment.rounds.namePlaceholder" required style="min-width:20rem">
        <input type="number" name="year" value="{{ now()->year }}" min="2000" max="2200" required>
        <button type="submit" class="rd-btn" data-i18n="assessment.rounds.openNewButton">+ เปิดรอบใหม่</button>
      </form>
    </div>

    <div class="rd-card">
      <h2><span data-i18n="assessment.rounds.allTitle">รอบทั้งหมด</span> — <span data-i18n="{{ $isSelf ? 'assessment.rounds.selfTab' : 'assessment.rounds.employeeTab' }}">{{ $tabLabel }}</span></h2>
      <p class="rd-hint" data-i18n="assessment.rounds.listHint">กดเปิดรอบเพื่อสลับมาทำงานกับรอบและปีนั้น · กดลบเพื่อลบรอบพร้อมข้อมูลของรอบนั้น</p>
      <table class="rd-tbl">
        <tr><th data-i18n="assessment.common.year">ปี</th><th data-i18n="assessment.common.roundName">ชื่อรอบ</th><th data-i18n="assessment.evaluate.status">สถานะ</th><th data-i18n="assessment.common.openedAt">เปิดเมื่อ</th><th data-i18n="assessment.common.by">โดย</th><th></th></tr>
        @forelse ($rounds as $r)
          <tr>
            <td>{{ $r->year }}</td>
            <td><b>{{ $r->name }}</b></td>
            <td>
              @if ($r->isOpen())
                <span class="rd-open" data-i18n="assessment.rounds.openState">● เปิดอยู่</span>
              @else
                <span class="rd-closed" data-i18n="assessment.rounds.closedState">ปิดแล้ว</span>
              @endif
            </td>
            <td>{{ $r->opened_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>{{ $r->opened_by_name ?? '—' }}</td>
            <td>
              <div class="rd-actions">
                @unless ($isSelf)
                  <a href="{{ route('assessment.results.index', ['round' => $r->id]) }}" class="rd-btn ghost sm nav-go" style="text-decoration:none" data-i18n="assessment.rounds.viewResults">ดูผลลัพธ์</a>
                @endunless
                @if ($r->isOpen())
                  <form method="POST" action="{{ route('assessment.rounds.close', $r) }}" style="display:inline" data-confirm-close data-round-name="{{ $r->name }}">
                    @csrf @method('PUT')
                    <button type="submit" class="rd-btn ghost sm" data-i18n="assessment.rounds.close">ปิดรอบ</button>
                  </form>
                @else
                  <form method="POST" action="{{ route('assessment.rounds.reopen', $r) }}" style="display:inline" data-confirm-reopen data-round-name="{{ $r->name }}" data-round-year="{{ $r->year }}">
                    @csrf @method('PUT')
                    <button type="submit" class="rd-btn sm" data-i18n="assessment.rounds.open">เปิดรอบ</button>
                  </form>
                @endif
                <button type="button" class="rd-btn danger sm"
                        data-del-round="{{ route('assessment.rounds.destroy', $r) }}"
                        data-del-name="{{ $r->name }}" data-del-year="{{ $r->year }}" data-i18n="assessment.common.delete">ลบ</button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" style="color:var(--muted-light)" data-i18n="assessment.rounds.empty">ยังไม่มีรอบ — เปิดรอบแรกด้านบน</td></tr>
        @endforelse
      </table>
    </div>
  </div>

  {{-- modal สำเร็จ (เปิดรอบ / สลับรอบ / ลบ) --}}
  @if (session('round_ok'))
    <div class="rd-modal" data-ok-modal>
      <div class="rd-overlay" data-ok-close></div>
      <div class="rd-dialog rd-ok-dialog">
        <div class="rd-ok-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path></svg>
        </div>
        <h3 data-i18n="assessment.common.success">สำเร็จ</h3>
        <p>{{ session('round_ok') }}</p>
        <div class="rd-foot">
          <button type="button" class="rd-btn" data-ok-close data-i18n="assessment.common.ok">ตกลง</button>
        </div>
      </div>
    </div>
  @endif

  {{-- modal แจ้งเตือน (ยังไม่เปิดรอบ) — รูปแบบเดียวกับ modal สำเร็จ --}}
  @if (session('round_warn'))
    <div class="rd-modal" data-ok-modal>
      <div class="rd-overlay" data-ok-close></div>
      <div class="rd-dialog rd-ok-dialog">
        <div class="rd-warn-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
        </div>
        <h3 data-i18n="assessment.common.notice">แจ้งเตือน</h3>
        <p>{{ session('round_warn') }}</p>
        <div class="rd-foot">
          <button type="button" class="rd-btn" data-ok-close data-i18n="assessment.common.ok">ตกลง</button>
        </div>
      </div>
    </div>
  @endif

  {{-- modal ยืนยันลบรอบ --}}
  <div class="rd-modal" data-rd-modal hidden>
    <div class="rd-overlay" data-rd-close></div>
    <div class="rd-dialog">
      <h3 data-i18n="assessment.rounds.deleteTitle">ลบรอบนี้?</h3>
      <p><span data-i18n="assessment.rounds.deleteBefore">จะลบรอบ</span> <b data-rd-name></b> <span data-i18n="assessment.rounds.deleteAfter">และข้อมูลทั้งหมดของรอบนี้ (คะแนน · ผู้ประเมิน · ระดับ) อย่างถาวร — กู้คืนไม่ได้</span></p>
      <div class="rd-foot">
        <button type="button" class="rd-btn ghost" data-rd-close data-i18n="assessment.common.cancel">ยกเลิก</button>
        <form method="POST" data-rd-form>
          @csrf @method('DELETE')
          <button type="submit" class="rd-btn danger" data-i18n="assessment.rounds.deletePermanent">ลบถาวร</button>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      const t = (key, fallback, replacements = null) => window.__portalLang?.text
        ? window.__portalLang.text(key, fallback, replacements)
        : fallback;
      document.querySelector('[data-confirm-open-new]')?.addEventListener('submit', event => {
        if (!window.confirm(t('assessment.rounds.confirmOpenNew', 'เปิดรอบใหม่? รอบที่เปิดอยู่จะถูกปิดและเริ่มชุดข้อมูลใหม่'))) event.preventDefault();
      });
      document.querySelectorAll('[data-confirm-close]').forEach(roundForm => roundForm.addEventListener('submit', event => {
        if (!window.confirm(t('assessment.rounds.confirmClose', 'ปิดรอบ “{name}”?', { name: roundForm.dataset.roundName }))) event.preventDefault();
      }));
      document.querySelectorAll('[data-confirm-reopen]').forEach(roundForm => roundForm.addEventListener('submit', event => {
        if (!window.confirm(t('assessment.rounds.confirmReopen', 'เปิดหรือสลับมาที่รอบ “{name}” (ปี {year})?', { name: roundForm.dataset.roundName, year: roundForm.dataset.roundYear }))) event.preventDefault();
      }));
      const modal = document.querySelector('[data-rd-modal]');
      if (!modal) return;
      const form = modal.querySelector('[data-rd-form]');
      const nameEl = modal.querySelector('[data-rd-name]');
      const open = () => { modal.hidden = false; document.body.style.overflow = 'hidden'; };
      const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
      modal.querySelectorAll('[data-rd-close]').forEach(el => el.addEventListener('click', close));
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) close(); });
      document.querySelectorAll('[data-del-round]').forEach(btn => {
        btn.addEventListener('click', function () {
          form.action = this.dataset.delRound;
          nameEl.textContent = `${this.dataset.delName} (${t('assessment.common.year', 'ปี')} ${this.dataset.delYear})`;
          open();
        });
      });

      // modal สำเร็จ / แจ้งเตือน — เปิดอัตโนมัติเมื่อมี session
      const okModal = document.querySelector('[data-ok-modal]');
      if (okModal) {
        const closeOk = () => { okModal.remove(); document.body.style.overflow = ''; };
        document.body.style.overflow = 'hidden';
        okModal.querySelectorAll('[data-ok-close]').forEach(el => el.addEventListener('click', closeOk));
        document.addEventListener('keydown', e => { if (e.key === 'Escape' || e.key === 'Enter') closeOk(); }, { once: true });
      }
    })();
  </script>
@endsection
