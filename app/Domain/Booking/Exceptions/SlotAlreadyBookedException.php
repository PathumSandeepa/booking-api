<?php

declare(strict_types=1);

namespace App\Domain\Booking\Exceptions;

use App\Domain\Booking\Enums\BookingSlot;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class SlotAlreadyBookedException extends RuntimeException
{
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly BookingSlot $slot,
    ) {
        parent::__construct(sprintf(
            'The %s slot on %s is already booked.',
            $slot->value,
            $date->toDateString(),
        ));
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
        ], Response::HTTP_CONFLICT);
    }
}
