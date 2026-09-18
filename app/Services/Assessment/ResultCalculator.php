<?php

namespace App\Services\Assessment;

use App\Models\Assessment\AsmLevelProp;
use App\Models\Assessment\AsmScoreBox;
use Illuminate\Support\Collection;

/**
 * คำนวณผลลัพธ์หัวข้อ 5 ต่อพนักงาน — แยกตาม "ลำดับผู้ประเมิน" ที่ admin ตั้งในคอลัมน์ชนิด Input
 * (union ของ input_levels ทุกคอลัมน์ เช่น Leadership 1,2,3 + Attritude 1,2 → คิด 3 ชุด: ลำดับ 1,2,3)
 *
 * กติกา (ตกลงกับ Manager ในหัวข้อ 3):
 *   - สัดส่วน (3.1 percent):  component = (ค่า ÷ คะแนนเต็มรวมของคอลัมน์ที่มีค่า) × weight — N/A ตัดออกจากตัวหาร
 *   - ชนิด Input: ใช้ค่าช่องของลำดับนั้น ; คอลัมน์ที่ไม่มีลำดับนั้น/ยังไม่กรอก = N/A ของชุดนั้น
 *   - Attendance/หักคะแนน:   fraction = max(0, 100 + Σ(จำนวน × rate)) / 100 (ว่าง = 0 ครั้ง) × weight — เท่ากันทุกลำดับ
 *   - Attendance/เกรด:        ค่ามากกว่า 0 → จำกัดเกรดสูงสุดตาม grade_cap (ใช้ตัวแย่สุด ทุกลำดับ)
 *   - เพิ่มเติม (3.1 extra) + ชนิดคะแนนเพิ่มเติม (bonus): บวก/ลบตรงตามค่า (ช่องแรก) — เท่ากันทุกลำดับ
 *   - ไม่คำนวณ (none) / ไม่ตั้งสัดส่วน: ข้าม
 *   - เกรด (ตาม legacy): A≥95, B≥85, C≥75, D≥60, F<60
 */
class ResultCalculator
{
    private const GRADE_ORDER = ['A', 'B', 'C', 'D', 'F'];

    private const GRADE_TEXT = [
        'A' => 'Outstanding', 'B' => 'Exceeds expectation', 'C' => 'Meets expectation',
        'D' => 'Below expectation', 'F' => 'Needs improvement',
    ];

    /** @var Collection<int,AsmScoreBox> */
    protected Collection $tops;

    /** @var array<int,Collection<int,AsmScoreBox>> */
    protected array $children = [];

    protected array $props;

    public function __construct()
    {
        $all = AsmScoreBox::orderBy('sort')->orderBy('id')->get();
        $this->tops = $all->whereNull('parent_id')->values();
        foreach ($all->whereNotNull('parent_id')->groupBy('parent_id') as $pid => $kids) {
            $this->children[$pid] = $kids->values();
        }
        $this->props = AsmLevelProp::matrix();
    }

    /**
     * ลำดับผู้ประเมินที่ใช้จริง = union ของ input_levels ทุกคอลัมน์ชนิด Input (เรียงน้อย→มาก)
     * ไม่มีคอลัมน์ Input เลย → [1,2]
     *
     * @return int[]
     */
    public function evalLevels(): array
    {
        $set = [];
        foreach ($this->tops as $top) {
            foreach ($this->children[$top->id] ?? collect() as $kid) {
                if ($kid->type !== 'input') {
                    continue;
                }
                foreach (array_filter(explode(',', $kid->input_levels ?: '1,2')) as $l) {
                    $set[(int) $l] = true;
                }
            }
        }
        $levels = array_keys($set);
        sort($levels);

        return $levels === [] ? [1, 2] : $levels;
    }

