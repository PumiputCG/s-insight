<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Assessment\Concerns\HandlesAssessmentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assessment\AsmEmployeeScore;
use App\Models\Assessment\AsmHierarchy;
use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmSelfAnswer;
use App\Models\Assessment\AsmSelfChoice;
use App\Models\Assessment\AsmSelfParticipant;
use App\Models\Assessment\AsmSelfQuestion;
use App\Models\Assessment\AsmSelfSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View as ViewContract;

/**
 * เปิดรอบประเมิน (admin + HR) — แยก 2 ชนิด: ประเมินพนักงาน / ประเมินตัวเอง
 *
 * ต้องมีรอบชนิดนั้นเปิดก่อนถึงจะจัดการส่วนนั้นได้
 * เปิด/สลับรอบใหม่ = ปิดรอบเดิมของชนิดนั้น + ข้อมูลของชนิดนั้นเป็นชุดของรอบที่เปิด (รอบอื่นเก็บไว้)
 */
class AssessmentRoundController extends Controller
{
    use HandlesAssessmentAccess;

    /** ชนิดที่กำลังดูจาก ?type= (default employee) */
    private function tab(Request $request): string
    {
        return $request->query('type') === AsmRound::TYPE_SELF ? AsmRound::TYPE_SELF : AsmRound::TYPE_EMPLOYEE;
    }

    public function index(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $type = $this->tab($request);

        return view('assessment.rounds', [
            'me' => app('current_user'),
            'tab' => $type,
            'rounds' => AsmRound::where('type', $type)->orderByDesc('year')->orderByDesc('id')->get(),
            'openRound' => AsmRound::open($type),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $data = $request->validate([
            'type' => ['required', 'in:employee,self'],
            'name' => ['required', 'string', 'max:191'],
            'year' => ['required', 'integer', 'between:2000,2200'],
        ]);

        // เปิดได้ทีละรอบต่อชนิด — ปิดรอบเดิมของชนิดนั้นก่อน
        AsmRound::where('type', $data['type'])->where('status', 'open')
            ->update(['status' => 'closed', 'closed_at' => now()]);

        $me = app('current_user');
        AsmRound::create([
            'type' => $data['type'],
            'name' => trim($data['name']),
            'year' => (int) $data['year'],
            'status' => 'open',
            'opened_by' => $me->id,
            'opened_by_name' => $me->full_name_th ?: $me->employee_code,
            'opened_at' => now(),
        ]);

        return redirect()->route('assessment.rounds.index', ['type' => $data['type']])
            ->with('round_ok', 'เปิดรอบใหม่แล้ว — ข้อมูลของชนิดนี้เริ่มชุดใหม่ของรอบนี้ (รอบเดิมเก็บไว้ switch กลับไปได้)');
    }

    public function close(AsmRound $round): RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        if ($round->isOpen()) {
            $round->update(['status' => 'closed', 'closed_at' => now()]);
        }

        return redirect()->route('assessment.rounds.index', ['type' => $round->type])
            ->with('round_ok', "ปิดรอบ \"{$round->name}\" แล้ว");
    }

    /** เปิด/สลับกลับมารอบที่ปิดไว้ — ปิดรอบเปิดปัจจุบันของชนิดเดียวกัน แล้วสลับมารอบนี้ (ข้อมูลตามรอบ) */
    public function reopen(AsmRound $round): RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        AsmRound::where('type', $round->type)->where('status', 'open')->where('id', '!=', $round->id)
            ->update(['status' => 'closed', 'closed_at' => now()]);

        $round->update(['status' => 'open', 'closed_at' => null]);

        return redirect()->route('assessment.rounds.index', ['type' => $round->type])
            ->with('round_ok', "สลับมาที่รอบ \"{$round->name}\" (ปี {$round->year}) แล้ว — ข้อมูลของรอบนี้พร้อมแก้ไข");
    }

    /** ลบรอบ + ข้อมูลของรอบนั้นทั้งหมด (คะแนน/ผู้ประเมิน/ระดับ) — ยืนยันผ่าน modal ที่ frontend */
    public function destroy(AsmRound $round): RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $type = $round->type;
        $name = $round->name;

        DB::connection('mysql_assessment')->transaction(function () use ($round) {
            AsmEmployeeScore::where('round_id', $round->id)->delete();
            AsmHierarchy::where('round_id', $round->id)->delete();
            AsmPositionLevel::where('round_id', $round->id)->delete();
            AsmSelfParticipant::where('round_id', $round->id)->delete();
            $submissionIds = AsmSelfSubmission::where('round_id', $round->id)->pluck('id');
            AsmSelfAnswer::whereIn('submission_id', $submissionIds)->delete();
            AsmSelfSubmission::where('round_id', $round->id)->delete();
            $questionIds = AsmSelfQuestion::where('round_id', $round->id)->pluck('id');
            AsmSelfChoice::whereIn('question_id', $questionIds)->delete();
            AsmSelfQuestion::where('round_id', $round->id)->delete();
            $round->delete();
        });

        return redirect()->route('assessment.rounds.index', ['type' => $type])
            ->with('round_ok', "ลบรอบ \"{$name}\" และข้อมูลของรอบนี้แล้ว");
    }
}
