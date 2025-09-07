<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model implements TranslatableContract
{
    use HasFactory, Translatable ,SoftDeletes;
    public $translatedAttributes = ['name', 'description'];
    protected $fillable = ['status'];


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
