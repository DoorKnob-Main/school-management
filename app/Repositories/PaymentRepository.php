<?php

namespace App\Repositories;

use App\Interfaces\PaymentInterface;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\StudentFee;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentRepository implements PaymentInterface
{
    public function getStudentFeeSummary($studentId, $sessionId)
    {
        $promotion = Promotion::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->first();
        $currentClassId = $promotion->class_id ?? null;

        $feeStructure = null;

        // Only consider StudentFee rows whose structure applies to the
        // student's CURRENT class (or the session default) — ignores stale
        // rows left behind from before a promotion/class change. If both a
        // class-specific and a default row exist, the class-specific one wins.
        $studentFee = StudentFee::with(['feeStructure.installments', 'feeStructure.components.componentType'])
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->whereHas('feeStructure', function ($q) use ($currentClassId) {
                $q->where('class_id', $currentClassId)->orWhereNull('class_id');
            })
            ->get()
            ->sortByDesc(function ($sf) {
                return $sf->feeStructure->class_id !== null;
            })
            ->first();

        if ($studentFee && $studentFee->feeStructure) {
            $feeStructure = $studentFee->feeStructure;
        } elseif ($promotion) {
            // No persisted assignment yet (e.g. legacy data) — resolve live.
            $feeStructure = FeeStructure::with(['installments', 'components.componentType'])
                ->where('session_id', $sessionId)
                ->where('class_id', $currentClassId)
                ->first();

            if (!$feeStructure) {
                $feeStructure = FeeStructure::with(['installments', 'components.componentType'])
                    ->where('session_id', $sessionId)
                    ->whereNull('class_id')
                    ->first();
            }
        }

        $totalFee = $feeStructure ? floatval($feeStructure->total_amount) : 0.00;

        $paidAmount = floatval(
            FeePayment::where('student_id', $studentId)
                ->where('session_id', $sessionId)
                ->sum('amount')
        );

        $remainingDue = max(0.00, round($totalFee - $paidAmount, 2));

        $status = 'No Fee Assigned';
        if ($totalFee > 0) {
            if ($paidAmount >= $totalFee) {
                $status = 'Paid';
            } elseif ($paidAmount > 0) {
                $status = 'Partial';
            } else {
                $status = 'Overdue';
            }
        }

        return [
            'fee_structure' => $feeStructure,
            'total_fee' => $totalFee,
            'paid_amount' => $paidAmount,
            'remaining_due' => $remainingDue,
            'status' => $status,
        ];
    }

    public function recordPayment($data)
    {
        return DB::transaction(function () use ($data) {
            $receiptNumber = $this->generateReceiptNumber();

            $payment = FeePayment::create([
                'receipt_number'   => $receiptNumber,
                'student_id'       => $data['student_id'],
                'session_id'       => $data['session_id'],
                'class_id'         => $data['class_id'],
                'fee_structure_id' => $data['fee_structure_id'] ?? null,
                'amount'           => $data['amount'],
                'payment_date'     => $data['payment_date'],
                'payment_mode'     => $data['payment_mode'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => $data['created_by'],
            ]);

            return $payment;
        });
    }

    public function getPaymentHistory($studentId, $sessionId)
    {
        $payments = FeePayment::with(['creator', 'feeStructure'])
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->orderBy('payment_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $summary = $this->getStudentFeeSummary($studentId, $sessionId);
        $totalFee = $summary['total_fee'];

        // Calculate running balance after each payment
        $runningPaid = 0;
        $history = [];

        foreach ($payments as $pay) {
            $runningPaid += floatval($pay->amount);
            $dueAfter = max(0, round($totalFee - $runningPaid, 2));

            $pay->remaining_due_after = $dueAfter;
            $history[] = $pay;
        }

        return $history;
    }

    public function getPaymentsBySession($sessionId, array $filters = [])
    {
        $query = FeePayment::with(['student.parent_info', 'schoolClass', 'creator'])
            ->where('session_id', $sessionId);

        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['payment_mode'])) {
            $query->where('payment_mode', $filters['payment_mode']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('payment_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('payment_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    public function findPaymentById($id)
    {
        return FeePayment::with(['student.parent_info', 'student.academic_info', 'schoolClass', 'feeStructure.installments', 'creator'])
            ->findOrFail($id);
    }

    public function generateReceiptNumber()
    {
        $datePrefix = Carbon::now()->format('Ymd');
        $prefix = "FEE-{$datePrefix}-";

        $latest = FeePayment::where('receipt_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $seq = intval(substr($latest->receipt_number, -5)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
