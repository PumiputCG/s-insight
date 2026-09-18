<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** ภาพแปลนบริษัท — วาดโซน/อาคารทับได้หลายจุด (รองรับหลายแปลนในอนาคต) */
class A5sCompanyPlan extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_company_plans';

    protected $fillable = ['name', 'image_path', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function zones(): HasMany
    {
        return $this->hasMany(A5sZone::class, 'company_plan_id')->orderBy('sort');
    }
}
