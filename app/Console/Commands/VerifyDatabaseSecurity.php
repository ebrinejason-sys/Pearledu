<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Guards the core promise: RLS must not be bypassable. Fails if the app's DB
 * role is a SUPERUSER or has BYPASSRLS, or if any tenant table lacks FORCE RLS.
 * Run in deploy/CI: `php artisan db:verify-security`.
 */
class VerifyDatabaseSecurity extends Command {
    protected $signature = 'db:verify-security';
    protected $description = 'Assert the DB role cannot bypass Row-Level Security.';

    public function handle(): int {
        $role = DB::selectOne("SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user");
        if ($role->rolsuper || $role->rolbypassrls) {
            $this->error('FAIL: current DB role is superuser/BYPASSRLS — RLS is bypassable. Use a plain role.');
            return self::FAILURE;
        }

        $tables = ['schools','students','role_assignments','sms_credit_ledger','sms_messages','guardianships'];
        $bad = [];
        foreach ($tables as $t) {
            $row = DB::selectOne("SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE relname = ?", [$t]);
            if (! $row || ! $row->relrowsecurity || ! $row->relforcerowsecurity) $bad[] = $t;
        }
        if ($bad) { $this->error('FAIL: RLS not forced on: '.implode(', ', $bad)); return self::FAILURE; }

        $this->info('OK: non-privileged role, FORCE RLS active. No bypass path.');
        return self::SUCCESS;
    }
}
