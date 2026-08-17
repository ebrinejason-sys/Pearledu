<?php

namespace Tests\Unit;

use App\Services\Authorization\InvitePolicy;
use PHPUnit\Framework\TestCase;

class InvitePolicyTest extends TestCase
{
    public function test_platform_invitable_includes_deputy(): void
    {
        $this->assertContains('director_of_studies', InvitePolicy::PLATFORM_INVITABLE);
        $this->assertContains('deputy_head_teacher', InvitePolicy::PLATFORM_INVITABLE);
        $this->assertContains('school_admin', InvitePolicy::PLATFORM_INVITABLE);
        $this->assertContains('student', InvitePolicy::PLATFORM_INVITABLE);
        $this->assertContains('parent', InvitePolicy::PLATFORM_INVITABLE);
    }

    public function test_class_teacher_may_only_invite_parents(): void
    {
        $this->assertSame(['parent'], InvitePolicy::MATRIX['class_teacher']);
    }

    public function test_school_admin_may_invite_students(): void
    {
        $this->assertContains('student', InvitePolicy::MATRIX['school_admin']);
        $this->assertContains('student', InvitePolicy::MATRIX['head_teacher']);
    }

    public function test_director_of_studies_may_invite_teachers(): void
    {
        $this->assertContains('subject_teacher', InvitePolicy::MATRIX['director_of_studies']);
        $this->assertContains('class_teacher', InvitePolicy::MATRIX['head_teacher']);
        $this->assertContains('director_of_studies', InvitePolicy::MATRIX['school_admin']);
    }

    public function test_bursar_cannot_invite_staff(): void
    {
        $this->assertSame([], InvitePolicy::MATRIX['bursar']);
    }
}
