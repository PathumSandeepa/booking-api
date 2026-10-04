<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\DataTransferObjects\RescheduleBookingData;
use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use Illuminate\Support\Facades\Log;

final readonly class RescheduleBookingAction
{
    public function __construct(private BookingRepository $bookings) {}

    public function __invoke(string $id, RescheduleBookingData $data): Booking
    {
        $context = [
            'id' => $id,
            'date' => $data->date->toDateString(),
            'slot' => $data->slot->value,
        ];

        $booking = $this->bookings->findById($id);

        if (! $booking instanceof Booking) {
            Log::warning('Booking reschedule failed, booking not found.', $context);

            throw new BookingNotFoundException($id);
        }

        $context += [
            'from_date' => $booking->date->toDateString(),
            'from_slot' => $booking->slot->value,
        ];

        // The booking already owns its current slot, so it must not conflict with itself.
        if ($this->bookings->existsForSlot($data->date, $data->slot, $id)) {
            Log::warning('Booking reschedule rejected, slot already taken.', $context);

            throw new SlotAlreadyBookedException($data->date, $data->slot);
        }

        try {
            $booking = $this->bookings->reschedule($booking, $data);
        } catch (SlotAlreadyBookedException $exception) {
            Log::warning('Booking reschedule rejected by unique constraint, a concurrent request won the slot.', $context);

            throw $exception;
        }

        Log::info('Booking rescheduled.', $context);

        return $booking;
    }
}
