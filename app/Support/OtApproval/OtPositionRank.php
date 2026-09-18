<?php

namespace App\Support\OtApproval;

use App\Support\PositionRank;

/**
 * ลำดับความอาวุโสของตำแหน่งฝั่ง OT — ตารางจริงย้ายไปอยู่ที่ `App\Support\PositionRank` แล้ว
 * (2026-09-01 ระบบ Assessment ขอใช้ลำดับชุดเดียวกัน จึงยกไปไว้ตรงกลาง กันตารางลำดับซ้ำ 2 ชุด)
 *
 * คลาสนี้คงไว้เพื่อไม่ต้องแก้จุดที่เรียกใช้เดิมในโมดูล OT — พฤติกรรมเหมือนเดิมทุกอย่าง
 * ถ้าจะแก้ลำดับตำแหน่ง ให้ไปแก้ที่ `App\Support\PositionRank` ที่เดียว
 */
class OtPositionRank
{
    /** ตำแหน่งที่ไม่รู้จักไปอยู่ท้ายสุดเสมอ ไม่ใช่แทรกกลางจนอ่านลำดับไม่ออก */
    public const UNRANKED = PositionRank::UNRANKED;

    public static function of(?string $jobCode): int
    {
        return PositionRank::of($jobCode);
    }
}
