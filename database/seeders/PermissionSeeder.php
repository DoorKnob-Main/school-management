<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'create users', 'view users', 'edit users', 'delete users',
            'promote students',
            'create notices', 'view notices', 'edit notices', 'delete notices',
            'create events', 'view events', 'edit events', 'delete events',
            'create syllabi', 'view syllabi', 'edit syllabi', 'delete syllabi',
            'create routines', 'view routines', 'edit routines', 'delete routines',
            'create exams', 'view exams', 'delete exams',
            'create exams rule', 'view exams rule', 'edit exams rule', 'delete exams rule', 'view exams history',
            'create grading systems', 'view grading systems', 'edit grading systems', 'delete grading systems',
            'create grading systems rule', 'view grading systems rule', 'edit grading systems rule', 'delete grading systems rule',
            'take attendances', 'view attendances', 'update attendances type',
            'submit assignments', 'create assignments', 'view assignments',
            'save marks', 'view marks',
            'create school sessions',
            'create semesters', 'view semesters', 'edit semesters',
            'assign teachers',
            'create courses', 'view courses', 'edit courses',
            'view academic settings', 'update marks submission window', 'update browse by session',
            'create classes', 'view classes', 'edit classes',
            'create sections', 'view sections', 'edit sections',
            'view payments', 'collect fees', 'manage expenses', 'view reports', 'view transactions', 'send fee reminder',
            'manage biometric devices', 'sync biometric attendance', 'view biometric logs', 'manage student leaves', 'approve student leaves', 'correct attendance'
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $user = User::updateOrCreate(
            ['email' => 'admin@ut.com'],
            [
                'first_name'  => 'Hasib',
                'last_name'   => 'Mahmud',
                'password'    => Hash::make('password'),
                'gender'      => 'Male',
                'nationality' => 'Bangladeshi',
                'phone'       => '+8801700000000',
                'address'     => '123 Main St',
                'address2'    => 'Apt 1',
                'city'        => 'Dhaka',
                'zip'         => '1200',
                'role'        => 'admin',
            ]
        );

        $user->givePermissionTo($permissions);
    }
}
