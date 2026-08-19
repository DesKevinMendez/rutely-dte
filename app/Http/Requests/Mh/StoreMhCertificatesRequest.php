<?php

namespace App\Http\Requests\Mh;

use App\Environment;
use App\Models\Company;
use App\Models\MhCertificates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Rutely\DteSigned\Certificate\MhCertificate;
use Throwable;

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
                $certificate = MhCertificate::fromXml(
                    $this->string('certificadoXml')->toString(),
                    (string) ($this->input('passwordPri') ?? ''),
                );
            } catch (Throwable) {
                $validator->errors()->add('certificadoXml', 'El certificado de Hacienda no es válido o la contraseña privada es incorrecta.');

                return;
            }

            $company = Company::query()->find($this->user()?->company_id);
            $certificateNit = preg_replace('/\D+/', '', (string) $certificate->nit) ?? '';
            $companyNit = preg_replace('/\D+/', '', (string) $company?->nit) ?? '';

            if ($certificateNit === '' || $certificateNit !== $companyNit) {
                $validator->errors()->add('certificadoXml', 'El NIT del certificado no coincide con el NIT de la empresa.');
            }
        }];
    }
}
