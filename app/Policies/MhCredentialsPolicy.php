<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\MhCredentials;
use App\Models\User;
use App\Role;
use Illuminate\Auth\Access\Response;

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

    /**
     * A NIT can be used when it is not registered to another company and matches the other environment of the user's company.
     */
    public function createWithNit(User $user, string $nit, string $environment): Response
    {
        if (! $this->canManage($user) || $this->isRegisteredToAnotherCompany($user, $nit)) {
            return Response::deny(__('validation.custom.nit.other_company'));
        }

        if ($this->differsFromOtherEnvironment($user, $nit, $environment)) {
            return Response::deny(__('validation.custom.nit.other_environment'));
        }

        return Response::allow();
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

    private function isRegisteredToAnotherCompany(User $user, string $nit): bool
    {
        return Company::query()->where('nit', $nit)->whereKeyNot($user->company_id)->exists()
            || MhCredentials::query()->where('nit', $nit)->where('company_id', '!=', $user->company_id)->exists();
    }

    private function differsFromOtherEnvironment(User $user, string $nit, string $environment): bool
    {
        return MhCredentials::query()
            ->where('company_id', $user->company_id)
            ->where('environment', '!=', $environment)
            ->where('nit', '!=', $nit)
            ->exists();
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole([Role::ADMIN->value, Role::SUPERADMIN->value]);
    }
}
