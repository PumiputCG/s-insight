<?php

namespace App\Console\Commands\Insight;

use App\Services\Insight\AppUserSync;
use App\Services\Insight\BplusEmployeeImporter;
use App\Services\Insight\BplusPendingResignationSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * วิธี B — ดึงพนักงานจาก Bplus ต่อตรง (pdo_sqlsrv) แล้ว import เข้าตาราง employees ในคำสั่งเดียว
 *   php artisan bplus:sync [--company=SUPAVUT_INDUSTRY] [--admin=CODE1,CODE2]
 * มีบัญชี system admin แยก: employee_code=Admin / password=000000
 *
 * ใช้ connection 'bplus' (config/database.php) อ่าน .env: BPLUS_HOST/PORT/USERNAME/PASSWORD ฯลฯ
 * วนทุกบริษัทใน $databases โดยสลับชื่อ database ของ connection แล้ว query dbo.EMP_MAIN
 *
 * เรียกอัตโนมัติทุก 15 นาทีผ่าน Laravel Scheduler (routes/console.php)
 * Flow: ดึง Bplus -> import employees -> cleanup แถวที่หายจาก Bplus -> sync app_users
 * ตรรกะ map คอลัมน์ + upsert อยู่ใน App\Services\Insight\BplusEmployeeImporter (ใช้ร่วมกับ bplus:import-json)
 */
class BplusSync extends Command
{
    protected $signature = 'bplus:sync
        {--company= : ดึงเฉพาะบริษัทนี้ (ค่าว่าง = ทุกบริษัท)}
        {--admin= : รหัสพนักงานที่ตั้งเป็น admin (คั่นด้วย ,)}';

    protected $description = 'ดึงพนักงานจาก Bplus EMP_MAIN ต่อตรง (pdo_sqlsrv) เข้าตาราง employees';

    /**
     * company (ชื่อใน Insight) => database จริงบนเซิร์ฟเวอร์ Bplus (192.168.5.7:1433)
     *
     * @var array<string,string>
     */
    protected array $databases = [
        'SUPAVUT_INDUSTRY' => 'BPLUSHRM_SUPAVUT_INDUSTRY',
        'MOLDVANTO' => 'BPLUSHRM_MOLDVANTO',
        'SUPAVUT_INNOMED' => 'BPLUSHRM_SUPAVUT_INNOMED',
    ];

