@extends('layouts.portal')

@section('title', 'ตั้งค่า')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="set.kicker">ตั้งค่าระบบ</span>
  <span class="tt-title" data-i18n="set.title">ตั้งค่า</span>
@endsection

@php
  $sections = [
    ['scope' => 'login',     'title' => 'set.login.title', 'note' => 'set.login.note', 'allowed' => $allowed['login']],
    ['scope' => 'email',     'title' => 'set.email.title', 'note' => 'set.email.note', 'allowed' => $allowed['email']],
    ['scope' => 'signature', 'title' => 'set.sig.title',   'note' => 'set.sig.note',   'allowed' => $allowed['signature']],
  ];
@endphp

@section('page-style')
    .set-wrap { width: min(100%, 60rem); margin-top: clamp(.5rem, 2vw, 1rem); }

    .set-brand {
      font-family: "Montserrat", var(--font-body);
      font-size: clamp(1.3rem, 2.4vw, 1.7rem); font-weight: 800; letter-spacing: .04em;
      margin-bottom: 1.4rem;
    }
    .set-brand .brand-accent { color: var(--moss); }

    .set-card {
      border: 1px solid var(--line-light);
      border-radius: 0.4rem;
      background: var(--panel-soft);
      margin-bottom: .9rem;
      overflow: hidden;
    }

    .set-card > summary {
      display: flex; align-items: center; gap: 1rem;
      padding: 1.1rem 1.3rem;
      cursor: pointer; list-style: none;
    }
    .set-card > summary::-webkit-details-marker { display: none; }
    .set-card > summary:hover { background: var(--hover-soft); }
    .set-card[open] > summary { border-bottom: 1px solid var(--line-light); }
    .set-sum-caret { width: 1rem; height: 1rem; flex: 0 0 auto; transition: transform .2s var(--ease-out); fill: none; stroke: var(--muted-light); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .set-card[open] > summary .set-sum-caret { transform: rotate(90deg); }
    .set-sum-main { min-width: 0; }
    .set-sum-main .set-card-kicker { color: var(--moss); font-family: "Montserrat", var(--font-body); font-size: .62rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .set-sum-main h2 { margin-top: .15rem; font-family: var(--font-display); font-size: clamp(1.15rem, 2.2vw, 1.5rem); font-weight: 400; }
    .set-sum-state { margin-left: auto; flex: 0 0 auto; color: var(--muted-light); font-size: .8rem; white-space: nowrap; }

    .set-body { padding: 1.1rem 1.3rem 1.3rem; }
    .set-note { max-width: 46rem; color: var(--muted-light); font-size: .88rem; }

    .set-toolbar { display: flex; gap: .5rem; flex-wrap: wrap; margin: 1rem 0 .9rem; }
    .set-mini {
      padding: .45rem .85rem;
      border: 1px solid var(--line-strong); border-radius: 0.25rem;
      background: transparent; color: var(--light-text); cursor: pointer;
      font-size: .76rem; font-weight: 600;
      transition: border-color .2s var(--ease-out), background-color .2s var(--ease-out);
    }
    .set-mini:hover { border-color: var(--moss); background: var(--hover-soft); }

    .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); gap: .5rem; }
    .pos-item {
      display: flex; align-items: center; gap: .7rem;
      padding: .6rem .8rem;
      border: 1px solid var(--line-light); border-radius: 0.25rem;
      background: var(--panel); cursor: pointer;
      transition: border-color .2s var(--ease-out);
    }
    .pos-item:hover { border-color: var(--moss); }
    .pos-item input { width: 1.15rem; height: 1.15rem; flex: 0 0 auto; accent-color: var(--moss); cursor: pointer; }
    .pos-name { min-width: 0; font-size: .92rem; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pos-count { margin-left: auto; flex: 0 0 auto; color: var(--moss); font-weight: 700; font-size: .78rem; }

    .set-actions { margin-top: 1.3rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .set-save {
      min-height: 2.7rem; padding: 0 1.3rem;
      display: inline-flex; align-items: center; gap: .5rem;
      border: 1px solid var(--moss); border-radius: 0.25rem;
      background: rgb(91 141 239 / 18%); color: var(--light-text); cursor: pointer;
      font-size: .84rem; font-weight: 700; letter-spacing: .04em;
      transition: background-color .2s var(--ease-out);
    }
    .set-save:hover { background: rgb(91 141 239 / 30%); }
    .set-count-label { color: var(--muted-light); font-size: .82rem; }
@endsection

@section('content')
  <div class="set-wrap">
    <div class="set-brand">SUPAVUT <span class="brand-accent">INSIGHT</span></div>

    @if (session('success'))
      <div class="flash success" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif

    @foreach ($sections as $sec)
      <details class="set-card" id="set-{{ $sec['scope'] }}">
        <summary>
          <svg class="set-sum-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
          <span class="set-sum-main">
            <span class="set-card-kicker" data-i18n="{{ $sec['title'] }}">สิทธิ์</span>
            <h2 data-i18n="{{ $sec['title'] }}">สิทธิ์</h2>
          </span>
          <span class="set-sum-state">
            @if ($sec['allowed'] === null)
              <span data-i18n="set.stateAll">อนุญาตทุกตำแหน่ง</span>
            @else
              {{ number_format(count($sec['allowed'])) }} / {{ number_format(count($positions)) }}
            @endif
          </span>
        </summary>

        <div class="set-body">
          <p class="set-note" data-i18n="{{ $sec['note'] }}">—</p>

          <form method="POST" action="{{ route('settings.positions', $sec['scope']) }}" class="pos-form">
            @csrf
            @method('PUT')

            <div class="set-toolbar">
              <button type="button" class="set-mini" data-act="all" data-i18n="set.selectAll">เลือกทั้งหมด</button>
              <button type="button" class="set-mini" data-act="clear" data-i18n="set.clearAll">ล้างทั้งหมด</button>
            </div>

            <div class="pos-grid">
              @foreach ($positions as $p)
                @php $checked = $sec['allowed'] === null ? true : in_array($p['job_code'], $sec['allowed'], true); @endphp
                <label class="pos-item">
                  <input type="checkbox" name="positions[]" value="{{ $p['job_code'] }}" {{ $checked ? 'checked' : '' }}>
                  <span class="pos-name">
                    <span data-val="th">{{ $p['job_th'] ?: $p['job_en'] ?: $p['job_code'] ?: 'ไม่ระบุตำแหน่ง' }}</span>
                    <span data-val="en">{{ $p['job_en'] ?: $p['job_th'] ?: $p['job_code'] ?: 'Unspecified' }}</span>
                  </span>
                  <span class="pos-count">{{ number_format($p['count']) }}</span>
                </label>
              @endforeach
            </div>

            <div class="set-actions">
              <button type="submit" class="set-save">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
                <span data-i18n="set.save">บันทึกการตั้งค่า</span>
              </button>
              <span class="set-count-label"><span data-count>0</span> / {{ count($positions) }} <span data-i18n="set.unitPos">ตำแหน่ง</span></span>
            </div>
          </form>
        </div>
      </details>
    @endforeach

    {{-- ดึงข้อมูลพนักงาน B Plus (manual) --}}
    <style>
      .bplus-actions { display:flex; gap:.7rem; flex-wrap:wrap; margin-top:.4rem; }
      .bplus-actions .set-save[disabled] { opacity:.6; cursor:default; }
      .bplus-recent { display:flex; gap:.6rem; flex-wrap:wrap; margin-top:.8rem; }
      .bp-recent-btn { display:inline-flex; align-items:center; gap:.45rem; padding:.42rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:transparent; color:var(--muted-light); cursor:pointer; font-size:.78rem; font-weight:600; transition:border-color .2s var(--ease-out), color .2s var(--ease-out); }
      .bp-recent-btn:hover { border-color:var(--moss); color:var(--light-text); }
      .bp-recent-btn[hidden] { display:none; }
      .bplus-result { margin-top:.7rem; font-size:.84rem; padding:.6rem .9rem; border-radius: 0.25rem; border:1px solid #d98a80; color:#d98a80; }
      .bplus-result[hidden] { display:none; }

      /* ── Modal รายงานการเปลี่ยนแปลง ── */
      .bp-modal { position:fixed; inset:0; z-index:80; display:flex; align-items:center; justify-content:center; padding:1.2rem; }
      .bp-modal[hidden] { display:none; }
      .bp-overlay { position:absolute; inset:0; background:rgb(10 12 10 / 55%); backdrop-filter:blur(3px); }
      .bp-dialog {
        position:relative; width:min(100%, 52rem); max-height:min(84vh, 46rem);
        display:flex; flex-direction:column;
        border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel);
        box-shadow:0 24px 60px rgb(0 0 0 / 35%); overflow:hidden;
        animation:bpIn .22s var(--ease-out);
      }
      @keyframes bpIn { from { opacity:0; transform:translateY(.6rem) scale(.985); } to { opacity:1; transform:none; } }
      .bp-head { display:flex; align-items:flex-start; gap:1rem; padding:1.15rem 1.3rem .95rem; border-bottom:1px solid var(--line-light); }
      .bp-head-kicker { color:var(--moss); font-family:"Montserrat", var(--font-body); font-size:.6rem; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
      .bp-head h3 { margin-top:.2rem; font-family:var(--font-display); font-size:1.25rem; font-weight:400; }
      .bp-head-time { margin-top:.15rem; color:var(--muted-light); font-size:.76rem; }
      .bp-close { margin-left:auto; flex:0 0 auto; width:2rem; height:2rem; display:grid; place-items:center; border:1px solid var(--line-light); border-radius: 0.25rem; background:transparent; color:var(--muted-light); cursor:pointer; transition:border-color .2s var(--ease-out), color .2s var(--ease-out); }
      .bp-close:hover { border-color:var(--moss); color:var(--light-text); }
      .bp-tabs { display:flex; gap:.55rem; flex-wrap:wrap; padding:.95rem 1.3rem; border-bottom:1px solid var(--line-light); }
      .bp-tab { display:flex; flex-direction:column; align-items:flex-start; gap:.1rem; min-width:7.2rem; padding:.55rem .85rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:var(--light-text); cursor:pointer; text-align:left; transition:border-color .2s var(--ease-out), background-color .2s var(--ease-out); }
      .bp-tab:hover { border-color:var(--moss); }
      .bp-tab.active { border-color:var(--moss); background:var(--hover-soft); box-shadow:inset 0 0 0 1px var(--moss); }
      .bp-tab b { font-size:1.05rem; font-weight:700; line-height:1.1; }
      .bp-tab span { color:var(--muted-light); font-size:.68rem; letter-spacing:.03em; }
      .bp-tab.add b { color:var(--moss); }
      .bp-tab.warning b { color:#d6a84b; }
      .bp-tab.remove b { color:#d98a80; }
      .bp-body { flex:1; min-height:0; display:flex; flex-direction:column; padding:1.05rem 1.3rem 1.2rem; }
      .bp-tbl-wrap { flex:1; min-height:0; overflow:auto; border:1px solid var(--line-light); border-radius: 0.25rem; }
      .bp-tbl { width:100%; border-collapse:collapse; font-size:.82rem; }
      .bp-tbl th { position:sticky; top:0; z-index:1; background:var(--panel-soft); color:var(--muted-light); font-size:.7rem; font-weight:700; letter-spacing:.05em; text-align:left; padding:.5rem .75rem; border-bottom:1px solid var(--line-light); white-space:nowrap; }
      .bp-tbl td { padding:.45rem .75rem; border-bottom:1px solid var(--line-light); vertical-align:top; }
      .bp-tbl tr:last-child td { border-bottom:none; }
      .bp-tbl th.no, .bp-tbl td.no { width:1%; white-space:nowrap; text-align:right; }
      .bp-tbl td.no { color:var(--muted-light); font-variant-numeric:tabular-nums; }
      .bp-tbl td.code { font-weight:700; white-space:nowrap; }
      .bp-tbl td.co { color:var(--muted-light); font-size:.76rem; white-space:nowrap; }
      .bp-empty { margin:auto; display:flex; flex-direction:column; align-items:center; gap:.5rem; padding:2.2rem 1rem; text-align:center; }
      .bp-empty svg { color:var(--moss); }
      .bp-empty b { font-size:.95rem; }
      .bp-empty span { color:var(--muted-light); font-size:.8rem; }
      .bp-foot { display:flex; justify-content:flex-end; padding: .9rem 1.3rem; border-top:1px solid var(--line-light); }
    </style>
    <details class="set-card" id="set-bplus" data-csrf="{{ csrf_token() }}">
      <summary>
        <svg class="set-sum-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
        <span class="set-sum-main">
          <span class="set-card-kicker" data-i18n="set.employeeData">ข้อมูลพนักงาน</span>
          <h2 data-i18n="set.bplus.title">ดึงข้อมูลพนักงาน B Plus</h2>
        </span>
        <span class="set-sum-state" data-i18n="set.bplus.actionsCount">2 ปุ่ม</span>
      </summary>

      <div class="set-body">
        <p class="set-note" data-i18n="set.bplus.note">ดึงข้อมูลล่าสุดจาก Bplus และซิงค์บัญชีล็อกอิน — เสร็จแล้วเปิดรายงานอัตโนมัติ และเปิดดูรายงานรอบล่าสุดย้อนหลังได้ตลอด</p>
        <div class="bplus-actions">
          <button type="button" class="set-save" data-bplus="pull">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
            <span data-i18n="set.bplus.pull">ดึงข้อมูลจาก B Plus</span>
          </button>
          <button type="button" class="set-save" data-bplus="appusers">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span data-i18n="set.bplus.syncAccounts">ซิงค์บัญชีล็อกอิน</span>
          </button>
        </div>

        {{-- เปิดรายงานรอบล่าสุด (ไม่รันใหม่) — โผล่เมื่อเคยรันแล้ว --}}
        <div class="bplus-recent">
          <button type="button" class="bp-recent-btn" data-bp-open="pull" @if (! $reports['pull']) hidden @endif>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <span data-i18n="set.bplus.latestPull">ดูผลการดึงข้อมูลล่าสุด</span>
          </button>
          <button type="button" class="bp-recent-btn" data-bp-open="appusers" @if (! $reports['appusers']) hidden @endif>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <span data-i18n="set.bplus.latestSync">ดูผลการซิงค์ล่าสุด</span>
          </button>
        </div>
        <p class="bplus-result" data-bplus-error hidden></p>
      </div>
    </details>

    {{-- Modal รายงานการเปลี่ยนแปลง (ดึง Bplus / Sync app_users) --}}
    <div class="bp-modal" data-bp-modal hidden>
      <div class="bp-overlay" data-bp-close></div>
      <div class="bp-dialog" role="dialog" aria-modal="true" aria-labelledby="bp-title">
        <div class="bp-head">
          <div>
            <div class="bp-head-kicker" data-i18n="set.bplus.reportKicker">รายงานการเปลี่ยนแปลง</div>
            <h3 id="bp-title" data-bp-title>—</h3>
            <div class="bp-head-time" data-bp-time></div>
          </div>
          <button type="button" class="bp-close" data-bp-close data-i18n-aria="common.close" aria-label="ปิด">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
          </button>
        </div>
        <div class="bp-tabs" data-bp-tabs></div>
        <div class="bp-body" data-bp-body></div>
        <div class="bp-foot">
          <button type="button" class="set-save" data-bp-close data-i18n="common.close">ปิด</button>
        </div>
      </div>
    </div>

    {{-- รูปพนักงาน — ดึงจากโฟลเดอร์ที่ HR วางไว้ แล้วผูกกับตัวพนักงาน --}}
    <style>
      .ph-grid { display:grid; gap:.5rem; grid-template-columns:repeat(auto-fit,minmax(9rem,1fr)); margin:.9rem 0; }
      .ph-stat { border:1px solid var(--line-light); border-radius:0.28rem; padding:.6rem .75rem; background:var(--panel-soft); }
      .ph-stat b { display:block; font-size:1.15rem; line-height:1.2; }
      .ph-stat span { color:var(--muted-light); font-size:.74rem; }
      .ph-src { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; color:var(--muted-light); font-size:.78rem; margin-bottom:.5rem; }
      .ph-src code { font-size:.74rem; word-break:break-all; }
      .ph-dot { width:.5rem; height:.5rem; border-radius:50%; flex:0 0 auto; background:#d98a80; }
      .ph-dot.is-ok { background:var(--moss); }
      .ph-sub { margin-top:.9rem; border:1px solid var(--line-light); border-radius:0.28rem; overflow:hidden; }
      .ph-sub > summary { display:flex; align-items:center; justify-content:space-between; gap:.8rem; padding:.6rem .8rem; cursor:pointer; font-size:.84rem; }
      .ph-sub > summary::-webkit-details-marker { display:none; }
      .ph-sub[open] > summary { border-bottom:1px solid var(--line-light); }
      .ph-scroll { max-height:19rem; overflow:auto; }
      .ph-tbl { width:100%; border-collapse:collapse; font-size:.78rem; }
      .ph-tbl th, .ph-tbl td { padding:.42rem .7rem; text-align:left; border-bottom:1px solid var(--line-light); white-space:nowrap; }
      .ph-tbl th { position:sticky; top:0; background:var(--panel); color:var(--muted-light); font-size:.72rem; font-weight:700; }
      .ph-tbl tr:last-child td { border-bottom:0; }
      .ph-none { padding:1.1rem .8rem; color:var(--muted-light); font-size:.8rem; text-align:center; }
      .ph-result { margin-top:.7rem; font-size:.82rem; padding:.6rem .9rem; border-radius:0.25rem; border:1px solid var(--line-strong); color:var(--light-text); }
      .ph-result[hidden] { display:none; }
      .ph-result.is-bad { border-color:#d98a80; color:#d98a80; }
    </style>
    <details class="set-card" id="set-photos" data-csrf="{{ csrf_token() }}" data-ph-url="{{ route('settings.photos.pull') }}">
      <summary>
        <svg class="set-sum-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
        <span class="set-sum-main">
          <span class="set-card-kicker" data-i18n="set.employeeData">ข้อมูลพนักงาน</span>
          <h2 data-i18n="set.photos.title">รูปพนักงาน</h2>
        </span>
        <span class="set-sum-state">{{ number_format($photos['with_photo']) }} / {{ number_format($photos['active_total']) }}</span>
      </summary>

      <div class="set-body">
        <p class="set-note" data-i18n="set.photos.note">ดึงรูปที่ HR ถ่ายไว้ในโฟลเดอร์กลาง (ตั้งชื่อไฟล์เป็นรหัสพนักงาน) เข้าระบบแล้วผูกกับตัวพนักงาน รูปจะติดตัวไปแม้พนักงานลาออกและบัญชีถูกลบแล้ว</p>

        <div class="ph-src">
          <span class="ph-dot {{ $photos['source_readable'] ? 'is-ok' : '' }}" aria-hidden="true"></span>
          @if ($photos['source_readable'])
            <span data-i18n="set.photos.sourceOk">เข้าโฟลเดอร์ต้นทางได้</span>
          @else
            <span data-i18n="set.photos.sourceFail">เข้าโฟลเดอร์ต้นทางไม่ได้ เครื่องที่รันระบบต้องต่อ share นี้ได้ก่อน</span>
          @endif
          <code>{{ $photos['source_path'] }}</code>
        </div>

        <div class="ph-grid">
          <div class="ph-stat">
            <b data-ph-with>{{ number_format($photos['with_photo']) }}</b>
            <span data-i18n="set.photos.statWith">พนักงานที่มีรูปแล้ว</span>
          </div>
          <div class="ph-stat">
            <b data-ph-missing>{{ number_format($photos['missing_count']) }}</b>
            <span data-i18n="set.photos.statMissing">ยังไม่มีรูป</span>
          </div>
          <div class="ph-stat">
            <b data-ph-orphan>{{ number_format($photos['orphan_count']) }}</b>
            <span data-i18n="set.photos.statOrphan">ไฟล์ที่หาเจ้าของไม่ได้</span>
          </div>
          <div class="ph-stat">
            <b data-ph-lastrun>{{ $photos['last_run']['at'] ?? '—' }}</b>
            <span data-i18n="set.photos.statLastRun">ดึงรูปรอบล่าสุด</span>
          </div>
        </div>

        <div class="bplus-actions">
          <button type="button" class="set-save" data-ph-pull @if (! $photos['source_readable']) disabled @endif>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
            <span data-i18n="set.photos.pull">ดึงรูปใหม่ตอนนี้</span>
          </button>
        </div>
        <p class="ph-result" data-ph-result hidden></p>

        <details class="ph-sub">
          <summary>
            <span data-i18n="set.photos.missingTitle">พนักงานที่ยังไม่มีรูป (ให้ HR ตามถ่าย)</span>
            <span class="set-sum-state">{{ number_format($photos['missing_count']) }}</span>
          </summary>
          @if ($photos['missing_count'])
            <div class="ph-scroll">
              <table class="ph-tbl">
                <thead>
                  <tr>
                    <th data-i18n="set.photos.colCode">รหัส</th>
                    <th data-i18n="set.photos.colName">ชื่อ-สกุล</th>
                    <th data-i18n="set.photos.colPosition">ตำแหน่ง</th>
                    <th data-i18n="set.photos.colDept">แผนก</th>
                    <th data-i18n="set.photos.colHire">วันเริ่มงาน</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($photos['missing'] as $m)
                    <tr>
                      <td>{{ $m['code'] }}</td>
                      <td><span data-loc-th="{{ $m['name_th'] }}" data-loc-en="{{ $m['name_en'] }}">{{ $m['name_th'] }}</span></td>
                      <td><span data-loc-th="{{ $m['position_th'] }}" data-loc-en="{{ $m['position_en'] }}">{{ $m['position_th'] }}</span></td>
                      <td><span data-loc-th="{{ $m['dept_th'] }}" data-loc-en="{{ $m['dept_en'] }}">{{ $m['dept_th'] }}</span></td>
                      <td>{{ $m['hire_date'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="ph-none" data-i18n="set.photos.noMissing">พนักงานที่ทำงานอยู่มีรูปครบทุกคนแล้ว</p>
          @endif
        </details>

        <details class="ph-sub">
          <summary>
            <span data-i18n="set.photos.orphanTitle">ไฟล์รูปที่หาเจ้าของไม่ได้</span>
            <span class="set-sum-state">{{ number_format($photos['orphan_count']) }}</span>
          </summary>
          @if (count($photos['orphans']))
            <div class="ph-scroll">
              <table class="ph-tbl">
                <thead>
                  <tr>
                    <th data-i18n="set.photos.colFile">ชื่อไฟล์</th>
                    <th data-i18n="set.photos.colReason">สาเหตุ</th>
                    <th data-i18n="set.photos.colModified">วันที่ไฟล์</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($photos['orphans'] as $o)
                    <tr>
                      <td>{{ $o['file'] }}</td>
                      <td>{{ $o['reason'] }}</td>
                      <td>{{ $o['modified'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="ph-none" data-i18n="set.photos.noOrphan">ไม่มีไฟล์ที่หาเจ้าของไม่ได้</p>
          @endif
        </details>
      </div>
    </details>

    {{-- ดาวน์โหลดข้อมูลพนักงานทั้งหมด (Excel) --}}
    <details class="set-card" id="set-export">
      <summary>
        <svg class="set-sum-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"></path></svg>
        <span class="set-sum-main">
          <span class="set-card-kicker" data-i18n="set.employeeData">ข้อมูลพนักงาน</span>
          <h2 data-i18n="set.export.title">ดาวน์โหลดข้อมูลพนักงาน (Excel)</h2>
        </span>
        <span class="set-sum-state">Excel</span>
      </summary>

      <div class="set-body">
        <p class="set-note" data-i18n="set.export.note">ดาวน์โหลดพนักงานที่ทำงานอยู่ทั้งหมด — รหัส · ชื่อ-สกุล (ไทย) · ชื่อ-สกุล (อังกฤษ) · ตำแหน่ง · แผนก · เลขที่ประกันสังคม โดยระบบเติมชื่ออังกฤษที่ว่างจากชื่อในระบบให้อัตโนมัติ</p>
        <div class="bplus-actions">
          <a href="{{ route('settings.employees.export') }}" class="set-save" style="text-decoration:none;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
            <span data-i18n="set.export.button">ดาวน์โหลด Excel พนักงาน</span>
          </a>
        </div>
      </div>
    </details>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      document.querySelectorAll('.pos-form').forEach(function (form) {
        var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="positions[]"]'));
        var countEl = form.querySelector('[data-count]');
        function refresh() { if (countEl) countEl.textContent = boxes.filter(function (b) { return b.checked; }).length; }
        boxes.forEach(function (b) { b.addEventListener('change', refresh); });
        form.querySelectorAll('[data-act]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var on = btn.getAttribute('data-act') === 'all';
            boxes.forEach(function (b) { b.checked = on; });
            refresh();
          });
        });
        refresh();
      });

      // เปิด section ที่เพิ่งบันทึก (ตาม fragment #set-xxx)
      if (location.hash) {
        var el = document.querySelector(location.hash);
        if (el && el.tagName === 'DETAILS') el.open = true;
      }

      // ปุ่มดึงข้อมูล Bplus / ซิงค์ app_users + modal รายงานแบบแท็บ (พนักงานใหม่ · รวมทั้งหมด · ลาออก)
      var bplusCard = document.getElementById('set-bplus');
      if (bplusCard) {
        var csrf = bplusCard.getAttribute('data-csrf');
        var modal = document.querySelector('[data-bp-modal]');
        var errLine = bplusCard.querySelector('[data-bplus-error]');
        var urls = {
          pull: "{{ route('settings.bplus.pull') }}",
          appusers: "{{ route('settings.bplus.appusers') }}",
          report: "{{ route('settings.bplus.report', ['kind' => '__KIND__']) }}"
        };
        var currentReport = null;

        function text(key, fallback, replacements) {
          var lang = document.documentElement.getAttribute('data-lang') || 'th';
          var value = (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
          Object.keys(replacements || {}).forEach(function (name) {
            value = String(value).replace(new RegExp('\\{' + name + '\\}', 'g'), replacements[name]);
          });
          return value;
        }

        function reportTitle(kind, fallback) {
          return text(kind === 'pull' ? 'set.bplus.pullTitle' : 'set.bplus.syncTitle', fallback);
        }

        function tabLabel(kind, tab) {
          var keys = kind === 'pull'
            ? { created: 'set.bplus.tabNewHires', all: 'set.bplus.tabAll', working: 'set.bplus.tabWorking', pending_resign: 'set.bplus.tabPendingResign', resigned: 'set.bplus.tabResigned' }
            : { created: 'set.bplus.tabNewAccounts', updated: 'set.bplus.tabUpdatedAccounts', removed: 'set.bplus.tabRemovedAccounts' };
          return text(keys[tab.key], tab.label);
        }

        function extraLabel(kind, tab) {
          var keys = kind === 'pull'
            ? { created: 'set.bplus.extraStartDate', all: 'set.bplus.extraStatus', pending_resign: 'set.bplus.extraEffectiveResignDate', resigned: 'set.bplus.extraResignDate' }
            : { created: 'set.bplus.extraStartDate', updated: 'set.bplus.extraSyncedAt', removed: 'set.bplus.extraSyncedAt' };
          return tab.extra ? text(keys[tab.key], tab.extra) : '';
        }

        function localizedExtra(value) {
          var keys = {
            'ทำงาน': 'set.bplus.statusWorking',
            'ลาออก (รอปิดงวด)': 'set.bplus.statusPendingResign',
            'ลาออก': 'set.bplus.statusResigned'
          };
          return keys[value] ? text(keys[value], value) : value;
        }

        function esc(s) {
          return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
          });
        }

        function fmtTime(iso) {
          if (!iso) return '';
          var dt = new Date(iso);
          if (isNaN(dt)) return '';
          var p = function (n) { return String(n).padStart(2, '0'); };
          return p(dt.getDate()) + '/' + p(dt.getMonth() + 1) + '/' + dt.getFullYear() + ' ' + p(dt.getHours()) + ':' + p(dt.getMinutes());
        }

        // ── ตารางของแท็บที่เลือก ──
        function renderTab(tab) {
          var body = modal.querySelector('[data-bp-body]');
          var rows = tab.rows || [];
          if (rows.length === 0) {
            var label = tabLabel(currentReport.kind, tab);
            body.innerHTML =
              '<div class="bp-empty">' +
                '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m8.5 12.2 2.4 2.4 4.6-5"></path></svg>' +
                '<b>' + esc(text('set.bplus.emptyTitle', 'ไม่มีรายชื่อในหมวด {label}', { label: label })) + '</b>' +
                '<span>' + esc(text('set.bplus.emptyBody', 'รอบล่าสุดไม่มีการเปลี่ยนแปลงในหมวดนี้')) + '</span>' +
              '</div>';
            return;
          }
          var extraTh = tab.extra ? '<th>' + esc(extraLabel(currentReport.kind, tab)) + '</th>' : '';
          var html = rows.map(function (r, i) {
            var extraTd = tab.extra ? '<td>' + esc(localizedExtra(r.extra)) + '</td>' : '';
            return '<tr><td class="no">' + (i + 1).toLocaleString() + '</td><td class="code">' + esc(r.code) + '</td><td>' + esc(r.name) + '</td><td>' + esc(r.position) + '</td><td>' + esc(r.dept) + '</td>' + extraTd + '<td class="co">' + esc(r.company) + '</td></tr>';
          }).join('');
          body.innerHTML =
            '<div class="bp-tbl-wrap"><table class="bp-tbl">' +
              '<thead><tr><th class="no">No.</th><th>' + esc(text('set.bplus.employeeCode', 'รหัสพนักงาน')) + '</th><th>' + esc(text('set.bplus.fullName', 'ชื่อ-สกุล')) + '</th><th>' + esc(text('set.bplus.position', 'ตำแหน่ง')) + '</th><th>' + esc(text('set.bplus.department', 'แผนก')) + '</th>' + extraTh + '<th>' + esc(text('set.bplus.company', 'บริษัท')) + '</th></tr></thead>' +
              '<tbody>' + html + '</tbody>' +
            '</table></div>';
        }

        // ── รายงานทั้งชุด: หัว + แท็บกดสลับได้ ──
        function renderReport(d) {
          currentReport = d;
          modal.querySelector('[data-bp-title]').textContent = reportTitle(d.kind, d.title || text('set.bplus.report', 'รายงาน'));
          modal.querySelector('[data-bp-time]').textContent = d.ran_at ? text('set.bplus.latestRun', 'รอบล่าสุด') + ' ' + fmtTime(d.ran_at) : '';

          var tabsEl = modal.querySelector('[data-bp-tabs]');
          tabsEl.innerHTML = '';
          var tabs = d.tabs || [];
          var activeIdx = tabs.findIndex(function (t) { return (t.rows || []).length > 0; });
          if (activeIdx < 0) activeIdx = 0;

          tabs.forEach(function (tab, i) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'bp-tab ' + (tab.tone || '') + (i === activeIdx ? ' active' : '');
            btn.innerHTML = '<b>' + (tab.rows || []).length.toLocaleString() + '</b><span>' + esc(tabLabel(d.kind, tab)) + '</span>';
            btn.addEventListener('click', function () {
              tabsEl.querySelectorAll('.bp-tab').forEach(function (t) { t.classList.remove('active'); });
              btn.classList.add('active');
              renderTab(tab);
            });
            tabsEl.appendChild(btn);
          });
          if (tabs[activeIdx]) renderTab(tabs[activeIdx]);
        }

        function openModal() { modal.hidden = false; document.body.style.overflow = 'hidden'; }
        function closeModal() { modal.hidden = true; document.body.style.overflow = ''; }
        modal.querySelectorAll('[data-bp-close]').forEach(function (el) { el.addEventListener('click', closeModal); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

        function showError(text) {
          errLine.textContent = text;
          errLine.hidden = false;
        }

        // ปุ่ม `ดูผล...ล่าสุด` — เปิดรายงานรอบล่าสุด (fetch สด: รวมทั้งหมด/ทำงาน/ลาออก/อัปเดตบัญชี เป็นข้อมูลปัจจุบัน)
        bplusCard.querySelectorAll('[data-bp-open]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var kind = btn.getAttribute('data-bp-open');
            btn.disabled = true;
            fetch(urls.report.replace('__KIND__', kind), { headers: { 'Accept': 'application/json' } })
              .then(function (r) { return r.json(); })
              .then(function (d) { if (d.ok) { renderReport(d); openModal(); } })
              .catch(function () { showError(text('set.bplus.connectionError', 'เชื่อมต่อไม่สำเร็จ')); })
              .finally(function () { btn.disabled = false; });
          });
        });

        // ปุ่มรัน 2 ปุ่ม — เสร็จแล้วเปิดรายงานอัตโนมัติ + โชว์ปุ่มเปิดย้อนหลังของ kind นั้น
        bplusCard.querySelectorAll('[data-bplus]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var kind = btn.getAttribute('data-bplus');
            var span = btn.querySelector('span');
            var label = span.textContent;
            var allBtns = bplusCard.querySelectorAll('[data-bplus]');
            allBtns.forEach(function (b) { b.disabled = true; });
            span.textContent = text('set.bplus.running', 'กำลังทำงาน...');
            errLine.hidden = true;
            fetch(urls[kind], { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
              .then(function (r) { return r.json(); })
              .then(function (d) {
                if (d.ok) {
                  var recent = bplusCard.querySelector('[data-bp-open="' + kind + '"]');
                  if (recent) recent.hidden = false;
                  renderReport(d);
                  openModal();
                } else {
                  showError(d.message || text('set.bplus.error', 'ผิดพลาด'));
                }
              })
              .catch(function () { showError(text('set.bplus.connectionError', 'เชื่อมต่อไม่สำเร็จ')); })
              .finally(function () { allBtns.forEach(function (b) { b.disabled = false; }); span.textContent = label; });
          });
        });

        document.addEventListener('insight:languagechange', function () {
          if (currentReport && !modal.hidden) renderReport(currentReport);
        });
      }

      // ── ปุ่มดึงรูปพนักงานจากโฟลเดอร์ของ HR ──────────────────────────────
      // ปกติมี Scheduled Task ดึงให้อยู่แล้ว ปุ่มนี้ไว้ตอนเร่ง (HR เพิ่งวางรูปแล้วอยากเห็นเลย)
      var photoCard = document.getElementById('set-photos');
      if (photoCard) {
        var phBtn = photoCard.querySelector('[data-ph-pull]');
        var phResult = photoCard.querySelector('[data-ph-result]');
        var phUrl = photoCard.getAttribute('data-ph-url');
        var phCsrf = photoCard.getAttribute('data-csrf');

        function phText(key, fallback) {
          var lang = document.documentElement.getAttribute('data-lang') || 'th';
          var map = (window.__portalCopy && window.__portalCopy[lang]) || {};
          return map[key] || fallback;
        }

        function phShow(message, isBad) {
          if (!phResult) return;
          phResult.textContent = message;
          phResult.classList.toggle('is-bad', !!isBad);
          phResult.hidden = false;
        }

        if (phBtn && phUrl) {
          phBtn.addEventListener('click', function () {
            var label = phBtn.querySelector('span');
            var original = label ? label.textContent : '';
            phBtn.disabled = true;
            if (label) label.textContent = phText('set.photos.pulling', 'กำลังดึงรูป...');
            if (phResult) phResult.hidden = true;

            fetch(phUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': phCsrf, 'Accept': 'application/json' } })
              .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, body: d }; }); })
              .then(function (res) {
                var d = res.body || {};
                if (!res.ok || !d.ok) {
                  phShow(d.message || phText('set.photos.failed', 'ดึงรูปไม่สำเร็จ'), true);
                  return;
                }

                var r = d.result || {};
                phShow(
                  phText('set.photos.done', 'ดึงรูปเสร็จแล้ว') + ' — ' +
                  phText('set.photos.resImported', 'ใหม่') + ' ' + (r.imported || 0) + ' · ' +
                  phText('set.photos.resUpdated', 'อัปเดต') + ' ' + (r.updated || 0) + ' · ' +
                  phText('set.photos.resUnchanged', 'เหมือนเดิม') + ' ' + (r.unchanged || 0) + ' · ' +
                  phText('set.photos.resOrphan', 'หาเจ้าของไม่ได้') + ' ' + (r.orphans || 0),
                  false
                );

                // อัปเดตตัวเลขสรุปทันที ส่วนตารางรายชื่อต้องรีเฟรชหน้าถึงจะเปลี่ยน
                var st = d.status || {};
                var set = function (sel, value) {
                  var el = photoCard.querySelector(sel);
                  if (el && value !== undefined && value !== null) el.textContent = Number(value).toLocaleString();
                };
                set('[data-ph-with]', st.with_photo);
                set('[data-ph-missing]', st.missing_count);
                set('[data-ph-orphan]', st.orphan_count);
                var lastRun = photoCard.querySelector('[data-ph-lastrun]');
                if (lastRun && st.last_run && st.last_run.at) lastRun.textContent = st.last_run.at;
              })
              .catch(function () { phShow(phText('set.photos.connectionError', 'เชื่อมต่อไม่สำเร็จ'), true); })
              .finally(function () {
                phBtn.disabled = false;
                if (label) label.textContent = original;
              });
          });
        }
      }
    })();
  </script>
@endsection
