<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
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
        // return [
        //     'name' => ['required','max:20'],
        //     'user_name' => ['required','unique:admins,user_name','min:2','max:20'],
        //     'password' => ['required','min:5','max:25'],
        //     'mobile' => ['required','min:10','max:15'],
        //     'type' => ['nullable'],
        //     'role' => ['required'],
        //     'permission' => ['required'],
        //     'fcm_token' => ['nullable'],
        // ];

        return [
            'name' => ['required', 'max:20'],
            'user_name' => ['required', 'unique:admins,user_name', 'min:2', 'max:20'],
            'password' => ['required', 'min:8', 'max:25'],
            'mobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/', 'unique:admins,mobile'],
            'role' => ['required'],
            'restaurant_id' => ['nullable'],
            'email' => ['required', 'email'],
            'type_id' => ['required_if:role,موظف,employee', 'exists:types,id'],
            'category' => [
                'required_if:type_id,4,8',
                'array',
                Rule::requiredIf(function () {
                    return in_array($this->input('type_id'), [4, 8]);
                }),
            ],
            // 'permission' => ['required_unless:role,أدمن,admin'
            // function($attribute, $value, $fail) {
            //     if ($this->input('role') === 'أدمن' || $this->input('role') === 'Admin') {
            //         return;
            //     }

            //     if (is_array($value)) {
            //         foreach ($value as $val) {
            //             if (is_null($val)) {
            //                 $fail('The ' . $attribute . ' field has invalid values.');
            //             }
            //         }
            //     } else {
            //         if (is_null($value)) {
            //             $fail('The ' . $attribute . ' field is required.');
            //         }
            //     }

            // }
            // ],
            'fcm_token' => ['nullable'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // if ($this->input('role') === 'Data entry' || $this->input('role') === 'مدخل بيانات') {
            //     $validator->errors()->add('role', trans('locale.dataEntry'));

            // }
            if ($this->input('role') === 'Admin' || $this->input('role') === 'أدمن') {
                $restaurantId = $this->restaurant_id;

                $existingAdmin = Admin::where('restaurant_id', $restaurantId)
                    ->role('admin')
                    ->count();

                if ($existingAdmin != 0) {
                    $validator->errors()->add('role', trans('locale.theRestaurantHasAdmin'));
                }
            }
        });
    }
}
