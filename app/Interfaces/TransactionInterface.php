<?php

namespace App\Interfaces;

interface TransactionInterface
{
    public function getFilteredTransactions(array $filters = []);
    public function createIncomeTransaction($payment, $sectionId = null);
    public function createExpenseTransaction($expense);
    public function getAnalyticsSummary(array $filters = []);
}
