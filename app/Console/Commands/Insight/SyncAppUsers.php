<?php

namespace App\Console\Commands\Insight;

use App\Models\Insight\Employee;
use App\Services\Insight\AppUserSync;
use Illuminate\Console\Command;

/**
 * สร้าง/อัปเดต/ลบ บัญชีล็อกอิน (app_users) ให้ตรงกับตาราง employees
 *   php artisan appusers:sync [--admin=CODE1,CODE2]
 *
 * - พนักงาน active -> มีบัญชี (password เริ่มต้นของ user = เลขบัตร plaintext)
 * - มีบัญชี system admin แยก: employee_code=Admin / password=000000
 * - คนข้ามบริษัท (เลขบัตรเดียวกัน) -> รวมเป็นบัญชีเดียว (company เก็บหลายบริษัท)
 * - พนักงานลาออก -> ลบบัญชีทิ้งจริง แต่แถวใน employees ยังอยู่
 * ตรรกะอยู่ใน App\Services\Insight\AppUserSync (ทำงานทั้งระบบในครั้งเดียวเพื่อรวมข้ามบริษัท)
 */
class SyncAppUsers extends Command
{
    protected $signature = 'appusers:sync
        {--admin= : รหัสพนักงานที่ตั้งเป็น admin (คั่นด้วย ,)}';

    protected $description = 'ซิงค์บัญชีล็อกอิน (app_users) จากตารางพนักงานกลาง employees';

    public function handle(AppUserSync $sync): int
    {
        $admins = array_filter(array_map('trim', explode(',', (string) $this->option('admin'))));

        if (Employee::query()->doesntExist()) {
            $this->warn('ไม่มีข้อมูลใน employees — รัน bplus:sync หรือ bplus:import-json ก่อน');

            return self::SUCCESS;
        }

        $r = $sync->sync($admins);

        $this->info("app_users: สร้าง {$r['created']} / อัปเดต {$r['updated']} / ลบ(ลาออก) {$r['removed']}".
            ($admins ? '  | admin: '.implode(', ', $admins) : ''));

        return self::SUCCESS;
    }
}
