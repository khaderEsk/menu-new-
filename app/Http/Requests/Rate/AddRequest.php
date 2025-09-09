<?php

namespace App\Http\Requests\Rate;

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
            'rate' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'note' => ['nullable', 'max:1600'],
            'type' => ['required', 'in:person,anonymous'],
            'name' => ['required_if:type,person'],
            'phone' => ['required_if:type,person'],
            'gender' => ['required_if:type,person'],
            'birthday' => ['nullable', 'required_if:type,person', 'numeric'],
            'service' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'arakel' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'foods' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'drinks' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'sweets' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'games_room' => ['nullable', 'numeric', 'min:0', 'max:3'],
            // 'restaurant_id' => ['required',Rule::exists('restaurants','id')->whereNull('deleted_at')],
        ];
    }
}
