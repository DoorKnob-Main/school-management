<?php

namespace App\Interfaces;

interface FeeStructureInterface
{
    public function getAllBySession($sessionId);
    public function getForClass($sessionId, $classId);
    public function store($data);
    public function findById($id);
    public function update($id, $data);
    public function delete($id);
    public function assignToStudent($studentId, $sessionId, $classId, $feeStructureId);
}
