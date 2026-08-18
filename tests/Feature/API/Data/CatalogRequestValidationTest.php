<?php

test('catalog endpoints validate per page', function (string $routeName) {
    $response = $this->getJson(route($routeName, [
        'per_page' => 'invalid',
    ]));

    $response->assertUnprocessable()
        ->assertExactJson([
            'message' => 'The per page field must be an integer.',
            'errors' => [
                'per_page' => [
                    'The per page field must be an integer.',
                ],
            ],
        ]);
})->with([
    'departments' => 'api.v1.data.departments.index',
    'municipalities' => 'api.v1.data.municipalities.index',
    'districts' => 'api.v1.data.districts.index',
    'economic activities' => 'api.v1.data.economic-activities.index',
    'establishment types' => 'api.v1.data.establishment-types.index',
]);
