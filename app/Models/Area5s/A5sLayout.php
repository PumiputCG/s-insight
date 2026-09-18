<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Layout พื้นที่ (ภาพแผนผัง) — ปกใช้ภาพเดียวกัน · เจ้าของ = created_by (allocator แก้ได้เฉพาะของตัวเอง)
 * ผูกกับรอบเดือน (round_id): เปิดเดือนใหม่เริ่มว่าง · สลับกลับเดือนเก่า Layout เดือนนั้นกลับมา
 */
class A5sLayout extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_layouts';

    protected $fillable = ['round_id', 'floor_id', 'name', 'description', 'image_path', 'created_by', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function points(): HasMany
    {
        return $this->hasMany(A5sPoint::class, 'layout_id')->orderBy('sort')->orderBy('code');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(A5sRound::class, 'round_id');
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(A5sFloor::class, 'floor_id');
    }

    /** layout เป็นของรอบที่เปิดอยู่ (แก้ไข/บันทึกได้) หรือไม่ */
    public function inOpenRound(): bool
    {
        $open = A5sRound::open();

        return $open !== null && (int) $this->round_id === (int) $open->id;
    }
}
