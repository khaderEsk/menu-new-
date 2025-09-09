<?php

namespace App\Http\Requests\Item;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'id' => ['nullable', Rule::exists('items', 'id')->whereNull('deleted_at')],
            'item_id' => ['nullable', Rule::exists('items', 'id')->whereNull('item_id')],
            'name_en' => ['required', Rule::unique('item_translations', 'name')->where('restaurant_id', auth()->user()->restaurant_id)->where('category_id', $this->category_id)->ignore($this->id, "item_id")],
            'name_ar' => ['required', Rule::unique('item_translations', 'name')->where('restaurant_id', auth()->user()->restaurant_id)->where('category_id', $this->category_id)->ignore($this->id, "item_id")],
            'description_en' => ['nullable', 'max:1700'],
            'description_ar' => ['nullable', 'max:1700'],
            'price' => ['nullable'],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->whereNull('deleted_at')->where('restaurant_id', auth()->user()->restaurant_id),
                function ($attribute, $value, $fail) {
                    $category = Category::find($value);
                    if ($category && $category->hasSubcategories()) {
                        $fail(trans('locale.theSelectedCategoryHasSubcategories'));
                    }
                },
            ],
            'image' => ['nullable', 'image'],
            'icon' => ['nullable', 'image'],
            'is_panorama' => ['required'],
            'is_size' => ['boolean'],
            'sizes' => ['array', 'required_if:is_size,1'],
            'sizes.*.name_en' => ['string', 'max:50', 'required_if:is_size,1'],
            'sizes.*.name_ar' => ['string', 'max:50', 'required_if:is_size,1'],
            'sizes.*.price' => ['numeric', 'required_if:is_size,1'],
            'sizes.*.image' => ['nullable', 'image', 'mimes:png,jpg'],
            'sizes.*.description_ar' => ['nullable', 'string', 'max:1700'],
            'sizes.*.description_en' => ['nullable', 'string', 'max:1700'],
            'is_component' => ['boolean'],
            'components' => ['array', 'required_if:is_component,1'],
            'components.*.name_en' => ['required_if:is_component,1', 'max:50'],
            'components.*.name_ar' => ['required_if:is_component,1', 'max:50'],
            'components.*.status' => ['required_if:is_component,1', 'boolean'],
            'is_topping' => ['required', 'boolean'],
            'toppings' => ['array', 'required_if:is_topping,1'],
            'toppings.*.name_en' => ['string', 'max:50', 'required_if:is_topping,1'],
            'toppings.*.name_ar' => ['string', 'max:50', 'required_if:is_topping,1'],
            'toppings.*.price' => ['numeric', 'required_if:is_topping,1'],
            'toppings.*.icon' => ['nullable', 'image'],
            'is_nutrition' => ['required', 'boolean'],
            'nutrition' => ['nullable', 'required_if:is_nutrition,1'],
            'nutrition.amount' => ['numeric', 'required_if:is_nutrition,1'],
            'nutrition.unit' => [Rule::in(['g', 'ml', 'kg', 'L']), 'required_if:is_nutrition,1'],
            'nutrition.kcal' => ['nullable', 'numeric'],
            'nutrition.protein' => ['nullable', 'numeric'],
            'nutrition.fat' => ['nullable', 'numeric'],
            'nutrition.carbs' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string'],
        ];
    }
}
