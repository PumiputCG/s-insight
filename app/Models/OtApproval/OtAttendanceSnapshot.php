<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;

class OtAttendanceSnapshot extends Model
{
    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_attendance_snapshots';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'immutable_date',
        'punch_count' => 'integer',
        'punches' => 'array',
        'work_hours' => 'decimal:2',
        'synced_at' => 'immutable_datetime',
    ];
}
