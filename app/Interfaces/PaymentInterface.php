<?php

namespace App\Interfaces;

interface PaymentInterface
{
    public function getStudentFeeSummary($studentId, $sessionId);
    public function recordPayment($data);
    public function getPaymentHistory($studentId, $sessionId);
    public function getPaymentsBySession($sessionId, array $filters = []);
    public function findPaymentById($id);
    public function generateReceiptNumber();
}
