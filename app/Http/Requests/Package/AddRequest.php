<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;

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
            'title_en' => ['required','unique:package_translations,title'],
            'title_ar' => ['required','unique:package_translations,title'],
            'price' => ['required','numeric'],
            'value' => ['required','unique:packages,value'],
        ];
    }
}
