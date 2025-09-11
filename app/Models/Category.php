<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia, TranslatableContract
{
    use HasFactory,  InteractsWithMedia, SoftDeletes, Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'index',
        'is_active',
        'restaurant_id',
        'category_id',
    ];

    protected $hidden = [
        'admin_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function ScopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    // public function ScopeOrder($query)
    // {
    //     return $query->orderBy('index');
    // }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class);
    }   

    public function hasSubcategories()
    {
        return $this->children()->exists();
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function hasItems()
    {
        return $this->items()->exists();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class);
    }

}
