<?php

namespace App\Policies;

use App\Models\User;
use App\Role;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManage($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->canManage($user, $model) && $user->isNot($model);
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    private function canManageUsers(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [Role::ADMIN->value, Role::SUPERADMIN->value], true);
    }

    private function canManage(User $user, User $model): bool
    {
        if (! $this->canManageUsers($user) || $user->company_id !== $model->company_id) {
            return false;
        }

        return $user->role === Role::SUPERADMIN->value || $model->role !== Role::SUPERADMIN->value;
    }
}
