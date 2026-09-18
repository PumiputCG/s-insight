{{-- สวิตช์เปิด/ปิดว่าจะให้ผู้ประเมินเห็นคำอธิบายของคอลัมน์นี้หรือไม่ (ปิด = ซ่อน ไม่ได้ลบข้อความ) --}}
@php $on = $note === null ? true : (bool) $note->is_visible; @endphp
<button type="button"
        class="qn-eye {{ $on ? 'is-on' : '' }}"
        data-qn-eye
        data-box="{{ $boxId }}"
        data-level="{{ $lv }}"
        data-on-text="แสดง"
        data-off-text="ซ่อน"
        aria-pressed="{{ $on ? 'true' : 'false' }}"
        title="เปิด/ปิดการแสดงคำอธิบายนี้ให้ผู้ประเมิน">
  <span class="dot" aria-hidden="true"></span>
  <span data-qn-eye-text>{{ $on ? 'แสดง' : 'ซ่อน' }}</span>
</button>
