<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'date' => $this->date->toDateString(),
            'slot' => $this->slot->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
