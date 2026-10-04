<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_booking(): void
    {
        $date = CarbonImmutable::today()->addWeek()->toDateString();

        $response = $this->postJson('/bookings', [
            'name' => 'Jane Silva',
            'email' => 'Jane@Example.com',
            'date' => $date,
            'slot' => '10:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Jane Silva')
            ->assertJsonPath('email', 'jane@example.com')
            ->assertJsonPath('date', $date)
            ->assertJsonPath('slot', '10:00');

        $this->assertDatabaseCount('bookings', 1);
    }

    #[Test]
    public function it_rejects_a_double_booking_for_the_same_date_and_slot(): void
    {
        $date = CarbonImmutable::today()->addWeek()->toDateString();
        Booking::factory()->on($date, BookingSlot::TenAm)->create();

        $response = $this->postJson('/bookings', [
            'name' => 'Kamal Perera',
            'email' => 'kamal@example.com',
            'date' => $date,
            'slot' => '10:00',
        ]);

        $response->assertConflict()->assertJsonStructure(['message', 'errors' => ['slot']]);

        $this->assertDatabaseCount('bookings', 1);
    }

    #[Test]
    public function it_allows_the_same_slot_on_a_different_date(): void
    {
        $date = CarbonImmutable::today()->addWeek();
        Booking::factory()->on($date->toDateString(), BookingSlot::TenAm)->create();

        $this->postJson('/bookings', [
            'name' => 'Kamal Perera',
            'email' => 'kamal@example.com',
            'date' => $date->addDay()->toDateString(),
            'slot' => '10:00',
        ])->assertCreated();

        $this->assertDatabaseCount('bookings', 2);
    }

    #[Test]
    public function it_rejects_missing_or_malformed_fields(): void
    {
        $this->postJson('/bookings', [
            'name' => '',
            'email' => 'not-an-email',
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'slot']);
    }

    #[Test]
    public function it_rejects_a_date_in_the_past(): void
    {
        $this->postJson('/bookings', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'date' => '2020-01-01',
            'slot' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    #[Test]
    public function it_rejects_a_slot_outside_the_allowed_set(): void
    {
        $this->postJson('/bookings', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
            'slot' => '03:30',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('slot');
    }

    #[Test]
    public function it_accepts_a_booking_for_today(): void
    {
        $this->postJson('/bookings', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'date' => CarbonImmutable::today()->toDateString(),
            'slot' => '10:00',
        ])->assertCreated();
    }

    #[Test]
    public function it_rejects_a_date_beyond_the_booking_window(): void
    {
        $tooFar = CarbonImmutable::today()->addDays((int) config('booking.max_advance_days') + 1);

        $this->postJson('/bookings', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'date' => $tooFar->toDateString(),
            'slot' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    #[Test]
    public function it_rejects_a_single_character_name(): void
    {
        $this->postJson('/bookings', [
            'name' => 'J',
            'email' => 'test@example.com',
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
            'slot' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }
}
