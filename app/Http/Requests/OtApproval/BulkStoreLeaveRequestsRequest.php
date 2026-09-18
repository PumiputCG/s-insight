<?php

namespace App\Http\Requests\OtApproval;

use App\Models\OtApproval\OtDepartmentAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreLeaveRequestsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /** @return array<string, mixed> */
  public function rules(): array
  {
    return [
      'company' => ['required', 'string', Rule::in(array_keys(OtDepartmentAssignment::COMPANIES))],
      'dept_code' => ['nullable', 'string', 'max:50'],
      'items' => ['required', 'array', 'min:1', 'max:500'],
      'items.*.employee_code' => ['required', 'string', 'max:30'],
      'items.*.leave_type' => ['required', 'string', Rule::in(array_keys((array) config('leave_approval.types', [])))],
      'items.*.start_date' => ['required', 'date_format:Y-m-d'],
      'items.*.end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:items.*.start_date'],
      'items.*.note' => ['nullable', 'string', 'max:1000'],
    ];
  }
}
