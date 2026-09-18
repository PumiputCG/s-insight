<?php

namespace Tests\Unit\OtApproval;

use App\Services\OtApproval\BplusAttendanceService;
use PHPUnit\Framework\TestCase;

class BplusAttendanceServiceTest extends TestCase
{
    public function test_it_uses_first_and_latest_raw_scan_when_bplus_has_not_processed_the_day(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '70001',
            'raw_stamp_1' => '1899-12-30 07:50:00',
            'raw_stamp_2' => '1899-12-30 12:00:00',
            'raw_stamp_3' => '1899-12-30 17:01:00',
        ]);

        $this->assertSame('07:50', $attendance['clock_in']);
        $this->assertSame('17:01', $attendance['clock_out']);
        $this->assertSame(3, $attendance['punch_count']);
        $this->assertSame('raw', $attendance['attendance_source']);
        $this->assertSame('latest_scan', $attendance['attendance_state']);
        $this->assertFalse($attendance['clock_out_is_final']);
    }

    public function test_processed_times_override_raw_scans_for_historical_attendance(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '70002',
            'processed_in' => '2026-08-06 08:00:00',
            'processed_out' => '2026-08-06 20:00:00',
            'raw_stamp_1' => '1899-12-30 07:55:00',
            'raw_stamp_2' => '1899-12-30 20:04:00',
            'work_hours' => '11.00',
        ]);

        $this->assertSame('08:00', $attendance['clock_in']);
        $this->assertSame('20:00', $attendance['clock_out']);
        $this->assertSame('11.00', $attendance['work_hours']);
        $this->assertSame('processed', $attendance['attendance_source']);
        $this->assertSame('final_out', $attendance['attendance_state']);
        $this->assertTrue($attendance['clock_out_is_final']);
    }

    /**
     * กะดึกที่ Bplus ยังไม่ประมวลผล — เคสที่เคยพังจริง
     *
     * รูดของวันทำงานมี 2 กลุ่มปนกัน: ตอนเช้าคือ "ขาออกของกะเมื่อวาน"
     * ตอนเย็นคือ "ขาเข้าของกะคืนนี้" ส่วนขาออกจริงไปอยู่ในรูดของวันถัดไป
     * ของเดิมหยิบรูดแรก/สุดท้ายของวันเดียว เลยได้เวลาเข้า-ออกสลับกันและผิดกะ
     */
    public function test_night_shift_takes_clock_in_from_the_evening_and_clock_out_from_the_next_day(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '64151',
            'shift_code' => 'AN03',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            // วันทำงาน: 08:09 คือขาออกของกะเมื่อวาน · 19:55 คือขาเข้าของคืนนี้
            'raw_stamp_1' => '1899-12-30 08:09:00',
            'raw_stamp_2' => '1899-12-30 19:55:00',
            // วันถัดไป: 05:03 คือขาออกจริงของกะนี้ · 19:50 คือขาเข้าของคืนถัดไป
            'next_stamp_1' => '1899-12-30 05:03:00',
            'next_stamp_2' => '1899-12-30 19:50:00',
        ]);

        $this->assertSame('19:55', $attendance['clock_in'], 'ขาเข้าต้องเป็นรูดตอนเย็น ไม่ใช่รูดเช้าของกะเมื่อวาน');
        $this->assertSame('05:03', $attendance['clock_out'], 'ขาออกต้องมาจากรูดของวันถัดไป');
        $this->assertSame('latest_scan', $attendance['attendance_state']);
    }

    /**
     * เช้าของวันที่กะดึกยังไม่เริ่ม — เคสที่ Manager เจอจริงเวลา 08:30
     *
     * วันนั้นมีแต่รูดขาออกของกะเมื่อคืนอยู่ ถ้าเผลอหยิบมาจะกลายเป็น
     * "เข้างานแล้ว 08:04" ทั้งที่กะเริ่ม 20:00 ตอนเย็น
     */
    public function test_night_shift_has_no_clock_in_before_the_shift_starts(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '60585',
            'shift_code' => 'AN03',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            // 08:04 คือขาออกของกะเมื่อคืน ไม่ใช่ขาเข้าของกะคืนนี้
            'raw_stamp_1' => '1899-12-30 08:04:00',
        ]);

        $this->assertNull($attendance['clock_in'], 'กะยังไม่เริ่ม ต้องยังไม่มีเวลาเข้า');
        $this->assertNull($attendance['clock_out']);
        $this->assertSame('not_scanned', $attendance['attendance_state']);
        $this->assertSame([], $attendance['punches'], 'รูดของกะเมื่อคืนต้องไม่ติดมาในแถวของวันนี้');
    }

    /**
     * ไม่ได้มาทำกะดึกเลย — รูดเช้าวันถัดไปเป็นของกะอื่น ห้ามนับเป็นขาออก
     *
     * เคสจริง: 29 ก.ค. ไม่มีรูดสักครั้ง แต่ 30 ก.ค. มีรูด 07:58
     * ซึ่งคือ "ขาเข้าของกะเช้าวันที่ 30" ไม่ใช่ขาออกของกะดึกคืนวันที่ 29
     */
    public function test_night_shift_without_a_clock_in_does_not_borrow_the_next_morning_punch(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '40001',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            // วันทำงานไม่มีรูดเลย
            'next_stamp_1' => '1899-12-30 07:58:00',
        ]);

        $this->assertNull($attendance['clock_in']);
        $this->assertNull($attendance['clock_out'], 'ไม่ได้เริ่มกะ จึงไม่มีขาออก');
        $this->assertSame('not_scanned', $attendance['attendance_state']);
    }

    public function test_night_shift_still_shows_what_bplus_processed_even_without_a_clock_in(): void
    {
        // Bplus เป็นบันทึกทางการ ถ้าให้ค่าออกมาต้องแสดงตามนั้น แม้ไม่มีขาเข้า
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '71422',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            'processed_out' => '2026-07-27 07:58:00',
        ]);

        $this->assertNull($attendance['clock_in']);
        $this->assertSame('07:58', $attendance['clock_out']);
    }

    public function test_night_shift_keeps_a_late_clock_out_from_overtime_after_the_shift(): void
    {
        // กะเลิก 05:00 ทำ OT ต่อถึง 08:00 แล้วรูดออก 08:26 ต้องยังนับเป็นขาออก
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '40001',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            'raw_stamp_1' => '1899-12-30 19:48:00',
            'next_stamp_1' => '1899-12-30 08:26:00',
        ]);

        $this->assertSame('19:48', $attendance['clock_in']);
        $this->assertSame('08:26', $attendance['clock_out']);
    }

    public function test_night_shift_ignores_the_next_night_clock_in_that_shares_the_next_day(): void
    {
        // รูด 19:50 ของวันถัดไปคือขาเข้าของกะคืนถัดไป ห้ามเอามาเป็นขาออกของกะนี้
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '40002',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            'raw_stamp_1' => '1899-12-30 19:04:00',
            'next_stamp_1' => '1899-12-30 04:55:00',
            'next_stamp_2' => '1899-12-30 19:50:00',
        ]);

        $this->assertSame('04:55', $attendance['clock_out']);
    }

    public function test_night_shift_still_prefers_the_processed_times_from_bplus(): void
    {
        // Bplus ประมวลผลแล้วถือเป็นค่าจริงเสมอ ไม่ต้องเดาจากรูดดิบ
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '64151',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            'processed_in' => '2026-08-17 19:51:00',
            'processed_out' => '2026-08-18 08:09:00',
            'raw_stamp_1' => '1899-12-30 08:09:00',
            'raw_stamp_2' => '1899-12-30 19:51:00',
        ]);

        $this->assertSame('19:51', $attendance['clock_in']);
        $this->assertSame('08:09', $attendance['clock_out']);
        $this->assertSame('final_out', $attendance['attendance_state']);
        $this->assertTrue($attendance['clock_out_is_final']);
    }

    public function test_day_shift_is_unaffected_by_the_next_day_punches(): void
    {
        // กะเช้าไม่ข้ามวัน ต้องไม่ไปหยิบรูดของวันถัดไปมาเป็นขาออก
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '60054',
            'shift_in' => '08:00:00',
            'shift_out' => '17:00:00',
            'shift_out_day' => 1,
            'raw_stamp_1' => '1899-12-30 07:52:00',
            'raw_stamp_2' => '1899-12-30 17:04:00',
            'next_stamp_1' => '1899-12-30 07:48:00',
        ]);

        $this->assertSame('07:52', $attendance['clock_in']);
        $this->assertSame('17:04', $attendance['clock_out']);
    }

    public function test_single_raw_scan_is_not_treated_as_clock_out(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '70003',
            'raw_stamp_1' => '1899-12-30 08:02:00',
        ]);

        $this->assertSame('08:02', $attendance['clock_in']);
        $this->assertNull($attendance['clock_out']);
        $this->assertSame('in_only', $attendance['attendance_state']);
        $this->assertFalse($attendance['clock_out_is_final']);
    }

    /**
     * รูดซ้ำหน้าเครื่อง — เคสที่เคยพังจริงในแผนก MFG/PF วันที่ 19 ส.ค.
     *
     * พนักงานรูด 07:39 แล้วเครื่องไม่ตอบ จึงรูดซ้ำ 07:40 กฎเดิม "มีรูด 2 ครั้ง
     * แปลว่ารูดสุดท้ายคือขาออก" ทำให้หน้าภาพรวมขึ้นว่าออกงานแล้วตั้งแต่ 07:40
     * ทั้งที่กะ 08:00-17:00 ยังไม่เริ่มด้วยซ้ำ
     */
    public function test_duplicate_scan_at_the_gate_is_not_treated_as_clock_out(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '68384',
            'shift_in' => '08:00:00',
            'shift_out' => '17:00:00',
            'shift_out_day' => 1,
            'raw_stamp_1' => '1899-12-30 07:39:00',
            'raw_stamp_2' => '1899-12-30 07:40:00',
        ]);

        $this->assertSame('07:39', $attendance['clock_in']);
        $this->assertNull($attendance['clock_out']);
        $this->assertSame('in_only', $attendance['attendance_state']);
        // รูดซ้ำยังต้องถูกเก็บไว้ในรายการ เพราะเป็นข้อมูลจริงที่เครื่องบันทึก
        $this->assertSame(2, $attendance['punch_count']);
    }

    /**
     * ออกก่อนเลิกกะจริงต้องยังนับเป็นขาออก
     *
     * ข้อมูลจริงของ Bplus 7 วันมีคนเข้า 07:18 ออก 07:59 (ทำงาน 41 นาที) และ
     * Bplus รับเป็นขาออกจริง เกณฑ์ตัดรูดซ้ำจึงต้องอยู่ต่ำกว่า 41 นาที
     * ห้ามเปลี่ยนไปใช้เกณฑ์ "ขาออกต้องใกล้เวลาเลิกกะ" เพราะจะตัดเคสนี้ทิ้ง
     */
    public function test_short_but_real_workday_still_counts_as_clock_out(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '69960',
            'shift_in' => '08:00:00',
            'shift_out' => '17:00:00',
            'shift_out_day' => 1,
            'raw_stamp_1' => '1899-12-30 07:18:00',
            'raw_stamp_2' => '1899-12-30 07:59:00',
        ]);

        $this->assertSame('07:18', $attendance['clock_in']);
        $this->assertSame('07:59', $attendance['clock_out']);
    }

    /**
     * รูดซ้ำที่คร่อมเที่ยงคืนของกะดึก
     *
     * รูดครั้งแรก 23:45 แล้วรูดซ้ำ 00:05 ซึ่งตกไปอยู่ในวันถัดไปของ TMSTAMP
     * ถ้าวัดช่วงห่างแบบไม่บวก 24 ชม. จะได้ค่าติดลบและหลุดเป็นขาออกทันที
     */
    public function test_night_shift_ignores_a_duplicate_scan_that_crosses_midnight(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '64152',
            'shift_code' => 'AN05',
            'shift_in' => '23:30:00',
            'shift_out' => '08:30:00',
            'shift_out_day' => 2,
            'raw_stamp_1' => '1899-12-30 23:45:00',
            'next_stamp_1' => '1899-12-30 00:05:00',
        ]);

        $this->assertSame('23:45', $attendance['clock_in']);
        $this->assertNull($attendance['clock_out']);
        $this->assertSame('in_only', $attendance['attendance_state']);
    }

    /** กะดึกปกติต้องไม่โดนเกณฑ์รูดซ้ำเล่นงาน เพราะห่างกันข้ามคืนอยู่แล้ว */
    public function test_night_shift_clock_out_after_midnight_is_kept(): void
    {
        $attendance = (new BplusAttendanceService)->normalizeAttendanceRow([
            'employee_code' => '64153',
            'shift_code' => 'AN03',
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'shift_out_day' => 2,
            'raw_stamp_1' => '1899-12-30 19:41:00',
            'next_stamp_1' => '1899-12-30 05:02:00',
        ]);

        $this->assertSame('19:41', $attendance['clock_in']);
        $this->assertSame('05:02', $attendance['clock_out']);
    }
}
