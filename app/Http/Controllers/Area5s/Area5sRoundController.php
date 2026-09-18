<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Services\Area5s\A5sRoundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View as ViewContract;

/**
 * รอบรายเดือน 5ส (admin) — 1 เดือนตั้งได้หลายครั้งตรวจ (seq + วันที่)
 * เปิดได้ทีละครั้งตรวจ: เปิดครั้งใหม่ระบบปิดครั้งเดิมให้ · ครั้งที่ยังไม่มีข้อมูลลบได้
 */
class Area5sRoundController extends Controller
{
    use HandlesArea5sAccess;

    public function index(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $currentYearBe = A5sRoundService::currentYearBe();
        $yearBe = (int) ($request->query('year') ?: $currentYearBe);
        $yearBe = max($currentYearBe - 10, min($currentYearBe + 2, $yearBe));

        // แต่ละเดือนมีหลายครั้งตรวจ (seq) — group ตามเดือน
        $roundsFlat = A5sRound::where('year', $yearBe)->orderBy('month')->orderBy('seq')->get();
        $rounds = $roundsFlat->groupBy('month');
        $openRound = A5sRound::open();
        $stats = A5sTask::whereIn('round_id', $roundsFlat->pluck('id'))
            ->selectRaw("round_id, COUNT(*) AS total,
                SUM(status = 'passed') AS passed,
                SUM(status = 'failed') AS failed,
                SUM(status IN ('submitted', 'resubmitted')) AS waiting")
            ->groupBy('round_id')
            ->get()
            ->keyBy('round_id');

        // ครั้งตรวจที่ยังไม่มีข้อมูล = ลบได้
        $deletableRoundIds = $roundsFlat
            ->reject(fn (A5sRound $r) => A5sRoundService::roundHasData($r))
            ->pluck('id')
            ->all();

        return view('area5s.rounds', [
            'me' => $this->me(),
            'yearBe' => $yearBe,
            'currentYearBe' => $currentYearBe,
            'currentMonth' => (int) now()->month,
            'rounds' => $rounds,
            'openRound' => $openRound,
            'stats' => $stats,
            'deletableRoundIds' => $deletableRoundIds,
            'monthNames' => A5sRoundService::MONTHS_TH,
        ]);
    }

    /** ตั้งค่าครั้งตรวจของเดือน — รับวันที่หลายรายการ สร้าง/แก้รอบย่อย (planned) */
    public function setup(Request $request): RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'dates' => ['required', 'array', 'min:1', 'max:31'],
            'dates.*' => ['nullable', 'date'],
        ]);

        A5sRoundService::planInspections((int) $data['year'], (int) $data['month'], $data['dates'], (int) $this->me()->id);

        return redirect()
            ->route('area5s.rounds.index', ['year' => $data['year']])
            ->with('success', 'ตั้งค่าครั้งตรวจของ '.A5sRoundService::monthLabel((int) $data['month']).' '.$data['year'].' แล้ว ('.count($data['dates']).' ครั้ง)');
    }

    /** เปิดรอบย่อยที่เจาะจง (planned/closed → open) */
    public function openRound(A5sRound $round): RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        A5sRoundService::openRound($round, (int) $this->me()->id);

        return redirect()
            ->route('area5s.rounds.index', ['year' => $round->year])
            ->with('success', 'เปิด '.A5sRoundService::roundLabel($round).' แล้ว — พร้อมบันทึก 5ส');
    }

    /** ลบครั้งตรวจ (เฉพาะที่ยังไม่มีข้อมูล) */
    public function destroy(A5sRound $round): RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $label = A5sRoundService::roundLabel($round);
        $ok = A5sRoundService::deleteRound($round, (int) $this->me()->id);

        return redirect()
            ->route('area5s.rounds.index', ['year' => $round->year])
            ->with($ok ? 'success' : 'error', $ok ? ($label.' ลบแล้ว') : 'ลบไม่ได้ — ครั้งตรวจนี้มีข้อมูลแล้ว');
    }

    public function close(A5sRound $round): RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        A5sRoundService::closeRound($round, (int) $this->me()->id);

        return redirect()
            ->route('area5s.rounds.index', ['year' => $round->year])
            ->with('success', 'ปิด '.A5sRoundService::roundLabel($round).' แล้ว');
    }
}
