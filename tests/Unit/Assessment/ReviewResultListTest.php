<?php

namespace Tests\Unit\Assessment;

use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmRound;
use App\Models\Insight\Employee;
use App\Services\Assessment\ResultCalculator;
use App\Services\Assessment\ReviewResultList;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewResultListTest extends TestCase
{
    private array $originalAssessmentConnection;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-31 09:00:00');
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
            $table->string('employee_code');
            $table->string('job_code')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->string('emp_status');
            $table->timestamps();
        });

        Schema::connection('mysql_assessment')->create('asm_position_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('job_code');
            $table->unsignedInteger('level');
            $table->timestamps();
        });
        Schema::connection('mysql_assessment')->create('asm_score_boxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('type');
            $table->string('code')->nullable();
            $table->decimal('weight', 10, 4)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('att_form')->nullable();
            $table->decimal('rate', 10, 3)->nullable();
            $table->string('grade_cap')->nullable();
            $table->decimal('full_score', 10, 2)->nullable();
            $table->string('input_levels')->nullable();
            $table->text('scale_json')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql_assessment')->create('asm_level_props', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('level');
            $table->unsignedBigInteger('box_id');
            $table->string('mode');
            $table->decimal('weight', 10, 2);
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
        // คอลัมน์ "คะแนนตนเอง" ดึงจากรอบ self ที่เปิดอยู่ จึงต้องมี 2 ตารางนี้ด้วย
        Schema::connection('mysql_assessment')->create('asm_rounds', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->unsignedInteger('year');
            $table->string('status');
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->string('opened_by_name')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql_assessment')->create('asm_self_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code');
            $table->unsignedInteger('level')->nullable();
            $table->decimal('earned_score', 10, 2)->nullable();
            $table->decimal('possible_score', 10, 2)->nullable();
            $table->decimal('total_percent', 10, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
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

    public function test_review_list_uses_only_level_one_total_from_result_calculator(): void
    {
        DB::table('employees')->insert([
            'employee_code' => '64045',
            'job_code' => 'P2',
            'emp_status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('mysql_assessment')->table('asm_position_levels')->insert([
            'round_id' => 10,
            'job_code' => 'P2',
            'level' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $topBoxId = DB::connection('mysql_assessment')->table('asm_score_boxes')->insertGetId([
            'name' => 'Leadership',
            'type' => 'group',
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inputBoxId = DB::connection('mysql_assessment')->table('asm_score_boxes')->insertGetId([
            'parent_id' => $topBoxId,
            'name' => 'Leadership score',
            'type' => 'input',
            'sort' => 1,
            'full_score' => 10,
            'input_levels' => '1,2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('mysql_assessment')->table('asm_level_props')->insert([
            'level' => 2,
            'box_id' => $topBoxId,
            'mode' => 'percent',
            'weight' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('mysql_assessment')->table('asm_employee_scores')->insert([
            'round_id' => 10,
            'employee_code' => '64045',
            'box_id' => $inputBoxId,
            'value' => 8,
            'value2' => 5,
            'na_mask' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // รอบ self ที่เปิดอยู่ + คะแนนประเมินตัวเองของคนเดียวกัน (คนละรอบกับรอบประเมินพนักงาน)
        $selfRoundId = DB::connection('mysql_assessment')->table('asm_rounds')->insertGetId([
            'type' => 'self',
            'name' => 'รอบประเมินตัวเอง',
            'year' => 2026,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('mysql_assessment')->table('asm_self_submissions')->insert([
            'round_id' => $selfRoundId,
            'employee_code' => '64045',
            'level' => 2,
            'earned_score' => 37,
            'possible_score' => 56,
            'total_percent' => 66.07,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $round = (new AsmRound)->forceFill(['id' => 10]);
        $this->assertSame(2, (int) AsmPositionLevel::map(10)['P2']);
        $this->assertNotNull(Employee::active()->where('employee_code', '64045')->first());

        $calculator = new ResultCalculator;
        $calculated = $calculator->compute(2, [
            $inputBoxId => ['v' => 8.0, 'v2' => 5.0, 'v3' => null, 'v4' => null, 'na' => 0],
        ]);
        $this->assertSame(80.0, $calculated['totals'][1]);

        $rows = (new ReviewResultList($calculator))->enrich(collect([
            ['employee_code' => '64045'],
        ]), $round);

        $this->assertSame(80.0, $rows->first()['level_one_total']);
        $this->assertSame('80.00', $rows->first()['level_one_total_display']);
        $this->assertArrayNotHasKey('level_two_total', $rows->first());
        // คะแนนตนเอง = total_percent ของรอบ self ที่เปิดอยู่ (ค่าเดียวกับหน้า self?tab=results)
        $this->assertSame(66.07, $rows->first()['self_score']);
        $this->assertSame('66.07', $rows->first()['self_score_display']);
    }

    public function test_review_list_displays_dash_when_level_one_total_is_unavailable(): void
    {
        DB::table('employees')->insert([
            'employee_code' => '10001',
            'job_code' => 'P0',
            'emp_status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $round = (new AsmRound)->forceFill(['id' => 10]);
        $rows = app(ReviewResultList::class)->enrich(collect([
            ['employee_code' => '10001'],
        ]), $round);

        $this->assertNull($rows->first()['level_one_total']);
        $this->assertSame('-', $rows->first()['level_one_total_display']);
        // ยังไม่มีรอบ self เปิดอยู่ → คะแนนตนเองต้องเป็นขีด ไม่ error
        $this->assertNull($rows->first()['self_score']);
        $this->assertSame('-', $rows->first()['self_score_display']);
    }
}
