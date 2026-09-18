<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;

/**
 * log การ import คะแนนแต่ละครั้ง (asm_import_batches)
 */
class AsmImportBatch extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_import_batches';

    protected $fillable = [
        'filename', 'uploaded_by', 'total_rows', 'matched_rows', 'updated_cells', 'columns_matched',
    ];

    protected $casts = [
        'columns_matched' => 'array',
    ];
}
