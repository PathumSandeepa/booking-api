<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ConcurrentBookingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_unique_constraint_rejects_a_duplicate_that_slips_past_the_availability_check(): void
    {
        $repository = $this->app->make(BookingRepository::class);
        $data = new CreateBookingData(
            name: 'Jane Silva',
            email: 'jane@example.com',
            date: CarbonImmutable::today()->addWeek(),
            slot: BookingSlot::TenAm,
        );

        $repository->create($data);

        $this->expectException(SlotAlreadyBookedException::class);

        $repository->create($data);
    }

    #[Test]
    public function only_one_of_two_identical_requests_is_accepted(): void
    {
        $payload = [
            'name' => 'Jane Silva',
            'email' => 'jane@example.com',
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
            'slot' => '10:00',
        ];

        $this->postJson('/bookings', $payload)->assertCreated();
        $this->postJson('/bookings', $payload)->assertConflict();

        $this->assertDatabaseCount('bookings', 1);
    }
}
