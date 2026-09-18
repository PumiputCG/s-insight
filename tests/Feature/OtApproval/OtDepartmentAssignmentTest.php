<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\OtDepartmentAssignment;
use App\Support\OtApproval\OtShiftGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * กติกาที่ Manager กำหนด (2026-08-11):
 *   - Foreman กำหนดได้หลายคนต่อบริษัท+แผนก และติดป้ายกะ (ทั้งหมด/เช้า/ดึก) ให้แต่ละคน
 *   - Supervisor ยังเป็น 1 คนต่อบริษัท+แผนก ไม่เปลี่ยน
 *   - ป้ายกะเป็นข้อมูลแสดงผล ไม่ใช่สิทธิ์
 *
 * โปรเจคนี้ test DB เป็น sqlite :memory: และไม่ได้รันมมิเกรชัน จึงสร้างเฉพาะ
 * ตารางที่จำเป็นเองในเทสต์ และชี้ connection ของโมดูลมาที่ sqlite เดียวกัน
 */
class OtDepartmentAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql_ot_approval' => config('database.connections.sqlite')]);
        DB::purge('mysql_ot_approval');

        Schema::connection('mysql_ot_approval')->create('ot_department_assignments', function ($table) {
            $table->id();
            $table->string('company', 50);
            $table->string('dept_code', 50)->default('');
            $table->string('role', 20);
            $table->string('shift_group', 20)->nullable();
            $table->unsignedBigInteger('app_user_id');
            $table->string('employee_code', 30);
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['company', 'dept_code', 'role', 'app_user_id'], 'ot_department_role_user_unique');
        });
    }

    public function test_many_foremen_can_share_one_department_with_different_shifts(): void
    {
        $this->makeForeman(101, 'FM001', OtShiftGroup::MORNING);
        $this->makeForeman(102, 'FM002', OtShiftGroup::NIGHT);

        $foremen = OtDepartmentAssignment::query()
            ->where('dept_code', 'SPV004')
            ->where('role', OtDepartmentAssignment::ROLE_FOREMAN)
            ->orderBy('employee_code')
            ->get();

        $this->assertCount(2, $foremen, 'ต้องกำหนด Foreman ได้มากกว่า 1 คนต่อแผนก');
        $this->assertSame([OtShiftGroup::MORNING, OtShiftGroup::NIGHT], $foremen->pluck('shift_group')->all());
    }

    public function test_the_same_person_cannot_be_added_twice_to_one_department_role(): void
    {
        $this->makeForeman(101, 'FM001', OtShiftGroup::MORNING);

        // ยิงซ้ำคนเดิมต้องเป็นการอัปเดตแถวเดิม ไม่ใช่เพิ่มแถวใหม่
        OtDepartmentAssignment::updateOrCreate(
            [
                'company' => 'SUPAVUT_INDUSTRY',
                'dept_code' => 'SPV004',
                'role' => OtDepartmentAssignment::ROLE_FOREMAN,
                'app_user_id' => 101,
            ],
            ['shift_group' => OtShiftGroup::NIGHT, 'employee_code' => 'FM001'],
        );

        $rows = OtDepartmentAssignment::where('app_user_id', 101)->get();

        $this->assertCount(1, $rows, 'คนเดิมในแผนกเดิมต้องไม่เกิดแถวซ้ำ');
        $this->assertSame(OtShiftGroup::NIGHT, $rows->first()->shift_group, 'ต้องอัปเดตกะให้แถวเดิม');
    }

    public function test_shift_label_only_applies_to_foreman(): void
    {
        $foreman = $this->makeForeman(101, 'FM001', OtShiftGroup::NIGHT);
        $supervisor = OtDepartmentAssignment::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV004',
            'role' => OtDepartmentAssignment::ROLE_SUPERVISOR,
            'shift_group' => null,
            'app_user_id' => 201,
            'employee_code' => 'SV001',
        ]);

        $this->assertTrue($foreman->usesShiftGroup());
        $this->assertFalse($supervisor->usesShiftGroup(), 'Supervisor ดูแลทั้งแผนก ไม่ต้องมีป้ายกะ');
        $this->assertSame('กะเวลา B', $foreman->shiftGroupLabel());
    }

    public function test_foreman_can_be_assigned_to_all_shifts(): void
    {
        $foreman = $this->makeForeman(101, 'FM001', OtShiftGroup::ALL);

        $this->assertContains(OtShiftGroup::ALL, OtShiftGroup::keys());
        $this->assertSame(
            [OtShiftGroup::ALL, OtShiftGroup::MORNING, OtShiftGroup::NIGHT],
            array_column(OtShiftGroup::options(), 'key'),
            'Dropdown ต้องเรียง ทั้งหมด ก่อนกะเวลา A และกะเวลา B โดยไม่รวมตัวเลือกว่างที่หน้า UI เติมเอง',
        );
        $this->assertSame('ทั้งหมด', $foreman->shiftGroupLabel('th'));
        $this->assertSame('All shifts', $foreman->shiftGroupLabel('en'));
        $this->assertSame('ဆိုင်းအားလုံး', $foreman->shiftGroupLabel('my'));
        $this->assertSame(OtShiftGroup::UNKNOWN, OtShiftGroup::of(OtShiftGroup::ALL));
    }

    public function test_foreman_assignment_maps_to_the_initial_request_shift_filter(): void
    {
        $this->assertSame('g:morning', OtShiftGroup::filterValue(OtShiftGroup::MORNING));
        $this->assertSame('g:night', OtShiftGroup::filterValue(OtShiftGroup::NIGHT));
        $this->assertSame(OtShiftGroup::ALL, OtShiftGroup::filterValue(OtShiftGroup::ALL));
        $this->assertSame(OtShiftGroup::ALL, OtShiftGroup::filterValue(null));
        $this->assertSame(OtShiftGroup::ALL, OtShiftGroup::filterValue('unknown-value'));
    }

    /** กะแต่ละแบบย่อยของ Bplus (วันงาน / วันหยุด / นักขัตฤกษ์) ต้องตกกลุ่มเดียวกัน */
    public function test_shift_variants_resolve_to_the_same_group(): void
    {
        foreach (['AD03', 'AD03O', 'AD03OX'] as $code) {
            $this->assertSame(OtShiftGroup::MORNING, OtShiftGroup::of($code), $code);
        }

        foreach (['AN03', 'AN03O', 'SN06', 'AC05'] as $code) {
            $this->assertSame(OtShiftGroup::NIGHT, OtShiftGroup::of($code), $code);
        }
    }

    public function test_unknown_shift_is_never_guessed(): void
    {
        $this->assertSame(OtShiftGroup::UNKNOWN, OtShiftGroup::of('ZZ99'));
        $this->assertSame(OtShiftGroup::UNKNOWN, OtShiftGroup::of(null));
        $this->assertSame(OtShiftGroup::UNKNOWN, OtShiftGroup::of(''));
    }

    private function makeForeman(int $userId, string $code, ?string $shiftGroup): OtDepartmentAssignment
    {
        return OtDepartmentAssignment::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV004',
            'role' => OtDepartmentAssignment::ROLE_FOREMAN,
            'shift_group' => $shiftGroup,
            'app_user_id' => $userId,
            'employee_code' => $code,
        ]);
    }
}
