<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourierStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class PointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address' => fake()->address(),
            'lat' => fake()->latitude(30, 50),
            'long' => fake()->longitude(30, 50),
        ];
    }
}
