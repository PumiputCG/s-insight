<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsmSelfSubmission extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_self_submissions';

    protected $fillable = [
        'round_id', 'employee_code', 'level', 'earned_score', 'possible_score',
        'total_percent', 'submitted_at',
    ];

    protected $casts = [
        'round_id' => 'integer',
        'level' => 'integer',
        'earned_score' => 'decimal:2',
        'possible_score' => 'decimal:2',
        'total_percent' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(AsmSelfAnswer::class, 'submission_id')->orderBy('question_no');
    }
}
