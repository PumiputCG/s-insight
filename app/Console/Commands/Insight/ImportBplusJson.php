<?php

namespace App\Console\Commands\Insight;

use App\Services\Insight\BplusEmployeeImporter;
use Illuminate\Console\Command;

/**
 * วิธี A — นำเข้าพนักงาน "ทุกคอลัมน์" จากไฟล์ JSON ที่ดึงจาก Bplus EMP_MAIN (ผลลัพธ์ scripts/bplus_pull.ps1)
 *   php artisan bplus:import-json "<path.json>" <COMPANY> [--truncate]
 *
 * นำเข้าเฉพาะตาราง employees (mirror). บัญชีล็อกอินสร้างทีหลังด้วย appusers:sync
 * ตรรกะการ map คอลัมน์อยู่ใน App\Services\Insight\BplusEmployeeImporter (ใช้ร่วมกับ bplus:sync)
 */
class ImportBplusJson extends Command
{
    protected $signature = 'bplus:import-json
        {file : พาธไฟล์ JSON ที่ดึงจาก Bplus (ผลลัพธ์ scripts/bplus_pull.ps1)}
        {company : ชื่อบริษัท เช่น SUPAVUT_INDUSTRY หรือ MOLDVANTO}
        {--truncate : ล้างพนักงานของบริษัทนี้ก่อน import}';

    protected $description = 'นำเข้าพนักงานทุกคอลัมน์จากไฟล์ JSON ของ Bplus EMP_MAIN เข้าตาราง employees (เก็บแถวดิบใน source_raw)';

    public function handle(BplusEmployeeImporter $importer): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("ไม่พบไฟล์: {$file}");

            return self::FAILURE;
        }

        $company = $this->argument('company');

        $rows = json_decode((string) file_get_contents($file), true);
        if (! is_array($rows)) {
            $this->error('อ่าน JSON ไม่สำเร็จ หรือรูปแบบไม่ใช่ array ของ object');

            return self::FAILURE;
        }
        // ConvertTo-Json คืน object เดียวเมื่อมีแถวเดียว -> ห่อเป็น array
        if ($rows !== [] && array_keys($rows) !== range(0, count($rows) - 1)) {
            $rows = [$rows];
        }

        $this->info('ไฟล์: '.$file);
        $this->info('จำนวนแถวใน JSON: '.count($rows));

        if ($this->option('truncate')) {
            $this->warn("จะล้างพนักงานบริษัท {$company} เดิมก่อน import");
        }

        $result = $importer->import($rows, $company, (bool) $this->option('truncate'));

        if ($result['deleted']) {
            $this->warn("ล้างพนักงานบริษัท {$company} เดิม {$result['deleted']} แถว");
        }
        $this->info("employees: นำเข้า/อัปเดต {$result['imported']} คน (ข้าม {$result['skipped']} แถวที่ไม่มีรหัสพนักงาน)");

        $this->newLine();
        $this->info('เสร็จสิ้น ✅  ขั้นต่อไป: php artisan appusers:sync');

        return self::SUCCESS;
    }
}
