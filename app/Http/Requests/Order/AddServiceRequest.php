<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddServiceRequest extends FormRequest
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
            'count' => ['required','integer','min:1'],
            'service_id' => ['required','integer',Rule::exists('services','id')->where('restaurant_id',auth()->user()->restaurant_id)],
            // 'invoice_id' => ['required',Rule::exists('invoices','id')->where('restaurant_id',auth()->user()->restaurant_id)],
            'invoice_id' => ['required_without:table_id'],
            'table_id' => ['required_without:invoice_id'],
        ];
    }
}
