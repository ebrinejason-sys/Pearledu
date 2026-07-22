<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoan extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'book_id', 'student_id', 'loaned_on', 'due_on', 'returned_on'];
    protected $casts = ['loaned_on' => 'date', 'due_on' => 'date', 'returned_on' => 'date'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
