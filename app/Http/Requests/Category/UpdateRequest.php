<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
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
        // if(request()->has('category_id'))
        // {
            return [
                'id' => [Rule::exists('categories','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id),'required'],
                'name_en' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $categoryId = request()->category_id;
                        $restaurantId = auth()->user()->restaurant_id;
                        // $search = request('name_en');

                        // $exists = Category::where('restaurant_id', $restaurantId)
                        //     ->where('id', $categoryId)
                        //     ->where('id', "!=", $this->id)
                        //     ->whereTranslation('name', $search)
                        //     ->count();
                        $exists = Category::where('restaurant_id', $restaurantId)
                            ->where('category_id', $categoryId)
                            ->where('id', "!=", $this->id)
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
                        // $search = request('name_ar');

                        // $exists = Category::where('restaurant_id', $restaurantId)
                        //     ->where('id', $categoryId)
                        //     ->where('id', "!=", $this->id)
                        //     ->whereTranslation('name', $search)
                        //     ->count();

                        $exists = Category::where('restaurant_id', $restaurantId)
                            ->where('category_id', $categoryId)
                            ->where('id', "!=", $this->id)
                            ->whereHas('translations', function ($query) use ($value) {
                                $query->where('name', $value);
                            })
                            ->count();

                        if ($exists != 0) {
                            $fail(trans('locale.nameArAlreadyExists'));
                        }
                    },
                ],


                // ------------------------------------




                // ------------------------------------

                'category_id' => [
                    'nullable',
                    Rule::exists('categories', 'id')->whereNull('deleted_at'),
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            if ($value == $this->id) {
                                $fail(trans('locale.categoryCannotBeItself'));
                            }
                            $parentCategory = Category::find($value);
                            if ($parentCategory && $parentCategory->hasItems()) {
                                $fail(trans('locale.cannotAddSubcategoryToCategory'));
                            }
                        }
                    },
                ],

                'image' => ['nullable', 'image'],
            ];
        // }
        // else
        // {
        //     return [
        //         'id' => [Rule::exists('categories','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id),'required'],
        //         'name_en' => ['required',Rule::unique('category_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)->ignore($this->id,"category_id")],
        //         'name_ar' => ['required',Rule::unique('category_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)->ignore($this->id,"category_id")],
        //         'category_id' => [
        //             'nullable',
        //             Rule::exists('categories','id')->whereNull('deleted_at'),
        //             function ($attribute, $value, $fail) {
        //                 if ($value) {
        //                     if ($value == $this->id) {
        //                         $fail(trans('locale.categoryCannotBeItself'));
        //                     }
        //                     $parentCategory = Category::find($value);
        //                     if ($parentCategory && $parentCategory->hasItems()) {
        //                         $fail(trans('locale.cannotAddSubcategoryToCategory'));
        //                     }
        //                 }
        //             },
        //         ],

        //         'image' => ['nullable','image'],
        //     ];

        // }

        // return [
        //     'id' => [Rule::exists('categories','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id),'required'],
        //     'name_en' => ['required',Rule::unique('category_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)->ignore($this->id,"category_id")],
        //     'name_ar' => ['required',Rule::unique('category_translations', 'name')->where('restaurant_id',auth()->user()->restaurant_id)->ignore($this->id,"category_id")],
        //     'category_id' => [
        //         'nullable',
        //         // Rule::exists('categories','id')->whereNull('deleted_at')->whereNull('category_id'),
        //         Rule::exists('categories','id')->whereNull('deleted_at'),
        //         function ($attribute, $value, $fail) {
        //             if ($value) {
        //                 if ($value == $this->id) {
        //                     $fail(trans('locale.categoryCannotBeItself'));
        //                 }

        //                 $parentCategory = Category::find($value);
        //                 if ($parentCategory && $parentCategory->hasItems()) {
        //                     $fail(trans('locale.cannotAddSubcategoryToCategory'));
        //                 }
        //             }
        //         },
        //     ],
        //     'image' => ['nullable','image'],
        // ];
    }
}
