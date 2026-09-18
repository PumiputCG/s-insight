{{-- กล่องกรอกคำอธิบาย 3 ภาษา ของ (คอลัมน์ × ระดับตำแหน่ง) — บันทึกอัตโนมัติตอนออกจากช่อง
     $boxId, $lv, $note, $where ('main' = หัวข้อหลัก | 'sub' = คอลัมน์ย่อย) --}}
@php
  $on = $note === null ? true : (bool) $note->is_visible;
  $vals = ['th' => $note->desc_th ?? '', 'en' => $note->desc_en ?? '', 'my' => $note->desc_my ?? ''];
  $ph = [
    'th' => ($where ?? 'main') === 'main'
      ? 'คำอธิบายของหัวข้อนี้ — จะขึ้นใต้ชื่อหัวข้อในแบบฟอร์มจริง'
      : 'คำอธิบายของคอลัมน์นี้ — จะขึ้นใต้ชื่อคอลัมน์ในแบบฟอร์มจริง',
    'en' => 'English (optional)',
    'my' => 'မြန်မာ (optional)',
  ];
@endphp
<div class="qn-editor {{ $on ? '' : 'qn-note-off' }}" data-qn-note>
  <div class="qn-editor-bar">
    <span class="qn-editor-tag">✎ คำอธิบายที่จะแสดงตรงนี้</span>
    @include('assessment.partials.qneye', ['boxId' => $boxId, 'lv' => $lv, 'note' => $note])
    <div class="qn-langs" data-qn-langs role="group" aria-label="เลือกภาษาคำอธิบาย">
      <button type="button" class="qn-lang-btn is-active" data-qn-lang="th">TH</button>
      <button type="button" class="qn-lang-btn" data-qn-lang="en">EN</button>
      <button type="button" class="qn-lang-btn" data-qn-lang="my">MY</button>
    </div>
  </div>
  @foreach (['th', 'en', 'my'] as $lang)
    <div class="qn-desc" data-qn-desc="{{ $lang }}" @if ($lang !== 'th') hidden @endif>
      <textarea data-qn-field="desc_{{ $lang }}"
                data-box="{{ $boxId }}"
                data-level="{{ $lv }}"
                rows="2"
                placeholder="{{ $ph[$lang] }}">{{ $vals[$lang] }}</textarea>
    </div>
  @endforeach
</div>
