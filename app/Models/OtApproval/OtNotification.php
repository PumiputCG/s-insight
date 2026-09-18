<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** แจ้งเตือน in-app ของโมดูล OT — เห็นเฉพาะของพนักงานคนนั้น */
class OtNotification extends Model
{
    public const CATEGORY_OT = 'ot';

    public const CATEGORY_LEAVE = 'leave';

    /** แจ้ง admin ว่ามีเอกสารพร้อมส่งให้ HR — แยกแท็บจาก OT/การลา เพราะเป็นงานคนละบทบาท */
    public const CATEGORY_DOWNLOAD = 'download';

    public const TYPE_SUBMITTED = 'request_submitted';

    public const TYPE_DECIDED = 'request_decided';

    public const TYPE_DOWNLOAD_READY = 'download_ready';

    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_notifications';

    protected $fillable = [
        'employee_code', 'type', 'category', 'title', 'body', 'link', 'item_count', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'item_count' => 'integer',
    ];

    public function scopeOwnedBy(Builder $query, string $employeeCode): Builder
    {
        return $query->where('employee_code', $employeeCode);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
