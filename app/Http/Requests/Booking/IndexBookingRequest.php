<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

final class IndexBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date_format' => 'The date filter must use the YYYY-MM-DD format, for example '.CarbonImmutable::today()->toDateString().'.',
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

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Booking list rejected, malformed date filter.', [
            'date' => $this->query('date'),
        ]);

        parent::failedValidation($validator);
    }
}
