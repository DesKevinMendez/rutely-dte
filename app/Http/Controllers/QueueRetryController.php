<?php

namespace App\Http\Controllers;

use App\Models\MhTransmission;
use App\Response\CommonResponse;
use App\Services\Dte\TransmissionRetryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QueueRetryController extends Controller
{
    public function store(Request $request, TransmissionRetryService $retryService): CommonResponse
    {
        Gate::authorize('retry', MhTransmission::class);

        $failed = MhTransmission::query()
            ->where('company_id', $request->user()->company_id)
            ->where('status', 'failed')
            ->latestAttempts()
            ->latest('created_at')
            ->limit(50)
            ->get();

        $results = $failed->map(fn (MhTransmission $transmission): array => [
            'transmission_id' => $transmission->id,
            'result' => $retryService->retry($transmission),
        ])->values()->all();

        return new CommonResponse([
            'count' => count($results),
            'results' => $results,
        ]);
    }
}
