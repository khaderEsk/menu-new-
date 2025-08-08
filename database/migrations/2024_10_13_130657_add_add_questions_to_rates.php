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
        Schema::table('rates', function (Blueprint $table) {
            $table->string('service')->default(0);
            $table->string('arakel')->default(0);
            $table->string('foods')->default(0);
            $table->string('drinks')->default(0);
            $table->string('sweets')->default(0);
            $table->string('games_room')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('service');
            $table->dropColumn('arakel');
            $table->dropColumn('foods');
            $table->dropColumn('drinks');
            $table->dropColumn('sweets');
            $table->dropColumn('games_room');
        });
    }
};
