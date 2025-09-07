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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->double('consumer_spending')->default(1);
            $table->double('local_administration')->default(1);
            $table->double('reconstruction')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('consumer_spending');
            $table->dropColumn('local_administration');
            $table->dropColumn('reconstruction');
        });
    }
};
