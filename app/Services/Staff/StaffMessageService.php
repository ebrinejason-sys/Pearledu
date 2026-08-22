<?php

namespace App\Services\Staff;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\StaffConversation;
use App\Models\StaffConversationParticipant;
use App\Models\StaffMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class StaffMessageService
{
    /**
     * @return Collection<int, User>
     */
    public function staffDirectory(School $school): Collection
    {
        $ids = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
            ->pluck('user_id')
            ->unique();

        return User::query()->whereIn('id', $ids)->where('status', 'active')->orderBy('full_name')->get();
    }

    /**
     * @param  list<int>  $participantIds
     */
    public function start(School $school, User $author, array $participantIds, string $body, ?string $subject = null): StaffConversation
    {
        $ids = collect($participantIds)
            ->map(fn ($id) => (int) $id)
            ->push($author->id)
            ->unique()
            ->values();

        $allowed = $this->staffDirectory($school)->pluck('id');
        foreach ($ids as $id) {
            if (! $allowed->contains($id)) {
                throw new RuntimeException('Messages stay among this school’s staff.');
            }
        }

        $conversation = StaffConversation::create([
            'school_id' => $school->id,
            'subject' => $subject,
            'created_by' => $author->id,
        ]);

        foreach ($ids as $id) {
            StaffConversationParticipant::create([
                'school_id' => $school->id,
                'conversation_id' => $conversation->id,
                'user_id' => $id,
                'last_read_at' => $id === $author->id ? now() : null,
            ]);
        }

        $this->reply($conversation, $author, $body);

        return $conversation->fresh(['participants.user', 'messages.author']);
    }

    public function reply(StaffConversation $conversation, User $author, string $body): StaffMessage
    {
        $isParticipant = StaffConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $author->id)
            ->exists();
        if (! $isParticipant) {
            throw new RuntimeException('You are not in this conversation.');
        }

        $message = StaffMessage::create([
            'school_id' => $conversation->school_id,
            'conversation_id' => $conversation->id,
            'user_id' => $author->id,
            'body' => $body,
        ]);

        StaffConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $author->id)
            ->update(['last_read_at' => now()]);

        $conversation->touch();

        return $message;
    }

    /**
     * @return Collection<int, StaffConversation>
     */
    public function inbox(School $school, User $user)
    {
        return StaffConversation::query()
            ->where('school_id', $school->id)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('updated_at')
            ->get();
    }
}
