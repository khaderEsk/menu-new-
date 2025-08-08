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
            $table->unsignedBigInteger('font_type_welcome')->default(1);
            $table->foreign('font_type_welcome')->references('id')->on('fonts')->cascadeOnDelete();
            $table->unsignedBigInteger('font_type_item_ar')->default(1);
            $table->foreign('font_type_item_ar')->references('id')->on('fonts')->cascadeOnDelete();
            $table->unsignedBigInteger('font_type_category_en')->default(1);
            $table->foreign('font_type_category_en')->references('id')->on('fonts')->cascadeOnDelete();
            $table->unsignedBigInteger('font_type_category_ar')->default(1);
            $table->foreign('font_type_category_ar')->references('id')->on('fonts')->cascadeOnDelete();
            $table->unsignedBigInteger('font_type_item_en')->default(1);
            $table->foreign('font_type_item_en')->references('id')->on('fonts')->cascadeOnDelete();
            $table->integer('font_size_welcome')->nullable();
            $table->integer('font_size_category')->nullable();
            $table->integer('font_size_item')->nullable();
            $table->boolean('font_bold_category')->nullable();
            $table->boolean('font_bold_item')->nullable();
            $table->string('empty_image')->nullable();
            $table->double('home_opacity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('font_size_welcome');
            $table->dropColumn('font_type_welcome');
            $table->dropColumn('font_size_category');
            $table->dropColumn('font_type_category_en');
            $table->dropColumn('font_type_category_ar');
            $table->dropColumn('font_size_item');
            $table->dropColumn('font_type_item_en');
            $table->dropColumn('font_type_item_ar');
            $table->dropColumn('font_bold_category');
            $table->dropColumn('font_bold_item');
            $table->dropColumn('empty_image');
            $table->dropColumn('home_opacity');
        });
    }
};
