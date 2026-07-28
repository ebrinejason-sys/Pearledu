<?php

namespace Tests\Unit;

use App\Models\HelpdeskTicket;
use App\Models\School;
use App\Models\User;
use App\Services\Authorization\HelpdeskScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private HelpdeskScope $scope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->scope = app(HelpdeskScope::class);
    }

    public function test_parent_sees_only_own_tickets(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        $own = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'subject' => 'My issue',
            'body' => 'Details',
            'status' => 'open',
        ]);
        $other = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'subject' => 'Teacher issue',
            'body' => 'Details',
            'status' => 'open',
        ]);

        $this->assertTrue($this->scope->canCreate($parent, $this->school->id));
        $this->assertFalse($this->scope->canManage($parent, $this->school->id));
        $this->assertTrue($this->scope->canView($parent, $this->school->id, $own));
        $this->assertFalse($this->scope->canView($parent, $this->school->id, $other));
        $this->assertTrue($this->scope->canClose($parent, $this->school->id, $own));
        $this->assertFalse($this->scope->canClose($parent, $this->school->id, $other));
    }

    public function test_school_admin_can_manage_all_tickets(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $ticket = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'subject' => 'Parent issue',
            'body' => 'Details',
            'status' => 'open',
        ]);

        $this->assertTrue($this->scope->canManage($admin, $this->school->id));
        $this->assertTrue($this->scope->canView($admin, $this->school->id, $ticket));
        $this->assertTrue($this->scope->canClose($admin, $this->school->id, $ticket));
    }
}
