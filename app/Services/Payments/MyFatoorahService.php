<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;


class MyFatoorahService implements PaymentGetwayInterface
{

    public function makeInvoice($session_id, $invoice) {}

    public function charge(array $data) {}
}