    /**
     * คำนวณ 1 คน — $vals: [box_id => ['v' => ?float, 'v2' => ?float, 'v3' => ?float, 'v4' => ?float]]
     *
     * @return array{levels:int[],totals:array<int,?float>,grades:array<int,?string>,texts:array<int,?string>}
     */
    public function compute(?int $level, array $vals): array
    {
        $evalLevels = $this->evalLevels();
        $capIdx = -1;
        $capNames = [];   // ชื่อคอลัมน์ที่ trigger cap แต่ละขั้น — ไว้วงเล็บต่อท้ายคำบรรยาย เช่น "Meets expectation (หนังสือเตือน)"

        // grade cap (เหตุวินัย) — ดูเสมอแม้หัวข้อไม่อยู่ในสูตร
        foreach ($this->tops as $top) {
            foreach ($this->children[$top->id] ?? collect() as $kid) {
                $v = $vals[$kid->id]['v'] ?? null;
                if ($kid->type === 'attendance' && $kid->att_form === 'grade' && $kid->grade_cap && (float) ($v ?? 0) > 0) {
                    $i = (int) array_search($kid->grade_cap, self::GRADE_ORDER, true);
                    $capIdx = max($capIdx, $i);
                    $capNames[$i][] = $kid->name;
                }
            }
        }

        $blank = [
            'levels' => $evalLevels,
            'totals' => array_fill_keys($evalLevels, null),
            'grades' => array_fill_keys($evalLevels, null),
            'texts' => array_fill_keys($evalLevels, null),
        ];

        if ($level === null || $level < 1) {
            return $blank;   // ระดับ 0 / ยังไม่จัดระดับ = ไม่คำนวณ (ระดับเกิน 5 ที่ admin เพิ่มเอง คิดตามสัดส่วน 3.1 ปกติ)
        }

        $props = $this->props[$level] ?? [];
        $tot = array_fill_keys($evalLevels, 0.0);
        $extra = 0.0;
        $any = false;

        foreach ($this->tops as $top) {
            $prop = $props[$top->id] ?? null;
            if (! $prop || $prop['mode'] === 'none') {
                continue;
            }
            $kids = $this->children[$top->id] ?? collect();

            // เพิ่มเติม (+/- แยก): รวมค่าคอลัมน์รองตรง ๆ
            if ($prop['mode'] === 'extra') {
                foreach ($kids as $kid) {
                    $v = $vals[$kid->id]['v'] ?? null;
                    if ($v !== null) {
                        $extra += $v;
                        $any = true;
                    }
                }

                continue;
            }

            $w = (float) $prop['weight'];
            if ($w <= 0) {
                continue;
            }

            $ded = null;                                      // Attendance หักคะแนน
            $base = 0.0;                                      // คอลัมน์ค่าเดียว (เท่ากันทุกลำดับ)
            $baseFull = 0.0;
            $sumL = array_fill_keys($evalLevels, 0.0);        // ส่วนของชนิด Input ต่อลำดับ
            $fullL = array_fill_keys($evalLevels, 0.0);

            foreach ($kids as $kid) {
                $v = $vals[$kid->id]['v'] ?? null;
                if ($kid->type === 'attendance' && ($kid->att_form ?? 'score') === 'score' && $kid->rate !== null) {
                    $ded = ($ded ?? 0.0) + ((float) ($v ?? 0)) * (float) $kid->rate;   // ว่าง = 0 ครั้ง
                } elseif ($kid->type === 'bonus') {
                    if ($v !== null) {
                        $extra += $v;
                        $any = true;
                    }
                } elseif ($kid->type === 'input' && (float) ($kid->full_score ?? 0) > 0) {
                    // ค่าช่องตามตำแหน่งของลำดับนั้นใน input_levels ของคอลัมน์
                    $slots = array_values(array_filter(explode(',', $kid->input_levels ?: '1,2')));
                    foreach ($evalLevels as $L) {
                        $pos = array_search((string) $L, $slots, true);
                        if ($pos === false) {
                            continue;                          // คอลัมน์นี้ไม่มีผู้ประเมินลำดับ L
                        }
                        $vv = $vals[$kid->id][$pos === 0 ? 'v' : 'v'.($pos + 1)] ?? null;
                        if ($vv === null) {
                            continue;                          // ยังไม่กรอก = N/A ของชุดนั้น
                        }
                        $sumL[$L] += (float) $vv;
                        $fullL[$L] += (float) $kid->full_score;
                    }
                } elseif ($kid->type === 'score' && (float) ($kid->full_score ?? 0) > 0) {
                    if ($v === null) {
                        continue;                              // N/A → ตัดออกจากตัวหาร
                    }
                    $base += (float) $v;
                    $baseFull += (float) $kid->full_score;
                }
            }

            if ($ded !== null) {
                $frac = max(0.0, 100.0 + $ded) / 100.0;
                foreach ($evalLevels as $L) {
                    $tot[$L] += $frac * $w;
                }
                $any = true;
            } else {
                foreach ($evalLevels as $L) {
                    $denom = $baseFull + $fullL[$L];
                    if ($denom > 0) {
                        $tot[$L] += (($base + $sumL[$L]) / $denom) * $w;
                        $any = true;
                    }
                }
            }
        }

        if (! $any) {
            return $blank;
        }

        $totals = [];
        $grades = [];
        $texts = [];
        foreach ($evalLevels as $L) {
            $t = $tot[$L] + $extra;
            [$g, $x] = $this->grade($t, $capIdx, $capNames[$capIdx] ?? []);
            $totals[$L] = round($t, 2);
            $grades[$L] = $g;
            $texts[$L] = $x;
        }

        return ['levels' => $evalLevels, 'totals' => $totals, 'grades' => $grades, 'texts' => $texts];
    }

