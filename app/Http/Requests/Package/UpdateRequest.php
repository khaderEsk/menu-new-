<?php

namespace App\Http\Requests\Package;

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
            'id' => ['required','exists:packages,id'],
            'title_en' => ['required',Rule::unique('package_translations', 'title')->ignore($this->id,"package_id")],
            'title_ar' => ['required',Rule::unique('package_translations', 'title')->ignore($this->id,"package_id")],
            'price' => ['required_without:id','numeric'],
            'value' => ['required_without:id',Rule::unique('packages', 'value')->ignore($this->id,"id")],
        ];
    }
}
