<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Domain\Booking\Actions\ListBookingsAction;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Resources\BookingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class IndexBookingController
{
    public function __invoke(IndexBookingRequest $request, ListBookingsAction $listBookings): AnonymousResourceCollection
    {
        return BookingResource::collection($listBookings($request->filterDate()));
    }
}
