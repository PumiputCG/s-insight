@php
  $questionColumnCount = $selectable ? 0 : (int) ($resultQuestionCount ?? 0);
  $columnCount = 7 + $questionColumnCount;
  $excelColumn = static function (int $number): string {
    $label = '';
    while ($number > 0) {
      $number--;
      $label = chr(65 + ($number % 26)).$label;
      $number = intdiv($number, 26);
    }

    return $label;
  };
  $percentText = static fn ($value): string => $value === null
    ? '—'
    : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
@endphp

<div class="self-wrap">
  <table class="imp-tbl self-sheet {{ $selectable ? 'is-manage' : 'is-results' }}" data-filter-table="{{ $tableName }}">
    <thead>
      <tr class="imp-h1">
        @for ($column = 1; $column <= $columnCount; $column++)
          <th>{{ $excelColumn($column) }}</th>
        @endfor
      </tr>
      <tr class="imp-h2">
        @if ($selectable)<th data-i18n="assessment.self.participantColumn">ประเมินตัวเอง</th>@endif
        <th>#</th>
        <th data-i18n="assessment.employeeCode">รหัส</th>
        <th data-i18n="assessment.fullName">ชื่อ-สกุล</th>
        <th data-i18n="assessment.form.position">ตำแหน่ง</th>
        <th data-i18n="assessment.form.department">แผนก</th>
        <th data-i18n="assessment.levelShort">ระดับ</th>
        @unless ($selectable)
          <th data-i18n="assessment.self.scoreColumn">คะแนนประเมินตัวเอง</th>
          @for ($questionNo = 1; $questionNo <= $questionColumnCount; $questionNo++)
            <th><span data-i18n="assessment.self.questionShort">ข้อที่</span> {{ $questionNo }}</th>
          @endfor
        @endunless
      </tr>
      <tr class="imp-filter">
        @for ($column = 0; $column < $columnCount; $column++)
          <th><button type="button" class="ss-fbtn" data-filter-btn data-name="{{ $tableName }}" data-col="{{ $column }}" aria-label="Filter">⌄</button></th>
        @endfor
      </tr>
    </thead>
    <tbody>
      @forelse ($tableRows as $row)
        <tr data-filter-data-row="{{ $tableName }}" @if ($selectable) data-employee-row="{{ $row['employee_code'] }}" @endif>
          @if ($selectable)
            <td class="self-check-cell">
              <input type="checkbox" class="self-check" data-participant-check value="{{ $row['employee_code'] }}" @checked($row['is_selected'])>
              <span class="self-status" data-filter-text data-selected-label
                    data-i18n="{{ $row['is_selected'] ? 'assessment.self.selected' : 'assessment.self.notSelected' }}">
                {{ $row['is_selected'] ? 'เลือก' : 'ไม่เลือก' }}
              </span>
            </td>
          @endif
          <td class="no">{{ $loop->iteration }}</td>
          <td class="code">{{ $row['employee_code'] }}</td>
          <td><span data-loc-th="{{ $row['name'] }}" data-loc-en="{{ $row['name_en'] }}">{{ $row['name'] }}</span></td>
          <td><span data-loc-th="{{ $row['position'] }}" data-loc-en="{{ $row['position_en'] }}">{{ $row['position'] }}</span></td>
          <td><span data-loc-th="{{ $row['department'] }}" data-loc-en="{{ $row['department_en'] }}">{{ $row['department'] }}</span></td>
          <td data-level-cell data-job-code="{{ $row['job_code'] }}">
            <span class="self-level-pill {{ $row['level'] === null ? 'is-empty' : '' }}">{{ $row['level'] ?? '—' }}</span>
          </td>
          @unless ($selectable)
            @php
              $rowAnswers = $row['question_scores'] ?? [];
              // ระดับของพนักงานคนนี้มีกี่ข้อ — เกินจากนี้คือคำถามของระดับอื่น ปล่อยเซลล์ว่าง
              $rowQuestionCount = (int) ($row['level_question_count'] ?? 0);
            @endphp
            <td class="self-result-score">
              @if ($row['self_score'] === null)
                <span class="self-dash">—</span>
              @else
                {{ $percentText($row['self_score']) }}
              @endif
            </td>
            @for ($questionNo = 1; $questionNo <= $questionColumnCount; $questionNo++)
              @php
                $questionScore = $rowAnswers[$questionNo] ?? null;
                $inScope = $questionNo <= $rowQuestionCount || array_key_exists($questionNo, $rowAnswers);
              @endphp
              <td class="self-result-question {{ $inScope ? '' : 'is-out' }}">
                @if (! $inScope)
                  {{-- คำถามของระดับอื่น — ไม่ใช่ของพนักงานคนนี้ --}}
                @elseif ($questionScore === 'N/A')
                  N/A
                @elseif ($questionScore === null)
                  <span class="self-dash">—</span>
                @else
                  {{ $percentText($questionScore) }}
                @endif
              </td>
            @endfor
          @endunless
        </tr>
      @empty
        <tr><td colspan="{{ $columnCount }}"><div class="self-empty" data-i18n="assessment.self.noParticipants">ไม่พบพนักงาน</div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
