<?php

namespace App\Http\Requests\Qr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddQrRequest extends FormRequest
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
            'id' => ['nullable',Rule::exists('ip_qrs','id')],
            "restaurant_id" => ['required',Rule::exists('restaurants','id')->whereNull('deleted_at')],
            // 'name' => [Rule::unique('ip_qrs','name')->ignore($this->id, 'id'),'nullable'],
            // 'email' => [Rule::unique('ip_qrs','email')->ignore($this->id, 'id'),'nullable'],
            'name' => [Rule::unique('ip_qrs','name')->ignore($this->restaurant_id, 'restaurant_id'),'nullable'],
            'email' => [Rule::unique('ip_qrs','email')->ignore($this->restaurant_id, 'restaurant_id'),'nullable'],
            'restaurant_url' => 'nullable',
            'phone' => 'nullable',
            'facebook_url' => 'nullable',
            'instagram_url' => 'nullable',
            "whatsapp_phone" => 'nullable',
            "address" => 'nullable',
            "website" => 'nullable',

        ];
    }
}
