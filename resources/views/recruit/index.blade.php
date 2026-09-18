@extends('layouts.portal')

@section('title', 'สรรหาบุคลากร')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="recruit.kicker">การสรรหา</span>
  <span class="tt-title" data-i18n="rc.overview">ภาพรวม</span>
@endsection

@section('page-style')
    .rcv-wrap { width: min(100%, 64rem); margin-top: clamp(.5rem, 2vw, 1rem); }

    .rcv-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .rcv-head h1 { font-family: var(--font-display); font-size: clamp(1.5rem, 3vw, 2.1rem); font-weight: 400; }
    .rcv-legend { display: inline-flex; gap: 1rem; flex-wrap: wrap; font-size: .76rem; color: var(--muted-light); }
    .rcv-legend span { display: inline-flex; align-items: center; gap: .4rem; }
    .rcv-dot { width: .65rem; height: .65rem; border-radius: 50%; }
    .rcv-dot.done { background: #4caf6e; }
    .rcv-dot.pending { background: #e3b341; }
    .rcv-dot.ack { background: #4a90e2; }

    .rcv-table { width: 100%; border-collapse: collapse; border: 1px solid var(--line-light); border-radius: 0.36rem; overflow: hidden; }
    .rcv-table thead th { text-align: left; padding: .8rem 1rem; background: var(--panel-soft); color: var(--muted-light); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .rcv-table tbody td { padding: .85rem 1rem; border-top: 1px solid var(--line-light); font-size: .9rem; }
    .rcv-status { display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; }
    .rcv-empty { padding: 3rem 1rem; text-align: center; color: var(--muted-light); }
    .rcv-empty .em-icon { width: 3rem; height: 3rem; margin: 0 auto .8rem; opacity: .5; fill: none; stroke: currentColor; stroke-width: 1.5; }
@endsection

@section('content')
  <div class="rcv-wrap">
    <div class="rcv-head">
      <h1 data-i18n="rc.historyTitle">ประวัติการขออัตรากำลังคน</h1>
      <div class="rcv-legend">
        <span><i class="rcv-dot done"></i> <span data-i18n="rc.legend.done">เสร็จ</span></span>
        <span><i class="rcv-dot pending"></i> <span data-i18n="rc.legend.pending">รอดำเนินการ</span></span>
        <span><i class="rcv-dot ack"></i> <span data-i18n="rc.legend.ack">รับทราบ (Recruit)</span></span>
      </div>
    </div>

    <table class="rcv-table">
      <thead>
        <tr>
          <th data-i18n="rc.col.requester">ผู้ขอ</th>
          <th data-i18n="rc.col.reqDate">วันที่ขอ</th>
          <th data-i18n="rc.col.startDate">วันเริ่มงาน</th>
          <th data-i18n="rc.col.status">สถานะ</th>
        </tr>
      </thead>
      <tbody>
        {{-- ยังไม่มีคำขอจริง (ฟอร์ม/workflow รอเฟสถัดไป) — เตรียมโครงตารางไว้ --}}
        <tr>
          <td colspan="4">
            <div class="rcv-empty">
              <svg class="em-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l3 3 8-8"></path><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"></path></svg>
              <p data-i18n="rc.empty">ยังไม่มีคำขออัตรากำลังคน</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
