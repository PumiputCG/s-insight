<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;

/** ผู้รับผิดชอบจุด — ledger ไม่ลบแถว: ถอดคน = set removed_at (ประวัติไม่หาย) */
class A5sPointAssignee extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_point_assignees';

    protected $fillable = ['point_id', 'employee_code', 'assigned_by', 'assigned_at', 'removed_at', 'removed_by'];

    protected $casts = ['assigned_at' => 'datetime', 'removed_at' => 'datetime'];
}
