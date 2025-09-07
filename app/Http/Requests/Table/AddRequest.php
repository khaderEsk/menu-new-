<?php

namespace App\Http\Requests\Table;

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
            'number_table' => ['required','numeric',Rule::unique('tables','number_table')->where('restaurant_id',auth()->user()->restaurant_id)->whereNull('deleted_at')],
            'is_qr_table' => ['required','boolean'],
        ];
    }
}
