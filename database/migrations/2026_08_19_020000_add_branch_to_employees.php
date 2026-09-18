<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เก็บ "สาขา" ของพนักงานจาก Bplus เพื่อใช้เป็นตัวกรองในหน้า Time & Leave
 *
 * Manager ต้องการแยกคนไทยกับคนพม่า แต่ Bplus **ไม่มีฟิลด์สัญชาติที่ใช้งานได้เลย**
 * (`FOREIGNINFO` มี 838 แถวแต่ว่างทั้งหมด · `EMP_ADDR_COUNTRY` กรอกไม่ถึง 10%
 * และไม่มีค่า "พม่า" สักแถว) สิ่งที่บริษัทใช้แบ่งจริงคือ **สาขา** ในตาราง `BRANCH`
 * ของ Supavut Industry มี `10 = โรงงาน` กับ `11 = โรงงาน-พม่า` แยกไว้ชัดเจนแล้ว
 * (โรงงาน-พม่า 441 คน ซึ่งใกล้เคียงกับ 416 คนที่เลขบัตรขึ้นต้น 0/6/7)
 *
 * `EMP_MAIN` view ที่ `bplus:sync` ใช้อยู่ไม่มีคอลัมน์สาขา จึงต้อง join
 * `PERSONALINFO.PRS_BR` -> `BRANCH.BR_KEY` เพิ่มในคำสั่ง sync
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'branch_code')) {
                $table->string('branch_code', 20)->nullable()->after('dept_en');
            }
            if (! Schema::hasColumn('employees', 'branch_th')) {
                $table->string('branch_th', 120)->nullable()->after('branch_code');
            }
            if (! Schema::hasColumn('employees', 'branch_en')) {
                $table->string('branch_en', 120)->nullable()->after('branch_th');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['branch_code', 'branch_th', 'branch_en']);
        });
    }
};
