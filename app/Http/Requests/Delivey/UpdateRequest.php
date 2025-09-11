<?php

namespace App\Http\Requests\Delivey;

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
            'id' => ['required','exists:users,id'],
            'restaurant_id' => ['required','exists:restaurants,id'],
            'name' => ['required_without:id'],
            'username' => [Rule::unique('users')->where('restaurant_id',$this->restaurant_id)->ignore($this->id,"id"),'required'],
            'password' => ['required_without:id','min:8','max:25'],
            'phone' => ['required_without:id','max:15'],
            'birthday' => ['nullable', 'date', 'before:' . now()->subYear()->toDateString()],
            'gender' => ['nullable'],
            'address' => ['nullable'],
            'image' => ['nullable'],
        ];
    }
}
