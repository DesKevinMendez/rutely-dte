<?php

namespace App\Models;

use App\Environment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'environment',
    'nit',
    'encrypted_certificate',
    'encrypted_private_key_password',
    'active',
])]
class MhCertificates extends Model
{
    /** @use HasFactory<\Database\Factories\MhCertificatesFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'environment' => Environment::class,
            'encrypted_certificate' => 'encrypted',
            'encrypted_private_key_password' => 'encrypted',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
