<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ListBookingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_all_bookings(): void
    {
        Booking::factory()->count(3)->create();

        $this->getJson('/bookings')
            ->assertOk()
            ->assertJsonCount(3);
    }

    #[Test]
    public function it_filters_bookings_by_date(): void
    {
        $date = CarbonImmutable::today()->addWeek();
        Booking::factory()->on($date->toDateString(), BookingSlot::NineAm)->create();
        Booking::factory()->on($date->addDay()->toDateString(), BookingSlot::NineAm)->create();

        $this->getJson('/bookings?date='.$date->toDateString())
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.date', $date->toDateString());
    }

    #[Test]
    public function it_returns_an_empty_list_when_there_are_no_bookings_at_all(): void
    {
        $this->getJson('/bookings')
            ->assertOk()
            ->assertExactJson([]);
    }

    #[Test]
    public function it_returns_an_empty_list_when_no_bookings_match_the_date(): void
    {
        $date = CarbonImmutable::today()->addYear()->toDateString();

        $this->getJson('/bookings?date='.$date)
            ->assertOk()
            ->assertExactJson([]);
    }

    #[Test]
    public function it_rejects_a_malformed_date_filter(): void
    {
        $this->getJson('/bookings?date=nonsense')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    #[Test]
    public function it_rejects_a_date_filter_in_the_wrong_order(): void
    {
        $this->getJson('/bookings?date=05-10-2026')
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.date.0',
                'The date filter must use the YYYY-MM-DD format, for example '.CarbonImmutable::today()->toDateString().'.',
            );
    }
}
