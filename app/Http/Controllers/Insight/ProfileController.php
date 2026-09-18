<?php

namespace App\Http\Controllers\Insight;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use App\Models\Insight\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * จัดการรูปโปรไฟล์ของผู้ใช้ปัจจุบัน — เก็บไฟล์ใน storage/app/public/profiles
 * และเก็บ path ลง app_users.profile_picture
 */
class ProfileController extends Controller
{
    public function updatePicture(Request $request): RedirectResponse
    {
        $me = app('current_user');

        // รองรับ jpg/png/webp และไฟล์จาก iPhone (heic/heif) ขนาดใหญ่
        // หมายเหตุ: ขนาดสูงสุดจริงยังถูกจำกัดด้วย php.ini (upload_max_filesize/post_max_size)
        $request->validate([
            'picture' => ['required', 'file', 'max:40960', 'mimes:jpg,jpeg,png,webp,heic,heif'],
        ], [
            'picture.max' => 'ไฟล์รูปใหญ่เกินไป (สูงสุด 40MB)',
            'picture.mimes' => 'รองรับเฉพาะไฟล์ภาพ .jpg .png .webp .heic .heif',
            'picture.required' => 'กรุณาเลือกไฟล์รูป',
        ], [
            'picture' => 'รูปโปรไฟล์',
        ]);

        // ลบรูปเดิม (ถ้ามีและอยู่ใน disk public)
        if ($me->profile_picture && Storage::disk('public')->exists($me->profile_picture)) {
            Storage::disk('public')->delete($me->profile_picture);
        }

        $path = $request->file('picture')->store('profiles', 'public');

        $me->forceFill(['profile_picture' => $path])->save();

        return redirect()->route('dashboard')->with('success', 'อัปเดตรูปโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * บันทึกลายเซ็น (AJAX) — รับ PNG data URL (โปร่งใส ตัดพื้นหลังออกแล้วจากฝั่ง client)
     * เก็บลง app_users.signature เพื่อนำไปประทับเอกสารในอนาคต
     */
    public function updateSignature(Request $request): JsonResponse
    {
        $me = app('current_user');

        if (! $this->canUse($me, Setting::SIGNATURE_POSITIONS)) {
            return response()->json(['ok' => false, 'forbidden' => true], 403);
        }

        $data = $request->validate([
            // data:image/png;base64,xxxx — จำกัดความยาวกันสแปม (~5MB base64)
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:7000000'],
        ], [
            'signature.required' => 'กรุณาเซ็นก่อนบันทึก',
            'signature.starts_with' => 'รูปแบบลายเซ็นไม่ถูกต้อง',
            'signature.max' => 'ลายเซ็นมีขนาดใหญ่เกินไป',
        ]);

        $me->forceFill(['signature' => $data['signature']])->save();

        return response()->json(['ok' => true]);
    }

    /** ลบลายเซ็นที่บันทึกไว้ (AJAX) */
    public function deleteSignature(): JsonResponse
    {
        $me = app('current_user');
        $me->forceFill(['signature' => null])->save();

        return response()->json(['ok' => true]);
    }

    /** บันทึกอีเมลของผู้ใช้ (AJAX) — เก็บ app_users.email */
    public function updateEmail(Request $request): JsonResponse
    {
        $me = app('current_user');

        if (! $this->canUse($me, Setting::EMAIL_POSITIONS)) {
            return response()->json(['ok' => false, 'forbidden' => true], 403);
        }

        $data = $request->validate([
            'email' => ['nullable', 'email:rfc', 'max:255'],
        ], [
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
        ]);

        $me->forceFill(['email' => $data['email'] ?: null])->save();

        return response()->json(['ok' => true, 'email' => $me->email]);
    }

    /** admin ใช้ได้เสมอ ; user เช็คสิทธิ์ตามตำแหน่ง (job_code) */
    private function canUse(AppUser $me, string $key): bool
    {
        return $me->isAdmin() || Setting::isPositionAllowed($key, optional($me->employee)->job_code);
    }
}
