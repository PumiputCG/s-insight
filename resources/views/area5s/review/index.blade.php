@extends('layouts.portal')

@section('title', 'ตรวจประเมิน 5S')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.review.title">ตรวจประเมิน</span>
@endsection

@php
  $tabs = [
    'pending' => ['label' => 'รอตรวจ', 'key' => 'a5s.review.tab.pending', 'color' => 'var(--moss)'],
    'failed' => ['label' => 'ไม่ผ่าน', 'key' => 'a5s.review.tab.failed', 'color' => '#d98a80'],
    'passed' => ['label' => 'ผ่าน', 'key' => 'a5s.review.tab.passed', 'color' => '#4caf7d'],
    'draft' => ['label' => 'ยังไม่ส่ง', 'key' => 'a5s.review.tab.draft', 'color' => 'var(--muted-light)'],
  ];
  $statusLabels = \App\Http\Controllers\Area5s\Area5sResponsibleController::STATUS_LABELS;
@endphp

@section('page-style')
    .a5r-wrap { display:grid; gap:1rem; width:min(100%, 80rem); margin:0 auto; }
    .a5r-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .a5r-head h2 { margin:0 0 .2rem; font-size:1.12rem; font-weight:700; color:var(--light-text); }
    .a5r-head p { margin:0; color:var(--muted-light); font-size:.83rem; }
    .a5r-round { display:inline-flex; align-items:center; gap:.4rem; padding:.32rem .8rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); color:var(--muted-light); font-size:.78rem; font-weight:600; }
    .a5r-round b { color:var(--moss); }

    .a5r-tabs { display:flex; gap:.4rem; flex-wrap:wrap; }
    .a5r-tab { display:inline-flex; align-items:center; gap:.45rem; padding:.45rem 1rem; border:1px solid var(--line-light); border-radius:999px; background:var(--menu-bg); color:var(--muted-light); font-size:.8rem; font-weight:700; text-decoration:none; }
    .a5r-tab b { font-variant-numeric:tabular-nums; }
    .a5r-tab.is-active { border-color:var(--moss); color:var(--moss); background:color-mix(in srgb, var(--moss) 10%, transparent); }

    .a5r-panel { border:1px solid var(--line-light); border-radius: 0.34rem; background:var(--panel-soft); overflow:hidden; }
    .a5r-table-wrap { overflow:auto; }
    table.a5r-table { width:100%; min-width:52rem; border-collapse:separate; border-spacing:0; font-size:.84rem; }
    .a5r-table th { position:sticky; top:0; background:var(--panel-soft); padding:.6rem .9rem; border-bottom:1px solid var(--line-light); color:var(--muted-light); font-size:.74rem; font-weight:700; text-align:left; white-space:nowrap; }
    .a5r-table td { padding:.62rem .9rem; border-bottom:1px solid var(--line-light); vertical-align:middle; }
    .a5r-table tbody tr:hover { background:var(--hover-soft); }
    .a5r-table tbody tr:last-child td { border-bottom:none; }
    .a5r-point { display:grid; gap:.1rem; min-width:0; }
    .a5r-point strong { color:var(--light-text); font-size:.88rem; }
    .a5r-point small { color:var(--muted-light); font-size:.74rem; }
    .a5r-people { color:var(--muted-light); font-size:.78rem; max-width:16rem; }
    .a5r-pill { display:inline-flex; align-items:center; gap:.35rem; padding:.18rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .a5r-pill::before { content:""; width:.4rem; height:.4rem; border-radius:50%; background:currentColor; }
    .a5r-pill.st-submitted, .a5r-pill.st-resubmitted { background:color-mix(in srgb, var(--moss) 13%, transparent); color:var(--moss); }
    .a5r-pill.st-failed { background:rgb(217 138 128 / 15%); color:#d98a80; }
    .a5r-pill.st-passed { background:rgb(76 175 125 / 14%); color:#4caf7d; }
    .a5r-pill.st-draft, .a5r-pill.st-not_started { background:var(--hover-soft); color:var(--muted-light); }
    .a5r-btn { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .9rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; font-size:.78rem; font-weight:700; text-decoration:none; white-space:nowrap; }
    .a5r-btn.secondary { background:transparent; color:var(--moss); }
    .a5r-empty { padding:2rem 1rem; text-align:center; color:var(--muted-light); font-size:.85rem; }
    .flash.success { max-width:80rem; margin:0 auto 1rem; padding:.6rem .9rem; border:1px solid var(--moss); border-radius: 0.25rem; color:var(--moss); background:rgb(91 141 239 / 12%); font-size:.85rem; }
    .flash.error { max-width:80rem; margin:0 auto 1rem; padding:.6rem .9rem; border:1px solid #d98a80; border-radius: 0.25rem; color:#d98a80; background:rgb(217 138 128 / 12%); font-size:.85rem; }
@endsection

@section('content')
  <div class="a5r-wrap">
    <div class="a5r-head">
      <div>
        <h2 data-i18n="a5s.review.queueHeading">ตรวจประเมินพื้นที่ 5ส</h2>
        <p data-i18n="a5s.review.queueHint">แสดงเฉพาะจุดที่คุณได้รับมอบหมายให้ตรวจ · เลือกผ่าน หรือไม่ผ่านพร้อมเหตุผล</p>
      </div>
      @if ($round)
        <span class="a5r-round"><span data-i18n="a5s.common.round">รอบ</span> <b>{{ $round->month }}/{{ $round->year }}</b> · <span data-i18n="a5s.common.deadline">กำหนดส่ง</span> {{ $round->deadline_at?->format('d/m/Y') ?? '-' }}</span>
      @else
        <span class="a5r-round" data-i18n="a5s.review.noActiveRound">ยังไม่มีรอบที่เปิดอยู่</span>
      @endif
    </div>

    <div class="a5r-tabs">
      @foreach ($tabs as $key => $t)
        <a class="a5r-tab nav-go {{ $tab === $key ? 'is-active' : '' }}" href="{{ route('area5s.review.index', ['tab' => $key]) }}">
          <span data-i18n="{{ $t['key'] }}">{{ $t['label'] }}</span> <b>{{ $groups[$key]->count() }}</b>
        </a>
      @endforeach
    </div>

    <div class="a5r-panel">
      <div class="a5r-table-wrap">
        <table class="a5r-table">
          <thead>
            <tr>
              <th data-i18n="a5s.common.area">จุดพื้นที่</th>
              <th data-i18n="a5s.common.assignees">ผู้รับผิดชอบ</th>
              <th data-i18n="a5s.common.cards">รายการ</th>
              <th data-i18n="a5s.common.sentAt">ส่งเมื่อ</th>
              <th data-i18n="a5s.common.submitCount">ครั้งที่ส่ง</th>
              <th data-i18n="a5s.common.status">สถานะ</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($groups[$tab] as $task)
              <tr>
                <td>
                  <div class="a5r-point">
                    <strong><span data-i18n="a5s.common.point">จุด</span> {{ $task->point_code }} · {{ $task->point_name }}</strong>
                    <small>{{ $task->layout_name }}</small>
                  </div>
                </td>
                @php
                  $assigneeHtml = collect($task->assignees_json)->map(function ($person) {
                    $name = $person['name'] ?? ($person['code'] ?? '-');
                    $nameTh = $person['name_th'] ?? $name;
                    $nameEn = $person['name_en'] ?? $nameTh;
                    $nameMy = $person['name_my'] ?? $nameEn;
                    return '<span data-a5s-name-th="'.e($nameTh).'" data-a5s-name-en="'.e($nameEn).'" data-a5s-name-my="'.e($nameMy).'">'.e($name).'</span>';
                  })->implode(', ');
                @endphp
                <td><span class="a5r-people">{!! $assigneeHtml ?: '-' !!}</span></td>
                <td>{{ number_format($task->cards_count) }}</td>
                <td>{{ $task->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                <td>{{ number_format($task->submit_count) }}</td>
                <td><span class="a5r-pill st-{{ $task->status }}" data-i18n="a5s.status.{{ $task->status }}">{{ $statusLabels[$task->status] ?? $task->status }}</span></td>
                <td style="text-align:right">
                  <a class="a5r-btn nav-go {{ in_array($task->status, ['submitted', 'resubmitted'], true) ? '' : 'secondary' }}"
                     href="{{ route('area5s.review.show', $task) }}">
                    @if (in_array($task->status, ['submitted', 'resubmitted'], true))
                      <span data-i18n="a5s.review.check">ตรวจ</span>
                    @else
                      <span data-i18n="a5s.review.viewDetail">ดูรายละเอียด</span>
                    @endif
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="a5r-empty" data-i18n="a5s.review.emptyCategory">ไม่มีงานในหมวดนี้</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
