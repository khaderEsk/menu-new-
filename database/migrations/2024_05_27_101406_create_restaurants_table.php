<?php

use App\Models\City;
use App\Models\Emoji;
use App\Models\MenuTemplate;
use App\Models\SuperAdmin;
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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name_url');
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('whatsapp_phone')->nullable();
            $table->date('end_date')->nullable();
            $table->string('message_bad')->nullable();
            $table->string('message_good')->nullable();
            $table->string('message_perfect')->nullable();
            $table->string('color')->default('Color(0xffffffff)');
          	$table->string('background_color')->default('Color(0xffffffff)');
            $table->boolean('is_news')->default(1);
            $table->boolean('is_rate')->default(1);
            $table->boolean('is_active')->default(1);
            $table->boolean('is_table')->default(1);
            $table->boolean('is_order')->default(1);
            $table->string('birthday_message')->nullable();
            $table->double('price_km')->nullable()->default(0);
            $table->foreignIdFor(City::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Emoji::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(MenuTemplate::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(SuperAdmin::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
