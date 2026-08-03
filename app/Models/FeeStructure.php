<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'session_id',
        'class_id',
        'total_amount',
        'description',
    ];

    public function session()
    {
        return $this->belongsTo(SchoolSession::class, 'session_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function installments()
    {
        return $this->hasMany(FeeInstallment::class, 'fee_structure_id');
    }

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class, 'fee_structure_id');
    }
}
