<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContingencyEvents\StoreContingencyEventRequest;
use App\Response\CommonResponse;
use App\Services\Dte\ContingencyService;

class ContingencyEventController extends Controller
{
    public function store(StoreContingencyEventRequest $request, ContingencyService $contingencyService): CommonResponse
    {
        $result = $contingencyService->create((string) $request->user()->company_id, $request->validated());

        return new CommonResponse([
            'data' => [
                'event' => $result['event'],
                'contingency_document' => $result['document'],
                'mh_result' => $result['mhResult'],
            ],
        ]);
    }
}
