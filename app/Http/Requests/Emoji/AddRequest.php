<?php

namespace App\Http\Requests\Emoji;

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
            'name' => [Rule::unique('emoji','name')->whereNull('deleted_at'),'required'],
            'bad_image' => ['required','image'],
            'good_image' => ['required','image'],
            'perfect_image' => ['required','image'],
        ];
    }
}
