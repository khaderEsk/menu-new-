<?php

namespace App\Http\Controllers;

use App\Services\PaymentMethodService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public PaymentMethodService $paymentMethod;
    public function __construct(PaymentMethodService $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function index()
    {
        return $this->paymentMethod->index();
    }

    public function store(Request $request)
    {
       $data = $request->validate([
            'name' => ['required' , 'string'],
       ]);

       return $this->paymentMethod->create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }


    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => ['nullable' , 'string'],
            'isActive' => ['nullable' , 'boolean'],
        ]);

        return $this->paymentMethod->create($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
