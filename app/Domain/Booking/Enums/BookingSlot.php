<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum BookingSlot: string
{
    case NineAm = '09:00';
    case TenAm = '10:00';
    case ElevenAm = '11:00';
    case TwelvePm = '12:00';
    case OnePm = '13:00';
    case TwoPm = '14:00';
    case ThreePm = '15:00';
    case FourPm = '16:00';
    case FivePm = '17:00';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
