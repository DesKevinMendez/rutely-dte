<?php

namespace App\Http\Requests\Company;

use App\Models\User;
use App\Role;

class StoreSuperadminCompanyRequest extends StoreCompanyRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasRole(Role::SUPERADMIN->value);
    }
}
