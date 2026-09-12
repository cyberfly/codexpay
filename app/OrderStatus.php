<?php

namespace App;

enum OrderStatus: string
{
    case New = 'New';
    case Processing = 'Processing';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baharu',
            self::Processing => 'Diproses',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
