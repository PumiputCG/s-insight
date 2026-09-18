<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsmSelfAnswer extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_self_answers';

    protected $fillable = [
        'submission_id', 'question_id', 'choice_id', 'question_no', 'is_na',
        'score', 'full_score', 'percent',
    ];

    protected $casts = [
        'submission_id' => 'integer',
        'question_id' => 'integer',
        'choice_id' => 'integer',
        'question_no' => 'integer',
        'is_na' => 'boolean',
        'score' => 'decimal:2',
        'full_score' => 'decimal:2',
        'percent' => 'decimal:2',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AsmSelfSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AsmSelfQuestion::class, 'question_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(AsmSelfChoice::class, 'choice_id');
    }
}
