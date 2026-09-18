<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Assessment\Concerns\HandlesAssessmentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assessment\AsmMember;
use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmSelfChoice;
use App\Models\Assessment\AsmSelfQuestion;
use App\Models\Assessment\AsmSelfSubmission;
use App\Models\Insight\Employee;
use App\Services\Assessment\SelfAssessmentRoster;
use App\Services\Assessment\SelfAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View as ViewContract;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ตั้งค่า สิทธิ์ คำถาม ตัวเลือก และแบบประเมินตัวเอง
 */
class AssessmentSelfController extends Controller
{
    use HandlesAssessmentAccess;

    public function index(Request $request, SelfAssessmentRoster $roster): ViewContract|RedirectResponse
    {
        $me = $this->me();
        $round = AsmRound::open(AsmRound::TYPE_SELF);
        $canManage = $this->canManageSelf();

        if (! $round) {
            if ($canManage) {
                return redirect()->route('assessment.rounds.index', ['type' => AsmRound::TYPE_SELF])
                    ->with('round_warn', 'ต้องเปิดรอบ "ประเมินตัวเอง" ก่อนจึงจะเข้าได้');
            }

            return $this->denyToSystems('toast.pageDenied', 'ยังไม่มีรอบประเมินตัวเองที่เปิดอยู่');
        }

        if (! $canManage) {
            return $this->renderForm($roster, $round);
        }

        $tab = in_array($request->query('tab'), ['manage', 'questions', 'results'], true)
            ? (string) $request->query('tab')
            : 'manage';
        $rows = $tab === 'questions' ? collect() : $roster->rows($round);
        $selectedCount = $roster->selectedCount($round);
        $levelMap = AsmPositionLevel::map($round->id);
        $positions = collect($this->positionOptions())
            ->map(fn (array $position): array => $position + [
                'level' => array_key_exists($position['code'], $levelMap)
                    ? (int) $levelMap[$position['code']]
                    : null,
            ]);

        return view('assessment.self', [
            'me' => $me,
            'selfRound' => $round,
            'tab' => $tab,
            'rows' => $rows,
            'resultRows' => $tab === 'results'
                ? $rows->where('is_selected', true)->values()
                : collect(),
            'positionsByLevel' => $positions->groupBy(
                fn (array $position): string => $position['level'] === null
                    ? 'unassigned'
                    : (string) $position['level'],
            ),
            'selectedCount' => $selectedCount,
            'levelOptions' => $this->selfLevelOptions($round->id),
            'questionsByLevel' => $tab === 'questions'
                ? AsmSelfQuestion::query()
                    ->with('choices')
                    ->where('round_id', $round->id)
                    ->orderBy('level')
                    ->orderBy('sort')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('level')
                : collect(),
            'resultQuestionCount' => $tab === 'results'
                ? $roster->resultQuestionCount($round)
                : 0,
            // รอบประเมินตัวเองทั้งหมด (รวมรอบที่ปิดแล้ว) — ให้ dropdown ของปุ่ม Export เลือกดาวน์โหลดย้อนหลังได้
            'selfRounds' => $tab === 'results'
                ? AsmRound::where('type', AsmRound::TYPE_SELF)
                    ->orderByDesc('year')->orderByDesc('id')->get()
                : collect(),
        ]);
    }

    public function form(SelfAssessmentRoster $roster): ViewContract|RedirectResponse
    {
        $round = AsmRound::open(AsmRound::TYPE_SELF);
        if (! $round) {
            return $this->denyToSystems('toast.pageDenied', 'ยังไม่มีรอบประเมินตัวเองที่เปิดอยู่');
        }

        return $this->renderForm($roster, $round);
    }

