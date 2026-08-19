<?php

namespace App\Policies;

use App\Models\Dte;
use App\Models\User;
use App\Role;

class DtePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canUseDtes($user);
    }

    public function view(User $user, Dte $dte): bool
    {
        return $this->canUseDtes($user) && $user->company_id === $dte->company_id;
    }

    public function create(User $user): bool
    {
        return $this->canUseDtes($user);
    }

    public function update(User $user, Dte $dte): bool
    {
        return false;
    }

    public function delete(User $user, Dte $dte): bool
    {
        return false;
    }

    public function restore(User $user, Dte $dte): bool
    {
        return false;
    }

    public function forceDelete(User $user, Dte $dte): bool
    {
        return false;
    }

    private function canUseDtes(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [Role::ADMIN->value, Role::USER->value, Role::SUPERADMIN->value], true);
    }
}
