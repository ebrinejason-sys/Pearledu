<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two-layer tenant isolation — database half.
 *
 * Per-request session GUCs (set by ResolveTenant middleware):
 *   app.is_platform        'on' => platform operator (all tenants)
 *   app.current_school_id  the tenant a request/operator is scoped to
 *
 * FORCE ROW LEVEL SECURITY means even the table owner (the app's DB role) is
 * bound by these policies. There is deliberately NO bypass/service-role key:
 * "platform" visibility is itself a policy branch, not a privileged connection.
 * Fail-closed: with no GUC set, tenant tables return zero rows.
 */
return new class extends Migration {
    private array $childTables = [
        'school_offerings', 'school_domains', 'school_classes',
        'role_assignments', 'teaching_assignments', 'subjects',
        'students', 'guardianships', 'school_invitations', 'audit_logs',
        'sms_credit_ledger', 'sms_messages',
    ];

    public function up(): void {
        $pred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR school_id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";
        $schoolsPred = "(
            COALESCE(current_setting('app.is_platform', true), 'off') = 'on'
            OR id = NULLIF(current_setting('app.current_school_id', true), '')::bigint
        )";

        DB::statement("ALTER TABLE schools ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE schools FORCE ROW LEVEL SECURITY");
        DB::statement("CREATE POLICY tenant_isolation ON schools USING $schoolsPred WITH CHECK $schoolsPred");

        foreach ($this->childTables as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY tenant_isolation ON {$t} USING {$pred} WITH CHECK {$pred}");
        }
    }

    public function down(): void {
        foreach (array_merge(['schools'], $this->childTables) as $t) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$t}");
            DB::statement("ALTER TABLE {$t} NO FORCE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} DISABLE ROW LEVEL SECURITY");
        }
    }
};
