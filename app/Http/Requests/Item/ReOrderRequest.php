<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReOrderRequest extends FormRequest
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
            'id' => ['required',Rule::exists('items','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id)],
            'index' => ['required','min:1','numeric'],
            'category_id' => ['required','exists:categories,id'],
        ];
    }
}
