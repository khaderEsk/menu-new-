<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Topping extends Model implements HasMedia, TranslatableContract
{
    use HasFactory, InteractsWithMedia, Translatable, SoftDeletes;
    public $translatedAttributes = ['name'];
    protected   $fillable = ['price', 'item_id'];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => floatval($value),
        );
    }

    public function Item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function cartItems()
    {
        return $this->belongsToMany(CartItem::class, 'cart_item_topping');
    }
}
