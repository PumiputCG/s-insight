<?php

namespace Tests\Feature\Insight;

use App\Models\Insight\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        Schema::dropIfExists('employees');
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('company', 30);
            $table->string('employee_code', 30);
            $table->string('license_id', 30)->nullable();
            $table->string('title', 30)->nullable();
            $table->string('name_th', 100)->nullable();
            $table->string('surname_th', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->unsignedBigInteger('pending_resign_transaction_key')->nullable();
            $table->timestamp('pending_resign_synced_at')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->string('manual_resign_reason')->nullable();
            $table->timestamp('manual_resign_set_at')->nullable();
            $table->string('emp_status', 10)->nullable();
            $table->timestamps();
        });

        Employee::unguarded(function (): void {
            Employee::create([
                'company' => 'SUPAVUT_INDUSTRY',
                'employee_code' => '71457',
                'emp_status' => '1',
                'pending_resign_date' => '2026-08-04',
            ]);
            Employee::create([
                'company' => 'SUPAVUT_INDUSTRY',
                'employee_code' => '71300',
                'emp_status' => '1',
                'pending_resign_date' => '2026-08-14',
            ]);
            Employee::create([
                'company' => 'SUPAVUT_INDUSTRY',
                'employee_code' => '71045',
                'emp_status' => '2',
                'resign_date' => '2026-07-19',
            ]);
            Employee::create([
                'company' => 'SUPAVUT_INDUSTRY',
                'employee_code' => '70001',
                'emp_status' => '1',
            ]);
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_pending_resignation_uses_effective_date_as_active_boundary(): void
    {
        $this->assertSame(
            ['70001', '71300'],
            Employee::active()->orderBy('employee_code')->pluck('employee_code')->all(),
        );
        $this->assertSame(
            ['71045', '71457'],
            Employee::resigned()->orderBy('employee_code')->pluck('employee_code')->all(),
        );

        $this->assertTrue(Employee::where('employee_code', '71457')->firstOrFail()->isResigned());
        $this->assertFalse(Employee::where('employee_code', '71300')->firstOrFail()->isResigned());
    }

    public function test_historical_scope_keeps_employee_before_effective_resignation_date(): void
    {
        $this->assertTrue(Employee::activeAt('2026-08-03')->where('employee_code', '71457')->exists());
        $this->assertFalse(Employee::activeAt('2026-08-04')->where('employee_code', '71457')->exists());

        $this->assertTrue(Employee::activeAt('2026-07-18')->where('employee_code', '71045')->exists());
        $this->assertFalse(Employee::activeAt('2026-07-19')->where('employee_code', '71045')->exists());
    }

    public function test_pending_tab_includes_future_effective_resignation_without_retiring_early(): void
    {
        $employee = Employee::where('employee_code', '71300')->firstOrFail();

        $this->assertSame('pending_resign', $employee->lifecycleStatus());
        $this->assertSame('2026-08-14', $employee->effectiveResignDate()?->toDateString());
        $this->assertTrue(Employee::pendingResignation()->whereKey($employee->id)->exists());
        $this->assertFalse(Employee::activeAt('2026-08-14')->whereKey($employee->id)->exists());
    }

    public function test_manual_resignation_override_survives_raw_active_status_and_keeps_history(): void
    {
        $employee = Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '69791',
            'emp_status' => '1',
            'manual_resign_date' => '2026-08-13',
            'manual_resign_reason' => 'Manager confirmed resignation',
        ]);

        $this->assertTrue(Employee::activeAt('2026-08-12')->whereKey($employee->id)->exists());
        $this->assertFalse(Employee::activeAt('2026-08-13')->whereKey($employee->id)->exists());
        $this->assertTrue(Employee::resignedAt('2026-08-13')->whereKey($employee->id)->exists());
        $this->assertTrue(Employee::finalResigned()->whereKey($employee->id)->exists());
        $this->assertFalse(Employee::payrollClosedResignation()->whereKey($employee->id)->exists());
        $this->assertSame('resigned', $employee->lifecycleStatus());
        $this->assertSame('2026-08-13', $employee->effectiveResignDate()?->toDateString());
    }
}
