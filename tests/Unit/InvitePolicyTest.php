<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Authorization\InvitePolicy;
use PHPUnit\Framework\TestCase;

class InvitePolicyTest extends TestCase
{
    public function test_platform_invitable_includes_deputy(): void
    {
        $this->assertContains('deputy_head_teacher', InvitePolicy::PLATFORM_INVITABLE);
        $this->assertContains('school_admin', InvitePolicy::PLATFORM_INVITABLE);
    }

    public function test_class_teacher_may_only_invite_parents(): void
    {
        $this->assertSame(['parent'], InvitePolicy::MATRIX['class_teacher']);
    }

    public function test_bursar_cannot_invite_staff(): void
    {
        $this->assertSame([], InvitePolicy::MATRIX['bursar']);
    }
}
