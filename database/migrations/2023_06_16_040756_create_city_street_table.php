<?php

use App\Models\City;
use App\Models\Street;
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
        Schema::create('city_street', function (Blueprint $table) {
            $table->foreignIdFor(City::class)->constrained();
            $table->foreignIdFor(Street::class)->constrained();
            $table->primary([
                (new City())->getForeignKey(),
                (new Street())->getForeignKey(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_street');
    }
};
