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
        Schema::create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_name')->collation('utf8_bin');
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->boolean('is_active')->default(1);
            $table->string('fcm_token')->nullable();
            $table->integer('restaurant_id')->nullable();
            $table->foreignIdFor(City::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admins');
    }
};
