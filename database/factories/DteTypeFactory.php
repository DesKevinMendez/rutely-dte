<?php

namespace Database\Factories;

use App\Models\DteType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DteType>
 */
class DteTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => '01',
            'description' => 'Factura',
        ];
    }
}
