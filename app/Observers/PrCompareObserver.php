<?php

namespace App\Observers;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Services\Insight\PrCompareNotifier;
use Illuminate\Database\Eloquent\Model;

/**
 * ดักทุกจุดที่ข้อมูลพนักงานเปลี่ยน แล้วบอก PR Compare ให้ดึงไปอัปเดต
 *
 * ใช้ observer แทนการไปแก้ทีละ controller เพราะครอบคลุมทุกทาง:
 * แก้โปรไฟล์ · admin แก้ให้ · เปลี่ยนรหัสผ่าน · อัปโหลดลายเซ็น · ดึงจาก Bplus
 *
 * ตัวส่งจะรวบ id ทั้งรอบแล้วยิงครั้งเดียวตอนจบ (ดู PrCompareNotifier)
 * จึงไม่ยิง 1,600 ครั้งตอน bplus:sync
 */
class PrCompareObserver
{
    public function saved(Model $model): void
    {
        $this->mark($model);
    }

    public function deleted(Model $model): void
    {
        $this->mark($model);
    }

    private function mark(Model $model): void
    {
        if ($model instanceof AppUser) {
            PrCompareNotifier::markUser($model->getKey());

            return;
        }

        if ($model instanceof Employee) {
            PrCompareNotifier::markEmployee($model->getKey());
        }
    }
}
