<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogIndexRequest;
use App\Http\Requests\User\StoreSuperadminCompanyUserRequest;
use App\Http\Resources\CommonCollection;
use App\Jobs\SendCompanyAdminCredentials;
use App\Models\Company;
use App\Models\User;
use App\Response\CommonResponse;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyUserController extends Controller
{
    public function index(CatalogIndexRequest $request, Company $company): CommonCollection
    {
        $users = User::query()
            ->with('roles')
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10));

        return CommonCollection::make($users);
    }

    public function store(StoreSuperadminCompanyUserRequest $request, Company $company): CommonResponse
    {
        $userData = $request->safe()->only(['name', 'email', 'phone']);
        $password = Str::password(16);

        $user = DB::transaction(function () use ($company, $password, $userData): User {
            $user = User::query()->create([
                ...$userData,
                'company_id' => $company->id,
                'password' => $password,
                'status' => true,
            ]);
            $user->assignRole(Role::ADMIN->value);

            return $user->refresh()->load('roles');
        });

        SendCompanyAdminCredentials::dispatch($user, $company, $password);

        return new CommonResponse($user, 201);
    }
}
