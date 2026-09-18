<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * คำถามประเมิน — ต่อคอลัมน์ชนิด Input × ลำดับผู้ประเมิน (1–4) · 3 ภาษา TH/EN/MY
 */
class AsmQuestion extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_questions';

    protected $fillable = ['box_id', 'level', 'sort', 'q_th', 'q_en', 'q_my'];

    protected $casts = [
        'level' => 'integer',
        'sort' => 'integer',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(AsmScoreBox::class, 'box_id');
    }
}
