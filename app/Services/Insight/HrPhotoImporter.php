<?php

namespace App\Services\Insight;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * ดึงรูปพนักงานจากโฟลเดอร์ของ HR เข้าระบบ
 *
 * เส้นทางข้อมูล:
 *   โฟลเดอร์ HR (\\192.168.5.1\_DriveZ\_HR\4.Photos Employees\<ปี พ.ศ.>\<รหัส>.jpg)
 *     -> storage/app/public/profiles/emp_<รหัส>.jpg
 *     -> employees.photo_path (+ app_users.profile_picture ถ้ามีบัญชี)
 *
 * ข้อจำกัดจริงที่ต้องออกแบบตาม (สำรวจเมื่อ 2026-09-02):
 * - โฟลเดอร์เป็น network share ที่ช้ามาก สแกนทั้งก้อน (สองหมื่นกว่าไฟล์) ไม่จบใน 9 นาที
 *   จึงสแกนเฉพาะโฟลเดอร์ปีปัจจุบัน + ไฟล์ที่วางหลวมๆ ที่ราก และจำไว้ใน employee_photo_files
 *   ว่าไฟล์ไหนดึงไปแล้ว รอบถัดไปจะข้ามได้เลย
 * - โฟลเดอร์ปี = ปีที่ถ่าย ไม่ใช่ปีที่เข้างาน (ปี 2568 มี 6,504 ไฟล์ มากกว่าพนักงานทั้งบริษัท)
 *   HR ถ่ายใหม่ยกบริษัทเป็นรอบ กติกาจึงเป็น "รูปใหม่สุดชนะ" ตามวันที่ไฟล์
 * - มีขยะปนเยอะ (.pdf .docx Thumbs.db .lnk ไฟล์ชื่อคนไม่ใช่รหัส) และไฟล์จำนวนมาก
 *   หาเจ้าของไม่ได้ ต้องเก็บเป็นรายงานให้ HR ตามเก็บ ไม่ใช่ข้ามเงียบๆ
 */
class HrPhotoImporter
{
    /** นามสกุลที่รับ — อย่างอื่นถือเป็นไฟล์ที่ไม่เกี่ยว */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    private string $root;

    private string $yearPrefix;

    private int $maxSize;

    private int $quality;

    public function __construct()
    {
        $this->root = rtrim((string) config('insight.hr_photos.path'), '/\\');
        $this->yearPrefix = (string) config('insight.hr_photos.year_folder_prefix');
        $this->maxSize = max(0, (int) config('insight.hr_photos.max_size'));
        $this->quality = min(100, max(40, (int) config('insight.hr_photos.jpeg_quality')));
    }

    public function sourcePath(): string
    {
        return $this->root;
    }

    /** เข้าโฟลเดอร์ต้นทางได้ไหม — ใช้เช็คก่อนขึ้นปุ่มในหน้าเว็บ */
    public function sourceReadable(): bool
    {
        return $this->root !== '' && @is_dir($this->root);
    }

