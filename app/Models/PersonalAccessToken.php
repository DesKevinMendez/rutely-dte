<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @return Attribute<string, never> */
    protected function tokenPreview(): Attribute
    {
        return Attribute::get(fn (): string => $this->getKey().'|'.substr($this->token, 0, 4).'***'.substr($this->token, -4));
    }
}
