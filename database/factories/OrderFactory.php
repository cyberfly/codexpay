<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 5, 250);

        return [
            'reference' => fake()->uuid(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $unitPrice * $quantity,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_note' => fake()->optional()->sentence(),
            'status' => OrderStatus::New,
        ];
    }
}
