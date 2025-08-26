<?php

namespace App\Http\Requests\User;

use App\Models\Admin;
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
            'id' => [Rule::exists('admins','id')->whereNull('deleted_at')->where('restaurant_id',auth()->user()->restaurant_id),'required'],
            'name' => ['required_without:id','filled'],
            'user_name' => ['required_without:id','filled',Rule::unique('admins','user_name')->ignore($this->id,'id')->whereNull('deleted_at')],
            'password' => ['nullable','filled','min:8','max:25'],
            'mobile' => ['required_without:id','regex:/^\+?[0-9]{10,15}$/',Rule::unique('admins','mobile')->ignore($this->id,'id')],
            'role' => ['nullable'],
            'type_id' => ['nullable'],
            'permission' => ['nullable'],
            // 'permission' => ['required_unless:role,أدمن,admin'],
            'fcm_token' => ['nullable'],
        ];
    }

    public function withValidator($validator)
    {
        $isUpdate = $this->has('id');
        // $isCurrentUserAdmin = auth()->user()->role === 'Admin' || auth()->user()->role === 'أدمن';
        $isCurrentUserAdmin = Admin::where('id', $this->id)->role('admin')->count();
        $isAdminRole = $this->input('role') === 'admin' || $this->input('role') === 'أدمن';
        if (!$isCurrentUserAdmin == 1) {
            $validator->after(function ($validator) use ($isAdminRole) {

                if ($isAdminRole) {
                    $restaurantId = auth()->user()->restaurant_id;

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
}
