<?php

declare(strict_types=1);

namespace App\Domain\Booking\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class BookingNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $id)
    {
        parent::__construct(sprintf('Booking [%s] was not found.', $id));
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
        ], Response::HTTP_NOT_FOUND);
    }
}
