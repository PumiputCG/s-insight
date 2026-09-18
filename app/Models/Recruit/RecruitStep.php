<?php

namespace App\Models\Recruit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ขั้นเส้นทางเอกสาร Recruit (1 step = 1 การ์ด) — อยู่ใต้ route, เรียงตาม position, role admin เลือกเอง
 */
class RecruitStep extends Model
{
    protected $connection = 'mysql_recruit';

    protected $table = 'recruit_steps';

    protected $fillable = ['route_id', 'position', 'role'];

    public const ROLES = ['dcc', 'manager', 'general_manager', 'hr_manager', 'recruit'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(RecruitRoute::class, 'route_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(RecruitMember::class, 'step_id');
    }
}
