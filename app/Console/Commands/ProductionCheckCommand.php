<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Fail-closed production readiness gate for deploy / go-live.
 *
 * Exit code 0 = ready; non-zero = hard failures present.
 */
class ProductionCheckCommand extends Command
{
    protected $signature = 'app:production-check
        {--strict : Treat warnings as failures}
        {--skip-db : Skip db:verify-security (config-only checks)}';

    protected $description = 'Validate production env/config before trusting live school traffic';

    /** @var list<string> */
    private array $failures = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('PearlEdu production check');
        $this->newLine();

        $this->checkApp();
        $this->checkSession();
        $this->checkMail();
        $this->checkSms();
        $this->checkTenancy();
        $this->checkSeedDemo();
        $this->checkSchoolPay();
        $this->checkLogging();
        $this->checkQueue();

        if (! $this->option('skip-db')) {
            $this->checkDatabaseSecurity();
        } else {
            $this->warnings[] = 'Skipped db:verify-security (--skip-db).';
        }

        $this->newLine();
        foreach ($this->warnings as $warning) {
            $this->warn('WARN  '.$warning);
        }
        foreach ($this->failures as $failure) {
            $this->error('FAIL  '.$failure);
        }

        if ($this->failures === [] && ($this->warnings === [] || ! $this->option('strict'))) {
            $this->info('OK: production configuration looks ready.');
            $this->line('Still required on the server: working cron for `php artisan schedule:run`, Resend/Twilio/SchoolPay credentials as needed, and a successful deploy of main.');

            return self::SUCCESS;
        }

        if ($this->option('strict') && $this->warnings !== [] && $this->failures === []) {
            $this->error('Strict mode: warnings treated as failures.');
        }