    /**
     * เกรดจากคะแนน + grade cap (เหตุวินัย) — คืน [ตัวอักษร, คำบรรยาย]
     * เกรดสุดท้ายชนเพดาน cap (โดนกดลง หรือเท่าพอดี) → วงเล็บชื่อคอลัมน์ที่เป็นเหตุต่อท้ายคำบรรยาย
     * เช่น "Meets expectation (หนังสือเตือน)" ; เกรดจากคะแนนแย่กว่า cap เอง = ไม่วงเล็บ (ไม่ใช่เพราะเหตุวินัย)
     *
     * @param  string[]  $capNames
     */
    protected function grade(float $total, int $capIdx, array $capNames = []): array
    {
        $letter = $total >= 95 ? 'A' : ($total >= 85 ? 'B' : ($total >= 75 ? 'C' : ($total >= 60 ? 'D' : 'F')));
        $idx = (int) array_search($letter, self::GRADE_ORDER, true);
        if ($capIdx >= 0 && $capIdx >= $idx) {
            $letter = self::GRADE_ORDER[$capIdx];
            $suffix = $capNames === [] ? '' : ' ('.implode(', ', array_unique($capNames)).')';

            return [$letter, self::GRADE_TEXT[$letter].$suffix];
        }

        return [$letter, self::GRADE_TEXT[$letter]];
    }

    /**
     * แปลงผลเป็นลิสต์ cell — แบ่งโซนต่อลำดับ: [รวม L1, เกรด L1, คำบรรยาย L1, รวม L2, ...]
     *
     * @return string[]
     */
    public function rowCells(array $r): array
    {
        $n = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $cells = [];
        foreach ($r['levels'] as $L) {
            $cells[] = $n($r['totals'][$L] ?? null);
            $cells[] = $r['grades'][$L] ?? '—';
            $cells[] = $r['texts'][$L] ?? '—';
        }

        return $cells;
    }

    /** หัวคอลัมน์แถว 2 (ซ้ำต่อลำดับ): คะแนนรวม | เกรด | เกรด คำบรรยาย @return string[] */
    public function resultHeaders(): array
    {
        $h = [];
        foreach ($this->evalLevels() as $L) {
            $h[] = 'คะแนนรวม';
            $h[] = 'เกรด';
            $h[] = 'เกรด คำบรรยาย';
        }

        return $h;
    }

    /**
     * โซนหัวแถว 1 — คั่นต่อลำดับผู้ประเมิน: [['label' => 'ลำดับ 1', 'span' => 3], ...]
     *
     * @return array<int,array{label:string,span:int}>
     */
    public function resultHeaderGroups(): array
    {
        return array_map(fn (int $L): array => ['label' => "ลำดับ {$L}", 'span' => 3], $this->evalLevels());
    }
}
