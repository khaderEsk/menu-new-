<?php

namespace App\Http\Requests\Delivey;

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
            'restaurant_id' => ['required','exists:restaurants,id'],
            'name' => ['required'],
            'username' => [Rule::unique('users')->where('role',1)->where('restaurant_id',$this->restaurant_id),'required'],
            'password' => ['required','min:8','max:25'],
            'phone' => ['required','max:15'],
            'birthday' => ['nullable', 'date', 'before:' . now()->subYear()->toDateString()],
            'gender' => ['nullable'],
            'address' => ['nullable'],
            'image' => ['nullable'],
            'fcm_token' => ['nullable'],
        ];
    }
}
