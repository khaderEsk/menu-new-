<?php

namespace App\Http\Requests\Item;

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
            'search' => ['nullable'],
            'per_page' => ['nullable'],
            // 'category_id' => ['required',Rule::exists('categories','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id)],
            'category_id' => ['required_without:restaurant_id',Rule::exists('categories','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id)],
            'restaurant_id' => ['nullable',Rule::exists('restaurants','id')->where('id',auth()->user()->restaurant_id)],
        ];
    }
}
