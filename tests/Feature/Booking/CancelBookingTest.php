<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Domain\Booking\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CancelBookingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_cancels_an_existing_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->deleteJson('/bookings/'.$booking->id)->assertOk();

        $this->assertDatabaseCount('bookings', 0);
    }

    #[Test]
    public function it_returns_not_found_for_an_unknown_id(): void
    {
        $this->deleteJson('/bookings/nonexistent-id-12345')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function it_frees_the_slot_after_cancellation(): void
    {
        $booking = Booking::factory()->create();

        $this->deleteJson('/bookings/'.$booking->id)->assertOk();

        $this->postJson('/bookings', [
            'name' => 'Kamal Perera',
            'email' => 'kamal@example.com',
            'date' => $booking->date->toDateString(),
            'slot' => $booking->slot->value,
        ])->assertCreated();
    }
}
