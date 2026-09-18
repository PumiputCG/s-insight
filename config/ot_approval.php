<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance source
    |--------------------------------------------------------------------------
    |
    | `bplus` is the production-safe default. Local Laravel uses `auto` when no
    | explicit value is configured: it tries Bplus first, then falls back to
    | deterministic demo attendance after a connection failure. Set `local`
    | to read a Bplus Snapshot from the OT database first and skip the live
    | connection; deterministic demo data is used only when no snapshot exists.
    |
    */
    'attendance_source' => env(
        'OT_APPROVAL_ATTENDANCE_SOURCE',
        env('APP_ENV') === 'local' ? 'auto' : 'bplus',
    ),

    /* เวลาพักหลังสิ้นสุดกะปกติก่อนเริ่ม OT ตามกฎบริษัท */
    'post_shift_break_minutes' => 60,

    /*
    | ตำแหน่งที่ไม่มีสิทธิ์ขอ OT
    | ใช้ job_code จาก Employee Master เป็นหลัก; ชื่อเป็น fallback เฉพาะกรณีไม่มีรหัส
    | Bplus ปัจจุบันยืนยัน Y1 ของ SUPAVUT_INDUSTRY และ MOLDVANTO = Employee with Disabilities
    */
    'ot_ineligible_positions' => [
        'codes_by_company' => [
            'SUPAVUT_INDUSTRY' => ['Y1'],
            'MOLDVANTO' => ['Y1'],
        ],
        'names' => ['Employee with Disabilities'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bplus V74 export
    |--------------------------------------------------------------------------
    |
    | สร้างไฟล์จาก Template จริงของ HR และเขียนทุกช่องเป็นข้อความตามต้นฉบับ
    | เพื่อรักษาเลขศูนย์นำหน้าในรหัสพนักงาน/รหัสข้อตกลง
    |
    */
    'v74' => [
        'template_path' => base_path('resources/templates/ot_approval/v74-template.xlsx'),
        'sheet_name' => 'BplusData',
        'shift_code' => '00',
        'swipe_character_code' => '0',
        'approval_method' => '1',
    ],

    /*
    | ประเภทที่ Foreman เห็นในฟอร์ม ส่วนรหัส V74 จะ Map ในเฟส Export
    */
    'types' => [
        'weekday_after_work' => [
            'multiplier' => '1.5',
            'timing' => 'after_shift',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10101',
                'MOLDVANTO' => '10101',
                'SUPAVUT_INNOMED' => '10101',
            ],
            'label_th' => 'ค่าล่วงเวลาX1.5 (หลังเลิกงาน)',
            'label_en' => 'Overtime X1.5 (after work)',
            'label_my' => 'အချိန်ပို X1.5 (အလုပ်ဆင်းပြီးနောက်)',
        ],
        'weekday_before_work' => [
            'multiplier' => '1.5',
            'timing' => 'before_shift',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10102',
                'MOLDVANTO' => '10102',
                'SUPAVUT_INNOMED' => '10102',
            ],
            'label_th' => 'ค่าล่วงเวลาX1.5 (ก่อนเข้างาน)',
            'label_en' => 'Overtime X1.5 (before work)',
            'label_my' => 'အချိန်ပို X1.5 (အလုပ်မစမီ)',
        ],
        'holiday_regular' => [
            'multiplier' => '1.0',
            'timing' => 'manual',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10103',
                'MOLDVANTO' => '10103',
                'SUPAVUT_INNOMED' => '10103',
            ],
            'label_th' => 'ค่าล่วงเวลาX1 วันหยุดพนักงานรายเดือน',
            'label_en' => 'Overtime X1 on monthly employee holiday',
            'label_my' => 'လစဉ်ဝန်ထမ်းနားရက် အချိန်ပို X1',
        ],
        'holiday_public_daily' => [
            'multiplier' => '1.0',
            'timing' => 'manual',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10103-1',
                'MOLDVANTO' => '10103-1',
            ],
            'label_th' => 'ค่าล่วงเวลาX1 วันหยุดนักขัตฤกษ์รายวัน',
            'label_en' => 'Overtime X1 on daily public holiday',
            'label_my' => 'နေ့စား အများပြည်သူရုံးပိတ်ရက် အချိန်ပို X1',
        ],
        'holiday_ot' => [
            'multiplier' => '2.0',
            'timing' => 'manual',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10104',
                'MOLDVANTO' => '10104',
                'SUPAVUT_INNOMED' => '10104',
            ],
            'label_th' => 'ค่าล่วงเวลาX2 วันหยุดพนักงานรายวัน',
            'label_en' => 'Overtime X2 on daily employee holiday',
            'label_my' => 'နေ့စားဝန်ထမ်းနားရက် အချိန်ပို X2',
        ],
        'holiday_before_work' => [
            'multiplier' => '3.0',
            'timing' => 'before_shift',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10105',
                'MOLDVANTO' => '10105',
                'SUPAVUT_INNOMED' => '10105',
            ],
            'label_th' => 'ค่าล่วงเวลาX3 (ก่อนเข้างานวันหยุด)',
            'label_en' => 'Overtime X3 (before holiday work)',
            'label_my' => 'နားရက်အလုပ်မစမီ အချိန်ပို X3',
        ],
        'holiday_after_work' => [
            'multiplier' => '3.0',
            'timing' => 'after_shift',
            'v74_agreement_codes' => [
                'SUPAVUT_INDUSTRY' => '10106',
                'MOLDVANTO' => '10106',
                'SUPAVUT_INNOMED' => '10106',
            ],
            'label_th' => 'ค่าล่วงเวลาX3 (หลังเข้างานวันหยุด)',
            'label_en' => 'Overtime X3 (after holiday work)',
            'label_my' => 'နားရက်အလုပ်ပြီးနောက် အချိန်ပို X3',
        ],
        'attendance_day_count' => [
            /* Legacy metadata: คงชื่อไว้สำหรับคำขอเก่า แต่ห้ามเลือก/บันทึก/Export ใหม่ */
            'selectable' => false,
            'multiplier' => '1.0',
            'timing' => 'manual',
            'label_th' => 'เก็บจำนวนวันมาทำงาน',
            'label_en' => 'Record attendance day',
            'label_my' => 'အလုပ်လာရက် မှတ်တမ်းတင်ရန်',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | กลุ่มกะ เช้า / ดึก
    |--------------------------------------------------------------------------
    |
    | อ่านจาก dbo.TMSHIFT ของ Bplus ทั้ง 3 ฐานเมื่อ 2026-08-11
    | ตัดกลุ่มด้วยคอลัมน์ SF_OUT_DAY ของ Bplus เอง (1 = เลิกวันเดียวกัน,
    | 2 = เลิกวันถัดไป) ไม่ได้เดาจากเวลาหรือจากตัวอักษรในรหัสกะ
    |
    | รหัสที่ลงไว้เป็น "รหัสฐาน" เท่านั้น Bplus แตกทุกกะเป็น 3 แบบย่อยตาม
    | ชนิดของวันโดยต่อท้ายรหัส เช่น AD03 (วันงาน) / AD03O (วันหยุด) /
    | AD03OX (นักขัตฤกษ์) ซึ่งเวลาเหมือนกันทุกประการ ตอนเทียบจึงต้องตัด
    | ท้าย O และ OX ออกก่อน ดู OtShiftGroup::of()
    |
    | กะที่ไม่อยู่ในลิสต์นี้จะถูกจัดเป็น "ไม่ระบุ" ไม่ใช่เดาเป็นกะเวลา A
    | เพราะการเดาผิดจะทำให้กรองพนักงานหายไปเงียบ ๆ
    |
    */
    'shift_groups' => [
        'all' => [
            'label_th' => 'ทั้งหมด',
            'label_en' => 'All shifts',
            'label_my' => 'ဆိုင်းအားလုံး',
            /* ป้ายผู้รับผิดชอบทุกกะเท่านั้น ไม่ใช้จัดกลุ่มรหัสกะจาก Bplus */
            'codes' => [],
        ],
        'morning' => [
            'label_th' => 'กะเวลา A',
            'label_en' => 'Shift A',
            'label_my' => 'အလှည့် A',
            /* SF_OUT_DAY = 1 เลิกงานวันเดียวกัน */
            'codes' => ['AC01', 'AC02', 'AD01', 'AD03', 'AD04', 'AD09', 'AD12', 'BD08', 'SD02'],
        ],
        'night' => [
            'label_th' => 'กะเวลา B',
            'label_en' => 'Shift B',
            'label_my' => 'အလှည့် B',
            /* SF_OUT_DAY = 2 เลิกงานวันถัดไป — รวมกะบ่ายที่เลิกหลังเที่ยงคืนด้วย */
            'codes' => ['AC05', 'AN03', 'AN06', 'AN07', 'AN14', 'SN01', 'SN05', 'SN06'],
        ],
    ],
];
