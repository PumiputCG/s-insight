<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtRequestApproval extends Model
{
    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_request_approvals';

    protected $fillable = [
        'ot_request_id', 'decision', 'actor_app_user_id', 'actor_employee_code', 'note',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(OtRequest::class, 'ot_request_id');
    }
}
