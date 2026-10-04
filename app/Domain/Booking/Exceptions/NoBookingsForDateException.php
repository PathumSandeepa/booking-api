<?php

declare(strict_types=1);

namespace App\Domain\Booking\Exceptions;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class NoBookingsForDateException extends RuntimeException
{
    public function __construct(public readonly CarbonImmutable $date)
    {
        parent::__construct(sprintf('No bookings found for %s.', $date->toDateString()));
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
        ], Response::HTTP_NOT_FOUND);
    }
}
