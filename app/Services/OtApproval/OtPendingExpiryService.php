<?php

namespace App\Services\OtApproval;

use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use App\Support\OtApproval\PayrollCycle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class OtPendingExpiryService
{
    public const AUTO_REJECT_NOTE = 'พ้นกำหนดอนุมัติ: Supervisor ไม่อนุมัติภายในกำหนดวันที่ 23';

    /**
     * @return array{checked:int, rejected:int, ids:array<int>}
     */
    public function rejectExpired(?CarbonInterface $now = null, ?int $limit = null): array
    {
        $checked = 0;
        $rejected = 0;
        $ids = [];

        $query = OtRequest::query()
            ->where('approval_status', OtRequest::APPROVAL_SUBMITTED)
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->chunkById(200, function ($requests) use ($now, &$checked, &$rejected, &$ids): void {
            foreach ($requests as $request) {
                $checked++;

                if (PayrollCycle::canApprove($request->work_date, $now)) {
                    continue;
                }

                if ($this->rejectOne((int) $request->id, $now)) {
                    $rejected++;
                    $ids[] = (int) $request->id;
                }
            }
        });

        return [
            'checked' => $checked,
            'rejected' => $rejected,
            'ids' => $ids,
        ];
    }

    private function rejectOne(int $id, ?CarbonInterface $now = null): bool
    {
        return DB::connection('mysql_ot_approval')->transaction(function () use ($id, $now): bool {
            /** @var OtRequest|null $request */
            $request = OtRequest::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (! $request || $request->approval_status !== OtRequest::APPROVAL_SUBMITTED) {
                return false;
            }

            if (PayrollCycle::canApprove($request->work_date, $now)) {
                return false;
            }

            $request->approval_status = OtRequest::APPROVAL_REJECTED;
            $request->decision_note = self::AUTO_REJECT_NOTE;
            $request->decided_at = $now ?? now();
            $request->decided_by_app_user_id = null;
            $request->decided_by_employee_code = null;

            if ($request->export_status !== OtRequest::EXPORT_EXPORTED) {
                $request->export_status = OtRequest::EXPORT_NOT_READY;
            }

            $request->save();

            OtRequestApproval::create([
                'ot_request_id' => $request->id,
                'decision' => OtRequest::APPROVAL_REJECTED,
                'actor_app_user_id' => null,
                'actor_employee_code' => null,
                'note' => self::AUTO_REJECT_NOTE,
            ]);

            return true;
        });
    }
}
