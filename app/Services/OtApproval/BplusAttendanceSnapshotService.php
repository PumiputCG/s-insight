<?php

namespace App\Services\OtApproval;

use App\Models\Insight\Employee;
use App\Models\OtApproval\OtAttendanceSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BplusAttendanceSnapshotService
{
    public function __construct(
        private readonly BplusAttendanceService $attendance,
    ) {}

    /**
     * อ่าน Bplus แบบ read-only แล้ว upsert สำเนาลงฐาน OT Approval Local
     *
     * @return array{date:string,total:int,companies:array<int,array<string,mixed>>}
     */
    public function sync(CarbonInterface $date, ?string $onlyCompany = null): array
    {
        $companies = array_keys(BplusAttendanceService::DATABASES);
        if ($onlyCompany !== null) {
            if (! in_array($onlyCompany, $companies, true)) {
                throw new RuntimeException('Unknown company: '.$onlyCompany);
            }

            $companies = [$onlyCompany];
        }

        $result = [];
        $grandTotal = 0;

        foreach ($companies as $company) {
            $employeeCodes = Employee::activeAt($date)
                ->where('company', $company)
                ->orderBy('employee_code')
                ->pluck('employee_code')
                ->map(fn (mixed $code) => trim((string) $code))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($employeeCodes === []) {
                $result[] = [
                    'company' => $company,
                    'employees' => 0,
                    'clocked_in' => 0,
                    'clocked_out' => 0,
                    'shifts' => 0,
                    'saved' => 0,
                ];

                continue;
            }

            $liveRows = collect($this->attendance->readBplusAttendanceRows($company, $date, $employeeCodes))
                ->keyBy('employee_code');
            $syncedAt = now();
            $rows = collect($employeeCodes)->map(function (string $employeeCode) use (
                $company,
                $date,
                $liveRows,
                $syncedAt,
            ): array {
                $attendance = $liveRows->get(
                    $employeeCode,
                    $this->attendance->emptyAttendanceRow($employeeCode),
                );

                return [
                    'company' => $company,
                    'employee_code' => $employeeCode,
                    'work_date' => $date->format('Y-m-d'),
                    'shift_code' => $attendance['shift_code'] ?: null,
                    'shift_name_th' => $attendance['shift_name_th'] ?: null,
                    'shift_name_en' => $attendance['shift_name_en'] ?: null,
                    'shift_in' => $attendance['shift_in'],
                    'shift_out' => $attendance['shift_out'],
                    'break_in' => $attendance['break_in'],
                    'break_out' => $attendance['break_out'],
                    'clock_in' => $attendance['clock_in'],
                    'clock_out' => $attendance['clock_out'],
                    'punch_count' => $attendance['punch_count'],
                    'punches' => json_encode($attendance['punches'], JSON_UNESCAPED_UNICODE),
                    'work_hours' => $attendance['work_hours'],
                    'attendance_source' => $attendance['attendance_source'],
                    'attendance_state' => $attendance['attendance_state'],
                    'synced_at' => $syncedAt,
                    'created_at' => $syncedAt,
                    'updated_at' => $syncedAt,
                ];
            })->all();

            DB::connection('mysql_ot_approval')->transaction(function () use ($rows): void {
                foreach (array_chunk($rows, 500) as $chunk) {
                    OtAttendanceSnapshot::upsert(
                        $chunk,
                        ['company', 'employee_code', 'work_date'],
                        [
                            'shift_code', 'shift_name_th', 'shift_name_en',
                            'shift_in', 'shift_out', 'break_in', 'break_out',
                            'clock_in', 'clock_out', 'punch_count', 'punches',
                            'work_hours', 'attendance_source', 'attendance_state',
                            'synced_at', 'updated_at',
                        ],
                    );
                }
            });

            $rowsCollection = collect($rows);
            $saved = count($rows);
            $grandTotal += $saved;
            $result[] = [
                'company' => $company,
                'employees' => count($employeeCodes),
                'clocked_in' => $rowsCollection->whereNotNull('clock_in')->count(),
                'clocked_out' => $rowsCollection->whereNotNull('clock_out')->count(),
                'shifts' => $rowsCollection->pluck('shift_code')->filter()->unique()->count(),
                'saved' => $saved,
            ];
        }

        return [
            'date' => $date->format('Y-m-d'),
            'total' => $grandTotal,
            'companies' => $result,
        ];
    }
}
