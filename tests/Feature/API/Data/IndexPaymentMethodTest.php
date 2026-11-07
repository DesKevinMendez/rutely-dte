<?php

use App\Models\PaymentMethod;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(PaymentMethodSeeder::class);
});

/**
 * @param  array<int, string>  $codes
 * @return array<int, array{id: string, code: string, description: string}>
 */
function expectedPaymentMethods(array $codes): array
{
    $ids = PaymentMethod::query()->pluck('id', 'code');

    $methods = array_filter(
        PaymentMethodSeeder::METHODS,
        fn (array $method): bool => in_array($method['code'], $codes, true),
    );

    return array_values(array_map(
        fn (array $method): array => ['id' => $ids[$method['code']], ...$method],
        $methods,
    ));
}

test('payment methods are public and return the official catalog ordered by code', function () {
    $codes = array_column(PaymentMethodSeeder::METHODS, 'code');

    $response = $this->getJson(route('api.v1.data.payment-methods.index', ['per_page' => 100]));

    $response->assertOk()
        ->assertExactJson([
            'data' => expectedPaymentMethods($codes),
            'pagination' => [
                'total' => count($codes),
                'per_page' => 100,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => count($codes),
            ],
        ]);

    expect(array_column($response->json('data'), 'code'))->toBe($codes);
});

test('payment methods use the default page size', function () {
    $codes = array_column(PaymentMethodSeeder::METHODS, 'code');

    $this->getJson(route('api.v1.data.payment-methods.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => expectedPaymentMethods(array_slice($codes, 0, 10)),
            'pagination' => [
                'total' => count($codes),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 2,
                'from' => 1,
                'to' => 10,
            ],
        ]);
});

test('payment methods can be filtered', function (Closure $filter, array $codes) {
    $this->getJson(route('api.v1.data.payment-methods.index', ['filter' => $filter()]))
        ->assertOk()
        ->assertExactJson([
            'data' => expectedPaymentMethods($codes),
            'pagination' => [
                'total' => count($codes),
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => count($codes),
            ],
        ]);
})->with([
    'by code' => [fn (): array => ['code' => '99'], ['99']],
    'by id' => [fn (): array => ['id' => PaymentMethod::query()->where('code', '04')->value('id')], ['04']],
    'by part of the description' => [fn (): array => ['description' => 'tarjeta'], ['02', '03']],
]);

test('payment methods reject unknown filters', function () {
    $this->getJson(route('api.v1.data.payment-methods.index', ['filter' => ['unknown' => 'value']]))
        ->assertBadRequest();
});

test('payment methods validate per page', function (mixed $perPage, string $message) {
    $this->getJson(route('api.v1.data.payment-methods.index', ['per_page' => $perPage]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page' => $message]);
})->with([
    'must be an integer' => ['invalid', 'El campo elementos por página debe ser un número entero.'],
    'must be at least one' => [0, 'El campo elementos por página debe ser al menos 1.'],
    'cannot exceed one hundred' => [101, 'El campo elementos por página no debe ser mayor que 100.'],
]);

test('payment method factories create valid persisted models', function () {
    DB::table('payment_methods')->delete();

    $paymentMethod = PaymentMethod::factory()->create();

    $this->assertDatabaseHas(PaymentMethod::class, [
        'id' => $paymentMethod->id,
        'code' => $paymentMethod->code,
        'description' => $paymentMethod->description,
    ]);
});

test('payment method seeder is idempotent and updates catalog rows by code', function () {
    $originalRows = DB::table('payment_methods')->get()->keyBy('code');

    DB::table('payment_methods')->where('code', '01')->update([
        'description' => 'Descripción anterior',
        'updated_at' => '2000-01-01 00:00:00',
    ]);

    $this->seed(PaymentMethodSeeder::class);

    $reseededRows = DB::table('payment_methods')->get()->keyBy('code');

    expect($reseededRows->pluck('id', 'code')->all())->toBe($originalRows->pluck('id', 'code')->all());

    $updatedRow = $reseededRows->get('01');

    expect($updatedRow->description)->toBe(PaymentMethodSeeder::METHODS[0]['description'])
        ->and($updatedRow->created_at)->toBe($originalRows->get('01')->created_at)
        ->and($updatedRow->updated_at)->not->toBe('2000-01-01 00:00:00');
});
