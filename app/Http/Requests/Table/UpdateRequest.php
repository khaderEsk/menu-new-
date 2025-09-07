<?php

namespace App\Http\Requests\Table;

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
            'id' => ['required','exists:tables,id'],
            'number_table' => ['required_without:id','numeric',Rule::unique('tables','number_table')->ignore($this->id, 'id')->where('restaurant_id',auth()->user()->restaurant_id)->whereNull('deleted_at')],
            'is_qr_table' => ['required','boolean'],
        ];
    }
}
