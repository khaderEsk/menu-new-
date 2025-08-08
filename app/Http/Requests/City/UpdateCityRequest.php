<?php

namespace App\Http\Requests\City;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends FormRequest
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
            'id' => ['required','exists:cities,id'],
            'name_en' => ['required',Rule::unique('city_translations', 'name')->ignore($this->id,"city_id")],
            'name_ar' => ['required',Rule::unique('city_translations', 'name')->ignore($this->id,"city_id")],
        ];
    }
}
