<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'ADMIN')->first();

        User::updateOrCreate(
            ['email' => 'admin@starmaxltd.com'],
            [
                'name' => 'System Admin',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make('ChangeMe123!'),
                'role_id' => $adminRole?->id,
                'is_active' => true,
            ]
        );
    }
}
