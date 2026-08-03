<?php

namespace App\Services;

use App\Interfaces\ExpenseInterface;
use App\Interfaces\TransactionInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class ExpenseService
{
    protected $expenseRepository;
    protected $transactionRepository;

    public function __construct(
        ExpenseInterface $expenseRepository,
        TransactionInterface $transactionRepository
    ) {
        $this->expenseRepository = $expenseRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function createExpense(array $data)
    {
        return DB::transaction(function () use ($data) {
            $amount = floatval($data['amount']);
            if ($amount <= 0) {
                throw new Exception('Expense amount must be greater than zero.');
            }

            // Save expense
            $expense = $this->expenseRepository->store($data);

            // Save transaction automatically
            $this->transactionRepository->createExpenseTransaction($expense);

            return $expense;
        });
    }
}
