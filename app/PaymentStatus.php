<?php

namespace App;

enum PaymentStatus: string
{
    case Pending = 'Pending';
    case Paid = 'Paid';
    case Failed = 'Failed';
    case Cancelled = 'Cancelled';
    case Expired = 'Expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu bayaran',
            self::Paid => 'Dibayar',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Tamat tempoh',
        };
    }
}
