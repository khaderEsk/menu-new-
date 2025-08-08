<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantRequest extends FormRequest
{
    public mixed $sub_opacity;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['nullable',Rule::unique('restaurant_translations', 'name')->ignore(auth()->user()->restaurant_id,"restaurant_id")],
            'name_ar' => ['nullable',Rule::unique('restaurant_translations', 'name')->ignore(auth()->user()->restaurant_id,"restaurant_id")],
            'facebook_url' => ['nullable'],
            'instagram_url' => ['nullable'],
            'whatsapp_phone' => ['nullable',Rule::unique('restaurants', 'whatsapp_phone')->ignore(auth()->user()->restaurant_id,"id")],
            'note_en' => ['nullable'],
            'note_ar' => ['nullable'],
            'message_bad' => ['nullable'],
            'message_good' => ['nullable'],
            'message_perfect' => ['nullable'],
            'color' => ['nullable'],
            'background_color' => ['nullable'],
            "f_color_category" => ['nullable'],
            "f_color_sub" => ['nullable'],
            "f_color_item" => ['nullable'],
            "f_color_rating" => ['nullable'],
            'welcome' => ['nullable'],
            'question' => ['nullable'],
            'if_answer_no' => ['nullable'],
            'consumer_spending' => ['nullable','min:1','numeric','max:50'],
            'local_administration' => ['nullable','min:1','numeric','max:50'],
            'reconstruction' => ['nullable','min:1','numeric','max:50'],
            'cover' => ['nullable','image'],
            'logo' => ['nullable','image'],
            'birthday_message' => ['nullable'],
            'image_or_color' => ['nullable','in:0,1'],
            'background_image_home_page' => ['nullable'],
            'background_image_category' => ['nullable'],
            'background_image_sub' => ['nullable'],
            'background_image_item' => ['nullable'],
            'rate_opacity' => ['nullable'],
            'sub_opacity' => ['nullable'],
            'image_or_write' => ['nullable'],
            'exchange_rate' => ['nullable'],
            'show_more_than_one_price' => ['nullable','in:0,1'],
            'logo_shape' => ['nullable'],
            'message_in_home_page' => ['nullable'],
            'logo_home_page' => ['nullable'],
            'fav_lang' => ['nullable'],
            'font_size_welcome' => ['nullable'],
            'font_type_welcome' => ['nullable'],
            'font_size_category' => ['nullable'],
            'font_type_category_en' => ['nullable'],
            'font_type_category_ar' => ['nullable'],
            'font_size_item' => ['nullable'],
            'font_type_item_en' => ['nullable'],
            'font_type_item_ar' => ['nullable'],
            'font_bold_category' => ['nullable'],
            'font_bold_item' => ['nullable'],
            'empty_image' => ['nullable'],
            'home_opacity' => ['nullable'],
            'price_km'  => ['nullable'],
            'price_type' => ['nullable'],
            'share_item_whatsapp' => ['nullable'],
        ];
    }
}

