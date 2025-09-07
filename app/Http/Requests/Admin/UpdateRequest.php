<?php

namespace App\Http\Requests\Admin;

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
            'name' => ['nullable','max:20'],
            'user_name' => ['nullable',Rule::unique('admins', 'user_name')->ignore(auth()->user()->id,"id")],
            'password' => ['nullable','min:8','max:25'],
            'mobile' => ['nullable','regex:/^\+?[0-9]{10,15}$/'],
        ];
    }
}
