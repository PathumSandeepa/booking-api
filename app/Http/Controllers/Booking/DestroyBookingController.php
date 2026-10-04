<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Domain\Booking\Actions\CancelBookingAction;
use Illuminate\Http\JsonResponse;

final class DestroyBookingController
{
    public function __invoke(string $id, CancelBookingAction $cancelBooking): JsonResponse
    {
        $cancelBooking($id);

        return new JsonResponse(['message' => 'Booking cancelled.']);
    }
}
