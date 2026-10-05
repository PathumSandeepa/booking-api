<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class ListBookingsAction
{
    public function __construct(private BookingRepository $bookings) {}

    public function __invoke(?CarbonImmutable $date = null): Collection
    {
        return $this->bookings->all($date);
    }
}
