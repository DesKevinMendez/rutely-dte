<?php

namespace App\Http\Controllers;

use App\Models\Dte;
use App\Models\MhTransmission;
use App\Response\CommonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function show(Request $request): CommonResponse
    {
        Gate::authorize('viewAny', Dte::class);

        $companyId = $request->user()->company_id;
        $baseQuery = Dte::query()->where('company_id', $companyId);

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'processed' => (clone $baseQuery)->where('status', 'PROCESADO')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'RECHAZADO')->count(),
            'invalidated' => (clone $baseQuery)->where('status', 'INVALIDADO')->count(),
            'total_amount' => (int) (clone $baseQuery)->sum('total_amount'),
            'pending_transmissions' => MhTransmission::query()
                ->where('company_id', $companyId)
                ->whereIn('status', ['pending', 'failed'])
                ->count(),
        ];

        $recentDtes = (clone $baseQuery)
            ->latest('created_at')
            ->limit(20)
            ->get();

        return new CommonResponse([
            'metrics' => $metrics,
            'recent_dtes' => $recentDtes,
        ]);
    }
}
