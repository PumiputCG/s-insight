<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sCard;
use App\Models\Area5s\A5sEvaluatorScope;
use App\Models\Area5s\A5sNotification;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Area5s\A5sTaskAttempt;
use App\Models\Insight\AppUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View as ViewContract;

/**
 * ตรวจประเมิน 5S — เห็นเฉพาะงานในขอบเขตตัวเอง (scope รายจุด หรือทั้ง layout) · admin เห็นทุกงาน
 * ผ่าน = ล็อกงานถาวร · ไม่ผ่าน = ผู้รับผิดชอบแก้แล้วส่งใหม่ได้จนรอบปิด (D6/D7)
 * ทั้งสองผลแนบหมายเหตุได้ ไม่บังคับ (Manager 2026-07-18): ผ่าน → advice · ปฏิเสธ → fail_reason
 */
class Area5sReviewController extends Controller
{
    use HandlesArea5sAccess;

    private function gateReviewer(): ?RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        if ($this->isA5sAdmin() || $this->isEvaluator() || $this->myScopes()->isNotEmpty()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณยังไม่ได้รับมอบหมายให้ตรวจประเมินพื้นที่');
    }

    private function myScopes(): Collection
    {
        $code = trim((string) ($this->me()->employee_code ?? ''));

        return $code === '' ? collect() : A5sEvaluatorScope::where('employee_code', $code)->get();
    }

    /** งานนี้อยู่ในขอบเขตของฉันไหม — scope เจาะจุด หรือ scope ทั้ง layout (point_id null) */
    private function inMyScope(A5sTask $task): bool
    {
        if ($this->isA5sAdmin()) {
            return true;
        }

        return $this->myScopes()->contains(fn ($s) => (int) $s->layout_id === (int) $task->layout_id
            && ($s->point_id === null || (int) $s->point_id === (int) $task->point_id));
    }

    public function index(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateReviewer()) {
            return $redirect;
        }

        $round = A5sRound::open();
        $tasks = collect();
        if ($round) {
            $query = A5sTask::where('round_id', $round->id)->where('status', '!=', 'not_started');
            if (! $this->isA5sAdmin()) {
                $scopes = $this->myScopes();
                $query->where(function ($q) use ($scopes) {
                    $q->whereRaw('1 = 0');
                    foreach ($scopes as $s) {
                        $q->orWhere(fn ($w) => $w->where('layout_id', $s->layout_id)
                            ->when($s->point_id !== null, fn ($x) => $x->where('point_id', $s->point_id)));
                    }
                });
            }
            $tasks = $query->withCount('cards')->orderByDesc('submitted_at')->orderByDesc('updated_at')->get();
        }

        $groups = [
            'pending' => $tasks->whereIn('status', ['submitted', 'resubmitted'])->values(),
            'failed' => $tasks->where('status', 'failed')->values(),
            'passed' => $tasks->where('status', 'passed')->values(),
            'draft' => $tasks->whereIn('status', ['draft'])->values(),
        ];

