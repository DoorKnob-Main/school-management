<?php

namespace App\Listeners;

use App\Events\PaymentCollected;
use App\Services\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentNotificationListener implements ShouldQueue
{
    protected $messageService;

    /**
     * Create the event listener.
     *
     * @param MessageService $messageService
     */
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Handle the event.
     *
     * @param PaymentCollected $event
     * @return void
     */
    public function handle(PaymentCollected $event)
    {
        $payment = $event->payment;
        $extraData = $event->paymentData;

        $student = $payment->student;
        $schoolClass = $payment->schoolClass;
        $parentInfo = $student ? $student->parent_info : null;

        $data = [
            'student_name'   => $student ? ($student->first_name . ' ' . $student->last_name) : 'Student',
            'father_name'    => $parentInfo->father_name ?? 'Parent',
            'mother_name'    => $parentInfo->mother_name ?? '',
            'class_name'     => $schoolClass->name ?? '',
            'paid_amount'    => $payment->amount,
            'receipt_number' => $payment->receipt_number,
            'due_amount'     => $extraData['remaining_due'] ?? 0,
            'father_phone'   => $parentInfo->father_phone ?? ($student->phone ?? ''),
        ];

        // Future Feature: Dispatches confirmation message via MessageService
        $this->messageService->sendPaymentConfirmation($data);
    }
}
