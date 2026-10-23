<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait FormatsPhoneNumber
{
    /**
     * Store phone numbers in El Salvador's international format.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: static function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $digits = preg_replace('/\D+/', '', $value);

                if ($digits === null || $digits === '') {
                    return null;
                }

                if (str_starts_with($digits, '503')) {
                    $digits = substr($digits, 3);
                }

                return '+503'.$digits;
            },
        );
    }
}
