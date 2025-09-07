<?php

namespace App\Http\Requests\RestaurantManager;

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
            'id' => [Rule::exists('admins','id')->whereNull('deleted_at'),'required'],
            'name' => ['required_without:id'],
            'user_name' => ['required_without:id',Rule::unique('admins','user_name')->ignore($this->id,'id')->whereNull('deleted_at'),'required'],
            'password' => ['nullable','filled','min:8','max:25'],
            'mobile' => ['required_without:id','regex:/^\+?[0-9]{10,15}$/'],
        ];
    }
}
