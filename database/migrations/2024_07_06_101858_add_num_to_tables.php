<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->string('num', 10)->unique()->nullable();
        });

        $records = DB::table('tables')->get();
        foreach ($records as $record) {
            DB::table('tables')
                ->where('id', $record->id)
                ->update(['num' => $this->generateUniqueRandomString()]);
            }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn('num');
        });
    }

    private function generateUniqueRandomString()
    {
        do {
            $randomString = $this->generateRandomDigits(5);
        } while (DB::table('tables')->where('num', $randomString)->exists());

        return $randomString;
    }

    private function generateRandomDigits($length)
    {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= mt_rand(0, 9);
        }
        return $digits;
    }
};
