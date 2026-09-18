<?php

namespace App\Services\Insight;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * บอก PR Compare ว่าข้อมูลพนักงานฝั่ง Insight เปลี่ยนแล้ว
 *
 * Insight เป็นเจ้าของข้อมูล ส่วน PR Compare (C:\xampp\htdocs\PR Compare) มิเรอร์ไปใช้ล็อกอิน
 * เดิม PR Compare ต้องซิงค์เองทุก 15 นาที ทำให้ข้อมูลที่แก้ที่นี่ไปโผล่ช้า
 * ตอนนี้เปลี่ยนเป็น "แก้ที่ Insight แล้วยิงบอกทันที" ปลายทางเห็นผลภายในวินาทีเดียว
 *
 * หลักการ:
 *   - ส่งแค่ **id ของแถวที่เปลี่ยน** ไม่ส่งข้อมูลพนักงานออกไปทางเน็ตเวิร์ก
 *     (PR Compare อ่านฐานข้อมูล insight ได้เองอยู่แล้ว)
 *   - รวบ id ทั้ง request/command แล้วยิงครั้งเดียวตอนจบ ไม่ยิงทีละแถว
 *   - **ห้ามทำให้ Insight พัง**: ต่อไม่ติด/ช้า/ตอบ error ก็แค่เขียน log แล้วผ่านไป
 *
 * ปิดใช้งานได้โดยเว้น PR_COMPARE_URL ว่างใน .env
 */
class PrCompareNotifier
{
    /** @var array<int,int> id ของ app_users ที่เปลี่ยนในรอบนี้ */
    private static array $users = [];

    /** @var array<int,int> id ของ employees ที่เปลี่ยนในรอบนี้ */
    private static array $employees = [];

    /** ลงทะเบียน callback ตอนจบไว้แล้วหรือยัง (กันลงซ้ำ) */
    private static bool $queued = false;

    /** เปลี่ยนเยอะกว่านี้ ให้ปลายทางซิงค์ทั้งชุดแทน จะเร็วกว่าไล่ทีละ id */
    private const BULK_THRESHOLD = 400;

    public static function markUser(int|string|null $id): void
    {
        if ($id) {
            self::$users[(int) $id] = (int) $id;
            self::queue();
        }
    }

    public static function markEmployee(int|string|null $id): void
    {
        if ($id) {
            self::$employees[(int) $id] = (int) $id;
            self::queue();
        }
    }

    /** สั่งให้ปลายทางซิงค์ใหม่ทั้งชุด (ใช้ตอนลบทิ้งหลายแถวหรือ import ก้อนใหญ่) */
    public static function markAll(): void
    {
        self::$users['all'] = 0;
        self::queue();
    }

    /** ยิงจริงตอนจบ request/command — เรียกซ้ำได้ ไม่ส่งซ้ำ */
    public static function flush(): void
    {
        $targets = self::targets();
        $secret = (string) config('insight.pr_compare_secret');

        $users = array_values(array_filter(self::$users));
        $employees = array_values(self::$employees);
        $all = array_key_exists('all', self::$users)
            || count($users) + count($employees) > self::BULK_THRESHOLD;

        // เคลียร์ก่อนยิง กันวนซ้ำถ้ามีการ save ระหว่างส่ง
        self::$users = [];
        self::$employees = [];
        self::$queued = false;

        if ($targets === [] || $secret === '') {
            return;   // ยังไม่ได้ตั้งค่า = ปิดใช้งาน
        }

        if (! $all && $users === [] && $employees === []) {
            return;
        }

        $payload = [
            'secret' => $secret,
            'app_users' => $all ? [] : $users,
            'employees' => $all ? [] : $employees,
            'all' => $all,
        ];

        foreach ($targets as $index => $url) {
            $endpoint = $url.'/insight-changed';

            try {
                // timeout สั้นไว้ก่อน — บน Apache/mod_php ตัว terminating callback
                // อาจรันก่อนปิด response ทำให้ผู้ใช้ต้องรอไปด้วย
                // ปลายทางสำรอง (เครื่อง dev) ให้สั้นกว่า เพราะปิดเครื่องอยู่บ่อย
                $response = Http::connectTimeout(1)
                    ->timeout($index === 0 ? 3 : 2)
                    ->acceptJson()
                    ->post($endpoint, $payload);

                // 404/401 ไม่ throw — ถ้าไม่ log ไว้ URL ผิดแล้วจะเงียบสนิท ข้อมูลค้างโดยไม่มีใครรู้
                if ($response->failed()) {
                    Log::warning('แจ้ง PR Compare ไม่สำเร็จ: HTTP '.$response->status().' ที่ '.$endpoint);
                }
            } catch (Throwable $e) {
                // ปลายทางล่มไม่ใช่เรื่องของ Insight — บันทึกไว้เฉยๆ แล้วยิงปลายทางถัดไปต่อ
                Log::warning('แจ้ง PR Compare ไม่สำเร็จ ที่ '.$endpoint.' : '.$e->getMessage());
            }
        }
    }

    /**
     * ปลายทางทั้งหมดที่ต้องแจ้ง
     *
     * ตัวแรกคือของจริงบนเซิร์ฟเวอร์ (`PR_COMPARE_URL`)
     * ที่เหลือคือเครื่อง dev (`PR_COMPARE_URLS` คั่นด้วยจุลภาค) ซึ่งเป็น best-effort
     *
     * @return array<int, string>
     */
    private static function targets(): array
    {
        $raw = trim((string) config('insight.pr_compare_url'))
            .','.trim((string) config('insight.pr_compare_urls'));

        $urls = [];
        foreach (explode(',', $raw) as $url) {
            $url = rtrim(trim($url), '/');

            if ($url !== '') {
                $urls[$url] = $url;   // กันตั้ง URL เดียวกันซ้ำ
            }
        }

        return array_values($urls);
    }

    /** ลงทะเบียนให้ flush ตอนจบ request/command ครั้งเดียวพอ */
    private static function queue(): void
    {
        if (self::$queued) {
            return;
        }

        self::$queued = true;
        app()->terminating(static fn () => self::flush());
    }
}
