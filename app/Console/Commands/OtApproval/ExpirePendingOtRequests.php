<?php

namespace App\Console\Commands\OtApproval;

use App\Services\OtApproval\OtPendingExpiryService;
use Illuminate\Console\Command;

class ExpirePendingOtRequests extends Command
{
    protected $signature = 'ot-approval:expire-pending {--limit= : Maximum submitted OT requests to scan}';

    protected $description = 'Reject submitted OT requests that passed the payroll approval deadline.';

    public function handle(OtPendingExpiryService $service): int
    {
        $limit = $this->option('limit');
        $limit = $limit === null || $limit === '' ? null : max(1, (int) $limit);

        $result = $service->rejectExpired(now(), $limit);

        $this->info(sprintf(
            'Checked %d submitted OT request(s), auto-rejected %d.',
            $result['checked'],
            $result['rejected'],
        ));

        return self::SUCCESS;
    }
}
