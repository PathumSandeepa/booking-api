<?php

declare(strict_types=1);

use App\Http\Controllers\Booking\DestroyBookingController;
use App\Http\Controllers\Booking\IndexBookingController;
use App\Http\Controllers\Booking\StoreBookingController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:'.config('booking.rate_limit'))->group(function (): void {
    Route::get('/bookings', IndexBookingController::class);
    Route::post('/bookings', StoreBookingController::class);
    Route::delete('/bookings/{id}', DestroyBookingController::class);
});
