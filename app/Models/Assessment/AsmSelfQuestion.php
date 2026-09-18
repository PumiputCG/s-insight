<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsmSelfQuestion extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_self_questions';

    protected $fillable = ['round_id', 'level', 'sort', 'q_th', 'q_en', 'q_my', 'full_score'];

    protected $casts = [
        'round_id' => 'integer',
        'level' => 'integer',
        'sort' => 'integer',
        'full_score' => 'decimal:2',
    ];

    public function choices(): HasMany
    {
        return $this->hasMany(AsmSelfChoice::class, 'question_id')->orderBy('sort')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AsmSelfAnswer::class, 'question_id');
    }
}
