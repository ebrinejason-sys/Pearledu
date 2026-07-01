<?php
namespace App\Services\Provisioning;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\SchoolOffering;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolProvisioner {
    public function __construct(
        private SchoolTemplateSeeder $template,
        private AuditLogger $audit,
        private TenantContext $context,
    ) {}

    /**
     * Onboard a school atomically. Subdomain is AUTO-ASSIGNED as
     * {prefix}{id} -> pearledu1, pearledu2, ... .{base_domain}.
     * Returns the raw invite token (deliver out-of-band via mail/SMS).
     */
    public function onboard(array $school, array $levels, array $admin, ?int $operatorId): array {
        return DB::transaction(function () use ($school, $levels, $admin, $operatorId) {
            $this->context->forPlatform();

            $created = School::create([
                'name'        => $school['name'],
                'slug'        => 'tmp-'.Str::lower(Str::random(10)),
                'district'    => $school['district'] ?? null,
                'emis_number' => $school['emis_number'] ?? null,
                'theme'       => $school['theme'] ?? 'pearledu',
                'status'      => 'active',
                'created_by'  => $operatorId,
            ]);
            $created->update(['slug' => config('tenancy.tenant_slug_prefix').$created->id]);

            foreach (array_unique($levels) as $lvl) {
                SchoolOffering::create(['school_id' => $created->id, 'level' => $lvl]);
            }
            $this->template->seed($created, $levels);

            $adminUser = $this->resolveUser($admin);
            $roleId = Role::where('key', 'school_admin')->value('id');
            RoleAssignment::firstOrCreate([
                'user_id' => $adminUser->id, 'role_id' => $roleId,
                'school_id' => $created->id, 'is_active' => true,
            ], ['assigned_by' => $operatorId]);

            $raw = Str::random(48);
            SchoolInvitation::create([
                'school_id' => $created->id, 'user_id' => $adminUser->id,
                'email' => $admin['email'] ?? null, 'phone' => $admin['phone'] ?? null,
                'role_key' => 'school_admin', 'token_hash' => Hash::make($raw),
                'expires_at' => now()->addDays(7), 'invited_by' => $operatorId,
            ]);

            $this->audit->record('school.onboarded', $created, ['slug'=>$created->slug,'levels'=>$levels]);
            return ['school'=>$created, 'admin'=>$adminUser, 'invite_token'=>$raw];
        });
    }

    private function resolveUser(array $a): User {
        $u = null;
        if (!empty($a['email'])) $u = User::whereRaw('lower(email)=lower(?)', [$a['email']])->first();
        if (!$u && !empty($a['phone'])) $u = User::where('phone', $a['phone'])->first();
        return $u ?? User::create([
            'full_name'=>$a['full_name'], 'email'=>$a['email']??null,
            'phone'=>$a['phone']??null, 'status'=>'invited',
        ]);
    }
}
