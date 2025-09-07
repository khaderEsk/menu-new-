<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rate',
        'note',
        'customer_id',
        'restaurant_id',
        'service',
        'arakel',
        'foods',
        'drinks',
        'sweets',
        'games_room',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'rate' => 'int',
        'service' => 'int',
        'arakel' => 'int',
        'foods' => 'int',
        'drinks' => 'int',
        'sweets' => 'int',
        'games_room' => 'int',
        // 'id'=>'int'
    ];
    // public function ScopeOrder($query)
    // {
    //     return $query->orderBy('created_at', 'desc');
    // }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