    /**
     * ดึงรูปเข้าระบบหนึ่งรอบ
     *
     * @param  array{all?:bool,year?:string|null,dry_run?:bool,trigger?:string}  $options
     * @return array<string,mixed> สรุปผลของรอบนี้ (ใช้ทั้งใน CLI และหน้าเว็บ)
     */
    public function run(array $options = []): array
    {
        $all = (bool) ($options['all'] ?? false);
        $year = $options['year'] ?? null;
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $trigger = (string) ($options['trigger'] ?? 'cli');

        $runId = $dryRun ? null : DB::table('employee_photo_runs')->insertGetId([
            'started_at' => now(),
            'trigger' => $trigger,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = [
            'scanned' => 0, 'imported' => 0, 'updated' => 0, 'unchanged' => 0,
            'orphans' => 0, 'skipped' => 0, 'failed' => 0,
            'error' => null, 'dry_run' => $dryRun, 'resized' => $this->canResize(),
        ];

        if (! $this->sourceReadable()) {
            // เข้า share ไม่ได้ (VPN หลุด / เครื่องไม่มีสิทธิ์) — จบแบบไม่แตะข้อมูลเดิม
            $summary['error'] = 'เข้าโฟลเดอร์รูปของ HR ไม่ได้: '.$this->root;
            $this->closeRun($runId, $summary);

            return $summary;
        }

        $files = $this->collectFiles($all, $year, $summary);
        $candidates = $this->newestPerCode($files, $summary);

        $this->import($candidates, $dryRun, $summary);

        $this->closeRun($runId, $summary);

        return $summary;
    }

    /** โฟลเดอร์ที่จะสแกนรอบนี้ */
    private function scanDirectories(bool $all, ?string $year): array
    {
        // ไฟล์ที่วางหลวมๆ ที่รากก็นับด้วย HR วางไว้ตรงนั้นบ่อย
        $dirs = [$this->root];

        if ($all) {
            foreach ((array) glob($this->root.'/*', GLOB_ONLYDIR) as $dir) {
                $dirs[] = $dir;
            }

            return array_values(array_unique($dirs));
        }

        // ปกติเอาแค่โฟลเดอร์ปีปัจจุบัน เพราะ share ช้ามาก
        $buddhistYear = $year !== null && $year !== ''
            ? $year
            : (string) (now()->year + 543);

        $yearDir = $this->root.'/'.$this->yearPrefix.$buddhistYear;
        if (@is_dir($yearDir)) {
            $dirs[] = $yearDir;
        }

        return $dirs;
    }

    /**
     * อ่านรายชื่อไฟล์รูปในโฟลเดอร์ที่เลือก พร้อมเวลา/ขนาด
     *
     * @return array<int,array<string,mixed>>
     */
    private function collectFiles(bool $all, ?string $year, array &$summary): array
    {
        $found = [];

        foreach ($this->scanDirectories($all, $year) as $dir) {
            foreach ((array) @scandir($dir) as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                $path = $dir.'/'.$name;
                if (! @is_file($path)) {
                    continue;
                }

                $summary['scanned']++;

                $ext = mb_strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
                    $summary['skipped']++;

                    continue;
                }

                $found[] = [
                    'path' => $path,
                    'name' => $name,
                    'code' => (string) pathinfo($name, PATHINFO_FILENAME),
                    'mtime' => (int) @filemtime($path),
                    'size' => (int) @filesize($path),
                ];
            }
        }

        return $found;
    }

    /**
     * เลือกไฟล์ที่ใหม่ที่สุดของแต่ละรหัสพนักงาน และคัดไฟล์ที่หาเจ้าของไม่ได้ออกเป็นรายงาน
     *
     * @param  array<int,array<string,mixed>>  $files
     * @return array<string,array<string,mixed>>
     */
    private function newestPerCode(array $files, array &$summary): array
    {
        // รหัสที่ซ้ำข้ามบริษัท ชื่อไฟล์บอกไม่ได้ว่าของบริษัทไหน จึงไม่เดา
        $ambiguous = Employee::query()
            ->select('employee_code')
            ->groupBy('employee_code')
            ->havingRaw('COUNT(DISTINCT company) > 1')
            ->pluck('employee_code')
            ->flip();

        $codes = collect($files)->pluck('code')->unique()->values();
        $known = Employee::whereIn('employee_code', $codes)
            ->pluck('company', 'employee_code');

        $candidates = [];

        foreach ($files as $file) {
            $code = $file['code'];

            $reason = match (true) {
                ! preg_match('/^\d+$/', $code) => 'ชื่อไฟล์ไม่ใช่รหัสพนักงาน',
                $ambiguous->has($code) => 'รหัสนี้ซ้ำข้ามบริษัท ระบุตัวไม่ได้',
                ! $known->has($code) => 'ไม่พบรหัสนี้ในทะเบียนพนักงาน',
                default => null,
            };

            if ($reason !== null) {
                $summary['orphans']++;
                $this->recordFile($file, null, 'orphan', $reason, null);

                continue;
            }

            // รูปใหม่สุดชนะ — HR ถ่ายใหม่ทับของเดิมเป็นรอบ
            if (! isset($candidates[$code]) || $file['mtime'] > $candidates[$code]['mtime']) {
                $file['company'] = (string) $known->get($code);
                $candidates[$code] = $file;
            }
        }

        return $candidates;
    }

    /**
     * ก็อปไฟล์เข้า storage แล้วผูกกับพนักงาน
     *
     * @param  array<string,array<string,mixed>>  $candidates
     */
    private function import(array $candidates, bool $dryRun, array &$summary): void
    {
        $disk = Storage::disk('public');

        foreach ($candidates as $code => $file) {
            $target = 'profiles/emp_'.$code.'.jpg';

            if ($this->alreadyCurrent($file, $target, $disk)) {
                $summary['unchanged']++;

                continue;
            }

            if ($dryRun) {
                $summary[$disk->exists($target) ? 'updated' : 'imported']++;

                continue;
            }

            $isUpdate = $disk->exists($target);

            try {
                $bytes = $this->prepareImage($file['path']);
                if ($bytes === null) {
                    throw new \RuntimeException('อ่านไฟล์ต้นทางไม่ได้');
                }

                $disk->put($target, $bytes);
                $this->removeOtherExtensions($disk, $code, $target);
                $this->attachToEmployee($code, $target, $file);

                $summary[$isUpdate ? 'updated' : 'imported']++;
                $this->recordFile($file, $code, 'imported', null, $target);
            } catch (\Throwable $e) {
                $summary['failed']++;
                $this->recordFile($file, $code, 'failed', mb_substr($e->getMessage(), 0, 200), null);
            }
        }
    }

    /** ไฟล์นี้เคยดึงเข้ามาแล้วและยังเป็นตัวเดิมอยู่ไหม (ตัดสินจากเวลา+ขนาดของต้นทาง) */
    private function alreadyCurrent(array $file, string $target, $disk): bool
    {
        $row = DB::table('employee_photo_files')->where('source_path', $file['path'])->first();

        return $row !== null
            && $row->status === 'imported'
            && (int) $row->source_size === $file['size']
            && $row->source_modified_at !== null
            && Carbon::parse($row->source_modified_at)->getTimestamp() === $this->fileTime($file['mtime'])?->getTimestamp()
            && $disk->exists($target);
    }

    /** ย่อรูปถ้าเปิด gd ไว้ ไม่งั้นก็อปไฟล์เดิมไปเลย (ยังใช้งานได้ แค่ไฟล์ใหญ่กว่า) */
    private function prepareImage(string $path): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        if (! $this->canResize() || $this->maxSize <= 0) {
            return $raw;
        }

        try {
            $image = @imagecreatefromstring($raw);
            if ($image === false) {
                return $raw;
            }

            $w = imagesx($image);
            $h = imagesy($image);
            $long = max($w, $h);

            if ($long <= $this->maxSize) {
                imagedestroy($image);

                return $raw;
            }

            $scale = $this->maxSize / $long;
            $resized = imagescale($image, (int) round($w * $scale), (int) round($h * $scale));
            imagedestroy($image);

            if ($resized === false) {
                return $raw;
            }

            ob_start();
            imagejpeg($resized, null, $this->quality);
            $out = (string) ob_get_clean();
            imagedestroy($resized);

            return $out !== '' ? $out : $raw;
        } catch (\Throwable) {
            return $raw;
        }
    }

