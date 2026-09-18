@extends('layouts.portal')

@section('title', 'ตั้งค่าระบบ Assessment')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="nav.sysSettings">ตั้งค่าระบบ</span>
@endsection

@section('page-style')
    .asm-panel { max-width:64rem; border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); padding:1.4rem 1.6rem; margin-bottom:1.4rem; }
    .asm-panel h2 { margin:0 0 .3rem; font-size:1.05rem; }
    .asm-panel .hint { margin:0 0 1rem; color:var(--muted-light); font-size:.83rem; }

    .asm-chip { display:inline-flex; align-items:center; gap:.55rem; padding:.45rem .7rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); margin:.25rem; }
    .asm-chip img, .asm-chip .av { width:1.7rem; height:1.7rem; border-radius:50%; object-fit:cover; background:var(--line-light); color:var(--muted-light); display:inline-flex; align-items:center; justify-content:center; font-size:.7rem; }
    .asm-chip .av svg { width:62%; height:62%; fill:currentColor; opacity:.72; }
    .asm-chip .meta { display:flex; flex-direction:column; line-height:1.1; }
    .asm-chip .meta small { color:var(--muted-light); font-size:.72rem; }
    .asm-chip button { border:none; background:transparent; color:#d98a80; cursor:pointer; font-size:1rem; padding:0 .2rem; }

    .asm-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.85rem; }
    .asm-btn:hover { border-color:var(--moss); }
    .asm-btn.primary { background:var(--moss); color:#0c0d0c; border-color:var(--moss); }

    .asm-search { position:relative; margin-top:.6rem; max-width:26rem; }
    .asm-search input { width:100%; padding:.55rem .8rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; }
    .asm-results { margin-top:.4rem; border:1px solid var(--line-light); border-radius: 0.25rem; overflow:hidden; display:none; }
    .asm-results.show { display:block; }
    .asm-results button { display:flex; align-items:center; gap:.6rem; width:100%; text-align:left; padding:.5rem .7rem; border:none; background:var(--menu-bg); color:inherit; cursor:pointer; border-bottom:1px solid var(--line-light); }
    .asm-results button:hover { background:var(--panel-soft); }
    .asm-results img, .asm-results .av { width:1.6rem; height:1.6rem; border-radius:50%; object-fit:cover; background:var(--line-light); color:var(--muted-light); display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .asm-results .av svg { width:62%; height:62%; fill:currentColor; opacity:.72; }

    .asm-pos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(15rem,1fr)); gap:.3rem .8rem; max-height:24rem; overflow:auto; padding:.3rem; border:1px solid var(--line-light); border-radius: 0.25rem; }
    .asm-pos-grid label { display:flex; align-items:center; gap:.5rem; padding:.3rem .2rem; font-size:.85rem; cursor:pointer; }
    .asm-pos-grid small { color:var(--muted-light); }
    .asm-toolbar { display:flex; gap:.6rem; align-items:center; margin:.8rem 0; flex-wrap:wrap; }
    .asm-note-inline { color:var(--muted-light); font-size:.78rem; }
@endsection

