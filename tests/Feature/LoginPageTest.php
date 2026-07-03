<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_the_tenant_schools_theme_not_the_default(): void
    {
        app(TenantContext::class)->forPlatform();
        School::create(['name' => 'EMIS Theme School', 'slug' => 'emistest1', 'theme' => 'emis', 'status' => 'active']);

        $response = $this->get('http://emistest1.voxsign.test/login');

        $response->assertStatus(200);
        $response->assertSee('--brand:#0B4DA2', false);
        $response->assertDontSee('--brand:#13443A', false);
    }

    public function test_login_page_renders_split_layout_with_avatar_stage(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('vx-auth-panel', false);
        $response->assertSee('vx-auth-stage', false);
        $response->assertSee('id="vx-login-avatar-3d"', false);
    }

    public function test_login_page_uses_theme_tokens_not_hardcoded_brand_colors(): void
    {
        $response = $this->get('/login');

        $response->assertSee('var(--sidebar)', false);
        $response->assertSee('var(--accent)', false);
        $response->assertDontSee('#FF6A3D', false);
        $response->assertDontSee('#12B3A6', false);
    }

    public function test_login_page_hides_avatar_stage_on_narrow_viewports(): void
    {
        $response = $this->get('/login');

        $response->assertSee('@media(max-width:860px)', false);
        $response->assertSee('.vx-auth-stage{display:none}', false);
    }

    public function test_login_form_still_renders_fields_and_csrf(): void
    {
        $response = $this->get('/login');

        $response->assertSee('name="_token"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
    }

    public function test_invalid_credentials_shows_error_message(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
