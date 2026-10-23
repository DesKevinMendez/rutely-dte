<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Role;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->company_id === $company->id
            && $user->hasAnyRole([Role::ADMIN->value, Role::USER->value, Role::SUPERADMIN->value]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN->value, Role::SUPERADMIN->value])
            && $user->company_id === null;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->company_id === $company->id
            && $user->hasAnyRole([Role::ADMIN->value, Role::SUPERADMIN->value]);
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }

    public function restore(User $user, Company $company): bool
    {
        return false;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }
}
