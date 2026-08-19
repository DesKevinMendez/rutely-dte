<?php

namespace App\Http\Controllers;

use App\Exceptions\DteSigningException;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\Dte\StoreDteRequest;
use App\Http\Resources\CommonCollection;
use App\Models\Dte;
use App\Response\CommonResponse;
use App\Services\Dte\DteIssuanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DteController extends Controller
{
    public function index(CatalogIndexRequest $request): CommonCollection
    {
        Gate::authorize('viewAny', Dte::class);

        $dtes = QueryBuilder::for(Dte::query()->where('company_id', $request->user()->company_id))
            ->allowedFilters(
                AllowedFilter::exact('tipoDte', 'dte_type'),
                AllowedFilter::exact('estado', 'status'),
                AllowedFilter::exact('environment'),
                AllowedFilter::exact('receiver_document'),
                AllowedFilter::partial('generation_code'),
                AllowedFilter::partial('control_number'),
                AllowedFilter::callback('query', function (Builder $query, mixed $value): void {
                    $search = (string) $value;
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('generation_code', 'like', "%{$search}%")
                            ->orWhere('control_number', 'like', "%{$search}%")
                            ->orWhere('receiver_document', 'like', "%{$search}%");
                    });
                }),
            )
            ->allowedSorts('created_at', 'total_amount', 'status', 'dte_type')
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($dtes);
    }

    public function store(StoreDteRequest $request, DteIssuanceService $issuanceService): CommonResponse
    {
        try {
            $result = $issuanceService->issue((string) $request->user()->company_id, $request->validated());
        } catch (DteSigningException $exception) {
            return new CommonResponse(
                status: 400,
                message: $exception->getMessage(),
            );
        }

        return new CommonResponse([
            'record' => $result['record'],
            'mh_result' => $result['mhResult'],
        ], 201);
    }

    public function show(Dte $dte): CommonResponse
    {
        Gate::authorize('view', $dte);

        return new CommonResponse($dte);
    }
}
