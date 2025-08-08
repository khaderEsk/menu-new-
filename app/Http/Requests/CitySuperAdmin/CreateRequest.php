<?php

namespace App\Http\Requests\CitySuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
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
            'user_name' => [Rule::unique('super_admins','user_name')->whereNull('deleted_at'),'required'],
            'password' => ['required','min:8','max:25'],
            // 'city_id' => ['nullable','exists:cities,id'],
            'city_id' => ['nullable','required_if:role,City super admin,سوبر أدمن تابع لمدينة','exists:cities,id'],
            'role' => ['required'],
            'permission' => ['nullable'],
        ];
    }
}
