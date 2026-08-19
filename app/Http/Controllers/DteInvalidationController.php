<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dte\StoreDteInvalidationRequest;
use App\Models\Dte;
use App\Response\CommonResponse;
use App\Services\Dte\DteInvalidationService;

class DteInvalidationController extends Controller
{
    public function store(
        StoreDteInvalidationRequest $request,
        Dte $dte,
        DteInvalidationService $invalidationService,
    ): CommonResponse {
        $result = $invalidationService->invalidate($dte, $request->validated());

        return new CommonResponse([
            'dte' => $result['updated'],
            'invalidation' => $result['invalidation'],
            'mh_result' => $result['mhResult'],
        ]);
    }
}
