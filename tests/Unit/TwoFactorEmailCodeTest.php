<?php

namespace Tests\Unit;

use App\Models\TwoFactorEmailCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorEmailCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_valid_when_unused_and_unexpired(): void
    {
        $user = User::factory()->create();
        $code = TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => bcrypt('481093'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($code->isValid());
    }

    public function test_is_invalid_when_expired(): void
    {
        $user = User::factory()->create();
        $code = TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => bcrypt('481093'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse($code->isValid());
    }

    public function test_is_invalid_when_used(): void
    {
        $user = User::factory()->create();
        $code = TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code_hash' => bcrypt('481093'),
            'expires_at' => now()->addMinutes(10),
            'used_at' => now(),
        ]);

        $this->assertFalse($code->isValid());
    }
}
