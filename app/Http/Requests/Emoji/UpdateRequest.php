<?php

namespace App\Http\Requests\Emoji;

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
            'id' => ['required', 'exists:emoji,id'],
            'name' => ['required_without:id','filled',Rule::unique('emoji', 'name')->ignore($this->id,"id")->whereNull('deleted_at')],
            'bad_image' => ['nullable','image'],
            'good_image' => ['nullable','image'],
            'perfect_image' => ['nullable','image'],
        ];
    }
}
