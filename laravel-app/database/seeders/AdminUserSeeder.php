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
        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();
        $adminRole = Role::where('name', 'ADMIN')->first();

        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'superadmin@starmaxltd.com');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD', 'SuperAdmin123!');
        $email = env('ADMIN_EMAIL', 'admin@starmaxltd.com');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');

        User::updateOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'first_name' => env('SUPER_ADMIN_FIRST_NAME', 'Super'),
                'last_name' => env('SUPER_ADMIN_LAST_NAME', 'Admin'),
                'password' => Hash::make($superAdminPassword),
                'role_id' => $superAdminRole?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'System Admin'),
                'first_name' => env('ADMIN_FIRST_NAME', 'System'),
                'last_name' => env('ADMIN_LAST_NAME', 'Admin'),
                'password' => Hash::make($password),
                'role_id' => $adminRole?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
