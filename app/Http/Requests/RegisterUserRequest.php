<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
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
            'restaurant_id' => ['required','exists:restaurants,id'],
            'name' => ['required'],
            'email' => ['nullable','email',Rule::unique('users')],
            'username' => [Rule::unique('users')->where('restaurant_id',$this->restaurant_id),'required'],
            'password' => ['required','min:8','max:25'],
            'phone' => ['required',Rule::unique('users'),'max:15'],
            'birthday' => ['nullable', 'date', 'before:' . now()->subYear()->toDateString()],
            'gender' => ['nullable'],
            'address' => ['nullable'],
            'city' => ['nullable'],
            'region' => ['nullable'],
            'longitude' => ['nullable'],
            'latitude' => ['nullable'],
            'fcm_token' => ['nullable'],
            'question' => ['required'],
            'answer' => ['required'],
        ];
    }
}
