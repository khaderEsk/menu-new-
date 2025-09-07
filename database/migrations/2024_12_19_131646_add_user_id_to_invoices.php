<?php

use App\Models\User;
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignIdFor(User::class)->nullable()->constrained()->cascadeOnUpdate();
            $table->boolean('is_delivery')->default(0);
            $table->string('accepted')->nullable();
            $table->foreignIdFor(User::class, 'delivery_id')->nullable()->constrained('users')->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_id');
            $table->dropColumn('is_delivery');
            $table->dropColumn('accepted');
            $table->dropColumn('delivery_id');
        });
    }
};
