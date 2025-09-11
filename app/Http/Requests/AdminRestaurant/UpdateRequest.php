<?php

namespace App\Http\Requests\AdminRestaurant;

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
            'name' => ['nullable','max:20','filled'],
            'user_name' => ['nullable',Rule::unique('admins', 'user_name')->ignore($this->id,"id")->whereNull('deleted_at'), 'min:2', 'max:20'],
            'password' => ['nullable','filled','min:8','max:25'],
            'mobile' => ['nullable','filled','regex:/^\+?[0-9]{10,15}$/',Rule::unique('admins', 'mobile')->ignore($this->id,"id")],
            'restaurant_id' => ['nullable'],
            'type_id' => ['nullable','exists:types,id'],
            'category' => [
                'array',
                Rule::requiredIf(function () {
                    return in_array($this->input('type_id'), [4, 8]);
                }),
            ],

        ];
    }
}
