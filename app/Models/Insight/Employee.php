<?php

namespace App\Models\Insight;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * พนักงาน (มาสเตอร์กลาง Insight) — mirror จาก Bplus EMP_MAIN เท่านั้น
 *
 * ไม่ใช่ตารางล็อกอินแล้ว (ล็อกอินอยู่ที่ app_users / โมเดล AppUser)
 * - เก็บพนักงานทุกคนรวมคนลาออก (emp_status=2) เพื่อรายงานย้อนหลัง
 * - license_id (เลขบัตร) "ไม่ซ่อน" ตามคำสั่งเจ้าของ (admin ต้องเปิดดูได้)
 */
class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'no',
        'company', 'employee_code', 'license_id',
        'title', 'gender', 'name_th', 'surname_th', 'name_en',
        'job_code', 'job_th', 'job_en',
        'dept_code', 'dept_th', 'dept_en',
        'hire_date', 'probation_end_date', 'resign_date',
        'pending_resign_date', 'pending_resign_transaction_key', 'pending_resign_synced_at',
        'manual_resign_date', 'manual_resign_reason', 'manual_resign_set_at',
        'emp_status',
        'photo_path', 'photo_source', 'photo_taken_at', 'photo_synced_at',
        'source_raw', 'synced_at',
    ];

    protected $casts = [
        'no' => 'integer',
        'hire_date' => 'date',
        'probation_end_date' => 'date',
        'resign_date' => 'date',
        'pending_resign_date' => 'date',
        'pending_resign_transaction_key' => 'integer',
        'pending_resign_synced_at' => 'datetime',
        'manual_resign_date' => 'date',
        'manual_resign_set_at' => 'datetime',
        'source_raw' => 'array',
        'synced_at' => 'datetime',
        'photo_taken_at' => 'datetime',
        'photo_synced_at' => 'datetime',
    ];

    /** บัญชีล็อกอินของพนักงานคนนี้ (มีเฉพาะคน active) */
    public function appUser(): HasOne
    {
        return $this->hasOne(AppUser::class, 'employee_code', 'employee_code')
            ->where('app_users.company', $this->company);
    }

    /**
     * URL รูปพนักงาน — คืน null ถ้าไม่มีรูป (ให้ฝั่ง view ใช้ไอคอนสำรองเอง)
     *
     * ลำดับ: รูปที่ HR ถ่ายให้ (employees.photo_path) ก่อน ตามที่เจ้าของสั่งว่า "เอารูปตามโฟลเดอร์"
     * ถ้าไม่มีค่อยถอยไปใช้รูปที่พนักงานอัปโหลดเองผ่านบัญชี
     * ตั้งใจให้เรียกได้แม้พนักงานลาออกไปแล้ว (บัญชีถูกลบ) เพราะรูปเกาะอยู่กับตัวพนักงาน
     */
    public function photoUrl(?string $appUserPicture = null): ?string
    {
        $path = trim((string) $this->photo_path) ?: trim((string) $appUserPicture);

        return $path !== '' ? asset('storage/'.$path) : null;
    }

    /** ลาออกแล้วตามวันมีผลหรือไม่ (Final master หรือรายการรอปิดงวด) */
    public function isResigned(): bool
    {
        return $this->isResignedAt(CarbonImmutable::today());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAt($query, CarbonImmutable::today());
    }

    public function scopeResigned(Builder $query): Builder
    {
        return $this->scopeResignedAt($query, CarbonImmutable::today());
    }

    /** พนักงานที่ยังทำงานในวันที่กำหนด ใช้รักษาประวัติ Attendance/OT ย้อนหลัง */
    public function scopeActiveAt(Builder $query, CarbonInterface|string $date): Builder
    {
        $date = $this->dateString($date);

        return $query
            ->where(function (Builder $hire) use ($date) {
                $hire->whereNull('hire_date')->orWhereDate('hire_date', '<=', $date);
            })
            ->where(function (Builder $manual) use ($date) {
                $manual->whereNull('manual_resign_date')
                    ->orWhereDate('manual_resign_date', '>', $date);
            })
            ->where(function (Builder $active) use ($date) {
                $active
                    ->where(function (Builder $working) use ($date) {
                        $working->where('emp_status', '1')
                            ->where(function (Builder $pending) use ($date) {
                                $pending->whereNull('pending_resign_date')
                                    ->orWhereDate('pending_resign_date', '>', $date);
                            });
                    })
                    ->orWhere(function (Builder $historicalFinal) use ($date) {
                        $historicalFinal->where('emp_status', '2')
                            ->whereNotNull('resign_date')
                            ->whereDate('resign_date', '>', $date);
                    });
            });
    }

    /** พนักงานที่ลาออกมีผลแล้วในวันที่กำหนด */
    public function scopeResignedAt(Builder $query, CarbonInterface|string $date): Builder
    {
        $date = $this->dateString($date);

        return $query->where(function (Builder $resigned) use ($date) {
            $resigned
                ->where(function (Builder $manual) use ($date) {
                    $manual->whereNotNull('manual_resign_date')
                        ->whereDate('manual_resign_date', '<=', $date);
                })
                ->orWhere(function (Builder $final) use ($date) {
                    $final->where('emp_status', '2')
                        ->where(function (Builder $effective) use ($date) {
                            $effective->whereNull('resign_date')
                                ->orWhereDate('resign_date', '<=', $date);
                        });
                })
                ->orWhere(function (Builder $pending) use ($date) {
                    $pending->where('emp_status', '1')
                        ->whereNotNull('pending_resign_date')
                        ->whereDate('pending_resign_date', '<=', $date);
                });
        });
    }

    /** ยังอยู่ใน Bplus กลุ่มลาออกรอปิดงวด ไม่รวมคนที่ Final master แล้ว */
    public function scopePendingResignation(Builder $query): Builder
    {
        return $query->where('emp_status', '1')
            ->whereNull('manual_resign_date')
            ->whereNotNull('pending_resign_date');
    }

    /** มีรายการลาออกแล้วจาก Final, Pending หรือ Local override โดยไม่จำกัดวันมีผล */
    public function scopeResignationRecorded(Builder $query): Builder
    {
        return $query->where(function (Builder $resignation): void {
            $resignation
                ->where('emp_status', '2')
                ->orWhere(function (Builder $pending): void {
                    $pending->where('emp_status', '1')
                        ->whereNotNull('pending_resign_date');
                })
                ->orWhereNotNull('manual_resign_date');
        });
    }

    /** ลาออก Final ใน Bplus หรือมี Local override ที่ Manager ยืนยันแล้ว */
    public function scopeFinalResigned(Builder $query): Builder
    {
        return $query->where(function (Builder $final): void {
            $final->where('emp_status', '2')->orWhereNotNull('manual_resign_date');
        });
    }

    /** ลาออกและปิดงวดใน Bplus แล้วเท่านั้น ไม่รวม Pending หรือ Local override */
    public function scopePayrollClosedResignation(Builder $query): Builder
    {
        return $query->where('emp_status', '2');
    }

    public function isResignedAt(CarbonInterface|string $date): bool
    {
        $date = CarbonImmutable::parse($this->dateString($date))->startOfDay();

        if ($this->manual_resign_date !== null
            && $this->manual_resign_date->startOfDay()->lessThanOrEqualTo($date)) {
            return true;
        }

        if ((string) $this->emp_status === '2') {
            return $this->resign_date === null || $this->resign_date->startOfDay()->lessThanOrEqualTo($date);
        }

        return (string) $this->emp_status === '1'
            && $this->pending_resign_date !== null
            && $this->pending_resign_date->startOfDay()->lessThanOrEqualTo($date);
    }

    public function effectiveResignDate(): ?CarbonInterface
    {
        if ((string) $this->emp_status === '2' && $this->resign_date !== null) {
            return $this->resign_date;
        }

        return $this->manual_resign_date ?? $this->pending_resign_date;
    }

    public function lifecycleStatus(): string
    {
        if ((string) $this->emp_status === '2' || $this->manual_resign_date !== null) {
            return 'resigned';
        }

        if ($this->pending_resign_date !== null) {
            return 'pending_resign';
        }

        return 'working';
    }

    private function dateString(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface
            ? $date->toDateString()
            : CarbonImmutable::parse($date)->toDateString();
    }

    public static function reindexNo(): void
    {
        $no = 1;

        self::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($employees) use (&$no) {
                foreach ($employees as $employee) {
                    self::query()
                        ->whereKey($employee->id)
                        ->update(['no' => $no++]);
                }
            });
    }

    public function fullNameTh(): string
    {
        $sur = trim((string) $this->surname_th);
        if ($sur === '*' || $sur === '-') {
            $sur = '';   // placeholder = ไม่มีนามสกุล
        }

        return trim(($this->title ?? '').($this->name_th ?? '').' '.$sur);
    }

    /**
     * ชื่อ-สกุลอังกฤษ — ใช้ name_en ถ้ามี ; ถ้าว่าง เติมจากชื่อ Latin ใน name_th/surname_th
     * (แรงงานต่างชาติมักกรอกชื่ออังกฤษไว้ในช่องชื่อไทย ช่อง name_en จึงว่าง)
     */
    public function fullNameEn(): string
    {
        $en = trim((string) $this->name_en);
        if ($en !== '') {
            return $en;
        }

        $parts = [];
        foreach ([$this->name_th, $this->surname_th] as $p) {
            $p = trim((string) $p);
            if ($p !== '' && $p !== '*' && $p !== '-') {
                $parts[] = $p;
            }
        }

        return trim(implode(' ', $parts));
    }

    /** ชื่อแผนกไทยแบบตัดวงเล็บตัวย่อท้ายออก เช่น "เทคโนโลยีสารสนเทศ (IT)" → "เทคโนโลยีสารสนเทศ" */
    public function deptThClean(): string
    {
        return trim(preg_replace('/\s*\([^()]*\)\s*$/u', '', (string) ($this->dept_th ?? '')));
    }

    /**
     * ชื่อแผนกสำหรับแสดงผล เช่น `Accounting (ACC)`
     *
     * Bplus เก็บชื่อเต็มไว้ใน dept_en และรหัสย่อที่คนในโรงงานเรียกกันจริงไว้ใน dept_th
     * (เช่น `Injection Moulding 1` กับ `MFG/IM1`) การ์ดจึงต้องมีทั้งคู่ —
     * ชื่อเต็มไว้อ่าน ตัวย่อไว้เทียบกับที่พูดกันหน้างาน
     */
    public function departmentLabel(): string
    {
        $full = trim((string) ($this->dept_en ?? ''));
        $short = $this->deptThClean();

        if ($full !== '' && $short !== '' && mb_strtolower($full) !== mb_strtolower($short)) {
            return $full.' ('.$short.')';
        }

        return $full !== '' ? $full : $short;
    }
}
