<?php

namespace App\Services\Area5s;

use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sCard;
use App\Models\Area5s\A5sEvaluatorScope;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sNotification;
use App\Models\Area5s\A5sPoint;
use App\Models\Area5s\A5sPointAssignee;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Area5s\A5sTaskAttempt;
use App\Models\Insight\Employee;
use Carbon\Carbon;

/**
 * เปิด/ปิดรอบรายเดือน 5ส (a5s_rounds.year เก็บเป็น พ.ศ.)
 * - เปิดได้ทีละรอบเดียว: เปิดเดือนใหม่ = ปิดรอบที่เปิดค้างอัตโนมัติ (สลับเดือนไปมาได้ ข้อมูลผูกกับรอบเดิม)
 * - เปิดรอบแล้ว snapshot สร้าง task ให้ทุกจุด active ทันที เพื่อเก็บประวัติ "ยังไม่ดำเนินการ" ของเดือนนั้น
 */
class A5sRoundService
{
    public const MONTHS_TH = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];

    public static function monthLabel(int $month): string
    {
        return self::MONTHS_TH[$month] ?? (string) $month;
    }

    /** ปี พ.ศ. ปัจจุบัน */
    public static function currentYearBe(): int
    {
        return (int) now()->year + 543;
    }

    /** ป้ายรอบ: "กรกฎาคม 2569" หรือ "กรกฎาคม 2569 · ครั้งที่ 2" */
    public static function roundLabel(A5sRound $round): string
    {
        $base = self::monthLabel($round->month).' '.$round->year;

        return ((int) ($round->seq ?? 1)) > 1 ? $base.' · ครั้งที่ '.$round->seq : $base;
    }

    /**
     * ตั้งค่าครั้งตรวจของเดือน (Manager 2026-07-24) — รับวันที่หลายรายการ สร้าง/อัปเดตรอบย่อย seq 1..N
     * รอบที่มีอยู่แล้ว = แก้เฉพาะวันที่ (คงสถานะ/ข้อมูลเดิม เช่น ครั้งที่ 1 ที่มีงานแล้ว) · รอบใหม่ = สถานะ planned (ยังไม่เปิด)
     *
     * @param  array<int, string|null>  $dates  วันที่ (Y-m-d) เรียงตามครั้งที่ 1..N
     */
    public static function planInspections(int $yearBe, int $month, array $dates, int $userId): void
    {
        $existing = A5sRound::where('year', $yearBe)->where('month', $month)->get()->keyBy('seq');
        $seq = 0;
        foreach ($dates as $date) {
            $seq++;
            $inspectedOn = $date ? Carbon::parse($date)->toDateString() : null;
            if ($round = $existing->get($seq)) {
                if ($round->inspected_on?->toDateString() !== $inspectedOn) {
                    $round->update(['inspected_on' => $inspectedOn]);
                }

                continue;
            }
            A5sRound::create([
                'year' => $yearBe,
                'month' => $month,
                'seq' => $seq,
                'inspected_on' => $inspectedOn,
                'status' => 'planned',
                'deadline_at' => Carbon::create($yearBe - 543, $month, 1)->endOfMonth(),
            ]);
        }
        A5sActivityLog::write($userId, 'round.plan', 'round', 0, [
            'year' => $yearBe, 'month' => $month, 'count' => $seq,
        ]);
    }

    /**
     * เปิดรอบย่อยที่เจาะจง (planned/closed → open) — ปิดรอบที่เปิดค้าง แล้ว clone โครงถ้ายังไม่มี (lazy) + snapshot tasks
     */
    public static function openRound(A5sRound $round, int $userId): A5sRound
    {
        self::closeOpenRounds($userId);

        // clone โครงเฉพาะรอบที่ยังไม่มี layout (planned เปิดครั้งแรก) — เอาจากรอบล่าสุดที่มีโครง
        if (! A5sLayout::where('round_id', $round->id)->exists()) {
            if ($source = self::latestRoundWithLayouts($round->id)) {
                self::cloneStructure($source, $round);
            }
        }

        $round->update([
            'status' => 'open',
            'opened_at' => $round->opened_at ?? now(),
            'closed_at' => null,
            'closed_by' => null,
        ]);
        $created = static::ensureTasks($round);

        A5sActivityLog::write($userId, 'round.open', 'round', $round->id, [
            'year' => $round->year, 'month' => $round->month, 'seq' => $round->seq, 'tasks_created' => $created,
        ]);

        return $round;
    }

    /** รอบล่าสุดที่มี layout ผูกอยู่ (ไว้ clone โครง) — เรียงปี/เดือน/ครั้ง ล่าสุดก่อน ไม่นับรอบ $exceptId */
    private static function latestRoundWithLayouts(int $exceptId): ?A5sRound
    {
        return A5sRound::where('id', '!=', $exceptId)
            ->whereHas('tasks') // มี task = เคยมีโครง
            ->orderByDesc('year')->orderByDesc('month')->orderByDesc('seq')
            ->get()
            ->first(fn (A5sRound $r) => A5sLayout::where('round_id', $r->id)->exists());
    }

    /**
     * เปิด "ครั้งตรวจใหม่" ในเดือนเดิม (seq +1) — clone โครง layout/จุด/ผู้รับผิดชอบ/ผู้ประเมิน จากครั้งล่าสุดของเดือนนั้น
     * ใช้เมื่อเดือนนั้นมีรอบอยู่แล้วและต้องการตรวจซ้ำรอบใหม่ในเดือนเดียวกัน (Manager 2026-07-24)
     */
    public static function openNewInspection(int $yearBe, int $month, int $userId): A5sRound
    {
        self::closeOpenRounds($userId);

        $source = A5sRound::where('year', $yearBe)->where('month', $month)->orderByDesc('seq')->first();
        $nextSeq = (int) (A5sRound::where('year', $yearBe)->where('month', $month)->max('seq') ?? 0) + 1;

        $round = A5sRound::create([
            'year' => $yearBe,
            'month' => $month,
            'seq' => $nextSeq,
            'inspected_on' => now()->toDateString(),
            'status' => 'open',
            'deadline_at' => Carbon::create($yearBe - 543, $month, 1)->endOfMonth(),
            'opened_at' => now(),
        ]);

        if ($source) {
            self::cloneStructure($source, $round);
        }
        $created = static::ensureTasks($round);

        A5sActivityLog::write($userId, 'round.open', 'round', $round->id, [
            'year' => $yearBe, 'month' => $month, 'seq' => $nextSeq, 'tasks_created' => $created, 'new_inspection' => true,
        ]);

        return $round;
    }

    /** รอบนี้ "มีข้อมูลจริง" ไหม — มีการส่ง/การ์ด/ผลตรวจ = ลบไม่ได้ · planned หรือเปิดแล้วยังไม่มีใครส่ง = ลบได้ */
    public static function roundHasData(A5sRound $round): bool
    {
        $taskIds = A5sTask::where('round_id', $round->id)->pluck('id');
        if ($taskIds->isEmpty()) {
            return false;
        }
        $hasSubmission = A5sTask::whereIn('id', $taskIds)
            ->where(fn ($q) => $q->where('submit_count', '>', 0)
                ->orWhereIn('status', ['submitted', 'resubmitted', 'passed', 'failed', 'draft']))
            ->exists();

        return $hasSubmission
            || A5sCard::whereIn('task_id', $taskIds)->exists()
            || A5sTaskAttempt::whereIn('task_id', $taskIds)->exists();
    }

    /** ลบครั้งตรวจ (เฉพาะที่ยังไม่มีข้อมูล) พร้อมโครงที่ clone มา — คืน true ถ้าลบสำเร็จ */
    public static function deleteRound(A5sRound $round, int $userId): bool
    {
        if (self::roundHasData($round)) {
            return false;
        }

        $layoutIds = A5sLayout::where('round_id', $round->id)->pluck('id');
        $pointIds = $layoutIds->isEmpty() ? collect() : A5sPoint::whereIn('layout_id', $layoutIds)->pluck('id');
        $taskIds = A5sTask::where('round_id', $round->id)->pluck('id');

        if ($taskIds->isNotEmpty()) {
            A5sTaskAttempt::whereIn('task_id', $taskIds)->delete();
            A5sCard::whereIn('task_id', $taskIds)->delete();
        }
        A5sTask::where('round_id', $round->id)->delete();
        if ($layoutIds->isNotEmpty()) {
            A5sEvaluatorScope::whereIn('layout_id', $layoutIds)->delete();
        }
        if ($pointIds->isNotEmpty()) {
            A5sPointAssignee::whereIn('point_id', $pointIds)->delete();
            A5sPoint::whereIn('id', $pointIds)->delete();
        }
        if ($layoutIds->isNotEmpty()) {
            A5sLayout::whereIn('id', $layoutIds)->delete();
        }

        A5sActivityLog::write($userId, 'round.delete', 'round', $round->id, [
            'year' => $round->year, 'month' => $round->month, 'seq' => $round->seq,
        ]);
        $round->delete();

        return true;
    }

    /** ปิดรอบที่เปิดอยู่ทั้งหมด (มีได้ทีละรอบ) */
    private static function closeOpenRounds(int $userId): void
    {
        A5sRound::where('status', 'open')->get()->each(function (A5sRound $other) use ($userId) {
            $other->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId]);
            A5sActivityLog::write($userId, 'round.close', 'round', $other->id, [
                'year' => $other->year, 'month' => $other->month, 'auto_switch' => true,
            ]);
        });
    }

    /**
     * clone โครงพื้นที่ (layout ผูกรอบ + จุด + ผู้รับผิดชอบ + ผู้ประเมิน) จากรอบต้นทางมารอบใหม่
     * โซน/ภาพย่อย/อาคาร/ชั้น เป็นของกลาง (ไม่ผูกรอบ) จึงไม่ clone — clone เฉพาะ layout/จุดที่ผูกรอบ
     */
    public static function cloneStructure(A5sRound $source, A5sRound $target): int
    {
        $layouts = A5sLayout::where('round_id', $source->id)->get();
        $cloned = 0;
        foreach ($layouts as $layout) {
            $newLayout = $layout->replicate();
            $newLayout->round_id = $target->id;
            $newLayout->created_at = now();
            $newLayout->updated_at = now();
            $newLayout->save();
            $cloned++;

            foreach (A5sPoint::where('layout_id', $layout->id)->get() as $point) {
                $newPoint = $point->replicate();
                $newPoint->layout_id = $newLayout->id;
                $newPoint->save();

                foreach (A5sPointAssignee::where('point_id', $point->id)->whereNull('removed_at')->get() as $a) {
                    $na = $a->replicate();
                    $na->point_id = $newPoint->id;
                    $na->save();
                }
                foreach (A5sEvaluatorScope::where('layout_id', $layout->id)->where('point_id', $point->id)->get() as $sc) {
                    $ns = $sc->replicate();
                    $ns->layout_id = $newLayout->id;
                    $ns->point_id = $newPoint->id;
                    $ns->save();
                }
            }
            foreach (A5sEvaluatorScope::where('layout_id', $layout->id)->whereNull('point_id')->get() as $sc) {
                $ns = $sc->replicate();
                $ns->layout_id = $newLayout->id;
                $ns->point_id = null;
                $ns->save();
            }
        }

        return $cloned;
    }

    /** เปิดรอบเดือน (year = พ.ศ.) — ปิดรอบอื่นที่เปิดอยู่ก่อน แล้ว snapshot tasks */
    public static function openMonth(int $yearBe, int $month, int $userId): A5sRound
    {
        A5sRound::where('status', 'open')
            ->where(fn ($q) => $q->where('year', '!=', $yearBe)->orWhere('month', '!=', $month))
            ->get()
            ->each(function (A5sRound $other) use ($userId) {
                $other->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId]);
                A5sActivityLog::write($userId, 'round.close', 'round', $other->id, [
                    'year' => $other->year, 'month' => $other->month, 'auto_switch' => true,
                ]);
            });

        $round = A5sRound::firstOrCreate(
            ['year' => $yearBe, 'month' => $month],
            [
                'status' => 'open',
                'deadline_at' => Carbon::create($yearBe - 543, $month, 1)->endOfMonth(),
                'opened_at' => now(),
            ]
        );
        // เปิดรอบเดิมที่เคยปิดกลับมา — ข้อมูล task/การ์ดของเดือนนั้นกลับมาใช้งานต่อ
        if (! $round->isOpen()) {
            $round->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null]);
        }

        $created = static::ensureTasks($round);
        A5sActivityLog::write($userId, 'round.open', 'round', $round->id, [
            'year' => $yearBe, 'month' => $month, 'tasks_created' => $created,
        ]);

        // แจ้งเตือนผู้รับผิดชอบทุกคน (in-app)
        $codes = A5sPointAssignee::whereNull('removed_at')->pluck('employee_code')->unique();
        foreach ($codes as $code) {
            A5sNotification::create([
                'employee_code' => $code,
                'type' => 'round.opened',
                'title' => 'เปิดรอบ 5ส '.static::monthLabel($month).' '.$yearBe,
                'body' => 'เพิ่มการ์ดและส่งตรวจได้แล้ว',
                'link' => route('area5s.responsible.index'),
            ]);
        }

        return $round;
    }

    public static function closeRound(A5sRound $round, int $userId): void
    {
        if (! $round->isOpen()) {
            return;
        }

        $round->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId]);
        A5sActivityLog::write($userId, 'round.close', 'round', $round->id, [
            'year' => $round->year, 'month' => $round->month,
        ]);
    }

    /** snapshot: สร้าง task ให้ทุกจุด active ของ layout active ที่ยังไม่มี task ในรอบนี้ — คืนจำนวนที่สร้าง */
    public static function ensureTasks(A5sRound $round): int
    {
        // เฉพาะจุดของ layout ที่ผูกกับรอบนี้ (layout รายเดือน) — รอบใหม่ที่ยังไม่มี layout = 0 task เริ่มว่าง
        $points = A5sPoint::with('layout')
            ->where('is_active', true)
            ->whereHas('layout', fn ($q) => $q->where('is_active', true)->where('round_id', $round->id))
            ->get();
        if ($points->isEmpty()) {
            return 0;
        }

        $existing = A5sTask::where('round_id', $round->id)->pluck('point_id')->flip();
        $assigneesByPoint = A5sPointAssignee::whereIn('point_id', $points->pluck('id'))
            ->whereNull('removed_at')
            ->get()
            ->groupBy('point_id');
        $scopes = A5sEvaluatorScope::whereIn('layout_id', $points->pluck('layout_id')->unique())->get();
        $codes = $assigneesByPoint->flatten()->pluck('employee_code')
            ->merge($scopes->pluck('employee_code'))
            ->unique()
            ->values();
        $employees = Employee::active()->whereIn('employee_code', $codes)->get()->keyBy('employee_code');
        $nameOf = fn (string $code) => ($e = $employees->get($code)) ? ($e->fullNameTh() ?: $e->name_en ?: $code) : $code;

        $created = 0;
        foreach ($points as $point) {
            if ($existing->has($point->id)) {
                continue;
            }

            $pointAssignees = $assigneesByPoint->get($point->id, collect())
                ->map(fn ($a) => ['code' => $a->employee_code, 'name' => $nameOf($a->employee_code)])
                ->values()->all();
            $pointEvaluators = $scopes
                ->filter(fn ($s) => (int) $s->layout_id === (int) $point->layout_id
                    && ($s->point_id === null || (int) $s->point_id === (int) $point->id))
                ->unique('employee_code')
                ->map(fn ($s) => ['code' => $s->employee_code, 'name' => $nameOf($s->employee_code)])
                ->values()->all();

            A5sTask::create([
                'round_id' => $round->id,
                'point_id' => $point->id,
                'layout_id' => $point->layout_id,
                'layout_name' => $point->layout?->name ?? '-',
                'point_code' => $point->code,
                'point_name' => $point->name,
                'point_description' => $point->description,
                'assignees_json' => $pointAssignees,
                'evaluators_json' => $pointEvaluators,
                'status' => 'not_started',
            ]);
            $created++;
        }

        return $created;
    }
}
