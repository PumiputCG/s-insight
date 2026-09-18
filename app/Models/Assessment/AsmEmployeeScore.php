<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คะแนนรายพนักงานต่อกล่อง (asm_employee_scores) — EAV ทศนิยม 4 ตำแหน่ง
 *
 * - 1 แถว = (employee_code, box_id) ; value = null แปลว่ายังไม่มีคะแนน
 * - อัปเดตได้ทั้งจาก import และแก้ในระบบ
 */
class AsmEmployeeScore extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_employee_scores';

    protected $fillable = ['round_id', 'employee_code', 'box_id', 'value', 'value2', 'value3', 'value4', 'na_mask', 'updated_by'];

    protected $casts = [
        'value' => 'decimal:4',
        'value2' => 'decimal:4',
        'value3' => 'decimal:4',
        'value4' => 'decimal:4',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(AsmScoreBox::class, 'box_id');
    }
}
