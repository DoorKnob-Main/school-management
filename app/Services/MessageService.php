<?php

namespace App\Services;

use App\Models\User;
use App\Models\SchoolSession;
use Illuminate\Support\Facades\Log;

/**
 * MessageService
 * Abstraction layer for sending SMS, WhatsApp, and email messages.
 * Future-ready for third-party gateway integrations (Twilio, MSG91, WhatsApp Cloud API, etc.)
 */
class MessageService
{
    /**
     * Replace message template placeholders dynamically.
     * 
     * Supported Placeholders:
     * {student_name}, {father_name}, {mother_name}, {class_name}, {section_name},
     * {due_amount}, {paid_amount}, {receipt_number}, {school_name}, {due_date}
     */
    public function formatMessage(string $template, array $data): string
    {
        $placeholders = [
            '{student_name}'   => $data['student_name'] ?? '',
            '{father_name}'    => $data['father_name'] ?? '',
            '{mother_name}'    => $data['mother_name'] ?? '',
            '{class_name}'     => $data['class_name'] ?? '',
            '{section_name}'   => $data['section_name'] ?? '',
            '{due_amount}'     => isset($data['due_amount']) ? '₹' . number_format($data['due_amount'], 2) : '',
            '{paid_amount}'    => isset($data['paid_amount']) ? '₹' . number_format($data['paid_amount'], 2) : '',
            '{receipt_number}' => $data['receipt_number'] ?? '',
            '{school_name}'    => $data['school_name'] ?? config('app.name', 'Unifiedtransform'),
            '{due_date}'       => $data['due_date'] ?? date('Y-m-d'),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Send Fee Reminder message to Parent.
     */
    public function sendReminder(string $phone, string $message, string $channel = 'SMS'): array
    {
        // Log notification attempt
        Log::info("Sending Fee Reminder via {$channel} to {$phone}: {$message}");

        // TODO: Integrate SMS / WhatsApp gateway provider here.
        // Example: Twilio, MSG91, Meta WhatsApp Cloud API.

        return [
            'success' => true,
            'channel' => $channel,
            'phone'   => $phone,
            'response_message' => 'Message queued / sent successfully.',
        ];
    }

    /**
     * Send Payment Confirmation Message to Parent upon fee collection.
     * (Future feature - Service abstraction ready for Event Listener hook)
     */
    public function sendPaymentConfirmation(array $paymentData): array
    {
        $template = "Dear Parent,\n"
            . "We have successfully received {paid_amount} for {student_name}.\n"
            . "Receipt Number: {receipt_number}\n"
            . "Remaining Due: {due_amount}\n"
            . "Thank You.";

        $message = $this->formatMessage($template, $paymentData);
        $phone = $paymentData['father_phone'] ?? ($paymentData['phone'] ?? '');

        Log::info("Sending Payment Confirmation to {$phone}: {$message}");

        // TODO: Bind gateway API call for payment receipts.

        return [
            'success' => true,
            'message' => $message,
            'response_message' => 'Payment confirmation message dispatched.',
        ];
    }
}
