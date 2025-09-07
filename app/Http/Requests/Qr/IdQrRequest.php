<?php

namespace App\Http\Requests\Qr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdQrRequest extends FormRequest
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
            "restaurant_id" => [Rule::exists('restaurants','id'),'required'],
        ];
    }
}
