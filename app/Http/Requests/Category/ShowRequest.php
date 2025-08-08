<?php

namespace App\Http\Requests\Category;

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
            // 'id' => [Rule::exists('admins','id')->whereNull('deleted_at')->where('admin_id',auth('admins')->user()->id),'nullable'],
            'search' => ['nullable'],
            'per_page' => ['nullable'],
            'category_id' => ['nullable',Rule::exists('categories','id')->whereNull('deleted_at')],
            // 'category_id' => ['nullable',Rule::exists('categories','id')->whereNull('deleted_at')->whereNull('category_id')],

        ];
    }
}
