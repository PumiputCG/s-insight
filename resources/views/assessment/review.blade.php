@extends('layouts.portal')

@section('title', 'ตรวจสอบผลลัพธ์')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.review.title">รายการตรวจสอบผลลัพธ์</span>
@endsection

@section('page-style')
    .asm-review-stack {
      width: min(100%, 86rem);
      margin: 0 auto;
      display: grid;
      gap: 1rem;
    }

    .asm-review-intro {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .asm-review-intro h2 {
      margin: 0 0 .2rem;
      color: var(--light-text);
      font-size: 1.12rem;
      font-weight: 700;
    }

    .asm-review-intro p {
      max-width: 52rem;
      margin: 0;
      color: var(--muted-light);
      font-size: .83rem;
      line-height: 1.55;
    }

    .asm-review-meta {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: .5rem;
      flex-wrap: wrap;
    }

    .asm-review-chip {
      display: inline-flex;
      align-items: center;
      min-height: 1.9rem;
      padding: .25rem .7rem;
      border: 1px solid var(--line-light);
      border-radius: .25rem;
      background: var(--menu-bg);
      color: var(--moss);
      font-size: .8rem;
      font-weight: 700;
      white-space: nowrap;
    }

    /* ป้ายรอบที่เปิด — ข้อความล้วน ไม่มีกรอบ/พื้น */
    .asm-review-chip.is-muted {
      min-height: 0;
      padding: 0;
      border: 0;
      background: none;
      color: var(--muted-light);
      font-weight: 600;
    }

    .asm-review-panel {
      width: 100%;
      overflow: hidden;
      border: 1px solid var(--line-light);
      border-radius: .34rem;
      background: var(--panel-soft);
    }

    .asm-review-panel-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--line-light);
    }

    .asm-review-panel-head strong {
      color: var(--light-text);
      font-size: 1rem;
    }

    .asm-review-head-tools {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      flex-wrap: wrap;
    }

    .asm-review-table-wrap {
      overflow: auto;
      background: var(--menu-bg);
    }

    .asm-review-table {
      width: 100%;
      min-width: 64rem;
      border-collapse: separate;
      border-spacing: 0;
      font-size: .84rem;
    }

    .asm-review-table th,
    .asm-review-table td {
      padding: .78rem .9rem;
      border-right: 1px solid var(--line-light);
      border-bottom: 1px solid var(--line-light);
      text-align: left;
      vertical-align: middle;
    }

    .asm-review-table th:last-child,
    .asm-review-table td:last-child {
      border-right: none;
    }

    .asm-review-table th {
      position: sticky;
      top: 0;
      z-index: 1;
      border-right-color: rgb(255 255 255 / 16%);
      border-bottom-color: rgb(255 255 255 / 16%);
      background: #23262e;
      color: #fff;
      font-size: .78rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .asm-review-table tr:last-child td {
      border-bottom: none;
    }

    .asm-review-table tbody tr {
      transition: background-color .16s var(--ease-out);
    }

    .asm-review-table tbody tr:hover {
      background: var(--hover-soft);
    }

    .asm-review-center {
      text-align: center !important;
    }

    .asm-review-no {
      width: 4rem;
      color: var(--muted-light);
      white-space: nowrap;
    }

    /* หัวคอลัมน์กึ่งกลาง แต่ข้อมูลในเซลล์ชิดซ้าย */
    .asm-review-table td.asm-review-person-cell {
      min-width: 19rem;
      text-align: left !important;
    }

    .asm-review-person {
      display: grid;
      grid-template-columns: 3.15rem minmax(0, 1fr);
      align-items: center;
      gap: .75rem;
      min-width: 0;
    }

    .asm-review-avatar {
      position: relative;
      width: 3.15rem;
      height: 3.15rem;
      overflow: hidden;
      padding: 0;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      background: var(--panel-soft);
      color: var(--muted-light);
      cursor: zoom-in;
    }

    .asm-review-avatar img,
    .asm-review-avatar svg {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
    }

    .asm-review-avatar:hover {
      border-color: var(--moss);
    }

    .asm-review-avatar:focus-visible,
    .asm-profile-close:focus-visible {
      outline: 3px solid color-mix(in srgb, var(--moss), transparent 55%);
      outline-offset: 2px;
    }

    .asm-review-name {
      min-width: 0;
      display: grid;
      gap: .18rem;
    }

    .asm-review-name strong {
      overflow: hidden;
      color: var(--light-text);
      font-size: .9rem;
      line-height: 1.35;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .asm-review-name-line {
      display: flex;
      align-items: center;
      gap: .45rem;
      color: var(--muted-light);
      font-size: .76rem;
    }

    .asm-review-table td.asm-review-text {
      max-width: 16rem;
      color: var(--light-text);
      text-align: center;
    }

    .asm-review-text span {
      display: block;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .asm-review-score {
      min-width: 10rem;
      text-align: center !important;
      font-variant-numeric: tabular-nums;
    }

    /* ตัวเลขล้วน ไม่มีกรอบ/พื้น */
    .asm-review-score-value {
      display: inline-block;
      color: var(--light-text);
      font-size: .94rem;
      font-weight: 700;
    }

    .asm-review-score-value.is-empty {
      color: var(--muted-light);
      font-weight: 600;
    }

    /* ป้ายแว่นขยายมุมล่างขวาของรูป — โผล่ตอน hover เหมือนหน้าฟอร์มประเมิน */
    .asm-review-avatar.is-zoomable {
      cursor: zoom-in;
    }

    .asm-photo-zoom {
      position: absolute;
      right: 0;
      bottom: 0;
      width: 1.25rem;
      height: 1.25rem;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: rgb(38 37 31 / 72%);
      color: #fff;
      opacity: 0;
      transition: opacity .16s ease;
    }

    .asm-photo-zoom svg {
      width: .8rem;
      height: .8rem;
    }

    .asm-review-avatar.is-zoomable:hover .asm-photo-zoom,
    .asm-review-avatar.is-zoomable:focus-visible .asm-photo-zoom {
      opacity: 1;
    }

    /* กล่องรูปขนาดเต็ม — ซูม/ลากเลื่อนได้ (รูปแบบเดียวกับ /assessment/evaluate/{emp}/{level}) */
    .photo-modal { position: fixed; inset: 0; z-index: 90; display: grid; place-items: center; padding: 1.5rem; overflow: auto; }
    .photo-modal[hidden] { display: none; }
    .photo-modal-back { position: absolute; inset: 0; background: rgb(12 13 10 / 78%); }
    .photo-modal-box { position: relative; display: grid; gap: .5rem; max-width: min(92vw, 40rem); }
    .photo-modal-stage { border-radius: .3rem; background: #111; box-shadow: 0 24px 70px rgb(0 0 0 / 45%); touch-action: none; }
    .photo-modal-box img { display: block; width: auto; max-width: none; height: auto; margin: 0 auto; border-radius: .3rem; cursor: zoom-in; transition: height .1s linear; user-select: none; -webkit-user-drag: none; }
    .photo-modal-name { margin: 0; color: #f2efe6; font-size: .86rem; font-weight: 700; text-align: center; }
    .photo-modal-hint { margin: 0; color: rgb(242 239 230 / 62%); font-size: .72rem; text-align: center; }
    .photo-modal-hint[hidden] { display: none; }
    .photo-modal-x { position: absolute; top: -.6rem; right: -.6rem; width: 2rem; height: 2rem; display: grid; place-items: center; border: 0; border-radius: 50%; background: #fdfcf8; color: #26251f; cursor: pointer; font-size: 1rem; box-shadow: 0 6px 18px rgb(0 0 0 / 35%); }
    .photo-modal-x:hover { background: #fff; }

    @media (max-width: 48rem) {
      .asm-review-intro {
        align-items: flex-start;
      }

      .asm-review-meta {
        justify-content: flex-start;
      }

      .asm-review-panel-head {
        align-items: flex-start;
        flex-direction: column;
      }

      .photo-modal { padding: .9rem; }
    }
@endsection

@section('content')
  <div class="asm-review-stack">
    <div class="asm-review-intro">
      <div>
        <h2 data-i18n="assessment.review.title">รายการตรวจสอบผลลัพธ์</h2>
        <p data-i18n="assessment.review.help">แสดงเฉพาะพนักงานที่คุณถูกกำหนดเป็นผู้ตรวจสอบลำดับ 3 หรือ 4 ในรอบที่เปิดอยู่</p>
      </div>
      <div class="asm-review-meta">
        @if (($openRound ?? null))
          <span class="asm-review-chip is-muted">
            <span data-i18n="assessment.openRound">รอบที่เปิด:</span>&nbsp;{{ $openRound->name }} ({{ $openRound->year }})
          </span>
        @endif
      </div>
    </div>

    <section class="asm-review-panel" aria-label="รายการตรวจสอบผลลัพธ์" data-i18n-aria="assessment.review.title">
      <div class="asm-review-panel-head">
        <strong data-i18n="assessment.evaluate.employee">พนักงานที่ประเมิน</strong>
        <span class="asm-review-head-tools">
          @include('assessment.partials.sortselect')
          <span class="asm-review-chip">{{ number_format($assignments->count()) }} <span data-i18n="assessment.evaluate.itemUnit">รายการ</span></span>
        </span>
      </div>

      <div class="asm-review-table-wrap" tabindex="0" aria-label="ตารางตรวจสอบผลลัพธ์" data-i18n-aria="assessment.review.tableLabel">
        <table class="asm-review-table" data-sortable-table>
          <thead>
            <tr>
              <th class="asm-review-center">No.</th>
              <th class="asm-review-center" data-i18n="assessment.evaluate.employee">พนักงานที่ประเมิน</th>
              <th class="asm-review-center" data-i18n="assessment.evaluate.position">ตำแหน่ง</th>
              <th class="asm-review-center" data-i18n="assessment.evaluate.department">แผนก</th>
              <th class="asm-review-center" data-i18n="assessment.review.levelOneTotal">คะแนนประเมิน</th>
              <th class="asm-review-center" data-i18n="assessment.review.selfScore">คะแนนตนเอง</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($assignments as $row)
              <tr data-sort-code="{{ $row['employee_code'] }}" data-sort-rank="{{ $row['position_rank'] ?? 999 }}">
                <td class="asm-review-no asm-review-center" data-row-no>{{ number_format($loop->iteration) }}</td>
                <td class="asm-review-person-cell">
                  <div class="asm-review-person">
                    @if (! empty($row['avatar']))
                      {{-- มีรูปจริง → กดเพื่อดูขนาดเต็มและซูมได้ --}}
                      <button
                        type="button"
                        class="asm-review-avatar is-zoomable"
                        data-photo-open
                        data-photo-src="{{ $row['avatar'] }}"
                        data-photo-name="{{ $row['name'] }}"
                        data-photo-name-en="{{ $row['name_en'] ?? $row['name'] }}"
                        aria-label="ดูรูปโปรไฟล์ {{ $row['name'] }} ขนาดเต็ม"
                        data-i18n-aria="assessment.evaluate.viewPhoto"
                        title="กดเพื่อขยายรูป"
                        data-i18n-title="assessment.evaluate.zoomOpen"
                      >
                        <img src="{{ $row['avatar'] }}" alt="">
                        <span class="asm-photo-zoom" aria-hidden="true">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5M11 8v6M8 11h6" />
                          </svg>
                        </span>
                      </button>
                    @else
                      <span class="asm-review-avatar" aria-hidden="true">
                        @include('assessment.partials.default-avatar')
                      </span>
                    @endif
                    <div class="asm-review-name">
                      <strong title="{{ $row['name'] }}" data-loc-th="{{ $row['name'] }}" data-loc-en="{{ $row['name_en'] ?? $row['name'] }}">{{ $row['name'] }}</strong>
                      <div class="asm-review-name-line">
                        <span>{{ $row['employee_code'] }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="asm-review-text"><span title="{{ $row['position'] ?: '-' }}" data-loc-th="{{ $row['position'] ?: '-' }}" data-loc-en="{{ $row['position_en'] ?: ($row['position'] ?: '-') }}">{{ $row['position'] ?: '-' }}</span></td>
                <td class="asm-review-text"><span title="{{ $row['department'] ?: '-' }}" data-loc-th="{{ $row['department'] ?: '-' }}" data-loc-en="{{ $row['department_en'] ?: ($row['department'] ?: '-') }}">{{ $row['department'] ?: '-' }}</span></td>
                <td class="asm-review-score">
                  <span class="asm-review-score-value {{ ($row['level_one_total'] ?? null) === null ? 'is-empty' : '' }}">{{ $row['level_one_total_display'] ?? '-' }}</span>
                </td>
                <td class="asm-review-score">
                  <span class="asm-review-score-value {{ ($row['self_score'] ?? null) === null ? 'is-empty' : '' }}">{{ $row['self_score_display'] ?? '-' }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  </div>

  {{-- รูปโปรไฟล์ขนาดเต็ม — ซูม/ลากเลื่อนได้ --}}
  <div class="photo-modal" data-photo-modal hidden>
    <div class="photo-modal-back" data-photo-close></div>
    <div class="photo-modal-box" role="dialog" aria-modal="true" aria-label="รูปโปรไฟล์" data-i18n-aria="assessment.evaluate.profileTitle">
      <button type="button" class="photo-modal-x" data-photo-close aria-label="ปิด" data-i18n-aria="profile.close_photo">✕</button>
      <div class="photo-modal-stage">
        <img data-photo-img src="" alt="" draggable="false">
      </div>
      <p class="photo-modal-name" data-photo-name></p>
      <p class="photo-modal-hint" data-photo-hint data-i18n="assessment.evaluate.zoomHint">หมุนล้อเมาส์เพื่อซูม · ดับเบิลคลิกเพื่อขยาย/ย่อ</p>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    'use strict';
    // กดรูปโปรไฟล์เพื่อดูขนาดเต็ม · หมุนล้อเมาส์ซูม · ลากเลื่อนเมื่อซูมแล้ว (รูปแบบเดียวกับหน้าฟอร์มประเมิน)
    (() => {
      const box = document.querySelector('[data-photo-modal]');
      if (!box) return;
      const img = box.querySelector('[data-photo-img]');
      const name = box.querySelector('[data-photo-name]');

      const MIN = 1, MAX = 6;
      let scale = 1;          // 1 = ขนาดพอดีจอ · มากกว่านั้น = ภาพใหญ่ขึ้นจริง
      let baseH = 0;          // ความสูงจริงของภาพตอนพอดีจอ (px)
      let dragging = false, moved = false, sx = 0, sy = 0, sl = 0, st = 0;

      // ชื่อใต้รูปตามภาษาที่เลือกอยู่
      const localizedName = (btn) => {
        const lang = document.documentElement.getAttribute('data-lang') || 'th';
        return lang === 'th'
          ? (btn.dataset.photoName || '')
          : (btn.dataset.photoNameEn || btn.dataset.photoName || '');
      };

      const clamp = (v) => Math.min(MAX, Math.max(MIN, v));
      const apply = () => {
        if (baseH) img.style.height = (baseH * scale) + 'px';
        img.style.cursor = scale > 1 ? (dragging ? 'grabbing' : 'grab') : 'zoom-in';
        box.querySelector('[data-photo-hint]')?.toggleAttribute('hidden', scale > 1);
      };
      const reset = () => { scale = 1; apply(); box.scrollTo({ top: 0, left: 0 }); };

      // วัดขนาดพอดีจอครั้งแรกที่ภาพโหลดเสร็จ
      const measure = () => {
        img.style.height = '';
        const fitH = Math.min(img.naturalHeight || 0, window.innerHeight * 0.78);
        baseH = fitH || img.clientHeight;
        apply();
      };
      img.addEventListener('load', measure);

      const closePhoto = () => { box.hidden = true; img.removeAttribute('src'); scale = 1; baseH = 0; };

      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-photo-open]');
        if (btn) {
          e.preventDefault();
          img.src = btn.dataset.photoSrc || '';
          img.alt = localizedName(btn);
          name.textContent = localizedName(btn);
          reset();
          box.hidden = false;
          box.querySelector('.photo-modal-x')?.focus();
          return;
        }
        // ลากแล้วปล่อยบนพื้นหลัง ไม่ควรนับเป็นการกดปิด
        if (!moved && e.target.closest('[data-photo-close]')) closePhoto();
        moved = false;
      });

      // ล้อเมาส์ = ภาพใหญ่/เล็กลงจริง · เลื่อนกล่องตามเพื่อให้จุดใต้เคอร์เซอร์อยู่ที่เดิม
      box.addEventListener('wheel', (e) => {
        if (box.hidden) return;
        e.preventDefault();
        const next = clamp(scale * (e.deltaY < 0 ? 1.18 : 1 / 1.18));
        if (next === scale) return;

        const rect = img.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width;    // จุดบนภาพที่เคอร์เซอร์ชี้ (0-1)
        const py = (e.clientY - rect.top) / rect.height;
        scale = next;
        apply();

        if (scale === MIN) { box.scrollTo({ top: 0, left: 0 }); return; }
        const after = img.getBoundingClientRect();
        box.scrollLeft += (after.width - rect.width) * px;
        box.scrollTop += (after.height - rect.height) * py;
      }, { passive: false });

      // ลากเพื่อเลื่อนดู (เลื่อน scroll ของกล่อง)
      img.addEventListener('pointerdown', (e) => {
        if (scale <= 1) return;
        dragging = true; moved = false;
        sx = e.clientX; sy = e.clientY;
        sl = box.scrollLeft; st = box.scrollTop;
        img.setPointerCapture(e.pointerId);
        apply();
      });
      img.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        box.scrollLeft = sl - (e.clientX - sx);
        box.scrollTop = st - (e.clientY - sy);
        moved = true;
      });
      const endDrag = (e) => {
        if (!dragging) return;
        dragging = false;
        try { img.releasePointerCapture(e.pointerId); } catch (err) {}
        apply();
      };
      img.addEventListener('pointerup', endDrag);
      img.addEventListener('pointercancel', endDrag);

      // ดับเบิลคลิก = ขยาย 2.5 เท่า / กลับขนาดเดิม
      img.addEventListener('dblclick', (e) => {
        e.preventDefault();
        if (scale > 1) { reset(); return; }
        const rect = img.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width;
        const py = (e.clientY - rect.top) / rect.height;
        scale = 2.5;
        apply();
        const after = img.getBoundingClientRect();
        box.scrollLeft = (after.width * px) - (box.clientWidth / 2);
        box.scrollTop = (after.height * py) - (box.clientHeight / 2);
      });

      document.addEventListener('keydown', (e) => {
        if (box.hidden) return;
        if (e.key === 'Escape') closePhoto();
        if (e.key === '0') reset();
      });
    })();
  </script>
@endsection
