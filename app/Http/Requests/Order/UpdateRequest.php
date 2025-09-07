<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

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
            'id' => ['required','exists:orders,id'],
            'count' => ['required_without:id','filled','numeric','min:1'],
            'item_id' => ['required_without:id','filled','exists:items,id'],
            // 'table_id' => ['required_without:id','filled','exists:tables,id'],
            'status' => ['nullable','in:waiting,accepted,preparation,done'],
        ];
    }
}
