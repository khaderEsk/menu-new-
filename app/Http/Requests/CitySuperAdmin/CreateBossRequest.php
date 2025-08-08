<?php

namespace App\Http\Requests\CitySuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBossRequest extends FormRequest
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
            'name' => ['required'],
            'user_name' => [Rule::unique('admins')->whereNull('deleted_at'),'required'],
            'password' => ['required','min:8','max:25'],
            'mobile' => ['required','regex:/^\+?[0-9]{10,15}$/'],

        ];
    }
}
