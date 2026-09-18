<?php

return [
  /* การลาเฟสแรกมีเฉพาะหยุดงานตามมาตรา 75 แบบเต็มวัน */
  'types' => [
    'section_75' => [
      'label_short' => 'ลา 75',
      'label_th' => 'หยุดงานตามมาตรา 75',
      'label_en' => 'Section 75 temporary work suspension',
      'label_my' => 'ပုဒ်မ ၇၅ အရ အလုပ်ယာယီရပ်နားခြင်း',
      'bplus_stamp_type_key' => '20030',
      'deduction_agreement_code' => '020008(1)',
      'quantity' => '1',
    ],
  ],

  'export' => [
    'template_path' => base_path('resources/templates/ot_approval/leave75-template.xlsx'),
    'directory' => storage_path('app/private/ot-approval/leave-exports'),
    'sheet_name' => 'BplusData',
    'shift_code' => '00',
    'swipe_character_code' => '0',
    'approval_method' => '1',
  ],

  /* รอบเงินเดือน 21 เดือนก่อนหน้า ถึง 20 เดือนปัจจุบัน อนุมัติช้าที่สุดวันที่ 21 */
  'payroll_cycle' => [
    'start_day' => 21,
    'end_day' => 20,
    'approval_deadline_day' => 21,
  ],
];
