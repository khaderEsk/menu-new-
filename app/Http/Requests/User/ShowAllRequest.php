<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

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
            'type_id' => ['nullable'],
            'active' => ['nullable', 'in:0,1'],
            'search' => ['nullable'],
            'per_page' => ['nullable'],
            // 'role' => ['nullable'],
            'type_id' => ['nullable', 'integer', 'exists:types,id'],
            'startDate' => ['nullable', 'date', function ($attribute, $value, $fail) {
                if (request('endDate') && $value >= request('endDate')) {
                    $fail('The startDate must be less than endDate');
                }
            },],
            'endDate' => ['nullable', 'date', function ($attribute, $value, $fail) {
                if (request('startDate') && $value <= request('startDate')) {
                    $fail('The endDate must be greater than startDate');
                }
            },],
        ];
    }
}
