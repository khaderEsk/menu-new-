<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'super_admin_id',
        'name',
        'is_active',
    ];

    protected $hidden = [
        'super_admin_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }
}
