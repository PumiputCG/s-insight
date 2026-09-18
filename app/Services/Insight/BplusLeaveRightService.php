<?php

namespace App\Services\Insight;

use App\Services\OtApproval\BplusAttendanceService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * อ่านสิทธิ์ลาและยอดใช้จริงจาก B Plus แบบ read-only
 *
 * SP_ShowRight เป็นแหล่งคำนวณเดียวกับ B Plus และรวมข้อมูลจากงวดก่อน
 * ผลเงินเดือน รายการรอประมวลผล ผลเวลา และรายการอนุมัติแล้ว
 */
class BplusLeaveRightService
{
    /** @var string[] */
    private const USAGE_PREFIXES = ['B4', 'PRR', 'PRT', 'TMR', 'APT'];

    /**
     * @return array{available:bool,year:int,as_of:string,generated_at:string,error:?string,rows:array<int,array<string,mixed>>}
     */
    public function forEmployee(
        string $company,
        string $employeeCode,
        ?CarbonInterface $asOf = null,
    ): array {
        $asOf = $asOf === null
            ? CarbonImmutable::today()
            : CarbonImmutable::instance($asOf);
        $database = BplusAttendanceService::DATABASES[$company] ?? null;

        if ($database === null) {
            return $this->unavailable($asOf, 'ไม่พบฐานข้อมูล B Plus ของบริษัทนี้');
        }

        try {
            Config::set('database.connections.bplus.database', $database);
            DB::purge('bplus');
            $connection = DB::connection('bplus');
            $personal = $connection->selectOne(
                'SELECT PRS_KEY FROM dbo.PERSONALINFO WHERE LTRIM(RTRIM(PRS_NO)) = ?',
                [trim($employeeCode)],
            );

            if ($personal === null) {
                return $this->unavailable($asOf, 'ไม่พบรหัสพนักงานนี้ใน B Plus');
            }

            $rows = $connection->select(
                'SET NOCOUNT ON; EXEC dbo.SP_ShowRight @EmpKey = ?, @SETDATE = ?',
                [$personal->PRS_KEY, $asOf->startOfYear()->toDateString()],
            );

            return [
                'available' => true,
                'year' => $asOf->year,
                'as_of' => $asOf->toDateString(),
                'generated_at' => now()->toIso8601String(),
                'error' => null,
                'rows' => $this->normalizeRows($rows),
            ];
        } catch (Throwable) {
            return $this->unavailable($asOf, 'ไม่สามารถอ่านสิทธิ์ลาจาก B Plus ได้ในขณะนี้');
        }
    }

    /**
     * @param  array<int,array<string,mixed>|object>  $rows
     * @return array<int,array<string,mixed>>
     */
    public function normalizeRows(array $rows): array
    {
        return collect($rows)->map(function (array|object $source): array {
            $row = array_change_key_case((array) $source, CASE_UPPER);
            $usage = [];
            $usedTimes = 0.0;
            $usedUnits = 0.0;

            foreach (self::USAGE_PREFIXES as $prefix) {
                $times = $this->decimal($row[$prefix.'_TIMES'] ?? 0);
                $units = $this->decimal($row[$prefix.'_DAYS'] ?? 0);
                $usage[strtolower($prefix)] = [
                    'times' => $times,
                    'units' => $units,
                ];
                $usedTimes += $times;
                $usedUnits += $units;
            }

            $entitled = $this->decimal($row['DAYS_PER_YEAR'] ?? 0);

            return [
                'key' => trim((string) ($row['SYSLKUP_KEY'] ?? '')),
                'name_th' => trim((string) ($row['SYSLKUP_T_DESC'] ?? '')),
                'name_en' => trim((string) ($row['SYSLKUP_E_DESC'] ?? '')),
                'entitled' => $entitled,
                'used' => round($usedUnits, 4),
                'used_times' => round($usedTimes, 4),
                'remaining' => round($entitled - $usedUnits, 4),
                'rules' => [
                    'times_cumulative' => $this->decimal($row['TIMES_CUM'] ?? 0),
                    'units_per_time' => $this->decimal($row['DAYS_PER_TIME'] ?? 0),
                    'units_per_month' => $this->decimal($row['DAYS_PER_MONTH'] ?? 0),
                    'times_per_year' => $this->decimal($row['TIMES_PER_YEAR'] ?? 0),
                    'units_per_year' => $entitled,
                ],
                'usage' => $usage,
            ];
        })->values()->all();
    }

    private function decimal(mixed $value): float
    {
        return round((float) $value, 4);
    }

    /**
     * @return array{available:bool,year:int,as_of:string,generated_at:string,error:string,rows:array<int,never>}
     */
    private function unavailable(CarbonInterface $asOf, string $message): array
    {
        return [
            'available' => false,
            'year' => (int) $asOf->year,
            'as_of' => $asOf->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'error' => $message,
            'rows' => [],
        ];
    }
}
