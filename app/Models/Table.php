<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number_table',
        'number_of_chairs',
        'restaurant_id',
        'visited',
        'is_qr_table',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->num = $model->generateUniqueRandomString();
        });
    }

    /**
     * توليد سلسلة عشوائية فريدة مكونة من 5 أرقام على الأقل
     *
     * @return string
     */
    private function generateUniqueRandomString()
    {
        do {
            $randomString = $this->generateRandomDigits(5);
        } while (self::where('num', $randomString)->exists());

        return $randomString;
    }

    /**
     * توليد سلسلة من الأرقام العشوائية
     *
     * @param int $length
     * @return string
     */
    private function generateRandomDigits($length)
    {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= mt_rand(0, 9);
        }
        return $digits;
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function employeeTable(): HasMany
    {
        return $this->hasMany(EmployeeTable::class);
    }
}
