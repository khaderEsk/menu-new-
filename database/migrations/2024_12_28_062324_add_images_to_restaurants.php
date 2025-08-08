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
            $table->boolean('image_or_color')->default(0);
            $table->string('background_image_home_page')->nullable();
            $table->string('background_image_category')->nullable();
            $table->string('background_image_sub')->nullable();
            $table->string('background_image_item')->nullable();
            $table->double('rate_opacity')->nullable();
            $table->double('sub_opacity')->nullable();
            $table->boolean('image_or_write')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('image_or_color');
            $table->dropColumn('background_image_home_page');
            $table->dropColumn('background_image_category');
            $table->dropColumn('background_image_sub');
            $table->dropColumn('background_image_item');
            $table->dropColumn('rate_opacity');
            $table->dropColumn('sub_opacity');
            $table->dropColumn('image_or_write');
        });
    }
};
