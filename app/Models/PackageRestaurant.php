<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PackageRestaurant extends Pivot
{
    protected $fillable = [
        'package_id',
        'restaurant_id',
    ];
}
