<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        $admin = User::where('login', 'admin')->first();

        if (!$admin) {
            $admin = new User();
            $admin->id = Str::uuid()->toString();
            $admin->login = 'admin';
            $admin->name = 'Super Admin';
            $admin->email = 'admin@biznespilot.uz';
            $admin->phone = '+998901234567';
            $admin->password = Hash::make('admin123');
            $admin->save();
        }

        // Assign super_admin role for platform administration
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }
    }
}
