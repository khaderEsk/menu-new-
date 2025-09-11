<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckCouponRequest extends FormRequest
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
            'code' => ['required',Rule::exists('coupons','code')
            ->where('is_active',1)
            ->where('restaurant_id',auth()->user()->restaurant_id)
            ->where(function ($query) {
                $query->whereDate('from_date', '<=', now())
                ->whereDate('to_date', '>=', now());
                })
            ],
            'total' => ['required'],
        ];
    }
}
