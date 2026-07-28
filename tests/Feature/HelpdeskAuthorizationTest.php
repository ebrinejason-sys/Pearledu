<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\HelpdeskTicket;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user);
    }

    public function test_parent_index_hides_other_members_tickets(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'subject' => 'Parent ticket subject',
            'body' => 'Mine',
            'status' => 'open',
        ]);
        HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'subject' => 'Teacher secret ticket',
            'body' => 'Not for parents',
            'status' => 'open',
        ]);

        $response = $this->actingAsInSchool($parent)->get(route('app.helpdesk.index'));

        $response->assertOk();
        $response->assertSee('Parent ticket subject');
        $response->assertDontSee('Teacher secret ticket');
    }

    public function test_parent_cannot_close_another_members_ticket(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        $ticket = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'subject' => 'Teacher ticket',
            'body' => 'Body',
            'status' => 'open',
        ]);

        $this->actingAsInSchool($parent)
            ->post(route('app.helpdesk.close', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $ticket->id,
            'status' => 'open',
        ]);
    }

    public function test_parent_can_close_own_ticket(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $ticket = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'subject' => 'My ticket',
            'body' => 'Body',
            'status' => 'open',
        ]);

        $this->actingAsInSchool($parent)
            ->post(route('app.helpdesk.close', $ticket))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_admin_can_list_and_close_any_ticket(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $ticket = HelpdeskTicket::create([
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'subject' => 'Needs admin close',
            'body' => 'Body',
            'status' => 'open',
        ]);

        $this->actingAsInSchool($admin)
            ->get(route('app.helpdesk.index'))
            ->assertOk()
            ->assertSee('Needs admin close');

        $this->actingAsInSchool($admin)
            ->post(route('app.helpdesk.close', $ticket))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_parent_can_create_ticket(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $this->actingAsInSchool($parent)
            ->post(route('app.helpdesk.store'), [
                'subject' => 'New help request',
                'body' => 'Please help with login.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'school_id' => $this->school->id,
            'user_id' => $parent->id,
            'subject' => 'New help request',
            'status' => 'open',
        ]);
    }
}
