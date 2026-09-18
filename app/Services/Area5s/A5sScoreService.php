<?php

namespace App\Services\Area5s;

use App\Models\Area5s\A5sTaskAttempt;
use Illuminate\Support\Collection;

/**
 * คะแนน 5ส เป็น % — ผ่าน=100 / ไม่ผ่าน=0 เฉลี่ยทุกครั้งที่ส่ง (ตาม Manager 2026-07-24)
 *
 * ลำดับการเฉลี่ย (เฉลี่ยซ้อนชั้น):
 *   จุด/ครั้งตรวจ = avg(attempt ของ task นั้น)            เช่น (0+0+100)/3 = 33.33
 *   คน/พื้นที่ ต่อครั้งตรวจ = avg(คะแนนจุดในกลุ่มนั้น)
 *   เดือน / รวม = avg(คะแนนของแต่ละครั้งตรวจ)
 * จุดที่ยังไม่มี attempt (ยังไม่ตัดสิน) = ไม่นับ (null) ไม่ดึงค่าเฉลี่ยลง
 */
class A5sScoreService
{
    /** คะแนนต่อ task (จุด/ครั้งตรวจ) จาก attempts — คืน [task_id => float|null] */
    public static function taskScores(Collection $taskIds): array
    {
        $ids = $taskIds->map(fn ($v) => (int) $v)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $byTask = A5sTaskAttempt::whereIn('task_id', $ids->all())
            ->get(['task_id', 'result'])
            ->groupBy('task_id');

        $out = [];
        foreach ($ids as $id) {
            $rows = $byTask->get($id);
            $out[$id] = ($rows && $rows->isNotEmpty())
                ? round($rows->avg(fn ($a) => $a->result === 'pass' ? 100 : 0), 2)
                : null;
        }

        return $out;
    }

    /** เฉลี่ยคะแนนจากชุดค่า (ข้าม null) — คืน float|null */
    public static function average(array $scores): ?float
    {
        $vals = array_values(array_filter($scores, fn ($v) => $v !== null));

        return $vals ? round(array_sum($vals) / count($vals), 2) : null;
    }

    /**
     * คะแนนรวมของชุด task (เฉลี่ยคะแนนจุด ข้ามจุดที่ยังไม่ตัดสิน)
     * ใช้ได้ทั้งระดับ คน/พื้นที่/รอบ — แค่ส่ง task_id ที่เกี่ยวเข้ามา
     */
    public static function scoreOfTasks(Collection $taskIds): ?float
    {
        return self::average(self::taskScores($taskIds));
    }

    /** จัดรูปแสดงผล: 33.33 -> "33.33%" · null -> "—" */
    public static function label(?float $score): string
    {
        return $score === null ? '—' : rtrim(rtrim(number_format($score, 2), '0'), '.').'%';
    }
}
