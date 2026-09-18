<?php

namespace App\Http\Controllers\Insight;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use App\Services\Insight\AppUserSync;
use App\Services\Insight\BplusEmployeeImporter;
use App\Services\Insight\BplusPendingResignationSync;
use App\Services\Insight\HrPhotoImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View as ViewContract;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * ตั้งค่าระบบ (เฉพาะผู้ดูแลระบบ)
 *
 * คุมสิทธิ์ตามตำแหน่ง (job_code) 3 อย่าง — เก็บเป็น array ของ job_code
 *   - login     : ตำแหน่งที่อนุญาตให้ล็อกอิน
 *   - email     : ตำแหน่งที่อนุญาตให้กรอก/บันทึกอีเมล
 *   - signature : ตำแหน่งที่อนุญาตให้บันทึกลายเซ็น
 * null (ยังไม่เคยตั้ง) = อนุญาตทุกตำแหน่ง
 */
class SettingController extends Controller
{
    /** scope -> setting key */
    private const SCOPES = [
        'login' => Setting::LOGIN_POSITIONS,
        'email' => Setting::EMAIL_POSITIONS,
        'signature' => Setting::SIGNATURE_POSITIONS,
    ];

    /**
     * ลำดับการแสดงตำแหน่ง (ยึด job_en) — บนสุดสำคัญสุด, Student Trainer ล่างสุด
     *
     * @var string[]
     */
    private const POSITION_ORDER = [
        'CEO', 'President', 'CFO', 'General Manager', 'Deputy General Manager', 'Manager', 'Assist Manager',
        'Supervisor', 'Programmer Supervisor', 'Design Supervisor', 'Senior Engineer', 'Senior Design Engineer',
        'Senior Programmer Staff', 'Senior Staff', 'Senior Technician', 'Senior Operator', 'Engineer', 'Design Engineer',
        'Programmer Staff', 'Staff', 'Foreman', 'Leader', 'Technician', 'Operator', 'Driver (Executive)', 'Driver (Office)',
        'Driver (Logistic)', 'Cooking', 'Maid', 'Employee with Disabilities', 'Student Trainer',
    ];

    /** บริษัทหลักก่อน เหมือน rule เลือกรหัสหลักใน AppUserSync */
    private const EXPORT_COMPANY_PRIORITY = [
        'SUPAVUT_INDUSTRY' => 0,
        'MOLDVANTO' => 1,
        'SUPAVUT_INNOMED' => 2,
    ];

    public function index(): ViewContract
    {
        return view('insight.settings.index', [
            'me' => app('current_user'),
            'positions' => $this->orderedPositions(),
            'allowed' => [
                'login' => $this->allowedArray(Setting::LOGIN_POSITIONS),
                'email' => $this->allowedArray(Setting::EMAIL_POSITIONS),
                'signature' => $this->allowedArray(Setting::SIGNATURE_POSITIONS),
            ],
            // ผลรอบล่าสุดของ 2 ปุ่ม manual — แสดงค้างไว้ให้เปิดรายงานย้อนหลังได้
            'reports' => [
                'pull' => $this->reportMeta('pull'),
                'appusers' => $this->reportMeta('appusers'),
            ],
            // สถานะรูปพนักงาน — ให้ HR เห็นเองว่าเหลือใครยังไม่ได้ถ่าย และไฟล์ไหนหาเจ้าของไม่ได้
            'photos' => $this->photoStatus(app(HrPhotoImporter::class)),
        ]);
    }

    public function updatePositions(Request $request, string $scope): RedirectResponse
    {
        abort_unless(isset(self::SCOPES[$scope]), 404);

        $data = $request->validate([
            'positions' => ['array'],
            'positions.*' => ['string'],
        ]);

        $codes = array_values(array_unique(array_map('strval', $data['positions'] ?? [])));

        Setting::put(self::SCOPES[$scope], $codes);

        return redirect()->route('settings.index')->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว')->withFragment($scope);
    }

    /** บริษัท (Insight) => ฐานข้อมูลจริงบน Bplus */
    private const BPLUS_DATABASES = [
        'SUPAVUT_INDUSTRY' => 'BPLUSHRM_SUPAVUT_INDUSTRY',
        'MOLDVANTO' => 'BPLUSHRM_MOLDVANTO',
        'SUPAVUT_INNOMED' => 'BPLUSHRM_SUPAVUT_INNOMED',
    ];

