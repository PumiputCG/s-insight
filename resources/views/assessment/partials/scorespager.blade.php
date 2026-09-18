{{-- ตัวแบ่งหน้าตาราง (ใช้ร่วม 1.2 และ 4.1) — $openQuery = param เพื่อเปิด section เดิมค้างไว้หลังเปลี่ยนหน้า --}}
@php
  $openQuery = $openQuery ?? [];
  $pagerTarget = $pagerTarget ?? '';
@endphp
@once
  <style>
    .asm-page { display:flex; align-items:center; justify-content:flex-end; gap:.55rem; margin:.6rem 0 .2rem; color:var(--muted-light); font-size:.8rem; flex-wrap:wrap; }
    .asm-page-link { padding:.36rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; text-decoration:none; font-weight:600; cursor:pointer; }
    .asm-page-link:not(.is-disabled):hover { border-color:var(--moss); color:var(--moss); }
    .asm-page-link.is-disabled { opacity:.45; pointer-events:none; }
    .asm-page select { padding:.32rem .5rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; font-size:.78rem; }
  </style>
@endonce
<div class="asm-page" @if($pagerTarget !== '') data-pager-target="{{ $pagerTarget }}" @endif>
  <span data-i18n="assessment.common.perPage">ต่อหน้า</span>
  <select data-page-select>
    @foreach ([100, 200, 500, 1000] as $pp)
      <option value="{{ request()->fullUrlWithQuery(array_merge($openQuery, ['per_page' => $pp, 'page' => 1])) }}" @selected(($perPage ?? 100) === $pp)>{{ $pp }}</option>
    @endforeach
  </select>
  <a class="asm-page-link {{ $employees->onFirstPage() ? 'is-disabled' : '' }}" @if (! $employees->onFirstPage()) href="{{ $employees->appends($openQuery)->previousPageUrl() }}" @endif data-i18n="common.prevPage">ก่อนหน้า</a>
  <span><span data-i18n="assessment.common.page">หน้า</span> {{ number_format($employees->currentPage()) }} / {{ number_format($employees->lastPage()) }}</span>
  <a class="asm-page-link {{ $employees->hasMorePages() ? '' : 'is-disabled' }}" @if ($employees->hasMorePages()) href="{{ $employees->appends($openQuery)->nextPageUrl() }}" @endif data-i18n="common.nextPage">ถัดไป</a>
</div>
