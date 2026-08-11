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
        {--date= : Single YYYY-MM-DD (uses SyncSchoolTransactions)}
        {--from= : Range start YYYY-MM-DD (uses SchoolRangeTransactions; hash = fromDate)}
        {--to= : Range end YYYY-MM-DD (defaults to today)}
        {--days= : Lookback days ending today (max 31; uses SchoolRangeTransactions)}';

    protected $description = 'Pull SchoolPay transactions (sync/range) and apply missing fee receipts';

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

        [$mode, $from, $to] = $this->resolveWindow();
        $totals = ['applied' => 0, 'skipped' => 0, 'unmatched' => 0];

        foreach ($schools as $school) {
            try {
                $stats = $mode === 'day'
                    ? $schoolPay->syncDay($school, $from)
                    : $schoolPay->syncRange($school, $from, $to);
            } catch (\Throwable $e) {
                $this->error("School {$school->id}: ".$e->getMessage());

                continue;
            }

            $label = $mode === 'day' ? $from : "{$from}..{$to}";
            $this->line(sprintf(
                'School %d %s — applied=%d skipped=%d unmatched=%d',
                $school->id,
                $label,
                $stats['applied'],
                $stats['skipped'],
                $stats['unmatched'],
            ));

            foreach ($totals as $key => $_) {
                $totals[$key] += $stats[$key];
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

    /**
     * @return array{0:'day'|'range',1:string,2:string}
     */
    private function resolveWindow(): array
    {
        if ($single = $this->option('date')) {
            $date = Carbon::parse($single)->toDateString();

            return ['day', $date, $date];
        }

        if ($from = $this->option('from')) {
            $fromDate = Carbon::parse($from)->toDateString();
            $toDate = Carbon::parse($this->option('to') ?: now(config('app.timezone')))->toDateString();

            return ['range', $fromDate, $toDate];
        }

        $days = max(1, min(31, (int) ($this->option('days') ?: config('schoolpay.sync_lookback_days', 2))));
        $toDate = now(config('app.timezone'))->toDateString();
        $fromDate = now(config('app.timezone'))->subDays($days - 1)->toDateString();

        return $days === 1
            ? ['day', $toDate, $toDate]
            : ['range', $fromDate, $toDate];
    }
}
