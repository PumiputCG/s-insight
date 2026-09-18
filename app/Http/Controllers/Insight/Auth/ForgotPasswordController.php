<?php

namespace App\Http\Controllers\Insight\Auth;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ลืมรหัสผ่าน — ตั้งรหัสใหม่จากหน้า login โดยยังไม่ได้ล็อกอิน
 *
 * ยืนยันตัวตนด้วย **รหัสพนักงาน + เลขบัตรประชาชน** ที่ต้องตรงกันทั้งคู่
 * (ต่างจาก ChangePasswordController ที่ใช้ในหน้าโปรไฟล์ ซึ่งรู้ตัวคนจาก session อยู่แล้ว)
 *
 * ⚠️ ข้อจำกัดที่ต้องรู้: เลขบัตรประชาชนเป็นรหัสผ่านเริ่มต้นของระบบนี้อยู่แล้ว (D-007)
 *    ใครที่รู้ทั้งรหัสพนักงานและเลขบัตรของคนอื่นจึงตั้งรหัสใหม่แทนเขาได้
 *    จำกัดจำนวนครั้งด้วย `throttle` ที่ route และไม่บอกว่าผิดช่องไหน กัน brute force
 */
class ForgotPasswordController extends Controller
{
    /** ข้อความเดียวใช้ทุกกรณีที่ไม่ผ่าน — ไม่บอกว่ารหัสพนักงานมีจริงไหม */
    private const FAILED = 'รหัสพนักงานหรือเลขบัตรประชาชนไม่ถูกต้อง';

    /** ตรวจคู่รหัสพนักงาน + เลขบัตร (AJAX) ก่อนเปิดช่องตั้งรหัสใหม่ใน modal */
    public function verify(Request $request): JsonResponse
    {
        $user = $this->resolve(
            (string) $request->input('employee_code'),
            (string) $request->input('id_card'),
        );

        return response()->json([
            'ok' => $user !== null,
            'message' => $user !== null ? '' : self::FAILED,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'id_card' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'รหัสผ่านใหม่กับยืนยันไม่ตรงกัน',
            'password.min' => 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 6 ตัวอักษร',
        ], [
            'employee_code' => 'รหัสพนักงาน',
            'id_card' => 'เลขบัตรประชาชน',
            'password' => 'รหัสผ่านใหม่',
        ]);

        $user = $this->resolve($data['employee_code'], $data['id_card']);

        if (! $user) {
            return back()
                ->withErrors(['forgot_id_card' => self::FAILED])
                ->with('forgot_open', true);
        }

        $user->forceFill([
            'password' => $data['password'],   // plaintext (ดูได้ ไม่ hash — D-007)
        ])->save();

        return redirect()->route('login')->with('success', 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว เข้าสู่ระบบได้เลย');
    }

    /**
     * หาบัญชีที่ "รหัสพนักงานและเลขบัตรตรงกันทั้งคู่"
     *
     * รหัสพนักงาน resolve แบบเดียวกับตอนล็อกอิน — รหัสหลักหรือรหัสในบริษัทใดบริษัทหนึ่ง (`companies`)
     * บัญชีที่ไม่มีเลขบัตรเก็บไว้ (เช่น system admin) ใช้ช่องทางนี้ไม่ได้
     */
    private function resolve(string $employeeCode, string $idCard): ?AppUser
    {
        $code = trim($employeeCode);
        $idCard = trim($idCard);

        if ($code === '' || $idCard === '') {
            return null;
        }

        $user = AppUser::where('employee_code', $code)
            ->orWhereRaw("JSON_SEARCH(companies, 'one', ?) IS NOT NULL", [$code])
            ->first();

        if (! $user || trim((string) $user->id_thai_hash) === '') {
            return null;
        }

        return hash_equals((string) $user->id_thai_hash, $idCard) ? $user : null;
    }
}
