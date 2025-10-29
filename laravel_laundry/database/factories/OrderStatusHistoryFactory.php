<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['ANTRIAN', 'PROSES', 'SIAP_DIAMBIL', 'SELESAI', 'BATAL'];
        $fromStatus = fake()->randomElement([null, ...$statuses]);
        $toStatus = fake()->randomElement($statuses);

        return [
            'order_id' => \App\Models\Orders::factory(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'by_user_id' => \App\Models\User::factory(),
            'notes' => fake()->optional()->sentence(),
            'changed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
