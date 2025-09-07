<?php

use App\Models\Restaurant;
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
        Schema::create('restaurant_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('note');
            $table->string('locale');
            $table->foreignIdFor(Restaurant::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['locale', 'restaurant_id']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_translations');
    }
};
