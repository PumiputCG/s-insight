<?php

namespace App\Http\Requests\OtApproval;

use App\Models\OtApproval\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideLeaveRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /** @return array<string, mixed> */
  public function rules(): array
  {
    return [
      'decision' => ['required', Rule::in([LeaveRequest::APPROVAL_APPROVED, LeaveRequest::APPROVAL_REJECTED])],
      'note' => [
        Rule::requiredIf(fn () => $this->input('decision') === LeaveRequest::APPROVAL_REJECTED),
        'nullable', 'string', 'max:1000',
      ],
    ];
  }
}
