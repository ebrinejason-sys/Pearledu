<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catalog-driven catch-up: every table with school_id (plus schools) must have
 * ENABLE + FORCE RLS and a tenant_isolation policy. Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $pred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";

        $schoolsPred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";

        $tables = DB::select("
            SELECT c.relname AS table_name
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = current_schema()
              AND c.relkind = 'r'
              AND (
                c.relname = 'schools'
                OR EXISTS (
                    SELECT 1 FROM pg_attribute a
                    WHERE a.attrelid = c.oid
                      AND a.attname = 'school_id'
                      AND a.attnum > 0
                      AND NOT a.attisdropped
                )
              )
            ORDER BY c.relname
        ");

        foreach ($tables as $row) {
            $table = $row->table_name;
            $using = $table === 'schools' ? $schoolsPred : $pred;

            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("CREATE POLICY tenant_isolation ON {$table} USING {$using} WITH CHECK {$using}");
        }
    }

    public function down(): void
    {
        // Intentionally empty: removing FORCE RLS would weaken tenant isolation.
    }
};
