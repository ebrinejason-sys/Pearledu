<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\School;
use App\Models\User;
use App\Services\Academics\TermCalendar;
use App\Services\Navigation\NavigationBuilder;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CoreWorkflowNavImportSetupTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    public function test_sidebar_hides_optional_modules_and_keeps_core(): void
    {
        $nav = app(NavigationBuilder::class)->build($this->admin);
        $labels = collect($nav['sections'])->flatMap(fn ($s) => collect($s['items'])->pluck('label'))->all();

        $this->assertContains('Students', $labels);
        $this->assertContains('Admissions', $labels);
        $this->assertContains('SMS', $labels);
        $this->assertNotContains('Hostel', $labels);
        $this->assertNotContains('Library', $labels);
        $this->assertNotContains('CBT', $labels);
    }

    public function test_term_suggestions_are_not_equal_thirds(): void
    {
        $terms = app(TermCalendar::class)->suggestThreeTerms('2026-02-02', '2026-12-04');
        $this->assertCount(3, $terms);
        $this->assertSame('Term I', $terms[0]['name']);
        $this->assertTrue($terms[0]['ends_on'] < $terms[1]['starts_on']);
        $this->assertTrue($terms[1]['ends_on'] < $terms[2]['starts_on']);
        $this->assertNotSame(
            (int) ((strtotime('2026-12-04') - strtotime('2026-02-02')) / 3),
            (int) ((strtotime($terms[0]['ends_on']) - strtotime($terms[0]['starts_on']))),
        );
    }

    public function test_csv_import_preview_and_commit(): void
    {
        $csv = "Name,Class,Parent Phone\nJohn Atwine,P4-IMP,0771111111\nMary Akello,P4-IMP,0752222222\n";
        $file = UploadedFile::fake()->createWithContent('learners.csv', $csv);

        $this->actingAs($this->admin)
            ->post(route('app.students.import.store'), ['file' => $file])
            ->assertRedirect(route('app.students.import'));

        $this->actingAs($this->admin)
            ->post(route('app.students.import.preview'), [
                'mapping' => ['full_name' => 0, 'class' => 1, 'parent_phone' => 2],
            ])
            ->assertRedirect(route('app.students.import'));

        $this->actingAs($this->admin)
            ->post(route('app.students.import.commit'))
            ->assertRedirect(route('app.students.index'));

        $this->assertDatabaseHas('students', ['full_name' => 'John Atwine', 'school_id' => $this->school->id]);
        $this->assertDatabaseHas('students', ['full_name' => 'Mary Akello', 'school_id' => $this->school->id]);
        $this->assertDatabaseHas('school_classes', ['code' => 'P4-IMP', 'school_id' => $this->school->id]);
    }

    public function test_setup_wizard_and_action_center_render(): void
    {
        $this->actingAs($this->admin)->get(route('app.setup.index'))
            ->assertOk()
            ->assertSee('Get this school ready')
            ->assertSee('Assign teachers to classes')
            ->assertSee('Create an assessment period');
        $this->actingAs($this->admin)->get(route('app.home'))->assertOk()->assertSee('Needs your attention', false);
    }

    public function test_academic_year_form_shows_term_dates(): void
    {
        $this->actingAs($this->admin)
            ->get(route('app.years.index'))
            ->assertOk()
            ->assertSee('terms[0][starts_on]', false)
            ->assertSee('Create Term I–III');
    }
}
