<?php

namespace App\Models\Recruit;

use Illuminate\Database\Eloquent\Model;

/**
 * ค่าตั้งของระบบ Recruit (key-value, value เป็น JSON) ใน insight_recruit
 *
 * เช่น `route_order` = ลำดับเส้นทางเอกสาร (admin จัดเอง)
 */
class RecruitSetting extends Model
{
    protected $connection = 'mysql_recruit';

    protected $table = 'recruit_settings';

    protected $fillable = ['key', 'value'];

    public const ROUTE_ORDER = 'route_order';

    /** ลำดับเส้นทางเริ่มต้น */
    public const DEFAULT_ROUTE = ['dcc', 'manager', 'hr_manager', 'recruit'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row || $row->value === null) {
            return $default;
        }

        $decoded = json_decode((string) $row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
    }

    /** ลำดับเส้นทางเอกสารปัจจุบัน (กรองให้เหลือเฉพาะ role ที่ถูกต้อง + เติมที่ขาด) */
    public static function routeOrder(): array
    {
        $saved = static::get(self::ROUTE_ORDER, null);
        if (! is_array($saved)) {
            return self::DEFAULT_ROUTE;
        }

        $valid = array_values(array_filter($saved, fn ($r) => in_array($r, self::DEFAULT_ROUTE, true)));
        // เติม role ที่ยังไม่อยู่ในลำดับไว้ท้าย
        foreach (self::DEFAULT_ROUTE as $r) {
            if (! in_array($r, $valid, true)) {
                $valid[] = $r;
            }
        }

        return $valid;
    }
}
