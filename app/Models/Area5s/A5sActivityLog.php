<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;

/** ประวัติการแก้ไข — ใคร ทำอะไร กับอะไร (เปลี่ยนผู้รับผิดชอบ/แก้จุด/เปิดปิด ฯลฯ) */
class A5sActivityLog extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_activity_log';

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'detail_json'];

    protected $casts = ['detail_json' => 'array'];

    public static function write(int $userId, string $action, string $subjectType, int $subjectId, array $detail = []): void
    {
        static::create([
            'user_id' => $userId, 'action' => $action,
            'subject_type' => $subjectType, 'subject_id' => $subjectId,
            'detail_json' => $detail ?: null,
        ]);
    }
}