    /** ชื่อบริษัทแบบอ่านง่าย ใช้แสดงในรายงานการเปลี่ยนแปลง */
    private const COMPANY_LABELS = [
        'SUPAVUT_INDUSTRY' => 'Supavut Industry',
        'MOLDVANTO' => 'Moldvanto',
        'SUPAVUT_INNOMED' => 'Supavut Innomed',
    ];

    /** คีย์ insight_settings เก็บผลรอบล่าสุดของ 2 ปุ่ม manual (message + เวลา + รายชื่อที่เปลี่ยน) */
    private const REPORT_KEYS = [
        'pull' => 'bplus_last_pull_report',
        'appusers' => 'bplus_last_sync_report',
    ];

    /** ปุ่มที่ 1 — ดึงพนักงานจาก Bplus เข้า employees (อัปเดตคนลาออก/ลบที่หายจาก Bplus ด้วย) */
    public function pullBplus(
        BplusEmployeeImporter $importer,
        BplusPendingResignationSync $pendingResignationSync,
    ): JsonResponse {
        $created = 0;
        $updated = 0;
        $resigned = 0;
        $deleted = 0;
        $total = 0;
        $pendingResignations = 0;
        $pendingMissing = [];
        $errors = [];
        $createdList = [];

        foreach (self::BPLUS_DATABASES as $company => $db) {
            try {
                Config::set('database.connections.bplus.database', $db);
                DB::purge('bplus');

                $rows = DB::connection('bplus')->select('SELECT * FROM dbo.EMP_MAIN');
                $r = $importer->import($rows, $company, false, true);
                $pending = $pendingResignationSync->sync(
                    $pendingResignationSync->fetchFromBplus(),
                    $company,
                );

                $created += $r['created'];
                $updated += $r['updated'];
                $resigned += count($r['resigned_list']);
                $deleted += $r['missing_deleted'];
                $total += $r['imported'];
                $pendingResignations += $pending['pending'];
                $pendingMissing = array_merge(
                    $pendingMissing,
                    array_map(
                        static fn (string $code): string => $company.':'.$code,
                        $pending['missing_codes'],
                    ),
                );

                $createdList = array_merge($createdList, $this->labelCompanies($r['created_list']));
            } catch (Throwable $e) {
                $errors[] = $company.': '.$e->getMessage();
            }
        }

        if (! empty($errors)) {
            return response()->json(['ok' => false, 'message' => 'ผิดพลาด: '.implode(' | ', $errors)], 500);
        }

        Setting::put(self::REPORT_KEYS['pull'], [
            'message' => "ดึงข้อมูลสำเร็จ · ใหม่ {$created} · อัปเดต {$updated} · ลาออก {$resigned}"
                ." · ลาออก (รอปิดงวด) {$pendingResignations}"
                .($pendingMissing !== [] ? ' · ไม่พบใน employees '.count($pendingMissing) : '')
                .($deleted > 0 ? " · ลบ(หายจาก Bplus) {$deleted}" : '')
                ." · รวม {$total} คน",
            'ran_at' => now()->toIso8601String(),
            'created_list' => $createdList,
            'pending_missing_codes' => $pendingMissing,
        ]);

        return response()->json($this->buildReport('pull'));
    }

