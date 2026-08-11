<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\SchoolPay\SchoolPayPaymentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncSchoolPayTransactions extends Command
{
    protected $signature = 'schoolpay:sync
        {--school= : Optional school id}
        {--date= : Single YYYY-MM-DD (defaults to lookback window ending today)}
        {--days= : Override lookback days}';

    protected $description = 'Pull SchoolPay transactions and apply missing fee receipts';

    public function handle(SchoolPayPaymentService $schoolPay): int
    {
        $query = School::query()
            ->where('schoolpay_enabled', true)
            ->whereNotNull('schoolpay_school_code')
            ->whereNotNull('schoolpay_api_password');

        if ($this->option('school')) {
            $query->where('id', (int) $this->option('school'));
        }

        $schools = $query->get();
        if ($schools->isEmpty()) {
            $this->info('No SchoolPay-enabled schools found.');

            return self::SUCCESS;
        }

        $dates = $this->datesToSync();
        $totals = ['applied' => 0, 'skipped' => 0, 'unmatched' => 0];

        foreach ($schools as $school) {
            foreach ($dates as $date) {
                try {
                    $stats = $schoolPay->syncDay($school, $date);
                } catch (\Throwable $e) {
                    $this->error("School {$school->id} {$date}: ".$e->getMessage());

                    continue;
                }

                $this->line(sprintf(
                    'School %d %s — applied=%d skipped=%d unmatched=%d',
                    $school->id,
                    $date,
                    $stats['applied'],
                    $stats['skipped'],
                    $stats['unmatched'],
                ));

                foreach ($totals as $key => $_) {
                    $totals[$key] += $stats[$key];
                }
            }
        }

        $this->info(sprintf(
            'Done. applied=%d skipped=%d unmatched=%d',
            $totals['applied'],
            $totals['skipped'],
            $totals['unmatched'],
        ));

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function datesToSync(): array
    {
        if ($single = $this->option('date')) {
            return [Carbon::parse($single)->toDateString()];
        }

        $days = max(1, min(31, (int) ($this->option('days') ?: config('schoolpay.sync_lookback_days', 2))));
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = now(config('app.timezone'))->subDays($i)->toDateString();
        }

        return $dates;
    }
}
