<?php

namespace App\Http\Resources;

use App\Models\Font;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // We now access the relationships that were eager-loaded in the controller.
        $lan = $request->header('language', 'en');

        // Safely access loaded relationships.
        $font = ($lan == 'ar') ? $this->whenLoaded('FontAr') : $this->whenLoaded('FontEn');
        $font_category = ($lan == "en") ? $this->whenLoaded('fontTypeCategoryEn') : $this->whenLoaded('fontTypeCategoryAr');
        $font_item = ($lan == "en") ? $this->whenLoaded('fontTypeItemEn') : $this->whenLoaded('fontTypeItemAr');
        $font_welcome = $this->whenLoaded('fontTypeWelcome');

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'note' => $this->note,
            'name_en' => $this->translate('en')->name ?? null,
            'name_ar' => $this->translate('ar')->name ?? null,
            'note_en' => $this->translate('en')->note ?? null,
            'note_ar' => $this->translate('ar')->note ?? null,
            'name_url' => $this->name_url,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'whatsapp_phone' => $this->whatsapp_phone,
            'end_date' => $this->end_date,
            'message_bad' => $this->message_bad,
            'message_good' => $this->message_good,
            'message_perfect' => $this->message_perfect,
            'bad_image' => $this->emoji->getFirstMediaUrl('emoji_bad'),
            'good_image' => $this->emoji->getFirstMediaUrl('emoji_good'),
            'perfect_image' => $this->emoji->getFirstMediaUrl('emoji_perfect'),
            'cover' => $this->getFirstMediaUrl('cover'),
            'logo' => $this->getFirstMediaUrl('logo'),
            'color' => $this->color,
            'background_color' => $this->background_color,
            'f_color_category' => $this->f_color_category,
            'f_color_sub' => $this->f_color_sub,
            'f_color_item' => $this->f_color_item,
            'f_color_rating' => $this->f_color_rating,
            'font_id_en' => $this->font_id_en,
            'font_id_ar' => $this->font_id_ar,
            'font' => $font,
            'consumer_spending' => $this->consumer_spending,
            'local_administration' => $this->local_administration,
            'reconstruction' => $this->reconstruction,
            'is_advertisement' => $this->is_advertisement,
            'is_news' => $this->is_news,
            'is_rate' => $this->is_rate,
            'rate_format' => $this->rate_format,
            'is_active' => $this->is_active,
            'is_table' => $this->is_table,
            'visited' => $this->visited,
            'is_order' => $this->is_order,
            'is_taxes' => $this->is_taxes,
            'city_id' => $this->city_id,
            'emoji_id' => $this->emoji_id,
            'menu_template_id' => $this->menu_template_id,
            'super_admin_id' => $this->super_admin_id,
            'is_welcome_massege' => $this->is_welcome_massege,
            'welcome' => $this->welcome,
            'question' => $this->question,
            'if_answer_no' => $this->if_answer_no,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accepted_by_waiter' => $this->accepted_by_waiter,
            'qr_offline' => $this->qr_offline ?? null,
            'is_sub_move' => $this->is_sub_move,
            'is_delivery' => $this->is_delivery,
            'is_takeout' => $this->is_takeout,
            'birthday_message' => $this->birthday_message ?? null,
            'image_or_color' => $this->image_or_color ?? null,
            'background_image_home_page' => $this->getFirstMediaUrl('background_image_home_page') ?? null,
            'background_image_category' => $this->getFirstMediaUrl('background_image_category') ?? null,
            'background_image_sub' => $this->getFirstMediaUrl('background_image_sub') ?? null,
            'background_image_item' => $this->getFirstMediaUrl('background_image_item') ?? null,
            'rate_opacity' => $this->rate_opacity ?? null,
            'sub_opacity' => $this->sub_opacity ?? null,
            'image_or_write' => $this->image_or_write ?? null,
            'exchange_rate' => $this->exchange_rate ?? null,
            'logo_shape' => $this->logo_shape ?? null,
            'show_more_than_one_price' => $this->show_more_than_one_price ?? null,
            'message_in_home_page' => $this->message_in_home_page ?? null,
            'logo_home_page' => $this->getFirstMediaUrl('logo_home_page') ?? $this->getFirstMediaUrl('logo'),
            'fav_lang' => $this->fav_lang ?? null,
            'font_size_welcome' => $this->font_size_welcome ?? null,
            'font_welcome' => $font_welcome->name ?? null,
            'font_type_welcome' => $this->font_type_welcome ?? null,
            'font_size_category' => $this->font_size_category ?? null,
            'font_type_category_en' => $this->font_type_category_en ?? null,
            'font_type_category_ar' => $this->font_type_category_ar ?? null,
            'font_category' => $font_category->name ?? null,
            'font_item' => $font_item->name ?? null,
            'font_size_item' => $this->font_size_item ?? null,
            'font_type_item_en' => $this->font_type_item_en ?? null,
            'font_type_item_ar' => $this->font_type_item_ar ?? null,
            'font_bold_category' => $this->font_bold_category ?? null,
            'font_bold_item' => $this->font_bold_item ?? null,
            'empty_image' => $this->empty_image ?? null,
            'home_opacity' => $this->home_opacity ?? null,
            'price_km' => $this->price_km ?? null,
            'price_type' => $this->price_type ?? null,
            'share_item_whatsapp' => $this->share_item_whatsapp ?? null,
            'admin_id' => $this->admin_id,
            'admins' => AdminResource::collection($this->whenLoaded('admins')),
            'translations' => $this->getTranslationsArray(),
        ];

        if($this->is_table == 1)
            $data['available_tables'] = $this->available_tables ?? null;

        if($this->is_takeout == 1)
            $data['qr_takeout'] = env('APP_URL')."/".str_replace('public', 'storage', $this->qr_takeout);

        return $data;
    }
}
