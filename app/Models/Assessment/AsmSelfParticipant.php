<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * รายชื่อพนักงานที่ Admin เลือกให้ทำ Self-assessment แยกตามรอบ
 * ข้อมูลชื่อ ตำแหน่ง และแผนกอ่านจาก Employee master ปัจจุบัน
 */
class AsmSelfParticipant extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_self_participants';

    protected $fillable = [
        'round_id',
        'employee_code',
        'is_selected',
        'selected_by',
        'selected_at',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
        'selected_at' => 'datetime',
    ];

    public function scopeSelected(Builder $query): Builder
    {
        return $query->where('is_selected', true);
    }
}
