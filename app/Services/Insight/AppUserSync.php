<?php

namespace App\Services\Insight;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;

/**
 * ซิงค์บัญชีล็อกอิน (app_users) จากตารางพนักงานกลาง (employees)
 *
 * ตัวตน = เลขบัตรประชาชน (employees.license_id) : 1 คน = 1 บัญชี แม้อยู่หลายบริษัท
 *
 * กติกา (ตามที่เจ้าของกำหนด):
 *   - รวมคนข้ามบริษัท: ถ้าเลขบัตรเดียวกันอยู่ 2-3 บริษัท -> บัญชีเดียว, คอลัมน์ company เก็บหลายบริษัท
 *     (คั่นด้วย ,) + เก็บ map บริษัท->รหัสพนักงานใน `companies` เพราะรหัสแต่ละบริษัทต่างกันได้
 *     -> บัญชีนั้นล็อกอินได้ทุกบริษัทที่คุม ด้วยรหัสของบริษัทนั้น และรหัสผ่านเดียวกัน
 *   - active (emp_status != '2') เท่านั้นที่มีบัญชี ; ลาออก/ออกไป -> ลบบัญชีทิ้งจริง (hard delete)
 *   - คนเคยลาออกแล้วกลับเข้าใหม่ -> สร้างบัญชีใหม่ด้วยเวลาปัจจุบัน (registered_at = now) รหัส = เลขบัตรอีกครั้ง
 *
 * ค่าตั้งครั้งแรกเท่านั้น (กันทับรหัสที่ผู้ใช้เปลี่ยนเอง): password, id_thai_hash, registered_at
 * บัญชี system admin แยกต่างหาก: employee_code=Admin, password=000000, ไม่ผูก employees และไม่ถูกลบตอน sync
 * ข้อมูลแสดงผล (ชื่อ/ตำแหน่ง/แผนก/company/companies) อัปเดตทุกครั้ง
 */
class AppUserSync
{
    private const SYSTEM_ADMIN_CODE = 'Admin';

    private const SYSTEM_ADMIN_PASSWORD = '000000';

