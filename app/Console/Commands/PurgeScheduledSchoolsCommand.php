<?php

namespace App\Console\Commands;

use App\Services\Provisioning\SchoolDeletionService;
use Illuminate\Console\Command;
use Throwable;

class PurgeScheduledSchoolsCommand extends Command
{
    protected $signature = 'schools:purge-scheduled';

    protected $description = 'Permanently remove schools whose deletion retention window has elapsed';

    public function handle(SchoolDeletionService $deleter): int
    {
        $due = $deleter->dueForPurge();
        if ($due->isEmpty()) {
            $this->info('No schools are due for purge.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($due as $school) {
            try {
                $result = $deleter->purge($school, force: false);
                $this->info('Purged '.$result['name'].' (tenant #'.$result['tenant_id'].').');
            } catch (Throwable $e) {
                $failed++;
                report($e);
                $this->error('Failed to purge school #'.$school->id.': '.$e->getMessage());
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
