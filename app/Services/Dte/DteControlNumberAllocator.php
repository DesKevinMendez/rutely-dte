<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\Dte;
use App\Models\DteCorrelative;
use Illuminate\Support\Facades\DB;

class DteControlNumberAllocator
{
    private const int CORRELATIVE_LENGTH = 15;

    /**
     * Take the establishment's next correlative for the fiscal year and build the
     * DTE control number from it.
     */
    public function next(Company $company, string $dteType, int $fiscalYear): string
    {
        return $this->controlNumber($company, $dteType, $this->nextCorrelative($company, $fiscalYear));
    }

    /**
     * Build a new control number for a DTE that Hacienda rejected because its number was
     * already registered. The establishment's correlative is not trusted here, since it
     * may have been set back: the number continues after the highest one Hacienda accepted
     * in the same establishment, environment and fiscal year, and after the rejected one,
     * which Hacienda already has. The establishment's correlative moves up to it so later
     * DTEs don't collide again; it never moves back.
     */
    public function nextAfterLastAccepted(Company $company, Dte $rejectedDte, int $fiscalYear): string
    {
        $key = DteCorrelative::keyFor($company, $fiscalYear);

        $correlative = DB::transaction(function () use ($company, $rejectedDte, $fiscalYear, $key): int {
            $row = DteCorrelative::query()->firstOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                ['last_value' => '0'],
            );
            $row = DteCorrelative::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $next = max(
                $this->lastAcceptedCorrelative($company, $rejectedDte->environment, $fiscalYear),
                $this->correlativeOf($rejectedDte->control_number),
            ) + 1;

            if ($next > (int) $row->last_value) {
                $row->update(['last_value' => (string) $next]);
            }

            return $next;
        });

        return $this->controlNumber($company, $rejectedDte->dte_type, $correlative);
    }

    private function nextCorrelative(Company $company, int $fiscalYear): int
    {
        $key = DteCorrelative::keyFor($company, $fiscalYear);

        return DB::transaction(function () use ($company, $key): int {
            $row = DteCorrelative::query()->firstOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                ['last_value' => '0'],
            );
            $row = DteCorrelative::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $next = ((int) $row->last_value) + 1;
            $row->update(['last_value' => (string) $next]);

            return $next;
        });
    }

    /**
     * Hacienda only seals the DTEs it accepts, and an invalidated DTE keeps its seal, so the
     * seal marks every number Hacienda registered for the establishment.
     */
    private function lastAcceptedCorrelative(Company $company, string $environment, int $fiscalYear): int
    {
        $highestCorrelative = Dte::query()
            ->where('company_id', $company->id)
            ->where('environment', $environment)
            ->whereNotNull('received_seal')
            ->where('control_number', 'like', 'DTE-__-'.$company->mh_establishment_code.'%')
            ->where('original_json->identificacion->fecEmi', 'like', $fiscalYear.'-%')
            ->max(DB::raw('right(control_number, '.self::CORRELATIVE_LENGTH.')'));

        return (int) $highestCorrelative;
    }

    private function correlativeOf(string $controlNumber): int
    {
        return (int) substr($controlNumber, -self::CORRELATIVE_LENGTH);
    }

    private function controlNumber(Company $company, string $dteType, int $correlative): string
    {
        return sprintf(
            'DTE-%s-%s%s-%s',
            $dteType,
            $company->mh_establishment_code,
            $company->mh_pos_code,
            str_pad((string) $correlative, self::CORRELATIVE_LENGTH, '0', STR_PAD_LEFT),
        );
    }
}
