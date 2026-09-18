<?php

namespace App\Services\Insight;

use App\Models\Insight\Employee;
use Illuminate\Support\Facades\DB;

/**
 * ตัวกลางนำเข้าพนักงานจาก Bplus EMP_MAIN เข้าตาราง employees
 *
 * ใช้ร่วมกันทั้ง 2 ทาง:
 *   - วิธี A: bplus:import-json อ่านไฟล์ JSON (ผลจาก scripts/bplus_pull.ps1) แล้วส่ง rows เข้ามา
 *   - วิธี B: bplus:sync ต่อตรง SQL Server ผ่าน connection 'bplus' แล้วส่ง rows เข้ามา
 *
 * - employees = mirror Bplus ล้วน (ไม่มีฟิลด์ล็อกอินแล้ว — ล็อกอินอยู่ที่ app_users)
 * - เก็บแถวดิบทั้งแถวไว้ใน employees.source_raw (กันข้อมูลตกหล่นเมื่อ Bplus ขยาย view)
 * - จับคู่ชื่อคอลัมน์แบบยืดหยุ่น (ไม่สนตัวพิมพ์เล็ก/ใหญ่) ตาม $map
 * - emp_status (PRI_STATUS): '2' = ลาออก (ใช้ตอน appusers:sync ตัดสินสร้าง/ลบบัญชี)
 * - cleanupMissing: ใช้กับ bplus:sync เท่านั้น เพื่อลบแถวที่ Bplus ไม่ส่งมาแล้วออกจาก mirror
 */
class BplusEmployeeImporter
{
    /**
     * รหัสพนักงานหลอกจาก Bplus (สร้างไว้จ่ายเงินเดือนข้ามบริษัทเท่านั้น เป็นคนที่มีรหัสจริงอยู่แล้ว)
     * — ไม่นำเข้า Insight ; ข้ามตอน import → cleanupMissing ของ bplus:sync จะลบแถวเดิมออกให้เอง
     */
    public const EXCLUDED_CODES = ['16899', '16900'];

    /**
     * logical field => รายชื่อคอลัมน์ต้นทางที่เป็นไปได้ (จับคู่แบบไม่สนตัวพิมพ์เล็ก/ใหญ่)
     *
     * @var array<string,string[]>
     */
    public array $map = [
        'employee_code' => ['EMP_CODE', 'PRS_NO', 'รหัสพนักงาน'],
        'license_id' => ['LICENSE_ID', 'EMP_I_CARD', 'เลขบัตรประชาชน'],
        'title' => ['TITLE', 'EMP_INTL', 'ชื่อต้น'],
        'gender' => ['TITLE_ID', 'EMP_GENDER', 'GENDER', 'เพศ'], // TITLE_ID = เพศจริง (ชื่อคอลัมน์หลอก)
        'name_th' => ['NAME_T', 'EMP_NAME', 'ชื่อตัว'],
        'surname_th' => ['SURNAME_T', 'EMP_SURNME', 'ชื่อสกุล'],
        'name_en' => ['NAME_E', 'EMP_E_NAME', 'ชื่อภาษาอื่น'],
        'job_code' => ['JOB_CODE', 'JBT_CODE', 'รหัสตำแหน่งงาน'],
        'job_th' => ['JOB_T', 'JBT_THAIDESC'],
        'job_en' => ['JOB_E', 'JBT_ENGDESC'],
        'dept_code' => ['DEPT_CODE', 'รหัสแผนก'],
        'dept_th' => ['DEPT_T', 'DEPT_THAIDESC'],
        'dept_en' => ['DEPT_E', 'DEPT_ENGDESC'],
        /* สาขา — ไม่ได้อยู่ใน EMP_MAIN แต่ `bplus:sync` join PERSONALINFO + BRANCH มาเติมให้
           ใช้แยก "โรงงาน" กับ "โรงงาน-พม่า" ซึ่งเป็นวิธีที่บริษัทแบ่งคนไทย/คนพม่าจริง */
        'branch_code' => ['BRANCH_CODE', 'BR_CODE'],
        'branch_th' => ['BRANCH_T', 'BR_THAIDESC'],
        'branch_en' => ['BRANCH_E', 'BR_ENGDESC'],
        // 4 คอลัมน์จาก PAYROLLINFO ที่เพิ่มเข้า view EMP_MAIN แล้ว (D-012)
        'hire_date' => ['HIRE_DATE', 'PRI_START_D', 'วันเริ่มงาน'],
        'probation_end_date' => ['PROBATION_END_DATE', 'PRI_PROB_D', 'วันพ้นทดลองงาน'],
        'resign_date' => ['RESIGN_DATE', 'PRI_RES_D', 'วันลาออก'],
        'emp_status' => ['EMP_STATUS', 'PRI_STATUS', 'สถานะ'], // 1=ทำงาน, 2=ลาออก
    ];

