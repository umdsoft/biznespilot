<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test admin user with super_admin role
        $admin = User::firstOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'Test Admin',
                'email' => 'admin@biznespilot.uz',
                'phone' => '+998901234567',
                'password' => Hash::make('password'),
            ]
        );

        // Assign super_admin role for platform administration
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        // Create a regular test user
        $user = User::firstOrCreate(
            ['login' => 'user1'],
            [
                'name' => 'Ali Valiyev',
                'email' => 'ali@example.uz',
                'phone' => '+998901111111',
                'password' => Hash::make('password'),
            ]
        );
    }
}
