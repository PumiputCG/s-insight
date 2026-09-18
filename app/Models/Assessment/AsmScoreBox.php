<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * คอลัมน์คะแนน (asm_score_boxes) — ชุดเดียวใช้ร่วมทุกคน (global)
 *
 * - parent_id = null → คอลัมน์ใหญ่/เดี่ยว ; มีค่า → คอลัมน์ย่อยของกล่องนั้น
 * - name = หัวคอลัมน์ใน Excel (ต้องไม่ซ้ำ) · weight = สัดส่วน · type = ประเภท (score/attendance/okr/bonus)
 */
class AsmScoreBox extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_score_boxes';

    protected $fillable = ['parent_id', 'name', 'type', 'code', 'weight', 'sort', 'att_form', 'rate', 'grade_cap', 'full_score', 'input_levels', 'scale_json'];

    protected $casts = [
        'weight' => 'decimal:4',
        'rate' => 'decimal:3',
        'full_score' => 'decimal:2',
        'sort' => 'integer',
        'scale_json' => 'array',
    ];

    /**
     * ชุดตัวเลือกคะแนนเริ่มต้น — ชุดเดิมที่เคยฮาร์ดโค้ดในแบบฟอร์ม
     * ใช้เมื่อ admin ยังไม่ได้ตั้งค่าของคอลัมน์นั้น
     *
     * @return array<int,array<string,mixed>>
     */
    public static function defaultScale(): array
    {
        return [
            ['th' => 'เสมอ', 'en' => 'Always', 'my' => 'အမြဲတမ်း', 'scores' => ['10', '9', '8'], 'na' => false],
            ['th' => 'บ่อย', 'en' => 'Often', 'my' => 'မကြာခဏ', 'scores' => ['7', '6', '5'], 'na' => false],
            ['th' => 'นานๆครั้ง', 'en' => 'Sometimes', 'my' => 'တစ်ခါတစ်ရံ', 'scores' => ['4', '3', '2'], 'na' => false],
            ['th' => 'แทบไม่เคย', 'en' => 'Almost never', 'my' => 'အလွန်နည်းပါး', 'scores' => ['1'], 'na' => false],
            ['th' => 'ไม่เคย', 'en' => 'Never', 'my' => 'ဘယ်တော့မှမ', 'scores' => ['0'], 'na' => false],
            ['th' => 'ไม่ประเมิน', 'en' => 'Not assessed', 'my' => 'မအကဲဖြတ်ပါ', 'scores' => ['N/A'], 'na' => true],
        ];
    }

    /**
     * ตัวเลือกคะแนนที่ใช้จริงของคอลัมน์นี้ (ตั้งเองแล้วใช้ของตัวเอง · ยังไม่ตั้งใช้ค่าเริ่มต้น)
     *
     * @return array<int,array<string,mixed>>
     */
    public function scale(): array
    {
        $raw = $this->scale_json;

        return is_array($raw) && $raw !== [] ? $raw : static::defaultScale();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AsmScoreBox::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AsmScoreBox::class, 'parent_id')->orderBy('sort')->orderBy('id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AsmEmployeeScore::class, 'box_id');
    }
}
