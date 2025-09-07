<?php

namespace App\Http\Requests\CitySuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowAllRequest extends FormRequest
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
            'city_id' => [Rule::exists('cities','id'),'nullable'],
            'active' => ['nullable','in:0,1'],
            'search' => ['nullable'],
            'per_page' => ['nullable'],
            'role' => ['nullable']
        ];
    }
}
