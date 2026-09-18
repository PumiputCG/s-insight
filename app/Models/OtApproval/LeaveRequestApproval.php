<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestApproval extends Model
{
  protected $connection = 'mysql_ot_approval';
  protected $table = 'leave_request_approvals';
  protected $fillable = [
    'leave_request_id', 'decision', 'actor_app_user_id', 'actor_employee_code', 'note',
  ];

  public function request(): BelongsTo
  {
    return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
  }
}
