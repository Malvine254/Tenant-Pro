<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'ADMIN', 'description' => 'System administrator'],
            ['name' => 'LANDLORD', 'description' => 'Property owner/manager'],
            ['name' => 'TENANT', 'description' => 'Tenant user'],
            ['name' => 'CARETAKER', 'description' => 'Maintenance/caretaker staff'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
