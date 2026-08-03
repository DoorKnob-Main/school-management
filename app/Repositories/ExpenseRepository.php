<?php

namespace App\Repositories;

use App\Interfaces\ExpenseInterface;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseRepository implements ExpenseInterface
{
    public function getAll(array $filters = [])
    {
        $query = Expense::with('creator');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
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
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
    }

    public function store($data)
    {
        return DB::transaction(function () use ($data) {
            return Expense::create([
                'category'         => $data['category'],
                'title'            => $data['title'],
                'amount'           => $data['amount'],
                'date'             => $data['date'],
                'payment_mode'     => $data['payment_mode'],
                'reference_number' => $data['reference_number'] ?? null,
                'description'      => $data['description'] ?? null,
                'created_by'       => $data['created_by'],
            ]);
        });
    }

    public function findById($id)
    {
        return Expense::with('creator')->findOrFail($id);
    }

    public function delete($id)
    {
        $expense = Expense::findOrFail($id);
        return $expense->delete();
    }

    public function getCategories()
    {
        return Expense::distinct()->pluck('category')->toArray();
    }
}
