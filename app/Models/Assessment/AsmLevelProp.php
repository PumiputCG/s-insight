<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * สัดส่วนของระดับ (หัวข้อ 3.1) — ต่อ "ระดับ 1–5 × หัวข้อหลัก"
 *
 * mode: percent = สัดส่วน (weight = ตัวเลข รวมควรได้ 100 ต่อระดับ)
 *       extra   = เพิ่มเติม (+/- รวมแยกกับสัดส่วน)
 *       none    = ไม่คำนวณ (ข้อมูลระดับนั้นเป็น - หรือ N/A)
 */
class AsmLevelProp extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_level_props';

    protected $fillable = ['level', 'box_id', 'mode', 'weight'];

    protected $casts = [
        'level' => 'integer',
        'weight' => 'decimal:2',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(AsmScoreBox::class, 'box_id');
    }

    /** matrix สำหรับ UI: level => [box_id => ['mode' => ..., 'weight' => float]] */
    public static function matrix(): array
    {
        $out = [];
        foreach (static::all() as $p) {
            $out[$p->level][$p->box_id] = [
                'mode' => in_array($p->mode, ['extra', 'none'], true) ? $p->mode : 'percent',
                'weight' => (float) $p->weight,
            ];
        }

        return $out;
    }
}
