<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class IndexBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function filterDate(): ?CarbonImmutable
    {
        $date = $this->query('date');

        if (blank($date)) {
            return null;
        }

        return CarbonImmutable::parse((string) $date)->startOfDay();
    }
}
