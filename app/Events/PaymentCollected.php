<?php

namespace App\Events;

use App\Models\FeePayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCollected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $paymentData;

    /**
     * Create a new event instance.
     *
     * @param FeePayment $payment
     * @param array $paymentData Extra contextual information (e.g. remaining_due, father_phone)
     */
    public function __construct(FeePayment $payment, array $paymentData = [])
    {
        $this->payment = $payment;
        $this->paymentData = $paymentData;
    }
}
