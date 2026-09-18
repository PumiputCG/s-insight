<?php

namespace App\Console\Commands\Insight;

use App\Models\Insight\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * หา/ลบ "คนซ้ำ" ในตาราง employees โดยยึด license_id (เลขบัตรประชาชน) เป็นตัวตนจริง
 *   php artisan employees:dedup [--company=] [--keep=newest|oldest] [--cross-company] [--apply]
 *
 * นิยาม "ซ้ำ":
 *   - เลขบัตร (license_id) เดียวกัน ปรากฏมากกว่า 1 แถว
 *
 * แยก 2 กรณี (ต้นเหตุมาจาก Bplus คืนหลาย EMP_CODE ต่อคน):
 *   A) ข้ามบริษัท  — license เดียวกันแต่คนละ company  -> ค่าเริ่มต้น "เก็บไว้ทั้งคู่" (by design hub
 *                    ป้อน 3 ระบบย่อยแยกตามบริษัท) ; จะยุบก็ต่อเมื่อใส่ --cross-company
 *   B) บริษัทเดียว — license เดียวกัน บริษัทเดียวกัน แต่คนละ employee_code -> ซ้ำจริง: เก็บ 1 ลบที่เหลือ
 *
 * เลือกแถวที่ "เก็บ" จาก employee_code:
 *   --keep=newest (ค่าเริ่มต้น) = เก็บรหัสมากสุด (สมมติว่ารหัสใหม่/ซีรีส์สูง = ปัจจุบัน)
 *   --keep=oldest             = เก็บรหัสน้อยสุด
 *   (numeric เทียบเป็นตัวเลข ; ถ้าไม่ใช่ตัวเลขล้วน เทียบเป็น string)
 *
 * ค่าเริ่มต้น = dry-run (รายงานอย่างเดียว ไม่ลบ) ; ใส่ --apply เพื่อ "ลบจริง"
 */
class DedupEmployees extends Command
{
    protected $signature = 'employees:dedup
        {--company= : จำกัดเฉพาะบริษัท (เว้นว่าง = ทุกบริษัท)}
        {--keep=newest : เมื่อซ้ำในบริษัทเดียว เก็บรหัสไหน: newest=รหัสมากสุด / oldest=รหัสน้อยสุด}
        {--cross-company : ยุบกรณีข้ามบริษัทด้วย (ค่าเริ่มต้น=ไม่ทำ เพราะ by design ให้คนอยู่ได้หลายบริษัท)}
        {--apply : ลบจริง (ไม่ใส่ = dry-run รายงานอย่างเดียว)}';

    protected $description = 'หา/ลบคนซ้ำ (ยึด license_id) — ค่าเริ่มต้น dry-run ; ใส่ --apply เพื่อลบจริง';

