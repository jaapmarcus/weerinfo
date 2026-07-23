<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('country_slug');
            $table->string('state');
            $table->string('state_slug');
            $table->string('city');
            $table->string('city_slug');
            $table->decimal('latitude', 9, 5);
            $table->decimal('longitude', 8, 5);
            $table->timestamps();

            $table->unique(['country_slug', 'state_slug', 'city_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
