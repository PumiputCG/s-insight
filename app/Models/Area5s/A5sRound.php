<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** รอบรายเดือน/ครั้งตรวจ — เปิดได้ทีละรอบ · 1 เดือนมีหลายครั้งตรวจ (seq + inspected_on) */
class A5sRound extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_rounds';

    protected $fillable = ['year', 'month', 'seq', 'inspected_on', 'status', 'deadline_at', 'opened_at', 'closed_at', 'closed_by'];

    protected $casts = ['inspected_on' => 'date', 'deadline_at' => 'datetime', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public static function open(): ?self
    {
        return static::query()->where('status', 'open')->orderByDesc('id')->first();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(A5sTask::class, 'round_id');
    }
}
