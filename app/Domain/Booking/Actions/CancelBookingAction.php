<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Repositories\BookingRepository;
use Illuminate\Support\Facades\Log;

final readonly class CancelBookingAction
{
    public function __construct(private BookingRepository $bookings) {}

    public function __invoke(string $id): void
    {
        if (! $this->bookings->deleteById($id)) {
            Log::warning('Booking cancellation failed, booking not found.', ['id' => $id]);

            throw new BookingNotFoundException($id);
        }

        Log::info('Booking cancelled.', ['id' => $id]);
    }
}
