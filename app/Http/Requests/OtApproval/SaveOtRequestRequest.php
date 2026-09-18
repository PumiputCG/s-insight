<?php

namespace App\Http\Requests\OtApproval;

use App\Models\OtApproval\OtDepartmentAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOtRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $selectableTypes = collect((array) config('ot_approval.types', []))
            ->filter(fn (array $type) => ($type['selectable'] ?? true) === true)
            ->keys()
            ->all();

        return [
            'company' => ['required', 'string', Rule::in(array_keys(OtDepartmentAssignment::COMPANIES))],
            'dept_code' => ['nullable', 'string', 'max:50'],
            'employee_code' => ['required', 'string', 'max:30'],
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'ot_type' => ['required', 'string', Rule::in($selectableTypes)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            // Foreman แก้จำนวนเองได้ เพราะต้องหักเวลาพักออกจาก OT ที่คาบเกี่ยวกะ
            // ถ้าไม่ส่งมา ระบบจะคิดเต็มช่วงเวลาให้เหมือนเดิม
            'requested_hours' => ['nullable', 'integer', 'min:0', 'max:24'],
            'requested_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'requested_hours.max' => 'จำนวนชั่วโมงต้องไม่เกิน 24',
            'requested_minutes.max' => 'จำนวนนาทีต้องไม่เกิน 59',
        ];
    }
}
