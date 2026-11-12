<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dte\UpdateSuperadminDteCorrelativeRequest;
use App\Models\Company;
use App\Models\DteCorrelative;
use App\Response\CommonResponse;
use Illuminate\Support\Facades\DB;

class DteCorrelativeController extends Controller
{
    public function show(Company $company): CommonResponse
    {
        $year = now('America/El_Salvador')->year;
        $lastValue = DteCorrelative::query()
            ->where('company_id', $company->id)
            ->where('key', DteCorrelative::keyFor($company, $year))
            ->value('last_value');

        return new CommonResponse(['year' => $year, 'next_value' => (int) $lastValue + 1]);
    }

    public function update(UpdateSuperadminDteCorrelativeRequest $request, Company $company): CommonResponse
    {
        $year = now('America/El_Salvador')->year;
        $nextValue = (int) $request->validated('next_value');

        return DB::transaction(function () use ($company, $year, $nextValue): CommonResponse {
            $row = DteCorrelative::query()->firstOrCreate(
                ['company_id' => $company->id, 'key' => DteCorrelative::keyFor($company, $year)],
                ['last_value' => '0'],
            );
            $row = DteCorrelative::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();

            $row->update(['last_value' => (string) ($nextValue - 1)]);

            return new CommonResponse(['year' => $year, 'next_value' => $nextValue]);
        });
    }
}
