<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RescheduleBookingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reschedules_a_booking_to_a_free_slot(): void
    {
        $booking = Booking::factory()->on(CarbonImmutable::today()->addWeek()->toDateString(), BookingSlot::TenAm)->create();
        $newDate = CarbonImmutable::today()->addWeeks(2)->toDateString();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => $newDate,
            'slot' => '14:00',
        ])->assertOk()
            ->assertJsonPath('id', $booking->id)
            ->assertJsonPath('date', $newDate)
            ->assertJsonPath('slot', '14:00');

        $booking->refresh();

        $this->assertSame($newDate, $booking->date->toDateString());
        $this->assertSame(BookingSlot::TwoPm, $booking->slot);
        $this->assertDatabaseCount('bookings', 1);
    }

    #[Test]
    public function it_keeps_the_name_and_email_untouched(): void
    {
        $booking = Booking::factory()->on(CarbonImmutable::today()->addWeek()->toDateString(), BookingSlot::TenAm)->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => CarbonImmutable::today()->addWeeks(2)->toDateString(),
            'slot' => '14:00',
        ])->assertOk()
            ->assertJsonPath('name', $booking->name)
            ->assertJsonPath('email', $booking->email);
    }

    #[Test]
    public function it_allows_rescheduling_onto_its_own_current_slot(): void
    {
        $date = CarbonImmutable::today()->addWeek()->toDateString();
        $booking = Booking::factory()->on($date, BookingSlot::TenAm)->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => $date,
            'slot' => '10:00',
        ])->assertOk()
            ->assertJsonPath('slot', '10:00');
    }

    #[Test]
    public function it_rejects_a_reschedule_onto_a_slot_taken_by_another_booking(): void
    {
        $date = CarbonImmutable::today()->addWeek()->toDateString();
        $booking = Booking::factory()->on($date, BookingSlot::TenAm)->create();
        Booking::factory()->on($date, BookingSlot::ElevenAm)->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => $date,
            'slot' => '11:00',
        ])->assertConflict()
            ->assertJsonPath('message', 'The 11:00 slot on '.$date.' is already booked.');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'slot' => '10:00',
        ]);
    }

    #[Test]
    public function it_frees_the_previous_slot_after_rescheduling(): void
    {
        $date = CarbonImmutable::today()->addWeek()->toDateString();
        $booking = Booking::factory()->on($date, BookingSlot::TenAm)->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => $date,
            'slot' => '15:00',
        ])->assertOk();

        $this->postJson('/bookings', [
            'name' => 'Kamal Perera',
            'email' => 'kamal@example.com',
            'date' => $date,
            'slot' => '10:00',
        ])->assertCreated();
    }

    #[Test]
    public function it_returns_not_found_for_an_unknown_id(): void
    {
        $this->putJson('/bookings/nonexistent-id-12345', [
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
            'slot' => '10:00',
        ])->assertNotFound()
            ->assertJsonPath('message', 'Booking [nonexistent-id-12345] was not found.');
    }

    #[Test]
    public function it_rejects_missing_fields(): void
    {
        $booking = Booking::factory()->create();

        $this->putJson('/bookings/'.$booking->id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'slot']);
    }

    #[Test]
    public function it_rejects_a_date_in_the_past(): void
    {
        $booking = Booking::factory()->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => '2020-01-01',
            'slot' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    #[Test]
    public function it_rejects_a_date_beyond_the_booking_window(): void
    {
        $booking = Booking::factory()->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => CarbonImmutable::today()->addDays((int) config('booking.max_advance_days') + 1)->toDateString(),
            'slot' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    #[Test]
    public function it_rejects_a_slot_outside_the_allowed_set(): void
    {
        $booking = Booking::factory()->create();

        $this->putJson('/bookings/'.$booking->id, [
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
            'slot' => '03:30',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('slot');
    }
}
