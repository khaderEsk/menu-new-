<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowRequest extends FormRequest
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
            'city_id' => ['nullable','exists:cities,id'],
            'restaurant_manager_id' => ['nullable','exists:admins,id'],
            // 'city_super_admin_id' => [Rule::exists('city_super_admins','id')->whereNull('deleted_at'),'nullable'],
            'search' => ['nullable'],
            'per_page' => ['nullable']
        ];
    }
}
