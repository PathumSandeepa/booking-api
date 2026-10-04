<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $date = CarbonImmutable::today()->addDay()->toDateString();

        foreach ([BookingSlot::NineAm, BookingSlot::ElevenAm, BookingSlot::TwoPm] as $slot) {
            Booking::factory()->on($date, $slot)->create();
        }
    }
}
