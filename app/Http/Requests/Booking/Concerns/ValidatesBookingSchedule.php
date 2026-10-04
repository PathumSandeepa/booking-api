<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking\Concerns;

use App\Domain\Booking\Enums\BookingSlot;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

trait ValidatesBookingSchedule
{
    protected function scheduleRules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.$this->lastBookableDate(),
            ],
            'slot' => ['required', 'string', Rule::enum(BookingSlot::class)],
        ];
    }

    protected function scheduleMessages(): array
    {
        return [
            'date.after_or_equal' => 'The date must be today or a future date.',
            'date.before_or_equal' => 'The date must be within '.$this->maxAdvanceDays().' days from today.',
            'date.date_format' => 'The date must use the YYYY-MM-DD format.',
            'slot.Illuminate\Validation\Rules\Enum' => 'The slot must be one of: '.implode(', ', BookingSlot::values()).'.',
        ];
    }

    private function maxAdvanceDays(): int
    {
        return (int) config('booking.max_advance_days');
    }

    private function lastBookableDate(): string
    {
        return CarbonImmutable::today()->addDays($this->maxAdvanceDays())->toDateString();
    }
}
