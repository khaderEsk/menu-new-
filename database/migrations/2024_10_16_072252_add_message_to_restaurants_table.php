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
            $table->boolean('is_welcome_massege')->default(0);
            $table->string('welcome')->default('أهلا وسهلا بكم بمطعم');
            $table->string('question')->default('هل أنت بداخل المطعم');
            $table->string('if_answer_no')->default('للطلبات الخارجية التواصل على الرقم');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('is_welcome_massege');
            $table->dropColumn('welcome');
            $table->dropColumn('question');
            $table->dropColumn('if_answer_no');
        });
    }
};
