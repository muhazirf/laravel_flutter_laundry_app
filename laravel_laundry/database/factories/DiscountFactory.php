<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['nominal', 'percent']);

        return [
            'outlet_id' => \App\Models\Outlets::factory(),
            'name' => fake()->words(2, true).' Discount',
            'type' => $type,
            'value' => $type === 'percent'
                ? fake()->numberBetween(5, 30)
                : fake()->numberBetween(1000, 50000),
            'note' => fake()->sentence(),
            'is_active' => fake()->boolean(80),
        ];
    }
}
