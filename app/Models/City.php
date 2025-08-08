<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    protected $fillable = [
      	'is_active',
    ];

    protected $translatedAttributes =
    [
        'name',
    ];

    protected $hidden = [
        'superAdmin_id',
        'created_at',
        'updated_at',
    ];

    public function superAdmins(): HasMany
    {
        return $this->hasMany(SuperAdmin::class);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }
}
