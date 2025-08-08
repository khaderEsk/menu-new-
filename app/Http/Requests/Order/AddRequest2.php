<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddRequest2 extends FormRequest
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
            'item_id' => ['required','integer',Rule::exists('items','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id)],
            'count' => ['required','integer','min:1'],
            'invoice_id' => ['required_without:table_id','nullable',Rule::exists('invoices','id')->where('restaurant_id',auth()->user()->restaurant_id)],
            'table_id' => ['required_without:invoice_id','nullable',Rule::exists('tables','id')->where('restaurant_id',auth()->user()->restaurant_id)],
        ];
    }
}
