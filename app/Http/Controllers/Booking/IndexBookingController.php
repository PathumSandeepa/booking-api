<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Domain\Booking\Actions\ListBookingsAction;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;

final class IndexBookingController
{
    public function __invoke(IndexBookingRequest $request, ListBookingsAction $listBookings): JsonResponse
    {
        $bookings = $listBookings($request->filterDate());

        return new JsonResponse(BookingResource::collection($bookings)->resolve());
    }
}
