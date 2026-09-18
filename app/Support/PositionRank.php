<?php

namespace App\Support;

/**
 * ลำดับความอาวุโสของตำแหน่ง (ใช้ร่วมทุกโมดูล) — ใช้เรียงพนักงานจากสูงไปต่ำ
 *
 * **Bplus ไม่ได้เซ็ตลำดับตำแหน่งไว้เลย**: `PRS_GRADE_EX` ว่างทั้ง 1,544 คน และ
 * ตาราง `JOBTITLE` มี 34 ตำแหน่งแต่ `JBT_UDF_1..6` ว่างหมด ไม่มีคอลัมน์ระดับใด ๆ
 *
 * สิ่งที่ใช้ได้คือโครงสร้างของรหัสตำแหน่งเอง: ตัวอักษร = สายงาน · ตัวเลข = ระดับในสาย
 * ลำดับข้ามสาย (เช่น SV2 กับ E2) ไม่มีข้อมูลใน Bplus ให้อ้างอิง จึงใช้ลำดับที่
 * Manager ยืนยันไว้เมื่อ 2026-08-19 — **ปรับได้ที่ตารางนี้ที่เดียว ไม่ต้องแก้โค้ดอื่น**
 *
 * เดิมอยู่ที่ `App\Support\OtApproval\OtPositionRank` ใช้เฉพาะระบบ OT · 2026-09-01
 * ระบบ Assessment (หน้า Evaluate/Review) ขอใช้ลำดับชุดเดียวกัน จึงย้ายตารางมาไว้ตรงกลาง
 * แล้วให้ `OtPositionRank` เรียกต่อมาที่นี่ เพื่อไม่ให้มีตารางลำดับ 2 ชุด
 */
class PositionRank
{
    /**
     * รหัสตำแหน่ง => ลำดับ (ยิ่งน้อยยิ่งอาวุโส)
     *
     * @var array<string, int>
     */
    private const RANKS = [
        'M8' => 1,   // Chairman
        'M7' => 2,   // President
        'M6' => 3,   // CEO
        'M5' => 4,   // CFO
        'M4' => 5,   // General Manager
        'M3' => 6,   // Deputy General Manager
        'M2' => 7,   // Manager
        'M1' => 8,   // Assist Manager
        'ED3' => 9,  // Design Supervisor
        'SV2' => 10, // Supervisor
        'P3' => 11,  // Programmer Supervisor
        'E2' => 12,  // Senior Engineer
        'ED2' => 13, // Senior Design Engineer
        'P2' => 14,  // Senior Programmer Staff
        'E1' => 15,  // Engineer
        'ED1' => 16, // Design Engineer
        'P1' => 17,  // Programmer Staff
        'SV1' => 18, // Foreman
        'S2' => 19,  // Senior Staff
        'S1' => 20,  // Staff
        'T2' => 21,  // Senior Technician
        'T1' => 22,  // Technician
        'W3' => 23,  // Leader
        'W2' => 24,  // Senior Operator
        'W1' => 25,  // Operator
        'C' => 26,   // Consultant
        'DE' => 27,  // Driver (Executive)
        'DL' => 28,  // Driver (Logistic)
        'DO' => 29,  // Driver (Office)
        'WC' => 30,  // Cooking
        'WM' => 31,  // Maid
        'Y1' => 32,  // Employee with Disabilities
        'Y2' => 33,  // Student Intern
    ];

    /** ตำแหน่งที่ไม่รู้จักไปอยู่ท้ายสุดเสมอ ไม่ใช่แทรกกลางจนอ่านลำดับไม่ออก */
    public const UNRANKED = 999;

    public static function of(?string $jobCode): int
    {
        $code = strtoupper(trim((string) $jobCode));

        return self::RANKS[$code] ?? self::UNRANKED;
    }
}
