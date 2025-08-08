<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'title',
        'body',
        'phone',
        'read_at',
    ];

    protected $hidden = [
        'updated_at',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
