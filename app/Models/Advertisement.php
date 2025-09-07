<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Advertisement extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'from_date',
        'to_date',
        'restaurant_id',
        'is_panorama',
        'hide_date',
    ];

    protected $hidden = [
        'restaurant_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function ScopeActive($query)
    {
        return $query->whereDate('from_date', '<=', \now())->whereDate('to_date', '>=', \now());
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
