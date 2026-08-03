<?php

namespace App\Models;

use App\Models\Mark;
use App\Models\StudentParentInfo;
use App\Models\StudentAcademicInfo;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'gender',
        'nationality',
        'phone',
        'address',
        'address2',
        'city',
        'zip',
        'photo',
        'birthday',
        'religion',
        'blood_type',
        'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the parent_info.
     */
    public function parent_info()
    {
        return $this->hasOne(StudentParentInfo::class, 'student_id', 'id');
    }

    /**
     * Get the academic_info.
     */
    public function academic_info()
    {
        return $this->hasOne(StudentAcademicInfo::class, 'student_id', 'id');
    }

    /**
     * Get the marks.
     */
    public function marks()
    {
        return $this->hasMany(Mark::class, 'student_id', 'id');
    }

    /**
     * Determine if the user is a Super Admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->hasRole('super_admin');
    }

    /**
     * Determine if the user is currently impersonating another role.
     *
     * @return bool
     */
    public function isImpersonating(): bool
    {
        return $this->isSuperAdmin() && session()->has('impersonated_role');
    }

    /**
     * Get the effective role of the user (accounting for impersonation).
     *
     * @return string
     */
    public function getEffectiveRoleAttribute(): string
    {
        if ($this->isSuperAdmin() && session()->has('impersonated_role')) {
            return session('impersonated_role');
        }

        return $this->role;
    }

    /**
     * Determine if the user is an admin or super admin.
     *
     * @return bool
     */
    public function isAdminOrSuperAdmin(): bool
    {
        $role = $this->effective_role;
        return $role === 'admin' || $role === 'super_admin' || $this->isSuperAdmin();
    }
}
