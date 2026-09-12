<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'order_id' => Order::factory(),
            'customer_email' => fake()->safeEmail(),
        ];
    }
}
