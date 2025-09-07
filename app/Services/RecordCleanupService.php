<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecordCleanupService
{
    public function deleteOldRecords($tableName, $dateColumn)
    {
        $date = Carbon::now()->subDays(30);
        DB::table($tableName)->where($dateColumn, '<', $date)->delete();
    }
}
