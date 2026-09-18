<?php

namespace App\Console\Commands\OtApproval;

use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\BplusAttendanceSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncBplusAttendance extends Command
{
    protected $signature = 'ot-approval:sync-attendance
        {date? : วันที่ทำงานรูปแบบ Y-m-d (ค่าเริ่มต้นคือวันนี้)}
        {--company= : ดึงเฉพาะบริษัทที่กำหนด}';

    protected $description = 'อ่านเวลาเข้า-ออกและกะจาก Bplus แล้วบันทึก Snapshot ลงฐาน Local OT Approval';

    public function handle(BplusAttendanceSnapshotService $snapshots): int
    {
        $dateInput = trim((string) ($this->argument('date') ?: now()->format('Y-m-d')));
        $company = trim((string) $this->option('company')) ?: null;

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateInput);
            if ($date === false || $date->format('Y-m-d') !== $dateInput) {
                throw new \InvalidArgumentException('Invalid date');
            }

            if ($date->isAfter(today())) {
                $this->error('วันที่ต้องไม่เกินวันนี้');

                return self::FAILURE;
            }

            if ($company !== null && ! array_key_exists($company, BplusAttendanceService::DATABASES)) {
                $this->error('ไม่รู้จักบริษัท '.$company);

                return self::FAILURE;
            }

            $this->info('กำลังอ่าน Bplus วันที่ '.$date->format('Y-m-d').' แบบ read-only...');
            $result = $snapshots->sync($date, $company);

            $this->table(
                ['บริษัท', 'พนักงาน', 'สแกนเข้า', 'สแกนออก', 'จำนวนกะ', 'บันทึก Local'],
                collect($result['companies'])->map(fn (array $row) => [
                    $row['company'],
                    $row['employees'],
                    $row['clocked_in'],
                    $row['clocked_out'],
                    $row['shifts'],
                    $row['saved'],
                ])->all(),
            );
            $this->info('บันทึก Snapshot สำเร็จ '.$result['total'].' คน');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('ดึง Attendance ไม่สำเร็จ: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
