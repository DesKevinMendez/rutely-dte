<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

class ForbiddenAbilityException extends AuthorizationException
{
    /**
     * Create a new forbidden ability exception.
     *
     * @param  array|string  $abilities  The abilities that the token is not allowed to have.
     * @param  string  $message
     */
    public function __construct(protected $abilities = [], $message = 'Forbidden ability provided.')
    {
        parent::__construct($message);

        $this->abilities = Arr::wrap($abilities);
    }

    /**
     * Get the forbidden abilities.
     *
     * @return array
     */
    public function abilities()
    {
        return $this->abilities;
    }
}
