<?php

namespace App\Services\Dte;

use App\Models\Company;
use App\Models\DteCorrelative;
use Illuminate\Support\Facades\DB;

class DteControlNumberAllocator
{
    /**
     * Take the establishment's next correlative for the fiscal year and build the
     * DTE control number from it.
     */
    public function next(Company $company, string $dteType, int $fiscalYear): string
    {
        return sprintf(
            'DTE-%s-%s%s-%s',
            $dteType,
            $company->mh_establishment_code,
            $company->mh_pos_code,
            str_pad((string) $this->nextCorrelative($company, $fiscalYear), 15, '0', STR_PAD_LEFT),
        );
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
}
