<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Guards the core promise: RLS must not be bypassable. Fails if the app's DB
 * role is a SUPERUSER or has BYPASSRLS, or if any school-scoped table (discovered
 * from PostgreSQL's catalog via school_id, plus `schools`) lacks FORCE RLS.
 * Run in deploy/CI: `php artisan db:verify-security`.
 */
class VerifyDatabaseSecurity extends Command
{
    protected $signature = 'db:verify-security';

    protected $description = 'Assert the DB role cannot bypass Row-Level Security on every school-scoped table.';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('FAIL: db:verify-security requires PostgreSQL.');

            return self::FAILURE;
        }

        $role = DB::selectOne('SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = current_user');
        if (! $role || $role->rolsuper || $role->rolbypassrls) {
            $this->error('FAIL: current DB role is superuser/BYPASSRLS — RLS is bypassable. Use a plain role.');

            return self::FAILURE;
        }

        $tables = $this->schoolScopedTables();
        if ($tables === []) {
            $this->error('FAIL: no school-scoped tables found in the current schema.');

            return self::FAILURE;
        }

        $bad = [];
        foreach ($tables as $table) {
            $row = DB::selectOne(
                'SELECT c.relrowsecurity, c.relforcerowsecurity
                 FROM pg_class c
                 JOIN pg_namespace n ON n.oid = c.relnamespace
                 WHERE n.nspname = current_schema()
                   AND c.relkind = \'r\'
                   AND c.relname = ?',
                [$table]
            );

            if (! $row || ! $row->relrowsecurity || ! $row->relforcerowsecurity) {
                $bad[] = $table;
            }
        }

        if ($bad !== []) {
            $this->error('FAIL: RLS not forced on ('.count($bad).'): '.implode(', ', $bad));

            return self::FAILURE;
        }

        $this->info('OK: non-privileged role; FORCE RLS on '.count($tables).' school-scoped table(s).');

        return self::SUCCESS;
    }

    /**
     * Every base table in the current schema that has a school_id column,
     * plus the schools root table (scoped by id).
     *
     * @return list<string>
     */
    public function schoolScopedTables(): array
    {
        $rows = DB::select("
            SELECT c.relname AS table_name
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = current_schema()
              AND c.relkind = 'r'
              AND (
                c.relname = 'schools'
                OR EXISTS (
                    SELECT 1
                    FROM pg_attribute a
                    WHERE a.attrelid = c.oid
                      AND a.attname = 'school_id'
                      AND a.attnum > 0
                      AND NOT a.attisdropped
                )
              )
            ORDER BY c.relname
        ");

        return array_values(array_map(fn ($r) => (string) $r->table_name, $rows));
    }
}
