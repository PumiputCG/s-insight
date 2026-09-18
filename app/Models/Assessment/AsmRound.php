<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;

/**
 * รอบประเมิน — แยก 2 ชนิด (employee / self) เปิดได้ทีละรอบต่อชนิด
 *
 * ต้องมีรอบชนิดนั้นเปิดก่อนถึงจะจัดการส่วนนั้นได้:
 *   - employee เปิด → จัดการการประเมินพนักงาน (คะแนน/คอลัมน์/สัดส่วน/คำถาม/ผลลัพธ์)
 *   - self เปิด → เข้าการประเมินตัวเองได้
 *
 * เปิด/สลับรอบ = คะแนน/ผู้ประเมิน/ระดับ ของชนิดนั้นเป็นชุดของรอบที่เปิด (ของรอบอื่นเก็บไว้ switch ดูได้)
 */
class AsmRound extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_rounds';

    public const TYPE_EMPLOYEE = 'employee';

    public const TYPE_SELF = 'self';

    protected $fillable = ['type', 'name', 'year', 'status', 'opened_by', 'opened_by_name', 'opened_at', 'closed_at'];

    protected $casts = [
        'year' => 'integer',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /** รอบที่เปิดอยู่ของชนิดนั้น (มีได้ทีละรอบต่อชนิด) */
    public static function open(string $type = self::TYPE_EMPLOYEE): ?self
    {
        return static::where('type', $type)->where('status', 'open')->orderByDesc('id')->first();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_SELF ? 'ประเมินตัวเอง' : 'ประเมินพนักงาน';
    }
}
