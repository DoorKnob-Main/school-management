<?php

namespace App\Repositories;

use App\Interfaces\FeeStructureInterface;
use App\Models\FeeStructure;
use App\Models\FeeInstallment;
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;

class FeeStructureRepository implements FeeStructureInterface
{
    public function getAllBySession($sessionId)
    {
        return FeeStructure::with(['schoolClass', 'installments'])
            ->where('session_id', $sessionId)
            ->get();
    }

    public function getForClass($sessionId, $classId)
    {
        return FeeStructure::with(['installments'])
            ->where('session_id', $sessionId)
            ->where(function ($query) use ($classId) {
                $query->where('class_id', $classId)
                    ->orWhereNull('class_id');
            })
            ->get();
    }

    public function store($data)
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = 0;
            if (isset($data['installments']) && is_array($data['installments'])) {
                foreach ($data['installments'] as $inst) {
                    $totalAmount += floatval($inst['amount'] ?? 0);
                }
            } else {
                $totalAmount = floatval($data['total_amount'] ?? 0);
            }

            $feeStructure = FeeStructure::create([
                'name' => $data['name'],
                'session_id' => $data['session_id'],
                'class_id' => !empty($data['class_id']) ? $data['class_id'] : null,
                'total_amount' => $totalAmount,
                'description' => $data['description'] ?? null,
            ]);

            if (isset($data['installments']) && is_array($data['installments'])) {
                foreach ($data['installments'] as $inst) {
                    if (!empty($inst['name']) && floatval($inst['amount']) > 0) {
                        FeeInstallment::create([
                            'fee_structure_id' => $feeStructure->id,
                            'name' => $inst['name'],
                            'due_date' => $inst['due_date'] ?? null,
                            'amount' => floatval($inst['amount']),
                        ]);
                    }
                }
            }

            return $feeStructure;
        });
    }

    public function findById($id)
    {
        return FeeStructure::with(['schoolClass', 'installments'])->findOrFail($id);
    }

    public function update($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $feeStructure = FeeStructure::findOrFail($id);

            $totalAmount = 0;
            if (isset($data['installments']) && is_array($data['installments'])) {
                foreach ($data['installments'] as $inst) {
                    $totalAmount += floatval($inst['amount'] ?? 0);
                }
            } else {
                $totalAmount = floatval($data['total_amount'] ?? $feeStructure->total_amount);
            }

            $feeStructure->update([
                'name' => $data['name'],
                'class_id' => !empty($data['class_id']) ? $data['class_id'] : null,
                'total_amount' => $totalAmount,
                'description' => $data['description'] ?? null,
            ]);

            if (isset($data['installments']) && is_array($data['installments'])) {
                $feeStructure->installments()->delete();
                foreach ($data['installments'] as $inst) {
                    if (!empty($inst['name']) && floatval($inst['amount']) > 0) {
                        FeeInstallment::create([
                            'fee_structure_id' => $feeStructure->id,
                            'name' => $inst['name'],
                            'due_date' => $inst['due_date'] ?? null,
                            'amount' => floatval($inst['amount']),
                        ]);
                    }
                }
            }

            return $feeStructure;
        });
    }

    public function delete($id)
    {
        $feeStructure = FeeStructure::findOrFail($id);
        return $feeStructure->delete();
    }

    public function assignToStudent($studentId, $sessionId, $classId, $feeStructureId)
    {
        return StudentFee::updateOrCreate(
            [
                'student_id' => $studentId,
                'session_id' => $sessionId,
            ],
            [
                'class_id' => $classId,
                'fee_structure_id' => $feeStructureId,
            ]
        );
    }
}
