{{-- ไอคอนคำอธิบาย (ฝั่ง admin) — กดเปิด modal แก้ไข · สีบอกสถานะในตัว
     เขียว = เปิดแสดง · แดง = มีข้อความแต่ปิดไว้ · เทา = ยังไม่มีข้อความ
     $key = box id (ตัวเลข) หรือ 'slot:total' สำหรับจุดพิเศษ --}}
@php
  $hasNote = $note !== null && $note->hasContent();
  $isOn = $note === null ? true : (bool) $note->is_visible;
@endphp
<button type="button"
        class="qn-i {{ $hasNote && $isOn ? 'has-note' : '' }} {{ $hasNote && ! $isOn ? 'is-off' : '' }}"
        data-qn-icon="{{ $key }}"
        aria-label="คำอธิบาย"
        title="คำอธิบาย">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
    <circle cx="12" cy="12" r="9" /><path d="M12 11.5v4.5M12 8h.01" />
  </svg>
</button>
