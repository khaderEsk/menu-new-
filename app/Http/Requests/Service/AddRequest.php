<?php

namespace App\Http\Requests\Service;

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
            'name_en' => ['required',Rule::unique('service_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)],
            'name_ar' => ['required',Rule::unique('service_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
