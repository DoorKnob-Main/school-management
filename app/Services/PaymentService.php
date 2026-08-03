<?php

namespace App\Services;

use App\Interfaces\PaymentInterface;
use App\Interfaces\TransactionInterface;
use App\Events\PaymentCollected;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    protected $paymentRepository;
    protected $transactionRepository;

    public function __construct(
        PaymentInterface $paymentRepository,
        TransactionInterface $transactionRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function collectFee(array $data)
    {
        return DB::transaction(function () use ($data) {
            $studentId = $data['student_id'];
            $sessionId = $data['session_id'];
            $amount = floatval($data['amount']);

            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }

            // Check current fee summary and validate overpayment
            $summary = $this->paymentRepository->getStudentFeeSummary($studentId, $sessionId);
            $totalFee = floatval($summary['total_fee']);
            $remainingDue = floatval($summary['remaining_due']);

            if ($totalFee > 0) {
                if ($remainingDue <= 0) {
                    throw new Exception('All fees for this student have already been paid in full.');
                }
                if ($amount > $remainingDue) {
                    throw new Exception("Payment amount (₹" . number_format($amount, 2) . ") cannot exceed the remaining due of ₹" . number_format($remainingDue, 2) . ".");
                }
            }

            // Save payment
            $payment = $this->paymentRepository->recordPayment($data);

            // Create ledger transaction
            $sectionId = $data['section_id'] ?? null;
            $this->transactionRepository->createIncomeTransaction($payment, $sectionId);

            // Re-calculate remaining due for event notification
            $newSummary = $this->paymentRepository->getStudentFeeSummary($studentId, $sessionId);
            
            // Dispatch event for future SMS/WhatsApp notifications
            event(new PaymentCollected($payment, [
                'remaining_due' => $newSummary['remaining_due'],
            ]));

            return $payment;
        });
    }
}
