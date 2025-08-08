<?php

namespace App\Http\Requests\Advertisement;

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
            'id' => [Rule::exists('advertisements','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id),'required'],
            'title' => ['required_without:id','filled',],
            'from_date' => ['required_without:id','filled','date','after_or_equal:today','before:to_date'],
            'to_date' => ['required_without:id','filled','date','after_or_equal:today','after:from_date'],
            'image' => ['nullable','image'],
            'is_panorama' => ['required'],
            'hide_date' => ['required'],
        ];
    }
}
