<?php

namespace Tests\Unit;

use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    /** @return array<string, list<string>> */
    private function roles(): array
    {
        return config('permissions.roles');
    }

    public function test_director_of_studies_is_catalogued(): void
    {
        $this->assertArrayHasKey('director_of_studies', $this->roles());
    }

    public function test_student_can_view_own_fees_but_cannot_pay(): void
    {
        $perms = $this->roles()['student'];
        $this->assertContains('self.fees.view', $perms);
        $this->assertContains('self.attendance.view', $perms);
        $this->assertNotContains('fees.pay', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('finance.manage', $perms);
    }

    public function test_parent_pays_but_cannot_write_grades(): void
    {
        $perms = $this->roles()['parent'];
        $this->assertContains('fees.pay', $perms);
        $this->assertContains('child.fees.view', $perms);
        $this->assertContains('child.attendance.view', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('finance.manage', $perms);
    }

    public function test_subject_teacher_is_academic_scoped_not_finance(): void
    {
        $perms = $this->roles()['subject_teacher'];
        $this->assertContains('assessment.enter', $perms);
        $this->assertContains('attendance.mark', $perms);
        $this->assertContains('learners.view', $perms);
        $this->assertNotContains('finance.manage', $perms);
        $this->assertNotContains('assessment.manage', $perms);
        $this->assertNotContains('learners.manage', $perms);
        $this->assertNotContains('sms.send', $perms);
        $this->assertNotContains('staff.manage', $perms);
    }

    public function test_class_teacher_cannot_enter_marks_or_manage_finance(): void
    {
        $perms = $this->roles()['class_teacher'];
        $this->assertContains('assessment.view', $perms);
        $this->assertContains('attendance.mark', $perms);
        $this->assertContains('learners.view', $perms);
        $this->assertNotContains('sms.send', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('finance.manage', $perms);
        $this->assertNotContains('learners.manage', $perms);
        $this->assertContains('users.invite.parent', $perms);
        $this->assertContains('class.view', $perms);
    }

    public function test_director_of_studies_manages_academics_not_finance_or_hr(): void
    {
        $perms = $this->roles()['director_of_studies'];
        $this->assertContains('assessment.manage', $perms);
        $this->assertContains('assessment.enter', $perms);
        $this->assertContains('curriculum.manage', $perms);
        $this->assertContains('attendance.manage', $perms);
        $this->assertNotContains('finance.manage', $perms);
        $this->assertNotContains('finance.view', $perms);
        $this->assertNotContains('hr.manage', $perms);
        $this->assertNotContains('learners.manage', $perms);
        $this->assertContains('learner.academic.manage', $perms);
        $this->assertContains('enrollment.manage', $perms);
        $this->assertContains('staff.invite.teacher', $perms);
        $this->assertNotContains('staff.manage', $perms);
    }

    public function test_bursar_is_finance_only(): void
    {
        $perms = $this->roles()['bursar'];
        $this->assertContains('finance.manage', $perms);
        $this->assertContains('fees.payment.reverse', $perms);
        $this->assertContains('fees.invoice.void', $perms);
        $this->assertNotContains('assessment.view', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('learners.manage', $perms);
        $this->assertNotContains('hr.manage', $perms);
    }

    public function test_head_teacher_cannot_write_grades_or_fees(): void
    {
        $perms = $this->roles()['head_teacher'];
        $this->assertContains('assessment.view', $perms);
        $this->assertContains('finance.view', $perms);
        $this->assertContains('staff.manage', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('assessment.manage', $perms);
        $this->assertNotContains('finance.manage', $perms);
    }

    public function test_director_is_read_heavy_without_operational_writes(): void
    {
        $perms = $this->roles()['director'];
        $this->assertContains('finance.view', $perms);
        $this->assertContains('assessment.view', $perms);
        $this->assertContains('attendance.view', $perms);
        $this->assertContains('learners.view', $perms);
        $this->assertNotContains('finance.manage', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('attendance.mark', $perms);
        $this->assertNotContains('attendance.manage', $perms);
        $this->assertNotContains('learners.manage', $perms);
    }

    public function test_deputy_matches_head_except_promotions(): void
    {
        $perms = $this->roles()['deputy_head_teacher'];
        $this->assertContains('assessment.view', $perms);
        $this->assertContains('finance.view', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('finance.manage', $perms);
        $this->assertNotContains('promotions.approve', $perms);
    }

    public function test_secretary_is_front_office_without_finance_or_grades(): void
    {
        $perms = $this->roles()['secretary'];
        $this->assertContains('staff.id.print', $perms);
        $this->assertContains('staff.attendance.mark', $perms);
        $this->assertContains('staff.messages', $perms);
        $this->assertNotContains('finance.manage', $perms);
        $this->assertNotContains('finance.view', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('assessment.manage', $perms);
        $this->assertNotContains('attendance.mark', $perms);
    }

    public function test_director_views_payroll_but_cannot_manage_it(): void
    {
        $perms = $this->roles()['director'];
        $this->assertContains('hr.payroll.view', $perms);
        $this->assertContains('staff.attendance.view', $perms);
        $this->assertNotContains('hr.payroll.manage', $perms);
        $this->assertNotContains('staff.attendance.mark', $perms);
        $this->assertNotContains('finance.manage', $perms);
    }

    public function test_bursar_manages_payroll_without_grade_writes(): void
    {
        $perms = $this->roles()['bursar'];
        $this->assertContains('hr.payroll.manage', $perms);
        $this->assertContains('hr.payroll.view', $perms);
        $this->assertNotContains('assessment.enter', $perms);
        $this->assertNotContains('staff.attendance.mark', $perms);
    }
}
