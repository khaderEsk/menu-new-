<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use HasFactory, HasApiTokens, HasRoles;

    protected $fillable = [
        'user_name',
        'password',
        'name',
        'mobile',
        'type',
        'fcm_token',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notificationable');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

}
