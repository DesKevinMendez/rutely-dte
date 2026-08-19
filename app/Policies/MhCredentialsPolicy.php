<?php

namespace App\Policies;

use App\Models\MhCredentials;
use App\Models\User;
use App\Role;

class MhCredentialsPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, MhCredentials $mhCredentials): bool
    {
        return $this->canManage($user) && $user->company_id === $mhCredentials->company_id;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, MhCredentials $mhCredentials): bool
    {
        return $this->view($user, $mhCredentials);
    }

    public function delete(User $user, MhCredentials $mhCredentials): bool
    {
        return false;
    }

    public function restore(User $user, MhCredentials $mhCredentials): bool
    {
        return false;
    }

    public function forceDelete(User $user, MhCredentials $mhCredentials): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [Role::ADMIN->value, Role::SUPERADMIN->value], true);
    }
}
