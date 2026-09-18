<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLeaveRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /** @return array<string, mixed> */
  public function rules(): array
  {
    return [
      'company' => ['required', 'string', Rule::in(array_keys(\App\Models\OtApproval\OtDepartmentAssignment::COMPANIES))],
      'dept_code' => ['nullable', 'string', 'max:50'],
      'employee_code' => ['required', 'string', 'max:30'],
      'leave_type' => ['required', 'string', Rule::in(array_keys((array) config('leave_approval.types', [])))],
      'start_date' => ['required', 'date_format:Y-m-d'],
      'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
      'note' => ['nullable', 'string', 'max:1000'],
    ];
  }
}
