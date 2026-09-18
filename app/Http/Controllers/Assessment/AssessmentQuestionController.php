<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Assessment\Concerns\HandlesAssessmentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assessment\AsmBoxNote;
use App\Models\Assessment\AsmLevelProp;
use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmQuestion;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmScoreBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View as ViewContract;

/**
 * กำหนดคำถาม (admin + HR) — ต่อคอลัมน์ชนิด Input × ลำดับผู้ประเมินที่คอลัมน์นั้นตั้งไว้
 * ลำดับ 1 เห็นชุดคำถามของลำดับ 1, ลำดับ 2 เห็นของลำดับ 2 · รองรับ TH/EN/MY
 */
class AssessmentQuestionController extends Controller
{
    use HandlesAssessmentAccess;

    public function index(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }
        if (! AsmRound::open()) {
            return redirect()->route('assessment.rounds.index')->with('round_warn', 'ต้องเปิดรอบก่อนจึงจะจัดการระบบภายในได้');
        }

        $round = AsmRound::open();

        // แท็บ = "ระดับตำแหน่ง" ชุดเดียวกับที่ใช้กำหนดสัดส่วนคะแนน (หัวข้อ 3.1)
        // เอาระดับที่มีตำแหน่งจัดไว้ + ระดับที่ตั้งสัดส่วนไว้ มารวมกัน · ระดับ 0 ไม่ถูกประเมินจึงตัดออก
        $levels = collect(AsmPositionLevel::map($round->id))->values()
            ->merge(AsmLevelProp::query()->distinct()->pluck('level'))
            ->map(fn ($l) => (int) $l)
            ->filter(fn (int $l) => $l >= 1)
            ->unique()->sort()->values()->all();

        $activeLevel = (int) (request('level') ?: ($levels[0] ?? 1));
        if (! in_array($activeLevel, $levels, true)) {
            $activeLevel = (int) ($levels[0] ?? 1);
        }

        $boxes = AsmScoreBox::whereNull('parent_id')->with('children')
            ->orderBy('sort')->orderBy('id')->get();
        $propMatrix = AsmLevelProp::matrix();

