<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowByNameRequest extends FormRequest
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
            'restaurant_name' => [Rule::exists('restaurants','name_url')->whereNull('deleted_at'),'required_without:id'],
            'id' => [Rule::exists('restaurants','id')->whereNull('deleted_at'),'required_without:restaurant_name'],
            'qr_code' => ['nullable'],
        ];
    }
}
