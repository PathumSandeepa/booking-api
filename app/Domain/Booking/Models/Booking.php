<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Domain\Booking\Enums\BookingSlot;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'name',
        'email',
        'date',
        'slot',
    ];

    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'slot' => BookingSlot::class,
        ];
    }
}
