<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInfoRequest extends FormRequest
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
            'name' => ['nullable'],
            'username' => ['nullable',Rule::unique('users')->where('restaurant_id',auth()->user()->restaurant_id)->ignore(auth()->user()->id,"id")],
            'email' => ['nullable',Rule::unique('users')->where('restaurant_id',auth()->user()->restaurant_id)->ignore(auth()->user()->id,"id")],
            'phone' => ['nullable','max:15',Rule::unique('users')->where('restaurant_id',auth()->user()->restaurant_id)->ignore(auth()->user()->id,"id")],
        ];
    }
}