        return view('area5s.review.index', [
            'me' => $this->me(),
            'round' => $round,
            'groups' => $groups,
            'tab' => in_array($request->query('tab'), ['pending', 'failed', 'passed', 'draft'], true) ? $request->query('tab') : 'pending',
        ]);
    }

    public function show(A5sTask $task): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateReviewer()) {
            return $redirect;
        }
        if (! $this->inMyScope($task)) {
            return $this->denyToSystems('toast.pageDenied', 'งานนี้ไม่อยู่ในขอบเขตที่คุณตรวจ');
        }

        $cards = A5sCard::with('images')->where('task_id', $task->id)->orderBy('sort')->orderBy('id')->get();
        $owners = AppUser::whereIn('id', $cards->pluck('created_by')->unique())->get()->keyBy('id');
        $round = A5sRound::find($task->round_id);

        return view('area5s.review.show', [
            'me' => $this->me(),
            'task' => $task,
            'round' => $round,
            'canDecide' => in_array($task->status, ['submitted', 'resubmitted'], true) && $round?->isOpen(),
            'cards' => $cards->map(function (A5sCard $c) use ($owners) {
                $owner = $owners->get($c->created_by);
                $ownerPayload = $this->a5sAppUserPayload($owner, $owner?->employee_code ?? '');

                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'detail' => $c->detail,
                    'owner' => $ownerPayload['name'] ?: '-',
                    'owner_th' => $ownerPayload['name_th'] ?: '-',
                    'owner_en' => $ownerPayload['name_en'] ?: '-',
                    'owner_my' => $ownerPayload['name_my'] ?: '-',
                    'created_at' => $c->created_at?->format('d/m/Y H:i'),
                    'images' => $c->images->map(fn ($i) => asset('storage/'.$i->path))->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    /** บันทึกผล — ผ่าน (ล็อกถาวร) / ไม่ผ่าน · แนบหมายเหตุได้ทั้งสองแบบ ไม่บังคับ (Manager 2026-07-18) */
    public function decide(Request $request, A5sTask $task): RedirectResponse
    {
        if ($redirect = $this->gateReviewer()) {
            return $redirect;
        }
        if (! $this->inMyScope($task)) {
            return $this->denyToSystems('toast.pageDenied', 'งานนี้ไม่อยู่ในขอบเขตที่คุณตรวจ');
        }
        // อนุญาตแก้ไขผลหลังตัดสินแล้ว (ผ่าน/ปฏิเสธ) ได้ ตราบใดที่รอบยังเปิด (Manager 2026-07-23)
        if (! in_array($task->status, ['submitted', 'resubmitted', 'passed', 'failed'], true)) {
            return back()->with('error', 'งานนี้ยังไม่ได้ส่งตรวจ');
        }
        $round = A5sRound::find($task->round_id);
        if (! $round?->isOpen()) {
            return back()->with('error', 'รอบเดือนของงานนี้ปิดแล้ว');
        }

        $data = $request->validate([
            'result' => ['required', 'in:pass,fail'],
            'fail_reason' => ['nullable', 'string', 'max:3000'],
            'advice' => ['nullable', 'string', 'max:3000'],
        ]);

        $pass = $data['result'] === 'pass';
        $task->status = $pass ? 'passed' : 'failed';
        $task->result = $data['result'];
        $task->fail_reason = $pass ? null : (trim((string) ($data['fail_reason'] ?? '')) ?: null);
        $task->advice = trim((string) ($data['advice'] ?? '')) ?: null;
        $task->evaluated_by = $this->me()->id;
        $task->evaluated_at = now();
        $task->save();

        // บันทึกประวัติครั้งนี้ (append-only) — แก้ผลซ้ำโดยไม่ส่งใหม่ = อัปเดต attempt เดิมของ seq นั้น (Manager 2026-07-24)
        $attemptSeq = max(1, (int) $task->submit_count);
        $cardSnapshot = $task->cards()->orderBy('sort')->orderBy('id')->get(['title', 'detail'])
            ->map(fn ($c) => ['title' => (string) $c->title, 'detail' => (string) $c->detail])
            ->all();
        A5sTaskAttempt::updateOrCreate(
            ['task_id' => $task->id, 'seq' => $attemptSeq],
            [
                'result' => $data['result'],
                'fail_reason' => $pass ? null : $task->fail_reason,
                'advice' => $task->advice,
                'cards_json' => $cardSnapshot,
                'evaluated_by' => $this->me()->id,
                'submitted_at' => $task->submitted_at,
                'evaluated_at' => $task->evaluated_at,
            ]
        );

        // แจ้งเตือนผู้รับผิดชอบทุกคนของจุด (in-app)
        foreach ((array) $task->assignees_json as $a) {
            A5sNotification::create([
                'employee_code' => $a['code'],
                'type' => $pass ? 'task.passed' : 'task.failed',
                'title' => ($pass ? '✅ ผ่าน' : '❌ ปฏิเสธ').": {$task->layout_name} จุด {$task->point_code}",
                'body' => $pass ? ($task->advice ?: 'ผ่านการประเมินแล้ว') : ($task->fail_reason ?: 'ถูกปฏิเสธ — โปรดแก้ไขและส่งตรวจใหม่'),
                'link' => route('area5s.responsible.show', $task->layout_id),
            ]);
        }
        A5sActivityLog::write($this->me()->id, $pass ? 'task.pass' : 'task.fail', 'task', $task->id, [
            'point' => $task->point_code, 'reason' => $task->fail_reason,
        ]);

        if ($request->input('_return') === 'my-work-layout') {
            return redirect()
                ->route('area5s.evaluations.show', ['layout' => $task->layout_id, 'point' => $task->point_id])
                ->with('success', $pass ? "ให้ผ่าน: จุด {$task->point_code} {$task->point_name}" : "ปฏิเสธ: จุด {$task->point_code} — ผู้รับผิดชอบจะแก้และส่งใหม่ได้")
                ->with('evaluation_point_id', $task->point_id);
        }

        return redirect()->route('area5s.review.index', ['tab' => $pass ? 'passed' : 'failed'])
            ->with('success', $pass ? "ให้ผ่าน: จุด {$task->point_code} {$task->point_name}" : "ปฏิเสธ: จุด {$task->point_code} — ผู้รับผิดชอบจะแก้และส่งใหม่ได้");
    }
}
