<?php

namespace App\Http\Middleware;

use App\Models\Area5s\A5sMember;
use App\Models\Area5s\A5sRound;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * บล็อกทุกหน้า 5S เมื่อไม่มีรอบเดือนเปิดอยู่ (ยกเว้น route rounds.* ที่ถูก exempt ใน routes)
 * — แสดงหน้าข้อความกลางจอ · admin เห็นปุ่มไปเปิดรอบ · JSON ตอบ 423
 */
class EnsureArea5sRoundOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $open = A5sRound::open();
        } catch (\Throwable $e) {
            $open = null;
        }

        if ($open) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => 'ยังไม่มีการเปิดรอบเดือน 5ส — บันทึกและส่งตรวจได้เมื่อเปิดรอบ',
            ], 423);
        }

        $me = app()->bound('current_user') ? app('current_user') : null;
        $isAdmin = false;
        try {
            $isAdmin = $me && ($me->isAdmin() || A5sMember::hasRole($me->id, A5sMember::ROLE_ADMIN));
        } catch (\Throwable $e) {
            $isAdmin = false;
        }

        return response()->view('area5s.no-round', ['me' => $me, 'isAdmin' => $isAdmin]);
    }
}
