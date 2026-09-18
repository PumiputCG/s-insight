<?php

namespace Tests\Unit\Assessment;

use App\Models\Assessment\AsmRound;
use App\Models\Insight\AppUser;
use App\Services\Assessment\SelfAssessmentRoster;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelfAssessmentRosterTest extends TestCase
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
            $table->string('company')->nullable();
            $table->string('employee_code');
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

        Schema::connection('mysql_assessment')->create('asm_self_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code');
            $table->boolean('is_selected')->default(false);
            $table->unsignedBigInteger('selected_by')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();
            $table->unique(['round_id', 'employee_code']);
        });
        Schema::connection('mysql_assessment')->create('asm_position_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('job_code');
            $table->unsignedInteger('level');
            $table->timestamps();
        });
        Schema::connection('mysql_assessment')->create('asm_self_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->unsignedInteger('level');
            $table->unsignedInteger('sort');
            $table->text('q_th');
            $table->text('q_en');
            $table->text('q_my');
            $table->decimal('full_score', 8, 2)->default(5);
            $table->timestamps();
        });
        Schema::connection('mysql_assessment')->create('asm_self_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code');
            $table->unsignedInteger('level');
            $table->decimal('earned_score', 10, 2)->nullable();
            $table->decimal('possible_score', 10, 2)->nullable();
            $table->decimal('total_percent', 7, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql_assessment')->create('asm_self_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('choice_id');
            $table->unsignedInteger('question_no');
            $table->boolean('is_na');
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('full_score', 8, 2)->nullable();
            $table->decimal('percent', 7, 2)->nullable();
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

    public function test_only_selected_active_employee_can_assess(): void
    {
        $this->insertEmployee('10001', '1');
        $this->insertEmployee('70742', '2', '2026-07-01');
        $this->insertParticipant('10001', true);
        $this->insertParticipant('70742', true);

        $roster = app(SelfAssessmentRoster::class);

        $this->assertTrue($roster->canAssess($this->user('10001'), $this->round()));
        $this->assertFalse($roster->canAssess($this->user('70742'), $this->round()));
        $this->assertSame(1, $roster->selectedCount($this->round()));
    }

    public function test_unselected_employee_cannot_assess(): void
    {
        $this->insertEmployee('10001', '1');
        $this->insertParticipant('10001', false);

        $this->assertFalse(
            app(SelfAssessmentRoster::class)->canAssess($this->user('10001'), $this->round()),
        );
    }

    public function test_rows_use_current_employee_master_and_position_level(): void
    {
        $this->insertEmployee('10001', '1');
        $this->insertEmployee('70742', '2', '2026-07-01');
        $this->insertParticipant('10001', true);
        DB::connection('mysql_assessment')->table('asm_position_levels')->insert([
            'round_id' => 11,
            'job_code' => 'P1',
            'level' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = app(SelfAssessmentRoster::class)->rows($this->round());

        $this->assertCount(1, $rows);
        $this->assertSame('10001', $rows->first()['employee_code']);
        $this->assertSame(3, $rows->first()['level']);
        $this->assertTrue($rows->first()['is_selected']);
        $this->assertNull($rows->first()['self_score']);
    }

    public function test_save_changes_accepts_only_active_employee_master_codes(): void
    {
        $this->insertEmployee('10001', '1');
        $this->insertEmployee('70742', '2', '2026-07-01');
        $roster = app(SelfAssessmentRoster::class);

        $count = $roster->saveSelectionChanges($this->round(), [[
            'employee_code' => '10001',
            'is_selected' => true,
        ]], 9);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('asm_self_participants', [
            'round_id' => 11,
            'employee_code' => '10001',
            'is_selected' => true,
            'selected_by' => 9,
        ], 'mysql_assessment');

        $this->expectException(ValidationException::class);
        $roster->saveSelectionChanges($this->round(), [[
            'employee_code' => '70742',
            'is_selected' => true,
        ]], 9);
    }

    public function test_rows_and_result_question_count_include_saved_self_scores(): void
    {
        $this->insertEmployee('10001', '1');
        $this->insertParticipant('10001', true);
        $submissionId = DB::connection('mysql_assessment')->table('asm_self_submissions')->insertGetId([
            'round_id' => 11,
            'employee_code' => '10001',
            'level' => 3,
            'earned_score' => 4,
            'possible_score' => 5,
            'total_percent' => 80,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('mysql_assessment')->table('asm_self_answers')->insert([
            'submission_id' => $submissionId,
            'question_id' => 10,
            'choice_id' => 20,
            'question_no' => 1,
            'is_na' => false,
            'score' => 4,
            'full_score' => 5,
            'percent' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roster = app(SelfAssessmentRoster::class);
        $row = $roster->rows($this->round())->first();

        $this->assertSame(80.0, $row['self_score']);
        $this->assertSame([1 => 80.0], $row['question_scores']);
        $this->assertSame(1, $roster->resultQuestionCount($this->round()));
    }

    private function insertEmployee(string $code, string $status, ?string $resignDate = null): void
    {
        DB::table('employees')->insert([
            'company' => 'SUPAVUT',
            'employee_code' => $code,
            'title' => 'นาย',
            'name_th' => 'พนักงาน',
            'surname_th' => $code,
            'name_en' => 'Employee '.$code,
            'job_code' => 'P1',
            'job_th' => 'พนักงาน',
            'job_en' => 'Employee',
            'dept_code' => 'IT',
            'dept_th' => 'เทคโนโลยีสารสนเทศ',
            'dept_en' => 'Information Technology',
            'hire_date' => '2020-01-01',
            'resign_date' => $resignDate,
            'emp_status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertParticipant(string $code, bool $selected): void
    {
        DB::connection('mysql_assessment')->table('asm_self_participants')->insert([
            'round_id' => 11,
            'employee_code' => $code,
            'is_selected' => $selected,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function user(string $code): AppUser
    {
        return (new AppUser)->forceFill([
            'employee_code' => $code,
            'companies' => [],
        ]);
    }

    private function round(): AsmRound
    {
        return (new AsmRound)->forceFill([
            'id' => 11,
            'name' => 'ปี พ.ศ.2569',
            'year' => 2026,
            'type' => AsmRound::TYPE_SELF,
        ]);
    }
}
