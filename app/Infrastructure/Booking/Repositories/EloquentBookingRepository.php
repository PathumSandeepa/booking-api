<?php

declare(strict_types=1);

namespace App\Infrastructure\Booking\Repositories;

use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Domain\Booking\DataTransferObjects\RescheduleBookingData;
use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

final class EloquentBookingRepository implements BookingRepository
{
    public function all(?CarbonImmutable $date = null): Collection
    {
        return Booking::query()
            ->when($date, fn ($query) => $query->whereDate('date', $date->toDateString()))
            ->orderBy('date')
            ->orderBy('slot')
            ->get();
    }

    public function findById(string $id): ?Booking
    {
        return Booking::query()->whereKey($id)->first();
    }

    public function existsForSlot(CarbonImmutable $date, BookingSlot $slot, ?string $ignoreBookingId = null): bool
    {
        return Booking::query()
            ->whereDate('date', $date->toDateString())
            ->where('slot', $slot->value)
            ->when($ignoreBookingId, fn ($query, string $id) => $query->whereKeyNot($id))
            ->exists();
    }

    public function create(CreateBookingData $data): Booking
    {
        try {
            return Booking::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'date' => $data->date->toDateString(),
                'slot' => $data->slot->value,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new SlotAlreadyBookedException($data->date, $data->slot);
        }
    }

    public function deleteById(string $id): bool
    {
        return Booking::query()->whereKey($id)->delete() > 0;
    }

    public function reschedule(Booking $booking, RescheduleBookingData $data): Booking
    {
        try {
            $booking->update([
                'date' => $data->date->toDateString(),
                'slot' => $data->slot->value,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new SlotAlreadyBookedException($data->date, $data->slot);
        }

        return $booking->refresh();
    }
}
