<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'id' => ['required', Rule::exists('restaurants', 'id')->whereNull('deleted_at')],
            'admin_id' => [Rule::exists('admins', 'id'), 'nullable'],
            'name_en' => ['required', Rule::unique('restaurant_translations', 'name')->ignore($this->id, "restaurant_id")->whereNull('deleted_at')],
            'name_ar' => ['required', Rule::unique('restaurant_translations', 'name')->ignore($this->id, "restaurant_id")->whereNull('deleted_at')],
            'name_url' => ['required_without:id', 'filled', Rule::unique('restaurants', 'name_url')->ignore($this->id, 'id')],
            // 'name_url' => ['required_without:id','filled','regex:/^[a-zA-Z0-9_\-]+$/',Rule::unique('restaurants','name_url')->ignore($this->id, 'id')],
            'facebook_url' => ['nullable'],
            'instagram_url' => ['nullable'],
            'whatsapp_phone' => ['nullable', Rule::unique('restaurants', 'whatsapp_phone')->ignore($this->id, "id")],
            // 'date' => ['nullable','date'],
            'note_en' => ['required'],
            'note_ar' => ['required'],
            'message_bad' => ['nullable'],
            'message_good' => ['nullable'],
            'message_perfect' => ['nullable'],
            'color' => ['required_without:id'],
            'background_color' => ['nullable'],
            'f_color_category' => ['nullable'],
            'f_color_sub' => ['nullable'],
            'f_color_item' => ['nullable'],
            "f_color_rating" => ['nullable'],
            'font_id_en' => ['nullable', 'filled', Rule::exists('fonts', 'id')],
            'font_id_ar' => ['nullable', 'filled', Rule::exists('fonts', 'id')],
            'consumer_spending' => ['nullable', 'min:1', 'numeric', 'max:50'],
            'local_administration' => ['nullable', 'min:1', 'numeric', 'max:50'],
            'reconstruction' => ['nullable', 'min:1', 'numeric', 'max:50'],
            'is_advertisement' => ['required_without:id', 'in:0,1'],
            'is_news' => ['required_without:id', 'in:0,1'],
            'is_rate' => ['required_without:id', 'in:0,1'],
            'rate_format' => ['required_without:id', 'in:0,1'],
            'is_active' => ['required_without:id', 'in:0,1'],
            'is_table' => ['required_without:id', 'in:0,1'],
            'is_order' => ['required_without:id', 'in:0,1'],
            'is_taxes' => ['required_without:id', 'in:0,1'],
            'city_id' => ['required_without:id', Rule::exists('cities', 'id')],
            'emoji_id' => ['required_without:id', Rule::exists('emoji', 'id')->whereNull('deleted_at')],
            'menu_template_id' => ['required_without:id', Rule::exists('menu_templates', 'id')->whereNull('deleted_at')],
            'cover' => ['nullable', 'image'],
            'logo' => ['nullable', 'image'],
            'welcome' => 'nullable',
            'question' => 'nullable',
            'if_answer_no' => 'nullable',
            'is_welcome_massege' => 'nullable|boolean',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'accepted_by_waiter' => 'nullable|boolean',
            'is_sub_move' => 'nullable|boolean',
            'is_delivery' => ['nullable', 'boolean'],
            'is_takeout' => ['nullable', 'boolean'],
            'image_or_color' => ['nullable', 'in:0,1'],
            'background_image_home_page' => ['nullable'],
            'background_image_category' => ['nullable'],
            'background_image_sub' => ['nullable'],
            'background_image_item' => ['nullable'],
            'rate_opacity' => ['nullable'],
            'sub_opacity' => ['nullable'],
            'image_or_write' => ['nullable'],
            'exchange_rate' => ['nullable'],
            'show_more_than_one_price' => ['nullable', 'in:0,1'],
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
            'user_link' => ['nullable' , 'string'],
            'delivery_link' => ['nullable' , 'string'],
            'admin_link' => ['nullable' , 'string']
        ];
    }
}
