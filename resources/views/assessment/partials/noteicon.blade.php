{{-- ไอคอนเปิดอ่านคำอธิบาย — ขึ้นเฉพาะคอลัมน์ที่มีข้อความ
     $desc = ['th' => ..., 'en' => ..., 'my' => ...] · $title = ชื่อคอลัมน์
     เก็บครบ 3 ภาษาไว้ที่ปุ่ม แล้ว JS เลือกตามธงตอนเปิด modal --}}
@if (! empty($desc))
  <button type="button" class="paper-note-btn"
          data-note-open
          data-note-title="{{ $title }}"
          data-note-th="{{ $desc['th'] ?? '' }}"
          data-note-en="{{ $desc['en'] ?? ($desc['th'] ?? '') }}"
          data-note-my="{{ $desc['my'] ?? ($desc['en'] ?? ($desc['th'] ?? '')) }}"
          aria-label="อ่านคำอธิบาย {{ $title }}"
          title="อ่านคำอธิบาย">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" /><path d="M12 11.5v4.5M12 8h.01" />
    </svg>
  </button>
@endif
