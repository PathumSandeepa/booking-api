<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\Booking\Concerns\ValidatesBookingSchedule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBookingRequest extends FormRequest
{
    use ValidatesBookingSchedule;

    public function rules(): array
    {
        return $this->scheduleRules();
    }

    public function messages(): array
    {
        return $this->scheduleMessages();
    }
}
