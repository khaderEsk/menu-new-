<?php

namespace App\Services\Payment;

class StripeService implements PaymentGetwayInterface
{
    public function makeInvoice($session_id, $InvoiceValue) {}

    public function charge(array $data) {}

}
