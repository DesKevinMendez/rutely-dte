<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Resources\CommonCollection;
use App\Models\DocumentType;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DocumentTypeListController extends Controller
{
    public function __invoke(CatalogIndexRequest $request): CommonCollection
    {
        $types = QueryBuilder::for(DocumentType::class)
            ->allowedFilters(
                AllowedFilter::exact('id'),
                AllowedFilter::exact('code'),
                AllowedFilter::partial('description'),
            )
            ->select(['id', 'code', 'description'])
            ->orderBy('code')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($types);
    }
}
