<?php

namespace App\Models;

use App\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'reference',
    'product_id',
    'product_name',
    'unit_price',
    'quantity',
    'coupon_id',
    'coupon_code',
    'discount_amount',
    'total_price',
    'customer_name',
    'customer_email',
    'customer_phone',
    'customer_note',
    'status',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the product ordered by the customer.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the coupon applied to this order.
     *
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the redemption record for this order.
     *
     * @return HasOne<CouponRedemption, $this>
     */
    public function couponRedemption(): HasOne
    {
        return $this->hasOne(CouponRedemption::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }
}
