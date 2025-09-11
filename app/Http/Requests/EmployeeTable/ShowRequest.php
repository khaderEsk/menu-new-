<?php

namespace App\Http\Requests\EmployeeTable;

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
            'per_page' => ['nullable'],
            'search' => ['nullable'],
            'table_id' => ['nullable',Rule::exists('tables','id')->where('restaurant_id',auth()->user()->restaurant_id)],
            'type' => ['nullable'],
            'emp_id' => ['nullable'],
            'date' => ['nullable','date'],
        ];
    }
}
