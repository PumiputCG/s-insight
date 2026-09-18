<?php

namespace App\Http\Requests\OtApproval;

use App\Models\OtApproval\OtDepartmentAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ขอ OT ทั้งกะในครั้งเดียว
 *
 * Foreman กรอกค่ากลางครั้งเดียวแล้วระบบกระจายลงทุกคน จากนั้นแก้รายคนได้
 * ค่าที่ส่งมาจึงเป็น "ต่อคน" ทั้งหมด ไม่ใช่ค่ากลาง เพราะบางแถวถูกแก้แล้ว
 */
class BulkStoreOtRequestsRequest extends FormRequest
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
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],

            'items' => ['required', 'array', 'min:1', 'max:300'],
            'items.*.employee_code' => ['required', 'string', 'max:30', 'distinct'],
            'items.*.ot_type' => ['required', 'string', Rule::in($selectableTypes)],
            'items.*.start_time' => ['required', 'date_format:H:i'],
            'items.*.end_time' => ['required', 'date_format:H:i', 'different:items.*.start_time'],
            'items.*.requested_hours' => ['nullable', 'integer', 'min:0', 'max:24'],
            'items.*.requested_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'กรุณาเลือกอย่างน้อย 1 คน',
            'items.*.employee_code.distinct' => 'มีพนักงานซ้ำในรายการ',
        ];
    }
}
