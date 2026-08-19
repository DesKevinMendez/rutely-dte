<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\CommonCollection;
use App\Models\User;
use App\Response\CommonResponse;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(CatalogIndexRequest $request): CommonCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = QueryBuilder::for(User::query()->where('company_id', $request->user()->company_id))
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::exact('role'),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts(['name', 'email', 'role', 'status', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($users);
    }

    public function store(StoreUserRequest $request): CommonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['role'] ??= Role::USER->value;
        $data['status'] ??= true;

        $user = User::query()->create($data);

        return new CommonResponse(['data' => $user], 201);
    }

    public function show(User $user): CommonResponse
    {
        Gate::authorize('view', $user);

        return new CommonResponse(['data' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): CommonResponse
    {
        $user->update($request->validated());

        return new CommonResponse(['data' => $user->refresh()]);
    }

    public function destroy(Request $request, User $user): CommonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return new CommonResponse(['message' => 'Usuario eliminado correctamente.']);
    }
}
