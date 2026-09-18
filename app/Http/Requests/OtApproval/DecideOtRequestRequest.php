<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideOtRequestRequest extends FormRequest
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
            'note.required' => 'กรุณาระบุเหตุผลที่ไม่อนุมัติ',
        ];
    }
}
