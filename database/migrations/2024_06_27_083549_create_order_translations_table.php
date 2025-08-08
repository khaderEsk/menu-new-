<?php

use App\Models\Order;
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
        Schema::create('order_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('locale');
            $table->foreignIdFor(Order::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['locale', 'order_id']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_translations');
    }
};
