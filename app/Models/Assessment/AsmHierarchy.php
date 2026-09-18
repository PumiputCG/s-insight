<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;

/**
 * ลำดับชั้นผู้ประเมินต่อพนักงาน (asm_hierarchy) — HR กรอกเอง
 *
 * l1 Supervisor · l2 Division MGR · l3 Dept MGR · l4 Plant MGR (id + ชื่อ)
 */
class AsmHierarchy extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_hierarchy';

    protected $fillable = [
        'round_id', 'employee_code',
        'l1_id', 'l1_name', 'l2_id', 'l2_name',
        'l3_id', 'l3_name', 'l4_id', 'l4_name',
        'updated_by',
    ];
}
