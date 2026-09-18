<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** การ์ดข้อมูลที่ผู้รับผิดชอบส่ง — หัวข้อ + รายละเอียด + รูป >= 1 (แต่ละคนเพิ่มของตัวเองในงานเดียวกัน) */
class A5sCard extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_cards';

    protected $fillable = ['task_id', 'title', 'detail', 'created_by', 'sort'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(A5sTask::class, 'task_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(A5sCardImage::class, 'card_id')->orderBy('sort')->orderBy('id');
    }
}
