<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Product;

class OrderPricing
{
    /**
     * Calculate an order's monetary amounts in decimal currency strings.
     *
     * @return array{subtotal: string, discount_amount: string, total_price: string}
     */
    public function quote(Product $product, int $quantity, ?Coupon $coupon = null): array
    {
        $subtotalInSen = $this->toSen($product->price) * $quantity;
        $discountInSen = $coupon?->discountInSen($subtotalInSen) ?? 0;

        return [
            'subtotal' => $this->fromSen($subtotalInSen),
            'discount_amount' => $this->fromSen($discountInSen),
            'total_price' => $this->fromSen($subtotalInSen - $discountInSen),
        ];
    }

    /**
     * Convert a two-decimal currency amount into sen.
     */
    private function toSen(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    /**
     * Convert sen into a two-decimal currency amount.
     */
    private function fromSen(int $amountInSen): string
    {
        return number_format($amountInSen / 100, 2, '.', '');
    }
}
