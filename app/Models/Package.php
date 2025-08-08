<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    protected $fillable = [
        'value',
        'price',
        'is_active',
    ];

    protected $translatedAttributes = [
        'title',
    ];

    protected $hidden = [
        'updated_at',
    ];

    public function restaurant()
    {
        return $this->belongsToMany(Restaurant::class);
    }
}
