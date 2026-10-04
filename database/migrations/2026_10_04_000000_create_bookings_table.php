<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->date('date');
            $table->string('slot', 5);
            $table->timestamps();

            $table->unique(['date', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
