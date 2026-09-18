<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class A5sZoneMap extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_zone_maps';

    protected $fillable = ['zone_id', 'name', 'image_path', 'sort', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(A5sZone::class, 'zone_id');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(A5sZoneMapArea::class, 'zone_map_id')->orderBy('sort');
    }
}
