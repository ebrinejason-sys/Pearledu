<?php

namespace App\Services\Authorization;

use App\Models\HelpdeskTicket;
use App\Models\User;

/**
 * Helpdesk: create / view own / manage school-wide.
 * Normal members only see and close their own tickets.
 */
class HelpdeskScope
{
    public function canManage(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'helpdesk.manage');
    }

    public function canCreate(User $user, int $schoolId): bool
    {
        return $this->canManage($user, $schoolId)
            || $this->has($user, $schoolId, 'helpdesk.create');
    }

    public function canViewOwn(User $user, int $schoolId): bool
    {
        return $this->canManage($user, $schoolId)
            || $this->has($user, $schoolId, 'helpdesk.view_own')
            || $this->has($user, $schoolId, 'helpdesk.create');
    }

    public function canAccessIndex(User $user, int $schoolId): bool
    {
        return $this->canCreate($user, $schoolId)
            || $this->canViewOwn($user, $schoolId)
            || $this->canManage($user, $schoolId);
    }

    public function canView(User $user, int $schoolId, HelpdeskTicket $ticket): bool
    {
        if ((int) $ticket->school_id !== $schoolId) {
            return false;
        }

        if ($this->canManage($user, $schoolId)) {
            return true;
        }

        return $this->canViewOwn($user, $schoolId)
            && (int) $ticket->user_id === (int) $user->id;
    }

    public function canClose(User $user, int $schoolId, HelpdeskTicket $ticket): bool
    {
        if ($ticket->status === 'closed') {
            return false;
        }

        return $this->canView($user, $schoolId, $ticket);
    }

    private function has(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
