<?php
return ['roles' => [
    'school_admin'    => ['school.manage','accounts.manage','learners.manage','staff.manage','finance.view','assessment.view','emis.manage','sms.send','sms.manage'],
    'director'        => ['school.view','finance.view','assessment.view','reports.view','sms.send'],
    'head_teacher'    => ['school.view','staff.manage','assessment.manage','promotions.approve','attendance.view','sms.send'],
    'bursar'          => ['finance.manage','fees.record','fees.report','sms.send'],
    'class_teacher'   => ['attendance.mark','assessment.enter','class.view','sms.send'],
    'subject_teacher' => ['assessment.enter','marksheet.submit'],
    'parent'          => ['child.results.view','child.fees.view','fees.pay'],
    'student'         => ['self.results.view','self.timetable.view'],
]];
