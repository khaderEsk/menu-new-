<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionFact extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'unit',
        'kcal',
        'protein',
        'fat',
        'carbs'
    ];


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
