<?php

declare(strict_types=1);

namespace App\Domain\Booking\Repositories;

use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Domain\Booking\DataTransferObjects\RescheduleBookingData;
use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface BookingRepository
{
    public function all(?CarbonImmutable $date = null): Collection;

    public function findById(string $id): ?Booking;

    public function existsForSlot(CarbonImmutable $date, BookingSlot $slot, ?string $ignoreBookingId = null): bool;

    public function create(CreateBookingData $data): Booking;

    public function reschedule(Booking $booking, RescheduleBookingData $data): Booking;

    public function deleteById(string $id): bool;
}
