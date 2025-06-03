<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            //User
            ['name' => 'Create Users', 'slug' => 'create-users'],
            ['name' => 'View Users', 'slug' => 'view-users'],
            ['name' => 'Show Users', 'slug' => 'show-users'],
            ['name' => 'Update Users', 'slug' => 'update-users'],
            ['name' => 'delete Users', 'slug' => 'delete-users'],

            //Role  
            ['name' => 'Create Roles', 'slug' => 'create-roles'],
            ['name' => 'View Roles', 'slug' => 'view-roles'],
            ['name' => 'Show Roles', 'slug' => 'show-roles'],
            ['name' => 'Update Roles', 'slug' => 'update-roles'],
            ['name' => 'Delete Roles', 'slug' => 'delete-roles'],

            //Permission
            ['name' => 'Create Permissions', 'slug' => 'create-permissions'],
            ['name' => 'View Permissions', 'slug' => 'view-permissions'],
            ['name' => 'Show Permissions', 'slug' => 'show-permissions'],
            ['name' => 'Update Permissions', 'slug' => 'update-permissions'],
            ['name' => 'Delete Permissions', 'slug' => 'delete-permissions'],

            //Dashboard
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard'],

            //Type Of Health Facility
            ['name' => 'Create Type Of Health Facility', 'slug' => 'create-type-of-health-facility'],
            ['name' => 'View Type Of Health Facility', 'slug' => 'view-type-of-health-facility'],
            ['name' => 'Show Type Of Health Facility', 'slug' => 'show-type-of-health-facility'],
            ['name' => 'Update Type Of Health Facility', 'slug' => 'update-type-of-health-facility'],
            ['name' => 'Delete Type Of Health Facility', 'slug' => 'delete-type-of-health-facility'],

            //Health Facility
            ['name' => 'Create Health Facility', 'slug' => 'create-health-facility'],
            ['name' => 'View Health Facility', 'slug' => 'view-health-facility'],
            ['name' => 'Show Health Facility', 'slug' => 'show-health-facility'],
            ['name' => 'Update Health Facility', 'slug' => 'update-health-facility'],
            ['name' => 'Delete Health Facility', 'slug' => 'delete-health-facility'],

            //Medical Device Category
            ['name' => 'Create Medical Device Category', 'slug' => 'create-medical-device-category'],
            ['name' => 'View Medical Device Category', 'slug' => 'view-medical-device-category'],
            ['name' => 'Show Medical Device Category', 'slug' => 'show-medical-device-category'],
            ['name' => 'Update Medical Device Category', 'slug' => 'update-medical-device-category'],
            ['name' => 'Delete Medical Device Category', 'slug' => 'delete-medical-device-category'],

            //Medical Device
            ['name' => 'Create Medical Device', 'slug' => 'create-medical-device'],
            ['name' => 'View Medical Device', 'slug' => 'view-medical-device'],
            ['name' => 'Show Medical Device', 'slug' => 'show-medical-device'],
            ['name' => 'Update Medical Device', 'slug' => 'update-medical-device'],
            ['name' => 'Delete Medical Device', 'slug' => 'delete-medical-device'],

            //Region
            ['name' => 'Create Region', 'slug' => 'create-region'],
            ['name' => 'View Region', 'slug' => 'view-region'],
            ['name' => 'Show Region', 'slug' => 'show-region'],
            ['name' => 'Update Region', 'slug' => 'update-region'],
            ['name' => 'Delete Region', 'slug' => 'delete-region'],

            //Division
            ['name' => 'Crete Division', 'slug' => 'create-division'],
            ['name' => 'View Division', 'slug' => 'view-division'],
            ['name' => 'Show Division', 'slug' => 'show-division'],
            ['name' => 'Update Division', 'slug' => 'update-division'],
            ['name' => 'Delete Division', 'slug' => 'delete-division'],

            //Position
            ['name' => 'Create Position', 'slug' => 'create-position'],
            ['name' => 'View Position', 'slug' => 'view-position'],
            ['name' => 'Show Position', 'slug' => 'show-position'],
            ['name' => 'Update Position', 'slug' => 'update-position'],
            ['name' => 'Delete Position', 'slug' => 'delete-position'],

            //Completion Status
            ['name' => 'Create Completion Status', 'slug' => 'create-completion-status'],
            ['name' => 'View Completion Status', 'slug' => 'view-completion-status'],
            ['name' => 'Show Completion Status', 'slug' => 'show-completion-status'],
            ['name' => 'Update Completion Status', 'slug' => 'update-completion-status'],
            ['name' => 'Delete Completion Status', 'slug' => 'delete-completion-status'],


        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Super admin with all permissions',
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Admin with limited permissions',
        ]);

        $employeeRole = Role::create([
            'name' => 'Employee',
            'slug' => 'employee',
            'description' => 'Employee user',
        ]);

        // Assign permissions to roles
        $superAdminRole->permissions()->attach(Permission::all());

        $adminRole->permissions()->attach(
            Permission::whereIn('slug', [
                'view-dashboard',
                'create-users',
                'view-users',
                'update-users',
                'delete-users',
            ])->get()
        );

        $employeeRole->permissions()->attach(
            Permission::where('slug', [
                'view-dashboard',
                'view-users',   
                'update-users',
            ])->get()
        );

        // Create admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
            'remember_token' => Str::random(10),
        ]);

        // Create manager user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'remember_token' => Str::random(10),
        ]);

        // Create regular user
        User::create([
            'name' => 'Employee User',
            'email' => 'employee@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => $employeeRole->id,
            'remember_token' => Str::random(10),
        ]);
    }
}
