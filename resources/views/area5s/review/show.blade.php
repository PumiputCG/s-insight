@extends('layouts.portal')

@section('title', 'ตรวจ: จุด '.$task->point_code.' — '.$task->layout_name)

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title"><span data-i18n="a5s.review.title">ตรวจประเมิน</span> — <span data-i18n="a5s.common.point">จุด</span> {{ $task->point_code }}</span>
@endsection

@php $statusLabels = \App\Http\Controllers\Area5s\Area5sResponsibleController::STATUS_LABELS; @endphp

@section('page-style')
    .a5d-wrap { display:grid; gap:.9rem; width:min(100%, 64rem); margin:0 auto; }
    .a5d-top { display:flex; align-items:center; justify-content:space-between; gap:.8rem; flex-wrap:wrap; }
    .a5d-back { display:inline-flex; align-items:center; gap:.4rem; padding:.42rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.8rem; font-weight:600; text-decoration:none; }
    .a5d-back:hover { border-color:var(--moss); color:var(--moss); }

    .a5d-head { border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1rem 1.2rem; display:grid; gap:.3rem; }
    .a5d-head h2 { margin:0; font-size:1.05rem; color:var(--light-text); }
    .a5d-head p { margin:0; color:var(--muted-light); font-size:.82rem; }
    .a5d-meta { display:flex; gap:1.2rem; flex-wrap:wrap; margin-top:.3rem; color:var(--muted-light); font-size:.78rem; }
    .a5d-meta b { color:var(--light-text); }
    .a5d-pill { display:inline-flex; align-items:center; gap:.35rem; width:fit-content; padding:.2rem .65rem; border-radius:999px; font-size:.74rem; font-weight:700; }
    .a5d-pill::before { content:""; width:.4rem; height:.4rem; border-radius:50%; background:currentColor; }
    .a5d-pill.st-submitted, .a5d-pill.st-resubmitted { background:color-mix(in srgb, var(--moss) 13%, transparent); color:var(--moss); }
    .a5d-pill.st-failed { background:rgb(217 138 128 / 15%); color:#d98a80; }
    .a5d-pill.st-passed { background:rgb(76 175 125 / 14%); color:#4caf7d; }
    .a5d-pill.st-draft, .a5d-pill.st-not_started { background:var(--hover-soft); color:var(--muted-light); }

    .a5d-prev { border:1px solid rgb(217 138 128 / 45%); border-radius: 0.28rem; background:rgb(217 138 128 / 8%); padding:.75rem .95rem; font-size:.82rem; color:var(--light-text); }
    .a5d-prev b { color:#d98a80; }

    .a5d-cards { display:grid; gap:.8rem; }
    .a5d-card { border:1px solid var(--line-light); border-radius: 0.32rem; background:var(--menu-bg); padding:.9rem 1rem; }
    .a5d-card-head { display:flex; align-items:baseline; justify-content:space-between; gap:.7rem; flex-wrap:wrap; margin-bottom:.35rem; }
    .a5d-card-head strong { color:var(--light-text); font-size:.92rem; }
    .a5d-card-head small { color:var(--muted-light); font-size:.74rem; }
    .a5d-card p { margin:.2rem 0 .6rem; color:var(--muted-light); font-size:.83rem; line-height:1.55; white-space:pre-line; }
    .a5d-imgs { display:grid; grid-template-columns:repeat(auto-fill, minmax(8.5rem, 1fr)); gap:.5rem; }
    .a5d-imgs a { display:block; aspect-ratio:4/3; border-radius: 0.25rem; overflow:hidden; border:1px solid var(--line-light); }
    .a5d-imgs img { width:100%; height:100%; object-fit:cover; transition:transform .2s ease; }
    .a5d-imgs a:hover img { transform:scale(1.05); }

    /* ฟอร์มตัดสิน */
    .a5d-decide { border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); padding:1rem 1.2rem; display:grid; gap:.7rem; }
    .a5d-decide h3 { margin:0; font-size:.95rem; color:var(--light-text); }
    .a5d-choices { display:flex; gap:.6rem; flex-wrap:wrap; }
    .a5d-choice { flex:1; min-width:10rem; display:flex; align-items:center; gap:.6rem; padding:.75rem .9rem; border:2px solid var(--line-light); border-radius: 0.28rem; background:var(--menu-bg); cursor:pointer; font-size:.9rem; font-weight:700; color:var(--muted-light); }
    .a5d-choice input { accent-color:currentColor; }
    .a5d-choice.pass:has(input:checked) { border-color:#4caf7d; color:#4caf7d; background:rgb(76 175 125 / 8%); }
    .a5d-choice.fail:has(input:checked) { border-color:#d98a80; color:#d98a80; background:rgb(217 138 128 / 8%); }
    .a5d-decide label.fld { display:grid; gap:.3rem; font-size:.78rem; color:var(--muted-light); font-weight:600; }
    .a5d-decide textarea { padding:.55rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.84rem; min-height:4rem; resize:vertical; }
    .a5d-decide textarea:focus { outline:none; border-color:var(--moss); }
    .a5d-reason[hidden] { display:none; }
    .a5d-submit { justify-self:end; display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.4rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; font-size:.85rem; font-weight:700; cursor:pointer; }
    .a5d-submit:disabled { opacity:.5; cursor:not-allowed; }
    .a5d-errors { color:#d98a80; font-size:.8rem; }
    .a5d-note { color:var(--muted-light); font-size:.78rem; }
@endsection

@section('content')
  <div class="a5d-wrap">
    <div class="a5d-top">
      <a class="a5s-back nav-go" href="{{ route('area5s.review.index') }}"><span aria-hidden="true">&lsaquo;</span> <span data-i18n="a5s.common.back">กลับ</span></a>
      <span class="a5d-pill st-{{ $task->status }}" data-i18n="a5s.status.{{ $task->status }}">{{ $statusLabels[$task->status] ?? $task->status }}</span>
    </div>

    <div class="a5d-head">
      <h2><span data-i18n="a5s.common.point">จุด</span> {{ $task->point_code }} · {{ $task->point_name }}</h2>
      <p>{{ $task->layout_name }}@if ($task->point_description) — {{ $task->point_description }}@endif</p>
      <div class="a5d-meta">
        @php
          $assigneeHtml = collect($task->assignees_json)->map(function ($person) {
            $name = $person['name'] ?? ($person['code'] ?? '-');
            $nameTh = $person['name_th'] ?? $name;
            $nameEn = $person['name_en'] ?? $nameTh;
            $nameMy = $person['name_my'] ?? $nameEn;
            return '<span data-a5s-name-th="'.e($nameTh).'" data-a5s-name-en="'.e($nameEn).'" data-a5s-name-my="'.e($nameMy).'">'.e($name).'</span>';
          })->implode(', ');
        @endphp
        <span><span data-i18n="a5s.common.assignees">ผู้รับผิดชอบ</span>: <b>{!! $assigneeHtml ?: '-' !!}</b></span>
        <span><span data-i18n="a5s.common.sentAt">ส่งเมื่อ</span>: <b>{{ $task->submitted_at?->format('d/m/Y H:i') ?? '-' }}</b></span>
        <span><span data-i18n="a5s.common.submitCount">ครั้งที่ส่ง</span>: <b>{{ $task->submit_count }}</b></span>
        <span><span data-i18n="a5s.common.round">รอบ</span>: <b>{{ $round?->month }}/{{ $round?->year }}</b></span>
      </div>
    </div>

    @if ($task->status === 'failed' && $task->fail_reason)
      <div class="a5d-prev"><b data-i18n="a5s.review.latestFailReason">เหตุผลที่ไม่ผ่าน (รอบล่าสุด):</b> {{ $task->fail_reason }}</div>
    @endif
    @if ($task->status === 'passed')
      <div class="a5d-prev" style="border-color:rgb(76 175 125 / 45%); background:rgb(76 175 125 / 8%)">
        <b style="color:#4caf7d" data-i18n="a5s.review.passedDone">ผ่านการประเมินแล้ว</b> <span data-i18n="a5s.review.when">เมื่อ</span> {{ $task->evaluated_at?->format('d/m/Y H:i') }}@if ($task->advice) · <span data-i18n="a5s.work.advice">คำแนะนำ:</span> {{ $task->advice }}@endif
      </div>
    @endif

    {{-- การ์ดที่ส่งมา --}}
    <div class="a5d-cards">
      @forelse ($cards as $card)
        <article class="a5d-card">
          <div class="a5d-card-head">
            <strong>{{ $card['title'] }}</strong>
            <small><span data-i18n="a5s.common.by">โดย</span>
              <span data-a5s-name-th="{{ $card['owner_th'] ?? $card['owner'] }}" data-a5s-name-en="{{ $card['owner_en'] ?? ($card['owner_th'] ?? $card['owner']) }}" data-a5s-name-my="{{ $card['owner_my'] ?? ($card['owner_en'] ?? ($card['owner_th'] ?? $card['owner'])) }}">{{ $card['owner'] }}</span>
              · {{ $card['created_at'] }}</small>
          </div>
          @if ($card['detail'])<p>{{ $card['detail'] }}</p>@endif
          <div class="a5d-imgs">
            @foreach ($card['images'] as $img)
              <a href="{{ $img }}" target="_blank" rel="noopener"><img src="{{ $img }}" alt="" loading="lazy"></a>
            @endforeach
          </div>
        </article>
      @empty
        <div class="a5d-card" style="text-align:center; color:var(--muted-light)" data-i18n="a5s.review.noCardsInTask">ยังไม่มีรายการในงานนี้</div>
      @endforelse
    </div>

    {{-- ตัดสินผล --}}
    @if ($canDecide)
      <form class="a5d-decide" method="POST" action="{{ route('area5s.review.decide', $task) }}">
        @csrf
        <h3 data-i18n="a5s.review.decisionTitle">ผลการตรวจประเมิน</h3>
        @if ($errors->any())
          <div class="a5d-errors">@foreach ($errors->all() as $e) {{ $e }} @endforeach</div>
        @endif
        <div class="a5d-choices">
          <label class="a5d-choice pass"><input type="radio" name="result" value="pass" required> <span data-i18n="a5s.review.pass">ผ่าน</span></label>
          <label class="a5d-choice fail"><input type="radio" name="result" value="fail"> <span data-i18n="a5s.review.reject">ปฏิเสธ</span></label>
        </div>
        <label class="fld a5d-reason" data-fail-reason hidden><span data-i18n="a5s.review.failReasonRequired">เหตุผลที่ไม่ผ่าน (บังคับ)</span>
          <textarea name="fail_reason" maxlength="3000" data-i18n-placeholder="a5s.review.rejectPlaceholder" placeholder="ระบุจุดที่ต้องแก้ไข">{{ old('fail_reason') }}</textarea>
        </label>
        <label class="fld"><span data-i18n="a5s.review.adviceOptional">คำแนะนำเพิ่มเติม (ไม่บังคับ)</span>
          <textarea name="advice" maxlength="3000" data-i18n-placeholder="a5s.review.advicePlaceholder" placeholder="ข้อเสนอแนะถึงผู้รับผิดชอบ">{{ old('advice') }}</textarea>
        </label>
        <p class="a5d-note" data-i18n="a5s.review.decisionNote">ผ่าน = งานเดือนนี้ของจุดนี้เสร็จสิ้นและถูกล็อกถาวร · ไม่ผ่าน = ผู้รับผิดชอบแก้ไขและส่งใหม่ได้จนกว่ารอบจะปิด</p>
        <button type="submit" class="a5d-submit" data-i18n="a5s.review.saveDecision">บันทึกผลการประเมิน</button>
      </form>
    @endif
  </div>

  <script>
    'use strict';
    (function () {
      const reason = document.querySelector('[data-fail-reason]');
      if (!reason) return;
      document.querySelectorAll('input[name="result"]').forEach(r => {
        r.addEventListener('change', () => { reason.hidden = r.value !== 'fail' || !r.checked ? !document.querySelector('input[name="result"][value="fail"]').checked : false; });
      });
      // เผื่อ validation เด้งกลับพร้อม old input
      if (@json(old('result')) === 'fail') {
        const failRadio = document.querySelector('input[name="result"][value="fail"]');
        if (failRadio) { failRadio.checked = true; reason.hidden = false; }
      }
    })();
  </script>
@endsection
