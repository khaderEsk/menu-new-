<?php

namespace App\Http\Requests\CitySuperAdmin;

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
            'id' => [Rule::exists('super_admins','id')->whereNull('deleted_at'),'required'],
            'name' => ['required_without:id','filled',],
            'user_name' => ['required_without:id','filled',Rule::unique('super_admins','user_name')->ignore($this->id,'id')->whereNull('deleted_at')],
            'password' => ['nullable','filled','min:8','max:25'],
            'city_id' => ['required_if:role,City super admin,سوبر أدمن تابع لمدينة',Rule::exists('cities','id'),'nullable'],
            'restaurant_id' => ['nullable'],
            'role' => ['required'],
            'permission' => ['nullable'],
        ];
    }
}