    public function handle(): int
    {
        $keep = $this->option('keep') === 'oldest' ? 'oldest' : 'newest';
        $crossCompany = (bool) $this->option('cross-company');
        $apply = (bool) $this->option('apply');
        $company = $this->option('company');

        // 1) ดึงแถวที่มี license_id (ไม่ว่าง) ทั้งหมด แล้วจัดกลุ่มในหน่วยความจำ
        $q = Employee::query()
            ->whereNotNull('license_id')->where('license_id', '<>', '')
            ->select(['id', 'company', 'employee_code', 'license_id', 'title', 'name_th', 'surname_th', 'job_th', 'dept_th']);
        if ($company) {
            $q->where('company', $company);
        }
        $all = $q->orderBy('license_id')->orderBy('employee_code')->get();

        // license_id => company => [rows]
        $byLicense = [];
        foreach ($all as $e) {
            $byLicense[$e->license_id][$e->company][] = $e;
        }

        $toDelete = [];   // แถวที่จะลบ
        $keptCount = 0;
        $crossKept = 0;   // กรณี A ที่เก็บไว้ (ไม่ลบ)
        $crossGroups = 0;
        $sameGroups = 0;

        foreach ($byLicense as $license => $companies) {
            $totalRows = array_sum(array_map('count', $companies));
            if ($totalRows < 2) {
                continue; // ไม่ซ้ำ
            }

            // กรณี A: ข้ามบริษัท
            if (count($companies) > 1) {
                $crossGroups++;
                if (! $crossCompany) {
                    // เก็บทั้งหมด (by design) — แต่ภายในบริษัทเดียวกันถ้าซ้ำก็ยังต้องยุบ
                    foreach ($companies as $rows) {
                        $crossKept += count($rows);
                        if (count($rows) > 1) {
                            $sameGroups++;
                            $this->pickDuplicates($rows, $keep, $toDelete, $keptCount);
                        } else {
                            $keptCount += count($rows);
                        }
                    }

                    continue;
                }

                // --cross-company: ยุบทุกแถวของ license นี้เหลือ 1
                $flat = [];
                foreach ($companies as $rows) {
                    foreach ($rows as $r) {
                        $flat[] = $r;
                    }
                }
                $this->pickDuplicates($flat, $keep, $toDelete, $keptCount);

                continue;
            }

            // กรณี B: บริษัทเดียว
            $rows = array_values($companies)[0];
            $sameGroups++;
            $this->pickDuplicates($rows, $keep, $toDelete, $keptCount);
        }

        // 2) รายงาน
        $this->newLine();
        $this->info('สแกนคนซ้ำจากเลขบัตร (license_id)'.($company ? " — บริษัท {$company}" : ' — ทุกบริษัท'));
        $this->line('  กลุ่มข้ามบริษัท (A): '.$crossGroups.($crossCompany ? '  (ยุบด้วย --cross-company)' : '  (เก็บไว้ทั้งคู่)'));
        $this->line('  กลุ่มซ้ำในบริษัทเดียว (B): '.$sameGroups);
        $this->line('  เก็บรหัส: '.($keep === 'newest' ? 'ใหม่สุด (มากสุด)' : 'เก่าสุด (น้อยสุด)'));

        if ($toDelete === []) {
            $this->newLine();
            $this->info('ไม่มีแถวที่ต้องลบ ✅');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('=== แถวที่จะ "ลบ" ('.count($toDelete).' แถว) ===');
        $this->table(
            ['license_id', 'company', 'code(ลบ)', 'ชื่อ', 'ตำแหน่ง', 'แผนก'],
            array_map(fn ($e) => [
                $e->license_id,
                $e->company,
                $e->employee_code,
                trim(($e->title ?? '').($e->name_th ?? '').' '.($e->surname_th ?? '')),
                $e->job_th ?: '-',
                $e->dept_th ?: '-',
            ], $toDelete)
        );

        if (! $apply) {
            $this->newLine();
            $this->warn('นี่คือ dry-run — ยังไม่ลบอะไร');
            $this->line('→ ตรวจรายการด้านบนให้ชัด แล้วรันซ้ำพร้อม --apply เพื่อลบจริง');

            return self::SUCCESS;
        }

        // 3) ลบจริง
        $ids = array_map(fn ($e) => $e->id, $toDelete);
        $deleted = 0;
        DB::transaction(function () use ($ids, &$deleted) {
            $deleted = Employee::whereIn('id', $ids)->delete();
        });

        Employee::reindexNo();

        $this->newLine();
        $this->info("ลบคนซ้ำแล้ว {$deleted} แถว (เหลือเก็บไว้ {$keptCount} แถวจากกลุ่มที่ซ้ำ)");

        return self::SUCCESS;
    }

    /**
     * จากชุดแถวที่ซ้ำ (>1) เลือก 1 แถวเก็บ ที่เหลือใส่ลง $toDelete
     *
     * @param  array<int,Employee>  $rows
     * @param  array<int,Employee>  $toDelete
     */
    protected function pickDuplicates(array $rows, string $keep, array &$toDelete, int &$keptCount): void
    {
        if (count($rows) < 2) {
            $keptCount += count($rows);

            return;
        }

        usort($rows, function ($a, $b) {
            $ca = (string) $a->employee_code;
            $cb = (string) $b->employee_code;
            if (ctype_digit($ca) && ctype_digit($cb)) {
                return (int) $ca <=> (int) $cb;
            }

            return strcmp($ca, $cb);
        });

        // หลัง sort: index 0 = น้อยสุด, ท้ายสุด = มากสุด
        $keepRow = $keep === 'newest' ? end($rows) : $rows[0];

        foreach ($rows as $r) {
            if ($r->id === $keepRow->id) {
                $keptCount++;
            } else {
                $toDelete[] = $r;
            }
        }
    }
}
