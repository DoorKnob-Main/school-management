<?php

namespace App\Repositories;

use App\Interfaces\FeeStructureInterface;
use App\Models\FeeStructure;
use App\Models\FeeInstallment;
use App\Models\FeeComponentType;
use App\Models\FeeStructureComponent;
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;

class FeeStructureRepository implements FeeStructureInterface
{
    public function getAllBySession($sessionId)
    {
        return FeeStructure::with(['schoolClass', 'installments', 'components.componentType'])
            ->where('session_id', $sessionId)
            ->get();
    }

    /**
     * The single fee structure that applies to a class: a class-specific
     * structure takes priority, falling back to the session's default
     * (class_id null) structure. Never both at once — a class should have
     * exactly one applicable fee structure, not stacked charges.
     */
    public function getForClass($sessionId, $classId)
    {
        $structure = FeeStructure::with(['installments', 'components.componentType'])
            ->where('session_id', $sessionId)
            ->where('class_id', $classId)
            ->first();

        if (!$structure) {
            $structure = FeeStructure::with(['installments', 'components.componentType'])
                ->where('session_id', $sessionId)
                ->whereNull('class_id')
                ->first();
        }

        return $structure ? collect([$structure]) : collect();
    }

    public function store($data)
    {
        $classId = !empty($data['class_id']) ? $data['class_id'] : null;
        $duplicate = FeeStructure::where('session_id', $data['session_id'])
            ->where('class_id', $classId)
            ->exists();

        if ($duplicate) {
            $label = $classId ? 'this class' : 'the default (all classes)';
            throw new \Exception("A fee structure already exists for {$label} this session. Edit or delete it before creating another — a class can only have one active fee structure at a time.");
        }

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

            // Components (breakup) are the source of truth for total_amount
            // when supplied — they represent what the fee is actually made of.
            $componentsTotal = $this->persistComponents($feeStructure, $data['components'] ?? []);
            if ($componentsTotal !== null) {
                $feeStructure->update(['total_amount' => $componentsTotal]);
            }

            // Backfill: assign this structure to students already enrolled in
            // the target class (or all students of the session for a default
            // structure) so existing students aren't left unassigned.
            $studentsQuery = \App\Models\Promotion::where('session_id', $feeStructure->session_id);
            if ($feeStructure->class_id) {
                $studentsQuery->where('class_id', $feeStructure->class_id);
            }
            foreach ($studentsQuery->get(['student_id', 'class_id']) as $enrollment) {
                $this->assignToStudent($enrollment->student_id, $feeStructure->session_id, $enrollment->class_id, $feeStructure->id);
            }

            return $feeStructure;
        });
    }

    public function findById($id)
    {
        return FeeStructure::with(['schoolClass', 'installments', 'components.componentType'])->findOrFail($id);
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

            if (isset($data['components'])) {
                $componentsTotal = $this->persistComponents($feeStructure, $data['components']);
                if ($componentsTotal !== null) {
                    $feeStructure->update(['total_amount' => $componentsTotal]);
                }
            }

            return $feeStructure;
        });
    }

    /**
     * Replace a fee structure's component breakup and return the computed
     * total (fixed components summed, percentage components applied on top
     * of the fixed subtotal). Returns null if no components were supplied,
     * so callers can leave total_amount untouched (lump-sum/installment path).
     */
    private function persistComponents(FeeStructure $feeStructure, array $components)
    {
        $feeStructure->components()->delete();

        if (empty($components)) {
            return null;
        }

        $types = FeeComponentType::whereIn('id', collect($components)->pluck('fee_component_type_id'))
            ->get()->keyBy('id');

        $fixedSubtotal = 0;
        foreach ($components as $component) {
            $type = $types->get($component['fee_component_type_id']);
            if ($type && !$type->isPercentage()) {
                $fixedSubtotal += floatval($component['amount']);
            }
        }

        $total = $fixedSubtotal;
        foreach ($components as $component) {
            $type = $types->get($component['fee_component_type_id']);
            if (!$type) {
                continue;
            }

            FeeStructureComponent::create([
                'fee_structure_id' => $feeStructure->id,
                'fee_component_type_id' => $type->id,
                'amount' => floatval($component['amount']),
            ]);

            if ($type->isPercentage()) {
                $total += $fixedSubtotal * (floatval($component['amount']) / 100);
            }
        }

        return $total;
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
                'fee_structure_id' => $feeStructureId,
            ],
            [
                'class_id' => $classId,
            ]
        );
    }

    public function assignApplicableStructuresToStudent($studentId, $sessionId, $classId)
    {
        $structures = $this->getForClass($sessionId, $classId);
        foreach ($structures as $structure) {
            $this->assignToStudent($studentId, $sessionId, $classId, $structure->id);
        }
        return $structures;
    }
}
