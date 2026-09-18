<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ประวัติการส่ง→ตรวจต่อครั้ง (append-only) ของ task หนึ่ง ๆ
 * result=pass -> 100% · result=fail -> 0% (ใช้คิดคะแนน) · cards_json = snapshot หัวข้อ+รายละเอียด (ไม่เก็บภาพ)
 */
class A5sTaskAttempt extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_task_attempts';

    protected $fillable = [
        'task_id', 'seq', 'result', 'fail_reason', 'advice', 'cards_json',
        'evaluated_by', 'submitted_at', 'evaluated_at',
    ];

    protected $casts = [
        'cards_json' => 'array',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
    ];

    /** คะแนนของ attempt นี้: ผ่าน=100 / ไม่ผ่าน=0 */
    public function scorePercent(): int
    {
        return $this->result === 'pass' ? 100 : 0;
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(A5sTask::class, 'task_id');
    }
}
