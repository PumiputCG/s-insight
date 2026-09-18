{{-- ปุ่มซ่อน/แสดง "ทั้งคอลัมน์" ให้ผู้ประเมิน — ไอคอนตา (ขีดทับ = ซ่อนอยู่)
     $key = box id (ตัวเลข) หรือ 'slot:total' · $note --}}
@php $on = $note === null ? true : (bool) $note->is_visible; @endphp
<button type="button"
        class="qn-hide {{ $on ? '' : 'is-hidden' }}"
        data-qn-hide="{{ $key }}"
        aria-pressed="{{ $on ? 'false' : 'true' }}"
        aria-label="ซ่อนจากผู้ประเมิน"
        title="{{ $on ? 'กดเพื่อซ่อนจากผู้ประเมิน' : 'ซ่อนอยู่ — กดเพื่อแสดง' }}">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
    <line class="qn-hide-slash" x1="3" y1="21" x2="21" y2="3" />
  </svg>
</button>