    public function saveParticipants(Request $request, SelfAssessmentRoster $roster): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'ไม่มีสิทธิ์บันทึกข้อมูล'], 403);
        }

        $data = $request->validate([
            'changes' => ['required', 'array', 'min:1', 'max:2000'],
            'changes.*.employee_code' => ['required', 'string', 'max:50'],
            'changes.*.is_selected' => ['required', 'boolean'],
        ]);
        $round = AsmRound::open(AsmRound::TYPE_SELF);
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบประเมินตัวเองก่อน'], 422);
        }

        $selectedCount = $roster->saveSelectionChanges(
            $round,
            $data['changes'],
            (int) $this->me()->id,
        );

        return response()->json([
            'ok' => true,
            'selected_count' => $selectedCount,
            'message' => 'บันทึกรายชื่อผู้ประเมินตัวเองแล้ว',
        ]);
    }

    public function setLevel(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'ไม่มีสิทธิ์บันทึกข้อมูล'], 403);
        }

        $data = $request->validate([
            'job_code' => ['required', 'string', 'max:100'],
            'level' => ['nullable', 'integer', 'between:1,99'],
        ]);
        $round = AsmRound::open(AsmRound::TYPE_SELF);
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบประเมินตัวเองก่อน'], 422);
        }

        $level = $data['level'] ?? null;
        if ($level === null) {
            AsmPositionLevel::query()
                ->where('round_id', $round->id)
                ->where('job_code', $data['job_code'])
                ->delete();
        } else {
            AsmPositionLevel::updateOrCreate(
                ['round_id' => $round->id, 'job_code' => $data['job_code']],
                ['level' => (int) $level],
            );
        }

        return response()->json(['ok' => true, 'level' => $level]);
    }

    /**
     * ระดับที่มีอยู่จริงของรอบประเมินตัวเอง — เหมือนการ์ด 1.1 ของหน้าประเมินพนักงาน
     * นับจากตำแหน่งที่จัดไว้ + ระดับที่มีคำถามแล้ว · อย่างน้อยแสดง 1..5
     *
     * @return array<int,int>
     */
    private function selfLevelOptions(int $roundId): array
    {
        $posMax = (int) (AsmPositionLevel::where('round_id', $roundId)->max('level') ?? 0);
        $qMax = (int) (AsmSelfQuestion::where('round_id', $roundId)->max('level') ?? 0);

        return range(1, max(5, $posMax, $qMax));
    }

    /**
     * ลบระดับ (ปุ่ม ✕ บนการ์ด 1.1) — ระดับที่สูงกว่าเลื่อนลงมาแทนให้เลขเรียงต่อเนื่อง
     * ตำแหน่งในระดับที่ลบจะกลับไปเป็น "ยังไม่กำหนดระดับ" · คำถามของระดับนั้นถูกลบด้วย
     */
    public function deleteLevel(Request $request): JsonResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();

        $data = $request->validate([
            'level' => ['required', 'integer', 'between:1,99'],
        ]);
        $level = (int) $data['level'];

        DB::connection('mysql_assessment')->transaction(function () use ($round, $level) {
            // คำถาม + ตัวเลือกของระดับที่ลบ (ข้ามข้อที่มีคนตอบแล้ว)
            $questionIds = AsmSelfQuestion::where('round_id', $round->id)->where('level', $level)->pluck('id');
            foreach ($questionIds as $qid) {
                $answered = DB::connection('mysql_assessment')->table('asm_self_answers')->where('question_id', $qid)->exists();
                if ($answered) {
                    continue;
                }
                AsmSelfChoice::where('question_id', $qid)->delete();
                AsmSelfQuestion::where('id', $qid)->delete();
            }

            // ตำแหน่งในระดับนี้ → กลับไปยังไม่กำหนดระดับ
            AsmPositionLevel::where('round_id', $round->id)->where('level', $level)->delete();

            // ระดับที่สูงกว่าเลื่อนลง 1 ขั้น (เรียงจากน้อยไปมาก กันชน unique)
            AsmPositionLevel::where('round_id', $round->id)->where('level', '>', $level)
                ->orderBy('level')->decrement('level');
            AsmSelfQuestion::where('round_id', $round->id)->where('level', '>', $level)
                ->orderBy('level')->decrement('level');
        });

        return response()->json(['ok' => true, 'levels' => $this->selfLevelOptions((int) $round->id)]);
    }

    /**
     * บันทึกชุดคำถาม + ตัวเลือกของระดับหนึ่งทีเดียว (หน้า admin แบบใหม่)
     *
     * ส่งมาทั้งชุดเสมอ — ข้อ/ตัวเลือกที่หายไปจากชุด = ถูกลบ (กันไว้ไม่ให้ลบข้อที่มีคนตอบแล้ว)
     */
    public function saveQuestionSet(Request $request): JsonResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();

        $data = $request->validate([
            'level' => ['required', 'integer', 'between:1,50'],
            'questions' => ['present', 'array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.q_th' => ['required', 'string', 'max:2000'],
            'questions.*.q_en' => ['nullable', 'string', 'max:2000'],
            'questions.*.q_my' => ['nullable', 'string', 'max:2000'],
            'questions.*.full_score' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'questions.*.choices' => ['present', 'array', 'min:1'],
            'questions.*.choices.*.id' => ['nullable', 'integer'],
            'questions.*.choices.*.choice_th' => ['required', 'string', 'max:255'],
            'questions.*.choices.*.choice_en' => ['nullable', 'string', 'max:255'],
            'questions.*.choices.*.choice_my' => ['nullable', 'string', 'max:255'],
            'questions.*.choices.*.score' => ['nullable', 'numeric', 'max:999999.99'],
            'questions.*.choices.*.is_na' => ['sometimes', 'boolean'],
        ]);

        $level = (int) $data['level'];

        // ตรวจก่อนบันทึก: คะแนนตัวเลือกต้องไม่เกินคะแนนเต็มของข้อนั้น
        foreach ($data['questions'] as $i => $q) {
            $max = 0.0;
            foreach ($q['choices'] as $c) {
                if (! ($c['is_na'] ?? false)) {
                    $max = max($max, (float) ($c['score'] ?? 0));
                }
            }
            if ($max > (float) $q['full_score']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ข้อที่ '.($i + 1).': คะแนนตัวเลือกสูงสุด ('.$max.') เกินคะแนนเต็มของข้อ ('.$q['full_score'].')',
                ], 422);
            }
        }

        $keptQuestionIds = [];

        DB::connection('mysql_assessment')->transaction(function () use ($data, $round, $level, &$keptQuestionIds) {
            foreach (array_values($data['questions']) as $index => $q) {
                $thText = trim($q['q_th']);
                $question = null;
                if (! empty($q['id'])) {
                    $question = AsmSelfQuestion::where('round_id', $round->id)->where('level', $level)->find($q['id']);
                }
                $question ??= new AsmSelfQuestion(['round_id' => $round->id, 'level' => $level]);

                $question->round_id = $round->id;
                $question->level = $level;
                $question->sort = $index + 1;
                $question->q_th = $thText;
                $question->q_en = trim((string) ($q['q_en'] ?? '')) ?: $thText;
                $question->q_my = trim((string) ($q['q_my'] ?? '')) ?: ($question->q_en ?: $thText);
                $question->full_score = (float) $q['full_score'];
                $question->save();
                $keptQuestionIds[] = $question->id;

                // ตัวเลือกของข้อนี้
                $keptChoiceIds = [];
                foreach (array_values($q['choices']) as $ci => $c) {
                    $cTh = trim($c['choice_th']);
                    $choice = null;
                    if (! empty($c['id'])) {
                        $choice = AsmSelfChoice::where('question_id', $question->id)->find($c['id']);
                    }
                    $choice ??= new AsmSelfChoice(['question_id' => $question->id]);

                    $choice->question_id = $question->id;
                    $choice->sort = $ci + 1;
                    $choice->choice_th = $cTh;
                    $choice->choice_en = trim((string) ($c['choice_en'] ?? '')) ?: $cTh;
                    $choice->choice_my = trim((string) ($c['choice_my'] ?? '')) ?: ($choice->choice_en ?: $cTh);
                    $choice->is_na = (bool) ($c['is_na'] ?? false);
                    $choice->score = $choice->is_na ? null : (float) ($c['score'] ?? 0);
                    $choice->save();
                    $keptChoiceIds[] = $choice->id;
                }

                AsmSelfChoice::where('question_id', $question->id)->whereNotIn('id', $keptChoiceIds ?: [0])->delete();
            }

            // ข้อที่ถูกเอาออกจากชุด — ลบเฉพาะข้อที่ยังไม่มีใครตอบ
            $removable = AsmSelfQuestion::where('round_id', $round->id)->where('level', $level)
                ->whereNotIn('id', $keptQuestionIds ?: [0])
                ->pluck('id');
            foreach ($removable as $qid) {
                $answered = DB::connection('mysql_assessment')->table('asm_self_answers')->where('question_id', $qid)->exists();
                if ($answered) {
                    continue;
                }
                AsmSelfChoice::where('question_id', $qid)->delete();
                AsmSelfQuestion::where('id', $qid)->delete();
            }
        });

        return response()->json(['ok' => true, 'count' => count($keptQuestionIds)]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();
        $data = $this->validateQuestion($request);
        $sort = ((int) AsmSelfQuestion::query()
            ->where('round_id', $round->id)
            ->where('level', $data['level'])
            ->max('sort')) + 1;

        AsmSelfQuestion::create([
            'round_id' => $round->id,
            'level' => $data['level'],
            'sort' => $sort,
            'q_th' => $data['q_th'],
            'q_en' => $data['q_en'],
            'q_my' => $data['q_my'],
            'full_score' => $data['full_score'],
        ]);

        return $this->questionRedirect((int) $data['level'], 'บันทึกคำถามแล้ว กรุณาเพิ่มตัวเลือกคำตอบ');
    }

    public function updateQuestion(Request $request, AsmSelfQuestion $question): RedirectResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();
        $this->ensureQuestionInRound($question, $round);
        $data = $request->validate([
            'q_th' => ['required', 'string', 'max:2000'],
            'q_en' => ['required', 'string', 'max:2000'],
            'q_my' => ['required', 'string', 'max:2000'],
            'full_score' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ]);
        $maximumChoiceScore = (float) ($question->choices()
            ->where('is_na', false)
            ->max('score') ?? 0);
        if ($maximumChoiceScore > (float) $data['full_score']) {
            throw ValidationException::withMessages([
                'full_score' => 'คะแนนเต็มต้องไม่น้อยกว่าคะแนนตัวเลือกสูงสุด '.(float) $maximumChoiceScore,
            ]);
        }
        $data['full_score'] = (float) $data['full_score'];
        $question->update($data);

        return $this->questionRedirect($question->level, 'แก้ไขคำถามแล้ว');
    }

    public function deleteQuestion(AsmSelfQuestion $question): RedirectResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();
        $this->ensureQuestionInRound($question, $round);
        if ($question->answers()->exists()) {
            return $this->questionRedirect($question->level, null, 'ลบคำถามไม่ได้ เพราะมีพนักงานตอบคำถามข้อนี้แล้ว');
        }

        DB::connection('mysql_assessment')->transaction(function () use ($question): void {
            $question->choices()->delete();
            $question->delete();
        });

        return $this->questionRedirect($question->level, 'ลบคำถามแล้ว');
    }

    public function storeChoice(Request $request, AsmSelfQuestion $question): RedirectResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();
        $this->ensureQuestionInRound($question, $round);
        $data = $this->validateChoice($request, $question);
        if ($data['is_na'] && $question->choices()->where('is_na', true)->exists()) {
            return $this->questionRedirect($question->level, null, 'คำถามหนึ่งข้อมีตัวเลือก N/A ได้เพียง 1 รายการ');
        }

        $data['sort'] = ((int) $question->choices()->max('sort')) + 1;
        $question->choices()->create($data);

        return $this->questionRedirect($question->level, 'เพิ่มตัวเลือกแล้ว');
    }

    public function updateChoice(Request $request, AsmSelfChoice $choice): RedirectResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();
        $question = $choice->question()->firstOrFail();
        $this->ensureQuestionInRound($question, $round);
        $data = $this->validateChoice($request, $question);
        if ($data['is_na'] && $question->choices()
            ->where('is_na', true)
            ->whereKeyNot($choice->id)
            ->exists()) {
            return $this->questionRedirect($question->level, null, 'คำถามหนึ่งข้อมีตัวเลือก N/A ได้เพียง 1 รายการ');
        }

        $choice->update($data);

        return $this->questionRedirect($question->level, 'แก้ไขตัวเลือกแล้ว');
    }

    public function deleteChoice(AsmSelfChoice $choice): RedirectResponse
    {
        $this->ensureCanManage();
        $round = $this->openSelfRoundOrFail();
        $question = $choice->question()->firstOrFail();
        $this->ensureQuestionInRound($question, $round);
        if ($choice->answers()->exists()) {
            return $this->questionRedirect($question->level, null, 'ลบตัวเลือกไม่ได้ เพราะมีพนักงานเลือกตัวเลือกนี้แล้ว');
        }

        $choice->delete();

        return $this->questionRedirect($question->level, 'ลบตัวเลือกแล้ว');
    }

    public function saveForm(
        Request $request,
        SelfAssessmentRoster $roster,
        SelfAssessmentService $service,
    ): RedirectResponse {
        $round = AsmRound::open(AsmRound::TYPE_SELF);
        if (! $round) {
            return $this->denyToSystems('toast.pageDenied', 'ยังไม่มีรอบประเมินตัวเองที่เปิดอยู่');
        }

        $participant = $roster->participantForUser($this->me(), $round);
        if (! $participant) {
            return $this->denyToSystems('toast.pageDenied', 'คุณไม่ได้อยู่ในรายชื่อประเมินตัวเองของรอบนี้');
        }

        $employee = Employee::active()->where('employee_code', $participant->employee_code)->firstOrFail();
        $level = AsmPositionLevel::map($round->id)[(string) $employee->job_code] ?? null;
        if ($level === null) {
            return back()->withErrors(['answers' => 'Admin ยังไม่ได้กำหนดระดับตำแหน่งของคุณ']);
        }

        $questions = AsmSelfQuestion::query()
            ->with('choices')
            ->whereHas('choices')
            ->where('round_id', $round->id)
            ->where('level', $level)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        $data = $request->validate([
            'answers' => ['required', 'array', 'max:200'],
            'answers.*' => ['required', 'integer'],
        ]);

        $service->submit(
            $round,
            (string) $participant->employee_code,
            (int) $level,
            $questions,
            $data['answers'],
        );

        return redirect()->route('assessment.self.form')
            ->with('self_success', 'บันทึกการประเมินตัวเองเรียบร้อยแล้ว');
    }

    /**
     * แท็บผลลัพธ์ (self) — Export ไฟล์ผลลัพธ์การประเมินตัวเองของรอบที่เปิดอยู่
     * ชีต 1 = ผลลัพธ์รายคน (ตรงกับตารางบนหน้าจอ) · ชีต 2 = คำถามของแต่ละระดับ ให้อ่านคอลัมน์ "ข้อที่ N" ออก
     */
    public function resultsDownload(Request $request, SelfAssessmentRoster $roster): StreamedResponse|RedirectResponse
    {
        if (! $this->canManageSelf()) {
            return $this->denyToSystems('toast.pageDenied', 'ไม่มีสิทธิ์จัดการการประเมินตัวเอง');
        }

        // เลือกรอบได้จาก dropdown — รวมรอบที่ปิดแล้ว (ข้อมูลทุกตารางผูกกับ round_id จึงย้อนหลังได้)
        $roundId = (int) $request->query('round');
        $round = $roundId
            ? AsmRound::where('type', AsmRound::TYPE_SELF)->find($roundId)
            : AsmRound::open(AsmRound::TYPE_SELF);
        if (! $round) {
            return redirect()->route('assessment.self.index', ['tab' => 'results'])
                ->with('self_error', $roundId ? 'ไม่พบรอบประเมินตัวเองที่เลือก' : 'ต้องเปิดรอบ "ประเมินตัวเอง" ก่อนจึงจะ Export ได้');
        }

        $rows = $roster->rows($round)->where('is_selected', true)->values();
        $questionCount = $roster->resultQuestionCount($round);
        $questions = AsmSelfQuestion::query()
            ->where('round_id', $round->id)
            ->orderBy('level')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Self Results');

        // ----- หัวตาราง -----
        $headers = ['รหัสพนักงาน', 'ชื่อ-สกุล', 'ชื่อ (EN)', 'ตำแหน่ง', 'แผนก', 'ระดับ', 'สถานะ', 'วันที่ส่ง', 'คะแนนประเมินตัวเอง (%)'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        $firstQuestionCol = count($headers) + 1;
        for ($questionNo = 1; $questionNo <= $questionCount; $questionNo++) {
            $sheet->setCellValueExplicit([$firstQuestionCol + $questionNo - 1, 1], 'ข้อที่ '.$questionNo.' (%)', DataType::TYPE_STRING);
        }
        $lastCol = max(count($headers), $firstQuestionCol + $questionCount - 1);

        // ----- ข้อมูลรายคน — ค่าในเซลล์ตรงกับตารางบนหน้าจอ -----
        $rowNo = 2;
        foreach ($rows as $row) {
            $answers = $row['question_scores'] ?? [];
            $levelQuestionCount = (int) ($row['level_question_count'] ?? 0);

            $sheet->setCellValueExplicit([1, $rowNo], (string) $row['employee_code'], DataType::TYPE_STRING);
            $sheet->setCellValue([2, $rowNo], (string) $row['name']);
            $sheet->setCellValue([3, $rowNo], (string) $row['name_en']);
            $sheet->setCellValue([4, $rowNo], (string) $row['position']);
            $sheet->setCellValue([5, $rowNo], (string) $row['department']);
            if ($row['level'] !== null) {
                $sheet->setCellValue([6, $rowNo], (int) $row['level']);
            }
            $sheet->setCellValueExplicit([7, $rowNo], $row['submitted_at'] ? 'ส่งแล้ว' : 'ยังไม่ส่ง', DataType::TYPE_STRING);
            if ($row['submitted_at']) {
                $sheet->setCellValueExplicit([8, $rowNo], $row['submitted_at']->format('Y-m-d H:i'), DataType::TYPE_STRING);
            }
            if ($row['self_score'] !== null) {
                $sheet->setCellValue([9, $rowNo], round((float) $row['self_score'], 2));
            } else {
                $sheet->setCellValueExplicit([9, $rowNo], '-', DataType::TYPE_STRING);
            }

            for ($questionNo = 1; $questionNo <= $questionCount; $questionNo++) {
                $col = $firstQuestionCol + $questionNo - 1;
                // เกินจำนวนข้อของระดับนี้ = คำถามของระดับอื่น → เว้นว่างเหมือนบนหน้าจอ
                if ($questionNo > $levelQuestionCount && ! array_key_exists($questionNo, $answers)) {
                    continue;
                }
                $value = $answers[$questionNo] ?? null;
                if ($value === 'N/A') {
                    $sheet->setCellValueExplicit([$col, $rowNo], 'N/A', DataType::TYPE_STRING);
                } elseif ($value === null) {
                    $sheet->setCellValueExplicit([$col, $rowNo], '-', DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$col, $rowNo], round((float) $value, 2));
                }
            }
            $rowNo++;
        }

        $this->styleSelfExportHeader($sheet, $lastCol);

        // ----- ชีต 2: คำถามของแต่ละระดับ (อ่านคู่กับคอลัมน์ "ข้อที่ N") -----
        $questionSheet = $ss->createSheet();
        $questionSheet->setTitle('Questions');
        foreach (['ระดับ', 'ข้อที่', 'คำถาม (TH)', 'คำถาม (EN)', 'คะแนนเต็ม'] as $index => $header) {
            $questionSheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        $questionRow = 2;
        $sequence = [];
        foreach ($questions as $question) {
            $level = (int) $question->level;
            $sequence[$level] = ($sequence[$level] ?? 0) + 1;
            $questionSheet->setCellValue([1, $questionRow], $level);
            $questionSheet->setCellValue([2, $questionRow], $sequence[$level]);
            $questionSheet->setCellValue([3, $questionRow], (string) $question->q_th);
            $questionSheet->setCellValue([4, $questionRow], (string) $question->q_en);
            $questionSheet->setCellValue([5, $questionRow], (float) $question->full_score);
            $questionRow++;
        }
        $this->styleSelfExportHeader($questionSheet, 5);
        $ss->setActiveSheetIndex(0);

        $filename = 'assessment_self_results_'.$round->year.'_รอบ'.$round->id.'_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($ss) {
            (new Xlsx($ss))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** หัวตาราง Export ของ self: ตัวหนา + กึ่งกลาง + autosize + ตรึงแถวหัว */
    private function styleSelfExportHeader(Worksheet $sheet, int $lastCol): void
    {
        $lastCol = max(1, $lastCol);
        $style = $sheet->getStyle([1, 1, $lastCol, 1]);
        $style->getFont()->setBold(true);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (range(1, $lastCol) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }


    private function canManageSelf(): bool
    {
        $me = $this->me();

        return $me->isAdmin() || (AsmMember::isHr($me->id) && $this->positionAllowed());
    }

    private function renderForm(
        SelfAssessmentRoster $roster,
        AsmRound $round,
    ): ViewContract|RedirectResponse {
        $me = $this->me();
        $participant = $roster->participantForUser($me, $round);
        if (! $participant) {
            return $this->denyToSystems('toast.pageDenied', 'คุณไม่ได้อยู่ในรายชื่อประเมินตัวเองของรอบนี้');
        }

        $employee = Employee::active()
            ->where('employee_code', $participant->employee_code)
            ->first();
        $levelMap = AsmPositionLevel::map($round->id);
        $selfLevel = $employee && array_key_exists((string) $employee->job_code, $levelMap)
            ? (int) $levelMap[(string) $employee->job_code]
            : null;
        $questions = $selfLevel === null
            ? collect()
            : AsmSelfQuestion::query()
                ->with('choices')
                ->whereHas('choices')
                ->where('round_id', $round->id)
                ->where('level', $selfLevel)
                ->orderBy('sort')
                ->orderBy('id')
                ->get();
        $submission = AsmSelfSubmission::query()
            ->with('answers')
            ->where('round_id', $round->id)
            ->where('employee_code', $participant->employee_code)
            ->first();
        $profile = trim((string) ($me->profile_picture ?? ''));

        return view('assessment.self-user', [
            'me' => $me,
            'selfRound' => $round,
            'employee' => $employee,
            'selfLevel' => $selfLevel,
            'questions' => $questions,
            'submission' => $submission,
            'answerMap' => $submission?->answers->pluck('choice_id', 'question_id') ?? collect(),
            'avatarUrl' => $profile === ''
                ? null
                : (preg_match('/^https?:\/\//i', $profile) ? $profile : asset('storage/'.ltrim($profile, '/'))),
        ]);
    }

    private function ensureCanManage(): void
    {
        abort_unless($this->canManageSelf(), 403, 'ไม่มีสิทธิ์จัดการการประเมินตัวเอง');
    }

    private function openSelfRoundOrFail(): AsmRound
    {
        $round = AsmRound::open(AsmRound::TYPE_SELF);
        abort_unless($round, 422, 'ต้องเปิดรอบประเมินตัวเองก่อน');

        return $round;
    }

    private function ensureQuestionInRound(AsmSelfQuestion $question, AsmRound $round): void
    {
        abort_unless((int) $question->round_id === (int) $round->id, 404);
    }

    /** @return array{level:int,q_th:string,q_en:string,q_my:string,full_score:float} */
    private function validateQuestion(Request $request): array
    {
        $data = $request->validate([
            'level' => ['required', 'integer', 'between:1,99'],
            'q_th' => ['required', 'string', 'max:2000'],
            'q_en' => ['required', 'string', 'max:2000'],
            'q_my' => ['required', 'string', 'max:2000'],
            'full_score' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ]);
        $data['full_score'] = (float) $data['full_score'];

        return $data;
    }

    /** @return array{choice_th:string,choice_en:string,choice_my:string,score:?float,is_na:bool} */
    private function validateChoice(Request $request, AsmSelfQuestion $question): array
    {
        $data = $request->validate([
            'choice_type' => ['required', 'in:score,na'],
            'choice_th' => ['required', 'string', 'max:255'],
            'choice_en' => ['required', 'string', 'max:255'],
            'choice_my' => ['required', 'string', 'max:255'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);
        $data['is_na'] = $data['choice_type'] === 'na';
        unset($data['choice_type']);
        if (! $data['is_na'] && ($data['score'] ?? null) === null) {
            throw ValidationException::withMessages([
                'score' => 'กรุณากรอกคะแนนของตัวเลือก หรือเลือกชนิด N/A',
            ]);
        }
        $data['score'] = $data['is_na'] ? null : (float) $data['score'];
        if (! $data['is_na'] && $data['score'] > (float) $question->full_score) {
            throw ValidationException::withMessages([
                'score' => 'คะแนนตัวเลือกต้องไม่เกินคะแนนเต็มของคำถาม '.(float) $question->full_score,
            ]);
        }

        return $data;
    }

    private function questionRedirect(
        int $level,
        ?string $notice = null,
        ?string $error = null,
    ): RedirectResponse {
        $redirect = redirect()->route('assessment.self.index', [
            'tab' => 'questions',
            'level' => $level,
        ])->withFragment('self-level-'.$level);

        if ($notice !== null) {
            $redirect->with('self_notice', $notice);
        }
        if ($error !== null) {
            $redirect->with('self_error', $error);
        }

        return $redirect;
    }

    /** @return array<int,array{code:string,name:string,count:int}> */
    private function positionOptions(): array
    {
        return Employee::active()
            ->selectRaw('job_code as code, MAX(job_th) as th, MAX(job_en) as en, COUNT(*) as cnt')
            ->whereNotNull('job_code')
            ->where('job_code', '!=', '')
            ->groupBy('job_code')
            ->orderBy('th')
            ->get()
            ->map(fn ($position): array => [
                'code' => (string) $position->code,
                'name' => (string) ($position->th ?: $position->en ?: $position->code),
                'count' => (int) $position->cnt,
            ])
            ->values()
            ->all();
    }
}
