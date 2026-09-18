<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;

/**
 * ระดับตำแหน่ง (asm_position_levels) — map ตำแหน่ง (job_code) → ระดับ 1–5
 *
 * admin จัดตำแหน่งลงการ์ดระดับ (เช่น Operator=1, Staff=2, CEO=5)
 * ใช้เติมคอลัมน์ "ระดับตำแหน่ง (1–5)" ในฟอร์มดาวน์โหลด
 */
class AsmPositionLevel extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_position_levels';

    protected $fillable = ['round_id', 'job_code', 'level'];

    protected $casts = ['level' => 'integer'];

    /** map job_code => level ของรอบนั้น (null = ไม่กรอง — ไม่ควรใช้หลังมีระบบรอบ) */
    public static function map(?int $roundId = null): array
    {
        return static::when($roundId !== null, fn ($q) => $q->where('round_id', $roundId))
            ->pluck('level', 'job_code')->all();
    }
}
