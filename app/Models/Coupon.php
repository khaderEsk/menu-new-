<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'from_date',
        'to_date',
        'type',
        'percent',
        'is_active',
        'restaurant_id',
        'qr',
        'driver_token',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class , 'user_coupons')->withPivot('used', 'used_at')->withTimestamps();
    }
}
