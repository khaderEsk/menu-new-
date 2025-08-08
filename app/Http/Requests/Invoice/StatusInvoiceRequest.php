<?php

namespace App\Http\Requests\Invoice;

use App\Enum\InvoiceStatus;
use App\Models\Invoice;
use App\Rules\IsValidInvoiceStatus;
use App\Traits\ResponseTrait; // <-- 1. Import your custom response trait
use Illuminate\Contracts\Validation\Validator; // <-- 2. Import the Validator contract
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException; // <-- 3. Import the Exception class
use Illuminate\Validation\Rule;

class StatusInvoiceRequest extends FormRequest
{
    use ResponseTrait; // <-- 4. Use your trait in the class

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole('superAdmin')) {
            return true;
        }

        // Get the new status the user is trying to set.
        $newStatus = InvoiceStatus::fromString($this->input('status'));

        // If the status string is invalid, fromString() will return null.
        // We return true to allow the request to proceed to the validation phase,
        // where our IsValidInvoiceStatus rule will catch the error.
        if ($newStatus === null) {
            return true;
        }

        // If the status is valid, we can now safely check the policy.
        $invoice = Invoice::findOrFail($this->input('id'));
        return $this->user()->can('updateStatus', [$invoice, $newStatus]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            // The 'status' must be a valid string defined in our Enum.
            'status' => ['required', 'string', new IsValidInvoiceStatus],
        ];

        // Dynamically set the validation rule for the 'id' field based on the user's role.
        if ($user && $user->hasRole('superAdmin')) {
            $rules['id'] = ['required', Rule::exists('invoices', 'id')];
        } else {
            $rules['id'] = ['required', Rule::exists('invoices', 'id')->where('restaurant_id', $user->restaurant_id)];
        }

        return $rules;
    }

    /**
     * ✅ ADD THIS METHOD
     * This method overrides the default validation failure response.
     * It allows you to use your custom ResponseTrait.
     */
    protected function failedValidation(Validator $validator)
    {
        // Get the first validation error message.
        $errorMessage = $validator->errors()->first();

        // Use your custom messageErrorResponse from the trait.
        // We throw an exception with your custom response.
        throw new HttpResponseException(
            $this->messageErrorResponse($errorMessage, 422)
        );
    }
}
