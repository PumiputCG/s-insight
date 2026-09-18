{{-- แถบแท็บบนของกลุ่มการประเมินพนักงาน: จัดการ · กำหนดคำถาม · ผลลัพธ์
     ท้ายแถว = ป้ายรอบที่เปิด — ส่ง roundLabel => false เพื่อซ่อน
     (หน้าผลลัพธ์ซ่อนไว้ แล้ววางการ์ดสรุป partials.lvsummary เองใน .res-head ให้อยู่แถวเดียวกับปุ่มปี) --}}
@php($showRoundLabel = $roundLabel ?? true)
<style>
  .asm-tabs { display:flex; align-items:flex-start; gap:.45rem; flex-wrap:wrap; margin:0 0 1.1rem; }
  .asm-tab { padding:.45rem 1rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--panel-soft); color:inherit; text-decoration:none; font-size:.85rem; font-weight:600; transition:border-color .15s, background-color .15s; }
  .asm-tab:hover { border-color:var(--moss); }
  .asm-tab.is-active { background:var(--moss); border-color:var(--moss); color:#fff; }

  .asm-tab-round { margin-left:auto; color:var(--muted-light); font-size:.8rem; }
  .asm-tab-round b { color:var(--moss); }
</style>
<div class="asm-tabs">
  <a href="{{ route('assessment.scores.index') }}" class="asm-tab nav-go {{ request()->routeIs('assessment.scores.*') ? 'is-active' : '' }}" data-i18n="assessment.common.manage">จัดการ</a>
  <a href="{{ route('assessment.questions.index') }}" class="asm-tab nav-go {{ request()->routeIs('assessment.questions.*') ? 'is-active' : '' }}" data-i18n="assessment.common.questions">กำหนดคำถาม</a>
  <a href="{{ route('assessment.results.index') }}" class="asm-tab nav-go {{ request()->routeIs('assessment.results.*') ? 'is-active' : '' }}" data-i18n="assessment.common.results">ผลลัพธ์</a>

  @if ($showRoundLabel)
    <span class="asm-tab-round">
      @if (($openRound ?? null))
        <span data-i18n="assessment.self.openRound">รอบที่เปิด</span>: <b>{{ $openRound->name }}</b> ({{ $openRound->year }})
      @else
        <span style="color:#d98a80" data-i18n="assessment.common.noOpenRound">ยังไม่เปิดรอบ</span>
      @endif
    </span>
  @endif
</div>
