<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    protected $fillable = [
        'price',
        'restaurant_id',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    protected $translatedAttributes = [
        'name',
    ];
}
