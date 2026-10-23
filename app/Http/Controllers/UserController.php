<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\CommonCollection;
use App\Models\User;
use App\Response\CommonResponse;
use App\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as PermissionRole;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(CatalogIndexRequest $request): CommonCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = QueryBuilder::for(User::query()->with('roles')->where('company_id', $request->user()->company_id))
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::scope('role'),
                AllowedFilter::exact('status'),
            )
            ->allowedSorts(
                'name',
                'email',
                AllowedSort::callback('role', function (Builder $query, bool $descending): void {
                    $modelHasRolesTable = (string) config('permission.table_names.model_has_roles');
                    $user = $query->getModel();
                    $role = (new PermissionRole)->qualifyColumn('name');

                    $query->orderBy(
                        PermissionRole::query()
                            ->select($role)
                            ->join($modelHasRolesTable, $modelHasRolesTable.'.role_id', '=', (new PermissionRole)->qualifyColumn('id'))
                            ->whereColumn($modelHasRolesTable.'.model_id', $user->qualifyColumn($user->getKeyName()))
                            ->where($modelHasRolesTable.'.model_type', $user::class)
                            ->orderBy($role)
                            ->limit(1),
                        $descending ? 'desc' : 'asc',
                    );
                }),
                'status',
                'created_at',
            )
            ->defaultSort('name')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($users);
    }

    public function store(StoreUserRequest $request): CommonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? Role::USER->value;
        unset($data['role']);

        $data['company_id'] = $request->user()->company_id;
        $data['status'] ??= true;

        $user = User::query()->create($data);
        $user->assignRole($role);

        return new CommonResponse($user->refresh()->load('roles'), 201);
    }

    public function show(User $user): CommonResponse
    {
        Gate::authorize('view', $user);

        return new CommonResponse($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): CommonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? null;
        unset($data['role']);

        $user->update($data);

        if ($role !== null) {
            $user->syncRoles($role);
        }

        return new CommonResponse($user->refresh()->load('roles'));
    }

    public function destroy(Request $request, User $user): CommonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return new CommonResponse(message: 'Usuario eliminado correctamente.');
    }
}
