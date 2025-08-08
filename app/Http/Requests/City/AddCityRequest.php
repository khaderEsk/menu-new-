<?php

namespace App\Http\Requests\City;

use Illuminate\Foundation\Http\FormRequest;

class AddCityRequest extends FormRequest
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
            'name_en' => ['required','unique:city_translations,name'],
            'name_ar' => ['required','unique:city_translations,name'],
        ];
    }
}
