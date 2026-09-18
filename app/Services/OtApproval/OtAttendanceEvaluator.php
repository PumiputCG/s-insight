<?php

namespace App\Services\OtApproval;

use App\Models\OtApproval\OtRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class OtAttendanceEvaluator
{
    /** @param array<string, mixed> $attendance */
    public function evaluate(OtRequest $request, array $attendance, ?CarbonInterface $now = null): string
    {
        $clockIn = $this->time($attendance['clock_in'] ?? null);
        $clockOut = $this->time($attendance['clock_out'] ?? null);
        $workDate = CarbonImmutable::parse($request->work_date)->startOfDay();
        $requestedStart = CarbonImmutable::parse($request->requested_start_at);
        $requestedEnd = CarbonImmutable::parse($request->requested_end_at);
        $evaluatedAt = $now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now);
        $attendanceFinal = $this->attendanceIsFinal($attendance, $requestedEnd, $evaluatedAt);

        if ($clockIn === null) {
            return $attendanceFinal
                ? OtRequest::ATTENDANCE_FAILED
                : OtRequest::ATTENDANCE_WAITING;
        }

        /* OT ก่อนเข้างานและ OT วันหยุด หลักฐานว่าทำจริงอยู่ที่ "ขาเข้า" ไม่ใช่ขาออก
           เช่น กะ 08:00-17:00 ขอ OT 06:00-07:00 แต่สแกนเข้า 07:20 = ไม่ได้มาทำช่วงนั้นจริง
           ถ้าดูแต่ขาออกจะผ่านเสมอเพราะสแกนออกตอนเย็นเลยเวลาจบ OT ไปไกลแล้ว
           ส่วน OT หลังเลิกงานไม่ต้องตรวจ เพราะสแกนเข้าตอนเช้าอยู่ก่อนช่วง OT อยู่แล้ว */
        if ($this->requiresClockInProof($request)) {
            $clockInAt = $this->punchDateTime($workDate, $clockIn, $requestedStart);
            if ($clockInAt->gt($requestedStart)) {
                return OtRequest::ATTENDANCE_FAILED;
            }
        }

        if ($clockOut === null) {
            return $attendanceFinal
                ? OtRequest::ATTENDANCE_FAILED
                : OtRequest::ATTENDANCE_WAITING;
        }

        $clockOutAt = $this->punchDateTime($workDate, $clockOut, $requestedEnd);

        if ($clockOutAt->lt($requestedEnd)) {
            return $attendanceFinal
                ? OtRequest::ATTENDANCE_FAILED
                : OtRequest::ATTENDANCE_WAITING;
        }

        if (! $this->workedFullShift($request, $clockOut)) {
            return $attendanceFinal
                ? OtRequest::ATTENDANCE_FAILED
                : OtRequest::ATTENDANCE_WAITING;
        }

        return OtRequest::ATTENDANCE_PASSED;
    }

    /** @param array<string, mixed> $attendance */
    public function canCreateRequest(array $attendance): bool
    {
        // การสร้างคำขอเป็นสิทธิ์ตามบทบาท/แผนก ไม่ผูกกับจำนวน Punch ระหว่างวัน
        return true;
    }

    /** @param array<string, mixed> $attendance */
    public function statusWithoutRequest(array $attendance): string
    {
        return 'not_requested';
    }

    public function displayStatusKey(OtRequest $request): string
    {
        if ($request->export_status === OtRequest::EXPORT_EXPORTED) {
            return 'exported';
        }

        if ($request->approval_status === OtRequest::APPROVAL_CANCELLED) {
            return 'cancelled';
        }

        if ($request->approval_status === OtRequest::APPROVAL_REJECTED) {
            return 'rejected';
        }

        if ($request->attendance_status === OtRequest::ATTENDANCE_FAILED) {
            return 'failed_time';
        }

        if ($request->approval_status === OtRequest::APPROVAL_DRAFT) {
            return 'draft';
        }

        if ($request->approval_status === OtRequest::APPROVAL_APPROVED
            && $request->attendance_status === OtRequest::ATTENDANCE_PASSED) {
            return 'success';
        }

        if ($request->approval_status === OtRequest::APPROVAL_APPROVED) {
            return 'approved_waiting_scan';
        }

        if ($request->attendance_status === OtRequest::ATTENDANCE_PASSED) {
            return 'waiting_approval';
        }

        return 'pending';
    }

    /**
     * ประเภทที่ต้องพิสูจน์ด้วยเวลาสแกนเข้า = ก่อนเข้างาน (before_shift) และวันหยุด (manual)
     *
     * ถ้าไม่รู้จักประเภท (คำขอเก่าที่ยังไม่มี ot_type) จะไม่บังคับกฎนี้
     * เพื่อไม่ให้ข้อมูลเดิมกลายเป็นไม่ผ่านย้อนหลังโดยไม่ได้ตั้งใจ
     */
    private function requiresClockInProof(OtRequest $request): bool
    {
        $type = trim((string) $request->ot_type);
        if ($type === '') {
            return false;
        }

        return in_array(
            config('ot_approval.types.'.$type.'.timing'),
            ['before_shift', 'manual'],
            true,
        );
    }

    /**
     * Last Punch ระหว่างวันยังไม่ใช่เวลาออกงานสุดท้าย
     *
     * ถือว่าปิดผลแล้วเมื่อ Bplus ส่ง processed_out มา หรือเมื่อเลยวันสิ้นสุด OT
     * ไปแล้วหนึ่งวัน เพื่อให้ Snapshot ย้อนหลังตัดสินผลได้แม้ยังใช้ state รุ่นเก่า
     *
     * @param  array<string, mixed>  $attendance
     */
    private function attendanceIsFinal(
        array $attendance,
        CarbonImmutable $requestedEnd,
        CarbonImmutable $now,
    ): bool {
        if (($attendance['clock_out_is_final'] ?? false) === true) {
            return true;
        }

        if (($attendance['attendance_state'] ?? null) === 'final_out') {
            return true;
        }

        // รองรับ Snapshot เก่าที่บันทึก processed pair ก่อนมี state final_out
        if (! array_key_exists('clock_out_is_final', $attendance)
            && ($attendance['attendance_source'] ?? null) === 'processed'
            && $this->time($attendance['clock_out'] ?? null) !== null) {
            return true;
        }

        return $now->startOfDay()->gt($requestedEnd->startOfDay());
    }

    /** ตรวจว่าอยู่จนครบกะหรือไม่ — สนใจเฉพาะขากลับ ส่วนมาสายไม่นำมาคิด */
    private function workedFullShift(OtRequest $request, string $clockOut): bool
    {
        $shiftIn = $this->time($request->shift_in);
        $shiftOut = $this->time($request->shift_out);
        if ($shiftIn === null || $shiftOut === null) {
            return true;
        }

        $workDate = CarbonImmutable::parse($request->work_date)->startOfDay();
        $shiftStart = $this->dateTime($workDate, $shiftIn);
        $shiftEnd = $this->dateTime($workDate, $shiftOut);
        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd = $shiftEnd->addDay();
        }

        $clockOutAt = $this->punchDateTime($workDate, $clockOut, $shiftEnd);

        /* มาสายไม่ตัดสิทธิ์ OT — เริ่มนับจากเวลาเข้ากะเสมอ ไม่ใช่เวลาสแกนเข้าจริง
           เดิมใช้เวลาสแกนจริง คนที่มาสายแม้ 1 นาที (เช่น 69859 เข้า 08:04 ของกะ 08:00)
           จะทำงานไม่ครบกะแล้วโดนตัด OT ทิ้งทั้งก้อน ทั้งที่มาสายเป็นเรื่องของ
           payroll คนละส่วนกับการทำ OT จริง
           ขากลับยังนับตามจริง กลับก่อนเลิกกะจึงยังไม่ผ่านเหมือนเดิม */
        $actualStart = $shiftStart;
        $actualEnd = $clockOutAt->lessThan($shiftEnd) ? $clockOutAt : $shiftEnd;
        if ($actualEnd->lessThanOrEqualTo($actualStart)) {
            return false;
        }

        $scheduledMinutes = $shiftStart->diffInMinutes($shiftEnd);
        $workedMinutes = $actualStart->diffInMinutes($actualEnd);
        $breakIn = $this->time($request->break_in);
        $breakOut = $this->time($request->break_out);

        if ($breakIn !== null && $breakOut !== null) {
            $breakStart = $this->dateTime($workDate, $breakIn);
            if ($breakStart->lt($shiftStart) && $shiftEnd->toDateString() !== $workDate->toDateString()) {
                $breakStart = $breakStart->addDay();
            }
            $breakEnd = $this->dateTime($breakStart->startOfDay(), $breakOut);
            if ($breakEnd->lessThanOrEqualTo($breakStart)) {
                $breakEnd = $breakEnd->addDay();
            }

            $scheduledMinutes -= $this->overlapMinutes($shiftStart, $shiftEnd, $breakStart, $breakEnd);
            $workedMinutes -= $this->overlapMinutes($actualStart, $actualEnd, $breakStart, $breakEnd);
        }

        return $workedMinutes >= $scheduledMinutes;
    }

    private function punchDateTime(CarbonImmutable $workDate, string $time, CarbonImmutable $reference): CarbonImmutable
    {
        $closest = $this->dateTime($workDate, $time);
        $closestSeconds = abs($closest->getTimestamp() - $reference->getTimestamp());
        foreach ([-1, 1] as $dayOffset) {
            $candidate = $this->dateTime($workDate->addDays($dayOffset), $time);
            $distance = abs($candidate->getTimestamp() - $reference->getTimestamp());
            if ($distance < $closestSeconds) {
                $closest = $candidate;
                $closestSeconds = $distance;
            }
        }

        return $closest;
    }

    private function dateTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date->toDateString().' '.$time);
    }

    private function overlapMinutes(
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $otherStart,
        CarbonImmutable $otherEnd,
    ): int {
        $overlapStart = $start->greaterThan($otherStart) ? $start : $otherStart;
        $overlapEnd = $end->lessThan($otherEnd) ? $end : $otherEnd;

        return $overlapEnd->greaterThan($overlapStart)
            ? $overlapStart->diffInMinutes($overlapEnd)
            : 0;
    }

    private function time(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);
        if (preg_match('/(\d{2}):(\d{2})/', $text, $matches) !== 1) {
            return null;
        }

        return $matches[1].':'.$matches[2];
    }
}
