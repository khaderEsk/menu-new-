<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model implements TranslatableContract
{
    use HasFactory, SoftDeletes, Translatable;

    protected $fillable = [
        'price',
        'count',
        'table_id',
        'customer_id',
        'invoice_id',
        'restaurant_id',
        'user_id',
        'toppings',
        'components',
        'size',
        'item_id',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    protected $cast = [
        'toppings' => 'array',
        'components' => 'array'
    ];
    protected $translatedAttributes = [
        'name',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
