<?php

namespace App\Models\Insight;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * บัญชีล็อกอิน Insight (เฉพาะพนักงาน active — ลาออกแล้วลบทิ้งจริง ไม่เก็บ)
 *
 * - ตัวตน = เลขบัตรประชาชน (id_thai_hash) : 1 คน = 1 บัญชี แม้อยู่ 2-3 บริษัท
 * - login = รหัสพนักงาน + password (รหัสบัตรประชาชน หรือรหัสที่ admin กำหนด)
 *   resolve จากรหัสหลักและ map `companies` {"SUPAVUT_INDUSTRY":"71019","MOLDVANTO":"16899"}
 * - password เริ่มต้นของ user = เลขบัตรประชาชน (plaintext)
 * - system admin แยก: employee_code=Admin, password=000000
 * - **ไม่ซ่อน password/id_thai_hash** (คำสั่งเจ้าของ: admin ต้องเปิดดูได้กรณี user ลืมรหัส)
 *   จึงไม่มี $hidden และไม่ hash รหัสผ่าน
 */
class AppUser extends Authenticatable
{
    protected $table = 'app_users';

    protected $fillable = [
        'id_thai_hash', 'company', 'employee_code', 'companies', 'password', 'role',
        'full_name_th', 'full_name_en', 'position', 'department',
        'email', 'profile_picture', 'signature', 'reset_token', 'reset_token_expiry',
        'registered_at',
    ];

    protected $casts = [
        'companies' => 'array',
        'registered_at' => 'datetime',
        'reset_token_expiry' => 'datetime',
    ];

    /** role: บังคับให้เป็น admin หรือ user เท่านั้น */
    protected function role(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): string => strtolower(trim((string) $value)) === 'admin' ? 'admin' : 'user',
        );
    }

    /** พนักงานต้นทางใน employees (อ้างด้วยรหัสหลัก — บัญชีอาจคุมหลายบริษัท) */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_code', 'employee_code');
    }

    /** บริษัททั้งหมดที่บัญชีนี้คุม (จาก company CSV) */
    public function companyList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->company))));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * ยังมีสัญญาจ้างที่ active อย่างน้อยหนึ่งบริษัทหรือไม่
     * เก็บ app_users ไว้เพื่อรักษาข้อมูลเดิม แต่ไม่ให้คนที่พ้นสภาพครบทุกบริษัทล็อกอิน
     */
    public function hasActiveEmployment(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $companies = is_array($this->companies) ? $this->companies : [];
        if ($companies === []) {
            $company = trim((string) $this->company);
            $employeeCode = trim((string) $this->employee_code);
            if ($company !== '' && $employeeCode !== '') {
                $companies[$company] = $employeeCode;
            }
        }

        if ($companies === []) {
            return false;
        }

        return Employee::active()->where(function ($query) use ($companies): void {
            foreach ($companies as $company => $employeeCode) {
                $query->orWhere(function ($employment) use ($company, $employeeCode): void {
                    $employment
                        ->where('company', trim((string) $company))
                        ->where('employee_code', trim((string) $employeeCode));
                });
            }
        })->exists();
    }

    public function fullNameTh(): string
    {
        return trim((string) ($this->full_name_th ?? ''));
    }
}
