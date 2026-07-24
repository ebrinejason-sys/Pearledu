<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpdeskTicket extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'assigned_to', 'subject', 'body',
        'status', 'priority', 'category', 'admin_notes', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
