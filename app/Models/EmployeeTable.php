<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_time',
        'table_id',
        'admin_id',
        'restaurant_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function Table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }
}