    /**
     * นำเข้าพนักงานของบริษัทหนึ่งจากชุดแถวดิบ (mirror Bplus เท่านั้น)
     *
     * นอกจากตัวเลขสรุป ยังคืนรายชื่อการเปลี่ยนแปลง (ใครเข้า/ใครออก) ไว้แสดงผลด้วย:
     *   - created_list  : พนักงานใหม่ที่เพิ่งเข้า mirror รอบนี้
     *   - resigned_list : สถานะเปลี่ยนเป็นลาออก ('2') ในรอบนี้
     *   - missing_list  : หายจากผล Bplus → ถูกลบออกจาก mirror (เฉพาะ cleanupMissing)
     *
     * @param  iterable<int,array<string,mixed>|object>  $rows  แถวจาก JSON (array) หรือ DB::select (stdClass)
     * @return array{imported:int,created:int,updated:int,skipped:int,deleted:int,missing_deleted:int,created_list:array<int,array<string,string>>,resigned_list:array<int,array<string,string>>,missing_list:array<int,array<string,string>>}
     */
    public function import(iterable $rows, string $company, bool $truncate = false, bool $cleanupMissing = false): array
    {
        $deleted = 0;
        if ($truncate) {
            $deleted = Employee::where('company', $company)->delete();
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $missingDeleted = 0;
        $currentCodes = [];
        $createdList = [];
        $resignedList = [];
        $missingList = [];

        DB::transaction(function () use ($rows, $company, $cleanupMissing, &$created, &$updated, &$skipped, &$missingDeleted, &$currentCodes, &$createdList, &$resignedList, &$missingList) {
            foreach ($rows as $row) {
                $row = is_object($row) ? (array) $row : $row;
                if (! is_array($row)) {
                    $skipped++;

                    continue;
                }

                $lc = $this->normalizeKeys($row);
                $empCode = (string) ($this->pick($lc, 'employee_code') ?? '');
                if ($empCode === '') {
                    $skipped++;

                    continue;
                }
                // รหัสหลอก (payroll) — ไม่นำเข้า และไม่นับใน currentCodes เพื่อให้ cleanupMissing ลบแถวเดิมออก
                if (in_array($empCode, self::EXCLUDED_CODES, true)) {
                    $skipped++;

                    continue;
                }
                $currentCodes[$empCode] = true;

                // สถานะจาก Bplus (PRI_STATUS): '2' = ลาออก ; อื่น/ว่าง = ทำงานอยู่
                $status = $this->pick($lc, 'emp_status');
                $statusStr = ($status === null || $status === '') ? null : (string) $status;

                $emp = Employee::firstOrNew(['company' => $company, 'employee_code' => $empCode]);
                $isNew = ! $emp->exists;
                $wasResigned = ! $isNew && (string) $emp->emp_status === '2';

                // mirror Bplus — อัปเดตทุกครั้ง
                $emp->license_id = $this->pick($lc, 'license_id') ?: null;
                $emp->title = $this->pick($lc, 'title') ?: null;
                $emp->gender = $this->pick($lc, 'gender') ?: null;
                $emp->name_th = $this->pick($lc, 'name_th') ?: null;
                $emp->surname_th = $this->pick($lc, 'surname_th') ?: null;
                $emp->name_en = $this->pick($lc, 'name_en') ?: null;
                $emp->job_code = $this->pick($lc, 'job_code') ?: null;
                $emp->job_th = $this->pick($lc, 'job_th') ?: null;
                $emp->job_en = $this->pick($lc, 'job_en') ?: null;
                $emp->dept_code = $this->pick($lc, 'dept_code') ?: null;
                $emp->dept_th = $this->pick($lc, 'dept_th') ?: null;
                $emp->dept_en = $this->pick($lc, 'dept_en') ?: null;
                $emp->branch_code = $this->pick($lc, 'branch_code') ?: null;
                $emp->branch_th = $this->pick($lc, 'branch_th') ?: null;
                $emp->branch_en = $this->pick($lc, 'branch_en') ?: null;
                $emp->hire_date = $this->pick($lc, 'hire_date') ?: null;                 // PRI_START_D
                $emp->probation_end_date = $this->pick($lc, 'probation_end_date') ?: null; // PRI_PROB_D
                $emp->resign_date = $this->pick($lc, 'resign_date') ?: null;             // PRI_RES_D
                $emp->emp_status = $statusStr;                  // 1=ทำงาน, 2=ลาออก
                $emp->source_raw = $row;                        // เก็บทั้งแถวดิบ
                $emp->synced_at = now();

                $emp->save();
                $isNew ? $created++ : $updated++;

                // เก็บรายชื่อการเปลี่ยนแปลงไว้รายงาน (ใครเข้ามาใหม่ / ใครเพิ่งลาออก)
                if ($isNew) {
                    $createdList[] = $this->changeDetail($emp);
                } elseif ($statusStr === '2' && ! $wasResigned) {
                    $resignedList[] = $this->changeDetail($emp);
                }
            }

            if ($cleanupMissing) {
                $query = Employee::where('company', $company);
                $codes = array_keys($currentCodes);

                if ($codes !== []) {
                    $query->whereNotIn('employee_code', $codes);
                }

                // ดึงรายชื่อก่อนลบ เพื่อรายงานว่าใครหายไปจาก Bplus
                foreach ((clone $query)->orderBy('employee_code')->get() as $missing) {
                    $missingList[] = $this->changeDetail($missing);
                }

                $missingDeleted = $query->delete();
            }
        });

        Employee::reindexNo();

        return [
            'imported' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'deleted' => $deleted,
            'missing_deleted' => $missingDeleted,
            'created_list' => $createdList,
            'resigned_list' => $resignedList,
            'missing_list' => $missingList,
        ];
    }

    /**
     * ข้อมูลย่อของพนักงานสำหรับรายงานการเปลี่ยนแปลง (ไม่มีข้อมูลลับ)
     *
     * @return array<string,string>
     */
    protected function changeDetail(Employee $emp): array
    {
        return [
            'code' => (string) $emp->employee_code,
            'name' => $emp->fullNameTh() ?: (string) ($emp->name_en ?: '—'),
            'position' => (string) ($emp->job_th ?: $emp->job_en ?: '—'),
            'dept' => (string) ($emp->deptThClean() ?: $emp->dept_en ?: '—'),
            'company' => (string) $emp->company,
        ];
    }

    /**
     * ทำสำเนา row โดยใช้คีย์ตัวพิมพ์เล็ก (trim) เพื่อจับคู่แบบไม่สนตัวพิมพ์
     *
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    protected function normalizeKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $out[mb_strtolower(trim((string) $k))] = $v;
        }

        return $out;
    }

    /**
     * เลือกค่าจาก row ตาม candidate names ของ logical field
     *
     * @param  array<string,mixed>  $lcRow
     */
    protected function pick(array $lcRow, string $field): mixed
    {
        foreach ($this->map[$field] ?? [] as $cand) {
            $key = mb_strtolower(trim($cand));
            if (array_key_exists($key, $lcRow)) {
                $v = $lcRow[$key];

                return is_string($v) ? trim($v) : $v;
            }
        }

        return null;
    }
}
