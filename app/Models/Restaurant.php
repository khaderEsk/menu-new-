<?php

namespace App\Models;

use App\Enum\RateFormat;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Restaurant extends Model implements HasMedia, TranslatableContract
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Translatable;

    protected $fillable = [
        'name_url',
        'facebook_url',
        'instagram_url',
        'whatsapp_phone',
        'end_date',
        'message_bad',
        'message_good',
        'message_perfect',
        'color',
        'background_color',
        "f_color_category",
        "f_color_sub",
        "f_color_item",
        "f_color_rating",
        'font_id_en',
        'font_id_ar',
        'consumer_spending',
        'local_administration',
        'reconstruction',
        'is_news',
        'is_rate',
        'is_active',
        'is_table',
        'is_advertisement',
        'is_taxes',
        'city_id',
        'emoji_id',
        'menu_template_id',
        'super_admin_id',
        'admin_id',
        'visited',
        'rate_format',
        'welcome',
        'question',
        'if_answer_no',
        'is_welcome_massege',
        'latitude',
        'longitude',
        'accepted_by_waiter',
        'is_sub_move',
        'is_takeout',
        'is_delivery',
        'birthday_message',
        'image_or_color',
        'background_image_home_page',
        'background_image_category',
        'background_image_sub',
        'background_image_item',
        'rate_opacity',
        'sub_opacity',
        'image_or_write',
        'exchange_rate',
        'show_more_than_one_price',
        'logo_shape',
        'message_in_home_page',
        'qr_takeout',
        'logo_home_page',
        'fav_lang',
        'font_size_welcome',
        'font_type_welcome',
        'font_size_category',
        'font_type_category_en',
        'font_type_category_ar',
        'font_size_item',
        'font_type_item_en',
        'font_type_item_ar',
        'font_bold_category',
        'font_bold_item',
        'empty_image',
        'home_opacity',
        'price_km',
        'price_type',
        'share_item_whatsapp',
    ];

    protected $translatedAttributes = [
        'name',
        'note',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'rate_format' => RateFormat::class,
    ];

    public function getMessageBadAttribute($key)
    {
        if(is_null($key))
            return "";
    }
    public function getMessageGoodAttribute($key)
    {
        if(is_null($key))
            return "";
    }
    public function getMessagePerfectAttribute($key)
    {
        if(is_null($key))
            return "";
    }
    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function ipQr(): HasOne
    {
        return $this->hasOne(IpQr::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function superAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function menuTemplate(): BelongsTo
    {
        return $this->belongsTo(MenuTemplate::class);
    }

    public function emoji(): BelongsTo
    {
        return $this->belongsTo(Emoji::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function package()
    {
        return $this->belongsToMany(Package::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function fontEn()
    {
        return $this->belongsTo(Font::class,'font_id_en');
    }

    public function fontAr()
    {
        return $this->belongsTo(Font::class,'font_id_ar');
    }

    public function employeeTable(): HasMany
    {
        return $this->hasMany(EmployeeTable::class);
    }

    public function addreses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }
    public function fontTypeWelcome()
    {
        return $this->belongsTo(Font::class, 'font_type_welcome');
    }

    public function fontTypeCategoryEn()
    {
        return $this->belongsTo(Font::class, 'font_type_category_en');
    }

    public function fontTypeCategoryAr()
    {
        return $this->belongsTo(Font::class, 'font_type_category_ar');
    }

    public function fontTypeItemEn()
    {
        return $this->belongsTo(Font::class, 'font_type_item_en');
    }

    public function fontTypeItemAr()
    {
        return $this->belongsTo(Font::class, 'font_type_item_ar');
    }
}
