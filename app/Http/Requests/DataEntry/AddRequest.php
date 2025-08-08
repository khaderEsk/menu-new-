<?php

namespace App\Http\Requests\DataEntry;

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
            'name' => ['required'],
            'user_name' => [Rule::unique('super_admins'),'required'],
            'password' => ['required','min:8','max:25'],
            'city_id' => ['nullable',Rule::exists('cities','id')],
        ];
    }
}
