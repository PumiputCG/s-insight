<?php

namespace App\Console\Commands\Area5s;

use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sRound;
use App\Services\Area5s\A5sScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ตรวจสุขภาพระบบ 5ส + map ข้อมูลเก่าที่ยังผูกไม่ครบ
 *
 * ใช้บนเซิร์ฟหลังรัน `php artisan migrate --force`:
 *   php artisan a5s:doctor          ตรวจอย่างเดียว ไม่แก้อะไร
 *   php artisan a5s:doctor --fix    แก้ mapping ที่เดาได้อย่างปลอดภัย
 *
 * "map ข้อมูลเก่า" ที่คำสั่งนี้ทำให้:
 *   1. layout ที่ยังไม่ผูกชั้น (floor_id ว่าง)
 *      - ใช้ layout ชื่อเดียวกันในรอบอื่นที่ผูกชั้นแล้วเป็นตัวชี้ขาด (layout ถูก clone ต่อรอบ จึงแม่นที่สุด)
 *      - ไม่มีใบพี่น้อง ค่อยเดาจากชื่อ เช่น "… – Floor 1" → ชั้นที่มีเลข 1 (ต้องเจอชั้นเดียวเท่านั้น)
 *   2. ชั้นที่ยังไม่ผูกอาคาร (zone_map_area_id ว่าง)
 *      - โซนมีอาคารเดียว → ผูกเลย
 *      - โซนมีหลายอาคาร → เลือกอาคารที่ "ยังไม่มีชั้นชื่อนี้" ถ้าเหลือใบเดียวก็ชี้ขาดได้
 *   3. รอบเก่าที่ยังไม่มี inspected_on → เติมจาก opened_at
 * ทุกข้อทำเฉพาะกรณีที่ชี้ขาดได้แน่นอน · กรณีกำกวมจะรายงานให้ตัดสินใจเอง ไม่เดามั่ว
 */
class A5sDoctor extends Command
{
    protected $signature = 'a5s:doctor {--fix : แก้ mapping ที่เดาได้อย่างปลอดภัย (ไม่ใส่ = ตรวจอย่างเดียว)}';

    protected $description = 'ตรวจความถูกต้องของระบบ 5ส และ map ข้อมูลเก่าที่ผูกไม่ครบ';

    private int $problems = 0;

    private int $fixed = 0;

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $this->info($fix ? '=== a5s:doctor (โหมดแก้ไข) ===' : '=== a5s:doctor (ตรวจอย่างเดียว) ===');
        $this->newLine();

        if (! $this->checkSchema()) {
            return self::FAILURE;
        }

        $this->checkRounds($fix);
        $this->checkMapping($fix);
        $this->checkTasks($fix);
        $this->checkScores();
        $this->checkPeople();

        $this->newLine();
        if ($this->problems === 0) {
            $this->info('✓ ไม่พบปัญหา'.($this->fixed ? " (แก้ไปแล้ว {$this->fixed} รายการ)" : ''));

            return self::SUCCESS;
        }

        $this->warn("พบ {$this->problems} เรื่องที่ต้องดู".($this->fixed ? " · แก้อัตโนมัติแล้ว {$this->fixed} รายการ" : ''));
        if (! $fix) {
            $this->line('  ลองใหม่ด้วย --fix เพื่อให้แก้ mapping ที่เดาได้อย่างปลอดภัย');
        }

