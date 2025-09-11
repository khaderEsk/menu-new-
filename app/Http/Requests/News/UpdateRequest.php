<?php

namespace App\Http\Requests\News;

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
            'id' => ['required',Rule::exists('news','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id)],
            'name_en' => ['required'],
            'name_ar' => ['required'],
            'description_en' => ['required','max:1700'],
            'description_ar' => ['required','max:1700'],
            'image' => ['nullable'],
        ];
    }
}
