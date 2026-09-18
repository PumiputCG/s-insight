<?php

namespace App\Http\Requests\OtApproval;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadLeave75Request extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /** @return array<string, mixed> */
  public function rules(): array
  {
    return [
      'scope' => ['required', Rule::in(['date', 'cycle'])],
      'date' => [Rule::requiredIf(fn () => $this->input('scope') === 'date'), 'nullable', 'date_format:Y-m-d'],
      'cycle' => [Rule::requiredIf(fn () => $this->input('scope') === 'cycle'), 'nullable', 'date_format:Y-m'],
      // วันที่ยื่นคำขอ — ใช้กับรอบที่ยื่นย้อนหลัง (หลังวันลา) แยกไฟล์รายวัน กัน HR import ซ้ำ
      'submitted_on' => ['nullable', 'date_format:Y-m-d'],
      /* ไฟล์รวมของวันลา — คำขอที่ยื่นตั้งแต่ต้นจนถึงวันลาอยู่ในไฟล์เดียว
         ปกติค่านี้คือวันลาเอง ส่งมาพร้อม date เสมอ */
      'submitted_until' => ['nullable', 'date_format:Y-m-d'],
    ];
  }
}
