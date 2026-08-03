<?php

namespace App\Interfaces;

interface ExpenseInterface
{
    public function getAll(array $filters = []);
    public function store($data);
    public function findById($id);
    public function delete($id);
    public function getCategories();
}
