<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * งานประจำเดือนต่อจุด (snapshot ณ ตอนเปิดรอบ — ประวัติเดือนก่อนไม่ขยับ)
 * status: not_started|draft|submitted|failed|resubmitted|passed · overdue คำนวณจาก round.deadline_at
 */
class A5sTask extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_tasks';

    protected $fillable = [
        'round_id', 'point_id', 'layout_id', 'layout_name', 'point_code', 'point_name', 'point_description',
        'assignees_json', 'evaluators_json', 'status', 'submitted_at', 'submit_count',
        'result', 'fail_reason', 'advice', 'evaluated_by', 'evaluated_at',
    ];

    protected $casts = [
        'assignees_json' => 'array', 'evaluators_json' => 'array',
        'submitted_at' => 'datetime', 'evaluated_at' => 'datetime',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(A5sPoint::class, 'point_id');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(A5sRound::class, 'round_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(A5sCard::class, 'task_id')->orderBy('sort')->orderBy('id');
    }

    /** ประวัติการส่ง→ตรวจต่อครั้ง (append-only) — โชว์ครั้งก่อน + คิด % */
    public function attempts(): HasMany
    {
        return $this->hasMany(A5sTaskAttempt::class, 'task_id')->orderBy('seq');
    }
}
