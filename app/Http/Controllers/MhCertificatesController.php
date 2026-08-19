<?php

namespace App\Http\Controllers;

use App\Environment;
use App\Http\Requests\Mh\ShowMhCertificatesRequest;
use App\Http\Requests\Mh\StoreMhCertificatesRequest;
use App\Models\MhCertificates;
use App\Response\CommonResponse;
use Rutely\DteSigned\Certificate\MhCertificate;

class MhCertificatesController extends Controller
{
    public function store(StoreMhCertificatesRequest $request): CommonResponse
    {
        $data = $request->validated();
        $password = (string) ($data['passwordPri'] ?? '');
        $certificate = MhCertificate::fromXml($data['certificadoXml'], $password);

        $storedCertificate = MhCertificates::query()->updateOrCreate(
            [
                'company_id' => $request->user()->company_id,
                'environment' => $data['environment'],
            ],
            [
                'nit' => (string) $certificate->nit,
                'encrypted_certificate' => $data['certificadoXml'],
                'encrypted_private_key_password' => $password,
                'active' => true,
            ],
        );

        return new CommonResponse(['data' => $this->metadata($storedCertificate->refresh())]);
    }

    public function show(ShowMhCertificatesRequest $request): CommonResponse
    {
        $environment = $request->validated('environment', Environment::SANDBOX->value);
        $certificate = MhCertificates::query()
            ->where('company_id', $request->user()->company_id)
            ->where('environment', $environment)
            ->latest('updated_at')
            ->firstOrFail();

        return new CommonResponse(['data' => $this->metadata($certificate)]);
    }

    /** @return array<string, mixed> */
    private function metadata(MhCertificates $certificate): array
    {
        return [
            'id' => $certificate->id,
            'environment' => $certificate->environment->value,
            'nit' => $certificate->nit,
            'active' => $certificate->active,
            'updated_at' => $certificate->updated_at?->toJSON(),
        ];
    }
}
