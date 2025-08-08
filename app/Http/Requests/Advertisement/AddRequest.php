<?php

namespace App\Http\Requests\Advertisement;

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
            'title' => ['required'],
            'from_date' => ['required','date','after_or_equal:today','before:to_date'],
            'to_date' => ['required','date','after_or_equal:today','after:from_date'],
            'image' => ['required','image'],
            'is_panorama' => ['required'],
            'hide_date' => ['required'],
        ];
    }
}
