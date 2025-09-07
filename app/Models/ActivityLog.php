<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'loggable_id',
        'loggable_type',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'original_data',
        'new_data',
        'ip_address'
    ];

    // public static function deleteOldLogs()
    // {
    //     $date = Carbon::now()->subDays(30);
    //     self::where('created_at', '<', $date)->delete();
    // }

    public function loggable()
    {
        return $this->morphTo();
    }
}

