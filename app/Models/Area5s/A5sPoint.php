<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** จุดบน Layout — พิกัด x,y เป็น % ของภาพ (0-100) */
class A5sPoint extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_points';

    protected $fillable = ['layout_id', 'code', 'name', 'description', 'x', 'y', 'is_active', 'sort'];

    protected $casts = ['is_active' => 'boolean', 'x' => 'decimal:4', 'y' => 'decimal:4'];

    public function layout(): BelongsTo
    {
        return $this->belongsTo(A5sLayout::class, 'layout_id');
    }

    /** ผู้รับผิดชอบที่ยัง active (removed_at = null) */
    public function assignees(): HasMany
    {
        return $this->hasMany(A5sPointAssignee::class, 'point_id')->whereNull('removed_at');
    }
}
