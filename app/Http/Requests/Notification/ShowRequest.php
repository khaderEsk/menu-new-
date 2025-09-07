<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

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
            'address' => ['nullable','string'],
            'from_date' => ['nullable','date',
                        function ($attribute, $value, $fail) {
                            if (request('to_age') && strtotime($value) >= strtotime(request('to_age'))) {
                                $fail(trans('locale.TheFromAgeMustBe'));
                            }
                        },],
            'to_date' => ['nullable','date',
                        function ($attribute, $value, $fail) {
                            if (request('from_age') && strtotime($value) <= strtotime(request('from_age'))) {
                                $fail(trans('locale.TheToAgeMustBe'));
                            }
                        },],
            'from_age' => ['nullable','numeric',
                        function ($attribute, $value, $fail) {
                            if (request('to_age') && $value >= request('to_age')) {
                                $fail(trans('locale.TheFromAgeMustBe'));
                            }
                        },],
            'to_age' => ['nullable','numeric',
                        function ($attribute, $value, $fail) {
                            if (request('from_age') && $value <= request('from_age')) {
                                $fail(trans('locale.TheToAgeMustBe'));
                            }
                        },],
            'items' => ['nullable','array'],
            'items.*' => ['integer'],
            'gender' => ['nullable','in:male,female'],
            'per_page' => ['nullable'],
        ];
    }
}
