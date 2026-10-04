<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BookingFactory extends Factory
{
    protected $model = Booking::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        $slots = BookingSlot::cases();
        $index = self::$sequence++;

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'date' => CarbonImmutable::today()->addDays(1 + intdiv($index, count($slots)))->toDateString(),
            'slot' => $slots[$index % count($slots)]->value,
        ];
    }

    public function on(string $date, BookingSlot $slot): self
    {
        return $this->state(fn () => [
            'date' => $date,
            'slot' => $slot->value,
        ]);
    }
}
