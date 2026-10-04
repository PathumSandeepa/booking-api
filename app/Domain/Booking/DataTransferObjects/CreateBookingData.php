<?php

declare(strict_types=1);

namespace App\Domain\Booking\DataTransferObjects;

use App\Domain\Booking\Enums\BookingSlot;
use Carbon\CarbonImmutable;

final readonly class CreateBookingData
{
    public function __construct(
        public string $name,
        public string $email,
        public CarbonImmutable $date,
        public BookingSlot $slot,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            name: trim((string) $payload['name']),
            email: mb_strtolower(trim((string) $payload['email'])),
            date: CarbonImmutable::parse((string) $payload['date'])->startOfDay(),
            slot: BookingSlot::from((string) $payload['slot']),
        );
    }
}
