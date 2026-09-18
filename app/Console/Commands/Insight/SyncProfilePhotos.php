<?php

namespace App\Console\Commands\Insight;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * ผูกไฟล์รูปในโฟลเดอร์ storage/app/public/profiles เข้ากับพนักงานและบัญชีผู้ใช้
 *   php artisan profiles:sync [--keep-uploads] [--dry-run]
 *
 * ไฟล์ที่ HR ถ่ายให้ตั้งชื่อ emp_<รหัสพนักงาน>.<นามสกุล> เช่น emp_71056.jpg
 * (ไฟล์ชื่ออื่นคือรูปที่พนักงานอัปโหลดเองผ่านหน้าโปรไฟล์ ไม่ถูกแตะที่นี่)
 *
 * เขียนลง 2 ที่:
 *   1) employees.photo_path      ที่อยู่จริงของรูป — รูปติดตัวพนักงานแม้ลาออกแล้วบัญชีถูกลบ
 *   2) app_users.profile_picture ให้หน้าเดิมที่อ่านจากบัญชียังเห็นรูปเหมือนเดิม
 *
 * กติกาเรื่องรูปทับกัน (เจ้าของสั่ง 2026-09-02): รูปจาก HR ชนะเสมอ
 * เพราะโฟลเดอร์ของ HR คือแหล่งจริง — ถ้าต้องการพฤติกรรมเดิมให้ใส่ --keep-uploads
 */
class SyncProfilePhotos extends Command
{
    protected $signature = 'profiles:sync
        {--keep-uploads : ไม่ทับรูปที่พนักงานอัปโหลดเอง (พฤติกรรมเดิมก่อน 2026-09-02)}
        {--dry-run : แสดงผลอย่างเดียว ไม่บันทึกลงฐานข้อมูล}';

    protected $description = 'ผูกไฟล์รูป profiles/emp_<code> เข้ากับ employees.photo_path และ app_users.profile_picture';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $keepUploads = (bool) $this->option('keep-uploads');
        $dryRun = (bool) $this->option('dry-run');

        if (! $disk->exists('profiles')) {
            $this->error('ไม่พบโฟลเดอร์ storage/app/public/profiles');

            return self::FAILURE;
        }

        $photos = $this->hrPhotoMap($disk);

        if ($photos === []) {
            $this->warn('ไม่พบไฟล์รูปแบบ emp_<code>.<ext> ในโฟลเดอร์ profiles');

            return self::SUCCESS;
        }

        $this->info('พบไฟล์รูปจาก HR '.count($photos).' ไฟล์');

        // รหัสที่ซ้ำข้ามบริษัท — ชื่อไฟล์ emp_<code> บอกไม่ได้ว่าเป็นของบริษัทไหน จึงข้ามและรายงาน
        $ambiguous = Employee::query()
            ->select('employee_code')
            ->groupBy('employee_code')
            ->havingRaw('COUNT(DISTINCT company) > 1')
            ->pluck('employee_code')
            ->flip();

        $stat = $this->syncEmployees($disk, $photos, $ambiguous, $dryRun);
        $users = $this->syncAppUsers($disk, $photos, $keepUploads, $dryRun);

        $this->report($stat, $users, $ambiguous->count());

        if ($dryRun) {
            $this->newLine();
            $this->warn('โหมด --dry-run ยังไม่บันทึกลงฐานข้อมูล');
        }

