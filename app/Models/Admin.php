<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory, HasApiTokens, SoftDeletes, HasRoles, Notifiable;

    protected $fillable = [
        'user_name',
        'password',
        'name',
        'mobile',
        'type',
        'fcm_token',
        'restaurant_id',
        'type_id',
        'email',
        'code'
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function find(mixed $id) {}

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function employeeTables(): HasMany
    {
        return $this->hasMany(EmployeeTable::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    protected $guard_name = 'web';
}
