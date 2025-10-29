<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(['cash', 'transfer', 'e_wallet']);
        $names = [
            'cash' => ['Cash', 'Tunai', 'Uang Tunai'],
            'transfer' => ['BCA Transfer', 'BRI Transfer', 'Mandiri Transfer', 'BNI Transfer'],
            'e_wallet' => ['GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja'],
        ];

        return [
            'outlet_id' => \App\Models\Outlets::factory(),
            'category' => $category,
            'name' => fake()->randomElement($names[$category]),
            'logo' => fake()->imageUrl(100, 100, 'business'),
            'owner_name' => $category === 'cash' ? null : fake()->name(),
            'tags' => $category === 'e_wallet' ? ['qr_code', 'instant'] : null,
            'is_active' => fake()->boolean(90),
        ];
    }
}
