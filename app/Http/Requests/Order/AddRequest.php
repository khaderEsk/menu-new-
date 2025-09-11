<?php

namespace App\Http\Requests\Order;

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
            'data' => ['required', 'array'],
            'data.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->whereNull('deleted_at')->where('restaurant_id', auth()->user()->restaurant_id)],
            'data.*.count' => ['required', 'integer', 'min:1'],
            'invoice_id' => ['required_without:table_id', Rule::exists('invoices', 'id')->where('restaurant_id', auth()->user()->restaurant_id)],
            'table_id' => ['required_without:invoice_id', Rule::exists('tables', 'id')->where('restaurant_id', auth()->user()->restaurant_id)],
            'data.*.size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'data.*.toppings.*.topping_id' => ['nullable', 'exists:toppings,id', 'integer'],
            'data.*.components.*.component_id' => ['nullable', 'exists:components,id', 'integer'],
        ];
    }
}
