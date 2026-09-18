<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOtRequestsRequest extends FormRequest
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
        ];
    }
}
