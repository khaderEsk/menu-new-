<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddRequest extends FormRequest
{
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
            'admin_id' => [Rule::exists('admins','id'),'nullable'],
            // 'name_en' => ['required',Rule::unique('restaurant_translations', 'name')->whereNull('deleted_at')],
            'name_en' => ['required',Rule::unique('restaurant_translations', 'name')->ignore($this->id,"restaurant_id")->whereNull('deleted_at')],
            'name_ar' => ['required',Rule::unique('restaurant_translations', 'name')->ignore($this->id,"restaurant_id")->whereNull('deleted_at')],
            //'name_url' => ['required','unique:restaurants,name_url','regex:/^[a-zA-Z0-9_\-]+$/'],
            'name_url' => ['required','unique:restaurants,name_url'],
            'facebook_url' => ['nullable'],
            'instagram_url' => ['nullable'],
            'whatsapp_phone' => ['nullable','unique:restaurants,whatsapp_phone'],
            'note_en' => ['required'],
            'note_ar' => ['required'],
            'message_bad' => ['nullable'],
            'message_good' => ['nullable'],
            'message_perfect' => ['nullable'],
            'color' => ['required'],
            'background_color' => ['nullable'],
            'f_color_category' => ['nullable'],
            'f_color_sub' => ['nullable'],
            'f_color_item' => ['nullable'],
            "f_color_rating" => ['nullable'],
            'font_id_en' => ['nullable', Rule::exists('fonts','id')],
            'font_id_ar' => ['nullable', Rule::exists('fonts','id')],
            'consumer_spending' => ['nullable','min:1','numeric','max:50'],
            'local_administration' => ['nullable','min:1','numeric','max:50'],
            'reconstruction' => ['nullable','min:1','numeric','max:50'],
            'type' => ['nullable'],
            'is_advertisement' => ['nullable','in:0,1'],
            'is_news' => ['nullable','in:0,1'],
            'is_rate' => ['required','in:0,1'],
            'rate_format' => ['required_if:is_rate,1','in:0,1'],
            'is_active' => ['required','in:0,1'],
            'is_table' => ['required','in:0,1'],
            'is_order' => ['required','in:0,1'],
            'is_taxes' => ['required','in:0,1'],
            'city_id' => [Rule::exists('cities','id'),'required'],
            'emoji_id' => [Rule::exists('emoji','id')->whereNull('deleted_at'),'required'],
            'menu_template_id' => [Rule::exists('menu_templates','id')->whereNull('deleted_at'),'required'],
            'cover' => ['required','image'],
            'logo' => ['required','image'],
            'is_welcome_massege' => 'nullable|boolean',
            'welcome' => 'nullable',
            'question' => 'nullable',
            'if_answer_no' => 'nullable',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'accepted_by_waiter' => 'nullable|boolean',
            'is_sub_move' => 'nullable|boolean',
            'is_delivery' => ['nullable', 'boolean'],
            'is_takeout' => ['nullable', 'boolean'],
            // 'password' => ['required','min:8','max:25'],
            // 'mobile' =>  ['required','regex:/^\+?[0-9]{10,15}$/'],
            // 'fcm_token' => ['nullable'],
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
