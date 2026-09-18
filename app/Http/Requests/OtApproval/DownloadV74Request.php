<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadV74Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(['date', 'cycle'])],
            'date' => [
                Rule::requiredIf(fn () => $this->input('scope') === 'date'),
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
            'cycle' => [
                Rule::requiredIf(fn () => $this->input('scope') === 'cycle'),
                'nullable',
                'date_format:Y-m',
            ],
            /* วันที่ยื่นคำขอ — ใช้แยกไฟล์ของวันทำงานเดียวกันออกจากกัน
               กัน HR import ทับรายการที่เคยส่งไปแล้วจนชั่วโมงบวกซ้ำใน Bplus */
            'submitted_on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
