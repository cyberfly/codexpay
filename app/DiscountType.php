<?php

namespace App;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';

    /**
     * Get the customer-facing label for the discount type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Peratus',
            self::FixedAmount => 'Nilai RM',
        };
    }
}
