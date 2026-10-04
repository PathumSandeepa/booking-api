<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Booking\Repositories\BookingRepository;
use App\Infrastructure\Booking\Repositories\EloquentBookingRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BookingRepository::class, EloquentBookingRepository::class);
    }
}
