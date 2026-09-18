<span class="lv-chip" data-code="{{ $p['code'] }}">
  <span class="nm">{{ $p['name'] }} <small>({{ $p['count'] }})</small></span>
  <select data-set-level title="เลือกระดับ">
    <option value="" {{ $p['level'] === null ? 'selected' : '' }}>–</option>
    {{-- ระดับ 0 (default) + ระดับ 1..N ที่ admin เพิ่มเอง --}}
    @foreach (range(0, max(0, (int) ($maxLevel ?? 0))) as $l)
      <option value="{{ $l }}" {{ (string) $p['level'] === (string) $l ? 'selected' : '' }}>{{ $l }}</option>
    @endforeach
  </select>
</span>
