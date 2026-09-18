<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class A5sZoneMapArea extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_zone_map_areas';

    protected $fillable = ['zone_map_id', 'name', 'description', 'shape_points', 'color', 'sort', 'is_active', 'created_by'];

    protected $casts = ['shape_points' => 'array', 'is_active' => 'boolean'];

    public function zoneMap(): BelongsTo
    {
        return $this->belongsTo(A5sZoneMap::class, 'zone_map_id');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(A5sFloor::class, 'zone_map_area_id')->orderBy('sort')->orderBy('name');
    }
}
