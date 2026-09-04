<?php

namespace App\Enums;

enum BookingSource: string
{
    case Website = 'website';
    case Phone = 'phone';
    case WalkIn = 'walk_in';
    case ThirdParty = 'third_party';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Phone => 'Phone',
            self::WalkIn => 'Walk-in',
            self::ThirdParty => 'Third-party',
        };
    }
}