    /** ปุ่มที่ 2 — ซิงค์บัญชีล็อกอิน app_users จาก employees (สร้างคน active / ลบคนลาออก) */
    public function syncAppUsers(AppUserSync $appUserSync): JsonResponse
    {
        try {
            $u = $appUserSync->sync();
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'ผิดพลาด: '.$e->getMessage()], 500);
        }

        Setting::put(self::REPORT_KEYS['appusers'], [
            'message' => "ซิงค์บัญชีล็อกอินสำเร็จ · สร้าง {$u['created']} · อัปเดต {$u['updated']} · ลบ(ลาออก) {$u['removed']}",
            'ran_at' => now()->toIso8601String(),
            'created_list' => $this->labelCompanies($u['created_list']),
            'removed_list' => $this->labelCompanies($u['removed_list']),
        ]);

        return response()->json($this->buildReport('appusers'));
    }

    /** เปิดรายงานรอบล่าสุดย้อนหลัง (ปุ่ม `ดูรายละเอียด` — ไม่ต้องรันใหม่) */
    public function report(string $kind): JsonResponse
    {
        abort_unless(isset(self::REPORT_KEYS[$kind]), 404);

        return response()->json($this->buildReport($kind));
    }

    /**
     * รายงานแบบแท็บ: รายชื่อที่เปลี่ยน (จากผลรอบล่าสุดที่เก็บไว้) + รายชื่อสถานะปัจจุบัน (query สด)
     *
     * @return array<string,mixed>
     */
    private function buildReport(string $kind): array
    {
        $stored = Setting::get(self::REPORT_KEYS[$kind], []);
        $stored = is_array($stored) ? $stored : [];
        $ranAt = (string) ($stored['ran_at'] ?? '');

        if ($kind === 'pull') {
            $ranAt = $this->latestEmployeeSyncAt($ranAt);
            $title = 'ดึงข้อมูลพนักงาน B Plus';
            // พนักงานใหม่ = query สดจากวันเริ่มงาน (ไม่ใช้ diff รอบกดปุ่ม — bplus:sync อัตโนมัติทุก 15 นาทีดูดเข้าก่อนเสมอ)
            $tabs = [
                ['key' => 'created', 'label' => 'พนักงานใหม่ (7 วัน)', 'tone' => 'add', 'extra' => 'วันเริ่มงาน', 'rows' => $this->newHireRows()],
                ['key' => 'all', 'label' => 'รวมทั้งหมด', 'tone' => 'flat', 'extra' => 'สถานะ', 'rows' => $this->employeeRows(null)],
                ['key' => 'working', 'label' => 'ทำงาน', 'tone' => 'flat', 'rows' => $this->employeeRows('working')],
                ['key' => 'pending_resign', 'label' => 'ลาออก (รอปิดงวด)', 'tone' => 'warning', 'extra' => 'วันที่มีผลลาออก', 'rows' => $this->employeeRows('pending_resign')],
                ['key' => 'resigned', 'label' => 'ลาออก', 'tone' => 'remove', 'extra' => 'วันลาออก', 'rows' => $this->employeeRows('resigned')],
            ];
        } else {
            // เวลาที่ซิงค์: แถว diff (ลบ) ใช้เวลา run รอบล่าสุด, แถว active ใช้ updated_at ต่อคน
            $ranLabel = $this->shortDateTime($ranAt);
            $title = 'ซิงค์บัญชีล็อกอิน';
            $tabs = [
                ['key' => 'created', 'label' => 'สร้างบัญชีใหม่ (7 วัน)', 'tone' => 'add', 'extra' => 'วันเริ่มงาน', 'rows' => $this->newAccountRows()],
                ['key' => 'updated', 'label' => 'อัปเดตบัญชี', 'tone' => 'flat', 'extra' => 'เวลาที่ซิงค์', 'rows' => $this->accountRows()],
                ['key' => 'removed', 'label' => 'ลบบัญชี (ลาออก)', 'tone' => 'remove', 'extra' => 'เวลาที่ซิงค์', 'rows' => $this->withExtra($stored['removed_list'] ?? [], $ranLabel)],
            ];
        }

        return [
            'ok' => true,
            'kind' => $kind,
            'title' => $title,
            'message' => (string) ($stored['message'] ?? ''),
            'ran_at' => $ranAt,
            'tabs' => $tabs,
        ];
    }

    /**
     * ใส่ค่าคอลัมน์ extra ให้ทุกแถว (สำหรับ snapshot ที่ทุกแถวใช้เวลาซิงค์เดียวกัน)
     *
     * @param  array<int,array<string,string>>  $rows
     * @return array<int,array<string,string>>
     */
    private function withExtra(array $rows, string $extra): array
    {
        return array_map(static fn (array $r): array => array_merge($r, ['extra' => $extra]), $rows);
    }

    /** message + เวลา ของรอบล่าสุด ไว้แสดงค้างในหน้า settings (null = ยังไม่เคยรัน) */
    private function reportMeta(string $kind): ?array
    {
        $stored = Setting::get(self::REPORT_KEYS[$kind], null);

        if (! is_array($stored) || ($stored['message'] ?? '') === '') {
            return null;
        }

        $ranAt = (string) ($stored['ran_at'] ?? '');
        if ($kind === 'pull') {
            $ranAt = $this->latestEmployeeSyncAt($ranAt);
        }

        return ['message' => (string) $stored['message'], 'ran_at' => $ranAt];
    }

    /**
     * รายชื่อพนักงานจาก mirror (query สด) — แยกสถานะปัจจุบันและสถานะ Bplus ที่รอปิดงวด
     * ลาออกเรียงวันลาออกจากล่าสุด → อดีต (ไม่มีวันลาออกไปท้าย)
     *
     * @return array<int,array<string,string>>
     */
    private function employeeRows(?string $status): array
    {
        $query = Employee::query();
        if ($status === 'working') {
            $query->active();
        } elseif ($status === 'pending_resign') {
            $query->pendingResignation();
        } elseif ($status === 'resigned') {
            $query->finalResigned();
        }

        // ลาออก: ล่าสุดก่อน (วันปัจจุบัน → อดีต) ; อื่น ๆ: เรียงตามรหัสพนักงาน
        if ($status === 'pending_resign') {
            $query->orderByRaw('pending_resign_date IS NULL')
                ->orderByDesc('pending_resign_date')
                ->orderBy('employee_code');
        } elseif ($status === 'resigned') {
            $query->orderByRaw('COALESCE(resign_date, manual_resign_date) IS NULL')
                ->orderByRaw('COALESCE(resign_date, manual_resign_date) DESC')
                ->orderBy('employee_code');
        } else {
            $query->orderBy('employee_code');
        }

        $rows = $query->get()->map(fn (Employee $e): array => [
            'code' => (string) $e->employee_code,
            'name' => $e->fullNameTh() ?: (string) ($e->name_en ?: '—'),
            'position' => (string) ($e->job_th ?: $e->job_en ?: '—'),
            'dept' => (string) ($e->deptThClean() ?: $e->dept_en ?: '—'),
            'company' => (string) $e->company,
            'extra' => match ($status) {
                null => match ($e->lifecycleStatus()) {
                    'pending_resign' => 'ลาออก (รอปิดงวด)',
                    'resigned' => 'ลาออก',
                    default => 'ทำงาน',
                },
                'pending_resign' => $this->shortDate($e->pending_resign_date),
                'resigned' => $this->shortDate($e->effectiveResignDate()),
                default => '',
            },
        ])->values()->all();

        return $this->labelCompanies($rows);
    }

    /** เวลาล่าสุดจากงาน scheduled/manual เพื่อไม่ให้หัวรายงานค้างอยู่ที่รอบกดปุ่มเก่า */
    private function latestEmployeeSyncAt(string $storedRanAt): string
    {
        $candidates = array_filter([
            $storedRanAt,
            (string) Employee::query()->max('synced_at'),
            (string) Employee::query()->max('pending_resign_synced_at'),
            (string) Employee::query()->max('manual_resign_set_at'),
        ]);

        if ($candidates === []) {
            return '';
        }

        return collect($candidates)
            ->sortByDesc(static fn (string $date): int => Carbon::parse($date)->getTimestamp())
            ->first();
    }

    /**
     * รายชื่อบัญชีที่ล็อกอินได้ทั้งหมดใน app_users (query สด) + เวลาที่ซิงค์ล่าสุดต่อคน (updated_at)
     *
     * @return array<int,array<string,string>>
     */
    private function accountRows(): array
    {
        $rows = AppUser::query()->orderBy('employee_code')->get()->map(fn (AppUser $u): array => [
            'code' => (string) $u->employee_code,
            'name' => (string) ($u->full_name_th ?: $u->full_name_en ?: '—'),
            'position' => (string) ($u->position ?: '—'),
            'dept' => (string) ($u->department ?: '—'),
            'company' => (string) $u->company,
            'extra' => $this->shortDateTime($u->updated_at),
        ])->values()->all();

        return $this->labelCompanies($rows);
    }

    /**
     * พนักงานที่เริ่มงานภายใน N วัน (คนที่เพิ่งรับเข้ามา) — เรียงวันเริ่มงานล่าสุดก่อน
     *
     * @return array<int,array<string,string>>
     */
    private function newHireRows(int $days = 7): array
    {
        $rows = Employee::active()
            ->where('hire_date', '>=', now()->subDays($days)->startOfDay())
            ->orderByDesc('hire_date')->orderBy('employee_code')
            ->get()
            ->map(fn (Employee $e): array => [
                'code' => (string) $e->employee_code,
                'name' => $e->fullNameTh() ?: (string) ($e->name_en ?: '—'),
                'position' => (string) ($e->job_th ?: $e->job_en ?: '—'),
                'dept' => (string) ($e->deptThClean() ?: $e->dept_en ?: '—'),
                'company' => (string) $e->company,
                'extra' => $this->shortDate($e->hire_date),
            ])->values()->all();

        return $this->labelCompanies($rows);
    }

    /**
     * บัญชีล็อกอินของพนักงานที่เริ่มงานภายใน N วัน — จับคู่จากรหัสในทุกบริษัทของบัญชี
     *
     * @return array<int,array<string,string>>
     */
    private function newAccountRows(int $days = 7): array
    {
        // employee_code => hire_date ของคนเริ่มงานใหม่
        $hires = Employee::active()
            ->where('hire_date', '>=', now()->subDays($days)->startOfDay())
            ->pluck('hire_date', 'employee_code');

        $rows = [];
        foreach (AppUser::query()->orderBy('employee_code')->get() as $u) {
            $hireDate = $hires[$u->employee_code] ?? null;
            if ($hireDate === null) {
                foreach ((array) ($u->companies ?? []) as $code) {
                    if (isset($hires[$code])) {
                        $hireDate = $hires[$code];
                        break;
                    }
                }
            }
            if ($hireDate === null) {
                continue;
            }

            $rows[] = [
                'code' => (string) $u->employee_code,
                'name' => (string) ($u->full_name_th ?: $u->full_name_en ?: '—'),
                'position' => (string) ($u->position ?: '—'),
                'dept' => (string) ($u->department ?: '—'),
                'company' => (string) $u->company,
                'sort_date' => (string) $hireDate,
                'extra' => $this->shortDate($hireDate),
            ];
        }

        // วันเริ่มงานล่าสุดก่อน
        usort($rows, fn (array $a, array $b): int => strcmp($b['sort_date'], $a['sort_date']));
        $rows = array_map(function (array $r): array {
            unset($r['sort_date']);

            return $r;
        }, $rows);

        return $this->labelCompanies($rows);
    }

    /** วันที่แบบสั้น d/m/Y (รับได้ทั้ง Carbon/string จาก Bplus) ; แปลงไม่ได้ = ค่าดิบ */
    private function shortDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    /** วันเวลาแบบสั้น d/m/Y H:i (เวลาที่ซิงค์) ; แปลงไม่ได้/ว่าง = '—' */
    private function shortDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    /**
     * แปลงรหัสบริษัทในรายชื่อเป็นชื่ออ่านง่าย (รองรับหลายบริษัทคั่นด้วย , จาก app_users)
     *
     * @param  array<int,array<string,string>>  $rows
     * @return array<int,array<string,string>>
     */
    private function labelCompanies(array $rows): array
    {
        return array_map(function (array $row): array {
            $labels = array_map(
                fn (string $c): string => self::COMPANY_LABELS[trim($c)] ?? trim($c),
                explode(',', (string) ($row['company'] ?? ''))
            );
            $row['company'] = implode(', ', array_filter($labels));

            return $row;
        }, $rows);
    }

    /** ดาวน์โหลดรายชื่อพนักงาน active เป็น Excel แบบไม่ซ้ำตัวตน — เติมชื่อ-สกุล(Eng) ที่ว่างให้ก่อน */
    /**
     * ปุ่ม "ดึงรูปใหม่ตอนนี้" ในหน้าตั้งค่า
     *
     * ปกติมี Scheduled Task ดึงให้อยู่แล้ว ปุ่มนี้ไว้ใช้ตอนเร่ง — HR เพิ่งวางรูปแล้วอยากเห็นเลย
     */
    public function pullPhotos(HrPhotoImporter $importer): JsonResponse
    {
        if (! $importer->sourceReadable()) {
            return response()->json([
                'ok' => false,
                'message' => 'เข้าโฟลเดอร์รูปของ HR ไม่ได้ — เครื่องที่รันระบบต้องต่อ share นี้ได้ก่อน',
                'path' => $importer->sourcePath(),
            ], 422);
        }

        try {
            $result = $importer->run(['trigger' => 'manual']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'ดึงรูปไม่สำเร็จ: '.$e->getMessage()], 500);
        }

        return response()->json([
            'ok' => $result['error'] === null,
            'result' => $result,
            'status' => $this->photoStatus($importer),
        ]);
    }

    /**
     * สถานะรูปพนักงานสำหรับหน้าตั้งค่า
     *
     * จงใจส่ง "รายชื่อ" มาด้วย ไม่ใช่แค่ตัวเลข เพราะถ้าบอกแค่จำนวน HR ก็ยังไม่รู้ว่าต้องตามใคร
     *
     * @return array<string,mixed>
     */
    private function photoStatus(HrPhotoImporter $importer): array
    {
        $active = Employee::active()
            ->orderByDesc('hire_date')
            ->orderBy('employee_code')
            ->get(['company', 'employee_code', 'title', 'name_th', 'surname_th', 'name_en', 'job_th', 'job_en', 'dept_th', 'dept_en', 'hire_date', 'photo_path']);

        $missing = $active->filter(fn (Employee $e): bool => trim((string) $e->photo_path) === '')
            ->map(fn (Employee $e): array => [
                'code' => (string) $e->employee_code,
                'company' => $e->company,
                'name_th' => $e->fullNameTh() ?: (string) $e->employee_code,
                'name_en' => $e->fullNameEn() ?: (string) $e->employee_code,
                'position_th' => $e->job_th ?: $e->job_en ?: '-',
                'position_en' => $e->job_en ?: $e->job_th ?: '-',
                'dept_th' => $e->dept_th ?: $e->dept_en ?: '-',
                'dept_en' => $e->dept_en ?: $e->dept_th ?: '-',
                'hire_date' => $e->hire_date?->format('d/m/Y') ?? '-',
            ])
            ->values();

        $orphans = DB::table('employee_photo_files')
            ->where('status', 'orphan')
            ->orderByDesc('source_modified_at')
            ->limit(200)
            ->get(['file_name', 'source_path', 'reason', 'source_modified_at'])
            ->map(fn ($row): array => [
                'file' => $row->file_name,
                'reason' => $row->reason,
                'modified' => $row->source_modified_at ? Carbon::parse($row->source_modified_at)->format('d/m/Y') : '-',
            ])
            ->all();

        $lastRun = DB::table('employee_photo_runs')->orderByDesc('id')->first();

        return [
            'source_path' => $importer->sourcePath(),
            'source_readable' => $importer->sourceReadable(),
            'active_total' => $active->count(),
            'with_photo' => $active->count() - $missing->count(),
            'missing' => $missing->all(),
            'missing_count' => $missing->count(),
            'orphans' => $orphans,
            'orphan_count' => (int) DB::table('employee_photo_files')->where('status', 'orphan')->count(),
            'last_run' => $lastRun ? [
                'at' => Carbon::parse($lastRun->finished_at ?? $lastRun->started_at)->format('d/m/Y H:i'),
                'trigger' => $lastRun->trigger,
                'imported' => (int) $lastRun->imported,
                'updated' => (int) $lastRun->updated,
                'unchanged' => (int) $lastRun->unchanged,
                'orphans' => (int) $lastRun->orphans,
                'failed' => (int) $lastRun->failed,
                'error' => $lastRun->error,
            ] : null,
        ];
    }

    public function exportEmployees(): StreamedResponse
    {
        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Employees');

        $headers = ['รหัสพนักงาน', 'ชื่อ-สกุล(ไทย)', 'ชื่อ-สกุล(Eng)', 'ตำแหน่ง', 'แผนก', 'เลขที่ประกันสังคม'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueExplicit([$i + 1, 1], $h, DataType::TYPE_STRING);
        }

        $row = 2;
        foreach ($this->employeesForExport() as $e) {
            $sheet->setCellValueExplicit([1, $row], (string) $e->employee_code, DataType::TYPE_STRING);
            $sheet->setCellValue([2, $row], $e->fullNameTh());
            $sheet->setCellValue([3, $row], $e->fullNameEn());   // เติมให้ครบถ้าว่าง
            $sheet->setCellValue([4, $row], (string) ($e->job_th ?: $e->job_en ?: ''));
            $sheet->setCellValue([5, $row], (string) ($e->deptThClean() ?: $e->dept_en ?: ''));
            $sheet->setCellValueExplicit([6, $row], (string) ($e->license_id ?? ''), DataType::TYPE_STRING);
            $row++;
        }

        $sheet->getStyle([1, 1, count($headers), 1])->getFont()->setBold(true);
        foreach (range(1, count($headers)) as $ci) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
        }

        $filename = 'employees_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($ss) {
            (new Xlsx($ss))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Export รายชื่อแบบ 1 ตัวตนต่อ 1 แถว: ถ้าเลขบัตรซ้ำข้ามบริษัท ให้เก็บบริษัทหลักก่อน
     * แต่พนักงานที่ไม่มีเลขบัตรยัง export ทุกแถวเพื่อไม่ให้ข้อมูลหายแบบเดาสุ่ม
     *
     * @return Employee[]
     */
    private function employeesForExport(): array
    {
        /** @var array<string,Employee> $selectedByLicense */
        $selectedByLicense = [];
        $withoutLicense = [];

        Employee::active()
            ->orderBy('employee_code')
            ->get()
            ->each(function (Employee $employee) use (&$selectedByLicense, &$withoutLicense) {
                $license = $this->exportLicenseKey($employee);
                if ($license === null) {
                    $withoutLicense[] = $employee;

                    return;
                }

                if (! isset($selectedByLicense[$license]) || $this->prefersExportEmployee($employee, $selectedByLicense[$license])) {
                    $selectedByLicense[$license] = $employee;
                }
            });

        $employees = array_merge(array_values($selectedByLicense), $withoutLicense);
        usort($employees, static fn (Employee $a, Employee $b): int => strcmp((string) $a->employee_code, (string) $b->employee_code));

        return $employees;
    }

    private function exportLicenseKey(Employee $employee): ?string
    {
        $license = trim((string) $employee->license_id);

        return $license === '' || $license === '-' || $license === '*' ? null : $license;
    }

    private function prefersExportEmployee(Employee $candidate, Employee $current): bool
    {
        $candidateRank = self::EXPORT_COMPANY_PRIORITY[$candidate->company] ?? 99;
        $currentRank = self::EXPORT_COMPANY_PRIORITY[$current->company] ?? 99;

        if ($candidateRank !== $currentRank) {
            return $candidateRank < $currentRank;
        }

        return strcmp((string) $candidate->employee_code, (string) $current->employee_code) < 0;
    }

    /** ตำแหน่งทั้งหมด (active) เรียงตาม POSITION_ORDER ; ที่ไม่อยู่ในลิสต์ไปท้ายสุด */
    private function orderedPositions(): array
    {
        $rank = array_change_key_case(array_flip(self::POSITION_ORDER), CASE_LOWER);

        $positions = Employee::active()
            ->selectRaw("COALESCE(NULLIF(job_code, ''), '__none__') as jcode,
                         MAX(job_th) as job_th, MAX(job_en) as job_en, count(*) as n")
            ->groupBy('jcode')
            ->get()
            ->map(fn ($p) => [
                'job_code' => $p->jcode === '__none__' ? '' : $p->jcode,
                'job_th' => $p->job_th,
                'job_en' => $p->job_en,
                'count' => (int) $p->n,
            ])->all();

        usort($positions, function ($a, $b) use ($rank) {
            $ra = $rank[mb_strtolower(trim((string) $a['job_en']))] ?? 999;
            $rb = $rank[mb_strtolower(trim((string) $b['job_en']))] ?? 999;

            return $ra <=> $rb ?: strcmp((string) $a['job_en'], (string) $b['job_en']);
        });

        return $positions;
    }

    private function allowedArray(string $key): ?array
    {
        $allowed = Setting::get($key, null);

        return is_array($allowed) ? $allowed : null;
    }
}
