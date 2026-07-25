<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Force-logout a user across database sessions (and invalidate remember-me). */
class SessionInvalidator
{
    public function invalidate(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Rotate remember token so "remember me" cookies stop working.
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }
}
