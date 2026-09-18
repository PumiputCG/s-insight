<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สมุดบันทึกการดึงรูปจากโฟลเดอร์ของ HR
     *
     * ทำไมต้องมีตาราง ไม่ใช่แค่ก็อปไฟล์ทิ้งไว้:
     * 1) โฟลเดอร์ต้นทางเป็น network share ที่ช้ามาก (สแกนทั้งก้อนไม่จบใน 9 นาที)
     *    ต้องจำไว้ว่าไฟล์ไหนเคยดึงแล้วเพื่อข้ามในรอบถัดไป
     * 2) ไฟล์จำนวนมากจับคู่กับพนักงานไม่ได้ (ชื่อไฟล์เป็นชื่อคน · เป็น PDF · ไม่มีในทะเบียน)
     *    ต้องเก็บไว้เป็นรายงานให้ HR ตามเก็บ ไม่ใช่ข้ามเงียบๆ
     */
    public function up(): void
    {
        // ไฟล์ต้นทางแต่ละไฟล์ที่เคยเห็น
        Schema::create('employee_photo_files', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 500)->unique();   // path เต็มในโฟลเดอร์ HR
            $table->string('file_name');
            $table->string('employee_code', 32)->nullable()->index();
            $table->string('company', 64)->nullable();
            $table->timestamp('source_modified_at')->nullable();
            $table->unsignedBigInteger('source_size')->default(0);

            // imported = ดึงเข้าสำเร็จ · orphan = หาเจ้าของไม่ได้ · skipped = ไม่ใช่ไฟล์รูปที่รับ
            $table->string('status', 16)->index();
            $table->string('reason')->nullable();

            $table->string('stored_path')->nullable();      // profiles/emp_<code>.jpg
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // สรุปผลแต่ละรอบที่รัน — ให้หน้ารายงานบอกได้ว่ารอบล่าสุดเป็นยังไง
        Schema::create('employee_photo_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('trigger', 16)->default('schedule'); // schedule | manual | cli
            $table->unsignedInteger('scanned')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('unchanged')->default(0);
            $table->unsignedInteger('orphans')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_photo_runs');
        Schema::dropIfExists('employee_photo_files');
    }
};
