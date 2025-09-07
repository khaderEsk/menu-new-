<?php

use App\Models\City;
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
        Schema::create('city_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('locale');
            $table->foreignIdFor(City::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['locale' , 'city_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_translations');
    }
};
