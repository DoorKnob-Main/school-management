<?php

namespace App\Repositories;

use App\Interfaces\TransactionInterface;
use App\Models\Transaction;
use App\Models\FeePayment;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionInterface
{
    public function getFilteredTransactions(array $filters = [])
    {
        $query = Transaction::with(['student', 'schoolClass', 'section', 'expense', 'feePayment', 'creator']);

        if (!empty($filters['session_id'])) {
            $query->where(function($q) use ($filters) {
                $q->where('session_id', $filters['session_id'])
                  ->orWhereNull('session_id');
            });
        }

        if (!empty($filters['class_id'])) {
            if (is_array($filters['class_id'])) {
                $query->whereIn('class_id', $filters['class_id']);
            } else {
                $query->where('class_id', $filters['class_id']);
            }
        }

        if (!empty($filters['section_id'])) {
            $query->where('section_id', $filters['section_id']);
        }

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['payment_mode'])) {
            $query->where('payment_mode', $filters['payment_mode']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
                  })
                  ->orWhereHas('expense', function ($eq) use ($search) {
                      $eq->where('title', 'like', "%{$search}%")
                         ->orWhere('category', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
    }

    public function createIncomeTransaction($payment, $sectionId = null)
    {
        return Transaction::create([
            'transaction_type' => 'income',
            'fee_payment_id'   => $payment->id,
            'student_id'       => $payment->student_id,
            'session_id'       => $payment->session_id,
            'class_id'         => $payment->class_id,
            'section_id'       => $sectionId,
            'amount'           => $payment->amount,
            'payment_mode'     => $payment->payment_mode,
            'reference_number' => $payment->receipt_number,
            'date'             => $payment->payment_date,
            'created_by'       => $payment->created_by,
        ]);
    }

    public function createExpenseTransaction($expense)
    {
        return Transaction::create([
            'transaction_type' => 'expense',
            'expense_id'       => $expense->id,
            'amount'           => $expense->amount,
            'payment_mode'     => $expense->payment_mode,
            'reference_number' => $expense->reference_number ?? ('EXP-' . $expense->id),
            'date'             => $expense->date,
            'created_by'       => $expense->created_by,
        ]);
    }

    public function getAnalyticsSummary(array $filters = [])
    {
        $transactions = $this->getFilteredTransactions($filters);

        $totalCollection = $transactions->where('transaction_type', 'income')->sum('amount');
        $totalExpenses = $transactions->where('transaction_type', 'expense')->sum('amount');
        $netBalance = $totalCollection - $totalExpenses;
        $transactionCount = $transactions->count();

        return [
            'total_collection'  => $totalCollection,
            'total_expenses'    => $totalExpenses,
            'net_balance'       => $netBalance,
            'transaction_count' => $transactionCount,
        ];
    }
}