        return self::SUCCESS;
    }

    /**
     * map: รหัสพนักงาน -> path ของรูปที่ HR ถ่ายให้
     *
     * @return array<string,string>
     */
    private function hrPhotoMap($disk): array
    {
        $photos = [];

        foreach ($disk->files('profiles') as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            if (! str_starts_with($name, 'emp_')) {
                continue;
            }
            $code = substr($name, 4);
            if ($code !== '') {
                $photos[$code] = $path;
            }
        }

        return $photos;
    }

    /**
     * เขียนรูปลงตัวพนักงาน — วนทุกคนรวมคนลาออก เพราะรูปต้องไม่หายตอนบัญชีถูกลบ
     *
     * @param  array<string,string>  $photos
     * @return array<string,int>
     */
    private function syncEmployees($disk, array $photos, $ambiguous, bool $dryRun): array
    {
        $stat = ['linked' => 0, 'updated' => 0, 'fallback' => 0, 'ambiguous' => 0, 'nophoto' => 0];

        Employee::query()->chunkById(300, function ($employees) use ($photos, $disk, $dryRun, $ambiguous, &$stat) {
            // รูปที่พนักงานอัปโหลดเอง ใช้เป็นตัวสำรองเมื่อ HR ยังไม่ได้ถ่ายให้
            $uploads = AppUser::whereIn('employee_code', $employees->pluck('employee_code'))
                ->pluck('profile_picture', 'employee_code');

            foreach ($employees as $emp) {
                $code = (string) $emp->employee_code;

                if ($ambiguous->has($code)) {
                    $stat['ambiguous']++;

                    continue;
                }

                $hrPhoto = $photos[$code] ?? null;
                $upload = $this->usableUpload($disk, (string) ($uploads[$code] ?? ''));

                if ($hrPhoto !== null) {
                    [$path, $source] = [$hrPhoto, 'hr'];
                } elseif ($upload !== '') {
                    [$path, $source] = [$upload, 'upload'];
                } else {
                    $stat['nophoto']++;

                    continue;
                }

                if ($emp->photo_path === $path && $emp->photo_source === $source) {
                    continue;
                }

                if ($source === 'upload') {
                    $stat['fallback']++;
                } elseif (trim((string) $emp->photo_path) === '') {
                    $stat['linked']++;
                } else {
                    $stat['updated']++;
                }

                if (! $dryRun) {
                    $emp->forceFill([
                        'photo_path' => $path,
                        'photo_source' => $source,
                        'photo_taken_at' => $this->fileTime($disk, $path),
                        'photo_synced_at' => now(),
                    ])->save();
                }
            }
        });

        return $stat;
    }

    /** รูปที่พนักงานอัปเองจะใช้ได้ต่อเมื่อไฟล์ยังอยู่จริง กัน path ค้างชี้ไฟล์ที่ถูกลบไปแล้ว */
    private function usableUpload($disk, string $path): string
    {
        $path = trim($path);

        if ($path === '' || str_starts_with($path, 'profiles/emp_')) {
            return '';
        }

        return $disk->exists($path) ? $path : '';
    }

    /**
     * เขียนกลับเข้าบัญชีผู้ใช้ ให้หน้าที่ยังอ่านจาก app_users เห็นรูปตรงกัน
     *
     * @param  array<string,string>  $photos
     * @return array<string,int>
     */
    private function syncAppUsers($disk, array $photos, bool $keepUploads, bool $dryRun): array
    {
        $u = ['linked' => 0, 'repaired' => 0, 'overwritten' => 0, 'kept' => 0, 'missing' => 0];

        AppUser::query()->chunkById(200, function ($users) use ($photos, $disk, $keepUploads, $dryRun, &$u) {
            foreach ($users as $user) {
                $photo = $photos[(string) $user->employee_code] ?? null;

                if ($photo === null) {
                    $u['missing']++;

                    continue;
                }

                $current = (string) $user->profile_picture;

                if ($current === $photo) {
                    continue;
                }

                $hasCurrentFile = $current !== '' && $disk->exists($current);

                if ($hasCurrentFile && $keepUploads) {
                    $u['kept']++;

                    continue;
                }

                if ($hasCurrentFile) {
                    $u['overwritten']++;
                } elseif ($current !== '') {
                    $u['repaired']++;
                } else {
                    $u['linked']++;
                }

                if (! $dryRun) {
                    $user->forceFill(['profile_picture' => $photo])->save();
                }
            }
        });

        return $u;
    }

    /**
     * @param  array<string,int>  $stat
     * @param  array<string,int>  $users
     */
    private function report(array $stat, array $users, int $ambiguousCodes): void
    {
        $this->newLine();
        $this->line('<comment>พนักงาน (employees.photo_path)</comment>');
        $this->line('  ผูกรูปใหม่ (ยังไม่เคยมี)       : '.$stat['linked']);
        $this->line('  เปลี่ยนเป็นรูปใหม่จาก HR       : '.$stat['updated']);
        $this->line('  ใช้รูปที่พนักงานอัปเอง (สำรอง) : '.$stat['fallback']);
        $this->line('  ข้าม รหัสซ้ำข้ามบริษัท         : '.$stat['ambiguous'].' คน ('.$ambiguousCodes.' รหัส)');
        $this->line('  ยังไม่มีรูปเลย                 : '.$stat['nophoto']);

        $this->newLine();
        $this->line('<comment>บัญชีผู้ใช้ (app_users.profile_picture)</comment>');
        $this->line('  ผูกรูปใหม่                     : '.$users['linked']);
        $this->line('  ซ่อมค่าที่ชี้ไฟล์หาย           : '.$users['repaired']);
        $this->line('  ทับรูปที่อัปโหลดเอง            : '.$users['overwritten']);
        $this->line('  คงรูปที่อัปโหลดเองไว้          : '.$users['kept']);
        $this->line('  ไม่มีไฟล์รูปของรหัสนี้         : '.$users['missing']);
    }

    /** วันที่ของไฟล์ — บอกว่ารูปถ่ายเมื่อไหร่ ถ้าอ่านไม่ได้ปล่อยเป็น null */
    private function fileTime($disk, string $path): ?Carbon
    {
        try {
            return Carbon::createFromTimestamp($disk->lastModified($path));
        } catch (\Throwable) {
            return null;
        }
    }
}
