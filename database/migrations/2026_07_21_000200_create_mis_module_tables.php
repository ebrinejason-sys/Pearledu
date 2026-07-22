<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PearlEdu MIS modules (Sprints 1–12): academic structure through facilities/HR.
 * All school-scoped tables get FORCE RLS in the follow-up enable migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $t->string('name');
            $t->unsignedTinyInteger('sequence')->default(1);
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->timestamps();
            $t->unique(['academic_year_id', 'name']);
        });

        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $t->string('status')->default('active'); // active|completed|transferred|graduated|repeated
            $t->timestamps();
            $t->unique(['student_id', 'academic_year_id']);
        });

        Schema::create('attendance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->date('attended_on');
            $t->string('status'); // present|absent|late|excused
            $t->string('reason')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['student_id', 'attended_on']);
        });

        Schema::create('assessment_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $t->string('name');
            $t->decimal('max_score', 8, 2)->default(100);
            $t->boolean('is_locked')->default(false);
            $t->timestamps();
        });

        Schema::create('marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('assessment_period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->decimal('score', 8, 2)->nullable();
            $t->string('grade')->nullable();
            $t->text('comment')->nullable();
            $t->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['assessment_period_id', 'student_id', 'subject_id'], 'marks_period_student_subject_uq');
        });

        Schema::create('promotion_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('from_year_id')->constrained('academic_years')->cascadeOnDelete();
            $t->foreignId('to_year_id')->constrained('academic_years')->cascadeOnDelete();
            $t->string('status')->default('draft'); // draft|approved|committed
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('committed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('promotion_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('batch_id')->constrained('promotion_batches')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('from_class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->foreignId('to_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('outcome'); // promote|repeat|graduate|transfer
            $t->timestamps();
        });

        Schema::create('timetable_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name');
            $t->time('starts_at');
            $t->time('ends_at');
            $t->unsignedTinyInteger('sequence')->default(1);
            $t->timestamps();
        });

        Schema::create('rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name');
            $t->unsignedSmallInteger('capacity')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('timetable_slots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $t->unsignedTinyInteger('day_of_week'); // 1=Mon .. 7=Sun
            $t->foreignId('period_id')->constrained('timetable_periods')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'day_of_week', 'period_id', 'class_id'], 'tt_class_collision_uq');
            $t->unique(['school_id', 'day_of_week', 'period_id', 'teacher_id'], 'tt_teacher_collision_uq');
            $t->unique(['school_id', 'day_of_week', 'period_id', 'room_id'], 'tt_room_collision_uq');
        });

        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $t->string('name');
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3)->default('UGX');
            $t->timestamps();
        });

        Schema::create('fee_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();
            $t->string('reference')->nullable();
            $t->decimal('amount', 12, 2);
            $t->decimal('balance', 12, 2);
            $t->string('status')->default('open'); // open|partial|paid|void
            $t->date('due_on')->nullable();
            $t->timestamps();
        });

        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('method')->default('cash'); // cash|mtn_momo|airtel_money|bank
            $t->string('provider_ref')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('title');
            $t->text('body');
            $t->string('audience')->default('school'); // school|class|role|guardians
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('role_key')->nullable();
            $t->boolean('send_sms')->default(false);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('admission_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('applicant_name');
            $t->string('guardian_name')->nullable();
            $t->string('guardian_email')->nullable();
            $t->string('guardian_phone')->nullable();
            $t->foreignId('requested_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('status')->default('pending'); // pending|accepted|rejected|enrolled
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('lms_materials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->string('url')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('lms_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('title');
            $t->text('instructions')->nullable();
            $t->timestamp('due_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('cbt_exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $t->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $t->string('title');
            $t->unsignedSmallInteger('duration_minutes')->default(30);
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });

        Schema::create('cbt_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('exam_id')->constrained('cbt_exams')->cascadeOnDelete();
            $t->text('prompt');
            $t->json('choices');
            $t->string('correct_key');
            $t->decimal('points', 6, 2)->default(1);
            $t->timestamps();
        });

        Schema::create('library_books', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('title');
            $t->string('author')->nullable();
            $t->string('isbn')->nullable();
            $t->unsignedInteger('copies')->default(1);
            $t->timestamps();
        });

        Schema::create('library_loans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->date('loaned_on');
            $t->date('due_on')->nullable();
            $t->date('returned_on')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->unsignedInteger('quantity')->default(0);
            $t->string('location')->nullable();
            $t->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name');
            $t->string('vehicle')->nullable();
            $t->decimal('fee', 12, 2)->nullable();
            $t->timestamps();
        });

        Schema::create('hostel_rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->string('name');
            $t->unsignedSmallInteger('capacity')->default(4);
            $t->timestamps();
        });

        Schema::create('hostel_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('room_id')->constrained('hostel_rooms')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->timestamps();
            $t->unique(['student_id', 'room_id', 'starts_on']);
        });

        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('reason')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
        });

        Schema::create('clinic_visits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->timestamp('visited_at');
            $t->string('complaint')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('helpdesk_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('subject');
            $t->text('body');
            $t->string('status')->default('open');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $tables = [
            'helpdesk_tickets', 'clinic_visits', 'leave_requests', 'hostel_allocations', 'hostel_rooms',
            'transport_routes', 'inventory_items', 'library_loans', 'library_books', 'cbt_questions',
            'cbt_exams', 'lms_assignments', 'lms_materials', 'admission_applications', 'announcements',
            'fee_payments', 'fee_invoices', 'fee_structures', 'timetable_slots', 'rooms', 'timetable_periods',
            'promotion_items', 'promotion_batches', 'marks', 'assessment_periods', 'attendance_records',
            'enrollments', 'terms', 'academic_years',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
