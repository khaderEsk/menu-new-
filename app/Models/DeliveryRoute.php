<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRoute extends Model
{
    use HasFactory;
    protected $fillable = [
        'start_lat',
        'start_lon',
        'end_lat',
        'end_lon',
        'distance',
        'duration',
        'order_id',
    ];

    protected $casts = [
        'start_lat' => 'float',
        'start_lon' => 'float',
        'end_lat' => 'float',
        'end_lon' => 'float',
        'speed' => 'float',
        'distance' => 'float',
        'duration' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
