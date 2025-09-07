<?php

namespace App\Http\Requests\Advertisement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdRestaurantRequest extends FormRequest
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
            'id' => [Rule::exists('advertisements','id')->whereNull('deleted_at'),'required'],
            'restaurant_id' => ['required',Rule::exists('restaurants','id')->whereNull('deleted_at')],

        ];
    }
}
