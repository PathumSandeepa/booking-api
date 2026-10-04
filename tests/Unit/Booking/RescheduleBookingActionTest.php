<?php

declare(strict_types=1);

namespace Tests\Unit\Booking;

use App\Domain\Booking\Actions\RescheduleBookingAction;
use App\Domain\Booking\DataTransferObjects\RescheduleBookingData;
use App\Domain\Booking\Enums\BookingSlot;
use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\CarbonImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RescheduleBookingActionTest extends TestCase
{
    #[Test]
    public function it_reschedules_when_the_target_slot_is_free(): void
    {
        $data = $this->data();
        $expected = $this->booking();

        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('findById')->once()->with('booking-id')->andReturn($this->booking());
        $repository->shouldReceive('existsForSlot')->once()->andReturnFalse();
        $repository->shouldReceive('reschedule')->once()->andReturn($expected);

        $this->assertSame($expected, (new RescheduleBookingAction($repository))('booking-id', $data));
    }

    #[Test]
    public function it_throws_when_the_booking_does_not_exist(): void
    {
        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('findById')->once()->with('missing-id')->andReturnNull();
        $repository->shouldNotReceive('reschedule');

        $this->expectException(BookingNotFoundException::class);

        (new RescheduleBookingAction($repository))('missing-id', $this->data());
    }

    #[Test]
    public function it_throws_when_the_target_slot_belongs_to_another_booking(): void
    {
        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('findById')->once()->andReturn($this->booking());
        $repository->shouldReceive('existsForSlot')->once()->andReturnTrue();
        $repository->shouldNotReceive('reschedule');

        $this->expectException(SlotAlreadyBookedException::class);

        (new RescheduleBookingAction($repository))('booking-id', $this->data());
    }

    #[Test]
    public function it_excludes_the_booking_itself_from_the_availability_check(): void
    {
        $data = $this->data();
        $expected = $this->booking();

        $repository = Mockery::mock(BookingRepository::class);
        $repository->shouldReceive('findById')->once()->andReturn($this->booking());
        $repository->shouldReceive('existsForSlot')
            ->once()
            ->with($data->date, $data->slot, 'booking-id')
            ->andReturnFalse();
        $repository->shouldReceive('reschedule')->once()->andReturn($expected);

        (new RescheduleBookingAction($repository))('booking-id', $data);
    }

    private function data(): RescheduleBookingData
    {
        return new RescheduleBookingData(
            date: CarbonImmutable::today()->addWeeks(2),
            slot: BookingSlot::TwoPm,
        );
    }

    private function booking(): Booking
    {
        return new Booking([
            'name' => 'Jane Silva',
            'email' => 'jane@example.com',
            'date' => CarbonImmutable::today()->addWeek()->toDateString(),
            'slot' => BookingSlot::TenAm->value,
        ]);
    }
}
