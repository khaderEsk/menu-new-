<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpQr extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'restaurant_url',
        'website',
        'phone',
        'mobile',
        'facebook_url',
        'instagram_url',
        "whatsapp_phone",
        "address",
        "restaurant_id",
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
