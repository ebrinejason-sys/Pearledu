<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentifierLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_normalized_phone(): void
    {
        $this->makeSchoolUser([
            'full_name' => 'Phone User',
            'email' => 'phone-user@ci.test',
            'phone' => '+256712345678',
            'password' => Hash::make('secret-pass'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'identifier' => '0712345678',
            'password' => 'secret-pass',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_wrong_phone_password_fails(): void
    {
        User::create([
            'full_name' => 'Phone User',
            'phone' => '+256700111222',
            'password' => Hash::make('secret-pass'),
            'status' => 'active',
        ]);

        $response = $this->from('/login')->post('/login', [
            'identifier' => '0700111222',
            'password' => 'nope',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_school_user_without_membership_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'orphan@ci.test',
            'password' => Hash::make('secret-pass'),
            'status' => 'active',
        ]);

        $this->from('/login')->post('/login', [
            'identifier' => 'orphan@ci.test',
            'password' => 'secret-pass',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }
}