    private function canResize(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagescale');
    }

    /**
     * แปลงเวลาไฟล์เป็น Carbon ในโซนเวลาของแอป
     *
     * ต้องระบุโซนเวลาให้ชัด ไม่งั้น createFromTimestamp() จะได้เวลา UTC
     * พอเขียนลง DB แล้วอ่านกลับมาตีความเป็นเวลาไทย จะเพี้ยนไป 7 ชั่วโมง
     * ทำให้เทียบว่า "ไฟล์เดิมหรือเปล่า" ไม่ตรงสักที แล้วดึงรูปซ้ำทุกรอบ
     */
    private function fileTime(int $mtime): ?Carbon
    {
        return $mtime > 0 ? Carbon::createFromTimestamp($mtime, config('app.timezone')) : null;
    }

    /** กันรูปซ้ำคนเดียวกันคนละนามสกุล เช่นเคยเป็น emp_x.png แล้วรอบนี้เขียน emp_x.jpg */
    private function removeOtherExtensions($disk, string $code, string $keep): void
    {
        foreach (self::IMAGE_EXTENSIONS as $ext) {
            $other = 'profiles/emp_'.$code.'.'.$ext;
            if ($other !== $keep && $disk->exists($other)) {
                $disk->delete($other);
            }
        }
    }

    /** ผูกรูปเข้ากับพนักงาน (และบัญชีถ้ามี) — HR ชนะรูปที่อัปเองตามที่เจ้าของสั่ง */
    private function attachToEmployee(string $code, string $target, array $file): void
    {
        Employee::where('employee_code', $code)->update([
            'photo_path' => $target,
            'photo_source' => 'hr',
            'photo_taken_at' => $this->fileTime($file['mtime']),
            'photo_synced_at' => now(),
            'updated_at' => now(),
        ]);

        AppUser::where('employee_code', $code)->update([
            'profile_picture' => $target,
            'updated_at' => now(),
        ]);
    }

    /** บันทึกไฟล์ต้นทางลงสมุด เพื่อข้ามในรอบถัดไปและทำรายงานไฟล์ที่หาเจ้าของไม่ได้ */
    private function recordFile(array $file, ?string $code, string $status, ?string $reason, ?string $storedPath): void
    {
        DB::table('employee_photo_files')->updateOrInsert(
            ['source_path' => $file['path']],
            [
                'file_name' => $file['name'],
                'employee_code' => $code,
                'company' => $file['company'] ?? null,
                'source_modified_at' => $this->fileTime($file['mtime']),
                'source_size' => $file['size'],
                'status' => $status,
                'reason' => $reason,
                'stored_path' => $storedPath,
                'imported_at' => $status === 'imported' ? now() : null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function closeRun(?int $runId, array $summary): void
    {
        if ($runId === null) {
            return;
        }

        DB::table('employee_photo_runs')->where('id', $runId)->update([
            'finished_at' => now(),
            'scanned' => $summary['scanned'],
            'imported' => $summary['imported'],
            'updated' => $summary['updated'],
            'unchanged' => $summary['unchanged'],
            'orphans' => $summary['orphans'],
            'skipped' => $summary['skipped'],
            'failed' => $summary['failed'],
            'error' => $summary['error'],
            'updated_at' => now(),
        ]);
    }
}
