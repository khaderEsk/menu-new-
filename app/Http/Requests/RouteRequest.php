<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lon' => 'required|numeric|between:-180,180',
            'end_lat' => 'required|numeric|between:-90,90',
            'end_lon' => 'required|numeric|between:-180,180',
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'start_lat.required' => 'Start latitude is required.',
            'start_lon.required' => 'Start longitude is required.',
            'end_lat.required' => 'End latitude is required.',
            'end_lon.required' => 'End longitude is required.',
        ];
    }
}