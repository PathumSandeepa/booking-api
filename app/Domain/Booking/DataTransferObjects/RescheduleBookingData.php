<?php

declare(strict_types=1);

namespace App\Domain\Booking\DataTransferObjects;

use App\Domain\Booking\Enums\BookingSlot;
use Carbon\CarbonImmutable;

final readonly class RescheduleBookingData
{
    public function __construct(
        public CarbonImmutable $date,
        public BookingSlot $slot,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            date: CarbonImmutable::parse((string) $payload['date'])->startOfDay(),
            slot: BookingSlot::from((string) $payload['slot']),
        );
    }
}
