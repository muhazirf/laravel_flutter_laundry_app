<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Orders::factory(),
            'method_id' => \App\Models\PaymentMethod::factory(),
            'amount' => fake()->numberBetween(10000, 500000),
            'paid_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'ref_no' => fake()->optional()->numerify('REF-########'),
            'note' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['SUCCESS', 'VOID']),
        ];
    }
}
