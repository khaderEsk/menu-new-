<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Authenticatable
{
    use HasFactory, HasApiTokens, SoftDeletes, HasRoles;

    protected $fillable = [
        'user_name',
        'password',
        'name',
        'phone',
        'birthday',
        'gender',
        'restaurant_id',
        'table_id',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
        'password',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    protected $guard_name = 'web';

}
