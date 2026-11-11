<?php

namespace App\Http\Controllers;

use App\Exceptions\DteIssuerProfileException;
use App\Http\Requests\Dte\IndexDteRequest;
use App\Http\Requests\Dte\StoreDteRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Company;
use App\Models\Dte;
use App\Models\MhTransmission;
use App\Models\User;
use App\Response\CommonResponse;
use App\Services\Dte\DteIssuanceService;
use App\Services\Dte\DteListQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DteController extends Controller
{
    public function index(IndexDteRequest $request, DteListQuery $listQuery): CommonCollection
    {
        /** @var User|Company|null $principal */
        $principal = $request->user();

        if ($principal instanceof User) {
            $companyId = $principal->company_id;
        } elseif ($principal instanceof Company) {
            $companyId = $principal->getKey();
        } else {
            throw new AuthorizationException;
        }

        $dtes = $listQuery->forCompany($companyId, $request)
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($dtes);
    }

    public function store(StoreDteRequest $request, DteIssuanceService $issuanceService): CommonResponse
    {
        $principal = $request->user();
        $companyId = match (true) {
            $principal instanceof Company => (string) $principal->getKey(),
            $principal instanceof User => (string) $principal->company_id,
            default => throw new AuthorizationException,
        };

        try {
            $result = $issuanceService->issue($companyId, $request->validated());
        } catch (DteIssuerProfileException $exception) {
            return new CommonResponse(
                status: 400,
                message: $exception->getMessage(),
            );
        }

        return new CommonResponse(
            data: [
                'record' => $result['record'],
                'mh_result' => $result['mhResult'],
            ],
            status: 201,
        );
    }

    public function show(Request $request, Dte $dte): CommonResponse
    {
        $principal = $request->user();

        if ($principal instanceof Company) {
            if ($principal->getKey() !== $dte->company_id) {
                throw new AuthorizationException;
            }
        } else {
            Gate::authorize('view', $dte);
        }

        $transmission = MhTransmission::query()
            ->where('company_id', $dte->company_id)
            ->where('transmittable_type', $dte->getMorphClass())
            ->where('transmittable_id', $dte->getKey())
            ->where('operation', 'dte')
            ->orderByDesc('attempt')
            ->first(['attempt', 'http_status', 'status', 'response_json']);

        if ($transmission !== null) {
            $dte->setAttribute('mh_transmission', [
                'attempt' => $transmission->attempt,
                'http_status' => $transmission->http_status,
                'status' => $transmission->status,
                'response_json' => $transmission->response_json,
            ]);
        }

        return new CommonResponse($dte);
    }
}
