<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnsureTwoFactorPendingTest extends TestCase
{
    public function test_challenge_route_redirects_to_login_without_pending_session(): void
    {
        $response = $this->get('/login/2fa/challenge');

        $response->assertRedirect('/login');
    }
}
