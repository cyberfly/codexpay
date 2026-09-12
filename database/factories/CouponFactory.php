<?php

namespace Database\Factories;

use App\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SALE##'),
            'discount_type' => DiscountType::Percentage,
            'discount_value' => '10.00',
            'is_active' => true,
            'applies_to_all_products' => true,
            'expires_at' => null,
        ];
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the coupon has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subSecond(),
        ]);
    }
}
