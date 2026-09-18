<?php

namespace App\Console\Commands\Insight;

use App\Models\Insight\AppUser;
use Illuminate\Console\Command;
use PDO;
use Throwable;

/**
 * ดึงข้อมูลโปรไฟล์ที่ผู้ใช้แก้บนเซิร์ฟเวอร์ (อีเมล / ลายเซ็น) กลับลงเครื่อง local
 *   php artisan insight:pull-profile [--host=] [--env=] [--prune] [--dry-run]
 *
 * - เซิร์ฟเวอร์เป็นแหล่งข้อมูลจริง (ดูกฎเดียวกับรูปพนักงานใน profiles:sync)
 * - ค่าเชื่อมต่ออ่านสดจาก .env ของเซิร์ฟตอนรัน ไม่เก็บรหัสผ่านไว้ในโปรเจกต์
 * - ค่าเริ่มต้นจะ "เติม/ทับเฉพาะค่าที่เซิร์ฟมี" ไม่ล้างของ local ที่เซิร์ฟว่าง
 *   ถ้าต้องการให้เหมือนกันเป๊ะ (ล้างของที่เซิร์ฟไม่มีด้วย) ใส่ --prune
 * - รูปโปรไฟล์ไม่รวมในคำสั่งนี้ ใช้ profiles:sync --force แทน เพราะเป็นไฟล์
 */
class PullProfileFieldsFromServer extends Command
{
    /** คอลัมน์ที่ผู้ใช้แก้เองผ่านหน้าโปรไฟล์ และเก็บค่าไว้ในตารางโดยตรง */
    private const FIELDS = ['email', 'signature'];

    protected $signature = 'insight:pull-profile
        {--host=192.168.7.12 : IP ของเซิร์ฟเวอร์ (ใน .env ของเซิร์ฟเขียนเป็น 127.0.0.1 ตามมุมมองตัวเอง)}
        {--server-env=\\\\192.168.7.12\\htdocs\\Insight\\.env : ที่อยู่ไฟล์ .env ของเซิร์ฟเวอร์}
        {--prune : ล้างค่าใน local ด้วยถ้าเซิร์ฟไม่มีค่านั้น}
        {--dry-run : แสดงผลอย่างเดียว ไม่บันทึก}';

    protected $description = 'ดึงอีเมลและลายเซ็นที่แก้บนเซิร์ฟเวอร์กลับลงฐานข้อมูล local';

    public function handle(): int
    {
        $envPath = (string) $this->option('server-env');
        $raw = @file_get_contents($envPath);
        if ($raw === false) {
            $this->error("อ่านไฟล์ .env ของเซิร์ฟไม่ได้: {$envPath}");

            return self::FAILURE;
        }

        $cfg = $this->parseEnv($raw);
        foreach (['DB_DATABASE', 'DB_USERNAME'] as $key) {
            if (($cfg[$key] ?? '') === '') {
                $this->error("ไฟล์ .env ของเซิร์ฟไม่มีค่า {$key}");

                return self::FAILURE;
            }
        }

        $host = (string) $this->option('host');
        $dryRun = (bool) $this->option('dry-run');
        $prune = (bool) $this->option('prune');

        try {
            $pdo = new PDO(
                'mysql:host='.$host.';port='.($cfg['DB_PORT'] ?? '3306').';dbname='.$cfg['DB_DATABASE'].';charset=utf8mb4',
                $cfg['DB_USERNAME'],
                $cfg['DB_PASSWORD'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10],
            );
        } catch (Throwable $exception) {
            $this->error('เชื่อมต่อฐานข้อมูลเซิร์ฟไม่ได้: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('เชื่อมต่อ '.$cfg['DB_DATABASE'].' @ '.$host.' สำเร็จ');

        $columns = implode(', ', self::FIELDS);
        $remote = $pdo->query("SELECT employee_code, {$columns} FROM app_users WHERE employee_code IS NOT NULL")
            ->fetchAll(PDO::FETCH_ASSOC);

        $changes = [];   // employee_code => [field => [เดิม, ใหม่]]
        $missing = 0;

        $locals = AppUser::query()
            ->whereNotNull('employee_code')
            ->get(['id', 'employee_code', ...self::FIELDS])
            ->keyBy(fn (AppUser $user) => (string) $user->employee_code);

        foreach ($remote as $row) {
            $code = (string) $row['employee_code'];
            $local = $locals->get($code);
            if (! $local) {
                $missing++;

                continue;
            }

            $patch = [];
            foreach (self::FIELDS as $field) {
                $serverValue = $row[$field];
                $localValue = $local->{$field};

                $serverHas = $serverValue !== null && $serverValue !== '';
                if (! $serverHas && ! $prune) {
                    continue;   // เซิร์ฟไม่มีค่า และไม่ได้สั่ง prune → ไม่แตะของ local
                }

                $normalized = $serverHas ? $serverValue : null;
                if (($localValue ?? null) === $normalized) {
                    continue;
                }

                $patch[$field] = $normalized;
                $changes[$code][$field] = [$this->preview($localValue), $this->preview($normalized)];
            }

            if ($patch !== [] && ! $dryRun) {
                $local->forceFill($patch)->save();
            }
        }

        $this->newLine();
        $this->line('  แถวบนเซิร์ฟ            : '.count($remote));
        $this->line('  ไม่มีรหัสนี้ใน local   : '.$missing);
        $this->line('  คนที่มีการเปลี่ยนแปลง : '.count($changes));

        foreach ($changes as $code => $fields) {
            foreach ($fields as $field => [$before, $after]) {
                $this->line(sprintf('    %-10s %-10s %s  ->  %s', $code, $field, $before, $after));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('โหมด --dry-run ยังไม่บันทึกลงฐานข้อมูล');
        }

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function parseEnv(string $raw): array
    {
        $cfg = [];
        foreach (preg_split('/\R/', $raw) as $line) {
            if (preg_match('/^\s*(DB_[A-Z_]+)\s*=\s*(.*)$/', $line, $m)) {
                $cfg[$m[1]] = trim($m[2], " \"'");
            }
        }

        return $cfg;
    }

    /** ลายเซ็นเป็น base64 ยาวมาก แสดงแค่ขนาดพอให้เห็นว่าเปลี่ยน */
    private function preview(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '(ว่าง)';
        }

        $text = (string) $value;

        return str_starts_with($text, 'data:image')
            ? '[ลายเซ็น '.number_format(mb_strlen($text)).' ตัวอักษร]'
            : mb_strimwidth($text, 0, 34, '…');
    }
}
