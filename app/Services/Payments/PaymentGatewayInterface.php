<?php

namespace App\Services\Payment;

interface PaymentGetwayInterface
{
    public function makeInvoice($session_id, $InvoiceValue);

    public function charge(array $data);
}
