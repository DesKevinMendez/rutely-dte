<?php

namespace App\Policies;

use App\Models\ContingencyEvent;
use App\Models\User;
use App\Role;

class ContingencyEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canUseContingency($user);
    }

    public function view(User $user, ContingencyEvent $contingencyEvent): bool
    {
        return $this->canUseContingency($user) && $user->company_id === $contingencyEvent->company_id;
    }

    public function create(User $user): bool
    {
        return $this->canUseContingency($user);
    }

    public function update(User $user, ContingencyEvent $contingencyEvent): bool
    {
        return false;
    }

    public function delete(User $user, ContingencyEvent $contingencyEvent): bool
    {
        return false;
    }

    public function restore(User $user, ContingencyEvent $contingencyEvent): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContingencyEvent $contingencyEvent): bool
    {
        return false;
    }

    private function canUseContingency(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [Role::ADMIN->value, Role::USER->value, Role::SUPERADMIN->value], true);
    }
}
