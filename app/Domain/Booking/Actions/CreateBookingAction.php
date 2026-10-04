<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use Illuminate\Support\Facades\Log;

final readonly class CreateBookingAction
{
    public function __construct(private BookingRepository $bookings) {}

    public function __invoke(CreateBookingData $data): Booking
    {
        $context = [
            'date' => $data->date->toDateString(),
            'slot' => $data->slot->value,
        ];

        if ($this->bookings->existsForSlot($data->date, $data->slot)) {
            Log::warning('Booking rejected, slot already taken.', $context);

            throw new SlotAlreadyBookedException($data->date, $data->slot);
        }

        try {
            $booking = $this->bookings->create($data);
        } catch (SlotAlreadyBookedException $exception) {
            Log::warning('Booking rejected by unique constraint, a concurrent request won the slot.', $context);

            throw $exception;
        }

        Log::info('Booking created.', $context + ['id' => $booking->id]);

        return $booking;
    }
}
