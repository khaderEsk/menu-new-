<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifyPasswordRequest extends FormRequest
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
            'restaurant_id' => ['required','exists:restaurants,id'],
            'method' => ['required','in:0,1'],
            'username' => ['required_if:method,0'],
            'email' => ['required_if:method,1','email'],
            'question' => ['required_if:method,0'],
            'answer' => ['required_if:method,0'],
        ];
    }
}