        return self::FAILURE;
    }

    private function checkApp(): void
    {
        if (! app()->isProduction()) {
            $this->warnings[] = 'APP_ENV is "'.config('app.env').'" — re-run on the production server with APP_ENV=production.';
        }

        if (config('app.debug')) {
            $this->failOrWarn(
                app()->isProduction(),
                'APP_DEBUG is true — must be false in production (leaks stack traces).'
            );
        }

        $key = (string) config('app.key');
        if ($key === '' || ! Str::startsWith($key, 'base64:')) {
            $this->failures[] = 'APP_KEY is missing or invalid. Run php artisan key:generate on the server.';
        }

        $url = (string) config('app.url');
        if (app()->isProduction() && ! Str::startsWith($url, 'https://')) {
            $this->failures[] = 'APP_URL must be https://… in production (got '.$url.').';
        }
    }

    private function checkSession(): void
    {
        if (config('session.secure') !== true) {
            $this->failOrWarn(
                app()->isProduction(),
                'SESSION_SECURE_COOKIE must be true in production.'
            );
        }

        $domain = config('session.domain');
        if (app()->isProduction() && (! is_string($domain) || $domain === '' || ! str_starts_with($domain, '.'))) {
            $this->failures[] = 'SESSION_DOMAIN should be a shared cookie domain like .voxsign.co.ug';
        }

        if (! config('session.encrypt')) {
            $this->warnings[] = 'SESSION_ENCRYPT is off — prefer true for school MIS sessions.';
        }

        $lifetime = (int) config('session.lifetime', 30);
        if ($lifetime > 120) {
            $this->failOrWarn(
                app()->isProduction(),
                "SESSION_LIFETIME is {$lifetime} minutes — prefer 15–30 so idle staff are signed out on shared computers."
            );
        }
        if ($lifetime < 5) {
            $this->warnings[] = "SESSION_LIFETIME is {$lifetime} minutes — too short; staff will be signed out while working.";
        }
    }

    private function checkMail(): void
    {
        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->failOrWarn(
                app()->isProduction(),
                "MAIL_MAILER={$mailer} will not deliver invites or 2FA email OTPs."
            );
        }

        $from = (string) config('mail.from.address');
        if ($from === '' || str_contains($from, 'example.com')) {
            $this->failOrWarn(app()->isProduction(), 'MAIL_FROM_ADDRESS is empty or still an example address.');
        }

        $password = (string) config('mail.mailers.smtp.password');
        if (app()->isProduction() && $mailer === 'smtp' && $password === '') {
            $this->failures[] = 'MAIL_PASSWORD is empty — Resend SMTP will not send.';
        }
    }

    private function checkSms(): void
    {
        $driver = (string) config('sms.driver', 'fake');
        if (in_array($driver, ['fake', 'log'], true)) {
            // Soft: schools can go live without SMS; sends are blocked by ProductionBlockedGateway.
            $this->warnings[] = "SMS_DRIVER={$driver} does not deliver SMS. Set SMS_DRIVER=twilio (+ Twilio creds) before using Send SMS in production.";
        }

        if ($driver === 'twilio') {
            foreach (['sid' => 'TWILIO_ACCOUNT_SID', 'token' => 'TWILIO_AUTH_TOKEN', 'from' => 'TWILIO_FROM_NUMBER'] as $key => $env) {
                if (! filled(config('sms.twilio.'.$key))) {
                    $this->failures[] = "{$env} is required when SMS_DRIVER=twilio.";
                }
            }
        }
    }

    private function checkTenancy(): void
    {
        $base = (string) config('tenancy.base_domain');
        $pearledu = (string) config('tenancy.pearledu_landing_host');
        $landing = config('tenancy.landing_hosts', []);

        if (str_ends_with($base, '.test') || str_contains($base, 'localhost')) {
            $this->failOrWarn(app()->isProduction(), "TENANCY_BASE_DOMAIN looks local ({$base}).");
        }

        if (str_ends_with($pearledu, '.test') || str_contains($pearledu, 'localhost')) {
            $this->failOrWarn(app()->isProduction(), "TENANCY_PEARLEDU_LANDING_HOST looks local ({$pearledu}).");
        }

        if (! is_array($landing) || $landing === []) {
            $this->failures[] = 'TENANCY_LANDING_HOSTS is empty.';
        }
    }

    private function checkSeedDemo(): void
    {
        if (config('app.seed_demo_tenant')) {
            $this->failOrWarn(
                app()->isProduction(),
                'SEED_DEMO_TENANT is enabled — must be false in production.'
            );
        }

        if (filled(config('app.seed_test_school_password'))) {
            $this->failOrWarn(
                app()->isProduction(),
                'SEED_TEST_SCHOOL_PASSWORD is set — walkthrough seed passwords must not exist on a live server.'
            );
        }
    }

    private function checkSchoolPay(): void
    {
        $base = (string) config('schoolpay.base_url');
        if (str_contains($base, 'schoolpaytest') || str_contains($base, 'uatpaymentapi')) {
            $this->failOrWarn(
                app()->isProduction(),
                'SCHOOLPAY_BASE_URL points at UAT/test — use https://schoolpay.co.ug/paymentapi for live fees.'
            );
        }
    }

    private function checkLogging(): void
    {
        $level = strtolower((string) config('logging.channels.stack.level', config('logging.default')));
        // Laravel stores level on the single/daily channel more often:
        $singleLevel = strtolower((string) (config('logging.channels.single.level') ?? 'debug'));
        if (in_array($singleLevel, ['debug', 'info'], true) && app()->isProduction()) {
            $this->warnings[] = "LOG_LEVEL is {$singleLevel} — prefer warning or error in production.";
        }

        $default = (string) config('logging.default');
        if ($default === 'stack') {
            $stack = config('logging.channels.stack.channels', []);
            if (is_array($stack) && in_array('single', $stack, true)) {
                $this->warnings[] = 'Logging uses the single channel — prefer daily rotation (LOG_STACK=daily) on the server.';
            }
        }
    }

    private function checkQueue(): void
    {
        $queue = (string) config('queue.default');
        if ($queue === 'sync' && app()->isProduction()) {
            $this->warnings[] = 'QUEUE_CONNECTION=sync runs jobs inline — OK without cron, but prefer database + schedule:run.';
        }

        $this->line('NOTE  Ensure cPanel cron runs: * * * * * cd /path/to/app && php artisan schedule:run');
    }

    private function checkDatabaseSecurity(): void
    {
        $code = Artisan::call('db:verify-security');
        $output = trim(Artisan::output());
        if ($code !== 0) {
            $this->failures[] = 'db:verify-security failed: '.$output;
        } else {
            $this->line('OK    '.$output);
        }
    }

    private function failOrWarn(bool $hard, string $message): void
    {
        if ($hard) {
            $this->failures[] = $message;
        } else {
            $this->warnings[] = $message;
        }
    }
}
