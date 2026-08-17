<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'scope', 'label'];

    public const SCHOOL_ADMIN = 'school_admin';

    public const DIRECTOR = 'director';

    public const HEAD_TEACHER = 'head_teacher';

    public const DEPUTY_HEAD_TEACHER = 'deputy_head_teacher';

    public const DIRECTOR_OF_STUDIES = 'director_of_studies';

    public const BURSAR = 'bursar';

    public const CLASS_TEACHER = 'class_teacher';

    public const SUBJECT_TEACHER = 'subject_teacher';

    public const PARENT = 'parent';

    public const STUDENT = 'student';

    /** @var list<string> */
    public const SCHOOL = [
        self::SCHOOL_ADMIN,
        self::DIRECTOR,
        self::HEAD_TEACHER,
        self::DEPUTY_HEAD_TEACHER,
        self::DIRECTOR_OF_STUDIES,
        self::BURSAR,
        self::CLASS_TEACHER,
        self::SUBJECT_TEACHER,
        self::PARENT,
        self::STUDENT,
    ];

    /** Roles that may appear as timetable / teaching-assignment teachers. */
    public const TEACHING_CAPABLE = [
        self::SUBJECT_TEACHER,
        self::CLASS_TEACHER,
        self::HEAD_TEACHER,
        self::DEPUTY_HEAD_TEACHER,
        self::DIRECTOR_OF_STUDIES,
        self::SCHOOL_ADMIN,
    ];

    /** School-wide academic/ops leaders (unrestricted learner & register visibility). */
    public const SCHOOL_WIDE = [
        self::SCHOOL_ADMIN,
        self::DIRECTOR,
        self::HEAD_TEACHER,
        self::DEPUTY_HEAD_TEACHER,
        self::DIRECTOR_OF_STUDIES,
    ];
}
