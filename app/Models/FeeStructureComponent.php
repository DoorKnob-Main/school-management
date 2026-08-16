<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructureComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_structure_id',
        'fee_component_type_id',
        'amount',
    ];

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function componentType()
    {
        return $this->belongsTo(FeeComponentType::class, 'fee_component_type_id');
    }
}
