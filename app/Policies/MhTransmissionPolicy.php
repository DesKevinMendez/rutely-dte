<?php

namespace App\Policies;

use App\Models\MhTransmission;
use App\Models\User;
use App\Role;

class MhTransmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole([Role::ADMIN->value, Role::USER->value, Role::SUPERADMIN->value]);
    }

    public function retry(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole([Role::ADMIN->value, Role::SUPERADMIN->value]);
    }

    public function view(User $user, MhTransmission $transmission): bool
    {
        return $this->viewAny($user) && $user->company_id === $transmission->company_id;
    }
}
