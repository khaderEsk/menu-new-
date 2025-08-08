<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddRequest extends FormRequest
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
        if(request()->has('category_id'))
        {
            return [
                'name_en' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $categoryId = request()->category_id;
                        $restaurantId = auth()->user()->restaurant_id;

                        $exists = Category::where('restaurant_id', $restaurantId)
                            ->where('category_id', $categoryId)
                            ->whereHas('translations', function ($query) use ($value) {
                                $query->where('name', $value);
                            })
                            ->count();



                        if ($exists != 0) {
                            $fail(trans('locale.nameEnAlreadyExists'));
                        }
                    },
                ],

                'name_ar' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $categoryId = request()->category_id;
                        $restaurantId = auth()->user()->restaurant_id;


                        $exists = Category::where('restaurant_id', $restaurantId)
                            ->where('category_id', $categoryId)
                            ->whereHas('translations', function ($query) use ($value) {
                                $query->where('name', $value);
                            })
                            ->count();

                        if ($exists != 0) {
                            $fail(trans('locale.nameArAlreadyExists'));
                        }
                    },
                ],

                'category_id' => [
                    'nullable',
                    Rule::exists('categories', 'id')->whereNull('deleted_at'),
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $parentCategory = Category::find($value);
                            if ($parentCategory && $parentCategory->hasItems()) {
                                $fail(trans('locale.cannotAddSubcategoryToCategory'));
                            }
                        }
                    },
                ],

                'image' => ['nullable', 'image'],
            ];
        }
        else
        {
            return [
                'name_en' => ['required',Rule::unique('category_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)],
                'name_ar' => ['required',Rule::unique('category_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)],
                // 'category_id' => ['nullable',Rule::exists('categories','id')->whereNull('deleted_at')->whereNull('category_id')],

                'category_id' => [
                    'nullable',
                    Rule::exists('categories','id')->whereNull('deleted_at'),
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $parentCategory = Category::find($value);
                            if ($parentCategory && $parentCategory->hasItems()) {
                                $fail(trans('locale.cannotAddSubcategoryToCategory'));
                            }
                        }
                    },
                ],

                'image' => ['nullable','image'],
            ];

        }
    }
}
