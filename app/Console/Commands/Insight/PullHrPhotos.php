<?php

namespace App\Console\Commands\Insight;

use App\Services\Insight\HrPhotoImporter;
use Illuminate\Console\Command;

/**
 * ดึงรูปพนักงานจากโฟลเดอร์ของ HR เข้าระบบ
 *   php artisan photos:pull                  ปกติ — เอาเฉพาะโฟลเดอร์ปีปัจจุบัน + ไฟล์ที่รากโฟลเดอร์
 *   php artisan photos:pull --all            สแกนทุกโฟลเดอร์ย้อนหลัง (ช้ามาก ใช้ตอนตั้งระบบครั้งแรก)
 *   php artisan photos:pull --year=2568      เจาะจงโฟลเดอร์ปี พ.ศ. เดียว
 *   php artisan photos:pull --dry-run        ดูผลอย่างเดียว ไม่เขียนอะไร
 *
 * ตรรกะทั้งหมดอยู่ใน HrPhotoImporter เพราะปุ่ม "ดึงรูปใหม่ตอนนี้" ในหน้า admin ใช้ตัวเดียวกัน
 */
class PullHrPhotos extends Command
{
    protected $signature = 'photos:pull
        {--all : สแกนทุกโฟลเดอร์ย้อนหลัง (ช้ามาก)}
        {--year= : ระบุโฟลเดอร์ปี พ.ศ. เช่น 2568}
        {--dry-run : แสดงผลอย่างเดียว ไม่บันทึก}';

    protected $description = 'ดึงรูปพนักงานจากโฟลเดอร์ของ HR เข้า storage แล้วผูกกับ employees.photo_path';

    public function handle(HrPhotoImporter $importer): int
    {
        $this->line('โฟลเดอร์ต้นทาง: '.$importer->sourcePath());

        if (! $importer->sourceReadable()) {
            $this->error('เข้าโฟลเดอร์ไม่ได้ — เช็คว่าเครื่องนี้ต่อ share ได้หรือยัง (VPN / สิทธิ์)');

            return self::FAILURE;
        }

        $started = microtime(true);

        $result = $importer->run([
            'all' => (bool) $this->option('all'),
            'year' => $this->option('year'),
            'dry_run' => (bool) $this->option('dry-run'),
            'trigger' => 'cli',
        ]);

        $this->newLine();
        $this->line('  ไฟล์ที่สแกน               : '.$result['scanned']);
        $this->line('  ดึงเข้าใหม่               : '.$result['imported']);
        $this->line('  อัปเดตรูปเดิม             : '.$result['updated']);
        $this->line('  เหมือนเดิม ข้าม           : '.$result['unchanged']);
        $this->line('  หาเจ้าของไม่ได้           : '.$result['orphans']);
        $this->line('  ไม่ใช่ไฟล์รูป ข้าม        : '.$result['skipped']);
        $this->line('  ล้มเหลว                   : '.$result['failed']);
        $this->line('  ใช้เวลา                   : '.number_format(microtime(true) - $started, 1).' วินาที');

        if (! $result['resized']) {
            $this->newLine();
            $this->warn('ยังไม่ได้เปิด PHP extension gd — ก็อปไฟล์ต้นฉบับทั้งไฟล์ (ไม่ได้ย่อรูป)');
        }

        if ($result['error']) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        if ($result['dry_run']) {
            $this->newLine();
            $this->warn('โหมด --dry-run ยังไม่บันทึกอะไร');
        }

        return self::SUCCESS;
    }
}
