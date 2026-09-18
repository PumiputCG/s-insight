<?php

namespace App\Services\Insight;

use App\Models\Insight\Employee;
use Illuminate\Support\Facades\DB;

/**
 * อ่านรายการลาออกที่ HR บันทึกแล้วแต่ Bplus ยังไม่ปิดงวด และทำ Snapshot ลง employees.
 *
 * Bplus ยังคงเป็น Read-only: service นี้ใช้ SELECT เท่านั้นกับ connection `bplus`.
 * การล้างรายการเก่าจะเกิดหลังอ่านบริษัทนั้นสำเร็จครบแล้วเท่านั้น.
 */
class BplusPendingResignationSync
{
    public const RESIGN_EVENT_CODE = 5;

    /**
     * @return array<int,object>
     */
    public function fetchFromBplus(): array
    {
        return DB::connection('bplus')->select(<<<'SQL'
WITH pending_resignations AS (
    SELECT
        LTRIM(RTRIM(prs.PRS_NO)) AS employee_code,
        prt.PRT_KEY AS transaction_key,
        prt.PRT_DATE AS effective_resign_date,
        ROW_NUMBER() OVER (
            PARTITION BY LTRIM(RTRIM(prs.PRS_NO))
            ORDER BY prt.PRT_KEY DESC
        ) AS row_number
    FROM dbo.PRTRAN AS prt
    INNER JOIN dbo.PRDEFTAB AS definition
        ON definition.DF_KEY = prt.PRT_DF
    INNER JOIN dbo.PERSONALINFO AS prs
        ON prs.PRS_EMP = prt.PRT_EMP
    WHERE definition.DF_CODE = ?
      AND prt.PRT_DATE IS NOT NULL
      AND NULLIF(LTRIM(RTRIM(prs.PRS_NO)), '') IS NOT NULL
)
SELECT employee_code, transaction_key, effective_resign_date
FROM pending_resignations
WHERE row_number = 1
ORDER BY employee_code
SQL, [self::RESIGN_EVENT_CODE]);
    }

    /**
     * @param  iterable<int,array<string,mixed>|object>  $rows
     * @return array{pending:int,updated:int,cleared:int,missing:int,missing_codes:array<int,string>}
     */
    public function sync(iterable $rows, string $company): array
    {
        $source = [];

        foreach ($rows as $row) {
            $row = is_object($row) ? (array) $row : $row;
            $employeeCode = trim((string) ($row['employee_code'] ?? ''));
            $effectiveDate = trim((string) ($row['effective_resign_date'] ?? ''));

            if ($employeeCode === '' || $effectiveDate === '') {
                continue;
            }

            $source[$employeeCode] = [
                'effective_resign_date' => $effectiveDate,
                'transaction_key' => isset($row['transaction_key']) ? (int) $row['transaction_key'] : null,
            ];
        }

        $updated = 0;
        $cleared = 0;
        $missingCodes = [];
        $syncedAt = now();

        DB::transaction(function () use (
            $source,
            $company,
            $syncedAt,
            &$updated,
            &$cleared,
            &$missingCodes,
        ): void {
            $employees = Employee::query()
                ->where('company', $company)
                ->whereIn('employee_code', array_keys($source))
                ->get()
                ->keyBy(fn (Employee $employee): string => trim((string) $employee->employee_code));

            foreach ($source as $employeeCode => $pending) {
                /** @var Employee|null $employee */
                $employee = $employees->get($employeeCode);
                if (! $employee) {
                    $missingCodes[] = $employeeCode;

                    continue;
                }

                // Final master มีลำดับสูงกว่า Pending transaction เสมอ.
                if ((string) $employee->emp_status === '2') {
                    $employee->pending_resign_date = null;
                    $employee->pending_resign_transaction_key = null;
                    $employee->pending_resign_synced_at = $syncedAt;
                    $employee->save();

                    continue;
                }

                $employee->pending_resign_date = $pending['effective_resign_date'];
                $employee->pending_resign_transaction_key = $pending['transaction_key'];
                $employee->pending_resign_synced_at = $syncedAt;
                $employee->save();
                $updated++;
            }

            $currentCodes = array_keys($source);
            $stale = Employee::query()
                ->where('company', $company)
                ->whereNotNull('pending_resign_date');

            if ($currentCodes !== []) {
                $stale->whereNotIn('employee_code', $currentCodes);
            }

            $cleared = $stale->update([
                'pending_resign_date' => null,
                'pending_resign_transaction_key' => null,
                'pending_resign_synced_at' => $syncedAt,
            ]);
        });

        return [
            'pending' => count($source),
            'updated' => $updated,
            'cleared' => $cleared,
            'missing' => count($missingCodes),
            'missing_codes' => array_values($missingCodes),
        ];
    }
}
