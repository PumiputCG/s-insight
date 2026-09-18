<?php

namespace App\Console\Commands\Insight;

use App\Services\Insight\BplusPendingResignationSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/** ซิงค์เฉพาะสถานะลาออก (รอปิดงวด) โดยไม่ import/delete employees และไม่แตะ app_users */
class BplusPendingResignationSyncCommand extends Command
{
    protected $signature = 'bplus:sync-pending-resignations
        {--company= : ซิงค์เฉพาะบริษัทที่กำหนด}';

    protected $description = 'ซิงค์เฉพาะรายการลาออก (รอปิดงวด) จาก Bplus แบบ read-only';

    /** @var array<string,string> */
    private const DATABASES = [
        'SUPAVUT_INDUSTRY' => 'BPLUSHRM_SUPAVUT_INDUSTRY',
        'MOLDVANTO' => 'BPLUSHRM_MOLDVANTO',
        'SUPAVUT_INNOMED' => 'BPLUSHRM_SUPAVUT_INNOMED',
    ];

    public function handle(BplusPendingResignationSync $sync): int
    {
        $only = trim((string) $this->option('company'));
        $targets = self::DATABASES;

        if ($only !== '') {
            if (! isset($targets[$only])) {
                $this->error('ไม่รู้จักบริษัท: '.$only);

                return self::FAILURE;
            }

            $targets = [$only => $targets[$only]];
        }

        $hasError = false;
        foreach ($targets as $company => $database) {
            try {
                Config::set('database.connections.bplus.database', $database);
                DB::purge('bplus');

                $result = $sync->sync($sync->fetchFromBplus(), $company);
                $this->info(
                    "{$company}: รอปิดงวด {$result['pending']}"
                    ." / อัปเดต {$result['updated']}"
                    ." / ล้างรายการเดิม {$result['cleared']}"
                    ." / ไม่พบ {$result['missing']}"
                );

                if ($result['missing_codes'] !== []) {
                    $this->warn('  รหัสที่ไม่พบใน Insight: '.implode(', ', $result['missing_codes']));
                }
            } catch (Throwable $e) {
                $hasError = true;
                $this->error("{$company}: ".$e->getMessage());
            }
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }
}
