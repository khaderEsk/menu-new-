<?php

use App\Models\Customer;
use App\Models\Restaurant;
use App\Models\Table;
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Customer::class)->nullable()->constrained();
            $table->foreignIdFor(Table::class)->nullable()->constrained();
            $table->foreignIdFor(Restaurant::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->double('total')->nullable();
            $table->double('consumer_spending')->default(1);
            $table->double('local_administration')->default(1);
            $table->double('reconstruction')->default(1);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
