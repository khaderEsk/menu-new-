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
        Schema::create('topping_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('locale');
            $table->foreignId('topping_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['locale' , 'topping_id']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topping_translations');
    }
};
