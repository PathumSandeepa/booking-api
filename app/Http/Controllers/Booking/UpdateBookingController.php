<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Domain\Booking\Actions\RescheduleBookingAction;
use App\Domain\Booking\DataTransferObjects\RescheduleBookingData;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;

final class UpdateBookingController
{
    public function __invoke(UpdateBookingRequest $request, string $id, RescheduleBookingAction $rescheduleBooking): JsonResponse
    {
        $booking = $rescheduleBooking($id, RescheduleBookingData::fromArray($request->validated()));

        return new JsonResponse(BookingResource::make($booking)->resolve());
    }
}