        return self::SUCCESS;
    }

    /** schema ต้องครบก่อน ไม่งั้นตรวจต่อไม่ได้ */
    private function checkSchema(): bool
    {
        $this->line('[1] schema');
        $need = [
            'ตาราง a5s_task_attempts' => Schema::connection('mysql_area5s')->hasTable('a5s_task_attempts'),
            'a5s_rounds.seq' => Schema::connection('mysql_area5s')->hasColumn('a5s_rounds', 'seq'),
            'a5s_rounds.inspected_on' => Schema::connection('mysql_area5s')->hasColumn('a5s_rounds', 'inspected_on'),
        ];
        $missing = array_keys(array_filter($need, fn ($has) => ! $has));
        foreach ($need as $label => $has) {
            $this->line('    '.($has ? '✓' : '✗').' '.$label);
        }
        if ($missing) {
            $this->newLine();
            $this->error('  schema ยังไม่ครบ — รัน `php artisan migrate --force` ก่อน แล้วค่อยรันคำสั่งนี้ใหม่');

            return false;
        }
        $this->newLine();

        return true;
    }

    private function checkRounds(bool $fix): void
    {
        $this->line('[2] รอบ');
        $rounds = A5sRound::orderBy('year')->orderBy('month')->orderBy('seq')->get();
        $this->line("    รอบทั้งหมด {$rounds->count()}");

        $open = $rounds->where('status', 'open');
        if ($open->count() > 1) {
            $this->problems++;
            $this->warn('    ! มีรอบเปิดพร้อมกัน '.$open->count().' รอบ: '.$open->pluck('id')->implode(', '));
        }

        // รอบเก่าที่ยังไม่มีวันที่ตรวจ — เติมจากวันเปิดรอบ
        $noDate = $rounds->filter(fn (A5sRound $r) => ! $r->inspected_on && $r->opened_at);
        if ($noDate->isNotEmpty()) {
            $this->problems++;
            $this->warn('    ! รอบที่ยังไม่มีวันที่ตรวจ: '.$noDate->pluck('id')->implode(', '));
            if ($fix) {
                foreach ($noDate as $round) {
                    $round->inspected_on = $round->opened_at->toDateString();
                    $round->save();
                    $this->fixed++;
                    $this->line("      → รอบ {$round->id} ตั้ง inspected_on = {$round->inspected_on}");
                }
            }
        }

        // seq ซ้ำในเดือนเดียวกัน
        $dup = $rounds->groupBy(fn (A5sRound $r) => $r->year.'-'.$r->month.'-'.$r->seq)->filter(fn ($g) => $g->count() > 1);
        if ($dup->isNotEmpty()) {
            $this->problems++;
            $this->warn('    ! seq ซ้ำในเดือนเดียวกัน: '.$dup->keys()->implode(', '));
        }
        $this->newLine();
    }

    /** map ข้อมูลเก่า: layout→ชั้น และ ชั้น→อาคาร */
    private function checkMapping(bool $fix): void
    {
        $this->line('[3] mapping ข้อมูลเก่า');
        $db = DB::connection('mysql_area5s');

        // 3.1 ชั้นที่ยังไม่ผูกอาคาร — ผูกได้เมื่อโซนนั้นมีอาคารเดียว
        $floors = $db->table('a5s_floors')->whereNull('zone_map_area_id')->get();
        if ($floors->isEmpty()) {
            $this->line('    ✓ ทุกชั้นผูกอาคารแล้ว');
        } else {
            $this->problems++;
            $this->warn("    ! ชั้นที่ยังไม่ผูกอาคาร: {$floors->count()}");
            foreach ($floors as $floor) {
                $areas = $db->table('a5s_zone_map_areas as a')
                    ->join('a5s_zone_maps as m', 'm.id', '=', 'a.zone_map_id')
                    ->where('m.zone_id', $floor->zone_id)
                    ->where('a.is_active', 1)
                    ->get(['a.id', 'a.name']);

                $target = null;
                $why = '';
                if ($areas->count() === 1) {
                    $target = $areas[0];
                    $why = 'โซนนี้มีอาคารเดียว';
                } elseif ($areas->count() > 1) {
                    // อาคารที่ "ยังไม่มีชั้นชื่อนี้" — ถ้าเหลือใบเดียวก็ชี้ขาดได้
                    $taken = $db->table('a5s_floors')
                        ->whereNotNull('zone_map_area_id')
                        ->where('name', $floor->name)
                        ->pluck('zone_map_area_id')
                        ->unique()
                        ->flip();
                    $candidates = $areas->reject(fn ($a) => $taken->has($a->id))->values();
                    if ($candidates->count() === 1) {
                        $target = $candidates[0];
                        $why = 'อาคารอื่นในโซนมีชั้นชื่อนี้แล้ว เหลือใบเดียวที่ยังขาด';
                    }
                }

                if ($target) {
                    $this->line("      floor {$floor->id} ({$floor->name}) → อาคาร {$target->id} {$target->name}  [{$why}]");
                    if ($fix) {
                        $db->table('a5s_floors')->where('id', $floor->id)->update(['zone_map_area_id' => $target->id, 'updated_at' => now()]);
                        $this->fixed++;
                    }
                } else {
                    $this->line("      floor {$floor->id} ({$floor->name}) — โซนนี้มี {$areas->count()} อาคาร ชี้ขาดไม่ได้ เลือกเองที่หน้า จัดการพื้นที่");
                }
            }
        }

        // 3.2 layout ที่ยังไม่ผูกชั้น — จับคู่จากชื่อท้าย layout เช่น "… – Floor 2" กับชื่อชั้น F2
        $layouts = A5sLayout::whereNull('floor_id')->get(['id', 'round_id', 'name']);
        if ($layouts->isEmpty()) {
            $this->line('    ✓ ทุก layout ผูกชั้นแล้ว');
        } else {
            $this->problems++;
            $this->warn("    ! layout ที่ยังไม่ผูกชั้น: {$layouts->count()}");
            $allFloors = $db->table('a5s_floors')->get(['id', 'name', 'zone_id', 'zone_map_area_id']);
            foreach ($layouts as $layout) {
                // สัญญาณที่แม่นที่สุด: layout ชื่อเดียวกันในรอบอื่น (ถูก clone มาจากใบเดียวกัน) ที่ผูกชั้นแล้ว
                $sibling = $db->table('a5s_layouts')
                    ->where('name', $layout->name)
                    ->whereNotNull('floor_id')
                    ->pluck('floor_id')
                    ->unique()
                    ->values();

                $floorId = null;
                $why = '';
                if ($sibling->count() === 1) {
                    $floorId = (int) $sibling[0];
                    $why = 'ใบชื่อเดียวกันในรอบอื่นผูกชั้นนี้ไว้';
                } else {
                    $guess = $this->guessFloor($layout->name, $allFloors);
                    if ($guess) {
                        $floorId = (int) $guess->id;
                        $why = 'ชื่อ layout ระบุชั้นตรงกับชั้นเดียวในระบบ';
                    }
                }

                if ($floorId) {
                    $fname = $allFloors->firstWhere('id', $floorId)->name ?? $floorId;
                    $this->line("      layout {$layout->id} ({$layout->name}) → floor {$floorId} {$fname}  [{$why}]");
                    if ($fix) {
                        $db->table('a5s_layouts')->where('id', $layout->id)->update(['floor_id' => $floorId, 'updated_at' => now()]);
                        $this->fixed++;
                    }
                } else {
                    $this->line("      layout {$layout->id} ({$layout->name}) — ชี้ขาดไม่ได้ ต้องผูกเองที่หน้า mapping");
                }
            }
        }

        // 3.3 กรอบบนแปลนที่ยังไม่ได้วาด (ทำให้การ์ดอาคารในภาพรวมไม่มีภาพ)
        $zoneNoShape = $db->table('a5s_zones')->whereNull('shape_points')->count();
        $areaNoShape = $db->table('a5s_zone_map_areas')->whereNull('shape_points')->count();
        $mapNoImage = $db->table('a5s_zone_maps')->whereNull('image_path')->count();
        if ($zoneNoShape || $areaNoShape || $mapNoImage) {
            $this->problems++;
            $this->warn("    ! โซนยังไม่วาดกรอบ {$zoneNoShape} · อาคารยังไม่วาดกรอบ {$areaNoShape} · ภาพโซนที่ยังไม่อัปโหลด {$mapNoImage}");
            $this->line('      (ต้องวาด/อัปโหลดเองที่หน้า แปลนบริษัท — เดาแทนไม่ได้)');
        } else {
            $this->line('    ✓ โซน/อาคารวาดกรอบและมีภาพครบ');
        }
        $this->newLine();
    }

    /** เดาชั้นจากชื่อ layout เช่น "HR Room – Floor 1" → ชั้นชื่อ F1 / Floor 1 / 1 */
    private function guessFloor(string $layoutName, $floors)
    {
        if (! preg_match('/(?:floor|ชั้น)\s*([0-9]+)/i', $layoutName, $m)) {
            return null;
        }
        $n = (int) $m[1];
        $matched = $floors->filter(function ($floor) use ($n) {
            return preg_match('/([0-9]+)/', (string) $floor->name, $fm) && (int) $fm[1] === $n;
        })->values();

        // ชี้ขาดได้เฉพาะเมื่อเจอชั้นเดียว
        return $matched->count() === 1 ? $matched[0] : null;
    }

    private function checkTasks(bool $fix): void
    {
        $this->line('[4] จุด / task / ผลตรวจ');
        $db = DB::connection('mysql_area5s');
        $tasks = $db->table('a5s_tasks')->get([
            'id',
            'point_id',
            'round_id',
            'status',
            'submit_count',
            'result',
            'fail_reason',
            'advice',
            'evaluated_by',
            'submitted_at',
            'evaluated_at',
            'updated_at',
        ]);
        $attempts = $db->table('a5s_task_attempts')->get(['id', 'task_id', 'seq', 'result']);
        $byTask = $attempts->groupBy('task_id');
        $this->line("    task {$tasks->count()} · attempt {$attempts->count()}");

        $pointIds = $db->table('a5s_points')->pluck('id')->flip();
        $roundIds = $db->table('a5s_rounds')->pluck('id')->flip();
        $orphan = $tasks->filter(fn ($t) => ! $pointIds->has($t->point_id) || ! $roundIds->has($t->round_id));
        $this->report('task ที่ชี้จุด/รอบที่ไม่มีจริง', $orphan->pluck('id'));

        $dup = $tasks->groupBy(fn ($t) => $t->point_id.'-'.$t->round_id)->filter(fn ($g) => $g->count() > 1);
        $this->report('task ซ้ำ point+round', $dup->keys());

        $taskIds = $tasks->pluck('id')->flip();
        $this->report('attempt ที่ไม่มี task', $attempts->filter(fn ($a) => ! $taskIds->has($a->task_id))->pluck('id'));
        $this->report('attempt.result ที่ไม่ใช่ pass/fail', $attempts->filter(fn ($a) => ! in_array($a->result, ['pass', 'fail'], true))->pluck('id'));

        $missingAttempts = $tasks->filter(function ($task) use ($byTask) {
            return in_array($task->status, ['passed', 'failed'], true)
                && $byTask->get($task->id, collect())->isEmpty();
        })->values();

        if ($missingAttempts->isNotEmpty()) {
            $this->problems++;
            $shown = $missingAttempts->pluck('id')->take(20)->implode(', ');
            $this->warn("    ! decided tasks without attempt history: {$missingAttempts->count()} -> {$shown}".($missingAttempts->count() > 20 ? ' ...' : ''));

            if ($fix) {
                $now = now();
                $cardsByTask = $db->table('a5s_cards')
                    ->whereIn('task_id', $missingAttempts->pluck('id')->all())
                    ->orderBy('sort')
                    ->orderBy('id')
                    ->get(['task_id', 'title', 'detail'])
                    ->groupBy('task_id');

                foreach ($missingAttempts as $task) {
                    $pass = $task->status === 'passed';
                    $evaluatedAt = $task->evaluated_at ?: $task->updated_at ?: $now;
                    $submittedAt = $task->submitted_at ?: $evaluatedAt;
                    $cardSnapshot = $cardsByTask->get($task->id, collect())
                        ->map(fn ($card) => [
                            'title' => (string) $card->title,
                            'detail' => (string) $card->detail,
                        ])
                        ->values()
                        ->all();

                    $db->table('a5s_task_attempts')->insert([
                        'task_id' => $task->id,
                        'seq' => max(1, (int) $task->submit_count),
                        'result' => $pass ? 'pass' : 'fail',
                        'fail_reason' => $pass ? null : $task->fail_reason,
                        'advice' => $task->advice,
                        'cards_json' => json_encode($cardSnapshot, JSON_UNESCAPED_UNICODE),
                        'evaluated_by' => $task->evaluated_by,
                        'submitted_at' => $submittedAt,
                        'evaluated_at' => $evaluatedAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $this->fixed++;
                }

                $this->line("      -> backfilled {$missingAttempts->count()} missing attempt history rows from current passed/failed task status");
                $attempts = $db->table('a5s_task_attempts')->get(['id', 'task_id', 'seq', 'result']);
                $byTask = $attempts->groupBy('task_id');
            }
        }
        $mismatch = $tasks->filter(function ($t) use ($byTask) {
            if (! in_array($t->status, ['passed', 'failed'], true)) {
                return false;
            }
            $last = $byTask->get($t->id, collect())->sortBy('seq')->last();

            return ! $last || ($last->result === 'pass' ? 'passed' : 'failed') !== $t->status;
        });
        $this->report('task ที่สถานะไม่ตรงกับผลตรวจล่าสุด', $mismatch->pluck('id'));
        $this->newLine();
    }

    private function checkScores(): void
    {
        $this->line('[5] คะแนน');
        $db = DB::connection('mysql_area5s');
        $attempts = $db->table('a5s_task_attempts')->get(['task_id', 'result'])->groupBy('task_id');
        $taskIds = $db->table('a5s_tasks')->pluck('id');
        $calc = A5sScoreService::taskScores($taskIds);

        $bad = collect();
        foreach ($taskIds as $id) {
            $rows = $attempts->get($id, collect());
            $manual = $rows->isEmpty() ? null : round($rows->avg(fn ($a) => $a->result === 'pass' ? 100 : 0), 2);
            if (($calc[(int) $id] ?? null) !== $manual) {
                $bad->push($id);
            }
        }
        $this->report('คะแนนที่คำนวณไม่ตรงกับผลตรวจ', $bad);
        if ($bad->isEmpty()) {
            $this->line("    ✓ คะแนนตรงทั้ง {$taskIds->count()} ใบ");
        }
        $this->newLine();
    }

    private function checkPeople(): void
    {
        $this->line('[6] ผู้รับผิดชอบ / ผู้ประเมิน (รอบที่เปิดอยู่)');
        $db = DB::connection('mysql_area5s');
        $openId = (int) (A5sRound::open()?->id ?? 0);
        if (! $openId) {
            $this->line('    (ยังไม่มีรอบเปิด — ข้าม)');
            $this->newLine();

            return;
        }
        $points = $db->table('a5s_points as p')->join('a5s_layouts as l', 'l.id', '=', 'p.layout_id')
            ->where('l.round_id', $openId)->where('p.is_active', 1)->pluck('p.id');
        $assigned = $db->table('a5s_point_assignees')->whereNull('removed_at')->pluck('point_id')->unique()->flip();
        $this->report('จุดที่ยังไม่มีผู้รับผิดชอบ', $points->reject(fn ($id) => $assigned->has($id))->values());

        $layouts = $db->table('a5s_layouts')->where('round_id', $openId)->pluck('id');
        $scoped = $db->table('a5s_evaluator_scopes')->pluck('layout_id')->unique()->flip();
        $this->report('layout ที่ยังไม่มีผู้ประเมิน', $layouts->reject(fn ($id) => $scoped->has($id))->values());
        $this->newLine();
    }

    private function report(string $label, $ids): void
    {
        $ids = collect($ids);
        if ($ids->isEmpty()) {
            return;
        }
        $this->problems++;
        $shown = $ids->take(20)->implode(', ');
        $this->warn("    ! {$label}: {$ids->count()} → {$shown}".($ids->count() > 20 ? ' …' : ''));
    }
}
