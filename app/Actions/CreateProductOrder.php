<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\OrderStatus;
use App\Support\OrderPricing;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateProductOrder
{
    public function __construct(private OrderPricing $orderPricing) {}

    /**
     * Create an order and lock its coupon redemption, when supplied.
     *
     * @param  array{customer_name: string, customer_email: string, customer_phone: string, customer_note?: string|null, coupon_code?: string|null}  $attributes
     */
    public function handle(Product $product, int $quantity, string $submissionToken, array $attributes): Order
    {
        try {
            return DB::transaction(function () use ($attributes, $product, $quantity, $submissionToken): Order {
                $existingOrder = Order::query()
                    ->where('submission_token', $submissionToken)
                    ->lockForUpdate()
                    ->first();

                if ($existingOrder !== null) {
                    return $existingOrder;
                }

                $coupon = $this->findAvailableCoupon(
                    $attributes['coupon_code'] ?? null,
                    $product,
                    $attributes['customer_email'],
                );
                $pricing = $this->orderPricing->quote($product, $quantity, $coupon);

                $order = Order::create([
                    'reference' => (string) Str::uuid(),
                    'submission_token' => $submissionToken,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'discount_amount' => $pricing['discount_amount'],
                    'total_price' => $pricing['total_price'],
                    'customer_name' => $attributes['customer_name'],
                    'customer_email' => $attributes['customer_email'],
                    'customer_phone' => $attributes['customer_phone'],
                    'customer_note' => $attributes['customer_note'] ?? null,
                    'status' => OrderStatus::New,
                ]);

                if ($coupon !== null) {
                    CouponRedemption::create([
                        'coupon_id' => $coupon->id,
                        'order_id' => $order->id,
                        'customer_email' => Str::lower($attributes['customer_email']),
                    ]);
                }

                return $order;
            });
        } catch (QueryException $exception) {
            if ($this->isOrderSubmissionConflict($exception)) {
                return Order::query()
                    ->where('submission_token', $submissionToken)
                    ->firstOrFail();
            }

            if ($this->isCouponRedemptionConflict($exception)) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'Kupon ini telah digunakan dengan e-mel ini.',
                ]);
            }

            throw $exception;
        }
    }

    /**
     * Resolve and validate an optional coupon for the product.
     */
    private function findAvailableCoupon(?string $couponCode, Product $product, string $customerEmail): ?Coupon
    {
        if ($couponCode === null || trim($couponCode) === '') {
            return null;
        }

        $coupon = Coupon::query()
            ->where('code', Str::upper(trim($couponCode)))
            ->first();

        if ($coupon === null) {
            $this->couponValidationError('Kod kupon tidak sah.');
        }

        if (! $coupon->is_active) {
            $this->couponValidationError('Kupon ini tidak aktif.');
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            $this->couponValidationError('Kupon ini telah tamat tempoh.');
        }

        if (! $coupon->applies_to_all_products && ! $coupon->products()->whereKey($product)->exists()) {
            $this->couponValidationError('Kupon tidak sah untuk produk ini.');
        }

        if (CouponRedemption::query()
            ->where('coupon_id', $coupon->id)
            ->where('customer_email', Str::lower($customerEmail))
            ->exists()) {
            $this->couponValidationError('Kupon ini telah digunakan dengan e-mel ini.');
        }

        return $coupon;
    }

    /**
     * Throw a coupon validation error.
     */
    private function couponValidationError(string $message): never
    {
        throw ValidationException::withMessages(['coupon_code' => $message]);
    }

    /**
     * Determine whether a query exception is the one-redemption constraint.
     */
    private function isCouponRedemptionConflict(QueryException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return str_contains($message, 'unique')
            && str_contains($message, 'coupon_redemptions');
    }

    /**
     * Determine whether a query exception is the unique submission token constraint.
     */
    private function isOrderSubmissionConflict(QueryException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return str_contains($message, 'unique')
            && str_contains($message, 'orders.submission_token');
    }
}
