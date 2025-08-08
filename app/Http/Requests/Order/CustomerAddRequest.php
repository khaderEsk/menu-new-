<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerAddRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function rules()
    {
        return [
            'data' => ['required', 'array'],
            'data.*.item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')
                    ->whereNull('deleted_at')
                    ->where('restaurant_id', $this->user()->restaurant_id)
            ],
            'data.*.count' => ['required', 'integer', 'min:1'],
            'data.*.size_id' => [
                'nullable',
                'integer',
                'exists:sizes,id',
                Rule::requiredIf(function () {
                    foreach ($this->data as $item) {
                        $itemHasSizes = \App\Models\Item::find($item['item_id'])->sizes()->exists();
                        if ($itemHasSizes && !isset($item['size_id'])) {
                            return true;
                        }
                    }
                    return false;
                })
            ],
            'data.*.toppings.*.topping_id' => [
                'nullable',
                'exists:toppings,id',
                'integer',
                Rule::requiredIf(function () {
                    foreach ($this->data as $item) {
                        $itemHasToppings = \App\Models\Item::find($item['item_id'])->toppings()->exists();
                        if ($itemHasToppings && (!isset($item['toppings']) || empty($item['toppings']))) {
                            return true;
                        }
                    }
                    return false;
                })
            ],
            'data.*.components.*.component_id' => [
                'nullable',
                'exists:components,id',
                'integer',
                Rule::requiredIf(function () {
                    foreach ($this->data as $item) {
                        $itemHasComponents = \App\Models\Item::find($item['item_id'])->components()->exists();
                        if ($itemHasComponents && (!isset($item['components']) || empty($item['components']))) {
                            return true;
                        }
                    }
                    return false;
                })
            ],
            'isDelivery' => ['nullable'],
            'url' => ['nullable'],
            'longitude' => ['nullable'],
            'latitude' => ['nullable'],
            'address_id' => ['nullable', Rule::exists('addresses', 'id')->whereNull('deleted_at')],
            'friend_address' => ['nullable'],
            'code' => [
                'nullable',
                Rule::exists('coupons', 'code')
                    ->where('is_active', 1)
                    ->where('restaurant_id', $this->user()->restaurant_id)
                    ->where(function ($query) {
                        $query->whereDate('from_date', '<=', now())
                            ->whereDate('to_date', '>=', now());
                    })
            ],
            'delivery_price' => ['nullable'],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
}
