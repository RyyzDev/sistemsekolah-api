<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Admin', 'Teacher', 'Student', 'Parent'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $permissions = [
            'manage-academic-years',
            'manage-semesters',
            'manage-classrooms',
            'manage-subjects',
            'manage-students',
            'manage-teachers',
            'manage-parents',
            'manage-attendances',
            'manage-grades',
            'view-attendances',
            'view-grades',
            'view-report-cards',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $adminRole = Role::findByName('Admin');
        $adminRole->givePermissionTo(Permission::all());

        $teacherRole = Role::findByName('Teacher');
        $teacherRole->givePermissionTo([
            'manage-attendances',
            'manage-grades',
            'view-attendances',
            'view-grades',
        ]);

        $studentRole = Role::findByName('Student');
        $studentRole->givePermissionTo([
            'view-attendances',
            'view-grades',
            'view-report-cards',
        ]);

        $parentRole = Role::findByName('Parent');
        $parentRole->givePermissionTo([
            'view-attendances',
            'view-grades',
            'view-report-cards',
        ]);
    }
}