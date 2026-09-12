<?php

namespace App\Models;

use App\DiscountType;
use Carbon\Carbon;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'code',
    'discount_type',
    'discount_value',
    'is_active',
    'applies_to_all_products',
    'expires_at',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * Get the products this coupon is limited to.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Get all recorded redemptions for this coupon.
     *
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Determine whether this coupon can be used for a product now.
     */
    public function isAvailableFor(Product $product): bool
    {
        if (! $this->is_active || ($this->expires_at !== null && $this->expires_at->isPast())) {
            return false;
        }

        return $this->applies_to_all_products
            || $this->products()->whereKey($product)->exists();
    }

    /**
     * Calculate the discount in sen for a subtotal in sen.
     */
    public function discountInSen(int $subtotalInSen): int
    {
        $discountInSen = match ($this->discount_type) {
            DiscountType::Percentage => intdiv(
                ($subtotalInSen * $this->amountInSen()) + 5_000,
                10_000,
            ),
            DiscountType::FixedAmount => $this->amountInSen(),
        };

        return min($discountInSen, $subtotalInSen);
    }

    /**
     * Normalize coupon codes before persistence.
     */
    public function setCodeAttribute(string $code): void
    {
        $this->attributes['code'] = Str::upper(trim($code));
    }

    /**
     * Store expiry timestamps in Coordinated Universal Time and expose them in application time.
     */
    protected function expiresAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $value === null
                ? null
                : Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone')),
            set: fn (mixed $value): ?string => $value === null
                ? null
                : Carbon::parse($value)->utc()->format($this->getDateFormat()),
        );
    }

    /**
     * Convert the decimal discount value to sen without floating-point arithmetic.
     */
    private function amountInSen(): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $this->discount_value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'is_active' => 'boolean',
            'applies_to_all_products' => 'boolean',
        ];
    }
}
