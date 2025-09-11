<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'name' => $this->name,
            'token' => $this->token,
            'type' => $this->type ? $this->type->name: null,
            'type_id' =>$this->type_id? $this->type_id : null,
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'note' => $this->restaurant->note,
                'name_en' => $this->restaurant->translate('en')->name,
                'name_ar' => $this->restaurant->translate('ar')->name,
                'note_en' => $this->restaurant->translate('en')->note,
                'note_ar' => $this->restaurant->translate('ar')->note,
                'name_url' => $this->restaurant->name_url,
                'facebook_url' => $this->restaurant->facebook_url,
                'instagram_url' => $this->restaurant->instagram_url,
                'whatsapp_phone' => $this->restaurant->whatsapp_phone,
                'end_date' => $this->restaurant->end_date,
                'message_bad' => $this->restaurant->message_bad,
                'message_good' => $this->restaurant->message_good,
                'message_perfect' => $this->restaurant->message_perfect,
                'cover' => $this->restaurant->getFirstMediaUrl('cover'),
                'logo' => $this->restaurant->getFirstMediaUrl('logo'),
                'color' => $this->restaurant->color,
                'background_color' => $this->restaurant->background_color,
                'f_color_category' => $this->restaurant->f_color_category,
                'f_color_sub' => $this->restaurant->f_color_sub,
                'f_color_item' => $this->restaurant->f_color_item,
                'f_color_rating' => $this->f_color_rating,
                'font_id_en' => $this->restaurant->font_id_en,
                'font_id_ar' => $this->restaurant->font_id_ar,
                'consumer_spending' => $this->restaurant->consumer_spending,
                'local_administration' => $this->restaurant->local_administration,
                'reconstruction' => $this->restaurant->reconstruction,
                'is_advertisement' => $this->restaurant->is_advertisement,
                'is_news' => $this->restaurant->is_news,
                'is_rate' => $this->restaurant->is_rate,
                'rate_format' => $this->restaurant->rate_format,
                'is_active' => $this->restaurant->is_active,
                'is_table' => $this->restaurant->is_table,
                'visited' => $this->restaurant->visited,
                'is_order' => $this->restaurant->is_order,
                'is_taxes' => $this->restaurant->is_taxes,
                'city_id' => $this->restaurant->city_id,
                'emoji_id' => $this->restaurant->emoji_id,
                'menu_template_id' => $this->restaurant->menu_template_id,
                'super_admin_id' => $this->restaurant->super_admin_id,
                'is_welcome_massege' => $this->restaurant->is_welcome_massege,
                'welcome' => $this->restaurant->welcome,
                'question' => $this->restaurant->question,
                'if_answer_no' => $this->restaurant->if_answer_no,
                'latitude' => $this->restaurant->latitude,
                'longitude' => $this->restaurant->longitude,
                // 'is_qr_nfc' => $this->restaurant->is_qr_nfc,
                'is_sub_move' => $this->restaurant->is_sub_move,
                'is_delivery' => $this->restaurant->is_delivery,
                'is_takeout' => $this->restaurant->is_takeout,
                'birthday_message' => $this->restaurant->birthday_message ?? null,
                'image_or_color' => $this->restaurant->image_or_color ?? null,
                'background_image_home_page' => $this->restaurant->getFirstMediaUrl('background_image_home_page') ?? null,
                'background_image_category' => $this->restaurant->getFirstMediaUrl('background_image_category') ?? null,
                'background_image_sub' => $this->restaurant->getFirstMediaUrl('background_image_sub') ?? null,
                'background_image_item' => $this->restaurant->getFirstMediaUrl('background_image_item') ?? null,
                'rate_opacity' => $this->restaurant->rate_opacity ?? null,
                'sub_opacity' => $this->restaurant->sub_opacity ?? null,
                'image_or_write' => $this->restaurant->image_or_write ?? null,
                'exchange_rate' => $this->restaurant->exchange_rate ?? null,
                'logo_shape' => $this->restaurant->logo_shape ?? null,
                'show_more_than_one_price' => $this->restaurant->show_more_than_one_price ?? null,
                'message_in_home_page' => $this->restaurant->message_in_home_page ?? null,
                'logo_home_page' => $this->restaurant->getFirstMediaUrl('logo_home_page') ?? $this->restaurant->getFirstMediaUrl('logo'),
                'fav_lang' => $this->restaurant->fav_lang ?? null,
                'font_size_welcome' => $this->restaurant->font_size_welcome ?? null,
                'font_type_welcome' => $this->restaurant->font_type_welcome ?? null,
                'font_size_category' => $this->restaurant->font_size_category ?? null,
                'font_type_category_en' => $this->restaurant->font_type_category_en ?? null,
                'font_type_category_ar' => $this->restaurant->font_type_category_ar ?? null,
                'font_size_item' => $this->restaurant->font_size_item ?? null,
                'font_type_item_en' => $this->restaurant->font_type_item_en ?? null,
                'font_type_item_ar' => $this->restaurant->font_type_item_ar ?? null,
                'font_bold_category' => $this->restaurant->font_bold_category ?? null,
                'font_bold_item' => $this->restaurant->font_bold_item ?? null,
                'empty_image' => $this->restaurant->empty_image ?? null,
                'home_opacity' => $this->restaurant->home_opacity ?? null,
                'price_km' => $this->restaurant->price_km ?? null,
                'price_type' => $this->restaurant->price_type ?? null,
                'share_item_whatsapp' => $this->restaurant->share_item_whatsapp ?? null,
                'admin_id' => $this->restaurant->admin_id,
                'translations' => $this->restaurant->translations->mapWithKeys(function($translation) {
                    return [
                        $translation->locale => [
                            'name' => $translation->name,
                            'note' => $translation->note
                        ]
                    ];
                }),
            ],
            'roles' => $this->roles,
            'permissions' => $this->permissions
        ];
        if($this->is_takeout == 1)
        $data['qr_takeout'] = env('APP_URL')."/".str_replace('public', 'storage', $this->qr_takeout);

        return $data;
    }
}
