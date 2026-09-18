@extends('layouts.portal')

@section('title', 'ประเมินพนักงาน')

@section('topbar-title')
  <span class="tt-kicker" data-i18n="assessment.kicker">การประเมิน</span>
  <span class="tt-title" data-i18n="assessment.evaluate.title">ประเมินพนักงาน</span>
@endsection

@section('page-style')
    /* การ์ดแยกตามหน้าที่ประเมิน (ลำดับ 1+2 / ลำดับ 1 / ลำดับ 2 / 3,4 ต่อข้างล่าง) เรียงลงมาเรื่อย ๆ */
    .asm-evaluate-stack {
      width: min(100%, 86rem);
      margin: 0 auto;
      display: grid;
      gap: 1.1rem;
    }

    .asm-evaluate-intro {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .asm-evaluate-intro h2 {
      margin: 0 0 .2rem;
      color: var(--light-text);
      font-size: 1.12rem;
      font-weight: 700;
    }

    .asm-evaluate-intro p {
      margin: 0;
      color: var(--muted-light);
      font-size: .83rem;
    }

    .asm-evaluate-panel {
      width: 100%;
      border: 1px solid var(--line-light);
      border-radius: 0.34rem;
      background: var(--panel-soft);
      overflow: hidden;
      box-shadow: 0 18px 54px rgb(0 0 0 / 12%);
    }

    .asm-evaluate-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      padding: 1.15rem 1.25rem 1rem;
      border-bottom: 1px solid var(--line-light);
      background: linear-gradient(180deg, var(--hover-soft), transparent);
    }

    .asm-evaluate-title {
      display: grid;
      gap: .25rem;
      min-width: 0;
    }

    .asm-evaluate-title h2 {
      margin: 0;
      color: var(--light-text);
      font-size: 1.08rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .asm-evaluate-title p {
      margin: 0;
      max-width: 48rem;
      color: var(--muted-light);
      font-size: .84rem;
      line-height: 1.6;
    }

    .asm-evaluate-meta {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: .5rem;
      flex-wrap: wrap;
      flex: 0 0 auto;
    }

    .asm-evaluate-chip {
      display: inline-flex;
      align-items: center;
      min-height: 1.9rem;
      padding: .25rem .7rem;
      border: 1px solid var(--line-light);
      border-radius: 0.25rem;
      background: var(--menu-bg);
      color: var(--moss);
      font-size: .8rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .asm-evaluate-chip.is-muted {
      color: var(--muted-light);
      font-weight: 600;
    }

    .asm-evaluate-table-wrap {
      overflow: auto;
      background: var(--menu-bg);
    }

    .asm-evaluate-table {
      width: 100%;
      min-width: 72rem;
      border-collapse: separate;
      border-spacing: 0;
      font-size: .84rem;
    }

    .asm-evaluate-table th,
    .asm-evaluate-table td {
      padding: .78rem .9rem;
      border-right: 1px solid var(--line-light);   /* เส้นแบ่งคอลัมน์ */
      border-bottom: 1px solid var(--line-light);
      text-align: left;
      vertical-align: middle;
    }

    /* คอลัมน์สุดท้ายไม่ต้องมีเส้นขวา จะได้ไม่ซ้อนขอบตาราง */
    .asm-evaluate-table th:last-child,
    .asm-evaluate-table td:last-child {
      border-right: none;
    }

    /* หัวคอลัมน์ — พื้นดำ ตัวอักษรขาว บอกชัดว่าเป็นหัวตาราง */
    .asm-evaluate-table th {
      position: sticky;
      top: 0;
      z-index: 1;
      border-right-color: rgb(255 255 255 / 16%);
      border-bottom-color: rgb(255 255 255 / 16%);
      background: #23262e;
      color: #fff;
      font-size: .78rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .asm-evaluate-table tr:last-child td {
      border-bottom: none;
    }

    .asm-evaluate-table tbody tr {
      transition: background-color .16s var(--ease-out);
    }

    .asm-evaluate-table tbody tr:hover {
      background: var(--hover-soft);
    }

    .asm-no-cell {
      width: 4rem;
      color: var(--muted-light);
      white-space: nowrap;
    }

    .asm-person-cell {
      min-width: 19rem;
    }

    .asm-reviewer-cell {
      min-width: 20rem;
    }

    .asm-eval-person {
      display: grid;
      grid-template-columns: 3.15rem minmax(0, 1fr);
      gap: .75rem;
      align-items: center;
      min-width: 0;
    }

    .asm-avatar-action {
      position: relative;
      width: 3.15rem;
      height: 3.15rem;
      display: grid;
      place-items: center;
      padding: 0;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      overflow: hidden;
      background: color-mix(in srgb, var(--moss) 18%, var(--panel-soft));
      color: var(--moss);
      font-weight: 800;
      line-height: 1;
      cursor: pointer;
      transition: border-color .16s var(--ease-out), transform .16s var(--ease-out);
    }

    .asm-avatar-action:hover {
      border-color: var(--moss);
      transform: translateY(-1px);
    }

    .asm-avatar-action img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .asm-avatar-action.is-compact {
      width: 2.65rem;
      height: 2.65rem;
    }

    .asm-avatar-action > svg {
      width: 62%;
      height: 62%;
      fill: currentColor;
      opacity: .72;
    }

    .asm-avatar-action-label {
      position: absolute;
      inset: auto 0 0;
      display: grid;
      place-items: center;
      min-height: 1.1rem;
      background: rgb(0 0 0 / 62%);
      color: #fff;
      font-size: .56rem;
      font-weight: 700;
      opacity: 0;
      transform: translateY(100%);
      transition: opacity .16s var(--ease-out), transform .16s var(--ease-out);
    }

    .asm-avatar-action:hover .asm-avatar-action-label,
    .asm-avatar-action:focus-visible .asm-avatar-action-label {
      opacity: 1;
      transform: translateY(0);
    }

    .asm-eval-name {
      display: grid;
      gap: .1rem;
      min-width: 0;
    }

    .asm-eval-name strong {
      overflow: hidden;
      color: var(--light-text);
      font-size: .92rem;
      font-weight: 700;
      line-height: 1.35;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .asm-eval-name span {
      color: var(--muted-light);
      font-size: .78rem;
      line-height: 1.35;
    }

    .asm-reviewer-person {
      display: grid;
      grid-template-columns: 2.65rem minmax(0, 1fr);
      gap: .7rem;
      align-items: center;
      min-width: 0;
    }

    .asm-reviewer-list {
      display: grid;
      gap: .65rem;
    }

    .asm-reviewer-entry + .asm-reviewer-entry {
      padding-top: .65rem;
      border-top: 1px solid var(--line-light);
    }

    .asm-reviewer-copy {
      display: grid;
      gap: .2rem;
      min-width: 0;
    }

    .asm-reviewer-headline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .5rem;
      min-width: 0;
    }

    .asm-reviewer-headline strong {
      overflow: hidden;
      color: var(--light-text);
      font-size: .86rem;
      font-weight: 700;
      line-height: 1.35;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .asm-reviewer-role {
      color: var(--muted-light);
      font-size: .74rem;
      line-height: 1.35;
    }

    .asm-reviewer-state {
      flex: 0 0 auto;
      padding: .12rem .38rem;
      border-radius: 999px;
      background: color-mix(in srgb, #d69a1f 14%, transparent);
      color: #a86400;
      font-size: .66rem;
      font-weight: 700;
      line-height: 1.35;
      white-space: nowrap;
    }

    .asm-reviewer-state.is-done {
      background: color-mix(in srgb, var(--success) 14%, transparent);
      color: var(--success);
    }

    .asm-reviewer-state.is-partial {
      background: color-mix(in srgb, #c8964a 14%, transparent);
      color: #a66d21;
    }

    .asm-text-cell {
      max-width: 13rem;
      color: var(--light-text);
      font-weight: 600;
    }

    .asm-text-cell span {
      display: block;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* จัดกึ่งกลาง: No. · ตำแหน่ง · แผนก — ทั้งหัวคอลัมน์และข้อมูล
       (ต้องระบุ .asm-evaluate-table นำหน้า ให้ชนะ th,td { text-align:left } ด้านบน) */
    .asm-evaluate-table th.asm-center-head,
    .asm-evaluate-table td.asm-no-cell,
    .asm-evaluate-table td.asm-text-cell {
      text-align: center;
    }

    /* คอลัมน์ "ประเมิน" — สถานะ + ปุ่ม จัดกึ่งกลาง ตำแหน่งเดียวกันทุกแถว */
    .asm-cta-head {
      text-align: center !important;
    }

    /* ชนะ .asm-evaluate-table td (text-align:left) — pill กับปุ่มอยู่กึ่งกลางคอลัมน์จริง */
    /* padding:0 + relative — ให้แถบสถานะกินเต็มช่องจริง (ขอบ td ยังเห็นเพราะ inset อ้าง padding box) */
    .asm-evaluate-table td.asm-status-cell {
      position: relative;
      width: 11rem;
      padding: 0;
      text-align: center;
    }

    .asm-evaluate-table td.asm-cta-cell {
      width: 10.5rem;
      text-align: center;
    }

    .asm-evaluate-table th.asm-cta-head {
      text-align: center;
    }

    /* สถานะ — แถบสีจางเต็มช่องคอลัมน์ ไม่มีจุดนำ ไม่มีกรอบ */
    .asm-status-pill {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: .3rem .5rem;
      background: var(--panel-soft);
      color: var(--muted-light);
      font-size: .84rem;
      font-weight: 700;
      line-height: 1.25;
      white-space: nowrap;
    }

    .asm-status-pill.is-done {
      background: color-mix(in srgb, var(--success) 16%, transparent);
      color: var(--success);
    }

    .asm-status-pill.is-pending {
      background: color-mix(in srgb, #d69a1f 17%, transparent);
      color: #a86400;
    }

    /* กรอกคะแนนแล้วบางคอลัมน์แต่ยังไม่ครบ */
    .asm-status-pill.is-partial {
      background: color-mix(in srgb, #c8964a 15%, transparent);
      color: #c8964a;
    }

    .asm-eval-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 2.1rem;
      width: fit-content;
      padding: .35rem .8rem;
      border: 1px solid var(--moss);
      border-radius: 0.25rem;
      background: var(--moss);
      color: #fff;
      font-size: .8rem;
      font-weight: 700;
      line-height: 1.2;
      text-decoration: none;
      transition: transform .16s var(--ease-out), filter .16s var(--ease-out);
      white-space: nowrap;
    }

    .asm-eval-btn:hover {
      filter: brightness(1.05);
      transform: translateY(-1px);
    }

    .asm-eval-btn.is-secondary {
      background: transparent;
      color: var(--moss);
    }

    .asm-profile-dialog {
      position: fixed;
      inset: 50% auto auto 50%;
      width: min(94vw, 42rem);
      max-width: none;
      max-height: calc(100dvh - 2rem);
      margin: 0;
      padding: 0;
      border: 1px solid var(--line-light);
      border-radius: 0.36rem;
      background: var(--panel-soft);
      color: var(--light-text);
      box-shadow: 0 28px 90px rgb(0 0 0 / 42%);
      overflow: auto;
      transform: translate(-50%, -50%);
    }

    .asm-profile-dialog::backdrop {
      background: rgb(0 0 0 / 62%);
    }

    .asm-profile-modal {
      display: grid;
      gap: 1.1rem;
      justify-items: center;
      padding: 1.15rem;
    }

    .asm-profile-modal-head {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding-bottom: .75rem;
      border-bottom: 1px solid var(--line-light);
    }

    .asm-profile-modal-head strong {
      font-size: .95rem;
      line-height: 1.35;
    }

    .asm-profile-close {
      width: 2rem;
      height: 2rem;
      display: grid;
      place-items: center;
      border: 1px solid var(--line-light);
      border-radius: 0.25rem;
      background: transparent;
      color: var(--muted-light);
      font-weight: 800;
      cursor: pointer;
    }

    .asm-profile-close:hover {
      border-color: var(--moss);
      color: var(--light-text);
    }

    .asm-profile-photo {
      width: min(78vw, 28rem);
      aspect-ratio: 1;
      display: grid;
      place-items: center;
      justify-self: center;
      border: 1px solid var(--line-light);
      border-radius: 50%;
      overflow: hidden;
      background: color-mix(in srgb, var(--moss) 16%, var(--panel-soft));
      color: var(--moss);
      font-size: 5rem;
      font-weight: 800;
    }

    .asm-profile-photo.is-default {
      border-color: transparent;
      background: color-mix(in srgb, var(--moss) 14%, var(--panel-soft));
    }

    .asm-profile-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .asm-profile-photo [data-profile-fallback] {
      width: 100%;
      height: 100%;
      display: grid;
      place-items: center;
      line-height: 0;
    }

    .asm-profile-photo [data-profile-fallback] svg {
      display: block;
      width: 76%;
      height: 76%;
      fill: currentColor;
      opacity: .68;
    }

    .asm-profile-facts {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .6rem;
    }

    .asm-profile-fact {
      min-width: 0;
      padding: .7rem;
      border: 1px solid var(--line-light);
      border-radius: 0.26rem;
      background: var(--menu-bg);
    }

    .asm-profile-fact span {
      display: block;
      color: var(--muted-light);
      font-size: .7rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .asm-profile-fact strong {
      display: block;
      overflow: hidden;
      margin-top: .12rem;
      color: var(--light-text);
      font-size: .84rem;
      line-height: 1.45;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    @media (max-width: 760px) {
      .asm-evaluate-head {
        padding: 1rem;
      }

      .asm-evaluate-meta {
        width: 100%;
        justify-content: flex-start;
      }

      .asm-evaluate-table th,
      .asm-evaluate-table td {
        padding: .7rem;
      }

      .asm-profile-facts {
        grid-template-columns: 1fr;
      }
    }
@endsection

@section('content')
  @php
    // แยกการ์ดตามบทบาทประเมิน: [1] หัวหน้างานโดยตรง, [2] ผู้ประเมินระดับฝ่าย, [1,2] รวมเป็นบทบาทเดียว
    $buckets = [];
    foreach ($assignments as $row) {
      foreach ($row['groups'] as $g) {
        $key = implode('-', $g['covers']);
        $buckets[$key]['covers'] = $g['covers'];
        $buckets[$key]['items'][] = [
          'row' => $row,
          'level' => $g['level'],
          'evaluator' => $g['reviewer'] ?? [],
          'evaluators' => $row['evaluators'] ?? [],
          'state' => $row['level_status'][$g['level']] ?? ['assessed' => false, 'partial' => false],
        ];
      }
    }
    uasort($buckets, fn ($a, $b) => [min($a['covers']), -count($a['covers'])] <=> [min($b['covers']), -count($b['covers'])]);
    $roleMeta = function (array $covers): array {
      $levels = array_values(array_unique(array_filter(array_map('intval', $covers))));
      sort($levels);

      if ($levels === [1]) {
        return ['label' => 'หัวหน้างานโดยตรง', 'key' => 'assessment.evaluate.role.directSupervisor'];
      }

      if ($levels === [2]) {
        return ['label' => 'ผู้ประเมินระดับฝ่าย', 'key' => 'assessment.evaluate.role.division'];
      }

      if (in_array(1, $levels, true) && in_array(2, $levels, true)) {
        return ['label' => 'หัวหน้างานโดยตรง / ผู้ประเมินระดับฝ่าย', 'key' => 'assessment.evaluate.role.directAndDivision'];
      }

      return ['label' => 'ผู้ประเมินลำดับที่ '.implode(', ', $levels), 'key' => null];
    };
  @endphp

  <div class="asm-evaluate-stack">
    <div class="asm-evaluate-intro">
      <div>
        <h2 data-i18n="assessment.evaluate.pageTitle">รายการประเมินพนักงาน</h2>
        <p data-i18n="assessment.evaluate.help">เลือกพนักงานจากรายการ แล้วกดประเมินหรือประเมินต่อในรอบที่เปิดอยู่</p>
      </div>
      <div class="asm-evaluate-meta" aria-label="ข้อมูลรอบประเมิน">
        @include('assessment.partials.sortselect')
        <span class="asm-evaluate-chip">{{ number_format($assignments->count()) }} <span data-i18n="assessment.evaluate.itemUnit">รายการ</span></span>
        @if (($openRound ?? null))
          <span class="asm-evaluate-chip is-muted">{{ $openRound->name }} · {{ $openRound->year }}</span>
        @endif
      </div>
    </div>

    @foreach ($buckets as $bucket)
      @php
        $role = $roleMeta($bucket['covers']);
      @endphp
      <section class="asm-evaluate-panel" aria-label="{{ $role['label'] }}" @if ($role['key']) data-i18n-aria="{{ $role['key'] }}" @endif>
        <div class="asm-evaluate-head">
          <div class="asm-evaluate-title">
            <h2 @if ($role['key']) data-i18n="{{ $role['key'] }}" @endif>{{ $role['label'] }}</h2>
          </div>
          <div class="asm-evaluate-meta">
            <span class="asm-evaluate-chip">{{ number_format(count($bucket['items'])) }} <span data-i18n="assessment.evaluate.itemUnit">รายการ</span></span>
          </div>
        </div>

        <div class="asm-evaluate-table-wrap">
          <table class="asm-evaluate-table" data-sortable-table>
            <thead>
              <tr>
                <th class="asm-center-head">No.</th>
                <th class="asm-center-head" data-i18n="assessment.evaluate.employee">พนักงานที่ประเมิน</th>
                <th class="asm-center-head" data-i18n="assessment.evaluate.position">ตำแหน่ง</th>
                <th class="asm-center-head" data-i18n="assessment.evaluate.department">แผนก</th>
                <th class="asm-cta-head" data-i18n="assessment.evaluate.status">สถานะ</th>
                <th class="asm-center-head" data-i18n="assessment.evaluate.evaluator">ผู้ประเมิน</th>
                <th class="asm-cta-head" data-i18n="assessment.evaluate.action">ประเมิน</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($bucket['items'] as $i => $item)
                @php
                  $row = $item['row'];
                  $evaluator = $item['evaluator'];
                  $evaluators = $item['evaluators'];
                  $isDone = $item['state']['assessed'] ?? false;
                  $isPartial = $item['state']['partial'] ?? false;
                @endphp
                <tr data-sort-code="{{ $row['employee_code'] }}" data-sort-rank="{{ $row['position_rank'] ?? 999 }}">
                  <td class="asm-no-cell" data-row-no>{{ number_format($i + 1) }}</td>
                  <td class="asm-person-cell">
                    <div class="asm-eval-person">
                      <button
                        type="button"
                        class="asm-avatar-action"
                        data-profile-trigger
                        data-avatar="{{ $row['avatar'] ?? '' }}"
                        data-code="{{ $row['employee_code'] }}"
                        data-name="{{ $row['name'] }}"
                        data-position="{{ $row['position'] ?: '-' }}"
                        data-department="{{ $row['department'] ?: '-' }}"
                        data-name-en="{{ $row['name_en'] ?? $row['name'] }}"
                        data-position-en="{{ $row['position_en'] ?: ($row['position'] ?: '-') }}"
                        data-department-en="{{ $row['department_en'] ?: ($row['department'] ?: '-') }}"
                        aria-label="ดูรูปโปรไฟล์ {{ $row['name'] }}"
                        data-i18n-aria="assessment.evaluate.viewPhoto"
                      >
                        @if (! empty($row['avatar']))
                          <img src="{{ $row['avatar'] }}" alt="">
                        @else
                          @include('assessment.partials.default-avatar')
                        @endif
                        <span class="asm-avatar-action-label" data-i18n="assessment.evaluate.viewPhoto">ดูรูป</span>
                      </button>
                      <div class="asm-eval-name">
                        <strong title="{{ $row['name'] }}" data-loc-th="{{ $row['name'] }}" data-loc-en="{{ $row['name_en'] ?? $row['name'] }}">{{ $row['name'] }}</strong>
                        <span>{{ $row['employee_code'] }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="asm-text-cell"><span title="{{ $row['position'] ?: '-' }}" data-loc-th="{{ $row['position'] ?: '-' }}" data-loc-en="{{ $row['position_en'] ?: ($row['position'] ?: '-') }}">{{ $row['position'] ?: '-' }}</span></td>
                  <td class="asm-text-cell"><span title="{{ $row['department'] ?: '-' }}" data-loc-th="{{ $row['department'] ?: '-' }}" data-loc-en="{{ $row['department_en'] ?: ($row['department'] ?: '-') }}">{{ $row['department'] ?: '-' }}</span></td>
                  {{-- สถานะ (คอลัมน์แยก) — ครบ = ประเมินแล้ว, บางส่วน = ยังประเมินไม่ครบ --}}
                  <td class="asm-status-cell">
                    <span class="asm-status-pill {{ $isDone ? 'is-done' : ($isPartial ? 'is-partial' : 'is-pending') }}">
                      @if ($isDone)
                        <span data-i18n="assessment.evaluate.status.done">ประเมินแล้ว</span>
                      @elseif ($isPartial)
                        <span data-i18n="assessment.evaluate.status.partial">ยังประเมินไม่ครบ</span>
                      @else
                        <span data-i18n="assessment.evaluate.status.pending">รอประเมิน</span>
                      @endif
                    </span>
                  </td>
                  <td class="asm-reviewer-cell">
                    <div class="asm-reviewer-list">
                      @foreach ($evaluators as $evaluatorItem)
                        @php
                          $evaluatorPerson = $evaluatorItem['reviewer'] ?? [];
                          $evaluatorRole = $roleMeta($evaluatorItem['covers'] ?? []);
                          $evaluatorDone = $evaluatorItem['state']['assessed'] ?? false;
                          $evaluatorPartial = $evaluatorItem['state']['partial'] ?? false;
                        @endphp
                        <div class="asm-reviewer-person asm-reviewer-entry">
                          <button
                            type="button"
                            class="asm-avatar-action is-compact"
                            data-profile-trigger
                            data-avatar="{{ $evaluatorPerson['avatar'] ?? '' }}"
                            data-code="{{ $evaluatorPerson['employee_code'] ?? '-' }}"
                            data-name="{{ $evaluatorPerson['name'] ?? '-' }}"
                            data-position="{{ $evaluatorPerson['position'] ?? '-' }}"
                            data-department="{{ $evaluatorPerson['department'] ?? '-' }}"
                            data-name-en="{{ $evaluatorPerson['name_en'] ?? ($evaluatorPerson['name'] ?? '-') }}"
                            data-position-en="{{ $evaluatorPerson['position_en'] ?? ($evaluatorPerson['position'] ?? '-') }}"
                            data-department-en="{{ $evaluatorPerson['department_en'] ?? ($evaluatorPerson['department'] ?? '-') }}"
                            aria-label="ดูรูปโปรไฟล์ {{ $evaluatorPerson['name'] ?? 'ผู้ประเมิน' }}"
                            data-i18n-aria="assessment.evaluate.viewPhoto"
                          >
                            @if (! empty($evaluatorPerson['avatar']))
                              <img src="{{ $evaluatorPerson['avatar'] }}" alt="">
                            @else
                              @include('assessment.partials.default-avatar')
                            @endif
                            <span class="asm-avatar-action-label" data-i18n="assessment.evaluate.viewPhoto">ดูรูป</span>
                          </button>
                          <div class="asm-reviewer-copy">
                            <div class="asm-reviewer-headline">
                              <strong
                                title="{{ $evaluatorPerson['name'] ?? '-' }}"
                                data-loc-th="{{ $evaluatorPerson['name'] ?? '-' }}"
                                data-loc-en="{{ $evaluatorPerson['name_en'] ?? ($evaluatorPerson['name'] ?? '-') }}"
                              >{{ $evaluatorPerson['name'] ?? '-' }}</strong>
                              <span class="asm-reviewer-state {{ $evaluatorDone ? 'is-done' : ($evaluatorPartial ? 'is-partial' : '') }}">
                                @if ($evaluatorDone)
                                  <span data-i18n="assessment.evaluate.status.done">ประเมินแล้ว</span>
                                @elseif ($evaluatorPartial)
                                  <span data-i18n="assessment.evaluate.status.partial">ยังประเมินไม่ครบ</span>
                                @else
                                  <span data-i18n="assessment.evaluate.status.pending">รอประเมิน</span>
                                @endif
                              </span>
                            </div>
                            <span class="asm-reviewer-role" @if ($evaluatorRole['key']) data-i18n="{{ $evaluatorRole['key'] }}" @endif>{{ $evaluatorRole['label'] }}</span>
                          </div>
                        </div>
                      @endforeach
                      @if ($evaluators === [])
                        <div class="asm-eval-name">
                          <strong>-</strong>
                        </div>
                      @endif
                    </div>
                  </td>
                  <td class="asm-cta-cell">
                    <a
                      class="asm-eval-btn nav-go {{ $isDone ? 'is-secondary' : '' }}"
                      href="{{ route('assessment.evaluate.show', ['employee' => $row['employee_code'], 'level' => $item['level']]) }}"
                      aria-label="ประเมิน {{ $row['name'] }} {{ $role['label'] }}"
                    >
                      @if ($isDone)
                        <span data-i18n="assessment.evaluate.button.edit">ดู / แก้ไข</span>
                      @elseif ($isPartial)
                        <span data-i18n="assessment.evaluate.button.continue">ประเมินต่อ</span>
                      @else
                        <span data-i18n="assessment.evaluate.button.start">ประเมิน</span>
                      @endif
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    @endforeach
  </div>

  <dialog class="asm-profile-dialog" data-profile-dialog aria-label="รูปโปรไฟล์พนักงาน" data-i18n-aria="assessment.evaluate.profileTitle">
    <div class="asm-profile-modal">
      <div class="asm-profile-modal-head">
        <strong data-profile-title data-i18n="assessment.evaluate.profileTitle">รูปโปรไฟล์พนักงาน</strong>
        <button type="button" class="asm-profile-close" data-profile-close aria-label="ปิด" data-i18n-aria="profile.close_photo">×</button>
      </div>

      <div class="asm-profile-photo" data-profile-photo aria-hidden="true">
        <img src="" alt="" data-profile-image hidden>
        <span data-profile-fallback>
          @include('assessment.partials.default-avatar')
        </span>
      </div>

      <div class="asm-profile-facts">
        <div class="asm-profile-fact">
          <span data-i18n="assessment.evaluate.employeeCode">รหัสพนักงาน</span>
          <strong data-profile-code>-</strong>
        </div>
        <div class="asm-profile-fact">
          <span data-i18n="assessment.evaluate.fullName">ชื่อ-สกุล</span>
          <strong data-profile-name>-</strong>
        </div>
        <div class="asm-profile-fact">
          <span data-i18n="assessment.evaluate.position">ตำแหน่ง</span>
          <strong data-profile-position>-</strong>
        </div>
        <div class="asm-profile-fact">
          <span data-i18n="assessment.evaluate.department">แผนก</span>
          <strong data-profile-department>-</strong>
        </div>
      </div>
    </div>
  </dialog>
@endsection

@section('page-script')
  <script>
    'use strict';

    (() => {
      const dialog = document.querySelector('[data-profile-dialog]');
      if (!dialog) return;

      const image = dialog.querySelector('[data-profile-image]');
      const fallback = dialog.querySelector('[data-profile-fallback]');
      const photo = dialog.querySelector('[data-profile-photo]');
      const title = dialog.querySelector('[data-profile-title]');
      const code = dialog.querySelector('[data-profile-code]');
      const name = dialog.querySelector('[data-profile-name]');
      const position = dialog.querySelector('[data-profile-position]');
      const department = dialog.querySelector('[data-profile-department]');

      const setText = (node, value) => {
        if (node) node.textContent = value || '-';
      };

      // ธง TH = ค่าไทย · EN/MY = ค่าอังกฤษ (ไม่มีก็ถอยมาไทย)
      const langPick = (button, base) => {
        const lang = document.documentElement.getAttribute('data-lang') || 'th';
        if (lang === 'th') return button.dataset[base] || '';
        return button.dataset[base + 'En'] || button.dataset[base] || '';
      };

      const openProfile = (button) => {
        const avatar = button.dataset.avatar || '';
        setText(title, langPick(button, 'name') || 'รูปโปรไฟล์พนักงาน');
        setText(code, button.dataset.code);
        setText(name, langPick(button, 'name'));
        setText(position, langPick(button, 'position'));
        setText(department, langPick(button, 'department'));

        if (avatar) {
          image.src = avatar;
          image.hidden = false;
          fallback.hidden = true;
          photo?.classList.remove('is-default');
        } else {
          image.removeAttribute('src');
          image.hidden = true;
          fallback.hidden = false;
          photo?.classList.add('is-default');
        }

        if (typeof dialog.showModal === 'function') {
          dialog.showModal();
        } else {
          dialog.setAttribute('open', '');
        }
      };

      document.querySelectorAll('[data-profile-trigger]').forEach((button) => {
        button.addEventListener('click', () => openProfile(button));
      });

      dialog.querySelectorAll('[data-profile-close]').forEach((button) => {
        button.addEventListener('click', () => {
          if (typeof dialog.close === 'function') {
            dialog.close();
          } else {
            dialog.removeAttribute('open');
          }
        });
      });

      dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        if (typeof dialog.close === 'function') {
          dialog.close();
        } else {
          dialog.removeAttribute('open');
        }
      });
    })();
  </script>
@endsection
