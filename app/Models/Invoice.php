<?php

namespace App\Models;

use App\Enum\InvoiceStatus;
use App\Enum\StatusInvoice;
use App\Observers\InvoiceObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

// #[ObservedBy([InvoiceObserver::class])]  
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'num',
        'price',
        'total',
        'status',
        'consumer_spending',
        'local_administration',
        'reconstruction',
        'customer_id',
        'table_id',
        'restaurant_id',
        'user_id',
        'accepted',
        'delivery_id',
        'is_delivery',
        'receipt_at',
        'customer_received_at',
        'address_id',
        'delivery_price',
        'discount',
        'admin_id'
    ];
    protected $casts = [
        'status' => InvoiceStatus::class,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class)->withTrashed();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // public function getStatusAttribute($value)
    // {
    //     return InvoiceStatus::from($value)->label();
    // }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withTrashed();
    }
    // public function setStatusAttribute($status)
    // {
    //     $this->attributes['status'] = $status->value;
    // }
    public function deliveryRoute()
    {
        return $this->hasOne(DeliveryRoute::class);
    }
    public function getDeliveryDurationMinAttribute(): float
    {
        // Return the duration of the related route, or 0 if no route exists.
        return $this->deliveryRoute->duration ?? 0;
    }
}
