@extends('layouts.portal')

@section('title', 'ประเมินตัวเอง')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="nav.asmSelf">การประเมินตัวเอง</span>
@endsection

@section('page-style')
  /* Paper visual language shared with the employee evaluation form. */
  .self-form-page { display:grid; gap:.9rem; width:min(100%, 56rem); margin:0 auto; }
  .self-paper { padding:2.2rem 2.6rem 2.4rem; border:1px solid #e3dfd2; border-radius:.25rem; background:#fdfcf8; color:#26251f; box-shadow:0 14px 40px rgb(0 0 0 / 16%); }
  .self-paper-head { padding-bottom:1.1rem; border-bottom:2px solid #26251f; text-align:center; }
  .self-paper-logo { display:block; width:3.1rem; height:3.1rem; margin:0 auto .45rem; object-fit:contain; }
  .self-paper-title { margin:0 0 .2rem; color:#26251f; font-size:1.22rem; font-weight:800; letter-spacing:.02em; line-height:1.35; text-wrap:balance; }
  .self-paper-meta { display:flex; align-items:center; justify-content:center; gap:.35rem .8rem; flex-wrap:wrap; margin:0; color:#6f6b5e; font-size:.82rem; }
  .self-paper-meta span + span::before { content:'·'; margin-right:.8rem; color:#9d9785; }
  .self-profile { display:grid; grid-template-columns:7rem minmax(0, 1fr); gap:1.75rem; align-items:stretch; padding:1rem .2rem 1.1rem; border-bottom:1px solid #26251f; }
  .self-avatar-button, .self-avatar-fallback { width:7rem; min-height:7rem; aspect-ratio:1; border:1.5px solid #9d9785; border-radius:.28rem; background:#f1eee4; overflow:hidden; }
  .self-avatar-button { padding:0; cursor:zoom-in; }
  .self-avatar-button:focus-visible { outline:2px solid #5b7343; outline-offset:3px; }
  .self-avatar-button img { display:block; width:100%; height:100%; object-fit:cover; }
  .self-avatar-fallback { display:grid; place-items:center; color:#5b7343; font-size:1.35rem; font-weight:700; }
  .self-profile-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.7rem 1.5rem; align-content:center; }
  .self-profile-field { min-width:0; padding:0 0 .42rem; border-bottom:1px dashed #d8d2bf; }
  .self-profile-field.wide { grid-column:span 2; }
  .self-profile-label { display:block; color:#6f6b5e; font-size:.78rem; font-weight:700; line-height:1.3; }
  .self-profile-value { display:block; margin-top:.12rem; overflow:hidden; text-overflow:ellipsis; color:#26251f; font-size:.92rem; font-weight:400; white-space:nowrap; }
  .self-paper-note { display:flex; align-items:flex-start; gap:.55rem; padding:1rem .2rem; border-bottom:2px solid #26251f; color:#4a463c; font-size:.82rem; line-height:1.65; }
  .self-paper-note > span { color:#6f6b5e; }
  .self-paper-note b { color:#26251f; font-size:.9rem; }
  .self-paper-alert { margin:1rem .2rem 0; padding:.7rem .8rem; border:1px solid #d9d4c3; border-radius:.25rem; font-size:.8rem; }
  .self-paper-alert.success { border-color:#a9c6b6; background:#f3faf6; color:#275d43; }
  .self-paper-alert.error { border-color:#d8aaa5; background:#fff7f6; color:#9a3f37; }
  .self-question-list { display:grid; gap:1.35rem; padding:1.2rem .2rem 0; }
  .self-question { padding-top:1rem; border-top:1px solid #26251f; scroll-margin-top:6rem; }
  .self-question:first-child { padding-top:0; border-top:0; }
  .self-question + .self-question { border-top-style:dashed; border-top-color:#d8d2bf; }
  .self-question.is-missing { border-color:#b3543c; outline:2px solid rgb(179 84 60 / 12%); outline-offset:.4rem; }
  .self-question-top { display:grid; grid-template-columns:3.2rem minmax(0, 1fr) auto; align-items:start; gap:.75rem; padding:0 0 .75rem; }
  .self-question-no { color:#6f6b5e; font-size:.8rem; font-weight:700; font-variant-numeric:tabular-nums; }
  .self-question-text { color:#26251f; font-size:.9rem; font-weight:800; line-height:1.55; white-space:pre-wrap; }
  .self-question-percent { min-width:4.3rem; color:#3c5f2c; font-size:.84rem; font-weight:700; text-align:right; font-variant-numeric:tabular-nums; }
  .self-options { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem .65rem; padding:0 0 .2rem 3.95rem; }
  /* ไม่มีกรอบ — ใช้พื้นจาง ๆ ตอนชี้/เลือกแทน ให้อ่านต่อเนื่องเป็นแผ่นกระดาษ */
  .self-option { display:grid; grid-template-columns:auto minmax(0, 1fr); align-items:center; gap:.48rem; min-height:2.8rem; padding:.48rem .58rem; border:0; border-radius:.28rem; background:transparent; cursor:pointer; transition:background-color .15s ease, color .15s ease; }
  .self-option:hover { background:rgb(91 115 67 / 8%); color:#3c5f2c; }
  .self-option.is-selected { background:#eef1e8; color:#3c5f2c; font-weight:700; }
  .self-option input { width:1rem; height:1rem; margin:0; accent-color:#5b7343; }
  .self-option-copy { min-width:0; color:inherit; font-size:.78rem; line-height:1.4; }
  .self-paper-footer { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:1.8rem; padding:1rem 0 0; border-top:2px solid #26251f; background:transparent; }
  .self-total { display:flex; align-items:baseline; gap:.45rem; color:#6f6b5e; font-size:.77rem; }
  .self-total strong { color:#3c5f2c; font-size:1.05rem; font-variant-numeric:tabular-nums; }
  .self-submit { min-width:10rem; min-height:2.45rem; padding:.55rem 1.4rem; border:1px solid #5b7343; border-radius:.25rem; background:#5b7343; color:#fff; cursor:pointer; font:inherit; font-size:.82rem; font-weight:700; }
  .self-submit:hover { filter:brightness(1.06); }
  .self-submit:focus-visible { outline:2px solid rgb(91 115 67 / 30%); outline-offset:3px; }
  .self-submit:disabled { opacity:.45; cursor:not-allowed; }
  .self-empty-state { margin-top:1.4rem; padding:1.4rem; border:1px dashed #c9c4b2; border-radius:.25rem; color:#6f6b5e; text-align:center; font-size:.84rem; }
  @media print {
    .self-paper { box-shadow:none; }
  }
  @media (max-width:720px) {
    .self-paper { padding:1.3rem 1.1rem 1.6rem; }
    .self-profile { grid-template-columns:5rem minmax(0, 1fr); gap:1rem; }
    .self-avatar-button, .self-avatar-fallback { width:5rem; min-height:5rem; }
    .self-profile-grid { grid-template-columns:1fr 1fr; }
    .self-profile-field.wide { grid-column:span 2; }
    .self-question-top { grid-template-columns:2.7rem minmax(0, 1fr); }
    .self-question-percent { grid-column:2; text-align:left; }
    .self-options { grid-template-columns:1fr; padding-left:0; }
    .self-paper-footer { align-items:stretch; flex-direction:column; }
    .self-submit { width:100%; }
  }
  @media (max-width:520px) {
    .self-profile { grid-template-columns:1fr; }
    .self-profile-grid { grid-template-columns:1fr; }
    .self-profile-field.wide { grid-column:auto; }
  }
@endsection

@section('page-script')
  <script>
    'use strict';

    (() => {
      const form = document.querySelector('[data-self-form]');
      if (!form) return;
      const questions = Array.from(form.querySelectorAll('[data-self-question]'));
      const totalNode = form.querySelector('[data-self-total]');
      const progressNode = form.querySelector('[data-self-progress]');
      const submitButton = form.querySelector('[data-self-submit]');

      const formatPercent = (value) => {
        if (value === null || Number.isNaN(value)) return 'N/A';
        return (Math.round(value * 100) / 100).toLocaleString(undefined, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 2,
        }) + '%';
      };

      const refresh = () => {
        let answered = 0;
        let earned = 0;
        let possible = 0;

        questions.forEach((question) => {
          const selected = question.querySelector('input[type="radio"]:checked');
          const percentNode = question.querySelector('[data-question-percent]');
          question.querySelectorAll('.self-option').forEach((option) => {
            option.classList.toggle('is-selected', option.contains(selected));
          });
          question.classList.remove('is-missing');

          if (!selected) {
            percentNode.textContent = '—';
            return;
          }

          answered++;
          if (selected.dataset.na === '1') {
            percentNode.textContent = 'N/A';
            return;
          }

          const score = Number(selected.dataset.score);
          const full = Number(question.dataset.fullScore);
          const percent = full > 0 ? (score / full) * 100 : 0;
          earned += score;
          possible += full;
          percentNode.textContent = formatPercent(percent);
        });

        progressNode.textContent = answered + '/' + questions.length;
        totalNode.textContent = possible > 0 ? formatPercent((earned / possible) * 100) : 'N/A';
        submitButton.disabled = questions.length === 0;
      };

      form.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', refresh);
      });

      form.addEventListener('submit', (event) => {
        const missing = questions.find((question) => !question.querySelector('input[type="radio"]:checked'));
        if (!missing) return;
        event.preventDefault();
        missing.classList.add('is-missing');
        missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
        missing.querySelector('input[type="radio"]')?.focus();
      });

      refresh();
    })();
  </script>
@endsection

@section('content')
  @php
    $employeeNameTh = $employee?->fullNameTh() ?: '-';
    $employeeNameEn = $employee?->fullNameEn() ?: $employeeNameTh;
    $initial = mb_substr(trim((string) ($employee?->name_th ?: $employee?->name_en ?: '?')), 0, 1);
    $submittedPercent = $submission?->total_percent;
  @endphp

  <div class="self-form-page">
    <section class="self-paper">
      <header class="self-paper-head">
        <img class="self-paper-logo" src="{{ asset('assets/assessment/form-logo.png') }}" alt="" aria-hidden="true">
        <h1 class="self-paper-title" data-i18n="assessment.self.formTitle">ประเมินตัวเอง</h1>
        <p class="self-paper-meta">
          <span><span data-i18n="assessment.self.openRound">รอบที่เปิด</span> {{ $selfRound->name }}</span>
          <span><span data-i18n="assessment.self.year">ปี</span> {{ $selfRound->year }}</span>
          @if ($submission?->submitted_at)
            <span><span data-i18n="assessment.self.lastSaved">บันทึกล่าสุด</span> {{ $submission->submitted_at->format('d/m/Y H:i') }}</span>
          @endif
        </p>
      </header>

      <div class="self-profile">
        @if ($avatarUrl)
          <button type="button" class="self-avatar-button" data-image-preview data-image-src="{{ $avatarUrl }}" data-image-alt="{{ $employeeNameTh }}" aria-label="ดูรูปโปรไฟล์">
            <img src="{{ $avatarUrl }}" alt="{{ $employeeNameTh }}">
          </button>
        @else
          <div class="self-avatar-fallback" aria-hidden="true">{{ $initial }}</div>
        @endif

        <div class="self-profile-grid">
          <div class="self-profile-field">
            <span class="self-profile-label" data-i18n="assessment.employeeCode">รหัสพนักงาน</span>
            <span class="self-profile-value">{{ $employee?->employee_code ?? '-' }}</span>
          </div>
          <div class="self-profile-field wide">
            <span class="self-profile-label" data-i18n="assessment.fullName">ชื่อ-สกุล</span>
            <span class="self-profile-value" data-loc-th="{{ $employeeNameTh }}" data-loc-en="{{ $employeeNameEn }}" data-loc-my="{{ $employeeNameEn }}">{{ $employeeNameTh }}</span>
          </div>
          <div class="self-profile-field">
            <span class="self-profile-label" data-i18n="assessment.form.position">ตำแหน่ง</span>
            <span class="self-profile-value"
                  data-loc-th="{{ $employee?->job_th ?: ($employee?->job_en ?? '-') }}"
                  data-loc-en="{{ $employee?->job_en ?: ($employee?->job_th ?? '-') }}"
                  data-loc-my="{{ $employee?->job_en ?: ($employee?->job_th ?? '-') }}">
              {{ $employee?->job_th ?: ($employee?->job_en ?? '-') }}
            </span>
          </div>
          <div class="self-profile-field">
            <span class="self-profile-label" data-i18n="assessment.form.department">แผนก</span>
            <span class="self-profile-value"
                  data-loc-th="{{ $employee?->deptThClean() ?: ($employee?->dept_en ?? '-') }}"
                  data-loc-en="{{ $employee?->dept_en ?: ($employee?->deptThClean() ?? '-') }}"
                  data-loc-my="{{ $employee?->dept_en ?: ($employee?->deptThClean() ?? '-') }}">
              {{ $employee?->deptThClean() ?: ($employee?->dept_en ?? '-') }}
            </span>
          </div>
          {{-- ระดับไม่แสดงในฟอร์มของผู้ทำแบบประเมิน (ใช้ภายในเพื่อเลือกชุดคำถามเท่านั้น) --}}
        </div>
      </div>

      <div class="self-paper-note">
        <span aria-hidden="true">ⓘ</span>
        <div>
          <b data-i18n="assessment.self.formInstructionTitle">วิธีประเมิน</b><br>
          <span data-i18n="assessment.self.formInstruction">เลือกคำตอบให้ครบทุกข้อ ระบบจะเทียบคะแนนที่เลือกกับคะแนนเต็มที่ Admin กำหนดให้แต่ละข้อ และไม่นำ N/A มาคำนวณ</span>
        </div>
      </div>

      @if (session('self_success'))
        <div class="self-paper-alert success">{{ session('self_success') }}</div>
      @endif
      @if ($errors->any())
        <div class="self-paper-alert error">{{ $errors->first() }}</div>
      @endif

      @if ($selfLevel === null)
        <div class="self-empty-state">
          <b data-i18n="assessment.self.levelMissing">Admin ยังไม่ได้กำหนดระดับตำแหน่งของคุณ</b>
        </div>
      @elseif ($questions->isEmpty())
        <div class="self-empty-state">
          <b data-i18n="assessment.self.noFormQuestions">ระดับของคุณยังไม่มีคำถามสำหรับประเมินตัวเอง</b>
        </div>
      @else
        <form method="POST" action="{{ route('assessment.self.form.save') }}" data-self-form>
          @csrf
          @method('PUT')
          <div class="self-question-list">
            @foreach ($questions as $question)
              @php
                $fullScore = (float) $question->full_score;
                $selectedChoice = (int) old('answers.'.$question->id, $answerMap->get($question->id, 0));
              @endphp
              <article class="self-question" data-self-question data-full-score="{{ (float) $fullScore }}">
                <div class="self-question-top">
                  <span class="self-question-no"><span data-i18n="assessment.self.questionShort">ข้อที่</span> {{ $loop->iteration }}</span>
                  <span class="self-question-text"
                        data-loc-th="{{ $question->q_th }}"
                        data-loc-en="{{ $question->q_en }}"
                        data-loc-my="{{ $question->q_my }}">{{ $question->q_th }}</span>
                  {{-- คะแนนรายข้อไม่แสดงให้ผู้ทำแบบประเมินเห็น --}}
                  <span class="self-question-percent" data-question-percent hidden>—</span>
                </div>
                <div class="self-options">
                  @foreach ($question->choices as $choice)
                    <label class="self-option">
                      <input type="radio"
                             name="answers[{{ $question->id }}]"
                             value="{{ $choice->id }}"
                             data-score="{{ $choice->is_na ? '' : (float) $choice->score }}"
                             data-na="{{ $choice->is_na ? '1' : '0' }}"
                             @checked($selectedChoice === (int) $choice->id)
                             required>
                      <span class="self-option-copy"
                            data-loc-th="{{ $choice->choice_th }}"
                            data-loc-en="{{ $choice->choice_en }}"
                            data-loc-my="{{ $choice->choice_my }}">{{ $choice->choice_th }}</span>
                      {{-- ไม่โชว์ป้าย N/A และคะแนนรายตัวเลือกให้ผู้ทำแบบประเมิน --}}
                    </label>
                  @endforeach
                </div>
              </article>
            @endforeach
          </div>

          <footer class="self-paper-footer">
            <div>
              <div class="self-total">
                <span data-i18n="assessment.self.answered">ตอบแล้ว</span>
                <strong data-self-progress>0/{{ $questions->count() }}</strong>
              </div>
              {{-- คะแนนรวมไม่แสดงให้ผู้ทำแบบประเมินเห็น (ดูได้ที่หน้าผลลัพธ์ของ admin) --}}
              <strong data-self-total hidden>{{ $submittedPercent === null ? '—' : ((float) $submittedPercent).'%' }}</strong>
            </div>
            <button type="submit" class="self-submit" data-self-submit data-i18n="assessment.self.submitForm">บันทึกการประเมินตัวเอง</button>
          </footer>
        </form>
      @endif
    </section>
  </div>
@endsection
