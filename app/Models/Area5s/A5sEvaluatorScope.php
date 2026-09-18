<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;

/** ขอบเขตผู้ตรวจ — point_id null = ทั้ง layout ; ระบุ = เจาะจุด (เจาะจงชนะ) */
class A5sEvaluatorScope extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_evaluator_scopes';

    protected $fillable = ['employee_code', 'layout_id', 'point_id', 'created_by'];
}
