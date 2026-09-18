<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsmSelfChoice extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_self_choices';

    protected $fillable = [
        'question_id', 'sort', 'choice_th', 'choice_en', 'choice_my', 'score', 'is_na',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'sort' => 'integer',
        'score' => 'decimal:2',
        'is_na' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(AsmSelfQuestion::class, 'question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AsmSelfAnswer::class, 'choice_id');
    }
}
