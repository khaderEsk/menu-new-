<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Item extends Model implements HasMedia, TranslatableContract
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Translatable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'price',
        'index',
        'is_active',
        'category_id',
        'restaurant_id',
        'is_panorama',
        'item_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function ScopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => floatval($value),
        );
    }

    // public function ScopeOrder($query)
    // {
    //     return $query->orderBy('index');
    // }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function imagesUrl()
    {
        $mediaItems = $this->getMedia('item');
        $images = [];
        foreach ($mediaItems as $media) {
            $images[] = $media->getUrl();
        }
        return $images;
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(Size::class);
    }

    public function toppings(): HasMany
    {
        return $this->hasMany(Topping::class);
    }
    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }
    public function nutrition(): HasOne
    {
        return $this->hasOne(NutritionFact::class);
    }
}
    