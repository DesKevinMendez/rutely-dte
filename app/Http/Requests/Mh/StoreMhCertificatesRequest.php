<?php

namespace App\Http\Requests\Mh;

use App\Environment;
use App\Models\MhCertificates;
use App\Services\Mh\MhCertificateService;
use DomainException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMhCertificatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MhCertificates::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'environment' => ['required', 'string', Rule::enum(Environment::class)],
            'certificadoXml' => ['required', 'string'],
            'passwordPri' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                app(MhCertificateService::class)->validateForCompany(
                    (string) $this->user()?->company_id,
                    $this->string('certificadoXml')->toString(),
                    (string) ($this->input('passwordPri') ?? ''),
                );
            } catch (DomainException $exception) {
                $validator->errors()->add('certificadoXml', $exception->getMessage());
            }
        }];
    }
}
