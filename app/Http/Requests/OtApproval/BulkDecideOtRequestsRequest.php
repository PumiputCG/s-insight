<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ตัดสินผลหลายคำขอในครั้งเดียว (อนุมัติหรือปฏิเสธรายการที่เลือก)
 * กติกาเหมือน DecideOtRequestRequest ทุกอย่าง เพิ่มแค่รายการ id ที่เลือก
 */
class BulkDecideOtRequestsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('note')) {
            return;
        }

        $note = trim((string) $this->input('note'));
        $this->merge(['note' => $note !== '' ? $note : null]);
    }

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
            'decision' => ['required', 'string', Rule::in(['approved', 'rejected'])],
            'note' => [
                Rule::requiredIf(fn () => $this->input('decision') === 'rejected'),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'request_ids.required' => 'กรุณาเลือกอย่างน้อย 1 รายการ',
            'note.required' => 'กรุณาระบุเหตุผลที่ไม่อนุมัติ',
        ];
    }
}
