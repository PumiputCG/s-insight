<?php

namespace Tests\Unit\Assessment;

use App\Models\Assessment\AsmRound;
use App\Models\Insight\AppUser;
use App\Services\Assessment\HierarchyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HierarchyAccessTest extends TestCase
{
    private array $originalAssessmentConnection;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-28 09:00:00');
        $this->originalAssessmentConnection = config('database.connections.mysql_assessment', []);
        config()->set('database.connections.mysql_assessment', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('mysql_assessment');

        Schema::dropIfExists('employees');
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('company')->nullable();
            $table->string('employee_code');
            $table->string('license_id')->nullable();
            $table->string('title')->nullable();
            $table->string('name_th')->nullable();
            $table->string('surname_th')->nullable();
            $table->string('name_en')->nullable();
            $table->string('job_code')->nullable();
            $table->string('job_th')->nullable();
            $table->string('job_en')->nullable();
            $table->string('dept_code')->nullable();
            $table->string('dept_th')->nullable();
            $table->string('dept_en')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->string('emp_status');
            $table->timestamps();
        });

        Schema::connection('mysql_assessment')->create('asm_hierarchy', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code');
            foreach (range(1, 4) as $level) {
                $table->string('l'.$level.'_id')->nullable();
                $table->string('l'.$level.'_name')->nullable();
            }
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_assessment')->create('asm_score_boxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('type');
            $table->unsignedInteger('sort')->default(0);
            $table->string('input_levels')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_assessment')->create('asm_employee_scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code');
            $table->unsignedBigInteger('box_id');
            $table->decimal('value', 10, 4)->nullable();
            $table->decimal('value2', 10, 4)->nullable();
            $table->decimal('value3', 10, 4)->nullable();
            $table->decimal('value4', 10, 4)->nullable();
            $table->unsignedInteger('na_mask')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        DB::purge('mysql_assessment');
        config()->set('database.connections.mysql_assessment', $this->originalAssessmentConnection);

        parent::tearDown();
    }

    public function test_assignments_omit_resigned_targets_but_keep_active_targets(): void
    {
        $this->insertEmployee('10001', '1', null, 'พนักงานปัจจุบัน');
        $this->insertEmployee('70742', '2', '2026-07-01', 'พนักงานลาออก');
        $this->insertHierarchy('10001', '71056');
        $this->insertHierarchy('70742', '71056');

        $assignments = app(HierarchyAccess::class)->assignmentsFor(
            $this->reviewer('71056'),
            [1, 2],
            $this->round(),
        );

        $this->assertSame(['10001'], $assignments->pluck('employee_code')->all());
        $this->assertFalse($assignments->contains('employee_code', '70742'));
    }

    public function test_access_count_ignores_resigned_targets(): void
    {
        $this->insertEmployee('70742', '2', '2026-07-01', 'พนักงานลาออก');
        $this->insertHierarchy('70742', '71056');

        $access = app(HierarchyAccess::class)->accessFor($this->reviewer('71056'), $this->round());

        $this->assertFalse($access['can_evaluate']);
        $this->assertSame(0, $access['evaluate_count']);
    }

    public function test_assignments_expose_reviewer_identity_from_admin_hierarchy_and_merge_roles(): void
    {
        $this->insertEmployee('10001', '1', null, 'พนักงานปัจจุบัน');
        $this->insertEmployee('71019', '1', null, 'ชื่อจากมาสเตอร์');
        DB::table('employees')->where('employee_code', '71019')->update([
            'name_en' => 'Mister Evaluator Example',
            'job_th' => 'ผู้จัดการ',
            'job_en' => 'Manager',
            'dept_th' => 'HRD',
            'dept_en' => 'Human Resource Development',
        ]);
        $this->insertHierarchy('10001', '71019', '71019');

        $reviewer = $this->reviewer('71019');
        $reviewer->profile_picture = 'profiles/reviewer.jpg';
        $assignments = app(HierarchyAccess::class)->assignmentsFor(
            $reviewer,
            [1, 2],
            $this->round(),
        );

        $group = $assignments->first()['groups'][0];

        $this->assertSame([1, 2], $group['covers']);
        $this->assertSame('71019', $group['reviewer']['employee_code']);
        $this->assertSame('ผู้ประเมินทดสอบ', $group['reviewer']['name']);
        $this->assertSame('Mister Evaluator Example', $group['reviewer']['name_en']);
        $this->assertSame('ผู้จัดการ', $group['reviewer']['position']);
        $this->assertSame('Human Resource Development', $group['reviewer']['department_en']);
        $this->assertStringEndsWith('/storage/profiles/reviewer.jpg', $group['reviewer']['avatar']);
        $this->assertCount(1, $assignments->first()['evaluators']);
    }

    public function test_assignments_list_level_one_above_level_two_with_each_reviewers_status(): void
    {
        $this->insertEmployee('64045', '1', null, 'พนักงานปัจจุบัน');
        $this->insertEmployee('64519', '1', null, 'ผู้ประเมินลำดับหนึ่ง');
        $this->insertEmployee('71019', '1', null, 'ผู้ประเมินลำดับสอง');
        $this->insertHierarchy(
            '64045',
            '64519',
            '71019',
            'น.ส.นิลกมล ฝ่ายพาน',
            'นายฐานพัฒน์ พิมายกลาง',
        );

        $boxIds = [];
        foreach ([1, 2] as $sort) {
            $boxIds[] = DB::connection('mysql_assessment')->table('asm_score_boxes')->insertGetId([
                'parent_id' => 99,
                'type' => 'input',
                'sort' => $sort,
                'input_levels' => '1,2',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        foreach ($boxIds as $boxId) {
            DB::connection('mysql_assessment')->table('asm_employee_scores')->insert([
                'round_id' => 10,
                'employee_code' => '64045',
                'box_id' => $boxId,
                'value' => 8,
                'value2' => null,
                'na_mask' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $assignment = app(HierarchyAccess::class)->assignmentsFor(
            $this->reviewer('71019'),
            [1, 2],
            $this->round(),
        )->first();
        $evaluators = $assignment['evaluators'];

        $this->assertSame(['64519', '71019'], array_column(array_column($evaluators, 'reviewer'), 'employee_code'));
        $this->assertSame([[1], [2]], array_column($evaluators, 'covers'));
        $this->assertSame(['น.ส.นิลกมล ฝ่ายพาน', 'นายฐานพัฒน์ พิมายกลาง'], array_column(array_column($evaluators, 'reviewer'), 'name'));
        $this->assertTrue($evaluators[0]['state']['assessed']);
        $this->assertSame('done', $evaluators[0]['state']['status']);
        $this->assertFalse($evaluators[1]['state']['assessed']);
        $this->assertSame('pending', $evaluators[1]['state']['status']);
        $this->assertSame([2], $assignment['groups'][0]['covers']);
    }

    private function insertEmployee(string $code, string $status, ?string $resignDate, string $name): void
    {
        DB::table('employees')->insert([
            'company' => 'MOLDVANTO',
            'employee_code' => $code,
            'title' => 'น.ส.',
            'name_th' => $name,
            'surname_th' => 'ทดสอบ',
            'job_code' => 'P1',
            'job_th' => 'พนักงาน',
            'dept_code' => 'HRM',
            'dept_th' => 'ทรัพยากรบุคคล',
            'hire_date' => '2020-01-01',
            'resign_date' => $resignDate,
            'emp_status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertHierarchy(
        string $employeeCode,
        string $reviewerCode,
        ?string $secondReviewerCode = null,
        string $reviewerName = 'ผู้ประเมินทดสอบ',
        ?string $secondReviewerName = null,
    ): void {
        DB::connection('mysql_assessment')->table('asm_hierarchy')->insert([
            'round_id' => 10,
            'employee_code' => $employeeCode,
            'l1_id' => $reviewerCode,
            'l1_name' => $reviewerName,
            'l2_id' => $secondReviewerCode,
            'l2_name' => $secondReviewerCode === null ? null : ($secondReviewerName ?? $reviewerName),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function reviewer(string $code): AppUser
    {
        return new AppUser([
            'employee_code' => $code,
            'companies' => [],
            'role' => 'user',
        ]);
    }

    private function round(): AsmRound
    {
        $round = new AsmRound([
            'type' => AsmRound::TYPE_EMPLOYEE,
            'name' => 'ปี พ.ศ.2569',
            'year' => 2026,
            'status' => 'open',
        ]);
        $round->setAttribute('id', 10);
        $round->exists = true;

        return $round;
    }
}
