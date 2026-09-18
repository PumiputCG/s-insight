<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** ชั้นในแต่ละโซน — ถาวร ไม่ผูกรอบเดือน (ตัวเลือกตอนสร้าง Layout ใช้ชั้นของโซนที่เลือก) */
class A5sFloor extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_floors';

    protected $fillable = ['zone_id', 'zone_map_area_id', 'name', 'sort', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(A5sZone::class, 'zone_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(A5sZoneMapArea::class, 'zone_map_area_id');
    }

    public function layouts(): HasMany
    {
        return $this->hasMany(A5sLayout::class, 'floor_id');
    }
}
