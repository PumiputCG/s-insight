<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** โซน/อาคารบนแปลนบริษัท — กรอบ polygon (พิกัด % ของภาพ) ถาวร ไม่ผูกรอบเดือน */
class A5sZone extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_zones';

    protected $fillable = ['company_plan_id', 'name', 'description', 'shape_points', 'color', 'sort', 'is_active', 'created_by'];

    protected $casts = ['shape_points' => 'array', 'is_active' => 'boolean'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(A5sCompanyPlan::class, 'company_plan_id');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(A5sFloor::class, 'zone_id')->orderBy('sort');
    }

    public function zoneMaps(): HasMany
    {
        return $this->hasMany(A5sZoneMap::class, 'zone_id')->orderBy('sort');
    }
}
