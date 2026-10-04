<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class StoreBookingController
{
    public function __invoke(StoreBookingRequest $request, CreateBookingAction $createBooking): JsonResponse
    {
        $booking = $createBooking(CreateBookingData::fromArray($request->validated()));

        return BookingResource::make($booking)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
