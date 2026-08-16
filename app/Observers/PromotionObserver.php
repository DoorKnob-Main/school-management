<?php

namespace App\Observers;

use App\Interfaces\FeeStructureInterface;
use App\Models\Promotion;

class PromotionObserver
{
    protected $feeStructureRepository;

    public function __construct(FeeStructureInterface $feeStructureRepository)
    {
        $this->feeStructureRepository = $feeStructureRepository;
    }

    public function created(Promotion $promotion)
    {
        $this->assign($promotion);
    }

    public function updated(Promotion $promotion)
    {
        if ($promotion->wasChanged('class_id') || $promotion->wasChanged('session_id')) {
            $this->assign($promotion);
        }
    }

    protected function assign(Promotion $promotion)
    {
        $this->feeStructureRepository->assignApplicableStructuresToStudent(
            $promotion->student_id,
            $promotion->session_id,
            $promotion->class_id
        );
    }
}
