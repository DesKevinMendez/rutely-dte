<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dte\ExportDteRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\Dte\DteListQuery;
use App\Services\Dte\DteWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DteExportController extends Controller
{
    public function __invoke(ExportDteRequest $request, DteListQuery $listQuery, DteWorkbook $workbook): BinaryFileResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Company $company */
        $company = $user->company()->firstOrFail(['id', 'name', 'nit']);

        $dtes = $listQuery->forCompany($company->id, $request)
            ->select(['dte_type', 'environment', 'status', 'issuer_nit', 'receiver_document',
                'control_number', 'generation_code', 'received_seal', 'total_amount', 'original_json'])
            ->orderBy('id')
            ->cursor();

        $file = $workbook->create(
            $company,
            $dtes,
            $request->validated('filter.date'),
            $request->validated('filter.from'),
            $request->validated('filter.to'),
        );

        return response()->download($file, 'Reporte-DTE.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->setPrivate()->deleteFileAfterSend(true);
    }
}
