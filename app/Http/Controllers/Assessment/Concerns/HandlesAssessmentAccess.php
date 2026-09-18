<?php

namespace App\Http\Controllers\Assessment\Concerns;

use App\Models\Assessment\AsmMember;
use App\Models\Insight\Setting;
use App\Services\Assessment\HierarchyAccess;
use Illuminate\Http\RedirectResponse;

/**
 * ตรวจสิทธิ์เข้าระบบ Assessment (แบบเดียวกับ Recruit)
 *
 * - admin = เข้าได้ทุกอย่าง
 * - hr member = จัดการคะแนน/กล่องได้ (ตั้งค่าระบบยังจำกัด admin)
 * - พนักงานทั่วไป = เข้าได้ถ้าตำแหน่งอยู่ในรายการที่อนุญาต (ภาพรวม/ประเมินตนเองอนาคต)
 */
trait HandlesAssessmentAccess
{
    protected function me()
    {
        return app('current_user');
    }

    protected function isAsmHr(): bool
    {
        $me = $this->me();

        return $me && AsmMember::isHr($me->id);
    }

    /** ตำแหน่งของผู้ใช้นี้ได้รับอนุญาตเข้าระบบหรือไม่ (null=อนุญาตทั้งหมด) */
    protected function positionAllowed(): bool
    {
        $me = $this->me();
        $jobCode = optional($me->employee)->job_code;

        return Setting::isPositionAllowed(Setting::ASSESSMENT_POSITIONS, $jobCode);
    }

    /** เฉพาะ admin หรือ HR member */
    protected function gateAdminOrHr(): ?RedirectResponse
    {
        $me = $this->me();
        // HR member ต้องผ่านรายการตำแหน่งด้วย (ตำแหน่งเป็นเงื่อนไขบังคับ ยกเว้น admin)
        if ($me->isAdmin() || (AsmMember::isHr($me->id) && $this->positionAllowed())) {
            return null;
        }

        $hierarchyAccess = app(HierarchyAccess::class)->accessFor($me);
        $hasAssessmentEntry = AsmMember::isMember($me->id)
            || $this->positionAllowed()
            || $hierarchyAccess['can_evaluate']
            || $hierarchyAccess['can_review'];

        return $this->denyToSystems(
            $hasAssessmentEntry ? 'toast.pageDenied' : 'toast.assessmentDenied',
            $hasAssessmentEntry ? 'คุณไม่มีสิทธิ์เข้าหน้านี้' : 'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Assessment',
        );
    }

    /** กลับหน้าระบบทั้งหมดพร้อม toast แทนหน้า 403 */
    protected function denyToSystems(string $key, string $fallback): RedirectResponse
    {
        return redirect()
            ->route('systems.index')
            ->with('toast_key', $key)
            ->with('toast_fallback', $fallback)
            ->with('toast_type', 'error');
    }
}
