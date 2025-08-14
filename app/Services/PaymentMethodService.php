<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentMethodService
{
    use ResponseTrait;

    public function index()
    {
        $user = auth()->user();
        
        if ($user->hasRole('customer')  || $user->hasRol('takeout')) {
            $payments = PaymentMethod::query()->where('isActive', false)->get();
        } else {
            $payments = PaymentMethod::all();
        }

        return $payments;
    }

    public function create(array $data)
    {
        try {

            DB::beginTransaction();

            PaymentMethod::query()->create($data);

            DB::commit();

            return $this->messageSuccessResponse('Payment Methode Created Successfully', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . ' in file ' . $e->getFile()  . ' in line ' . $e->getLine());
            return $this->messageErrorResponse('Create Payment Methode Faild', 500);
        }
    }

    public function update($id, $data)
    {

        $paymentMethod = PaymentMethod::query()->find($id);

        if ($paymentMethod == null) {
            return $this->messageErrorResponse('Payment Method Not Found', 404);
        }

        $paymentMethod->update([
            'name' => $data['name'],
            'isActive' => $data['isActive'],
        ]);

        return $this->messageSuccessResponse('Payment Method Updated Successfully', 200);
    }
}
