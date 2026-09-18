<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;

/** ยกเลิกคำขอที่ยังไม่ถูกตัดสิน — เหตุผลบังคับกรอก เพราะเก็บไว้เป็นประวัติให้ตรวจย้อนได้ */
class CancelLeaveRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'request_ids' => ['required', 'array', 'min:1', 'max:200'],
            'request_ids.*' => ['required', 'integer', 'distinct'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'กรุณาระบุเหตุผลที่ยกเลิก',
            'reason.min' => 'เหตุผลสั้นเกินไป กรุณาอธิบายอย่างน้อย 3 ตัวอักษร',
        ];
    }
}