    /**
     * นอกจากตัวเลขสรุป ยังคืนรายชื่อบัญชีที่สร้างใหม่ (created_list) และที่ถูกลบเพราะลาออก (removed_list)
     *
     * @param  string[]  $admins  รหัสพนักงานที่ตั้งเป็น admin
     * @return array{created:int,updated:int,removed:int,created_list:array<int,array<string,string>>,removed_list:array<int,array<string,string>>}
     */
    public function sync(array $admins = []): array
    {
        $created = 0;
        $updated = 0;
        $createdList = [];
        $removedList = [];

        // 1) รวมพนักงาน active ตามเลขบัตร (ข้ามบริษัท) ; ไม่มีเลขบัตร -> แยกตาม company+code
        //    หมายเหตุ: chunkById ต้องเรียงตาม id เท่านั้น ห้ามใส่ orderBy เอง (จะข้ามแถว)
        //    -> ลำดับความสำคัญของบริษัท (เลือก primary) ไปจัดตอน build ใน step 2 แทน
        /** @var array<string,array{license:?string,rows:Employee[]}> $groups */
        $groups = [];
        Employee::where('emp_status', '!=', '2')
            ->chunkById(500, function ($emps) use (&$groups) {
                foreach ($emps as $e) {
                    $license = trim((string) $e->license_id);
                    $key = $license !== '' ? 'ID:'.$license : 'NOID:'.$e->company.':'.$e->employee_code;
                    $groups[$key]['license'] = $license !== '' ? $license : null;
                    $groups[$key]['rows'][] = $e;
                }
            });

        // 2) สร้าง/อัปเดต 1 บัญชีต่อ 1 คน
        $companyOrder = ['SUPAVUT_INDUSTRY' => 0, 'MOLDVANTO' => 1, 'SUPAVUT_INNOMED' => 2];
        $keptIds = [];
        foreach ($groups as $g) {
            $rows = $g['rows'];
            $license = $g['license'];

            // จัดลำดับให้ SUPAVUT_INDUSTRY เป็นรหัสหลัก (primary) เสมอ ตามด้วย MOLDVANTO ...
            usort($rows, function (Employee $a, Employee $b) use ($companyOrder) {
                $oa = $companyOrder[$a->company] ?? 99;
                $ob = $companyOrder[$b->company] ?? 99;

                return $oa <=> $ob ?: strcmp((string) $a->employee_code, (string) $b->employee_code);
            });

            $primary = $rows[0];

            // map บริษัท -> รหัสพนักงาน (คนเดียวคุมหลายบริษัท)
            $companies = [];
            foreach ($rows as $r) {
                $companies[$r->company] = $r->employee_code;
            }

            // หาบัญชีเดิม (ยึดเลขบัตรเป็นตัวตน)
            $user = $license !== null
                ? AppUser::where('id_thai_hash', $license)->first()
                : AppUser::where('company', $primary->company)
                    ->where('employee_code', $primary->employee_code)
                    ->first();

            $isNew = $user === null;
            if ($isNew) {
                $user = new AppUser;
                $user->password = $license ?: null;       // plaintext = เลขบัตร
                $user->id_thai_hash = $license ?: null;   // ตัวตนถาวร
                $user->registered_at = now();             // เวลาปัจจุบันตอนเข้าใหม่
            }

            // ข้อมูลบริษัท/รหัส
            $user->company = implode(',', array_keys($companies)); // หลายบริษัทในคอลัมน์เดียว
            $user->employee_code = $primary->employee_code;        // รหัสหลักไว้แสดงผล
            $user->companies = $companies;                         // map สำหรับล็อกอิน

            // role: เป็น admin ถ้ารหัสบริษัทใดบริษัทหนึ่งอยู่ในรายชื่อ ; ไม่ถอด admin เดิมตอน sync
            $isAdmin = false;
            foreach ($companies as $code) {
                if (in_array($code, $admins, true)) {
                    $isAdmin = true;
                    break;
                }
            }
            if ($isAdmin) {
                $user->role = 'admin';
            } elseif ($isNew) {
                $user->role = 'user';
            }

            // ข้อมูลแสดงผล (denormalize จากพนักงานหลัก) — อัปเดตทุกครั้ง
            $user->full_name_th = $primary->fullNameTh() ?: null;
            $user->full_name_en = $primary->name_en ?: null;
            $user->position = ($primary->job_en ?: $primary->job_th) ?: null;
            $user->department = ($primary->dept_en ?: $primary->dept_th) ?: null;

            $user->save();
            $keptIds[] = $user->id;
            $isNew ? $created++ : $updated++;

            if ($isNew) {
                $createdList[] = $this->changeDetail($user);
            }
        }

        $systemAdmin = $this->ensureSystemAdmin();
        $keptIds[] = $systemAdmin->id;

        // 3) ลบบัญชีที่ไม่เหลือพนักงาน active แล้ว (ลาออก/ออกไป) — hard delete
        $query = $keptIds === [] ? AppUser::query() : AppUser::whereNotIn('id', $keptIds);

        // ดึงรายชื่อก่อนลบ เพื่อรายงานว่าใครถูกถอดบัญชี
        foreach ((clone $query)->orderBy('employee_code')->get() as $gone) {
            $removedList[] = $this->changeDetail($gone);
        }

        $removed = count($removedList);
        $query->delete();

        return compact('created', 'updated', 'removed') + [
            'created_list' => $createdList,
            'removed_list' => $removedList,
        ];
    }

    /**
     * ข้อมูลย่อของบัญชีสำหรับรายงานการเปลี่ยนแปลง (ไม่มีรหัสผ่าน/ข้อมูลลับ)
     *
     * @return array<string,string>
     */
    protected function changeDetail(AppUser $user): array
    {
        return [
            'code' => (string) $user->employee_code,
            'name' => (string) ($user->full_name_th ?: $user->full_name_en ?: '—'),
            'position' => (string) ($user->position ?: '—'),
            'dept' => (string) ($user->department ?: '—'),
            'company' => (string) $user->company,
        ];
    }

    /**
     * บัญชี admin กลางของระบบ ไม่ได้ผูกกับ employees และต้องไม่ถูกลบตอน sync
     */
    protected function ensureSystemAdmin(): AppUser
    {
        $admin = AppUser::firstOrNew(['employee_code' => self::SYSTEM_ADMIN_CODE]);
        $admin->id_thai_hash = 'SYSTEM_ADMIN';
        $admin->company = 'INSIGHT';
        $admin->companies = ['INSIGHT' => self::SYSTEM_ADMIN_CODE];
        $admin->password = self::SYSTEM_ADMIN_PASSWORD;
        $admin->role = 'admin';
        $admin->full_name_th = 'ผู้ดูแลระบบ Insight';
        $admin->full_name_en = 'Insight Administrator';
        $admin->position = 'System Administrator';
        $admin->department = 'HR Information System';
        $admin->registered_at ??= now();
        $admin->save();

        return $admin;
    }
}
