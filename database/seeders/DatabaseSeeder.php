<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Insight นำเข้าพนักงานจาก Bplus ผ่านคำสั่ง php artisan bplus:import-json
     * จึงไม่ใช้ seeder สร้างข้อมูลตัวอย่าง
     */
    public function run(): void
    {
        //
    }
}