        return view('assessment.questions', [
            'me' => app('current_user'),
            'openRound' => $round,
            'evalLevels' => $levels,
            'activeLevel' => $activeLevel,
            // โครงเดียวกับแบบฟอร์มที่ผู้ประเมินเห็น: หัวข้อหลัก → คอลัมน์ย่อย + คะแนนตัวอย่าง
            'boxes' => $boxes,
            'sections' => $this->previewSections($boxes, $propMatrix[$activeLevel] ?? []),
            'notes' => AsmBoxNote::matrix(),
            // คำอธิบายของจุดพิเศษ (ตอนนี้มี 'total' = คะแนนรวมในหัวฟอร์ม)
            'slotNotes' => AsmBoxNote::slotMatrix(),
            'inputBoxes' => AsmScoreBox::whereNotNull('parent_id')->where('type', 'input')
                ->orderBy('sort')->orderBy('id')->get(),
            'questions' => AsmQuestion::orderBy('sort')->orderBy('id')->get()->groupBy(fn ($q) => $q->box_id.'-'.$q->level),
        ]);
    }

    /**
     * คะแนนตัวอย่างต่อคอลัมน์ — ค่าคงที่ (ไม่สุ่ม) ให้ admin เห็นหน้าตาเหมือนฟอร์มจริง
     * เลียนแบบรูปแบบค่าที่ evaluateShow ส่งให้ view
     */
    private function demoValue(AsmScoreBox $kid, int $index): string
    {
        if ($kid->type === 'attendance') {
            // ช่องจำนวนครั้ง: ป่วย/ลากิจ/มาสาย มีค่า, ที่เหลือ 0 · ช่องชนิดเกรด (หนังสือเตือน/พักงาน) = 0
            if (($kid->att_form ?? 'score') === 'grade') {
                return '0';
            }

            return (string) ([1, 2, 0, 0, 4, 0][$index % 6]);
        }

        if ($kid->type === 'bonus') {
            return '2';
        }

        // score / input → 80% ของคะแนนเต็ม (เต็ม 10 → 8)
        $full = (float) ($kid->full_score ?? 10);
        $val = $full > 0 ? round($full * 0.8, 2) : 8;

        return rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
    }

    /**
     * โครง "หัวข้อ → คอลัมน์" แบบเดียวกับแบบฟอร์มจริง พร้อมคะแนนตัวอย่าง
     * ใช้เรนเดอร์หน้ากำหนดคำถามให้หน้าตาตรงกับที่ผู้ประเมินเห็น
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function previewSections($boxes, array $props)
    {
        return $boxes->map(function (AsmScoreBox $box) use ($props) {
            $isAttendance = $box->children->contains(fn (AsmScoreBox $k): bool => $k->type === 'attendance');
            $children = $box->children->isNotEmpty() ? $box->children : collect([$box]);

            return [
                'id' => $box->id,
                'name' => $box->name,
                'is_attendance' => $isAttendance,
                'mode' => $props[$box->id]['mode'] ?? null,
                'weight' => $props[$box->id]['weight'] ?? null,
                'columns' => $children->values()->map(fn (AsmScoreBox $kid, int $i): array => [
                    'id' => $kid->id,
                    'name' => $kid->name,
                    'type' => $kid->type,
                    'value' => $this->demoValue($kid, $i),
                    'is_input' => $kid->type === 'input',
                    // ชุดตัวเลือกคะแนน (เฉพาะชนิด Input) — ยังไม่ตั้งเอง = ชุดเริ่มต้น
                    'scale' => $kid->type === 'input' ? $kid->scale() : null,
                    'scale_custom' => $kid->type === 'input' ? is_array($kid->scale_json) && $kid->scale_json !== [] : false,
                ])->all(),
            ];
        })
            // แสดงเฉพาะหัวข้อที่ระดับนี้ใช้จริง — เกณฑ์เดียวกับ $displayMainBoxes ในแบบฟอร์มจริง
            ->filter(function (array $s): bool {
                if ($s['mode'] === null) {
                    return false;   // ระดับนี้ยังไม่ได้ตั้งสัดส่วนให้หัวข้อนี้
                }
                $mode = in_array($s['mode'], ['extra', 'none'], true) ? $s['mode'] : 'percent';
                if ($mode === 'none') {
                    return false;
                }

                return $mode === 'extra' || (float) ($s['weight'] ?? 0) > 0;
            })
            ->values();
    }

    /**
     * บันทึกชุดตัวเลือกคะแนนของคอลัมน์ชนิด Input (ทั้งชุดในครั้งเดียว)
     * ส่ง scale = [] → กลับไปใช้ชุดเริ่มต้น
     */
    public function saveScale(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'box_id' => ['required', 'integer'],
            'scale' => ['present', 'array'],
            'scale.*.th' => ['required', 'string', 'max:120'],
            'scale.*.en' => ['nullable', 'string', 'max:120'],
            'scale.*.my' => ['nullable', 'string', 'max:120'],
            'scale.*.na' => ['sometimes', 'boolean'],
            'scale.*.scores' => ['required', 'array', 'min:1'],
            'scale.*.scores.*' => ['required', 'string', 'max:8'],
        ]);

        $box = AsmScoreBox::whereNotNull('parent_id')->where('type', 'input')->find($data['box_id']);
        if (! $box) {
            return response()->json(['ok' => false, 'message' => 'คอลัมน์นี้ไม่ใช่ชนิด Input'], 422);
        }

        $scale = [];
        foreach ($data['scale'] as $row) {
            $th = trim($row['th']);
            $scores = array_values(array_filter(array_map(
                fn ($s) => trim((string) $s),
                $row['scores'],
            ), fn (string $s): bool => $s !== ''));
            if ($th === '' || $scores === []) {
                continue;   // ตัวเลือกที่ไม่มีชื่อหรือไม่มีคะแนน = ข้าม
            }
            $scale[] = [
                'th' => $th,
                'en' => trim((string) ($row['en'] ?? '')) ?: $th,
                'my' => trim((string) ($row['my'] ?? '')) ?: (trim((string) ($row['en'] ?? '')) ?: $th),
                'scores' => $scores,
                'na' => (bool) ($row['na'] ?? false),
            ];
        }

        $box->scale_json = $scale === [] ? null : $scale;   // ว่าง = กลับไปใช้ชุดเริ่มต้น
        $box->save();

        return response()->json(['ok' => true, 'count' => count($scale), 'using_default' => $scale === []]);
    }

    /**
     * บันทึกคำอธิบาย/สวิตช์แสดงผล ของคอลัมน์ × ลำดับผู้ประเมิน (auto-save จากหน้ากำหนดคำถาม)
     * ส่งมาเฉพาะ field ที่แก้ — field ที่ไม่ส่งจะคงค่าเดิม
     */
    public function saveNote(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'box_id' => ['nullable', 'integer'],
            'slot' => ['nullable', 'string', 'in:total'],          // จุดพิเศษที่ไม่ใช่คอลัมน์คะแนน
            'level' => ['required', 'integer', 'between:1,50'],   // ระดับตำแหน่ง (admin เพิ่มเกิน 5 ได้)
            'desc_th' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'desc_en' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'desc_my' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        $slot = $data['slot'] ?? null;
        $boxId = $slot === null ? ($data['box_id'] ?? null) : null;

        if ($slot === null) {
            if ($boxId === null || ! AsmScoreBox::find($boxId)) {
                return response()->json(['ok' => false, 'message' => 'ไม่พบคอลัมน์'], 422);
            }
        }

        $note = AsmBoxNote::firstOrNew(
            $slot === null
                ? ['box_id' => (int) $boxId, 'level' => (int) $data['level']]
                : ['slot' => $slot, 'level' => (int) $data['level']],
        );

        foreach (['desc_th', 'desc_en', 'desc_my'] as $f) {
            if (array_key_exists($f, $data)) {
                $note->$f = trim((string) ($data[$f] ?? '')) ?: null;
            }
        }
        if (array_key_exists('is_visible', $data)) {
            $note->is_visible = (bool) $data['is_visible'];
        } elseif (! $note->exists) {
            $note->is_visible = true;   // แถวใหม่ที่ยังไม่ได้แตะสวิตช์ = แสดงไว้ก่อน (ตรงกับ default ของตาราง)
        }
        $note->updated_by = app('current_user')->id;
        $note->save();

        return response()->json([
            'ok' => true,
            'note' => [
                'box_id' => $note->box_id,
                'slot' => $note->slot,
                'level' => $note->level,
                'is_visible' => (bool) $note->is_visible,
                'has_content' => $note->hasContent(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'box_id' => ['required', 'integer'],
            'level' => ['required', 'integer', 'between:1,4'],
            'q_th' => ['nullable', 'string'],
            'q_en' => ['nullable', 'string'],
            'q_my' => ['nullable', 'string'],
        ]);

        $box = AsmScoreBox::whereNotNull('parent_id')->where('type', 'input')->find($data['box_id']);
        if (! $box) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบคอลัมน์ชนิด Input'], 422);
        }

        $qTh = trim((string) ($data['q_th'] ?? ''));
        $qEn = trim((string) ($data['q_en'] ?? ''));
        $qMy = trim((string) ($data['q_my'] ?? ''));
        $fallback = $qTh ?: ($qEn ?: $qMy);
        if ($fallback === '') {
            return response()->json(['ok' => false, 'message' => 'กรุณากรอกคำถาม'], 422);
        }

        $q = AsmQuestion::create([
            'box_id' => $box->id,
            'level' => $data['level'],
            'sort' => (int) AsmQuestion::where('box_id', $box->id)->where('level', $data['level'])->max('sort') + 1,
            'q_th' => $qTh ?: $fallback,
            'q_en' => $qEn ?: null,
            'q_my' => $qMy ?: null,
        ]);

        return response()->json(['ok' => true, 'question' => $q->only(['id', 'box_id', 'level', 'q_th', 'q_en', 'q_my'])]);
    }

    public function update(Request $request, AsmQuestion $question): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'q_th' => ['sometimes', 'string'],
            'q_en' => ['sometimes', 'nullable', 'string'],
            'q_my' => ['sometimes', 'nullable', 'string'],
        ]);

        foreach (['q_th', 'q_en', 'q_my'] as $f) {
            if (array_key_exists($f, $data)) {
                $v = trim((string) ($data[$f] ?? ''));
                $question->$f = $f === 'q_th' ? ($v ?: $question->q_th) : ($v ?: null);
            }
        }
        $question->save();

        return response()->json(['ok' => true]);
    }

    public function destroy(AsmQuestion $question): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $question->delete();

        return response()->json(['ok' => true]);
    }
}
