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

    /**
     * Teacher → class teacher. Reuses staff messages; no parallel social graph.
     */
    public function flagConcern(School $school, User $author, int $classId, string $body): StaffConversation
    {
        $classTeacherId = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->value('user_id');

        if (! $classTeacherId) {
            throw new RuntimeException('This class has no class teacher to message.');
        }
        if ((int) $classTeacherId === (int) $author->id) {
            throw new RuntimeException('You are the class teacher for this class.');
        }

        return $this->start($school, $author, [(int) $classTeacherId], $body, 'Learner concern');
    }

    /**
     * Class teacher → deputy / DOS / head.
     */
    public function escalateTo(School $school, User $author, string $roleKey, string $body): StaffConversation
    {
        $allowed = [Role::DEPUTY_HEAD_TEACHER, Role::DIRECTOR_OF_STUDIES, Role::HEAD_TEACHER];
        if (! in_array($roleKey, $allowed, true)) {
            throw new RuntimeException('Escalate to the deputy, Director of Studies, or Head Teacher.');
        }

        $userId = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('key', $roleKey))
            ->value('user_id');

        if (! $userId) {
            throw new RuntimeException('Nobody with that role is assigned at this school.');
        }
        if ((int) $userId === (int) $author->id) {
            throw new RuntimeException('You already hold that role.');
        }

        $labels = [
            Role::DEPUTY_HEAD_TEACHER => 'Escalation to Deputy Head',
            Role::DIRECTOR_OF_STUDIES => 'Escalation to Director of Studies',
            Role::HEAD_TEACHER => 'Escalation to Head Teacher',
        ];

        return $this->start($school, $author, [(int) $userId], $body, $labels[$roleKey]);
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
