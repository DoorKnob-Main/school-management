<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeComponentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'calc_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function structureComponents()
    {
        return $this->hasMany(FeeStructureComponent::class);
    }

    public function isPercentage()
    {
        return $this->calc_type === 'percentage';
    }
}
