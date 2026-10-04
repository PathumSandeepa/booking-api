<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Exceptions\NoBookingsForDateException;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final readonly class ListBookingsAction
{
    public function __construct(private BookingRepository $bookings) {}

    public function __invoke(?CarbonImmutable $date = null): Collection
    {
        $bookings = $this->bookings->all($date);

        if ($date !== null && $bookings->isEmpty()) {
            Log::info('Booking list requested for a date with no bookings.', ['date' => $date->toDateString()]);

            throw new NoBookingsForDateException($date);
        }

        return $bookings;
    }
}
