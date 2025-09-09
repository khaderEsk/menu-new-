<?php

namespace App\Http\Requests\Rate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowRequest extends FormRequest
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
            'restaurant_id' => ['nullable', Rule::exists('restaurants', 'id')->whereNull('deleted_at')],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'type' => ['nullable', 'in:person,anonymous'],
            'rate' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'from_age' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    if (request('to_age') && $value >= request('to_age')) {
                        $fail(trans('locale.TheFromAgeMustBe'));
                    }
                },
            ],
            'to_age' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    if (request('from_age') && $value <= request('from_age')) {
                        $fail(trans('locale.TheToAgeMustBe'));
                    }
                },
            ],
            'gender' => ['nullable', 'in:male,female'],
            'per_page' => ['nullable'],
        ];
    }
}
