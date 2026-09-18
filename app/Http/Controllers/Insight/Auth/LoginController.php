<?php

namespace App\Http\Controllers\Insight\Auth;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use App\Models\Insight\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

/**
 * Login เอง: รหัสพนักงาน + รหัสบัตรประชาชน/รหัสผ่าน (เทียบ plaintext — ไม่ hash ตามคำสั่งเจ้าของ)
 * ไม่ต้องเลือกบริษัท: resolve บัญชีจากรหัสพนักงานอย่างเดียว
 *   - ค้นทั้งรหัสหลัก (employee_code) และรหัสในแต่ละบริษัทใน map `companies`
 *     {"SUPAVUT_INDUSTRY":"71019","MOLDVANTO":"16899"} -> คนข้ามบริษัทพิมพ์รหัสบริษัทไหนก็เข้าได้
 * รหัสผ่านเริ่มต้นของ user = เลขบัตรประชาชน (ผู้ใช้เปลี่ยนได้)
 * บัญชี system admin แยก: employee_code=Admin / password=000000
 * พนักงานลาออกจะไม่มีบัญชีใน app_users -> ล็อกอินไม่ได้
 */
class LoginController extends Controller
{
    public function show(Request $request): ViewContract|RedirectResponse
    {
        if ($request->session()->get('insight_user_id')) {
            return redirect()->route('systems.index');
        }

        return View::make('insight.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], [
            'employee_code' => 'รหัสพนักงาน',
            'password' => 'รหัสบัตรประชาชนหรือรหัสผ่าน',
        ]);

        $code = trim($data['employee_code']);

        // resolve บัญชีจากรหัสพนักงาน: รหัสหลัก หรือ รหัสในบริษัทใดบริษัทหนึ่ง (companies map)
        $user = AppUser::where('employee_code', $code)
            ->orWhereRaw("JSON_SEARCH(companies, 'one', ?) IS NOT NULL", [$code])
            ->first();

        // แยกข้อความ: ไม่พบรหัสพนักงาน vs รหัสผ่านไม่ถูกต้อง (แสดงใต้ช่องที่ผิด)
        if (! $user) {
            return back()
                ->withInput($request->only('employee_code'))
                ->withErrors(['employee_code' => 'รหัสพนักงานไม่ถูกต้อง']);
        }

        // เทียบ plaintext (ไม่ hash)
        if (! hash_equals((string) $user->password, (string) $data['password'])) {
            return back()
                ->withInput($request->only('employee_code'))
                ->withErrors(['password' => 'รหัสผ่านไม่ถูกต้อง']);
        }

        if (! $user->hasActiveEmployment()) {
            return back()
                ->withInput($request->only('employee_code'))
                ->withErrors(['employee_code' => 'พนักงานพ้นสภาพแล้ว ไม่สามารถเข้าสู่ระบบได้']);
        }

        // คุมสิทธิ์ตามตำแหน่ง (ตั้งค่าได้ที่หน้า Setting) — admin เข้าได้เสมอ
        if (! $user->isAdmin() && ! $this->positionAllowed($user)) {
            return back()
                ->withInput($request->only('employee_code'))
                ->withErrors(['employee_code' => 'ตำแหน่งของคุณยังไม่ได้รับอนุญาตให้เข้าสู่ระบบ ติดต่อผู้ดูแลระบบ']);
        }

        // ป้องกัน session fixation
        $request->session()->regenerate();
        $request->session()->put('insight_user_id', $user->id);
        // ประกาศ 5ส เด้งครั้งเดียวต่อการล็อกอิน — consume ตอนเข้า /area5s/home ครั้งแรก (logout→login ใหม่ = เห็นอีก)
        $request->session()->put('a5s_welcome_pending', true);

        return redirect()->intended(route('systems.index'));
    }

    /**
     * ตำแหน่งของผู้ใช้ได้รับอนุญาตให้ล็อกอินหรือไม่
     * - ไม่เคยตั้งค่า (null) = อนุญาตทุกตำแหน่ง
     * - ตั้งค่าแล้ว = อนุญาตเฉพาะ job_code ที่อยู่ในรายการ
     */
    protected function positionAllowed(AppUser $user): bool
    {
        return Setting::isPositionAllowed(Setting::LOGIN_POSITIONS, optional($user->employee)->job_code);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('insight_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'ออกจากระบบแล้ว');
    }
}