@section('content')
  {{-- ===== สมาชิก HR ===== --}}
  <section class="asm-panel" data-members-panel>
    <h2 data-i18n="assessment.settings.hrTitle">สิทธิ์ผู้ใช้งาน (HR)</h2>
    <p class="hint" data-i18n="assessment.settings.hrHint">พนักงานที่ถูกเพิ่มจะเข้าระบบ Assessment และจัดการคะแนนได้ (admin เข้าได้อยู่แล้ว)</p>

    <div data-members-list>
      @forelse ($members as $m)
        <span class="asm-chip" data-member-id="{{ $m['id'] }}">
          @if ($m['avatar'])<img src="{{ $m['avatar'] }}" alt="">@else<span class="av">@include('assessment.partials.default-avatar')</span>@endif
          <span class="meta">{{ $m['name'] }}<small>{{ $m['code'] }} · {{ strtoupper($m['role']) }}</small></span>
          <button type="button" title="ลบ" data-i18n-title="assessment.settings.removeTitle" data-remove-member>&times;</button>
        </span>
      @empty
        <span class="asm-note-inline" data-empty-members data-i18n="assessment.settings.noMembers">ยังไม่มีสมาชิก</span>
      @endforelse
    </div>

    <div class="asm-search" data-user-search>
      <input type="text" placeholder="ค้นหาพนักงาน (รหัส / ชื่อ) เพื่อเพิ่มเป็น HR" data-i18n-placeholder="assessment.settings.searchPlaceholder" data-search-input>
      <div class="asm-results" data-search-results></div>
    </div>
  </section>

  {{-- ===== ตำแหน่งที่เข้าระบบได้ ===== --}}
  <section class="asm-panel">
    <h2 data-i18n="assessment.settings.positionsTitle">ตำแหน่งที่เข้าใช้ระบบได้</h2>
    <p class="hint" data-i18n="assessment.settings.positionsHint">ติ๊กตำแหน่ง (ดึงจากข้อมูลพนักงาน Bplus) ที่อนุญาตให้เข้าระบบ — ถ้ายังไม่เคยบันทึก = อนุญาตทุกตำแหน่ง</p>

    <div class="asm-toolbar">
      <button type="button" class="asm-btn" data-pos-all data-i18n="assessment.settings.selectAll">เลือกทั้งหมด</button>
      <button type="button" class="asm-btn" data-pos-none data-i18n="assessment.settings.clear">ไม่เลือก</button>
      <button type="button" class="asm-btn primary" data-pos-save data-i18n="assessment.settings.save">บันทึกตำแหน่ง</button>
      <span class="asm-note-inline" data-pos-status></span>
    </div>

    @php $allowedArr = is_array($allowed) ? $allowed : null; @endphp
    <div class="asm-pos-grid">
      @foreach ($positions as $p)
        <label>
          <input type="checkbox" value="{{ $p['code'] }}" data-pos-check
                 {{ $allowedArr === null || in_array($p['code'], $allowedArr, true) ? 'checked' : '' }}>
          <span>{{ $p['name'] }} <small>({{ $p['count'] }})</small></span>
        </label>
      @endforeach
    </div>
  </section>

  <script>
    'use strict';
    (function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const J = (url, opt = {}) => fetch(url, Object.assign({ headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }, opt)).then(r => r.json());
      const copyText = (key, fallback) => window.__portalLang?.text ? window.__portalLang.text(key, fallback) : fallback;

      // ---- members ----
      const list = document.querySelector('[data-members-list]');
      const searchInput = document.querySelector('[data-search-input]');
      const results = document.querySelector('[data-search-results]');
      const defaultAvatarSvg = `<svg viewBox="0 0 48 48" focusable="false" aria-hidden="true" shape-rendering="geometricPrecision"><path fill="currentColor" stroke="none" d="M24 5.5c-5.2 0-9.4 4.2-9.4 9.4 0 3.6 2 6.7 5 8.3-6.9 1.7-12.2 7.7-13.2 15.4-.2 1.5 1 2.9 2.5 2.9h30.2c1.5 0 2.7-1.4 2.5-2.9-1-7.7-6.3-13.7-13.2-15.4 3-1.6 5-4.7 5-8.3 0-5.2-4.2-9.4-9.4-9.4Z"></path></svg>`;
      const defaultAvatar = () => `<span class="av">${defaultAvatarSvg}</span>`;

      function chip(m) {
        const av = m.avatar ? `<img src="${m.avatar}" alt="">` : defaultAvatar();
        const el = document.createElement('span');
        el.className = 'asm-chip';
        el.dataset.memberId = m.id;
        el.innerHTML = `${av}<span class="meta">${m.name}<small>${m.code} · ${(m.role||'hr').toUpperCase()}</small></span><button type="button" title="${copyText('assessment.settings.removeTitle', 'ลบ')}" data-remove-member>&times;</button>`;
        return el;
      }

      list.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove-member]');
        if (!btn) return;
        const chipEl = btn.closest('[data-member-id]');
        J(`{{ url('assessment/settings/members') }}/${chipEl.dataset.memberId}`, { method: 'DELETE' })
          .then(() => chipEl.remove());
      });

      let searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        searchTimer = setTimeout(() => {
          J(`{{ route('assessment.settings.users') }}?q=${encodeURIComponent(q)}`).then(d => {
            results.innerHTML = '';
            (d.users || []).forEach(u => {
              const av = u.avatar ? `<img src="${u.avatar}" alt="">` : defaultAvatar();
              const b = document.createElement('button');
              b.type = 'button';
              b.innerHTML = `${av}<span>${u.name} <small style="color:var(--muted-light)">${u.code} · ${u.position||''}</small></span>`;
              b.onclick = () => {
                J(`{{ route('assessment.settings.members.add') }}`, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                  body: JSON.stringify({ app_user_id: u.app_user_id }),
                }).then(res => {
                  if (res.ok) {
                    const ex = list.querySelector(`[data-member-id="${res.member.id}"]`);
                    if (!ex) list.appendChild(chip(res.member));
                    const empty = list.querySelector('[data-empty-members]'); if (empty) empty.remove();
                  }
                  results.classList.remove('show'); searchInput.value = '';
                });
              };
              results.appendChild(b);
            });
            results.classList.add('show');
          });
        }, 250);
      });
      document.addEventListener('click', e => { if (!e.target.closest('[data-user-search]')) results.classList.remove('show'); });

      // ---- positions ----
      const status = document.querySelector('[data-pos-status]');
      const checks = () => Array.from(document.querySelectorAll('[data-pos-check]'));
      document.querySelector('[data-pos-all]').onclick = () => checks().forEach(c => c.checked = true);
      document.querySelector('[data-pos-none]').onclick = () => checks().forEach(c => c.checked = false);
      document.querySelector('[data-pos-save]').onclick = function () {
        const codes = checks().filter(c => c.checked).map(c => c.value);
        J(`{{ route('assessment.settings.positions') }}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({ job_codes: codes }),
        }).then(res => {
          status.textContent = res.ok
            ? copyText('assessment.settings.saved', 'บันทึกแล้ว ✓')
            : copyText('assessment.settings.error', 'ผิดพลาด');
          setTimeout(() => status.textContent = '', 2500);
        });
      };
    })();
  </script>
@endsection
