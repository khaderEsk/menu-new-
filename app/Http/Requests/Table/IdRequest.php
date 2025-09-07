<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdRequest extends FormRequest

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
            'id' => ['required',Rule::exists('tables','id')->where('restaurant_id',auth()->user()->restaurant_id)->whereNull('deleted_at')],
            // 'restaurant_id' => ['required','exists:restaurants,id'],
            'status' => ['nullable','in:accepted,preparation,done'],
        ];
    }
}
