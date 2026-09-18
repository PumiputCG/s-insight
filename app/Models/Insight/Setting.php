<?php

namespace App\Models\Insight;

use Illuminate\Database\Eloquent\Model;

/**
 * ค่าตั้งระบบ Insight (key-value, value เป็น JSON)
 *
 * ใช้ผ่าน helper static: Setting::get($key, $default) / Setting::put($key, $value)
 */
class Setting extends Model
{
    protected $table = 'insight_settings';

    protected $fillable = ['key', 'value'];

    /** คีย์สิทธิ์ตามตำแหน่ง (value = array ของ job_code ที่อนุญาต ; null = อนุญาตทั้งหมด) */
    public const LOGIN_POSITIONS = 'login_allowed_job_codes';

    public const EMAIL_POSITIONS = 'email_allowed_job_codes';

    public const SIGNATURE_POSITIONS = 'signature_allowed_job_codes';

    /** คีย์สิทธิ์เข้าระบบ Assessment ตามตำแหน่ง (value = array ของ job_code ที่อนุญาต ; null = ทั้งหมด) */
    public const ASSESSMENT_POSITIONS = 'assessment_allowed_job_codes';

    /** คีย์สิทธิ์เข้าระบบ SUPAVUT 5S AREA ตามตำแหน่ง (รูปแบบเดียวกับ Assessment) */
    public const AREA5S_POSITIONS = 'area5s_allowed_job_codes';

    /** คีย์สิทธิ์เข้า OT Approval ตามตำแหน่ง (ค่าเริ่มต้นของโมดูลนี้ = ไม่อนุญาต จนกว่า admin จะเลือก) */
    public const OT_APPROVAL_POSITIONS = 'ot_approval_allowed_job_codes';

    /**
     * ตำแหน่งที่ Foreman จะไม่เห็นปุ่ม `ขอ OT`
     *
     * เป็น blacklist ตรงข้ามกับคีย์ด้านบน: ไม่ติ๊ก = ขอ OT ได้ตามปกติ
     * ตำแหน่งใหม่ที่ Bplus เพิ่มมาจึงไม่ถูกบล็อกโดยไม่ตั้งใจ
     */
    public const OT_REQUEST_HIDDEN_POSITIONS = 'ot_request_hidden_job_codes';

    /**
     * ตำแหน่งที่ Foreman จะไม่เห็นปุ่ม `ขอลา`
     *
     * คู่แฝดของคีย์ด้านบนแต่คนละระบบ: OT กับการลา 75 ตั้งค่าแยกกันได้
     * ไม่ติ๊ก = ขอลาได้ตามปกติ ตำแหน่งใหม่จาก Bplus จึงไม่ถูกบล็อกโดยไม่ตั้งใจ
     */
    public const LEAVE_REQUEST_HIDDEN_POSITIONS = 'leave_request_hidden_job_codes';

    /**
     * ตำแหน่ง (job_code) ได้รับอนุญาตสำหรับสิทธิ์นี้หรือไม่
     * - ไม่เคยตั้งค่า (null) = อนุญาตทุกตำแหน่ง
     * - ตั้งค่าแล้ว = เฉพาะ job_code ที่อยู่ในรายการ
     */
    public static function isPositionAllowed(string $key, ?string $jobCode): bool
    {
        $allowed = static::get($key, null);
        if (! is_array($allowed)) {
            return true;
        }

        $jobCode = trim((string) $jobCode);

        return $jobCode !== '' && in_array($jobCode, $allowed, true);
    }

    /** อ่านค่า (decode JSON) ; ไม่มี key -> คืน $default */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row || $row->value === null) {
            return $default;
        }

        $decoded = json_decode((string) $row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    /** บันทึกค่า (encode JSON) */
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)],
        );
    }
}
