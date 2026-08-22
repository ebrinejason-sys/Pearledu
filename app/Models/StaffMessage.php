<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMessage extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'conversation_id', 'user_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(StaffConversation::class, 'conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
