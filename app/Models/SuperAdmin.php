<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;


class SuperAdmin extends Authenticatable
{
    use HasFactory, HasApiTokens, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'user_name',
        'password',
        'is_active',
        'city_id',
        'restaurant_id',
        'email'
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function menuTemplates(): HasMany
    {
        return $this->hasMany(MenuTemplate::class);
    }

    public function emoji(): HasMany
    {
        return $this->hasMany(Emoji::class);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    // public function dataEntries(): MorphMany
    // {
    //     return $this->morphMany(DataEntry::class, 'data_entryable');
    // }


    // protected array $guard_name = ['api', 'web'];
    protected $guard_name = 'web';
}
