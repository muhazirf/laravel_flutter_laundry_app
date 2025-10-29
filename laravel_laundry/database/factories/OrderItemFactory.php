<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = fake()->randomElement(['kg', 'pcs', 'meter']);
        $qty = fake()->randomFloat(2, 0.5, 10);
        $pricePerUnit = fake()->numberBetween(5000, 25000);
        $lineTotal = $qty * $pricePerUnit;

        return [
            'order_id' => \App\Models\Orders::factory(),
            'service_variant_id' => \App\Models\ServiceVariants::factory(),
            'unit' => $unit,
            'qty' => $qty,
            'price_per_unit_snapshot' => $pricePerUnit,
            'line_total' => $lineTotal,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
