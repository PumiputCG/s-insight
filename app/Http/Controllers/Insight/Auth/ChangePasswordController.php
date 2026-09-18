<?php

namespace App\Http\Controllers\Insight\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

/**
 * เปลี่ยนรหัสผ่านเอง (เก็บ plaintext — ดูได้ ไม่ hash ตามคำสั่งเจ้าของ)
 * - รหัสเริ่มต้น = เลขบัตรประชาชน (เก็บถาวรใน id_thai_hash ใช้ยืนยันตัวตน)
 */
class ChangePasswordController extends Controller
{
    public function edit(): ViewContract
    {
        // ฟอร์มเปลี่ยนรหัสผ่านอยู่ในหน้าโปรไฟล์ (dashboard) แล้ว — ส่งกลับไปที่นั่น
        return View::make('insight.dashboard.user', [
            'me' => app('current_user'),
            'emp' => app('current_user')->employee,
        ]);
    }

    /**
     * ตรวจสอบเลขบัตรประชาชนของผู้ใช้ปัจจุบัน (AJAX) ก่อนเปิดช่องตั้งรหัสใหม่ใน modal
     */
    public function verify(Request $request): JsonResponse
    {
        $me = app('current_user');
        $idCard = trim((string) $request->input('id_card'));

        $ok = $idCard !== '' && hash_equals((string) $me->id_thai_hash, $idCard);

        return response()->json(['ok' => $ok]);
    }

    public function update(Request $request): RedirectResponse
    {
        $me = app('current_user');

        $data = $request->validate([
            'id_card' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'รหัสผ่านใหม่กับยืนยันไม่ตรงกัน',
            'password.min' => 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 6 ตัวอักษร',
        ], [
            'id_card' => 'เลขบัตรประชาชน',
            'password' => 'รหัสผ่านใหม่',
        ]);

        // ยืนยันตัวตนด้วยเลขบัตรประชาชน (เก็บถาวรใน id_thai_hash, plaintext ตาม D-007)
        if (! hash_equals((string) $me->id_thai_hash, trim((string) $data['id_card']))) {
            return back()->with('error', 'เลขบัตรประชาชนไม่ถูกต้อง')->withErrors([
                'id_card' => 'เลขบัตรประชาชนไม่ถูกต้อง',
            ]);
        }

        $me->forceFill([
            'password' => $data['password'],          // plaintext (ดูได้ ไม่ hash)
        ])->save();

        return redirect()->route('dashboard')->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
