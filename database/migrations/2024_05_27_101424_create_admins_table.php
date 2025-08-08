<?php

use App\Models\Admin;
use App\Models\City;
use App\Models\Emoji;
use App\Models\MenuForm;
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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('password');
            $table->string('name');
            $table->string('mobile');
            $table->boolean('is_active')->default(1);
            $table->string('type')->nullable();
            $table->string('fcm_token')->nullable();
            $table->foreignIdFor(Restaurant::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
