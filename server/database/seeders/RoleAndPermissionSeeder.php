<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Vendor permissions
            'manage vendor profile',
            'view vendor dashboard',
            'manage portfolio',
            'manage availability',
            'respond to emergency',
            
            // Client permissions
            'create booking',
            'view own bookings',
            'cancel own booking',
            'trigger emergency',
            
            // Admin permissions
            'manage vendors',
            'verify vendors',
            'suspend vendors',
            'manage emergencies',
            'override backup',
            'manage categories',
            'manage users',
            'view audit log',
            'adjust reliability',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        
        // Admin role - has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Vendor role
        $vendorRole = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $vendorRole->givePermissionTo([
            'manage vendor profile',
            'view vendor dashboard',
            'manage portfolio',
            'manage availability',
            'respond to emergency',
        ]);

        // Client role
        $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $clientRole->givePermissionTo([
            'create booking',
            'view own bookings',
            'cancel own booking',
            'trigger emergency',
        ]);
    }
}
