<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\Dte;
use App\Models\MhTransmission;
use Illuminate\Support\Carbon;

class DuplicateControlNumberService
{
    public const string MH_REJECTION = '[identificacion.numeroControl] YA EXISTE UN REGISTRO CON ESE VALOR';

    public function __construct(
        private readonly DteControlNumberAllocator $controlNumbers,
        private readonly FirmadorService $firmador,
    ) {}

    public function wasRejectedFor(MhTransmission $transmission): bool
    {
        return str_contains((string) data_get($transmission->response_json, 'descripcionMsg'), self::MH_REJECTION);
    }

    /**
     * Normativa MH, rule 10: a rejected document is corrected and transmitted again with the
     * codigoGeneracion it originally had, unless the rejection was about that code. The fix
     * here is a free numeroControl, so the document is signed again with the same code.
     */
    public function correct(Dte $dte): void
    {
        $company = Company::query()->findOrFail($dte->company_id);
        /** @var array<string, mixed> $document */
        $document = $dte->original_json;
        $controlNumber = $this->controlNumbers->next($company, $dte->dte_type, $this->fiscalYear($document));

        data_set($document, 'identificacion.numeroControl', $controlNumber);

        $signedDocument = $this->firmador->signDocument($company->id, $document, $dte->environment, $company->nit);

        $dte->update([
            'control_number' => $controlNumber,
            'original_json' => $document,
            'signed_json' => $signedDocument,
        ]);
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
