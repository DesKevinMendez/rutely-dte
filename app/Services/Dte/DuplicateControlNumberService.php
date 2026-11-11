<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\Dte;
use App\Models\MhTransmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DuplicateControlNumberService
{
    public const string MH_REJECTION = '[identificacion.numeroControl] YA EXISTE UN REGISTRO CON ESE VALOR';

    private const int CORRECTION_WINDOW_HOURS = 24;

    public function __construct(
        private readonly DteControlNumberAllocator $controlNumbers,
        private readonly FirmadorService $firmador,
    ) {}

    public function wasRejectedFor(MhTransmission $transmission): bool
    {
        return str_contains((string) data_get($transmission->response_json, 'descripcionMsg'), self::MH_REJECTION);
    }

    /**
     * Normativa MH, section 10: a rejected DTE is corrected and transmitted again with the
     * same codigoGeneracion when it happens within 24 hours of the rejection, and with a
     * new one after that. The fix here is a free numeroControl, so the document is signed again.
     */
    public function correct(Dte $dte): void
    {
        $company = Company::query()->findOrFail($dte->company_id);
        /** @var array<string, mixed> $document */
        $document = $dte->original_json;
        $controlNumber = $this->controlNumbers->next($company, $dte->dte_type, $this->fiscalYear($document));
        $generationCode = $this->isWithinCorrectionWindow($dte)
            ? $dte->generation_code
            : strtoupper((string) Str::uuid());

        data_set($document, 'identificacion.numeroControl', $controlNumber);
        data_set($document, 'identificacion.codigoGeneracion', $generationCode);

        $signedDocument = $this->firmador->signDocument($company->id, $document, $dte->environment, $company->nit);

        $dte->update([
            'control_number' => $controlNumber,
            'generation_code' => $generationCode,
            'original_json' => $document,
            'signed_json' => $signedDocument,
        ]);
    }

    private function isWithinCorrectionWindow(Dte $dte): bool
    {
        return MhTransmission::query()
            ->where('company_id', $dte->company_id)
            ->whereMorphedTo('transmittable', $dte)
            ->where('operation', 'dte')
            ->where('status', 'failed')
            ->where('request_json->codigoGeneracion', $dte->generation_code)
            ->where('responded_at', '<=', Carbon::now()->subHours(self::CORRECTION_WINDOW_HOURS))
            ->doesntExist();
    }

    /** @param  array<string, mixed>  $document */
    private function fiscalYear(array $document): int
    {
        $issuedOn = data_get($document, 'identificacion.fecEmi');

        return is_string($issuedOn)
            ? Carbon::parse($issuedOn)->year
            : now('America/El_Salvador')->year;
    }
}
