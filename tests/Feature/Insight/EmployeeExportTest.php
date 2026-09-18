<?php

namespace Tests\Feature\Insight;

use App\Http\Controllers\Insight\DashboardController;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use App\Services\Insight\AppUserSync;
use App\Services\Insight\BplusEmployeeImporter;
use App\Services\Insight\BplusLeaveRightService;
use App\Services\OtApproval\OtNotificationService;
use Carbon\CarbonInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EmployeeExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('app_users');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('insight_settings');

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('no')->nullable();
            $table->string('company', 30);
            $table->string('employee_code', 30);
            $table->string('license_id', 30)->nullable();
            $table->string('title', 30)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('name_th', 100)->nullable();
            $table->string('surname_th', 100)->nullable();
            $table->string('name_en', 150)->nullable();
            $table->string('job_code', 30)->nullable();
            $table->string('job_th', 191)->nullable();
            $table->string('job_en', 191)->nullable();
            $table->string('dept_code', 20)->nullable();
            $table->string('dept_th', 191)->nullable();
            $table->string('dept_en', 191)->nullable();
            $table->string('branch_code', 20)->nullable();
            $table->string('branch_th', 120)->nullable();
            $table->string('branch_en', 120)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->unsignedBigInteger('pending_resign_transaction_key')->nullable();
            $table->timestamp('pending_resign_synced_at')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->string('manual_resign_reason')->nullable();
            $table->timestamp('manual_resign_set_at')->nullable();
            $table->string('emp_status', 10)->nullable();
            $table->json('source_raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->string('id_thai_hash')->nullable()->unique();
            $table->string('company');
            $table->string('employee_code', 30);
            $table->json('companies')->nullable();
            $table->string('password')->nullable();
            $table->string('role', 50)->default('user');
            $table->string('full_name_th', 255)->nullable();
            $table->string('full_name_en', 255)->nullable();
            $table->string('position', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('reset_token', 100)->nullable();
            $table->timestamp('reset_token_expiry')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('insight_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function test_employee_export_keeps_one_active_row_per_license_preferring_supavut_industry(): void
    {
        $admin = AppUser::create([
            'id_thai_hash' => 'SYSTEM_ADMIN',
            'company' => 'INSIGHT',
            'employee_code' => 'Admin',
            'companies' => ['INSIGHT' => 'Admin'],
            'password' => '000000',
            'role' => 'admin',
            'full_name_th' => 'ผู้ดูแลระบบ Insight',
        ]);

        Employee::create([
            'company' => 'MOLDVANTO',
            'employee_code' => '16899',
            'license_id' => '1100702509547',
            'title' => 'นาย',
            'name_th' => 'ฐานพัฒน์',
            'surname_th' => 'พิมายกลาง',
            'name_en' => 'Mister Thannapat Pimaiklang',
            'job_th' => 'Manager',
            'dept_th' => 'ROVENTO/IT',
            'emp_status' => '1',
        ]);

        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71019',
            'license_id' => '1100702509547',
            'title' => 'นาย',
            'name_th' => 'ฐานพัฒน์',
            'surname_th' => 'พิมายกลาง',
            'name_en' => 'Mister Thannapat Pimaiklang',
            'job_th' => 'Manager',
            'dept_th' => 'HRD',
            'emp_status' => '1',
        ]);

        Employee::create([
            'company' => 'MOLDVANTO',
            'employee_code' => '16900',
            'license_id' => '1329900288225',
            'title' => 'น.ส.',
            'name_th' => 'พิชญ์ศิณีรัตน์',
            'surname_th' => 'อึ้งกล้า',
            'name_en' => 'Miss Pichsineerat Uengkla',
            'job_th' => 'Staff',
            'dept_th' => 'ROVENTO/IT',
            'emp_status' => '1',
        ]);

        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '69739',
            'license_id' => '1329900288225',
            'title' => 'น.ส.',
            'name_th' => 'พิชญ์ศิณีรัตน์',
            'surname_th' => 'อึ้งกล้า',
            'name_en' => 'Miss Pichsineerat Uengkla',
            'job_th' => 'Staff',
            'dept_th' => 'HRM',
            'emp_status' => '1',
        ]);

        Employee::create([
            'company' => 'MOLDVANTO',
            'employee_code' => '70000',
            'license_id' => null,
            'title' => 'นาย',
            'name_th' => 'ไม่มีเลขบัตร',
            'surname_th' => 'ยังต้องออก',
            'job_th' => 'Staff',
            'dept_th' => 'IT',
            'emp_status' => '1',
        ]);

        $response = $this
            ->withSession(['insight_user_id' => $admin->id])
            ->get(route('settings.employees.export'));

        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'employees_export_');
        file_put_contents($path, $response->streamedContent());

        try {
            $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);
        } finally {
            @unlink($path);
        }

        $rowsByCode = [];
        foreach (array_slice($rows, 1) as $row) {
            if (($row['A'] ?? '') !== '') {
                $rowsByCode[(string) $row['A']] = $row;
            }
        }

        $this->assertArrayHasKey('71019', $rowsByCode);
        $this->assertArrayHasKey('69739', $rowsByCode);
        $this->assertArrayHasKey('70000', $rowsByCode);
        $this->assertArrayNotHasKey('16899', $rowsByCode);
        $this->assertArrayNotHasKey('16900', $rowsByCode);
        $this->assertSame('HRD', $rowsByCode['71019']['E']);
        $this->assertSame('HRM', $rowsByCode['69739']['E']);
    }

    public function test_bplus_report_separates_pending_resignations_and_shows_effective_date(): void
    {
        $admin = AppUser::create([
            'id_thai_hash' => 'SYSTEM_ADMIN',
            'company' => 'INSIGHT',
            'employee_code' => 'Admin',
            'companies' => ['INSIGHT' => 'Admin'],
            'password' => '000000',
            'role' => 'admin',
            'full_name_th' => 'ผู้ดูแลระบบ Insight',
        ]);

        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71457',
            'title' => 'นาย',
            'name_th' => 'พีรพงษ์',
            'surname_th' => 'พลางวัน',
            'job_th' => 'Operator',
            'dept_th' => 'Logistics - Domestic',
            'emp_status' => '1',
            'pending_resign_date' => '2026-08-04',
            'pending_resign_synced_at' => '2026-08-13 11:00:00',
        ]);

        Setting::put('bplus_last_pull_report', [
            'message' => 'ดึงข้อมูลสำเร็จ',
            'ran_at' => '2026-08-13T09:52:00+07:00',
        ]);

        $response = $this
            ->withSession(['insight_user_id' => $admin->id])
            ->get(route('settings.bplus.report', ['kind' => 'pull']));

        $response->assertOk()
            ->assertJsonPath('tabs.3.key', 'pending_resign')
            ->assertJsonPath('tabs.3.label', 'ลาออก (รอปิดงวด)')
            ->assertJsonPath('tabs.3.rows.0.code', '71457')
            ->assertJsonPath('tabs.3.rows.0.extra', '04/08/2026');
    }

    public function test_account_is_kept_but_access_requires_one_active_company(): void
    {
        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71457',
            'license_id' => '1234567890123',
            'emp_status' => '1',
            'pending_resign_date' => '2020-01-01',
        ]);
        $active = Employee::create([
            'company' => 'MOLDVANTO',
            'employee_code' => '17000',
            'license_id' => '1234567890123',
            'emp_status' => '1',
        ]);

        $user = AppUser::create([
            'id_thai_hash' => '1234567890123',
            'company' => 'SUPAVUT_INDUSTRY,MOLDVANTO',
            'employee_code' => '71457',
            'companies' => [
                'SUPAVUT_INDUSTRY' => '71457',
                'MOLDVANTO' => '17000',
            ],
            'password' => '1234567890123',
            'role' => 'user',
        ]);

        $this->assertTrue($user->hasActiveEmployment());

        $active->update(['pending_resign_date' => '2020-01-01']);
        $this->assertFalse($user->hasActiveEmployment());
        $this->assertDatabaseHas('app_users', ['id' => $user->id]);
    }

    public function test_bplus_sync_updates_employee_department_position_and_login_profile(): void
    {
        $importer = new BplusEmployeeImporter;
        $users = new AppUserSync;

        $base = [
            'EMP_CODE' => '72001',
            'LICENSE_ID' => 'TEST-LICENSE-72001',
            'NAME_T' => 'เปลี่ยนข้อมูล',
            'SURNAME_T' => 'ทดสอบ',
            'PRI_STATUS' => '1',
        ];

        $importer->import([$base + [
            'JOB_CODE' => 'S1',
            'JOB_T' => 'Staff',
            'DEPT_CODE' => 'SPV-HRM',
            'DEPT_T' => 'HRM',
        ]], 'SUPAVUT_INDUSTRY');
        $users->sync();

        $importer->import([$base + [
            'JOB_CODE' => 'M1',
            'JOB_T' => 'Manager',
            'DEPT_CODE' => 'SPV-IT',
            'DEPT_T' => 'Information Technology',
        ]], 'SUPAVUT_INDUSTRY');
        $users->sync();

        $this->assertDatabaseHas('employees', [
            'employee_code' => '72001',
            'job_code' => 'M1',
            'job_th' => 'Manager',
            'dept_code' => 'SPV-IT',
            'dept_th' => 'Information Technology',
        ]);
        $this->assertDatabaseHas('app_users', [
            'employee_code' => '72001',
            'position' => 'Manager',
            'department' => 'Information Technology',
        ]);
    }

    public function test_admin_overview_separates_payroll_closed_resignations_and_builds_filters(): void
    {
        $admin = AppUser::create([
            'id_thai_hash' => 'SYSTEM_ADMIN',
            'company' => 'INSIGHT',
            'employee_code' => 'Admin',
            'companies' => ['INSIGHT' => 'Admin'],
            'password' => '000000',
            'role' => 'admin',
        ]);
        app()->instance('current_user', $admin);

        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '70001',
            'name_th' => 'ปิดงวด',
            'surname_th' => 'แล้ว',
            'job_th' => 'Operator',
            'dept_th' => 'Production',
            'emp_status' => '2',
            'resign_date' => '2026-08-01',
        ]);
        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '70002',
            'name_th' => 'รอปิด',
            'surname_th' => 'งวด',
            'job_th' => 'Staff',
            'dept_th' => 'HRM',
            'emp_status' => '1',
            'pending_resign_date' => '2020-01-01',
        ]);
        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '69791',
            'name_th' => 'Manual',
            'surname_th' => 'Override',
            'job_th' => 'Operator',
            'dept_th' => 'Assembly',
            'emp_status' => '1',
            'manual_resign_date' => '2020-01-01',
        ]);
        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71001',
            'name_th' => 'วิภา',
            'surname_th' => 'สุขใจ',
            'name_en' => 'Wipa Sukjai',
            'job_code' => 'S1',
            'job_th' => 'Staff',
            'dept_code' => 'SPV-HRM',
            'dept_th' => 'HRM',
            'emp_status' => '1',
        ]);
        Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71002',
            'name_th' => 'สมชาย',
            'surname_th' => 'ผลิตดี',
            'job_code' => 'W1',
            'job_th' => 'Operator',
            'dept_code' => 'SPV-PROD',
            'dept_th' => 'Production',
            'emp_status' => '1',
        ]);

        $controller = new DashboardController;
        $view = $controller->adminOverview();
        $stats = $view->getData()['stats'];
        $rows = collect($stats['resigned_list'])->keyBy('code');

        $this->assertSame(1, $stats['payroll_closed']);
        $this->assertSame(1, $stats['pending_resignation']);
        $this->assertSame(3, $stats['resigned']);
        $this->assertTrue($rows['70001']['payroll_closed']);
        $this->assertFalse($rows['70002']['payroll_closed']);
        $this->assertFalse($rows['69791']['payroll_closed']);
        $this->assertTrue($rows['70002']['pending_resignation']);
        $this->assertFalse($rows['70001']['pending_resignation']);
        $this->assertContains('Production', $stats['resigned_departments']);
        $this->assertContains('Operator', $stats['resigned_positions']);
        $this->assertContains('SUPAVUT_INDUSTRY|SPV-HRM', collect($stats['active_departments'])->pluck('key')->all());
        $this->assertContains('S1', collect($stats['active_positions'])->pluck('code')->all());
        $this->assertStringContainsString('70001', $rows['70001']['search_text']);
        $this->assertStringContainsString('ปิดงวด แล้ว', $rows['70001']['search_text']);

        $searchResponse = $controller->searchActiveEmployees(Request::create('/admin/employees/search', 'GET', [
            'query' => 'วิภา สุขใจ',
            'department' => 'SUPAVUT_INDUSTRY|SPV-HRM',
            'position' => 'S1',
        ]));
        $searchData = $searchResponse->getData(true);
        $this->assertSame(1, $searchData['total']);
        $this->assertSame('71001', $searchData['employees'][0]['code']);
        $this->assertFalse($searchData['limited']);
        $this->assertSame(422, $controller->searchActiveEmployees(Request::create('/admin/employees/search'))->status());

        app()->instance(OtNotificationService::class, new class extends OtNotificationService
        {
            // ต้องตรงกับ signature จริงที่รับ category (กระดิ่งกับกล่องดาวน์โหลดนับแยกกัน)
            public function unreadCountFor(string $employeeCode, string|array|null $category = null): int
            {
                return 0;
            }
        });
        $html = $view->render();

        $closedButtonAt = strpos($html, 'data-resigned-open="closed"');
        $pendingButtonAt = strpos($html, 'data-resigned-open="pending"');
        $allButtonAt = strpos($html, 'data-resigned-open="all"');
        $this->assertNotFalse($closedButtonAt);
        $this->assertNotFalse($pendingButtonAt);
        $this->assertNotFalse($allButtonAt);
        $this->assertLessThan($pendingButtonAt, $allButtonAt);
        $this->assertLessThan($closedButtonAt, $pendingButtonAt);
        $this->assertStringContainsString('id="activeEmployeeSearchForm"', $html);
        $this->assertStringContainsString('id="activeEmployeeQuery"', $html);
        $this->assertStringContainsString('id="activeEmployeeDepartment"', $html);
        $this->assertStringContainsString('id="activeEmployeePosition"', $html);
        $this->assertStringContainsString('id="resignedQuery"', $html);
        $this->assertStringContainsString('id="resignedDepartment"', $html);
        $this->assertStringContainsString('id="resignedPosition"', $html);
        $this->assertStringContainsString('data-payroll-closed="1"', $html);
        $this->assertStringContainsString('data-payroll-closed="0"', $html);
        $this->assertStringContainsString('data-pending-resignation="1"', $html);
        $this->assertStringContainsString('data-pending-resignation="0"', $html);
        $this->assertStringContainsString('id="employeeDetailModal"', $html);
        $this->assertStringContainsString('data-employee-detail', $html);
        $this->assertStringContainsString('class="emp-avatar employee-profile-photo"', $html);
        $this->assertStringContainsString('data-image-preview data-image-src=', $html);
        $this->assertStringContainsString("imageViewer.classList.contains('is-open')", $html);
        $this->assertStringContainsString('/admin/employee/leave-rights', $html);
    }

    public function test_admin_can_load_an_active_employee_profile_with_bplus_leave_rights(): void
    {
        Employee::create([
            'company' => 'MOLDVANTO',
            'employee_code' => '71056',
            'license_id' => 'TEST-71056',
            'title' => 'นาย',
            'name_th' => 'ภูมิพัฒน์',
            'surname_th' => 'ไชยชาติ',
            'name_en' => 'Pumiput Chaichat',
            'job_th' => 'Staff',
            'dept_code' => 'LVT002',
            'dept_th' => 'IT',
            'hire_date' => '2024-01-02',
            'emp_status' => '1',
        ]);

        $service = new class extends BplusLeaveRightService
        {
            public function forEmployee(
                string $company,
                string $employeeCode,
                ?CarbonInterface $asOf = null,
            ): array {
                return [
                    'available' => true,
                    'year' => 2026,
                    'as_of' => '2026-08-13',
                    'generated_at' => '2026-08-13T12:00:00+07:00',
                    'error' => null,
                    'rows' => [[
                        'key' => '7',
                        'name_th' => 'ลาป่วย',
                        'name_en' => 'Sick',
                        'entitled' => 30.0,
                        'used' => 1.0,
                        'remaining' => 29.0,
                    ]],
                ];
            }
        };

        $response = (new DashboardController)->employeeLeaveRights(
            Request::create('/admin/employee/leave-rights', 'GET', [
                'company' => 'MOLDVANTO',
                'employee_code' => '71056',
            ]),
            $service,
        );
        $data = $response->getData(true);

        $this->assertSame('MOLDVANTO', $data['employee']['company']);
        $this->assertSame('71056', $data['employee']['code']);
        $this->assertSame('Pumiput Chaichat', $data['employee']['name_en']);
        $this->assertSame(29, $data['leave']['rows'][0]['remaining']);
    }
}
