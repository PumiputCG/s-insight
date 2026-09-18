<?php

namespace App\Http\Middleware;

use App\Models\Insight\AppUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * ยืนยันตัวตนด้วย session เอง (custom auth — เทียบ password แบบ plaintext)
 * - ไม่มี session / บัญชีถูกลบ (พนักงานลาออก) -> เด้งไปหน้า login
 * - แชร์ตัวแปร $me (AppUser) ให้ทุก view + ผูกใน container ('current_user')
 */
class AuthenticateEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('insight_user_id');
        $me = $id ? AppUser::find($id) : null;

        if (! $me || ! $me->hasActiveEmployment()) {
            $request->session()->forget('insight_user_id');

            return redirect()->route('login')->with(
                'error',
                $me ? 'พนักงานพ้นสภาพแล้ว ไม่สามารถเข้าสู่ระบบได้' : 'กรุณาเข้าสู่ระบบก่อนใช้งาน',
            );
        }

        // ผูกไว้ใน container + แชร์ให้ view (ใช้ $me ใน blade ได้)
        app()->instance('current_user', $me);
        View::share('me', $me);

        return $next($request);
    }
}
