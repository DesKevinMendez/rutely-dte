<?php

namespace App\Policies;

use App\Models\MhCertificates;
use App\Models\User;
use App\Role;

class MhCertificatesPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, MhCertificates $mhCertificates): bool
    {
        return $this->canManage($user) && $user->company_id === $mhCertificates->company_id;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, MhCertificates $mhCertificates): bool
    {
        return $this->view($user, $mhCertificates);
    }

    public function delete(User $user, MhCertificates $mhCertificates): bool
    {
        return false;
    }

    public function restore(User $user, MhCertificates $mhCertificates): bool
    {
        return false;
    }

    public function forceDelete(User $user, MhCertificates $mhCertificates): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->company_id !== null
            && in_array($user->role, [Role::ADMIN->value, Role::SUPERADMIN->value], true);
    }
}
