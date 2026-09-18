@extends('layouts.portal')

@section('title', 'จัดการพื้นที่ 5S')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.manage.title">จัดการพื้นที่</span>
@endsection

@section('page-style')
    .a5z-wrap { display:grid; gap:.65rem; width:min(100%, 84rem); margin:0 auto; }
    .a5z-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:inherit; cursor:pointer; font-size:.8rem; font-weight:650; text-decoration:none; }
    .a5z-btn:hover { border-color:var(--moss); color:var(--moss); }

    .a5z-empty { display:grid; gap:.5rem; place-items:center; padding:3rem 1.5rem; text-align:center; border:1px dashed var(--line-strong); border-radius: 0.32rem; color:var(--muted-light); font-size:.86rem; }
    .a5z-unmapped { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.7rem 1rem; border:1px dashed color-mix(in srgb, #c8964a 50%, var(--line-light)); border-radius: 0.28rem; background:rgb(200 150 74 / 8%); color:#c8964a; font-size:.82rem; font-weight:650; flex-wrap:wrap; }

    {{-- บล็อกภาพบริษัท → เผยอาคาร ใช้ CSS ชุดเดียวกับหน้า /area5s (Manager 2026-08-27) --}}
    @include('area5s.partials.plan-reveal-style')
@endsection

@section('content')
  <div class="a5z-wrap">
    @if (! $plan)
      <div class="a5z-empty">
        @if ($isA5sAdmin)
          <span data-i18n="a5s.manage.noPlanAdmin">ยังไม่มีแปลนบริษัท — ไปที่ "แก้ไขแปลนบริษัท" เพื่ออัปโหลดภาพและวาดโซน</span>
          <a class="a5z-btn nav-go" href="{{ route('area5s.plan.index') }}" data-i18n="a5s.plan.title">แปลนบริษัท</a>
        @else
          <span data-i18n="a5s.manage.noPlanAllocator">ยังไม่มีแปลนบริษัท — รอ Admin ตั้งค่าอาคาร/โซนก่อน</span>
        @endif
      </div>
    @else
      {{-- แสดงผลชุดเดียวกับหน้า /area5s: ภาพบริษัท → กดแล้วเผยอาคาร
           ต่างกันแค่กดการ์ดแล้วเข้าหน้าจัดการพื้นที่ของอาคารนั้น (Manager 2026-08-27) --}}
      {{-- ปุ่ม "แก้ไขแปลนบริษัท" คงไว้ตามเดิม (Admin เท่านั้น) — วางในแถวหัวข้อฝั่งขวา --}}
      @include('area5s.partials.plan-reveal', $isA5sAdmin ? [
        'revealActionUrl' => route('area5s.plan.index'),
        'revealActionLabel' => 'แก้ไขแปลนบริษัท',
        'revealActionI18n' => 'a5s.manage.editPlan',
      ] : [])

      @if (($planAreas ?? collect())->isEmpty())
        <div class="a5z-empty" data-i18n="a5s.manage.noZoneYet">ยังไม่มีโซนบนแปลนนี้</div>
      @endif

      @if ($unmappedCount > 0)
        <a class="a5z-unmapped nav-go" href="{{ route('area5s.manage.unmapped') }}">
          <span><span data-i18n="a5s.manage.unmappedHint">มีพื้นที่เดิม</span> {{ $unmappedCount }} <span data-i18n="a5s.manage.unmappedHint2">รายการยังไม่ได้ผูกชั้น — คลิกเพื่อจัดการ</span></span>
          <span aria-hidden="true">&rarr;</span>
        </a>
      @endif
    @endif
  </div>
@endsection
