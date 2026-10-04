<?php

declare(strict_types=1);

namespace Tests\Unit\Booking;

use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateBookingActionTest extends TestCase
{
    #[Test]
    public function it_persists_the_booking_when_the_slot_is_free(): void
    {
        $data = $this->data();
        $expected = new Booking;

        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('existsForSlot')->once()->andReturnFalse();
        $repository->shouldReceive('create')->once()->with($data)->andReturn($expected);

        $this->assertSame($expected, (new CreateBookingAction($repository))($data));
    }

    #[Test]
    public function it_throws_when_the_slot_is_taken(): void
    {
        $data = $this->data();

        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('existsForSlot')->once()->andReturnTrue();
        $repository->shouldNotReceive('create');

        $this->expectException(SlotAlreadyBookedException::class);

        (new CreateBookingAction($repository))($data);
    }

    private function data(): CreateBookingData
    {
        return new CreateBookingData(
            name: 'Jane Silva',
            email: 'jane@example.com',
            date: CarbonImmutable::today()->addWeek(),
            slot: BookingSlot::TenAm,
        );
    }
}
