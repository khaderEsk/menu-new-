<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdInvoiceRequest extends FormRequest
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
            'delivery_id' => ['required','exists:users,id'],
            'invoice_id' => ['required','exists:invoices,id'],
            'delivery_price' => ['nullable','numeric'],
        ];
    }
}
