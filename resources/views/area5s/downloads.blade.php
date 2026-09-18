@extends('layouts.portal')

@section('title', 'ดาวน์โหลดเอกสาร · 5S AREA')

@section('topbar-title')
  <span class="tt-kicker">5S AREA</span>
  <span class="tt-title" data-i18n="a5s.downloads.title">ดาวน์โหลดเอกสาร</span>
@endsection

@section('page-style')
    .a5d-wrap { display:grid; place-items:center; min-height:min(58vh, 30rem); }
    .a5d-card { display:grid; gap:1rem; place-items:center; width:min(100%, 34rem); padding:2.4rem 1.6rem; border:1px solid var(--line-light); border-radius: 0.4rem; background:var(--panel-soft); text-align:center; }
    .a5d-icon { width:3.4rem; height:3.4rem; display:grid; place-items:center; border-radius:50%; background:rgb(91 141 239 / 14%); }
    .a5d-icon svg { width:1.8rem; height:1.8rem; fill:none; stroke:var(--moss); stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .a5d-card h2 { margin:0; color:var(--light-text); font-size:1.02rem; }
    .a5d-card p { margin:0; color:var(--muted-light); font-size:.82rem; line-height:1.6; }
    .a5d-controls { display:flex; align-items:center; justify-content:center; gap:.6rem; flex-wrap:wrap; margin-top:.2rem; }
    .a5d-controls select { min-height:2.35rem; padding:.45rem .7rem; border:1px solid var(--line-light); border-radius: 0.25rem; background:var(--menu-bg); color:var(--light-text); font-size:.84rem; }
    .a5d-controls select:focus { outline:none; border-color:var(--moss); }
    .a5d-download { display:inline-flex; align-items:center; gap:.45rem; min-height:2.35rem; padding:.5rem 1.15rem; border:1px solid var(--moss); border-radius: 0.25rem; background:var(--moss); color:#fff; font-size:.84rem; font-weight:750; text-decoration:none; cursor:pointer; }
    .a5d-download:hover { filter:brightness(1.06); }
    .a5d-download svg { width:1rem; height:1rem; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .a5d-empty { color:var(--muted-light); font-size:.84rem; }
@endsection

@section('content')
  <div class="a5d-wrap">
    <div class="a5d-card">
      <span class="a5d-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
      </span>
      <h2 data-i18n="a5s.downloads.title">ดาวน์โหลดเอกสาร</h2>
      <p data-i18n="a5s.downloads.hint">ผลตรวจ 5ส ของรอบเดือนที่เลือก — ไฟล์ Excel (.xlsx)</p>

      @if (! $round)
        <span class="a5d-empty" data-i18n="a5s.downloads.noRound">ยังไม่มีรอบเดือนในระบบ — เปิดรอบแรกได้ที่เมนู "รายเดือน"</span>
      @else
        <div class="a5d-controls">
          <form method="GET" action="{{ route('area5s.downloads.index') }}">
            <select name="round" onchange="this.form.submit()" data-i18n-aria="a5s.downloads.selectRound" aria-label="เลือกรอบเดือน">
              @foreach ($rounds as $r)
                <option value="{{ $r->id }}" data-round-month="{{ $r->month }}" data-round-year="{{ $r->year }}" data-round-open="{{ $r->isOpen() ? '1' : '0' }}" {{ $r->id === $round->id ? 'selected' : '' }}>
                  {{ $monthNames[$r->month] ?? $r->month }} {{ $r->year }}{{ $r->isOpen() ? ' · เปิดอยู่' : '' }}
                </option>
              @endforeach
            </select>
          </form>
          <a class="a5d-download" href="{{ route('area5s.downloads.export', ['round' => $round->id]) }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
            <span data-i18n="a5s.downloads.downloadExcel">ดาวน์โหลด Excel</span>
          </a>
          {{-- ดาวน์โหลดทั้งปี (ทุกครั้งตรวจ + คะแนน%) (Manager 2026-07-24) --}}
          <a class="a5d-download is-year" href="{{ route('area5s.downloads.export.year', ['year' => $round->year]) }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
             <span data-i18n="a5s.downloads.downloadYear" data-a5d-year-label data-year="{{ $round->year }}">ดาวน์โหลดทั้งปี {{ $round->year }}</span>
          </a>
        </div>
      @endif
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    (function area5sDownloadsLanguage() {
      'use strict';

      function dictionary() {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang]) || {};
      }

      function paint() {
        var dict = dictionary();
        document.querySelectorAll('[data-round-month]').forEach(function (option) {
          var month = dict['a5s.month.' + option.dataset.roundMonth] || option.dataset.roundMonth;
          var open = option.dataset.roundOpen === '1' ? ' · ' + (dict['a5s.rounds.stateOpen'] || 'เปิดอยู่') : '';
          option.textContent = month + ' ' + option.dataset.roundYear + open;
        });
        document.querySelectorAll('[data-a5d-year-label]').forEach(function (label) {
          label.textContent = (dict['a5s.downloads.downloadYear'] || 'ดาวน์โหลดทั้งปี') + ' ' + label.dataset.year;
        });
      }

      document.addEventListener('insight:languagechange', paint);
      paint();
    })();
  </script>
@endsection
