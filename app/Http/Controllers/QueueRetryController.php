<?php

namespace App\Http\Controllers;

use App\Models\MhTransmission;
use App\Response\CommonResponse;
use App\Services\Mh\MhTransmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QueueRetryController extends Controller
{
    public function store(Request $request, MhTransmissionService $transmissionService): CommonResponse
    {
        Gate::authorize('retry', MhTransmission::class);

        $failed = MhTransmission::query()
            ->where('company_id', $request->user()->company_id)
            ->where('status', 'failed')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->unique(fn (MhTransmission $transmission): string => implode(':', [
                $transmission->transmittable_type,
                $transmission->transmittable_id,
                $transmission->operation,
            ]));

        $results = $failed->map(fn (MhTransmission $transmission): array => [
            'transmission_id' => $transmission->id,
            'result' => $transmissionService->retry($transmission),
        ])->values()->all();

        return new CommonResponse([
            'data' => [
                'count' => count($results),
                'results' => $results,
            ],
        ]);
    }
}
