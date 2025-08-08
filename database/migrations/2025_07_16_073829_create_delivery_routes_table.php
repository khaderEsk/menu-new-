<?php

use App\Models\Invoice;
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
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->decimal('start_lat', 9, 6)->nullable();
            $table->decimal('start_lon', 9, 6)->nullable();
            $table->decimal('end_lat', 9, 6)->nullable();
            $table->decimal('end_lon', 9, 6)->nullable();
            $table->decimal('distance', 10, 2)->nullable();
            $table->decimal('duration', 10, 2)->nullable();
            $table->foreignIdFor(Invoice::class)->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
    }
};
