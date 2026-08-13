<?php

namespace Tests\Unit;

use App\Models\SchoolInvitation;
use Tests\TestCase;

class SchoolInvitationExpiryTest extends TestCase
{
    public function test_is_expired_is_false_when_expires_at_is_missing(): void
    {
        $invite = new SchoolInvitation;
        $invite->expires_at = null;

        $this->assertFalse($invite->isExpired());
    }

    public function test_is_expired_when_date_is_in_the_past(): void
    {
        $invite = new SchoolInvitation;
        $invite->expires_at = now()->subMinute();

        $this->assertTrue($invite->isExpired());
    }
}
