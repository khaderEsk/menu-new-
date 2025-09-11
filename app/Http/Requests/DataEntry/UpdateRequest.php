<?php

namespace App\Http\Requests\DataEntry;

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
            'id' => ['required','exists:super_admins,id'],
            'name' => ['required'],
            'user_name' => [Rule::unique('super_admins', 'user_name')->ignore($this->id),'required'],
            'password' => ['required','min:8','max:25'],
            'city_id' => ['nullable',Rule::exists('cities','id')],
        ];
    }
}