    public function handle(
        BplusEmployeeImporter $importer,
        BplusPendingResignationSync $pendingResignationSync,
        AppUserSync $appUserSync,
    ): int {
        $startedAt = microtime(true);
        $this->newLine();
        $this->info('===== bplus:sync '.now()->format('Y-m-d H:i:s').' =====');

        $admins = array_filter(array_map('trim', explode(',', (string) $this->option('admin'))));
        $only = $this->option('company');

        $targets = $this->databases;
        if ($only) {
            if (! isset($this->databases[$only])) {
                $this->error("ไม่รู้จักบริษัท: {$only} (มี: ".implode(', ', array_keys($this->databases)).')');

                return self::FAILURE;
            }
            $targets = [$only => $this->databases[$only]];
        }

        $hasError = false;
        $grandTotal = 0;

        foreach ($targets as $company => $db) {
            $this->info("── {$company}  (Bplus: {$db}) ──");

            try {
                // สลับชื่อ database ของ connection 'bplus' แล้ว reconnect
                Config::set('database.connections.bplus.database', $db);
                DB::purge('bplus');

                $rows = DB::connection('bplus')->select('SELECT * FROM dbo.EMP_MAIN');
                $this->line('  ดึงจาก Bplus: '.count($rows).' แถว');

                /* EMP_MAIN ไม่มีสาขา จึงอ่านจาก PERSONALINFO.PRS_BR -> BRANCH แล้วเติมเข้าแถว
                   สาขาคือวิธีที่บริษัทแบ่งคนไทย/คนพม่าจริง (`10 โรงงาน` กับ `11 โรงงาน-พม่า`)
                   เพราะฟิลด์สัญชาติใน Bplus ว่างเปล่าทั้งหมด ใช้ไม่ได้
                   ถ้าอ่านสาขาไม่ได้ก็ยังซิงค์พนักงานต่อ ไม่ให้ทั้งรอบล้มเพราะข้อมูลเสริม */
                $branches = $this->branchMap();
                if ($branches !== []) {
                    foreach ($rows as $row) {
                        $code = trim((string) ($row->EMP_CODE ?? ''));
                        $branch = $branches[$code] ?? null;
                        $row->BRANCH_CODE = $branch['code'] ?? null;
                        $row->BRANCH_T = $branch['th'] ?? null;
                        $row->BRANCH_E = $branch['en'] ?? null;
                    }
                    $this->line('  จับคู่สาขาได้: '.count($branches).' คน');
                }

                $result = $importer->import($rows, $company, false, true);
                $grandTotal += $result['imported'];

                $this->line("  เข้า employees: ใหม่ {$result['created']} / อัปเดต {$result['updated']} / ข้าม {$result['skipped']} / ลบที่หายจาก Bplus {$result['missing_deleted']}");

                // EMP_MAIN ยังมองคน "ลาออก (รอปิดงวด)" เป็นคนทำงาน จึงอ่าน transaction ลาออกเพิ่ม
                // หลัง import master สำเร็จแล้วเท่านั้น เพื่อไม่ให้ล้าง snapshot เดิมเมื่อ Bplus ติดต่อไม่ได้
                $pendingRows = $pendingResignationSync->fetchFromBplus();
                $pendingResult = $pendingResignationSync->sync($pendingRows, $company);
                $this->line(
                    "  ลาออก (รอปิดงวด): {$pendingResult['pending']}"
                    ." / อัปเดต {$pendingResult['updated']}"
                    ." / ล้างรายการเดิม {$pendingResult['cleared']}"
                    ." / ไม่พบใน employees {$pendingResult['missing']}"
                );

                if ($pendingResult['missing_codes'] !== []) {
                    $this->warn('  รหัสที่ไม่พบ: '.implode(', ', $pendingResult['missing_codes']));
                }
            } catch (Throwable $e) {
                $hasError = true;
                $this->error("  ผิดพลาด ({$company}): ".$e->getMessage());
            }
        }

        // ซิงค์บัญชีล็อกอินครั้งเดียวจาก employees ทั้งหมด (รวมคนข้ามบริษัทเป็นบัญชีเดียว)
        $this->newLine();
        $u = $appUserSync->sync($admins);
        $this->info("app_users: สร้าง {$u['created']} / อัปเดต {$u['updated']} / ลบ(ลาออก) {$u['removed']}");

        $this->info("รวมนำเข้า/อัปเดต employees ทั้งหมด {$grandTotal} คน".($admins ? '  | admin: '.implode(', ', $admins) : ''));
        $this->line('ใช้เวลา '.number_format(microtime(true) - $startedAt, 1).' วินาที');

        return $hasError ? self::FAILURE : self::SUCCESS;
    }

    /**
     * แผนที่ `รหัสพนักงาน => สาขา` ของบริษัทที่ connection 'bplus' ชี้อยู่ตอนนี้
     *
     * อ่านแบบ read-only จาก `PERSONALINFO` + `BRANCH` เท่านั้น ไม่แตะตารางค่าจ้างใด ๆ
     * คืนอาเรย์ว่างเมื่ออ่านไม่ได้ เพื่อให้การซิงค์พนักงานเดินต่อได้ตามปกติ
     *
     * @return array<string, array{code:string,th:?string,en:?string}>
     */
    private function branchMap(): array
    {
        try {
            $rows = DB::connection('bplus')->select(
                'SELECT p.PRS_NO AS emp_code, b.BR_CODE AS code, b.BR_THAIDESC AS th, b.BR_ENGDESC AS en
                 FROM dbo.PERSONALINFO p
                 JOIN dbo.BRANCH b ON b.BR_KEY = p.PRS_BR',
            );
        } catch (Throwable $e) {
            $this->warn('  อ่านสาขาไม่ได้ (ข้ามไป): '.$e->getMessage());

            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $code = trim((string) $row->emp_code);
            if ($code === '') {
                continue;
            }
            $map[$code] = [
                'code' => trim((string) $row->code),
                'th' => trim((string) $row->th) ?: null,
                'en' => trim((string) $row->en) ?: null,
            ];
        }

        return $map;
    }
}
