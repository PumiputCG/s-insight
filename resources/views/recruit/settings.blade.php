@extends('layouts.portal')

@section('title', 'ตั้งค่าระบบ Recruit')

@section('topbar-title')
  <span class="tt-kicker">RECRUIT SYSTEM</span>
  <span class="tt-title" data-i18n="nav.sysSettings">ตั้งค่าระบบ</span>
@endsection

@php
  $roleLabels = ['dcc' => 'DCC', 'manager' => 'Manager', 'general_manager' => 'General Manager', 'hr_manager' => 'HR Manager', 'recruit' => 'Recruit'];
  $roleLabelKeys = ['dcc' => 'set.recruit.role.dcc', 'manager' => 'set.recruit.role.manager', 'general_manager' => 'set.recruit.role.general_manager', 'hr_manager' => 'set.recruit.role.hr_manager', 'recruit' => 'set.recruit.role.recruit'];
@endphp

@section('page-style')
    .set-wrap { width: min(100%, 74rem); margin-top: clamp(.5rem, 2vw, 1rem); }
    .set-section-label { color: var(--moss); font-family: "Montserrat", var(--font-body); font-size: .66rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; margin-bottom: .6rem; }
    .set-note { max-width: 38rem; color: var(--muted-light); font-size: .82rem; line-height: 1.55; margin-bottom: 1.2rem; }

    /* ── เส้นทางอนุมัติ (route) — มีได้หลายเส้นทาง ── */
    .rc-route { border: 1px solid var(--line-light); border-radius: 0.4rem; background: var(--panel-soft); padding: 1rem 1.05rem 1.15rem; margin-bottom: 1.1rem; }
    .rc-route-head { display: flex; align-items: center; gap: .65rem; margin-bottom: .95rem; }
    .rc-route-no { width: 1.9rem; height: 1.9rem; flex: 0 0 auto; border-radius: 50%; display: grid; place-items: center; background: var(--moss); color: #fff; font-weight: 800; font-size: .82rem; }
    .rc-route-name { flex: 1 1 auto; min-width: 0; min-height: 2.3rem; padding: .4rem .7rem; border: 1px solid transparent; border-radius: 0.25rem; background: transparent; color: var(--light-text); font-family: var(--font-display); font-size: 1.05rem; font-weight: 400; }
    .rc-route-name:hover { border-color: var(--line-strong); }
    .rc-route-name:focus { outline: 0; border-color: var(--moss); background: var(--panel); }
    .rc-route-del { flex: 0 0 auto; min-height: 2rem; padding: .3rem .72rem; border: 1px solid var(--line-strong); border-radius: 0.25rem; background: transparent; color: var(--muted-light); cursor: pointer; font-size: .74rem; transition: border-color .2s var(--ease-out), color .2s var(--ease-out); }
    .rc-route-del:hover { border-color: var(--danger); color: var(--danger); }

    /* ── การ์ด = ขั้นในเส้นทาง (เรียงลำดับ) ── */
    .rc-steps { display: grid; grid-template-columns: repeat(auto-fill, minmax(13.9rem, 1fr)); gap: .75rem; align-items: start; }
    .rc-step-card { min-height: 12rem; border: 1px solid var(--line-light); border-radius: 0.3rem; background: var(--panel); padding: .78rem; --cursor-current: var(--cursor-action); cursor: var(--cursor-action); transition: border-color .2s var(--ease-out), background .2s var(--ease-out), opacity .2s var(--ease-out), transform .2s var(--ease-out); }
    .rc-step-card:hover { border-color: var(--line-strong); background: var(--hover-soft); }
    .rc-step-card.is-dragging { opacity: .55; transform: scale(.985); border-color: var(--moss); }
    .rc-step-card.is-drop-target { border-color: var(--moss); box-shadow: inset 0 0 0 1px rgb(91 141 239 / 42%); }
    .rc-card-top { display: grid; grid-template-columns: 1.65rem minmax(0, 1fr) 1.85rem; align-items: center; gap: .5rem; margin-bottom: .65rem; }
    .rc-pos { width: 1.65rem; height: 1.65rem; flex: 0 0 auto; border-radius: 50%; display: grid; place-items: center; background: rgb(91 141 239 / 20%); color: var(--moss); font-weight: 800; font-size: .76rem; }
    .rc-role-select { width: 100%; min-width: 0; min-height: 2.15rem; padding: .35rem .55rem; border: 1px solid var(--line-strong); border-radius: 0.25rem; background: var(--panel); color: var(--light-text); font-weight: 600; font-size: .78rem; }
    .rc-role-select:focus { outline: 0; border-color: var(--moss); }
    .rc-card-tools { display: inline-flex; justify-content: flex-end; gap: .3rem; flex: 0 0 auto; }
    .rc-card-tools button { width: 1.85rem; height: 1.85rem; border: 1px solid var(--line-strong); border-radius: 0.25rem; background: transparent; color: var(--light-text); cursor: pointer; }
    .rc-card-tools button:hover:not(:disabled) { border-color: var(--moss); background: var(--hover-soft); }
    .rc-card-tools button:disabled { opacity: .3; cursor: not-allowed; }
    .rc-card-tools .rc-del:hover { border-color: var(--danger); color: var(--danger); }

    .rc-members { display: flex; flex-direction: column; gap: .42rem; }
    .rc-chip { display: grid; grid-template-columns: 2.15rem minmax(0, 1fr); align-items: start; gap: .45rem .52rem; padding: .48rem .5rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--panel-soft); }
    .rc-av { width: 2.15rem; height: 2.15rem; border-radius: 50%; overflow: hidden; flex: 0 0 auto; display: grid; place-items: center; background: var(--panel-soft); border: 1px solid var(--line-strong); color: var(--muted-light); }
    .rc-av img { width: 100%; height: 100%; object-fit: cover; }
    .rc-av.avatar-placeholder svg { width: 1.45rem; height: 1.45rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .rc-info { min-width: 0; flex: 1; }
    .rc-nm { display: block; font-size: .79rem; font-weight: 600; line-height: 1.32; overflow-wrap: anywhere; word-break: break-word; }
    .rc-ps { display: block; margin-top: .08rem; font-size: .68rem; line-height: 1.35; color: var(--muted-light); overflow-wrap: anywhere; word-break: break-word; }
    .rc-chip-tools { grid-column: 1 / -1; display: inline-flex; align-items: center; justify-content: flex-end; gap: .28rem; flex-wrap: wrap; min-width: 0; }
    .rc-dept-btn { display: none; align-items: center; gap: .25rem; max-width: 100%; min-height: 1.75rem; padding: .26rem .48rem; border: 1px solid var(--line-strong); border-radius: 0.25rem; background: transparent; color: var(--muted-light); cursor: pointer; font-size: .68rem; line-height: 1.2; white-space: nowrap; }
    .rc-dept-btn:hover { border-color: var(--moss); color: var(--light-text); }
    .rc-step-card[data-role="dcc"] .rc-dept-btn { display: inline-flex; }
    .rc-x { width: 1.75rem; height: 1.75rem; border: 0; border-radius: 0.25rem; background: transparent; color: var(--muted-light); cursor: pointer; font-size: 1rem; line-height: 1; }
    .rc-x:hover { background: var(--hover-soft); color: var(--danger); }

    .rc-add { margin-top: .55rem; width: 100%; padding: .42rem .75rem; border: 1px dashed var(--line-strong); border-radius: 0.25rem; background: transparent; color: var(--muted-light); cursor: pointer; font-size: .76rem; transition: border-color .2s var(--ease-out), color .2s var(--ease-out); }
    .rc-add:hover { border-color: var(--moss); color: var(--light-text); }
    .rc-add-step { margin-top: 1rem; width: 100%; padding: .7rem; border: 1px dashed var(--line-strong); border-radius: 0.28rem; background: transparent; color: var(--muted-light); cursor: pointer; font-size: .85rem; font-weight: 600; transition: border-color .2s var(--ease-out), color .2s var(--ease-out); }
    .rc-add-step:hover { border-color: var(--moss); color: var(--light-text); }
    .rc-empty-steps { color: var(--muted-light); font-size: .88rem; padding: 1.2rem 0; text-align: center; }

    .rc-add-route { width: 100%; padding: .9rem; border: 1px dashed var(--moss); border-radius: 0.34rem; background: rgb(91 141 239 / 8%); color: var(--moss); cursor: pointer; font-size: .9rem; font-weight: 800; letter-spacing: .02em; transition: background .2s var(--ease-out), color .2s var(--ease-out); }
    .rc-add-route:hover { background: rgb(91 141 239 / 18%); color: var(--light-text); }
    .rc-empty-routes { color: var(--muted-light); font-size: .9rem; padding: 1.5rem 0; text-align: center; }

    /* ── modals ── */
    .rc-modal { position: fixed; inset: 0; z-index: 95; display: grid; place-items: center; padding: 1.25rem; background: var(--overlay-bg); backdrop-filter: blur(4px); opacity: 0; visibility: hidden; transition: opacity .2s var(--ease-out), visibility .2s; }
    .rc-modal.is-open { opacity: 1; visibility: visible; }
    .rc-dialog { width: min(100%, 30rem); max-height: min(80vh, 40rem); display: flex; flex-direction: column; border: 1px solid var(--line-light); border-radius: 0.4rem; background: var(--panel); }
    .rc-dialog-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.2rem; border-bottom: 1px solid var(--line-light); }
    .rc-dialog-head h2 { font-family: var(--font-display); font-size: 1.2rem; font-weight: 400; }
    .rc-close { width: 2rem; height: 2rem; display: grid; place-items: center; border: 0; border-radius: 50%; background: transparent; color: var(--muted-light); cursor: pointer; font-size: 1.1rem; }
    .rc-close:hover { background: var(--hover-soft); color: var(--light-text); }
    .rc-search { margin: 1rem 1.2rem .4rem; }
    .rc-search input { width: 100%; min-height: 2.6rem; padding: .5rem .8rem; border: 1px solid var(--line-strong); border-radius: 0.25rem; background: var(--hover-soft); color: var(--light-text); }
    .rc-search input:focus { outline: 0; border-color: var(--moss); }
    .rc-results { padding: .3rem 1.2rem 1rem; overflow-y: auto; display: flex; flex-direction: column; gap: .35rem; }
    .rc-result { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem .6rem; border: 1px solid var(--line-light); border-radius: 0.25rem; background: var(--panel-soft); cursor: pointer; text-align: left; }
    .rc-result:hover { border-color: var(--moss); background: var(--hover-soft); }
    .rc-result:disabled { opacity: .45; cursor: default; }
    .rc-empty { color: var(--muted-light); text-align: center; padding: 1.5rem 0; }
    .rc-dept-list { padding: .6rem 1.2rem; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: .3rem; }
    .rc-dept-item { display: flex; align-items: center; gap: .5rem; padding: .45rem .5rem; border: 1px solid var(--line-light); border-radius: 0.25rem; cursor: pointer; font-size: .82rem; }
    .rc-dept-item:hover { border-color: var(--moss); }
    .rc-dept-item input { accent-color: var(--moss); }
    .rc-dialog-foot { padding: .9rem 1.2rem; border-top: 1px solid var(--line-light); display: flex; justify-content: flex-end; }
    .rc-save { min-height: 2.5rem; padding: 0 1.2rem; border: 1px solid var(--moss); border-radius: 0.25rem; background: rgb(91 141 239 / 18%); color: var(--light-text); cursor: pointer; font-weight: 700; font-size: .82rem; }
    .rc-save:hover { background: rgb(91 141 239 / 30%); }
    @media (max-width: 720px) {
      .rc-steps { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) { .rc-dept-list { grid-template-columns: 1fr; } }
@endsection

@section('content')
  <div class="set-wrap">
    <p class="set-section-label">RECRUIT SYSTEM</p>
    <p class="set-note" data-i18n="set.recruit.note2">สร้างได้หลายเส้นทางอนุมัติ แต่ละเส้นทางจัดลำดับการ์ด เลือกบทบาท และเพิ่มสมาชิก — DCC จะเลือกเองตอนส่งคำขอว่าจะเดินตามเส้นทางไหน</p>

    <div id="rcRoutes">
      @forelse ($routes as $route)
        <section class="rc-route" data-route-id="{{ $route['id'] }}">
          <header class="rc-route-head">
            <span class="rc-route-no" data-route-no></span>
            <input type="text" class="rc-route-name" data-route-name value="{{ $route['name'] }}" maxlength="60" autocomplete="off" aria-label="ชื่อเส้นทาง">
            <button type="button" class="rc-route-del" data-del-route data-i18n="set.recruit.delRoute">ลบเส้นทาง</button>
          </header>

          <div class="rc-steps" data-steps>
            @forelse ($route['steps'] as $step)
              <div class="rc-step-card" data-step-id="{{ $step['id'] }}" data-role="{{ $step['role'] }}" draggable="true">
                <div class="rc-card-top">
                  <span class="rc-pos" data-pos></span>
                  <select class="rc-role-select" data-role-select>
                    <option value="" data-i18n="set.recruit.pickRole" {{ $step['role'] ? '' : 'selected' }}>เลือกบทบาท</option>
                    @foreach ($roles as $r)
                      <option value="{{ $r }}" data-i18n="{{ $roleLabelKeys[$r] }}" {{ $step['role'] === $r ? 'selected' : '' }}>{{ $roleLabels[$r] }}</option>
                    @endforeach
                  </select>
                  <span class="rc-card-tools">
                    <button type="button" class="rc-del" data-del-step aria-label="ลบการ์ด">&times;</button>
                  </span>
                </div>
                <div class="rc-members" data-members>
                  @foreach ($step['members'] as $m)
                    <div class="rc-chip" data-member-id="{{ $m['id'] }}" data-uid="{{ $m['app_user_id'] }}" data-depts="{{ implode(',', $m['dept_codes']) }}">
                      @if ($m['avatar'])
                        <span class="rc-av"><img src="{{ $m['avatar'] }}" alt=""></span>
                      @else
                        <span class="rc-av avatar-placeholder" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><circle cx="24" cy="18" r="9"></circle><path d="M8 42c2.6-9.4 9-14.5 16-14.5S37.4 32.6 40 42H8Z"></path></svg></span>
                      @endif
                      <span class="rc-info">
                        <span class="rc-nm">{{ $m['code'] }} · {{ $m['name'] }}</span>
                        <span class="rc-ps">{{ $m['position'] ?: '—' }}{{ $m['department'] ? ' · '.$m['department'] : '' }}</span>
                      </span>
                      <span class="rc-chip-tools">
                        <button type="button" class="rc-dept-btn" data-dept-btn>แผนก ({{ count($m['dept_codes']) }})</button>
                        <button type="button" class="rc-x" data-remove aria-label="ลบ">&times;</button>
                      </span>
                    </div>
                  @endforeach
                </div>
                <button type="button" class="rc-add" data-add-member>+ <span data-i18n="set.recruit.add">เพิ่มสมาชิก</span></button>
              </div>
            @empty
              <p class="rc-empty-steps" data-i18n="set.recruit.noStep">ยังไม่มีการ์ด — กด "เพิ่มการ์ด" เพื่อสร้างขั้นแรก</p>
            @endforelse
          </div>

          <button type="button" class="rc-add-step" data-add-step>+ <span data-i18n="set.recruit.addCard">เพิ่มการ์ด</span></button>
        </section>
      @empty
        <p class="rc-empty-routes" data-i18n="set.recruit.noRoute">ยังไม่มีเส้นทาง — กด "เพิ่มเส้นทาง" เพื่อสร้างเส้นทางอนุมัติแรก</p>
      @endforelse
    </div>

    <button type="button" class="rc-add-route" id="rcAddRoute">+ <span data-i18n="set.recruit.addRoute">เพิ่มเส้นทาง</span></button>
  </div>

  {{-- Modal เลือกพนักงาน --}}
  <div class="rc-modal" id="rcModal" role="dialog" aria-modal="true">
    <div class="rc-dialog">
      <div class="rc-dialog-head">
        <h2 data-i18n="set.recruit.pickTitle">เพิ่มสมาชิก</h2>
        <button type="button" class="rc-close" id="rcModalClose" aria-label="ปิด">&times;</button>
      </div>
      <div class="rc-search"><input type="text" id="rcSearch" placeholder="ค้นหาชื่อ / รหัสพนักงาน" data-i18n-placeholder="set.recruit.search" autocomplete="off"></div>
      <div class="rc-results" id="rcResults"></div>
    </div>
  </div>

  {{-- Modal เลือกแผนก (DCC) --}}
  <div class="rc-modal" id="rcDeptModal" role="dialog" aria-modal="true">
    <div class="rc-dialog">
      <div class="rc-dialog-head">
        <h2 data-i18n="set.recruit.deptTitle">แผนกที่มองเห็นได้</h2>
        <button type="button" class="rc-close" id="rcDeptClose" aria-label="ปิด">&times;</button>
      </div>
      <div class="rc-dept-list" id="rcDeptList"></div>
      <div class="rc-dialog-foot"><button type="button" class="rc-save" id="rcDeptSave" data-i18n="set.recruit.deptSave">บันทึกแผนก</button></div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    (function () {
      var routesWrap = document.getElementById('rcRoutes');
      if (!routesWrap) return;
      var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      var base = "{{ url('recruit/settings') }}";
      var usersUrl = "{{ route('recruit.settings.users') }}";
      var deptsUrl = "{{ route('recruit.settings.departments') }}";
      var roleLabels = @json($roleLabels);
      var roleLabelKeys = @json($roleLabelKeys);

      function hdr() { return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }; }
      function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]; }); }
      function dict(k, fb) { var l = document.documentElement.getAttribute('data-lang') || 'th'; var m = (window.__portalCopy && window.__portalCopy[l]) || {}; return m[k] || fb; }
      function avatarPlaceholder() {
        return '<span class="rc-av avatar-placeholder" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><circle cx="24" cy="18" r="9"></circle><path d="M8 42c2.6-9.4 9-14.5 16-14.5S37.4 32.6 40 42H8Z"></path></svg></span>';
      }
      function roleLabel(role) { return dict(roleLabelKeys[role], roleLabels[role] || role); }

      // ── ลำดับเส้นทาง + การ์ด ──
      function renumberRoutes() {
        routesWrap.querySelectorAll('.rc-route').forEach(function (r, i) {
          var no = r.querySelector('[data-route-no]'); if (no) no.textContent = i + 1;
        });
      }
      function renumberSteps(stepsEl) {
        stepsEl.querySelectorAll('.rc-step-card').forEach(function (c, i) {
          c.setAttribute('draggable', 'true');
          c.querySelector('[data-pos]').textContent = i + 1;
        });
      }
      function saveOrder(stepsEl) {
        var order = Array.prototype.map.call(stepsEl.querySelectorAll('.rc-step-card'), function (c) { return Number(c.getAttribute('data-step-id')); });
        fetch(base + '/steps/reorder', { method: 'PUT', headers: hdr(), body: JSON.stringify({ order: order }) });
      }

      // ── chip + member ──
      function chipHtml(m) {
        var av = m.avatar ? '<span class="rc-av"><img src="' + esc(m.avatar) + '" alt=""></span>' : avatarPlaceholder();
        var sub = esc(m.position || '—') + (m.department ? ' · ' + esc(m.department) : '');
        return '<div class="rc-chip" data-member-id="' + m.id + '" data-uid="' + m.app_user_id + '" data-depts="' + esc((m.dept_codes || []).join(',')) + '">' +
          av +
          '<span class="rc-info"><span class="rc-nm">' + esc(m.code) + ' · ' + esc(m.name) + '</span><span class="rc-ps">' + sub + '</span></span>' +
          '<span class="rc-chip-tools"><button type="button" class="rc-dept-btn" data-dept-btn>' + dict('set.recruit.dept', 'แผนก') + ' (' + (m.dept_codes || []).length + ')</button>' +
          '<button type="button" class="rc-x" data-remove aria-label="ลบ">&times;</button></span></div>';
      }

      function cardHtml(step) {
        var opts = '<option value="" data-i18n="set.recruit.pickRole">' + esc(dict('set.recruit.pickRole', 'เลือกบทบาท')) + '</option>';
        Object.keys(roleLabels).forEach(function (r) {
          opts += '<option value="' + r + '" data-i18n="' + esc(roleLabelKeys[r]) + '">' + esc(roleLabel(r)) + '</option>';
        });
        return '<div class="rc-step-card" data-step-id="' + step.id + '" data-role="" draggable="true">' +
          '<div class="rc-card-top"><span class="rc-pos" data-pos></span>' +
          '<select class="rc-role-select" data-role-select>' + opts + '</select>' +
          '<span class="rc-card-tools"><button type="button" class="rc-del" data-del-step>&times;</button></span></div>' +
          '<div class="rc-members" data-members></div>' +
          '<button type="button" class="rc-add" data-add-member>+ ' + dict('set.recruit.add', 'เพิ่มสมาชิก') + '</button></div>';
      }

      function routeHtml(route) {
        return '<section class="rc-route" data-route-id="' + route.id + '">' +
          '<header class="rc-route-head"><span class="rc-route-no" data-route-no></span>' +
          '<input type="text" class="rc-route-name" data-route-name value="' + esc(route.name) + '" maxlength="60" autocomplete="off" aria-label="ชื่อเส้นทาง">' +
          '<button type="button" class="rc-route-del" data-del-route>' + esc(dict('set.recruit.delRoute', 'ลบเส้นทาง')) + '</button></header>' +
          '<div class="rc-steps" data-steps><p class="rc-empty-steps">' + esc(dict('set.recruit.noStep', 'ยังไม่มีการ์ด — กด "เพิ่มการ์ด" เพื่อสร้างขั้นแรก')) + '</p></div>' +
          '<button type="button" class="rc-add-step" data-add-step>+ ' + esc(dict('set.recruit.addCard', 'เพิ่มการ์ด')) + '</button></section>';
      }

      // ── เพิ่มเส้นทาง ──
      document.getElementById('rcAddRoute').addEventListener('click', function () {
        fetch(base + '/routes', { method: 'POST', headers: hdr() }).then(function (r) { return r.json(); }).then(function (d) {
          if (!d.ok) return;
          var emptyR = routesWrap.querySelector('.rc-empty-routes'); if (emptyR) emptyR.remove();
          routesWrap.insertAdjacentHTML('beforeend', routeHtml(d.route));
          renumberRoutes();
        });
      });

      // ── delegate clicks ──
      routesWrap.addEventListener('click', function (e) {
        // ลบเส้นทาง
        if (e.target.closest('[data-del-route]')) {
          var routeEl = e.target.closest('.rc-route');
          if (!confirm(dict('set.recruit.delRouteConfirm', 'ลบเส้นทางนี้พร้อมการ์ดทั้งหมด?'))) return;
          fetch(base + '/routes/' + routeEl.getAttribute('data-route-id'), { method: 'DELETE', headers: hdr() })
            .then(function () {
              routeEl.remove();
              renumberRoutes();
              if (!routesWrap.querySelector('.rc-route')) {
                routesWrap.insertAdjacentHTML('beforeend', '<p class="rc-empty-routes">' + esc(dict('set.recruit.noRoute', 'ยังไม่มีเส้นทาง')) + '</p>');
              }
            });
          return;
        }
        // เพิ่มการ์ดในเส้นทางนี้
        if (e.target.closest('[data-add-step]')) {
          var rEl = e.target.closest('.rc-route'), stepsAdd = rEl.querySelector('[data-steps]');
          fetch(base + '/routes/' + rEl.getAttribute('data-route-id') + '/steps', { method: 'POST', headers: hdr() })
            .then(function (r) { return r.json(); }).then(function (d) {
              if (!d.ok) return;
              var empty = stepsAdd.querySelector('.rc-empty-steps'); if (empty) empty.remove();
              stepsAdd.insertAdjacentHTML('beforeend', cardHtml(d.step));
              renumberSteps(stepsAdd);
            });
          return;
        }

        var card = e.target.closest('.rc-step-card'); if (!card) return;
        var stepId = card.getAttribute('data-step-id');
        var stepsEl = card.closest('[data-steps]');

        if (e.target.closest('[data-del-step]')) {
          if (!confirm(dict('set.recruit.delCard', 'ลบการ์ดนี้?'))) return;
          fetch(base + '/steps/' + stepId, { method: 'DELETE', headers: hdr() }).then(function () { card.remove(); renumberSteps(stepsEl); });
          return;
        }
        if (e.target.closest('[data-add-member]')) { openPicker(card); return; }
        if (e.target.closest('[data-remove]')) {
          var chip = e.target.closest('.rc-chip'), mid = chip.getAttribute('data-member-id');
          fetch(base + '/members/' + mid, { method: 'DELETE', headers: hdr() }).then(function () { chip.remove(); });
          return;
        }
        if (e.target.closest('[data-dept-btn]')) { openDept(e.target.closest('.rc-chip')); return; }
      });

      // ── role change ──
      routesWrap.addEventListener('change', function (e) {
        var sel = e.target.closest('[data-role-select]'); if (!sel) return;
        var card = sel.closest('.rc-step-card'), stepId = card.getAttribute('data-step-id');
        card.setAttribute('data-role', sel.value);
        fetch(base + '/steps/' + stepId + '/role', { method: 'PUT', headers: hdr(), body: JSON.stringify({ role: sel.value || null }) });
      });

      // ── เปลี่ยนชื่อเส้นทาง (บันทึกเมื่อพิมพ์เสร็จ / ออกจากช่อง) ──
      var nameTimers = {};
      routesWrap.addEventListener('input', function (e) {
        var inp = e.target.closest('[data-route-name]'); if (!inp) return;
        var routeId = inp.closest('.rc-route').getAttribute('data-route-id');
        clearTimeout(nameTimers[routeId]);
        nameTimers[routeId] = setTimeout(function () { saveName(routeId, inp.value); }, 500);
      });
      routesWrap.addEventListener('blur', function (e) {
        var inp = e.target.closest('[data-route-name]'); if (!inp) return;
        var routeId = inp.closest('.rc-route').getAttribute('data-route-id');
        clearTimeout(nameTimers[routeId]);
        saveName(routeId, inp.value);
      }, true);
      function saveName(routeId, name) {
        name = (name || '').trim(); if (!name) return;
        fetch(base + '/routes/' + routeId, { method: 'PUT', headers: hdr(), body: JSON.stringify({ name: name }) });
      }

      // ── user picker ──
      var modal = document.getElementById('rcModal'), results = document.getElementById('rcResults'), searchInput = document.getElementById('rcSearch');
      var pickCard = null, debounce;
      function openPicker(card) { pickCard = card; modal.classList.add('is-open'); searchInput.value = ''; loadUsers(''); searchInput.focus(); }
      function closePicker() { modal.classList.remove('is-open'); }
      document.getElementById('rcModalClose').addEventListener('click', closePicker);
      modal.addEventListener('click', function (e) { if (e.target === modal) closePicker(); });
      searchInput.addEventListener('input', function () { clearTimeout(debounce); debounce = setTimeout(function () { loadUsers(searchInput.value.trim()); }, 250); });

      function loadUsers(q) {
        results.innerHTML = '<p class="rc-empty">' + esc(dict('set.recruit.loading', 'กำลังค้นหา...')) + '</p>';
        fetch(usersUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); })
          .then(function (d) { renderUsers(d.users || []); }).catch(function () { results.innerHTML = '<p class="rc-empty">' + esc(dict('set.recruit.empty', 'ไม่พบพนักงาน')) + '</p>'; });
      }
      function renderUsers(users) {
        if (!users.length) { results.innerHTML = '<p class="rc-empty">' + esc(dict('set.recruit.empty', 'ไม่พบพนักงาน')) + '</p>'; return; }
        var exist = {}; pickCard.querySelectorAll('.rc-chip').forEach(function (c) { exist[c.getAttribute('data-uid')] = true; });
        var html = '';
        users.forEach(function (u) {
          var av = u.avatar ? '<img src="' + esc(u.avatar) + '" alt="">' : esc((u.name || '?').charAt(0).toUpperCase());
          var sub = esc(u.position || '—') + (u.department ? ' · ' + esc(u.department) : '');
          html += '<button type="button" class="rc-result" ' + (exist[String(u.app_user_id)] ? 'disabled' : '') + '>' + (u.avatar ? '<span class="rc-av">' + av + '</span>' : avatarPlaceholder()) +
            '<span class="rc-info"><span class="rc-nm">' + esc(u.code) + ' · ' + esc(u.name) + '</span><span class="rc-ps">' + sub + '</span></span></button>';
        });
        results.innerHTML = html;
        results.querySelectorAll('.rc-result').forEach(function (btn, i) { btn.__user = users[i]; });
      }
      results.addEventListener('click', function (e) {
        var btn = e.target.closest('.rc-result'); if (!btn || btn.disabled) return;
        var u = btn.__user, stepId = pickCard.getAttribute('data-step-id');
        fetch(base + '/steps/' + stepId + '/members', { method: 'POST', headers: hdr(), body: JSON.stringify({ app_user_id: u.app_user_id }) })
          .then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) { pickCard.querySelector('[data-members]').insertAdjacentHTML('beforeend', chipHtml(d.member)); btn.disabled = true; }
          });
      });

      // ── dept picker (DCC) ──
      var deptModal = document.getElementById('rcDeptModal'), deptList = document.getElementById('rcDeptList'), deptChip = null, deptCache = null;
      document.getElementById('rcDeptClose').addEventListener('click', function () { deptModal.classList.remove('is-open'); });
      deptModal.addEventListener('click', function (e) { if (e.target === deptModal) deptModal.classList.remove('is-open'); });
      function openDept(chip) {
        deptChip = chip;
        var selected = (chip.getAttribute('data-depts') || '').split(',').filter(Boolean);
        deptModal.classList.add('is-open');
        deptList.innerHTML = '<p class="rc-empty">' + esc(dict('set.recruit.loading', 'กำลังค้นหา...')) + '</p>';
        var show = function (depts) {
          deptList.innerHTML = depts.map(function (d) {
            return '<label class="rc-dept-item"><input type="checkbox" value="' + esc(d.code) + '" ' + (selected.indexOf(d.code) >= 0 ? 'checked' : '') + '><span>' + esc(d.name) + '</span></label>';
          }).join('');
        };
        if (deptCache) { show(deptCache); }
        else { fetch(deptsUrl, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) { deptCache = d.departments || []; show(deptCache); }); }
      }
      document.getElementById('rcDeptSave').addEventListener('click', function () {
        if (!deptChip) return;
        var codes = Array.prototype.map.call(deptList.querySelectorAll('input:checked'), function (i) { return i.value; });
        var mid = deptChip.getAttribute('data-member-id');
        fetch(base + '/members/' + mid + '/departments', { method: 'PUT', headers: hdr(), body: JSON.stringify({ dept_codes: codes }) })
          .then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) {
              deptChip.setAttribute('data-depts', codes.join(','));
              deptChip.querySelector('[data-dept-btn]').textContent = dict('set.recruit.dept', 'แผนก') + ' (' + codes.length + ')';
              deptModal.classList.remove('is-open');
            }
          });
      });

      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closePicker(); deptModal.classList.remove('is-open'); } });

      // ── drag card เพื่อเรียงลำดับ (ภายในเส้นทางเดียวกัน) ──
      var draggedCard = null, dragStepsEl = null, orderDirty = false;
      function clearDropTargets() { routesWrap.querySelectorAll('.is-drop-target').forEach(function (c) { c.classList.remove('is-drop-target'); }); }
      routesWrap.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.rc-step-card');
        if (!card || e.target.closest('button, select, input, label')) { e.preventDefault(); return; }
        draggedCard = card;
        dragStepsEl = card.closest('[data-steps]');
        orderDirty = false;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.getAttribute('data-step-id'));
        window.requestAnimationFrame(function () { card.classList.add('is-dragging'); });
      });
      routesWrap.addEventListener('dragover', function (e) {
        if (!draggedCard) return;
        var target = e.target.closest('.rc-step-card');
        if (!target || target === draggedCard || target.closest('[data-steps]') !== dragStepsEl) return;
        e.preventDefault();
        clearDropTargets();
        target.classList.add('is-drop-target');
        var rect = target.getBoundingClientRect();
        if (e.clientX < rect.left + (rect.width / 2)) { dragStepsEl.insertBefore(draggedCard, target); }
        else { dragStepsEl.insertBefore(draggedCard, target.nextSibling); }
        orderDirty = true;
        renumberSteps(dragStepsEl);
      });
      routesWrap.addEventListener('drop', function (e) { if (!draggedCard) return; e.preventDefault(); clearDropTargets(); });
      routesWrap.addEventListener('dragend', function () {
        if (!draggedCard) return;
        draggedCard.classList.remove('is-dragging');
        clearDropTargets();
        var el = dragStepsEl;
        draggedCard = null; dragStepsEl = null;
        if (orderDirty && el) saveOrder(el);
      });

      renumberRoutes();
      routesWrap.querySelectorAll('[data-steps]').forEach(renumberSteps);
    })();
  </script>
@endsection
