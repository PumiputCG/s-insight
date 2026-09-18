<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คำอธิบายประกอบฟอร์มประเมิน — ต่อ (คอลัมน์ × ลำดับผู้ประเมิน)
 *
 * ผูกได้ทั้งหัวข้อหลักและคอลัมน์ย่อย · is_visible = ให้ผู้ประเมินเห็นหรือไม่
 */
class AsmBoxNote extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_box_notes';

    protected $fillable = [
        'box_id', 'slot', 'level', 'desc_th', 'desc_en', 'desc_my',
        'image_path', 'file_path', 'is_visible', 'updated_by',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(AsmScoreBox::class, 'box_id');
    }

    /** มีเนื้อหาให้แสดงไหม (ข้อความว่างทั้ง 3 ภาษา = ไม่ต้องแสดง) */
    public function hasContent(): bool
    {
        return trim((string) $this->desc_th) !== ''
            || trim((string) $this->desc_en) !== ''
            || trim((string) $this->desc_my) !== '';
    }

    /**
     * map สำหรับ view: [box_id => [level => note]]
     *
     * @return array<int,array<int,self>>
     */
    public static function matrix(): array
    {
        $out = [];
        foreach (static::whereNotNull('box_id')->get() as $note) {
            $out[(int) $note->box_id][(int) $note->level] = $note;
        }

        return $out;
    }

    /**
     * คำอธิบายของ "จุดพิเศษ" ที่ไม่ใช่คอลัมน์คะแนน เช่น slot 'total' (คะแนนรวมในหัวฟอร์ม)
     *
     * @return array<string,array<int,self>>
     */
    public static function slotMatrix(): array
    {
        $out = [];
        foreach (static::whereNotNull('slot')->get() as $note) {
            $out[(string) $note->slot][(int) $note->level] = $note;
        }

        return $out;
    }
}
