<?php

namespace App\Models\Recruit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * เส้นทางอนุมัติเอกสาร Recruit (1 route = 1 เส้นทาง) — มีได้หลายเส้นทาง
 *
 * DCC จะเลือกเองตอนสร้างคำขอว่าจะเดินตามเส้นทางไหน
 * แต่ละเส้นทางมีการ์ด (steps) เรียงลำดับเป็นของตัวเอง
 */
class RecruitRoute extends Model
{
    protected $connection = 'mysql_recruit';

    protected $table = 'recruit_routes';

    protected $fillable = ['name', 'position'];

    public function steps(): HasMany
    {
        return $this->hasMany(RecruitStep::class, 'route_id');
    }

    /**
     * เส้นทางที่ DCC คนนี้ส่งคำขอของแผนก $deptCode ได้
     *
     * กฎ: เส้นทางที่การ์ด DCC ของ user คนนี้ระบุแผนก $deptCode ไว้
     * - ใส่แผนกเดียวกันหลายเส้นทาง → ได้หลายตัวเลือก
     * - ใส่แผนกต่างกันต่อเส้นทาง → ได้เส้นทางเดียวต่อแผนก
     * (จะถูกใช้ในฟอร์มสร้างคำขอ — DCC เลือกแผนกก่อน แล้วกรองเส้นทางที่เลือกได้)
     */
    public static function availableForDeptUser(int $appUserId, string $deptCode): Collection
    {
        return static::query()
            ->whereHas('steps', fn ($s) => $s->where('role', 'dcc')
                ->whereHas('members', fn ($m) => $m->where('app_user_id', $appUserId)
                    ->whereJsonContains('dept_codes', $deptCode)))
            ->orderBy('position')->orderBy('id')->get();
    }
}
